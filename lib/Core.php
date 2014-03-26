<?php
/**
 * Core
 * 
 * Загрузчик модулей и вспомогательные утилиты.
 * 
 * <p>Модуль Core реализует стандартный механизм динамической подгрузки  остальных модулей
 * библиотеки, который позволяет разделить имя модуля и физический путь к файлы, содержащему
 * его код, а также исключить повторную загрузку модуля.</p>
 * Файлы модулей библиотеки
 * <p>Модуль библиотеки представляет собой набор именованных особым  образом классов,
 * функционально связанных между собой. Физически модуль представляет собой один php-файл --
 * файл модуля.  Этот подход отличается от подхода, принятого во многих  PHP-фреймворках,
 * где принято соглашение один класс -- один файл.</p>
 * <p>Каждый модуль имеет свое уникальное имя, состоящее из отдельных  частей, отражающих
 * положение модулей в общей иерархии, при этом в качестве разделителя используется
 * точка.</p>
 * <p>Со стороны файловой системы иерархия имен отражается в виде набора вложенных каталогов.
 * Например, модулю DB соответствует файл lib/DB.php, модулю DB.ORM -- файл lib/DB/ORM.php,
 * и так далее.</p>
 * <p>Отметим, что для разных префиксов имен модулей могут быть указаны различные каталоги
 * файловой системы.</p>
 * Структура модуля
 * <p>Модули решают проблему пространств имен классов. До версии 5.3 в PHP не было пространств
 * имен, да и реализация в 5.3 не отличается большим изяществом. В настоящее время фреймворк
 * не использует пространства имен в реализации 5.3, но будет их использовать после
 * того, как эта версия получит достаточное распространение. Сейчас задача решается
 * традиционным для PHP способом -- с помощью префиксов имен классов.</p>
 * <p>Имена всех классов, входящих в модуль, используют в качестве префикса имя модуля, при
 * этом логический разделитель "точка" заменяется на подчеркивание.</p>
 * <p>Каждый модуль должен включать также собственно класс модуля, имя этого класса должно
 * совпадать с именем модуля с учетом замены разделителей. Класс модуля может содержать
 * только статические методы, его экземпляр никогда не создается. Кроме того, класс должен
 * удовлетворять следуюшим требованиям:</p>
 * <ul><li>имплементировать интерфейс Core.ModuleInterface;</li>
 * <li>определять строковую константу VERSION, содержащую версию модуля.</li>
 * </ul><p>Помимо этого, класс модуля может содержать набор фабричных методов для создания
 * экземпляров классов, предназначенных для использования снаружи модуля, а также
 * различные вспомогательные методы. Следуя практике, распространенной в модулях языка Perl,
 * класс модуля может реализовывать упрощенный процедурный фасад поверх объектного
 * интерфейса модуля.</p>
 * <p>Модуль также может реализовывать статический метод initialize(). Если такой метод
 * присутствует в классе модуля, он будет вызван после его загрузки.</p>
 * <p>Таким образом, модуль имеет следующую структуру:</p>
 * <code>
 * class Sample implements Core_ModuleInterface {
 * const VERSION = '0.1.0';
 * 
 * static public function PublicClass1() { return new Sample_PublicClass1(); }
 * }
 * 
 * class Sample_PublicClass1 { ... }
 * 
 * class Sample_PrivateClass1 { ... }
 * </code>
 * Модули приложения
 * <p>Часто возникает необходимость объединения модулей под одной версией. Например, если
 * пользовательское приложение состоит их нескольких модулей, как правило, нет необходимости
 * ведения номеров версий для каждой части приложения.</p>
 * <p>В этом случае, для того, чтобы удовлетворить требования, предъявляемые к классу модуля,
 * рекомендуется реализовать собственный интерфейс, унаследованный от Core_ModuleInterface,
 * и определить константу VERSION в нем. При этом классы модуля приложений должны
 * имплементировать этот интерфейс.</p>
 * <p>Например:</p>
 * <code>
 * interface Sample_ModuleInterface extends Core_ModuleInterface {
 * const VERSION = '0.1.0';
 * }
 * 
 * class Sample_Module1 implements Sample_ModuleInterface {  ...  }
 * class Sample_Module2 implements Sample_ModuleInterface {  ...  }
 * </code>
 * Загрузка модуля
 * <p>Модуль Core является единственным модулем, который необходимо загрузить с помощью
 * вызова include(). После загрузки Core необходимо вызвать статический метод
 * Core::initialize(), который создаст экземпляр загрузчика. Необходимость в явном вызове
 * этого метода объясняется возможностью передачи в него параметров конфигурации
 * загрузчика.</p>
 * <p>После вызова Core::initialize() любой модуль фреймворка может быть загружен с помощью
 * вызова Core::load(). Например:</p>
 * <code>
 * include('lib/Core.php');
 * Core::initialize();
 * Core::load('DB.ORM', 'Mail.Message', 'CLI');
 * </code>
 * Конфигурирование модулей
 * <p>Модули могут быть параметризованы набором опций. Для указания значений опций
 * необходимо использовать вызов Core::configure($module, $config). Вызов необходимо
 * выполнить перед загрузкой модуля. Поскольку модули могут подгружать друг друга,
 * лучше всего делать это после вызова Core::initialize(). Массив опций передается в
 * качестве параметра методу initialize() при загрузке модуля.</p>
 * <p>Пример конфигурирования модуля:</p>
 * <code>
 * Core::configure('DB.ORM.Assets', array('root_path' => 'my/path'));
 * Core::load('File.Assets');
 * </code>
 * Вспомогательные классы
 * <p>Помимо загрузчика модулей ядро содержит набор классов, содержащих статические методы,
 * дублирующие многие встроенные функции PHP. Некоторые такие методы делают использование
 * встроенных функций более удобным, другие -- позволяют сделать код более наглядным.</p>
 * <p>Первоначально идея была в группировке наиболее употребимых функций в классы-утилиты и
 * использование только этих функций в библиотечном коде. Таким образом, можно было бы
 * гарантировать отсутствие отрицательных эффектов от смены поведения встроенных функций при
 * смене версий языка и создать иллюзию присутствия упорядоченного встроенного API.</p>
 * <p>На практике, к сожалению, использование дополнительного вызова не всегда желательно из
 * соображений производительности. Поэтому в критичных участках кода приходится использовать
 * встроенные функции. Тем не менее, в клиентских приложениях разумное использование этих
 * может сделать код более эстетичным :)</p>
 * Стандартные интерфейсы
 * <p>Помимо вспомогательных классов, модуль Core определяет набор стандартных интерфейсов,
 * определяющих протоколы доступа к свойствам объектов, индексированного доступа и т.д.
 * Явная имплементация этих интерфейсов в пользовательских классах позволяет не забыть о
 * необходимости реализации тех или иных методов.</p>
 * 
 * @package Core
 * @version 0.2.13
 */

