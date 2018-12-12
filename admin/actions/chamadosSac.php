<?php

if (acessoPermitido($mainConnection, $_SESSION['admin'], 32, true)) {

    $_POST['dia'] = explode('/', $_POST['dia']);
    $_POST['dia'] = $_POST['dia'][2] . $_POST['dia'][1] . $_POST['dia'][0];

    $_POST['diaResolucao'] = explode('/', $_POST['diaResolucao']);
    $_POST['diaResolucao'] = $_POST['diaResolucao'][2] . $_POST['diaResolucao'][1] . $_POST['diaResolucao'][0];

    if ($_GET['action'] == 'add') { /* ------------ INSERT ------------ */

	$query = "INSERT INTO FATO_SAC
		    (ID_DIA,ID_ORIGEM_CHAMADO,ID_TIPO_CHAMADO,ID_TIPO_RESOLUCAO,ID_DIA_RESOLUCAO,DS_OBSERVACAO)
		    VALUES (?,?,?,?,?,?)";
	$params = array($_POST['dia'], $_POST['origem'], $_POST['tipo'], $_POST['resolucao'], $_POST['diaResolucao'], utf8_encode2($_POST['obs']));

	if (executeSQL($conn, $query, $params)) {
        $log = new Log($_SESSION['admin']);
        $log->__set('funcionalidade', 'SAC-Chamados');
        $log->__set('parametros', $params);
        $log->__set('log', $query);
        $log->save($mainConnection);

	    $query = "SELECT MAX(ID_NR_CHAMADO) ID_NR_CHAMADO FROM FATO_SAC
		    WHERE ID_DIA = ? AND ID_ORIGEM_CHAMADO = ? AND ID_TIPO_CHAMADO = ?
		    AND ID_TIPO_RESOLUCAO = ? AND ID_DIA_RESOLUCAO = ? AND DS_OBSERVACAO = ?";
	    $rs = executeSQL($conn, $query, $params, true);
	    $retorno = 'true?id=' . $rs['ID_NR_CHAMADO'];
	} else {
	    $retorno = sqlErrors();
	}

    } else if ($_GET['action'] == 'update' and isset($_GET['id'])) { /* ------------ UPDATE ------------ */

	$query = "UPDATE FATO_SAC SET
		    ID_DIA = ?,
		    ID_ORIGEM_CHAMADO = ?,
		    ID_TIPO_CHAMADO = ?,
		    ID_TIPO_RESOLUCAO = ?,
		    ID_DIA_RESOLUCAO = ?,
		    DS_OBSERVACAO = ?
		    WHERE ID_NR_CHAMADO = ?";
	$params = array($_POST['dia'], $_POST['origem'], $_POST['tipo'], $_POST['resolucao'], $_POST['diaResolucao'], utf8_encode2($_POST['obs']), $_GET['id']);

	if (executeSQL($conn, $query, $params)) {
        $log = new Log($_SESSION['admin']);
        $log->__set('funcionalidade', 'SAC-Chamados');
        $log->__set('parametros', $params);
        $log->__set('log', $query);
        $log->save($mainConnection);

	    $retorno = 'true?id=' . $_GET['id'];
	} else {
	    $retorno = sqlErrors();
	}
    } else if ($_GET['action'] == 'delete' and isset($_GET['id'])) { /* ------------ DELETE ------------ */

	$query = 'DELETE FROM FATO_SAC WHERE ID_NR_CHAMADO = ?';
	$params = array($_GET['id']);

	if (executeSQL($conn, $query, $params)) {
	    $log = new Log($_SESSION['admin']);
	    $log->__set('funcionalidade', 'SAC-Chamados');
	    $log->__set('parametros', $params);
	    $log->__set('log', $query);
	    $log->save($mainConnection);
	    
	    $retorno = 'true';
	} else {
	    $retorno = sqlErrors();
	}
    }

    if (is_array($retorno)) {
	if ($retorno[0]['code'] == 2627) {
	    echo 'Esse dia/chamado já está cadastrado.';
	} else {
	    echo $retorno[0]['message'];
	}
    } else {
	echo $retorno;
    }
}
?>