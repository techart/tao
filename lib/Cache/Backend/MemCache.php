<?php
/// <module name="Cache.Backend.MemCache" version="0.2.0" maintainer="svistunov@techart.ru">
///   <brief>MemCache кэширование</brief>
/// <class name="Cache.Backend.MemCache" stereotype="module">
///   <implements interface="Core.ModuleInterface" />
///   <depends supplier="Cache.Backend.MemCache.Backend" stereotype="creates" />
class Cache_Backend_MemCache implements Core_ModuleInterface {

///   <constants>
  const VERSION = '0.2.0';
  const DEFAULT_TIMEOUT = 60;
///   </constants>

///   <protocol name="creating">

///   <method name="initialize" scope="class">
///     <brief>Инициализация модуля</brief>
///     <details>Проверяет включено ли разширение memcache</details>
///     <body>
  static public function initialize() {
    if (!extension_loaded('memcache'))
      throw new Cache_Exception('No memcache php-module');
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="building">

///   <method name="Backend" returns="Cache.Backend.MemCache.Backend">
///     <brief>Фабричный метод, возвращает объект класса Cache.Backend.MemCache.Backend</brief>
///     <args>
///       <arg name="dsn" type="string" brief="строка подключения" />
///       <arg name="timeout" type="int" default="Cache_Backend_MemCache::DEFAULT_TIMEOUT" brief="время в течении которого хранится значение в кэше (сек)" />
///     </args>
///     <body>
  public function Backend($dsn, $timeout = Cache_Backend_MemCache::DEFAULT_TIMEOUT) {
    return new Cache_Backend_MemCache_Backend($dsn, $timeout);
  }
///     </body>
///   </method>

///   </protocol>
}
/// </class>

/// <class name="Cache.Backend.MemCache.Backend" extends="Cache.Backend">
///   <brief>Класс реализующий memcache кэширование</brief>
class Cache_Backend_MemCache_Backend extends Cache_Backend {

  private $memcache;

///   <protocol name="creating">

///   <method name="__construct">
///     <brief>Конструктор</brief>
///     <args>
///       <arg name="dsn" type="string" brief="строка подключения" />
///       <arg name="timeout" type="int" default="Cache_Backend_MemCache::DEFAULT_TIMEOUT" brief="время в течении которого хранится значение в кэше (сек)" />
///     </args>
///     <body>
  public function __construct($dsn, $timeout = Cache_Backend_MemCache::DEFAULT_TIMEOUT) {
    $m1 = Core_Regexps::match_with_results('{^memcache://([^:]+):?(\d+)?}', $dsn);
    if (!$m1) throw new Cache_BadDSNException($dsn);
    $this->memcache = new Memcache;
    if (!$this->memcache->connect($m1[1], Core::if_null($m1[2], 11211)))
      throw new Cache_Exception('Could not connect');
    $this->timeout = $timeout;
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="processing">

///   <method name="close" access="protected">
///     <brief>Закрывает соединение с memcache</brief>
///     <body>
  protected function close() {
    $this->memcache->close();
  }
///     </body>
///   </method>

///   <method name="get" returns="mixed">
///     <brief>Возвращает значение по ключу, если значение не установлено возвращает $default</brief>
///     <args>
///       <arg name="key" type="string" brief="ключ" />
///       <arg name="default" default="null" brief="значение по умолчанию" />
///     </args>
///     <body>
 public function get($key, $default = null) {
    $res = Core::if_false($this->memcache->get($key), $default);
    Events::call('cache.get', $key, $default, $res);
    return $res;
  }
///     </body>
///   </method>

///   <method name="set" returns="boolean">
///     <brief>Устанавливает значение по ключу</brief>
///     <args>
///       <arg name="key" type="string" brief="ключ" />
///       <arg name="value" brief="значение" />
///       <arg name="timeout" type="int" brief="время в течении которого хранится значение в кэше (сек)" />
///     </args>
///     <body>
  public function set($key, $value, $timeout = null) {
    $timeout = Core::if_null($timeout, $this->timeout);
    Events::call('cache.set', $key, $value, $timeout);
    return $this->memcache->set($key, $value, false, $timeout);
  }
///     </body>
///   </method>

///   <method name="delete" returns="boolean">
///     <brief>Удалят значение из кэша</brief>
///     <args>
///       <arg name="key" type="string" brief="ключ" />
///     </args>
///     <body>
  public function delete($key) {
    Events::call('cache.delete', $key);
    return $this->memcache->delete($key); }
///     </body>
///   </method>

///   <method name="has" returns="boolean">
///     <brief>Проверяет есть ли занчение с ключом $key в кэше</brief>
///     <args>
///       <arg name="key" type="string" brief="ключ" />
///     </args>
///     <body>
  public function has($key) {
    Events::call('cache.has', $key);
    return (boolean) $this->memcache->get($key); }
///     </body>
///   </method>

///   <method name="flush">
///     <brief>Инвалидирует кэш</brief>
///     <body>
  public function flush() {
    Events::call('cache.flush');
    Events::call('cache.delete', $s = '*');
    return $this->memcache->flush();
  }
///     </body>
///   </method>

///   </protocol>
}
/// </class>

/// </module>
