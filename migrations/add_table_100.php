<?php

namespace snitch\authevesso\migrations;

class add_table_100 extends \phpbb\db\migration\migration
{
    /**
     * This migration depends on phpBB's v314 migration
     * already being installed.
     */
    static public function depends_on()
    {
        return array('\snitch\authevesso\migrations\add_module_100');
    }

    public function update_schema()
    {
        return array(
            'add_columns'        => array(
                $this->table_prefix . 'users'        => array(
                    'user_refreshToken'    => array('VCHAR:255', NULL),
                    'user_characterID'    => array('BINT', NULL),
                    'user_lastAPI' => array('TIMESTAMP', NULL),
                    'user_APIfailcount' => array('UINT', NULL),
                ),
                $this->table_prefix . 'sessions'        => array(
                    'session_authstate'    => array('VCHAR:255', NULL),
                    'session_refreshToken'    => array('VCHAR:255', NULL),
                    'session_characterID'    => array('BINT', NULL),
                    'session_characterName'    => array('VCHAR:100', NULL),
                ),
            ),

            'add_tables'    => array(
                $this->table_prefix . 'authevesso_groups' => array(
                    'COLUMNS' => array(
                        'id'                => array('BINT', NULL),
                        'name'              => array('VCHAR_UNI:255', ''),
                        'type'              => array('VCHAR:20', ''),
                        'forum_group'       => array('UINT', NULL),
                        'ts_group'          => array('UINT', NULL),
                    ),
                ),
            ),
        );
    }

    public function revert_schema()
    {
        return array(
            'drop_columns'        => array(
                $this->table_prefix . 'users'        => array(
                    'user_refreshToken',
                    'user_characterID',
                    'user_lastAPI',
                    'user_APIfailcount',
                ),
                $this->table_prefix . 'sessions'        => array(
                    'session_authstate',
                    'session_refreshToken',
                    'session_characterID',
                    'session_characterName',
                ),
            ),

            'drop_tables'    => array(
                $this->table_prefix . 'authevesso_groups',
            ),
        );
    }

}
