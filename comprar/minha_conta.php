<?php
require 'acessoLogado.php';
require_once('../settings/multisite/unique.php');

if (isset($_SESSION['user']) and is_numeric($_SESSION['user'])) {
    require_once('../settings/functions.php');
    require_once('../settings/pagseguro_functions.php');
    require_once('../settings/pagarme_functions.php');

    if ($is_manutencao === true) {
        header("Location: manutencao.php");
        die();
    }

    $mainConnection = mainConnection();  
        
    $query = "SELECT DS_NOME, DS_SOBRENOME, CONVERT(VARCHAR(10), DT_NASCIMENTO, 103) DT_NASCIMENTO, DS_TELEFONE, DS_CELULAR, DS_DDD_TELEFONE, DS_DDD_CELULAR, CD_CPF, CD_RG, ID_ESTADO, DS_CIDADE, DS_BAIRRO, DS_ENDERECO, NR_ENDERECO, DS_COMPL_ENDERECO, CD_CEP, CD_EMAIL_LOGIN, IN_RECEBE_INFO, IN_RECEBE_SMS, IN_SEXO, ID_DOC_ESTRANGEIRO, ISNULL(IN_ASSINANTE, 'N') AS IN_ASSINANTE FROM MW_CLIENTE WHERE ID_CLIENTE = ?";
    $params = array($_SESSION['user']);
    $rs = executeSQL($mainConnection, $query, $params, true);

    $rs['DT_NASCIMENTO'] = explode('/', $rs['DT_NASCIMENTO']);

    $isAssinante = $rs["IN_ASSINANTE"] == 'S';

    $query = "SELECT
                AC.ID_ASSINATURA_CLIENTE,
                A.DS_ASSINATURA,
                DC.CD_NUMERO_CARTAO,
                AC.DT_PROXIMO_PAGAMENTO,
                DATEADD(DAY, -1, AC.DT_PROXIMO_PAGAMENTO) AS DT_VALIDADE_BILHETES,
                A.QT_BILHETE,
                (SELECT COUNT(1)
                    FROM MW_PROMOCAO
                    WHERE ID_ASSINATURA_CLIENTE = AC.ID_ASSINATURA_CLIENTE AND ID_PEDIDO_VENDA IS NULL) AS QT_BILHETES_DISPONIVEIS,
                DATEDIFF(DAY, AC.DT_COMPRA, GETDATE()) AS DIAS_DESDE_COMPRA,
                A.QT_DIAS_CANCELAMENTO,
                AC.IN_ATIVO,
                A.DS_IMAGEM
                FROM MW_ASSINATURA A
                INNER JOIN MW_ASSINATURA_CLIENTE AC ON AC.ID_ASSINATURA = A.ID_ASSINATURA
                INNER JOIN MW_DADOS_CARTAO DC ON DC.ID_DADOS_CARTAO = AC.ID_DADOS_CARTAO
                WHERE AC.ID_CLIENTE = ? AND (AC.IN_ATIVO = 1 OR (AC.IN_ATIVO = 0 AND AC.DT_PROXIMO_PAGAMENTO > GETDATE()))";
    $params = array($_SESSION['user']);
    $resultAssinaturas = executeSQL($mainConnection, $query, $params);

    $isAssinanteCompre = hasRows($resultAssinaturas);

    if ($isAssinanteCompre) {
        require_once('../settings/Cypher.class.php');
        $cipher = new Cipher('1ngr3ss0s');
    }

    $query = "SELECT
                    *
                FROM (
                    SELECT
                        AH.ID_ASSINATURA_HISTORICO AS ID_PEDIDO_VENDA,
                        ' - ' AS IN_RETIRA_ENTREGA,
                        CONVERT(VARCHAR(10), AH.DT_PAGAMENTO, 103) AS DT_PEDIDO_VENDA, 
                        AH.VL_PAGAMENTO AS VL_TOTAL_PEDIDO_VENDA,
                        'F' AS IN_SITUACAO,
                        '' AS ID_PEDIDO_PAI,
                        '' AS CD_MEIO_PAGAMENTO,
                        ASS.DS_ASSINATURA,
                        'A' AS TIPO_PEDIDO, --assinatura
                        AH.DT_PAGAMENTO AS ORDER_KEY,
                        AC.ID_ASSINATURA_CLIENTE
                    FROM MW_ASSINATURA_HISTORICO AH
                    INNER JOIN MW_ASSINATURA_CLIENTE AC ON AC.ID_ASSINATURA_CLIENTE = AH.ID_ASSINATURA_CLIENTE
                    INNER JOIN MW_ASSINATURA ASS ON ASS.ID_ASSINATURA = AC.ID_ASSINATURA

                    WHERE ID_CLIENTE = ?

                    UNION ALL

                    SELECT DISTINCT
                        PV.ID_PEDIDO_VENDA,
                        CASE PV.IN_RETIRA_ENTREGA
                        WHEN 'R' THEN 'retirada no Local'
                        WHEN 'E' THEN 'no endereço'
                        ELSE ' - '
                        END IN_RETIRA_ENTREGA,
                        CONVERT(VARCHAR(10), PV.DT_PEDIDO_VENDA, 103) DT_PEDIDO_VENDA, 
                        PV.VL_TOTAL_PEDIDO_VENDA,
                        PV.IN_SITUACAO,
                        PV.ID_PEDIDO_PAI,
                        M.CD_MEIO_PAGAMENTO,
                        ASS.DS_ASSINATURA,
                        'P' AS TIPO_PEDIDO, --pedido
                        PV.DT_PEDIDO_VENDA AS ORDER_KEY,
                        NULL
                    FROM MW_PEDIDO_VENDA PV
                    INNER JOIN CI_MIDDLEWAY..order_host oh ON pv.id_pedido_venda=oh.id_pedido_venda
                    INNER JOIN CI_MIDDLEWAY..host h ON oh.id_host=h.id
                    LEFT JOIN MW_ITEM_PEDIDO_VENDA IPV ON IPV.ID_PEDIDO_VENDA = PV.ID_PEDIDO_PAI
                    LEFT JOIN MW_APRESENTACAO A ON A.ID_APRESENTACAO = IPV.ID_APRESENTACAO
                    LEFT JOIN MW_EVENTO E ON E.ID_EVENTO = A.ID_EVENTO
                    LEFT JOIN MW_MEIO_PAGAMENTO M ON M.ID_MEIO_PAGAMENTO = PV.ID_MEIO_PAGAMENTO
                    
                    LEFT JOIN MW_PROMOCAO P ON P.ID_PEDIDO_VENDA = PV.ID_PEDIDO_VENDA
                    LEFT JOIN MW_ASSINATURA_PROMOCAO AP ON AP.ID_PROMOCAO_CONTROLE = P.ID_PROMOCAO_CONTROLE
                    LEFT JOIN MW_ASSINATURA ASS ON ASS.ID_ASSINATURA = AP.ID_ASSINATURA

                    WHERE pv.ID_CLIENTE = ?
                    AND h.host=?
                ) AS DADOS
                ORDER BY ORDER_KEY DESC, ID_PEDIDO_VENDA DESC";
    $params = array($_SESSION['user'], $_SESSION['user'], $_SERVER["HTTP_HOST"]);
    $result = executeSQL($mainConnection, $query, $params);


    $queryTeatros = "SELECT DISTINCT
                        B.ID_BASE,
                        B.DS_NOME_TEATRO
                    FROM MW_PACOTE_RESERVA PR
                    INNER JOIN MW_PACOTE P ON P.ID_PACOTE = PR.ID_PACOTE
                    INNER JOIN MW_APRESENTACAO A ON A.ID_APRESENTACAO = P.ID_APRESENTACAO
                    INNER JOIN MW_APRESENTACAO A2 ON A2.ID_EVENTO = A.ID_EVENTO AND A2.DT_APRESENTACAO = A.DT_APRESENTACAO AND A2.HR_APRESENTACAO = A.HR_APRESENTACAO
                    INNER JOIN MW_EVENTO E ON E.ID_EVENTO = A.ID_EVENTO
                    INNER JOIN MW_BASE B ON B.ID_BASE  = E.ID_BASE
                    where PR.ID_CLIENTE = ?";
    $resultTeatros = executeSQL($mainConnection, $queryTeatros, array($_SESSION['user']));

    $options = $isAssinanteCompre ? '<option value="' . multiSite_getNameWithoutDotCom(). '">'.multiSite_getName().'</option>' : '';
    while ($rsTeatros = fetchResult($resultTeatros)) {
        $options .= '<option value="'.$rsTeatros['ID_BASE'].'">'.utf8_encode2($rsTeatros['DS_NOME_TEATRO']).'</option>';
    }


    $queryAcao = "SELECT DISTINCT
                    CASE WHEN CAST(CONVERT(VARCHAR(10), GETDATE(), 120) AS SMALLDATETIME) BETWEEN P.DT_INICIO_FASE1 AND P.DT_FIM_FASE2 THEN 1 ELSE 0 END IN_RENOVAR
                    ,CASE WHEN CAST(CONVERT(VARCHAR(10), GETDATE(), 120) AS SMALLDATETIME) BETWEEN P.DT_INICIO_FASE1 AND P.DT_FIM_FASE1 THEN 1 ELSE 0 END IN_SOLICITAR
                    ,CASE WHEN CAST(CONVERT(VARCHAR(10), GETDATE(), 120) AS SMALLDATETIME) BETWEEN P.DT_INICIO_FASE2 AND P.DT_FIM_FASE2 THEN 1 ELSE 0 END IN_TROCAR
                    ,CASE WHEN CAST(CONVERT(VARCHAR(10), GETDATE(), 120) AS SMALLDATETIME) BETWEEN P.DT_INICIO_FASE1 AND P.DT_FIM_FASE2 THEN 1 ELSE 0 END IN_CANCELAR   
                    ,P.DT_INICIO_FASE2
                    ,P.DT_FIM_FASE2
                    ,CASE WHEN P.DT_INICIO_FASE2 = P.DT_INICIO_FASE3 AND P.DT_FIM_FASE2 = P.DT_FIM_FASE3 THEN 1
                        WHEN CAST(CONVERT(VARCHAR(10), GETDATE(), 120) AS SMALLDATETIME) BETWEEN P.DT_INICIO_FASE3 AND P.DT_FIM_FASE3 THEN 0
                    ELSE 1 END IN_ACAO
                FROM 
                    MW_PACOTE_RESERVA PR
                INNER JOIN MW_PACOTE P ON P.ID_PACOTE = PR.ID_PACOTE
                WHERE
                    PR.ID_CLIENTE = ? AND PR.IN_STATUS_RESERVA NOT IN ('R')";
    $params = array($_SESSION['user']);
    $rsAcao = executeSQL($mainConnection, $queryAcao, $params);
    $arrAcoes = array();
    while ($acao = fetchResult($rsAcao)) {
        $arrAcoes["renovar"] = $acao["IN_RENOVAR"];
        $arrAcoes["solicitar Troca"] = $acao["IN_SOLICITAR"];
        $arrAcoes["efetuar Troca"] = $acao["IN_TROCAR"];
        $arrAcoes["cancelar"] = $acao["IN_CANCELAR"];
        $arrAcoes["dtInicio"] = $acao["DT_INICIO_FASE2"]->format('d/m/Y');
        $arrAcoes["dtFim"] = $acao["DT_FIM_FASE2"]->format('d/m/Y');
        $visible = $acao["IN_ACAO"];
    }
}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
    <head>
    <!-- Google Tag Manager -->
    <script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
    new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
    j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
    'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
    })(window,document,'script','dataLayer','GTM-WNN2XTF');</script>
    <!-- End Google Tag Manager -->

        <meta http-equiv="Content-type" content="text/html; charset=utf-8" />
        <meta name="robots" content="noindex,nofollow"/>
        <link href="<?php echo multiSite_getFavico()?>" rel="shortcut icon"/>
        <link href='https://fonts.googleapis.com/css?family=Paprika|Source+Sans+Pro:200,400,400italic,200italic,300,900' rel='stylesheet' type='text/css'/>
        <link rel="stylesheet" href="../stylesheets/cicompra.css"/>
        
        <link rel="stylesheet" href="../stylesheets/ajustes2.css"/>

        <script src="../javascripts/jquery.2.0.0.min.js" type="text/javascript"></script>
        <script src="../javascripts/jquery.placeholder.js" type="text/javascript"></script>
        <script src="../javascripts/jquery.selectbox-0.2.min.js" type="text/javascript"></script>
        <script src="../javascripts/jquery.mask.min.js" type="text/javascript"></script>
        <script src="../javascripts/cicompra.js" type="text/javascript"></script>

        <script src="../javascripts/jquery.cookie.js" type="text/javascript"></script>
        <script src="../javascripts/jquery.utils2.js" type="text/javascript"></script>
        <script src="../javascripts/common.js" type="text/javascript"></script>
        <script src="../javascripts/simpleFunctions.js" type="text/javascript"></script>

        <script src="../javascripts/minhaConta.js" type="text/javascript"></script>
        <script src="../javascripts/identificacao_cadastro.js" type="text/javascript"></script>
        <script src="../javascripts/dadosEntrega.js" type="text/javascript"></script>

        <?php if (isset($_GET['atualizar_dados']) || isset($_GET['atualizar_endereco'])) { ?>
            <script type="text/javascript">
                $(function(){
                    var dados_atualizados = false,
                        endereco_atualizado = false;

                    $.dialog({text: 'Por favor, para concluir a operação é necessário conferir e atualizar os dados cadastrais.'});

                    $('.menu_conta .botao').hide();

                    if ($.getUrlVar('atualizar_endereco') != undefined){
                        $('.container_enderecos, .add_endereco').hide();

                        $('.botao.enderecos').trigger('click');

                        $('#enderecos a.end_editar[href*="id='+$.getUrlVar('atualizar_endereco')+'"]').trigger('click');
                        $('#enderecos a.end_cancelar').hide();

                        $('#enderecos').on('endereco_salvo', function(){
                            sctop = 0;
                            if (!dados_atualizados) {
                                endereco_atualizado = true;
                                $('.botao.dados_conta').trigger('click');
                            } else {
                                document.location = $.getUrlVar('redirect');
                            }
                        });
                    } else endereco_atualizado = true;

                    if ($.getUrlVar('atualizar_dados') != undefined){
                        $('.botao.dados_conta').trigger('click');

                        $('#form_cadastro').on('dados_salvos', function(){
                            sctop = 0;
                            if (!endereco_atualizado) {
                                dados_atualizados = true;
                                $('.botao.enderecos').trigger('click');
                            } else {
                                document.location = $.getUrlVar('redirect');
                            }
                        });
                    } else dados_atualizados = true;

                    if (!dados_atualizados || !endereco_atualizado) {
                        $('.descricao_pag .descricao .descricao').text('Por favor, confirme os dados abaixo antes de continuar a compra.');
                    }
                });
            </script>
        <?php } ?>

        <title><?php echo multiSite_getTitle()?></title>
        <style type="text/css">
            div.descricao_pag div.descricao{ float:left; width:840px; }            
            a.botao{ margin-right: 10px; }
            a.botao.enderecos{ margin-right: 10px; }
            table#meus_pedidos{
                float:left;
                width:100%;
                border-collapse:collapse;
                margin-top: 15px;
                margin-left: 0px;
            }

            span#detalhes_historico span.pedido_resumo table {
                margin: 15px 0 30px 120px;
            }

            span#detalhes_historico span.pedido_resumo table tr td {
                padding-left: 15px;
                padding-bottom: 10px;
            }

            div.acoes div.sbHolder.destaque a.sbSelector,
            div.acoes div.sbHolder.destaque ul.sbOptions li:first-child a {
                color: #930606;
                text-transform: uppercase;
            }

            div.acoes div.sbHolder.teatros {
                width: 500px;
            }
            
            div.acoes div.sbHolder.teatros ul.sbOptions {
                width: 512px;
            }

            div.acoes div.sbHolder.teatros a.sbSelector {
                width: 472px;
            }
            #selos {
                margin-bottom: 0;
            }

            .tabela_assinaturas {
                width: 950px;
                margin: 0 0 50px 30px;
            }
            .tabela_assinaturas .logo img {
                width: 170px;
            }
            .tabela_assinaturas .logo {
                width: 130px;
                background: #000;
                background: -moz-linear-gradient(-45deg, #000000 23%, #eaeaea 100%);
                background: -webkit-linear-gradient(-45deg, #000 23%,#eaeaea 100%);
                background: linear-gradient(135deg, #000 23%,#eaeaea 100%);
                filter: progid:DXImageTransform.Microsoft.gradient( startColorstr='#000000', endColorstr='#eaeaea',GradientType=1 );
                border-radius: 10%;
            }
            .tabela_assinaturas .texto {
                width: 540px;
                padding-left: 30px;
            }
            .tabela_assinaturas .acao {
                text-align: right;
            }
            .tabela_assinaturas .top_line {
                border-top: 1px dotted #A9A9A9;
                padding-top: 15px;
            }
            .tabela_assinaturas .bottom_line {
                border-bottom: 1px dotted #A9A9A9;
                padding-bottom: 15px;
            }
            .tabela_assinaturas .espaco {
                width: 20px;
            }
        </style>
        <script>
            $(function() {
                $('#assinaturas tbody').on('change', 'input[name*=pacote]', function(){
                    $(this).next('label').next('input').prop('checked', $(this).prop('checked'));
                    var status = $(this).attr('status');

                    if($(this).is(':checked')){
                        $('input[name*=pacote]').filter(function(){ return $(this).attr('status') !== status }).prop('disabled', true)
                        .next('label').next('input').prop('disabled', true);
                    }else{
                        if (!$('input[name*=pacote]:checked')[0]) {
                            $('input[name*=pacote]').prop('disabled', false).next('label').next('input').prop('disabled', false);
                        }
                    }

                    $('#acao').selectbox('detach');
                    $('#acao option').prop('disabled', false);

                    $('input[name*=pacote]:checked').each(function(i, e){
                        var status = $(e).attr('status').split('');

                        $('#acao option').filter(function(){ return jQuery.inArray($(this).attr('status'), status) !== -1; }).prop('disabled', true);
                    });

                    $('#acao').selectbox('attach');

                    if ($('#acao').is('.destaque')) {
                        $('#acao').next('div.sbHolder').addClass('destaque')
                    }
                });
                <?php if ($isAssinante) { ?>
                <?php } else {
                        if (isset($_GET['assinatura'])) {
                            $msg_nao_assinante = 'Pacotes disponíveis apenas para assinantes, novas assinaturas consulte caderno de programação.';
                            echo "$.dialog({title: 'Alerta...', text: '$msg_nao_assinante'});";
                        }
                    }
                ?>
            });
        </script>        
    </head>
    <body>
    <!-- Google Tag Manager (noscript) -->
    <noscript><iframe src="https://www.googletagmanager.com/ns.html?id=GTM-WNN2XTF" 
    height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
    <!-- End Google Tag Manager (noscript) -->
            
        <div class="bg__main" style=""></div><div id="pai">
            <?php require "header.php"; ?>
            <div id="content">
                <div class="alert">
                    <div class="centraliza">
                        <img src="../images/ico_erro_notificacao.png">
                            <div class="container_erros"></div>
                            <a>fechar</a>
                    </div>
                </div>

                <div class="row centraliza">
                    <div class="row descricao_pag">
                        <div class="img">
                            <img src="../images/ico_enderecos.png">
                        </div>
                        <div class="descricao">
                            <p class="nome">
                                Minha conta 
                                <a href="logout.php">logout</a>
                            </p>
                            <p class="descricao">
                                Olá <b><?php echo utf8_encode2($rs['DS_NOME']); ?>,</b> veja seus dados da conta, histórico de pedidos, troque
                                a sua senha ou altere suas configurações do guia de espetáculos
                            </p>
                            <div class="menu_conta">
                                <a href="#meus_pedidos" class="botao meus_pedidos ativo">meus pedidos</a>
                                <a href="#dados_conta" class="botao dados_conta">dados da conta</a>
                                <?php if (!(isset($_SESSION['operador']) and is_numeric($_SESSION['operador']))) {
                                ?>
                                    <a href="#trocar_senha" class="botao trocar_senha">troca de senha</a>
                                <?php } ?>
                                <a href="#enderecos" class="botao enderecos ativo">endereços</a>
                                <?php if ($isAssinante OR $isAssinanteCompre) { ?>
                                    <a href="#frmAssinatura" class="botao assinaturas">assinaturas</a>
                                <?php } ?>
                            </div>
                        </div>
                    </div>

                    <div class="row content__minha-conta">

                    <?php require 'div_cadastro.php'; ?>

                                <table id="meus_pedidos" style="display: none">
                                    <thead>
                                        <tr>
                                            <td width="120">Pedido</td>
                                            <td width="170">Forma de Entrega</td>
                                            <td width="140">Data do Pedido</td>
                                            <td width="140">Total do Pedido</td>
                                            <td width="190">Status</td>
                                            <?php if ($isAssinante OR $isAssinanteCompre) { ?>
                                            <td width="210">Assinatura</td>
                                            <?php } ?>
                                        </tr>
                                    </thead>
                                    <tbody>
                            <?php
                                while ($rs = fetchResult($result)) {
                            ?>
                                    <tr>
                                        <?php if ($rs['TIPO_PEDIDO'] == 'A') { ?>
                                            <td class="npedido <?php echo 'assinatura_'.$rs['ID_ASSINATURA_CLIENTE']; ?>">
                                                <a href="detalhes_pedido.php?pedido=A<?php echo $rs['ID_PEDIDO_VENDA']; ?>">A<?php echo $rs['ID_PEDIDO_VENDA']; ?></a>
                                            </td>
                                        <?php } else { ?>
                                            <td class="npedido">
                                                <a href="detalhes_pedido.php?pedido=<?php echo $rs['ID_PEDIDO_VENDA']; ?>"><?php echo $rs['ID_PEDIDO_VENDA']; ?></a>
                                            </td>
                                        <?php } ?>
                                        <td><?php echo $rs['IN_RETIRA_ENTREGA']; ?></td>
                                        <td><?php echo $rs['DT_PEDIDO_VENDA']; ?></td>
                                        <td>R$ <?php echo number_format($rs['VL_TOTAL_PEDIDO_VENDA'], 2, ',', ''); ?></td>
                                        <td>
                                            <?php
                                            echo comboSituacao('situacao', $rs['IN_SITUACAO'], false);
                                            // fastchash
                                            if (in_array($rs['CD_MEIO_PAGAMENTO'], array('892', '893')) and $rs['IN_SITUACAO'] == 'P') {

                                                echo "<br/><a href='./pagamento_fastcash.php?pedido={$rs['ID_PEDIDO_VENDA']}'>Comprovar Pagamento</a>";

                                            }
                                            // pagseguro
                                            elseif (in_array($rs['CD_MEIO_PAGAMENTO'], array('900', '901', '902')) and $rs['IN_SITUACAO'] == 'P') {

                                                $query = "SELECT OBJ_PAGSEGURO FROM MW_PEDIDO_PAGSEGURO WHERE ID_PEDIDO_VENDA = ? ORDER BY DT_STATUS DESC";
                                                $params = array($rs['ID_PEDIDO_VENDA']);
                                                $rs2 = executeSQL($mainConnection, $query, $params, true);

                                                if (!empty($rs2)) {
                                                    $transaction =  unserialize(base64_decode($rs2['OBJ_PAGSEGURO']));

                                                    if ($rs['CD_MEIO_PAGAMENTO'] == '900' AND $transaction->getStatus()->getValue() == 1) {
                                                        echo "<br/><a href='".$transaction->getPaymentLink()."' target='_blank'>Imprimir Boleto</a>";
                                                    } elseif ($rs['CD_MEIO_PAGAMENTO'] == '901' AND $transaction->getStatus()->getValue() == 1) {
                                                        echo "<br/><a href='".$transaction->getPaymentLink()."' target='_blank'>Efetuar Débito</a>";
                                                    } else {
                                                        $status = getStatusPagSeguro($transaction->getStatus()->getValue());
                                                        echo "<br/>".$status['name'];
                                                    }
                                                }
                                            }
                                            // pagarme
                                            elseif (in_array($rs['CD_MEIO_PAGAMENTO'], array('911')) and $rs['IN_SITUACAO'] == 'P') {

                                                $query = "SELECT OBJ_PAGSEGURO FROM MW_PEDIDO_PAGSEGURO WHERE ID_PEDIDO_VENDA = ? ORDER BY DT_STATUS DESC";
                                                $params = array($rs['ID_PEDIDO_VENDA']);
                                                $rs2 = executeSQL($mainConnection, $query, $params, true);

                                                if (!empty($rs2)) {
                                                    $transaction =  unserialize(base64_decode($rs2['OBJ_PAGSEGURO']));

                                                    if ($transaction['status'] == 'waiting_payment') {
                                                        echo "<br/><a href='".$transaction['boleto_url']."' target='_blank'>Imprimir Boleto</a>";
                                                    } else {
                                                        $status = getStatusPagarme($transaction['status']);
                                                        echo "<br/>".$status['name'];
                                                    }
                                                }
                                            }
                                            ?>
                                        </td>
                                        <?php if ($isAssinante OR $isAssinanteCompre) { ?>
                                        <td>
                                            <?php
                                                echo $rs['ID_PEDIDO_PAI'] ? 'ref. assinatura '.$rs['ID_PEDIDO_PAI'] : '';
                                                echo (($rs['TIPO_PEDIDO'] == 'P' AND $rs['DS_ASSINATURA'] != '') ? 'utilizou bilhetes<br/>' : '') . ($rs['DS_ASSINATURA'] ? $rs['DS_ASSINATURA'] : '');
                                            ?>
                                        </td>
                                        <?php } ?>                                        
                                    </tr>
                            <?php
                                }
                            ?>
                            </tbody>
                        </table>
                        <span id="detalhes_pedido" style="display: none"></span>

                    <?php if (!(isset($_SESSION['operador']) and is_numeric($_SESSION['operador']))) {
                    ?>
                                    <form id="trocar_senha" style="display: none" method="post" action="cadastro.php">
                                        <div class="coluna coluna_endereco">
                                            <div class="input_area login troca_de_senha">
                                                <div class="icone"></div>
                                                <div class="inputs">
                                                    <p class="titulo">Trocar a senha</p>
                                                    <div class="form-group">
                                                        <label for="senha">Digite sua senha</label>
                                                        <input type="password" class="form-control" name="senha" id="senha" placeholder="Digite sua senha atual"/>
                                                        <div class="erro_help">
                                                            <p class="erro">senha atual não confere</p>
                                                            <p class="help"></p>
                                                        </div>
                                                    </div>
                                                    <div class="form-group">
                                                        <label for="senha1">Digite sua nova senha</label>
                                                        <input type="password" name="senha1" id="senha1" placeholder="Digite sua nova senha" class="form-control" />
                                                        <div class="erro_help">
                                                            <p class="erro">mínimo 6 caracteres com letras e números</p>
                                                            <p class="help"></p>
                                                        </div>
                                                    </div>
                                                    <div class="form-group">
                                                        <input type="password" name="senha2" id="senha2" placeholder="Confirme sua nova senha"class="form-control" />
                                                        <div class="erro_help">
                                                        <p class="erro">as senhas devem ser idênticas</p>
                                                        <p class="help"></p>
                                                        </div>
                                                    </div>

                                                    <input type="button" class="submit salvar_dados" value="Enviar"/>
                                                    <div class="erro_help">
                                                        <p class="help senha hidden">senha alterada com sucesso</p>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </form>
                    <?php } ?>

                                <span id="enderecos" style="display: none" class="minha_conta">
                        <?php require "dadosEntrega.php"; ?>
                            </span>
                        
                        <?php if($isAssinante OR $isAssinanteCompre){ ?>
                            <form name="frmAssinatura" id="frmAssinatura" method="post">
                                <input name="dtInicio" type="hidden" value="<?php echo $arrAcoes["dtInicio"]; ?>" />
                                <input name="dtFim" type="hidden" value="<?php echo $arrAcoes["dtFim"]; ?>" />

                                <div class="acoes">

                                    <p class="titulo">Assinaturas:</p>
                                    <select name="local" id="comboTeatroAssinaturas">
                                        <?php echo $options; ?>
                                    </select><br/><br/><br/>

                                    <span class="tabela_pacotes">
                                        <p>Selecione as séries de apresentações<br>abaixo e escolha a ação desejada</p>
                                        <select name="acao" id="acao">
                                            <option value="-" selected>ações possíveis nesta fase</option>
                                <?php
                                if($visible){
                                ?>
                                    <?php foreach ($arrAcoes as $key => $val) {
                                    ?>
                                    <?php
                                            if ($val == 1) {
                                                $status = array("renovar" => "R",
                                                    "solicitar Troca" => "S",
                                                    "efetuar Troca" => "T",
                                                    "cancelar" => "C");
                                    ?>
                                                <option status="<?php echo $status[$key]; ?>" value="<?php echo str_replace(" ", "", $key); ?>"><?php echo ucfirst($key); ?></option>
                                    <?php
                                            }
                                        }
                                    ?>
                                <?php
                                    }
                                ?>
                                        </select>
                                    </span>
                                </div>

                            <table id="assinaturas" class="tabela_pacotes">
                                <thead>
                                    <tr>
                                        <td width="200" colspan="2">Pacotes</td>
                                        <td width="100">Temporada</td>
                                        <td width="190">Setor</td>
                                        <td width="80">Lugar</td>
                                        <td width="110">Preço</td>
                                        <td width="110">Valor Pago</td>
                                        <td width="190">Situação</td>
                                      </tr>
                                </thead>
                                <tbody>
                                </tbody>
                            </table>
                            
                            <?php
                            while ($rs = fetchResult($resultAssinaturas)) {

                                $texto_alterar = 'alterar cartão de crédito';
                                $mostrar_alteracao = true;

                                if ((isset($_SESSION['operador']) OR $rs['DIAS_DESDE_COMPRA'] > $rs['QT_DIAS_CANCELAMENTO']) AND $rs['IN_ATIVO']) {
                                    $mostrar_cancelamento = true;
                                } elseif (!$rs['IN_ATIVO']) {
                                    $mostrar_cancelamento = false;
                                    $texto_alterar = 'reativar assinatura';
                                } else {
                                    $mostrar_cancelamento = false;
                                }
                            ?>
                                <table class="tabela_assinaturas">
                                    <tr>
                                        <td rowspan="4" class="logo"><img src="<?php echo $rs['DS_IMAGEM']; ?>" /></td>
                                        <td class="espaco"></td>
                                        <td class="texto"><?php echo $rs['DS_ASSINATURA']; ?></td>
                                        <td class="acao cancelar" rowspan="2">
                                            <?php if ($mostrar_cancelamento) { ?>
                                            <a href="cadastro.php?action=cancelar_assinatura&id=<?php echo $rs['ID_ASSINATURA_CLIENTE']; ?>">cancelar assinatura</a>
                                            <?php } ?>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="espaco"></td>
                                        <td class="texto top_line">**** **** **** <?php echo substr($cipher->decrypt($rs['CD_NUMERO_CARTAO']), -4); ?></td>
                                    </tr>
                                    <tr>
                                        <td class="espaco"></td>
                                        <td class="texto bottom_line">
                                            <?php
                                            if ($rs['IN_ATIVO']) {
                                                $valor = getProximoValorAssinatura($rs['ID_ASSINATURA_CLIENTE']);

                                                echo "o próximo pagamento será no dia ".$rs['DT_PROXIMO_PAGAMENTO']->format('d/m').", no valor de R$ ".number_format($valor, 2, ',', '');
                                            } else {
                                                echo "essa assinatura foi cancelada, mas ainda pode ser utilizada até o dia ".$rs['DT_PROXIMO_PAGAMENTO']->format('d/m');
                                            }
                                            ?>
                                        </td>
                                        <td class="acao historico"><a href="minha_conta.php?pedido=<?php echo $rs['ID_ASSINATURA_CLIENTE']; ?>">historico de pagamento</a></td>
                                    </tr>
                                    <tr>
                                        <td class="espaco"></td>
                                        <td class="texto">
                                            o plano possui <?php echo $rs['QT_BILHETE']; ?> bilhetes<br/>
                                            <?php
                                            if ($rs['QT_BILHETES_DISPONIVEIS'] == 0) {
                                                echo "e você já utilizou seus bilhetes desse mês";
                                            } elseif ($rs['QT_BILHETES_DISPONIVEIS'] == 1) {
                                                echo "e você ainda tem 1 bilhete disponível até o dia ".$rs['DT_VALIDADE_BILHETES']->format('d/m');
                                            } else {
                                                echo "e você ainda tem {$rs['QT_BILHETES_DISPONIVEIS']} bilhetes disponíveis até o dia ".$rs['DT_VALIDADE_BILHETES']->format('d/m');
                                            }
                                            ?>
                                        </td>
                                        <td class="acao alterar">
                                            <?php if ($mostrar_alteracao) { ?>
                                            <a href="assinaturaPagamento.php?action=alterar_assinatura&id=<?php echo $rs['ID_ASSINATURA_CLIENTE']; ?>"><?php echo $texto_alterar; ?></a>
                                            <?php } ?>
                                        </td>
                                    </tr>
                                </table>
                            <?php
                            }
                            ?>
                        </form>
                        <span id="detalhes_historico"></span>
                        <?php } ?>
                    </div>
                </div></div>

                <div id="texts">
                    <div class="centraliza">
                        <p></p>
                    </div>
                </div>

<?php include "footer.php"; ?>

<?php //include "selos.php"; ?>
<div id="overlay">
            <?php require 'termosUso.php'; ?>
        </div>
        </div>
    </body>
</html>