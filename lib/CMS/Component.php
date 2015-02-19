<?php
/**
 * @package CMS\Component
 */

Core::load('Config.DSL');

class CMS_Component implements Core_ModuleInterface
{

	const VERSION = '0.1.0';

	protected static $component_names = array();
	protected $name;
	protected $cconfig = array();
	protected $config_dir = 'config';
	protected $user_config_dir = 'app/config';
	protected $process_schema = true;
	protected $services = array();

	public function __construct($name)
	{
		$this->name = $name;
		$this->cache = WS::env()->cache;
		$this->setup();
		if ($this->autodump_assets()) {
			$this->assets_dump_all();
		}
		//TODO: config source (file, cache, vars ...)
	}

	protected function setup()
	{
		$dir = $this->dir();
		Text_Insertions::filter()->add_views_paths(array(
 			$dir . '/app/views',
 			$dir . '/views',
 		));
	}

	public static function initialize($options = array())
	{
		// Little magic for late static binding
		if (get_class() != get_called_class()) {
			static::options($options);
			static::instance()->process_component_config();
			static::register();
		}
	}


	public static function options($config = array())
	{
		foreach($config as $key => $value) {
			static::$$key = $value;
		}
	}

	public static function name()
	{
		$class = get_called_class();
		if (!isset(static::$component_names[$class])) {
			$name = preg_replace('!^(.+)_([^_]+)$!', '$2', $class);
			static::$component_names[$class] =  $name;
		}
		return static::$component_names[$class];
	}

	protected static function register()
	{
		CMS::add_component_object(static::instance());
	}

	protected static function instance()
	{
		$class = get_called_class();
		if (!CMS::component(static::name())) {
			return Core::make($class, static::name());
		} else {
			return CMS::component(static::name());
		}
	}

	protected static function router_class()
	{
		$class = get_called_class();
		$suffix = array('App_Router', 'App_Mapper', 'Router', 'Mapper');
		foreach ($suffix as $s) {
			$candidate = "{$class}_{$s}";
			if (class_exists($candidate, false)) {
				return $candidate;
			}
		}
		return null;
	}

	public static function router()
	{
		$class = static::router_class();
		if (!is_null($class)) {
			return Core::make($class);
		}
		return null;
	}

	public function autodump_assets()
	{
		return true;
	}

	public function assets_dirs()
	{
		if (isset($this->config('component')->assets_dirs)) {
			return $this->config('component')->assets_dirs;
		}
		return array('files', 'images');
	}

	public function assets_dump_all($force = false)
	{
		$result = array();
		if ($force || !$this->cache->get($this->cache_key('assets:dump_all'))) {
			foreach ($this->assets_dirs() as $dir) {
				$this->assets_dump($dir, $result);
			}
			$this->cache->set($this->cache_key('assets:dump_all'), 1, 0);
		}
		return $result;
	}

	protected function asset_dump_dir($path = '', $from_path = '')
	{
		$path = trim(dirname(str_replace($from_path, '', $path)), '/');
		return "components/{$this->name()}/{$path}";
	}

	public function assets_dump($from, &$result = array())
	{
		$from = IO_FS::dir($this->dir($from));
		if ($from->exists()) {
			foreach (IO_FS::Query()->recursive()->apply_to($from) as $f) {
				$dest = Templates_HTML::file_to_docroot('file://' . $f->path, $this->asset_dump_dir($f->path, $from->path));
				$result[$f->path] = $dest;
			}
		}
		return $this;
	}

