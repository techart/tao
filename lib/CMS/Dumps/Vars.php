<?php

/**
 * @package CMS\Dumps\Vars
 */
class CMS_Dumps_Vars extends CMS_Dumps_AbstractDumper implements Core_ModuleInterface
{

	const MODULE = 'CMS.Dumps.Vars';
	const VERSION = '0.0.0';

	static $ids = array();

	static function load($component)
	{
		CMS_Vars::$files_dir = "../" . CMS::$www . "/" . Core::option('files_name') . "/vars";
		$rows = CMS::vars()->db()->select_component($component);
		foreach ($rows as $row)
			$row->del($row->id);
		self::$ids = array(0 => 0);
		while ($line = self::get()) {
			$line = trim($line);
			if ($line != '') {
				if ($line == '!ENDDUMP') {
					return;
				} else {
					if ($line == '!BEGIN') {
						self::load_var();
					} else {
						if ($m = Core_Regexps::match_with_results('{^!FILE\s+(\d+)(.+)$}', $line)) {
							$id = trim($m[1]);
							$filename = trim($m[2]);
							if ($filename == '') {
								$filename = rand(11111, 99999);
							}
							self::load_file((int)$id, $filename);
						}
					}
				}
			}
		}
	}

	static function load_var()
	{
		$var = CMS::vars()->db()->make_entity();
		while ($line = self::get()) {
			$line = trim($line);
			if ($line != '') {
				if ($line == '!END') {
					$oid = $var->id;
					$var->id = 0;
					$var->parent_id = self::$ids[$var->parent_id];
					$var->insert();
					self::$ids[$oid] = $var->id;
					return;
				} else {
					if ($m = Core_Regexps::match_with_results('{^([^\s:]+):(.*)$}', $line)) {
						$key = trim($m[1]);
						$value = trim($m[2]);
						$value = $value == '!BEGIN' ? self::decode_content() : base64_decode($value);
						if ($key == 'site' && $value == '__') {
							$value = CMS_Admin::site();
						}
						$var->$key = $value;
					}
				}
			}
		}
	}

	static function load_file($oid, $filename)
	{
		$id = self::$ids[$oid];
		if ($id == 0 || $oid == 0) {
			return;
		}

		$dir = "../" . CMS::$www . "/" . Core::option('files_name') . "/vars/$id";
		if (!IO_FS::exists($dir)) {
			IO_FS::make_nested_dir($dir, CMS::$chmod_dir);
		}

		self::decode_file("$dir/$filename");

		$var = CMS::vars()->db()->find($id);
		if ($var && $var->type != 'array') {
			$var->value = str_replace("/$oid/$filename", "/$id/$filename", $var->value);
			$var->update();
		}

	}

	static function dump($component)
	{
		Core::load('MIME.Encode');

		$filename = 'vars';
		if ($component != '') {
			$filename .= "-$component";
		}
		$filename .= '-' . date('Y-m-d-G-i');

		header("Content-Type: text/plain; charset=utf8");
		header("Content-Disposition: attachment; filename=$filename.dmp");

		print "!DUMP VARS $component\n\n";

		$rows = CM::vars()->db()->select_component($component);
		foreach ($rows as $row) {
			print "!BEGIN\n";
			foreach (CMS::vars()->db()->columns as $column) {
				print "$column:";
				$v = base64_encode($row->$column);
				if (strlen($v) > 70) {
					print "!BEGIN\n";
					print trim(chunk_split($v));
					print "\n!END";
				} else {
					print $v;
				}
				print "\n";
			}
			print "!END\n\n";

			$dir = "./" . Core::option('files_name') . "/vars/$row->id";
			if (IO_FS::exists($dir)) {
				foreach (IO_FS::Dir($dir) as $file) {
					print "!FILE $row->id $file->name\n";
					Core_Strings::begin_binary();
					foreach (MIME_Encode::Base64Encoder($file->open()) as $line) {
						$line = trim($line);
						print "$line\n";
					}
					Core_Strings::end_binary();
					print "!ENDFILE\n\n";
				}
			}

		}
		print "!ENDDUMP\n\n";
		return true;
	}

}

