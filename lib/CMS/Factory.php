<?php

class CMS_Factory implements Core_ModuleInterface { 

	static $name = 'CMSComponentFactory';
	static $admin_menu_title = 'Фабрика';

	static function initialize($config=array())
	{
		foreach($config as $key => $value) self::$$key = $value;
		CMS::add_component(self::$name, new CMS_Factory_Mapper());
		CMS_Admin::menu(self::$admin_menu_title,CMS::admin_path('factory'),'puzzle.png');
	}
}


class CMS_Factory_Mapper extends CMS_Mapper {

	public function controllers()
	{
		return array(
			'CMS.Controller.Factory'  => array(
				'path' => '{admin:factory}',
				'rules' => array(
					'{^$}' => array('action' => 'index'),
					'{^([^/]+)/$}' => array('{1}',false,'action' => 'table'),
					'{^([^/]+)/(schema|orm|admin)/$}' => array('{1}','{2}','action' => 'table'),
					'{^([^/]+)/(component)/$}' => array('{1}','{2}','action' => '{2}'),
				),
			),
		);
	}

	public function index_url()
	{
		return CMS::admin_path('factory');
	}


	public function table_url($table)
	{
		return CMS::admin_path("factory/{$table}");
	}

	public function orm_url($table)
	{
		return $this->table_url($table).'orm/';
	}

	public function admin_url($table)
	{
		return $this->table_url($table).'admin/';
	}

	public function schema_url($table)
	{
		return $this->table_url($table).'schema/';
	}

	public function component_url($table)
	{
		return $this->table_url($table).'component/';
	}
}


