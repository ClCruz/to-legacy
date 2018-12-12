<?php
require_once '../settings/functions.php';
require_once '../settings/settings.php';
// error_reporting(E_ALL);
// echo "<HTMLDEBUG ON>";
echo "<HTMLDEBUG OFF>";
session_start();

require_once 'functions.php';

require_once 'configs.php';

require_once 'login_required.php';

// history - go back -------------- begin

// todas as variaveis do GET que podem conter o comando de voltar
$vars_to_go_back = array('history', 'evento', 'apresentacao', 'bilhete', 'confirmacao', 'pedido', 'fileira', 'cadeira');

foreach ($vars_to_go_back as $value) {
	if (in_array($value, array_keys($_GET))) {
		if ($_GET[$value] == 999999999) {

			// remove a tela atual da variavel de navegacao
			array_pop($_SESSION['history']);

			// remove a tela anterior e repassa as variaveis antigas para o novo request
			$_GET = array_pop($_SESSION['history']);

			break;
		}
	}
}

// history - go back -------------- end


// controle das telas principais - menu
$menu_options	= array('Venda',	'Reimpressão',	'Estorno',	'Relatórios',	'Sair');
$menu_pages		= array('sell',		'reprint',		'refund',	'reports',		'logoff');

if (in_array($_SESSION['pos_user']['id'], array(1, 3, 8, 555))) {
	$menu_options[] = 'dev_config';
	$menu_pages[]	= 'dev_config';
}

if (isset($_GET['screen'])) {

	if ($menu_pages[$_GET['screen']] != null) {
		$_SESSION['screen'] = $menu_pages[$_GET['screen']];
	} else {
		unset($_SESSION['screen']);
	}
}


// se o comando de reset for enviado entao limpar reservas e variaveis de navegacao
if (isset($_GET['reset'])) {

	limpa_navegacao();
}


if (!isset($_SESSION['screen'])) {

	if (!isset($_GET['reset'])) {

		// maquininha volta para o ultimo init toda vez que o cancelar é pressionado

		echo "<INIT KEEP_COOKIES=1>";
		echo_header();
		echo "<WRITE_AT LINE=9 COLUMN=0>          Aguarde...</WRITE_AT>";
		echo "<GET TYPE=HIDDEN NAME=reset VALUE=1>";
		echo "<POST>";
		die();
	}

	echo_header();

	echo utf8_decode("<WRITE_AT LINE=5 COLUMN=0> {$_SESSION['pos_user']['name']},</WRITE_AT>");
	echo utf8_decode("<WRITE_AT LINE=7 COLUMN=0> Selecione uma ação:</WRITE_AT>");

	echo_select('screen', $menu_options, 6);

	echo "<POST>";

} else {

	if (!isset($_GET['ignore_history'])) {
		$_SESSION['history'][] = $_GET;
	}

	require_once $_SESSION['screen'].'.php';
}