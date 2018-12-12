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
    *   BaseComponent class is an abstraction for common methods, used by the Receiver
    *   and Sender components.
    */
    abstract class BaseComponent
    {
        /**
        *   The callback function.
        */
        protected $CallbackFn;

        /**
        *   The error callback function.
        */
        protected $ErrorCallbackFn;

        /**
        *   The last error message.
        */
        protected $LastError;

        /**
        *   Constructor. Calls the SetAPIHeader() when created.
        */
        function __construct() {
            $this->SetAPIHeader();
        }

        /**
        *   Validate function. Validate all component input parameters
        *   @returns True or False
        */
        abstract protected function Validate();

        /**
        *   Invokes the callback function.
        */
        abstract protected function InvokeCallback();

        /**
        *   Sets the specified function as the class callback.
        *   @param $callbackFn A function or method to invoke when the component completes the validation of the input parameters.
        */
        public function SetCallback($callbackFn)
        {
            $this->CallbackFn = $callbackFn;
        }

        /**
        *   Sets the specified function as the class error callback.
        *   @param $errorCallbackFn A function or method to invoke when the component raises an error.
        */
        public function SetErrorCallback($errorCallbackFn)
        {
            $this->ErrorCallbackFn = $errorCallbackFn;
        }

        /**
        *   Raises an error.
        *   @param $errorMsg The error message.
        *   @param $field The component field that raised the error, if available.
        */
        protected function RaiseError($errorMsg, $field = null)
        {
            $this->LastError = $errorMsg;

            if (!empty($this->ErrorCallbackFn))
            {
                call_user_func($this->ErrorCallbackFn, $this, get_called_class(), $field, $errorMsg);
            }
        }

        /**
        *   Gets the specified parameter from the $_REQUEST collection.
        *   @param $paramName The parameter name.
        */
        protected function GetParameter($paramName)
        {
            if (isset($_REQUEST[$paramName]))
            {
                return $_REQUEST[$paramName];
            }
            else
            {
                return null;
            }
        }

        /**
        *   Gets the last error raised by the component.
        *   @returns The last error message.
        */
        public function GetLastError()
        {
            return $this->LastError;
        }

        /**
        *   Sets the specified message as the last error, without trigger any callback.
        *   @param $errorMsg The error message.
        */
        public function SetError($errorMsg)
        {
            $this->LastError = $errorMsg;
        }

        /**
        *   Clears the last error message.
        */
        public function ClearError()
        {
            $this->LastError = null;
        }

        /**
        *   Sets the API Http Header, to inform the Fastcash server.
        */
        public function SetAPIHeader()
        {
            try
            {
                header("FastcashAPI: 4.0-php");
            }
            catch(Exception $e)
            {
            }
        }
    }
}
?>