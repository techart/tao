<?php
/// <module name="Core" version="0.2.13" maintainer="timokhin@techart.ru">
/// <brief>Загрузчик модулей и вспомогательные утилиты.</brief>
/// <details>
///   <p>Модуль Core реализует стандартный механизм динамической подгрузки  остальных модулей
///      библиотеки, который позволяет разделить имя модуля и физический путь к файлы, содержащему
///      его код, а также исключить повторную загрузку модуля.</p>
///   <h3>Файлы модулей библиотеки</h3>
///   <p>Модуль библиотеки представляет собой набор именованных особым  образом классов,
///      функционально связанных между собой. Физически модуль представляет собой один php-файл --
///      файл модуля.  Этот подход отличается от подхода, принятого во многих  PHP-фреймворках,
///      где принято соглашение один класс -- один файл.</p>
///   <p>Каждый модуль имеет свое уникальное имя, состоящее из отдельных  частей, отражающих
///      положение модулей в общей иерархии, при этом в качестве разделителя используется
///      точка.</p>
///   <p>Со стороны файловой системы иерархия имен отражается в виде набора вложенных каталогов.
///      Например, модулю DB соответствует файл lib/DB.php, модулю DB.ORM -- файл lib/DB/ORM.php,
///      и так далее.</p>
///   <p>Отметим, что для разных префиксов имен модулей могут быть указаны различные каталоги
///      файловой системы.</p>
///   <h3>Структура модуля</h3>
///   <p>Модули решают проблему пространств имен классов. До версии 5.3 в PHP не было пространств
///      имен, да и реализация в 5.3 не отличается большим изяществом. В настоящее время фреймворк
///      не использует пространства имен в реализации 5.3, но будет их использовать после
///      того, как эта версия получит достаточное распространение. Сейчас задача решается
///      традиционным для PHP способом -- с помощью префиксов имен классов.</p>
///   <p>Имена всех классов, входящих в модуль, используют в качестве префикса имя модуля, при
///      этом логический разделитель "точка" заменяется на подчеркивание.</p>
///   <p>Каждый модуль должен включать также собственно класс модуля, имя этого класса должно
///      совпадать с именем модуля с учетом замены разделителей. Класс модуля может содержать
///      только статические методы, его экземпляр никогда не создается. Кроме того, класс должен
///      удовлетворять следуюшим требованиям:</p>
///   <ul>
///     <li>имплементировать интерфейс Core.ModuleInterface;</li>
///     <li>определять строковую константу VERSION, содержащую версию модуля.</li>
///   </ul>
///   <p>Помимо этого, класс модуля может содержать набор фабричных методов для создания
///      экземпляров классов, предназначенных для использования снаружи модуля, а также
///      различные вспомогательные методы. Следуя практике, распространенной в модулях языка Perl,
///      класс модуля может реализовывать упрощенный процедурный фасад поверх объектного
///      интерфейса модуля.</p>
///   <p>Модуль также может реализовывать статический метод initialize(). Если такой метод
///      присутствует в классе модуля, он будет вызван после его загрузки.</p>
///   <p>Таким образом, модуль имеет следующую структуру:</p>
///   <code><![CDATA[
/// class Sample implements Core_ModuleInterface {
///   const VERSION = '0.1.0';
///
///   static public function PublicClass1() { return new Sample_PublicClass1(); }
/// }
///
/// class Sample_PublicClass1 { /*...*/ }
///
/// class Sample_PrivateClass1 { /*...*/ }
/// ]]></code>
///     <h3>Модули приложения</h3>
///     <p>Часто возникает необходимость объединения модулей под одной версией. Например, если
///        пользовательское приложение состоит их нескольких модулей, как правило, нет необходимости
///        ведения номеров версий для каждой части приложения.</p>
///     <p>В этом случае, для того, чтобы удовлетворить требования, предъявляемые к классу модуля,
///        рекомендуется реализовать собственный интерфейс, унаследованный от Core_ModuleInterface,
///        и определить константу VERSION в нем. При этом классы модуля приложений должны
///        имплементировать этот интерфейс.</p>
///     <p>Например:</p>
///     <code><![CDATA[
/// interface Sample_ModuleInterface extends Core_ModuleInterface {
///   const VERSION = '0.1.0';
///  }
///
///  class Sample_Module1 implements Sample_ModuleInterface { /* ... */ }
///  class Sample_Module2 implements Sample_ModuleInterface { /* ... */ }
///  ]]></code>
///     <h3>Загрузка модуля</h3>
///     <p>Модуль Core является единственным модулем, который необходимо загрузить с помощью
///        вызова include(). После загрузки Core необходимо вызвать статический метод
///        Core::initialize(), который создаст экземпляр загрузчика. Необходимость в явном вызове
///        этого метода объясняется возможностью передачи в него параметров конфигурации
///        загрузчика.</p>
///     <p>После вызова Core::initialize() любой модуль фреймворка может быть загружен с помощью
///        вызова Core::load(). Например:</p>
///     <code><![CDATA[
/// include('lib/Core.php');
/// Core::initialize();
/// Core::load('DB.ORM', 'Mail.Message', 'CLI');
/// ]]></code>
///   <h3>Конфигурирование модулей</h3>
///   <p>Модули могут быть параметризованы набором опций. Для указания значений опций
///      необходимо использовать вызов Core::configure($module, $config). Вызов необходимо
///      выполнить перед загрузкой модуля. Поскольку модули могут подгружать друг друга,
///      лучше всего делать это после вызова Core::initialize(). Массив опций передается в
///      качестве параметра методу initialize() при загрузке модуля.</p>
///   <p>Пример конфигурирования модуля:</p>
///   <code><![CDATA[
/// Core::configure('DB.ORM.Assets', array('root_path' => 'my/path'));
/// Core::load('File.Assets');
/// ]]></code>
///   <h3>Вспомогательные классы</h3>
///   <p>Помимо загрузчика модулей ядро содержит набор классов, содержащих статические методы,
///      дублирующие многие встроенные функции PHP. Некоторые такие методы делают использование
///     встроенных функций более удобным, другие -- позволяют сделать код более наглядным.</p>
///   <p>Первоначально идея была в группировке наиболее употребимых функций в классы-утилиты и
///      использование только этих функций в библиотечном коде. Таким образом, можно было бы
///      гарантировать отсутствие отрицательных эффектов от смены поведения встроенных функций при
///      смене версий языка и создать иллюзию присутствия упорядоченного встроенного API.</p>
///   <p>На практике, к сожалению, использование дополнительного вызова не всегда желательно из
///      соображений производительности. Поэтому в критичных участках кода приходится использовать
///      встроенные функции. Тем не менее, в клиентских приложениях разумное использование этих
///      может сделать код более эстетичным :)</p>
///   <h3>Стандартные интерфейсы</h3>
///   <p>Помимо вспомогательных классов, модуль Core определяет набор стандартных интерфейсов,
///      определяющих протоколы доступа к свойствам объектов, индексированного доступа и т.д.
///      Явная имплементация этих интерфейсов в пользовательских классах позволяет не забыть о
///      необходимости реализации тех или иных методов.</p>
/// </details>

/// <class name="Core" stereotype="module">
///   <brief>Модуль Core</brief>
///   <details>
///     <p>Core -- единственный модуль, который необходимо загружать с помощью вызова include(). Все
///        остальные модули необходимо подгружать с помощью Core::load().</p>
///   </details>
///   <implements interface="Core.ModuleInterface" />
class Core implements Core_ModuleInterface {

///   <constants>
  const MODULE        = 'Core';
  const VERSION       = '0.3.0';
  const RELEASE       =  20000;
  const PATH_VARIABLE = 'TAO_PATH';
///   </constants>

  static protected $loader = null;
  static protected $autoload = true;
  static protected $replace_classes = array();
  static protected $options = array('files_name' => 'files', 'spl_autoload' => false, 'spl_aggressive_autoload' => false, 
          'modules_cache' => false, 'modules_cache_path' => '../modules.cache.php');
  static protected $spl_autoload_registered = false;
  static protected $start_time = 0;
  static protected $cached_modules = array();
  static protected $flush_modules_cahce = false;
  protected static $default_deprecated_dir = '../tao/deprecated/lib/';
  protected static $base_dir = null;
  protected static $save_dir = null;

///   <protocol name="creating">

///   <method name="initialize" scope="class">
///     <brief>Выполняет инициализацию модуля.</brief>
///     <args>
///       <arg name="config" type="array" default="array()" brief="массив настроек" />
///     </args>
///     <details>
///       <p>Изначально планировалось, что в массиве настроек будут поддерживаться различные опции
///          ядра, однако пока поддерживается единственный элемент loader, который в свою очередь
///          содержит список путей к модулям в формате "префикс имени модуля" => "путь к файлам
///          иерархии модулей".</p>
///       <p>Например, если мы хотим, чтобы модули, начинающиеся с App. грузились из каталога
///          app/lib, модули, начинающиеся с Test. -- из каталога test, а все остальное -- из
///          каталога lib, можно использовать следующую конструкцию:</p>
///       <code><![CDATA[
/// include('lib/Core.php');
/// Core::initialize(array('loader' => array(
///   'App' => 'app/lib', 'Test' => 'test', '*' => 'lib')));
/// ]]></code>
///       <p>Порядок следования важен, более ранние пути имеют приоритет, соответственно, путь по
///        умолчанию (*) необходимо писать последним.</p>
///        <p>Существует также возможность указывать набор путей поиска модулей с помощью переменной
///         окружения TAO_PATH. Для приведенного выше примера необходимо выставить следующее
///         значение этой переменной:</p>
///        <code><![CDATA[
/// export TAO_PATH='App:app/lib;Test:test;*:lib'
/// ]]></code>
///        <p>Для использования этих настроек достаточно вызвать Core::initialize() без параметров.
///           Кроме того, эти способы настройки можно использовать совместно.</p>
///     </details>
///     <body>
  static public function initialize(array $config = array()) {
    self::$base_dir = getcwd();
    self::$start_time = microtime();
    $loader_opts = self::parse_environment_paths();
    if (isset($config['loader']))
      $loader_opts = Core_Arrays::merge($loader_opts, $config['loader']);
    self::$loader = new Core_ModuleLoader();
    self::$loader->paths($loader_opts);
    self::options($config);
    self::init_autoload();
    self::init_module_cache();
    self::init_deprecated();
  }
///     </body>
///   </method>

  protected static function init_autoload() {
    if (self::option('spl_autoload')) {
      self::register_spl_autoload();
    }
  }

  protected static function init_module_cache() {
    if (Core::option('modules_cache')) {
      Core::load('Events');
      $modules = array();
      if (is_file(self::option('modules_cache_path'))) {
        self::$cached_modules = include(self::option('modules_cache_path'));
      } else {
        self::$cached_modules = Core::find_all_modules();
        self::$flush_modules_cahce = true;
      }
      Events::add_listener('ws.response', array('Core', 'flush_modules_cache'));
    }
  }

  protected static function init_deprecated() {
    if (self::option('deprecated')) {
      $dir = self::option('deprecated_dir');
      if(empty($dir)) {
        $dir = self::$default_deprecated_dir;
      }
      spl_autoload_register(function($class) use ($dir) {
        $file = str_replace('_', DIRECTORY_SEPARATOR, $class) . '.php';
        $file = $dir . $file;
        if (is_file($file)) require $file;
      });
    }
  }

  protected static function push_dir()
  {
    self::$save_dir = getcwd();
    if (self::$base_dir) {
      chdir(self::$base_dir);
    }
  }

  protected static function pop_dir()
  {
    chdir(self::$save_dir);
  }

  static public function start_time() {
    return self::$start_time;
  }

  static public function is_flush_modules_cache() {
    return self::$flush_modules_cahce;
  }

  static public function options($values = array()) {
    if (empty($values))
      return self::$options;
    foreach($values as $k => $v)
      self::option($k, $v);
    return self::$options;
  }
  
  static public function option($name, $value = null) {
    if (is_null($value)) {
      return isset(self::$options[$name]) ? self::$options[$name] : null;
    } else {
      return self::$options[$name] = $value;
    }
  }

  static public function register_spl_autoload() {
    if (!self::$spl_autoload_registered)
      spl_autoload_register('Core::spl_autoload');
    Core::option('spl_autoload', true);
    self::$spl_autoload_registered = true;
  }

  static public function spl_autoload($class) {
    $virtual = str_replace('_', '.', $class);
    if (!Core::loader()->load($virtual)) {
      $module = Core_Types::module_name_for($class);
      Core::loader()->load($module);
    }
  }

