<?php
 /**
  * @author Fastcash <cash@fastcash.com.br>
  * @copyright 2013 Fastcash
  * @license MIT
  */
  /*
  * DO NOT MODIFY THIS CLASS. 
  * This class may be updated in the future by us.
  */
namespace Fastcash
{
    class TransactionResponse
    {
        public $Transaction;
        public $Confirmation;
        public $Parameters;
        
        function __construct($data) {
            $this->Transaction = new TransactionData($data["Transaction"]);
            $this->Confirmation = new ConfirmationRequirementData($data["Confirmation"]);
            
            $this->Parameters = array();
            
            $params = $data["Parameters"];
            
            for($x = 0; $x < count($params); $x++)
            {
                array_push($this->Parameters, new ParameterData($params[$x]));
            }
        }
        
        public function __toString()
        {
            return json_encode($this);
        }
    }
}
?>