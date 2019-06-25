<?php
use Swagger\Client\Configuration;
use Swagger\Client\ApiException;
use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use Kevinrob\GuzzleCache\CacheMiddleware;
use Doctrine\Common\Cache\FilesystemCache;
use Kevinrob\GuzzleCache\Strategy\PrivateCacheStrategy;
use Kevinrob\GuzzleCache\Storage\DoctrineCacheStorage;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;

require_once(realpath(dirname(__FILE__)).'/esi/vendor/autoload.php');
require_once(realpath(dirname(__FILE__)).'/esi/autoload.php');

class ESIAPI
{
    protected $esiConfig;
    protected $error = false;
    protected $message = null;
    protected $log;
    private $timeout = 3;
    private $connect_timeout = 2;
    private $retries = 2;
    private $retryDelay = 2;

    public function __construct(\Swagger\Client\Configuration $esiConfig = null) 
    {
        global $config;    
        if($esiConfig == null)
        {
            $this->esiConfig = Configuration::getDefaultConfiguration();
            //$this->esiConfig->setCurlTimeout(3);
            $this->esiConfig->setUserAgent($config['snitch_authevesso_esi_ua']);
            // disable the expect header, because the ESI server reacts with HTTP 502
            //$this->esiConfig->addDefaultHeader('Expect', '');
        }
    }
    
    public function setAccessToken($accessToken)
    {
        $this->esiConfig->setAccessToken($accessToken);
    }

    public function setTimeout($timeout)
    {
        $this->timeout = $timeout;
    }

    public function setConnectTimeout($timeout)
    {
        $this->connect_timeout = $timeout;
    }

    public function setRetries($retries)
    {
        $this->retries = $retries;
    }

    public function setRetryDelay($delay)
    {
        $this->retryDelay = $delay;
    }

    public function getRetryDelayMs()
    {
        return function( $retries ) {
            return 1000 * $this->retryDelay;
        };
    }

    public function getConfig()
    {
        return $this->esiConfig;
    }

    public function getApi($api) {
        $className = "Swagger\\Client\\Api\\".$api."Api";
        $stack = HandlerStack::create();
        $stack->push( Middleware::retry( $this->retryDecider(), $this->getRetryDelayMs() ) );
        $stack->push(
        new CacheMiddleware(
            new PrivateCacheStrategy(
                new DoctrineCacheStorage(
                    new FilesystemCache(realpath(dirname(__FILE__)).'/../../../../cache/')
                )
            )
        ), 
        'private-cache'
        );
        return new $className(new Client(['handler' => $stack, 
                                          'defaults' => ['connect_timeout' => $this->connect_timeout, 
                                                         'timeout' => $this->timeout ]]
                                        ), $this->esiConfig);
    }

    public static function checkTQ() {
        global $config;
        $stack = HandlerStack::create();
        $stack->push(
        new CacheMiddleware(
            new PrivateCacheStrategy(
                new DoctrineCacheStorage(
                    new FilesystemCache(realpath(dirname(__FILE__)).'/../../../../cache/')
                )
            )
        ),
        'private-cache'
        );
        $esiConfig = Configuration::getDefaultConfiguration();
        $esiConfig->setUserAgent($config['snitch_authevesso_esi_ua']);
        $statusApi = new Swagger\Client\Api\StatusApi(new Client(['handler' => $stack,
                                                                  'defaults' => ['connect_timeout' => 2,
                                                                                 'timeout' => 3]]
                                                     ), $esiConfig);
        try {
            $status = json_decode($statusApi->getStatus('tranquility'), true);
        } catch (Exception $e) {
            return false;
        }
        return $status;
    }

    private function retryDecider() {
       return function (
          $retries,
          Request $request,
          Response $response = null,
          RequestException $exception = null
       ) {
          // Limit the number of retries
          if ( $retries >= $this->retries ) {
             return false;
          }
     
          // Retry connection exceptions
          if( $exception instanceof ConnectException ) {
             return true;
          }
     
          if( $response ) {
             // Retry on server errors
             if( $response->getStatusCode() >= 500 ) {
                return true;
             }
          }
     
          return false;
       };
    }
    
}
?>