  static public function cached_modules($module = null, $path = null) {
    if (is_null($module)) return self::$cached_modules;
    if (is_null($path) && isset($cached_modules[$module])) return self::$cached_modules[$module];
    self::$flush_modules_cahce = true;
    return self::$cached_modules[$module] = $path;
  }

  static public function flush_modules_cache() {
    if (self::is_flush_modules_cache()) {
      $cache = "<?php return " . var_export((array) self::$cached_modules, true) . ";";
      file_put_contents(self::option('modules_cache_path'), $cache);
    }
  }

  static public function find_all_modules() {
    $dir = __DIR__;
    Core::load('IO.FS');
    $q = IO_FS::Query()->glob('*.php')->recursive(true);
    $result = array();
    foreach ($q->apply_to(IO_FS::Dir($dir)) as $name => $file) {
      $path_to_file = trim(str_replace($dir, '', $name), '/ ');
      $module = str_replace('.php', '', $path_to_file);
      $module = str_replace('/', '.', $module);
      $result[$module] = $path_to_file;
    }
    Core::load('Events');
    Events::call('core.find_all_modules', $result);
    return $result;
  }

///   </protocol>

///   <protocol name="accessing">

///   <method name="loader" returns="Core.ModuleLoader" scope="class">
///     <brief>Возвращает экземпляр загрузчика модулей</brief>
///     <body>
  static public function loader($instance = null) {
    if ($instance instanceof Core_ModuleLoaderInterface)
      self::$loader = self::$loader->merge($instance);
    return self::$loader;
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="loading">

///   <method name="load" scope="class" varargs="true">
///     <brief>Выполняет загрузку модулей</brief>
///     <details>
///       <p>Имена модулей передаются в качестве параметров метода.</p>
///     </details>
///     <body>
  static public function load() {
    self::push_dir();
    if (Core::option('spl_aggressive_autoload')) return;
    foreach (func_get_args() as $module) self::$loader->load($module);
    self::pop_dir();
  }
///     </body>
///   </method>

///   <method name="configure" scope="class">
///     <brief>Выполняет конфигурирование модуля</brief>
///     <args>
///       <arg name="module" brief="имя модуля" />
///       <arg name="config" type="array" default="array()" brief="массив опций модуля" />
///     </args>
///     <details>
///       <p>Массив параметров передается в статический метод initialize() класса модуля.</p>
///     </details>
///     <body>
  static public function configure($module, array $config = array()) {
    foreach ((Core_Types::is_array($module) ? $module : array($module => $config)) as $k => $v)
      self::$loader->configure($k, $v);
  }
///     </body>
///   </method>

///   <method name="is_loaded" scope="class" returns="boolean">
///     <brief>Проверяет, был ли уже загружен модуль</brief>
///     <args>
///       <arg name="module" type="string" brief="имя модуля" />
///     </args>
///     <body>
  static public function is_loaded($module) { return self::$loader->is_loaded($module); }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="supporting">

///   <method name="equals" returns="boolean">
///     <brief>Сравнивает две переменные произвольного типа</brief>
///     <args>
///       <arg name="a" />
///       <arg name="b" />
///     </args>
///     <body>
  static public function equals($a, $b) {
    if ($a instanceof Core_EqualityInterface) return $a->equals($b);
    if ($b instanceof Core_EqualityInterface) return $b->equals($a);

    if ((($a instanceof stdClass) && ($b instanceof stdClass))) {
      $a = (array) clone $a;
      $b = (array) clone $b;
    }

    if ((is_array($a) && is_array($b)) ||
        (($a instanceof ArrayObject) && ($b instanceof ArrayObject))) {

      if (count($a) != count($b)) return false;

      foreach ($a as $k => $v)
        if ((isset($a[$k]) && !isset($b[$k])) || !Core::equals($v, $b[$k])) return false;
      return true;
    }

    return ($a === $b);
  }
///     </body>
///   </method>

///   <method name="object" returns="stdClass">
///     <brief>Создает объект класса stdClass</brief>
///     <args>
///       <arg name="values" brief="итерируемый объект значений для инициализации объекта" />
///     </args>
///     <details>
///       <p>Метод предназначен создания объекта класса stdClass с установленным набором
///          свойств.</p>
///       <p>Вызов:</p>
///       <code><![CDATA[
/// return Core::object(array('str' => 'Test', 'num' => 1));
/// ]]></code>
///       <p>эквивалентен коду:</p>
///       <code><![CDATA[
/// $a = new stdClass();
/// $a->str = 'Test';
/// $a->num = 1;
/// return $a;
/// ]]></code>
///     <p>Разумеется, никто не запрещает использование второго варианта, особенно в местах, где
///        критична скорость.</p>
///     </details>
///     <body>
  static public function object($values = array()) {
    $r = new stdClass();
    foreach ($values as $k => $v) $r->$k = $v;
    return $r;
  }
///     </body>
///   </method>

///   <method name="hash" returns="ArrayObject" scope="class">
///     <brief>Создает объект класса ArrayObject</brief>
///     <args>
///       <arg name="values" brief="значения инициализации объекта" default="array()" />
///     </args>
///     <details>
///       <p>Метод является парным к Core::object(), логичнее было бы назвать его Core::array(),
///          но array -- зарезервированное слово.</p>
///       <p>Если параметр $values не является массивом, он приводится к массиву.</p>
///     </details>
///     <body>
  static public function hash($values = array()) { return new ArrayObject((array) $values); }
///     </body>
///   </method>

///   <method name="call" returns="Core.Call" scope="class">
///     <args>
///       <arg name="target" />
///       <arg name="method" type="string" />
///     </args>
///     <body>
  static public function call($target, $method) {
    $args = func_get_args();
    return new Core_Call(array_shift($args), array_shift($args), $args);
  }
///     </body>
///   </method>

///   <method name="invoke" >
///     <args>
///       <arg name="call" type="mixen" />
///     </args>
///     <body>
  static public function invoke($call, array $parms = array()) {
    if (is_string($call) && strpos($call, '::') !== FALSE) {
      $parts = explode('::', $call);
      $virtual = Core_Types::virtual_class_name_for($parts[0]);
      $real = Core_Types::real_class_name_for($parts[0]);
      Core::autoload($virtual, $real);
      $call = array($real, $parts[1]);
    }
    if ($call instanceof Core_InvokeInterface) return $call->invoke($parms);
    return call_user_func_array($call, $parms);
  }
///     </body>
///   </method>

  static public function cached_invoke($target, $method, array $parms = array(), $timeout = 0, $autoload = false) {
    $class = Core_Types::real_class_name_for($target);
    if ($autoload) self::autoload($class);
    if (!method_exists($class, $method)) return false;
    $ref = new ReflectionClass($class);
    $file = $ref->getFileName();
    if (!empty($file)) {
      $key = "cached_invoke:$class";
      $cached_tm  = WS::env()->cache->get($key, 0);
      $tm = filemtime($file);
      if ($cached_tm < $tm) {
        Core::call($target, $method)->invoke($parms);
        WS::env()->cache->set($key, $tm, $timeout);
      }
      return true;
    }
    return false;
  }
  
  static public function clear_invoke_cache() { return WS::env()->cache->delete("cached_invoke"); }
  
  static public function is_cli() {return php_sapi_name() == 'cli' && empty($_SERVER['REMOTE_ADDR']);}

///   <method name="with" returns="Object" scope="class">
///     <brief>Обеспечивает возможность построения цепочки вызовов для переданного объекта.</brief>
///     <args>
///       <arg name="object" brief="объект" />
///     </args>
///     <details>
///       <p>Метод, вообще говоря, ничего не делает -- просто возвращает переданный объект.</p>
///       <p>Однако, появляется возможность писать так:</p>
///       <code><![CDATA[
/// Core::with(new TestObject())->do_test();
/// ]]></code>
///       <p>В данном случае появляется возможность вызова метода созданного объекта без
///          использования дополнительных переменных.</p>
///     </details>
///     <body>
  static public function with($object) { return $object; }
///     </body>
///   </method>

///   <method name="with_clone" returns="Object" scope="class">
///     <brief>Тоже что и with, только возвращает клон объекта</brief>
///     <args>
///       <arg name="object" brief="объект" />
///     </args>
///     <body>
  static public function with_clone($object) { return clone $object; }
///     </body>
///   </method>

///   <method name="with_index" returns="mixed" scope="class">
///     <brief>Возвращает элемент индексируемого объекта по его индексу</brief>
///     <args>
///       <arg name="object" brief="индексируемый объект" />
///       <arg name="index"  brief="значение индекса"  />
///     </args>
///     <details>
///     <p>Метод может быть использован для индексированного доступа к анонимному объекту.</p>
///     <p>Например:</p>
///     <code><![CDATA[
/// Core::with_index($this->options, 'validator')->validate($entity);
/// ]]></code>
///     </details>
///     <body>
  static public function with_index($object, $index) { return $object[$index]; }
///     </body>
///   </method>

///   <method name="with_attr" returns="mixed" scope="class">
///     <brief>Возвращает значение свойства объекта</brief>
///     <args>
///       <arg name="object" brief="объект" />
///       <arg name="attr" type="string" brief="имя свойства" />
///     </args>
///     <details>
///     <p>Метод позволяет получить значение свойства объекта в случае, когда имя свойства
///        необходимо сформировать динамически. Например:</p>
///     <code><![CDATA[
/// if (Core::with_attr($result, $fields[$i]) > 0) ...
/// ]]></code>
///     </details>
///     <body>
  static public function with_attr($object, $attr) { return $object->$attr; }
///     </body>
///   </method>

///   <method name="if_null" returns="mixed" scope="class">
///     <brief>Возвращает альтернативу для null-значения</brief>
///     <args>
///       <arg name="value" brief="значение" />
///       <arg name="alternative" brief="альтернативное значение" />
///     </args>
///     <details>
///       <p>Две приведенные ниже конструкции эквивалентны:</p>
///       <code><![CDATA[
/// $a = Core::if_null($value, $alternative );
/// $a = $value === null ? $alternative : $value;
/// ]]></code>
///       <p>Однако в случае использования метода можно, например, вызвать метод сразу для
///          результата выражения без дополнительной переменной.</p>
///       <code><![CDATA[
/// Core::if_null($object, $alternative)->call($parms);
/// ]]></code>
///     </details>
///     <body>
  static public function if_null($value, $alternative) {
    return $value === null ? $alternative : $value;
  }
///     </body>
///   </method>

///   <method name="if_not" returns="mixed" scope="class">
///     <brief>Возвращает альтернативу для неистинного значения</brief>
///     <args>
///       <arg name="value" brief="значение" />
///       <arg name="alternative" brief="альтернативное значение" />
///     </args>
///     <details>
///       <p>Метод аналогичен Core::if_null, но альтернативное значение возвращается если
///          значение $value не истинно.</p>
///     </details>
///     <body>
  static public function if_not($value, $alternative) {
    return $value ? $value : $alternative;
  }
///     </body>
///   </method>

///   <method name="if_false" returns="mixed" scope="class">
///     <brief>Возвращает альтернативу для ложного значения</brief>
///     <args>
///       <arg name="value" brief="значение" />
///       <arg name="alternative" brief="альтернативное значение" />
///     </args>
///     <details>
///       <p>Метод аналогичен Core::if_null, но альтернативное значение возвращается если значение
///          $valuе тождественно равно false.</p>
///     </details>
///     <body>
  static public function if_false($value, $alternative) {
    return $value === false ? $alternative : $value;
  }
///     </body>
///   </method>

///   <method name="if_not_set" returns="mixed" scope="class">
///     <brief>Возвращает альтернативу отсутствующему индексированному значению</brief>
///     <args>
///       <arg name="values"      brief="массив значение" />
///       <arg name="index"       type="mixed" brief="индекс" />
///       <arg name="alternative" type="mixed" brief="альтернативное значение" />
///     </args>
///     <details>
///       <p>Следующие два выражения эквиваленты:</p>
///       <code><![CDATA[
/// $a = Core::if_not_set($array, $index, $alternative);
/// $a = isset($values[$index]) ? $values[$index] : $alternative;
/// ]]></code>
///     <p>Использование метода удобно в случае, если для полученного значения необходимо сразу
///        вызвать метод или обратиться к свойству.</p>
///     </details>
///     <body>
  static public function if_not_set($values, $index, $alternative) {
    return isset($values[$index]) ? $values[$index] : $alternative;
  }
///     </body>
///   </method>

//TODO: вынести функционал make amake в отдельный модуль
///   <method name="make" returns="object" scope="class">
///     <brief>Создает объект заданного класса</brief>
///     <args>
///       <arg name="class" type="string" brief="имя класса" />
///     </args>
///     <details>
///       <p>В качестве имена класса можно использовать как настоящее имя класса, так и логическое,
///          в виде иерархии модулей и имени класса, разделенных точками.</p>
///       <p>Все остальные параметры метода передаются в конструктор создаваемого класса.</p>
///     </details>
///     <body>
  static public function make($class) {
    $args = func_get_args();
    return self::amake($class,array_slice($args, 1));
  }
///     </body>
///   </method>

///   <method name="amake" returns="object" scope="class">
///     <brief>Создает объект заданного класса с массивом значений параметров конструктора</brief>
///     <args>
///       <arg name="class" type="string" brief="имя класса" />
///       <arg name="parms" type="array"  brief="массив параметров" />
///     </args>
///     <details>
///       <p>Метод идентичен методу make, за исключением того, что значения параметров конструктора
///          передаются в виде массива, а не в виде параметров метода.</p>
///     </details>
///     <body>
  static public function amake($class, array $parms) {
    self::push_dir();
    $class = self::replace_class($class);
    $real_name = Core_Types::real_class_name_for($class);
    self::autoload($class, $real_name);
    $reflection = Core_Types::reflection_for($real_name);
    self::pop_dir();
    return $reflection->getConstructor() ?
      $reflection->newInstanceArgs($parms):
      $reflection->newInstance();
  }
///     </body>
///   </method>

  static public function replace_class($class) {
    if (!empty(self::$replace_classes[$class]) 
      && Core_Types::is_subclass_of($class, self::$replace_classes[$class])
      )
      return self::$replace_classes[$class];
    else
      return $class;
  }
  
  static public function current_replace_class_for($class) {
    if (empty(self::$replace_classes[$class])) return $class;
    return self::current_replace_class_for(self::$replace_classes[$class]);
  }

  static public function replace_class_map($class, $replace_class) {
    self::$replace_classes[$class] = $replace_class;
  }
  
  static public function replace_class_maps(array $maps) {
    foreach ($maps as $c => $rc) self::replace_class($c, $rc);
  }

  static public function enable_autoload() {
    self::$autoload = true;
  }
  
  static public function disable_autoload() {
    self::$autoload = false;
  }

  static public function autoload($class, $real_name = null) {
    if (!self::$autoload) return;
    $real_name = !is_null($real_name) ? $real_name : Core_Types::real_class_name_for($class);
    if (!class_exists($real_name, false)) {
      $module = Core_Types::module_name_for($class);
      $module_real = Core_Types::real_class_name_for($module);
      if (file_exists(Core::loader()->file_path_for($class, false))) self::$loader->load($class);
      if (!class_exists($module_real, false) && file_exists(Core::loader()->file_path_for($module, false))) self::$loader->load($module);
    }
  }

///   <method name="normalize_args" scope="class" returns="array">
///     <brief>Выполняет нормализацию аргументов</brief>
///     <args>
///       <arg name="args" type="array" brief="массив аргументов" />
///     </args>
///     <details>
///       <p>Иногда возникает необходимость в реализации методов, которые могут принимать аргументы
///          двумя способами: список аргументов произвольной длины или список аргументов в виде
///          массива. Например, мы хотим, чтобы один и тот же метод мог использоваться
///          как разработчиком приложения, которому проще написать список аргументов без
///          дополнительного массива, так и другими модулями библиотеки, в ситуации, когда список
///          формируется динамически и его проще передать в виде уже сформированного массива.</p>
///       <p>Метод предназначен для совместного использования с функций func_get_args(), причем
///          вызов Core::normalize_args(func_get_args()) работает.</p>
///       <p>Результатом выполнения является первый аргумент, если он является массивом с
///          присутствующим аргументом с индексом 0, или, в противном случае, массив аргументов
///          целиком.</p>
///       <p>Метод необходимо применять с осторожностью, так как при таком подходе мы теряем
///          информацию о типах аргументов, и т.д.</p>
///     </details>
///     <body>
  static public function normalize_args(array $args) {
    return (count($args) == 1 && isset($args[0]) && is_array($args[0])) ? $args[0] : $args;
  }
///     </body>
///   </method>

///   <method name="parse_environment_paths" returns="array" access="private" scope="class">
///     <brief>Выполняет разбор переменной окружения TAO_PATH</brief>
///     <details>
///       <p>Метод возвращает массив вида "префикс имени модуля" => "путь к иерархии модулей",
///          пригодный для использования в качестве параметра loader метода Core::initialize().</p>
///     </details>
///     <body>
  static private function parse_environment_paths() {
    $result = array();
    if (($path_var = getenv(self::PATH_VARIABLE)) !== false)
      foreach (Core_Strings::split_by(';', $path_var) as $rule)
        if ($m = Core_Regexps::match_with_results('{^([-A-Za-z0-9*][A-Za-z0-9_.]*):(.+)$}', $rule))
          $result[$m[1]] = $m[2];
    return $result;
  }
///     </body>
///   </method>

///   </protocol>
}
/// </class>


/// <composition>
///   <source class="Core" multiplicity="1" />
///   <target class="Core.ModuleLoader" multiplicity="1" />
/// </composition>

interface Core_InvokeInterface {
  public function invoke($args = array());
}


/// <class name="Core.Call">
class Core_Call implements Core_InvokeInterface {

