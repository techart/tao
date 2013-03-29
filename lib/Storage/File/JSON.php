<?php

Core::load('Storage.File');

class Storage_File_JSON implements COre_ModuleInterface {
	const VERSION = '0.0.0';

	static public function storage($path = null) {
		if (is_null($path)) return new Storage_File_JSON_Type();
		return new Storage_File_JSON_Type($path);
	}

}

class Storage_File_JSON_Type extends Storage_File_Type {

	public function read($file) {
		$string = file_get_contents($file);
		if ($string)
			return json_decode($string, true);
		return null;
	}

	public function write($file, $data) {
		$res = json_encode($data);
		return (bool) file_put_contents($file, $res, LOCK_EX);
	}

}