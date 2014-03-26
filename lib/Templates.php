<?php
/**
 * Templates
 * 
 * Модуль поределяет базовае классы для шаблонов
 * 
 * @package Templates
 * @version 0.2.1
 */
Core::load('IO.FS', 'Object');

/**
 * @package Templates
 */
class Templates implements Core_ConfigurableModuleInterface {
  const VERSION = '0.2.1';

  static protected $options = array(
    'templates_root' => array('../app/views'));

  static protected $helpers;


/**
 * Инициализация модуля
 * 
 * @param array $options
 */
  static public function initialize(array $options = array()) {
    self::options($options);
    self::$helpers = Object::Aggregator();
    if (Core::option('deprecated')) {
      self::$options['templates_root'][] = Core::tao_dir() . '/deprecated/views';
    }
  }



/**
 * Устанваливает опции
 * 
 * @param array $options
 * @return mixed
 */
  static public function options(array $options = array()) {
    if (count($options)) Core_Arrays::update(self::$options, $options);
    return self::$options;
  }

/**
 * Устанавливает опцию
 * 
 * @param string $name
 * @param  $value
 * @return mixed
 */
  static public function option($name, $value = null) {
    $prev = null;
    if (array_key_exists($name, self::$options)) {
      $prev = self::$options[$name];
      if ($value !== null) self::options(array($name => $value));
    }
    return $prev;
  }



/**
 * Регестрирует хелперы
 * 
 */
  static public function use_helpers() {
    $args = Core::normalize_args(func_get_args());
    foreach ($args as $k => $v)
      if ($v instanceof Templates_HelperInterface) self::$helpers->append($v, $k);
  }

/**
 * @param string $name
 * @param  $helper
 */
  static public function use_helper($name, $helper) {
    self::$helpers->append($helper, $name);
  }

/**
 * Возвращает делегатор хелперов
 * 
 * @return Object_Aggregator
 */
  static public function helpers() { return self::$helpers; }



  static function is_absolute_path($path) {
    return in_array($path[0], array('.', '/'));
  }
  
  static function add_extension($path, $extension) {
    return Core_Strings::ends_with($path, $extension) ? $path : $path.$extension;
  }

/**
 * @param stirng $path
 * @return string
 */
  static function get_path($path, $extension = '', $paths = array()) {
    if (!empty($extension)) {
      $path = self::add_extension($path, $extension);
    }
    if (self::is_absolute_path($path)) {
      return $path;
    }
    $paths = array_merge($paths, self::option('templates_root'));
    foreach ($paths as $root) {
      if (IO_FS::exists($res = $root . '/' . $path)) {
        break;
      }
    }
    return $res;
  }

  public static function add_path($path, $position = 1)
  {
    $paths = self::option('templates_root');
    Core_Arrays::put($paths, $path, $position);
    $paths = array_unique($paths);
    self::option('templates_root', $paths);
    return $paths;
  }

  public static function add_asset_path($path, $position = 1)
  {
    Core::load('Templates.HTML');
    Templates_HTML::append_assets_path($path, $position);
  }

/**
 * Фабричный метод, возвращает объект класса Templates.HTML.Template
 * 
 * @param string $name
 * @return Templates_HTML_Template
 */
  static public function HTML($name) {
    static $loaded = false;
    if (!$loaded) {
      Core::load('Templates.HTML');
      $loaded = true;
    }
    return Templates_HTML::Template($name);
  }

/**
 * Фабричный метод, возвращает объект класса Templates.XML.Template
 * 
 * @param string $name
 * @return Templates_XML_Template
 */
  static public function XML($name) {
    static $loaded;
    if (!$loaded) {
      Core::load('Templates.XML');
      $loaded = true;
    }
    return new Templates_XML_Template($name);
  }

/**
 * Фабричный метод, возвращает объект класса Templates.Text.Template
 * 
 * @param string $name
 * @return Templates_Text_Template
 */
  static public function Text($name) {
    static $loaded;
    if (!$loaded) {
      Core::load('Templates.Text');
      $loaded = true;
    }
    return new Templates_Text_Template($name);
  }

/**
 * Фабричный метод, возвращает объект класса Templates.JSON.Template
 * 
 * @param string $name
 * @return Templates_JSON_Template
 */
  static public function JSON($name) {
    Core::load('Templates.JSON');
    return new Templates_JSON_Template($name);
  }

}

/**
 * Интерфей хелпера
 * 
 * Все хелперы должны реализовывать этот интерфейс
 * 
 * @package Templates
 */
interface Templates_HelperInterface {}


/**
 * Класс исключения
 * 
 * @package Templates
 */
class Templates_Exception extends Core_Exception {}


/**
 * Класс исключения для отсутствующего шаблона
 * 
 * @package Templates
 */
class Templates_MissingTemplateException extends Templates_Exception {

  protected $path;


/**
 * Конструктор
 * 
 * @param string $path
 */
  public function __construct($path) {
    $this->path = $path;
    parent::__construct("Missing template for path: $path");
  }


}


/**
 * Абстрактный класс шаблона
 * 
 * @abstract
 * @package Templates
 */
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


