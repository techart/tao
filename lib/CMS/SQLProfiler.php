<?php
/**
 * @package CMS\SQLProfiler
 */


class CMS_SQLProfiler implements Core_ModuleInterface {

	const MODULE  = 'CMS.SQLProfiler';
	const VERSION = '0.0.0';

	static $uri_filter  = false;
	static $host_filter  = false;
	static $ip_filter  = false;
	static $time_filter = 0;
	static $log_file = '../logs/mysql_queries.log';
	static $clear_on_start = false;
	
	static $cnt = 0;

	static function initialize($config=array()) {
		foreach($config as $key => $value) {
			$key = trim($key);
			if ($key!='') {
				self::$$key = $value;
			}
		}
		DB_SQL::db()->connection->prepare('set profiling=1')->execute();
	}

	static function dump() {
		$result = DB_SQL::db()->connection->prepare('show profiles')->execute();
		$rows = $result->fetch_all();
		print '<table style="margin: 10px;" cellspacing="1" bgcolor="#000"><tr style="background-color: #888;color: #fff"><th style="padding:3px;">ID</th><th style="padding:3px;">Duration</th><th style="padding:3px;">Query</th></tr>';
		foreach($rows as $row) {
			print '<tr style="background-color: white; color: black;">';
				print '<td nowrap style="padding:3px;">'.$row['Query_ID'].'</td>';
				print '<td nowrap style="padding:3px;">'.$row['Duration'].'</td>';
				print '<td style="padding:3px;">'.$row['Query'].'</td>';
			print '</tr';
		}
		print '</table>';
	}
	
}

