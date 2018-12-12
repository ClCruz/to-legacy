<?php
if (acessoPermitido($mainConnection, $_SESSION['admin'], 24, true)) {

if ($_GET['action'] == 'add') { /*------------ INSERT ------------*/

	$query = "SELECT ID_APRESENTACAO FROM MW_APRESENTACAO WHERE ID_EVENTO = ? AND IN_ATIVO = 1 AND CONVERT(VARCHAR(10), DT_APRESENTACAO, 103) = ? AND HR_APRESENTACAO = ? ORDER BY ID_APRESENTACAO";
	$params = array($_POST['evento'], $_POST['data'], $_POST['hora']);
	$result = executeSQL($mainConnection, $query, $params);

	$apresentacoes = array();

	while ($rs = fetchResult($result) and empty($retorno)) {

		$result1 = executeSQL($mainConnection, "SELECT 1 FROM MW_PACOTE_APRESENTACAO WHERE ID_APRESENTACAO = ?", array($rs['ID_APRESENTACAO']));

		$result2 = executeSQL($mainConnection, "SELECT 1 FROM MW_PACOTE WHERE ID_APRESENTACAO = ?", array($rs['ID_APRESENTACAO']));

		$result3 = executeSQL($mainConnection, "SELECT 1 FROM MW_ITEM_PEDIDO_VENDA WHERE ID_APRESENTACAO = ?", array($rs['ID_APRESENTACAO']));

		if (hasRows($result1)) {
			$retorno = 'Esta apresentação já está em um pacote!';
		} else if (hasRows($result2)) {
			$retorno = 'Esta apresentação já está em uso como um pacote!';
		} else if (hasRows($result3)) {
			$retorno = 'Esta apresentação já tem lugares vendidos!';
		} else {
			$apresentacoes[] = $rs['ID_APRESENTACAO'];
		}
	}

	if (empty($retorno)) {
		foreach ($apresentacoes as $id) {
			$query = 'INSERT INTO MW_PACOTE_APRESENTACAO (ID_PACOTE, ID_APRESENTACAO) VALUES (?, ?)';
			$params = array($_POST['pacote'], $id);
			executeSQL($mainConnection, $query, $params);

			$errors = sqlErrors();

			if (!empty($errors)) {
				break;
			} else {
                $log = new Log($_SESSION['admin']);
                $log->__set('funcionalidade', 'Apresentações do Pacote');
                $log->__set('parametros', $params);
                $log->__set('log', $query);
                $log->save($mainConnection);
			}
		}
	}

	if (empty($errors) and empty($retorno)) {
		$retorno = 'true?apresentacao='.$apresentacoes[0];
	} else if (empty($retorno)) {
		$retorno = sqlErrors();
	}
	
} else if ($_GET['action'] == 'delete' and isset($_GET['apresentacao'])) { /*------------ DELETE ------------*/

	$query = "SELECT A.ID_APRESENTACAO FROM MW_APRESENTACAO A
				INNER JOIN MW_APRESENTACAO B ON B.ID_EVENTO = A.ID_EVENTO AND B.DT_APRESENTACAO = A.DT_APRESENTACAO AND B.HR_APRESENTACAO = A.HR_APRESENTACAO
				WHERE A.IN_ATIVO = '1' AND B.IN_ATIVO = '1' AND B.ID_APRESENTACAO = ?";
	$params = array($_GET['apresentacao']);
	$result = executeSQL($mainConnection, $query, $params);

	$apresentacoes = '';

	while ($rs = fetchResult($result) and empty($retorno)) {

		$result1 = executeSQL($mainConnection, "SELECT 1 FROM MW_PACOTE_RESERVA R
												INNER JOIN MW_PACOTE_APRESENTACAO P ON P.ID_PACOTE = R.ID_PACOTE
												WHERE P.ID_APRESENTACAO = ?", array($rs['ID_APRESENTACAO']));

		$result2 = executeSQL($mainConnection, "SELECT 1 FROM MW_ITEM_PEDIDO_VENDA WHERE ID_APRESENTACAO = ?", array($rs['ID_APRESENTACAO']));

		if (hasRows($result1) or hasRows($result2)) {
			$retorno = 'Não foi possível excluir!<br/><br/>Já existem compras/reservas para esta apresentação.';
		} else {
			$apresentacoes .=  $rs['ID_APRESENTACAO'] . ',';
		}
	}
	
	$apresentacoes = substr($apresentacoes, 0, -1);

	if (empty($retorno)) {
		$query = 'DELETE FROM MW_PACOTE_APRESENTACAO WHERE ID_PACOTE = ? AND ID_APRESENTACAO IN ('.$apresentacoes.')';
		$params = array($_POST['pacote']);
		
		if (executeSQL($mainConnection, $query, $params)) {
            $log = new Log($_SESSION['admin']);
            $log->__set('funcionalidade', 'Apresentações do Pacote');
            $log->__set('parametros', $params);
            $log->__set('log', $query);
            $log->save($mainConnection);
            
			$retorno = 'true';
		} else {
			$retorno = sqlErrors();
		}
	}
	
} else if ($_GET['action'] == 'comboEvento') { /*------------ COMBO EVENTOS ------------*/
	
	$result = executeSQL($mainConnection, "SELECT E.ID_EVENTO, E.DS_EVENTO
											FROM MW_EVENTO E
											INNER JOIN MW_ACESSO_CONCEDIDO AC ON AC.ID_BASE = E.ID_BASE AND AC.CODPECA = E.CODPECA AND AC.ID_USUARIO = ?
											WHERE E.IN_ATIVO = 1 AND E.ID_BASE = ?
											AND E.ID_EVENTO NOT IN (SELECT ID_EVENTO FROM MW_PACOTE P INNER JOIN MW_APRESENTACAO A ON A.ID_APRESENTACAO = P.ID_APRESENTACAO)
											AND E.ID_EVENTO NOT IN (SELECT ID_EVENTO FROM MW_PACOTE_APRESENTACAO P INNER JOIN MW_APRESENTACAO A ON A.ID_APRESENTACAO = P.ID_APRESENTACAO WHERE ID_PACOTE = ?)
											AND E.ID_EVENTO NOT IN (SELECT ID_EVENTO FROM MW_ITEM_PEDIDO_VENDA IP INNER JOIN MW_APRESENTACAO A ON A.ID_APRESENTACAO = IP.ID_APRESENTACAO)
											ORDER BY DS_EVENTO", array($_SESSION['admin'], $_POST['base'], $_POST['pacote']));

    $retorno = '<option value="">Selecione um evento...</option>';
    while ($rs = fetchResult($result)) {
		$retorno .= '<option value="' . $rs['ID_EVENTO'] . '">' . utf8_encode2($rs['DS_EVENTO']) . '</option>';
    }
	
} else if ($_GET['action'] == 'comboData') { /*------------ COMBO DATAS ------------*/
	
	$result = executeSQL($mainConnection, "SELECT DISTINCT DT_APRESENTACAO
											FROM MW_APRESENTACAO
											WHERE IN_ATIVO = 1 AND DT_APRESENTACAO > GETDATE() AND ID_EVENTO = ?
											ORDER BY DT_APRESENTACAO", array($_POST['evento']));

    $retorno = '<option value="">Selecione uma data...</option>';
    while ($rs = fetchResult($result)) {
		$retorno .= '<option value="' . $rs['DT_APRESENTACAO']->format('d/m/Y') . '">' . $rs['DT_APRESENTACAO']->format('d/m/Y') . '</option>';
    }
	
} else if ($_GET['action'] == 'comboHora') { /*------------ COMBO HORAS ------------*/
	
	$result = executeSQL($mainConnection, "SELECT DISTINCT HR_APRESENTACAO
											FROM MW_APRESENTACAO
											WHERE IN_ATIVO = 1 AND CONVERT(VARCHAR(10), DT_APRESENTACAO, 103) = ? AND ID_EVENTO = ?
											ORDER BY HR_APRESENTACAO", array($_POST['data'], $_POST['evento']));

    $retorno = '<option value="">Selecione um horário...</option>';
    while ($rs = fetchResult($result)) {
		$retorno .= '<option value="' . $rs['HR_APRESENTACAO'] . '">' . $rs['HR_APRESENTACAO'] . '</option>';
    }
	
}

if (is_array($retorno)) {
	if ($retorno[0]['code'] == 547) {
		echo 'Não foi possível excluir!<br/><br/>Existem vendas desta apresentação.';
	} else {
		echo $retorno[0]['message'];
	}
} else {
	echo $retorno;
}

}
?>