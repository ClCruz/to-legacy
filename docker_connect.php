<html>
<head>
</head>
<body>

<?php
function prodConnection() {
	$host = '192.168.91.17';
	$port = '1433';
	$dbname = 'CI_MIDDLEWAY';
	$user = 'web';
	$pass = '!ci@web@2018!';
	
	return sqlsrv_connect($host.','.$port, array("UID" => $user, "PWD" => $pass, "Database" => $dbname));
}
function devConnection() {
	$host = '192.168.11.3\sqlstd2012';
	$port = '1433';
	$dbname = 'APACS';
	$user = 'dev';
	$pass = 'Intuiti@2018!';
	
	return sqlsrv_connect($host.','.$port, array("UID" => $user, "PWD" => $pass, "Database" => $dbname));
}
function FormatErrors( $errors )  
{  
    /* Display errors. */  
    echo "Error information: <br/>";  

    foreach ( $errors as $error )  
    {  
        echo "SQLSTATE: ".$error['SQLSTATE']."<br/>";  
        echo "Code: ".$error['code']."<br/>";  
        echo "Message: ".$error['message']."<br/>";  
    }  
} 

function executeSQL($conn, $strSql, $params = array(), $returnRs = false) {

    try {

        if (empty($params)) {
            echo "executing with no param.<br />";
            $result = sqlsrv_query($conn, $strSql);
        } else {
            echo "executing with param.<br />";
            $result = sqlsrv_query($conn, $strSql, $params);
        }

        echo "executed.<br />";
        //echo print_r($result, true);
        if ($returnRs) {
            echo "returning RS.<br />";
            return fetchResult($result);
        } else {
            echo "returning without RS.<br />";
            return $result;
        }
    } catch (Exception $e) {
        echo 'Caught exception: ',  $e->getMessage(), "\n";
    }    
}
function fetchResult($result, $fetchType = SQLSRV_FETCH_BOTH) {
    return sqlsrv_fetch_array($result, $fetchType);
}
function getEnconding() {
    
}

function utf8_encode2($str) {
    if (preg_match('!!u', $str))
    {
       return $str;
    }
    else 
    {
       return utf8_encode2($aux);       
    }
}

function test() {
    getEnconding();
    echo "<br />IS_TEST: " . $_ENV['IS_TEST'];
    echo "<br />getenv IS_TEST: " . getenv('IS_TEST');
    
    echo "<br />_ENV: " . print_r($_ENV,true);
    echo "<br />_SERVER: " . print_r($_SERVER, true);
    $mainConnection = null;
    if ($_REQUEST["type"] == "dev") {
        $mainConnection = devConnection();
    }
    else {
        $mainConnection = prodConnection();
    }

    if( $mainConnection ) {
        echo "<br />Connection established.";
    }else{
            echo "<br />Connection could not be established.";
            die( FormatErrors( sqlsrv_errors()));
    }

    echo "<br />Adding query.";
    $query = "select ds_programa from [dbo].[mw_programa] where id_programa=390";

    echo "<br />executeSQL.";
	$rs = executeSQL($mainConnection, $query, array(), true);
    echo "<br />executed.";
    $aux = $rs["ds_programa"];
    echo "<br />utf8_encode ds_programa: " . utf8_encode2($aux);
    echo "<br />normal ds_programa: " . $aux;
    die("<br />end.");
}

test();


?>
</body>
</html>