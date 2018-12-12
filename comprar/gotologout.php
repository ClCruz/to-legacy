<?php
session_start();
header("Access-Control-Allow-Origin: *");
require_once('../settings/settings.php');
require_once('../settings/functions.php');

foreach ($_COOKIE as $key => $val) {
	setcookie($key, "", time() - 3600);
}

session_start();
session_unset();
session_destroy();

header('Location: www.tixs.me');
die();
?>