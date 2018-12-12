<?php
require_once('../log4php/log.php');

class PagarMe_Recipient extends PagarMe_Model {

	const ENDPOINT_RECIPIENTS = '/recipients';

	public static function findAllByRecipientId($recipientId)
	{
		$request = new PagarMe_Request(
            self::ENDPOINT_RECIPIENTS . '/' . $recipientId . '/balance/operations', 'GET'
        );

        $response = $request->run();
        $class = get_called_class();
        return new $class($response);
    }


    public static function findRecebedor($recipientId)
	{
		$request = new PagarMe_Request(
            self::ENDPOINT_RECIPIENTS . '/' . $recipientId, 'GET'
        );

        $response = $request->run();
        $class = get_called_class();
        return new $class($response);
    }

    public static function findBankAccount($bank_account_id)
	{
		$request = new PagarMe_Request(
             '/bank_accounts/' . $bank_account_id, 'GET'
        );

        $response = $request->run();
        $class = get_called_class();
        return new $class($response);
    }
    

    public static function getOperationHistory($recipientId, $status, $count, $start_date, $end_date)
	{
        //status: waiting_funds
        //status: available
        //status: transferred

        if ($status == "") {
            $status = "waiting_funds";
        }

        //error_log("start_date_timestamp " . $start_date_timestamp);
        //error_log("end_date_timestamp " . $end_date_timestamp);

		$request = new PagarMe_Request(
            '/balance/operations', 'GET'
        );
        $params = array("recipient_id"=> $recipientId
        ,"status" => $status
        ,"count"=> 1000
        ,"start_date" => $start_date
        ,"end_date" => $end_date
        );

        // error_log("params " . print_r($params, true));

        $response = $request->runWithParameter($params);
        $class = get_called_class();
        return new $class($response);
    }

    public static function getListPayables($recipientId, $status, $count, $page)
	{
        //status: paid
        //status: waiting_funds

        if ($status == "" || $status == null) {
            $status = "waiting_funds";
        }

        if ($count == "" || $count == null) {
            $count = 10000;
        }

        if ($page == "" || $page == null) {
            $page = 1;
        }

        //error_log("start_date_timestamp " . $start_date_timestamp);
        //error_log("end_date_timestamp " . $end_date_timestamp);

		$request = new PagarMe_Request(
            '/payables', 'GET'
        );
        $params = array("recipient_id"=> $recipientId
        ,"status" => $status
        ,"count"=> $count
        ,"page" => $page);

        // error_log("params " . print_r($params, true));

        $response = $request->runWithParameter($params);
        $class = get_called_class();
        return new $class($response);
    }
    
    

	public static function findSaldoByRecipientId($recipientId)
	{
        log_trace("Call of findSaldoByRecipientId. recipient_id: " . $recipientId);

        //ticketspay
        if ($recipientId == "re_cjfac43dq039c445ymjsxw9x4") {
            $request = new PagarMe_Request(
                '/balance', 'GET'
            );
            log_trace("findSaldoByRecipientId 1. - request: " . print_r($request,true));
            $params = array("recipient_id"=> $recipientId);

            $response = $request->runWithParameter($params);
            $class = get_called_class();
            log_trace("findSaldoByRecipientId 2. - response: " . print_r($response,true));
            return new $class($response);
        }
        else {
            $request = new PagarMe_Request(
                self::ENDPOINT_RECIPIENTS . '/' . $recipientId . '/balance', 'GET'
            );
            log_trace("findSaldoByRecipientId 1.1 - request: " . print_r($request,true));
            $response = $request->run();
            $class = get_called_class();
            log_trace("findSaldoByRecipientId 2.1 - response: " . print_r($response,true));
            return new $class($response);

        }
    }

