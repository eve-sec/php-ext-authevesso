<?php

namespace snitch\authevesso\migrations;

class version_124 extends \phpbb\db\migration\migration
{
    /**
     * This migration depends on phpBB's v314 migration
     * already being installed.
     */
    static public function depends_on()
    {
        return array('\snitch\authevesso\migrations\version_122');
    }

    public function update_data()
    {
        return array(

            // Add the config variable we want to be able to set
            array('config.update', array('snitch_authevesso_version', '1.2.4')),
            array('config.add', array('snitch_authevesso_tsquery', 10011)),
        );
    }
}
