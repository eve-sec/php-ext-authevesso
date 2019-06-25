<?php
/**
*
* @package phpBB Extension - Snitch EVE SSO auth
* @copyright (c) 2017 Snitch Ashor
*
*/
namespace snitch\authevesso\auth\provider;

use phpbb\request\request_interface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use \EVEHELPERS;
use \snitch\authevesso\ESISSO; 

require_once(realpath(dirname(__FILE__))."/../../loadclasses.php");

/**
* EVESSO authentication provider for phpBB 3.2
*/
class evesso extends \phpbb\auth\provider\db
{
	/**
	 * phpBB database driver
	 *
	 * @var \phpbb\db\driver\driver_interface
	 */
	protected $db;

	/**
	 * phpBB config
	 *
	 * @var \phpbb\config\config
	 */
	protected $config;

	/**
	 * phpBB request object
	 *
	 * @var \phpbb\request\request
	 */
	protected $request;

	/**
	 * phpBB user object
	 *
	 * @var \phpbb\user
	 */
	protected $user;

	/**
	 * phpBB root path
	 *
	 * @var string
	 */
	protected $phpbb_root_path;

	/**
	 * php file extension
	 *
	 * @var string
	 */
	protected $php_ext;

	/**
	 * auth adapter settings
	 *
	 * @var array
	 */
	protected $settings = array();

	/**
	 * EVESSO Authentication Constructor
	 *  - called when instance of this class is created
	 *
	 * @param	\phpbb\db\driver\driver_interface	$db			Database object
	 * @param	\phpbb\config\config 			$config			Config object
	 * @param	\phpbb\request\request 			$request		Request object
	 * @param	\phpbb\user 				$user			User object
	 * @param	string 					$phpbb_root_path	Relative path to phpBB root
	 * @param	string 					$php_ext		PHP file extension
	 */
	public function __construct(
		\phpbb\db\driver\driver_interface $db,
		\phpbb\config\config $config,
		\phpbb\request\request $request,
		\phpbb\user $user,
		$phpbb_root_path,
		$php_ext,
                \phpbb\controller\helper $helper,
                \phpbb\auth\auth $auth
	)
	{
		$this->db = $db;
		$this->config = $config;
		$this->request = $request;
		$this->user = $user;
		$this->phpbb_root_path = $phpbb_root_path;
		$this->php_ext = $php_ext;
                $this->helper = $helper;
                $this->auth = $auth;

		$this->settings['clientid'] = (empty($this->config['snitch_authevesso_clientid'])) ? '' : $this->config['snitch_authevesso_clientid'];
		$this->settings['code'] = (empty($this->config['snitch_authevesso_code'])) ? '' : $this->config['snitch_authevesso_code'];
		$this->settings['login_handler'] = 'authevesso/login';
		$this->settings['logout_handler'] = 'authevesso/logout';
                $this->settings['esi_ua'] = (empty($this->config['snitch_authevesso_code'])) ? 'evesso phpBB auth provider' : $this->config['snitch_authevesso_esi_ua'];
                $this->settings['admin_user'] = (empty($this->config['snitch_authevesso_admin_user'])) ? '' : $this->config['snitch_authevesso_admin_user'];
	}

	/**
	 * {@inheritdoc}
	 * - called when authentication method is enabled
	 */
	public function init()
	{
		// check if all required fields are filled
		if(
			!isset($this->settings['admin_user']) || $this->settings['admin_user'] == ''
			|| !isset($this->settings['clientid']) || $this->settings['clientid'] == ''
                        || !isset($this->settings['code']) || $this->settings['code'] == ''
		)
		{
			return $this->user->lang['EVESSO_SETUP_BEFORE_USE'];
		}
                $this->config->set('allow_avatar_remote', 1, 0);
		return false;
	}

