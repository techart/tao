<?php
/**
 * @package Storage\File\Serialize
 */


Core::load('Storage.File');

class Storage_File_Serialize implements COre_ModuleInterface {
	const VERSION = '0.0.0';

	static public function storage($path = null) {
		if (is_null($path)) return new Storage_File_Serialize_Type();
		return new Storage_File_Serialize_Type($path);
	}

}

class Storage_File_Serialize_Type extends Storage_File_Type {

	public function read($file) {
		$string = file_get_contents($file);
		if ($string)
			return unserialize($string);
		return null;
	}

	public function write($file, $data) {
		$res = serialize($data);
		return (bool) file_put_contents($file, $res, LOCK_EX);
	}

}