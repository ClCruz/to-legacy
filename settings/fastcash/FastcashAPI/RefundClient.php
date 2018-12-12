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
    
    class RefundClient extends BaseClient
    {
        public $Error;
        public $ErrorBody;
        public $Description;
        
        function __construct() {
            parent::__construct();
        }
        
        public function Send($refundRequest)
        {
            if ($refundRequest instanceOf RefundRequest)
            {
                try
                {
                    $request = $this->Client->post("Refund", null, json_encode($refundRequest));
                    $request->setHeader("Content-type", "text/json");
                    $response = $request->send();
                    
                    $this->Error = null;
                    $this->ErrorBody = null; 
                    $this->Description = null;
                    
                    if ($response->getStatusCode() == 202 || $response->getStatusCode() == 200)
                    {
                        $this->Description = $response->getBody(); 
                        
                        return true;
                    }
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