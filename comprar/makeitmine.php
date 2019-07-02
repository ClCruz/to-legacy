<?php
require_once('../settings/functions.php');
require_once('../settings/settings.php');
session_start();

if (isset($_SESSION["user"]) && is_numeric($_SESSION["user"])) {
	$mainConnection = mainConnection();
	
	$query = 'EXEC dbo.pr_purchase_makeitbemine ?,?';
	$params = array(session_id(),$_SESSION["user"]);
	$result = executeSQL($mainConnection, $query, $params);
}
?>
