<?php

namespace snitch\authevesso\event;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;

require_once(realpath(dirname(__FILE__))."/../libraries/TeamSpeak3/TeamSpeak3.php");

class profile_listener implements EventSubscriberInterface
{
    /**
     * Assign functions defined in this class to event listeners in the core
     *
     * @return array
     */
    static public function getSubscribedEvents()
    {
        return array(
            'core.ucp_profile_info_modify_sql_ary' => 'check_tsgroups',
            'core.user_setup' => 'load_language_on_setup',
        );
    }


    /**
     * Load the Acme Demo language file
     *     acme/demo/language/en/demo.php
     *
     * @param \phpbb\event\data $event The event object
     */
    public function load_language_on_setup($event)
    {
        $lang_set_ext = $event['lang_set_ext'];
        $lang_set_ext[] = array(
            'ext_name' => 'snitch/authevesso',
            'lang_set' => 'common',
        );
        $event['lang_set_ext'] = $lang_set_ext;
    }

    /**
     * Load the Acme Demo language file
     *     acme/demo/language/en/demo.php
     *
     * @param \phpbb\event\data $event The event object
     */
    public function check_tsgroups($cp_data, $data, $sql_ary)
    {
        global $config, $user, $db, $table_prefix;
        if (!$config['snitch_authevesso_enablets']) {
            return;
        }
        $user->get_profile_fields($user->data['user_id']);
        $oldid = $user->profile_fields['pf_authevesso_tsid'];
        $newid = $cp_data->get_data()['cp_data']['pf_authevesso_tsid'];
        if (!empty($newid) && $oldid != $newid ) {
            // Only handle the TS groups that are set in the ACP.
            $sql = "SELECT ts_group FROM ".$table_prefix."authevesso_groups WHERE ts_group IS NOT NULL";
            $result = $db->sql_query($sql);
            $rows = $db->sql_fetchrowset($result);
            $db->sql_freeresult($result);
            if(count($rows)) {
               $handling = array_column($rows, 'ts_group');
            } else {
               $handling = array();
            }
            $sql = "SELECT ts_group FROM ".$table_prefix."authevesso_groups AS pag 
                    INNER JOIN ".$table_prefix."authevesso_membership AS pam ON (pag.id = pam.corporationID OR pag.id = pam.allianceID)
                    INNER JOIN ".USERS_TABLE." AS pu ON pu.user_characterID = pam.characterID
                    WHERE user_id = ".$user->data['user_id']." AND ts_group IS NOT NULL";
            $result = $db->sql_query($sql);
            $rows = $db->sql_fetchrowset($result);
            $db->sql_freeresult($result);
            if(count($rows)) {
               $ts3newgroups = array_intersect($handling, array_column($rows, 'ts_group'));
               try {
                   $ts3 = \TeamSpeak3::factory('serverquery://'.$config['snitch_authevesso_tsadmin'].':'.$config['snitch_authevesso_tspass'].'@'.$config['snitch_authevesso_tsserver'].':'.$config['snitch_authevesso_tsquery'].'/')->serverGetByPort($config['snitch_authevesso_tsport']);
                   $ts3client = $ts3->clientGetNameByUid($newid);
                   $ts3groups = array_intersect($handling, array_keys($ts3->clientGetServerGroupsByDbid($ts3client["cldbid"])));
               } catch (\Exception $e) {
                   trigger_error($user->lang('ERROR').': '.$e->getMessage().'<br /><a href="'.generate_board_url().'/ucp.php?i=ucp_profile&mode=profile_info">'.$user->lang('BACK_TO_PREV') .'</a>', E_USER_WARNING);
               }
               $toadd = array_diff($ts3newgroups, $ts3groups);
               $toremove = array_diff($ts3groups, $ts3newgroups);
               foreach($toadd as $add) {
                   $ts3->serverGroupClientAdd($add, $ts3client["cldbid"]);
               }
               foreach($toremove as $remove) {
                   $ts3->serverGroupClientDel($remove, $ts3client["cldbid"]);
               }
            }
        } 
    }
}