/**
 * Модуль Core
 * 
 * <p>Core -- единственный модуль, который необходимо загружать с помощью вызова include(). Все
 * остальные модули необходимо подгружать с помощью Core::load().</p>
 * 
 * @package Core
 */
class Core implements Core_ModuleInterface {

  const MODULE        = 'Core';
  const VERSION       = '0.3.0';
  const RELEASE       =  20000;
  const PATH_VARIABLE = 'TAO_PATH';

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


/**
 * Выполняет инициализацию модуля.
 * 
 * @param array $config
 */
  static public function initialize(array $config = array()) {
    self::$base_dir = getcwd();
    self::$start_time = microtime();
    $loader_opts = self::parse_environment_paths();
    if (isset($config['loader']))
      $loader_opts = Core_Arrays::merge($loader_opts, $config['loader']);
    self::$loader = new Core_ModuleLoader();
    self::$loader->paths($loader_opts);
    self::options($config);
    Core::load('Config');
    if (is_array(Config::modules())) {
      self::configure(Config::modules());
    }
    self::init_autoload();
    self::init_module_cache();
    self::init_deprecated();
  }

  public static function tao_lib_dir()
  {
    return __DIR__;
  }

  public static function tao_dir()
  {
    return realpath(__DIR__ . '/..');
  }

  public static function tao_config_dir()
  {
    return self::tao_dir() . '/config';
  }

  public static function tao_deprecated_file($file)
  {
    return self::tao_dir() . '/deprecated/' . $file;
  }

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



/**
 * Возвращает экземпляр загрузчика модулей
 * 
 * @return Core_ModuleLoader
 */
  static public function loader($instance = null) {
    if ($instance instanceof Core_ModuleLoaderInterface)
      self::$loader = self::$loader->merge($instance);
    return self::$loader;
  }



/**
 * Выполняет загрузку модулей
 * 
 */
  static public function load() {
    self::push_dir();
    if (Core::option('spl_aggressive_autoload')) return;
    foreach (func_get_args() as $module) self::$loader->load($module);
    self::pop_dir();
  }

/**
 * Выполняет конфигурирование модуля
 * 
 * @param  $module
 * @param array $config
 */
  static public function configure($module, array $config = array()) {
    foreach ((Core_Types::is_array($module) ? $module : array($module => $config)) as $k => $v)
      self::$loader->configure($k, $v);
  }

/**
 * Проверяет, был ли уже загружен модуль
 * 
 * @param string $module
 * @return boolean
 */
  static public function is_loaded($module) { return self::$loader->is_loaded($module); }



/**
 * Сравнивает две переменные произвольного типа
 * 
 * @param  $a
 * @param  $b
 * @return boolean
 */
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

/**
 * Создает объект класса stdClass
 * 
 * @param  $values
 * @return stdClass
 */
  static public function object($values = array()) {
    $r = new stdClass();
    foreach ($values as $k => $v) $r->$k = $v;
    return $r;
  }

/**
 * Создает объект класса ArrayObject
 * 
 * @param  $values
 * @return ArrayObject
 */
  static public function hash($values = array()) { return new ArrayObject((array) $values); }

/**
 * @param  $target
 * @param string $method
 * @return Core_Call
 */
  static public function call($target, $method) {
    $args = func_get_args();
    return new Core_Call(array_shift($args), array_shift($args), $args);
  }

/**
 * @param mixen $call
 */
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

/**
 * Обеспечивает возможность построения цепочки вызовов для переданного объекта.
 * 
 * @param  $object
 * @return Object
 */
  static public function with($object) { return $object; }

/**
 * Тоже что и with, только возвращает клон объекта
 * 
 * @param  $object
 * @return Object
 */
  static public function with_clone($object) { return clone $object; }

/**
 * Возвращает элемент индексируемого объекта по его индексу
 * 
 * @param  $object
 * @param  $index
 * @return mixed
 */
  static public function with_index($object, $index) { return $object[$index]; }

/**
 * Возвращает значение свойства объекта
 * 
 * @param  $object
 * @param string $attr
 * @return mixed
 */
  static public function with_attr($object, $attr) { return $object->$attr; }

/**
 * Возвращает альтернативу для null-значения
 * 
 * @param  $value
 * @param  $alternative
 * @return mixed
 */
  static public function if_null($value, $alternative) {
    return $value === null ? $alternative : $value;
  }

/**
 * Возвращает альтернативу для неистинного значения
 * 
 * @param  $value
 * @param  $alternative
 * @return mixed
 */
  static public function if_not($value, $alternative) {
    return $value ? $value : $alternative;
  }

/**
 * Возвращает альтернативу для ложного значения
 * 
 * @param  $value
 * @param  $alternative
 * @return mixed
 */
  static public function if_false($value, $alternative) {
    return $value === false ? $alternative : $value;
  }

/**
 * Возвращает альтернативу отсутствующему индексированному значению
 * 
 * @param  $values
 * @param mixed $index
 * @param mixed $alternative
 * @return mixed
 */
  static public function if_not_set($values, $index, $alternative) {
    return isset($values[$index]) ? $values[$index] : $alternative;
  }

//TODO: вынести функционал make amake в отдельный модуль
/**
 * Создает объект заданного класса
 * 
 * @param string $class
 * @return object
 */
  static public function make($class) {
    $args = func_get_args();
    return self::amake($class,array_slice($args, 1));
  }

/**
 * Создает объект заданного класса с массивом значений параметров конструктора
 * 
 * @param string $class
 * @param array $parms
 * @return object
 */
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

/**
 * Выполняет нормализацию аргументов
 * 
 * @param array $args
 * @return array
 */
  static public function normalize_args(array $args) {
    return (count($args) == 1 && isset($args[0]) && is_array($args[0])) ? $args[0] : $args;
  }

/**
 * Выполняет разбор переменной окружения TAO_PATH
 * 
 * @return array
 */
  static private function parse_environment_paths() {
    $result = array();
    if (($path_var = getenv(self::PATH_VARIABLE)) !== false)
      foreach (Core_Strings::split_by(';', $path_var) as $rule)
        if ($m = Core_Regexps::match_with_results('{^([-A-Za-z0-9*][A-Za-z0-9_.]*):(.+)$}', $rule))
          $result[$m[1]] = $m[2];
    return $result;
  }

}



