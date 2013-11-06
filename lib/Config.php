<?php

Core::load('Config.DSL');

class Config implements Core_ModuleInterface
{
	protected static $options = array(
		'paths' => array(
			'app' => '../app/config/app.php',
			'site' => '../config/site.php',
		),
		'extends_path' => array(),
		'base_paths' => array('../app/config/', '../config/'),
	);

	public static function initialize($opts = array())
	{
		self::$options = Core_Arrays::deep_merge_update(self::$options, $opts);
	}

	protected static $instances = array();

	static public function core()
	{
		return self::get('core');
	}

	static public function site()
	{
		return self::get('site');
	}

	public static function app()
	{
		return self::get('app')->extend(self::site());
	}

	public static function configure()
	{
		return self::get('configure');
	}

	public static function all()
	{
		$res = self::app();
		foreach (self::$options['extends_path'] as $name => $path) {
			$res->$name = self::instance($path);
		}
		return $res;
	}

	public static function get($name)
	{
		if (isset(self::$options['paths'][$name])) {
			return self::instance(self::$options['paths'][$name]);
		}
		$path = self::path_for($name);
		self::$options['extends_path'][$name] = $path;
		return self::instance($path);
	}

	protected static function path_for($name)
	{
		$path = '';
		foreach (self::$options['base_paths'] as $bp) {
			$path = trim($bp, '/') . '/' . $name . '.php';
			if (is_file($path)) {
				return $path;
			}
		}
		return $path;
	}

	public static function instance($path)
	{
		if (!isset(self::$instances[$path])) {
			$inst = new Config_Instance();
			$inst->___path = $path;
			self::$instances[$path] = Config_DSL::Builder($inst)->load($path)->object;
		}
		return self::$instances[$path];
	}

}

class Config_Instance extends stdClass
{
	protected function include_file($name)
	{
		if (isset($this->___path) && ($dir = dirname($this->___path))) {
			$file = $dir . '/' . $name . '.php';
			$this->$name = Config_DSL::load($file);
		}
		return $this;
	}

	protected function extend_object($object)
	{
		$merge = array_merge((array) $this, (array) $object);
		foreach ($merge as $key => $value) {
			$this->$key = $value;
		}
		return $this;
	}

	public function extend($var)
	{
		if (is_string($var)) {
			return $this->include_file($var);
		} else {
			return $this->extend_object($var);
		}
	}

	public function write($path = null)
	{
		$path = !is_null($path) ? $path : $this->___path;
		if (!empty($path)) {
			Core::load('IO.FS');
			IO_FS::File($path)->update("<?php \nreturn " . var_export($this, true) . ';');
		}
	}

	public function read($path = null)
	{
		$path = !is_null($path) ? $path : $this->___path;
		if (!empty($path) && is_file($path)) {
			$obj = Config_DSL::Builder(new self())->load($path)->object;
			$this->extend($obj);
		}
		return $this;
	}

	public static function __set_state($vars = array())
	{
		$res = new self();
		foreach ($vars as $k => $v) {
			$res->$k = $v;
		}
		return $res;
	}
}