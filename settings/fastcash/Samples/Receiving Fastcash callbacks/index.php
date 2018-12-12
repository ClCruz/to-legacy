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

require_once "../Fastcash.php";

$function = null;
$handler = null;

if (!Fastcash\Security::VerifyIP($_SERVER["REMOTE_ADDR"]))
{
    die($_SERVER["REMOTE_ADDR"]);
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
    //TODO: Implement your logic for the OnlineCredit function:
    //Validate the parameters, and trigger your function that deliver the product to the client.

    //Return true/false or an array(false, "Error message")
    return true;
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

    //Return true/false or an array(false, "Error message")
    return true;
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

    //Return true/false or an array(false, "Error message")
    return true;
}
?>