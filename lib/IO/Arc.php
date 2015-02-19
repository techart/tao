<?php
/**
 * @package IO\Arc
 */

Core::load('IO.FS');

class IO_Arc implements Core_ModuleInterface
{
	public static function ZIP($file)
	{
		Core::load('IO.Arc.ZIP');
		return new IO_Arc_ZIP($file);
	}
}

abstract class IO_Arc_Archiver
{
	public function add($path)
	{
		if (!IO_FS::exists($path)) {
			throw new IO_Arc_FileNotFound_Exception("File not found: {$path}");
		} elseif (is_dir($path)) {
			$this->add_dir($path);
		} else {
			$this->add_file($path);
		}
		return $this;
	}

	public function add_dir($path)
	{
		$this->add_empty_dir($path);
		foreach (IO_FS::Dir($path) as $entry) {
			$_path = $entry->path;
			$this->add($_path);
		}
		return $this;
	}

	abstract public function add_file($path);

	abstract public function add_empty_dir($path);

	abstract public function extract_to($path);
}

class IO_Arc_Exception extends IO_FS_Exception
{
}

class IO_Arc_FileNotFound_Exception extends IO_Arc_Exception
{
}

class IO_Arc_InvalidArchive_Exception extends IO_Arc_Exception
{
}
