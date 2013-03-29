<?php

Core::load('CMS');

class VersionAdapter implements Core_ModuleInterface {
	const VERSION = '0.0.0';
	
	
	static function initialize() {
		spl_autoload_register(array('VersionAdapter','autoload'));
	}
	
	static function autoload($class) {
		$file = CMS::$libpath."/VersionAdapter/$class.php";
		if (is_file($file)) include($file);
	}
	
}
