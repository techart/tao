<?php
/**
 * @package CMS\Component
 */


class CMS_Component implements Core_ModuleInterface {

	const VERSION = '0.1.0';

	protected $name;
	protected $cconfig = array();

	protected $config_dir = 'config';
	protected $user_config_dir = 'app/config';

	protected $process_schema = true;

	public function __construct($name) {
		$this->name = $name;
		$this->cache = WS::env()->cache;
		//TODO: config source (file, cache, vars ...)
	}

	public function is_auto_schema()
	{
		return true;
	}

	public function dir() {
		return CMS::component_dir($this->name);
	}

	public function config($name) {
		if (!empty($this->cconfig[$name]))
			return $this->cconfig[$name];
		$config = null;
		$file = $this->dir() . "/{$this->config_dir}/$name.php";
		if (is_file($file))
			$config = include $file;
		$file = $this->dir() . "/{$this->user_config_dir}/$name.php";
		if (is_file($file)) {
			$user_data = include $file;
			$config = Core_Arrays::deep_merge_update($config, (array) $user_data);
		}
		return $this->cconfig[$name] = (object) $config;
	}

	public function filepath($to) {
		return $this->dir() . '/' . $to;
	}

	public function config_path($to) {
		return $this->dir() . "/{$this->config_dir}/$to";
	}

	public function __get($name) {
		if (isset($this->$name)) return $this->$name;
		return null;
	}

	// public static function after_initialize() {
	// 	$me = CMS::component();
	// 	if (!$me) return;
	// 	if ($me->process_schema)
	// 		$me->process_schema();
	// }

	public function process_schema() {
		$schema = $this->config('schema');
		$fields = $this->config('fields');
		$cache_key = md5(serialize($schema) . serialize($fields));
		if ($this->cache->has($cache_key)) {
			return $this;
		}
		$this->cache->set($cache_key, 1);
		if (empty($schema)) return;
		if (empty($fields)) $fields = Core::hash();

		$schema = clone $schema;

		// some time we have fields without info in schema
		// fix it
		$schema_keys = array_keys((array) $schema);
		$fields_keys = array_keys((array) $fields);
		$diff = array_diff($fields_keys, $schema_keys);
		foreach ($diff as $name) {
			$schema->$name = array();
		}

		Core::load('DB.Schema');
		Core::load('CMS.Fields');
		foreach ($schema as $name => &$table) {
			if (!empty($fields->$name)) {
				CMS_Fields::fields_to_schema($fields->$name, $name, $table);
			}
		}

		foreach ($schema as $name => $ttable) {
			if (empty($ttable)) {
				unset($schema->$name);
			}
		}
		
		DB_Schema::process_cache($schema);
	}
}