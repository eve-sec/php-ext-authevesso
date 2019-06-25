<?php

use \Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use \snitch\authevesso\URL;
namespace snitch\authevesso;

require_once('ext/snitch/authevesso/loadclasses.php');

class login
{
    /* @var \phpbb\config\config */
    protected $config;

    /* @var \phpbb\controller\helper */
    protected $helper;

    /* @var \phpbb\template\template */
    protected $template;

    /* @var \phpbb\user */
    protected $user;

    protected $settings = array();

    public function __construct(\phpbb\config\config $config, \phpbb\controller\helper $helper, \phpbb\template\template $template, \phpbb\user $user, \phpbb\auth\auth $auth, \phpbb\request\request $request)
    {
        global $db;
        $this->config   = $config;
        $this->helper   = $helper;
        $this->template = $template;
        $this->user     = $user;

        $this->settings['clientid'] = (empty($this->config['snitch_authevesso_clientid'])) ? '' : $this->config['snitch_authevesso_clientid'];
        $this->settings['code'] = (empty($this->config['snitch_authevesso_code'])) ? '' : $this->config['snitch_authevesso_code'];
        $this->request = $request;
        $user->session_begin();
        $auth->acl($user->data);
        $user->setup();
        $this->auth = $auth;
    }

    function random_str($length, $keyspace = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ')
    {
    $str = '';
    $max = mb_strlen($keyspace, '8bit') - 1;
    for ($i = 0; $i < $length; ++$i) {
        $str .= $keyspace[random_int(0, $max)];
    }
        return $str;
    }

    function setSession($sql_ary) {
        global $db;
            
        $sid = $this->user->data['session_id'];
        $sql = 'UPDATE ' . SESSIONS_TABLE . ' SET ' . $db->sql_build_array('UPDATE', $sql_ary) . ' WHERE session_id=\'' . $sid.'\'';
        $db->sql_query($sql);
    }

    public function handleadm()
    {
        global $db;
        global $phpbb_root_path;
        if ($this->user->data['user_id'] == 1) {
            $this->handle();
        }
        $sql = "SELECT * FROM ".USER_GROUP_TABLE." INNER JOIN ".GROUPS_TABLE." ON ".USER_GROUP_TABLE.".group_id = ".GROUPS_TABLE.".group_id WHERE group_name = 'ADMINISTRATORS' AND user_id = ".$this->user->data['user_id']." AND user_pending = 0";
        $result = $db->sql_query($sql);
        $row = $db->sql_fetchrow($result);
        $db->sql_freeresult($result);
        if (!$row) {
            print $this->user->data['user_id'];
            return false;
        } else {
            $this->setSession(array('session_admin' => true));
            $this->user->data['session_admin'] = true;
            $this->user->session_begin(true);
            redirect($phpbb_root_path.'adm/index.php?sid='.$this->user->data['session_id']);
        }
    }

    public function handleimg() {
        return $this->helper->render("loginimg.html");
    }

    public function handle()
    {
        global $phpbb_root_path;
        $sid = $this->user->data['session_id'];
        if (request_var('code', '') != '' ) {
          $code = request_var('code', '');
          $state = request_var('state', '');
          if (!isset($this->user->data['session_authstate']) || $state != $this->user->data['session_authstate']) {
            $html = "Error: Invalid state, aborting.";
            session_destroy();
            trigger_error($this->user->lang('AUTHEVESSO_STATE_ERROR') .'<br /><a href="'.generate_board_url().'">'.$this->user->lang('BACK_TO_PREV') .'</a>', E_USER_WARNING);
            exit;
          }
          $esisso = new ESISSO();
          $esisso->setCode($code);
          if (!$esisso->getError()) {
                $this->setSession(array('session_characterID' => $esisso->getCharacterID(),
                                        'session_characterName' => $esisso->getCharacterName(),
                                        'session_refreshToken' => $esisso->getRefreshToken(),
                                        'session_autologin' => 1));
                //redirect($phpbb_root_path.'app.php/authevesso/login?login=success');
                redirect($this->helper->route('snitch_authevesso_login', array('login' => 'success', 'target' => request_var('target', ''))));
          } else {
            trigger_error($this->user->lang('ERROR').$esisso->getMessage().'<br /><a href="'.generate_board_url().'">'.$this->user->lang('BACK_TO_PREV') .'</a>', E_USER_WARNING);
            exit;
          }
        } elseif (request_var('login', '') == 'success' ) {
            $this->user->set_login_key();
            setcookie($this->config['cookie_name'].'_k',$this->user->cookie_data['k'],time()+31556926,$this->config['cookie_path'],$this->config['cookie_domain'],$this->config['cookie_secure']);
            setcookie($this->config['cookie_name'].'_u',$this->user->data['user_id'],time()+31556926,$this->config['cookie_path'],$this->config['cookie_domain'],$this->config['cookie_secure']);
            setcookie($this->config['cookie_name'].'_sid',$this->user->data['session_id'],time()+31556926,$this->config['cookie_path'],$this->config['cookie_domain'],$this->config['cookie_secure']);
            if (request_var('target', '') != '') {
                redirect(base64_decode(request_var('target', '')), false, true);
            } else {
                redirect($phpbb_root_path);
            }
        }
        $all_scopes = unserialize(EVESSO_SCOPES);
        $scopes = array_intersect_key($all_scopes, array_flip(json_decode($this->config['snitch_authevesso_scopes'], true)));
        $authurl = "https://login.eveonline.com/oauth/authorize/";
        $state = self::random_str(32);
        $this->setSession(array('session_authstate' => $state));
        if (request_var('target', '') != '') {
            $url = $authurl."?response_type=code&redirect_uri=".rawurlencode($this->helper->route('snitch_authevesso_login', array('target' => base64_encode(request_var('target', ''))), true, null, \Symfony\Component\Routing\Generator\UrlGeneratorInterface::ABSOLUTE_URL))."&client_id=".$this->settings['clientid']."&scope=".implode(' ',$scopes)."&state=".$state;
        } else {
            $url = $authurl."?response_type=code&redirect_uri=".rawurlencode($this->helper->route('snitch_authevesso_login', array(), true, null, \Symfony\Component\Routing\Generator\UrlGeneratorInterface::ABSOLUTE_URL))."&client_id=".$this->settings['clientid']."&scope=".implode(' ',$scopes)."&state=".$state;
        }
        redirect($url, false, true);
        exit;
    }
}
?>
