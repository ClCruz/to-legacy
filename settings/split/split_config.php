<?php
require_once('../settings/functions.php');
require_once($_SERVER['DOCUMENT_ROOT']."/settings/multisite/unique.php");

require_once('../settings/pagarme/Pagarme.php');

require_once('../log4php/log.php');

$gw_pagarme = array("apikey" => "", "postbackURI"=> "");
$gw_tipagos = array("idLoja" => "", "keyLoja"=> "", "codProduto"=> "", "url_ws"=> "");
$postback_url = "";


//$type: pagarme, tipagos
function configureSplit($type) {
    global $gw_pagarme, $gw_tipagos, $postback_url;
    //log_trace("Call configureSplit to " . $type . " isTest? " . $_ENV['IS_TEST']);

    switch ($type) {
        case "pagarme":
            $gw_pagarme["apikey"] = getwhitelabel_gateway_pagarme()["api"];
            $gw_pagarme["postbackURI"] = getwhitelabel("legacy")."/comprar/pagarme_receiver.php";
            log_trace("configureSplit variables.");

            Pagarme::setApiKey($gw_pagarme["apikey"]);
            $postback_url = $gw_pagarme["postbackURI"];
            log_trace("configureSplit of pagare.");

            return $gw_pagarme;
    break;
        case "tipagos":
            if ($_ENV['IS_TEST']) {
                $gw_tipagos["url_ws"] = "https://www.ti-pagos.com/bridgeservices/";
                $gw_tipagos["idLoja"] = "7309"; 
                $gw_tipagos["keyLoja"] = "49994822278418282883";
                $gw_tipagos["codProduto"] = "47";
            
            } else {
                $gw_tipagos["url_ws"] = "https://www.ti-pagos.com/bridgeservices/";
                $gw_tipagos["idLoja"] = "7922"; 
                $gw_tipagos["keyLoja"] = "88281288497982783035";
                $gw_tipagos["codProduto"] = "55";
            }
            log_trace("configureSplit variables.");
            return $gw_tipagos;
        break;
    }
    return null;
}
?>