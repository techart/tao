<?php

/**
 * CMS.Vars
 *
 * @package CMS\Vars
 * @version 0.0.0
 */

/**
 * @package CMS\Vars
 */
class CMS_Vars implements Core_ModuleInterface
{

	const VERSION = '0.0.0';

	static $types = array();
	static $cache = array();
	static $pcache = array();
	static $plugins_on_change = array();
	static $files_dir;
	static $type = 'orm';

	/**
	 * @param array $config
	 */
	static function initialize($config = array())
	{
		self::$files_dir = './' . Core::option('files_name') . '/vars';
		foreach ($config as $key => $value)
			self::$$key = $value;

		Core::load('CMS.Vars.Types');
		if (self::$type == 'orm') {
			Core::load('CMS.Vars.ORM');
			WS::env()->orm->submapper('vars', 'CMS.Vars.ORM.Mapper');
		}
		if (self::$type == 'storage') {
			Core::load('Storage');
			Storage::manager()->add('vars', 'CMS.Vars.Storage');
		}
		CMS::cached_run('CMS.Vars.Schema');

		self::register_type(
			'CMS.Vars.Types.Dir',
			'CMS.Vars.Types.Integer',
			'CMS.Vars.Types.String',
			'CMS.Vars.Types.Text',
			'CMS.Vars.Types.Html',
			'CMS.Vars.Types.Array',
			'CMS.Vars.Types.Mail',
			'CMS.Vars.Types.HtmlP',
			'CMS.Vars.Types.File'
		);

		CMS_Dumps::dumper('VARS', 'CMS.Dumps.Vars');

	}

	static public function storage_type()
	{
		return self::$type;
	}

	/**
	 * @param string $name
	 * @param string $class
	 * @param string $method
	 */
	static function on_change($fc, $class, $method)
	{
		self::$plugins_on_change[$fc][] = array($class, $method);
	}

	/**
	 */
	static function register_type()
	{
		$args = func_get_args();
		foreach ($args as $class) {
			$instance = Core::make($class);;
			$type = $instance->type();
			self::$types[$type] = $instance;
		}
	}

	static public function type($name)
	{
		return self::$types[$name];
	}

	static public function types()
	{
		return self::$types;
	}

	static public function db()
	{
		switch (self::$type) {
			case 'orm':
				return WS::env()->orm->vars;
			case 'storage':
				return Storage::manager()->vars;
		}
	}

	/**
	 */
	static function set()
	{
		$args = func_get_args();
		if (sizeof($args) < 2) {
			return;
		}
		$name = $args[0];
		$site = '__';
		$component = '';
		if (sizeof($args) == 2) {
			$value = $args[1];
		}

		if (sizeof($args) > 2) {
			$site = $args[1];
			$value = $args[2];
		}

		if (sizeof($args) > 3) {
			$component = $args[2];
			$value = $args[3];
		}

		$var = self::get_var_by_parms($name, $site, $component);

		if (!$var) {
			return false;
		}
		$value = self::$types[$var->vartype]->set($var, $value);
		self::reset_cache($name, $site, $component);
	}

	/**
	 * @param string       $name
	 * @param string|false $site
	 *
	 * @return string
	 */
	static function title($name, $site = false)
	{
		if (!$site) {
			$site = CMS::site();
		}
		$var = self::get_var_by_parms($name, $site, '');
		if (!$var) {
			return false;
		}
		return $var->title;
	}

	/**
	 * @param string      $name
	 * @param array|false $parms
	 */
	static function get($name, $p = false)
	{
		$site = false;
		if (is_string($p)) {
			$site = $p;
		}
		if (!$site) {
			$site = CMS::site();
		}
		$var = self::get_var_by_parms($name, $site, '');
		if (!$var) {
			return false;
		}
		$value = self::$types[$var->vartype]->get($var, $p);
		return $value;
	}

	/**
	 * @param string      $name
	 * @param array|false $parms
	 */
	static function my($name, $p = false)
	{
		return self::get_for_component(CMS::$current_component_name, $name, $p);
	}

	/**
	 * @param string $name
	 */
	static function random($name, $component = '')
	{
		$var = self::get_var_by_parms($name, CMS::site(), $component);
		if (!$var) {
			return false;
		}
		while ($var->vartype == 'dir') {
			$vars = (array)self::db()->find_dir($var->id, CMS::site(), $component)->select();
			shuffle($vars);
			$var = $vars[0];
		}
		return self::$types[$var->vartype]->get($var);
	}

