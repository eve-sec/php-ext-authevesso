<?php

namespace snitch\authevesso\migrations;

class version_101 extends \phpbb\db\migration\migration
{
    public function effectively_installed()
    {
        return isset($this->config['snitch_authevesso_version']) && version_compare($this->config['snitch_authevesso_version'], '1.0.1', '>=');
    }

    static public function depends_on()
    {
        return array('\snitch\authevesso\migrations\add_module_100',
                     '\snitch\authevesso\migrations\add_table_100',
                     '\snitch\authevesso\migrations\profilefield_tsid_100');
    }

    public function update_data()
    {
        return array(
            array('config.update', array('snitch_authevesso_version', '1.0.1')),
        );
    }
}

