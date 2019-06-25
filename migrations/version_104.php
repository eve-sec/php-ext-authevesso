<?php

namespace snitch\authevesso\migrations;

class version_104 extends \phpbb\db\migration\migration
{
    /**
     * This migration depends on phpBB's v314 migration
     * already being installed.
     */
    static public function depends_on()
    {
        return array('\snitch\authevesso\migrations\version_101');
    }

    public function update_schema()
    {
        return array(
            'add_tables'    => array(
                $this->table_prefix . 'authevesso_membership' => array(
                    'COLUMNS' => array(
                        'characterID'       => array('BINT', 1),
                        'corporationID'     => array('BINT', NULL),
                        'corporationName'   => array('VCHAR_UNI:100', NULL),
                        'allianceID'        => array('BINT', NULL),
                        'allianceName'      => array('VCHAR_UNI:100', NULL),
                    ),
                    'PRIMARY_KEY' => 'characterID',
                    'KEYS' => array(
                        'id'            => array('INDEX', 'characterID'),
                    ),
                ),
            ),
        );
    }

    public function revert_schema()
    {
        return array(
            'drop_tables'    => array(
                $this->table_prefix . 'authevesso_membership',
            ),
        );
    }

}
