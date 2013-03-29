<?php
/// <module name="CMS.Admin" maintainer="gusev@techart.ru" version="0.0.0">

/// <class name="CMS.Admin" stereotype="module">
///   <implements interface="Core.ModuleInterface" />
///   <depends supplier="Webkit.Session" stereotype="uses" />
class CMS_Admin implements Core_ModuleInterface { 
	
///   <constants>
	const MODULE = 'CMS.Admin'; 
	const VERSION = '0.0.0'; 
///   </constants>
	
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
	
	static $logo = '/image/admin/logo.gif';
	static $stdstyles = array();

	static $jquery = false;

/// <protocol name="creating">
	
	
///   <method scope="class" name="initialize">
///     <args>
///       <arg name="config" type="array" default="array()" />
///     </args>
///     <body>
	static function initialize($config=array()) { 
		foreach($config as $key => $value) self::$$key = $value;
		CMS::$admin = self::$path;
		//if (!self::$jquery) self::$jquery = CMS::stdfile_url('scripts/jquery-1.4.2.js');
		if (!self::$jquery) self::$jquery = 'jquery.js';
		$session = WS::env()->request->session();
		if (isset($session['admin/site'])) { 
			self::$site = $session['admin/site']; 
		} 
		
		else { 
			self::$site = CMS::$defsite; 
			$session['admin/site'] = self::$site; 
		} 
	} 
///     </body>
///   </method>
/// </protocol>
	
/// <protocol name="quering">
	
///   <method scope="class" name="path" returns="string">
///     <body>
	static function path() {
		return trim(trim(self::$path,'/'));
	}
///     </body>
///   </method>
	
///   <method scope="class" name="site" returns="string">
///     <body>
	static function site() { 
		return self::get_site(); 
	} 
///     </body>
///   </method>
	
///   <method scope="class" name="host" returns="string">
///     <body>
	static function host() { 
		return self::$host; 
	} 
///     </body>
///   </method>
/// </protocol>
	
/// <protocol name="accessing">
	
///   <method scope="class" name="set_site">
///     <args>
///       <arg name="site" type="string" />
///     </args>
///     <body>
	static function set_site($site) { 
		$session = WS::env()->request->session();
		self::$site = $site; 
		$session['admin/site'] = $site; 
	} 
///     </body>
///   </method>
	
///   <method scope="class" name="get_site" returns="string">
///     <body>
	static function get_site() { 
		$session = WS::env()->request->session();
		if (isset($session['admin/site'])) { 
			return $session['admin/site']; 
		} 
		
		else { 
			return CMS::$defsite; 
		} 
	} 
///     </body>
///   </method>
/// </protocol>
	
/// <protocol name="performing">
	
	static function add_menu_item($item=array()) { 
		self::menu($item['t'],$item['u']);
	} 

///   <method scope="class" name="help" returns="string">
///     <body>
	static function help($name,$component=false) {
		if (!$component) $component = CMS::$current_component_name;
		$lang = self::$lang;
		return "$lang/$component/$name";
	}
///     </body>
///   </method>

///   <method scope="class" name="layout" returns="string">
///     <body>
	static function layout() {
		return CMS::view('admin-layout.phtml');
	}
///     </body>
///   </method>



