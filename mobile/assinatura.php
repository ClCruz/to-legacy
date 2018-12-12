<?php

require_once('../settings/settings.php');
require_once('../settings/functions.php');
$mainConnection = mainConnection();
session_start();

if($_POST['action'] == "consulta"){
	$query = "SELECT ID_ASSINATURA, DS_ASSINATURA FROM MW_ASSINATURA ORDER BY DS_ASSINATURA";
    $result = executeSQL($mainConnection, $query, array());

    $combo = array();
    while ($rs = fetchResult($result)) {
        $combo[] = array("id" => $rs['ID_ASSINATURA'], "value" => utf8_encode2($rs['DS_ASSINATURA']));
    }        
    echo json_encode($combo);
    die();
} else if ($_REQUEST['action'] == "venda"){

    if(!isset($_REQUEST["id_usuario"])){
        echo "<h1>Usuário Inválido!</h1>";
        die();
    }
	$_SESSION['operador'] = $_REQUEST['id_usuario'];
	$_SESSION['usuario_pdv'] = 0;
    $_SESSION['user'] = null;    
    if(!isset($_REQUEST["assinatura"])){
        echo "<h1>Assinatura Inválida!</h1>";
        die();
    }
	$redirect = "assinatura.php?id=". $_REQUEST['assinatura'];
	header("Location: ../comprar/etapa3_2.php?assinatura=1&redirect=". urlencode($redirect));
}

?>