interface Core_InvokeInterface {
  public function invoke($args = array());
}


/**
 * @package Core
 */
class Core_Call implements Core_InvokeInterface {

  private $call;
  private $args;
  private $cache = array();
  private $enable_cache = false;
  private $autoload;


/**
 * @param  $target
 * @param string $method
 * @param array $args
 */
  public function __construct($target, $method, array $args = array(), $autoload = true) {
    if (is_string($target)) {
      $target = Core_Types::real_class_name_for($target);
    }
    $this->autoload = $autoload;
    $this->call = array($target, (string) $method);
    $this->args = $args;
  }



/**
 */
  public function update_args($args) {
    $this->args = array_merge($this->args, $args);
    return $this;
  }

  public function cache($v = true) {
    $this->enable_cache = $v;
    return $this;
  }



/**
 * @return mixed
 */
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

  protected function get_args($values = array()) {
    return array_merge($this->args, (array) $values);
  }

}


/**
 * Интерфейс доступа к свойствам объекта
 * 
 * <p>В PHP поддерживается набор специальных методов, обеспечивающих динамическое
 * переопределение свойств объектов. Помимо реализации динамических свойств, реализация
 * этих методов позволяет контролировать внешний доступ к тем или иным свойствам
 * объекта.</p>
 * <p>Имплементация объектом интерфейса Core.PropertyAccessInterface с одной стороны
 * говорит о наличии у него доступных снаружи свойств, а с другой - гарантирует реализацию
 * всех необходимых методов и, таким образом, непротиворечивость в работе со свойствами
 * (в частности, корректную отработу isset(), unset() и т.д.).</p>
 * 
 * @package Core
 */
interface Core_PropertyAccessInterface {

/**
 * Возвращает значение свойства
 * 
 * @param string $property
 * @return mixed
 */
  public function __get($property);

/**
 * Устанавливает значение свойства
 * 
 * @param string $property
 * @param  $value
 * @return mixed
 */
  public function __set($property, $value);

/**
 * Проверяет установку значения свойства
 * 
 * @param string $property
 * @return boolean
 */
  public function __isset($property);

/**
 * Удаляет свойство объекта
 * 
 * @param string $property
 */
  public function __unset($property);

}


/**
 * Интерфейс доступа к индексированным свойствам объекта
 * 
 * <p>На данный момент интерфейс наследуется от ArrayAccess и не расширяет его.</p>
 * 
 * @package Core
 */
interface Core_IndexedAccessInterface extends ArrayAccess {}


/**
 * Counting-интерфейс
 * 
 * <p>Пока просто наследуется от Countable без расширения, заведен для единообразия.</p>
 * 
 * @package Core
 */
interface Core_CountInterface extends Countable {}

/**
 * Интерфейс, определяющий наличие динамической диспетчеризации методов объекта.
 * 
 * <p>Пока включает в себя стандартный PHP-метод __call, однако может быть расширен, например
 * функцией, определяющей возможность вызова того или иного метода.</p>
 * 
 * @package Core
 */
interface Core_CallInterface {

/**
 * Осуществляет динамическую диспетчеризацию вызовов
 * 
 * @param string $method
 * @param array $args
 */
  public function __call($method, $args);

}


/**
 * Интерфейс клонирования
 * 
 * <p>Пока включает только стандартный метод __clone().</p>
 * 
 * @package Core
 */
interface Core_CloneInterface {

/**
 */
  public function __clone();

}


/**
 * Интерфейс сравнения
 * 
 * <p>В PHP нет стандартного интерфейса для выполнения операции сравнения. Вопрос реализации
 * такого интерфейса -- дело тонкое, но бывают ситуации, когда даже кривая реализация лучше
 * ее отсутствия.</p>
 * <p>Интерфейс декларирует метод equals(), который вызывается при выполнении сравнения с
 * помощью вызова Core::equals().</p>
 * 
 * @package Core
 */
interface Core_EqualityInterface {

/**
 * @param  $to
 * @return boolean
 */
  public function equals($to);

}


/**
 * Интерфейс получения строкового представления объекта
 * 
 * <p>Реализация этого интерфейса определяет возможность приведения объекта к строке.</p>
 * <p>Интерфейс декларирует стандартный метод __toString(), а также его синоним as_string() для
 * выполнения приведения к строке в явном виде. Реализация должна гарантировать полное
 * совпадение результатов вызова этих методов! Таким образом, для объекта, реализуюшего
 * интерфейс, следующие выражения эквивалентны:</p>
 * <code>
 * $str = (string) $object;
 * $str = $object->as_string();
 * </code>
 * 
 * @package Core
 */
interface Core_StringifyInterface {

/**
 * Возвращает строковое представление объекта
 * 
 * @return string
 */
  public function as_string();

/**
 * Возвращает строковое представление объекта
 * 
 * @return string
 */
  public function __toString();

}


/**
 * Интерфейс класса модуля
 * 
 * <p>Каждый модуль должен содержать класс с именем, совпадающим с классом модуля, и
 * имплементирующим этот интерфейс, а также содержащий константу VERSION.</p>
 * <p>Класс также может имплементировать статический метод initialize(), вызываемый
 * загрузчиком после загрузки модуля.</p>
 * 
 * @package Core
 */
interface Core_ModuleInterface {}


