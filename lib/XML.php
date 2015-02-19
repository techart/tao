<?php
/**
 * XML
 *
 * Модуль для работы с XML
 *
 * @package XML
 * @version 0.2.0
 */

/**
 * @package XML
 */
class XML implements Core_ModuleInterface
{

	const VERSION = '0.2.0';

	static protected $loader;

	/**
	 * Инициализация модуля
	 *
	 */
	static public function initialize()
	{
		self::$loader = new XML_Loader();
	}

	/**
	 * Вовзращает DOMDocument
	 *
	 * @param  $source
	 *
	 * @return DOMDocument
	 */
	static public function load($source)
	{
		return self::$loader->load($source);
	}

	/**
	 * Возвращает ошибки парсинга XML
	 *
	 * @return ArrayObject
	 */
	static public function errors()
	{
		return self::$loader->errors;
	}

	/**
	 * Фабричный метод, возвращает объект класса XML.Loader
	 *
	 * @return XML_Loader
	 */
	static public function Loader()
	{
		return new XML_Loader();
	}

	/**
	 * Фабричный метод, возвращает объект класса XML.Builder
	 *
	 * @return XML_Builder
	 */
	static public function Builder()
	{
		return new XML_Builder();
	}

	static public function ElementIterator()
	{
		$args = func_get_args();
		return Core::amake('XML.ElementIterator', $args);
	}

	static public function Writer()
	{
		$args = func_get_args();
		return Core::amake('XML.Writer', $args);
	}

}

/**
 * Служит для перехвата ошибок парсинга XML документа
 *
 * @package XML
 */
class XML_Loader implements Core_PropertyAccessInterface
{

	protected $errors;

	/**
	 * Конструктор
	 *
	 */
	public function __construct()
	{
		$this->errors = new ArrayObject();
	}

	/**
	 * Вовзращает DOMDocument и перехватывает ошибки парсинга
	 *
	 * @param  $source
	 *
	 * @return DOMDocument
	 */
	public function load($source)
	{
		$error_handling_mode = libxml_use_internal_errors(true);

		libxml_clear_errors();

		if ($source instanceof IO_FS_File) {
			$result = DOMDocument::load($source->path);
		} else {
			$result = DOMDocument::loadXML((string)$source);
		}

		$this->errors = new ArrayObject(libxml_get_errors());
		libxml_use_internal_errors($error_handling_mode);

		return $result;
	}

	/**
	 * Доступ на чтение к свойствам объекта
	 *
	 * @param string $property
	 *
	 * @return mixed
	 */
	public function __get($property)
	{
		switch ($property) {
			case 'has_errors':
				return (count($this->errors) > 0);
			case 'errors':
				return clone $this->errors;
			default:
				throw new Core_MissingPropertyException($property);
		}
	}

	/**
	 * Доступ на чтение к свойтвам объекта
	 *
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
	 * Проверяет установленно ли свойство объекта
	 *
	 * @param string $property
	 *
	 * @return boolean
	 */
	public function __isset($property)
	{
		switch ($property) {
			case 'has_errors':
			case 'errors':
				return true;
			default:
				return false;
		}
	}

	/**
	 * Очищает свойство объекта
	 *
	 * @param string $property
	 */
	public function __unset($property)
	{
		throw new Core_ReadOnlyObjectException($this);
	}

}

/**
 * Класс служит для удобного построения XML документа (DOMDocument)
 *
 * @package XML
 */
