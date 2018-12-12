<?php
require_once('../settings/functions.php');
require_once('../settings/settings.php');
include('../settings/Log.class.php');
$mainConnection = mainConnection();
session_start();

$pagina = basename(__FILE__);

if (acessoPermitido($mainConnection, $_SESSION['admin'], 250, true)) {

    if ($_POST['pedido'] or isset($_GET['action'])) {

        include('./actions/' . $pagina);

    } else {

    require_once('../settings/Paginator.php');

    if (isset($_GET["dt_inicial"]) && isset($_GET["dt_final"]) && isset($_GET["nm_cliente"]) && isset($_GET["nm_operador"]) && isset($_GET["cd_cpf"]) && isset($_GET["num_pedido"])) {

        $situacao = 'F';

        $where = "WHERE CONVERT(DATETIME,CONVERT(CHAR(8), PV.DT_PEDIDO_VENDA, 112)) BETWEEN CONVERT(DATETIME, ?, 103) AND CONVERT(DATETIME, ?, 103) AND PV.IN_SITUACAO = ?";

        $params = array($_GET["dt_inicial"], $_GET["dt_final"], $situacao);

        $paramsTotal = array($_GET["dt_inicial"], $_GET["dt_final"], $situacao);

        $select = "SELECT
                    (CONVERT(VARCHAR(10), PV.DT_PEDIDO_VENDA, 103) + ' - ' + CONVERT(VARCHAR(8), PV.DT_PEDIDO_VENDA, 114)) AS DT_PEDIDO_VENDA,
                    PV.ID_PEDIDO_VENDA,
                    C.DS_NOME AS CLIENTE,
                    C.DS_SOBRENOME,
                    SUM(IPV.VL_UNITARIO) AS TOTAL_UNIT,
                    PV.IN_SITUACAO,
                    ROW_NUMBER() OVER(ORDER BY PV.ID_PEDIDO_VENDA DESC) AS 'LINHA',
                    COUNT(1) AS QUANTIDADE,
                    PV.IN_RETIRA_ENTREGA,
                    C.DS_DDD_TELEFONE,
                    C.DS_TELEFONE,
                    U.DS_NOME,
                    MP.NM_CARTAO_EXIBICAO_SITE ";

        $from = " FROM
                      MW_PEDIDO_VENDA PV
                      INNER JOIN MW_CLIENTE C ON C.ID_CLIENTE = PV.ID_CLIENTE
                      INNER JOIN MW_MEIO_PAGAMENTO MP ON MP.ID_MEIO_PAGAMENTO = PV.ID_MEIO_PAGAMENTO
                      LEFT JOIN MW_ITEM_PEDIDO_VENDA IPV ON IPV.ID_PEDIDO_VENDA = PV.ID_PEDIDO_VENDA
                      LEFT JOIN MW_USUARIO U ON U.ID_USUARIO=PV.ID_USUARIO_CALLCENTER ";

        $from2 = "FROM
                      MW_PEDIDO_VENDA PV
                      INNER JOIN MW_CLIENTE C ON C.ID_CLIENTE = PV.ID_CLIENTE
                      INNER JOIN MW_MEIO_PAGAMENTO MP ON MP.ID_MEIO_PAGAMENTO = PV.ID_MEIO_PAGAMENTO
                      INNER JOIN MW_ITEM_PEDIDO_VENDA_HIST IPV ON IPV.ID_PEDIDO_VENDA = PV.ID_PEDIDO_VENDA
                      LEFT JOIN MW_USUARIO U ON U.ID_USUARIO=PV.ID_USUARIO_CALLCENTER ";

        $group = " GROUP BY
                      (CONVERT(VARCHAR(10), PV.DT_PEDIDO_VENDA, 103) + ' - ' + CONVERT(VARCHAR(8), PV.DT_PEDIDO_VENDA, 114)),
                      PV.ID_PEDIDO_VENDA,
                      C.DS_NOME,
                      C.DS_SOBRENOME,
                      PV.IN_SITUACAO,
                      DT_PEDIDO_VENDA,
                      PV.IN_RETIRA_ENTREGA,
                      C.DS_DDD_TELEFONE,
                      C.DS_TELEFONE,
                      U.DS_NOME,
                      MP.NM_CARTAO_EXIBICAO_SITE";

        if (!empty($_GET["num_pedido"])) {
            $where .= " AND PV.ID_PEDIDO_VENDA = ?";

            $params[] = $_GET["num_pedido"];
            $paramsTotal[] = $_GET["num_pedido"];
        }
        if (!empty($_GET["nm_cliente"])) {
            $where .= " AND (C.DS_NOME LIKE '%" . utf8_decode(trim($_GET["nm_cliente"])) . "%' OR C.DS_SOBRENOME LIKE '%" . utf8_decode(trim($_GET["nm_cliente"])) . "%')";
            $join = true;

            //$params[] = $_GET["nm_cliente"];
        }

        if (!empty($_GET["nm_evento"])) {
            $from .= "  LEFT JOIN MW_APRESENTACAO A ON IPV.ID_APRESENTACAO = A.ID_APRESENTACAO
                        LEFT JOIN MW_EVENTO E ON E.ID_EVENTO=A.ID_EVENTO ";
        }

        if (!empty($_GET["nm_operador"])) {
            if ($_GET["nm_operador"] == 'Web' || $_GET["nm_operador"] == 'WEB' || $_GET["nm_operador"] == 'web') {
                $select = "SELECT
                            (CONVERT(VARCHAR(10), PV.DT_PEDIDO_VENDA, 103) + ' - ' + CONVERT(VARCHAR(8), PV.DT_PEDIDO_VENDA, 114)) AS DT_PEDIDO_VENDA,
                            PV.ID_PEDIDO_VENDA,
                            C.DS_NOME AS CLIENTE,
                            C.DS_SOBRENOME,
                            SUM(IPV.VL_UNITARIO) AS TOTAL_UNIT,
                            PV.IN_SITUACAO,
                            ROW_NUMBER() OVER(ORDER BY PV.ID_PEDIDO_VENDA DESC) AS 'LINHA',
                            COUNT(1) AS QUANTIDADE,
                            PV.IN_RETIRA_ENTREGA,
                            C.DS_DDD_TELEFONE,
                            C.DS_TELEFONE ";

                $group = " GROUP BY
                              (CONVERT(VARCHAR(10), PV.DT_PEDIDO_VENDA, 103) + ' - ' + CONVERT(VARCHAR(8), PV.DT_PEDIDO_VENDA, 114)),
                              PV.ID_PEDIDO_VENDA,
                              C.DS_NOME,
                              C.DS_SOBRENOME,
                              PV.IN_SITUACAO,
                              DT_PEDIDO_VENDA,
                              PV.IN_RETIRA_ENTREGA,
                              C.DS_DDD_TELEFONE,
                              C.DS_TELEFONE ";

                $from = "FROM MW_PEDIDO_VENDA PV INNER JOIN MW_CLIENTE C ON C.ID_CLIENTE = PV.ID_CLIENTE AND PV.ID_USUARIO_CALLCENTER IS NULL
                          LEFT JOIN MW_ITEM_PEDIDO_VENDA_HIST IPV ON IPV.ID_PEDIDO_VENDA = PV.ID_PEDIDO_VENDA ";
                $join2 = true;
            } else {
                $where .= " AND U.DS_NOME LIKE '%" . utf8_decode(trim($_GET["nm_operador"])) . "%'";
                $from = "FROM MW_PEDIDO_VENDA PV INNER JOIN MW_CLIENTE C ON C.ID_CLIENTE = PV.ID_CLIENTE
                          LEFT JOIN MW_ITEM_PEDIDO_VENDA IPV ON IPV.ID_PEDIDO_VENDA = PV.ID_PEDIDO_VENDA
                          LEFT JOIN MW_USUARIO U ON U.ID_USUARIO=PV.ID_USUARIO_CALLCENTER ";
                $join3 = true;
            }
        }
        if (!empty($_GET["cd_cpf"])) {
            $where .= " AND C.CD_CPF = ?";
            $join = true;

            $params[] = $_GET["cd_cpf"];
            $paramsTotal[] = $_GET["cd_cpf"];
        }

        if (!empty($_GET["nm_evento"])) {
            $where .= " AND E.ID_EVENTO = ?";
            $join4 = true;

            $from2 .= " LEFT JOIN MW_APRESENTACAO A ON IPV.ID_APRESENTACAO = A.ID_APRESENTACAO
                        INNER JOIN MW_EVENTO E ON E.ID_EVENTO=A.ID_EVENTO ";

            $params[] = $_GET["nm_evento"];
            $paramsTotal[] = $_GET["nm_evento"];
        }

        $selectTr = "SELECT PV.ID_PEDIDO_VENDA FROM MW_PEDIDO_VENDA PV ";
        if (isset($join)) {
            $selectTr .= " INNER JOIN MW_CLIENTE C ON C.ID_CLIENTE = PV.ID_CLIENTE ";
        }
        if (isset($join2)) {
            $selectTr .= "   PV.ID_PEDIDO_VENDA FROM MW_PEDIDO_VENDA PV
                            INNER JOIN MW_CLIENTE CL ON CL.ID_CLIENTE = PV.ID_CLIENTE AND PV.ID_USUARIO_CALLCENTER IS NULL ";
        }
        if (isset($join3)) {
            $selectTr .= "   PV.ID_PEDIDO_VENDA FROM MW_PEDIDO_VENDA PV
                            LEFT JOIN MW_USUARIO U ON U.ID_USUARIO=PV.ID_USUARIO_CALLCENTER ";
        }
        if (isset($join4)) {
            $selectTr = " SELECT DISTINCT PV.ID_PEDIDO_VENDA FROM MW_PEDIDO_VENDA PV
                        LEFT JOIN MW_ITEM_PEDIDO_VENDA IPV ON IPV.ID_PEDIDO_VENDA = PV.ID_PEDIDO_VENDA
                        LEFT JOIN MW_APRESENTACAO A ON IPV.ID_APRESENTACAO = A.ID_APRESENTACAO
                        INNER JOIN MW_EVENTO E ON E.ID_EVENTO=A.ID_EVENTO ";
        }
        $queryTr = $selectTr . $where;

        $tr = numRows($mainConnection, $queryTr, $params);
        $total_reg = (!isset($_GET["controle"])) ? 10 : $_GET["controle"];
        $offset = (isset($_GET["offset"])) ? $_GET["offset"] : 1;
        $final = ($offset + $total_reg) - 1;

        $params = array_merge($params, $params);

        $strSql = "WITH RESULTADO AS (" .
                $select .
                $from .
                $where .
                $group . "
				  
				  UNION ALL
                                  " .
                $select .
                $from2 .
                $where .
                $group . ")
				  SELECT * FROM RESULTADO WHERE LINHA BETWEEN " . $offset . " AND " . $final . " ORDER BY ID_PEDIDO_VENDA DESC";

        // EXECUTA QUERY PRINCIPAL PARA CONSULTAR PEDIDOS VENDIDOS
        $result = executeSQL($mainConnection, $strSql, $params);

        $query = "SELECT
                          SUM (IPV.VL_UNITARIO) AS TOTAL_PEDIDO
                  FROM
                          MW_PEDIDO_VENDA PV
                          LEFT JOIN MW_ITEM_PEDIDO_VENDA IPV ON IPV.ID_PEDIDO_VENDA = PV.ID_PEDIDO_VENDA ";
        if (isset($join)) {
            $query .= "INNER JOIN MW_CLIENTE C ON C.ID_CLIENTE = PV.ID_CLIENTE ";
        }
        if (isset($join2)) {
            $query .= "INNER JOIN MW_CLIENTE CL ON CL.ID_CLIENTE = PV.ID_CLIENTE AND PV.ID_USUARIO_CALLCENTER IS NULL ";
        }
        if (isset($join3)) {
            $query .= "LEFT JOIN MW_USUARIO U ON U.ID_USUARIO=PV.ID_USUARIO_CALLCENTER ";
        }
        if (isset($join4)) {
            $query .= "   LEFT JOIN MW_APRESENTACAO A ON IPV.ID_APRESENTACAO = A.ID_APRESENTACAO
                          INNER JOIN MW_EVENTO E ON E.ID_EVENTO = A.ID_EVENTO ";
        }
        $query .= $where;

        // Executa query para somar total de ingressos
        $rs = executeSQL($mainConnection, $query, $paramsTotal, true);
        $total['TOTAL_PEDIDO'] = $rs['TOTAL_PEDIDO'];

        $paramsTotal = array_merge($paramsTotal, $paramsTotal);

        $query = "SELECT
					  COUNT(1) AS QUANTIDADE,
                                          SUM(IPV.VL_TAXA_CONVENIENCIA) AS TOTALSERVICO
				  FROM 
					  MW_PEDIDO_VENDA PV
                                          LEFT JOIN MW_ITEM_PEDIDO_VENDA IPV ON IPV.ID_PEDIDO_VENDA = PV.ID_PEDIDO_VENDA ";

        if (isset($join)) {
            $query .= "INNER JOIN MW_CLIENTE C ON C.ID_CLIENTE = PV.ID_CLIENTE ";
        }
        if (isset($join2)) {
            $query .= "INNER JOIN MW_CLIENTE CL ON CL.ID_CLIENTE = PV.ID_CLIENTE AND PV.ID_USUARIO_CALLCENTER IS NULL ";
        }
        if (isset($join3)) {
            $query .= "LEFT JOIN MW_USUARIO U ON U.ID_USUARIO=PV.ID_USUARIO_CALLCENTER ";
        }
        if (isset($join4)) {
            $query .= "   LEFT JOIN MW_APRESENTACAO A ON IPV.ID_APRESENTACAO = A.ID_APRESENTACAO
                          LEFT JOIN MW_EVENTO E ON E.ID_EVENTO = A.ID_EVENTO ";
        }
        $query .= $where . "
                          UNION ALL

                          SELECT
                                  COUNT(1) AS QUANTIDADE,
                                  SUM(IPV.VL_TAXA_CONVENIENCIA) AS TOTALSERVICO
                          FROM
                                  MW_PEDIDO_VENDA PV
                                  INNER JOIN MW_ITEM_PEDIDO_VENDA_HIST IPV ON IPV.ID_PEDIDO_VENDA = PV.ID_PEDIDO_VENDA ";
        if (isset($join)) {
            $query .= "INNER JOIN MW_CLIENTE C ON C.ID_CLIENTE = PV.ID_CLIENTE ";
        }
        if (isset($join2)) {
            $query .= "INNER JOIN MW_CLIENTE CL ON CL.ID_CLIENTE = PV.ID_CLIENTE AND PV.ID_USUARIO_CALLCENTER IS NULL ";
        }
        if (isset($join3)) {
            $query .= "LEFT JOIN MW_USUARIO U ON U.ID_USUARIO=PV.ID_USUARIO_CALLCENTER ";
        }
        if (isset($join4)) {
            $query .= "   LEFT JOIN MW_APRESENTACAO A ON IPV.ID_APRESENTACAO = A.ID_APRESENTACAO
                          LEFT JOIN MW_EVENTO E ON E.ID_EVENTO = A.ID_EVENTO ";
        }
        $query .= $where;

        //Executa query para somar total de ingressos e calcular valor total dos serviços
        $result2 = executeSQL($mainConnection, $query, $paramsTotal);

        $total['QUANTIDADE'] = 0;
        $total['SERVICO'] = 0;
        while ($rs = fetchResult($result2)) {
            $total['QUANTIDADE'] += $rs['QUANTIDADE'];
            $total['SERVICO'] += $rs["TOTALSERVICO"];
        }
    }
?>
    <script type="text/javascript" src="../javascripts/jquery.ui.datepicker-pt-BR.js"></script>
    <script type="text/javascript" src="../javascripts/jquery.combobox-autocomplete.js"></script>
    <script type="text/javascript" src="../javascripts/simpleFunctions.js"></script>
    <script>
        $(function() {
            var pagina = '<?php echo $pagina; ?>';
            var $estorno_dialog = $('#estorno_form').hide();

            $('.numbersOnly').onlyNumbers();

            $('button, .button').button();
            //$(".datepicker").datepicker();
            $('input.datepicker').datepicker({
                changeMonth: true,
                changeYear: true,
                onSelect: function(date, e) {
                    if ($(this).is('#dt_inicial')) {
                        $('#dt_final').datepicker('option', 'minDate', $(this).datepicker('getDate'));
                    }
                }
            }).datepicker('option', $.datepicker.regional['pt-BR']);

            $("#btnRelatorio").click(function(){
                if (!verificaCPF($('#cd_cpf').val())) {
                    $.dialog({title: 'Alerta...', text: 'CPF inválido.'});
                } else {
                    document.location = '?p=' + pagina.replace('.php', '') + '&dt_inicial=' + $("#dt_inicial").val() + '&dt_final='+ $("#dt_final").val() + '&nm_cliente=' + $("#nm_cliente").val() + '&cd_cpf=' + $("#cd_cpf").val() + '&num_pedido=' + $("#num_pedido").val() + '&nm_operador='+ $("#nm_operador").val() +'&nm_evento=' + $("#evento").val();
                }
            });

            $('#tabPedidos tr:not(.ui-widget-header, .estorno)').hover(function() {
                $(this).addClass('ui-state-hover').next('.estorno').addClass('ui-state-hover');
            }, function() {
                $(this).removeClass('ui-state-hover').next('.estorno').removeClass('ui-state-hover');
            });

            $('#tabPedidos tr:not(.ui-widget-header, .total)').click(function() {
                $('loadingIcon').fadeIn('fast');
                var $this = $(this),
                url = $this.find('a').attr('destino');
                $.ajax({
                    url: url,
                    success: function(data) {
                        $('#tabPedidos').find('.itensDoPedido').hide();
                        $this.after('<tr class="itensDoPedido"><td colspan="10">' + data + '</td></tr>');
                    },
                    complete: function() {
                        $('loadingIcon').fadeOut('slow');
                    }
                });
            });

            $estorno_dialog.dialog({
                autoOpen: false,
                height: 'auto',
                width: 450,
                modal: true,
                closeOnEscape: false,
                draggable: false,
                buttons: {
                    'Confirmar': function() {
                        var $this = $(this),
                            valid = true;

                        if ($estorno_dialog.find('textarea').val() == '') {
                            $.dialog({title: 'Aviso...', text: 'A justificativa deve ser informada.'});
                            return;
                        }

                        if ($('.bank_data').is(':visible')) {
                            $('.bank_data').find('[name=banco], [name=cpf], [name=nome], [name=nr_agencia], [name=nr_conta]').each(function(){
                                if ($(this).val().trim().length < 1) {
                                    valid = false;
                                }
                            });

                            if (!valid) {
                                $.dialog({title: 'Aviso...', text: 'Os dados bancários devem ser informados.'});
                                return;
                            }
                        }

                        $this.parent().hide();

                        temp_dialog = $('<p title="Aguarde...">Processando...</p>').dialog({
                            open: function(event, ui){
                                $(event.target).parentsUntil('ui-dialog').find('.ui-dialog-buttonpane, button').hide()
                            }
                        });

                        $.ajax({
                            url: pagina,
                            type: 'post',
                            data: $(this).serialize(),
                            success: function(resp){
                                temp_dialog.dialog('close');
                                $this.dialog('close');
                                if (resp == 'ok') {
                                    $.dialog({
                                        title: 'Sucesso',
                                        text: 'Pedido cancelado/estornado.',
                                        uiOptions: {
                                            buttons: {
                                                'Ok': function() {
                                                    document.location = document.location;
                                                }
                                            }
                                        }
                                    });
                                } else {
                                    $.dialog({
                                        title: 'Aviso...',
                                        text: resp,
                                        uiOptions: {
                                            buttons: {
                                                'Ok': function() {
                                                    document.location = document.location;
                                                }
                                            }
                                        }
                                    });
                                }
                            }

                        });
                    },
                    'Cancelar': function() {
                        $(this).dialog('close');
                    }
                },
                close: function() {
                    $(this).find(':input').val('');
                }
            });

            $('.estorno').click(function(){
                var $this = $(this);

                $estorno_dialog.find('input[name="pedido"]').val($this.attr('pedido'));

                if (~$this.data('meio-pagamento').toLowerCase().indexOf('boleto')) {
                    $estorno_dialog.find('.bank_data').show();
                } else {
                    $estorno_dialog.find('.bank_data').hide();
                }

                $estorno_dialog.dialog('open');
            });

            $estorno_dialog.find('textarea').keyup(function(e) {
                var $this = $(this),
                    tval = $this.val(),
                    tlength = tval.length,
                    set = $this.attr('maxlength'),
                    remain = set - tlength;
                $this.next('p').find('span').text(remain);
                if (remain <= 0) {
                    $this.val((tval).substring(0, set))
                }
            }).keyup();

            $("#controle").change(function(){
                document.location = '?p=' + pagina.replace('.php', '') + '&controle=' + $("#controle").val() + '&dt_inicial=' + $("#dt_inicial").val() + '&dt_final=' + $("#dt_final").val() + '&nm_cliente=' + $("#nm_cliente").val() + '&cd_cpf=' + $("#cd_cpf").val() + '&num_pedido=' + $("#num_pedido").val() + '&nm_operador=' + $("#nm_operador").val() + '&nm_evento=' + $("#evento").val() + '';
            });

            $.ajax({
              url: pagina + '?action=load_evento_combo&nm_evento=<?php echo $_GET['nm_evento']; ?>',
              success: function(data) {
                $('#evento').html(data).combobox();
              }
            });

            $(':input[name=banco]').combobox();
        });    
    </script>
    <style type="text/css">
        #paginacao{
            width: 100%;
            text-align: center;
            margin-top: 10px;
        }

        .bank_data {
            margin-top: 15px;
        }

        .bank_data td {
            padding: 4px 0;
            width: 50%;
        }
    </style>
    <h2>Estorno de Pedidos</h2>
<?php
    $mes = date("m") - 1;
?>
    <table>
      <tr>
        <td>Pedido nº</td>
        <td><input size="10" type="text" value="<?php echo (isset($_GET["num_pedido"])) ? $_GET["num_pedido"] : "" ?>" id="num_pedido" name="num_pedido" /></td>
        <td>CPF</td>
        <td><input type="text" value="<?php echo (isset($_GET["cd_cpf"])) ? $_GET["cd_cpf"] : "" ?>" id="cd_cpf" name="cd_cpf" maxlength="13" /></td>
        <td>Nome do Cliente</td>
        <td><input size="40" type="text" value="<?php echo (isset($_GET["nm_cliente"])) ? $_GET["nm_cliente"] : "" ?>" id="nm_cliente" name="nm_cliente" /></td>
      </tr>
      <tr>
        <td>Data Inicial</td>
        <td><input type="text" value="<?php echo (isset($_GET["dt_inicial"])) ? $_GET["dt_inicial"] : date("d/m/Y") ?>" class="datepicker" id="dt_inicial" readonly name="dt_inicial" /></td>
        <td>Data Final</td>
        <td><input type="text" class="datepicker" value="<?php echo (isset($_GET["dt_final"])) ? $_GET["dt_final"] : date("d/m/Y") ?>" id="dt_final" name="dt_final" readonly/></td>
        <td>Nome do Operador</td>
        <td><input size="40" type="text" value="<?php echo (isset($_GET["nm_operador"])) ? $_GET["nm_operador"] : "" ?>" id="nm_operador" name="nm_operador" /></td>
      </tr>
      <tr>
        <td>Nome do Evento</td>
        <td><select name="evento" class="inputStyle" id="evento"><option value="">Carregando combo...</option></select></td>
        <td></td>
        <td></td>
        <td align="center"><input type="submit" class="button" id="btnRelatorio" value="Buscar" /></td>
        <td></td>
      </tr>
    </table><br/>

<form id="estorno_form" title="Dados para Estorno">
    <input type="hidden" name="pedido" />
    
    <p>Justificativa:</p>
    <textarea name="justificativa" maxlength="250" style="width:100%; height:100px;"></textarea>
    <p>Caracteres restantes: <span></span><p>

    <table class="bank_data">
        <tr><td>Banco:</td><td><?php echo comboBanco('banco'); ?></td></tr>
        <tr><td>Agência:</td><td><input type="text" name="nr_agencia" /></td></tr>
        <tr><td>Agência DV:</td><td><input type="text" name="dv_agencia" /></td></tr>
        <tr><td>Conta:</td><td><input type="text" name="nr_conta" /></td></tr>
        <tr><td>Conta DV:</td><td><input type="text" name="dv_conta" /></td></tr>
        <tr><td>CPF/CNPJ:</td><td><input type="text" class="numbersOnly" name="cpf" /></td></tr>
        <tr><td>Nome Favorecido:</td><td><input type="text" name="nome" /></td></tr>
    </table>
</form>

<!-- Tabela de pedidos -->
<table class="ui-widget ui-widget-content" id="tabPedidos">
    <thead>
        <tr class="ui-widget-header">
            <th style="text-align: center; width: 10px;">Visualizar</th>
            <th>Pedido nº</th>
            <th>Operador</th>
            <th>Data do Pedido</th>
            <th>Cliente e Telefone</th>
            <th>Valor total</th>
            <th>Qtde Ingressos</th>
            <th>Situação</th>
            <th>Forma de Entrega</th>
            <th>Ações</th>
        </tr>
    </thead>
    <tbody>
        <?php
               if (isset($result)) {
                   while ($rs = fetchResult($result)) {
        ?>
                       <tr>
                           <td style="text-align: center;"><a style="cursor: pointer;" destino="listaItens.php?pedido=<?php echo $rs['ID_PEDIDO_VENDA']; ?>&evento=<?php echo $_GET["nm_evento"]; ?>">+</a></td>
                           <td><?php echo $rs['ID_PEDIDO_VENDA']; ?></td>
                           <td>
                <?php
                       if (empty($rs['DS_NOME'])) {
                           echo 'Web';
                       } else {
                           echo $rs['DS_NOME'];
                       }
                ?>
                   </td>
                   <td><?php echo $rs['DT_PEDIDO_VENDA'] ?></td>
                   <td><?php echo utf8_encode2($rs['CLIENTE'] . " " . $rs['DS_SOBRENOME']) . "<br/>" . $rs['DS_DDD_TELEFONE'] . " " . $rs['DS_TELEFONE']; ?></td>
                   <td><?php echo number_format($rs['TOTAL_UNIT'], 2, ",", "."); ?></td>
                   <td><?php echo $rs['QUANTIDADE']; ?></td>
                   <td><?php echo comboSituacao('situacao', $rs['IN_SITUACAO'], false); ?></td>
                   <td><?php echo comboFormaEntrega($rs['IN_RETIRA_ENTREGA']); ?></td>
                   <td><button class="estorno" pedido="<?php echo $rs['ID_PEDIDO_VENDA']; ?>" data-meio-pagamento="<?php echo utf8_encode2($rs['NM_CARTAO_EXIBICAO_SITE']); ?>">Estornar</button></td>
               </tr>
        <?php
                   }
               }
        ?>
           </tbody>
       </table>
       <div id="paginacao">
    <?php
               if ($tr) {
                   //paginacao($pc, $intervalo, $tp, true);
                   $link = "?p=" . basename($pagina, '.php') . "&dt_inicial=" . $_GET["dt_inicial"] . "&dt_final=" . $_GET["dt_final"] . "&num_pedido=" . $_GET["num_pedido"] . "&nm_cliente=" . $_GET["nm_cliente"] . "&nm_operador=" . $_GET["nm_operador"] . "&cd_cpf=" . $_GET["cd_cpf"] . "&nm_evento=" . $_GET["nm_evento"] . "&controle=" . $total_reg . "&bar=2&baz=3&offset=";
                   //$link = "?p=listaMovimentacao&dt_inicial=" . $_GET["dt_inicial"] . "&dt_final=" . $_GET["dt_final"] . "&situacao=" . $situacao . "&controle=" . $total_reg . "&bar=2&baz=3&offset=";
                   Paginator::paginate($offset, $tr, $total_reg, $link, true);
               }
    ?>
           </div>

<?php
    }
}
?>
