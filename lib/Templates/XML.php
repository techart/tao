<?php
/// <module name="Templates.XML" version="0.2.0" maintainer="timokhin@techart.ru">
///   <brief>XML-шаблоны</brief>

Core::load('Templates', 'XML');

/// <class name="Templates.XML" stereotype="module">
///   <implements interface="Core.ModuleInterface" />
///   <depends supplier="Templates.XML.Template" stereotype="creates" />
///   <depends supplier="Templates" stereotype="uses" />
class Templates_XML implements Core_ModuleInterface {
///   <constants>
  const VERSION = '0.2.0';
///   </constants>

  static protected $helpers;

///   <protocol name="creating">

///   <method name="initialize" scope="class">
///     <brief>Инициализация</brief>
///     <body>
  static public function initialize() {
    self::$helpers = Object::Aggregator()->fallback_to(Templates::helpers());
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="configuring">

///   <method name="use_helpers" scope="class">
///     <brief>Регестрирует хелпер</brief>
///     <body>
  static public function use_helpers() {
    $args = Core::normalize_args(func_get_args());
    foreach ($args as $k => $v)
      if ($v instanceof Templates_HelperInterface)
        self::$helpers->append($v, is_numeric($k) ? null : (string) $k);
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="quering">

///   <method name="helpers" returns="Object.Aggregator">
///     <brief>Возвращает делегатор хелперов</brief>
///     <body>
  static public function helpers() { return self::$helpers; }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="building">

///   <method name="Template" returns="Templates.XML.Template" scope="class">
///     <brief>Фабричный метод, возвращающий объект класса Templates.XML.Template</brief>
///     <args>
///       <arg name="name" type="string" brief="имя шаблона" />
///     </args>
///     <body>
  static public function Template($name) { return new Templates_XML_Template($name); }
///     </body>
///   </method>

///   </protocol>

}
/// </class>

/// <class name="Templates.XML.Template" extends="Templates.Template">
///   <brief>XML-шаблон</brief>
///   <depends supplier="Templates.MissingTemplateException" stereotype="throws" />
class Templates_XML_Template extends Templates_Template {

  protected $xml;

///   <protocol name="creating">

///   <method name="__construct">
///     <brief>Конструктор</brief>
///     <args>
///       <arg name="name" type="string" brief="имя шаблона" />
///     </args>
///     <body>
  public function __construct($name) {
    $this->xml = XML::Builder();
    parent::__construct($name);
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="performing">

///   <method name="render" returns="string">
///     <brief>Возвращает конечный результат</brief>
///     <body>
  public function render() {
    ob_start();
    $this->load($this->path);
    return Core::if_not(ob_get_clean(), $this->xml->as_string());
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="generating">

///   <method name="declaration" returns="string">
///     <brief>Формирует xml описание</brief>
///     <args>
///       <arg name="version"  type="string" default="'1.0'" brief="версия" />
///       <arg name="encoding" type="string" default="'UTF-8'" brief="кодировка" />
///     </args>
///     <body>
  public function declaration($version = '1.0', $encoding = 'UTF-8') {
    return '<?xml version="'.$version.'" encoding="'.$encoding.'" ?>'."\n";
  }
///     </body>
///   </method>

///   <method name="tag" returns="string">
///     <brief>Формирует xml-таг</brief>
///     <args>
///       <arg name="name" type="string" brief="имя тага" />
///       <arg name="attrs" type="array" default="array()" brief="массив атрибутов" />
///       <arg name="close" type="boolean" default="true" brief="закрыавть таг или нет" />
///     </args>
///     <body>
  public function tag($name, array $attrs = array(), $close = true) {
    $tag = '<'.((string) $name);

    foreach ($attrs as $k => $v)
      if (!is_array($v)) $tag .= ($v === true ? " $k " : ( $v === false ? '' :  " $k=\"".htmlspecialchars($v).'"'));

    return $tag .= (boolean) $close ? ' />' : '>';
  }
///     </body>
///   </method>

///   <method name="content_tag" returns="string">
///     <brief>Формирует таг с контеном</brief>
///     <args>
///       <arg name="name" type="string" brief="название" />
///       <arg name="content" type="string" brief="контент" />
///       <arg name="attrs" type="array" default="array()" brief="массив атрибутов" />
///     </args>
///     <body>
  public function content_tag($name, $content, array $attrs = array()) {
    $tag = '<'.((string) $name);

    foreach ($attrs as $k => $v)
      if (!is_array($v))  $tag .= ($v === true ? " $k " : ( $v === false ? '' :  " $k=\"".htmlspecialchars($v).'"'));

    return $tag .= '>'.((string) $content).'</'.((string) $name.'>');
  }
///     </body>
///   </method>

///   <method name="cdata_section" returns="string">
///     <brief>Оборачивает контент в CDATA</brief>
///     <args>
///       <arg name="content" type="string" brief="контент" />
///     </args>
///     <body>
  public function cdata_section($content) { return '<![CDATA['.((string) $content).']'.']>'; }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="supporting">

///   <method name="get_helpers" returns="Object.Aggregator" access="protected">
///     <brief>Возвращает делегатор хелперов</brief>
///     <body>
  protected function get_helpers() {
    return Core::if_null($this->helpers, Templates_XML::helpers());
  }
///     </body>
///   </method>

///   <method name="get_path" returns="string">
///     <brief>Возвращает путь до шаблона</brief>
///     <body>
  protected function get_path() { return parent::get_path().'.pxml'; }
///     </body>
///   </method>

///   <method name="load" access="protected" returns="Templates.XML.Template">
///     <brief>Инклюдит шаблон, создавая необходимые переменные</brief>
///     <args>
///       <arg name="__path" brief="путь к шаблону" />
///     </args>
///     <body>
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
///     </body>
///   </method>

///   </protocol>

///   <protocol name="accessing">

///   <method name="__get" returns="mixed">
///     <brief>Доступ на чтение к свойствам объекта</brief>
///     <details>
///       xml - XML.Builder для построения xml
///     </details>
///     <args>
///       <arg name="property" type="string" brief="имя свойства" />
///     </args>
///     <body>
  public function __get($property) {
    switch ($property) {
      case 'xml':
        return $this->xml->document;
      default:
        return parent::__get($property);
    }
  }
///     </body>
///   </method>

///   <method name="__set" returns="mixed">
///     <brief>Доступ на запись к свойствам объекта</brief>
///     <args>
///       <arg name="property" type="string" brief="имя свойтсва" />
///       <arg name="value" brief="значение" />
///     </args>
///     <body>
  public function __set($property, $value) {
    switch ($property) {
      case 'xml':
        throw new Core_ReadOnlyPropertyException($property);
      default:
        return parent::__set($property, $value);
    }
  }
///     </body>
///   </method>

///   <method name="__isset" returns="boolean">
///     <brief>Проверяет установленно ли свойство</brief>
///     <args>
///       <arg name="property" type="string" brief="имя свойтсва" />
///     </args>
///     <body>
  public function __isset($property) {
    switch ($property) {
      case 'xml':
        return isset($this->$property);
      default:
        return parent::__isset($property);
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
  public function __unset($property) {
    switch ($property) {
      case 'xml':
        throw new Core_UndestroyablePropertyException($property);
      default:
        parent::__unset($property);
    }
  }
///     </body>
///   </method>

///   </protocol>

}
/// </class>

/// </module>
