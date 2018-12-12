<?php
if (acessoPermitido($mainConnection, $_SESSION['admin'], 17, true)) {

if ($_GET['action'] == 'update' and isset($_GET['codestado'])) { /*------------ UPDATE ------------*/
	
	$query = "UPDATE MW_LIMITE_ENTREGA SET
					QT_HORAS_LIMITE = ?
				WHERE
					ID_ESTADO = ?";
	$params = array($_POST['qtdhoras'], $_GET['codestado']);
	
	if (executeSQL($mainConnection, $query, $params)) {
        $log = new Log($_SESSION['admin']);
        $log->__set('funcionalidade', 'Envio de Ingressos');
        $log->__set('parametros', $params);
        $log->__set('log', $query);
        $log->save($mainConnection);
        
		$retorno = 'true?codestado='.$_GET['codestado'];
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