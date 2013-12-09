<?php
/**
 * Cache.Tagged
 * 
 * @package Cache\Tagged
 * @version 0.1.0
 */
Core::load('Cache');

/**
 * @package Cache\Tagged
 */
class Cache_Tagged implements Core_ModuleInterface {
  const VERSION = '0.1.0';

  protected static $options = array(
    'tag_prefix' => '_tags:',
    'tag_key' => '_t',
    'data_key' => '_d'
  );


/**
 * @param array $options
 * @return mixed
 */
  static public function options(array $options = array()) {
    if (count($options)) Core_Arrays::update(self::$options, $options);
    return self::$options;
  }

/**
 * @param string $name
 * @param  $value
 * @return mixed
 */
  static public function option($name, $value = null) {
    $prev = isset(self::$options[$name]) ? self::$options[$name] : null;
    if ($value !== null) self::options(array($name => $value));
    return $prev;
  }



/**
 * @param string $backend
 * @return Cache_Tagged_Client
 */
  static public function Client($backend) {
    return new Cache_Tagged_Client($backend);
  }

}

/**
 * @package Cache\Tagged
 */
class Cache_Tagged_Client implements Core_CallInterface {
  protected $backend;

/**
 * @param mixed $backend
 */
  public function __construct($backend) {
    $this->backend = $backend instanceof Cache_Backend ? $backend :
      Cache::connect((string) $backend);
  }




/**
 * Возвращает значение по ключу, если значение не установлено возвращает $default
 * 
 * @param string $key
 * @param  $default
 * @return mixed
 */
  public function get($key, $default = null) {
    $cached = $this->backend->get($key);
    if (is_array($cached) && isset($cached[Cache_Tagged::option('tag_key')]) &&
        isset($cached[Cache_Tagged::option('data_key')])) {
      foreach($cached[Cache_Tagged::option('tag_key')] as $t) {
        if (!$this->tag_exists($t)){
          $this->backend->delete($key);
          return !is_null($default) ? $default : null;
        }
      }
      return is_null($cached[Cache_Tagged::option('data_key')]) ? $default :
        $cached[Cache_Tagged::option('data_key')];
    }
    return $cached;
  }

/**
 * @param string $key
 * @param  $value
 * @param int $timeout
 * @param mixed $rags
 * @return boolean
 */
  public function set($key, $value, $timeout = null, $tags = array()) {
    $tags = array_unique(array_filter(array_merge($tags, $this->tags_from($key))));
    if (count($tags) == 0) return $this->backend->set($key, $value, $timeout);
    $res = true;
    foreach($tags as $t) $res = $res && $this->update_tag($key, $t);
    return $res && $this->backend->set($key, array(
      Cache_Tagged::option('tag_key') => $tags,
      Cache_Tagged::option('data_key') => $value)
    );
  }

  public function delete($key) {
    if ($this->tag_exists($key)) return $this->delete_tags($key);
    else return $this->backend->delete($key);
  }

  protected function tags_from($key) {
    if (preg_match('{(.*)\:([^:]+)$}', $key, $m))
      return array_merge(array($m[1]), $this->tags_from($m[1]));
    return array();
  }
  
  protected function update_tag($key, $tag) {
    $tag_data = $this->backend->get(Cache_Tagged::option('tag_prefix').$tag);
    $tag_data = array_merge($tag_data ? $tag_data : array(), array($key));
    return $this->backend->set(Cache_Tagged::option('tag_prefix').$tag, $tag_data, 0);
  }

/**
 * @param string $t
 */
  public function tag_exists($t) {
    return $this->backend->has(Cache_Tagged::option('tag_prefix').$t);
  }

/**
 * @param  $key
 */
  public function get_tags($key) {
    if (is_array($data = $this->backend->get($key)) && isset($data[Cache_Tagged::option('tag_key')]))
      return $data[Cache_Tagged::option('tag_key')];
    return array();
  }

/**
 * @return boolean
 */
  public function delete_tags(){
    $args = func_get_args();
    $tags = Core::normalize_args($args);
    $res = true;
    foreach($tags as $t) {
      $tag_key = Cache_Tagged::option('tag_prefix').$t;
      $keys = $this->backend->get($tag_key);
      if (is_array($keys))
        foreach ($keys as $key)
          $res && $this->backend->delete($key);
      $res = $res && $this->backend->delete($tag_key);
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
    return (boolean) $this->get($key);
  }



/**
 * @param string $method
 * @param array $args
 */
  public function __call($method, $args) {
    return call_user_func_array(array($this->backend, $method), $args);
  }


}

