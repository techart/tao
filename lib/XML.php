<?php
/// <module name="XML" maintainer="timokhin@techart.ru" version="0.2.0">
///   <brief>Модуль для работы с XML</brief>

/// <class name="XML" stereotype="module">
///   <implements interface="Core.ModuleInterface" />
///   <depends supplier="XML.Loader" stereotype="creates" />
///   <depends supplier="XML.Builder" stereotype="creates" />
class XML implements Core_ModuleInterface {

///   <constants>
  const VERSION = '0.2.0';
///   </constants>

  static protected $loader;

///   <protocol name="initialize">

///   <method name="initialize" scope="class">
///     <brief>Инициализация модуля</brief>
///     <body>
  static public function initialize() { self::$loader = new XML_Loader(); }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="performing">

///   <method name="load" returns="DOMDocument" scope="class">
///     <brief>Вовзращает DOMDocument</brief>
///     <args>
///       <arg name="source" brief="IO.FS.File или строка" />
///     </args>
///     <body>
  static public function load($source) { return self::$loader->load($source); }
///     </body>
///   </method>

///   <method name="errors" returns="ArrayObject">
///     <brief>Возвращает ошибки парсинга XML</brief>
///     <body>
  static public function errors() { return self::$loader->errors; }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="building">

///   <method name="Loader" returns="XML.Loader" scope="class">
///     <brief>Фабричный метод, возвращает объект класса XML.Loader</brief>
///     <body>
  static public function Loader() { return new XML_Loader(); }
///     </body>
///   </method>

///   <method name="Builder" returns="XML.Builder" scope="class">
///     <brief>Фабричный метод, возвращает объект класса XML.Builder</brief>
///     <body>
  static public function Builder() { return new XML_Builder(); }
///     </body>
///   </method>

	static public function ElementIterator() {
		$args = func_get_args();
		return Core::amake('XML.ElementIterator', $args);
	}
	
	static public function Writer() {
		$args = func_get_args();
		return Core::amake('XML.Writer', $args);
	}

///   </protocol>
}
/// </class>

/// <class name="XML.Loader">
///   <brief>Служит для перехвата ошибок парсинга XML документа</brief>
///   <implements interface="Core.PropertyAccessInterface" />
class XML_Loader implements Core_PropertyAccessInterface {