	/**
	 * {@inheritdoc}
	 * - called when login form is submitted
	 */
	public function login($username = null, $password = null)
	{
		$evesso_user = $this->user->data['session_characterName'];
                $evesso_id = $this->user->data['session_characterID'];
                $evesso_token = $this->user->data['session_refreshToken'];
		// check if user and characterID are set, jump to fallback case (not logged in)
		if(
			(!empty($evesso_user) || !empty($evesso_id))
			//&& $this->request->server('AUTH_TYPE') === 'Evesso'
		)
		{
			$sql = sprintf('SELECT user_id, username, user_password, user_passchg, user_email, user_type, user_inactive_reason FROM %1$s WHERE user_characterID = \'%2$s\'', USERS_TABLE, $this->db->sql_escape($evesso_id));
			$result = $this->db->sql_query($sql);
			$row = $this->db->sql_fetchrow($result);
			$this->db->sql_freeresult($result);

			// user exists
			if($row)
			{
				// check for inactive users
				if($row['user_type'] == USER_INACTIVE || $row['user_type'] == USER_IGNORE)
				{
					return array(
						'status'	=> LOGIN_ERROR_ACTIVE,
						'error_msg'	=> 'ACTIVE_ERROR',
						'user_row'	=> $row,
					);
				}

				// success
                                $this->setSession(array('session_user_id' => $row['user_id']));
				return array(
					'status'		=> LOGIN_SUCCESS,
					'error_msg'		=> false,
					'user_row'		=> $row,
				);
			}

			// first login, create new user
			return array(
				'status'		=> LOGIN_SUCCESS_CREATE_PROFILE,
				'error_msg'		=> false,
				'user_row'		=> $this->newUserRow($evesso_user, $evesso_id, $evesso_token),
			);
		}

		// Fallback, not logged in
		return array(
			'status'	=> LOGIN_ERROR_EXTERNAL_AUTH,
			'error_msg'	=> 'LOGIN_ERROR_EXTERNAL_AUTH_EVESSO',
			'user_row'	=> array('user_id' => ANONYMOUS),
		);
	}

	/**
	 * {@inheritdoc}
	 - called when new session is created
	 */
	public function autologin()
	{
                $evesso_user = (isset($this->user->data['session_characterName'])? $this->user->data['session_characterName'] : '');
                $evesso_id = (isset($this->user->data['session_characterID'])? $this->user->data['session_characterID'] : '');
                $evesso_token = (isset($this->user->data['session_refreshToken'])? $this->user->data['session_refreshToken'] : '');

		// check if characterID is set, jump to fallback case (not logged in)
		if(
			!empty($evesso_id)
		)
		{
			$sql = sprintf('SELECT * FROM %1$s WHERE user_characterID = \'%2$s\'', USERS_TABLE, $this->db->sql_escape($evesso_id));
			$result = $this->db->sql_query($sql);
			$row = $this->db->sql_fetchrow($result);
			$this->db->sql_freeresult($result);

			// user exists
			if($row)
			{
				// check for inactive users
				if($row['user_type'] == USER_INACTIVE || $row['user_type'] == USER_IGNORE)
				{
					return array();
				}

				// success
                                //$this->user->set_login_key();
				return $row;
			}
                        //check if user exists without sso data
                        $sql = sprintf('SELECT * FROM %1$s WHERE username_clean = \'%2$s\'', USERS_TABLE, $this->db->sql_escape(utf8_clean_string($evesso_user)));
                        $result = $this->db->sql_query($sql);
                        $row = $this->db->sql_fetchrow($result);
                        $this->db->sql_freeresult($result);
                        // user exists
                        if($row)
                        {
                                // check for inactive users
                                if($row['user_type'] == USER_INACTIVE || $row['user_type'] == USER_IGNORE)
                                {
                                        return array();
                                }
                                
                                if(utf8_clean_string($this->settings['admin_user']) != utf8_clean_string($evesso_user) && ($row['user_type'] != USER_NORMAL || !$this->config['snitch_authevesso_existing'])) {
                                    $this->user->session_kill();
                                    $this->user->add_lang_ext('snitch/authevesso', 'common');
                                    trigger_error($this->user->lang('AUTHEVESSO_USER_EXISTS') .'<br /><br /><a href="'.generate_board_url().'">'.$this->user->lang('BACK_TO_PREV') .'</a>', E_USER_WARNING);
                                } else {
                                     $row['user_characterID'] = $evesso_id;
                                     $row['user_refreshToken'] = $evesso_token;
                                     $sql = "UPDATE ".USERS_TABLE." SET user_characterID = ".$evesso_id." , user_refreshToken = '".$evesso_token."' WHERE username_clean = '".$this->db->sql_escape(utf8_clean_string($evesso_user))."'";
                                     $this->db->sql_query($sql);
                                     $this->addtoGroups($row);
                                     return $row;
                                }
                        }

			// user does not exist atm, we'll fix that
			if(!function_exists('user_add'))
			{
				include($this->phpbb_root_path . 'includes/functions_user.' . $this->php_ext);
			}
            $groups = $this->checkGroups($evesso_id);
            if ($this->config['snitch_authevesso_req_corp']) {
                    if (!$groups) {
                        $this->user->session_kill();
                        $this->user->add_lang_ext('snitch/authevesso', 'common');
                        trigger_error($this->user->lang('AUTHEVESSO_CORP_REQUIRED') .'<br /><br /><a href="'.generate_board_url().'">'.$this->user->lang('BACK_TO_PREV') .'</a>', E_USER_WARNING);
                    }
            }
			user_add($this->newUserRow($evesso_user, $evesso_id, $evesso_token, $groups));

			// get the newly created user row
			// $sql already defined some lines before
			$result = $this->db->sql_query($sql);
			$row = $this->db->sql_fetchrow($result);
			$this->db->sql_freeresult($result);

			if($row)
			{
                                $this->addtoGroups($row);
				return $row;
			}
		}

		return array();
	}

