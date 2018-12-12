<?php
header("Content-type: application/vnd.ms-excel");
header("Content-type: application/force-download");
header("Content-Disposition: attachment; filename=movimentacao.xls");

require_once('acessoLogadoDie.php');

require_once('../settings/functions.php');

$pagina = basename(__FILE__);
$mainConnection = mainConnection();

if (isset($_GET["dt_inicial"]) && isset($_GET["dt_final"]) && isset($_GET["situacao"]) && isset($_GET["nm_cliente"]) && isset($_GET["nm_operador"]) && isset($_GET["cd_cpf"]) && isset($_GET["num_pedido"])) {

    $where = "WHERE CONVERT(DATETIME,CONVERT(CHAR(8), PV.DT_PEDIDO_VENDA, 112)) BETWEEN CONVERT(DATETIME, '". $_GET["dt_inicial"] ."', 103) AND CONVERT(DATETIME, '". $_GET["dt_final"] ."', 103) AND PV.IN_SITUACAO = '". $_GET["situacao"] ."'";

    $params = array($_GET["dt_inicial"], $_GET["dt_final"], $_GET["situacao"]);

    $paramsTotal = array($_GET["dt_inicial"], $_GET["dt_final"], $_GET["situacao"]);

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
                    C.DS_DDD_CELULAR,
                    C.DS_CELULAR,
                    U.DS_NOME,
                    PV.ID_IP,
                    PV.ID_USUARIO_ESTORNO,
                    PV.DS_MOTIVO_CANCELAMENTO,
                    PV.DT_HORA_CANCELAMENTO ";

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
                  C.DS_DDD_CELULAR,
                  C.DS_CELULAR,
                  U.DS_NOME,
                  PV.ID_IP,
                  PV.ID_USUARIO_ESTORNO,
                  PV.DS_MOTIVO_CANCELAMENTO,
                  PV.DT_HORA_CANCELAMENTO ";

    if (!empty($_GET["num_pedido"])) {

        $where .= " AND PV.ID_PEDIDO_VENDA = ". $_GET["num_pedido"];

        $params[] = $_GET["num_pedido"];
        $paramsTotal[] = $_GET["num_pedido"];
    }
    if (!empty($_GET["nm_cliente"])) {
        $where .= " AND (C.DS_NOME LIKE '%" . utf8_decode(trim($_GET["nm_cliente"])) . "%' OR C.DS_SOBRENOME LIKE '%" . utf8_decode(trim($_GET["nm_cliente"])) . "%')";
        $join = true;

        //$params[] = $_GET["nm_cliente"];
    }
    if (!empty($_GET["nm_operador"])) {
            if($_GET["nm_operador"] == 'Web' || $_GET["nm_operador"] == 'WEB' || $_GET["nm_operador"] == 'web'){
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
                            C.DS_DDD_CELULAR,
                            C.DS_CELULAR,
                            PV.ID_IP,
                            PV.ID_USUARIO_ESTORNO,
                            PV.DS_MOTIVO_CANCELAMENTO,
                            PV.DT_HORA_CANCELAMENTO ";

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
                              C.DS_DDD_CELULAR,
                              C.DS_CELULAR,
                              PV.ID_IP,
                              PV.ID_USUARIO_ESTORNO,
                              PV.DS_MOTIVO_CANCELAMENTO,
                              PV.DT_HORA_CANCELAMENTO ";

                $from = "FROM MW_PEDIDO_VENDA PV INNER JOIN MW_CLIENTE C ON C.ID_CLIENTE = PV.ID_CLIENTE AND PV.ID_USUARIO_CALLCENTER IS NULL
                          LEFT JOIN MW_ITEM_PEDIDO_VENDA_HIST IPV ON IPV.ID_PEDIDO_VENDA = PV.ID_PEDIDO_VENDA ";
                $join2=true;
            } else {
                $where .= " AND U.DS_NOME LIKE '%". utf8_decode(trim($_GET["nm_operador"])) ."%'";
                $from = "FROM MW_PEDIDO_VENDA PV INNER JOIN MW_CLIENTE C ON C.ID_CLIENTE = PV.ID_CLIENTE
                          LEFT JOIN MW_ITEM_PEDIDO_VENDA IPV ON IPV.ID_PEDIDO_VENDA = PV.ID_PEDIDO_VENDA
                          LEFT JOIN MW_USUARIO U ON U.ID_USUARIO=PV.ID_USUARIO_CALLCENTER ";
                $join3=true;
            }
        }
    if (!empty($_GET["cd_cpf"])) {
        $where .= " AND C.CD_CPF = '". $_GET["cd_cpf"] ."'";
        $join = true;

        $params[] = $_GET["cd_cpf"];
        $paramsTotal[] = $_GET["cd_cpf"];
    }
    if (!empty($_GET["nm_evento"])) {
            $where .= " AND E.ID_EVENTO = ". $_GET["nm_evento"];
            $join4 = true;

            $from .= " LEFT JOIN MW_APRESENTACAO A ON IPV.ID_APRESENTACAO = A.ID_APRESENTACAO
                        INNER JOIN MW_EVENTO E ON E.ID_EVENTO = A.ID_EVENTO ";

            $from2 .= " LEFT JOIN MW_APRESENTACAO A ON IPV.ID_APRESENTACAO = A.ID_APRESENTACAO
                        INNER JOIN MW_EVENTO E ON E.ID_EVENTO = A.ID_EVENTO ";

            $params[] = $_GET["nm_evento"];
            $paramsTotal[] = $_GET["nm_evento"];
        }

    $sql = $select.
           $from.
           $where.
           $group ." UNION ALL ".
           $select.
           $from2.
           $where.
           $group;    
    $result = executeSQL($mainConnection, $sql);

}

   $query = "SELECT
                                      SUM (IPV.VL_UNITARIO) AS TOTAL_PEDIDO
                              FROM
                                      MW_PEDIDO_VENDA PV
                                      LEFT JOIN MW_ITEM_PEDIDO_VENDA IPV ON IPV.ID_PEDIDO_VENDA = PV.ID_PEDIDO_VENDA ";
    if (isset($join)) {
        $query .= "INNER JOIN MW_CLIENTE C ON C.ID_CLIENTE = PV.ID_CLIENTE ";
    }
    if(isset($join2)){
        $query .= "INNER JOIN MW_CLIENTE CL ON CL.ID_CLIENTE = PV.ID_CLIENTE AND PV.ID_USUARIO_CALLCENTER IS NULL ";
    }
    if(isset($join3)){
        $query .= "LEFT JOIN MW_USUARIO U ON U.ID_USUARIO=PV.ID_USUARIO_CALLCENTER ";
    }
    if(isset($join4)){
        $query .= "   LEFT JOIN MW_APRESENTACAO A ON IPV.ID_APRESENTACAO = A.ID_APRESENTACAO
                      INNER JOIN MW_EVENTO E ON E.ID_EVENTO = A.ID_EVENTO ";
    }
    $query .= $where;
    $rs = executeSQL($mainConnection, $query, $paramsTotal, true);
    $total['TOTAL_PEDIDO'] = $rs['TOTAL_PEDIDO'];

    $paramsTotal = array_merge($paramsTotal, $paramsTotal);

    $query = "SELECT
                                      COUNT(1) AS QUANTIDADE,
                                      PV.VL_TOTAL_TAXA_CONVENIENCIA
                              FROM
                                      MW_PEDIDO_VENDA PV
                                      LEFT JOIN MW_ITEM_PEDIDO_VENDA IPV ON IPV.ID_PEDIDO_VENDA = PV.ID_PEDIDO_VENDA ";

    if (isset($join)) {
        $query .= "INNER JOIN MW_CLIENTE C ON C.ID_CLIENTE = PV.ID_CLIENTE ";
    }
    if(isset($join2)){
        $query .= "INNER JOIN MW_CLIENTE CL ON CL.ID_CLIENTE = PV.ID_CLIENTE AND PV.ID_USUARIO_CALLCENTER IS NULL ";
    }
    if(isset($join3)){
        $query .= "LEFT JOIN MW_USUARIO U ON U.ID_USUARIO=PV.ID_USUARIO_CALLCENTER ";
    }
    if(isset($join4)){
        $query .= "   LEFT JOIN MW_APRESENTACAO A ON IPV.ID_APRESENTACAO = A.ID_APRESENTACAO
                      INNER JOIN MW_EVENTO E ON E.ID_EVENTO = A.ID_EVENTO ";
    }
    $query .= $where ."
                        GROUP BY
                            PV.VL_TOTAL_TAXA_CONVENIENCIA

                      UNION ALL

                      SELECT
                              COUNT(1) AS QUANTIDADE,
                              PV.VL_TOTAL_TAXA_CONVENIENCIA
                      FROM
                              MW_PEDIDO_VENDA PV
                              INNER JOIN MW_ITEM_PEDIDO_VENDA_HIST IPV ON IPV.ID_PEDIDO_VENDA = PV.ID_PEDIDO_VENDA ";
    if (isset($join)) {
        $query .= "INNER JOIN MW_CLIENTE C ON C.ID_CLIENTE = PV.ID_CLIENTE ";
    }
    if(isset($join2)){
        $query .= "INNER JOIN MW_CLIENTE CL ON CL.ID_CLIENTE = PV.ID_CLIENTE AND PV.ID_USUARIO_CALLCENTER IS NULL ";
    }
    if(isset($join3)){
        $query .= "LEFT JOIN MW_USUARIO U ON U.ID_USUARIO=PV.ID_USUARIO_CALLCENTER ";
    }
    if(isset($join4)){
        $query .= "   LEFT JOIN MW_APRESENTACAO A ON IPV.ID_APRESENTACAO = A.ID_APRESENTACAO
                      INNER JOIN MW_EVENTO E ON E.ID_EVENTO = A.ID_EVENTO ";
    }
    $query .= $where ." GROUP BY
                            PV.VL_TOTAL_TAXA_CONVENIENCIA ";
    $result2 = executeSQL($mainConnection, $query, $paramsTotal);
    $total['QUANTIDADE'] = 0;
    $total['SERVICO'] = 0;
    while ($rs = fetchResult($result2)) {
        $total['QUANTIDADE'] += $rs['QUANTIDADE'];
        $total['SERVICO'] += $rs["VL_TOTAL_TAXA_CONVENIENCIA"];
    }