  private $call;
  private $args;
  private $cache = array();
  private $enable_cache = false;
  private $autoload;

///   <protocol name="creating">

///   <method name="__construct">
///     <args>
///       <arg name="target" />
///       <arg name="method" type="string" />
///       <arg name="args" type="array" />
///     </args>
///     <body>
  public function __construct($target, $method, array $args = array(), $autoload = true) {
    if (is_string($target)) {
      $target = Core_Types::real_class_name_for($target);
    }
    $this->autoload = $autoload;
    $this->call = array($target, (string) $method);
    $this->args = $args;
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="configuration">

///   <method name="update_args">
///     <body>
  public function update_args($args) {
    $this->args = array_merge($this->args, $args);
    return $this;
  }
///     </body>
///   </method>

  public function cache($v = true) {
    $this->enable_cache = $v;
    return $this;
  }

///   </protocol>

///   <protocol name="performing">

///   <method name="invoke" returns="mixed">
///     <body>
  public function invoke($args = array()) {
    $args = $this->get_args($args);
    if ($this->autoload && is_string($this->call[0]))
      Core::autoload(Core_Types::virtual_class_name_for($this->call[0]), $this->call[0]);
    if ($this->enable_cache) {
      $key = serialize($args);
      if (isset($this->cache[$key]))
        return $this->cache[$key];
      return $this->cache[$key] = call_user_func_array($this->call, $args);
    }
    return call_user_func_array($this->call, $args);
  }
///     </body>
///   </method>

  protected function get_args($values = array()) {
    return array_merge($this->args, (array) $values);
  }

///   </protocol>
}
/// </class>


/// <interface name="Core.PropertyAccessInterface">
///   <brief>Интерфейс доступа к свойствам объекта</brief>
///   <details>
///     <p>В PHP поддерживается набор специальных методов, обеспечивающих динамическое
///        переопределение свойств объектов. Помимо реализации динамических свойств, реализация
///        этих методов позволяет контролировать внешний доступ к тем или иным свойствам
///        объекта.</p>
///     <p>Имплементация объектом интерфейса Core.PropertyAccessInterface с одной стороны
///        говорит о наличии у него доступных снаружи свойств, а с другой - гарантирует реализацию
///        всех необходимых методов и, таким образом, непротиворечивость в работе со свойствами
///        (в частности, корректную отработу isset(), unset() и т.д.).</p>
///   </details>
interface Core_PropertyAccessInterface {
///   <protocol name="accessing">

///   <method name="__get" returns="mixed">
///     <brief>Возвращает значение свойства</brief>
///     <args>
///       <arg name="property" type="string" brief="имя свойства" />
///     </args>
///     <body>
  public function __get($property);
///     </body>
///   </method>

///   <method name="__set" returns="mixed">
///     <brief>Устанавливает значение свойства</brief>
///     <args>
///       <arg name="property" type="string" brief="имя свойства" />
///       <arg name="value"                  brief="значение свойства" />
///     </args>
///     <body>
  public function __set($property, $value);
///     </body>
///   </method>

///   <method name="__isset" returns="boolean">
///     <brief>Проверяет установку значения свойства</brief>
///     <args>
///       <arg name="property" type="string" brief="имя свойства" />
///     </args>
///     <body>
  public function __isset($property);
///     </body>
///   </method>

///   <method name="__unset">
///     <brief>Удаляет свойство объекта</brief>
///    <args>
///      <arg name="property" type="string" brief="имя свойства" />
///    </args>
///    <body>
  public function __unset($property);
///     </body>
///   </method>

///   </protocol>
}
/// </interface>


/// <interface name="Core.IndexedAccessInterface" extends="ArrayAccess">
///   <brief>Интерфейс доступа к индексированным свойствам объекта</brief>
///   <details>
///     <p>На данный момент интерфейс наследуется от ArrayAccess и не расширяет его.</p>
///   </details>
interface Core_IndexedAccessInterface extends ArrayAccess {}
/// </interface>


/// <interface name="Core.CountInterface" extends="Countable">
///   <brief>Counting-интерфейс</brief>
///   <details>
///     <p>Пока просто наследуется от Countable без расширения, заведен для единообразия.</p>
///   </details>
interface Core_CountInterface extends Countable {}
/// </interface>

/// <interface name="Core.CallInterface">
///   <brief>Интерфейс, определяющий наличие динамической диспетчеризации методов объекта.</brief>
///   <details>
///     <p>Пока включает в себя стандартный PHP-метод __call, однако может быть расширен, например
///        функцией, определяющей возможность вызова того или иного метода.</p>
///   </details>
interface Core_CallInterface {
///   <protocol name="calling">

///   <method name="__call">
///     <brief>Осуществляет динамическую диспетчеризацию вызовов</brief>
///     <args>
///       <arg name="method" type="string" brief="имя метода" />
///       <arg name="args" type="array" brief="массив аргументов" />
///     </args>
///     <body>
  public function __call($method, $args);
///     </body>
///  </method>

///   </protocol>
}
/// </interface>


/// <interface name="Core.CloneInterface">
///   <brief>Интерфейс клонирования</brief>
///   <details>
///     <p>Пока включает только стандартный метод __clone().</p>
///   </details>
interface Core_CloneInterface {
///   <protocol name="cloning">

///   <method name="__clone">
///     <body>
  public function __clone();
///     </body>
///   </method>

///   </protocol>
}
/// </interface>


/// <interface name="Core.EqualityInterface">
///   <brief>Интерфейс сравнения</brief>
///   <details>
///     <p>В PHP нет стандартного интерфейса для выполнения операции сравнения. Вопрос реализации
///        такого интерфейса -- дело тонкое, но бывают ситуации, когда даже кривая реализация лучше
///        ее отсутствия.</p>
///     <p>Интерфейс декларирует метод equals(), который вызывается при выполнении сравнения с
///        помощью вызова Core::equals().</p>
///   </details>
interface Core_EqualityInterface {
///   <protocol name="quering">

///   <method name="equals" returns="boolean">
///     <args>
///       <arg name="to" brief="значение, с которым необходимо выполнить сравнение" />
///     </args>
///     <body>
  public function equals($to);
///     </body>
///   </method>

///</protocol>
}
/// </interface>


/// <interface name="Core.StringifyInterface">
///   <brief>Интерфейс получения строкового представления объекта</brief>
///   <details>
///     <p>Реализация этого интерфейса определяет возможность приведения объекта к строке.</p>
///     <p>Интерфейс декларирует стандартный метод __toString(), а также его синоним as_string() для
///        выполнения приведения к строке в явном виде. Реализация должна гарантировать полное
///        совпадение результатов вызова этих методов! Таким образом, для объекта, реализуюшего
///        интерфейс, следующие выражения эквивалентны:</p>
///     <code><![CDATA[
/// $str = (string) $object;
/// $str = $object->as_string();
/// ]]></code>
///   </details>
interface Core_StringifyInterface {
///   <protocol name="stringifying">

///   <method name="as_string" returns="string">
///     <brief>Возвращает строковое представление объекта</brief>
///     <body>
  public function as_string();
///     </body>
///   </method>

///   <method name="__toString" returns="string">
///     <brief>Возвращает строковое представление объекта</brief>
///     <body>
  public function __toString();
///     </body>
///   </method>

///   </protocol>
}
/// </interface>


/// <interface name="Core.ModuleInterface">
///   <brief>Интерфейс класса модуля</brief>
///   <details>
///     <p>Каждый модуль должен содержать класс с именем, совпадающим с классом модуля, и
///        имплементирующим этот интерфейс, а также содержащий константу VERSION.</p>
///     <p>Класс также может имплементировать статический метод initialize(), вызываемый
///        загрузчиком после загрузки модуля.</p>
///   </details>
interface Core_ModuleInterface {}
/// </interface>


/// <interface name="Core.ConfigurableModuleInterface" extends="Core.ModuleInterface">
///     <brief>Интерфейс класса конфигурируемого модуля</brief>
///     <details>
///       <p>В случае, если модуль поддерживает конфигурирование, это может быть отражено явно
///          путем реализации этого интерфейса. В этом случае класс модуля реализует дополнительную
///          функциональность:</p>
///       <ol>
///         <li>метод initialize() должен принимать обязательный массив значений опций, по
///             умолчанию пустой;</li>
///         <li>метод options() реализует получение и установку списка опций;</li>
///         <li>метод option() реализует получение и установку единственной опции.</li>
///       </ol>
///     </details>
interface Core_ConfigurableModuleInterface extends Core_ModuleInterface {

///   <protocol name="creating">

///   <method name="initialize" scope="class">
///     <brief>Выполняет инициализацию модуля</brief>
///     <args>
///       <arg name="options" type="array" default="array()" brief="массив опций" />
///     </args>
///     <body>
  static public function initialize(array $options = array());
///     </body>
///   </method>

///   </protocol>

///   <protocol name="configuring">

///   <method name="options" returns="mixed" scope="class">
///     <brief>Устанавливает значения списка опций, возвращает список значений всех опций</brief>
///     <args>
///       <arg name="options" type="array" default="array()" brief="массив опций" />
///     </args>
///     <details>
///       <p>Метод может быть использован для возврата полного списка значений опций, либо для
///          установки значений нескольких различных опций за один вызов.</p>
///     </details>
///     <body>
  static public function options(array $options = array());
///     </body>
///   </method>

///   <method name="option" returns="mixed">
///     <brief>Устанавливает опцию или возвращает ее значение</brief>
///     <args>
///       <arg name="name"  type="string"  brief="имя опции" />
///       <arg name="value" default="null" brief="значение" />
///     </args>
///     <details>
///       <p>В случае, если указано значение $value, значение опции $name должно быть изменено.
///          В любом случае, метод должен вернуть актуальное значение опции.</p>
///       <p>Некоторую трудность может представить передача null-параметра, если такие значения
///          опций необходимы, можно проверять количество параметров, передаваемых в метод.</p>
///     </details>
///     <body>
  static public function option($name, $value = null);
///     </body>
///   </method>

///   </protocol>
}
/// </interface>


/// <class name="Core.AbstractConfigurableModule">
abstract class Core_AbstractConfigurableModule implements Core_ConfigurableModuleInterface {

