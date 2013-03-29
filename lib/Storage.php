<?php

class Storage implements Core_ModuleInterface {
	const VERSION = '0.0.0';

	static private $manager = null;

	public static function Entity($attrs = array()) {
		return new Storage_Entity($attrs);
	}

	public static function manager() {
		if (self::$manager) return self::$manager;
		return self::$manager = new Storage_Manager();
	}
}

class Storage_Entity implements Core_IndexedAccessInterface, Core_PropertyAccessInterface {

	protected $attrs = array();
	protected $storage = null;

	static protected $fields = array();

	static public function fields($data = array()) {
		if (empty($data)) return static::$fields;
		else return static::$fields = $data;
	}

	static public function add_field($name, $data = array()) {
		static::$fields[$name] = $data;
		return self;
	}

	static public function field($name) {
		$data = static::$fields[$name];
		$type_object = CMS_Fields::type(isset($data['type']) ? $data['type'] : '');
		return $type_object->container($name, $data, $this);
	}

	public function __construct($attrs = array()) {
		$this->setup()->assign($attrs);
	}

	public function setup() {
		return $this;
	}

	public function assign(array $attrs) {
		foreach ($attrs as $k => $v) $this->__set($k, $v);
		return $this;
	}

	public function id() {
		return $this['id'];
	}

	public function key() {
		return 'id';
	}

	protected function cache_dir_name() {
		return '_cache';
	}

	public function set_storage($storage) {
		return $this->storage = $storage;
	}

	public function get_storage() {
		return $this->storage;
	}

	public function cache_dir_path($p = false) {
		$path = $this->homedir($p);
		$path .= '/'.$this->cache_dir_name();
		return $path;
	}

	protected function mnemocode() {
		if ($this->storage)
			return $this->storage->__name();
		return strtolower(get_class($this));
	}

	protected function homedir_location($private = false) {
		return ($private ? '../' : '') . Core::option('files_name') . '/' . $this->mnemocode();
	}

	public function homedir($p = false) {
		if ($this->id() == 0) return false;
		$private = false;
		$path = false;
		if ($p === true) $private = true;
		if (is_string($p)) $path = $p;
		$dir = $this->homedir_location($private);
		$id = $this->id();
		$did = (int)floor($id/500);
		$s1 = str_pad((string)$id, 4,'0',STR_PAD_LEFT);
		$s2 = str_pad((string)$did,4,'0',STR_PAD_LEFT);
		$dir = "$dir/$s2/$s1";
		if ($path) $dir .= "/$path";
		return $dir;
	}

	public function get($name) {
		return isset($this->attrs[(string) $name]) ? $this->attrs[(string) $name] : null;
	}
  
	public function set($name, $value) {
		$this->attrs[(string) $name] =  $value;
		return $this;
	}

	public function offsetGet($index) {
		switch (true) {
			case method_exists($this, $name = "row_get_$index"):
				return $this->$name();
			case $index === '__class':
				return Core_Types::real_class_name_for($this);
			default:
				return $this->get($index, $name);
		}
	}

	public function offsetSet($index, $value) {
		switch (true) {
			case method_exists($this, $name = "row_set_$index"):
				$this->$name($value);
				break;
			case $index === '__class':
				break;
			default:
				return $this->set($index, $value);
		}
		return $this;
	}

	public function offsetExists($index) {
		return (isset($this->attrs[$index]) ||
			method_exists($this, "row_get_$index"));
	}

	public function offsetUnset($index) { throw new Core_ReadOnlyObjectException($this); }

	public function __get($property) {
		switch ($property) {
			case 'attrs':
			case 'attributes':
				return $this->attrs;
			default:
				if (method_exists($this, $method = "get_$property"))
					return $this->$method();
				else
					return $this->get($property, $method);
		}
	}

	public function __set($property, $value) {
		switch ($property) {
			case 'attrs':
				$this->attrs = $value;
				return $this;
			case 'attributes':
				throw new Core_ReadOnlyPropertyException($property);
			default:
				if (method_exists($this, $method = "set_$property")) {
					$this->$method($value); return $this;
				}
				else
				return $this->set($property, $value, $method);
		}
	}

	public function __isset($property) {
		switch ($property) {
			case 'attrs':
			case 'attributes':
				return true;
			default:
				return method_exists($this, "get_$property") || isset($this[$property]);
		}
	}

