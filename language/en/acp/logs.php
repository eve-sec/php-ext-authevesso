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
	'CRON_STARTED' => 'Cron Job started',
    'CRON_FINISHED' => 'Cron Job finished',
    'CRON_USER_DISABLED_CORP' => 'User disabled by cron job due to not being in a member corp',
    'CRON_USER_DISABLED_INVALID' => 'User disabled by cron job due to invalid refresh Token',
    'CRON_USER_DISABLED_SCOPES' => 'User disabled by cron job due to missing scopes',
    'ACP_AUTHEVESSO_LOG_COMMENT' => 'Log entry',
));
