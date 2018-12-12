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
	*   BaseReceiver is an abstraction class for the Receiver components.
	*   @example Fastcash/index.php
	*/
	abstract class BaseReceiver extends BaseComponent
	{
		/**
		*   The API function that the class is implementing.
		*/
		protected $Function;

		/**
		*   The transaction identifier.
		*/
		protected $Tid;

		/**
		*   The custom field sent optionally by you at the DynamicTransaction call.
		*/
		protected $Custom;

		/**
		*   The Fastcash`s Product id.
		*/
		protected $Prodid;

		/**
		*   Your identifier with Fastcash.
		*/
		protected $Pid;

		/**
		*   The integrity signature hash.
		*/
		protected $Dsignature;

		/**
		*   Constructor. Calls BaseComponent constructor.
		*/
		function __construct() {
			parent::__construct();
		}

		/**
		*   Main function that reads the Http parameters and process the request.
		*/
		abstract public function Listen();

		/**
		*   Responds the Request to Fastcash.
		*/
		abstract protected function Respond($success, $errorMsg = null);

		/**
		*   Builds the integrity signature of the parameters.
		*/
		abstract protected function BuildSignature();
		
		/**
		*   Receive the parameters, getting their values with GetParameter() function and setting the class fields.
		*/
		protected function ReceiveParameters()
		{
			$this->Function = strtolower($_REQUEST["function"]);
			$this->Tid = $this->GetParameter("tid");
			$this->Custom = $this->GetParameter("custom");
			$this->Prodid = $this->GetParameter("prodid");
			$this->Pid = $this->GetParameter("pid");
			$this->Dsignature = $this->GetParameter("dsignature");
		}

		/**
		*   Validates all required and basic parameters, calling RaiseError if theres an error. Else, calls Validate()
		*   @returns true or false
		*/
		protected function ValidateParameters()
		{
			if ($this->Tid == null || trim($this->Tid) == "")
			{
				$this->RaiseError("TID is invalid", "tid");
			}
			else if ($this->Pid == null || !is_numeric($this->Pid))
			{
				$this->RaiseError("Pid is invalid", "pid");
			}
			else if($this->Pid != IntegrationData::Pid)
			{
				$this->RaiseError("Pid is incorrect", "pid");
			}
			else if($this->Dsignature == null || strlen(trim($this->Dsignature)) != 32)
			{
				$this->RaiseError("Signature is invalid", "dsignature");
			}
			if (!$this->ValidateSignature())
			{
				$this->RaiseError("Signature is invalid", "dsignature");

				return false;
			}
			else
			{
				return $this->Validate();
			}

			return false;
		}

		/**
		*   Calls the BuildSignature() and validates the integrity hash signature. Uses the Security::CompareHash() function.
		*   @returns true or false
		*/
		protected function ValidateSignature()
		{
			$signature = $this->BuildSignature();

			return Security::CompareHash($this->Dsignature, $signature);
		}

		/**
		*   Process the result returned by the callback function. If the result is a valid boolean or an array(boolean, string), calls Respond(), else, throws an Exception.
		*   @see BaseComponent::SetCallback()
		*   @throws Exception If the callback result is not a boolean or an array(boolean, string).
		*/
		protected function ProcessResult($result)
		{
			if (is_array($result) && sizeof($result) >= 2)
			{
				$this->Respond($result[0], $result[1]);
			}
			else if (is_bool($result))
			{
				$this->Respond($result);
			}
			else if ($result == 0 || $result == 1)
			{
				$this->Respond(($result == 1 ? true : false));
			}
			else
			{
				throw new Exception("Callback result must be a Boolean Or an array with 2 elements, a bool(Cv) and a string(Error message).");
			}
		}
	}
}