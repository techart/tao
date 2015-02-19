<?php
/**
 * Dev.Source
 *
 * @package Dev\Source
 * @version 0.2.0
 */
Core::load('IO.FS', 'XML', 'CLI');

/**
 * @package Dev\Source
 */
class Dev_Source implements Core_ModuleInterface
{

	const MODULE = 'Dev.Source';
	const VERSION = '0.2.1';

	/**
	 * @param string $name
	 *
	 * @return Dev_Source_Module
	 */
	static public function Module($name)
	{
		return new Dev_Source_Module($name);
	}

	/**
	 * @return Dev_Source_Library
	 */
	static public function Library()
	{
		$args = func_get_args();
		return new Dev_Source_Library(((isset($args[0]) && is_array($args[0]) ? $args[0] : $args)));
	}

	/**
	 * @param string $path
	 *
	 * @return Dev_Source_LibraryDirIterator
	 */
	static public function LibraryDirIterator($path)
	{
		return new Dev_Source_LibraryDirIterator($path);
	}

}

/**
 * @package Dev\Source
 */
interface Dev_Source_LibraryIteratorInterface
{
}

/**
 * @package Dev\Source
 */
class Dev_Source_Exception extends Core_Exception
{
}


/**
 * @package Dev\Source
 */
class Dev_Source_InvalidSourceException extends Dev_Source_Exception
{

	protected $module;
	protected $source;
	protected $errors;

	/**
	 * @param string      $module
	 * @param string      $source
	 * @param ArrayObject $errors
	 */
	public function __construct($module, $source, ArrayObject $errors)
	{
		$this->module = $module;
		$this->source = $source;
		$this->errors = $errors;
		parent::__construct(Core_Strings::format('Invalid source for module %s (errors: %d)', $this->module, count($this->errors)));
	}

}


/**
 * @package Dev\Source
 */
class Dev_Source_Module
{

	protected $name;
	protected $file;
	protected $xml;

	/**
	 * @param string $name
	 */
	public function __construct($name)
	{
		$this->name = $name;
		$this->file = IO_FS::File(Core::loader()->file_path_for($name));
	}

	/**
	 * @param boolean $reload
	 *
	 * @return Dev_Source_Module
	 */
	protected function load($reload = false)
	{
		if (!$this->xml || $reload) {

			$is_cdata = false;
			$text = '';
			$is_ignore = false;

			foreach ($this->file->open('r')->text() as $line) {

				if (preg_match('{^///\s+<ignore>}', $line)) {
					$is_ignore = true;
				}
				if (preg_match('{^///\s+</ignore>}', $line)) {
					$is_ignore = false;
					$text .= "\n";
					continue;
				}

				if (preg_match('{^\s*$|^<\?php|^\?>}', $line) || $is_ignore) {
					$text .= "\n";
					continue;
				}

				if (preg_match('{^///(.*)$}', $line, $m)) {
					$text .= ($is_cdata ? "]]>\n" : '') . $m[1] . "\n";
					$is_cdata = false;
				} else {
					if ($is_cdata) {
						$text .= "\n" . rtrim($line);
					} else {
						$text .= '<![CDATA[' . rtrim($line);
						$is_cdata = true;
					}
				}
			}

			if (!($this->xml = Core::with($loader = XML::Loader())->load($text))) {
				throw new Dev_Source_InvalidSourceException($this->name, $text, $loader->errors);
			}
		}
		return $this->xml;
	}

	/**
	 * @param string $property
	 *
	 * @return mixed
	 */
	public function __get($property)
	{
		switch ($property) {
			case 'xml':
				return $this->load();
			case 'file':
			case 'name':
				return $this->$property;
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
		throw ($this->__isset($property)) ?
			new Core_ReadOnlyPropertyException($property) :
			new Core_MissingPropertyException($property);
	}

	/**
	 * @param string $property
	 *
	 * @return boolean
	 */
	public function __isset($property)
	{
		switch ($property) {
			case 'xml':
			case 'file':
			case 'name':
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
		throw ($this->__isset($property)) ?
			new Core_UndestroyablePropertyException($property) :
			new Core_MissingPropertyException($property);
	}

}


/**
 * @package Dev\Source
 */
//    <implements interface="IteratorAggregate" />
class Dev_Source_Library
	implements Core_PropertyAccessInterface,
	Dev_Source_LibraryIteratorInterface,
	IteratorAggregate
{

	protected $modules;
	protected $xml;

	/**
	 */
	public function __construct()
	{
		$this->modules = Core::hash();

		$args = func_get_args();
		foreach (((isset($args[0]) && is_array($args[0]) ? $args[0] : $args)) as $module) {
			$this->module($module);
		}

	}

	/**
	 * @param  $module
	 *
	 * @return Dev_Source_Library
	 */
	public function module($module)
	{
		$module = ($module instanceof Dev_Source_Module) ?
			$module :
			Dev_Source::Module((string)$module);

		$this->modules[$module->name] = $module;
		return $this;
	}

	/**
	 * @return Iterator
	 */
	public function getIterator()
	{
		return $this->modules->getIterator();
	}

	/**
	 * @param string $property
	 *
	 * @return mixed
	 */
	public function __get($property)
	{
		switch ($property) {
			case 'xml':
				return $this->load();
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
		throw new Core_ReadOnlyObjectException($this);
	}

	/**
	 * @param string $property
	 *
	 * @return boolean
	 */
	public function __isset($property)
	{
		switch ($property) {
			case 'xml':
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
		throw new Core_ReadOnlyObjectException($this);
	}

	/**
	 * @param boolean $reload
	 *
	 * @return Dev_Source_Library
	 */
	public function load($reload = false)
	{
		if (!$this->xml || $reload) {
			$library = Core::with(
				$this->xml = new DOMDocument()
			)->
				appendChild(new DOMElement('library'));

			foreach ($this->modules as $module)
				if ($module->xml) {
					$library->appendChild(
						$this->xml->importNode($module->xml->documentElement, true)
					);
				}
		}
		return $this->xml;
	}

}

/**
 * @package Dev\Source
 */
class Dev_Source_LibraryDirIterator
	implements Iterator,
	Dev_Source_LibraryIteratorInterface
{

	protected $path;
	protected $current;
	protected $dir_iterator;

	/**
	 * @param string $path
	 */
	public function __construct($path)
	{
		$this->path = (string)$path;
		$this->dir_iterator = IO_FS::Dir($this->path)->query(
			IO_FS::Query()->glob('*.php')->recursive(true)
		);
		$this->dir_iterator->rewind();
		$this->current = Dev_Source::Module($this->module_name($this->dir_iterator->current()->path));
	}

	/**
	 * @param string $path
	 *
	 * @return string
	 */
	protected function module_name($path)
	{
		return Core_Strings::replace(
			Core_Strings::replace(
				Core_Strings::replace($path, $this->path . "/", ''), '.php', ''
			), '/', '.'
		);
	}

	/**
	 */
	public function rewind()
	{
		$this->dir_iterator->rewind();
	}

	/**
	 * @return mixed
	 */
	public function current()
	{
		return $this->current;
	}

	/**
	 * @return string
	 */
	public function key()
	{
		return $this->current->name;
	}

	/**
	 */
	public function next()
	{
		$this->dir_iterator->next();
		if ($this->dir_iterator->valid()) {
			$this->current = Dev_Source::Module(
				$this->module_name($this->dir_iterator->current()->path)
			);
		} else {
			return null;
		}
	}

	/**
	 * @return boolean
	 */
	public function valid()
	{
		return $this->dir_iterator->valid();
	}

}


?>
