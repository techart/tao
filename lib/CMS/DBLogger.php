<?php
/**
 * @package CMS\DBLogger
 */


Core::load('DB');

class CMS_DBLogger implements Core_ModuleInterface, DB_QueryExecutionListener {

	const MODULE  = 'CMS.DBLogger';
	const VERSION = '0.0.0';

	static $uri_filter  = false;
	static $host_filter  = false;
	static $ip_filter  = false;
	static $time_filter = 0;
	static $log_file = '../logs/mysql_queries.log';
	static $clear_on_start = false;
	
	static $cnt = 0;
	static $alltime = 0;

	static function initialize($config=array()) {
		foreach($config as $key => $value) {
			$key = trim($key);
			if ($key!='') {
				self::$$key = $value;
			}
		}
		
		CMS::on_before_dispatch('CMS_DBLogger','start');
	}
	
	static function start() {
		if (self::$clear_on_start&&self::check_filters()) {
			$fh = fopen(self::$log_file,'w');
			fwrite($fh,"*** http://".$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']."\n");
			fwrite($fh,"*** ".date("Y.m.d - G:i")."\n\n");
			fclose($fh);
		}
		WS::env()->db->default->listener(new CMS_DBLogger());
	}
	
	static function check_filter($filter,$value) {
		if (is_string($filter)&&$value!=$filter)  return false;
		
		if (is_array($filter)) {
			$matched = false;
			foreach($filter as $v) {
				if ($v==$value) {
					$matched = true;
					break;
				}	
			}
			if (!$matched) return false;
		}
		
		return true;
	}
	
	static function check_filters() {
		if (!self::check_filter(self::$uri_filter,$_SERVER['REQUEST_URI']))  return false;
		if (!self::check_filter(self::$host_filter,$_SERVER['HTTP_HOST']))  return false;
		if (!self::check_filter(self::$ip_filter,CMS::ip()))  return false;
		return true;
	}
	
	public function on_execute(DB_Cursor $cursor) {
		self::$cnt++;
		if ($cursor->execution_time < self::$time_filter) return;
		if (!self::check_filters()) return;
		
		$dir = IO_FS::Path(self::$log_file)->dirname;
		IO_FS::mkdir($dir);
	
		$fh = fopen(self::$log_file,'a');
		fwrite($fh,$this->create_string($cursor)."\n");
		fclose($fh);
		chmod(self::$log_file,CMS::$chmod_file);
	}
	
	protected function create_string($cursor) {
		$scnt = str_pad(self::$cnt.".",5);
		$u = $_SERVER['REQUEST_URI'];
		$d = date("Y.m.d - G:i");
		$t = $cursor->execution_time;
		self::$alltime += $t;
		$at = self::$alltime;
		$q = preg_replace('{\s+}u',' ',$cursor->sql);
		foreach($cursor->binds as $var) {
			$q = preg_replace('{:[a-z0-9_]+}u','"'.mysql_escape_string($var).'"',$q,1);
		}
		$q = preg_replace('{\s+}u',' ',$q);
		return "$scnt [$d] [$t / $at] $u\n$q\n";
	}

}