  protected static $options = array();
  
///   <protocol name="creating">

///   <method name="initialize" scope="class">
///     <brief>Выполняет инициализацию модуля</brief>
///     <args>
///       <arg name="options" type="array" default="array()" brief="массив опций" />
///     </args>
///     <body>
  static public function initialize(array $options = array()) {
    return self::options($options);
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="configuring">

///   <method name="options" returns="mixed" scope="class">
///     <brief>Устанавливает значения списка опций, возвращает список значений всех опций</brief>
///     <args>
///       <arg name="options" type="array" default="array()" brief="массив опций" />
///     </args>
///     <details>
///       <p>Метод может быть использован для возврата полного списка значений опций, либо для
///          установки значений нескольких различных опций за один вызов.</p>
///     </details>
///     <body>
  static public function options(array $options = array()) {
    Core_Arrays::deep_merge_update_inplace(self::$options, $options);
    return self::$options;
  }
///     </body>
///   </method>

///   <method name="option" returns="mixed">
///     <brief>Устанавливает опцию или возвращает ее значение</brief>
///     <args>
///       <arg name="name"  type="string"  brief="имя опции" />
///       <arg name="value" default="null" brief="значение" />
///     </args>
///     <details>
///       <p>В случае, если указано значение $value, значение опции $name должно быть изменено.
///          В любом случае, метод должен вернуть актуальное значение опции.</p>
///       <p>Некоторую трудность может представить передача null-параметра, если такие значения
///          опций необходимы, можно проверять количество параметров, передаваемых в метод.</p>
///     </details>
///     <body>
  static public function option($name, $value = null) {
    if (is_null($value))
      return self::$options[$name];
    return self::$options[$name] = $value;
  }
///     </body>
///   </method>

///   </protocol>

}
/// </class>

/// <class name="Core.Exception" extends="Exception" stereotype="exception">
///   <brief>Базовый класс исключения</brief>
///   <implements interface="Core.PropertyAccessInterface" />
///   <details>
///     <p>Этот класс предназначен для использования в качестве базового для все всех классов
///        исключений фреймворка, а также для классов исключений, определяемых пользовательскими
///        приложениями.</p>
///     <p>На данный момент дополнительной функциональностью, реализуемой классом, является
///        ограничение доступа "только на чтение" для свойств класса. При этом подразумевается, что
///        объект исключения сохраняет дополнительную информацию об ошибке в виде набора внутренних
///        свойств, передаваемых в качестве параметров конструктора класса.</p>
///   </details>
class Core_Exception extends Exception implements Core_PropertyAccessInterface {

///   <protocol name="accessing" interface="Core.PropertyAccessInterface">

///   <method name="__get" returns="mixed">
///     <brief>Возвращает значение свойства</brief>
///     <args>
///       <arg name="property" type="string" brief="имя свойства" />
///     </args>
///     <body>
  public function __get($property) {
    return isset($this->$property) ? $this->$property : null;
  }
///     </body>
///   </method>

///   <method name="__set" returns="mixed" >
///     <brief>Устанавливает значение свойства</brief>
///     <args>
///       <arg name="property" type="string" brief="имя свойства" />
///       <arg name="value" brief="значние" />
///     </args>
///     <details>
///       <p>Все свойства объекта -- read-only.</p>
///     </details>
///     <body>
  public function __set($property, $value) {
    throw isset($this->$property) ?
      new Core_ReadOnlyPropertyException($property) :
      new Core_MissingPropertyException($property);
  }
///     </body>
///   </method>

///   <method name="__isset" returns="boolean" >
///     <brief>Проверяет, установлено ли значение свойства</brief>
///     <args>
///       <arg name="property" type="string" brief="имя свойства" />
///     </args>
///     <body>
  public function __isset($property) { return isset($this->$property); }
///     </body>
///   </method>

///   <method name="__unset" >
///     <brief>Удаляет значение свойства</brief>
///     <args>
///       <arg name="property" type="string" brief="имя свойства" />
///     </args>
///     <body>
  public function __unset($property) {
    throw isset($this->$property) ?
      new Core_ReadOnlyPropertyException($property) :
      new Core_MissingPropertyException($property);
  }
///     </body>
///   </method>

///   </protocol>
}
/// </class>


/// <class name="Core.TypeException" extends="Core.Exception" stereotype="exception">
///   <brief>Базовый класс исключений, связанных с контролем типов</brief>
class Core_TypeException extends Core_Exception {}
/// </class>


/// <class name="Core.NotImplementedException" extends="Core.Exception" stereotype="exception">
///   <brief>Исключение: нереализованный метод</brief>
class Core_NotImplementedException extends Core_Exception {}
/// </class>

/**
 * Исключение: отсутсвует ключ в массиве
 * 
 * @package Core
 */
class Core_MissingKeyIntoArrayException extends Core_Exception
{

  protected $arg_array_name;
  protected $arg_key_name;

	/**
	 * Конструктор
	 * 
	 * @params string $array_name Имя массива
	 * @params string $key_name Имя отсутствующего ключа
	 */
	public function __construct($array_name, $key_name)
	{
		$this->arg_array_name = $array_name;
		$this->arg_key_name = $key_name;
		parent::__construct("Missing key '{$this->arg_key_name}' into array '{$this->arg_array_name}'");
	}

}


/// <class name="Core.InvalidArgumentTypeException" extends="Core.TypeException" stereotype="exception">
///   <brief>Исключение: некорректный тип аргумента</brief>
///   <details>
///     <p>Класс предназначен для случаев проверки типов аргументов методов при невозможности
///        применения статической типизации.</p>
///     <p>Свойства:</p>
///     <dl>
///       <dt>arg_name</dt>
///       <dd>имя аргумента</dd>
///       <dt>arg_type</dt>
///       <dd>тип аргумента</dd>
///     </dl>
///   </details>
class Core_InvalidArgumentTypeException extends Core_TypeException {

  protected $arg_name;
  protected $arg_type;

///   <protocol name="creating">

///   <method name="__construct">
///     <brief>Конструктор</brief>
///     <args>
///       <arg name="name" type="string" brief="имя аргумента" />
///       <arg name="arg" brief="аргумент" />
///     </args>
///     <details>
///       <p>В качестве параметров конструктор получает имя аргумента в соответствии с определением
///          метода, и сам аргумент.</p>
///     </details>
///     <body>
  public function __construct($name, $arg) {
    $this->arg_name = (string) $name;
    $this->arg_type = (string) gettype($arg);
    parent::__construct("Invalid argument type for '$this->arg_name': ($this->arg_type)");
  }
///     </body>
///   </method>

///   </protocol>
}
/// </class>


/// <class name="Core.InvalidArgumentValueException" extends="Core.Exception" stereotype="exception">
class Core_InvalidArgumentValueException extends Core_Exception {

  protected $arg_name;
  protected $arg_value;

///   <protocol name="creating">

///   <method name="__construct">
///     <args>
///       <arg name="name" type="string" />
///       <arg name="value"  />
///     </args>
///     <body>
  public function __construct($name, $value) {
    $this->arg_name = (string) $name;
    $this->arg_value = $value;
    parent::__construct("Invalid argument value for '$this->arg_name': ($this->arg_value)");
  }
///     </body>
///   </method>

///   </protocol>
}
/// </class>


/// <class name="Core.ObjectAccessException" extends="Core.Exception" stereotype="exception">
///   <brief>Базовый класс исключения некорректного доступа к объекту</brief>
class Core_ObjectAccessException extends Core_Exception {}
/// </class>


/// <class name="Core.MissingPropertyException" extends="Core.ObjectAccessException" stereotype="exception">
///   <brief>Исключение: обращение к несуществующему свойству объекта</brief>
///   <details>
///     <p>Исключение должно генерироваться при попытке обращения к несуществующему свойству объекта,
///        как правило при реализации интерфейса Core.PropertyAccessInterface.</p>
///     <p>Свойства:</p>
///     <dl>
///       <dt>property</dt>
///       <dd>имя отсутствующего свойства</dd>
///     </dl>
///   </details>
class Core_MissingPropertyException extends Core_ObjectAccessException {

  protected $property;

///   <protocol name="creating">

///   <method name="__construct">
///     <brief>Конструктор</brief>
///     <args>
///       <arg name="property" type="string" brief="имя свойства" />
///     </args>
///     <body>
  public function __construct($property) {
    $this->property = (string) $property;
    parent::__construct("Missing property: $this->property");
  }
///     </body>
///   </method>

///   </protocol>
}
/// </class>


/// <class name="Core.MissingIndexedPropertyException" extends="Core.ObjectAccessException" stereotype="exception">
///   <brief>Исключение: обращение к несуществующему индексу</brief>
///   <details>
///     <p>Исключение может генерироваться при обращении к несуществующему индексу объекта,
///        реализующего индексированный доступ (интерфейс Core.IndexedAccessInterface).
///        Альтернативная стратегия -- возврат некоторого значения по умолчанию.</p>
///     <dl>
///       <dt>index</dt>
///       <dd>индекс</dd>
///     </dl>
///   </details>
class Core_MissingIndexedPropertyException extends Core_ObjectAccessException {

