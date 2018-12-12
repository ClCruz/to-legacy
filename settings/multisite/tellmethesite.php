<?php
require_once($_SERVER['DOCUMENT_ROOT']."/config/whitelabel.php");

 $whatIsTheSite = gethost();
// $whatIsTheSite = "compreingressos";
 //$whatIsTheSite = "ingressoslitoral";
// $whatIsTheSite = "bringressos";
// $whatIsTheSite = "ciadeingressos";
// $whatIsTheSite = "sazarte";
// $whatIsTheSite = "vivaingressos";

function getCurrentSite() {
    global $whatIsTheSite;
    $ret = $whatIsTheSite;
    return $ret;
}
?>