<?php
/**
 * CMS.ORM
 * 
 * @package CMS\ORM
 * @version 0.0.0
 */
Core::load('DB.ORM', 'Object');
/**
 * @package CMS\ORM
 */

class CMS_ORM implements Core_ModuleInterface {

	const MODULE = 'CMS.ORM';
	const VERSION = '0.0.0'; 
	


/**
 * @param array $config
 */
	static function initialize($config=array()) {
		foreach($config as $key => $value) self::$$key = $value;
	} 
	


	static function map($name,$classname) {
		CMS_ORM_Root::$classes[$name] = $classname;
	}

	static function mapper($mapper) {
		if (is_string($mapper)) return CMS::orm()->downto($mapper);
		return $mapper;
	}


	
} 

/**
 * @package CMS\ORM
 */

class CMS_ORM_Root extends DB_ORM_ConnectionMapper {

	static $classes = array();

	public function __construct() {
		if (CMS::db()) $this->connect(CMS::db());
		CMS_ORM_Entity::$db = $this;
		CMS_Controller_Base::$db = $this;
	}

	public function __can_map($name,$is_call) {
		return isset(self::$classes[$name]) || parent::__can_map($name,$is_call);
	}

	public function __map($name) {
		$class = isset(self::$classes[$name])? self::$classes[$name] : false;
		if (is_string($class)) return Core::make($class,$this);
		if (is_callable($class)) return call_user_func($class,$this);
		return parent::__map($name);
	}

}



/**
 * @package CMS\ORM
 */

class CMS_ORM_Mapper extends DB_ORM_SQLMapper {

	protected $fields = false;
	protected $fields_data = array();
	protected $schema_module = false;
	protected $__component;

	public function setup_config()
	{
		$fields = $this->fields();
		$schema = $this->schema();
		if (!empty($fields) || !empty($schema)) {
			Core::load('CMS.Fields');
			$this
				->columns(CMS_Fields::fields_to_columns($fields, self::table_from($this), $schema))
				->schema_fields($fields);
		}
	}

	protected function before_setup()
	{
		$this->setup_auto_add();
		return parent::before_setup();
	}

	protected function after_setup()
	{
		$this->setup_config();
		return parent::after_setup();
	}

	public function setup_auto_add()
	{
		$this->options(array(
			'auto_add' => false,
			'auto_add_field_created' => '_created',
			'auto_add_field_time' => '_created_time',
			'auto_add_lifetime' => 86400,

		));
		return $this;
	}

	public function auto_add($v = null)
	{
		if (is_null($this->option('auto_add'))) {
			$this->setup_auto_add();
		}
		if (is_null($v)) {
			return $this->option('auto_add');
		}
		$this->option('auto_add', $v);
		return $this;
	}

	public function auto_add_field_created($v = null)
	{
		if (is_null($this->option('auto_add'))) {
			$this->setup_auto_add();
		}
		if (is_null($v)) return $this->option('auto_add_field_created');
		$this->option('auto_add_field_created', $v);
		return $this;
	}

	public function auto_add_field_time($v = null)
	{
		if (is_null($this->option('auto_add'))) {
			$this->setup_auto_add();
		}
		if (is_null($v)) return $this->option('auto_add_field_time');
		$this->option('auto_add_field_time', $v);
		return $this;
	}

	public function auto_add_mapper_created()
	{
		if (is_null($this->option('auto_add'))) {
			$this->setup_auto_add();
		}
		$fc = $this->auto_add_field_created();
		return $this->spawn()->where("{$fc}=1");
	}

	public function auto_add_lifetime($v = null)
	{
		if (is_null($this->option('auto_add'))) {
			$this->setup_auto_add();
		}
		if (is_null($v)) return $this->option('auto_add_lifetime');
		$this->option('auto_add_lifetime', $v);
		return $this;
	}

	public function auto_add_delete_old()
	{
		if (is_null($this->option('auto_add'))) {
			$this->setup_auto_add();
		}
		$fc = $this->auto_add_field_created();
		$ft = $this->auto_add_field_time();
		$this->spawn()->where("{$fc}=0")->where("{$ft}<:$ft",date('Y-m-d H:i:s',time()-$this->auto_add_lifetime()))->delete_all();
	}