	/**
	 * {@inheritdoc}
	 * - called on every request when session is active
	 */
	public function validate_session($user)
	{
		// Check if evesso user is set
                $evesso_user = $user['session_characterName'];
                $evesso_id = $user['session_characterID'];
                $evesso_token = $user['session_refreshToken'];
		if(
			!empty($evesso_user) && !empty($evesso_id) && !empty($evesso_token)
		)
		{
                        if ($user['user_characterID'] === $evesso_id) {
                            if ($evesso_token !== $user['user_refreshToken']) {
                                global $db;
                                $sql_arr = array('user_refreshToken' => $evesso_token,);
                                $sql = 'UPDATE ' . USERS_TABLE . ' SET ' . $db->sql_build_array('UPDATE', $sql_arr) . ' WHERE user_id = ' . $user['user_id'];
                                $db->sql_query($sql);
                            }
			    return true;
                        } else {
                            $this->autologin();
                            $result = $this->login();
                            if ($result['status'] === LOGIN_SUCCESS) {
                                global $phpbb_root_path;
                                redirect($this->helper->route('snitch_authevesso_login', array('login' => 'success')));
                            } else if ($result['status'] === LOGIN_ERROR_ACTIVE) {
                                if ($result['user_row']['user_type'] == USER_INACTIVE && $result['user_row']['user_inactive_reason'] == INACTIVE_REMIND) {
                                    $esisso = new ESISSO(null, 0, $evesso_token);
                                    if ($esisso->verify()) {
                                        $groups = $this->checkGroups($evesso_id);
                                        if ($this->config['snitch_authevesso_req_corp']) {
                                            if (!$groups) {
                                                $this->user->session_kill();
                                                $this->user->add_lang_ext('snitch/authevesso', 'common');
                                                trigger_error($this->user->lang('AUTHEVESSO_CORP_REQUIRED') .'<br /><br /><a href="'.generate_board_url().'">'.$this->user->lang('BACK_TO_PREV') .'</a>', E_USER_WARNING);
                                            }
                                        }
                                        global $db;
                                        $sql_arr = array('user_type' => USER_NORMAL, 'user_inactive_time' => 0, 'user_inactive_reason' => 0);
                                        $sql = 'UPDATE ' . USERS_TABLE . ' SET ' . $db->sql_build_array('UPDATE', $sql_arr) . ' WHERE user_characterID = ' . $evesso_id;
                                        $db->sql_query($sql);
                                        return true;
                                    }
                                    return false;
                                } else {
                                    return false;
                                }
                            } else {
                                return false;
                            }
                        }
		}

		// if the user is authed but first case did not fire - invalidate his session so autologin() is called
		if($this->request->server('AUTH_TYPE') === 'Evesso')
		{
			return false;
		}

		// if the user type is ignore, then it's probably an anonymous user or a bot
		if($user['user_type'] == USER_IGNORE)
		{
			return true;
		}

                //if user is inactive and the above didn't reactivate invalidate session
                if($user['user_type'] == USER_INACTIVE)
                {
                        return false;
                }

                if($user['user_type'] == USER_NORMAL || $user['user_type'] == USER_FOUNDER) {
                    //check if user has already signed up using the SSO
                    if (!empty($user['user_characterID']) && !empty($user['user_refreshToken'])) {
                        return true;
                    } else {
                        return false;
                    }
                }

		// no case matched, shouldn't occur...
                return false;
	}

	/**
	 * {@inheritdoc}
	 * - called when user logs out
	 */
	/*public function logout($data, $new_session)
	{
		// the SP's logout handler
                return true;
	}*/

	/**
	 * {@inheritdoc}
	 * - should return custom configuration options
	 */
	public function acp()
	{
		// these are fields in the config for this auth provider
		return array(
			'snitch_authevesso_clientid',
			'snitch_authevesso_code',
			'snitch_authevesso_esi_ua',
			'snitch_authevesso_admin_user',
		);
	}