?>
<meta http-equiv='Content-Type' content='text/html; charset=utf-8' />
<style type="text/css">
    .moeda {
        mso-number-format:"_\(\[$R$ -416\]* \#\,\#\#0\.00_\)\;_\(\[$R$ -416\]* \\\(\#\,\#\#0\.00\\\)\;_\(\[$R$ -416\]* \0022-\0022??_\)\;_\(\@_\)";
    }
</style>
<p style="width:1000px;" align="center">
   <h2>Consulta de Pedidos</h2>
    <?php
        if(!empty($_GET["num_pedido"])){
   ?>
    <b>Pedido nº</b> <?php echo $_GET["num_pedido"]?> &nbsp;&nbsp;
    <?php
        }
        if(!empty($_GET["nm_cliente"])){
    ?>
           <b>Nome do Cliente</b> <?php echo $_GET["nm_cliente"]?> &nbsp;&nbsp;
    <?php
        }
        if(!empty($_GET["nm_operador"])){
    ?>
           <b>Nome do Operador</b> <?php echo $_GET["nm_operador"]?> &nbsp;&nbsp;
    <?php
        }
        if(!empty($_GET["cd_cpf"])){
    ?>
            <b>CPF</b> <?php echo $_GET["cd_cpf"] ?> &nbsp;&nbsp;
    <?php
        }
        if(!empty($_GET["nm_evento"])){
            $queryEvento = 'SELECT E.DS_EVENTO FROM MW_EVENTO E WHERE E.ID_EVENTO = '. $_GET["nm_evento"];
            $resultEventos = executeSQL($mainConnection, $queryEvento, null);
            while ($rs = fetchResult($resultEventos)) {
                $dsEvento = utf8_encode2($rs["DS_EVENTO"]);
            }
   ?>
            <b>Nome do Evento</b> <?php print $dsEvento ?> &nbsp;&nbsp;
   <?php
        }
    ?>
    <br/> <b>Data Inicial</b> <?php echo $_GET["dt_inicial"]?>&nbsp;&nbsp;<b>Data Final</b> <?php echo $_GET["dt_final"]?>&nbsp;&nbsp;<b>Situação</b> <?php echo comboSituacao('situacao', $_GET["situacao"], false)?>
