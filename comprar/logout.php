<?php
require_once($_SERVER['DOCUMENT_ROOT']."/settings/multisite/unique.php");
foreach ($_COOKIE as $key => $val) {
	setcookie($key, "", time() - 3600);
}

session_start();
session_unset();
session_destroy();

//die(multiSite_getURI("URI_SSL"));

if (!isset($_GET['redirect'])) {
	header("Location: ".multiSite_getURI("URI_SSL")."?logout=true");
} else {
	header("Location: ".$_GET['redirect']);
}
?>