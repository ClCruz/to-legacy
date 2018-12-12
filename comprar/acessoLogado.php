<?php
require_once('../settings/settings.php');
require_once('../settings/functions.php');
require_once('../settings/Cypher.class.php');

session_start();

//ACESSO PERMITIDO APENAS PARA CLIENTES LOGADOS
if (isset($_SESSION['user'])) {

	$cipher = new Cipher('1ngr3ss0s');
	$encryptedtext = $cipher->encrypt($_SESSION['user']);

	setcookie('user', $encryptedtext, $cookieExpireTime);

	if ($_SESSION['confirmar_email']) {
		header("Location: confirmacaoEmail.php?redirect=" . urlencode(getCurrentUrl()));
	}

} else {

	header("Location: login.php?redirect=" . urlencode(getCurrentUrl()));

}
?>