	protected function process_component_config($config_name = 'component')
	{
		$config = $this->config($config_name);
		// TODO: split to methods
		if (isset($config->admin_menu)) {
			$menu = (object) $config->admin_menu;
			CMS_Admin::menu($menu->caption, $menu->path, $menu->items, $menu->icon);
		}

		if (isset($config->templates)) {
			$helpers = $config->templates['helpers'];
			Templates_HTML::use_helpers($helpers);
		}

		if (isset($config->field_types)) {
			$types = $config->field_types;
			foreach ($types as $name => $class) {
				CMS::field_type($name, $class);
			}
		}

		if (isset($config->commands)) {
			$commands = $config->commands;
			foreach ($commands as $chapter => $data) {
				$args = array_merge(array($chapter, $data['name'], $data['callback']), isset($data['args']) ? $data['args'] : array());
				call_user_func_array(array('CMS', 'add_command'), $args);
			}
		}

		if (isset($config->insertions)) {
			$insertions = $config->insertions;
			foreach ($insertions as $ins) {
				$args = array_merge(array($ins['class']), $ins['names']);
				call_user_func_array(array('CMS', 'register_insertions'), $args);
			}
		}

		if (isset($config->events)) {
			$events = $config->events;
			foreach ($events as $name => $callback) {
				Events::add_listener($name, $callback);
			}
		}

		if (isset($config->orm)) {
			$orm = $config->orm;
			foreach ($orm as $name => $class) {
				CMS::orm()->submapper($name, $class);
			}
		}
	}

	public function service($name)
	{
		if (isset($this->services[$name])) {
			return $this->services[$name];
		}
		$config = $this->config('services');
		if (!isset($config->$name)) {
			throw new Core_Exception("{$this->name}: undefined service '$name'");
		}
		$class = $config->$name;
		$service = Core::make($class);
		return $this->services[$name] = $service;
	}

	public function is_auto_schema()
	{
		return true;
	}

	public function dir($path = '')
	{
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
			$config = (array)Config_DSL::Builder()->load($file)->object;
		}
		$file = $this->dir() . "/{$this->user_config_dir}/$name.php";
		if (is_file($file)) {
			$user_data = Config_DSL::Builder()->load($file)->object;
			$config = Core_Arrays::deep_merge_update($config, (array)$user_data);
		}
		$result = (object)$config;
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

	public function schema_modules()
	{
		return $this->config('component')->schema_modules;
	}

	//TODO: refactoring
	public function process_schema()
	{
		// process schema modules
		$modules = $this->schema_modules();
		if (!empty($modules)) {
			foreach ($modules as $name => $module) {
				CMS::cached_run($module);
			}
		}

		// get data from config
		$schema = $this->config('schema');
		$fields = $this->config('fields');
		$tmp1 = (array) $schema;
		$tmp2 = (array) $fields;
		if (empty($tmp1) && empty($tmp2)) {
			return;
		}
		if (empty($fields)) {
			$fields = Core::hash();
		}

		$schema = clone $schema;

		// some time we have fields without info in schema
		// fix it
		$schema_keys = array_keys((array)$schema);
		$fields_keys = array_keys((array)$fields);
		$diff = array_diff($fields_keys, $schema_keys);
		foreach ($diff as $name) {
			$schema->$name = array();
		}

		//fields to schema
		Core::load('DB.Schema');
		Core::load('CMS.Fields');
		foreach ($schema as $name => &$table) {
			if (!empty($fields->$name)) {
				$table_fields = $fields->$name;
				$table_name = $name;
				CMS_Fields::fields_to_schema($fields->$name, $name, $table);
				Events::add_once('db.schema.after_execute.' . $name, function ($tf_schema) use ($table_fields, $table_name) {
					foreach ($table_fields as $tf_name => $tf_data) {
						$tf_type = CMS_Fields::type($tf_data);
						$tf_type->process_schema($tf_name, $tf_data, $table_name, $table_fields);
					}
				});
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

		// run
		DB_Schema::process_cache($schema);
	}

	public function template_path($template)
	{
		$component_dir = $this->dir();
		$variants = array('/app/views/', '/views/');
		foreach ($variants as $v) {
			$path = $component_dir . $v . trim($template, '/');
			if (IO_FS::exists($path)) {
				return $path;
			}
		}
		return false;
	}

	protected function cache_key($key)
	{
		$cname = strtolower($this->get_name());
		if (!empty($cname)) {
			return "cms:component:{$cname}:{$key}";
		}
		return $key;
	}
}