<?php

namespace snitch\authevesso\acp;

use \ESIAPI;
use \EVEHELPERS;
use \Swagger\Client\Api\SearchApi;
use \Swagger\Client\Api\UniverseApi;


require_once(realpath(dirname(__FILE__))."/../loadclasses.php");
require_once(realpath(dirname(__FILE__))."/../libraries/TeamSpeak3/TeamSpeak3.php");

class main_module
{
    public $u_action;
    public $tpl_name;
    public $page_title;

    public function main($id, $mode)
    {
        if ($mode == 'scopes') {
            $this->scopes_page($id);
            return;
        } else if ($mode == 'logs') {
            $this->logs_page($id);
            return;
        }
        global $user, $template, $request, $config, $db, $table_prefix;

        $this->tpl_name = 'acp_authevesso_body';
        $this->page_title = $user->lang('ACP_AUTHEVESSO_TITLE');
        $user->add_lang_ext('snitch/authevesso', 'acp/board');
        add_form_key('snitch_authevesso_settings');

        if ($request->is_set_post('submit'))
        {
            if (!check_form_key('snitch_authevesso_settings'))
            {
                 trigger_error('FORM_INVALID');
            }
            $config->set('snitch_authevesso_enablets', $request->variable('snitch_authevesso_enablets', 0));
            $config->set('snitch_authevesso_existing', $request->variable('snitch_authevesso_existing', 0));
            $config->set('snitch_authevesso_req_corp', $request->variable('snitch_authevesso_req_corp', 0));
            $config->set('snitch_authevesso_req_validsso', $request->variable('snitch_authevesso_req_validsso', 0));
            $config->set('snitch_authevesso_avatarsize', $request->variable('snitch_authevesso_avatarsize', 128));
            $config->set('groups_check_gc', $request->variable('snitch_authevesso_croninterval', 6)*60*60);
            $config->set('snitch_authevesso_maxcron', $request->variable('snitch_authevesso_maxcron', 60));
            $config->set('snitch_authevesso_tsserver', $request->variable('snitch_authevesso_tsserver', 'localhost'));
            $config->set('snitch_authevesso_tsport', $request->variable('snitch_authevesso_tsport', 9987));
            $config->set('snitch_authevesso_tsadmin', $request->variable('snitch_authevesso_tsadmin', ''));
            $config->set('snitch_authevesso_tspass', $request->variable('snitch_authevesso_tspass', ''));
            $config->set('snitch_authevesso_tsquery', $request->variable('snitch_authevesso_tsquery', 10011));
            $sql_ary = array();
            $mappings = $request->variable('groups', array('' => array('' => '')));
            foreach ($mappings as $map) {
                if (!empty($map['name']) && !empty($map['type']) && !empty($map['forum_group'])) {
                    if (empty($map['id'])) {
                        $esiapi = new ESIAPI();
                        $searchapi = $esiapi->getApi('Search');
                        $result = json_decode($searchapi->getSearch(array($map['type']), $map['name'], 'en-us', 'tranquility', null, 'en-us', true), true);
                        if (!isset($result[$map['type']]) || count($result[$map['type']]) == 0 ) {
                            trigger_error($user->lang('ACP_AUTHEVESSO_COULDNT_FIND').' '.$map['type'].' '.$map['name'] . adm_back_link($this->u_action), E_USER_WARNING);
                        } elseif (count($result[$map['type']]) == 1 ) {
                            $map['id'] = $result[$map['type']][0];
                        } else {
                            $results = EVEHELPERS::esiIdsLookup($result[$map['type']]);
                            foreach ($results as $i => $r) {
                                if ($r['name'] == $map['name']) {
                                    $map['id'] = $i;
                                }
                            }
                            if (!isset($map['id']) || empty($map['id']) ) {
                                trigger_error($user->lang('ACP_AUTHEVESSO_COULDNT_FIND').' '.$map['type'].' '.$map['name'] . adm_back_link($this->u_action), E_USER_WARNING);
                            }
                        }
                    }
                    if (empty($map['ts_group']) || $map['ts_group'] == '') {
                        unset($map['ts_group']);
                    }
                    $sql_ary[] = $map;
                } 
            }
            $sql = 'TRUNCATE TABLE '.$table_prefix.'authevesso_groups';
            $db->sql_query($sql);
            foreach ($sql_ary as $sql_arr) {
                $sql = 'INSERT INTO '.$table_prefix.'authevesso_groups ' . $db->sql_build_array('INSERT', $sql_arr);
                $db->sql_query($sql);
            }
            trigger_error($user->lang('ACP_AUTHEVESSO_SETTING_SAVED') . adm_back_link($this->u_action));
        }
        if ($request->is_set_post('addrow'))
        {
            if (!check_form_key('snitch_authevesso_settings'))
            {
                 trigger_error('FORM_INVALID', E_USER_WARNING);
            }
            $mappings = $request->variable('groups', array('' => array('' => '')));
        }
        if (!empty($config['snitch_authevesso_tsadmin']) && !empty($config['snitch_authevesso_tspass'])) {
            try {
                $ts3 = \TeamSpeak3::factory('serverquery://'.$config['snitch_authevesso_tsadmin'].':'.$config['snitch_authevesso_tspass'].'@'.$config['snitch_authevesso_tsserver'].':'.$config['snitch_authevesso_tsquery'].'/')->serverGetByPort($config['snitch_authevesso_tsport']);
                $ts3groups_ary = $ts3->serverGroupList();
                $ts3groups = array();
                $template->assign_vars(array(
                    'SNITCH_AUTHEVESSO_TSERROR' => 0,
                    'SNITCH_AUTHEVESSO_TSERRORMSG' => '',
                ));
            } catch (\Exception $e) {
                $ts3groups_ary = array();
                $template->assign_vars(array(
                    'SNITCH_AUTHEVESSO_TSERROR' => 1,
                    'SNITCH_AUTHEVESSO_TSERRORMSG' => $e->getMessage(),
                ));
            }
        } else {
            $ts3groups_ary = array();
        }
        if (!isset($mappings) || !count($mappings)) {
            $sql = "SELECT * FROM ".$table_prefix."authevesso_groups";
            $result = $db->sql_query($sql);
            $mappings = $db->sql_fetchrowset($result);
            $db->sql_freeresult($result);
        }
        $mappings[] = array('id'=>null, 'name'=>null, 'type'=>null, 'forum_group'=>null, 'ts_group'=>null);
        $sql = "SELECT group_id,group_name FROM " . GROUPS_TABLE;
        $result = $db->sql_query($sql);
        $groups = $db->sql_fetchrowset($result);
        $db->sql_freeresult($result);
        foreach($mappings as $i => $map) {
            $template->assign_block_vars('groups', array(
                'NUM'       => $i,
                'ID'        => $map['id'],
                'NAME'      => $map['name'],
                'TYPE'      => $map['type'],
                'FORUM_GROUP' => $map['forum_group'],
                'TS_GROUP' => $map['ts_group'],
            ));
            foreach($ts3groups_ary as $ts3g) {
                $ts3groups[$ts3g->getId()] = (string)$ts3g;
                $template->assign_block_vars('groups.ts3groups', array(
                    'ITEM'        => (string)$ts3g,
                    'ITEM_NUM'    => $ts3g->getId(),
                ));
            }
            foreach($groups as $group) {
                $template->assign_block_vars('groups.forumgroups', array(
                    'ITEM'        => $group['group_name'],
                    'ITEM_NUM'    => $group['group_id'],
                ));
            }
        }
        $template->assign_vars(array(
            'SNITCH_AUTHEVESSO_ENABLETS' => $config['snitch_authevesso_enablets'],
            'SNITCH_AUTHEVESSO_EXISTING' => $config['snitch_authevesso_existing'],
            'SNITCH_AUTHEVESSO_REQ_CORP' => $config['snitch_authevesso_req_corp'],
            'SNITCH_AUTHEVESSO_REQ_VALIDSSO' => $config['snitch_authevesso_req_validsso'],
            'SNITCH_AUTHEVESSO_AVATARSIZE' => $config['snitch_authevesso_avatarsize'],
            'SNITCH_AUTHEVESSO_CRONINTERVAL' => (int)round($config['groups_check_gc']/(60*60)),
            'SNITCH_AUTHEVESSO_MAXCRON' => $config['snitch_authevesso_maxcron'],
            'SNITCH_AUTHEVESSO_TSSERVER' => $config['snitch_authevesso_tsserver'],
            'SNITCH_AUTHEVESSO_TSPORT' => $config['snitch_authevesso_tsport'],
            'SNITCH_AUTHEVESSO_TSADMIN' => $config['snitch_authevesso_tsadmin'],
            'SNITCH_AUTHEVESSO_TSPASS' => $config['snitch_authevesso_tspass'],
            'SNITCH_AUTHEVESSO_TSQUERY' => $config['snitch_authevesso_tsquery'],
            'U_ACTION'          => $this->u_action,
        ));
    }

