<?php
/**
 * Cache.Backend.Dummy
 * 
 * Модуль dummy-кэширования (кэширование, которое ничего не кэширует)
 * 
 * @package Cache\Backend\Dummy
 * @version 0.2.0
 */
/**
 * @package Cache\Backend\Dummy
 */
class Cache_Backend_Dummy implements Core_ModuleInterface {

  const VERSION = '0.2.0';


/**
 * @param string $dsn
 */
  public function Backend($dsn, $timeout = Cache::DEFAULT_TIMEOUT) {
    return new Cache_Backend_Dummy_Backend($dsn, $timeout);
  }

}

/**
 * Класс реализующий dummy-кэширование
 * 
 * @package Cache\Backend\Dummy
 */
class Cache_Backend_Dummy_Backend extends Cache_Backend {


/**
 * @param string $dsn
 */
  public function __construct($dsn, $timeout = Cache::DEFAULT_TIMEOUT) {
    if (!Core_Regexps::match('{^dummy://.*}', $dsn)) throw new Cache_BadDSNException($dsn);
  }



/**
 * Возвращает $default
 * 
 * @param string $key
 * @param  $default
 * @return mixed
 */
  public function get($key, $default = null) {
    Events::call('cache.get', $key, $default, $default);
    return $default;
  }

/**
 * Возвращает false
 * 
 * @param string $key
 * @param  $value
 * @param int $timeout
 * @return boolean
 */
  public function set($key, $value, $timeout = null) {
    Events::call('cache.set', $key, $value, $timeout);
    return false;
  }

/**
 * Возвращает false
 * 
 * @param string $key
 * @return boolean
 */
  public function delete($key) {
    Events::call('cache.delete', $key);
    return false;
  }

/**
 * Возвращает false
 * 
 * @param string $key
 * @return boolean
 */
  public function has($key) {
    Events::call('cache.has', $key);
    return false;
  }

/**
 * Инвалидирует кэш
 * 
 */
  public function flush() {
    Events::call('cache.flush');
    Events::call('cache.delete', $s = '*');
    return true;
  }

}

