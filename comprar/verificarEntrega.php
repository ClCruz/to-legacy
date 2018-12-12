<?php
if (isset($_COOKIE['entrega'])) {
    require_once('../settings/functions.php');
    require_once('../settings/settings.php');
    session_start();
    
    if (isset($_SESSION['user'])) {
	
	$mainConnection = mainConnection();
	$query = 'SELECT E.DS_EVENTO, CONVERT(VARCHAR(10), A.DT_APRESENTACAO, 103) DT_APRESENTACAO, A.HR_APRESENTACAO, E.IN_ENTREGA_INGRESSO
	    FROM MW_RESERVA R
	    INNER JOIN MW_APRESENTACAO A ON A.ID_APRESENTACAO = R.ID_APRESENTACAO AND A.IN_ATIVO = \'1\'
	    INNER JOIN MW_EVENTO E ON E.ID_EVENTO = A.ID_EVENTO AND E.IN_ATIVO = \'1\'
	    WHERE R.ID_SESSION = ? AND R.DT_VALIDADE >= GETDATE()
	    ORDER BY DS_EVENTO';
	$params = array(session_id());
	$result = executeSQL($mainConnection, $query, $params);
	
	$entregaTable = utf8_decode('Prezado cliente, o(s) seguinte(s) evento(s) não permite(m) entrega.<br><br><table class=\'ui-widget ui-widget-content\' style=\'width:100%; text-align:left\'><thead><th>Evento</th><th>Data</th><th>Hora</th></thead><tbody>');

	$naoEntregar = false;

	while ($rs = fetchResult($result)) {
	    if ($rs['IN_ENTREGA_INGRESSO'] != 1) {
		$naoEntregar = true;
		$entregaTable .= '<tr><td>' . $rs['DS_EVENTO'] . '</td><td>' . $rs['DT_APRESENTACAO'] . '</td><td>' . $rs['HR_APRESENTACAO'] . '</td></tr>';
	    }
	}
	$entregaTable .= utf8_decode('</tbody></table><br>Por favor, clique em <strong>alterar pedido</strong> e selecione outra forma de entrega ou remova o(s) ingresso(s) que esta(ão) em desacordo com a tabela acima.');
	
	if ($naoEntregar) {
	    if (basename($_SERVER['SCRIPT_FILENAME']) == 'etapa5.php') {
		header("Location: etapa4.php");
	    } else {
		$scriptEntrega = '<script type="text/javascript">
				    $(function(){
					$.dialog({title:"Aviso...", text:"' . utf8_encode2($entregaTable) . '", uiOptions:{width:500}});
				    });
				</script>';
	    }
	}
    }
}