</p>

<table class="ui-widget ui-widget-content">
    <thead>
        <tr class="ui-widget-header">
            <th>Pedido nº</th>
            <th>Operador</th>
            <th>Data</th>
            <th>IP</th>
            <th>Cliente e Telefone</th>
            <th>Valor total</th>
            <th>Qtde Ingressos</th>
            <th>Situação</th>
            <th>Forma de Entrega</th>
        </tr>
    </thead>
    <tbody>
        <?php
        if (isset($result) && hasRows($result)) {
            while ($rs = fetchResult($result)) {
        ?>
                <tr>
                    <td><?php echo $rs['ID_PEDIDO_VENDA']; ?></td>
                    <td>
                        <?php if(empty($rs['DS_NOME'])){
                                    echo 'Web';
                              }
                              else
                              {
                                  echo $rs['DS_NOME'];
                              }
                        ?>
                    </td>
                    <td><?php echo $rs['DT_PEDIDO_VENDA'] ?></td>
                    <td><?php echo $rs['ID_IP'] ?></td>
                    <td><?php echo utf8_encode2($rs['CLIENTE'] . " " . $rs['DS_SOBRENOME']) . " / " . $rs['DS_DDD_TELEFONE'] . " " . $rs['DS_TELEFONE'] . " / " . $rs['DS_DDD_CELULAR'] . " " . $rs['DS_CELULAR']; ?></td>
                    <td class="moeda"><?php echo str_replace(".", ",", $rs['TOTAL_UNIT']); ?></td>
                    <td><?php echo $rs["QUANTIDADE"];?></td>
                    <td><?php echo comboSituacao('situacao', $rs['IN_SITUACAO'], false)?></td>
                    <td><?php echo comboFormaEntrega($rs['IN_RETIRA_ENTREGA']); ?></td>
                </tr>
                <?php
                  if($_GET['situacao'] == 'S')
                  {
                    if($rs['DT_HORA_CANCELAMENTO'] != NULL || $rs['DT_HORA_CANCELAMENTO'] != "")
                    {
                      $dt_estorno = date_format($rs['DT_HORA_CANCELAMENTO'], 'd/m/Y').' às '.date_format($rs['DT_HORA_CANCELAMENTO'], 'H:i').'hs';
                    }
                    else
                    {
                      $dt_estorno = 'Sem informação';
                    }
                ?>
                  <tr>
                    <td colspan='4'>Estornado pelo usuário: <?php echo comboAdmins('admin', $rs['ID_USUARIO_ESTORNO'], false); ?></td>
                    <td colspan='4'>Motivo: <?php echo $rs['DS_MOTIVO_CANCELAMENTO']; ?></td>
                    <td colspan='3'>Data de Estorno: <?php echo $dt_estorno ?></td>
                  </tr>
                <?php
                  }
                ?>
                <tr></tr>
        <?php
            }
        }
        ?>
    </tbody>
</table>
<?php print_r(sqlErrors()); ?>