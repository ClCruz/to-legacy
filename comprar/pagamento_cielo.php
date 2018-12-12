<?php
session_start();
require_once('../settings/functions.php');
require_once('../settings/settings.php');

if ($is_manutencao === true) {
	header("Location: manutencao.php");
	die();
}

require('acessoLogado.php');

require_once('../settings/cielo_functions.php');

$mainConnection = mainConnection();

$campanha = get_campanha_etapa(basename(__FILE__, '.php'));

$json = json_encode(array('descricao' => '6.1 pagamento cielo - dados recebidos', 'post' => $_POST, 'get' => $_GET));
include('logiPagareChamada.php');

$response = consultarPedidoCielo($_POST['PaymentId']);

$json = json_encode(array('descricao' => '6.2 pagamento cielo - resultado da consulta', 'response' => $response));
include('logiPagareChamada.php');

$_GET['pedido'] = $response['transaction']['MerchantOrderId'];

$json = json_encode(array('descricao' => '6.3 pagamento cielo - tela final do pedido '.$_GET['pedido']));
include('logiPagareChamada.php');

$query = "SELECT PP.CD_STATUS
            FROM MW_PEDIDO_VENDA P
            INNER JOIN MW_CLIENTE C ON C.ID_CLIENTE = P.ID_CLIENTE
            INNER JOIN MW_PEDIDO_PAGSEGURO PP ON PP.ID_PEDIDO_VENDA = P.ID_PEDIDO_VENDA
            WHERE P.ID_PEDIDO_VENDA = ? AND P.ID_CLIENTE = ?
            ORDER BY PP.DT_STATUS DESC";
$params = array($_GET['pedido'], $_SESSION['user']);
$rs = executeSQL($mainConnection, $query, $params, true);

// se nao encontrar nenhum registro pode ser usuario tentando acessar
// um pedido de outro usuario ou meio de pagamento que nao bate com o selecionado
if (empty($rs)) {
    header("Location: ".$homeSite);
    die();
} else {

	if ($response['transaction']['Payment']['Status'] != $rs['CD_STATUS']) {
		$query = 'INSERT INTO MW_PEDIDO_PAGSEGURO (ID_PEDIDO_VENDA, DT_STATUS, CD_STATUS, OBJ_PAGSEGURO) VALUES (?, GETDATE(), ?, ?)';
		$params = array($_GET['pedido'], $response['transaction']['Payment']['Status'], base64_encode(serialize($response['transaction'])));
		executeSQL($mainConnection, $query, $params);
	}

	if ($response['transaction']['Payment']['Status'] != '2') {
		header("Location: pagamento_cancelado.php?manualmente=1&cielo=1");
        die();
	}

	$query = "SELECT M.CD_MEIO_PAGAMENTO, P.IN_SITUACAO
                FROM MW_PEDIDO_VENDA P
                INNER JOIN MW_MEIO_PAGAMENTO M ON M.ID_MEIO_PAGAMENTO = P.ID_MEIO_PAGAMENTO
                WHERE ID_PEDIDO_VENDA = ?";
    $params = array($_GET['pedido']);
    $rs = executeSQL($mainConnection, $query, $params, true);
    
    // se o pedido ja esta finalizado enviar para o pedido na minha_conta
    if ($rs['IN_SITUACAO'] == 'F') {
    	header("Location: minha_conta.php?pedido={$_GET['pedido']}");
    	die();
    }

    $parametros['OrderData']['OrderId'] = $_GET['pedido'];

    $result = new stdClass();
    $result->AuthorizeTransactionResult->OrderData->BraspagOrderId = 'Cielo';
    $result->AuthorizeTransactionResult->PaymentDataCollection->PaymentDataResponse->BraspagTransactionId = $response['transaction']['Payment']['PaymentId'];
    $result->AuthorizeTransactionResult->PaymentDataCollection->PaymentDataResponse->AcquirerTransactionId = $response['transaction']['Payment']['ProofOfSale'];
    $result->AuthorizeTransactionResult->PaymentDataCollection->PaymentDataResponse->AuthorizationCode = $response['transaction']['Payment']['AuthorizationCode'];
    $result->AuthorizeTransactionResult->PaymentDataCollection->PaymentDataResponse->PaymentMethod = $rs['CD_MEIO_PAGAMENTO'];

    ob_start();
    require_once "concretizarCompra.php";
    // se necessario, replica os dados de assinatura e imprime url de redirecionamento
    require "concretizarAssinatura.php";
    $return = ob_get_clean();

	header("Location: pagamento_ok.php?pedido={$_GET['pedido']}");
	die();
}