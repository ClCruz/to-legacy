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
    class ConfirmationRequirementData
    {
        public $Required;
        public $FieldsCount;
        public $F1;
        public $F2;
        public $F3;
        public $F4;
        
        function __construct($data) {
            $this->Required = $data["Required"];
            $this->FieldsCount = $data["FieldsCount"];
            $this->F1 = new ConfirmationRequirementField($data["F1"]);
            $this->F2 = new ConfirmationRequirementField($data["F2"]);
            $this->F3 = new ConfirmationRequirementField($data["F3"]);
            $this->F4 = new ConfirmationRequirementField($data["F4"]);
        }
        
        public function __toString()
        {
            return json_encode($this);
        }
    }
}
?>