<?php
require_once('../settings/functions.php');
require_once('../settings/settings.php');

session_start();

if (isset($_GET['origem'])) {
	$mainConnection = mainConnection();

	$_GET['origem'] = strtolower(trim($_GET['origem']));

	$query = 'SELECT ID_ORIGEM FROM MW_ORIGEM WHERE CD_ORIGEM = ?';
	$params = array($_GET['origem']);
	$rs = executeSQL($mainConnection, $query, $params, true);

	if (empty($rs)) {
		$query = 'INSERT INTO MW_ORIGEM VALUES (?, NULL)';
		executeSQL($mainConnection, $query, $params);

		$query = 'SELECT ID_ORIGEM FROM MW_ORIGEM WHERE CD_ORIGEM = ?';
		$rs = executeSQL($mainConnection, $query, $params, true);
	}

	$_SESSION['origem'] = $rs['ID_ORIGEM'];
} else {
	unset($_SESSION['origem']);
}