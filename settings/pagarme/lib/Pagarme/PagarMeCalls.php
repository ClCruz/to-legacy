<?php

class PagarMe_Calls extends PagarMe_Model {
    public static function getCompany()
	{
		$request = new PagarMe_Request(
            '/company', 'GET'
        );
        $params = array();

        $response = $request->runWithParameter($params);
        $class = get_called_class();
        return new $class($response);
    }
    public static function listTransfers($recipient_id)
	{
		$request = new PagarMe_Request(
            '/transfers', 'GET'
        );
        $params = array("recipient_id"=> $recipient_id, "count"=>99999, "page"=>1);

        $response = $request->runWithParameter($params);
        $class = get_called_class();
        return new $class($response);
    }
    public static function listAnticipations($recipient_id)
	{
		$request = new PagarMe_Request(
            '/recipients/'.$recipient_id.'/bulk_anticipations/', 'GET'
        );
        $params = array("count"=>99999, "page"=>1);

        $response = $request->runWithParameter($params);
        $class = get_called_class();
        return new $class($response);
    }
}
?>