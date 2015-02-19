<?php
/**
 * @package Storage\File\Export
 */

Core::load('Storage.File');

class Storage_File_Export implements COre_ModuleInterface
{
	const VERSION = '0.0.0';

	static public function storage($path = null)
	{
		if (is_null($path)) {
			return new Storage_File_Export_Type();
		}
		return new Storage_File_Export_Type($path);
	}

}

class Storage_File_Export_Type extends Storage_File_Type
{

	public function read($file)
	{
		if (is_file($file)) {
			$res = include($file);
			return $res;
		}
		return null;
	}

	public function write($file, $data)
	{
		$res = var_export($data, true);
		$res = "<?php
/**
 * @package Storage\File\Export
 */
 return \n $res ;";
		return (bool)file_put_contents($file, $res, LOCK_EX);
	}

}