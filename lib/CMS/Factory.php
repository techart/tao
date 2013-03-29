<?php

class CMS_Factory implements Core_ModuleInterface { 
	const MODULE = 'CMS.Factory'; 
	const VERSION = '0.0.0'; 
	
	static $name = 'CMSComponentFactory';
	static $admin_menu_title = 'Фабрика';
	static $use_style = false;
	static $repository = '/projects/tao_components';
	static $invalid_entries = array('.git','nbproject');
	
	static function initialize($config=array()) {
		foreach($config as $key => $value) self::$$key = $value;
		Core::load('CMS.Factory');
		CMS::add_component(self::$name, new CMS_Factory_Mapper());
		CMS_Admin::menu(self::$admin_menu_title,CMS::admin_path('factory'),'puzzle.png',array(
			'Создание объектов' => CMS::admin_path('factory'),
			'Репозиторий компонентов' => CMS::admin_path('factory/repository'),
		));
	}
	
	
} 


class CMS_Factory_Mapper extends CMS_Mapper {

	protected $controllers = array(
        	            
		'CMS.Controller.Factory'  => array(
			'path' => '{admin:factory}',
			'rules' => array(
				'{^$}' => array('action' => 'index'),
				'{^(entity|ac|component|download|schema)/$}' => array('action' => '{1}'),
				'{^(schema_end|cend|install|update|iok|uok|repository)/(?:(.+)/)?$}' => array('{2}','action' => '{1}'),
				'{^doc/(.+)/([^/]+)/$}' => array('{1}','{2}','action' => 'doc'),
			),
		),
	);
        
	public function index_url() {
       	return CMS::admin_path('factory');
	}
        
	public function doc_url($dir) {
		return CMS::admin_path('factory/doc').$dir;
	}
        
	public function repository_url($dir) {
		$url = CMS::admin_path('factory/repository').$dir;
		if ($dir!='') $url .= '/';
		return $url;
	}
        
	public function install_url($dir,$name) {
		$dir = trim(trim($dir,'/'));
		if ($dir!='') $dir .= '/';
		return CMS::admin_path('factory/install').$dir.$name.'/';
	}
        
	public function install_ok_url($dir) {
		$dir = trim(trim($dir,'/'));
		if ($dir!='') $dir .= '/';
		return CMS::admin_path('factory/iok').$dir;
	}
        
	public function update_ok_url($dir) {
		$dir = trim(trim($dir,'/'));
		if ($dir!='') $dir .= '/';
		return CMS::admin_path('factory/uok').$dir;
	}
        
	public function component_url() {
		return CMS::admin_path('factory/component');
	}
        
	public function component_end_url($component) {
		return CMS::admin_path('factory/cend')."$component/";
	}
        
	public function entity_url() {
		return CMS::admin_path('factory/entity');
	}

	public function schema_url() {
		return CMS::admin_path('factory/schema');
	}

	public function schema_end_url($component) {
		return CMS::admin_path('factory/schema_end')."$component/";
	}
        
	public function admin_controller_url() {
		return CMS::admin_path('factory/ac');
	}
        
	public function admin_download_url($file) {
		return CMS::admin_path('factory/download').'?file='.$file;
	}

}