	public function schema_fields($data=false)
	{
		if ($data) {
			if (is_callable($data)) {
				$this->fields_data = call_user_func($data);
			} elseif (is_array($data)) {
				$this->fields_data = array_merge_recursive($this->fields_data, $data);
			} elseif (is_string($data)) {
				$module = $data;
				$method = 'fields';
				if ($m = Core_Regexps::match_with_results('{^(.+)::(.+)$}',$data)) {
					$module = trim($m[1]);
					$method = trim($m[2]);
				}
				$this->schema_module = $module;
				if (strpos($module,'.')>0) {;
					Core::load($module);
					$module = str_replace('.','_',$module);
				}
				$this->fields_data = call_user_func(array($module,$method));
			}
			foreach($this->fields_data as $field => $data) {
				if ((isset($data['type'])&&$data['type']=='serial')||(isset($data['sqltype'])&&$data['sqltype']=='serial')) {
					$this->key($field);
					break;
				}
			}
			$schema = CMS_Fields::fields_to_schema($this->fields_data);
			if (!empty($schema)) {
				$columns = array_keys($schema['columns']);
				$this->columns($columns);
			}
			return $this;
		} else {
			if ($this->fields_data===false) {
				$this->fields_data = array();
			}
			return $this->fields_data;
		}
	}

	public function schema_tabs($action='edit') {
		if (!$this->schema_module) {
			return false;
		}
		$module = $this->schema_module;
		if (strpos($module,'.')>0) {;
			Core::load($module);
			$module = str_replace('.','_',$module);
		}
		if (method_exists($module,'tabs')) {
			return call_user_func(array($module,'tabs'),$action);
		}
		return false;
	}

	public function type_of($name)
	{
		if (is_array($this->fields_data)&&isset($this->fields_data[$name])&&isset($this->fields_data[$name]['type'])) {
			return $this->fields_data[$name]['type'];
		}
		return 'input';
	}

	public function type_object_of($name)
	{
		Core::load('CMS.Fields');
		return CMS_Fields::type($this->type_of($name));
	}

	public function type_data_of($name)
	{
		if (is_array($this->fields_data)&&isset($this->fields_data[$name])) {
			return $this->fields_data[$name];
		}
		return array();
	}

	public function component($c = null)
	{
		if (!is_null($c)) {
			$this->__component = $c;
			return $this;
		}
		if ($this->__component) return $this->__component;
		$this->__component = CMS::component_for($this);
		return $this->__component;
	}
	
	public function fields()
	{
		$name = $this->__name();
		$c = $this->component();
		$data = $c && $c->config('fields') && isset($c->config('fields')->$name) ? $c->config('fields')->$name : array();
		return $data ? array_merge_recursive($this->fields_data, $data) : array();
	}

	public function schema()
	{
		$schema = CMS_Fields::fields_to_schema($this->fields_data);
		$name = $this->__name();
		$component = $this->component();
		$data = $component && $component->config('schema') && isset($component->config('schema')->$name) ?
			$component->config('schema')->$name : array();
		return $data ? array_merge_recursive($data, $schema) : $schema;
	}

}



/**
 * @package CMS\ORM
 */

class CMS_ORM_Entity extends DB_ORM_Entity implements DB_ORM_AttrEntityInterface {

	static $db;

	protected $clear_cache = true;

	static function db()
	{
		return CMS::orm();
	}

	public function setup()
	{
		parent::setup();
		if ($this->mapper) {
			$fields = $this->mapper->schema_fields();
			if (is_array($fields)) {
				foreach($fields as $field => $data) {
					$value = '';
					if (isset($data['init_value'])) {
						$value = $data['init_value'];
					}
					$this[$field] = $value;
				}
			}
		}
		return $this;
	}

	public function auto_add() {
		if (!$this->mapper) return false;
		return $this->mapper->auto_add();
	}

	public function auto_add_field_created() {
		if (!$this->mapper) return '_created';
		return $this->mapper->auto_add_field_created();
	}

	public function auto_add_field_time() {
		if (!$this->mapper) return '_created_time';
		return $this->mapper->auto_add_field_time();
	}

	public function type_of($name) {
		if (!$this->mapper) return false;
		return $this->mapper->type_of($name);
	}

	public function type_object_of($name) {
		Core::load('CMS.Fields');
		if (!$this->mapper) return CMS_Fields::type('input');
		return $this->mapper->type_object_of($name);
	}

	public function type_data_of($name) {
		if (!$this->mapper) return array('type' => 'input');
		return $this->mapper->type_data_of($name);
	}

	public function all_fields() {
		if (!$this->mapper) return array();
		return $this->mapper->schema_fields();
	}

	public function field($name) {
		$type = $this->type_object_of($name);
		$data = $this->type_data_of($name);
		return $type->container($name,$data,$this);
	}

