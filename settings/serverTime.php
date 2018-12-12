<?php
require_once('../settings/functions.php');

$mainConnection = mainConnection();

if ($_GET['ie']) {
	$query = 'SELECT TOP 1
				 CONVERT(VARCHAR(10), GETDATE(), 103) DATA,  CONVERT(VARCHAR(8), GETDATE(), 108) HORA';
	$params = array(session_id());
	$rs = executeSQL($mainConnection, $query, $params, true);
	
	$data = explode('/', $rs['DATA']);
	$hora = explode(':', $rs['HORA']);
	
	if (($data[1] - 1) < 0) {
		$retorno = '(new Date().getTime() + 3000)';
	} else {
		$retorno = $data[2] . ',' . ($data[1] - 1) . ',' . $data[0] . ',' . $hora[0] . ',' . $hora[1] . ',' . $hora[2];
	}
	
	echo $retorno;
} else {
	$query = 'SELECT LEFT(DATENAME(MM, GETDATE()), 3) + RIGHT(CONVERT(VARCHAR(12), GETDATE(), 107), 9) + \' \' + CONVERT(VARCHAR(8), GETDATE(), 108)';
	
	$rs = executeSQL($mainConnection, $query, array(), true);
	
	echo $rs[0];
}

//date("M j, Y H:i:s O"); 
?>