/**
 * Интерфейс класса конфигурируемого модуля
 * 
 * <p>В случае, если модуль поддерживает конфигурирование, это может быть отражено явно
 * путем реализации этого интерфейса. В этом случае класс модуля реализует дополнительную
 * функциональность:</p>
 * <ol><li>метод initialize() должен принимать обязательный массив значений опций, по
 * умолчанию пустой;</li>
 * <li>метод options() реализует получение и установку списка опций;</li>
 * <li>метод option() реализует получение и установку единственной опции.</li>
 * </ol>
 * 
 * @package Core
 */
interface Core_ConfigurableModuleInterface extends Core_ModuleInterface {


/**
 * Выполняет инициализацию модуля
 * 
 * @param array $options
 */
  static public function initialize(array $options = array());



/**
 * Устанавливает значения списка опций, возвращает список значений всех опций
 * 
 * @param array $options
 * @return mixed
 */
  static public function options(array $options = array());

/**
 * Устанавливает опцию или возвращает ее значение
 * 
 * @param string $name
 * @param  $value
 * @return mixed
 */
  static public function option($name, $value = null);

}


/**
 * @package Core
 */
abstract class Core_AbstractConfigurableModule implements Core_ConfigurableModuleInterface {

  protected static $options = array();
  

/**
 * Выполняет инициализацию модуля
 * 
 * @param array $options
 */
  static public function initialize(array $options = array()) {
    return self::options($options);
  }



/**
 * Устанавливает значения списка опций, возвращает список значений всех опций
 * 
 * @param array $options
 * @return mixed
 */
  static public function options(array $options = array()) {
    Core_Arrays::deep_merge_update_inplace(self::$options, $options);
    return self::$options;
  }

/**
 * Устанавливает опцию или возвращает ее значение
 * 
 * @param string $name
 * @param  $value
 * @return mixed
 */
  static public function option($name, $value = null) {
    if (is_null($value))
      return self::$options[$name];
    return self::$options[$name] = $value;
  }


}

/**
 * Базовый класс исключения
 * 
 * <p>Этот класс предназначен для использования в качестве базового для все всех классов
 * исключений фреймворка, а также для классов исключений, определяемых пользовательскими
 * приложениями.</p>
 * <p>На данный момент дополнительной функциональностью, реализуемой классом, является
 * ограничение доступа "только на чтение" для свойств класса. При этом подразумевается, что
 * объект исключения сохраняет дополнительную информацию об ошибке в виде набора внутренних
 * свойств, передаваемых в качестве параметров конструктора класса.</p>
 * 
 * @package Core
 */
class Core_Exception extends Exception implements Core_PropertyAccessInterface {


/**
 * Возвращает значение свойства
 * 
 * @param string $property
 * @return mixed
 */
  public function __get($property) {
    return isset($this->$property) ? $this->$property : null;
  }

/**
 * Устанавливает значение свойства
 * 
 * @param string $property
 * @param  $value
 * @return mixed
 */
  public function __set($property, $value) {
    throw isset($this->$property) ?
      new Core_ReadOnlyPropertyException($property) :
      new Core_MissingPropertyException($property);
  }

/**
 * Проверяет, установлено ли значение свойства
 * 
 * @param string $property
 * @return boolean
 */
  public function __isset($property) { return isset($this->$property); }

/**
 * Удаляет значение свойства
 * 
 * @param string $property
 */
  public function __unset($property) {
    throw isset($this->$property) ?
      new Core_ReadOnlyPropertyException($property) :
      new Core_MissingPropertyException($property);
  }

}


/**
 * Базовый класс исключений, связанных с контролем типов
 * 
 * @package Core
 */
class Core_TypeException extends Core_Exception {}


/**
 * Исключение: нереализованный метод
 * 
 * @package Core
 */
class Core_NotImplementedException extends Core_Exception {}

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


/**
 * Исключение: некорректный тип аргумента
 * 
 * <p>Класс предназначен для случаев проверки типов аргументов методов при невозможности
 * применения статической типизации.</p>
 * <p>Свойства:</p>
 * arg_name
 * имя аргумента
 * arg_type
 * тип аргумента
 * 
 * @package Core
 */
class Core_InvalidArgumentTypeException extends Core_TypeException {

  protected $arg_name;
  protected $arg_type;


/**
 * Конструктор
 * 
 * @param string $name
 * @param  $arg
 */
  public function __construct($name, $arg) {
    $this->arg_name = (string) $name;
    $this->arg_type = (string) gettype($arg);
    parent::__construct("Invalid argument type for '$this->arg_name': ($this->arg_type)");
  }

}


/**
 * @package Core
 */
class Core_InvalidArgumentValueException extends Core_Exception {

  protected $arg_name;
  protected $arg_value;


/**
 * @param string $name
 * @param  $value
 */
  public function __construct($name, $value) {
    $this->arg_name = (string) $name;
    $this->arg_value = $value;
    parent::__construct("Invalid argument value for '$this->arg_name': ($this->arg_value)");
  }

}


/**
 * Базовый класс исключения некорректного доступа к объекту
 * 
 * @package Core
 */
class Core_ObjectAccessException extends Core_Exception {}


/**
 * Исключение: обращение к несуществующему свойству объекта
 * 
 * <p>Исключение должно генерироваться при попытке обращения к несуществующему свойству объекта,
 * как правило при реализации интерфейса Core.PropertyAccessInterface.</p>
 * <p>Свойства:</p>
 * property
 * имя отсутствующего свойства
 * 
 * @package Core
 */
class Core_MissingPropertyException extends Core_ObjectAccessException {

  protected $property;


/**
 * Конструктор
 * 
 * @param string $property
 */
  public function __construct($property) {
    $this->property = (string) $property;
    parent::__construct("Missing property: $this->property");
  }

}


/**
 * Исключение: обращение к несуществующему индексу
 * 
 * <p>Исключение может генерироваться при обращении к несуществующему индексу объекта,
 * реализующего индексированный доступ (интерфейс Core.IndexedAccessInterface).
 * Альтернативная стратегия -- возврат некоторого значения по умолчанию.</p>
 * index
 * индекс
 * 
 * @package Core
 */
class Core_MissingIndexedPropertyException extends Core_ObjectAccessException {

  protected $index;


/**
 * Конструктор
 * 
 * @param  $index
 */
  public function __construct($index) {
    $this->index = (string) $index;
    parent::__construct("Missing indexed property for index $this->index");
  }

}


