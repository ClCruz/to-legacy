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
    class ParameterData
    {
        public $Id;
        public $Name;
        public $Value;
        
        function __construct($data) {
            $this->Id = $data["Id"];
            $this->Name = $data["Name"];
            $this->Value = $data["Value"];
        }
        
        public function __toString()
        {
            return json_encode($this);
        }
    }
}
?>