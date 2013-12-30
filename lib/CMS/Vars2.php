<?php
/**
 * @package CMS\Vars2
 */


Core::load('CMS.Fields');

class CMS_Vars2 implements Core_ModuleInterface {

	const VERSION = '0.0.0';

	static $storage = 'CMS.Vars2.Storage.FS';
	static $admin_mapper = 'CMS.Vars2.Admin.Mapper';
	static $storage_object = false;
	static $types = array();
	static $types_objects = array();

	static function initialize($config=array()) {
		foreach($config as $key => $value) self::$$key = $value;
		CMS::add_component('AdminVars',Core::make(self::$admin_mapper));
	}

	public function register_type($type,$class) {
		self::$types[$type] = $class;
	}

	public function type_class($type) {
		if (isset(self::$types[$type])) return self::$types[$type];
		return self::$types['string'];
	}

	public function type($type) {
		if (!isset(self::$types_objects[$type])) {
			$class = $this->type_class($type);
			Core::load($class);
			self::$types_objects[$type] = Core::make($class);
		}
		return self::$types_objects[$type];
	}

	public function types_list() {
		$out = array();
		foreach(array_keys(self::$types) as $type) {
			if ($type!='dir') {
				$out[$type] = CMS::lang(self::type($type)->type_title());
			}
		}
		return $out;
	}

	public function entity($type,$data=array()) {
		$class = $this->type_class($type);
		Core::load($class);
		$entity = Core::make($class);
		$entity->setup();
		$entity['_type'] = $type;
		$data = $entity->unserialize_info($data);
		foreach($data as $key => $value) {
			$entity[$key] = $value;
		}
		return $entity;
	}

	public function setup() {
		foreach(array('Dir','String','Textarea','HTML','WIKI','Content','Array','Image','Gallery') as $type) {
			self::register_type(strtolower($type),"CMS.Vars2.Type.$type");
		}
		//Core::load('CMS.Vars2.Init'); CMS_Vars2_Init::run();
		CMS::cached_run('CMS.Vars2.Init');
	}

	public function storage() {
		if (!self::$storage_object) self::$storage_object = Core::make(self::$storage);
		return self::$storage_object;
	}

	public function exists($name) {
		return $this->storage()->exists($name);
	}

	public function get($name) {
		$var = $this->storage()->load($name);
		if (!$var) return null;
		return $var->get();
	}

	public function get_var($name) {
		$var = $this->storage()->load($name);
		return $var;
	}

	public function save_var($var) {
		$var_name = $var->name();
		/**
		@event cms.vars.change
		@alias cms.vars.change.{$var_name}
		@arg $var объект перед сохранением
		Вызывается непосредственно перед сохранением отдельной настройки (CMS.Vars). 
		Возможно обработать данные прежде чем они будут сохранены, сбросить кеш, сгенерировать еще что-то и т.п.
		Для обработки данных какой-то конкретной настройки вешайте обработчик на событие вида '''cms.vars.change.{$var_name}'''.
		*/
		Events::call("cms.vars.change",$var);
		Events::call("cms.vars.change.{$var_name}",$var);
		return $this->storage()->save($var);
	}

	public function delete($var) {
		if (is_string($var)) $var = $this->load($var);
		return $this->storage()->delete($var);
	}

	public function get_all_vars($name=false) {
		return $this->storage()->get_all_vars($name);
	}

	public function get_list($name=false) {
		$out = array();
		foreach($this->get_all_vars($name) as $var) {
			$out[$var->name()] = $var->get();
		}
		return $out;
	}

	public function config_fields() {
		Core::load('CMS.Vars2.Utils');
		return CMS_Vars2_Utils::config_fields();
	}

}


abstract class CMS_Var implements Core_PropertyAccessInterface, Core_IndexedAccessInterface {

	protected $_title;
	protected $_type;
	protected $_name;
	protected $_access;
	protected $attributes;

	public function setup()
	{
		$this->_title = '';
		$this->_name = '';
	}
	
	public function type_mnemocode() {
		$name = get_class($this);
		if ($m = Core_Regexps::match_with_results('{_([^_]+)$}',$name)) {
			$name = $m[1];
		}
		return trim(strtolower($name));
	}

	abstract public function type_title();

	public function type() {
		return CMS::vars()->type($this->_type);
	}

