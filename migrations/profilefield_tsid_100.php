<?php

namespace snitch\authevesso\migrations;

class profilefield_tsid_100 extends \phpbb\db\migration\profilefield_base_migration
{
	static public function depends_on()
	{
		return array(
			'\phpbb\db\migration\data\v310\profilefield_types',
		);
	}

	public function convert_user_field_to_custom_field($start)
	{
        }

	protected $profilefield_name = 'authevesso_tsid';
	protected $profilefield_database_type = array('VCHAR', '');
	protected $profilefield_data = array(
		'field_name'			=> 'authevesso_tsid',
		'field_type'			=> 'profilefields.type.string',
		'field_ident'			=> 'authevesso_tsid',
		'field_length'			=> '20',
		'field_minlen'			=> '2',
		'field_maxlen'			=> '100',
		'field_novalue'			=> '',
		'field_default_value'		=> '',
		'field_validation'		=> '.*',
		'field_required'		=> 0,
		'field_show_novalue'		=> 0,
		'field_show_on_reg'		=> 0,
		'field_show_on_pm'		=> 0,
		'field_show_on_vt'		=> 0,
		'field_show_profile'		=> 1,
		'field_hide'			=> 1,
		'field_no_view'			=> 1,
		'field_active'			=> 1,
	);
	//protected $user_column_name = 'user_tsid';
}
