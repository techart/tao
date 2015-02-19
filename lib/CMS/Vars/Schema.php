<?php
/**
 * @package CMS\Vars\Schema
 */

Core::load('DB.Schema');

class CMS_Vars_Schema implements Core_ModuleInterface
{

	const VERSION = '0.1.0';

	protected static $schema = array();
	protected static $init_data = array();
	protected static $options = array('insert_init_data' => true);

	public static function initialize(array $options = array())
	{
		self::$schema = self::default_schema();
		self::$init_data = self::default_init_data();
		self::options($options);
	}

	//TODO: testing
	public function options(array $options = array())
	{
		if (!empty($options['schema'])) {
			self::$schema = array_merge_recursive(self::$schema, $options['schema']);
		}
		if (!empty($options['init_data'])) {
			self::$init_data = array_merge_recursive(self::$init_data, $options['init_data']);
		}
		unset($options['schema']);
		unset($options['init_data']);
		self::$options = array_merge(self::$options, $options);
	}

	public function default_schema()
	{
		return array(
			'mysql_engine' => 'MyISAM',
			'columns' => array(
				'id' => array('type' => 'serial'),
				'parent_id' => array('type' => 'int', 'length' => '1', 'not null' => true, 'default' => '0'),
				'ord' => array('type' => 'int', 'default' => 0, 'not null' => 'true'),
				'site' => array('type' => 'varchar', 'length' => '10', 'not null' => true, 'default' => '__'),
				'component' => array('type' => 'varchar', 'length' => '50', 'not null' => true, 'default' => ''),
				'code' => array('type' => 'varchar', 'length' => '50', 'not null' => true),
				'title' => array('type' => 'varchar', 'length' => '200'),
				'vartype' => array('type' => 'varchar', 'length' => '20', 'not null' => true),
				'valuesrc' => array('type' => 'text', 'size' => 'medium', 'not null' => true, 'default' => ''),
				'value' => array('type' => 'text', 'size' => 'medium', 'not null' => true, 'default' => ''),
				'parms' => array('type' => 'text', 'not null' => true, 'default' => ''),
				'parmsrc' => array('type' => 'text', 'not null' => true, 'default' => ''),
				'full' => array('type' => 'int', 'size' => 'tiny', 'unsigned' => true, 'not null' => true, 'default' => '0'),
			),
			'indexes' => array(
				array('type' => 'primary key', 'columns' => array('id')),
				'fk_vars_id' => array('columns' => array('parent_id')),
				'idx_vars_code' => array('columns' => array('code')),
				'idx_vars_site' => array('columns' => array('site')),
				'idx_vars_full' => array('columns' => array('full')),
				'idx_vars_component' => array('columns' => array('component')),
			)
		);
	}

	protected static function default_init_data()
	{
		return array(
			'navigation-16' => array(
				'id' => 16,
				'parent_id' => 0,
				'site' => '__',
				'component' => '',
				'code' => 'navigation',
				'title' => 'Структура навигации',
				'vartype' => 'array',
				'valuesrc' => "О компании = {\r\n	url = /about/\r\n}\r\n\r\n",
				'value' => "a:1:{s:19:\"О компании\";a:1:{s:3:\"url\";s:7:\"/about/\";}}",
				'full' => 1
			),
			'head-14' => array(
				'id' => 14,
				'parent_id' => 0,
				'site' => '__',
				'component' => '',
				'code' => 'head',
				'title' => 'Мета-информация по умолчанию',
				'vartype' => 'array',
				'valuesrc' => "meta.title =\r\nmeta.description =\r\nmeta.keywords =",
				'value' => "a:3:{s:12:\"meta.title =\";s:0:\"\";s:18:\"meta.description =\";s:0:\"\";s:15:\"meta.keywords =\";s:0:\"\";}",
				'full' => 0
			),
			'admin-17' => array(
				'id' => 17,
				'parent_id' => 0,
				'site' => '__',
				'component' => '',
				'code' => 'admin',
				'title' => 'Настройки системы администрирования',
				'vartype' => 'dir',
				'full' => 0
			),
			'title-18' => array(
				'id' => 18,
				'parent_id' => 17,
				'site' => '__',
				'component' => '',
				'code' => 'title',
				'title' => 'Заголовок',
				'vartype' => 'string',
				'valuesrc' => "",
				'value' => "Система управления сайтом",
				'full' => 0
			),
			'navigation-19' => array(
				'id' => 19,
				'parent_id' => 17,
				'site' => '__',
				'component' => '',
				'code' => 'navigation',
				'title' => 'Структура навигации',
				'vartype' => 'array',
				'valuesrc' => "Настройки = {\r\n	url = /admin/vars/\r\n}\r\n\r\nСтраницы = {\r\n	url = /admin/pages/\r\n}\r\n\r\nНовости = {\r\n	url = /admin/news/\r\n}\r\n\r\n",
				'value' => "a:3:{s:18:\"Настройки\";a:1:{s:3:\"url\";s:12:\"/admin/vars/\";}s:16:\"Страницы\";a:1:{s:3:\"url\";s:13:\"/admin/pages/\";}s:14:\"Новости\";a:1:{s:3:\"url\";s:12:\"/admin/news/\";}}",
				'full' => 0
			),
		);
	}

	public static function run()
	{
		if (CMS::vars()->storage_type() == 'orm') {
			DB_Schema::process_cache(array(
					'vars' => self::$schema
				)
			);
		}
		self::insert_init_data();
	}

	public static function insert_init_data()
	{
		if (CMS::vars()->storage_type() == 'orm' && !CMS::vars()->db()->connection) {
			return;
		}
		if (!self::$options['insert_init_data'] || CMS::vars()->db()->count()) {
			return;
		}
		foreach (self::$init_data as $key => $data) {
			$e = CMS::vars()->db()->make_entity();
			$e->assign($data);
			if (!CMS::vars()->db()->find($e->id)) {
				$e->insert();
			}
		}
	}

}
