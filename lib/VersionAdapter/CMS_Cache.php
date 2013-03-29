<?php

Core::load('Cache');

class CMS_Cache implements Core_ModuleInterface { 

	const MODULE = 'CMS.Cache'; 
	const VERSION = '0.0.0'; 

	static $lifetime = 86400; 
	static $dir = "../cache";
	static $disabled = false;
	static protected $client;
	static protected $delim = ':';

	static function initialize($config=array()) { 
		foreach($config as $key => $value) self::$$key = $value;
		$dsn = 'fs://' . self::$dir;
		$backend = Cache::connect($dsn, self::$lifetime);
		self::$client = $backend;// Cache_Tagged::Client($backend);
	} 

	static function client() { return self::$client; }	
	
	
	static function set() {
		if (self::$disabled) return; 
		$args = func_get_args();
		if (sizeof($args)>2)
      return self::$client->set(self::get_key($args[1], $args[0]), $args[2]);
		else
      return self::$client->set(self::get_key($args[0]), $args[1]);
	}
	
	static protected function get_key($file, $dir = null) {
		return empty($dir) ? $file : $dir . self::$delim . $file;
	}
	 
	static function get() { 
		if (self::$disabled) return false; 
		$args = func_get_args(); 
		if (sizeof($args) > 1)
      return self::$client->get(self::get_key($args[1], $args[0]), false);
		else
      return self::$client->get(self::get_key($args[0]), false);
  }
	 
	static function reset() {
		$args = func_get_args();
		if (sizeof($args)>1)
      return self::$client->delete(self::get_key($args[1], $args[0]));
    else
      return self::$client->delete(self::get_key($args[0]));
	}

} 
