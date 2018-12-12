<?php

require_once("../settings/multisite/tellmethesite.php");

require_once("../settings/multisite/cnnConfig.php");
function oi() {
	return "oi";
}
function mainConnection() {
	//die("dddaa");
        $host = multiSite_getCurrentSQLServer()["host"];
	$port = multiSite_getCurrentSQLServer()["port"];
	$dbname = 'CI_MIDDLEWAY';
	$user = multiSite_getCurrentSQLServer()["user"];
	$pass = multiSite_getCurrentSQLServer()["pass"];
	// echo "<br />host: " .$host;
	// echo "<br />port: " .$port;
	// echo "<br />dbname: " .$dbname;
	// echo "<br />host: " .$user;
	// echo "<br />host: " .$pass;
        return sqlsrv_connect($host.','.$port, array("UID" => $user, "PWD" => $pass, "Database" => $dbname));
}

function getConnection($teatroID) {
        $mainConnection = mainConnection();
        $rs = executeSQL($mainConnection, 'SELECT DS_NOME_BASE_SQL FROM MW_BASE WHERE ID_BASE = ?', array($teatroID), true);

        $host = multiSite_getCurrentSQLServer()["host"];
        $port = multiSite_getCurrentSQLServer()["port"];
	$user = multiSite_getCurrentSQLServer()["user"];
	$pass = multiSite_getCurrentSQLServer()["pass"];

        return sqlsrv_connect($host.','.$port, array("UID" => $user, "PWD" => $pass, "Database" => $rs['DS_NOME_BASE_SQL']));
}

function getConnectionTsp() {
        $host = multiSite_getCurrentSQLServer()["host"];
        $port = multiSite_getCurrentSQLServer()["port"];
        $dbname = 'tspweb';
	$user = multiSite_getCurrentSQLServer()["user"];
	$pass = multiSite_getCurrentSQLServer()["pass"];

        return sqlsrv_connect($host.','.$port, array("UID" => $user, "PWD" => $pass, "Database" => $dbname));
}

function getConnectionDw() {
    $host = 'localhost\\sql2008';
        $port = '1433';
        $dbname = 'CI_DW';
        $user = 'sa';
        $pass = 'sa';

        return sqlsrv_connect($host.','.$port, array("UID" => $user, "PWD" => $pass, "Database" => $dbname));
}

function getConnectionHome() {
	return false;

	if ($_ENV['IS_TEST']) return false;
	// return false;

	$host = multiSite_getCurrentMysql()["host"];;
	$port = multiSite_getCurrentMysql()["port"];
	$dbname = multiSite_getCurrentMysql()["database"];
	$user = multiSite_getCurrentMysql()["user"];;
	$pass = multiSite_getCurrentMysql()["pass"];;

	try {
		$conn = new PDO("mysql:host=$host;port=$port;dbname=$dbname", $user, $pass);
		$conn->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
		$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	} catch (Exception $e) {
		$conn = false;
	}

	return $conn;
}
?>