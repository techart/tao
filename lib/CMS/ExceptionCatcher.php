<?php

/**
 * @package CMS\ExceptionCatcher
 */
class CMS_ExceptionCatcher implements Core_ModuleInterface
{

	const MODULE = 'CMS.ExceptionCatcher';
	const VERSION = '0.0.0';

	static function run($e)
	{
		self::send($e);
		header("http/1.0 503 Service Temporarily Unavailable");
		$draw = CMS::$cfg->debug->dump;
		if ($draw) {
			self::draw($e);
			die;
		} else {
			throw($e);
		}
	}

	static function send($e)
	{
		$email = CMS::$cfg->debug->email;
		if ($email) {
			$project = CMS::$cfg->debug->project;
			if (!$project) {
				$project = $_SERVER['SERVER_NAME'];
			}
			$subj = "$project: exception";
			$headers = getallheaders();
			$text = date('d.m.Y - G:i:s') . "\n";
			$text .= $_SERVER['REQUEST_METHOD'] . ' ' . $_SERVER['REQUEST_URI'] . "\n";
			$text .= $e->getMessage() . "\n";
			$text .= 'FILE: ' . $e->getFile() . "\n";
			$text .= 'LINE: ' . $e->getLine() . "\n";
			$text .= "\n" . self::dump($_GET, 'GET') . self::dump($_POST, 'POST') . self::dump($_COOKIE, 'COOKIE') . self::dump($_SESSION, 'SESSION') . self::dump($_SERVER, 'SERVER') . self::dump($headers, 'HTTP REQUEST HEADERS');
			mail($email, $subj, $text);
		}
	}

	static function dump($array, $title)
	{
		unset($array['PHP_AUTH_PW']);
		unset($array['Authorization']);
		ob_start();
		$t = "-- $title -";
		while (strlen($t) < 50)
			$t .= '-';
		print "$t\n";
		if (sizeof($array) == 0) {
			print "empty\n";
		} else {
			foreach ($array as $k => $v) {
				print "$k: ";
				if (is_array($v) || is_object($v)) {
					print base64_encode(serialize($v));
				} else {
					print $v;
				}
				print "\n";
			}
		}
		print "\n";
		$rc = ob_get_contents();
		ob_end_clean();
		return $rc;
	}

	static function draw($e)
	{
		$title = 'Exception';
		include(CMS::views_path('exception.phtml'));
	}

	static function render_array($m)
	{
		$out = "";
		$c = 0;
		foreach ($m as $key => $value) {
			if ($out != '') {
				$out .= ', ';
			}
			if ($key != $c) {
				$out .= "$key:";
			}
			$out .= self::render_value($value);
			$c++;
		}
		return $out;
	}

	static function render_value($value)
	{
		if (is_string($value)) {
			return '"' . $value . '"';
		}
		if (is_numeric($value)) {
			return $value;
		}
		if ($value === true) {
			return 'true';
		}
		if ($value === false) {
			return 'false';
		}
		if (is_object($value)) {
			return get_class($value);
		}
		if (is_array($value)) {
			return '[' . self::render_array($value) . ']';
		}
		return '???';
	}
} 

