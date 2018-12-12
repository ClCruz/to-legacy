<?php
require_once('../settings/functions.php');
require_once('../settings/settings.php');
session_start();

// verifica os dados se nao for um usuario PDV ou se tiver marcado entrega
if (!(isset($_SESSION['usuario_pdv']) and $_SESSION['usuario_pdv'] == 1) OR isset($_COOKIE['entrega'])) {

	$query = "SELECT NR_ENDERECO FROM MW_CLIENTE WHERE ID_CLIENTE = ?";
	$params = array($_SESSION['user']);
	$rs = executeSQL($mainConnection, $query, $params, true);

	if ($rs['NR_ENDERECO'] == NULL) {
		$redirect = 'minha_conta.php?atualizar_dados=1';
	}

	if (isset($_COOKIE['entrega']) AND $_COOKIE['entrega'] != -1) {
		$query = "SELECT NR_ENDERECO FROM MW_ENDERECO_CLIENTE WHERE ID_ENDERECO_CLIENTE = ?";
		$params = array($_COOKIE['entrega']);
		$rs = executeSQL($mainConnection, $query, $params, true);

		if ($rs['NR_ENDERECO'] == NULL) {
			$redirect = ($redirect ? $redirect.'&' : 'minha_conta.php?').'atualizar_endereco='.$_COOKIE['entrega'];
		}
	}

	if ($redirect) {
		$redirect .= '&redirect=etapa5.php';

		header("Location: $redirect");
	}

}