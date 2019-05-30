<?php
require_once($_SERVER['DOCUMENT_ROOT']."/settings/multisite/tellmethesite.php");
require_once($_SERVER['DOCUMENT_ROOT']."/config/whitelabel.php");
$isDev = true;

function multiSite_getCurrentSQLServer() {
    global $isDev;
    
    $ret = array("host" => null, "user" => null, "pass" => null, "port"=> "1433");
    $ret = array("host" => getwhitelabeldb()["host"]
                ,"user" => getwhitelabeldb()["user"]
                ,"pass" => getwhitelabeldb()["pass"]
                ,"port"=> getwhitelabeldb()["port"]);    
    return $ret;
}
function multiSite_getCurrentMysql() {
    $ret = array("host" => null, "user" => null, "pass" => null, "database" => null);
    die("nomore");    
    return $ret;
}

?>