/**
 * Исключение: вызов несуществующего метода
 * 
 * <p>Исключение может генерироваться при попытке вызова отсутствующего метода объекта с
 * помощью динамической диспетчеризации (Core.CallInterface::__call()).</p>
 * method
 * имя метода
 * 
 * @package Core
 */
class Core_MissingMethodException extends Core_ObjectAccessException {

  protected $method;


/**
 * Конструктор
 * 
 * @param string $method
 */
  public function __construct($method) {
    $this->method = (string) $method;
    parent::__construct("Missing method: $this->method");
  }

}


/**
 * Исключение: попытка записи read-only свойства
 * 
 * <p>Исключение должно генерироваться при попытке записи свойства, доступного только для
 * чтения. В большинстве случаев необходимость в его использовании возникает при реализации
 * интерфейса Core.PropertyAccessInterface</p>
 * <p>Свойства:</p>
 * property
 * имя свойства
 * 
 * @package Core
 */
class Core_ReadOnlyPropertyException extends Core_ObjectAccessException {

  protected $property;


/**
 * Конструктор
 * 
 * @param string $property
 */
  public function __construct($property) {
    $this->property = (string) $property;
    parent::__construct("The property is read-only: $this->property");
  }
}


/**
 * Исключение: попытка записи read-only индексного свойства
 * 
 * <p>Класс аналогичен Core.ReadOnlyPropertyException, но предназначен для случаев обращения
 * по индексу (интерфейс Core.IndexedAccessInterface).</p>
 * <p>Свойства:</p>
 * index
 * индекс
 * 
 * @package Core
 */
class Core_ReadOnlyIndexedPropertyException extends Core_ObjectAccessException {

  protected $index;


/**
 * Конструктор
 * 
 * @param  $index
 */
  public function __construct($index) {
    $this->index = (string) $index;
    parent::__construct("The property is read-only for index: $this->index");
  }

}


/**
 * Класс исключения для объектов доступных только для чтения
 * 
 * @package Core
 */
class Core_ReadOnlyObjectException extends Core_ObjectAccessException {
  protected $object;


/**
 * Конструктор
 * 
 * @param  $object
 */
  public function __construct($object) {
    $this->object = $object;
    parent::__construct("Read only object");
  }

}


/**
 * Исключение: попытка удаления свойства объекта
 * 
 * <p>Существование этого исключения связано с противоречивой семантикой операции unset()
 * применительно к свойствам объектов. Оригинальная семантика -- удаление
 * public-свойства. В случае обеспечения доступа к свойствам через
 * Core.PropertyAccessInterface возможны две стратегии:</p>
 * <ul><li>присваивание свойству значения null;</li>
 * <li>генерирование исключения класса Core.UndestroayablePropertException.</li>
 * </ul><p>Свойства:</p>
 * property
 * имя свойства
 * 
 * @package Core
 */
class Core_UndestroyablePropertyException extends Core_ObjectAccessException {

  protected $property;


/**
 * Конструктор
 * 
 * @param string $property
 */
  public function __construct($property) {
    $this->property = (string) $property;
    parent::__construct("Unable to destroy property: $property");
  }

}

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


/**
 * Базовый класс исключений загрузчика модулей
 * 
 * @package Core
 */
class Core_ModuleException extends Core_Exception {}


/**
 * Исключение: модуль не найден
 * 
 * <p>Генерируется в случае, если закгрузчик не может найти файл, содержащий код модуля.</p>
 * <p>Свойства:</p>
 * module
 * имя загружаемого модуля
 * 
 * @package Core
 */
class Core_ModuleNotFoundException extends Core_ModuleException {

  protected $module;


/**
 * Конструктор
 * 
 * @param string $module
 * @param string $path
 */
  public function __construct($module, $path = '') {
    $this->module = (string) $module;
    parent::__construct("Module $this->module not found" . ($path ? " in $path" : ''));
  }

}


/**
 * Исключение: некорректный формат модуля
 * 
 * <p>Исключение генерируется в случае, если загрузчик нашел файл модуля, но модуль не
 * удовлетворяет следующим критериям:</p>
 * <ul><li>модуль должен содержать класс, имя которго соответствует имени модуля;</li>
 * <li>класс модуля должен имплементировать интерфейс Core.ModuleInterface;</li>
 * <li>класс модуля должен содержать константу VERSION.</li>
 * </ul><p>Свойства:</p>
 * module
 * имя модуля
 * 
 * @package Core
 */
class Core_InvalidModuleException extends Core_ModuleException {

  protected $module;


/**
 * Конструктор
 * 
 * @param string $module
 */
  public function __construct($module) {
    $this->module = (string) $module;
    parent::__construct("Invalid module: $this->module");
  }

}

//TODO: merge в Interface и общий метод Core::merge();
interface Core_ModuleLoaderInterface {
  public function configure($module, array $config);
  public function load($module);
  public function paths(array $paths = array());
  public function already_loaded($module);
  public function merge(Core_ModuleLoaderInterface $instance);
  public function is_loaded($module);
}


/**
 * Загрузчик модулей
 * 
 * <p>Объект этого класса создается в единственном экземпляре и используется неявно с помощью
 * вызовов Core::load(), Core::configure()  и т.д. Вам не придется создавать экземпляр этого
 * класса самостоятельно.</p>
 * 
 * @package Core
 */
class Core_ModuleLoader implements Core_ModuleLoaderInterface {

