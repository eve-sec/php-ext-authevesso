<?php

namespace snitch\authevesso\acp;

class main_info
{
    public function module()
    {
        return array(
            'filename'  => '\snitch\authevesso\acp\main_module',
            'title'     => 'ACP_AUTHEVESSO_TITLE',
            'modes'    => array(
                'settings'  => array(
                    'title' => 'ACP_AUTHEVESSO',
                    'auth'  => 'ext_snitch/authevesso && acl_a_user',
                    'cat'   => array('ACP_AUTHEVESSO_TITLE'),
                ),
                'scopes'  => array(
                    'title' => 'ACP_AUTHEVESSO_SCOPES',
                    'auth'  => 'ext_snitch/authevesso && acl_a_user',
                    'cat'   => array('ACP_AUTHEVESSO_TITLE'),
                ),
                'logs'  => array(
                    'title' => 'ACP_AUTHEVESSO_LOGS',
                    'auth'  => 'ext_snitch/authevesso && acl_a_user',
                    'cat'   => array('ACP_AUTHEVESSO_TITLE'),
                ),
            ),
        );
    }
}