  protected $errors;

///   <protocol name="creating">

///   <method name="__construct">
///     <brief>Конструктор</brief>
///     <body>
  public function __construct() { $this->errors = new ArrayObject(); }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="performing">

///   <method name="load" returns="DOMDocument">
///     <brief>Вовзращает DOMDocument и перехватывает ошибки парсинга</brief>
///     <args>
///       <arg name="source" brief="IO.FS.File или строка" />
///     </args>
///     <body>
  public function load($source) {
    $error_handling_mode = libxml_use_internal_errors(true);

    libxml_clear_errors();

    if ($source instanceof IO_FS_File) {
      $result = DOMDocument::load($source->path);
    } else {
      $result = DOMDocument::loadXML((string) $source);
    }

    $this->errors = new ArrayObject(libxml_get_errors());
    libxml_use_internal_errors($error_handling_mode);

    return $result;
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="accessing">

///   <method name="__get" returns="mixed">
///     <brief>Доступ на чтение к свойствам объекта</brief>
///     <details>
///       <dl>
///         <dt>has_errors</dt><dd>возвращает истину , если есть ошибки</dd>
///         <dt>errors</dt><dd>возвращает ошибки парсинга вввиде объекта LibXMLError </dd>
///       </dl>
///     </details>
///     <args>
///       <arg name="property" type="string" brief="имя свойтсва" />
///     </args>
///     <body>
  public function __get($property) {
    switch ($property) {
      case 'has_errors':
        return (count($this->errors) > 0);
      case 'errors':
        return clone $this->errors;
      default:
        throw new Core_MissingPropertyException($property);
    }
  }
///     </body>
///   </method>

///   <method name="__set" returns="mixed">
///     <brief>Доступ на чтение к свойтвам объекта</brief>
///     <details>
///       Выбрасывает исключение, доступ только для чтения
///     </details>
///     <args>
///       <arg name="property" type="string" brief="имя свойства" />
///       <arg name="value" brief="значение" />
///     </args>
///     <body>
  public function __set($property, $value) { throw new Core_ReadOnlyObjectException($this); }
///     </body>
///   </method>

///   <method name="__isset" returns="boolean">
///     <brief>Проверяет установленно ли свойство объекта</brief>
///     <args>
///       <arg name="property" type="string" brief="имя свойства" />
///     </args>
///     <body>
  public function __isset($property) {
    switch ($property) {
      case 'has_errors':
      case 'errors':
        return true;
      default:
        return false;
    }
  }
///     </body>
///   </method>

///   <method name="__unset">
///     <brief>Очищает свойство объекта</brief>
///     <args>
///       <arg name="property" type="string" brief="имя свойства" />
///     </args>
///     <body>
  public function __unset($property) { throw new Core_ReadOnlyObjectException($this); }
///     </body>
///   </method>

///   </protocol>
}
/// </class>


/// <class name="XML.Builder">
///   <brief>Класс служит для удобного построения XML документа (DOMDocument)</brief>
///   <implements interface="Core.PropertyAccessInterface" />
///   <implements interface="Core.IndexedPropertyAccessInterface" />
///   <implements interface="Core.CallInterface" />
///   <implements interface="Core.StringifyInterface" />
class XML_Builder
  implements Core_PropertyAccessInterface,
             Core_CallInterface,
             Core_StringifyInterface,
             Core_IndexedAccessInterface {

  protected $parent;
  protected $node;
  protected $children = array();

///   <protocol name="creating">

///   <method name="__construct">
///     <brief>Конструктор</brief>
///     <args>
///       <arg name="node"   default="null" brief="xml-узел" />
///       <arg name="parent" type="XML.Builder" default="null" brief="родительский элемент" />
///     </args>
///     <body>
  public function __construct($node = null, XML_Builder $parent = null) {
    $this->node =  ($node === null) ? new DOMDocument('1.0', 'UTF-8') : $node;
    $this->parent = $parent;
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="calling" interface="Core.CallInterface">

///   <method name="__call" returns="XML.Builder">
///     <brief>С помощью вызова методов струиться документ</brief>
///     <details>
///       <p>Имя метода задает имя элемента, параметры - ассоциативный массив параметров узла или текст содержимого элемента.
///         Если нужно задать и параметры и содержимое, то первым элементом ассоциативного массива надо передать текст</p>
///       <p>Если имя метода имеет вид: begin_name, то создается узел с именем name и дальнейшие элементы будут вложенны в узел name</p>
///       <p>Свойство end возвращает на уровень выше, т.е. 'выходит' из узла</p>
///     </details>
///     <args>
///       <arg name="method" type="string" brief="имя мтода/узла" />
///       <arg name="args"   type="array" brief="массив атрубутов" />
///     </args>
///     <body>
  public function __call($method, $args) {
    if ($p = strpos($method, 'begin_') !== false) {
      $name = substr($method, $p + 5);
      if (!isset($this->children[$name])) $this->children[$name] = array();
      $child = new XML_Builder($this->make_element($name, $args), $this);
      $this->children[$name][] = $child;
      return $child;
    } else {
      $this->make_element($method, $args);
      return $this;
    }
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="stringifying" interface="Core.StringifyInterface">

///   <method name="as_string" returns="string">
///     <brief>Возвращает строку сформированного XML</brief>
///     <body>
  public function as_string() { return $this->node->saveXML(); }
///     </body>
///   </method>

///   <method name="__toString" returns="string">
///     <brief>Возвращает строку сформированного XML</brief>
///     <body>
  public function __toString() { return $this->as_string(); }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="accessing" interface="Core.PropertyAccessInterface">

///   <method name="__get" returns="mixed">
///     <brief>Доступ на чтение к свойствам объекта</brief>
///     <details>
///       <dl>
///         <dt>document</dt><dd>DOMDocument</dd>
///         <dt>children</dt><dd>массив вложенных узлов (детей)</dd>
///         <dt>end</dt><dd>возвращает родительский элемент</dd>
///         <dt>parent</dt><dd>возвращает родительский элемент</dd>
///         <dt>node</dt><dd>текущий узел</dd>
///       </dl>
///     </details>
///     <args>
///       <arg name="property" type="string" brief="имя свойства" />
///     </args>
///     <body>
  public function __get($property) {
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
///     </body>
///   </method>

///   <method name="__set" returns="mixed">
///     <brief>Доступ на чтение к свойствам объекта</brief>
///     <details>
///       Выкидывает исключение, доступ только для чтения
///     </details>
///     <args>
///       <arg name="property" type="string" brief="имя свойства" />
///       <arg name="value" brief="значение" />
///     </args>
///     <body>
  public function __set($property, $value) { throw new Core_ReadOnlyObjectException($this); }
///     </body>
///   </method>

///   <method name="__isset" returns="boolean">
///     <brief>Проверяет установленно ли свойство</brief>
///     <args>
///       <arg name="property" type="string" brief="имя свойства" />
///     </args>
///     <body>
  public function __isset($property) {
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
///     </body>
///   </method>

///   <method name="__unset">
///     <brief>Очищает свойство</brief>
///     <details>
///       Выкидывает исключение, доступ только для чтения
///     </details>
///     <args>
///       <arg name="property" type="string" brief="имя свойства" />
///     </args>
///     <body>
  public function  __unset($property) { throw new Core_ReadOnlyObjectException($this); }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="indexing">

///   <method name="offsetGet" returns="array">
///     <brief>Возвращает влоденный элемент</brief>
///     <args>
///       <arg name="index" type="string" brief="имя элемента" />
///     </args>
///     <body>
  public function offsetGet($index) {
    return isset($this->children[$index]) ? $this->children[$index] : null;
  }
///     </body>
///   </method>

///   <method name="offsetSet" returns="mixed">
///     <brief>Выкидывает исключение</brief>
///     <args>
///       <arg name="index" type="string" brief="имя элемента" />
///       <arg name="value" type="mixed" brief="значение" />
///     </args>
///     <body>
  public function offsetSet($index, $value) { throw new Core_ReadOnlyObjectException($this); }
///     </body>
///   </method>

///   <method name="offsetExists" returns="boolean">
///     <brief>Проверяет есть ли такой элемент среди вложенных</brief>
///     <args>
///       <arg name="index" type="string" />
///     </args>
///     <body>
  public function offsetExists($index) { return isset($this->children[$index]); }
///     </body>
///   </method>

///   <method name="offsetUnset">
///     <brief>Выкидывает исключение</brief>
///     <args>
///       <arg name="index" brief="имя элемента" />
///     </args>
///     <body>
  public function offsetUnset($index) { throw new Core_ReadOnlyObjectException($this); }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="supporting">

///   <method name="make_element" access="protected" returns="DOMElement">
///     <brief>Строит очередное элемент документа</brief>
///     <args>
///       <arg name="name" type="string" brief="имя элемента" />
///       <arg name="args" brief="массив параметров" />
///     </args>
///     <body>
  protected function make_element($name, array $args) {
    $content    = '';
    $attributes = array();

    if (isset($args[0])) {
      if (is_array($args[0])) {
        if (isset($args[0][0])) $content = array_shift($args[0]);
        $attributes = $args[0];
      } else {
        $content = $args[0];
        if (isset($args[1]) && is_array($args[1])) $attributes = $args[1];
      }
    }

    if ($content instanceof XML_Builder) $content = $content->node;

    if ($content instanceof DOMElement) {
      $element = $this->document->createElement($name);
      $element->appendChild($content);
    } else {
      $element = $this->document->createElement($name, (string) $content);
    }

    foreach ($attributes as $k => $v) if ($v !== null) $element->setAttribute($k, $v);

    $this->node->appendChild($element);

    return $element;
  }
///     </body>
///   </method>

///   </protocol>
}
/// </class>


class XML_ElementIterator implements Iterator {

	protected $reader;
	protected $name;
	protected $doc;
	
	protected $element;
	protected $key = 0;
	protected $current_depth = 0;
	
	protected $safe_limit = 1000000;

	public function __construct($reader, $name, $type = 'node') {
		$this->reader = $reader;
		$this->name = $name;
		$this->doc = new DOMDocument();
		$this->type = $type;
	}
	
	protected function create_element() {
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
	
	protected function element_none() {
		return $this->element = array();
	}
	
	protected function element_node() {
		$node = $this->reader->expand();
		if ($node)
			$this->element = simplexml_import_dom($this->doc->importNode($node, true));
		else
			$this->element = null;
	}
	
	protected function element_attrs() {
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
	
	
	function rewind() {
		$this->move();
		$this->current_depth = $this->reader->depth;
		$this->key = 0;
		$this->element = null;
		$this->create_element();
	}
	
	protected function move() {
		$i = 0;
		while ($this->reader->read()) {
			if ($this->reader->name == $this->name && $this->reader->nodeType == XMLReader::ELEMENT)
				return true;
			if ($i > $this->safe_limit) return false;
			$i++;
		}
		return true;
	}
	
	protected function search() {
		$i = 0;
		while ($this->reader->read()) {
			if ($this->reader->depth < $this->current_depth) {
				return false;
			}
			if (($this->reader->name == $this->name && $this->reader->nodeType == XMLReader::ELEMENT && $this->reader->depth == $this->current_depth))
				return true;
			if ($i > $this->safe_limit) return false;
			$i++;
		}
		return true;
	}

	function current() {
		return $this->element;
	}

	function key() {
		return $this->key;
	}

	function next() {
		if ($this->search()) {
			$this->create_element();
			$this->key++;
		}
		else {
			$this->element = null;
		}
	}

	function valid() {
		return !is_null($this->element);
	}

}

class XML_Writer implements Core_CallInterface {
	
	protected $xw;
	
	public function __construct() {
		$this->xw = new XMLWriter();
		$this->xw->openURI('php://output');
		$this->xw->setIndent(true);
	}
	
	public function start_XML(){
		$this->xw->startDocument('1.0', 'utf-8');
	}

	public function end_XML() {
		$this->xw->endDocument();
		$this->xw->flush();
	}

	public function __call($method, $args) {
		return call_user_func_array(array($this->xw, $method), $args);
	}
	
		
	public function cadata($name, $text = null, $attrs = array(), $end = true) {
		return $this->create_element($name, $text, $attrs, true, $end);
	}
	
	public function tag($name, $attrs, $end = true) {
		return $this->create_element($name, null, $attrs, false, $end);
	}
	
	public function content_tag($name, $text = null, $attrs = array(), $end = true) {
		return $this->create_element($name, $text, $attrs, false, $end);
	}

	public function create_element($name, $text = null, $attrs = array(), $cdata = false, $end = true) {
		$this->xw->startElement($name);
		if (!empty($attrs)){
			foreach ($attrs as $k => $v){
				$this->xw->writeAttribute($k, $v);
			}
		}
		if (!is_null($text)) {
			if ($cdata) $this->xw->startCData();
			$this->xw->text($text);
			if ($cdata) $this->xw->endCData();
		}
		if (!$end) return;
		if (!empty($text))
			$this->xw->fullEndElement();
		else
			$this->xw->endElement();
	}
}

/// </module>
