<?php
session_start();
header("Access-Control-Allow-Origin: *");
require_once('../settings/settings.php');
require_once('../settings/functions.php');


if ($_REQUEST['token']!='') {
    $mainConnection = mainConnection();
	
    $query = 'SELECT ID_CLIENTE FROM CI_MIDDLEWAY..mw_cliente WHERE token = ? AND dt_token_valid>=GETDATE()';
    $params = array($_REQUEST['token']);
        
    $rs = executeSQL($mainConnection, $query, $params, true);
//    die("ccc".print_r($rs,true));
    if ($rs['ID_CLIENTE']) {
        $_SESSION['user'] = $rs['ID_CLIENTE'];
    }   
}
if ($_REQUEST["id"] == "") {
    header('Location: www.tixs.me');
}
else {
    header('Location: /comprar/etapa1.php?apresentacao='.$_REQUEST["id"]);
}
die();
?>