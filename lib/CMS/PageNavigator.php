<?php
/// <module name="CMS.PageNavigator" maintainer="gusev@techart.ru" version="0.0.0">

/// <class name="CMS.PageNavigator" stereotype="module">
///   <implements interface="Core.ModuleInterface" />

class CMS_PageNavigator implements Core_ModuleInterface { 

///   <constants>
	const MODULE = 'CMS.PageNavigator'; 
	const VERSION = '0.0.0'; 
///   </constants>
	
	static $url_template; 
	static $current; 
	static $current_link = false;
	static $template = '../app/views/page-navigator.phtml'; 

///   <protocol name="creating">

///   <method scope="class" name="initialize">
///     <args>
///       <arg name="config" type="array" default="array()" />
///     </args>
///     <body>
	static function initialize($config=array()) { 
		foreach($config as $key => $value) self::$$key = $value;
		CMS::$page_navigator = new ReflectionMethod('CMS_PageNavigator','run'); 
	} 
///     </body>
///   </method>
	
///   </protocol>	

///   <protocol name="performing">

///   <method scope="class" name="run" returns="string">
///     <args>
///       <arg name="page" type="int" />
///       <arg name="numpages" type="int" />
///       <arg name="url_template" type="string" />
///     </args>
///     <body>
	static function run($page,$numpages,$tpl) { 
		if ($numpages<2) return ''; 
		
		self::$url_template = $tpl; 
		self::$current = $page; 
		
		ob_start(); 
		if (IO_FS::exists(self::$template)) { 
			include(self::$template); 
		} 
		
		else { 
			include(CMS::view('page-navigator.phtml')); 
		} 
		
		$out = ob_get_clean(); 
		return $out; 
	} 
///     </body>
///   </method>
	

///   </protocol>	

///   <protocol name="supporting">

///   <method scope="class" name="url" returns="string">
///     <args>
///       <arg name="page" type="int" />
///     </args>
///     <body>
	static function url($page) {
		if (is_callable(self::$url_template)) {
			return call_user_func(self::$url_template,$page);
		}
		$s = preg_replace('{\%$}',$page,self::$url_template); 
		$s = preg_replace('{\%([^0-9a-z])}i',"$page\\1",$s); 
		return $s; 
	} 
///     </body>
///   </method>
	
///   <method scope="class" name="link" returns="string">
///     <args>
///       <arg name="page" type="int" />
///       <arg name="link_text" type="string|false" />
///     </args>
///     <body>
	static function link($i,$t=false) {
		if (!$t) $t = $i; 
		if (!self::$current_link&&$i==self::$current) return "<b>$t</b>";
		return '<a href="'. (self::url($i)).'"'.($i==self::$current?' class="current"':'') .">$t</a>";
	} 
///     </body>
///   </method>
	

///   </protocol>	
	
} 
/// </class>

/// </module>
