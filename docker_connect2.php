<html>
<head>
</head>
<body>

<?php
function defaultInstance() {
	$host = '192.168.91.14';
	$port = '1433';
	$dbname = 'Teste';
	$user = 'dev';
	$pass = 'dev';
	
	return sqlsrv_connect($host.','.$port, array("UID" => $user, "PWD" => $pass, "Database" => $dbname));
}
function NamedInstance() {
	$host = '192.168.91.14\SQL2';
	$port = '1434';
	$dbname = 'Teste';
	$user = 'dev';
	$pass = 'dev';
	
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
    $defaultConnection = defaultInstance();
    $namedConnection = NamedInstance();

    if( $defaultConnection ) {
        echo "<br />defaultConnection Connection established.";
    }else{
            echo "<br />defaultConnection Connection could not be established.";
            echo "<br /><br />";
            echo FormatErrors( sqlsrv_errors());
    }
    if( $namedConnection ) {
        echo "<br />namedConnection Connection established.";
    }else{
            echo "<br />namedConnection Connection could not be established.";
            echo "<br /><br />";
            echo FormatErrors( sqlsrv_errors());
    }

    echo "<br />Adding query.";
    //$query = "select ds_programa from [dbo].[mw_programa] where id_programa=390";
    $query = "select [name] from dbo.tabela";

    echo "<br />executeSQL default.";
	$rs = executeSQL($defaultConnection, $query, array(), true);
    echo "<br />executed.";
    $aux1 = $rs["name"];

    echo "<br />executeSQL named.";
	$rs = executeSQL($namedConnection, $query, array(), true);
    echo "<br />executed.";
    $aux2 = $rs["name"];

    echo "<br />executed $aux1 and $aux2";
    die("<br />end.");
}

test();


?>
</body>
</html>