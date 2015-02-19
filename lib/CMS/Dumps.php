<?php
/**
 * @package CMS\Dumps
 */

Core::load('IO.FS');

class CMS_Dumps implements Core_ModuleInterface
{
	const MODULE = 'CMS.Dumps';
	const VERSION = '0.0.0';

	static $stream = false;
	static $dumpers = array();

	static function dumper($module, $class)
	{
		self::$dumpers[$module] = $class;
	}

	static function get()
	{
		if (!self::$stream) {
			return false;
		}
		return self::$stream->read_line();
	}

	static function Run()
	{
		Core::load('CMS.CLI');
		self::load_files(CMS_CLI::parms());
	}

	static function load_files($files)
	{
		foreach ($files as $file) {
			self::load($file);
		}
	}

	static function load($filename)
	{
		self::$stream = IO_FS::FileStream($filename);
		while ($line = self::get()) {
			$line = trim($line);
			if ($m = Core_Regexps::match_with_results('{^!DUMP\s+([^\s]+)(.*)$}', $line)) {
				$module = trim($m[1]);
				$parms = trim($m[2]);

				$dumper = trim(self::$dumpers[$module]);
				if ($dumper == '') {
					throw new CMS_Dumps_UnknownDumperException("Unknown dumper: $module");
				}
				$dumper_class_name = str_replace('.', '_', $dumper);

				if (!class_exists($dumper_class_name)) {
					Core::load($dumper);
				}

				$class = new ReflectionClass($dumper_class_name);
				$class->setStaticPropertyValue('stream', self::$stream);

				$method = $class->getMethod('load');
				$rc = $method->invokeArgs(null, array($parms));
				if (trim($rc) != '') {
					return $rc;
				}
			}
		}
		return true;
	}

	static function load_from_post()
	{
		$file = trim($_FILES['dump']['tmp_name']);
		if ($file != '') {
			return self::load($file);
		}
		return 'No files for load';
	}

}

abstract class CMS_Dumps_AbstractDumper
{

	static $stream = false;

	static function Base64DecoderStream($filename)
	{
		return new Dumps_Base64DecoderStream($filename);
	}

	static function get()
	{
		if (!self::$stream) {
			return false;
		}
		return self::$stream->read_line();
	}

	static function decode_file($filename)
	{
		$f = self::Base64DecoderStream($filename);
		Core_Strings::begin_binary();
		while ($line = self::get()) {
			$line = trim($line);
			if ($line == '!ENDFILE') {
				break;
			}
			$f->write($line);
		}
		$f->close();
		Core_Strings::end_binary();
		chmod($filename, CMS::$chmod_file);
	}

	static function decode_content()
	{
		$b64 = '';
		while ($line = self::get()) {
			$line = trim($line);
			if ($line == '!END') {
				break;
			}
			$b64 .= $line;
		}
		return base64_decode($b64);
	}

	abstract static function load($parms);

}

class CMS_Dumps_Exception extends CMS_Exception
{
}

class CMS_Dumps_UnknownDumperException extends CMS_Dumps_Exception
{
}

class Dumps_Base64DecoderStream
{

	protected $stream;
	protected $buf;

	public function __construct($filename)
	{
		$this->stream = IO_FS::FileStream($filename, 'w');
		$this->buf = '';
	}

	public function write($s)
	{
		$this->buf .= trim($s);
	}

	public function close()
	{
		$this->stream->write(base64_decode($this->buf));
		$this->stream->close();
	}

}

