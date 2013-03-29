<?php
/// <module name="CMS.ORM" maintainer="gusev@techart.ru" version="0.0.0">
Core::load('DB.ORM', 'Object');
/// <class name="CMS.ORM" stereotype="module">
///   <implements interface="Core.ModuleInterface" />

class CMS_ORM implements Core_ModuleInterface {

///   <constants>
	const MODULE = 'CMS.ORM';
	const VERSION = '0.0.0'; 
///   </constants>
	

///   <protocol name="creating">

///   <method scope="class" name="initialize">
///     <args>
///       <arg name="config" type="array" default="array()" />
///     </args>
///     <body>
	static function initialize($config=array()) {
		foreach($config as $key => $value) self::$$key = $value;
	} 
///     </body>
///   </method>
	
///   </protocol>	

///   <protocol name="performing">

	static function map($name,$classname) {
		CMS_ORM_Root::$classes[$name] = $classname;
	}

	static function mapper($mapper) {
		if (is_string($mapper)) return CMS::orm()->downto($mapper);
		return $mapper;
	}

///   </protocol>	

///   <protocol name="supporting">
///   </protocol>	
	
} 
/// </class>

/// <class name="CMS.ORM.Root">

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

/// </class>


/// <class name="CMS.ORM.Mapper">

class CMS_ORM_Mapper extends DB_ORM_SQLMapper {

	protected $fields = false;
	protected $__component;

	public function setup_config() {
		$fields = $this->fields();
		$schema = $this->schema();
		if (!empty($fields) || !empty($schema)) {
			Core::load('CMS.Fields');
			$this
				->columns(CMS_Fields::fields_to_columns($fields, self::table_from($this), $schema))
				->types()->build($fields)->end();
		}
	}

	protected function setup() {
		$this->setup_auto_add();
		$this->setup_config();
		return parent::setup();
	}

	public function setup_auto_add() {
		$this->options(array(
			'auto_add' => false,
			'auto_add_field_created' => '_created',
			'auto_add_field_time' => '_created_time',
			'auto_add_lifetime' => 86400,

		));
	}

	public function auto_add($v = null) {
		if (is_null($v)) return $this->option('auto_add');
		$this->option('auto_add', $v);
		return $this;
	}

	public function auto_add_field_created($v = null) {
		if (is_null($v)) return $this->option('auto_add_field_created');
		$this->option('auto_add_field_created', $v);
		return $this;
	}

	public function auto_add_field_time($v = null) {
		if (is_null($v)) return $this->option('auto_add_field_time');
		$this->option('auto_add_field_time', $v);
		return $this;
	}

	public function auto_add_mapper_created() {
		$fc = $this->auto_add_field_created();
		return $this->spawn()->where("{$fc}=1");
	}

	public function auto_add_lifetime($v = null) {
		if (is_null($v)) return $this->option('auto_add_lifetime');
		$this->option('auto_add_lifetime', $v);
		return $this;
	}

	public function auto_add_delete_old() {
		$fc = $this->auto_add_field_created();
		$ft = $this->auto_add_field_time();
		$this->spawn()->where("{$fc}=0")->where("{$ft}<:$ft",date('Y-m-d H:i:s',time()-$this->auto_add_lifetime()))->delete_all();
	}

//FIXME: циклическая ссылка
	public function types() {
		if (!$this->fields) $this->fields = new CMS_ORM_Fields($this);
		return $this->fields;
	}

	public function type_of($name) {
		return $this->types()->type_of($name);
	}

	public function type_object_of($name) {
		return $this->types()->type_object_of($name);
	}

	public function type_data_of($name) {
		$data = $this->types()->type_data_of($name);
		$data['__table'] = $this->__name();
		return $data;
	}

	public function fields_object() {
		if (!$this->fields) $this->fields = new CMS_ORM_Fields($this);
		return $this->fields;
	}


