<?php

ini_set("soap.wsdl_cache_enabled","0");
require_once ('../settings/nusoap-0.9.5/lib/nusoap.php');

$x = new s1();

class s1{

    function __construct(){
        $server = new soap_server;

        $server->register('hello');

        function hello(){
            return 'Hello world';
        }

        $HTTP_RAW_POST_DATA = isset($HTTP_RAW_POST_DATA) ? $HTTP_RAW_POST_DATA : '';
        $server->service($HTTP_RAW_POST_DATA);
    }
}

class s2{
    function __construct(){
        $server = new SoapServer(null, array('http://homolog.compreingressos.com:8081/compreingressos2/comprar/soap'));

        function hello(){
            return 'Hello world';
        }

        $server->addFunction('hello');

        if ( $_SERVER['REQUEST_METHOD'] == 'POST' )
        {
            $server->handle();
        }
        else
        {
            $funcs = $server->getFunctions();
            foreach ($funcs as $f)
            {
                print($f.'<br>');
            }
        }
    }
}
