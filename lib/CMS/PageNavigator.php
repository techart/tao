<?php
/**
 * CMS.PageNavigator
 * 
 * @package CMS\PageNavigator
 * @version 0.0.0
 */

/**
 * @package CMS\PageNavigator
 */

class CMS_PageNavigator implements Core_ModuleInterface { 

	const MODULE = 'CMS.PageNavigator'; 
	const VERSION = '0.0.0'; 
	
	static $url_template; 
	static $current; 
	static $current_link = false;
	static $template = '../app/views/page-navigator.phtml'; 


/**
 * @param array $config
 */
	static function initialize($config=array()) { 
		foreach($config as $key => $value) self::$$key = $value;
		CMS::$page_navigator = new ReflectionMethod('CMS_PageNavigator','run'); 
	} 
	


/**
 * @param int $page
 * @param int $numpages
 * @param string $url_template
 * @return string
 */
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
	



/**
 * @param int $page
 * @return string
 */
	static function url($page) {
		if (is_callable(self::$url_template)) {
			return call_user_func(self::$url_template,$page);
		}
		$s = preg_replace('{\%$}',$page,self::$url_template); 
		$s = preg_replace('{\%([^0-9a-z])}i',"$page\\1",$s); 
		return $s; 
	} 
	
/**
 * @param int $page
 * @param string|false $link_text
 * @return string
 */
	static function link($i,$t=false) {
		if (!$t) $t = $i; 
		if (!self::$current_link&&$i==self::$current) return "<b>$t</b>";
		return '<a href="'. (self::url($i)).'"'.($i==self::$current?' class="current"':'') .">$t</a>";
	} 
	

	
} 

