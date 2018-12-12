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
    use Guzzle\Common\Event;
    
    abstract class BaseClient
    {
        protected $Client;
        
        function __construct() {
            $this->Client = new Client(IntegrationData::FastcashAPIUrl);/*, array(
                    'ssl.certificate_authority' => 'system',
                    'curl.options' => array(
                        CURLOPT_SSL_VERIFYHOST      => false,
                        CURLOPT_SSL_VERIFYPEER      => false,
                        CURLOPT_PROXY               => 'proxyaddress:port',
                        CURLOPT_PROXYTYPE           => 'CURLPROXY_HTTP',
                        CURLOPT_PROXYAUTH           => 'CURLAUTH_BASIC',
                        CURLOPT_PROXYUSERPWD        => 'user:pass'
                    )));*/
            
            $this->Client->setDefaultHeaders(array(
                'Accept'          => 'application/json'
            ));

            $this->Client->getEventDispatcher()->addListener('client.create_request', function(Event $event) {
                $req = $event['request'];
                
                $apiKey = IntegrationData::FastcashAPIKey;
                $apiKeySecret = IntegrationData::FastcashAPIKeySecret;
                
                $timestamp = time();
                $urlHash = md5(utf8_encode2($req->getUrl()));
                
                if ($req instanceof EntityEnclosingRequestInterface)
					$bodyHash = md5(utf8_encode2($req->getBody()));
                else
					$bodyHash = md5("");
                
                $auth = hash_hmac("sha256", utf8_encode2($apiKey.$urlHash.$bodyHash.$timestamp), base64_decode($apiKeySecret));
                
                $req->setHeader("ApiKey", $apiKey);
                $req->setHeader("Timestamp", $timestamp);
                $req->setHeader("Api-Auth", $auth);
            });
        }
    }
}
?>