  protected $paths   = array(
  '-App' => '../app/lib',
  // '*' => '../tao/lib',
  );
  protected $configs = array();
  protected $loaded  = array('Core' => true);


/**
 * Конструктор
 * 
 * @param array $paths
 */
  public function __construct(array $paths = array()) {
    $this->paths['*'] = __DIR__;
    $this->paths($paths);
  }

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



/**
 * Устанавливает значения опций модуля
 * 
 * @param string $module
 * @param array $config
 * @return Core_ModuleLoader
 */
  public function configure($module, array $config) {
    $this->configs[$module] = isset($this->configs[$module])? array_merge_recursive( (array) $this->configs[$module] ,$config) : $config;
    return $this;
  }

/**
 * Подгружает модуль
 * 
 * @param string $module
 * @return Core_ModuleLoader
 */
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



/**
 * Проверяет загружен ли модуль
 * 
 * @param string $module
 * @return boolean
 */
  public function is_loaded($module) {
    return isset($this->loaded[(string) $module]);
  }

/**
 * Возвращает путь к файлу модуля
 * 
 * @param string $module
 * @return string
 */
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



/**
 * Выполняет непосредствунную загрузкку файла модуля
 * 
 * @param string $file
 * @param string $class_name
 * @param string $module
 * @return Core_ModuleLoader
 */
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

/**
 * Помечает модуль как загруженный
 * 
 * @param string $module
 * @return Core_ModuleLoader
 */
  protected function mark_as_loaded($module) {
    $this->loaded[$module] = true;
    return $this;
  }

/**
 * Проверяет, является ли заданный класс классом модуля
 * 
 * @param string $module_real_name
 * @return boolean
 */
  protected function is_module($module_real_name) {
    return in_array("Core_ModuleInterface",class_implements($module_real_name));
  }

}


/**
 * Класс обертка над встроенными функциями работы с регулярными выражениями
 * 
 * <p>Класс группирует функции для работы с регулярными выражениями в отдельное пространство
 * имен (исключительно из эстетических соображений), а также делает работу с некоторыми
 * функциями более удобной.</p>
 * 
 * @package Core
 */
class Core_Regexps {


/**
 * Сопоставляет строку с регулярным выражением
 * 
 * @param string $regexp
 * @param string $string
 * @return boolean
 */
  static public function match($regexp, $string) { return (boolean) preg_match($regexp, $string); }

/**
 * Сопоставляет строку с регулярным выражением, возвращает результат сопоставления
 * 
 * @param string $regexp
 * @param string $string
 * @return array
 */
  static public function match_with_results($regexp, $string) {
    $m = array();
    return preg_match($regexp, $string, $m) ? $m : false;
  }

/**
 * Сопоставляет строку с регулярным выражением, возвращает все результаты сопоставления
 * 
 * @param string $regexp
 * @param string $string
 * @param int $type
 * @return array
 */
  static public function match_all($regexp, $string, $type = PREG_PATTERN_ORDER) {
    $m = array();
    return preg_match_all($regexp, $string, $m, (int) $type) ? $m : false;
  }



/**
 * Выполняет квотинг строки для использования в качестве регулярного выражения
 * 
 * @param string $string
 * @return string
 */
  static public function quote($string) { return preg_quote($string); }



/**
 * Выполняет замену строк по регулярному выражению
 * 
 * @param string $regexp
 * @param string $replacement
 * @param string $source
 * @param int $limit
 * @return string
 */
  static public function replace($regexp, $replacement, $source, $limit = -1) {
    return preg_replace($regexp, $replacement, $source, (int) $limit);
  }

/**
 * Выполняет замену по регулярному выражению с использованием пользовательской функции
 * 
 * @param string $regexp
 * @param callback $callback
 * @param string $source
 * @param int $limit
 * @return string
 */
  static public function replace_using_callback($regexp, $callback, $source, $limit = -1) {
    return preg_replace_callback($regexp, $callback, $source, $limit);
  }

/**
 * Выполняет замену по регулярном выражению, возвращает количество замен
 * 
 * @param string $regexp
 * @param string $replacement
 * @param string $source
 * @param int $limit
 * @return int
 */
  static public function replace_ref($regexp, $replacement, &$source, $limit = -1) {
    $count = 0;
    $source = preg_replace($regexp, $replacement, $source, (int) $limit, $count);
    return $count;
  }



/**
 * Разбивает строку на подстроки по регулярному выражению
 * 
 * @param string $regexp
 * @param string $string
 * @param number $limit
 * @param number $flags
 * @return array
 */
  static public function split_by($regexp, $string, $limit = -1, $flags = 0) {
    return preg_split($regexp, $string, (int) $limit, (int) $flags);
  }

}


/**
 * Обертка над php-функциями для работы со строками
 * 
 * <p>Класс включает в себя набор функций для работы со строками. При этом используется явный
 * вызов функций модуля mbstring.</p>
 * <p>Поскольку мы работаем с UTF-8, модуль mbstring нужен практически всегда. Вместе с тем,
 * бывают ситуации, когда необходимо работать со строкой, как с последовательностью
 * байт, а не как с набором юникодных символов. Например, это может понадобиться при
 * обработке бинарных строк (со встроенными функциями для этого у PHP туго).</p>
 * <p>На данный момент принято некрасивое, но работающее решение ввести методы begin_binary() и
 * end_binary(), которые обеспечивают переход модуля mbstring в кодировку ASCII и выход из
 * нее. Соответственно, при необходимости использовать методы класса для обработки бинарных
 * данных соответствующий кусок кода необходимо выделить с помощью вызовов
 * begin_binary()/end_binary(), при этом вызовы могут быть вложенными.</p>
 * 
 * @package Core
 */
class Core_Strings {