    public function scopes_page($id)
    {
        global $user, $template, $request, $config, $db, $table_prefix;

        $this->tpl_name = 'acp_authevesso_scopes';
        $this->page_title = $user->lang('ACP_AUTHEVESSO_SCOPES');
        $user->add_lang_ext('snitch/authevesso', 'acp/board');
        add_form_key('snitch_authevesso_scopes');

        $all_scopes = unserialize(EVESSO_SCOPES); 

        if ($request->is_set_post('submit'))
        {
            if (!check_form_key('snitch_authevesso_scopes'))
            {
                 trigger_error('FORM_INVALID');
            }
            $req_scopes = $request->variable('scopes', array('' => ''));
            $arr = [];
            foreach($req_scopes as $s => $v) {
                if ($v) {
                    foreach($all_scopes as $i => $scope) {
                        ($scope == $s ? $arr[] = $i:'');
                    }
                }
            }
            (!count($arr)?$arr[]=0:'');
            $config->set('snitch_authevesso_scopes', json_encode($arr));
        }

        $scopes = json_decode($config['snitch_authevesso_scopes'], true);
        foreach($all_scopes as $i => $scope) {
            $template->assign_block_vars('scopes', array(
                'ITEM'        => $scope,
                'ITEM_NUM'    => (in_array($i, $scopes)?1:0),
            ));
        }
        $template->assign_vars(array(
            'U_ACTION'          => $this->u_action,
        ));

    }

    public function logs_page($id)
    {
        global $user, $template, $request, $config, $db, $table_prefix;

        $this->tpl_name = 'acp_authevesso_logs';
        $this->page_title = $user->lang('ACP_AUTHEVESSO_LOGS');
        $user->add_lang_ext('snitch/authevesso', 'acp/board');
        $user->add_lang_ext('snitch/authevesso', 'acp/logs');

        $sql = "SELECT * FROM ".$table_prefix."authevesso_logs as l LEFT JOIN ".USERS_TABLE." as u ON l.user_id = u.user_id ORDER BY log_id DESC LIMIT 50;";
        $result = $db->sql_query($sql);
        $entries = $db->sql_fetchrowset($result);
        $db->sql_freeresult($result);
        foreach($entries as $e) {
            $template->assign_block_vars('logs', array(
                'TIME'        => $user->format_date($e['log_time']),
                'USERNAME'    => $e['username'],
                'COMMENT'     => $user->lang($e['comment']),
            ));
        }
    }

}
