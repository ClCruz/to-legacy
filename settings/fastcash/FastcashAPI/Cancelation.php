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
	*   Cancelation class implements the Transaction Cancelation function. Its a Receiver component.
	*/
	class Cancelation extends BaseReceiver 
	{   
		/**
		*   The source of the cancelation: 0 for the User, 1 for the Fastcash.
		*/
		private $Source;

		/**
		*  The reason of the cancelation, if available.
		*/
		private $Reason;

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

			if ($this->Function == "transaction-cancelation")
			{
				$this->Source = $this->GetParameter("source");
				$this->Reason = $this->GetParameter("reason");

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
			if ($this->Source == null || !is_numeric($this->Source) || $this->Source < 0)
			{
				$this->RaiseError("Source is invalid", "source");

				return false;
			}
			else
			{
				return true;
			}
		}

		/**
		*   Builds the integrity signature of the parameters.
		*   @see Security::IntegrityHash
		*/
		protected function BuildSignature()
		{
			return Security::IntegrityHash(IntegrationData::CancelationKey, $this->Prodid, $this->Tid, $this->Custom, IntegrationData::Pid, $this->Source);
		}

		/**
		*   Invokes the callback function and process the result using BaseReceiver::ProcessResult.
		*   @see BaseComponent::SetCallback
		*/
		protected function InvokeCallback()
		{
			if (!empty($this->CallbackFn))
			{
				$result = call_user_func($this->CallbackFn, $this, $this->Tid, $this->Custom, $this->Source, $this->Reason);

				$this->ProcessResult($result);
			}
			else
			{
				$this->RaiseError("Cancelation callback not implemented!");
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