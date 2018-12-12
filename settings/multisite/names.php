<?php
require_once($_SERVER['DOCUMENT_ROOT']."/config/whitelabel.php");
include_once($_SERVER['DOCUMENT_ROOT']."/settings/multisite/tellmethesite.php");
    
function multiSite_getFacebook() {
    return "";
}
function multiSite_getTwitter() {
    return "";
}
function multiSite_getBlog() {
    return "";
}
function multiSite_getInstagram() {
    return "";
}
function multiSite_getYoutube() {
    return "";
}
function multiSite_getGooglePlus() {
    return "";
}

function multiSite_getName() {
    return getwhitelabel("appName");
}
function multiSite_getNameWithoutDotCom() {
    return getwhitelabel("appName");
}
function multiSite_getEmail($type) {
    $ret = getwhitelabelemail()[$type]["email"];
    return $ret;
}
function emailConfiguration($type) {
    $ret = array();
    $ret = array("smtp"=>getwhitelabelemail()["config"]["smtp"]["uri"]
        ,"port"=>getwhitelabelemail()["config"]["smtp"]["port"]
        ,"smtpsecure"=>getwhitelabelemail()["config"]["smtp"]["smtpsecure"]);
    return $ret;
}
function multiSite_getEmailPassword($type) {
    $ret = getwhitelabelemail()[$type]["pass"];;
    return $ret;
}
function multiSite_getPhone() {
    return "";
}
function multiSite_CNPJ() {
    $ret = getwhitelabel("cnpj");
    return $ret;
}
function multiSite_getTitle() {
    $ret = getwhitelabel("title");
    return $ret;
}
function multiSite_getURIReeimprimir($concat = "") {
    $ret = getwhitelabel("legacy")."/comprar/reimprimirEmail.php?pedido=";
    $ret .= $concat;
    return $ret;
}
function multiSite_getURICompra($concat = "") {
    $ret = getwhitelabel("legacy");
    if (substr($concat, 0, 1 ) != "/") {
        $ret = $ret."/";
    }
    $ret = $ret . $concat;
    return $ret;
}
function multiSite_getURIAdmin($concat = "") {
    $ret = getwhitelabel("legacy");

    $ret = $ret . "/admin/" . $concat;
    return $ret;
}
function multiSite_seloCertificado() {
    $ret = "https://seal.verisign.com/getseal?host_name=".getwhitelabel("host")."&size=S&use_flash=NO&use_transparent=getsealjs_b.js&lang=pt";
    return $ret;    
}
function multiSite_getDomainCompra() {
    $ret = str_replace("https://","",getwhitelabel("legacy"));
    return $ret;
}
function multiSite_getTomTicket() {
    $ret = "";
    return $ret;
}
function multiSite_getSearch($concat = "") {
    $ret = multiSite_getURI("URI_SSL");
    $ret .= "/busca/";
    $ret = $ret . $concat;
    return $ret;
}
function multiSite_getAPI() {
    $ret = "http://172.17.0.1:2002";
    return $ret;
}
function multiSite_getURI($type, $concat = "") {
    //die("getwhitelabelobj: ".json_encode(getwhitelabelobj()));
    $ret = getwhitelabel("uri");
    $ret = $ret . $concat;
    return $ret;
}
function getDateToString($time, $type) {
    $month = array("01"=>"janeiro", "02"=>"fevereiro", "03"=>"março", "04"=>"abril", "05"=>"maio", "06"=>"junho", "07"=>"julho", "08"=>"agosto", "09"=>"setembro", "10"=>"outubro", "11"=>"novembro", "12"=>"dezembro");
    $week = array("1"=>"domingo", "2"=>"segunda-feira", "3"=>"terça-feira", "4"=>"quarta-feira", "5"=>"quinta-feira", "6"=>"sexta-feira", "7"=>"sabado");
    $ret = "";
    
    switch ($type) {
        case "month":
            $ret = date('l',$time);
            $ret = $month[date('m',$time)];
        break;
        case "month-small":
            $ret = date('l',$time);
            $ret = substr($month[strval(date('m',$time))],0,3);
        break;
        case "week":
            $ret = date('M',$time);
            $ret = $week[date('w',$time)+1];
        break;
        case "week-small":
            $ret = date('M',$time);
            $ret = substr($week[strval(date('w',$time)+1)],0,3);
        break;
    }
    return $ret;
}
?>