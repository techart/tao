<?php
/// <module name="Templates.Text" version="0.1.0" maintainer="timokhin@techart.ru">
///   <brief>Текстовые шаблоны</brief>

Core::load('Templates', 'Text', 'Object');

/// <class name="Templates.Text" stereotype="module">
///   <implements interface="Core.ModuleInterface" />
///   <depends supplier="Templates.Text.Template" stereotype="creates" />
///   <depends supplier="Templates" stereotype="uses" />
class Templates_Text implements Core_ModuleInterface {
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
      if ($v instanceof Templates_HelperInterface) self::$helpers->append($v, is_numeric($k) ? null : (string) $k);
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

///   <method name="Template" returns="Templates.Text.Template" scope="class">
///     <brief>Фабричный методот, возвращающий объект класса Templates.Text.Template </brief>
///     <args>
///       <arg name="name" type="string" brief="имя шаблон" />
///     </args>
///     <body>
  static public function Template($name) { return new Templates_Text_Template($name); }
///     </body>
///   </method>

///   </protocol>
}
/// </class>


/// <class name="Templates.Text.Template" extends="Templates.Template">
///   <brief>Текстовый шаблон</brief>
///   <depends supplier="Templates.MissingTemplateException" stereotype="throws" />
class Templates_Text_Template extends Templates_Template {

  protected $text;

///   <protocol name="creating">

///   <method name="__construct">
///     <brief>Конструктор</brief>
///     <body>
  public function __construct($name) { $this->text = Text::Builder(); parent::__construct($name); }
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
    return Core::if_not(ob_get_clean(), $this->text->as_string());
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="supporting">

///   <method name="get_helpers" returns="Object.Aggregator" access="protected">
///     <brief>Возвращает делегатор хелперов</brief>
///     <body>
  protected function get_helpers() {
    return Core::if_null($this->helpers, Templates_Text::helpers());
  }
///     </body>
///   </method>

///   <method name="get_path" returns="string" access="protected">
///     <brief>Возвращает путь к шаблону</brief>
///     <body>
  protected function get_path() { return parent::get_path().'.ptxt'; }
///     </body>
///   </method>

///   <method name="load" returns="Templates.Text.Template" access="protected">
///     <brief>Инклюдит шаблон, создавая необходимые переменные</brief>
///     <args>
///       <arg name="__path" type="string" brief="путь к шаблону" />
///     </args>
///     <body>
  protected function load($__path) {
    foreach ($this->parms as $__k => $__v) $$__k = $__v;
    $parms = $this->parms;
    $text = $this->text;
    if (IO_FS::exists($__path)) {
      include($__path);
      return $this;
    }
    else
      throw new Templates_MissingTemplateException($__path);
  }
///     </body>
///   </method>

///   </protocol>
}
/// </class>

/// </module>
