<?php

namespace snitch\authevesso\migrations;

class version_120 extends \phpbb\db\migration\migration
{
    /**
     * This migration depends on phpBB's v314 migration
     * already being installed.
     */
    static public function depends_on()
    {
        return array('\snitch\authevesso\migrations\version_104');
    }

    public function update_schema()
    {
        return array(
            'add_tables'    => array(
                $this->table_prefix . 'authevesso_logs' => array(
                    'COLUMNS' => array(
                        'log_id'            => array('UINT', NULL, 'auto_increment'),
                        'log_time'          => array('UINT:11', NULL),
                        'user_id'           => array('UINT', NULL),
                        'characterID'       => array('BINT', NULL),
                        'comment'           => array('VCHAR:100', ''),
                    ),
                    'PRIMARY_KEY' => 'log_id',
                    'KEYS' => array(
                        'id'            => array('INDEX', 'log_id'),
                    ),
                ),
            ),
        );
    }


    public function update_data()
    {
        return array(

            // Add the config variable we want to be able to set
            array('config.update', array('snitch_authevesso_version', '1.2.0')),
            array('config.add', array('snitch_authevesso_req_corp', false)),
            array('config.add', array('snitch_authevesso_req_validsso', false)),
            array('config.add', array('snitch_authevesso_scopes', '[4]')),

            array('module.add', array(
                'acp',
                'ACP_AUTHEVESSO_TITLE',
                array(
                    'module_basename'       => '\snitch\authevesso\acp\main_module',
                    'modes'                 => array('scopes'),
                ),
            )),
            array('module.add', array(
                'acp',
                'ACP_AUTHEVESSO_TITLE',
                array(
                    'module_basename'       => '\snitch\authevesso\acp\main_module',
                    'modes'                 => array('logs'),
                ),
            )),
        );
    }
}