	/**
	 * @param array $name
	 */
	static function get_list($name, $component = '', $rec = false)
	{
		$var = self::get_var_by_parms($name, CMS::site(), $component);
		if (!$var) {
			return false;
		}
		$out = array();
		while ($var->vartype == 'dir') {
			$vars = (array)self::db()->find_dir($var->id, CMS::site(), $component)->select();
			$c = 0;
			foreach ($vars as $var) {
				$c++;
				$code = trim($var->code);
				if ($code == '') {
					$code = "_$c";
				}
				$out[$code] = self::$types[$var->vartype]->get($var);
			}
		}
		return $out;
	}

	/**
	 * @param string      $name
	 * @param string      $site
	 * @param string      $component
	 * @param array|false $parms
	 */
	static function get_by_parms($name, $site, $component, $p = false)
	{
		$var = self::get_var_by_parms($name, $site, $component);
		if (!$var) {
			return false;
		}
		$value = self::$types[$var->vartype]->get($var, $p);
		return $value;
	}

	/**
	 * @param string $name
	 * @param string $site
	 * @param string $component
	 */

	static function reset_cache($name, $site, $component)
	{
		$cname = self::cache_key($name, $site, $component);
		if (isset(self::$cache[$cname])) {
			unset(self::$cache[$cname]);
			$codes = explode('.', $name);
			$id = 0;
			foreach ($codes as $code) {
				$code = trim($code);
				if ($code != '') {
					$cparms = array(
						'parent_id' => $id,
						'code' => $code,
						'site' => $site,
						'component' => $component,
					);
					$_cparms = md5(serialize($cparms));
					if (isset(self::$pcache[$_cparms])) {
						$id = self::$pcache[$_cparms]->id;
						unset(self::$pcache[$_cparms]);
					}
				}
			}
		}
	}

	static function cache_key($name, $site, $component)
	{
		return "$component:$name/$site";
	}

	static function get_var_by_parms($name, $site, $component)
	{
		$cname = self::cache_key($name, $site, $component);
		if (isset(self::$cache[$cname])) {
			return self::$cache[$cname];
		}
		$codes = explode('.', $name);
		$id = 0;
		$var = false;
		foreach ($codes as $code) {
			$code = trim($code);
			if ($code != '') {
				$cparms = array(
					'parent_id' => $id,
					'code' => $code,
					'site' => $site,
					'component' => $component,
				);
				$_cparms = md5(serialize($cparms));
				if (isset(self::$pcache[$_cparms])) {
					$var = self::$pcache[$_cparms];
				} else {
					$var = self::db()->for_code($cparms)->select_first();
					self::$pcache[$_cparms] = $var;
				}
				if (!$var) {
					return false;
				}
				$id = $var->id;
			}
		}
		self::$cache[$cname] = $var;
		return $var;
	}

	/**
	 * @param string      $component
	 * @param string      $name
	 * @param array|false $parms
	 */
	static function get_for_component($component, $name, $p = false)
	{
		$site = false;
		if (is_string($p)) {
			$site = $p;
		}
		if (!$site) {
			$site = CMS::site();
		}
		$var = self::get_var_by_parms($name, $site, strtolower($component));

		if (!$var) {
			return false;
		}
		$v = self::$types[$var->vartype];
		$value = self::$types[$var->vartype]->get($var, $p);

		return $value;
	}

	/**
	 * @param mixed $value
	 */
	static function validate_parm($value)
	{
		if (!is_string($value)) {
			return $value;
		}
		while ($m = Core_Regexps::match_with_results('{^var:(.+)$}', trim($value))) {
			$value = CMS_Vars::get($m[1]);
		}
		return $value;
	}

	static public function on_change_call($id, $value, $data)
	{
		$fc = self::db()->full_code($id);
		if (!empty(CMS_Vars::$plugins_on_change[$fc])) {
			$rc = true;
			foreach (CMS_Vars::$plugins_on_change[$fc] as $m) {
				$class = trim($m[0]);
				$method = trim($m[1]);
				if ($class != '' && $method != '') {
					$r = Core::invoke(array($class, $method), array($value, $fc));
					if (is_string($r)) {
						return $r;
					}
					$rc = $rc && $r;
				}
			}
			return $rc;
		}
	}

}

