<?php
session_start();
session_unset();
header("Access-Control-Allow-Origin: *");
require_once('../settings/settings.php');
require_once('../settings/functions.php');
if ($_REQUEST['token']!='') {
    $mainConnection = mainConnection();
	
    $query = 'SELECT ID_CLIENTE FROM CI_MIDDLEWAY..mw_cliente WHERE token = ? AND dt_token_valid>=GETDATE()';
    $params = array($_REQUEST['token']);
        
    $rs = executeSQL($mainConnection, $query, $params, true);
    if ($rs['ID_CLIENTE']) {
        $_SESSION['user'] = $rs['ID_CLIENTE'];
    }   
}

header('Location: /comprar/minha_conta.php');
die();
?>