  static protected $encodings = array();


/**
 * Переводит модуль в бинарный режим.
 * 
 */
  static public function begin_binary() {
    array_push(self::$encodings, mb_internal_encoding());
    mb_internal_encoding('ASCII');
  }

/**
 * Переводит модуля из бинарного режима в режим использования предыдущей кодировки.
 * 
 */
  static public function end_binary() {
    if ($encoding = array_pop(self::$encodings))
      mb_internal_encoding($encoding);
  }



/**
 * Объединяет набор строк в одну
 * 
 * @return string
 */
  static public function concat() {
    $args = func_get_args();
    return implode('', Core::normalize_args($args));
  }

/**
 * Объединяет строки с использованием разделителя
 * 
 * @return string
 */
  static public function concat_with() {
    $args = Core::normalize_args(func_get_args());
    return implode((string) array_shift($args), $args);
  }


/**
 * Возвращает подстроку
 * 
 * @param string $string
 * @param int $start
 * @param int $length
 * @return string
 */
// TODO: eliminate if
  static public function substr($string, $start, $length = null) {
    return $length === null ?
      mb_substr($string, $start) :
      mb_substr($string, $start, $length);
  }

/**
 * Выполняет замену в строке
 * 
 * @param string $string
 * @param string $what
 * @param string $with
 * @return string
 */
  static public function replace($string, $what, $with) {
    return str_replace($what, $with, $string);
  }

/**
 * Удаляет пробельные символы в конце строки
 * 
 * @param string $tail
 * @return string
 */
  static public function chop($tail) { return rtrim($tail); }

/**
 * Удаляет пробельные символы в начале и конце строки
 * 
 * @param string $string
 * @param string $chars
 * @return string
 */
  static public function trim($string, $chars = null) {
    return $chars ? trim($string, $chars) : trim($string);
  }

/**
 * Разбивает строку по пробелам
 * 
 * @param string $string
 * @return array
 */
  static public function split($string) {  return explode(' ', $string); }

/**
 * Разбивает строку по заданному разделителю
 * 
 * @param string $delimiter
 * @param string $string
 * @return array
 */
  static public function split_by($delimiter, $string) {
    return ($string === '') ? array() : explode($delimiter, $string);
  }



/**
 * Выполняет форматирование строки
 * 
 * @return sprintf
 */
  static public function format() {
    $args = func_get_args();
    return vsprintf(array_shift($args), $args);
  }



/**
 * Проверяет, начинается ли строка с заданной подстроки
 * 
 * @param string $string
 * @param string $head
 * @return boolean
 */
  static public function starts_with($string, $head) { return (mb_strpos($string, $head) === 0); }

/**
 * Проверяет заканчивается ли строка заданной подстрокой
 * 
 * @param string $string
 * @param string $tail
 * @return boolean
 */
  static public function ends_with($string, $tail) {
    $pos = mb_strrpos($string, $tail);
    if ($pos === FALSE) return FALSE;
    return ((mb_strlen($string) - $pos) == mb_strlen($tail));
  }

/**
 * Проверяет, содержит ли строка заданную подстроку
 * 
 * @param string $string
 * @param string $fragment
 * @return boolean
 */
  static public function contains($string, $fragment) {
    return ($fragment && (mb_strpos($string,  $fragment) !== false));
  }



/**
 * Приводит все символы строки к нижнему регистру
 * 
 * @param string $string
 * @return string
 */
  static public function downcase($string) { return mb_strtolower($string); }

/**
 * Приводит все символы строки к верхнему регистру.
 * 
 * @param string $string
 * @return string
 */
  static public function upcase($string) {
    return mb_strtoupper($string);
  }

/**
 * Приводит первый символ строки к верхнему регистру
 * 
 * @param string $string
 * @return string
 */
  static public function capitalize($string) {
    return mb_strtoupper(mb_substr($string, 0, 1)).mb_substr($string, 1);
  }

/**
 * Приводит первый символ строки к нижнему регистру
 * 
 * @param string $string
 * @return string
 */
  static public function lcfirst($string) {
    return mb_strtolower(mb_substr($string, 0, 1)).mb_substr($string, 1);
  }

/**
 * Аналог ucfirst, работающий с UTF8
 * 
 * @param string $string
 * @return string
 */
  static public function capitalize_words($string) {
    return preg_replace_callback(
      '{(\s+|^)(.)}u',
      create_function('$m', 'return $m[1].mb_strtoupper(mb_substr($m[2],0,1));'),
      $string);
  }

/**
 * Приводит идентификатор к виду CamelCase
 * 
 * @param string $string
 * @return string
 */
  static public function to_camel_case($string, $lcfirst = false) {
    $s = str_replace(' ', '', ucwords(str_replace('_', ' ', $string)));
    return $lcfirst ? strtolower(substr($s, 0, 1)).substr($s, 1) : $s;
  }



/**
 * Декодирует строку из base64
 * 
 * @param string $string
 * @return string
 */
  static public function decode64($string) { return base64_decode($string); }

/**
 * Кодирует строку в base64
 * 
 * @param string $string
 * @return string
 */
  static public function encode64($string) { return base64_encode($string); }

}


/**
 * Набор методов для работы с информацией о типах
 * 
 * @package Core
 */
class Core_Types {


/**
 * Проверяет, является ли переданное значение массивом
 * 
 * @param  $object
 * @return boolean
 */
  static public function is_array(&$object) { return is_array($object); }

/**
 * Проверяет, является ли переданное значение строкой
 * 
 * @param  $object
 * @return boolean
 */
  static public function is_string(&$object) { return is_string($object);  }

/**
 * Проверяет, является ли переданное значение числом
 * 
 * @param  $object
 * @return boolean
 */
  static public function is_number(&$object) { return is_numeric($object); }

/**
 * Проверяет является ли переданное значение объектом
 * 
 * @param  $object
 * @return boolean
 */
  static public function is_object(&$object) { return is_object($object); }

/**
 * Проверяет является ли переданное значение ресурсом
 * 
 * @param  $object
 * @return boolean
 */
  static public function is_resource(&$object) { return is_resource($object); }

/**
 * Проверяет является ли переданное значение итерируемым объектом.
 * 
 * @param  $object
 * @return boolean
 */
  static public function is_iterable(&$object) {
    return is_array($object) || $object instanceof Traversable;
  }

/**
 * Проверяет является ли данный класс данного объект наследником заданного класса
 * 
 * @param  $ancestor
 * @param  $object
 * @return boolean
 */
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



/**
 * Возвращает имя класса для объекта
 * 
 * @param  $object
 * @param boolean $virtual
 * @return string
 */
  static function class_name_for($object, $virtual = false) {
    $class_name = is_object($object) ?
      get_class($object) : (
        is_string($object) ? $object : null );

    return $class_name ? (
      (boolean) $virtual ?
        str_replace('_', '.', $class_name) :
        str_replace('.', '_', $class_name) ) : null;
  }

/**
 * Возвращает виртуальное имя класса для заданного объекта
 * 
 * @param  $object
 * @return string
 */
  static public function virtual_class_name_for($object) { return self::class_name_for($object, true); }

/**
 * Вовзращает действительное имя класса для заданного объекта
 * 
 * @param  $object
 * @return string
 */
  static public function real_class_name_for($object) { return self::class_name_for($object, false); }

/**
 * Возвращает имя модуля для заданного объекта
 * 
 * @param  $object
 * @return string
 */
  static public function module_name_for($object) {
    return preg_replace('{\.[^.]+$}', '', self::class_name_for($object, true));
  }

/**
 * Возвращает reflection для заданного объекта или класса
 * 
 * @param  $object
 * @return mixed
 */
  static public function reflection_for($object) {
    if (Core_Types::is_string($object))
      return new ReflectionClass(self::real_class_name_for($object));

    if (Core_Types::is_object($object))
      return new ReflectionObject($object);

    throw new Core_InvalidArgumentTypeException('object', $object);
  }

/**
 * Возвращает список классов, составляющих иерархию наследования для данного объекта.
 * 
 * @param object|string $object
 * @param boolean $use_virtual_names
 * @return array
 */
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

/**
 * Проверяет существует ли класс с заданным именем
 * 
 * @param string $name
 * @return boolean
 */
  static public function class_exists($name) {
    return class_exists(self::class_name_for((string) $name, false));
  }

