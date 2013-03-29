<?php
/// <module name="Templates.JSON" version="0.2.1" maintainer="timokhin@techart.ru">
///   <brief>JSON шаблоны</brief>

Core::load('Templates', 'Object');

/// <class name="Templates.JSON" stereotype="module">
///   <implements interface="Core.ModuleInterface" />
///   <depends supplier="Templates.JSON.Template" stereotype="creates" />
///   <depends supplier="Templates" stereotype="uses" />
class Templates_JSON implements Core_ModuleInterface {
///   <constants>
  const VERSION = '0.2.1';
///   </constants>

  static protected $helpers;

///   <protocol name="creating">

///   <method name="initialize" scope="class">
///     <brief>Инициализирует модуль</brief>
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

///   <method name="Template" returns="Templates.JSON.Template" scope="class">
///     <brief>Фабричный метод, возвращает объект класса Templates.JSON.Template</brief>
///     <args>
///       <arg name="name" type="string" brief="имя шаблона" />
///     </args>
///     <body>
  static public function Template($name) {  return new Templates_JSON_Template($name);  }
///     </body>
///   </method>

///   </protocol>

}
/// </class>

/// <class name="Templates.JSON.Template" extends="Templates.Template">
///     <brief>JSON-шаблон</brief>
///   <depends supplier="Templates.MissingTemplateException" stereotype="throws" />
class Templates_JSON_Template extends Templates_Template {

///   <protocol name="performing">

///   <method name="render" returns="string">
///     <brief>Вовзращает конечный результат</brief>
///     <body>
  public function render() { return $this->load($this->path); }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="supporting">

///   <method name="get_helpers" returns="Object.Aggregator" access="protected">
///     <brief>Возвращает делегатор хелперов</brief>
///     <body>
  protected function get_helpers() {
    return Core::if_null($this->helpers, Templates_JSON::helpers());
  }
///     </body>
///   </method>

///   <method name="get_path" returns="string">
///     <brief>Вовзращает путь до шаблона</brief>
///     <body>
  protected function get_path() { return parent::get_path().'.pjson'; }
///     </body>
///   </method>

///   <method name="load" access="protected" returns="Templates.JSON.Template">
///     <brief>Инклюдит шаблон, создавая необходимые переменные</brief>
///     <args>
///       <arg name="__path" brief="путь до шаблона" />
///     </args>
///     <body>
  protected function load($__path) {
    foreach ($this->parms as $__k => $__v) $$__k = $__v;
    $parms = $this->parms;
    $json = array();
    if (IO_FS::exists($__path)) {
      ob_start();
      include($__path);
      return Core::if_not(ob_get_clean(), json_encode((array) $json));
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
