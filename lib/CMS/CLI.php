<?php
/**
 * @package CMS\CLI
 */

Core::load('CMS');
Core::load('CLI', 'CLI.GetOpt');

class CMS_CLI implements Core_ModuleInterface
{
	const MODULE = 'CMS.CLI';
	const VERSION = '0.0.0';

	static $parms = false;
	static $options = false;
	static $script = false;

	static function process()
	{
		$c = 0;
		self::$parms = array();
		self::$options = array();
		$argv = $GLOBALS['argv'];
		foreach ($argv as $arg) {
			$arg = trim($arg);

			if ($m = Core_Regexps::match_with_results('{^--([^\s=]+)=(.*)}', $arg)) {
				self::$options[$m[1]] = $m[2];
			} else {
				if ($m = Core_Regexps::match_with_results('{^--([^\s=]+)}', $arg)) {
					self::$options[$m[1]] = true;
				} else {
					if ($c == 0) {
						self::$script = $arg;
					} else {
						self::$parms[$c - 1] = $arg;
					}

					$c++;
				}
			}
		}
	}

	static function parms()
	{
		if (!self::$parms) {
			self::process();
		}
		return self::$parms;
	}

	static function opt($name)
	{
		$options = self::options();
		return $options[$name];
	}

	static function options()
	{
		if (!self::$options) {
			self::process();
		}
		return self::$options;
	}

	static function script()
	{
		if (!self::$script) {
			self::process();
		}
		return self::$script;
	}

	static function run_handler($module, $action, $args, $options)
	{
		if (is_file(Core::loader()->file_path_for($module))) {
			$instance = Core::make($module);
			if ($instance instanceof CMS_CLI_Handler
			   || (method_exists($instance, '_args') && method_exists($instance, '_options') && method_exists($instance, 'run'))
			) {
				return Core::make($module)->_args($args)->_options($options)->run($action);
			}
		}
	}

	static function run()
	{
		$_dir = getcwd();
		if ($m = Core_Regexps::match_with_results('{^(.+)/index\.php$}', $_SERVER['PHP_SELF'])) {
			chdir($m[1]);
		}

		$args = (array)self::parms();
		$options = (array)self::options();
		$action = false;

		if (sizeof($args) > 0) {
			$action = array_shift($args);
		}

		self::run_handler('CMS.CLI.Utils', $action, $args, $options);
		self::run_handler('App.CLI', $action, $args, $options);
		$cli_path = IO_FS::File(Core::loader()->file_path_for('App.CLI.Index', true))->dir_name;
		if (IO_FS::exists($cli_path)) {
			$dir = IO_FS::Dir($cli_path);
			foreach ($dir as $entry) {
				$entry = preg_replace('{\.php$}', '', $entry->name);
				self::run_handler("App.CLI.$entry", $action, $args, $options);
			}
		}
		foreach (CMS::$component_original_names as $component) {
			$comp_cli = "Component.$component.CLI";
			self::run_handler($comp_cli, $action, $args, $options);
			//$cli_path = IO_FS::File(Core::loader()->file_path_for("$comp_cli.Index", true))->dir_name;
			$cli_path = "../app/components/{$component}/lib/CLI";
			if (IO_FS::exists($cli_path)) {
				$dir = IO_FS::Dir($cli_path);
				foreach ($dir as $entry) {
					$entry = preg_replace('{\.php$}', '', $entry->name);
					self::run_handler("$comp_cli.$entry", $action, $args, $options);
				}
			}
		}
		
		chdir($_dir);
	}
	
	static function get_call_time($name)
	{
		static $schema_checked = false;
		if (!$schema_checked) {
			CMS::cached_run('CMS.Schema.CLI');
			$schema_checked = true;
		}
		$row = CMS::orm()->connection->prepare('SELECT * FROM tao_cli_calls WHERE name=:name ORDER BY time desc LIMIT 1')->bind(array('name' => $name))->execute()->fetch();
		if (!$row) {
			return 0;
		}
		return $row['time'];
	}
	
	static function set_call_time($name)
	{
		CMS::orm()->connection->prepare('DELETE FROM tao_cli_calls WHERE name=:name')->bind(array('name' => $name))->execute();
		CMS::orm()->connection->prepare('INSERT INTO tao_cli_calls SET name=:name, time=:time')->bind(array('name' => $name, 'time' => time()))->execute();
	}
}

class CMS_CLI_Handler
{

	protected $args = array();
	protected $options = array();

	public function __construct()
	{
	}

	public function _args($args)
	{
		$this->args = $args;
		return $this;
	}

	public function _options($options)
	{
		$this->options = $options;
		return $this;
	}

	public function run($action)
	{
		if ($action=='cli_dispatcher') {
			$class = get_class($this);
			$methods = get_class_methods($this);
			$thistime = (int)date('H')*60+(int)date('i');
			$today = date('Ymd');
			foreach($methods as $method) {
				$callname = "{$class}::{$method}";
				if ($method=='daily_service') {
					$dstime = trim(WS::env()->config->cli->daily_service);
					if ($m = Core_Regexps::match_with_results('{^(\d+)[^\d]+(\d+)$}',$dstime)) {
						$calltime = ((int)$m[1])*60+(int)$m[2];
						if ($thistime>=$calltime) {
							$last = CMS_CLI::get_call_time($callname);
							$lastday = date('Ymd',$last);
							if ($today!=$lastday) {
								CMS_CLI::set_call_time($callname);
								$this->run_action($method);
							}
						}
					}
				}
				else if ($m = Core_Regexps::match_with_results('{^every(\d+)$}',$method)) {
					$d = ((int)$m[1])*60;
					$last = CMS_CLI::get_call_time($callname);
					if (time()-$d>$last) {
						CMS_CLI::set_call_time($callname);
						$this->run_action($method);
					}
				}
				elseif ($m = Core_Regexps::match_with_results('{^daily(\d+)_(\d+)$}',$method)) {
					$calltime = ((int)$m[1])*60+(int)$m[2];
					if ($thistime>=$calltime) {
						$last = CMS_CLI::get_call_time($callname);
						$lastday = date('Ymd',$last);
						if ($today!=$lastday) {
							CMS_CLI::set_call_time($callname);
							$this->run_action($method);
						}
					}
				}
			}
		} else {
			return $this->run_action($action);
		}
	}

	public function run_action($action)
	{
		if ($action && method_exists($this, $action)) {
			return $this->$action();
		}
	}

}

