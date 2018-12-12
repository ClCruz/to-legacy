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
    class ClientData
    {
        public $Name;
        public $Email;
        public $MobilePhoneNumber;
        public $Cpf;
        
        function __construct($data = null) {
            if ($data != null)
            {
                $this->Name = $data["Name"];
                $this->Email = $data["Email"];
                $this->MobilePhoneNumber = $data["MobilePhoneNumber"];
                $this->Cpf = $data["Cpf"];
            }
        }
    }
}
?>