/**
 * Конструктор
 * 
 * @param string $name
 */
  public function __construct($name) {
    $this->name = $name;
    $this->helpers = Object::Aggregator()->fallback_to($this->get_helpers());
    $this->setup();
  }



/**
 * Регистрирует хелперы для данного шаблона
 * 
 * @return Templates_Templates
 */
  public function use_helpers() {
    $args = Core::normalize_args(func_get_args());
    if (count($args) > 0)
      foreach ($args as $k => $v)
        if ($v instanceof Templates_HelperInterface) $this->helpers->append($v, $k);

    return $this;
  }

/**
 * @param string $name
 * @param  $helper
 */
  public function use_helper($name, $helper) {
    $this->helpers->append($helper, $name);
  }

  public function options($options = array()) {
    foreach ($options as $k => $v)
      $this->option($k, $v);
    return $this;
  }

  public function option($name, $value) {
    $this->options[$name] = $value;
    return $this;
  }

/**
 * Устанавливает/добавляет переменные шаблона
 * 
 * @return Templates_Template
 */
  public function with() {
    $args = func_get_args();

    if (count($args) == 1)
      foreach ($args[0] as $k => $v) $this->update_parm($k, $v);
    else
      for ($i = 0; $i < count($args); $i+=2) $this->update_parm($args[$i], isset($args[$i+1]) ? $args[$i+1] : null);

    return $this;
  }

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



/**
 * Возвращает конечный результат
 * 
 * @abstract
 * @return string
 */
  abstract public function render();



/**
 * С помощью вызова метода можно зарегестрировать хелпер
 * 
 * @param string $method
 * @param array $args
 * @return mixed
 */
  public function __call($method, $args) {
    if (!empty($this->current_helper) && method_exists($this->current_helper, $method))
      return call_user_func_array(array($this->current_helper, $method), array_merge(array($this), $args));
    else
      return $this->get_helpers()->__call($method, array_merge(array($this), $args));
  }



/**
 * Доступ на чтение к свойствам объекта
 * 
 * @param string $property
 * @return mixed
 */
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

/**
 * Доступ на запись к свойствам объекта
 * 
 * @param string $property
 * @param  $value
 * @return mixed
 */
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

/**
 * Проверяет установленно ли свойтсво
 * 
 * @param string $property
 * @return boolean
 */
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

/**
 * Очищает свойство объекта
 * 
 * @param string $property
 */
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



/**
 * Вовзращает результат ввиде строки
 * 
 * @return string
 */
  public function as_string() { return $this->render(); }

/**
 * Вовзращает результат ввиде строки
 * 
 * @return string
 */
  public function __toString() { return $this->as_string(); }



  public function exists() { return IO_FS::exists($this->get_path());}

/**
 * Возвращает делигатор хелперов для шаблона
 * 
 * @abstract
 * @return Object_Aggregator
 */
  abstract protected function get_helpers();

/**
 * Возвращает параметры/переменные шаблона
 * 
 * @return array
 */
  protected function get_parms() { return $this->parms; }

/**
 * Возвращает путь до шаблона
 * 
 * @return string
 */
  protected function get_path() {
    return Templates::get_path($this->name, $this->extension);
  }

/**
 * Метод для предварительных настроек
 * 
 * @return Templates_Template
 */
  protected function setup() {}

}

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


/**
 * Вложенный шаблон
 * 
 * @abstract
 * @package Templates
 */
abstract class Templates_NestableTemplate extends Templates_CacheableTemplate {

  private $container;



/**
 * Устанавливает внутри какого шаблона находиться данный шаблон
 * 
 * @param Templates_Text_Template $container
 * @return Templates_NestableTemplate
 */
  public function inside(Templates_NestableTemplate $container) {
    $this->container = $container;
    $this->helpers->fallback_to($this->container->helpers);
    return $this;
  }

  public function pull() {
    $this->container = null;
    //$this->helpers->clear_fallback();
    return $this;
  }



/**
 * Возвращает конечный результат
 * 
 * @return string
 */
  public function render() { return $this->render_nested(); }



/**
 * Доступ на чтение к свойствам объекта
 * 
 * @param string $property
 * @return mixed
 */
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

/**
 * Доступ на запись к свойствам объекта
 * 
 * @param string $property
 * @param  $value
 * @return mixed
 */
  public function __set($property, $value) {
    switch ($property) {
      case 'container':
        throw new Core_ReadOnlyPropertyException($property);
      default:
        return parent::__set($property, $value);
    }
  }

/**
 * Проверяет установленно ли свойство объекта
 * 
 * @param string $property
 * @return boolean
 */
  public function __isset($property) {
    switch ($property) {
      case 'container':
        return isset($this->container);
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
      case 'container':
        throw new Core_UndestroyablePropertyException($property);
      default:
        parent::__unset($property);
    }
  }



/**
 * Возвращает конечный результат
 * 
 * @abstract
 * @param ArrayObject $content
 * @return string
 */
  abstract protected function render_nested(ArrayObject $content = null);

/**
 * Вовзращает параметры/переменные объекта
 * 
 * @return array
 */
  protected function get_parms() {
    return $this->container ?
      array_merge($this->container->get_parms(), $this->parms) :
      $this->parms; }

}

