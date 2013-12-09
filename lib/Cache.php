<?php

Core::load('Events');

/**
 * Cache
 * 
 * Кэширование данных
 * 
 * @package Cache
 * @version 0.2.1
 */
/**
 * @package Cache
 */
class Cache implements Core_ModuleInterface {
  const VERSION = '0.2.1';
  const DEFAULT_TIMEOUT = 60;

  static private $backends = array(
      'dummy' => 'Cache.Backend.Dummy.Backend',
      'memcache' => 'Cache.Backend.MemCache.Backend',
      'fs' => 'Cache.Backend.FS.Backend',
      'apc' => 'Cache.Backend.APC.Backend');


/**
 * Производит парсинг строки подключения и возвращает соответственный объект
 * 
 * @param  $DSN
 * @return Cache_Backend
 */
  static public function connect($dsn, $timeout = Cache::DEFAULT_TIMEOUT) {
    if (($m = Core_Regexps::match_with_results('{^([a-zA-Z]+)://}', (string) $dsn)) &&
        isset(self::$backends[$m[1]])) {
      $module = self::$backends[$m[1]];
      // Core::load($module = 'Cache.Backend.'.self::$backends[$m[1]]);
      return Core::make($module, $dsn, $timeout);
    } else
      throw new Cache_BadDSNException($dsn);
  }

  static public function add_backend($prefix, $class)
  {
    self::$backends[$prefix] = $class;
  }

}

/**
 * @package Cache
 */
class Cache_Exception extends Core_Exception {}

/**
 * @package Cache
 */
class Cache_BadDSNException extends Cache_Exception {
  protected $dsn;


/**
 * @param string $dsn
 */
  public function __construct($dsn) {
    parent::__construct("Bad DSN or unknown backend for $dsn");
  }

}

/**
 * Абстрактный класс, определяющий интерфейс кэширования
 * 
 * @abstract
 * @package Cache
 */
abstract class Cache_Backend implements Core_IndexedAccessInterface {

  protected $timeout;


/**
 */
  public function __destruct() {
    $this->close();
  }


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


/**
 * Закрывает соединение, если нужно
 * 
 */
  protected function close() {}

/**
 * Возвращает значение по ключу, если значение не установлено возвращает $default
 * 
 * @abstract
 * @param string $key
 * @param  $default
 * @return mixed
 */
  abstract public function get($key, $default = null);

/**
 * Устанавливает значение по ключу с заданным таймаутом или с таймаутом по умолчанию
 * 
 * @abstract
 * @param string $key
 * @param  $value
 * @param int $timeout
 * @return boolean
 */
  abstract public function set($key, $value, $timeout = null);

/**
 * Удалят значение из кэша
 * 
 * @abstract
 * @param string $key
 * @return boolean
 */
  abstract public function delete($key);

/**
 * Проверяет есть ли занчение с ключом $key в кэше
 * 
 * @param string $key
 * @return boolean
 */
  abstract public function has($key);

/**
 * Инвалидирует кэш
 * 
 */
  abstract public function flush();



/**
 * Доступ на чтение к значениям кэша через индексный интерфейс
 * 
 * @param  $index
 * @return mixed
 */
  public function offsetGet($index) { return $this->get($index); }

/**
 * Доступ на запись к значениям кэша через индексный интерфейс
 * 
 * @param  $index
 * @param  $value
 * @return mixed
 */
  public function offsetSet($index, $value) {
    $this->set($index, $value);
    return $this;
  }

/**
 * Проверяет есть ли значение с таким ключом в кэше
 * 
 * @param  $index
 * @return boolean
 */
  public function offsetExists($index) { return $this->has($index); }

/**
 * Удалят значение из кэша
 * 
 * @param  $index
 */
  public function offsetUnset($index) { $this->delete($index); }

}

