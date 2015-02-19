<?php
/**
 * File.Assets
 *
 * @package File\Assets
 * @version 2.0.0
 */

Core::load('IO.FS');

/**
 * @package File\Assets
 */
class File_Assets implements Core_ConfigurableModuleInterface
{
	const VERSION = '2.0.0';

	static protected $options = array(
		'root' => 'assets',
		'keep_originals' => false,
		'dir_permissions' => 0775,
		'file_permissions' => 0664);

	/**
	 * @param array $options
	 */
	static public function initialize(array $options = array())
	{
		self::options($options);
	}

	/**
	 * @param array $options
	 *
	 * @return mixed
	 */
	static public function options(array $options = array())
	{
		if (count($options)) {
			Core_Arrays::update(self::$options, $options);
		}
		return self::$options;
	}

	/**
	 * @param string $name
	 * @param        $value
	 *
	 * @return mixed
	 */
	static public function option($name, $value = null)
	{
		$prev = isset(self::$options[$name]) ? self::$options[$name] : null;
		if ($value !== null) {
			self::options(array($name => $value));
		}
		return $prev;
	}

	/**
	 * @param string $path
	 *
	 * @return File_Assets_Collection
	 */
	static public function Collection($path)
	{
		return new File_Assets_Collection($path);
	}

	/**
	 * @param string $path
	 *
	 * @return string
	 */
	static public function full_path_for($path)
	{
		return self::$options['root'] . '/' . $path;
	}

}

/**
 * @package File\Assets
 */
class File_Assets_Collection
	implements Core_PropertyAccessInterface,
	Core_IndexedAccessInterface,
	IteratorAggregate
{

	protected $path;

	/**
	 * @param DB_ORM_Entity $entity
	 */
	public function __construct($path)
	{
		$this->path = $path;
	}

	/**
	 * @param IO_FS_File $file
	 * @param string     $name
	 *
	 * @return boolean
	 */
	public function store(IO_FS_File $file, $name = '')
	{
		return $this->do_store($file, $name);
	}

	/**
	 * @return boolean
	 */
	public function remove($name)
	{
		return IO_FS::rm($this->full_path_for($name));
	}

	/**
	 */
	public function destroy()
	{
		return $this->rm_storage_dir();
	}

	/**
	 * @return IO_FS_DirIterator
	 */
	public function getIterator()
	{
		return new IO_FS_DirIterator(IO_FS::Dir($this->full_path));
	}

	/**
	 * @param string $property
	 *
	 * @return mixed
	 */
	public function __get($property)
	{
		switch ($property) {
			case 'path':
				return $this->$property;
			case 'full_path':
				return File_Assets::full_path_for($this->path);
			default:
				throw new Core_MissingPropertyException($property);
		}
	}

	/**
	 * @param string $property
	 * @param        $value
	 *
	 * @return mixed
	 */
	public function __set($property, $value)
	{
		switch ($property) {
			case 'path':
			case 'full_path':
				throw new Core_ReadOnlyPropertyException($property);
			default:
				throw new Core_MissingPropertyException($property);
		}
	}

	/**
	 * @param string $property
	 *
	 * @return boolean
	 */
	public function __isset($property)
	{
		switch ($property) {
			case 'path':
			case 'full_path':
				return true;
			default:
				return false;
		}
	}

	/**
	 * @param string $property
	 */
	public function __unset($property)
	{
		switch ($property) {
			case 'path':
			case 'full_path':
				throw new Core_UndestroyablePropertyException($property);
			default:
				throw new Core_MissingPropertyException($property);
		}
	}

	/**
	 * @param mixed $index
	 */
	public function offsetGet($index)
	{
		return IO_FS::exists($path = $this->full_path_for($index)) ?
			IO_FS::File($path) :
			null;
	}

	/**
	 * @param string $index
	 * @param        $value
	 *
	 * @return mixed
	 */
	public function offsetSet($index, $value)
	{
		if ($value instanceof IO_FS_File) {
			$this->do_store($value, $index ? $index : '');
			return $this;
		}
		throw new Core_InvalidArgumentTypeException('value', $value);
	}

	/**
	 * @param string $index
	 *
	 * @return boolean
	 */
	public function offsetExists($index)
	{
		return IO_FS::exists($this->full_path_for($index));
	}

	/**
	 * @param string $index
	 */
	public function offsetUnset($index)
	{
		$this->remove($index);
	}

	/**
	 * @param IO_FS_File $file
	 * @param string     $name
	 *
	 * @return boolean
	 */
	private function do_store(IO_FS_File $file, $name = '')
	{
		$method = File_Assets::option('keep_originals') ? 'copy_to' : 'move_to';
		return $this->make_storage_dir() &&
		($stored = $file->$method($this->full_path_for($name ? $name : $file->name))) &&
		$stored->chmod(File_Assets::option('file_permissions'));
	}

	/**
	 * @param string $name
	 *
	 * @return string
	 */
	protected function path_for($name)
	{
		return $this->path . '/' . $name;
	}

	/**
	 * @param string $name
	 *
	 * @return string
	 */
	public function full_path_for($name)
	{
		return File_Assets::full_path_for($this->path . '/' . $name);
	}

	/**
	 * @return boolean
	 */
	private function make_storage_dir()
	{
		return IO_FS::mkdir($this->full_path, File_Assets::option('dir_permissions'), true);
	}

	/**
	 * @return boolean
	 */
	private function rm_storage_dir()
	{
		$rc = true;
		foreach ($this as $file)
			$rc = $rc && IO_FS::rm($file->path);
		return $rc && IO_FS::rm($this->full_path);
	}

}

