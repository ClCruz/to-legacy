<?php
session_start();
if (isset($_GET['redirect']) and $_GET['redirect'] != '') {
	if ($_SESSION['senha'] == true) {
		header("Location: trocaSenha.php?redirect=" . urldecode($_GET['redirect']));
	} else {
		header("Location: " . urldecode($_GET['redirect']));
	}
} else {
	if (isset($_SESSION['operador'])) {
		if ($_SESSION['senha'] == true) {
			header("Location: trocaSenha.php?redirect=etapa0.php");
		} else {
			header("Location: etapa0.php");
		}
	} else {
		header("Location: minha_conta.php");
	}
}
?>