<?php
/**
 * @package IO\Arc\ZIP
 */


class IO_Arc_ZIP extends IO_Arc_Archiver implements Core_ModuleInterface
{
	protected $zip = false;
	protected $zip_path = false;

	public function __construct($file)
	{
		$this->zip_path = $file;
		$this->zip = new ZipArchive();
		if (IO_FS::exists($file)) {
			$res = $this->zip->open($file);
			if ($res===true) {
				return $this;
			}
			if ($res===ZIPARCHIVE::ER_NOZIP) {
				throw new IO_Arc_InvalidArchive_Exception("$file is not ZIP archive");
			}
			throw new IO_Arc_Exception("Error opening ZIP archive {$file}. Error code: {$res}");
		} else {
			$res = $this->zip->open($file,ZIPARCHIVE::CREATE);
		}
		return $this;
	}
	
	public function add_file($path)
	{
		$this->zip->addFile($path);
		return $this;
	}
	
	public function add_empty_dir($path)
	{
		$this->zip->addEmptyDir($path);
		return $this;
	}

	public function close()
	{
		$this->zip->close();
		if ($this->zip_path) {
			IO_FS::chmod($this->zip_path);
		}
		return $this;
	}
	
	public function extract_to($path)
	{
		$this->zip->extractTo($path);
	}

}

