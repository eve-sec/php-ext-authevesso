<?php
use Swagger\Client\ApiClient;
use Swagger\Client\Configuration;
use Swagger\Client\ApiException;
use Swagger\Client\Api\StatusApi;

require_once(realpath(dirname(__FILE__))."/esi/autoload.php");
require_once(realpath(dirname(__FILE__))."/class.esiapi.php");

class ESISTATUS extends \ESIAPI
{
        protected $log;

        public function __construct() {
            parent::__construct();
            $this->setMaxTries(1);
        }

        public function getServerStatus() {
            $statusapi = new StatusApi($this);
            try {
                $response = json_decode($statusapi->getStatus('tranquility'), true);
            } catch (Exception $e) {
                $this->error = true;
                $this->message = 'Could not fetch Server status: '.$e->getMessage().PHP_EOL;
                return false;
            }
            return $response;
        }
}
