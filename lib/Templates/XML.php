<?php
/**
 * Templates.XML
 * 
 * XML-шаблоны
 * 
 * @package Templates\XML
 * @version 0.2.0
 */

Core::load('Templates', 'XML');

/**
 * @package Templates\XML
 */
class Templates_XML implements Core_ModuleInterface {
  const VERSION = '0.2.0';

  static protected $helpers;


/**
 * Инициализация
 * 
 */
  static public function initialize() {
    self::$helpers = Object::Aggregator()->fallback_to(Templates::helpers());
  }



/**
 * Регестрирует хелпер
 * 
 */
  static public function use_helpers() {
    $args = Core::normalize_args(func_get_args());
    foreach ($args as $k => $v)
      if ($v instanceof Templates_HelperInterface)
        self::$helpers->append($v, is_numeric($k) ? null : (string) $k);
  }



/**
 * Возвращает делегатор хелперов
 * 
 * @return Object_Aggregator
 */
  static public function helpers() { return self::$helpers; }



/**
 * Фабричный метод, возвращающий объект класса Templates.XML.Template
 * 
 * @param string $name
 * @return Templates_XML_Template
 */
  static public function Template($name) { return new Templates_XML_Template($name); }


}

/**
 * XML-шаблон
 * 
 * @package Templates\XML
 */
class Templates_XML_Template extends Templates_Template {

  protected $xml;


/**
 * Конструктор
 * 
 * @param string $name
 */
  public function __construct($name) {
    $this->xml = XML::Builder();
    parent::__construct($name);
  }



/**
 * Возвращает конечный результат
 * 
 * @return string
 */
  public function render() {
    ob_start();
    $this->load($this->path);
    return Core::if_not(ob_get_clean(), $this->xml->as_string());
  }



/**
 * Формирует xml описание
 * 
 * @param string $version
 * @param string $encoding
 * @return string
 */
  public function declaration($version = '1.0', $encoding = 'UTF-8') {
    return '<?xml version="'.$version.'" encoding="'.$encoding.'" ?>'."\n";
  }

/**
 * Формирует xml-таг
 * 
 * @param string $name
 * @param array $attrs
 * @param boolean $close
 * @return string
 */
  public function tag($name, array $attrs = array(), $close = true) {
    $tag = '<'.((string) $name);

    foreach ($attrs as $k => $v)
      if (!is_array($v)) $tag .= ($v === true ? " $k " : ( $v === false ? '' :  " $k=\"".htmlspecialchars($v).'"'));

    return $tag .= (boolean) $close ? ' />' : '>';
  }

/**
 * Формирует таг с контеном
 * 
 * @param string $name
 * @param string $content
 * @param array $attrs
 * @return string
 */
  public function content_tag($name, $content, array $attrs = array()) {
    $tag = '<'.((string) $name);

    foreach ($attrs as $k => $v)
      if (!is_array($v))  $tag .= ($v === true ? " $k " : ( $v === false ? '' :  " $k=\"".htmlspecialchars($v).'"'));

    return $tag .= '>'.((string) $content).'</'.((string) $name.'>');
  }

/**
 * Оборачивает контент в CDATA
 * 
 * @param string $content
 * @return string
 */
  public function cdata_section($content) { return '<![CDATA['.((string) $content).']'.']>'; }



/**
 * Возвращает делегатор хелперов
 * 
 * @return Object_Aggregator
 */
  protected function get_helpers() {
    return Core::if_null($this->helpers, Templates_XML::helpers());
  }

/**
 * Возвращает путь до шаблона
 * 
 * @return string
 */
  protected function get_path() { return parent::get_path().'.pxml'; }

/**
 * Инклюдит шаблон, создавая необходимые переменные
 * 
 * @param  $__path
 * @return Templates_XML_Template
 */
  protected function load($__path) {
    foreach ($this->parms as $__k => $__v) $$__k = $__v;
    $parms = $this->parms;
    $xml   = $this->xml;
    if (IO_FS::exists($__path))
      include($__path);
    else
      throw new Templates_MissingTemplateException($path);
    return $this;
  }



/**
 * Доступ на чтение к свойствам объекта
 * 
 * @param string $property
 * @return mixed
 */
  public function __get($property) {
    switch ($property) {
      case 'xml':
        return $this->xml->document;
      default:
        return parent::__get($property);
    }
  }

/**
 * Доступ на запись к свойствам объекта
 * 
 * @param string $property
 * @param  $value
 * @return mixed
 */
  public function __set($property, $value) {
    switch ($property) {
      case 'xml':
        throw new Core_ReadOnlyPropertyException($property);
      default:
        return parent::__set($property, $value);
    }
  }

/**
 * Проверяет установленно ли свойство
 * 
 * @param string $property
 * @return boolean
 */
  public function __isset($property) {
    switch ($property) {
      case 'xml':
        return isset($this->$property);
      default:
        return parent::__isset($property);
    }
  }

/**
 * Очищает свойство объекта
 * 
 * @param string $property
 */
  public function __unset($property) {
    switch ($property) {
      case 'xml':
        throw new Core_UndestroyablePropertyException($property);
      default:
        parent::__unset($property);
    }
  }


}

