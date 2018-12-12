<?php
require_once('../settings/functions.php');
$mainConnection = mainConnection();
session_start();

if (acessoPermitido($mainConnection, $_SESSION['admin'], 12, true)) {

    $pagina = basename(__FILE__);

    if (isset($_GET["dt_inicial"]) && isset($_GET["dt_final"]) && isset($_GET["situacao"]) && isset($_GET["nm_cliente"]) && isset($_GET["nm_operador"]) && isset($_GET["cd_cpf"]) && isset($_GET["num_pedido"])) {

        $where = "WHERE CONVERT(DATETIME,CONVERT(CHAR(8), PV.DT_PEDIDO_VENDA, 112)) BETWEEN CONVERT(DATETIME, ?, 103) AND CONVERT(DATETIME, ?, 103) AND PV.IN_SITUACAO = ?";

        $params = array($_GET["dt_inicial"], $_GET["dt_final"], $_GET["situacao"]);

        $paramsTotal = array($_GET["dt_inicial"], $_GET["dt_final"], $_GET["situacao"]);

        $select = "SELECT
                    (CONVERT(VARCHAR(10), PV.DT_PEDIDO_VENDA, 103) + ' - ' + CONVERT(VARCHAR(8), PV.DT_PEDIDO_VENDA, 114)) AS DT_PEDIDO_VENDA,
                    PV.ID_PEDIDO_VENDA,
                    C.DS_NOME AS CLIENTE,
                    C.DS_SOBRENOME,
                    C.CD_EMAIL_LOGIN,
                    SUM(IPV.VL_UNITARIO) AS TOTAL_UNIT,
                    PV.IN_SITUACAO,
                    ROW_NUMBER() OVER(ORDER BY PV.ID_PEDIDO_VENDA DESC) AS 'LINHA',
                    COUNT(1) AS QUANTIDADE,
                    PV.IN_RETIRA_ENTREGA,
                    C.DS_DDD_TELEFONE,
                    C.DS_TELEFONE,
                    U.DS_NOME,
                    PV.ID_IP,
                    PV.ID_USUARIO_ESTORNO,
                    PV.DS_MOTIVO_CANCELAMENTO ";

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
                      C.CD_EMAIL_LOGIN,
                      PV.IN_SITUACAO,
                      DT_PEDIDO_VENDA,
                      PV.IN_RETIRA_ENTREGA,
                      C.DS_DDD_TELEFONE,
                      C.DS_TELEFONE,
                      U.DS_NOME,
                      PV.ID_IP,
                      PV.ID_USUARIO_ESTORNO,
                      PV.DS_MOTIVO_CANCELAMENTO,
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

        if (!empty($_GET["nm_operador"])) {
            if (strtolower($_GET["nm_operador"]) == 'web') {
                $select = "SELECT
                            (CONVERT(VARCHAR(10), PV.DT_PEDIDO_VENDA, 103) + ' - ' + CONVERT(VARCHAR(8), PV.DT_PEDIDO_VENDA, 114)) AS DT_PEDIDO_VENDA,
                            PV.ID_PEDIDO_VENDA,
                            C.DS_NOME AS CLIENTE,
                            C.DS_SOBRENOME,
                            C.CD_EMAIL_LOGIN,
                            SUM(IPV.VL_UNITARIO) AS TOTAL_UNIT,
                            PV.IN_SITUACAO,
                            ROW_NUMBER() OVER(ORDER BY PV.ID_PEDIDO_VENDA DESC) AS 'LINHA',
                            COUNT(1) AS QUANTIDADE,
                            PV.IN_RETIRA_ENTREGA,
                            C.DS_DDD_TELEFONE,
                            C.DS_TELEFONE,
                            PV.ID_IP,
                            PV.ID_USUARIO_ESTORNO,
                            PV.DS_MOTIVO_CANCELAMENTO ";

                $group = " GROUP BY
                              (CONVERT(VARCHAR(10), PV.DT_PEDIDO_VENDA, 103) + ' - ' + CONVERT(VARCHAR(8), PV.DT_PEDIDO_VENDA, 114)),
                              PV.ID_PEDIDO_VENDA,
                              C.DS_NOME,
                              C.DS_SOBRENOME,
                              C.CD_EMAIL_LOGIN,
                              PV.IN_SITUACAO,
                              DT_PEDIDO_VENDA,
                              PV.IN_RETIRA_ENTREGA,
                              C.DS_DDD_TELEFONE,
                              C.DS_TELEFONE,
                              PV.ID_IP,
                              PV.ID_USUARIO_ESTORNO,
                              PV.DS_MOTIVO_CANCELAMENTO ";

                $from = "FROM MW_PEDIDO_VENDA PV INNER JOIN MW_CLIENTE C ON C.ID_CLIENTE = PV.ID_CLIENTE AND PV.ID_USUARIO_CALLCENTER IS NULL
                          LEFT JOIN MW_ITEM_PEDIDO_VENDA_HIST IPV ON IPV.ID_PEDIDO_VENDA = PV.ID_PEDIDO_VENDA ";
                $join2 = true;
            } else {
                $where .= " AND U.DS_NOME LIKE '%" . utf8_decode(trim($_GET["nm_operador"])) . "%'";
                $from = "FROM MW_PEDIDO_VENDA PV INNER JOIN MW_CLIENTE C ON C.ID_CLIENTE = PV.ID_CLIENTE
                          LEFT JOIN MW_ITEM_PEDIDO_VENDA IPV ON IPV.ID_PEDIDO_VENDA = PV.ID_PEDIDO_VENDA
                          LEFT JOIN MW_USUARIO U ON U.ID_USUARIO=PV.ID_USUARIO_CALLCENTER 
                          LEFT JOIN MW_APRESENTACAO A ON IPV.ID_APRESENTACAO = A.ID_APRESENTACAO
                        INNER JOIN MW_EVENTO E ON E.ID_EVENTO=A.ID_EVENTO ";
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
    <script type="text/javascript" src="../javascripts/simpleFunctions.js"></script>
    <script>
        $(function() {
            var pagina = '<?php echo $pagina; ?>'
            $('.button').button();
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
                if(!verificaCPF($('#cd_cpf').val()))
                {
                    $.dialog({title: 'Alerta...', text: 'CPF inválido.'});
                }else{ if($('#situacao').val() == "V"){
                        $.dialog({title: 'Alerta...', text: 'Selecione a situação'});
                    }else{
                        document.location = '?p=' + pagina.replace('.php', '') + '&dt_inicial=' + $("#dt_inicial").val() + '&dt_final='+ $("#dt_final").val() + '&situacao=' + $("#situacao").val() + '&nm_cliente=' + $("#nm_cliente").val() + '&cd_cpf=' + $("#cd_cpf").val() + '&num_pedido=' + $("#num_pedido").val() + '&nm_operador='+ $("#nm_operador").val() +'&nm_evento=' + $("#evento").val();
                    }}
            });

            $('tr:not(.ui-widget-header)').hover(function() {
                $(this).addClass('ui-state-hover').next('.estorno').addClass('ui-state-hover');
            }, function() {
                $(this).removeClass('ui-state-hover').next('.estorno').removeClass('ui-state-hover');
            });
        });    
    </script>
    <h2>Consulta de Pedidos</h2>

<table>
  <tr>
    <td>
      Local
      <?php echo comboTeatroPorUsuario('teatro', $_SESSION['admin'], $_GET['teatro']) ?>
    </td>
    <td>
      Tipo de Lançamento
      <?php echo comboTipoLancamento('lancamento', $_GET['teatro'], $_GET['lancamento']) ?>
    </td>
    <td>
      Evento
      <?php echo comboEventoPorUsuario('evento', $_GET['teatro'], $_SESSION['admin'], $_GET['evento']) ?>
    </td>
  </tr>
  <tr>
    <td>
      Data Inicial
      <input type="text" class="datepicker" value="<?php echo (isset($_GET["dt_inicial"])) ? $_GET["dt_inicial"] : date("d/m/Y") ?>" id="dt_inicial" name="dt_inicial" readonly/>
    </td>
    <td>
      Data Final
      <input type="text" class="datepicker" value="<?php echo (isset($_GET["dt_final"])) ? $_GET["dt_final"] : date("d/m/Y") ?>" id="dt_final" name="dt_final" readonly/>
    </td>
    <td>
      Usuário
      <?php echo comboUsuariosPorBase('usuario', $_GET['teatro'], $_GET['usuario']) ?>
    </td>
  </tr>
</table>

<!-- Tabela de pedidos -->
<table class="ui-widget ui-widget-content" id="tabPedidos">
    <thead>
        <tr class="ui-widget-header">
            <th>Data da Movimentação</th>
            <th>Usuário</th>
            <th>Espetáculo</th>
            <th>Apresentação</th>
            <th>Lugares</th>
            <th>Tipo de Ingresso</th>
            <th>Valor</th>
            <th>Forma Pagamento</th>
            <th>Nome Cliente</th>
            <th>Telefone</th>
            <th>CPF</th>
        </tr>
    </thead>
    <tbody>
<?php
      if (isset($result)) {
          while ($rs = fetchResult($result)) {
?>
              <tr>
                  <td><?php echo $rs['Data da Venda'] ?></td>
                  <td><?php echo $rs['Nome Usuário'] ?></td>
                  <td><?php echo $rs['Nome da Peça'] ?></td>
                  <td><?php echo $rs['Data da Apresentação'] . ' às ' . $rs['Hora da Sessão'] ?></td>
                  <td><?php echo $rs['Poltrona'] ?></td>
                  <td><?php echo $rs['Ingresso'] ?></td>
                  <td><?php echo number_format($rs['Valor Bruto'], 2, ",", ".") ?></td>
                  <td><?php echo $rs['Forma de Pagamento'] ?></td>
                  <td><?php echo $rs['Nome do Cliente'] ?></td>
                  <td><?php echo $rs['Telefone'] ?></td>
                  <td><?php echo $rs['CPF'] ?></td>
              </tr>
<?php
          }
      }
?>
    </tbody>
</table>

<?php
}
?>
