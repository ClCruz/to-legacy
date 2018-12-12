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
    use Guzzle\Http\Client;
    use Guzzle\Http\Exception\ServerErrorResponseException;
    use Guzzle\Http\Exception\ClientErrorResponseException;
    
    class ConfirmationClient extends BaseClient
    {
        public $Error;
        public $ErrorBody;
        
        function __construct() {
            parent::__construct();
        }
        
        public function Send($confirmationRequest)
        {
            if ($confirmationRequest instanceOf ConfirmationRequest)
            {
                try
                {
                    $request = $this->Client->post("Confirmation", null, json_encode($confirmationRequest));
                    $request->setHeader("Content-type", "text/json");
                    $response = $request->send();
                    
                    if ($response->getStatusCode() == 202 || $response->getStatusCode() == 200)
                    {
                        return true;
                    }
                    
                    $this->Error = null;
                    $this->ErrorBody = null;                   
                }
                catch(ServerErrorResponseException $e)
                {
                    $this->Error = $e->getMessage();
                }
                catch(ClientErrorResponseException $ce)
                {
                    $this->Error = $ce->getMessage();
                    $this->ErrorBody = $ce->getResponse()->getBody();
                }
            }
            
            return false;
        }
    }
}
?>