	protected function serialized() {
		$out = array();
		foreach($this->all_fields() as $name => $data) {
			$type = CMS_Fields::type($data);
			$out += $type->serialized($name,$data);
		}
		return $out;
	}

	protected function multilinks() {
		return array();
	}

	public function before_encode_value($attr, $value)
	{
		if (isset($attr->type) && $attr->type == 'string' && !empty($value)) {
			return CMS::lang($value);
		}
		return $value;
	}

	public function before_update() {
		if ($this->auto_add()) {
			$fc = $this->auto_add_field_created();
			$this->{$fc} = 1;
		}
		return true && parent::before_update();
	}

	public function before_insert() {
		if ($this->auto_add()) {
			$fc = $this->auto_add_field_created();
			$ft = $this->auto_add_field_time();
			$this->{$fc} = 0;
			$this->{$ft} = date('Y-m-d H:i:s');
		}
		return true && parent::before_insert();
	}

	public function clear_cache($v = true) {
		$this->clear_cache = $v;
		return $this;
	}

	public function before_save() {
		$this->serialize_parms($this->serialized());
		if ($this->clear_cache) {
			if ($dir = $this->cache_dir_path()) CMS::rmdir($dir);
			if ($dir = $this->cache_dir_path(true)) CMS::rmdir($dir);
		}
		return true && parent::before_save();
	}

	public function after_save() {
		$this->multilink_save_all();
		return true && parent::after_save();
	}

	public function before_delete() {
		$this->multilink_delete_all();
		if ($dir = $this->homedir()) CMS::rmdir($dir);
		if ($dir = $this->homedir(true)) CMS::rmdir($dir);
		return true && parent::before_delete();
	}

	public function after_find() {
		$this->unserialize_parms($this->serialized());
		$this->multilink_load_all();
		return true && parent::after_find();
	}

	protected function serialize_parms() {
		$args = func_get_args();
		if (is_array($args[0])) $args = $args[0];
		foreach($args as $arg) {
			if (!is_string($this->$arg)) {
				$this->attrs[$arg] = serialize($this->attrs[$arg]);
			}
		}
	}

	protected function unserialize_parms() {
		$args = func_get_args();
		if (is_array($args[0])) $args = $args[0];
		foreach($args as $arg) {
			if (is_string($this->$arg)) $this->$arg = unserialize((string)$this->$arg);
			if (!$this->$arg) $this->$arg = array();
		}
	}
//TODO: merge whi associated in DB.ORM
	protected function multilink_delete_all() {
		foreach($this->multilinks() as $mapper) $this->multilink_delete($mapper);
	}

	protected function multilink_delete($mapper) {
		$mapper = CMS_ORM::mapper($mapper);
		list($key,$field) = $mapper->options['columns'];
		$mapper->spawn()->where("$key=:$key",$this->id())->delete_all();
	}

	protected function multilink_load_all() {
		foreach($this->multilinks() as $mapper) $this->multilink_load($mapper);
	}

	protected function multilink_load($mapper) {
		$mapper = CMS_ORM::mapper($mapper);
		list($key,$field) = $mapper->options['columns'];
		$links = new ArrayObject();
		foreach($mapper->spawn()->where("$key=:$key",$this->id()) as $row) {
			$id = $row->$field;
			$f = "$field$id";
			$this->$f = 1;
			$links[] = $id;
		}
		$this->$field = $links;
	}

	protected function multilink_save_all() {
		foreach($this->multilinks() as $mapper) $this->multilink_save($mapper);
	}

	protected function multilink_save($mapper) {
		$this->multilink_delete($mapper);
		$mapper = CMS_ORM::mapper($mapper);
		list($key,$field) = $mapper->options['columns'];
		$links = new ArrayObject();
		foreach($this->attrs as $k => $v) {
			if ($v && ($m = Core_Regexps::match_with_results("/^$field(\d+)\$/",$k))) {
				$id = (int)$m[1];
				$links[] = $id;
				$item = $mapper->make_entity();
				$item->$key = $this->id();
				$item->$field = $id;
				$mapper->insert($item);
			}
		}
		$this->$field = $links;
	}
	
	public function as_string() {
		return CMS::lang(parent::as_string());
	}

	protected function attrs_discover()
	{
		return Core::make('CMS.ORM.EntityAttrsDiscover');
	}

	protected static $__attrs = null;

	public function __attrs($flavor = array())
	{
		if (is_null(self::$__attrs)) {
			if ($discover = $this->attrs_discover()) {
				self::$__attrs = $discover->discover($this, $flavor);
			} else {
				self::$__attrs = array();
			}
		}
		return self::$__attrs;
		
	}
}





