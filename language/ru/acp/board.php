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
	'EVESSO' => 'EVE SSO auth',

	'EVESSO_CLIENTID' => 'Client ID',
	'EVESSO_CLIENTID_EXPLAIN' => 'Идентификатор клиента с developers.eveonline.com',

	'EVESSO_CODE' => 'App Code',
	'EVESSO_CODE_EXPLAIN' => 'Код проверки (Secret Key) c developers.eveonline.com',

	'EVESSO_ESI_UA' => 'User Agent',
	'EVESSO_ESI_UA_EXPLAIN' => 'Приложение при обмене данными с онлайн серверами SSO или API.',

	'EVESSO_ADMIN_USER' => 'Admin Character Name',
	'EVESSO_ADMIN_USER_EXPLAIN' => 'Имя персонажа администратора форума для предотвращения блокировки форума.',

        'ACP_AUTHEVESSO_GENERALSETTINGS' => 'Основные настройки',
        'ACP_AUTHEVESSO_AVATARSIZE' => 'Размер аватара',
        'ACP_AUTHEVESSO_CRONINTERVAL' => 'Cronjob интервал в часах',
        'ACP_AUTHEVESSO_MAXCRON' => 'Максимальное время cronjob в секундах',
        'ACP_AUTHEVESSO_ENABLETS' => 'Включить интеграцию с Teamspeak?',
        'ACP_AUTHEVESSO_EXISTING' => 'Позволить объединение с уже имеющимися аккаунтами?',
        'ACP_AUTHEVESSO_EXISTING_EXPLAIN' => 'Возможная проблема безопасности, не работает для администраторов/основателей.',
        'ACP_AUTHEVESSO_TSSERVERSETTINGS' => 'Настройки Teamspeak',
        'ACP_AUTHEVESSO_TSSERVER' => 'Сервер Teamspeak',
        'ACP_AUTHEVESSO_TSPORT' => 'Порт Teamspeak',
        'ACP_AUTHEVESSO_TSADMIN' => 'Администратор Teamspeak',
        'ACP_AUTHEVESSO_TSPASS' => 'Пароль администратора Teamspeak',
        'ACP_AUTHEVESSO_SETTING_SAVED' => 'Настройки успешно сохранены!',
        'ACP_AUTHEVESSO_TS3MAPPINGS' => 'Настройки групп Teamspeak',
        'ACP_AUTHEVESSO_NAME' => 'Название корпорации/альянса',
        'ACP_AUTHEVESSO_TYPE' => 'Тип данных',
        'ACP_AUTHEVESSO_CORP' => 'Корпорация',
        'ACP_AUTHEVESSO_ALLI' => 'Альянс',
        'ACP_AUTHEVESSO_FGROUP' => 'Назначить группу форума',
        'ACP_AUTHEVESSO_TSGROUP' => 'Назначить группу Teamspeak',
        'ACP_AUTHEVESSO_ADDROW' => 'Добавить строку',
        'ACP_AUTHEVESSO_COULDNT_FIND' => 'Не получается найти ID для',

));
