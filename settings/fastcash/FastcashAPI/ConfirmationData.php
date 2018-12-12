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
    class ConfirmationData
    {
        public $Tid;
        public $Pid;
        public $ProdId;
        public $F1;
        public $F2;
        public $F3;
        public $F4;
        public $PaidDate;
        public $Value;
        public $Observations;
        
        function __construct($data = null) {
            if ($data != null)
            {
                $this->Tid = $data["Tid"];
                $this->Pid = $data["Pid"];
                $this->ProdId = $data["ProdId"];
                $this->F1 = $data["F1"];
                $this->F2 = $data["F2"];
                $this->F3 = $data["F3"];
                $this->F4 = $data["F4"];
                $this->PaidDate = $data["PaidDate"];
                $this->Value = $data["Value"];
                $this->Observations = $data["Observations"];
            }
        }
        
        public function __toString()
        {
            return json_encode($this);
        }
    }
}
?>