<?php
/**
 * @package CMS\CLI
 */

Core::load('CMS');
Core::load('CLI','CLI.GetOpt');


class CMS_CLI implements Core_ModuleInterface {
	const MODULE  = 'CMS.CLI';
	const VERSION = '0.0.0';
	
	static $parms = false;
	static $options = false;
	static $script = false;
	
	static function process() {

		//CMS::$env->urls = WebKit_Controller::Mapper();
		//foreach(CMS::$mappers as $name => $mapper) CMS::$env->urls->map(strtolower($name),$mapper);


		$c = 0;
		self::$parms = array();
		self::$options = array();
		$argv = $GLOBALS['argv'];
		foreach($argv as $arg) {
			$arg = trim($arg);
			
			if ($m = Core_Regexps::match_with_results('{^--([^\s=]+)=(.*)}',$arg)) {
				self::$options[$m[1]] = $m[2];
			}
			
			else if ($m = Core_Regexps::match_with_results('{^--([^\s=]+)}',$arg)) {
				self::$options[$m[1]] = true;
			}
			
			else {
				if ($c==0) {
					self::$script = $arg;
				}
				
				else {
					self::$parms[$c-1] = $arg;
				}
				
				$c++;
			}
		}
	}
	
	static function parms() {
		if (!self::$parms) self::process();
		return self::$parms;
	}

	static function opt($name) {
		$options = self::options();
		return $options[$name];
	}
	
	static function options() {
		if (!self::$options) self::process();
		return self::$options;
	}
	
	static function script() {
		if (!self::$script) self::process();
		return self::$script;
	}

	static function run_handler($module,$action,$args,$options) {
		if (is_file(Core::loader()->file_path_for($module))) {
			Core::load($module);
			return Core::make($module)->_args($args)->_options($options)->run($action);
		}
	}

	static function run() {
		$_dir = getcwd();
		if ($m = Core_Regexps::match_with_results('{^(.+)/index\.php$}',$_SERVER['PHP_SELF'])) chdir($m[1]);

		$args = (array)self::parms();
		$options = (array)self::options();
		$action = false;

		if (sizeof($args)>0) {
			$action = array_shift($args);
		}

		self::run_handler('App.CLI',$action,$args,$options);
		$cli_path = IO_FS::File(Core::loader()->file_path_for('App.CLI.Index', true))->dir_name;
		if (IO_FS::exists($cli_path)) {
			$dir = IO_FS::Dir($cli_path);
			foreach($dir as $entry) {
				$entry = preg_replace('{\.php$}','',$entry->name);
				self::run_handler("App.CLI.$entry",$action,$args,$options);
			}
		}
		foreach(CMS::$component_original_names as $component) self::run_handler("Component.$component.CLI",$action,$args,$options);

		chdir($_dir);
	}
	
}


class CMS_CLI_Handler {

	protected $args = array();
	protected $options = array();

	public function __construct() {}

	public function _args($args) {
		$this->args = $args;
		return $this;
	}

	public function _options($options) {
		$this->options = $options;
		return $this;
	}

	public function run($action) {
		if ($action&&method_exists($this,$action)) {
			return $this->$action();
		}
	}

}