	static $embedded_admin_menu_builded = false;

///   <method scope="class" name="build_embedded_admin_menu">
///     <body>
	static function build_embedded_admin_menu($set) {
		if (self::$embedded_admin_menu_builded) return;
		self::$embedded_admin_menu_builded = true;
		$components = array_flip(CMS::$component_names);
		foreach($components as $class => $name) {
			if (class_exists($class)) {
				if (method_exists($class,'embedded_admin_menu')) {
					call_user_func(array($class,'embedded_admin_menu'),$set);
				}
			}
		}
	}
///     </body>
///   </method>

///   <method scope="class" name="embedded_admin_menu" returns="string">
///     <body>
	static function embedded_admin_menu($style='') {
		$menu = CMS::navigation()->admin();
		if (!$menu||$menu->count()==0) return '';
		ob_start();
		if (IO_FS::exists(CMS::app_path('views/embedded-admin-menu.phtml'))) include CMS::app_path('views/embedded-admin-menu.phtml');
		else include(CMS::views_path('embedded-admin-menu.phtml'));
		$contents = ob_get_contents();
		ob_end_clean();
		return $contents;
	}
///     </body>
///   </method>


///   <method scope="class" name="empty_layout" returns="string">
///     <body>
	static function empty_layout() {
		return CMS::view('admin-empty-layout.phtml');
	}
///     </body>
///   </method>

///   <method scope="class" name="logo" returns="string">
///     <body>
	static function logo() {
		if (IO_FS::exists('.'.self::$logo)) return self::$logo; 
		return CMS::stdfile_url('images/logo.gif');
	}
///     </body>
///   </method>
	
	
///   <method scope="class" name="menu">
///     <args>
///       <arg name="title" type="string" />
///       <arg name="uri" type="string" />
///       <arg name="icon" type="string|false" default="false" />
///       <arg name="submenu" type="array|false" default="false" />
///     </args>
///     <body>
	static function menu($title,$item,$p1=false,$p2=false) {
		//self::menu_process($title,$item,$p1,$p2);
		self::$admin_menu_src[] = array($title,$item,$p1,$p2);
	}
///     </body>
///   </method>


///   <method scope="class" name="get_menu">
///     <body>
	static function get_menu() {
		if (!self::$menu) {
			foreach(self::$admin_menu_src as $m) {
				list($title,$item,$p1,$p2) = $m;
				self::menu_process($title,$item,$p1,$p2);
			}
		}
		return self::$menu;
	}
///     </body>
///   </method>


///   <method scope="class" name="menu_process">
///     <args>
///       <arg name="title" type="string" />
///       <arg name="uri" type="string" />
///       <arg name="icon" type="string|false" default="false" />
///       <arg name="submenu" type="array|false" default="false" />
///     </args>
///     <body>
	static function menu_process($title,$item,$p1=false,$p2=false) {
		$sub = false;
		$icon = 'default';
		if (is_array($p1)) $sub = $p1;
		if (is_array($p2)) $sub = $p2;
		if (is_string($p1)) $icon = $p1;
		if (is_string($p2)) $icon = $p2;
		self::$admin_menu[$title] = $item;

		if (!Core_Regexps::match('{\.([a-z]+)$}',$icon)) $icon .= '.gif';

		if (IO_FS::exists("./image/admin/components/$icon")) $icon = "/image/admin/components/$icon";
		else if (IO_FS::exists(CMS::stdfile("images/components/$icon"))) $icon = CMS::stdfile_url("images/components/$icon");
		else $icon = CMS::stdfile_url('images/components/default.gif');

		self::$menu[] = array('t'=>$title,'u' => $item,'s'=>$sub,'i'=>$icon);
	}
///     </body>
///   </method>


///   <method scope="class" name="subsites_menu" returns="string">
///     <body>
	static function subsites_menu() {
		if (!isset(CMS::$sites)) return false;
		ob_start();
		
		$tpl = self::$sites_tpl.'.phtml';
		
		if (IO_FS::exists("../app/views/$tpl")) {
			include("../app/views/$tpl");
		}	
		
		else {
			include(CMS::view("$tpl"));
		}
		
		$content = ob_get_clean();
		return $content;
	}
///     </body>
///   </method>
	
///   <method scope="class" name="admin_menu" returns="string">
///     <args>
///       <arg name="access" type="string" default="full" />
///     </args>
///     <body>
	static function admin_menu($access='full') {
		if (!CMS::$globals[$access]) return '';
		ob_start();
		
		$tpl = self::$admin_menu_tpl.'.phtml';
		if (IO_FS::exists("../app/views/$tpl")) {
			include("../app/views/$tpl");
		}	
		
		else {
			include(CMS::view("$tpl"));
		}
		
		$content = ob_get_clean();
		return $content;
	}
///     </body>
///   </method>

///   <method scope="class" name="component_icon" returns="string">
///     <args>
///       <arg name="name" type="string" />
///     </args>
///     <body>
	static function component_icon($name) {
		return CMS::stdfile_url('images/components/default.gif');
	}
///     </body>
///   </method>

	
/// </protocol>
	
} 
/// </class>

/// </module>