  static public function is_callable($value) {
    return $value instanceof Core_Call || is_callable($value);
  }

}


/**
 * Набор методов для работы с массивами
 * 
 * @package Core
 */
class Core_Arrays {


/**
 * Возвращает массив ключей заданного массива
 * 
 * @param array $array
 * @return array
 */
  static public function keys(array &$array) { return array_keys($array); }



/**
 * Выбирает первый элемент массива
 * 
 * @param array $array
 * @return mixed
 */
  static public function shift(array &$array) { return array_shift($array); }

/**
 * Выбирает последний элемент массива
 * 
 * @param array $array
 * @return mixed
 */
  static public function pop(array &$array) { return array_pop($array); }

/**
 * Выбирает из массива значение с заданным ключом
 * 
 * @param array $array
 * @param  $key
 * @param  $default
 * @return mixed
 */
  static public function pick(array &$array, $key, $default = null) {
    if (isset($array[$key])) {
      $result = $array[$key];
      unset($array[$key]);
      return $result;
    } else
      return $default;
  }

  public static function put(array &$array, $value, $position = 0)
  {
    if (is_null($position)) {
      $position = count($array);
    }
    array_splice($array, $position, 0, array($value));
    return $array;
  }



/**
 * Изменяет порядок следования элементов в массиве на обратный.
 * 
 * @param array $array
 * @param boolean $preserve_keys
 * @return array
 */
  static public function reverse(array $array, $preserve_keys = false) {
    return array_reverse($array, (boolean) $preserve_keys);
  }

/**
 * Объединяет массив массивов в единый линейный массив.
 * 
 * @param array $array
 * @return array
 */
  static public function flatten(array $array) {
    $res = array();
    foreach ($array as $item) $res = self::merge($res, (array) $item);
    return $res;
  }

/**
 * Выполняет пользовательскую функцию над всеми элементами массива.
 * 
 * @param string $lambda
 * @param array $array
 * @return array
 */
// TODO: not only lambda functions
// TODO: $x -> $v
  static public function map($lambda, &$array) {
    return array_map(create_function('$x', $lambda), $array);
  }

/**
 * Выполняет объединение двух массивов
 * 
 * @param array $what
 * @param array $with
 * @return array
 */
  static public function merge(array $what, array $with) { return array_merge($what, $with); }

/**
 * Выполняет рекурсивное объединение массивов
 * 
 * @param array $what
 * @param array $with
 * @return array
 */
  static public function deep_merge_update(array $what, array $with) {
    foreach (array_keys($with) as $k)
      $what[$k] = (isset($what[$k]) && is_array($what[$k]) && is_array($with[$k])) ?
        self::deep_merge_update($what[$k], $with[$k]) : $with[$k];
    return $what;
  }

/**
 * Выполняет рекурсивное объединение массивов
 * 
 * @param array $what
 * @param array $with
 * @return array
 */
  static function deep_merge_append(array $what, array $with) {
    foreach (array_keys($with) as $k) {
      $what[$k] = (isset($what[$k]) && is_array($what[$k]) && is_array($with[$k])) ?
        self::deep_merge_append($what[$k], $with[$k]) :
        (isset($what[$k]) ? array_merge((array) $what[$k], (array) $with[$k]) : $with[$k]);
    }
    return $what;
  }

/**
 * Аналог deep_merge_update с передачей основного массива по ссылке
 * 
 * @param array $what
 * @param array $with
 */
  static public function deep_merge_update_inplace(array &$what, array $with) {
    foreach (array_keys($with) as $k) {
      if (isset($what[$k]) && is_array($what[$k]) && is_array($with[$k]))
        self::deep_merge_update_inplace($what[$k], $with[$k]);
      else
        $what[$k] = $with[$k];
    }
  }

/**
 * Обновление  существующих значений массива из другого массива
 * 
 * @param array $what
 * @param array $with
 */
  static public function update(array &$what, array $with) {
    foreach ($with as $k => &$v) if (array_key_exists($k, $what)) $what[$k] = $with[$k];
  }

/**
 * Дополнение массива значениями, отсутствующими в нем
 * 
 * @param array $what
 * @param array $with
 */
  static public function expand(array &$what, array $with) {
    foreach ($with as $k => &$v) if (!array_key_exists($k, $what)) $what[$k] = $with[$k];
  }

/**
 * Выполняет конкатенацию элементов массива с использованием заданного разделителя
 * 
 * @param string $delimiter
 * @param array $array
 * @return string
 */
  static public function join_with($delimiter, array $array) { return implode($delimiter, $array); }



/**
 * Выполняет поиск элемента массива
 * 
 * @param  $needle
 * @param array $heystack
 * @param boolean $string
 * @return mixed
 */
  static public function search($needle, array &$haystack, $strict = false) {
    return array_search($needle, $haystack, (boolean) $strict);
  }

/**
 * Проверяет присутствие элемента $value в массиве
 * 
 * @param array $array
 * @param  $value
 * @return boolean
 */
  static public function contains(array &$array, $value)  {
    return array_search($value, $array) !== false;
  }

  //TODO: рфекторинг и вынесение в отдельный модуль
  static public function create_tree($flat, $options = array()) {
    Core::load('Tree');
    return Tree::create_tree($flat, $options);
  }

}