class XML_Builder
	implements Core_PropertyAccessInterface,
	Core_CallInterface,
	Core_StringifyInterface,
	Core_IndexedAccessInterface
{

	protected $parent;
	protected $node;
	protected $children = array();

	/**
	 * Конструктор
	 *
	 * @param             $node
	 * @param XML_Builder $parent
	 */
	public function __construct($node = null, XML_Builder $parent = null)
	{
		$this->node = ($node === null) ? new DOMDocument('1.0', 'UTF-8') : $node;
		$this->parent = $parent;
	}

	/**
	 * С помощью вызова методов струиться документ
	 *
	 * @param string $method
	 * @param array  $args
	 *
	 * @return XML_Builder
	 */
	public function __call($method, $args)
	{
		if ($p = strpos($method, 'begin_') !== false) {
			$name = substr($method, $p + 5);
			if (!isset($this->children[$name])) {
				$this->children[$name] = array();
			}
			$child = new XML_Builder($this->make_element($name, $args), $this);
			$this->children[$name][] = $child;
			return $child;
		} else {
			$this->make_element($method, $args);
			return $this;
		}
	}

	/**
	 * Возвращает строку сформированного XML
	 *
	 * @return string
	 */
	public function as_string()
	{
		return $this->node->saveXML();
	}

	/**
	 * Возвращает строку сформированного XML
	 *
	 * @return string
	 */
	public function __toString()
	{
		return $this->as_string();
	}

	/**
	 * Доступ на чтение к свойствам объекта
	 *
	 * @param string $property
	 *
	 * @return mixed
	 */
	public function __get($property)
	{
		switch ($property) {
			case 'document':
				return $this->parent ? $this->parent->document : $this->node;
			case 'children':
				return $this->children;
			case 'end':
			case 'parent':
				return $this->parent;
			case 'node':
				return $this->node;
			default:
				throw new Core_MissingPropertyException($property);
		}
	}

	/**
	 * Доступ на чтение к свойствам объекта
	 *
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
	 * Проверяет установленно ли свойство
	 *
	 * @param string $property
	 *
	 * @return boolean
	 */
	public function __isset($property)
	{
		switch ($property) {
			case 'document':
			case 'children':
			case 'parent':
			case 'node':
				return true;
			default:
				return false;
		}
	}

	/**
	 * Очищает свойство
	 *
	 * @param string $property
	 */
	public function  __unset($property)
	{
		throw new Core_ReadOnlyObjectException($this);
	}

	/**
	 * Возвращает влоденный элемент
	 *
	 * @param string $index
	 *
	 * @return array
	 */
	public function offsetGet($index)
	{
		return isset($this->children[$index]) ? $this->children[$index] : null;
	}

	/**
	 * Выкидывает исключение
	 *
	 * @param string $index
	 * @param mixed  $value
	 *
	 * @return mixed
	 */
	public function offsetSet($index, $value)
	{
		throw new Core_ReadOnlyObjectException($this);
	}

	/**
	 * Проверяет есть ли такой элемент среди вложенных
	 *
	 * @param string $index
	 *
	 * @return boolean
	 */
	public function offsetExists($index)
	{
		return isset($this->children[$index]);
	}

	/**
	 * Выкидывает исключение
	 *
	 * @param  $index
	 */
	public function offsetUnset($index)
	{
		throw new Core_ReadOnlyObjectException($this);
	}

	/**
	 * Строит очередное элемент документа
	 *
	 * @param string $name
	 * @param        $args
	 *
	 * @return DOMElement
	 */
	protected function make_element($name, array $args)
	{
		$content = '';
		$attributes = array();

		if (isset($args[0])) {
			if (is_array($args[0])) {
				if (isset($args[0][0])) {
					$content = array_shift($args[0]);
				}
				$attributes = $args[0];
			} else {
				$content = $args[0];
				if (isset($args[1]) && is_array($args[1])) {
					$attributes = $args[1];
				}
			}
		}

		if ($content instanceof XML_Builder) {
			$content = $content->node;
		}

		if ($content instanceof DOMElement) {
			$element = $this->document->createElement($name);
			$element->appendChild($content);
		} else {
			$element = $this->document->createElement($name, (string)$content);
		}

		foreach ($attributes as $k => $v)
			if ($v !== null) {
				$element->setAttribute($k, $v);
			}

		$this->node->appendChild($element);

		return $element;
	}

}

class XML_ElementIterator implements Iterator
{

	protected $reader;
	protected $name;
	protected $doc;

	protected $element;
	protected $key = 0;
	protected $current_depth = 0;

	protected $safe_limit = 1000000;

	public function __construct($reader, $name, $type = 'node')
	{
		$this->reader = $reader;
		$this->name = $name;
		$this->doc = new DOMDocument();
		$this->type = $type;
	}

	protected function create_element()
	{
		if (Core_Types::is_callable($this->type)) {
			$this->element = Core::invoke($this->type, array($this->reader, $this->name));
			return $this;
		}
		$method = "element_" . $this->type;
		if (method_exists($this, $method)) {
			$this->$method();
		} else {
			$this->element = null;
		}
		return $this;
	}

	protected function element_none()
	{
		return $this->element = array();
	}

	protected function element_node()
	{
		$node = $this->reader->expand();
		if ($node) {
			$this->element = simplexml_import_dom($this->doc->importNode($node, true));
		} else {
			$this->element = null;
		}
	}

	protected function element_attrs()
	{
		if ($this->reader->name != $this->name) {
			return $this->element = null;
		}
		$res = array();
		for ($i = 0; $i < $this->reader->attributeCount; $i++) {
			$this->reader->moveToAttributeNo($i);
			$res[$this->reader->name] = $this->reader->value;
		}
		$this->element = $res;
	}

	function rewind()
	{
		$this->move();
		$this->current_depth = $this->reader->depth;
		$this->key = 0;
		$this->element = null;
		$this->create_element();
	}

	protected function move()
	{
		$i = 0;
		while ($this->reader->read()) {
			if ($this->reader->name == $this->name && $this->reader->nodeType == XMLReader::ELEMENT) {
				return true;
			}
			if ($i > $this->safe_limit) {
				return false;
			}
			$i++;
		}
		return true;
	}

	protected function search()
	{
		$i = 0;
		while ($this->reader->read()) {
			if ($this->reader->depth < $this->current_depth) {
				return false;
			}
			if (($this->reader->name == $this->name && $this->reader->nodeType == XMLReader::ELEMENT && $this->reader->depth == $this->current_depth)) {
				return true;
			}
			if ($i > $this->safe_limit) {
				return false;
			}
			$i++;
		}
		return true;
	}

	function current()
	{
		return $this->element;
	}

	function key()
	{
		return $this->key;
	}

	function next()
	{
		if ($this->search()) {
			$this->create_element();
			$this->key++;
		} else {
			$this->element = null;
		}
	}

	function valid()
	{
		return !is_null($this->element);
	}

}