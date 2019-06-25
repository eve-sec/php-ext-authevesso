<?php
/**
*
* @package phpBB Extension - Snitch EVE SSO auth
* @copyright (c) 2017 Snitch Ashor
* @translate by Jintaro Keo	
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
	'LOGIN_ERROR_EXTERNAL_AUTH_EVESSO' => 'Вы не можете зайти с Вашим аккаунтом EVE.',
	'LOGIN_AUTH_EVESSO' => 'Зайти через EVE SSO',
        'AUTHEVESSO_USER_EXISTS' => 'Имя пользователя уже существует, но не связано с аккаунтом EVE.',
        'ACP_WARNING' => 'Вы уже вошли в систему с учетной записью EVE, по соображениям безопасности, пожалуйста, выйдите из панели администратора, как только закончите.',
        'ACP_WARNING_OK' => 'Понятно!',
        'AUTHEVESSO_STATE_ERROR' => 'Ошибка: неверное состояние авторизации.',
        'ACP_AUTHEVESSO_TITLE' => 'EVE SSO авторизация',
        'ACP_AUTHEVESSO' => 'Настройки',
        'AUTHEVESSO_TSID' => 'Уникальный идентификатор TeamSpeak',
        'EVESSO_SETUP_BEFORE_USE' => 'Вы должны настроить аутентификацию EVE SSO перед тем, как переключить phpBB на этот метод аутентификации. Получить идентификатор клиента / код проверки можно <a href="https://developers.eveonline.com/">здесь</a>. При настройке приложения разработчика используйтеe \'<forum_url>/app.php/authevesso/login\'Как обратный URL-адрес и укажите требующиеся разрешения \'esi-corporations.read_corporation_membership.v1\'. Так же необходимо указать имя персонажа EVE, который станет администратором форума после смены метода авторизации, т.к. обычная запись администратора будет заблокирована.',

        'EVESSO' => 'EVE SSO авторизация',
        'EVE_SSO_AUTH' => 'EVE SSO авторизация',

        'EVESSO_CLIENTID' => 'Client ID',
        'EVESSO_CLIENTID_EXPLAIN' => 'Идентификатор клиента с developers.eveonline.com',

        'EVESSO_CODE' => 'App Code',
        'EVESSO_CODE_EXPLAIN' => 'Код проверки (Secret Key) c developers.eveonline.com',

        'EVESSO_ESI_UA' => 'User Agent',
        'EVESSO_ESI_UA_EXPLAIN' => 'Приложение при обмене данными с онлайн серверами SSO или API.',

        'EVESSO_ADMIN_USER' => 'Admin Character Name',
        'EVESSO_ADMIN_USER_EXPLAIN' => 'Имя персонажа администратора форума для предотвращения блокировки форума.',
));
