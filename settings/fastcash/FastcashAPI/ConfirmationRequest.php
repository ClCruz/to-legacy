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
    class ConfirmationRequest
    {
        public $Confirmation;
        
        function __construct() {
            $this->Confirmation = new ConfirmationData();
        }
    }
}
?>