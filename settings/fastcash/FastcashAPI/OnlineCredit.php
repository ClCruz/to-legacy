<?php 
 /**
  * @author Fastcash <cash@fastcash.com.br>
  * @copyright 2012 Fastcash
  * @license MIT
  */
  /*
  * DO NOT MODIFY THIS CLASS. 
  * This class may be updated in the future by us.
  */
namespace Fastcash
{
	/**
	*   OnlineCredit class implements the Online Credit function. Its a Receiver component.
	*/
	class OnlineCredit extends BaseReceiver 
	{   
		/**
		*   The Quantity parameter.
		*/
		private $Quant;

		/**
		*   The value that we received as payment for the transaction.
		*/
		private $ValueReceived;

		/**
		*   Constructor. Calls BaseReceiver constructor.
		*/
		function __construct() {
			parent::__construct();
		}

		/**
		*   Main function that reads the Http parameters and process the request.
		*/
		public function Listen()
		{
			$this->ReceiveParameters();

			if ($this->Function == "credit")
			{
				$this->Quant = $this->GetParameter("quant");
				$this->ValueReceived = $this->GetParameter("valuereceived");

				if ($this->ValidateParameters() === true)
				{
					$this->InvokeCallback();
				}
				else
				{
					$this->Respond(false, $this->GetLastError());
				}
			}
		}

		/**
		*   Validate function. Validate all component input parameters
		*   @returns True or False
		*/
		protected function Validate()
		{
			if ($this->Prodid == null || !is_numeric($this->Prodid) || $this->Prodid <= 0)
			{
				$this->RaiseError("ProdId is invalid", "prodid");

				return false;
			}
			else if ($this->Quant == null || !is_numeric($this->Quant) || $this->Quant <= 0)
			{
				$this->RaiseError("Quant is invalid", "quant");

				return false;
			}
			else if ($this->ValueReceived == null || !is_numeric($this->ValueReceived) || $this->ValueReceived <= 0)
			{
				$this->RaiseError("ValueReceived is invalid", "valuereceived");

				return false;
			}

			return true;
		}

		/**
		*   Builds the integrity signature of the parameters.
		*   @see Security::IntegrityHash
		*/
		protected function BuildSignature()
		{
			return Security::IntegrityHash($this->Tid, IntegrationData::OnlineCreditKey, $this->Quant, $this->ValueReceived, $this->Prodid, IntegrationData::Pid);
		}

		/**
		*   Invokes the callback function and process the result using BaseReceiver::ProcessResult.
		*   @see BaseComponent::SetCallback
		*/
		protected function InvokeCallback()
		{
			if (!empty($this->CallbackFn))
			{
				$result = call_user_func($this->CallbackFn, $this, $this->Tid, $this->Prodid, $this->Quant, $this->ValueReceived, $this->Custom);

				$this->ProcessResult($result);
			}
			else
			{
				$this->RaiseError("OnlineCredit callback not implemented!");
				$this->Respond(false, $this->GetLastError());
			}
		}

		/**
		*   Responds the Request to Fastcash, echoing the response Xml.
		*/
		protected function Respond($success, $errorMsg = null)
		{
			$pid = IntegrationData::Pid;
			$cv = ($success == true ? 1 : 0);
			$signature = $this->BuildSignature();
			$msg = ($errorMsg == null ? "" : $errorMsg);

			$xml = "<xml>
						<tid>$this->Tid</tid>
						<publisher_id>$pid</publisher_id>
						<cv>$cv</cv>
						<dsignature>$signature</dsignature>
						<err>$msg</err>
					</xml>";

			echo $xml;
		}
	} 
}
?>