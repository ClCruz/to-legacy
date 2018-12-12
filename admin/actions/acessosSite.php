<?php
if (acessoPermitido($mainConnection, $_SESSION['admin'], 32, true)) {

$_POST['dia'] = explode('/', $_POST['dia']);
$_POST['dia'] = $_POST['dia'][2].$_POST['dia'][1].$_POST['dia'][0];

if ($_GET['action'] == 'add') { /*------------ INSERT ------------*/

	$result = executeSQL($conn, "SELECT 1 FROM FATO_ACESSO_SITE WHERE ID_DIA = ? AND ID_PAGINA = ?", array($_POST['dia'], $_POST['pagina']));
	if (hasRows($result)) die('Já existe um registro com o mesmo dia/página.');
	
	$query = "INSERT INTO FATO_ACESSO_SITE
					(ID_DIA, ID_PAGINA, QT_ACESSO)
					VALUES (?, ?, ?)";
	$params = array($_POST['dia'], $_POST['pagina'], $_POST['acessos']);
	
	if (executeSQL($conn, $query, $params)) {
		$retorno = 'true?dia='.$_POST['dia'].'&mes='.$_POST['teatro'].'&pagina='.$_POST['pagina'];
	} else {
		$retorno = sqlErrors();
	}
	
} else if ($_GET['action'] == 'update' and isset($_GET['dia']) and isset($_GET['pagina'])) { /*------------ UPDATE ------------*/

	$result = executeSQL($conn,
						"SELECT 1 FROM FATO_ACESSO_SITE
						WHERE (ID_DIA = ? AND ID_PAGINA = ?)
						AND NOT (ID_DIA = ? AND ID_PAGINA = ?)",
						array($_POST['dia'], $_POST['pagina'], $_GET['dia'], $_GET['pagina']));
	if (hasRows($result)) die('Já existe um registro com o mesmo dia/página.');
	
	$query = "UPDATE FATO_ACESSO_SITE
				 SET
				 ID_DIA = ?
				 ,ID_PAGINA = ?
				 ,QT_ACESSO = ?
				 WHERE ID_DIA = ? AND ID_PAGINA = ?";
	$params = array($_POST['dia'], $_POST['pagina'], $_POST['acessos'], $_GET['dia'], $_GET['pagina']);
	
	if (executeSQL($conn, $query, $params)) {
		$retorno = 'true?dia='.$_POST['dia'].'&pagina='.$_POST['pagina'];
	} else {
		$retorno = sqlErrors();
	}
	
} else if ($_GET['action'] == 'delete' and isset($_GET['dia']) and isset($_GET['pagina'])) { /*------------ DELETE ------------*/
	
	$query = 'DELETE FROM FATO_ACESSO_SITE WHERE ID_DIA = ? AND ID_PAGINA = ?';
	$params = array($_GET['dia'], $_GET['pagina']);
	
	if (executeSQL($conn, $query, $params)) {
		$retorno = 'true';
	} else {
		$retorno = sqlErrors();
	}
	
}

if (is_array($retorno)) {
	if ($retorno[0]['code'] == 2627) {
		echo 'Esse dia/página já está cadastrado.';
	} else {
		echo $retorno[0]['message'];
	}
} else {
	echo $retorno;
}

}
?>