	public function component($c = null) {
		if (!is_null($c)) {
			$this->__component = $c;
			return $this;
		}
		if ($this->__component) return $this->__component;
		$this->__component = CMS::component_for($this);
		return $this->__component;
	}
	
	public function fields() {
		$name = $this->__name();
		$c = $this->component();
		$data = $c && $c->config('fields') && isset($c->config('fields')->$name) ? $c->config('fields')->$name : array();
		return $data ? $data : array();
	}

	public function schema() {
		$name = $this->__name();
		$component = $this->component();
		$data = $component && $component->config('schema') && isset($component->config('schema')->$name) ?
			$component->config('schema')->$name : array();
		return $data ? $data : array();
	}

}

/// </class>


/// <class name="CMS.ORM.Entity">

class CMS_ORM_Entity extends DB_ORM_Entity {

	static $db;

	protected $clear_cache = true;

	static function db() {
		return CMS::orm();
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

	public function fields() {
		if (!$this->mapper) return array();
		return $this->mapper->fields_object();
	}

	public function all_fields() {
		if (!$this->mapper) return array();
		return $this->mapper->fields_object()->all_fields();
	}

	public function fields_by_group($group) {
		if (!$this->mapper) return array();
		$fields = array();
		foreach ($this->mapper->fields_object()->by_group($group) as $name => $data) {
			$type = $this->type_object_of($name);
			$fields[$name] = $type->container($name, $data, $this);
		}
		return $fields;
	}

	public function fields_by_groups() {
		if (!$this->mapper) return array();
		$groups = array();
		foreach ($this->mapper->fields_object()->by_groups() as $gname => $gdata) {
			$fields = array();
			foreach ($gdata as $name => $data) {
				$type = $this->type_object_of($name);
				$fields[$name] = $type->container($name, $data, $this);
			}
			$groups[$gname] = $fields;
		}
		return $groups;
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


}

/// </class>



/// <class name="CMS.ORM.Entity">

class CMS_ORM_Fields {

	protected $mapper = false;
	protected $fields = array();
	protected $fields_parms = array();

	public function __construct($mapper=false) {
		$this->mapper = $mapper;
		return $this;
	}

	public function end() {
		$m = $this->mapper;
		unset($this->mapper);
		return $m;
	}

	public function all_fields() {
		return $this->fields;
	}

	public function type($name,$data) {
		if (is_string($data)) $data = array('type' => $data);
		$this->fields[$name] = $data;
		return $this;
	}

	public function by_group($group) {
		$f = Object::Filter($group, 'group');
		return array_filter($this->fields, array($f, 'filter'));
	}

	public function by_groups() {
		$res = array();
		foreach ($this->groups() as $g)
			$res[$g] = $this->by_group($g);
		return $res;
	}

	public function groups() {
		return $this->fields_parms('group');
	}

	public function tabs() {
		return $this->fields_parms('tabs');
	}

	public function fields_parms($name) {
		if (empty($this->fields_parms[$name])) {
			$res = array();
			foreach ($this->fields as $fname => $data)
				if (isset($data[$name])) $res[] = $data[$name];
			$this->fields_parms[$name] = array_unique($res);
		}
		return $this->fields_parms[$name];
	}


	public function type_of($name) {
		if (!isset($this->fields[$name])) return false;
		return $this->fields[$name]['type'];
	}

	public function type_data_of($name) {
		if (!isset($this->fields[$name])) return array('type' => 'input');
		return $this->fields[$name];
	}

	public function type_object_of($name) {
		Core::load('CMS.Fields');
		return CMS_Fields::type($this->type_of($name));
	}
	
	public function build(array $fields = array()) {
		foreach ($fields as $name => $data)
			$this->type($name, $data);
		return $this;
	}

	public function __call($name,$args) {
		if (count($args)==0) return $this;
		$this->type($name,$args[0]);
		return $this;
	}

	public function __get($name) {
		if (isset($this->fields[$name])) return $this->fields[$name];
		if ($name == 'fields') return $this->$name;
		return null;
	}

}

/// </class>


/// </module>
