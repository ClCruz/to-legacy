<?php
require_once($_SERVER['DOCUMENT_ROOT']."/config/whitelabel.php");
require_once($_SERVER['DOCUMENT_ROOT']."/settings/multisite/tellmethesite.php");
require_once($_SERVER['DOCUMENT_ROOT']."/settings/multisite/names.php");

function multiSite_getLogo() {
    $ret = "";
    $ret = getwhitelabel()["logo"];
    return $ret;
}
function multiSite_getLogoFullURI() {
    $ret = "";
    $ret = multiSite_getLogo();    
    return $ret;
}
function multiSite_getGoogleAnalytics() {
    $ret = getwhitelabel()["ga"];
    return $ret;
}
function multiSite_getFavico() {
    $ret = getwhitelabel()["favico"];
    return $ret;
}
function multiSite_getDefaultMiniatura() {
    $ret = "https://media.tixs.me/card.png";    
    return $ret;
}
?>