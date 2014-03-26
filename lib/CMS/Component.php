<?php
/**
 * @package CMS\Component
 */

Core::load('Config.DSL');


class CMS_Component implements Core_ModuleInterface
{

	const VERSION = '0.1.0';

	protected $name;
	protected $cconfig = array();

	protected $config_dir = 'config';
	protected $user_config_dir = 'app/config';

	protected $process_schema = true;

	public function __construct($name)
	{
		$this->name = $name;
		$this->cache = WS::env()->cache;
		//TODO: config source (file, cache, vars ...)
	}

	public function is_auto_schema()
	{
		return true;
	}

	public function dir($path = '') {
		return CMS::component_dir($this->name, $path);
	}

	public function config($name)
	{
		if (!empty($this->cconfig[$name])) {
			return $this->cconfig[$name];
		}
		$config = array();
		$file = $this->dir() . "/{$this->config_dir}/$name.php";
		if (is_file($file)) {
			$config = (array) Config_DSL::Builder()->load($file)->object;
		}
		$file = $this->dir() . "/{$this->user_config_dir}/$name.php";
		if (is_file($file)) {
			$user_data = Config_DSL::Builder()->load($file)->object;
			$config = Core_Arrays::deep_merge_update($config, (array) $user_data);
		}
		$result = (object) $config;
		$method = "config_$name";
		if (method_exists($this, $method)) {
			$result = $this->$method($result);
		}
		return $this->cconfig[$name] = $result;
	}

	public function filepath($to)
	{
		return $this->dir() . '/' . $to;
	}

	public function config_path($to)
	{
		return $this->dir() . "/{$this->config_dir}/$to";
	}

	public function __get($name)
	{
		if (isset($this->$name)) {
			return $this->$name;
		}
		return null;
	}

	public function get_name()
	{
		return CMS::get_component_name_for($this);
	}

	public function process_schema()
	{
		$schema = $this->config('schema');
		$fields = $this->config('fields');

		if (empty($schema)) {
			return;
		}
		if (empty($fields)) {
			$fields = Core::hash();
		}

		$schema = clone $schema;

		// some time we have fields without info in schema
		// fix it
		$schema_keys = array_keys((array) $schema);
		$fields_keys = array_keys((array) $fields);
		$diff = array_diff($fields_keys, $schema_keys);
		foreach ($diff as $name) {
			$schema->$name = array();
		}

		//fields to schema
		Core::load('DB.Schema');
		Core::load('CMS.Fields');
		foreach ($schema as $name => &$table) {
			if (!empty($fields->$name)) {
				CMS_Fields::fields_to_schema($fields->$name, $name, $table);
			}
		}

		// remove empty values
		foreach ($schema as $name => $ttable) {
			if (empty($ttable)) {
				unset($schema->$name);
			}
		}

		// cache
		$cname = strtolower($this->get_name());
		if (!empty($cname)) {
			$cache_key = 'cms:component:' . $cname . ':schema:' . md5(serialize($schema));
			if ($this->cache->has($cache_key)) {
				return $this;
			}
			$this->cache->set($cache_key, 1, 0);
		}
		
		DB_Schema::process_cache($schema);
	}
}