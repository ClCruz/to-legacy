<?php

require_once('../settings/functions.php');
require_once('../settings/settings.php');

session_start();
$parcelas = 1;

//RequestID
$ri = md5(time());
$ri = substr($ri, 0, 8) . '-' . substr($ri, 8, 4) . '-' . substr($ri, 12, 4) . '-' . substr($ri, 16, 4) . '-' . substr($ri, -12);

//Parâmetros obrigatórios.
$parametros = array();
$PaymentDataCollection = array();

$parametros['RequestId'] = $ri;
$parametros['Version'] = '1.0';

$parametros['OrderData']['MerchantId'] = 'AEDAFDE0-83A5-869F-214B-C8501B9C8697';

$parametros['OrderData']['OrderId'] = '';

if (isset($_COOKIE['id_braspag'])) {
    $parametros['OrderData']['BraspagOrderId'] = $_COOKIE['id_braspag'];
}

//Dados cliente
$parametros['CustomerData']['CustomerIdentity'] = "999999999999";
$parametros['CustomerData']['CustomerName'] = "Teste";
$parametros['CustomerData']['CustomerEmail'] = "teste@gmail.com";

//Dados do cartão
$PaymentDataCollection['CardHolder'] = "Comprador Teste";
$PaymentDataCollection['PaymentMethod'] = 997;
$PaymentDataCollection['CardNumber'] = "0000000000000001";
$PaymentDataCollection['CardExpirationDate'] = '01/2018';
$PaymentDataCollection['CardSecurityCode'] = 111;
$PaymentDataCollection['Currency'] = 'BRL';
$PaymentDataCollection['Country'] = 'BRA';
$PaymentDataCollection['ServiceTaxAmount'] = 0;
$PaymentDataCollection['TransactionType'] = 2;
$PaymentDataCollection['NumberOfPayments'] = 1;
$PaymentDataCollection['PaymentPlan'] = 0;

//Dados do endereço de entrega.
$frete = 0;

$parametros['OrderData']['OrderId'] = 1;

$PaymentDataCollection['Amount'] = 100;

$parametros['PaymentDataCollection'] = array(new SoapVar($PaymentDataCollection, SOAP_ENC_ARRAY, 'CreditCardDataRequest', 'https://www.pagador.com.br/webservice/pagador', 'PaymentDataRequest'));

$options = array(
    //'local_cert' => file_get_contents('../settings/cert.pem'),
    //'passphrase' => file_get_contents('cert.key'),
    //'authentication' => SOAP_AUTHENTICATION_BASIC || SOAP_AUTHENTICATION_DIGEST
    'trace' => true,
    'exceptions' => true,
    'cache_wsdl' => WSDL_CACHE_NONE,
    'proxy_host' => '192.168.13.1',
    'proxy_port' => 8080
);

$descricao_erro = '';
$url_braspag = 'https://homologacao.pagador.com.br/webservice/pagadorTransaction.asmx?WSDL';

try {
    $client = @new SoapClient($url_braspag, $options);
    $result = $client->AuthorizeTransaction(array('request' => $parametros));
} catch (SoapFault $e) {
    $descricao_erro = $e->getMessage();
} catch (Exception $e) {
    var_dump($e);
}

echo "<pre>";
var_dump($client);
var_dump($result);
var_dump($descricao_erro);
echo "</pre>";
die('' . time());