  protected $index;

///   <protocol name="creating">

///   <method name="__construct">
///     <brief>Конструктор</brief>
///     <args>
///       <arg name="index" brief="значение индекса" />
///     </args>
///     <body>
  public function __construct($index) {
    $this->index = (string) $index;
    parent::__construct("Missing indexed property for index $this->index");
  }
///     </body>
///   </method>

///   </protocol>
}
/// </class>


/// <class name="Core.MissingMethodException" extends="Core.ObjectAccessException" stereotype="exception">
///   <brief>Исключение: вызов несуществующего метода</brief>
///   <details>
///     <p>Исключение может генерироваться при попытке вызова отсутствующего метода объекта с
///        помощью динамической диспетчеризации (Core.CallInterface::__call()).</p>
///     <dl>
///       <dt>method</dt>
///       <dd>имя метода</dd>
///     </dl>
///   </details>
class Core_MissingMethodException extends Core_ObjectAccessException {

  protected $method;

///   <protocol name="creating">

///   <method name="__construct">
///     <brief>Конструктор</brief>
///     <args>
///       <arg name="method" type="string" brief="имя метода" />
///     </args>
///     <body>
  public function __construct($method) {
    $this->method = (string) $method;
    parent::__construct("Missing method: $this->method");
  }
///     </body>
///   </method>

///   </protocol>
}
/// </class>


/// <class name="Core.ReadOnlyPropertyException" extends="Core.ObjectAccessException" stereotype="exception">
///   <brief>Исключение: попытка записи read-only свойства</brief>
///   <details>
///     <p>Исключение должно генерироваться при попытке записи свойства, доступного только для
///        чтения. В большинстве случаев необходимость в его использовании возникает при реализации
///        интерфейса Core.PropertyAccessInterface</p>
///     <p>Свойства:</p>
///     <dl>
///       <dt>property</dt>
///       <dd>имя свойства</dd>
///     </dl>
///   </details>
class Core_ReadOnlyPropertyException extends Core_ObjectAccessException {

  protected $property;

///   <protocol name="creating>">

///   <method name="__construct">
///     <brief>Конструктор</brief>
///     <args>
///       <arg name="property" type="string" brief="имя свойства" />
///     </args>
///     <body>
  public function __construct($property) {
    $this->property = (string) $property;
    parent::__construct("The property is read-only: $this->property");
  }
///     </body>
///   </method>
///   </protocol>
}
/// </class>


/// <class name="Core.ReadOnlyIndexedPropertyException" extends="Core.ObjectAccessException" stereotype="exception">
///   <brief>Исключение: попытка записи read-only индексного свойства</brief>
///   <details>
///     <p>Класс аналогичен Core.ReadOnlyPropertyException, но предназначен для случаев обращения
///        по индексу (интерфейс Core.IndexedAccessInterface).</p>
///     <p>Свойства:</p>
///     <dl>
///       <dt>index</dt>
///       <dd>индекс</dd>
///     </dl>
///   </details>
class Core_ReadOnlyIndexedPropertyException extends Core_ObjectAccessException {

  protected $index;

///   <protocol name="creating">

///   <method name="__construct">
///     <brief>Конструктор</brief>
///     <args>
///       <arg name="index" brief="индекс" />
///     </args>
///     <body>
  public function __construct($index) {
    $this->index = (string) $index;
    parent::__construct("The property is read-only for index: $this->index");
  }
///     </body>
///   </method>

///   </protocol>
}
/// </class>


/// <class name="Core.ReadOnlyObjectException" extends="Core.ObjectAccessException" stereotype="exception">
///   <brief>Класс исключения для объектов доступных только для чтения</brief>
class Core_ReadOnlyObjectException extends Core_ObjectAccessException {
  protected $object;

///   <protocol name="creating">

///   <method name="__construct">
///     <brief>Конструктор</brief>
///     <args>
///       <arg name="object" brief="объект" />
///     </args>
///     <body>
  public function __construct($object) {
    $this->object = $object;
    parent::__construct("Read only object");
  }
///     </body>
///   </method>

///   </protocol>
}
/// </class>


/// <class name="Core.UndestroyablePropertyException" extends="Core.ObjectAccessException" stereotype="exception">
///   <brief>Исключение: попытка удаления свойства объекта</brief>
///   <details>
///     <p>Существование этого исключения связано с противоречивой семантикой операции unset()
///        применительно к свойствам объектов. Оригинальная семантика -- удаление
///        public-свойства. В случае обеспечения доступа к свойствам через
///        Core.PropertyAccessInterface возможны две стратегии:</p>
///     <ul>
///       <li>присваивание свойству значения null;</li>
///       <li>генерирование исключения класса Core.UndestroayablePropertException.</li>
///     </ul>
///     <p>Свойства:</p>
///     <dl>
///       <dt>property</dt>
///       <dd>имя свойства</dd>
///     </dl>
///   </details>
class Core_UndestroyablePropertyException extends Core_ObjectAccessException {

  protected $property;

///   <protocol name="creating">

///   <method name="__construct">
///     <brief>Конструктор</brief>
///     <args>
///       <arg name="property" type="string" brief="имя свойтсва" />
///     </args>
///     <body>
  public function __construct($property) {
    $this->property = (string) $property;
    parent::__construct("Unable to destroy property: $property");
  }
///     </body>
///   </method>

///   </protocol>
}
/// </class>

/**
 * Исключение: попытка удаления индексного свойства
 * 
 */
class Core_UndestroyableIndexedPropertyException extends Core_ObjectAccessException
{

  /**
   * Название индексного свойства
   * @var string
   */
  protected $property;

  /**
   * Конструктор
   * @param string $property имя свойства
   */
  public function __construct($property)
  {
    $this->property = (string) $property;
    parent::__construct("Unable to destroy indexed property: $property");
  }
}


/// <class name="Core.ModuleException" extends="Core.Exception" stereotype="exception">
///   <brief>Базовый класс исключений загрузчика модулей</brief>
class Core_ModuleException extends Core_Exception {}
/// </class>


/// <class name="Core.ModuleNotFoundException" extends="Core.ModuleException" stereotype="exception">
///   <brief>Исключение: модуль не найден</brief>
///   <details>
///     <p>Генерируется в случае, если закгрузчик не может найти файл, содержащий код модуля.</p>
///     <p>Свойства:</p>
///     <dl>
///       <dt>module</dt>
///       <dd>имя загружаемого модуля</dd>
///     </dl>
///   </details>
class Core_ModuleNotFoundException extends Core_ModuleException {

  protected $module;

///   <protocol name="creating">

///   <method name="__construct">
///     <brief>Конструктор</brief>
///     <args>
///       <arg name="module" type="string" brief="имя модуля" />
///       <arg name="path" type="string" brief="путь" />
///     </args>
///     <body>
  public function __construct($module, $path = '') {
    $this->module = (string) $module;
    parent::__construct("Module $this->module not found" . ($path ? " in $path" : ''));
  }
///     </body>
///   </method>

///   </protocol>
}
/// </class>


/// <class name="Core.InvalidModuleException" extends="Core.ModuleException" stereotype="exception">
///   <brief>Исключение: некорректный формат модуля</brief>
///   <details>
///     <p>Исключение генерируется в случае, если загрузчик нашел файл модуля, но модуль не
///        удовлетворяет следующим критериям:</p>
///     <ul>
///       <li>модуль должен содержать класс, имя которго соответствует имени модуля;</li>
///       <li>класс модуля должен имплементировать интерфейс Core.ModuleInterface;</li>
///       <li>класс модуля должен содержать константу VERSION.</li>
///     </ul>
///     <p>Свойства:</p>
///     <dl>
///       <dt>module</dt>
///       <dd>имя модуля</dd>
///     </dl>
///   </details>
class Core_InvalidModuleException extends Core_ModuleException {

  protected $module;

///   <protocol name="creating">

///   <method name="__construct">
///     <brief>Конструктор</brief>
///     <args>
///       <arg name="module" type="string" brief="имя модуля" />
///     </args>
///     <body>
  public function __construct($module) {
    $this->module = (string) $module;
    parent::__construct("Invalid module: $this->module");
  }
///     </body>
///   </method>

///   </protocol>
}
/// </class>

//TODO: merge в Interface и общий метод Core::merge();
interface Core_ModuleLoaderInterface {
  public function configure($module, array $config);
  public function load($module);
  public function paths(array $paths = array());
  public function already_loaded($module);
  public function merge(Core_ModuleLoaderInterface $instance);
  public function is_loaded($module);
}


/// <class name="Core.ModuleLoader">
///   <brief>Загрузчик модулей</brief>
///   <depends supplier="Core.ModuleException"         stereotype="throws" />
///   <depends supplier="Core.ModuleNotFoundException" stereotype="throws" />
///   <depends supplier="Core.InvalidModuleException"  stereotype="throws" />
///   <details>
///     <p>Объект этого класса создается в единственном экземпляре и используется неявно с помощью
///        вызовов Core::load(), Core::configure()  и т.д. Вам не придется создавать экземпляр этого
///        класса самостоятельно.</p>
///   </details>
class Core_ModuleLoader implements Core_ModuleLoaderInterface {

  protected $paths   = array(
  '-App' => '../app/lib',
  // '*' => '../tao/lib',
  );
  protected $configs = array();
  protected $loaded  = array('Core' => true);

///   <protocol name="creating">

///   <method name="__construct">
///     <brief>Конструктор</brief>
///     <args>
///       <arg name="paths" type="array" default="array()" brief="массив путей" />
///     </args>
///     <body>
  public function __construct(array $paths = array()) {
    $this->paths['*'] = __DIR__;
    $this->paths($paths);
  }
///     </body>
///   </method>

  public function paths(array $paths = array()) {
    if ($paths && count($paths)) {
      $old = $this->paths;
      $this->paths = $paths;
      foreach($old as $k => $v)
        if (!isset($this->paths[$k]))
    $this->paths[$k] = $v;
    }
    return $this;
  }
  
  public function already_loaded($module) {
    $this->loaded[(string) $module] = true;
    return $this;
  }
  
