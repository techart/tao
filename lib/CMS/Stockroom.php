<?php

/**
 * @package CMS\Stockroom
 */
class CMS_Stockroom implements Core_ModuleInterface
{
	static $repositories = array(
		'tao' => array(
			'url' => 'http://cms.techart.ru/components/',
			'title' => 'Текарт',
		),
	);

	static $curl_options = array(
		CURLOPT_TIMEOUT => 5,
	);

	static $disabled = false;
	static $name = 'CMSStockroom';
	static $admin_menu_title = 'Библиотека компонентов';
	static $info_dir = '../app/components/.info';

	static function initialize($config = array())
	{
		foreach ($config as $key => $value)
			self::$$key = $value;
		if (self::$disabled) {
			return;
		}
		CMS::add_component(self::$name, new CMS_Stockroom_Router());
		CMS_Admin::menu(self::$admin_menu_title, CMS::admin_path('stockroom'), 'bookshelf.png');
	}
}

class CMS_Stockroom_Router extends CMS_Router
{
	public function controllers()
	{
		return array(
			'CMS.Controller.Stockroom' => array(
				'path' => '{admin:stockroom}',
				'table-admin' => true,
			),
		);
	}
}

