<?php
/**
 * @package Config
 */


Core::load('Config.DSL');

class Config implements Core_ModuleInterface
{
	protected static $options = array(
		'instance_class' => 'Config.Instance'
	);

	protected static $instances = array();

	public static function initialize($opts = array())
	{
		self::$options['base_paths'] = array(Core::tao_config_dir(), '../app/config/', '../config/');
		self::$options = Core_Arrays::deep_merge_update(self::$options, $opts);
	}

	static public function modules()
	{
		return self::get('modules');
	}

	static public function site()
	{
		return self::get('site');
	}

	public static function app()
	{
		return self::get('site');
	}

	/**
	* @todo: find all files & load ?
	*/
	public static function all()
	{
		return self::app();
	}

	public static function get($name)
	{
		if (isset(self::$instances[$name])) {
			return self::$instances[$name];
		}
		$paths = self::paths_for($name);
		return self::$instances[$name] = self::instance($paths);
	}

	protected static function paths_for($name)
	{
		$paths = array();
		foreach (self::$options['base_paths'] as $bp) {
			$path = rtrim($bp, '/') . '/' . $name . '.php';
			if (is_file($path)) {
				$paths[] = $path;
			}
		}
		return $paths;
	}

	protected static function instance($paths)
	{
		$paths = (array) $paths;
		$res = null;
		foreach ($paths as $path) {
			$inst = self::make_instance();
			$inst->___path = $path;
			$inst = Config_DSL::Builder($inst)->load($path)->object;
			if (is_null($res)) {
				$res = $inst;
			} elseif (is_array($res) && is_array($inst)) {
				$res = array_replace_recursive($res, $inst);
			} else {
				$res = $res->extend($inst);
			}
		}
		return is_null($res) ? self::make_instance(): $res;
	}

	protected static function make_instance()
	{
		return Core::make(self::$options['instance_class']);
	}

}

class Config_Instance extends stdClass
{
	protected function include_file($name, $inplace = false)
	{
		if (isset($this->___path) && ($dir = dirname($this->___path))) {
			$file = $dir . '/' . $name . '.php';
			$data = Config_DSL::load($file);
			if ($inplace) {
				$this->extend_object($data);
			} else {
				$this->$name = $data;
			}
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

	public function extend($var, $inplace = false)
	{
		if (is_string($var)) {
			return $this->include_file($var, $inplace);
		} else {
			return $this->extend_object($var);
		}
	}

	public function write($path = null)
	{
		$path = !is_null($path) ? $path : $this->___path;
		if (!empty($path)) {
			Core::load('IO.FS');
			IO_FS::File($path)->update("<?php
/**
 * @package Config
 */
 \nreturn " . var_export($this, true) . ';');
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