<?php
/*
 * This file is a Sample use of the Receiver components.
 * Includes all the necessary functionality to correctly use the components.
 *
 * If you choose to use this file, just complete the On****Received functions at the bottom of this file
 * to call your business class or logic, according to the function, or, replace the SetCallback function
 * with a call to your own class/function.
 *
 * If you decide to use another file, don't forget to set the header, require the Fastcash.php,
 * verify the IP address of caller, instantiate the receiver components class as described
 * and set your callback function.
*/

header("Content-Type: text/xml");
header("Cache-Control: no-cache, must-revalidate, proxy-revalidate");

require_once "../settings/fastcash/Fastcash.php";




require_once "../settings/functions.php";

$mainConnection = mainConnection();

executeSQL($mainConnection, "insert into mw_log_ipagare values (getdate(), ?, ?)",
    array(-888, json_encode(array('descricao' => '6. retorno fastcash', 'post' => $_REQUEST)))
);




$function = null;
$handler = null;

if (!Fastcash\Security::VerifyIP($_SERVER["HTTP_X_FORWARDED_FOR"]))
{
    // die($_SERVER["HTTP_X_FORWARDED_FOR"]);
}

if (isset($_REQUEST["function"]))
{
    $function = $_GET["function"];
}
else
{
    die();
}

switch($function)
{
    case "credit":
    {
        $handler = new Fastcash\OnlineCredit();
        $handler->SetCallback("OnOnlineCreditReceived");

        break;
    }
    case "credit-consult":
    {
        $handler = new Fastcash\CreditConsult();
        $handler->SetCallback("OnCreditConsultReceived");

        break;
    }
    case "transaction-cancelation":
    {
        $handler = new Fastcash\Cancelation();
        $handler->SetCallback("OnCancelationReceived");

        break;
    }
}

if ($handler != null)
{
    $handler->Listen();
}

/**
*   Callback function for the OnlineCredit component.
*   @param $sender The OnlineCredit class instance reference.
*   @param $tid Your transaction identifier, sent with the DynamicTransaction call.
*   @param $prodId The Fastcash product id used at the DynamicTransaction.
*   @param $quant The quant (quantity) parameter sent with the DynamicTransaction call.
*   @param $valueReceived The value that we received as payment for the transaction. Validate this parameter to double check that the price was not changed at the communication.
*   @param $custom The custom parameter, sent optionally with the DynamicTransaction call.
*/
function OnOnlineCreditReceived($sender, $tid, $prodId, $quant, $valueReceived, $custom)
{
    $mainConnection = mainConnection();

    $query = "SELECT M.CD_MEIO_PAGAMENTO, P.IN_SITUACAO, P.VL_TOTAL_PEDIDO_VENDA
                FROM MW_PEDIDO_VENDA P
                INNER JOIN MW_MEIO_PAGAMENTO M ON M.ID_MEIO_PAGAMENTO = P.ID_MEIO_PAGAMENTO
                WHERE ID_PEDIDO_VENDA = ?";
    $params = array($tid);
    $rs = executeSQL($mainConnection, $query, $params, true);
    $valueSaved = number_format($rs['VL_TOTAL_PEDIDO_VENDA'], 2, '.', '');
    
    if ($valueSaved != $valueReceived)
        return array(false, "O valor do pedido não confere com o valor recebido pela Fastcash.");

    // se o pedido ja esta finalizado retornar sucesso
    // (de acordo com a documentacao da fastcash eles podem
    // enviar chamadas duplicadas e a resposta deve ser a mesma)
    if ($rs['IN_SITUACAO'] == 'F') return true;
    elseif ($rs['IN_SITUACAO'] == 'E') return array(false, "Pedido expirado.");
    elseif ($rs['IN_SITUACAO'] == 'S') return array(false, "Pedido estornado.");
    elseif ($rs['IN_SITUACAO'] == 'C') return array(false, "Pedido cancelado.");

    $parametros['OrderData']['OrderId'] = $tid;
    $result->AuthorizeTransactionResult->OrderData->BraspagOrderId = 'Fastcash';
    $result->AuthorizeTransactionResult->PaymentDataCollection->PaymentDataResponse->BraspagTransactionId = 'Fastcash';
    $result->AuthorizeTransactionResult->PaymentDataCollection->PaymentDataResponse->AcquirerTransactionId = 'Fastcash';
    $result->AuthorizeTransactionResult->PaymentDataCollection->PaymentDataResponse->AuthorizationCode = 'Fastcash';
    $result->AuthorizeTransactionResult->PaymentDataCollection->PaymentDataResponse->PaymentMethod = $rs['CD_MEIO_PAGAMENTO'];

    ob_start();
    require_once "concretizarCompra.php";
    // se necessario, replica os dados de assinatura e imprime url de redirecionamento
    require "concretizarAssinatura.php";
    $return = ob_get_clean();

    $return = ($return == '' OR substr($return, 0, 12) == 'redirect.php' ? true : array(false, $return));

    //Return true/false or an array(false, "Error message")
    return $return;
}

