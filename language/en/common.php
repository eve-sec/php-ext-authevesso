<?php
/**
*
* @package phpBB Extension - Snitch EVE SSO auth
* @copyright (c) 2017 Snitch Ashor
*
*/
if(!defined('IN_PHPBB'))
{
	exit;
}
if(empty($lang) || !is_array($lang))
{
	$lang = array();
}

$lang = array_merge($lang, array(
	'LOGIN_ERROR_EXTERNAL_AUTH_EVESSO' => 'You have not been logged in with your EVE account.',
	'LOGIN_AUTH_EVESSO' => 'Login with EVE SSO',
        'AUTHEVESSO_USER_EXISTS' => 'Username already exists but is not linked to an EVE account.',
        'AUTHEVESSO_CORP_REQUIRED' => 'Registration is restricted to memebers of certain corporations or alliances. If you think you should be able to register according to your corporation memebership, please try again in a few minutes.',
        'ACP_WARNING' => 'You are already logged in with your EVE account, for safety reasons please log out of the admin panel once you\'re done.',
        'ACP_WARNING_OK' => 'Understood!',
        'AUTHEVESSO_STATE_ERROR' => 'Error: Invalid auth state.',
        'ACP_AUTHEVESSO_TITLE' => 'EVE SSO auth',
        'ACP_AUTHEVESSO' => 'Settings',
        'ACP_AUTHEVESSO_SCOPES' => 'Requested Scopes',
        'ACP_AUTHEVESSO_LOGS' => 'Logs',
        'AUTHEVESSO_TSID' => 'Teamspeak unique ID',
        'EVESSO_SETUP_BEFORE_USE' => 'You have to setup EVE SSO authentication before you switch phpBB to this authentication method. Get a client ID / code combination from <a href="https://developers.eveonline.com/">here</a>. When setting up the developer app use \'<forum_url>/app.php/authevesso/login\' as callback url and request at least the scope \'esi-corporations.read_corporation_membership.v1\'. Also an EVE character needs to be selected which becomes a founder level admin upon login, your normal admin account stops working.',
        'EVESSO_OBTAINKEY' => 'Get a client ID / code combination from the <a href="https://developers.eveonline.com/">EVE Developers page</a>. Request at least the scopes: ',
        'EVESSO_CALLBACK_URL' => '<br />Set the callback url to: ',
        'AUTHEVESSO_API_ERROR' => 'Something went wrong when contacting the API, maybe it\'s downtime?',

        'EVESSO' => 'EVE SSO auth',
        'EVE_SSO_AUTH' => 'EVE SSO auth',

        'EVESSO_CLIENTID' => 'Client ID',
        'EVESSO_CLIENTID_EXPLAIN' => 'Client ID from developers.eveonline.com',

        'EVESSO_CODE' => 'App Code',
        'EVESSO_CODE_EXPLAIN' => 'Code from developers.eveonline.com',

        'EVESSO_ESI_UA' => 'User Agent',
        'EVESSO_ESI_UA_EXPLAIN' => 'User Agent when comunication with the eve online SSO or API servers',

        'EVESSO_ADMIN_USER' => 'Admin Character Name',
        'EVESSO_ADMIN_USER_EXPLAIN' => 'Character Name of the Board admnistrator to prevent lockout.',
        
         'EVESSO_TEST_SSO' => 'Test SSO login',
         'EVESSO_LOGGED_IN_AS' => 'You are currently logged in as ',
         'EVESSO_NOT_LOGGED_IN' => 'You are not logged in to an EVE account.',
));
