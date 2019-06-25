<?php

namespace snitch\authevesso\migrations;

class add_module_100 extends \phpbb\db\migration\migration
{
    /**
     * If our config variable already exists in the db
     * skip this migration.
     */
    public function effectively_installed()
    {
        return isset($this->config['snitch_authevesso_version']) && version_compare($this->config['snitch_authevesso_version'], '1.0.0', '>=');
    }

    /**
     * This migration depends on phpBB's v314 migration
     * already being installed.
     */
    static public function depends_on()
    {
        return array('\phpbb\db\migration\data\v31x\v314');
    }

    public function update_data()
    {
        return array(

            // Add the config variable we want to be able to set
            array('config.add', array('snitch_authevesso_version', '1.0.0')),
            array('config.add', array('snitch_authevesso_enablets', 0)),
            array('config.add', array('snitch_authevesso_existing', 0)),
            array('config.add', array('snitch_authevesso_avatarsize', 128)),
            array('config.add', array('groups_check_gc', 6*60*60)),
            array('config.add', array('snitch_authevesso_maxcron', 60)),
            array('config.add', array('snitch_authevesso_tsserver', 'localhost')),
	    array('config.add', array('snitch_authevesso_tsport', 9987)),
       	    array('config.add', array('snitch_authevesso_tsadmin', '')),
            array('config.add', array('snitch_authevesso_tspass', '')),
	    array('config.add', array('snitch_authevesso_admin_user', '')),
            array('config.add', array('snitch_authevesso_clientid', '')),
            array('config.add', array('snitch_authevesso_code', '')),
            array('config.add', array('groups_check_last_gc', '')),
            array('config.add', array('snitch_authevesso_esi_ua', 'phpbb_evesso '.generate_board_url())),


        	array('if', array(
			array('module.exists', array('acp','ACP_AUTHEVESSO_TITLE',array('module_basename' => '\snitch\authevesso\acp\main_module','modes' => array('settings')))),
			array('module.remove', array('acp','ACP_AUTHEVESSO_TITLE',array('module_basename' => '\snitch\authevesso\acp\main_module','modes' => array('settings')))),
		)),

                array('if', array(
                        array('module.exists', array('acp','ACP_CAT_DOT_MODS','ACP_AUTHEVESSO_TITLE')),
                        array('module.remove', array('acp','ACP_CAT_DOT_MODS','ACP_AUTHEVESSO_TITLE')),
                )),


            // Add a parent module (ACP_AUTHEVESSO_TITLE) to the Extensions tab (ACP_CAT_DOT_MODS)
            array('module.add', array(
                'acp',
                'ACP_CAT_DOT_MODS',
                'ACP_AUTHEVESSO_TITLE'
            )),

            // Add our main_module to the parent module (ACP_AUTHEVESSO_TITLE)
            array('module.add', array(
                'acp',
                'ACP_AUTHEVESSO_TITLE',
                array(
                    'module_basename'       => '\snitch\authevesso\acp\main_module',
                    'modes'                 => array('settings'),
                ),
            )),
        );
    }

    public function revert_data()
    {
        return array(

            // Add the config variable we want to be able to set
            array('config.remove', array('snitch_authevesso_version')),
            array('config.remove', array('snitch_authevesso_enablets')),
            array('config.remove', array('snitch_authevesso_existing')),
            array('config.remove', array('snitch_authevesso_avatarsize')),
            array('config.remove', array('groups_check_gc')),
            array('config.remove', array('snitch_authevesso_maxcron')),
            array('config.remove', array('snitch_authevesso_tsserver')),
            array('config.remove', array('snitch_authevesso_tsport')),
            array('config.remove', array('snitch_authevesso_tsadmin')),
            array('config.remove', array('snitch_authevesso_tspass')),
            array('config.remove', array('snitch_authevesso_admin_user')),
            array('config.remove', array('snitch_authevesso_clientid')),
            array('config.remove', array('snitch_authevesso_code')),
            array('config.remove', array('groups_check_last_gc')),
            array('config.remove', array('snitch_authevesso_esi_ua')),

            array('module.remove', array(
                'acp',
                'ACP_AUTHEVESSO_TITLE',
                array(
                    'module_basename'       => '\snitch\authevesso\acp\main_module',
                    'modes'                 => array('settings'),
                ),
            )),

            array('module.remove', array(
                'acp',
                'ACP_CAT_DOT_MODS',
                'ACP_AUTHEVESSO_TITLE'
            )),
        );
    }
}

