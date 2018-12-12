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
    class RefundRequest
    {
        public $Tid;
        public $Pid;
        public $PartialRefund;
        public $IncludesIndenization;
        public $RefundValue;
        public $RefundValueDescription;
        public $Reason;
        
        function __construct($data = null) {
            if ($data != null)
            {
                $this->Tid = $data["Tid"];
                $this->Pid = $data["Pid"];
                $this->PartialRefund = $data["PartialRefund"];
                $this->IncludesIndenization = $data["IncludesIndenization"];
                $this->RefundValue = $data["RefundValue"];
                $this->RefundValueDescription = $data["RefundValueDescription"];
                $this->Reason = $data["Reason"];
            }
        }
        
        public function __toString()
        {
            return json_encode($this);
        }
    }
}

?>