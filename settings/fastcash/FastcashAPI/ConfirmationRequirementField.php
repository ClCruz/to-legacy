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
    class ConfirmationRequirementField
    {
        public $Name;
        public $Required;
        public $DataType;
        
        function __construct($data) {
            if ($data != null)
            {
                $this->Name = $data["Name"];
                $this->Required = $data["Required"];
                $this->DataType = $data["DataType"];
            }
        }
        
        public function __toString()
        {
            return json_encode($this);
        }
    }
}
?>