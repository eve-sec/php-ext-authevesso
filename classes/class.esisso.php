<?php
namespace snitch\authevesso;

use Swagger\Client\ApiClient;
use Swagger\Client\Configuration;
use Swagger\Client\ApiException;
use Swagger\Client\Api\CharacterApi;

require_once(realpath(dirname(__FILE__))."/esi/autoload.php");

if (session_status() != PHP_SESSION_ACTIVE) {
  session_start();
}

// Credit to FuzzySteve https://github.com/fuzzysteve/eve-sso-auth/
class ESISSO
{
  private $code = null;
  protected $accessToken = null;
  private $refreshToken = null;
  private $scopes = array();
  private $ownerHash = null;
  protected $characterID = 0;
  protected $characterName = null;
  protected $error = false;
  protected $message = null;
  protected $failcount = 0;
  protected $enabled = true;
  protected $id = null;
  protected $expires = null;
  protected $config;

	function __construct($id = null, $characterID = 0, $refreshToken = null, $failcount = 0)
	{
                global $db, $config, $table_prefix;
                $this->config = $config;
                if($id != null) {
                        $this->id = $id;
                        $sql="SELECT * FROM ".USERS_TABLE." WHERE user_characterID IS NOT NULL AND user_id = ".$id.";";
                        $result = $db->sql_query($sql);
                        $row = $db->sql_fetchrow($result);
                        if($row) {
                        	$this->characterID = $row['user_characterID'];
                                $this->characterName = $row['username'];
                                $this->refreshToken = $row['user_refreshToken'];
                                $this->failcount = $row['user_APIfailcount'];
                                $this->enabled = ($row['user_type'] == USER_INACTIVE ? false : true);
                                $this->refresh(false);
                        }
                        $db->sql_freeresult($result);	
		} elseif ($characterID != 0) {
			$this->characterID = $characterID;
			$sql="SELECT * FROM ".USERS_TABLE." WHERE (user_characterID='".$characterID."')";
                        $result = $db->sql_query($sql);
                        $row = $db->sql_fetchrow($result);
                        if($row) {
				$this->id = $row['user_id'];
                                $this->characterName = $row['user_characterName'];
				$this->refreshToken = $row['user_refreshToken'];
                                $this->failcount = $row['user_APIfailcount'];
                                $this->enabled = ($row['user_type'] == USER_INACTIVE ? false : true);
				$this->refresh(false);
			}
                        $db->sql_freeresult($result);
		} elseif ($refreshToken != null) {
			$this->refreshToken = $refreshToken;
			$this->refresh();
		}
	}

