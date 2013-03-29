<?php
/// <module name="Templates" version="0.2.1" maintainer="timokhin@techart.ru">
///   <brief>Модуль поределяет базовае классы для шаблонов</brief>
Core::load('IO.FS', 'Object');

/// <class name="Templates" stereotype="module">
///   <implements interface="Core.ConfigurableModuleInterface" />
///   <depends supplier="Templates.HTML.Template" stereotype="creates" />
///   <depends supplier="Templates.XML.Template" stereotype="creates" />
///   <depends supplier="Templates.Text.Template" stereotype="creates" />
///   <depends supplier="Templates.JSON.Template" stereotype="creates" />
class Templates implements Core_ConfigurableModuleInterface {
///   <constants>
  const VERSION = '0.2.1';
///   </constants>

  static protected $options = array(
    'templates_root' => array('../app/views'));

  static protected $helpers;

///   <protocol name="creating">

///   <method name="initialize" scope="class">
///     <brief>Инициализация модуля</brief>
///     <details>
///       Устанавливает опиции и создает делегатор хелперов.
///       Хелперы - это классы, методы которых будут доступны внутри шаблона, т.е. вызовы будут делегироваться хелперам.
///       Зарегестрировать хелперы можно с помощью Templates::use_helpers либо соответствующим методом модуля или класса шаблона.
///       Примерами хелперов являются классы Templates.HTML.*
///     </details>
///     <args>
///       <arg name="options" type="array" default="array()" brief="массив опций" />
///     </args>
///     <body>
  static public function initialize(array $options = array()) {
    self::options($options);
    self::$helpers = Object::Aggregator();
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="configuring">

///   <method name="options" returns="mixed" scope="class">
///     <brief>Устанваливает опции</brief>
///     <args>
///       <arg name="options" type="array" default="array()" brief="массив опций" />
///     </args>
///     <body>
  static public function options(array $options = array()) {
    if (count($options)) Core_Arrays::update(self::$options, $options);
    return self::$options;
  }
///     </body>
///   </method>

///   <method name="option" returns="mixed">
///     <brief>Устанавливает опцию</brief>
///     <args>
///       <arg name="name" type="string" brief="название опции" />
///       <arg name="value" default="null" brief="значение" />
///     </args>
///     <body>
  static public function option($name, $value = null) {
    $prev = null;
    if (array_key_exists($name, self::$options)) {
      $prev = self::$options[$name];
      if ($value !== null) self::options(array($name => $value));
    }
    return $prev;
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="configuring">

///   <method name="use_helpers" scope="class">
///     <brief>Регестрирует хелперы</brief>
///     <body>
  static public function use_helpers() {
    $args = Core::normalize_args(func_get_args());
    foreach ($args as $k => $v)
      if ($v instanceof Templates_HelperInterface) self::$helpers->append($v, $k);
  }
///     </body>
///   </method>

///   <method name="use_helper" scope="class">
///     <args>
///       <arg name="name" type="string" />
///       <arg name="helper" />
///     </args>
///     <body>
  static public function use_helper($name, $helper) {
    self::$helpers->append($helper, $name);
  }
///     </body>
///   </method>

///   <method name="helpers" returns="Object.Aggregator">
///     <brief>Возвращает делегатор хелперов</brief>
///     <body>
  static public function helpers() { return self::$helpers; }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="supporting">

  static function is_absolute_path($path) {
    return in_array($path[0], array('.', '/'));
  }
  
  static function add_extension($path, $extension) {
    return Core_Strings::ends_with($path, $extension) ? $path : $path.$extension;
  }

///   <method name="absolute_path" returns="string">
///     <args>
///       <arg name="path" type="stirng" />
///     </args>
  static function get_path($path, $extension = '') {
    if (!empty($extension))
      $path = self::add_extension($path, $extension);
    if (self::is_absolute_path($path))
      return $path;
    foreach (self::option('templates_root') as $root)
      if (IO_FS::exists($res = $root . '/' . $path)) break;
    return $res;
  }
///   </method>

  static public function add_path($path) {
    if (!in_array($path, self::option('templates_root')))
      array_unshift(self::$options['templates_root'], $path);
  }
///   </protocol>

///   <protocol name="building">

///   <method name="HTML" returns="Templates.HTML.Template" scope="class">
///     <brief>Фабричный метод, возвращает объект класса Templates.HTML.Template</brief>
///     <args>
///       <arg name="name" type="string" brief="имя шаблона" />
///     </args>
///     <body>
  static public function HTML($name) {
    static $loaded = false;
    if (!$loaded) {
      Core::load('Templates.HTML');
      $loaded = true;
    }
    return Templates_HTML::Template($name);
  }
///     </body>
///   </method>

///   <method name="XML" returns="Templates.XML.Template" scope="class">
///     <brief>Фабричный метод, возвращает объект класса Templates.XML.Template</brief>
///     <args>
///       <arg name="name" type="string" brief="имя шаблона" />
///     </args>
///     <body>
  static public function XML($name) {
    static $loaded;
    if (!$loaded) {
      Core::load('Templates.XML');
      $loaded = true;
    }
    return new Templates_XML_Template($name);
  }
///     </body>
///   </method>

///   <method name="Text" returns="Templates.Text.Template" scope="class">
///     <brief>Фабричный метод, возвращает объект класса Templates.Text.Template</brief>
///     <args>
///       <arg name="name" type="string" brief="имя шаблона" />
///     </args>
///     <body>
  static public function Text($name) {
    static $loaded;
    if (!$loaded) {
      Core::load('Templates.Text');
      $loaded = true;
    }
    return new Templates_Text_Template($name);
  }
///     </body>
///   </method>

///   <method name="JSON" returns="Templates.JSON.Template" scope="class">
///     <brief>Фабричный метод, возвращает объект класса Templates.JSON.Template</brief>
///     <args>
///       <arg name="name" type="string" brief="имя шаблона" />
///     </args>
///     <body>
  static public function JSON($name) {
    Core::load('Templates.JSON');
    return new Templates_JSON_Template($name);
  }
///     </body>
///   </method>

///   </protocol>
}
/// </class>

/// <interface name="Templates.HelperInterface">
///   <brief>Интерфей хелпера</brief>
///     <details>
///       Все хелперы должны реализовывать этот интерфейс
///     </details>
interface Templates_HelperInterface {}
/// </interface>


/// <class name="Templates.Exception" extends="Core.Exception" stereotype="exception">
///   <brief>Класс исключения</brief>
class Templates_Exception extends Core_Exception {}
/// </class>


/// <class name="Templates.MissingTemplateException" extends="Templates.Exception" stereotype="exception">
///   <brief>Класс исключения для отсутствующего шаблона</brief>
class Templates_MissingTemplateException extends Templates_Exception {

  protected $path;

///   <protocol name="creating">

///   <method name="__construct" >
///     <brief>Конструктор</brief>
///     <args>
///       <arg name="path" type="string" brief="путь к шаблону" />
///     </args>
///     <body>
  public function __construct($path) {
    $this->path = $path;
    parent::__construct("Missing template for path: $path");
  }
///     </body>
///   </method>

///   </protocol>

}
/// </class>


/// <class name="Templates.Template" stereotype="abstract">
///   <brief>Абстрактный класс шаблона</brief>
///   <implements interface="Core.PropertyAccessInterface" />
///   <implements interface="Core.StringifyInterface" />
///   <implements interface="Core.CallInterface" />
///   <depends supplier="Templates" stereotype="uses" />
abstract class Templates_Template
  implements Core_PropertyAccessInterface,
             Core_CallInterface,
             Core_StringifyInterface {

  private $name;

  protected $current_helper;
  protected $helpers;
  protected $parms = array();
  protected $extension;
  protected $cache;
  protected $options = array();

///   <protocol name="creating">

///   <method name="__construct">
///     <brief>Конструктор</brief>
///     <args>
///       <arg name="name" type="string" brief="имя шаблона" />
///     </args>
///     <body>
  public function __construct($name) {
    $this->name = $name;
    $this->helpers = Object::Aggregator()->fallback_to($this->get_helpers());
    $this->setup();
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="configuring">

///   <method name="use_helpers" returns="Templates.Templates">
///     <brief>Регистрирует хелперы для данного шаблона</brief>
///     <body>
  public function use_helpers() {
    $args = Core::normalize_args(func_get_args());
    if (count($args) > 0)
      foreach ($args as $k => $v)
        if ($v instanceof Templates_HelperInterface) $this->helpers->append($v, $k);

    return $this;
  }
///     </body>
///   </method>

///   <method name="use_helper" scope="class">
///     <args>
///       <arg name="name" type="string" />
///       <arg name="helper" />
///     </args>
///     <body>
  static public function use_helper($name, $helper) {
    $this->helpers->append($helper, $name);
  }
///     </body>
///   </method>

  public function options($options = array()) {
    foreach ($options as $k => $v)
      $this->option($k, $v);
    return $this;
  }

  public function option($name, $value) {
    $this->options[$name] = $value;
    return $this;
  }

///   <method name="with" returns="Templates.Template">
///     <brief>Устанавливает/добавляет переменные шаблона</brief>
///     <details>
///       Эти переменные можно будет использовать в шаблоне
///     </details>
///     <body>
  public function with() {
    $args = func_get_args();

    if (count($args) == 1)
      foreach ($args[0] as $k => $v) $this->update_parm($k, $v);
    else
      for ($i = 0; $i < count($args); $i+=2) $this->update_parm($args[$i], isset($args[$i+1]) ? $args[$i+1] : null);

    return $this;
  }
///     </body>
///   </method>

  public function update_parm($name, $value) {
    if (isset($this->parms[$name]) && is_array($this->parms[$name]) && is_array($value))
      $this->parms[$name] = array_merge($this->parms[$name], $value);
    else
      $this->parms[$name] = $value;
    return $this;
  }
  
   public function replace_parm($name, $value) {
     $this->parms[$name] = $value;
     return $this;
   }

///   </protocol>

///   <protocol name="performing">

///   <method name="render" returns="string" stereotype="abstract">
///     <brief>Возвращает конечный результат</brief>
///     <body>
  abstract public function render();
///     </body>
///   </method>

///   </protocol>

///   <protocol name="calling">

///   <method name="__call" returns="mixed">
///     <brief>С помощью вызова метода можно зарегестрировать хелпер</brief>
///     <args>
///       <arg name="method" type="string" />
///       <arg name="args" type="array" />
///     </args>
///     <body>
  public function __call($method, $args) {
    if (!empty($this->current_helper) && method_exists($this->current_helper, $method))
      return call_user_func_array(array($this->current_helper, $method), array_merge(array($this), $args));
    else
      return $this->get_helpers()->__call($method, array_merge(array($this), $args));
  }
///     </body>
///  </method>

///   </protocol>

///   <protocol name="accessing">

///   <method name="__get" returns="mixed">
///     <brief>Доступ на чтение к свойствам объекта</brief>
///     <details>
///       <dl>
///         <dt>name</dt><dd>имя шаблона</dd>
///         <dt>parms</dt><dd>параметры/переменный шаблона</dd>
///         <dt>path</dt><dd>путь к шаблону</dd>
///         <dt>helpers</dt><dd>хелперы шаблона</dd>
///       </dl>
///     </details>
///     <args>
///       <arg name="property" type="string" brief="имя свойства" />
///     </args>
///     <body>
  public function __get($property) {
    switch ($property) {
      case 'name':
        return $this->$property;
      case 'parms':
        return $this->get_parms();
      case 'path':
        return $this->get_path();
      case 'helpers':
        return $this->get_helpers();
      default:
        if (isset($this->options[$property]))
          return $this->options[$property];
        $helpers = $this->get_helpers();
        if (isset($helpers[$property])) {
            $this->current_helper = $helpers[$property];
            return $this;
        }
        throw new Core_MissingPropertyException($property);
    }
  }
///     </body>
///   </method>

///   <method name="__set" returns="mixed">
///     <brief>Доступ на запись к свойствам объекта</brief>
///     <details>
///       Выбрасывается исключение, доступ только для чтения
///     </details>
///     <args>
///       <arg name="property" type="string" brief="имя свойства" />
///       <arg name="value" brief="значение" />
///     </args>
///     <body>
  public function __set($property, $value) {
    switch ($property) {
      case 'name':
      case 'path':
      case 'parms':
      case 'helpers':
        throw new Core_ReadOnlyPropertyException($property);
      default:
        if ($value instanceof Templates_HelperInterface) {
          $helpers = $this->get_helpers();
          return $this->use_helper($property, $value);
        }
        $this->options[$property] = $value;
        return $this;
        //throw new Core_MissingPropertyException($property);
    }
  }
///     </body>
///   </method>

///   <method name="__isset" returns="boolean">
///     <brief>Проверяет установленно ли свойтсво</brief>
///     <args>
///       <arg name="property" type="string" brief="имя свойства" />
///     </args>
///     <body>
  public function __isset($property) {
    switch ($property) {
      case 'name':
        return isset($this->$property);
      case 'path':
      case 'parms':
      case 'helpers':
        return true;
      default:
        return isset($this->helpers[$property]) || isset($this->options[$property]);
    }
  }
///     </body>
///   </method>

///   <method name="__unset">
///     <brief>Очищает свойство объекта</brief>
///     <details>
///       Выбрасывается исключение, доступ только для чтения
///     </details>
///     <args>
///       <arg name="property" type="string" brief="имя свойства" />
///     </args>
///     <body>
  public function __unset($property) {
    switch ($property) {
      case 'name':
      case 'path':
      case 'parms':
      case 'helpers':
        throw new Core_UndestroyablePropertyException($property);
      default:
        if (isset($this->helpers[$property]))
            unset($this->helpers[$property]);
        if (isset($this->options[$property]))
            unset($this->options[$property]);
        throw new Core_MissingPropertyException($property);
    }
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="stringifying">

///   <method name="as_string" returns="string">
///     <brief>Вовзращает результат ввиде строки</brief>
///     <body>
  public function as_string() { return $this->render(); }
///     </body>
///   </method>

///   <method name="__toString" returns="string">
///     <brief>Вовзращает результат ввиде строки</brief>
///     <body>
  public function __toString() { return $this->as_string(); }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="supporting">

  public function exists() { return IO_FS::exists($this->get_path());}

///   <method name="get_helpers" returns="Object.Aggregator" access="protected" stereotype="abstract">
///     <brief>Возвращает делигатор хелперов для шаблона</brief>
///     <body>
  abstract protected function get_helpers();
///     </body>
///   </method>

///   <method name="get_parms" returns="array" access="protected">
///     <brief>Возвращает параметры/переменные шаблона</brief>
///     <body>
  protected function get_parms() { return $this->parms; }
///     </body>
///   </method>

///   <method name="get_path" returns="string">
///     <brief>Возвращает путь до шаблона</brief>
///     <body>
  protected function get_path() {
    return Templates::get_path($this->name, $this->extension);
  }
///     </body>
///   </method>

///   <method name="setup" returns="Templates.Template" access="protected">
///     <brief>Метод для предварительных настроек</brief>
///     <body>
  protected function setup() {}
///     </body>
///   </method>

///   </protocol>
}
/// </class>

abstract class Templates_CacheableTemplate extends Templates_Template {

  protected $record_data = array();
  protected $record = false;
  protected $cache_self = false;
  protected $record_level = 0;

  public function cache($backend = null) {
    if (!is_null($backend)) {
      $this->cache = $backend;
    }
    if (is_null($this->cache)) {
      $this->cache = clone WS::env()->cache;
    }
    return $this->cache;
  }

  public function set_timeout($to) {
    $this->cache()->set_timeout($to);
    return $this;
  }

  public function enable_cache($v = true) {
    $this->cache_self = $v;
    return $this;
  }

  public function record($v = true) {
    $this->record = $v;
    if ($v) $this->record_level++;
    if (!$v) {
      $this->record_level--;
      if ($this->record_level == 0)
        $this->recrod_clear();
    }
    return $this;
  }

  protected function recrod_clear() {
    $this->record_data = array();
    return $this;
  }

  protected function record_call($method, $args = array()) {
    $this->record_data[] = array('method' => $method, 'args' => $args);
  }

  public function merge($obj) {
    $this->parms = array_merge($this->parms, $obj->parms);
    foreach ($obj->record_data as $data) {
      call_user_func_array(array($this, $data['method']), $data['args']);
    }
    return $this;
  }

  public function __sleep() {
    return array(/*'parms',*/ 'extension', 'record_data');
  }

  public function cached_call($key, $callback, $args = array(), $timeout = null, $force = false) {
    if ($c = $this->cache()->get($key)) {
        if (isset($c['obj']))
          $this->merge(unserialize($c['obj']));
        return $c['result'];
    }
    $result = null;
    $this->record();
    $result = call_user_func_array($callback, $args);

    if (!is_null($result) && $this->record_level == 1) {
      $this->cache()->set($key, array('result' => $result, 'obj' => serialize($this)), $timeout);
    }

    $this->record(false);
    return $result;
  }

  protected function helper_cache_key($method, $args) {
    return $this->cache_key($method, $args, 'helpers');
  }

  protected function cache_key($method, $args, $type) {
    try {
      $args_key = md5(serialize($args));
    } catch (Exception $e) {
      $args_key = 'exception';
    }
    return 'templates:' . $this->name . ":{$type}:" . $method . ':' . $args_key;
  }

  public function __call($method, $args) {
    if (Core_Strings::ends_with($method, '_cache')) {
      $method = str_replace('_cache', '', $method);
      //TODO: to Core::call
      if (version_compare(PHP_VERSION, '5.3.0') >= 0)
        $call = array('parent', '__call');
      else
        $call = array($this, 'parent::__call');
      return $this->cached_call($this->helper_cache_key($method, $args), $call, array($method, $args));
    }
    return parent::__call($method, $args);
  }

}


/// <class name="Templates.NestableTemplate" extends="Templates.Template" stereotype="abstract">
///     <brief>Вложенный шаблон</brief>
abstract class Templates_NestableTemplate extends Templates_CacheableTemplate {

  private $container;


///   <protocol name="performing">

///   <method name="inside" returns="Templates.NestableTemplate">
///     <brief>Устанавливает внутри какого шаблона находиться данный шаблон</brief>
///     <args>
///       <arg name="container" type="Templates.Text.Template" brief="шаблон-контейнер" />
///     </args>
///     <body>
  public function inside(Templates_NestableTemplate $container) {
    $this->container = $container;
    $this->helpers->fallback_to($this->container->helpers);
    return $this;
  }
///     </body>
///   </method>

  public function pull() {
    $this->container = null;
    //$this->helpers->clear_fallback();
    return $this;
  }

///   </protocol>

///   <protocol name="performing">

///   <method name="render" returns="string">
///     <brief>Возвращает конечный результат</brief>
///     <body>
  public function render() { return $this->render_nested(); }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="accessing">

///   <method name="__get" returns="mixed">
///     <brief>Доступ на чтение к свойствам объекта</brief>
///     <args>
///       <arg name="property" type="string" brief="имя свойства" />
///     </args>
///     <body>
  public function __get($property) {
    switch ($property) {
      case 'container':
        return $this->$property;
      case 'root':
        return empty($this->container) ? $this : $this->container->root;
      case 'is_root':
        return empty($this->container);
      default:
        return parent::__get($property);
    }
  }
///     </body>
///   </method>

///   <method name="__set" returns="mixed">
///     <brief>Доступ на запись к свойствам объекта</brief>
///     <details>
///       Выбрасывает исключение, доступ только для чтения
///     </details>
///     <args>
///       <arg name="property" type="string" brief="имя свойства" />
///       <arg name="value" brief="значение" />
///     </args>
///     <body>
  public function __set($property, $value) {
    switch ($property) {
      case 'container':
        throw new Core_ReadOnlyPropertyException($property);
      default:
        return parent::__set($property, $value);
    }
  }
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
      case 'container':
        return isset($this->container);
      default:
        return parent::__isset($property);
    }
  }
///     </body>
///   </method>

///   <method name="__unset">
///     <brief>Очищает свойство объекта</brief>
///     <details>
///       Выбрасывает исключение, доступ только для чтения
///     </details>
///     <args>
///       <arg name="property" type="string" brief="имя свойства объекта" />
///     </args>
///     <body>
  public function __unset($property) {
    switch ($property) {
      case 'container':
        throw new Core_UndestroyablePropertyException($property);
      default:
        parent::__unset($property);
    }
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="supporting">

///   <method name="render_nested" returns="string" stereotype="abstract">
///     <brief>Возвращает конечный результат</brief>
///     <args>
///       <arg name="content" type="ArrayObject" default="null" brief="контент" />
///     </args>
///     <body>
  abstract protected function render_nested(ArrayObject $content = null);
///     </body>
///   </method>

///   <method name="get_parms" returns="array" access="protected">
///     <brief>Вовзращает параметры/переменные объекта</brief>
///     <body>
  protected function get_parms() {
    return $this->container ?
      array_merge($this->container->get_parms(), $this->parms) :
      $this->parms; }
///     </body>
///   </method>

///   </protocol>
}
/// </class>

/// </module>
