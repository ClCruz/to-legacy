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
    class TransactionData
    {
        public $Tid;
        public $Pid;
        public $ProdId;
        public $Custom;
        public $Price;
        public $ItemDescription;
        public $PaymentMethod;
        public $SubPaymentMethod;
        public $Mode;
        public $Status;
        
        function __construct($data = null) {
            if ($data != null)
            {
                $this->Tid = $data["Tid"];
                $this->Pid = $data["Pid"];
                $this->ProdId = $data["ProdId"];
                $this->Custom = $data["Custom"];
                $this->Price = $data["Price"];
                $this->ItemDescription = $data["ItemDescription"];
                $this->PaymentMethod = $data["PaymentMethod"];
                $this->SubPaymentMethod = $data["SubPaymentMethod"];
                $this->Mode = $data["Mode"];
                $this->Status = $data["Status"];
            }
        }
        
        public function __toString()
        {
            return json_encode($this);
        }
    }
}
?>