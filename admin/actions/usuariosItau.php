<?php
if (acessoPermitido($mainConnection, $_SESSION['admin'], 9, true)) {

if ($_GET['action'] != 'delete' and $_GET['action'] != 'reset') {
	if (!verificaCPF($_POST['cpf'])) {
		echo 'O CPF informado não é válido.';
		exit();
	}
	$_POST['admin'] = $_POST['admin'] == 'on' ? 1 : 0;
	$_POST['ativo'] = $_POST['ativo'] == 'on' ? 1 : 0;
}

if ($_GET['action'] == 'add') { /*------------ INSERT ------------*/
	$query = 'SELECT 1 FROM MW_USUARIO_ITAU WHERE CD_LOGIN = ?';
	$params = array($_POST['login']);
	$result = executeSQL($mainConnection, $query, $params);
	if (hasRows($result)) {
		echo 'Já existe um usuário cadastrado com esse login.';
		exit();
	}

	$query = 'SELECT 1 FROM MW_USUARIO_ITAU WHERE CD_CPF = ?';
	$params = array($_POST['cpf']);
	$result = executeSQL($mainConnection, $query, $params);
	if (hasRows($result)) {
		echo 'Já existe um usuário cadastrado com esse CPF.';
		exit();
	}
	
	$query = "INSERT INTO MW_USUARIO_ITAU
					(CD_LOGIN, DS_NOME, DS_EMAIL, IN_ATIVO, IN_ADMIN, CD_PWW, CD_CPF, DS_DDD_CELULAR, DS_CELULAR)
					VALUES (?, ?, ?, ?, ?, '". md5('123456') . "', ?, ?, ?)";
	$params = array($_POST['login'], utf8_encode2($_POST['nome']), $_POST['email'], $_POST['ativo'], $_POST['admin'], $_POST['cpf'], $_POST['ddd'], $_POST['celular']);
	
	if (executeSQL($mainConnection, $query, $params)) {
	    $log = new Log($_SESSION['admin']);
	    $log->__set('funcionalidade', 'SISBIN x Usuários');
	    $log->__set('parametros', $params);
	    $log->__set('log', $query);
	    $log->save($mainConnection);

		$query = 'SELECT ID_USUARIO FROM MW_USUARIO_ITAU WHERE CD_LOGIN = ?';
		$params = array($_POST['login']);
		
		$rs = executeSQL($mainConnection, $query, $params, true);
		
		$retorno = 'true?codusuario='.$rs['ID_USUARIO'];
	} else {
		$retorno = sqlErrors();
	}
	
} else if ($_GET['action'] == 'update' and isset($_GET['codusuario'])) { /*------------ UPDATE ------------*/

	$query = 'SELECT 1 FROM MW_USUARIO_ITAU WHERE CD_CPF = ? AND ID_USUARIO <> ?';
	$params = array($_POST['cpf'], $_GET['codusuario']);
	$result = executeSQL($mainConnection, $query, $params);
	if (hasRows($result)) {
		echo 'Já existe um usuário cadastrado com esse CPF.';
		exit();
	}
	
	$query = "UPDATE MW_USUARIO_ITAU SET
					DS_NOME = ?,
					DS_EMAIL = ?,
					IN_ATIVO = ?,
					IN_ADMIN = ?,
					CD_CPF = ?,
					DS_DDD_CELULAR = ?,
					DS_CELULAR = ?
				WHERE
					ID_USUARIO = ?";
	$params = array(utf8_encode2($_POST['nome']), $_POST['email'], $_POST['ativo'], $_POST['admin'], $_POST['cpf'], $_POST['ddd'], $_POST['celular'], $_GET['codusuario']);
	
	if (executeSQL($mainConnection, $query, $params)) {
        $log = new Log($_SESSION['admin']);
        $log->__set('funcionalidade', 'SISBIN x Usuários');
        $log->__set('parametros', $params);
        $log->__set('log', $query);
        $log->save($mainConnection);

		$retorno = 'true?codusuario='.$_GET['codusuario'];
	} else {
		$retorno = sqlErrors();
	}
	
} else if ($_GET['action'] == 'delete' and isset($_GET['codusuario'])) { /*------------ DELETE ------------*/
	
	$query = 'DELETE FROM MW_USUARIO_ITAU WHERE ID_USUARIO = ?';
	$params = array($_GET['codusuario']);
	
	if (executeSQL($mainConnection, $query, $params)) {
        $log = new Log($_SESSION['admin']);
        $log->__set('funcionalidade', 'SISBIN x Usuários');
        $log->__set('parametros', $params);
        $log->__set('log', $query);
        $log->save($mainConnection);

		$retorno = 'true';
	} else {
		$retorno = sqlErrors();
	}
	
} else if ($_GET['action'] == 'reset' and isset($_GET['codusuario'])) { /*------------ RESET PWW ------------*/
	
	$query = "UPDATE MW_USUARIO_ITAU SET
					CD_PWW = '". md5('123456') . "'
				WHERE
					ID_USUARIO = ?";
	$params = array($_GET['codusuario']);
	
	if (executeSQL($mainConnection, $query, $params)) {
	    $log = new Log($_SESSION['admin']);
	    $log->__set('funcionalidade', 'SISBIN x Usuários');
	    $log->__set('parametros', $params);
	    $log->__set('log', $query);
	    $log->save($mainConnection);

		$retorno = 'true';
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