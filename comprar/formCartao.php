<?php
require_once('../settings/functions.php');
require_once('../settings/settings.php');

session_start();


if ($_POST) {
    sale_trace($_SESSION['user'],NULL,NULL,NULL,NULL,NULL,session_id(),'formCartao.php','Chamando bin','',0);
    require('validarBin.php');
    sale_trace($_SESSION['user'],NULL,NULL,NULL,NULL,NULL,session_id(),'formCartao.php','Chamando Validar Lote','',0);
    require('validarLote.php');
    sale_trace($_SESSION['user'],NULL,NULL,NULL,NULL,NULL,session_id(),'formCartao.php','Chamando Validar Assinatura','',0);
    require('verificarAssinatura.php');
    sale_trace($_SESSION['user'],NULL,NULL,NULL,NULL,NULL,session_id(),'formCartao.php','Chamando Processar dados da compra','',0);
    require('processarDadosCompra.php');
    sale_trace($_SESSION['user'],NULL,NULL,NULL,NULL,NULL,session_id(),'formCartao.php','Chamando Finalizando chamada do formCartao','',0);
} else {
    $mainConnection = mainConnection();

    // se o pedido tiver valor zero ele pode continuar se tiver um ingresso promocional
    // essa variavel nao representao o valor final, este sera recalculado no servidor
    if ($_COOKIE['total_exibicao'] == 0) {
        // meio de pagamento fixado como 885 (cd_meio_pagamento / pdv cc)
        // e variavel usuario_pdv = 1 para o javascript nao validar dads do cartao
        ?>
        <div class="container_cartoes">
            <p class="frase">Finalize seu pedido.</p>
            <br/>
            <input type="hidden" name="codCartao" value="885" />
            <input type="hidden" name="usuario_pdv" value="1" />
        </div>
        <?php

    } else {

        $query = "SELECT TOP 1 DATEDIFF(HOUR, GETDATE(), CONVERT(DATETIME, CONVERT(VARCHAR, A.DT_APRESENTACAO, 112) + ' ' + LEFT(A.HR_APRESENTACAO,2) + ':' + RIGHT(A.HR_APRESENTACAO,2) + ':00')) HORAS
                    FROM MW_RESERVA R
                    INNER JOIN MW_APRESENTACAO A ON A.ID_APRESENTACAO = R.ID_APRESENTACAO
                    WHERE R.ID_SESSION = ?
                    ORDER BY A.DT_APRESENTACAO";
        $params = array(session_id());
        $rs = executeSQL($mainConnection, $query, $params, true);
        $horas_antes_apresentacao = $rs['HORAS'];

        if(isset($_SESSION['usuario_pdv']) and $_SESSION['usuario_pdv'] == 1){
            $queryAux = " AND IN_TRANSACAO_PDV = 1 ";
        } else{
            $queryAux = " AND IN_TRANSACAO_PDV <> 1 ";
        }

        // se alguem evento tiver pagar.me ativo -----
        $query = "SELECT EP.ID_GATEWAY
                    FROM MW_EXCECAO_PAGAMENTO EP
                    INNER JOIN MW_EVENTO E ON E.ID_EVENTO = EP.ID_EVENTO
                    INNER JOIN MW_APRESENTACAO A ON A.ID_EVENTO = E.ID_EVENTO
                    INNER JOIN MW_RESERVA R ON R.ID_APRESENTACAO = A.ID_APRESENTACAO
                    WHERE R.ID_SESSION = ? GROUP BY EP.ID_GATEWAY";
        $rs = executeSQL($mainConnection, $query, $params, true);
        
        $quantidadeGateway = numRows($mainConnection, $query, $params);
        if($quantidadeGateway == 0 || $quantidadeGateway > 1){
            // gateway de pagamento padrao
            $rs['ID_GATEWAY'] = 6;// 6 = pagarme
        }


        $query = "SELECT cd_meio_pagamento, ds_meio_pagamento, nm_cartao_exibicao_site 
                  FROM mw_meio_pagamento 
                  WHERE in_ativo = 1 ". $queryAux ."
                  AND id_gateway NOT IN (SELECT id_gateway FROM mw_gateway WHERE in_exibe_usuario = 1 AND id_gateway != ?)
                  AND (qt_hr_anteced <= $horas_antes_apresentacao or qt_hr_anteced is null) ".
                      // se for ambiente de testes nao limitar a exibicao dos meios
                      ($_ENV['IS_TEST']
                        ? ''
                        // se nao for ambiente de testes exibir pagseguro apenas para teatros especificos
                        :  "and ((
                                nm_cartao_exibicao_site like '%pagseguro%'
                                and exists (select top 1 1 from mw_reserva r inner join mw_apresentacao a on a.id_apresentacao = r.ID_APRESENTACAO inner join mw_evento e on e.id_evento = a.id_evento where r.id_session = ? and e.id_base in (186,44))
                            ) or nm_cartao_exibicao_site not like '%pagseguro%')"
                      ).
                      "
                      AND id_meio_pagamento not in (
                        select id_meio_pagamento
                        from mw_reserva r
                        INNER JOIN mw_apresentacao a ON a.id_apresentacao = r.ID_APRESENTACAO
                        INNER JOIN mw_evento e ON e.id_evento = a.id_evento
                        inner JOIN mw_base_meio_pagamento b ON b.id_base = e.id_base
                            AND convert(DATE, getdate()) BETWEEN b.dt_inicio AND b.dt_fim
                        where r.id_session = ?
                      )
                      order by ds_meio_pagamento";

        $params = array($rs['ID_GATEWAY'], session_id());

        if (!$_ENV['IS_TEST']) {
            $params[] = session_id();
        }

        $result = executeSQL($mainConnection, $query, $params);


        $query = "SELECT top 1 cd_binitau from mw_reserva r
                    inner join mw_apresentacao a on a.id_apresentacao = r.id_apresentacao
                    inner join mw_evento e on e.id_evento = a.id_evento
                    where cd_binitau is not null and id_session = ?";
        $bin = executeSQL($mainConnection, $query, array(session_id()), true);

        $query = "select e.id_base, e.codpeca from mw_evento e inner join mw_apresentacao a on a.id_evento = e.id_evento inner join mw_reserva r on r.id_apresentacao = a.id_apresentacao where r.id_session = ?";
        $rsParcelas = executeSQL($mainConnection, $query, array(session_id()), true);
        $conn = getConnection($rsParcelas['id_base']);
        $query = 'select qt_parcelas from tabpeca where codpeca = ?';
        $rsParcelas = executeSQL($conn, $query, array($rsParcelas['codpeca']), true);
        $parcelas = $rsParcelas['qt_parcelas'];

        
    ?>

    <script>

        function numberToReal(numero) {
            var numero = numero.toFixed(2).split('.');
            numero[0] = numero[0].split(/(?=(?:...)*$)/).join('.');
            return numero.join(',');
        }

            <?php if (gethost()=="bringressos") {
                
?>
                $.getJSON('<?php echo multiSite_getURIAPI() ?>/v1/purchase/site/getinstallments.php?codCliente=<?php echo $_SESSION['user'] ?>&idSession=<?php echo session_id() ?>', function ( data ) { 
                        var data = data.installments;
                        var selectbox = $('#qt_parcelas');
                        selectbox.find('option').remove();
                        $.each(data, function (i, d) {
                            console.log(d);
                            if (d.installment == 1) {
                                $('<option class="sbOptions" selected="selected">').val(d.installment).text('À vista R$' + numberToReal((d.installment_amount / 100 ))).appendTo(selectbox);
                            } else {
                                $('<option class="sbOptions">').val(d.installment).text(d.installment + 'x R$' + numberToReal((d.installment_amount / 100.0 ))).appendTo(selectbox);
                            }
                        });
                        
                    } );
                    <?php
            } else {

            }
            ?>
                </script>
        <input type="hidden" name="usuario_pdv" value="<?php echo (isset($_SESSION["usuario_pdv"])) ? $_SESSION["usuario_pdv"] : 0; ?>" />
        
        <div class="container_cartoes">
            <p class="frase">5.1 Escolha o meio de pagamento</p>
            <div class="container inputs">
                <?php
                if ($_ENV['IS_TEST']) {
                ?>
                <div class="container_cartao">
                    <input id="997" type="radio" name="codCartao" class="radio card_others" value="997"
                        imgHelp="../images/cartoes/help_default.png" formatoCartao="0000-0000-0000-0000" formatoCodigo="000">
                    <label class="radio" for="997">
                        <img src="../images/cartoes/ico_default.png"><br>
                    </label>
                    <p class="nome">teste</p>
                </div>
                <?php
                }
                while ($rs = fetchResult($result)) {
                    // nao exibir fastcash e pagseguro se tiver promo bin na reserva
                    if ($bin != '' and in_array($rs['cd_meio_pagamento'], array('892', '893', '900', '901', '902'))) continue;

                    // paypal
                    if (in_array($rs['cd_meio_pagamento'], array('101'))) {
                        $carregar_paypal = true;
                        $formatoCartao = '';
                        $formatoCodigo = '';
                    }
                    // pagseguro
                    if (in_array($rs['cd_meio_pagamento'], array('900', '901', '902'))) {
                        $carregar_pagseguro_lib = true;
                        $formatoCartao = '00000000000000000000';
                        $formatoCodigo = '0000';
                    }
                    // pagarme
                    elseif (in_array($rs['cd_meio_pagamento'], array('910'))) {
                        $carregar_pagarme_lib = true;
                        $formatoCartao = '00000000000000000000';
                        $formatoCodigo = '0000';
                    }
                    // cielo
                    elseif (in_array($rs['cd_meio_pagamento'], array('920', '921'))) {
                        $carregar_cielo_lib = true;
                        $formatoCartao = '00000000000000000000';
                        $formatoCodigo = '0000';
                    }
                    // TIPagos
                    elseif (in_array($rs['cd_meio_pagamento'], array('998'))) {
                        $formatoCartao = '00000000000000000000';
                        $formatoCodigo = '0000';
                    }
                    // outros meios
                    else {
                        $formatoCartao = ($rs['nm_cartao_exibicao_site'] == 'Amex' ? '0000-000000-00000' : '0000-0000-0000-0000');
                        $formatoCodigo = ($rs['nm_cartao_exibicao_site'] == 'Amex' ? '0000' : '000');
                    }

                ?>
                <div class="container_cartao">
                    <input id="<?php echo $rs['cd_meio_pagamento']; ?>" type="radio" name="codCartao" class="<?php echo ($rs['cd_meio_pagamento'] == 101 ? 'radio card_paypal' : 'radio card_others') ?>" value="<?php echo $rs['cd_meio_pagamento']; ?>"
                        imgHelp="../images/cartoes/help_<?php echo file_exists('../images/cartoes/help_'.$rs['nm_cartao_exibicao_site'].'.png') ? utf8_encode2($rs['nm_cartao_exibicao_site']) : 'default'; ?>.png"
                        formatoCartao="<?php echo $formatoCartao ?>"
                        formatoCodigo="<?php echo $formatoCodigo ?>">
                    <label class="radio" for="<?php echo $rs['cd_meio_pagamento']; ?>">
                        <img src="<?php echo getCartaoImgURL($rs['nm_cartao_exibicao_site']); ?>"><br>
                    </label>
                    <p class="nome"><?php echo $rs['nm_cartao_exibicao_site'] ? utf8_encode2($rs['nm_cartao_exibicao_site']) : utf8_encode2($rs['ds_meio_pagamento']); ?></p>
                </div>
                <?php
                }
                ?>

            </div>
        </div>
        <div class="container_dados container_card_paypal" style="display:none;">
                <p class="frase">5.3 <span class="alt">Conecte-se ao paypal e efetue o pagamento</span></p>
                <div class="linha">
                    <div class="input">
                        <div id="paypal-button"></div>
                    </div>
                </div>
        </div>
        <div class="container_dados container_card_others">
                <?php
                if($_SESSION['usuario_pdv'] == 0){
                ?>
                <p class="frase isCard">5.2 <span class="alt">Dados do cartão</span></p>
                <div class="linha isCard" style="margin-top: 10px">
                    <div class="input">
                        <p class="titulo">Nome do titular</p>
                        <input type="text" class="form-control" name="nomeCartao">
                        <div class="erro_help">
                            <p class="help">Como impresso no cartão</p>
                        </div>
                    </div>
                <?php
                }
                ?>
                    <div class="input parcelas ">
                        <p class="titulo">Quantidade de parcelas</p>
                        <select name="qt_parcelas" style="display: block!important; color: black!important" class="sbHolder" id="qt_parcelas">
                            <?php
                            for ($i = 1; $i <= $parcelas; $i++) {
                                $valor = number_format(round(str_replace(',', '.', $_COOKIE['total_exibicao']) / $i, 2), 2, ',', '');
                                $desc = $i == 1 ? 'À vista' : $i . 'x';

                                echo "<option value='$i'>$desc - R$ $valor</option>";
                            }
                            // for ($i = 1; $i <= count($parcelasPagarme); $i++) {
                            //     $valor = $parcelasPagarme[$i].amount;
                            //     $desc = $i == 1 ? 'à vista' : $i . 'x';
                                

                            //     echo "<option value='$i'>$desc - R$ $valor</option>";
                            // }
                            ?>
                        </select>
                    </div>
                <?php
                if($_SESSION['usuario_pdv'] == 0){
                ?>
                </div>
                <div class="linha isCard" style="margin-top: 30px">
                    <div class="input">
                        <p class="titulo">Número do cartão</p>
                        <input type="text" class="form-control" name="numCartao" value="">
                        <div class="erro_help">
                            <p class="help">XXXX-XXXX-XXXX-XXXX</p>
                        </div>
                    </div>
                    <div class="input codigo">
                        <p class="titulo">Código de segurança</p>
                        <input type="text" class="form-control" name="codSeguranca">
                        <div class="erro_help">
                            <p class="help"><a href="#" class="meu_codigo_cartao">Onde está meu código?</a></p>
                        </div>
                    </div>
                    <div class="input data">
                        <p class="titulo">Validade</p>
                        <div class="mes">
                            <?php echo comboMeses('validadeMes', '', true, true); ?>
                        </div>
                        <div class="ano">
                            <?php echo comboAnos('validadeAno', '', date('Y'), date('Y') + 15, true); ?>
                        </div>
                        <div class="erro_help">
                            <p class="help">Insira a data de validade</p>
                        </div>
                    </div>
                </div>
                <?php
                }
                ?>
                <div class="linha <?php if (isset($_SESSION['assinatura'])) echo 'hidden'; ?>">
                    <div class="input presente nome hidden">
                        <p class="titulo">Nome completo do presenteado</p>
                        <input type="text" class="form-control" name="nomePresente" maxlength="60">
                        <div class="erro_help">
                            <p class="help"></p>
                        </div>
                    </div>
                    <div class="input presente email hidden">
                        <p class="titulo">E-mail do presenteado</p>
                        <input type="text" class="form-control" name="emailPresente" maxlength="100">
                        <div class="erro_help">
                            <p class="help"><a href="#" class="envio_presente_explicao">como funciona?</a></p>
                        </div>
                    </div>
                    <div class="input presente hidden" style="width: 820px;">
                        <p class="titulo">Para cancelar o envio como presente clique <a href="#" class="presente_toggle">aqui</a></p>
                    </div>
                    <div class="input presente" style="width: 820px; margin-top: 20px; margin-bottom: 20px">
                        <p class="titulo">Para enviar como presente clique <a href="#" class="presente_toggle">aqui</a></p>
                    </div>
                </div>
                <?php if (!isset($_SESSION['operador'])) { ?>
                <?php } ?>
<?php
    }
}

    ?>

    <script>


                $('#qt_parcelas').show();
                $('#dadosPagamento > div > div:nth-child(2) > div.container_dados.container_card_others > div:nth-child(2) > div.input.parcelas > div').hide();

    </script>

    <style>
            #qt_parcelas {
                display: block!important;
            }
            #dadosPagamento > div > div:nth-child(2) > div.container_dados.container_card_others > div:nth-child(2) > div.input.parcelas > div {
                display: none!important;
            }

    </style>
    
    <?php


?>