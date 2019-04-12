<?php
require_once('../settings/settings.php');
require_once('../settings/functions.php');
require_once('../settings/Cypher.class.php');

session_start();

//AQUI PARA FORCAR USUARIO MUDAR
// $_SESSION["user"] = 1197;

//ACESSO PERMITIDO APENAS PARA CLIENTES LOGADOS
if (isset($_SESSION['user'])) {
	
	$cipher = new Cipher('1ngr3ss0s');
	$encryptedtext = $cipher->encrypt($_SESSION['user']);
	
	setcookie('user', $encryptedtext, $cookieExpireTime);
} 
else {
	if ($_SERVER["PHP_SELF"]  == "/comprar/reimprimirEmail.php") {
		header("Location: ".getwhitelabelURI_home("/loginandshopping/printafter?pedido=".$_REQUEST["pedido"]));		
		die();
	}
	header("Location: login.php?redirect=" . urlencode(getCurrentUrl()));
}
?>