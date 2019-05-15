<?php
require_once($_SERVER['DOCUMENT_ROOT']."/config/whitelabel.php");
require_once($_SERVER['DOCUMENT_ROOT']."/settings/multisite/unique.php");

error_reporting(0);
$nomeSite = multiSite_getName();
$homeSite = multiSite_getURI("URI_SSL");
$title = "";// . ' - Painel Administrativo';

$locale = setlocale(LC_ALL, "pt_BR", "pt_BR.iso-8859-1", "pt_BR.utf-8", "portuguese");

if ( isset($_ENV['IS_TEST']) )
{
	$cookieExpireTime = time() + 60 * 120; //20min
	$compraExpireTime = 120;//minutos
}
else
{
	$cookieExpireTime = time() + 60 * 20; //20min
	$compraExpireTime = 15;//minutos
}

if ($_SERVER["HTTP_HOST"] == "localhost:2004") {
	$cookieExpireTime = time() + 60 * 180;
	$compraExpireTime = 180;
	
}

$uploadPath = '../images/uploads/';

$isContagemAcessos = true;
$is_manutencao = false;
$recaptcha = array(
	'private_key' => getwhitelabel("recaptcha_private"),
	'public_key' => getwhitelabel("recaptcha_public")
);

$mail_mkt = array(
	'login' => 'WScompreingr',
	'senha' => '13042015XY',
	'lista' => 'Clientes'
);

// para obter o caminho de upload do background na edicao de plateia
if (isset($_REQUEST['var'])) {
	echo $$_REQUEST['var'];
}
?>