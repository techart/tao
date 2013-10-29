<?php

Core::load('Events');

/// <module name="Cache" version="0.2.1" maintainer="timokhin@techart.ru">
///   <brief>Кэширование данных</brief>
/// <class name="Cache" stereotype="module">
///   <implements interface="Core.ModuleInterface" />
///   <depends supplier="Cache.Backend" stereotype="creates" />
///   <depends supplier="Cache.BadDSNException" stereotype="throws" />
class Cache implements Core_ModuleInterface {
///   <constants>
  const VERSION = '0.2.1';
  const DEFAULT_TIMEOUT = 60;
///   </constants>

  static private $backends = array(
      'dummy' => 'Cache.Backend.Dummy.Backend',
      'memcache' => 'Cache.Backend.MemCache.Backend',
      'fs' => 'Cache.Backend.FS.Backend',
      'apc' => 'Cache.Backend.APC.Backend');

///   <protocol name="building">

///   <method name="connect" returns="Cache.Backend" scope="class">
///     <brief>Производит парсинг строки подключения и возвращает соответственный объект</brief>
///     <args>
///       <arg name="DSN" brief="строка подключения" />
///     </args>
///     <body>
  static public function connect($dsn, $timeout = Cache::DEFAULT_TIMEOUT) {
    if (($m = Core_Regexps::match_with_results('{^([a-zA-Z]+)://}', (string) $dsn)) &&
        isset(self::$backends[$m[1]])) {
      $module = self::$backends[$m[1]];
      // Core::load($module = 'Cache.Backend.'.self::$backends[$m[1]]);
      return Core::make($module, $dsn, $timeout);
    } else
      throw new Cache_BadDSNException($dsn);
  }
///     </body>
///   </method>

  static public function add_backend($prefix, $class)
  {
    self::$backends[$prefix] = $class;
  }

///   </protocol>
}
/// </class>

/// <class name="Cache.Exception" extends="Core.Exception" stereotype="exception">
class Cache_Exception extends Core_Exception {}
/// </class>

///   <class name="Cache.BadDSNException" extends="Cache.Exception">
class Cache_BadDSNException extends Cache_Exception {
  protected $dsn;

///   <protocol name="creating">

///   <method name="__construct">
///     <args>
///       <arg name="dsn" type="string" brief="строка подключения" />
///     </args>
///     <body>
  public function __construct($dsn) {
    parent::__construct("Bad DSN or unknown backend for $dsn");
  }
///     </body>
///   </method>

///   </protocol>
}
/// </class>

/// <class name="Cache.Backend" stereotype="abstract">
///   <brief>Абстрактный класс, определяющий интерфейс кэширования</brief>
///   <implements interface="Core.IndexedAccessInterface" />
abstract class Cache_Backend implements Core_IndexedAccessInterface {

  protected $timeout;

///   <protocol name="destroying">

///   <method name="__destruct">
///     <body>
  public function __destruct() {
    $this->close();
  }
///     </body>
///   </method>

///   </protocol>

  public function is_support_nesting()
  {
    return false;
  }

  public function set_timeout($value) {
    $this->timeout = $value;
    return $this;
  }

  public function get_timeout() {
    return $this->timeout;
  }

  public function set_if_not($key, $value, $timeout = null) {
    $g = $this->get($key);
    if (!empty($g)) return $g;
    $this->set($key, Core::invoke($value), $timeout);
    return $value;
  }

///   <protocol name="processing">

///   <method name="close" access="protected">
///     <brief>Закрывает соединение, если нужно</brief>
///     <body>
  protected function close() {}
///     </body>
///   </method>

///   <method name="get" returns="mixed" stereotype="abstract">
///     <brief>Возвращает значение по ключу, если значение не установлено возвращает $default</brief>
///     <args>
///       <arg name="key" type="string" brief="ключ" />
///       <arg name="default" default="null" brief="значение по умолчанию" />
///     </args>
///     <body>
  abstract public function get($key, $default = null);
///     </body>
///   </method>

///   <method name="set" returns="boolean" stereotype="abstract">
///     <brief>Устанавливает значение по ключу с заданным таймаутом или с таймаутом по умолчанию</brief>
///     <args>
///       <arg name="key" type="string" brief="ключ" />
///       <arg name="value" brief="значение" />
///       <arg name="timeout" type="int" brief="время в течении которого хранится значение в кэше (сек)" />
///     </args>
///     <body>
  abstract public function set($key, $value, $timeout = null);
///     </body>
///   </method>

///   <method name="delete" returns="boolean" stereotype="abstract">
///     <brief>Удалят значение из кэша</brief>
///     <args>
///       <arg name="key" type="string" brief="ключ" />
///     </args>
///     <body>
  abstract public function delete($key);
///     </body>
///   </method>

///   <method name="has" returns="boolean">
///     <brief>Проверяет есть ли занчение с ключом $key в кэше</brief>
///     <args>
///       <arg name="key" type="string" brief="ключ" />
///     </args>
///     <body>
  abstract public function has($key);
///     </body>
///   </method>

///   <method name="flush">
///     <brief>Инвалидирует кэш</brief>
///     <body>
  abstract public function flush();
///     </body>
///   </method>

///   </protocol>

///   <protocol name="indexing" interface="Core.IndexedAccessInterface">

///   <method name="offsetGet" returns="mixed">
///     <brief>Доступ на чтение к значениям кэша через индексный интерфейс</brief>
///     <args>
///       <arg name="index" brief="ключ" />
///     </args>
///     <body>
  public function offsetGet($index) { return $this->get($index); }
///     </body>
///   </method>

///   <method name="offsetSet" returns="mixed">
///     <brief>Доступ на запись к значениям кэша через индексный интерфейс</brief>
///     <args>
///       <arg name="index" brief="ключ" />
///       <arg name="value" brief="значение" />
///     </args>
///     <body>
  public function offsetSet($index, $value) {
    $this->set($index, $value);
    return $this;
  }
///     </body>
///   </method>

///   <method name="offsetExists" returns="boolean">
///     <brief>Проверяет есть ли значение с таким ключом в кэше</brief>
///     <args>
///       <arg name="index" brief="ключ" />
///     </args>
///     <body>
  public function offsetExists($index) { return $this->has($index); }
///     </body>
///   </method>

///   <method name="offsetUnset">
///     <brief>Удалят значение из кэша</brief>
///     <args>
///       <arg name="index" brief="ключ" />
///     </args>
///     <body>
  public function offsetUnset($index) { $this->delete($index); }
///     </body>
///   </method>

///   </protocol>
}
/// </class>

/// </module>
