<?php
require_once('../settings/functions.php');
include('../settings/Log.class.php');
$mainConnection = mainConnection();
session_start();

if (acessoPermitido($mainConnection, $_SESSION['admin'], 216, true)) {

    require_once('../settings/Paginator.php');

    $pagina = basename(__FILE__);

    if (isset($_GET['action'])) {

        require('actions/' . $pagina);
    } else {

        if (isset($_GET["dt_inicial"]) && isset($_GET["dt_final"]) && isset($_GET["situacao"]) && isset($_GET["nm_cliente"]) && isset($_GET["cd_cpf"]) && isset($_GET["num_pedido"])) {

            $where = "WHERE CONVERT(DATETIME,CONVERT(CHAR(8), PV.DT_PEDIDO_VENDA, 112)) BETWEEN CONVERT(DATETIME, ?, 103) AND CONVERT(DATETIME, ?, 103) 
                        AND PV.IN_SITUACAO = 'F' AND PV.IN_RETIRA_ENTREGA = 'E' AND PV.ID_PEDIDO_PAI IS NULL ";

            $params = array($_GET["dt_inicial"], $_GET["dt_final"]);

            $paramsTotal = array($_GET["dt_inicial"], $_GET["dt_final"]);

            $select = "SELECT
                    (CONVERT(VARCHAR(10), PV.DT_PEDIDO_VENDA, 103) + ' - ' + CONVERT(VARCHAR(8), PV.DT_PEDIDO_VENDA, 114)) AS DT_PEDIDO_VENDA,
                    PV.ID_PEDIDO_VENDA,
                    C.DS_NOME AS CLIENTE,
                    C.DS_SOBRENOME,
                    SUM(IPV.VL_UNITARIO) AS TOTAL_UNIT,
                    PV.IN_SITUACAO,
                    ROW_NUMBER() OVER(ORDER BY PV.ID_PEDIDO_VENDA) AS 'LINHA',
                    COUNT(1) AS QUANTIDADE,
                    PV.IN_RETIRA_ENTREGA,
                    C.DS_DDD_TELEFONE,
                    C.DS_TELEFONE,
                    U.DS_NOME ";

            $from = " FROM MW_PEDIDO_VENDA PV INNER JOIN MW_CLIENTE C ON C.ID_CLIENTE = PV.ID_CLIENTE
                          LEFT JOIN MW_ITEM_PEDIDO_VENDA IPV ON IPV.ID_PEDIDO_VENDA = PV.ID_PEDIDO_VENDA
                          LEFT JOIN MW_USUARIO U ON U.ID_USUARIO=PV.ID_USUARIO_CALLCENTER ";

            $from2 = "FROM
                      MW_PEDIDO_VENDA PV
                      INNER JOIN MW_CLIENTE C ON C.ID_CLIENTE = PV.ID_CLIENTE
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
                      PV.VL_TOTAL_TAXA_CONVENIENCIA";

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

            if (!empty($_GET["nm_evento"])) {
                $where .= " AND E.ID_EVENTO = ?";
                $join4 = true;

                $from2 .= " LEFT JOIN MW_APRESENTACAO A ON IPV.ID_APRESENTACAO = A.ID_APRESENTACAO
                            INNER JOIN MW_EVENTO E ON E.ID_EVENTO=A.ID_EVENTO ";

                $params[] = $_GET["nm_evento"];
                $paramsTotal[] = $_GET["nm_evento"];
            }

            if (!empty($_GET["cd_cpf"])) {
                $where .= " AND C.CD_CPF = ?";
                $join2 = true;

                $params[] = $_GET["cd_cpf"];
                $paramsTotal[] = $_GET["cd_cpf"];
            }

            if ($_GET["situacao"] == 2) {
                $where .= " AND PV.DT_ENTREGA_INGRESSO IS NULL";
            } else {
                $where .= " AND PV.DT_ENTREGA_INGRESSO IS NOT NULL";

                $select .= " , PV.DT_ENTREGA_INGRESSO ";

                $group .= " , PV.DT_ENTREGA_INGRESSO ";
            }

            $selectTr = "SELECT PV.ID_PEDIDO_VENDA FROM MW_PEDIDO_VENDA PV ";
            if (isset($join)) {
                $selectTr .= " INNER JOIN MW_CLIENTE C ON C.ID_CLIENTE = PV.ID_CLIENTE ";
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
				  SELECT * FROM RESULTADO WHERE LINHA BETWEEN " . $offset . " AND " . $final . " ORDER BY ID_PEDIDO_VENDA ";
            $result = executeSQL($mainConnection, $strSql, $params);

            $query = "SELECT
                          SUM (IPV.VL_UNITARIO) AS TOTAL_PEDIDO
                  FROM
                          MW_PEDIDO_VENDA PV
                          LEFT JOIN MW_ITEM_PEDIDO_VENDA IPV ON IPV.ID_PEDIDO_VENDA = PV.ID_PEDIDO_VENDA ";

            if ($join OR $join2) {
                $query .= "INNER JOIN MW_CLIENTE C ON C.ID_CLIENTE = PV.ID_CLIENTE ";
            }
            if (isset($join4)) {
                $query .= "   LEFT JOIN MW_APRESENTACAO A ON IPV.ID_APRESENTACAO = A.ID_APRESENTACAO
                              LEFT JOIN MW_EVENTO E ON E.ID_EVENTO = A.ID_EVENTO ";
            }

            $query .= $where;
            $rs = executeSQL($mainConnection, $query, $paramsTotal, true);

            $total['TOTAL_PEDIDO'] = $rs['TOTAL_PEDIDO'];

            $paramsTotal = array_merge($paramsTotal, $paramsTotal);

            $query = "SELECT
					  COUNT(1) AS QUANTIDADE,
                                          SUM(IPV.VL_TAXA_CONVENIENCIA) AS TOTALSERVICO
				  FROM 
					  MW_PEDIDO_VENDA PV
                                          LEFT JOIN MW_ITEM_PEDIDO_VENDA IPV ON IPV.ID_PEDIDO_VENDA = PV.ID_PEDIDO_VENDA ";

            if ($join OR $join2) {
                $query .= "INNER JOIN MW_CLIENTE C ON C.ID_CLIENTE = PV.ID_CLIENTE ";
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
            if ($join OR $join2) {
                $query .= "INNER JOIN MW_CLIENTE C ON C.ID_CLIENTE = PV.ID_CLIENTE ";
            }
            if (isset($join4)) {
                $query .= "   LEFT JOIN MW_APRESENTACAO A ON IPV.ID_APRESENTACAO = A.ID_APRESENTACAO
                              LEFT JOIN MW_EVENTO E ON E.ID_EVENTO = A.ID_EVENTO ";
            }
            $query .= $where;
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
        <script type="text/javascript" src="../javascripts/simpleFunctions.js"></script>
        <script type="text/javascript" src="../javascripts/date.format.js"></script>
        <script>
            $(function() {
                var pagina = '<?php echo $pagina; ?>';
                var dtInicialOpc = '<?php echo $_GET["dt_inicial"] ?>';
                var dtFinalOpc = '<?php echo $_GET["dt_final"] ?>';

                $('input.button, a.button').button();

                $('#dt_final').datepicker({
                    changeMonth: true,
                    changeYear: true,
                    minDate: dtInicialOpc
                });

                $('input.datepicker').datepicker({
                    changeMonth: true,
                    changeYear: true,
                    onSelect: function(date, e) {
                        if ($(this).is('#dt_inicial')) {
                            $('#dt_final').datepicker('option', 'minDate', $(this).datepicker('getDate'));
                        }
                    }
                }).datepicker('option', $.datepicker.regional['pt-BR']);


                function setDatePickers2() {
                    $("input.datepicker").datepicker({
                        maxDate: '' + dtFinalOpc +'',
                        changeMonth: true,
                        changeYear: true,
                        dateFormate: 'dd/mm/yy',
                        onSelect: function(dateText, inst){
                            var the_date = new Date(dateText);
                            $("#dt_entrega").datepicker('option', 'maxDate',  the_date);
                        }
                    }).datepicker('option', $.datepicker.regional['pt-BR']);
                    $("dt_entrega").datepicker({ dateFormat: 'dd/mm/yy' });
                }

                $("#btnRelatorio").click(function(){
                    $('input.datepicker').datepicker({
                        changeMonth: true,
                        changeYear: true,
                        onSelect: function(date, e) {
                            if ($(this).is('#dt_inicial')) {
                                $('#dt_final').datepicker('option', 'minDate', $(this).datepicker('getDate'));
                            }
                        }
                    }).datepicker('option', $.datepicker.regional['pt-BR']);


                    if(!verificaCPF($('#cd_cpf').val()))
                    {
                        $.dialog({title: 'Alerta...', text: 'CPF inválido.'});
                    }else{ if($('#cboSituacao').val() == 0){
                            $.dialog({title: 'Alerta...', text: 'Selecione uma situação para efetuar a pesquisa.'});
                        }else{
                            document.location = '?p=' + pagina.replace('.php', '') + '&dt_inicial=' + $("#dt_inicial").val() + '&dt_final='+ $("#dt_final").val() + '&situacao=' + $("#cboSituacao").val() + '&nm_cliente=' + $("#nm_cliente").val() + '&cd_cpf=' + $("#cd_cpf").val() + '&num_pedido=' + $("#num_pedido").val() + '&nm_evento=' + $("#evento").val();
                        }}
                });

                $('#app table').delegate('a', 'click', function(event) {
                    event.preventDefault();

                    var $this = $(this),
                    href = $this.attr('href'),
                    id = 'id=' + $.getUrlVar('id', href),
                    tr = $this.closest('tr');

                    if (href.indexOf('?action=add') != -1 || href.indexOf('?action=update') != -1) {

                        $.ajax({
                            url: href,
                            type: 'post',
                            data: $('#dados').serialize(),
                            success: function(data) {
                                if (data.substr(0, 4) == 'true') {
                                    var id = $.serializeUrlVars(data);

                                    tr.find('td:not(.button):eq(0)').html($('#detalhes').val());
                                    tr.find('td:not(.button):eq(1)').html($('#pedido').val());
                                    tr.find('td:not(.button):eq(2)').html($('#dt_pedido').val());
                                    tr.find('td:not(.button):eq(3)').html($('#nome').val());
                                    tr.find('td:not(.button):eq(4)').html($('#total').val());
                                    tr.find('td:not(.button):eq(5)').html($('#qtde_ingressos').val());
                                    tr.find('td:not(.button):eq(6)').html($('#situacao').val());
                                    tr.find('td:not(.button):eq(7)').html($('#dt_entrega').val());

                                    $this.text('Editar').attr('href', pagina + '?action=edit&' + id);
                                    tr.removeAttr('id');
                                } else {
                                    $.dialog({text: data});
                                }
                            }
                        });
                    } else if (href.indexOf('?action=edit') != -1) {
                        if(!hasNewLine()) return false;

                        var values = new Array();

                        tr.attr('id', 'newLine');

                        $.each(tr.find('td:not(.button)'), function() {
                            values.push($(this).text());
                        });

                        if(trim(values[7])=='-'){
                            data = new Date();
                            data2 = data.format("d/m/yyyy");
                        }else{
                            data2 = values[7];
                        }

                        tr.find('td:not(.button):eq(0)').html('<input name="detalhes" size="10" type="text" class="readonly inputStyle" id="detalhes" maxlength="100" value="' + values[0] + '" />');
                        tr.find('td:not(.button):eq(1)').html('<input name="pedido" size="10" type="text" class="inputStyle readonly" id="pedido" maxlength="100" value="' + values[1] + '" readonly />');
                        tr.find('td:not(.button):eq(2)').html('<input name="dt_pedido" type="text" class="inputStyle readonly" id="dt_pedido" maxlength="100" value="' + values[2] + '" readonly />');
                        tr.find('td:not(.button):eq(3)').html('<input name="nome" type="text" class="inputStyle readonly" id="nome" maxlength="100" value="' + values[3] + '" readonly />');
                        tr.find('td:not(.button):eq(4)').html('<input name="total" type="text" size="10" class="inputStyle readonly" id="total" maxlength="100" value="' + values[4] + '" readonly />');
                        tr.find('td:not(.button):eq(5)').html('<input name="qtde_ingressos" size="5" type="text" class="inputStyle readonly" id="qtde_ingressos" maxlength="100" value="' + values[5] + '" readonly />');
                        tr.find('td:not(.button):eq(6)').html('<input name="situacao" type="text" class="inputStyle readonly" id="situacao" maxlength="100" value="' + trim(values[6]) + '"  readonly/>');
                        tr.find('td:not(.button):eq(7)').html('<input name="dt_entrega" size="15" type="text" class="inputStyle datepicker" id="dt_entrega" maxlength="100" value="' + data2 + '" />');

                        $this.text('Salvar').attr('href', pagina + '?action=update&' + id);

                        setDatePickers2();
                    } else if (href == '#delete') {
                        tr.remove();
                    }
                });

                $('.itensDetalhe').click(function() {
                    $('loadingIcon').fadeIn('fast');
                    var $this = $(this),
                    url = $this.attr('destino');
                    $.ajax({
                        url: url,
                        success: function(data) {
                            $('#tabPedidos').find('.itensDoPedido').hide();
                            $this.parent().parent().after('<tr class="itensDoPedido"><td colspan="10">' + data + '</td></tr>');
                        },
                        complete: function() {
                            $('loadingIcon').fadeOut('slow');
                        }
                    });
                });

                $("#controle").change(function(){
                document.location = '?p=' + pagina.replace('.php', '') + '&controle=' + $("#controle").val() + '&dt_inicial=' + $("#dt_inicial").val() + '&dt_final='+ $("#dt_final").val() + '&situacao=' + $("#cboSituacao").val() + '&nm_cliente=' + $("#nm_cliente").val() + '&cd_cpf=' + $("#cd_cpf").val() + '&num_pedido=' + $("#num_pedido").val() + '&nm_evento=' + $("#evento").val() + '';
            });
            });

        </script>
        <style type="text/css">
            #paginacao{
                width: 100%;
                text-align: center;
                margin-top: 10px;
            }
        </style>
        <h2>Entrega de Ingressos</h2>
<?php
        $mes = date("m") - 1;
?>
        <p>
            Pedido nº&nbsp;&nbsp; <input size="10" type="text" value="<?php echo (isset($_GET["num_pedido"])) ? $_GET["num_pedido"] : "" ?>" id="num_pedido" name="num_pedido" /> &nbsp;&nbsp;&nbsp;
            &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
            CPF <input type="text" value="<?php echo (isset($_GET["cd_cpf"])) ? $_GET["cd_cpf"] : "" ?>" id="cd_cpf" name="cd_cpf" maxlength="13" /> &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
            Cliente <input size="40" type="text" value="<?php echo (isset($_GET["nm_cliente"])) ? $_GET["nm_cliente"] : "" ?>" id="nm_cliente" name="nm_cliente" /><br/>
        </p><br/>
        <p>
            Data Inicial <input type="text" value="<?php echo (isset($_GET["dt_inicial"])) ? $_GET["dt_inicial"] : date("d/m/Y") ?>" class="datepicker" id="dt_inicial" readonly name="dt_inicial" />&nbsp;&nbsp;&nbsp;
            Data Final <input type="text" class="datepicker" value="<?php echo (isset($_GET["dt_final"])) ? $_GET["dt_final"] : date("d/m/Y") ?>" id="dt_final" name="dt_final" readonly/> &nbsp;&nbsp;&nbsp;
            Situação <select name="cboSituacao" class="inputStyle" id="cboSituacao">
        <?php
        $opcoes = array(0 => 'Selecione uma situação',
            1 => 'Ingressos Entregues',
            2 => 'Ingressos a Entregar');
        foreach ($opcoes as $i => $valor) {
            $selected = '';
            if ($_GET['situacao'] == $i)
                $selected = 'selected';
            echo "<option value=" . $i . " " . $selected . ">" . $valor . "</option>";
        }
        ?>
    </select>
</p><br/>
<p>
    <?php
        $name = "evento";
        $queryEvento = 'SELECT E.ID_EVENTO, E.DS_EVENTO FROM MW_EVENTO E WHERE IN_ATIVO = 1 ORDER BY DS_EVENTO ASC';
        $resultEventos = executeSQL($mainConnection, $queryEvento, null);
        $combo = '<select name="' . $name . '" class="inputStyle" id="' . $name . '"><option value="">Selecione um evento...</option>';

        while ($rs = fetchResult($resultEventos)) {
            $combo .= '<option value="' . $rs['ID_EVENTO'] . '"' .
                    (($_GET["nm_evento"] == $rs['ID_EVENTO']) ? ' selected' : '' ) .
                    '>' . utf8_encode2($rs['DS_EVENTO']) . '</option>';
        }
        $combo .= '</select>';
    ?>
        Evento &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<?php echo $combo; ?> &nbsp;&nbsp;&nbsp;
        <input type="submit" class="button" id="btnRelatorio" value="Buscar" />
    </p><br/>
    <!--<p>
    <// if (isset($result) && hasRows($result)) {
    ?>
        &nbsp;&nbsp;<a class="button" href="gerarExcel.php?dt_inicial=< echo $_GET["dt_inicial"]; ?>&dt_final=< echo $_GET["dt_final"]; ?>&situacao=< echo $_GET["situacao"]; ?>&num_pedido=<
        if (isset($_GET["num_pedido"])) {
            echo $_GET["num_pedido"];
        } else {
            echo "";
        } ?>&nm_cliente=<echo $_GET["nm_cliente"]; ?>&cd_cpf=< echo $_GET["cd_cpf"]; ?>">Exportar Excel</a>
               < } ?>
    </p><br>-->

    <form id="dados" name="dados" method="post">
        <table class="ui-widget ui-widget-content" id="tabPedidos">
            <thead>
                <tr class="ui-widget-header ">
                    <th style="text-align: center;" width="2%">Visualizar</th>
                    <th width="10%">Pedido nº</th>
                    <th width="15%">Data do Pedido</th>
                    <th width="15%">Cliente e Telefone</th>
                    <th width="10%">Valor Total</th>
                    <th width="10%">Qtde Ingressos</th>
                    <th width="15%">Situação</th>
                    <th width="15%">Data da Entrega</th>
                    <th width="5%">Ações</th>
                </tr>
            </thead>
            <tbody>
            <?php
            while ($rs = fetchResult($result)) {
                $id = $rs['ID_PEDIDO_VENDA'];
                $diaEntrega = $rs["DT_ENTREGA_INGRESSO"];
                $diaEntrega = substr($rs['DT_ENTREGA_INGRESSO'], -2) . '/' . substr($rs['DT_ENTREGA_INGRESSO'], 4, 2) . '/' . substr($rs['DT_ENTREGA_INGRESSO'], 0, 4);
            ?>
                <tr>
                    <td style="text-align: center;"><a style="cursor: pointer;" class="itensDetalhe" destino="listaItens.php?pedido=<?php echo $rs['ID_PEDIDO_VENDA']; ?>">+</a></td>
                    <td><?php echo $rs['ID_PEDIDO_VENDA']; ?></td>
                    <td><?php echo $rs['DT_PEDIDO_VENDA']; ?></td>
                    <td><?php echo utf8_encode2($rs['CLIENTE'] . " " . $rs['DS_SOBRENOME']) . "<br/>" . $rs['DS_DDD_TELEFONE'] . " " . $rs['DS_TELEFONE']; ?></td>
                    <td><?php echo number_format($rs['TOTAL_UNIT'], 2, ",", "."); ?></td>
                    <td><?php echo $rs['QUANTIDADE']; ?></td>
                    <td>
                    <?php
                    if ($rs['DT_ENTREGA_INGRESSO'] == NULL) {
                        echo "Ingresso a Entregar";
                    } else {
                        if ($rs['DT_ENTREGA_INGRESSO'] != NULL) {
                            echo "Ingresso Entregues";
                        }
                    }
                    ?>
                </td>
                <td><?php
                    if ($rs["DT_ENTREGA_INGRESSO"] != NULL) {
                        echo $rs["DT_ENTREGA_INGRESSO"]->format("d/m/Y H:i");
                    } else {
                        echo " - ";
                    }
                    ?>
                </td>
                <td class="button"><a class="edit" href="<?php echo $pagina; ?>?action=edit&id=<?php echo $id; ?>">Editar</a></td>
            </tr>
            <?php
                }
            ?>
                <tr class="total">
                    <td align="right" colspan="4"><strong>Totais</strong></td>
                    <td><?php echo number_format($total['TOTAL_PEDIDO'], 2, ",", "."); ?></td>
                    <td><?php echo $total['QUANTIDADE']; ?></td>
                    <td colspan="2"><strong>Total de Serviços</strong> <?php echo number_format($total['SERVICO'], 2, ",", "."); ?></td>
                </tr>
            </tbody>
        </table>
    </form>
    <div id="paginacao">
    <?php
                //paginacao($pc, $intervalo, $tp, true);
                $link = "?p=entregarIngressos&dt_inicial=" . $_GET["dt_inicial"] . "&dt_final=" . $_GET["dt_final"] . "&situacao=" . $_GET["situacao"] . "&num_pedido=" . $_GET["num_pedido"] . "&nm_cliente=" . $_GET["nm_cliente"] . "&cd_cpf=" . $_GET["cd_cpf"] . "&nm_evento=" . $_GET["nm_evento"] . "&controle=" . $total_reg . "&bar=2&baz=3&offset=";
                Paginator::paginate($offset, $tr, $total_reg, $link, true);
    ?>
            </div>
<?php
            }
        }
?>