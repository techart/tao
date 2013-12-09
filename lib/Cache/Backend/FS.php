<?php
/**
 * Cache.Backend.FS
 * 
 * Модуль для кэширования данных ввиде файлов
 * 
 * @package Cache\Backend\FS
 * @version 0.2.0
 */
Core::load('IO.FS', 'Time', 'Cache');

/**
 * @package Cache\Backend\FS
 */
class Cache_Backend_FS implements Core_ModuleInterface {

  const VERSION = '0.2.0';
  const DEFAULT_TIMEOUT = 60;


/**
 * Возвращает объект класса Cache_Backend_FS_Backend
 * 
 * @param string $dsn
 * @param int $timeout
 */
  public function Backend($dsn, $timeout = Cache_Backend_FS::DEFAULT_TIMEOUT) {
    return new Cache_Backend_FS_Backend($dsn, $timeout);
  }

}

/**
 * Класс реализующий файловое кэширование
 * 
 * @package Cache\Backend\FS
 */
class Cache_Backend_FS_Backend extends Cache_Backend {

  private $path;
  protected $prefix = 'fs';

  protected $use_array_cache = true;
  protected $values = array();
  protected $timestamps = array();


/**
 * Конструктор
 * 
 * @param string $dsn
 * @param int $timeout
 */
  public function __construct($dsn, $timeout = Cache_Backend_FS::DEFAULT_TIMEOUT) {
    $m1 = Core_Regexps::match_with_results("|^{$this->prefix}://(.*)|", $dsn);
    if (!$m1) throw new Cache_BadDSNException($dsn);
    $this->path = rtrim($m1[1], DIRECTORY_SEPARATOR);
    if (!IO_FS::exists($this->path)) {
      IO_FS::mkdir($this->path, null, true);
    }
    $this->timeout = $timeout;
  }


  public function is_support_nesting()
  {
    return true;
  }

  public function use_array_cache($v = true)
  {
    $this->use_array_cache = $v;
    return $this;
  }



/**
 * Возвращает значение по ключу, если значение не установлено возвращает $default
 * 
 * @param string $key
 * @param  $default
 * @return mixed
 */
 public function get($key, $default = null) {
    $res = $default;
    try {
      if (!$this->has($key)) {
        $res = $default;
      }
      else {
        if ($this->use_array_cache && isset($this->values[$key])) {
          return $this->values[$key];
        } else {
          $res = unserialize($this->get_content($key));
        }
      }
    } catch(Exception $e) {
      $res = $default;
    }
    Events::call('cache.get', $key, $default, $res);
    if ($this->use_array_cache) {
      $this->values[$key] = $res;
    }
    return $res;
  }

/**
 * Устанавливает значение по ключу с заданным таймаутом или с таймаутом по умолчанию
 * 
 * @param string $key
 * @param  $value
 * @param int $timeout
 * @return boolean
 */
  public function set($key, $value, $timeout = null) {
    try {
      $timeout = is_null($timeout) ? $this->timeout : $timeout;
      Events::call('cache.set', $key, $value, $timeout);
      $t = $timeout === 0 ? $timeout :
          (time() + Core::if_null($timeout, $this->timeout));
      $f = IO_FS::File($this->path($key));
      $s = $f->open('w+')->text();
      $s->write_line(Core_Strings::format('%10d', $t))->
        write(serialize($value))->
        close();
      $f->set_permission();
      if ($this->use_array_cache) {
        $this->values[$key] = $value; 
        $this->timestamps[$key] = $t;
      }
      return true;
    } catch (Exception $e) {
      return false;
    }
  }

/**
 * Удалят значение из кэша
 * 
 * @param string $key
 * @return boolean
 */
  public function delete($key) {
    Events::call('cache.delete',$key);
    $res = IO_FS::rm($this->path($key));
    if ($this->use_array_cache) {
      if (isset($this->values[$key])) {
        unset($this->values[$key]);
      }
      if (isset($this->timestamps[$key])) {
        unset($this->timestamps[$key]);
      }
    }
    return $res;
  }

/**
 * Проверяет есть ли занчение с ключом $key в кэше
 * 
 * @param string $key
 * @return boolean
 */
  public function has($key) {
    Events::call('cache.has', $key);
    $res = true;
    $timestamp = null;
    if ($this->use_array_cache && isset($this->timestamps[$key])) {
      $timestamp = $this->timestamps[$key];
    } else {
      if (!IO_FS::exists($this->path($key))) {
        $res = false;
      } else {
        $timestamp = $this->get_timestamp($key);
        if ($this->use_array_cache) {
          $this->timestamps[$key] = $timestamp;
        }
      }
    }
    if (!is_null($timestamp) && $timestamp !== 0 && $timestamp <= time()) {
      $this->delete($key);
      $res = false;
    }
    return $res;
  }

/**
 * Инвалидирует кэш
 * 
 */
  public function flush() {
    Events::call('cache.flush');
    Events::call('cache.delete',$s = '*');
    $res = IO_FS::clear_dir($this->path);
    if ($this->use_array_cache) {
      $this->timestamps = array();
      $this->values = array();
    }
    return $res;
  }

/**
 * Возвращает путь к файлу, в котором храниться значение с ключом $key
 * 
 * @param string $key
 * @return string
 */
  public function path($key) {
    $key = str_replace(DIRECTORY_SEPARATOR, '_', $key);
    $key = str_replace('.', '_', $key);
    if (Core_Strings::contains($key, ':') && preg_match('{(.*)\:([^:]+)$}', $key, $m)) {
      $dir = $this->path . DIRECTORY_SEPARATOR . trim(str_replace(':', DIRECTORY_SEPARATOR, $m[1]), DIRECTORY_SEPARATOR . ' ');
      if (!IO_FS::exists($dir)) IO_FS::mkdir($dir, null, true);
      $key = $m[2];
    } else {
      $dir = $this->path;
    }
  return $dir . DIRECTORY_SEPARATOR . $key;
  }

/**
 * Возвращает timestamp записанный в фаил, содержащий значение с ключом $key
 * 
 * @param string $key
 * @return int
 */
  protected function get_timestamp($key) {
    return (int) IO_FS::File($this->path($key))->load(false, null, 0, 10);
  }

/**
 * Извлекает из файла значение по ключу $key
 * 
 * @param string $key
 * @return string
 */
  protected function get_content($key) {
    return IO_FS::File($this->path($key))->load(false, null, 11);
  }

}