	/**
	 * {@inheritdoc}
	 * - should return configuration options template
	 */
	public function get_acp_template($new_config)
	{
                if (request_var('testlogin', 0 )) {
                    if ($this->user->data['session_characterName']) {
                        $message = $this->user->lang('EVESSO_LOGGED_IN_AS').$this->user->data['session_characterName'];
                    } else {
                        $message = $this->user->lang('EVESSO_NOT_LOGGED_IN');
                    }
                } else {
                    $message = '';
                }
                $url = $this->helper->route('snitch_authevesso_login', array('target' => generate_board_url()."/adm/index.php?sid=".$this->user->data['session_id']."&i=acp_board&mode=auth&testlogin=1"));
		return array(
			'TEMPLATE_FILE'	=> '@snitch_authevesso/auth_provider_evesso.html',
			'TEMPLATE_VARS'	=> array(
                                'AUTH_EVESSO_CALLBACK'			=> $this->helper->route('snitch_authevesso_login', array(), true, null, UrlGeneratorInterface::ABSOLUTE_URL),
				'AUTH_EVESSO_CLIENTID'			=> $new_config['snitch_authevesso_clientid'],
				'AUTH_EVESSO_CODE'			=> $new_config['snitch_authevesso_code'],
				'AUTH_EVESSO_ESI_UA'			=> $new_config['snitch_authevesso_esi_ua'],
				'AUTH_EVESSO_ADMIN_USER'		=> $new_config['snitch_authevesso_admin_user'],
                                'AUTH_EVESSO_TEST_URL'                  => $url,
                                'AUTH_EVESSO_MESSAGE'                   => $message,
                                'AUTH_EVESSO_SCOPES'                    => implode(', ', array_intersect_key(unserialize(EVESSO_SCOPES), array_flip(json_decode($this->config['snitch_authevesso_scopes'], true)))),
			),
		);
	}

	/**
	* {@inheritdoc}
	* - should return additional template data for login form
	*/
	public function get_login_data()
	{

                $evesso_id = $this->user->data['user_characterID'];
                $evesso_token = $this->user->data['user_refreshToken'];
                if(
                        empty($evesso_id) || empty($evesso_token)
                )
		{
			//page to send back to (forum index)
			$phpbb_url = append_sid(sprintf('%s/%s.%s', generate_board_url(), 'index', $this->php_ext), false, false);
			// the SP's login handler
			//$evesso_sp_url = sprintf('%s%s?target=%s', $this->settings['handler_base'], $this->settings['login_handler'], urlencode($phpbb_url));
                        $evesso_sp_url = $this->helper->route('snitch_authevesso_login', array('target' => $phpbb_url));

			redirect($evesso_sp_url, false, true);
		}
                //ok we git here which means user is authed but needs login probably adm
                if (substr( $this->user->page['page'],0 ,4) === 'adm/') {
                    redirect($this->helper->route('snitch_authevesso_adm_login'), false, true);
                }
                //Should never happen:
		return array(
			'TEMPLATE_FILE'	=> '@snitch_authevesso/login_body_evesso.html',
			'VARS'	=> array(
				'LOGINBOX_AUTHENTICATE_EVESSO' => true,
			),
		);
	}

	/**
	 * This function generates an array which can be passed to the user_add function in order to create a user
	 *
	 * @param 	string	$username 	The username of the new user.
	 * @param 	string	$password 	The password of the new user, may be empty
	 * @return 	array 			Contains data that can be passed directly to the user_add function.
	 */
	private function newUserRow($username, $characterID, $refreshToken, $groups = null)
	{
		// first retrieve default group id
		$sql = sprintf('SELECT group_id FROM %1$s WHERE group_name = \'%2$s\' AND group_type = \'%3$s\'', GROUPS_TABLE, $this->db->sql_escape('REGISTERED'), GROUP_SPECIAL);
		$result = $this->db->sql_query($sql);
		$row = $this->db->sql_fetchrow($result);
		$this->db->sql_freeresult($result);
                //check if its the admin user entered in the ACP
                if ($username === $this->settings['admin_user']) {
                    $type = USER_FOUNDER;
                } else {
                    $type = USER_NORMAL;
                }
		if(!$row)
		{
			trigger_error('NO_GROUP');
		}
        $size = $this->config['snitch_authevesso_avatarsize'];
        if ($groups) {
            foreach ($groups as $group) {
                if ($group['type'] == 'corporation') {
                    $groupid = $group['forum_group'];
                }
            }
            if (!$groupid) {
                $groupid = $groups[0]['forum_group'];
            }
        } else {
            $groupid = $row['group_id'];
        }
		// generate user account data
		return array(
			'username'		=> $username,
			'user_password'	=> '',
			'user_email'	=> '',
			'group_id'		=> (int)$groupid,
			'user_type'		=> $type,
			'user_ip'		=> $this->user->ip,
			'user_new'		=> 0,
            'user_characterID'	=> $characterID,
            'user_refreshToken'	=> $refreshToken,
            'user_avatar_type'     => 'avatar.driver.remote',
            'user_avatar'     => 'https://imageserver.eveonline.com/Character/'.$characterID.'_'.$size.'.jpg',
            'user_avatar_width'     => $size,
            'user_avatar_height'     => $size,
		);
	}

