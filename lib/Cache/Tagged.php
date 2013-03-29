<?php
/// <module name="Cache.Tagged" maintainer="svistunov@techart.ru" version="0.1.0">
Core::load('Cache');

/// <class name="Cache.Tagged" stereotype="module">
///   <implements interface="Core.ModuleInterface" />
class Cache_Tagged implements Core_ModuleInterface {
///   <constants>
  const VERSION = '0.1.0';
///   </constants>

  protected static $options = array(
    'tag_prefix' => '_tags:',
    'tag_key' => '_t',
    'data_key' => '_d'
  );

///   <protocol name="configuring">

///   <method name="options" returns="mixed" scope="class">
///     <args>
///       <arg name="options" type="array" default="array()" />
///     </args>
///     <body>
  static public function options(array $options = array()) {
    if (count($options)) Core_Arrays::update(self::$options, $options);
    return self::$options;
  }
///     </body>
///   </method>

///   <method name="option" returns="mixed">
///     <args>
///       <arg name="name" type="string" />
///       <arg name="value" default="null" />;
///     </args>
///     <body>
  static public function option($name, $value = null) {
    $prev = isset(self::$options[$name]) ? self::$options[$name] : null;
    if ($value !== null) self::options(array($name => $value));
    return $prev;
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="building">

///   <method name="Client" returns="Cache.Tagged.Client" steretype="static">
///     <args>
///       <arg name="backend" type="string" />
///     </args>
///     <body>
  static public function Client($backend) {
    return new Cache_Tagged_Client($backend);
  }
///     </body>
///   </method>

///   </protocol>
}
/// </class>

/// <class name="Cache.Tagged.Client">
///   <implements interface="Core.CallInterface" />
class Cache_Tagged_Client implements Core_CallInterface {
  protected $backend;
///   <protocol name="creating">

///   <method name="__construct">
///     <args>
///       <arg name="backend" type="mixed" />
///     </args>
///     <body>
  public function __construct($backend) {
    $this->backend = $backend instanceof Cache_Backend ? $backend :
      Cache::connect((string) $backend);
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="processing">


///   <method name="get" returns="mixed">
///     <brief>Возвращает значение по ключу, если значение не установлено возвращает $default</brief>
///     <args>
///       <arg name="key" type="string" brief="ключ" />
///       <arg name="default" default="null" brief="значение по умолчанию" />
///     </args>
///     <body>
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
///     </body>
///   </method>

///   <method name="set" returns="boolean">
///     <args>
///       <arg name="key" type="string" brief="ключ" />
///       <arg name="value" brief="значение" />
///       <arg name="timeout" type="int" brief="время в течении которого хранится значение в кэше (сек)" />
///       <arg name="rags" type="mixed" default="array()" />
///     </args>
///     <body>
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
///     </body>
///   </method>

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

///   <method name="tag_exists">
///     <args>
///       <arg name="t" type="string" />
///     </args>
///     <body>
  public function tag_exists($t) {
    return $this->backend->has(Cache_Tagged::option('tag_prefix').$t);
  }
///     </body>
///   </method>

///   <method name="get_tags">
///     <args>
///       <arg name="key" type="" />
///     </args>
///     <body>
  public function get_tags($key) {
    if (is_array($data = $this->backend->get($key)) && isset($data[Cache_Tagged::option('tag_key')]))
      return $data[Cache_Tagged::option('tag_key')];
    return array();
  }
///     </body>
///   </method>

///   <method name="delete_tags" returns="boolean">
///     <body>
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
///     </body>
///   </method>

///   <method name="has" returns="boolean">
///     <brief>Проверяет есть ли занчение с ключом $key в кэше</brief>
///     <args>
///       <arg name="key" type="string" brief="ключ" />
///     </args>
///     <body>
  public function has($key) {
    return (boolean) $this->get($key);
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="calling">

///   <method name="__call">
///     <args>
///       <arg name="method" type="string" />
///       <arg name="args" type="array" />
///     </args>
///     <body>
  public function __call($method, $args) {
    return call_user_func_array(array($this->backend, $method), $args);
  }
///     </body>
////  </method>

///   </protocol>

}
/// </class>

/// </module>