	public function fields() {
		return array(
			'value' => array(
				'type' => 'input',
				'tab' => 'default',
				'style' => 'width:100%;'
			),
		);
	}

	public function tab_title() {
		return CMS::lang($this->type_title());
	}

	public function tabs($item=false) {
		$def = $item? $item->tab_title() : '???';
		return array('default' => $def);
	}

	public function id() {
		return $this->_name;
	}

	public function name() {
		return $this->_name;
	}

	public function title() {
		return CMS::lang($this->_title);
	}

	public function field($name) {
		$fields = $this->fields();
		if (!isset($fields[$name])) return false;
		$type = CMS_Fields::type($fields[$name]);
		return $type->container($name,$fields[$name],$this);
	}

	public function serialized() {
		$out = array();
		foreach($this->fields() as $name => $data) {
			$type = CMS_Fields::type($data);
			$out += $type->serialized($name,$data);
		}
		return $out;
	}

	public function serialize_info($info) {
		foreach($this->serialized() as $field) {
			if (is_array($info[$field])) $info[$field] = serialize($info[$field]);
		}
		return $info;
	}

	public function unserialize_info($info) {
		foreach($this->serialized() as $field) {
			if (is_string($info[$field])) $info[$field] = unserialize($info[$field]);
		}
		return $info;
	}

	public function get() {
		return $this;
	}

	public function preview() {
		return htmlspecialchars(mb_substr($this['value'],0,70));
	}

	public function is_dir() {
		return false;
	}

	public function icon() {
		return CMS::stdfile_url('images/icons/document.png');
	}

	public function info() {
		$info = $this->attributes;
		$info['_type'] = $this->_type;
		$info['_name'] = $this->_name;
		foreach(CMS::vars()->config_fields() as $field => $data) {
			$info[$field] = $this->$field;
		}
		return $info;
	}

	public function homedir($private=false) {
		$path = $private?'../':'./';
		$path .= Core::option('files_name');
		$path .= '/varfiles/'.$this->id();
		CMS::mkdirs($path);
		return $path;
	}

	public function cache_dir_path($p=false) {
		return $this->homedir($p).'/_cache';
	}

	public function is_phantom() {
		$id = $this->id();
		return $id==''||$id=='.';
	}

	protected  function attr_get($name) {
		switch($name) {
			case '_title':
			case '_name':
			case '_type':
			case '_access':
				return $this->$name;
				break;
			default:
				return $this->attributes[$name];
		}
	}

	protected function attr_set($name,$value) {
		switch($name) {
			case '_title':
			case '_name':
			case '_type':
			case '_access':
				$this->$name = $value;
				break;
			default:
				$this->attributes[$name] = $value;
		}
	}

	protected function attr_isset($name) {
		switch($name) {
			case '_title':
			case '_name':
			case '_type':
			case '_access':
				return true;
				break;
			default:
				return isset($this->attributes[$name]);
		}
	}

	protected function attr_unset($name) {
		switch($name) {
			case '_title':
			case '_name':
			case '_type':
			case '_access':
				unset($this->$name);
				break;
			default:
				unset($this->attributes[$name]);
		}
	}

	public function __isset($name) {
		return $this->attr_isset($name);
	}

	public function offsetExists($name) {
		return $this->attr_isset($name);
	}

	public function __unset($name) {
		return $this->attr_unset($name);
	}

	public function offsetUnset($name) {
		return $this->attr_unset($name);
	}

	public function __get($name) {
		return $this->attr_get($name);
	}

	public function offsetGet($name) {
		return $this->attr_get($name);
	}

	public function __set($name,$value) {
		return $this->attr_set($name,$value);
	}

	public function offsetSet($name,$value) {
		return $this->attr_set($name,$value);
	}
	
	public function render()
	{
		return '[Undefined renderer for '.$this['_name'].']';
	}
	
	public function __toString()
	{
		return $this->render();
	}


}

abstract class CMS_Vars_Storage {
	abstract public function exists($name);
	abstract public function load($name);
	abstract public function delete($name);
	abstract public function save($var);
	abstract public function create_dir($name,$attrs);
	abstract public function update_dir($name,$attrs);
	abstract public function load_dir_info($name);
	abstract public function get_all_vars($name=false);
}
