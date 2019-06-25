<?php

namespace snitch\authevesso\event;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class rank_listener implements EventSubscriberInterface
{
	/** @var template */
	protected $template;

	/** @var config */
	protected $config;

	/** @var driver_interface */
	protected $db;

	/** @var string */
	protected $root_path;

	/** @var string */
	protected $php_ext;

	/** @var array */
	private $users_corpdata;
        

	/**
	 * Constructor
	 *
	 * @param template			$template
	 * @param config			$config
	 * @param driver_interface		$db
	 * @param string			$root_path
	 * @param string			$php_ext
	 * @access public
	 */
	public function __construct(
		\phpbb\template\twig\twig $template,
		\phpbb\config\db $config,
		\phpbb\db\driver\factory $db,
		$php_ext,
		$root_path
	)
	{
		$this->template		= $template;
		$this->config		= $config;
		$this->db		= $db;
		$this->root_path	= $root_path;
		$this->php_ext		= $php_ext;
	}

    /**
     * Assign functions defined in this class to event listeners in the core
     *
     * @return array
     */
    static public function getSubscribedEvents()
    {
	return array(
		'core.memberlist_view_profile'		=> 'viewprofile',
		'core.viewtopic_modify_post_data'	=> 'viewtopic_fetch',
		'core.viewtopic_modify_post_row'	=> 'viewtopic_assign',
		'core.ucp_pm_view_messsage'		=> 'viewpm',
	);
    }

	public function viewtopic_fetch($event)
	{
		$userids = array();

		foreach ($event['rowset'] as $postrow)
		{
			$userids[] = $postrow['user_id'];
		}

		$this->users_corpdata = $this->get_corpdata($userids);
	}

	/**
	 * @param Event $event
	 */
	public function viewtopic_assign($event)
	{
		$userid = $event['poster_id'];
		$corpdata = $this->users_corpdata[$userid];
		$event['post_row'] = array_merge($event['post_row'], $corpdata);
	}


	/**
	 * @param Event $event
	 */
	public function viewprofile($event)
	{
		$userid = $event['member']['user_id']; 
                $corpdata = $this->get_corpdata(array($userid))[$userid];
		$this->template->assign_vars($corpdata);
	}

	/**
	 * @param Event $event
	 */
	public function viewpm($event)
	{
		$userid = $event['user_info']['user_id'];
                $corpdata = $this->get_corpdata(array($userid))[$userid];
                $this->template->assign_vars($corpdata);
	}

    /**
     * Load the Acme Demo language file
     *     acme/demo/language/en/demo.php
     *
     * @param \phpbb\event\data $event The event object
     */
    public function rank_addcorp($data, $posts, $ranks)
    {
        global $config, $db, $table_prefix;
    }

    private function get_corpdata($userids) {
        global $table_prefix;
        $templatedata = array();
        $sql = "SELECT pu.user_id, pam.* FROM ".USERS_TABLE." AS pu LEFT JOIN ".$table_prefix."authevesso_membership AS pam ON pu.user_characterID = pam.characterID WHERE pu.user_id = ".implode(' OR pu.user_id =', $userids);
        $result = $this->db->sql_query($sql);
        $userrows = $this->db->sql_fetchrowset($result);
        $this->db->sql_freeresult($result);
       	foreach ($userrows as $row)
        {
       		$templatedata[$row['user_id']] = array(
       			'CORP_IMG'	 => $row['corporationID'],
       			'CORP_NAME'	 => $row['corporationName'],
       			'ALLY_IMG'       => $row['allianceID'],
                        'ALLY_NAME'      => $row['allianceName'],
       		);
        }
        return $templatedata;
    }
}
