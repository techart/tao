<?php
/**
 * CMS.Admin
 *
 * @package CMS\Admin
 * @version 0.0.0
 */

/**
 * @package CMS\Admin
 */
class CMS_Admin implements Core_ModuleInterface
{

	const MODULE = 'CMS.Admin';
	const VERSION = '0.0.0';

	static $config;
	static $site;
	static $menu = false;
	static $admin_menu = false;
	static $admin_menu_src = array();

	static $host = false;
	static $path = 'admin';

	static $sites_tpl = 'admin-sites';
	static $admin_menu_tpl = 'admin-menu';

	static $lang = 'ru';

	static $logo = '/images/admin/logo.gif';
	static $stdstyles = array();

	static $jquery = false;

	/**
	 * @param array $config
	 */
	static function initialize($config = array())
	{
		foreach ($config as $key => $value)
			self::$$key = $value;
		CMS::$admin = self::$path;
		//if (!self::$jquery) self::$jquery = CMS::stdfile_url('scripts/jquery-1.4.2.js');
		if (!self::$jquery) {
			self::$jquery = '/tao/scripts/jquery.js';
		}
		self::$site = CMS::$defsite;
	}

	/**
	 * @return string
	 */
	static function path()
	{
		return trim(trim(self::$path, '/'));
	}

	/**
	 * @return string
	 */
	static function site()
	{
		return self::get_site();
	}

	/**
	 * @return string
	 */
	static function host()
	{
		return self::$host;
	}

	/**
	 * @param string $site
	 */
	static function set_site($site)
	{
		self::$site = $site;
	}

	/**
	 * @return string
	 */
	static function get_site()
	{
		return self::$site;
	}

	static function add_menu_item($item = array())
	{
		self::menu($item['t'], $item['u']);
	}

	/**
	 * @return string
	 */
	static function help($name, $component = false)
	{
		if (!$component) {
			$component = CMS::$current_component_name;
		}
		$lang = self::$lang;
		return "$lang/$component/$name";
	}

	/**
	 * @return string
	 */
	static function layout()
	{
		return CMS::view('layouts/admin.phtml');
	}

	static $embedded_admin_menu_builded = false;

	/**
	 */
	static function build_embedded_admin_menu($set)
	{
		if (self::$embedded_admin_menu_builded) {
			return;
		}
		self::$embedded_admin_menu_builded = true;
		$components = array_flip(CMS::$component_names);
		foreach ($components as $class => $name) {
			if (class_exists($class)) {
				if (method_exists($class, 'embedded_admin_menu')) {
					call_user_func(array($class, 'embedded_admin_menu'), $set);
				}
			}
		}
	}

	/**
	 * @return string
	 */
	static function embedded_admin_menu($style = '')
	{
		$menu = CMS::navigation()->admin();
		if (!$menu || $menu->count() == 0) {
			return '';
		}
		ob_start();
		if (IO_FS::exists(CMS::app_path('views/embedded-admin-menu.phtml'))) {
			include CMS::app_path('views/embedded-admin-menu.phtml');
		} else {
			include(CMS::views_path('embedded-admin-menu.phtml'));
		}
		$contents = ob_get_contents();
		ob_end_clean();
		return $contents;
	}

	/**
	 * @return string
	 */
	static function empty_layout()
	{
		return CMS::view('admin-empty-layout.phtml');
	}

	/**
	 * @return string
	 */
	static function logo()
	{
		if (IO_FS::exists('.' . self::$logo)) {
			return self::$logo;
		}
		return CMS::stdfile_url('images/logo.gif');
	}

	/**
	 * @param string       $title
	 * @param string       $uri
	 * @param string|false $icon
	 * @param array|false  $submenu
	 */
	static function menu($title, $item, $p1 = false, $p2 = false)
	{
		//self::menu_process($title,$item,$p1,$p2);
		self::$admin_menu_src[] = array($title, $item, $p1, $p2);
	}

	/**
	 */
	static function get_menu()
	{
		if (!self::$menu) {
			foreach (self::$admin_menu_src as $m) {
				list($title, $item, $p1, $p2) = $m;
				self::menu_process($title, $item, $p1, $p2);
			}
		}
		return self::$menu;
	}

	/**
	 * @param string       $title
	 * @param string       $uri
	 * @param string|false $icon
	 * @param array|false  $submenu
	 */
	static function menu_process($title, $item, $p1 = false, $p2 = false)
	{
		$sub = false;
		$icon = 'default';
		if (is_array($p1)) {
			$sub = $p1;
		}
		if (is_array($p2)) {
			$sub = $p2;
		}
		if (is_string($p1)) {
			$icon = $p1;
		}
		if (is_string($p2)) {
			$icon = $p2;
		}
		self::$admin_menu[$title] = $item;

		if (!Core_Regexps::match('{\.([a-z]+)$}', $icon)) {
			$icon .= '.gif';
		}

		if (IO_FS::exists("./image/admin/components/$icon")) {
			$icon = "/image/admin/components/$icon";
		} else {
			if (IO_FS::exists(CMS::stdfile("images/components/$icon"))) {
				$icon = CMS::stdfile_url("images/components/$icon");
			} else {
				$icon = CMS::stdfile_url('images/components/default.gif');
			}
		}

		self::$menu[] = array('t' => $title, 'u' => $item, 's' => $sub, 'i' => $icon);
	}

	/**
	 * @return string
	 */
	static function subsites_menu()
	{
		if (!isset(CMS::$sites)) {
			return false;
		}
		ob_start();

		$tpl = self::$sites_tpl . '.phtml';

		if (IO_FS::exists("../app/views/$tpl")) {
			include("../app/views/$tpl");
		} else {
			include(CMS::view("$tpl"));
		}

		$content = ob_get_clean();
		return $content;
	}

	/**
	 * @param string $access
	 *
	 * @return string
	 */
	static function admin_menu($access = 'full')
	{
		if (!CMS::$globals[$access]) {
			return '';
		}
		ob_start();

		$tpl = self::$admin_menu_tpl . '.phtml';
		if (IO_FS::exists("../app/views/$tpl")) {
			include("../app/views/$tpl");
		} else {
			include(CMS::view("$tpl"));
		}

		$content = ob_get_clean();
		return $content;
	}

	/**
	 * @param string $name
	 *
	 * @return string
	 */
	static function component_icon($name)
	{
		return CMS::stdfile_url('images/components/default.gif');
	}

}


