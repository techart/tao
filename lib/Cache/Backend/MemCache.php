<?php
/**
 * Cache.Backend.MemCache
 * 
 * MemCache кэширование
 * 
 * @package Cache\Backend\MemCache
 * @version 0.2.0
 */
/**
 * @package Cache\Backend\MemCache
 */
class Cache_Backend_MemCache implements Core_ModuleInterface {

  const VERSION = '0.2.0';
  const DEFAULT_TIMEOUT = 60;


/**
 * Инициализация модуля
 * 
 */
  static public function initialize() {
    if (!extension_loaded('memcache'))
      throw new Cache_Exception('No memcache php-module');
  }



/**
 * Фабричный метод, возвращает объект класса Cache.Backend.MemCache.Backend
 * 
 * @param string $dsn
 * @param int $timeout
 * @return Cache_Backend_MemCache_Backend
 */
  public function Backend($dsn, $timeout = Cache_Backend_MemCache::DEFAULT_TIMEOUT) {
    return new Cache_Backend_MemCache_Backend($dsn, $timeout);
  }

}

/**
 * Класс реализующий memcache кэширование
 * 
 * @package Cache\Backend\MemCache
 */
class Cache_Backend_MemCache_Backend extends Cache_Backend {

  private $memcache;


/**
 * Конструктор
 * 
 * @param string $dsn
 * @param int $timeout
 */
  public function __construct($dsn, $timeout = Cache_Backend_MemCache::DEFAULT_TIMEOUT) {
    $m1 = Core_Regexps::match_with_results('{^memcache://([^:]+):?(\d+)?}', $dsn);
    if (!$m1) throw new Cache_BadDSNException($dsn);
    $this->memcache = new Memcache;
    if (!$this->memcache->connect($m1[1], Core::if_null($m1[2], 11211)))
      throw new Cache_Exception('Could not connect');
    $this->timeout = $timeout;
  }



/**
 * Закрывает соединение с memcache
 * 
 */
  protected function close() {
    $this->memcache->close();
  }

/**
 * Возвращает значение по ключу, если значение не установлено возвращает $default
 * 
 * @param string $key
 * @param  $default
 * @return mixed
 */
 public function get($key, $default = null) {
    $res = Core::if_false($this->memcache->get($key), $default);
    Events::call('cache.get', $key, $default, $res);
    return $res;
  }

/**
 * Устанавливает значение по ключу
 * 
 * @param string $key
 * @param  $value
 * @param int $timeout
 * @return boolean
 */
  public function set($key, $value, $timeout = null) {
    $timeout = Core::if_null($timeout, $this->timeout);
    Events::call('cache.set', $key, $value, $timeout);
    return $this->memcache->set($key, $value, false, $timeout);
  }

/**
 * Удалят значение из кэша
 * 
 * @param string $key
 * @return boolean
 */
  public function delete($key) {
    Events::call('cache.delete', $key);
    return $this->memcache->delete($key); }

/**
 * Проверяет есть ли занчение с ключом $key в кэше
 * 
 * @param string $key
 * @return boolean
 */
  public function has($key) {
    Events::call('cache.has', $key);
    return (boolean) $this->memcache->get($key); }

/**
 * Инвалидирует кэш
 * 
 */
  public function flush() {
    Events::call('cache.flush');
    Events::call('cache.delete', $s = '*');
    return $this->memcache->flush();
  }

}

