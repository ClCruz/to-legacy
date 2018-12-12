<?php
require_once "../settings/settings.php";
require_once "../settings/functions.php";

session_start();

if ((!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest')) {

	header('Content-type: application/json');
	echo json_encode(array('valor' => number_format(obterValorServico($_POST['id_bilhete'], $_POST['servicoPorPedido']), 2, ',', '')));
	die();

}