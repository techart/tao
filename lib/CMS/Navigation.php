<?php
/**
 * @package CMS\Navigation
 */


Core::load('Navigation');

class CMS_Navigation implements Core_ModuleInterface {
	const VERSION = '0.0.1';

	static $var = 'navigation';
	static protected $uri;

	protected $controller;

	static function initialize($config=array()) {
		foreach($config as $key => $value) self::$$key = $value;
		Navigation::option('navigation_set_class', 'CMS.Navigation.Set');
	}

	static public function layout($v=hull)
	{
		return Navigation::layout($v);
	}
	
	public function __construct() {
	  $this->controller = Navigation::controller();
	}
	
	public function process($uri) {
		if ($m = Core_Regexps::match_with_results('{^([^\?]+)\?}',$uri)) $uri = trim($m[1]);
		self::$uri = $uri;
		$struct = self::struct();
		if (is_array($struct)) {
			foreach(array_keys($struct) as $key) {
				if (preg_match('/^set:(.+)/',$key,$m)) {
					$set = trim($m[1]);
					if (is_array($struct[$key]))
					  $this->controller->$set()->process(self::$uri, $struct[$key]);
					unset($struct[$key]);
				}
			}
			$this->controller->process(self::$uri, $struct);
		}
	}
	
	//??????????
	public function setup_selected() {
	}
	
	protected function struct() {
		if (is_callable(CMS::$navigation_struct)) return call_user_func(CMS::$navigation_struct);
		if (is_string(self::$var)) return CMS::vars()->get(self::$var);
		return array();
	}
	
	public function __call($method, $args) {
	  return call_user_func_array(array($this->controller, $method), $args);
	}

	public function __get($name) {
		return $this->controller->$name;
	}
}

class CMS_Navigation_Set extends Navigation_Set {

	private function load_from_component_by_string($source, $_parms =  false, $regexp = '{^([^\s]+)\s+(.+)$}', $_component = null) {
		if (is_null($_component))
			$_component = $source;
		if ($m = Core_Regexps::match_with_results($regexp, $source)) {
				$_component = trim($m[1]);
				$_parms = trim($m[2]);
		}
		return $this->load_from_component($_component, $_parms);
	}

	private function load_from_component($_component, $_parms) {
		if (CMS::component_exists($_component)) {
			$_class = CMS::$component_names[$_component];
			$_classref = Core_Types::reflection_for($_class);
			return $_classref->hasMethod('navigation_tree')? $_classref->getMethod('navigation_tree')->invokeArgs(NULL,array($_parms,$this)) : false;
		}
		return false;
	}

	public function read_item($title,$item) {
		$title = CMS::lang($title);
		if (is_string($item)) $item = array('url' => trim($item));
		if (isset($item['title'])) $title = trim($item['title']);
		if (isset($item['navigation_id'])) $item['id'] = trim($item['navigation_id']);
		if (is_string($item['sub'])) {
			$sub = trim($item['sub']);
			$sub = $this->load_from_component_by_string($sub, $item['url']);
		}
		if ($sub) {
			$item['sub'] = $sub;
		}
		$url = $item['url'];
		$url1 = $item['url'];
		Events::call('cms.navigation.add', $title, $item, $url);
		if ($url1 != $url) {
			$item['url'] = $url;
		}
		return parent::read_item($title,$item);
	}
	
	public function load_data($data, $level = 0, $parent = null) {
		if (is_string($data)) {
			$sub = trim($data);
			$data = $this->load_from_component_by_string($sub);
		}
		return parent::load_data($data, $level, $parent);
	}

	public function add_item($link, $level, $parent = null) {
		$access = isset($link->access) ? trim($link->access) : '';
		if ($access !='' && !CMS::check_globals_or($access))
			return $this;
		if (empty($link->url) && empty($link->id) && $m = Core_Regexps::match_with_results('{^\%(.+)$}', trim($link->title))) {
			$_component = trim($m[1]);
			$links = $this->load_from_component_by_string($_component);
			if (!empty($links)) {
				return $this->load_data($links, $level, $parent);
			}
			return $this;
		}
		return parent::add_item($link, $level, $parent);
	}
}
