<?php
if (!isset($_SESSION['userItau']) or !is_numeric($_SESSION['userItau'])) {
	if ($_SERVER['X-Requested-With'] === 'XMLHttpRequest')
		exit(json_encode(array('error'=>'Favor efetuar a autenticaчуo.', 'redirect'=>'login.php')));
	
	header('Location: login.php');
}

if (isset($_SESSION['senha']) and basename($_SERVER['PHP_SELF']) != 'senha.php') header('Location: senha.php');
?>