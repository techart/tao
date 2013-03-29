<?php
/// <module name="Cache.Backend.Dummy" version="0.2.0" maintainer="timokhin@techart.ru">
///   <brief>Модуль dummy-кэширования (кэширование, которое ничего не кэширует)</brief>
/// <class name="Cache.Backend.Dummy" stereotype="module">
///   <implements interface="Core.ModuleInterface" />
///   <depends supplier="Cache.Backend.Dummy.Backend" stereotype="creates" />
class Cache_Backend_Dummy implements Core_ModuleInterface {

///   <constants>
  const VERSION = '0.2.0';
///   </constants>

///   <protocol name="building">

///   <method name="Backend">
///     <args>
///       <arg name="dsn" type="string" />
///     </args>
///     <body>
  public function Backend($dsn, $timeout = Cache::DEFAULT_TIMEOUT) {
    return new Cache_Backend_Dummy_Backend($dsn, $timeout);
  }
///     </body>
///   </method>

///   </protocol>
}
/// </class>

/// <class name="Cache.Backend.Dummy.Backend" extends="Cache.Backend">
///   <brief>Класс реализующий dummy-кэширование</brief>
class Cache_Backend_Dummy_Backend extends Cache_Backend {

///   <protocol name="creating">

///   <method name="__construct">
///     <args>
///       <arg name="dsn" type="string" breif="строка подключения вида: 'dummy://.*'" />
///     </args>
///     <body>
  public function __construct($dsn, $timeout = Cache::DEFAULT_TIMEOUT) {
    if (!Core_Regexps::match('{^dummy://.*}', $dsn)) throw new Cache_BadDSNException($dsn);
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="processing">

///   <method name="get" returns="mixed">
///     <brief>Возвращает $default</brief>
///     <args>
///       <arg name="key" type="string" brief="ключ" />
///       <arg name="default" default="null" brief="значение по умолчанию" />
///     </args>
///     <body>
  public function get($key, $default = null) {
    Events::call('cache.get', $key, $default, $default);
    return $default;
  }
///     </body>
///   </method>

///   <method name="set" returns="boolean">
///     <brief>Возвращает false</brief>
///     <args>
///       <arg name="key" type="string" brief="ключ" />
///       <arg name="value" brief="значение" />
///       <arg name="timeout" type="int" breif="время в течении которого хранится значение в кэше (сек)" />
///     </args>
///     <body>
  public function set($key, $value, $timeout = null) {
    Events::call('cache.set', $key, $value, $timeout);
    return false;
  }
///     </body>
///   </method>

///   <method name="delete" returns="boolean">
///     <brief>Возвращает false</brief>
///     <args>
///       <arg name="key" type="string" brief="ключ" />
///     </args>
///     <body>
  public function delete($key) {
    Events::call('cache.delete', $key);
    return false;
  }
///     </body>
///   </method>

///   <method name="has" returns="boolean">
///     <brief>Возвращает false</brief>
///     <args>
///       <arg name="key" type="string" brief="ключ" />
///     </args>
///     <body>
  public function has($key) {
    Events::call('cache.has', $key);
    return false;
  }
///     </body>
///   </method>

///   <method name="flush">
///     <brief>Инвалидирует кэш</brief>
///     <body>
  public function flush() {
    Events::call('cache.flush');
    Events::call('cache.delete', $s = '*');
    return true;
  }
///     </body>
///   </method>

///   </protocol>
}
/// </class>

/// </module>