    public static function getTransaction($transaction_id)
	{
		$request = new PagarMe_Request(
            '/transactions/' . $transaction_id, 'GET'
        );
        $params = array("transaction_id"=> $transaction_id);
        $response = $request->runWithParameter($params);
        $class = get_called_class();
        $transactions = new $class($response);

        $request2 = new PagarMe_Request(
            '/transactions/' . $transaction_id . '/payables', 'GET'
        );
        $response2 = $request2->runWithParameter($params);

        $class2 = get_called_class();
        $payables = new $class($response2);


        $obj->amount = $transactions->getAmount();
        $obj->card_holder_name = $transactions["card_holder_name"];
        
		try {
			$obj->customerName = $transactions["customer"]["name"];    
		}
		catch (Exception $e) {
			$obj->customerName = "";    
        }
        
        $split = array();

        try {
            foreach ($transactions["split_rules"] as $value) {
                $recipient_id = $value["recipient_id"];
                $percentage = $value["percentage"];
                $recebedor = PagarMe_Recipient::findRecebedor($recipient_id);
                $bank =PagarMe_Recipient::findBankAccount($recebedor["bank_account"]["id"]);
                $name = $bank["legal_name"];
                $documentNumber = $bank["document_number"];
                $documentType = $bank["document_type"];

                foreach ($payables as $value2) {
                    $recipient_idPlayable = $value2["recipient_id"];
                    $amount = $value2["amount"];
                    $fee = $value2["fee"];
                    $anticipation_fee = $value2["anticipation_fee"];

                    if ($recipient_id == $recipient_idPlayable) {
                        array_push($split, array(
                            "recipient_id" => $recipient_id,
                            "amount" => $amount,
                            "fee" => $fee,
                            "anticipation_fee" => anticipation_fee,
                            "name" => $name,
                            "documentNumber" => $documentNumber,
                            "documentType" => $documentType
                        ));
                    }                    
                }
            }
            $obj->split = $split;
        }
        catch (Exception $e) {
            error_log('erro do split.');
        }
    

       

        $ret = json_encode($obj);

        return $ret;
    }

    public static function getPayables($recipientId)
	{
		$request = new PagarMe_Request(
            '/payables', 'GET'
        );

        $params = array("recipient_id"=> $recipientId
        ,"status" => "waiting_funds"
        ,"count" => 9999
        );

        $response = $request->runWithParameter($params);

        $class = get_called_class();
        return new $class($response);
    }
    
    public static function getLimits($recipientId, $payment_date, $timeframe)
	{
		$request = new PagarMe_Request(
            self::ENDPOINT_RECIPIENTS . '/' . $recipientId . '/bulk_anticipations/limits', 'GET'
        );

        $params = array("payment_date"=> $payment_date
        ,"timeframe" => $timeframe
        );

        $response = $request->runWithParameter($params);

        $class = get_called_class();

        $limits = new $class($response);

        $payables = PagarMe_Recipient::getPayables($recipient_id);

        $maximum = array("amount"=> $limits["maximum"]["amount"]
        ,"anticipation_fee"=> $limits["maximum"]["anticipation_fee"]
        ,"fee"=> $limits["maximum"]["fee"]);

        $minimum = array("amount"=> $limits["minimum"]["amount"]
        ,"anticipation_fee"=> $limits["minimum"]["anticipation_fee"]
        ,"fee"=> $limits["minimum"]["fee"]);

        $play = array();

        foreach ($payables as $value) {
            if ($recipientId==$value["recipient_id"]) {
                array_push($play, array(
                    "id" => $value["id"],
                    "amount" => $value["amount"],
                    "status" => $value["status"],
                    "fee" => $value["fee"],
                    "anticipation_fee" => $value["anticipation_fee"],
                    "transaction_id" => $value["transaction_id"],
                    "recipient_id" => $value["recipient_id"]
                ));
            }
        }

        $obj = array("maximum"=> $maximum
        ,"minimum" => $minimum
        ,"payables" => $play
        );

        $ret = json_encode($obj);

        return $ret;
    }
    public static function getResumo($recipientId, $amount, $payment_date, $timeframe)
	{
		$request = new PagarMe_Request(
            self::ENDPOINT_RECIPIENTS . '/' . $recipientId . '/bulk_anticipations', 'POST'
        );
     
        $params = array("payment_date"=> $payment_date
        ,"timeframe" => $timeframe
        ,"requested_amount" => $amount
        ,"build" => true
        );
        $response = $request->runWithParameter($params);
        $class = get_called_class();
        $ret = new $class($response);
        $id = $ret->getId();
        $request2 = new PagarMe_Request(
           self::ENDPOINT_RECIPIENTS . '/' . $recipientId . '/bulk_anticipations/' . $id, 'DELETE'
        );
        $params2 = array("build" => true);
        $response2 = $request2->runWithParameter($params2);

        return $ret;
	}

}
