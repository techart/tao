<?php
/// <module name="Cache.Backend.APC" version="0.2.0" maintainer="omelkovitch@techart.ru">
///   <brief>APC (Alternative PHP Cache) кэширование</brief>
///   <class name="Cache.Backend.APC" stereotype="module">
///   <implements interface="Core.ModuleInterface" />
///   <depends supplier="Cache.Backend.APC.Backend" stereotype="creates" />
class Cache_Backend_APC implements Core_ModuleInterface {

///   <constants>
  const VERSION = '0.2.0';
  const DEFAULT_TIMEOUT = 60;
///   </constants>

///   <protocol name="creating">

///   <method name="initialize" scope="class">
///     <brief>Инициализация модуля</brief>
///     <details>Проверяет включено ли разширение apc</details>
///     <body>
  static public function initialize() {
    if (!extension_loaded('apc'))
      throw new Cache_Exception('No apc php-module');
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="processing">

///   <method name="cache_info" scope="class" returns="array">
///     <brief>Информация о кэше</brief>
///     <details>Возвращает информацию о кэшированных данных и мета-данные из хранилища APC или false при ошибке</details>
///     <args>
///       <arg name="cache_type" type="string" brief="Тип возвращаемой информации. 'user' - информация о пользоватеоьском кэше; 'filehits' - информация о используемых файлах. При неустановленном или ошибочном параметре возвращается информация о системном кэше (кэшированных файлах)." />
///       <arg name="limited" type="bool" brief="Ограничение возвращаемой информации. Если установлено в true - из возвращаемой информации будет удален индивидуальный список кэшированных элементов." />
///     </args>
///     <body>
  static public function cache_info($cache_type = '', $limited= false) {
    return apc_cache_info($cache_type, $limited);
  }
///     </body>
///   </method>

///   <method name="cache_info" scope="class" returns="bool">
///     <brief>Очищает пользовательский/системный кэш</brief>
///     <args>
///       <arg name="cache_type" type="string" brief="Тип очищаемого кэша. 'user' - пользоватеоьский кэш будет очищен; При неустановленном или ошибочном параметре будет очищен системный кэш (кэшированные файлы)." />
///     </args>
///     <body>
  static public function clear_cache($cache_type = null) { return apc_clear_cache($cache_type); }
///     </body>
///   </method>

///   <method name="compile_file" scope="class" returns="bool">
///     <brief>Сохраняет файл в двоичном кэше минуя все фильтры</brief>
///     <args>
///       <arg name="file_name" type="string" brief="Полный или относительный путь к php-файлу, который должен быть скомпилирован и сохранен в двоичкном кэше" />
///     </args>
///     <body>
  static public function compile_file($file_name) { return apc_compile_file($file_name); }
///     </body>
///   </method>

///   <method name="compile_dir" scope="class" returns="bool">
///     <brief>Сохраняет каталог в двоичном кэше</brief>
///     <args>
///       <arg name="dir_name" type="string" brief="Полный или относительный путь к каталогу, который должен быть скомпилирован и сохранен в двоичкном кэше" />
///       <arg name="recursively" type="bool" brief="Учитывать подкаталоги" />
///     </args>
///     <body>
  static public function compile_dir($dir_name, $recursively = true) {
    $compiled   = true;

    if ($recursively)
      foreach (glob($dir_name.DIRECTORY_SEPARATOR.'*', GLOB_ONLYDIR) as $dir)
        $compiled   = $compiled && self::compile_dir($dir, $recursively);

    foreach (glob($dir_name.DIRECTORY_SEPARATOR.'*.php') as $file)
      $compiled   = $compiled && apc_compile_file($file);

    return  $compiled;
  }
///     </body>
///   </method>

///   <method name="define_constants" scope="class" returns="array">
///     <brief>Задает набор констант</brief>
///     <details>Возвращает информацию о кэшированных данных и мета-данные из хранилища APC или false при ошибке</details>
///     <args>
///       <arg name="key" type="string" brief="Имя набора констант. По этому имени они потом могут быть загружены из кэша с помошью функции load_constants" />
///       <arg name="constants" type="array" brief="Ассоциативный массив констант вида constant_name => value. constant_name должно быть допустимым именем для константы, а value - допустимым значением для константы." />
///       <arg name="case_sensitive" type="bool" brief="Чувствительность к регистру" />
///     </args>
///     <body>
  static public function define_constants($key, array $constants, $case_sensitive = true) {
    return apc_define_constants($key, $constants, $case_sensitive);
  }
///     </body>
///   </method>

///   <method name="load_constants" scope="class" returns="array">
///     <brief>Загружает набор констант из кэша</brief>
///     <details>Возвращает информацию о кэшированных данных и мета-данные из хранилища APC или false при ошибке</details>
///     <args>
///       <arg name="key" type="string" brief="Имя набора констант" />
///       <arg name="case_sensitive" type="bool" brief="Чувствительность к регистру" />
///     </args>
///     <body>
  static public function load_constants($key, $case_sensitive = true) {
    return apc_load_constants($key, $case_sensitive);
  }
///     </body>
///   </method>

///   <method name="sma_info" scope="class" returns="array">
///     <brief>Информация о кэше</brief>
///     <details>Возвращает информацию о занимаемой кэшем памяти или false при ошибке</details>
///     <args>
///       <arg name="limited" type="bool" brief="Ограничение возвращаемой информации. Если установлено в true - из возвращаемой информации будет удалена детальная информация о каждом сегменте." />
///     </args>
///     <body>
  static public function sma_info($limited= false) { return apc_sma_info($limited); }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="building">

///   <method name="Backend" returns="Cache.Backend.APC.Backend">
///     <brief>Фабричный метод, возвращает объект класса Cache.Backend.APC.Backend</brief>
///     <args>
///       <arg name="dsn" type="string" brief="строка подключения, должна содержать 'apc://'" />
///       <arg name="timeout" type="int" default="Cache_Backend_APC::DEFAULT_TIMEOUT" brief="время в течении которого хранится значение в кэше (сек)" />
///     </args>
///     <body>
  public function Backend($dsn, $timeout = Cache_APC::DEFAULT_TIMEOUT) {
    return new Cache_Backend_APC_Backend($dsn, $timeout);
  }
///     </body>
///   </method>

///   </protocol>
}
/// </class>

/// <class name="Cache.Backend.APC.Backend" extends="Cache.Backend">
///   <brief>Класс реализующий APC кэширование</brief>
class Cache_Backend_APC_Backend extends Cache_Backend {

///   <protocol name="creating">

///   <method name="__construct">
///     <brief>Конструктор</brief>
///     <args>
///       <arg name="dsn" type="string" brief="строка подключения" />
///       <arg name="timeout" type="int" default="Cache_Backend_APC::DEFAULT_TIMEOUT" brief="время в течении которого хранится значение в кэше (сек)" />
///     </args>
///     <body>
  public function __construct($dsn, $timeout = Cache_Backend_APC::DEFAULT_TIMEOUT) {
    if (!Core_Regexps::match('{^apc://.*}', $dsn)) throw new Cache_BadDSNException($dsn);
    $this->timeout = $timeout;
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
   $v = apc_fetch($key);
   $res = $v === false ? $default : unserialize($v);
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
    return apc_store($key, serialize($value), $timeout);
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
    return apc_delete($key);
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
    Events::call('cache.has', $key);
    return (boolean) apc_fetch($key);
  }
///     </body>
///   </method>

///   <method name="flush">
///     <brief>Инвалидирует кэш</brief>
///     <body>
  public function flush() {
    Events::call('cache.flush');
    Events::call('cache.delete', $s = '*');
    $rc = apc_clear_cache('user');
    $rc = apc_clear_cache('opcode') && $rc;
    return $rc;
  }
///     </body>
///   </method>

///   </protocol>
}
/// </class>

/// </module>
