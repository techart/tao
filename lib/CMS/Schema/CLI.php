<?php

class CMS_Schema_CLI implements Core_ModuleInterface
{
	static function run()
	{
		CMS_Fields::process_schema('tao_cli_calls', self::fields());
	}

	static function fields()
	{
		return array(
			'name' => array(
				'sqltype' => 'varchar(250) index',
			),
			'time' => array(
				'sqltype' => 'int index',
			),
		);
	}
}