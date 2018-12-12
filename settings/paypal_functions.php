<?php
require_once('../settings/functions.php');

require_once('../settings/settings.php');

// require_once('../settings/PayPal-PHP-SDK/autoload.php');
//     if ($_ENV['IS_TEST']) {
//         $apiContext = new \PayPal\Rest\ApiContext(
//         new \PayPal\Auth\OAuthTokenCredential(
//             'AQ8hnNgMxLFzukyzkMMwfMFAkmHTBxv6uAuZ95rZLOOHdW6bAx7MyeMpGpVIzBN2DoIighIYNIBke1qO',     // ClientID
//             'EJ2Ijv3Tnef3m5r2MN7QY7bnAkj59Uq_M2xOf5zw4s5rvH2EluS-x7qAA56j--ApK3wKZUxhyfvx2bEA'      // ClientSecret
//         )
//     }
//     else {
//         $apiContext = new \PayPal\Rest\ApiContext(
//             new \PayPal\Auth\OAuthTokenCredential(
//                 'AagFfpGw_irk48l196ERKmqntzzTw8kDmf2glId43tuRENMx0-DIqUMq_kgZewGos3-8WjmoeLKXYvIP',     // ClientID
//                 'EIEFcgd_5jb9Wpyo24B4C50OFd3o8DvEtIW2o2m5nLDwkl_h6yM8BETKcbE1Q4T3JO8-lhe_xoYE2lNI'      // ClientSecret
//             )
//     }
    


	function getObjFromString($json) {
        return json_decode($json, true);
    }
    function getObjToSave($id_pedido_venda, $json_data, $json_payment) {
        $paypal_data = getObjFromString($json_data);
        $paypal_payment = getObjFromString($json_payment);

        $toReturn = array(
            "id_pedido_venda"=>$id_pedido_venda,
            "paymentToken"=>$paypal_data["paymentToken"],
            "orderID"=>$paypal_data["orderID"],
            "payerID"=>$paypal_data["payerID"],
            "paymentID"=>$paypal_data["paymentID"],
            "dataJSON"=>utf8_encode2($json_data),
            "paymentJSON"=>utf8_encode2($json_payment),
            "state"=>$paypal_payment["state"],
            "cart"=>$paypal_payment["cart"],
            "amount"=>$paypal_payment["transactions"][0]["amount"]["total"] ,
            "saleId"=>$paypal_payment["transactions"][0]["related_resources"][0]["sale"]["id"]         
        );
		
		return $toReturn;
    }

    function paypalRefund($id_pedido_venda) {
        // error_log("paypalRefund - id_pedido_venda: " . $id_pedido_venda);
        // $mainConnection = mainConnection();

        // $query = "
        // SELECT id_pedido_venda, saleId
        // FROM mw_gateway_paypal
        // WHERE id_pedido_venda=?";
        // $result = executeSQL($mainConnection, $query, array($_POST['pedido']));

        // $query = "SELECT id_pedido_venda, saleId
        // FROM mw_gateway_paypal
        // WHERE id_pedido_venda=?";
        // $params = array($id_pedido_venda);
        // $rs = executeSQL($mainConnection, $query, $params, true);

        // $saleId = $rs['saleId'];

        // use PayPal\Api\Capture;
        // use PayPal\Api\Refund;
        // use PayPal\Api\RefundRequest;

        // $captureId = "<your authorization id here>";



        // try {
        // Create a new apiContext object so we send a new PayPal-Request-Id (idempotency) header for this resource
        
        //     $apiContext = getApiContext($clientId, $clientSecret);
        // Retrieve Capture details
        //     $capture = Capture::get($captureId, $apiContext);
        // Refund the Capture
        //     $captureRefund = $capture->refundCapturedPayment($refundRequest, $apiContext);
        
        // } catch (Exception $ex) {
        //     error_log("Refund Capture Error: " . print_r)
        
        //     ResultPrinter::printError("Refund Capture", "Capture", null, $refundRequest, $ex);
        //     exit(1);
        // }

        // error_log("paypalRefund - saleId: " . $saleId);
    }

    function paypal_saveTo($obj) {
		$mainConnection = mainConnection();
        try {
            $query = "INSERT INTO [mw_gateway_paypal]
                    ([id_pedido_venda]
                    ,[dt_criacao]
                    ,[paymentToken]
                    ,[orderID]
                    ,[payerID]
                    ,[paymentID]
                    ,[dataJSON]
                    ,[paymentJSON]
                    ,[state]
                    ,[cart]
                    ,[saleId])
                VALUES
                    (?
                    ,GETDATE()
                    ,?
                    ,?
                    ,?
                    ,?
                    ,?
                    ,?
                    ,?
                    ,?
                    ,?)";

            $params = array($obj["id_pedido_venda"]
            ,$obj["paymentToken"]
            ,$obj["orderID"]
            ,$obj["payerID"]
            ,$obj["paymentID"]
            ,$obj["dataJSON"]
            ,$obj["paymentJSON"]
            ,$obj["state"]
            ,$obj["cart"]
            ,$obj["saleId"]);

            // error_log("query. " . $query);
            // error_log("params " . print_r($params, true));

            $result = executeSQL($mainConnection, $query, $params);

            // $sqlErrors = sqlErrors();
            // if ($errors and empty($sqlErrors)) {
 
            // } else {
            //     error_log("erro: ".print_r($sqlErrors, true));
            // }
        } catch (SoapFault $e) {
            error_log("paypal_functions .2 - error in paypal_saveTo: " . $e->getMessage());
        } catch (Exception $e) {
            error_log("paypal_functions .3 - error in paypal_saveTo: " . $e->getMessage());
        }
    }

?>