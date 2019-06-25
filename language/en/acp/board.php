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
	'EVESSO' => 'EVE SSO auth',

	'EVESSO_CLIENTID' => 'Client ID',
	'EVESSO_CLIENTID_EXPLAIN' => 'Client ID from developers.eveonline.com',

	'EVESSO_CODE' => 'App Code',
	'EVESSO_CODE_EXPLAIN' => 'Code from developers.eveonline.com',

	'EVESSO_ESI_UA' => 'User Agent',
	'EVESSO_ESI_UA_EXPLAIN' => 'User Agent when comunication with the eve online SSO or API servers',

	'EVESSO_ADMIN_USER' => 'Admin Character Name',
	'EVESSO_ADMIN_USER_EXPLAIN' => 'Character Name of the Board admnistrator to prevent lockout.',
        
        'EVESSO_TEST_SSO' => 'Test SSO login',

        'ACP_AUTHEVESSO_GENERALSETTINGS' => 'General Settings',
        'ACP_AUTHEVESSO_AVATARSIZE' => 'Avatar size',
        'ACP_AUTHEVESSO_CRONSETTINGS' => 'Cron Settings',
        'ACP_AUTHEVESSO_CRONINTERVAL' => 'Cronjob interval in hours',
        'ACP_AUTHEVESSO_MAXCRON' => 'Max. cronjob time in seconds',
        'ACP_AUTHEVESSO_ENABLETS' => 'Enable Teamspeak integration?',
        'ACP_AUTHEVESSO_EXISTING' => 'Allow linking to existing Accounts?',
        'ACP_AUTHEVESSO_EXISTING_EXPLAIN' => 'Potential security issue, will not work for admins/founders.',
        'ACP_AUTHEVESSO_REQ_CORP' => 'Require Corp/Ally',
        'ACP_AUTHEVESSO_REQ_CORP_EXPLAIN' => 'Prevents users from registering if they are not part of any corp or allaince listed below.',
        'ACP_AUTHEVESSO_REQ_VALIDSSO' => 'Check ESI access',
        'ACP_AUTHEVESSO_REQ_VALIDSSO_EXPLAIN' => 'Tries to access the user via the API and deactivates the user if the refresh token has been revoked.',
        'ACP_AUTHEVESSO_TSSERVERSETTINGS' => 'Teamspeak Settings',
        'ACP_AUTHEVESSO_TSSERVER' => 'Teamspeak server',
        'ACP_AUTHEVESSO_TSPORT' => 'Teamspeak server port',
        'ACP_AUTHEVESSO_TSADMIN' => 'Teamspeak admin user',
        'ACP_AUTHEVESSO_TSPASS' => 'Teamspeak admin password',
        'ACP_AUTHEVESSO_TSQUERY' => 'Teamspeak query port',
        'ACP_AUTHEVESSO_SETTING_SAVED' => 'Settings have been saved successfully!',
        'ACP_AUTHEVESSO_TS3MAPPINGS' => 'Forum/Teamspeak Group Mappings',
        'ACP_AUTHEVESSO_NAME' => 'Corp/Alliance name',
        'ACP_AUTHEVESSO_TYPE' => 'Entity type',
        'ACP_AUTHEVESSO_CORP' => 'Corporation',
        'ACP_AUTHEVESSO_ALLI' => 'Alliance',
        'ACP_AUTHEVESSO_FGROUP' => 'Assign Forum Group',
        'ACP_AUTHEVESSO_TSGROUP' => 'Assign Teamspeak Group',
        'ACP_AUTHEVESSO_ADDROW' => 'Add row',
        'ACP_AUTHEVESSO_COULDNT_FIND' => 'Could not find an ID for',

));