	public function setCode($code) {
		$this->code = $code;

                $url = 'https://login.eveonline.com/oauth/token';
                $header = 'Authorization: Basic '.base64_encode($this->config['snitch_authevesso_clientid'].':'.$this->config['snitch_authevesso_code']);
                $fields_string = '';
                $fields = array(
                    'grant_type' => 'authorization_code',
                    'code' => $code,
                    );
                foreach ($fields as $key => $value) {
                    $fields_string .= $key.'='.$value.'&';
                }
                rtrim($fields_string, '&');
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_USERAGENT, $this->config['snitch_authevesso_esi_ua']);
                curl_setopt($ch, CURLOPT_HTTPHEADER, array($header));
                curl_setopt($ch, CURLOPT_POST, count($fields));
                curl_setopt($ch, CURLOPT_POSTFIELDS, $fields_string);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
                $result = curl_exec($ch);
                if ($result === false) {
                    $this->error = true;
                    $this->message = (curl_error($ch));
                }
                curl_close($ch);
                if (!$this->error){
                    $response = json_decode($result);
                    $this->accessToken = $response->access_token;
                    $this->expires = (strtotime("now")+1000);
                    $this->refreshToken = $response->refresh_token;
                    $result = $this->verify();
                    return $result;
                } else {
                    return false;
                }
	}

        public function verify() {
		if (!isset($this->accessToken)) {
                    $this->error = true;
                    $this->message = "No Acess Token to verify.";
                    return false;
		} else {
                    $verify_url = 'https://login.eveonline.com/oauth/verify';
                    $ch = curl_init();
                    $header = 'Authorization: Bearer '.$this->accessToken;
                    curl_setopt($ch, CURLOPT_URL, $verify_url);
                    curl_setopt($ch, CURLOPT_USERAGENT, $this->config['snitch_authevesso_esi_ua']);
                    curl_setopt($ch, CURLOPT_HTTPHEADER, array($header));
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
                    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
                    $result = curl_exec($ch);
                    if ($result === false) {
                        $this->error = true;
                        $this->message = (curl_error($ch));
                    }
                    curl_close($ch);
                    if ($this->error) {
			return false;
		    }
                        $response = json_decode($result);
                        if (isset($response->error)) {
                            $this->error = true;
                            $this->message = $response->error;
                            return false;
                        }
                        if (!isset($response->CharacterID)) {
                            $this->error = true;
                            $this->message = "Failed to get character ID.";
                            return false;
                        }
                        $this->characterID = $response->CharacterID;
                        $this->characterName = $response->CharacterName;
                        $this->scopes = explode(' ', $response->Scopes);
                        if ($this->scopes == null || $this->scopes == '') {
                            $this->error = true;
                            $this->message = 'Scopes missing.';
                            return false;
                        }
                        $this->ownerHash = $response->CharacterOwnerHash;
                }
		return true;
	}

	public function refresh( $verify = true ) {
                if (!isset($this->refreshToken)) {
		    $this->error = true;
                    $this->message = "No refresh token set.";
                    return false;
		}
	        $fields = array('grant_type' => 'refresh_token', 'refresh_token' => $this->refreshToken);
       		$url = 'https://login.eveonline.com/oauth/token';
	        $header = 'Authorization: Basic '.base64_encode($this->config['snitch_authevesso_clientid'].':'.$this->config['snitch_authevesso_code']);
	        $fields_string = '';
	        foreach ($fields as $arrKey => $value) {
	            $fields_string .= $arrKey.'='.$value.'&';
	        }
	        $fields_string = rtrim($fields_string, '&');
	        $ch = curl_init();
	        curl_setopt($ch, CURLOPT_URL, $url);
	        curl_setopt($ch, CURLOPT_USERAGENT, $this->config['snitch_authevesso_esi_ua']);
	        curl_setopt($ch, CURLOPT_HTTPHEADER, array($header));
	        curl_setopt($ch, CURLOPT_POST, count($fields));
	        curl_setopt($ch, CURLOPT_POSTFIELDS, $fields_string);
	        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
	        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
 	        $result = curl_exec($ch);
                if ($result === false) {
                    $this->error = true;
                    $this->message = (curl_error($ch));
                }
 	        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		curl_close($ch);
                if ($httpCode < 199 || $httpCode > 299) {
                    $this->error = true;
                    $this->message = ("Error: Response ".$httpCode." when refreshing the Access Token.");
                }
                if ($this->error) {
                    $this->increaseFailCount();
                    return false;
                }
		$response = json_decode($result, true);
      		$this->accessToken = $response['access_token'];
                $this->expires = (strtotime("now")+1000);
                $expires = date('Y-m-d H:i:s', $this->expires);

                if ($verify) {
		    $this->verify();
                }
                $this->resetFailCount();
		return true;
	}

	public function increaseFailCount() {
                global $db;
                $this->failcount+=1;
                $sql="UPDATE ".USERS_TABLE." SET user_APIfailcount={$this->failcount} WHERE user_id={$this->id};";
                $result = $db->sql_query($sql);
	}

        public function resetFailCount() {
                global $db;
                if ($this->failcount != 0) {
                	$this->failcount = 0;
                        $sql="UPDATE ".USERS_TABLE." SET user_APIfailcount=0 WHERE user_id={$this->id};";
	                $result = $db->sql_query($sql);
                }
        }


        public function getError() {
		return $this->error;
	}

        public function getMessage() {
                return $this->message;
        }

        public function getAccessToken() {
                return $this->accessToken;
        }

        public function getRefreshToken() {
                return $this->refreshToken;
        }

        public function getOwnerHash() { 
		return $this->ownerHash;
	}

        public function getCharacterID() { 
		return $this->characterID;
	}

        public function getCharacterName() {
                return $this->characterName;
        }

        public function getFailcount() {
		return $this->failcount;
	}

	public function isEnabled() {
		return $this->enabled;
	}

        public function hasExpired() {
                if ($this->expires < strtotime("now")) {
                        return true;
                } else {
			return false;
		}
        }

        public function getScopes() {
                if (empty($this->scopes)) {
                        $this->verify();
                }
                return $this->scopes;
        }

}
?>