  public function merge(Core_ModuleLoaderInterface $instance) {
    $instance->paths($this->paths);
    foreach ($this->configs as $module => $config)
      $instance->configure($module, $config);
    foreach ($this->loaded as $module => $v)
      if ($v) $instance->already_loaded($module);
    return $instance;
  }

///   </protocol>

///   <protocol name="loading">

///   <method name="configure" returns="Core.ModuleLoader">
///     <brief>Устанавливает значения опций модуля</brief>
///     <args>
///       <arg name="module" type="string" brief="имя модуля" />
///       <arg name="config" type="array" brief="массив значений опций" />
///     </args>
///     <details>
///     </details>
///     <body>
  public function configure($module, array $config) {
    $this->configs[$module] = isset($this->configs[$module])? array_merge_recursive( (array) $this->configs[$module] ,$config) : $config;
    return $this;
  }
///     </body>
///   </method>

///   <method name="load" returns="Core.ModuleLoader">
///     <brief>Подгружает модуль</brief>
///     <args>
///       <arg name="module" type="string" brief="имя модуля" />
///     </args>
///     <body>
  public function load($module) {
    if (!$this->is_loaded($module)) {

      $real_module_name = Core_Types::real_class_name_for($module) ;
      $this->mark_as_loaded($module);

      $cached_path = Core::cached_modules($module);
      if ($cached_path) {
        $loaded = true;
        //FIXME:
        if (!Core_Strings::starts_with($cached_path, '.') && !Core_Strings::starts_with($cached_path, '/'))
          $cached_path = $this->paths['*'] . '/' . $cached_path;
        include($cached_path);
      }
      else {
        $loaded = $this->load_module_file($file = $this->file_path_for($module, false), $real_module_name, $module);
        Core::cached_modules($module, $file);
      }
      if (Core::option('spl_autoload') && !$loaded) return false;

      if ($this->is_module($real_module_name)) {
        if (method_exists($real_module_name, 'initialize'))
          call_user_func(array($real_module_name, 'initialize'),
            isset($this->configs[$module]) ?
              $this->configs[$module] :
              array());
      } else
        throw new Core_InvalidModuleException($module);
    }
    return Core::option('spl_autoload') ? true : $this;
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="quering">

///   <method name="is_loaded" returns="boolean">
///     <brief>Проверяет загружен ли модуль</brief>
///     <args>
///       <arg name="module" type="string" brief="имя модуля" />
///     </args>
///     <body>
  public function is_loaded($module) {
    return isset($this->loaded[(string) $module]);
  }
///     </body>
///   </method>

///   <method name="file_path_for" returns="string" access="protected">
///     <brief>Возвращает путь к файлу модуля</brief>
///     <args>
///       <arg name="module" type="string" brief="имя модуля" />
///     </args>
///     <details>
///       <p>Путь к файлу формируется с учетом настроек параметров поиска модулей, определяемых
///          при вызове Core::initialize() или с помощью переменной TAO_PATH.</p>
///       <p>В случае, если файла модуля не существует, генерируется исключение
///          Core.ModuleNotFoundException.</p>
///     </details>
///     <body>
  public function file_path_for($module, $first = true) {
    foreach ($this->paths as $name => $root) {
      if ($drop_prefix = ($name[0] == '-')) $name = substr($name, 1);

      if (preg_match(
          $name == '*' ?
            '{.+}' :
            '{^'.str_replace('.', '\.', $name).'}', $module))
        return   $root.'/'.str_replace(
          '.', '/',
          $drop_prefix ? preg_replace("{^$name.}", '', $module) : $module).'.php';
    }
    if (Core::option('spl_autoload')) return false;
    else throw new Core_ModuleNotFoundException($module);
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="supporting">

///   <method name="load_module_file" returns="Core.ModuleLoader" access="protected">
///     <brief>Выполняет непосредствунную загрузкку файла модуля</brief>
///     <args>
///       <arg name="file" type="string" brief="путь к файлу" />
///       <arg name="class_name" type="string" brief="имя класса модуля" />
///       <arg name="module" type="string" brief="имя модуля" />
///     </args>
///     <details>
///       <p>Метод просто вызывает include(), предназначен для выполнения include() в
///          (по возможности :) пустом окружении.</p>
///     </details>
///     <body>
  protected function load_module_file($file, $class_name = '', $module = '') {
    if (Core::option('spl_autoload') && !$file) return false;
    $path = realpath($file);
    if (empty($path) && !class_exists($class_name, true)) { //Даем сработать autoload
      if (Core::option('spl_autoload')) return false;
      else throw new Core_ModuleNotFoundException($module, $file);
    }
    if (!empty($path))
      include($path);
    return Core::option('spl_autoload') ? true : $this;
  }
///     </body>
///   </method>

///   <method name="mark_as_loaded" returns="Core.ModuleLoader" access="protected">
///     <brief>Помечает модуль как загруженный</brief>
///     <args>
///       <arg name="module" type="string" brief="имя модуля" />
///     </args>
///     <body>
  protected function mark_as_loaded($module) {
    $this->loaded[$module] = true;
    return $this;
  }
///     </body>
///   </method>

///   <method name="is_module" returns="boolean" access="protected">
///     <brief> Проверяет, является ли заданный класс классом модуля</brief>
///     <args>
///       <arg name="module_real_name" type="string" />
///     </args>
///     <details>
///       <p>Проверяется, что заданный класс имплементирует Core.ModuleInterface и содержит
///          константу VERSION.</p>
///     </details>
///     <body>
  protected function is_module($module_real_name) {
    return in_array("Core_ModuleInterface",class_implements($module_real_name));
  }
///     </body>
///   </method>

///   </protocol>
}
/// </class>


/// <class name="Core.Regexps" stereotype="utility">
///   <brief>Класс обертка над встроенными функциями работы с регулярными выражениями</brief>
///   <details>
///     <p>Класс группирует функции для работы с регулярными выражениями в отдельное пространство
///        имен (исключительно из эстетических соображений), а также делает работу с некоторыми
///        функциями более удобной.</p>
///   </details>
class Core_Regexps {

///   <protocol name="matching">

///   <method name="match" returns="boolean" scope="class">
///     <brief>Сопоставляет строку с регулярным выражением</brief>
///     <args>
///       <arg name="regexp" type="string" brief="регулярное выражение" />
///       <arg name="string" type="string" brief="строка" />
///     </args>
///     <details>
///       <p>Вызывает preg_match, может быть использована в случае, если необходимо просто проверить
///          строку на соответствие без получения результатов сопоставления.</p>
///     </details>
///     <body>
  static public function match($regexp, $string) { return (boolean) preg_match($regexp, $string); }
///     </body>
///   </method>

///   <method name="match_with_results" returns="array" scope="class">
///     <brief>Сопоставляет строку с регулярным выражением, возвращает результат сопоставления</brief>
///     <args>
///       <arg name="regexp" type="string" brief="регулярное выражение" />
///       <arg name="string" type="string" brief="строка" />
///     </args>
///     <details>
///       <p>В случае, если сопоставление выполнено успешно, возвращает результаты сопоставления в
///          виде массива. В противном случае возвращает false.</p>
///       <p>Например, приведенные ниже фрагменты кода эквивалентны:</p>
///       <code><![CDATA[
/// if ($m = Core_Regexps::match_with_results($regexp, $string)) print $m[1];
///
/// $m = array();
/// if (preg_match($regexp, $string, $m)) print $m[1];
/// ]]></code>
///     </details>
///     <body>
  static public function match_with_results($regexp, $string) {
    $m = array();
    return preg_match($regexp, $string, $m) ? $m : false;
  }
///     </body>
///   </method>

///   <method name="match_all" returns="array" scope="class">
///     <brief>Сопоставляет строку с регулярным выражением, возвращает все результаты сопоставления</brief>
///     <args>
///       <arg name="regexp" type="string" brief="регулярное выражение" />
///       <arg name="string" type="string" brief="строка" />
///       <arg name="type" type="int" default="PREG_PATTERN_ORDER" brief="способ группировки результатов" />
///     </args>
///     <details>
///       <p>Аналог Core_Regexps::match() для preg_match_all().</p>
///     </details>
///     <body>
  static public function match_all($regexp, $string, $type = PREG_PATTERN_ORDER) {
    $m = array();
    return preg_match_all($regexp, $string, $m, (int) $type) ? $m : false;
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="quoting">

///   <method name="quote" returns="string" scope="class">
///     <brief>Выполняет квотинг строки для использования в качестве регулярного выражения</brief>
///     <args>
///       <arg name="string" type="string" brief="строка" />
///     </args>
///     <p>Обертка над функцией preg_quote().</p>
///     <body>
  static public function quote($string) { return preg_quote($string); }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="replacing">

///   <method name="replace" returns="string" scope="class">
///     <brief>Выполняет замену строк по регулярному выражению</brief>
///     <args>
///       <arg name="regexp"      type="string" brief="регулярное выражение" />
///       <arg name="replacement" type="string" brief="строка замены" />
///       <arg name="source"      type="string" brief="исходная строка" />
///       <arg name="limit"       type="int"    brief="максимальное количество замен" default="-1" />
///     </args>
///     <details>
///       <p>Обертка над функцией  preg_replace.</p>
///     </details>
///     <body>
  static public function replace($regexp, $replacement, $source, $limit = -1) {
    return preg_replace($regexp, $replacement, $source, (int) $limit);
  }
///     </body>
///   </method>

///   <method name="replace_using_callback" returns="string" scope="class">
///     <brief>Выполняет замену по регулярному выражению с использованием пользовательской функции</brief>
///     <args>
///       <arg name="regexp"   type="string"   brief="регулярное выражение" />
///       <arg name="callback" type="callback" brief="пользовательская функция" />
///       <arg name="source"   type="string"   brief="строка-источник" />
///       <arg name="limit"   type="int"       default="-1" brief="максимальное количество замен" />
///     </args>
///     <details>
///       <p>Обертка над preg_replace_callback();</p>
///     </details>
///     <body>
  static public function replace_using_callback($regexp, $callback, $source, $limit = -1) {
    return preg_replace_callback($regexp, $callback, $source, $limit);
  }
///     </body>
///   </method>

///   <method name="replace_ref" returns="int" scope="class">
///     <brief>Выполняет замену по регулярном выражению, возвращает количество замен</brief>
///     <args>
///       <arg name="regexp" type="string" brief="регулярное выражение" />
///       <arg name="replacement" type="string" brief="строка замены" />
///       <arg name="source" type="string" brief="исходная строка" />
///       <arg name="limit" type="int" default="-1" brief="максимальное количество замен" />
///     </args>
///     <details>
///       <p>Обертка над preg_replace. Исходная строка передается по ссылке.</p>
///     </details>
///     <body>
  static public function replace_ref($regexp, $replacement, &$source, $limit = -1) {
    $count = 0;
    $source = preg_replace($regexp, $replacement, $source, (int) $limit, $count);
    return $count;
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="splitting">

///   <method name="split_by" returns="array" scope="class">
///     <brief>Разбивает строку на подстроки по регулярному выражению</brief>
///     <args>
///       <arg name="regexp" type="string" brief="регулярное выражение" />
///       <arg name="string" type="string" brief="строка" />
///       <arg name="limit" type="number"  default="-1" brief="количество замен" />
///       <arg name="flags" type="number"  default="0"  brief="флаги" />
///     </args>
///     <details>
///       <p>Обертка над функцией preg_split().</p>
///     </details>
///     <body>
  static public function split_by($regexp, $string, $limit = -1, $flags = 0) {
    return preg_split($regexp, $string, (int) $limit, (int) $flags);
  }
///     </body>
///   </method>

///   </protocol>
}
/// </class>


/// <class name="Core.Strings" stereotype="utility">
///   <brief>Обертка над php-функциями для работы со строками</brief>
///   <details>
///     <p>Класс включает в себя набор функций для работы со строками. При этом используется явный
///        вызов функций модуля mbstring.</p>
///     <p>Поскольку мы работаем с UTF-8, модуль mbstring нужен практически всегда. Вместе с тем,
///        бывают ситуации, когда необходимо работать со строкой, как с последовательностью
///        байт, а не как с набором юникодных символов. Например, это может понадобиться при
///        обработке бинарных строк (со встроенными функциями для этого у PHP туго).</p>
///     <p>На данный момент принято некрасивое, но работающее решение ввести методы begin_binary() и
///        end_binary(), которые обеспечивают переход модуля mbstring в кодировку ASCII и выход из
///        нее. Соответственно, при необходимости использовать методы класса для обработки бинарных
///        данных соответствующий кусок кода необходимо выделить с помощью вызовов
///        begin_binary()/end_binary(), при этом вызовы могут быть вложенными.</p>
///   </details>
class Core_Strings {

  static protected $encodings = array();

///   <protocol name="configuring">

///   <method name="begin_binary" scope="class">
///     <brief>Переводит модуль в бинарный режим.</brief>
///     <body>
  static public function begin_binary() {
    array_push(self::$encodings, mb_internal_encoding());
    mb_internal_encoding('ASCII');
  }
///     </body>
///   </method>

///   <method name="end_binary" scope="class">
///     <brief>Переводит модуля из бинарного режима в режим использования предыдущей кодировки.</brief>
///     <body>
  static public function end_binary() {
    if ($encoding = array_pop(self::$encodings))
      mb_internal_encoding($encoding);
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="processing">

///   <method name="concat" returns="string" scope="class" varargs="true">
///     <brief>Объединяет набор строк в одну</brief>
///     <details>
///       <p>Строки могут передаваться в виде набора параметров или в виде первого
///          параметра-массива.</p>
///     </details>
///     <body>
  static public function concat() {
    $args = func_get_args();
    return implode('', Core::normalize_args($args));
  }
///     </body>
///   </method>

///   <method name="concat_with" returns="string" scope="class" varargs="true">
///     <brief>Объединяет строки с использованием разделителя</brief>
///     <details>
///       <p>Метод аналогичен Core.String::concat(), но в качестве первого параметра передается
///          разделитель.</p>
///     </details>
///     <body>
  static public function concat_with() {
    $args = Core::normalize_args(func_get_args());
    return implode((string) array_shift($args), $args);
  }
///     </body>
///   </method>


///   <method name="substr" returns="string" scope="class">
///     <brief>Возвращает подстроку</brief>
///     <args>
///       <arg name="string" type="string" brief="строка" />
///       <arg name="start" type="int" brief="номер символа" />
///       <arg name="length" type="int" default="null" brief="длина строки" />
///     </args>
///     <details>
///       <p>Обертка над встроенной функцией substr.</p>
///     </details>
///     <body>
// TODO: eliminate if
  static public function substr($string, $start, $length = null) {
    return $length === null ?
      mb_substr($string, $start) :
      mb_substr($string, $start, $length);
  }
///     </body>
///   </method>

///   <method name="replace" returns="string" scope="class">
///     <brief>Выполняет замену в строке</brief>
///     <args>
///       <arg name="string" type="string" brief="строка" />
///       <arg name="what" type="string" brief="искомая подстрока" />
///       <arg name="with" type="string" brief="замена" />
///     </args>
///     <body>
  static public function replace($string, $what, $with) {
    return str_replace($what, $with, $string);
  }
///     </body>
///   </method>

///   <method name="chop" returns="string" scope="class">
///     <brief>Удаляет пробельные символы в конце строки</brief>
///     <args>
///       <arg name="tail" type="string" brief="строка" />
///     </args>
///     <details>
///       <p>Обертка над стандартной функцией rtrim.</p>
///     </details>
///     <body>
  static public function chop($tail) { return rtrim($tail); }
///     </body>
///   </method>

///   <method name="trim" returns="string" scope="class">
///     <brief>Удаляет пробельные символы в начале и конце строки</brief>
///     <args>
///       <arg name="string" type="string" brief="строка" />
///       <arg name="chars" type="string" default="null" brief="символы" />
///     </args>
///     <details>
///       <p>Обертка над функцией trim().</p>
///     </details>
///     <body>
  static public function trim($string, $chars = null) {
    return $chars ? trim($string, $chars) : trim($string);
  }
///     </body>
///   </method>

///   <method name="split" returns="array" scope="class">
///     <brief>Разбивает строку по пробелам</brief>
///     <args>
///       <arg name="string" type="string" brief="строка" />
///     </args>
///     <details>
///       <p>Вызова эквивалентен следующему:</p>
///       <code><![CDATA[
/// explode(' ', $string);
/// ]]></code>
///     </details>
///     <body>
  static public function split($string) {  return explode(' ', $string); }
///     </body>
///   </method>

///   <method name="split_by" returns="array" scope="class">
///     <brief>Разбивает строку по заданному разделителю</brief>
///     <args>
///       <arg name="delimiter" type="string" breif="разделитель" />
///       <arg name="string"    type="string" brief="строка" />
///     </args>
///     <details>
///       <p>Метод является оберткой над встроенным explode(), но обладает замечательной
///          особенностью: при передаче пустой строки он возвращает пустой массив.</p>
///     </details>
///     <body>
  static public function split_by($delimiter, $string) {
    return ($string === '') ? array() : explode($delimiter, $string);
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="formatting">

///   <method name="format" returns="sprintf" scope="class" varargs="true">
///     <brief>Выполняет форматирование строки</brief>
///     <details>
///       <p>Обертка над vsprintf().</p>
///     </details>
///     <body>
  static public function format() {
    $args = func_get_args();
    return vsprintf(array_shift($args), $args);
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="quering">

///   <method name="starts_with" returns="boolean" scope="class">
///     <brief>Проверяет, начинается ли строка с заданной подстроки</brief>
///     <args>
///       <arg name="string" type="string" brief="строка" />
///       <arg name="head"   type="string" brief="подстрока" />
///     </args>
///     <body>
  static public function starts_with($string, $head) { return (mb_strpos($string, $head) === 0); }
///     </body>
///   </method>

///   <method name="ends_with" returns="boolean" scope="class">
///     <brief>Проверяет заканчивается ли строка заданной подстрокой</brief>
///     <args>
///       <arg name="string" type="string" brief="строка" />
///       <arg name="tail"   type="string" brief="подстрока" />
///     </args>
///     <body>
  static public function ends_with($string, $tail) {
    $pos = mb_strrpos($string, $tail);
    if ($pos === FALSE) return FALSE;
    return ((mb_strlen($string) - $pos) == mb_strlen($tail));
  }
///     </body>
///   </method>

///   <method name="contains" returns="boolean" scope="class">
///     <brief>Проверяет, содержит ли строка заданную подстроку</brief>
///     <args>
///       <arg name="string"   type="string" brief="строка" />
///       <arg name="fragment" type="string" brief="подстрока" />
///     </args>
///     <details>
///       <p>Внимание, метод возвращает булевское значение (содержит/не содержит), а не позицию
///          подстроки в строке.</p>
///     </details>
///     <body>
  static public function contains($string, $fragment) {
    return ($fragment && (mb_strpos($string,  $fragment) !== false));
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="converting">

///   <method name="downcase" returns="string" scope="class">
///     <brief>Приводит все символы строки к нижнему регистру</brief>
///     <args>
///       <arg name="string" type="string" brief="строка" />
///     </args>
///     <details>
///     <p>Обертка над функцией strtolower().</p>
///     </details>
///     <body>
  static public function downcase($string) { return mb_strtolower($string); }
///     </body>
///   </method>

///   <method name="upcase" returns="string" scope="class">
///     <brief>Приводит все символы строки к верхнему регистру.</brief>
///     <args>
///       <arg name="string" type="string" brief="строка" />
///     </args>
///     <details>
///       <p>Обертка над функцией mb_strtoupper.</p>
///     </details>
///     <body>
  static public function upcase($string) {
    return mb_strtoupper($string);
  }
///     </body>
///   </method>

///   <method name="capitalize" returns="string" scope="class">
///     <brief>Приводит первый символ строки к верхнему регистру</brief>
///     <args>
///       <arg name="string" type="string" brief="строка" />
///     </args>
///     <details>
///       <p>В отличие от ucfirst, работает с UTF8.</p>
///     </details>
///     <body>
  static public function capitalize($string) {
    return mb_strtoupper(mb_substr($string, 0, 1)).mb_substr($string, 1);
  }
///     </body>
///   </method>

///   <method name="lcfirst" returns="string" scope="class">
///     <brief>Приводит первый символ строки к нижнему регистру</brief>
///     <args>
///       <arg name="string" type="string" brief="строка" />
///     </args>
///     <details>
///       <p>Работает с UTF8, может быть полезен, например, для преобразования имен в CamelCase.</p>
///     </details>
///     <body>
  static public function lcfirst($string) {
    return mb_strtolower(mb_substr($string, 0, 1)).mb_substr($string, 1);
  }
///     </body>
///   </method>

///   <method name="capitalize_words" returns="string" scope="class">
///     <brief>Аналог ucfirst, работающий с UTF8</brief>
///     <args>
///       <arg name="string" type="string" brief="строка" />
///     </args>
///     <details>
///       <p>Модуль mbstring не переопределяет ucfirst, поэтому медленно и печально.</p>
///     </details>
///     <body>
  static public function capitalize_words($string) {
    return preg_replace_callback(
      '{(\s+|^)(.)}u',
      create_function('$m', 'return $m[1].mb_strtoupper(mb_substr($m[2],0,1));'),
      $string);
  }
///     </body>
///   </method>

///   <method name="to_camel_case" returns="string" scope="class">
///     <brief>Приводит идентификатор к виду CamelCase</brief>
///     <args>
///       <arg name="string" type="string" brief="строка" />
///     </args>
///     <details>
///       <p>Метод работает только с ASCII идентификаторами.</p>
///     </details>
///     <body>
  static public function to_camel_case($string, $lcfirst = false) {
    $s = str_replace(' ', '', ucwords(str_replace('_', ' ', $string)));
    return $lcfirst ? strtolower(substr($s, 0, 1)).substr($s, 1) : $s;
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="encoding">

///   <method name="decode64" returns="string">
///     <brief>Декодирует строку из base64</brief>
///     <args>
///       <arg name="string" type="string" brief="строка в base64" />
///     </args>
///     <body>
  static public function decode64($string) { return base64_decode($string); }
///     </body>
///   </method>

///   <method name="encode64" returns="string">
///     <brief>Кодирует строку в base64</brief>
///     <args>
///       <arg name="string" type="string" brief="исходная строка" />
///     </args>
///     <body>
  static public function encode64($string) { return base64_encode($string); }
///     </body>
///   </method>

///   </protocol>
}
/// </class>


/// <class name="Core.Types" stereotype="utility">
///   <brief>Набор методов для работы с информацией о типах</brief>
class Core_Types {

///   <protocol name="testing">

///   <method name="is_array" returns="boolean" scope="class">
///     <brief>Проверяет, является ли переданное значение массивом</brief>
///     <args>
///       <arg name="object" brief="объект" />
///     </args>
///     <details>
///       <p>Обертка для is_array().</p>
///     </details>
///     <body>
  static public function is_array(&$object) { return is_array($object); }
///     </body>
///   </method>

///   <method name="is_string" returns="boolean" scope="class">
///     <brief>Проверяет, является ли переданное значение строкой</brief>
///     <args>
///       <arg name="object" brief="объект" />
///     </args>
///     <details>
///       <p>Обертка для is_string().</p>
///     </details>
///     <body>
  static public function is_string(&$object) { return is_string($object);  }
///     </body>
///   </method>

///   <method name="is_number" returns="boolean" scope="class">
///     <brief>Проверяет, является ли переданное значение числом</brief>
///     <args>
///       <arg name="object" brief="объект" />
///     </args>
///     <details>
///       <p>Обертка для is_numeric().</p>
///     </details>
///     <body>
  static public function is_number(&$object) { return is_numeric($object); }
///     </body>
///   </method>

///   <method name="is_object" returns="boolean" scope="class">
///     <brief>Проверяет является ли переданное значение объектом</brief>
///     <args>
///       <arg name="object" brief="объект" />
///     </args>
///     <details>
///       <p>Обертка для is_object().</p>
///     </details>
///     <body>
  static public function is_object(&$object) { return is_object($object); }
///     </body>
///   </method>

///   <method name="is_resource" returns="boolean" scope="class">
///     <brief>Проверяет является ли переданное значение ресурсом</brief>
///     <args>
///       <arg name="object" brief="объект" />
///     </args>
///     <details>
///       <p>Обертка для is_resource().</p>
///     </details>
///     <body>
  static public function is_resource(&$object) { return is_resource($object); }
///     </body>
///   </method>

///   <method name="is_iterable" returns="boolean" scope="class">
///     <brief>Проверяет является ли переданное значение итерируемым объектом.</brief>
///     <args>
///       <arg name="object" brief="объект" />
///     </args>
///     <details>
///       <p>Переданное значение является итерируемым, если это массив или объект, реализующий
///          интерфейс Traversable.</p>
///     </details>
///     <body>
  static public function is_iterable(&$object) {
    return is_array($object) || $object instanceof Traversable;
  }
///     </body>
///   </method>

///   <method name="is_subclass_of" returns="boolean" scope="class">
///     <brief>Проверяет является ли данный класс данного объект наследником заданного класса</brief>
///     <args>
///       <arg name="ancestor" brief="предок" />
///       <arg name="object" brief="объект" />
///     </args>
///     <details>
///       <p>Метод работает не только с экземплярами объектов, но и со строковыми названиями
///          классов, которые могут быть указаны в том числе и в нотации модулей (через точку).</p>
///     </details>
///     <body>
  static public function is_subclass_of($ancestor, $object) {
    $ancestor_class = self::real_class_name_for($ancestor);
    if (is_object($object)) return ($object instanceof $ancestor_class);

    $object_class = self::real_class_name_for($object);
    if (!class_exists($object_class, false)) return false;
    
    //return $object_class instanceof $ancestor_class;
    
    //TODO: remove:
    $object_reflection = new ReflectionClass($object_class);

    return Core::with($ancestor_reflection = new ReflectionClass($ancestor_class))->isInterface() ?
      $object_reflection->implementsInterface($ancestor_class) :
      $object_reflection->isSubclassOf($ancestor_reflection);
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="quering">

///   <method name="class_name_for" returns="string" scope="class">
///     <brief>Возвращает имя класса для объекта</brief>
///     <args>
///       <arg name="object" brief="объект" />
///       <arg name="virtual" type="boolean" default="false" brief="признак необходимости возврата виртуального имени" />
///     </args>
///     <details>
///       <p>Метод позволяет получить имя класса для заданного объекта, в том числе и в виртуальной
///          (модульной) нотации.</p>
///     </details>
///     <body>
  static function class_name_for($object, $virtual = false) {
    $class_name = is_object($object) ?
      get_class($object) : (
        is_string($object) ? $object : null );

    return $class_name ? (
      (boolean) $virtual ?
        str_replace('_', '.', $class_name) :
        str_replace('.', '_', $class_name) ) : null;
  }
///     </body>
///   </method>

///   <method name="virtual_class_name_for" returns="string" scope="class">
///     <brief>Возвращает виртуальное имя класса для заданного объекта</brief>
///     <args>
///       <arg name="object" brief="объект" />
///     </args>
///     <details>
///       <p>Эквивалентно Core_Types::class_name_for($object, true).</p>
///     </details>
///     <body>
  static public function virtual_class_name_for($object) { return self::class_name_for($object, true); }
///     </body>
///   </method>

///   <method name="real_class_name_for" returns="string" scope="class">
///     <brief>Вовзращает действительное имя класса для заданного объекта</brief>
///     <args>
///       <arg name="object" brief="объект" />
///     </args>
///     <details>
///       <p>Эквивалентно Core_Types::class_name_for($object, false).</p>
///     </details>
///     <body>
  static public function real_class_name_for($object) { return self::class_name_for($object, false); }
///     </body>
///   </method>

///   <method name="module_name_for" returns="string" scope="class">
///     <brief>Возвращает имя модуля для заданного объекта</brief>
///     <args>
///       <arg name="object" brief="объект" />
///     </args>
///     <body>
  static public function module_name_for($object) {
    return preg_replace('{\.[^.]+$}', '', self::class_name_for($object, true));
  }
///     </body>
///   </method>

///   <method name="reflection_for" returns="mixed" scope="class">
///     <brief>Возвращает reflection для заданного объекта или класса</brief>
///     <args>
///       <arg name="object" brief="объект" />
///     </args>
///     <details>
///       <p>В качестве параметра метода может быть использован экземпляр объекта или имя класса,
///          в том числе записанное в модульной нотации.</p>
///     </details>
///     <body>
  static public function reflection_for($object) {
    if (Core_Types::is_string($object))
      return new ReflectionClass(self::real_class_name_for($object));

    if (Core_Types::is_object($object))
      return new ReflectionObject($object);

    throw new Core_InvalidArgumentTypeException('object', $object);
  }
///     </body>
///   </method>

///   <method name="class_hierarchy_for" returns="array">
///     <brief>Возвращает список классов, составляющих иерархию наследования для данного объекта.</brief>
///     <args>
///       <arg name="object" type="object|string" brief="объект" />
///       <arg name="use_virtual_names" type="boolean" default="false" brief="признак необходимости использования виртуальных имен" />
///     </args>
///     <body>
  static public function class_hierarchy_for($object, $use_virtual_names = false) {
    $class = is_string($object) ? str_replace('.', '_', $object) : get_class($object);

    if ($use_virtual_names) {
      $r = array(str_replace('_', '.', $class));
      foreach (class_parents($class) as $c) $r[] = str_replace('_', '.', $c);
      return $r;
    } else {
      return array_merge(array($class), array_keys(class_parents($class)));
    }
  }
///     </body>
///   </method>

///   <method name="class_exists" returns="boolean" scope="class">
///     <brief>Проверяет существует ли класс с заданным именем</brief>
///     <args>
///       <arg name="name" type="string" brief="имя класса" />
///     </args>
///     <details>
///       <p>Допускаются как реальные, таки виртуальные имена.</p>
///     </details>
///     <body>
  static public function class_exists($name) {
    return class_exists(self::class_name_for((string) $name, false));
  }
///     </body>
///   </method>

  static public function is_callable($value) {
    return $value instanceof Core_Call || is_callable($value);
  }

///   </protocol>
}
/// </class>


/// <class name="Core.Arrays" stereotype="utility">
///   <brief>Набор методов для работы с массивами</brief>
class Core_Arrays {

///   <protocol name="quering">

///   <method name="keys" returns="array" scope="class">
///     <brief>Возвращает массив ключей заданного массива</brief>
///     <args>
///       <arg name="array" type="array" brief="массив" />
///     </args>
///     <details>
///       <p>Обертка над array_keys().</p>
///     </details>
///     <body>
  static public function keys(array &$array) { return array_keys($array); }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="extracting">

///   <method name="shift" returns="mixed" scope="class">
///     <brief>Выбирает первый элемент массива</brief>
///     <args>
///       <arg name="array" type="array" brief="массив" />
///     </args>
///     <details>
///       <p>Обертка над array_shift().</p>
///     </details>
///     <body>
  static public function shift(array &$array) { return array_shift($array); }
///     </body>
///   </method>

///   <method name="pop" returns="mixed" scope="class">
///     <brief>Выбирает последний элемент массива</brief>
///     <args>
///       <arg name="array" type="array" />
///     </args>
///     <details>
///       <p>Обертка над array_pop().</p>
///     </details>
///     <body>
  static public function pop(array &$array) { return array_pop($array); }
///     </body>
///   </method>

///   <method name="pick" returns="mixed" scope="class">
///     <brief>Выбирает из массива значение с заданным ключом</brief>
///     <args>
///       <arg name="array" type="array" brief="массив" />
///       <arg name="key" brief="ключ" />
///       <arg name="default" default="null" brief="значение по умолчанию" />
///     </args>
///     <details>
///       <p>Выбранный элемент удаляется из массива. В случае, если элемент отсутствует,
///          возврашается значение по умолчанию.</p>
///     </details>
///     <body>
  static public function pick(array &$array, $key, $default = null) {
    if (isset($array[$key])) {
      $result = $array[$key];
      unset($array[$key]);
      return $result;
    } else
      return $default;
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="transforming">

///   <method name="reverse" returns="array" scope="class">
///     <brief>Изменяет порядок следования элементов в массиве на обратный.</brief>
///     <args>
///       <arg name="array" type="array" brief="массив" />
///       <arg name="preserve_keys" type="boolean" default="false" brief="флаг" />
///     </args>
///     <details>
///       <p>Обертка над array_reverse.</p>
///     </details>
///     <body>
  static public function reverse(array $array, $preserve_keys = false) {
    return array_reverse($array, (boolean) $preserve_keys);
  }
///     </body>
///   </method>

///   <method name="flatten" returns="array" scope="class">
///     <brief>Объединяет массив массивов в единый линейный массив.</brief>
///     <args>
///       <arg name="array" type="array" brief="исходный массив" />
///     </args>
///     <body>
  static public function flatten(array $array) {
    $res = array();
    foreach ($array as $item) $res = self::merge($res, (array) $item);
    return $res;
  }
///     </body>
///   </method>

///   <method name="map" returns="array" scope="class">
///     <brief>Выполняет пользовательскую функцию над всеми элементами массива.</brief>
///     <args>
///       <arg name="lambda" type="string" brief="лямбда" />
///       <arg name="array" type="array" brief="массив" />
///     </args>
///     <details>
///       <p>В качестве параметра lambda должен быть передан пользовательский код, помещаемый в
///          lambda-функцию с помощью create_function. Значение текущего элемента массива доступно
///          в этом коде в виде переменной $x.</p>
///     </details>
///     <body>
// TODO: not only lambda functions
// TODO: $x -> $v
  static public function map($lambda, &$array) {
    return array_map(create_function('$x', $lambda), $array);
  }
///     </body>
///   </method>

///   <method name="merge" returns="array" scope="class">
///     <brief>Выполняет объединение двух массивов</brief>
///     <args>
///       <arg name="what" type="array" brief="массив" />
///       <arg name="with" type="array" brief="массив" />
///     </args>
///     <details>
///       <p>Обертка над Core_Arrays::merge().</p>
///     </details>
///     <body>
  static public function merge(array $what, array $with) { return array_merge($what, $with); }
///     </body>
///   </method>

///   <method name="deep_merge_update" returns="array">
///     <brief>Выполняет рекурсивное объединение массивов</brief>
///     <args>
///       <arg name="what" type="array" brief="массив" />
///       <arg name="with" type="array" brief="массив" />
///     </args>
///     <details>
///       <p>В PHP реализована встроенная функций array_merge_recursive, однако эта функция не
///          выполняет слияние для элементов с числовым индексом, выполняя вместо этого
///          объединение. Этот метод выполняет слиение вне зависимости от типа ключа.
///          Если в массивах встретились два значения с одинаковым индексом, то происходит замена.
///       </p>
///     </details>
///     <body>
  static public function deep_merge_update(array $what, array $with) {
    foreach (array_keys($with) as $k)
      $what[$k] = (isset($what[$k]) && is_array($what[$k]) && is_array($with[$k])) ?
        self::deep_merge_update($what[$k], $with[$k]) : $with[$k];
    return $what;
  }
///     </body>
///   </method>

///   <method name="deep_merge_update" returns="array">
///     <brief>Выполняет рекурсивное объединение массивов</brief>
///     <args>
///       <arg name="what" type="array" brief="массив" />
///       <arg name="with" type="array" brief="массив" />
///     </args>
///     <details>
///       <p>В PHP реализована встроенная функций array_merge_recursive, однако эта функция не
///          выполняет слияние для элементов с числовым индексом, выполняя вместо этого
///          объединение. Этот метод выполняет слиение вне зависимости от типа ключа.
///          Если в массивах встретились два значения с одинаковым индексом, то происходит объединение в массив.
///       </p>
///     </details>
///     <body>
  static function deep_merge_append(array $what, array $with) {
    foreach (array_keys($with) as $k) {
      $what[$k] = (isset($what[$k]) && is_array($what[$k]) && is_array($with[$k])) ?
        self::deep_merge_append($what[$k], $with[$k]) :
        (isset($what[$k]) ? array_merge((array) $what[$k], (array) $with[$k]) : $with[$k]);
    }
    return $what;
  }
///     </body>
///   </method>

///   <method name="deep_merge_inplace" scope="class">
///     <brief>Аналог deep_merge_update с передачей основного массива по ссылке</brief>
///     <args>
///       <arg name="what" type="array" brief="массив" />
///       <arg name="with" type="array" brief="массив" />
///     </args>
///     <body>
  static public function deep_merge_update_inplace(array &$what, array $with) {
    foreach (array_keys($with) as $k) {
      if (isset($what[$k]) && is_array($what[$k]) && is_array($with[$k]))
        self::deep_merge_update_inplace($what[$k], $with[$k]);
      else
        $what[$k] = $with[$k];
    }
  }
///     </body>
///   </method>

///   <method name="update" scope="class">
///     <brief>Обновление  существующих значений массива из другого массива</brief>
///     <args>
///       <arg name="what" type="array" brief="основной массив" />
///       <arg name="with" type="array" brief="массив обновлений" />
///     </args>
///     <details>
///       <p>Метод выполняет обновление массива $what значениями из массива $with при условии, что
///          обновляемый элемент уже был в массиве ранее. Метод работает только с элементами первого
///          уровня, без рекурсии.</p>
///     </details>
///     <body>
  static public function update(array &$what, array $with) {
    foreach ($with as $k => &$v) if (array_key_exists($k, $what)) $what[$k] = $with[$k];
  }
///     </body>
///   </method>

///   <method name="expand" scope="class">
///     <brief>Дополнение массива значениями, отсутствующими в нем</brief>
///     <args>
///       <arg name="what" type="array" brief="основной массив" />
///       <arg name="with" type="array" brief="массив дополнений" />
///     </args>
///     <details>
///       <p>Метод выполняет дополнение массива $what, то есть добавляет в него только элементы
///          массива with, ключи которых отсутствует в исходном массиве. Метод работает только с
///          элементами первого уровня, без рекурсии.</p>
///     </details>
///     <body>
  static public function expand(array &$what, array $with) {
    foreach ($with as $k => &$v) if (!array_key_exists($k, $what)) $what[$k] = $with[$k];
  }
///     </body>
///   </method>

///   <method name="join_with" returns="string" scope="class">
///     <brief>Выполняет конкатенацию элементов массива с использованием заданного разделителя</brief>
///     <args>
///       <arg name="delimiter" type="string" brief="разделитель" />
///       <arg name="array" type="array" brief="массив значений"  />
///     </args>
///     <details>
///       <p>Обертка над implode.</p>
///     </details>
///     <body>
  static public function join_with($delimiter, array $array) { return implode($delimiter, $array); }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="searching">

///   <method name="search" returns="mixed" scope="class">
///     <brief>Выполняет поиск элемента массива</brief>
///     <args>
///       <arg name="needle" brief="искомое значение" />
///       <arg name="heystack" type="array" brief="массив" />
///       <arg name="string" type="boolean" default="false" brief="признак контроля типов значений" />
///     </args>
///     <details>
///       <p>Обертка над array_search().</p>
///     </details>
///     <body>
  static public function search($needle, array &$haystack, $strict = false) {
    return array_search($needle, $haystack, (boolean) $strict);
  }
///     </body>
///   </method>

///   <method name="contains" returns="boolean" scope="class">
///     <brief>Проверяет присутствие элемента $value в массиве</brief>
///     <args>
///       <arg name="array" type="array" brief="массив" />
///       <arg name="value" brief="значение" />
///     </args>
///     <details>
///       <p>Метод идентичен следующему выражению:</p>
///       <code>
/// array_search($value, $array) !== false;
///       </code>
///     </details>
///     <body>
  static public function contains(array &$array, $value)  {
    return array_search($value, $array) !== false;
  }
///     </body>
///   </method>

  //TODO: рфекторинг и вынесение в отдельный модуль
  static public function create_tree($flat, $options = array()) {
    Core::load('Tree');
    return Tree::create_tree($flat, $options);
  }

///   </protocol>
}
/// </class>

/// </module>
