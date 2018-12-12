<?php
if (acessoPermitido($mainConnection, $_SESSION['admin'], 4, true)) {

if (isset($_POST['valor'])) {
	$_POST['valor'] = str_replace(',', '.', $_POST['valor']);
	if (!is_numeric($_POST['valor'])) {
		echo 'Favor informar um valor válido para o frete.';
		exit();
	}
}

if ($_GET['action'] == 'add') { /*------------ INSERT ------------*/
	
	$query = "INSERT INTO MW_TAXA_FRETE
					(ID_REGIAO_GEOGRAFICA, DT_INICIO_VIGENCIA, VL_TAXA_FRETE)
					VALUES (?, CONVERT(DATETIME, ?, 103), ?)";
	$params = array($_POST['regiao'], $_POST['data'], $_POST['valor']);
	
	if (executeSQL($mainConnection, $query, $params)) {
        $log = new Log($_SESSION['admin']);
        $log->__set('funcionalidade', 'Serviços de Entrega');
        $log->__set('parametros', $params);
        $log->__set('log', $query);
        $log->save($mainConnection);

		$query = 'SELECT DS_REGIAO_GEOGRAFICA FROM MW_REGIAO_GEOGRAFICA WHERE ID_REGIAO_GEOGRAFICA = ?';
		$params = array($_POST['regiao']);
		
		$rs = executeSQL($mainConnection, $query, $params, true);
		
		$retorno = 'true?regiao='.$rs['DS_REGIAO_GEOGRAFICA'].'&data='.$_POST['data'];
	} else {
		$retorno = sqlErrors();
	}
	
} else if ($_GET['action'] == 'update' and isset($_GET['regiao']) and isset($_GET['data'])) { /*------------ UPDATE ------------*/
	
	$data = strtotime(str_replace('/', '-', $_GET['data']));
	$hoje = strtotime(date('d-m-Y'));
	
	if ($data >= $hoje) {
		$query = "UPDATE T SET
						T.ID_REGIAO_GEOGRAFICA = ?,
						T.DT_INICIO_VIGENCIA = CONVERT(DATETIME, ?, 103),
						T.VL_TAXA_FRETE = ?
					FROM
						MW_TAXA_FRETE T
						INNER JOIN MW_REGIAO_GEOGRAFICA R ON R.ID_REGIAO_GEOGRAFICA = T.ID_REGIAO_GEOGRAFICA
					WHERE
						R.DS_REGIAO_GEOGRAFICA = ?
						AND T.DT_INICIO_VIGENCIA = CONVERT(DATETIME, ?, 103)";
		$params = array($_POST['regiao'], $_POST['data'], $_POST['valor'], $_GET['regiao'], $_GET['data']);
		
		if (executeSQL($mainConnection, $query, $params)) {
            $log = new Log($_SESSION['admin']);
            $log->__set('funcionalidade', 'Serviços de Entrega');
            $log->__set('parametros', $params);
            $log->__set('log', $query);
            $log->save($mainConnection);

			$query = 'SELECT DS_REGIAO_GEOGRAFICA FROM MW_REGIAO_GEOGRAFICA WHERE ID_REGIAO_GEOGRAFICA = ?';
			$params = array($_POST['regiao']);
			
			$rs = executeSQL($mainConnection, $query, $params, true);
			
			$retorno = 'true?regiao='.$rs['DS_REGIAO_GEOGRAFICA'].'&data='.$_POST['data'];
		} else {
			$retorno = sqlErrors();
		}
	} else {
		$retorno = 'Este registro ainda está em uso!';
	}
	
} else if ($_GET['action'] == 'delete' and isset($_GET['regiao']) and isset($_GET['data'])) { /*------------ DELETE ------------*/
	
	$data = strtotime(str_replace('/', '-', $_GET['data']));
	$hoje = strtotime(date('d-m-Y'));
	
	if ($data >= $hoje) {
		$query = 'DELETE T FROM MW_TAXA_FRETE T INNER JOIN MW_REGIAO_GEOGRAFICA R ON R.ID_REGIAO_GEOGRAFICA = T.ID_REGIAO_GEOGRAFICA WHERE
						R.DS_REGIAO_GEOGRAFICA = ? AND T.DT_INICIO_VIGENCIA = CONVERT(DATETIME, ?, 103)';
		$params = array($_GET['regiao'], $_GET['data']);
		
		if (executeSQL($mainConnection, $query, $params)) {
            $log = new Log($_SESSION['admin']);
            $log->__set('funcionalidade', 'Serviços de Entrega');
            $log->__set('parametros', $params);
            $log->__set('log', $query);
            $log->save($mainConnection);
            
			echo 'true';
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

}
?>