    public function checkGroups($characterID) {
        global $db, $table_prefix;
        $corpID = EVEHELPERS::getCorpForChar($characterID);
        if ($corpID) {
            $corpinfo = EVEHELPERS::getCorpInfo($corpID);
            if ($corpinfo->getAllianceId()) {
                $allyID = $corpinfo->getAllianceId();
            }
            $sql = "SELECT forum_group FROM ".$table_prefix."authevesso_groups WHERE id = ".$corpID.((isset($allyID))?' OR id = '.$allyID:'');
            $result = $db->sql_query($sql);
            $groups = $db->sql_fetchrowset($result);
            $db->sql_freeresult($result);
            if (count($groups)) {
                return $groups;
            } else {
                return null;
            }
        } else {
            return null;
        }
    }
    function addToGroups($user) {
        global $db, $table_prefix;
        $username = $user['username'];
        $id = $user['user_id'];
        if ($username === $this->settings['admin_user']) {
            $sql = "REPLACE INTO ".USER_GROUP_TABLE." (group_id, group_leader, user_id, user_pending) VALUES ((SELECT group_id FROM ".GROUPS_TABLE." WHERE group_name = 'ADMINISTRATORS'), 0, ".$id.", 0)";
            $db->sql_query($sql);
        }
        $characterID = $user['user_characterID'];
        $corpID = EVEHELPERS::getCorpForChar($characterID);
        if ($corpID) {
            $corpinfo = EVEHELPERS::getCorpInfo($corpID);
            $corpName = $corpinfo->getName();
            $membership = array('characterID' => $characterID, 'corporationID' => $corpID, 'corporationName' => $corpName);
            if ($corpinfo->getAllianceId()) {
                $allyID = $corpinfo->getAllianceId();
                $allyName = EVEHELPERS::getAllyInfo($allyID)->getName();
                $membership['allianceID'] = $allyID;
                $membership['allianceName'] = $allyName;
            }
            $sql = "SELECT forum_group, user_id FROM ".$table_prefix."authevesso_groups 
                    LEFT JOIN ".USER_GROUP_TABLE." ON (".$table_prefix."authevesso_groups.forum_group = ".USER_GROUP_TABLE.".group_id AND ".USER_GROUP_TABLE.".user_id = ".$id.") 
                    WHERE id = ".$corpID.((isset($allyID))?' OR id = '.$allyID:'')." AND user_id IS NULL";
            $result = $db->sql_query($sql);
            $toadd = $db->sql_fetchrowset($result);
            $this->db->sql_freeresult($result);

            //default group id
            $sql = sprintf('SELECT group_id FROM %1$s WHERE group_name = \'%2$s\' AND group_type = \'%3$s\'', GROUPS_TABLE, $this->db->sql_escape('REGISTERED'), GROUP_SPECIAL);
            $result = $this->db->sql_query($sql);
            $row = $this->db->sql_fetchrow($result);
            $defgroup = (int)$row['group_id'];
            $this->db->sql_freeresult($result);
            $toadd[] = array('forum_group' => $defgroup);

            foreach ($toadd as $add) {
                $sql = "REPLACE INTO ".USER_GROUP_TABLE." (group_id, group_leader, user_id, user_pending) VALUES (".$add['forum_group'].", 0, ".$id.", 0)";
                $db->sql_query($sql);
            }
            $sql = "REPLACE INTO ".$table_prefix."authevesso_membership " . $db->sql_build_array('INSERT', $membership);
            $db->sql_query($sql);

            $this->auth->acl_clear_prefetch();
        } else {
            $this->user->add_lang_ext('snitch/authevesso', 'common');
            trigger_error($this->user->lang('AUTHEVESSO_API_ERROR') .'<br /><br /><a href="'.generate_board_url().'">'.$this->user->lang('BACK_TO_PREV') .'</a>', E_USER_WARNING);
        }
    }

    function setSession($sql_ary) {
        global $db;
        $sid = $this->user->data['session_id'];
        $sql = 'UPDATE ' . SESSIONS_TABLE . ' SET ' . $db->sql_build_array('UPDATE', $sql_ary) . ' WHERE session_id=\'' . $sid.'\'';
        $db->sql_query($sql);
    }

}
