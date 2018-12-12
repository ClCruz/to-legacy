<?php
if ($_GET['action'] == 'add') { /*------------ INSERT ------------*/
	
	$query = "INSERT INTO MW_RESTRICAO_APRESENTACAO (ID_APRESENTACAO, CODTIPBILHETE)
				 SELECT ID_APRESENTACAO, ? FROM MW_APRESENTACAO WHERE ID_EVENTO = ? AND DS_PISO = ?";
	$params = array($_POST['codtipbilhete'], $_POST['evento'], $_POST['piso']);
	
	if (executeSQL($mainConnection, $query, $params)) {
		$query = 'SELECT DS_EVENTO FROM MW_EVENTO WHERE ID_EVENTO = ?';
		$params = array($_POST['idEvento']);
		
		$rs = executeSQL($mainConnection, $query, $params, true);
		
		$retorno = 'true?idEvento='.$rs['DS_EVENTO'].'&data='.$_POST['data'];
	} else {
		$retorno = sqlErrors();
	}
	
} else if ($_GET['action'] == 'delete' and isset($_GET['idEvento']) and isset($_GET['data'])) { /*------------ DELETE ------------*/
	
	$data = strtotime(str_replace('/', '-', $_GET['data']));
	$hoje = strtotime(date('d-m-Y'));
	
	if ($data > $hoje) {
		$query = 'DELETE T FROM MW_TAXA_CONVENIENCIA T INNER JOIN MW_EVENTO R ON R.ID_EVENTO = T.ID_EVENTO WHERE
						R.DS_EVENTO = ? AND T.DT_INICIO_VIGENCIA = CONVERT(DATETIME, ?, 103)';
		$params = array($_GET['idEvento'], $_GET['data']);
		
		if (executeSQL($mainConnection, $query, $params)) {
			$retorno = 'true';
		} else {
			$retorno = sqlErrors();
		}
	} else {
		$retorno = 'Este registro ainda está em uso!';
	}
	
}

if (is_array($retorno)) {
	if ($retorno[0]['code'] == 2627) {
		echo 'Já existe um registro cadastrado com essas informações.';
	} else {
		echo $retorno[0]['message'];
	}
} else {
	echo $retorno;
}
?>