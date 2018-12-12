<?php
if (acessoPermitido($mainConnection, $_SESSION['admin'], 24, true)) {

	$_POST['dataInicio2'] = $_POST['dataInicio2'] ? $_POST['dataInicio2'] : null;
	$_POST['dataFim2'] = $_POST['dataFim2'] ? $_POST['dataFim2'] : null;
	$_POST['dataInicio3'] = $_POST['dataInicio3'] ? $_POST['dataInicio3'] : null;
	$_POST['dataFim3'] = $_POST['dataFim3'] ? $_POST['dataFim3'] : null;

if ($_GET['action'] == 'add') { /*------------ INSERT ------------*/

	$result1 = executeSQL($mainConnection, "SELECT 1 FROM MW_PACOTE_APRESENTACAO WHERE ID_APRESENTACAO = ?", array($_POST['apresentacao']));

	$result2 = executeSQL($mainConnection, "SELECT 1 FROM MW_PACOTE WHERE ID_APRESENTACAO = ?", array($_POST['apresentacao']));

	if (hasRows($result1)) {
		$retorno = 'Esta apresentação já está em um pacote!';
	} else if (hasRows($result2)) {
		$retorno = 'Esta apresentação já está em uso como um pacote!';
	} else {

		$query = 'INSERT INTO MW_PACOTE
					(ID_APRESENTACAO, DT_INICIO_FASE1, DT_FIM_FASE1, DT_INICIO_FASE2, DT_FIM_FASE2, DT_INICIO_FASE3, DT_FIM_FASE3)
					VALUES (?, CONVERT(DATETIME, ?, 103), CONVERT(DATETIME, ?, 103), CONVERT(DATETIME, ?, 103), CONVERT(DATETIME, ?, 103), CONVERT(DATETIME, ?, 103), CONVERT(DATETIME, ?, 103))';
		$params = array($_POST['apresentacao'], $_POST['dataInicio1'], $_POST['dataFim1'], $_POST['dataInicio2'], $_POST['dataFim2'], $_POST['dataInicio3'], $_POST['dataFim3']);
		executeSQL($mainConnection, $query, $params);
		
		$errors = sqlErrors();
		
		if (empty($errors)) {
            $log = new Log($_SESSION['admin']);
            $log->__set('funcionalidade', 'Pacotes');
            $log->__set('parametros', $params);
            $log->__set('log', $query);
            $log->save($mainConnection);

			$rs = executeSQL($mainConnection, "SELECT ID_PACOTE FROM MW_PACOTE WHERE ID_APRESENTACAO = ?", array($_POST['apresentacao']), true);

			$retorno = 'true?pacote='.$rs['ID_PACOTE'];
		} else {
			$retorno = sqlErrors();
		}
	}
	
} else if ($_GET['action'] == 'update') { /*------------ UPDATE ------------*/
	
	$query = "UPDATE MW_PACOTE
				SET
				DT_INICIO_FASE1 = CONVERT(DATETIME, ?, 103),
				DT_FIM_FASE1 = CONVERT(DATETIME, ?, 103),
				DT_INICIO_FASE2 = CONVERT(DATETIME, ?, 103),
				DT_FIM_FASE2 = CONVERT(DATETIME, ?, 103),
				DT_INICIO_FASE3 = CONVERT(DATETIME, ?, 103),
				DT_FIM_FASE3 = CONVERT(DATETIME, ?, 103)
				WHERE ID_PACOTE = ?";
	$params = array($_POST['dataInicio1'], $_POST['dataFim1'], $_POST['dataInicio2'], $_POST['dataFim2'], $_POST['dataInicio3'], $_POST['dataFim3'], $_GET['pacote']);
	
	if (executeSQL($mainConnection, $query, $params)) {
        $log = new Log($_SESSION['admin']);
        $log->__set('funcionalidade', 'Pacotes');
        $log->__set('parametros', $params);
        $log->__set('log', $query);
        $log->save($mainConnection);

		$retorno = 'true?pacote='.$_GET['pacote'];
	} else {
		$retorno = sqlErrors();
	}
	
} else if ($_GET['action'] == 'delete' and isset($_GET['pacote'])) { /*------------ DELETE ------------*/

	$result1 = executeSQL($mainConnection, "SELECT 1 FROM MW_PACOTE_RESERVA WHERE ID_PACOTE = ?", array($_GET['pacote']));

	$result2 = executeSQL($mainConnection, "SELECT 1 FROM MW_PEDIDO_VENDA V
											INNER JOIN MW_ITEM_PEDIDO_VENDA I ON I.ID_PEDIDO_VENDA = V.ID_PEDIDO_VENDA
											INNER JOIN MW_PACOTE P ON P.ID_APRESENTACAO = I.ID_APRESENTACAO
											WHERE P.ID_PACOTE = ? AND V.IN_PACOTE = 'S'", array($_GET['pacote']));
	
	if (hasRows($result1) or hasRows($result2)) {
		$retorno = 'Não foi possível excluir!<br/><br/>Já existem compras/reservas para este pacote.';
	} else {
		$query = 'DELETE FROM MW_PACOTE WHERE ID_PACOTE = ?';
		$params = array($_GET['pacote']);
		
		if (executeSQL($mainConnection, $query, $params)) {
            $log = new Log($_SESSION['admin']);
            $log->__set('funcionalidade', 'Pacotes');
            $log->__set('parametros', $params);
            $log->__set('log', $query);
            $log->save($mainConnection);
            
			$retorno = 'true';
		} else {
			$retorno = sqlErrors();
		}
	}
	
}

if (is_array($retorno)) {
	if ($retorno[0]['code'] == 547) {
		echo 'Não foi possível excluir!<br/><br/>Existem apresentações neste pacote.';
	} else {
		echo $retorno[0]['message'];
	}
} else {
	echo $retorno;
}

}
?>