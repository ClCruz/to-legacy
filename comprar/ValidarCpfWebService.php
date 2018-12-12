<?php

//require_once ('../settings/nusoap-0.9.5/lib/nusoap.php');

$x = new t2();

class t2{
    function __construct(){

        $cliente = new SoapClient( 'http://www.nucleosernatural.com.br/wg/soap/nu/server.php?wsdl', array() );

        $rs = $cliente->__soapCall('hello', array('teste'));

        if ( is_soap_fault($rs) )
        {
            echo 'Soap Fault';
        }
        else
        {
            print_r($rs);
        }
    }
}