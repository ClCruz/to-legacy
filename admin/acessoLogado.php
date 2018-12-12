<?php
require_once('../settings/settings.php');
require_once('../settings/functions.php');
session_start();

//ACESSO PERMITIDO APENAS PARA ADMINS LOGADOS
if (isset($_SESSION['admin'])) {
	setcookie('admin', $_SESSION['admin'], $cookieExpireTime);
	if ($_SESSION['senha'] == true) {
		header("Location: login.php?action=trocarSenha&redirect=" . urlencode(getCurrentUrl()));
	}
} else {
	header("Location: login.php?redirect=" . urlencode(getCurrentUrl()));
}
?>