/**
*   Callback function for the CreditConsult component.
*   @param $sender The CreditConsult class instance reference.
*   @param $tid Your transaction identifier, sent with the DynamicTransaction call.
*   @param $custom The custom parameter, sent optionally with the DynamicTransaction call.
*/
function OnCreditConsultReceived($sender, $tid, $custom)
{
    //TODO: Implement your logic for the CreditConsult function:
    //Check your system to verify if the realtime and most updated status of the $tid.
    //We call this function when needed to double check the delivery.

    $mainConnection = mainConnection();

    $query = "SELECT P.IN_SITUACAO FROM MW_PEDIDO_VENDA P WHERE ID_PEDIDO_VENDA = ?";
    $params = array($tid);
    $rs = executeSQL($mainConnection, $query, $params, true);

    if ($rs['IN_SITUACAO'] == 'F') $return = true;
    elseif ($rs['IN_SITUACAO'] == 'E') $return = array(false, 'O tempo limite para a compra foi excedido.');
    elseif ($rs['IN_SITUACAO'] == 'P') $return = array(false, 'O pagamento ainda não foi confirmado.');
    elseif ($rs['IN_SITUACAO'] == 'S') $return = array(false, 'O pedido foi estornado.');
    else $return = false;

    return $return;
}

/**
*   Callback function for the Cancelation component.
*   @param $sender The Cancelation class instance reference.
*   @param $tid Your transaction identifier, sent with the DynamicTransaction call.
*   @param $custom The custom parameter, sent optionally with the DynamicTransaction call.
*   @param $source The source of the cancelation. 0 for the User, 1 for Fastcash system.
*   @param $reason The reason of the cancelation, if available.
*/
function OnCancelationReceived($sender, $tid, $custom, $source, $reason)
{
    //TODO: Implement your logic for the Cancelation function:
    //Check to see if the $tid has now yet been approved by the OnlineCredit
    //If its still pending, cancel the $tid.
    //This function may be called more than once, so ensure that it will not cause any problems.

    $mainConnection = mainConnection();

    $query = "SELECT P.IN_SITUACAO FROM MW_PEDIDO_VENDA P WHERE ID_PEDIDO_VENDA = ?";
    $params = array($tid);
    $rs = executeSQL($mainConnection, $query, $params, true);

    if ($rs['IN_SITUACAO'] == 'E' or $rs['IN_SITUACAO'] == 'S') $return = true;
    elseif ($rs['IN_SITUACAO'] == 'F') $return = array(false, 'O pedido já foi aprovado.');
    elseif ($rs['IN_SITUACAO'] == 'P') {

        $query = "UPDATE MW_RESERVA SET DT_VALIDADE = GETDATE()-1 WHERE ID_PEDIDO_VENDA = ?";
        executeSQL($mainConnection, $query, array($tid));

        $query = "exec prc_limpa_reserva";
        executeSQL($mainConnection, $query);

        $return = true;
    }
    else $return = false;

    return $return;
}
?>