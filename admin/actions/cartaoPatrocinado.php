<?php
if (acessoPermitido($mainConnection, $_SESSION['admin'], 23, true)) {

if ($_GET['action'] == 'add') { /*------------ INSERT ------------*/
	
	$query = "INSERT INTO MW_CARTAO_PATROCINADO
					(ID_PATROCINADOR, DS_CARTAO_PATROCINADO, CD_BIN)
					VALUES (?, ?, ?)";
	$params = array($_POST['idPatrocinador'], utf8_encode2($_POST['nome']), $_POST['bin']);
	
	if (executeSQL($mainConnection, $query, $params)) {
        $log = new Log($_SESSION['admin']);
        $log->__set('funcionalidade', 'Cartões Patrocinados');
        $log->__set('parametros', $params);
        $log->__set('log', $query);
        $log->save($mainConnection);

		$query = 'SELECT ID_CARTAO_PATROCINADO FROM MW_PATROCINADOR WHERE ID_PATROCINADOR = ? AND DS_CARTAO_PATROCINADO = ? AND CD_BIN = ?';
		$params = array($_POST['idPatrocinador'], utf8_encode2($_POST['nome']), $_POST['bin']);
		
		$rs = executeSQL($mainConnection, $query, $params, true);
		
		$retorno = 'true?idCartaoPatrocinado='.$rs['ID_CARTAO_PATROCINADO'];
	} else {
		$retorno = sqlErrors();
	}
	
} else if ($_GET['action'] == 'update' and isset($_GET['idCartaoPatrocinado'])) { /*------------ UPDATE ------------*/
	
	$query = "UPDATE MW_CARTAO_PATROCINADO
				 SET
				 ID_PATROCINADOR = ?
				 ,DS_CARTAO_PATROCINADO = ?
				 ,CD_BIN = ?
				 WHERE ID_CARTAO_PATROCINADO = ?";
	$params = array($_POST['idPatrocinador'], utf8_encode2($_POST['nome']), $_POST['bin'], $_GET['idCartaoPatrocinado']);
	
	if (executeSQL($mainConnection, $query, $params)) {
        $log = new Log($_SESSION['admin']);
        $log->__set('funcionalidade', 'Cartões Patrocinados');
        $log->__set('parametros', $params);
        $log->__set('log', $query);
        $log->save($mainConnection);

		$retorno = 'true?idCartaoPatrocinado='.$_GET['idCartaoPatrocinado'];
	} else {
		$retorno = sqlErrors();
	}
	
} else if ($_GET['action'] == 'delete' and isset($_GET['idCartaoPatrocinado'])) { /*------------ DELETE ------------*/
	
	$query = 'DELETE FROM MW_CARTAO_PATROCINADO WHERE ID_CARTAO_PATROCINADO = ?';
	$params = array($_GET['idCartaoPatrocinado']);
	
	if (executeSQL($mainConnection, $query, $params)) {
	    $log = new Log($_SESSION['admin']);
	    $log->__set('funcionalidade', 'Cartões Patrocinados');
	    $log->__set('parametros', $params);
	    $log->__set('log', $query);
	    $log->save($mainConnection);

	    
		$retorno = 'true';
	} else {
		$retorno = sqlErrors();
	}
	
}

if (is_array($retorno)) {
	if ($retorno[0]['code'] == 547) {
		echo 'Não foi possível excluir!<br/><br/>Esse registro já está em uso.';
	} else {
		echo $retorno[0]['message'];
	}
} else {
	echo $retorno;
}

}
?>