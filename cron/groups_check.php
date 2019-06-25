<?php
/**
*
* EVE Auth SSO extension for the phpBB Forum Software package.
*
* @copyright (c) 2017 Snitch Ashor
* @license GNU General Public License, version 2 (GPL-2.0)
*
*/
namespace snitch\authevesso\cron;

use \EVEHELPERS;
use \ESIAPI;
use \snitch\authevesso\ESISSO;
use \snitch\authevesso\AUTHEVESSO_LOG;
use \snitch\authevesso\EVESSO_SCOPES;

require_once(realpath(dirname(__FILE__))."/../loadclasses.php");
require_once(realpath(dirname(__FILE__))."/../libraries/TeamSpeak3/TeamSpeak3.php");

/**
 * Group check cron task.
 */
class groups_check extends \phpbb\cron\task\base
{
	/** @var \phpbb\config\config */
	protected $config;
        protected $cache;
        protected $auth;
        protected $log;
	/**
	 * Constructor
	 *
	 * @param \phpbb\config\config                 $config  Config object
	 * @access public
	 */
	public function __construct(\phpbb\config\config $config, \phpbb\cache\driver\driver_interface $cache, \phpbb\auth\auth $auth)
	{
		$this->config = $config;
                $this->cache = $cache;
                $this->auth = $auth;
                $this->log = new AUTHEVESSO_LOG();
	}
	/**
	 * Runs this cron task.
	 *
	 * @return void
	 */
	public function run()
	{
                $starttime = time();
                $this->log->add('CRON_STARTED');
                $esiapi = new ESIAPI();
                if (!$esiapi->checkTQ()) {
                    $this->log->add('CRON_SERVERSTATUS_FAILED');
                    return;
                }
                global $db, $table_prefix;
                $abort = false;

                //default group id
                $sql = sprintf('SELECT group_id FROM %1$s WHERE group_name = \'%2$s\' AND group_type = \'%3$s\'', GROUPS_TABLE, $db->sql_escape('REGISTERED'), GROUP_SPECIAL);
                $result = $db->sql_query($sql);
                $row = $db->sql_fetchrow($result);
                $defgroup = (int)$row['group_id'];

                $maxtime = $this->config['snitch_authevesso_maxcron'];
                $sql = "SELECT ts_group FROM ".$table_prefix."authevesso_groups WHERE ts_group IS NOT NULL";
                $result = $db->sql_query($sql);
                $rows = $db->sql_fetchrowset($result);
                $db->sql_freeresult($result);
                if(count($rows)) {
                    $tshandling = array_unique(array_column($rows, 'ts_group'));
                } else {
                    $tshandling = array();
                }
                $sql = "SELECT forum_group FROM ".$table_prefix."authevesso_groups WHERE forum_group IS NOT NULL";
                $result = $db->sql_query($sql);
                $rows = $db->sql_fetchrowset($result);
                $db->sql_freeresult($result);
                if(count($rows)) {
                    $fhandling = array_unique(array_column($rows, 'forum_group'));
                } else {
                    $fhandling = array();
                }
                if(!count($tshandling) && !count($fhandling)) {
                    $this->config->set('groups_check_last_gc', time());
                    return;
                }
                $sql = "SELECT user_id, user_refreshToken, user_characterID, user_type, group_id FROM ".USERS_TABLE." WHERE user_characterID IS NOT NULL AND (user_lastAPI < ".(time()-$this->config['groups_check_gc'])." OR user_lastAPI IS NULL) ORDER BY user_lastAPI ASC;";
                $result = $db->sql_query($sql);
                $dbusers = $db->sql_fetchrowset($result);
                $db->sql_freeresult($result);
                //update groups first
                foreach ($dbusers as $dbu) {
                    //get current groups
                    if ($this->config['snitch_authevesso_req_validsso'] && $dbu['user_type'] == USER_NORMAL) {
                        $esisso = new ESISSO($dbu['user_id']);
                        if(!$esisso->verify()) {
                            $sql = "UPDATE ".USERS_TABLE." SET user_type = ".USER_INACTIVE.", user_inactive_time = ".time().", user_inactive_reason = ".INACTIVE_REMIND." WHERE user_id = ".$dbu['user_id'];
                            $result = $db->sql_query($sql);
                            $this->log->add('CRON_USER_DISABLED_INVALID', $dbu['user_id'], $dbu['user_characterID']);
                        } else if ( count(array_diff(array_intersect_key(unserialize(EVESSO_SCOPES), array_flip(json_decode($this->config['snitch_authevesso_scopes'], true))), (array)$esisso->getScopes()) ) ) {
                            $sql = "UPDATE ".USERS_TABLE." SET user_type = ".USER_INACTIVE.", user_inactive_time = ".time().", user_inactive_reason = ".INACTIVE_REMIND." WHERE user_id = ".$dbu['user_id'];
                            $result = $db->sql_query($sql);
                            $this->log->add('CRON_USER_DISABLED_SCOPES', $dbu['user_id'], $dbu['user_characterID']);
                        }
                    }
                    $sql = "SELECT group_id FROM ".USER_GROUP_TABLE." WHERE user_id = ".$dbu['user_id'];
                    $result = $db->sql_query($sql);
                    $rows = $db->sql_fetchrowset($result);
                    $db->sql_freeresult($result);
                    if(count($rows)) {
                        $fgroups = array_intersect($fhandling, array_column($rows, 'group_id'));
                    } else {
                        $fgroups = array();
                    }
                    $corpID = EVEHELPERS::getCorpForChar($dbu['user_characterID']);
                    //no corpid means api call failed
                    if ($corpID == null) {
                        $this->markRun($dbu['user_id']);
                        continue;
                    }

                    //updata membership table
                    $corpinfo = EVEHELPERS::getCorpInfo($corpID);
                    $corpName = $corpinfo->getName();
                    $membership = array('characterID' => $dbu['user_characterID'], 'corporationID' => $corpID, 'corporationName' => $corpName);
                    if ($corpinfo->getAllianceId()) {
                        $allyID = $corpinfo->getAllianceId();
                        $allyName = EVEHELPERS::getAllyInfo($allyID)->getName();
                        $membership['allianceID'] = $allyID;
                        $membership['allianceName'] = $allyName;
                    } else {
                        $allyID = null;
                    }
                    $sql = "REPLACE INTO ".$table_prefix."authevesso_membership " . $db->sql_build_array('INSERT', $membership);
                    $db->sql_query($sql);

                    //get groups user should be in
                    $sql = "SELECT forum_group, type FROM ".$table_prefix."authevesso_groups WHERE (type = 'corporation' AND id = ".$corpID.")";
                    if ($allyID != null) {
                        $sql .= " OR (type = 'alliance' AND id = ".$allyID.")";
                    }
                    $result = $db->sql_query($sql);
                    $rows = $db->sql_fetchrowset($result);
                    $db->sql_freeresult($result);
                    if(count($rows)) {
                        $newfgroups = array_intersect($fhandling, array_column($rows, 'forum_group'));
                    } else {
                        $newfgroups = array();
                    }
                    $newdefgroup = null;
                    foreach ($rows as $row) {
                        if ($row['type'] == 'corporation') {
                            $newdefgroup = $row['forum_group'];
                        }
                    }
                    if (!$newdefgroup) {
                        if(count($rows)) {
                            $newdefgroup = $rows[0]['forum_group'];
                        } else {
                            $newdefgroup = $defgroup;
                        }
                    }
                    $toadd = array_diff($newfgroups, $fgroups);
                    $toremove = array_diff($fgroups, $newfgroups);
                    foreach ($toadd as $add) {
                        $sql = "REPLACE INTO ".USER_GROUP_TABLE." (group_id, group_leader, user_id, user_pending) VALUES (".$add.", 0, ".$dbu['user_id'].", 0)";
                        $db->sql_query($sql);
                    }
                    foreach ($toremove as $remove) {
                        $sql = "DELETE FROM ".USER_GROUP_TABLE." WHERE group_id = ".$remove." AND user_id = ".$dbu['user_id'];
                        $db->sql_query($sql);
                    }
                    if ($this->config['snitch_authevesso_req_corp'] && !count($newfgroups) && $dbu['user_type'] == USER_NORMAL) {
                            $sql = "UPDATE ".USERS_TABLE." SET user_type = ".USER_INACTIVE.", user_inactive_time = ".time().", user_inactive_reason = ".INACTIVE_REMIND." WHERE user_id = ".$dbu['user_id'];
                            $result = $db->sql_query($sql);
                            $this->log->add('CRON_USER_DISABLED_CORP', $dbu['user_id'], $dbu['user_characterID']);
                    }
                    if ($newdefgroup != $dbu['group_id']) {
                            $sql = "UPDATE ".USERS_TABLE." SET group_id = ".$newdefgroup." WHERE user_id = ".$dbu['user_id'];
                            $result = $db->sql_query($sql);
                    }
                    $this->markRun($dbu['user_id']);
                    $this->clearCache();
                    if ((time() - $starttime) > ($maxtime - 5)) {
                        //Only 5 seconds left, lets take care of TS
                        $abort = true;
                        break;
                    }
                }
                if ($this->config['snitch_authevesso_enablets'] && count($tshandling)) {
                    try {
                        $ts3 = \TeamSpeak3::factory('serverquery://'.$config['snitch_authevesso_tsadmin'].':'.$config['snitch_authevesso_tspass'].'@'.$config['snitch_authevesso_tsserver'].':'.$config['snitch_authevesso_tsquery'].'/')->serverGetByPort($config['snitch_authevesso_tsport']);
                        $handlets = true;
                    } catch (\Exception $e) {
                        $handlets = false;
                    }
                } else {
                    $handlets = false;
                }
                if ($handlets) {
                    foreach ($tshandling as $gid) {
                        //who is supposed o be in that group
                        $sql = "SELECT pf_authevesso_tsid AS uid FROM ".PROFILE_FIELDS_DATA_TABLE." AS ppfa
                                INNER JOIN ".USERS_TABLE." AS pu ON ppfa.user_id = pu.user_id
                                INNER JOIN ".$table_prefix."authevesso_membership AS pam ON pu.user_characterID = pam.characterID
                                INNER JOIN ".$table_prefix."authevesso_groups AS pag ON (pag.id = pam.corporationID OR pag.id = pam.allianceID)
                                WHERE pf_authevesso_tsid IS NOT NULL and pag.ts_group = ".$gid;
                        $result = $db->sql_query($sql);
                        $rows = $db->sql_fetchrowset($result);
                        $db->sql_freeresult($result);
                        if(count($rows)) {
                            $members = array_unique(array_column($rows, 'uid', 'uid'));
                        } else {
                            $members = array();
                        }
                        $tsgmembers = $ts3->serverGroupClientList($gid);
                        foreach ($tsgmembers as $tsgm) {
                            $uid = (string)$tsgm['client_unique_identifier'];
                            if (isset($members[$uid])) {
                                unset($members[$uid]);
                            } else {
                                $ts3->serverGroupClientDel($gid, $tsgm['cldbid']);
                            }
                        }
                        foreach($members as $newmember) {
                            try {
                                $ts3client = $ts3->clientGetNameByUid($newmember);
                                $ts3->serverGroupClientAdd($gid, $ts3client["cldbid"]);
                            } catch (\Exception $e) {
                                continue;
                            }
                        }
                    }
                }
                //Only mark as run when all users were finished
                if (!$abort) {
		    $this->config->set('groups_check_last_gc', time());
                }
                $this->log->add('CRON_FINISHED');
	}

        private function markRun($userid)
        {
            global $db;
            $sql = "UPDATE ".USERS_TABLE." SET user_lastAPI = ".time()." WHERE user_id = ".$userid;
            $db->sql_query($sql);
        }

        private function clearCache()
        {
            global $table_prefix;
            $this->cache->destroy('sql', GROUPS_TABLE);
            $this->cache->destroy('sql', USERS_TABLE);
            $this->cache->destroy('sql', USER_GROUP_TABLE);
            $this->cache->destroy('sql', $table_prefix."authevesso_membership");
            $this->auth->acl_clear_prefetch();
        }
	/**
	 * Returns whether this cron task can run, given current board configuration.
	 *
	 * If warnings are set to never expire, this cron task will not run.
	 *
	 * @return bool
	 */
	public function is_runnable()
	{
		return true;
	}
	/**
	 * Returns whether this cron task should run now, because enough time
	 * has passed since it was last run (6 hours).
	 *
	 * @return bool
	 */
	public function should_run()
	{
            return $this->config['groups_check_last_gc'] < time() - $this->config['groups_check_gc'];
	}
}
