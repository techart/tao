<?php

/**
 * @package CMS\Vars2\Utils
 */
class CMS_Vars2_Utils implements Core_ModuleInterface
{
	const VERSION = '0.0.0';

	static function config_fields()
	{
		return array(
			'_title' => array(
				'type' => 'input',
				'caption' => 'Наименование',
				'style' => 'width:100%',
				'tab' => '_config',
				'default' => '{name}',
			),
			'_access' => array(
				'type' => 'input',
				'caption' => 'Доступ',
				'style' => 'width:200px',
				'tab' => '_config',
				'default' => '',
			),
		);
	}
}