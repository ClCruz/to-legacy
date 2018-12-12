<?php
 /**
  * @author Fastcash <cash@fastcash.com.br>
  * @copyright 2014 Fastcash
  * @license MIT
  */
  /*
  * DO NOT MODIFY THIS CLASS. 
  * This class may be updated in the future by us.
  */
namespace Fastcash
{
	use Guzzle\Http\Client;
	use Guzzle\Http\Exception\ServerErrorResponseException;
	use Guzzle\Http\Exception\ClientErrorResponseException;
	
	class TransactionClient extends BaseClient
	{
		public $Error;
		public $ErrorBody;
		
		function __construct() {
			parent::__construct();
		}
		
		public function Send($transactionRequest)
		{
			if ($transactionRequest instanceOf TransactionRequest)
			{
				try
				{
					$request = $this->Client->post("Transaction", null, json_encode($transactionRequest));
					$request->setHeader("Content-type", "text/json");
					$json = $request->send()->json();
					
					$response = new TransactionResponse($json);
					
					$this->Error = null;
					$this->ErrorBody = null;
					
					return $response;
				}
				catch(ServerErrorResponseException $e)
				{
					$this->Error = $e->getMessage();
				}
				catch(ClientErrorResponseException $ce)
				{
					$this->Error = $ce->getMessage();
					$this->ErrorBody = $ce->getResponse()->getBody();
				}
			}
			
			return null;
		}
		
		public function Consult($tid, $pid)
		{
			try
			{
				$request = $this->Client->get("Transaction/?tid=".$tid."&pid=".$pid);
				$response = $request->send();
				$statusCode = $response->getStatusCode();
				
				if ( $statusCode == 200 || $statusCode == 302)
				{
					$json = $response->json();
					$tran = new TransactionResponse($json);
					
					$this->Error = null;
					$this->ErrorBody = null;
					
					return $tran;
				}
				else
				{
					$this->Error = $response->getReasonPhrase();
					$this->ErrorBody = $response->getBody();
				}
			}
			catch(ServerErrorResponseException $e)
			{
				$this->Error = $e->getMessage();
			}
			catch(ClientErrorResponseException $ce)
			{
				$this->Error = $ce->getMessage();
				$this->ErrorBody = $ce->getResponse()->getBody();
			}
			
			return null;
		}
	}
}
?>