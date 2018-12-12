<?php
require_once('../settings/functions.php');
$mainConnection = mainConnection();
session_start();

$query = "SELECT DISTINCT isnull(T.IN_TAXA_POR_PEDIDO, 'N') IN_TAXA_POR_PEDIDO FROM MW_RESERVA R
			INNER JOIN MW_APRESENTACAO A ON A.ID_APRESENTACAO = R.ID_APRESENTACAO
			LEFT JOIN MW_TAXA_CONVENIENCIA T ON T.ID_EVENTO = A.ID_EVENTO AND T.DT_INICIO_VIGENCIA <= GETDATE() AND T.IN_TAXA_POR_PEDIDO = 'S'
				AND T.DT_INICIO_VIGENCIA = (SELECT MAX(T2.DT_INICIO_VIGENCIA) FROM MW_TAXA_CONVENIENCIA T2 WHERE T2.ID_EVENTO = T.ID_EVENTO AND T2.DT_INICIO_VIGENCIA <= GETDATE())
			WHERE R.ID_SESSION = ?";
$result = executeSQL($mainConnection, $query, array(session_id()));

$rows = 0;
while ($rs = fetchResult($result)) $rows++;

$msgServicosPorPedido = 'Não é possível concluir o pedido com dois ou mais eventos que possuam taxas de serviços diferenciadas.<br><br>Favor alterar o pedido e remover um ou mais eventos para continuar.';

if ($rows > 1) {
	if (basename($_SERVER['SCRIPT_FILENAME']) == 'etapa5.php') {
		header("Location: etapa4.php");
	} else {
		$scriptServicosPorPedido = '<script type="text/javascript">
											$(function(){
												$.dialog({title:"Aviso...", text:"'.$msgServicosPorPedido.'", uiOptions:{width:500}});
											});
										</script>';
	}
}