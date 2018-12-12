<?php

require_once('../settings/functions.php');
$mainConnection = mainConnection();
session_start();

if (acessoPermitido($mainConnection, $_REQUEST['ID_USUARIO'], 305, true)) {
    
    $_SESSION['admin'] = $_REQUEST['ID_USUARIO'];

    $strQuery = "SELECT DS_NOME_BASE_SQL FROM MW_BASE WHERE ID_BASE = ".$_REQUEST["local"];
    $stmt = executeSQL($mainConnection, $strQuery, array(), true);
    $_SESSION["NomeBase"] = $stmt["DS_NOME_BASE_SQL"];
    //echo $stmt["DS_NOME_BASE_SQL"];
    $url = "CodPeca=". $_REQUEST["CodPeca"] ."&logo=imagem&Resumido=0&Small=0&DataIni=". $_REQUEST["DataIni"] ."&DataFim=". $_REQUEST["DataFim"] ."&HorSessao=". $_REQUEST["HorSessao"] ."&Sala=". $_REQUEST["Sala"];
    //echo $url;
    header("Location: ../admin/relBorderoCompleto2.php?". $url);
}else{
    json_encode(array("retorno" => "Acesso negado."));
}

?>