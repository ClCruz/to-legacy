<?php
if (acessoPermitido($mainConnection, $_SESSION['admin'], 290, true)) {

$_POST['ativo'] = $_POST['ativo'] == 'on' ? 1 : 0;

if ($_GET['action'] == 'update' and isset($_GET['id_evento'])) { /*------------ UPDATE ------------*/
	
	$query = "UPDATE MW_EVENTO SET
					IN_VER_NO_BORDERO = ?
				WHERE
					ID_EVENTO = ?";
	$params = array($_POST['ativo'], $_GET['id_evento']);

        $log = new Log($_SESSION['admin']);
        $log->__set('funcionalidade', 'Visualizar Evento no Borderô');
        $log->__set('parametros', $params);
        $log->__set('log', $query);
        $log->save($mainConnection);
	
	if (executeSQL($mainConnection, $query, $params)) {
		$retorno = 'true?id_evento='.$_GET['id_evento'];
	} else {
		$retorno = sqlErrors();
	}
	
}

if (is_array($retorno)) {
	echo $retorno[0]['message'];
} else {
	echo $retorno;
}

}
?>