<?php
session_start();
session_unset();

header("Access-Control-Allow-Origin: *");
require_once('../settings/settings.php');
require_once('../settings/functions.php');
require_once($_SERVER['DOCUMENT_ROOT']."/config/whitelabel.php");

if (gethost() == "compreingressos") {
// if (gethost() == "localhost") {
    header('Location: '.getwhitelabelobj_forced("teatroumc")["legacy"]."/comprar/gotoshopping.php?token=".$_REQUEST["token"]."&id=".$_REQUEST["id"]);
    die();
}

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
// die(gethost());
if ($_REQUEST["id"] == "") {
    header('Location: '.getwhitelabelobj()["uri"]);
}
else {
    header('Location: /comprar/etapa1.php?apresentacao='.$_REQUEST["id"]);
}
die();
?>