	public function __unset($property) {
		switch ($property) {
			case 'attrs':
			case 'attributes':
				throw new Core_UndestroyablePropertyException($property);
			default:
				throw new Core_MissingPropertyException($property);
		}
	}

	public function as_string() {
		$res = '';
		foreach(array('title', 'name', 'id') as $name)
			if (!empty($this[$name])) {
				$res = $this[$name];
				break;
			}
		return (string) $res;
	}

	public function __toString() {
		return $this->as_string();
	}

	public function as_array() {
		return $this->attrs;
	}

	public function update() {
		$args = func_get_args();
		array_unshift($args, $this);
		return Core::invoke(array($this->get_storage(), 'update'), $args);
	}

	public function insert() {
		$args = func_get_args();
		array_unshift($args, $this);
		return Core::invoke(array($this->get_storage(), 'insert'), $args);
	}

	public function delete() {
		$args = func_get_args();
		array_unshift($args, $this);
		return Core::invoke(array($this->get_storage(), 'delete'), $args);
	}

}

class Storage_Manager {

	protected $instance = array();
	protected $default_storage = 'Storage.File.Export.Type';

	public function add($name, $instance) {
		$this->instance[$name] = $instance;
		return $this;
	}

	public function remove($name) {
		unset($this->instance[$name]);
		return $this;
	}

	public function __call($name, $args) {
		if (!isset($this->instance[$name])) {
			if ($this->default_storage)
				$this->add($name, $this->default_storage);
			else
				return null;
		}
		$class = $this->instance[$name];
		$res = null;
		if (is_object($class)) {
			$res = $class;
		} else {
			$class = (string) $class;
			Core::autoload($class);
			$res = Core::amake($class, $args);
		}
		$res->__set_name($name);
		return $res;
	}

	public function __get($name) {
		return $this->__call($name, array());
	}

}

abstract class Storage_Type {

	protected $name = '';
	protected $current_query;

	public function __construct() {
		$this->current_query = $this->create_query();
		$this->setup();
	}

	public function setup() {
		return $this;
	}

	public function __name() {
		return $this->name;
	}

	public function __set_name($name) {
		$this->name = $name;
		return $this;
	}

	abstract public function update($e);

	abstract public function insert($e);

	abstract public function delete($e);

	abstract public function select($query = null);

	public function select_first($query = null) {
		$res = $this->select($query);
		return reset($res);
	}

	abstract public function count();

	abstract public function find($id);

	abstract public function delete_all();

	abstract public function create_query();

	public function make_entity($attrs = array()) {
		$e = Storage::Entity($attrs);
		$e->set_storage($this);
		return $e;
	}

}

abstract class Storage_Query {

	protected $options = array();
	protected $executor = null;
	protected $storage = null;

	public function set_storage($storage) {
		$this->storage = $storage;
		return $this;
	}

	public function get_storage() {
		return $this->storage;
	}

	public function set_executor($ex) {
		$this->executor = $ex;
		return $this;
	}

	public function is_empty() {
		return empty($this->options);
	}

	public function option($name, $value = null, $append = false) {
		if (is_null($value)) return $this->options[$name];
		if ($append)
			$this->options[$name][] = $value;
		else
			$this->options[$name] = $value;
		return $this;
	}

	public function order_by($field, $dir = 'asc') {
		$this->option('order_by', array($field, $dir), true);
		return $this;
	}

	public function range($limit, $offset = 0) {
		$this->option('range', array($limit, $offset));
		return $this;
	}

	public function eq($field, $value) {
		$this->option('eq', array($field, $value), true);
		return $this;
	}

	public function in($field, $values) {
		$this->option('in', array($field, $values), true);
		return $this;
	}

	public function eq_or_none($field, $value) {
		$this->option('eq_or_none', array($field, $value), true);
		return $this;
	}

	public function reset($name = null) {
		if (is_null($name)) $this->options = array();
		else unset($this->options[$name]);
		return $this;
	}

	public function filter($parms = array()) {
		$limit = isset($parms['limit']) ? $parms['limit'] : null;
		$offset = isset($parms['offset']) ? $parms['offset'] : 0;
		if ($limit) $this->range($limit, $offset);
		unset($parms['limit']);
		unset($parms['offset']);
		// foreach ($parms as $k => $v) {
		// 	$this->eq($k, $v);
		// }
		return $this;
	}

	abstract public function execute($parms);

	public function select() {
		if ($this->storage) return $this->storage->select($this);
		return array();
	}

	public function __sleep() {
		return array('options');
	}

}