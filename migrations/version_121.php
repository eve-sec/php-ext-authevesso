<?php

namespace snitch\authevesso\migrations;

class version_121 extends \phpbb\db\migration\migration
{
    /**
     * This migration depends on phpBB's v314 migration
     * already being installed.
     */
    static public function depends_on()
    {
        return array('\snitch\authevesso\migrations\version_120');
    }

    public function update_data()
    {
        return array(

            // Add the config variable we want to be able to set
            array('config.update', array('snitch_authevesso_version', '1.2.1')),
        );
    }
}
