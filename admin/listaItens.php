<?php
require_once('../settings/functions.php');
$mainConnection = mainConnection();
session_start();

if (acessoPermitido($mainConnection, $_SESSION['admin'], 12, true)) {

    $pagina = basename(__FILE__);

    if (isset($_GET["pedido"])) {
        $sql = "SELECT
				E.DS_EVENTO,  IPV.DS_SETOR, IPV.DS_LOCALIZACAO, QT_INGRESSOS, VL_UNITARIO,VL_TAXA_CONVENIENCIA,
                                (CONVERT(VARCHAR(10), A.DT_APRESENTACAO, 103) + ' ' + CONVERT(VARCHAR(5), A.HR_APRESENTACAO)) AS APRESENTACAO,
                                PV.VL_TOTAL_TAXA_CONVENIENCIA
			FROM 
				MW_PEDIDO_VENDA PV
			INNER JOIN MW_ITEM_PEDIDO_VENDA IPV ON IPV.ID_PEDIDO_VENDA = PV.ID_PEDIDO_VENDA
			INNER JOIN MW_APRESENTACAO A ON A.ID_APRESENTACAO = IPV.ID_APRESENTACAO
			INNER JOIN MW_EVENTO E ON E.ID_EVENTO = A.ID_EVENTO
			WHERE PV.ID_PEDIDO_VENDA = ?
			UNION ALL
			SELECT
				 DS_NOME_EVENTO AS DS_EVENTO,  DS_SETOR, DS_LOCALIZACAO, QT_INGRESSOS, VL_UNITARIO,VL_TAXA_CONVENIENCIA,
                                (CONVERT(VARCHAR(10), A.DT_APRESENTACAO, 103) + ' ' + CONVERT(VARCHAR(5), A.HR_APRESENTACAO)) AS APRESENTACAO,
                                PV.VL_TOTAL_TAXA_CONVENIENCIA
                        FROM
                             MW_PEDIDO_VENDA PV
                            INNER JOIN MW_ITEM_PEDIDO_VENDA_HIST IPV ON IPV.ID_PEDIDO_VENDA = PV.ID_PEDIDO_VENDA
                            INNER JOIN MW_APRESENTACAO A ON A.ID_APRESENTACAO = IPV.ID_APRESENTACAO
                            INNER JOIN MW_EVENTO E ON A.ID_EVENTO = E.ID_EVENTO
                        WHERE
                            PV.ID_PEDIDO_VENDA = ?";
        $params = array($_GET["pedido"],$_GET["pedido"]);
        $result = executeSQL($mainConnection, $sql, $params);
    }
    if (isset($result) && hasRows($result)) {
?>
        <table class="ui-widget ui-widget-content">
            <thead>
                <tr class="ui-widget-header">
                    <th colspan="2">Apresentação</th>
                    <th colspan="2">Evento</th>
                    <th colspan="2">Setor</th>
                    <th colspan="2">Localização</th>
                    <th>Qtd Ingressos</th>
                    <th>Valor unitário</th>
                    <th>Valor Serviço</th>
                </tr>
            </thead>
            <tbody>
        <?php while ($rs = fetchResult($result)) {
                $totalservico=$rs["VL_TOTAL_TAXA_CONVENIENCIA"];

 ?>
            <tr>
                <td colspan="2"><?php echo $rs['APRESENTACAO']; ?></td>
                <td colspan="2"><?php echo utf8_encode2($rs['DS_EVENTO']); ?></td>
                <td colspan="2"><?php echo utf8_encode2($rs['DS_SETOR']) ?></td>
                <td colspan="2"><?php echo $rs['DS_LOCALIZACAO']; ?></td>
                <td><?php echo $rs['QT_INGRESSOS']; ?></td>
                <td><?php echo str_replace(".", ",", $rs['VL_UNITARIO']); ?></td>
                <td><?php echo str_replace(".", ",", $rs['VL_TAXA_CONVENIENCIA']); ?></td>
            </tr>
        <?php } ?>
    </tbody>
</table>
<?php
    }
?>

<?php
}
?>