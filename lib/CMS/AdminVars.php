<?php

/**
 * @package CMS\AdminVars
 */
class CMS_AdminVars implements Core_ModuleInterface
{
	const MODULE = 'CMS.AdminVars';
	const VERSION = '0.0.0';

	static protected $options = array(
		'component' => array('name' => 'AdminVars', 'mapper' => 'CMS.AdminVars.Mapper', 'controller' => 'CMS.Controller.TreeAdminVars')
	);

	static function initialize($config = array())
	{
		self::options($config);
		CMS::add_component(self::$options['component']['name'], Core::make(self::$options['component']['mapper']));
		CMS_Admin::menu('lang:_vars:title', CMS::admin_path('vars'), 'hammer-screwdriver.png');
	}

	static function options($options = array())
	{
		self::$options = Core_Arrays::deep_merge_update(self::$options, $options);
	}

	static function option($name, $value = null)
	{
		$path = explode('/', $name);
		$v = null;
		foreach ($path as $p)
			$v = self::$options[$p];
		if (is_null($value)) {
			return $v;
		} else {
			self::options(array($name => $value));
		}
	}

}

class CMS_AdminVars_TreeMapper extends CMS_Mapper
{

	static protected $prefix = 'vars';

	public function route($request)
	{

		$this->get_component_name($request);

		$controller = CMS_AdminVars::option('component/controller');

		$this->controllers = array(
			'CMS.Controller.FieldsAdminVars' => array(
				'path' => CMS::admin_path(self::$prefix . '/' . 'parms'),
				'table-admin' => true,
			),
			'CMS.Controller.TreeAdminVars' => array(
				'path' => CMS::admin_path(self::$prefix),
				'rules' => array(
					'{^(\d+)$}' => array('{1}', 'action' => 'action_parms'),
				),
				'table-admin' => true,
			),
		);
		return parent::route($request);
	}

	protected function get_component_name($request)
	{
		$uri = $request->urn;
		$regexp = '{^' . rtrim(CMS::admin_path(self::$prefix), '/') . '(?:-([^/]+))?/(.*)$}';
		if (preg_match($regexp, $uri, $m)) {
			$component = false;
			if (trim($m[1]) != '') {
				$component = trim($m[1]);
				if (!CMS::component_exists($component)) {
					return false;
				}
				self::$prefix = self::$prefix . '-' . $component;
				WS::env()->adminvars = (object)array('component' => $component);
			}
		}
	}

	public function make_url()
	{
		$args = func_get_args();
		$path = CMS::admin_path() . self::$prefix . '/' . ($args[0] == 'dir' ? $args[1] : implode('/', $args));
		$component = WS::env()->vars->component;
		$url = $path . ($component ? '?component=' . $component : '');
		return $url;
	}

	public function make_uri()
	{
		$args = func_get_args();
		return Core::invoke(array($this, 'make_url'), $args);
	}

	public function attaches_url($id)
	{
		return CMS::admin_path(self::$prefix . '/attaches') . $id;
	}

	public function index_url()
	{
		return CMS::admin_path(self::$prefix);
	}

}

class CMS_AdminVars_Mapper extends WebKit_Controller_AbstractMapper
{

	static $component = false;

	public function route($request)
	{
		$uri = $request->urn;
		if (preg_match('{^' . CMS::admin_path() . 'vars(?:-([^/]+))?/(.*)$}', $uri, $m)) {
			$component = false;
			if (trim($m[1]) != '') {
				$component = trim($m[1]);
				if (!CMS::component_exists($component)) {
					return false;
				}
				self::$component = $component;

			}
			$path = trim($m[2]);
			$ctr = 'CMS.Controller.AdminVars';

			if ($path == '') {
				return array('controller' => $ctr, 'action' => 'index', 'id' => 0, 'component' => $component);
			}
			if (preg_match('{^(\d+)$}', $path, $m)) {
				return array('controller' => $ctr, 'action' => 'index', 'id' => $m[1], 'component' => $component);
			}
			if ($path == 'loaddump') {
				return array('controller' => $ctr, 'action' => 'loaddump');
			}
			if ($path == 'dump') {
				return array('controller' => $ctr, 'action' => 'dump', 'component' => $component);
			}
			if (preg_match('{(dumpvar|add|del|parms|chparms|change|addfile|delfile|imagelist|attaches)/(\d+)}', $path, $m)) {
				return array('controller' => $ctr, 'action' => $m[1], 'id' => $m[2], 'component' => $component);
			}
			return false;
		}
		return false;
	}

	public function attaches_url($id)
	{
		return CMS::admin_path('vars/attaches') . $id;
	}

	public function dump_url($component)
	{
		return CMS::admin_path() . 'vars' . (trim($component) == '' ? '' : "-$component") . '/dump';
	}

	public function loaddump_url()
	{
		return CMS::admin_path('vars') . 'loaddump';
	}

	public function index_url()
	{
		return CMS::admin_path('vars');
	}

	public function make_uri()
	{
		$args = func_get_args();
		$path = 'vars' . (self::$component ? '-' . self::$component : '');
		if ($args[0] == 'dir') {
			return CMS::admin_path() . "$path/" . $args[1];
		}
		return CMS::admin_path() . "$path/" . $args[0] . '/' . $args[1];
	}

}
