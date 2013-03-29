<?php
/// <module name="Dev.Unit" version="0.2.6" maintainer="svistunov@techart.ru">
///   <brief>Простейший xUnit-подобный framework для написания тестов.</brief>
///   <details>
///     <p>Модуль построение по классической xUnit-архитектуре, описание которой легко найти в
///        сети.</p>
///   </details>
Core::load('Object');

/// <class name="Dev.Unit" stereotype="module">
///   <brief>Класс модуля</brief>
///   <details>
///     <p>Помимо набора фабричных методов, модуль реализует простой процедурный интерфейс для
///        выполнения тестирования. Например, если у нас есть тестовый модуль Test.Object,
///        с помощью процедурного интерфейса можно выполнить следующие действия.</p>
///     <code><![CDATA[
/// // Загрузить набор тестов из модуля:
/// Dev_Unit::load('Test.Object');
/// // Загрузить набор тестов из модуля и выполнить его:
/// Dev_Unit::load_and_run('Test.Object');
/// // Создать набор текстов из модуля (при вызове из метода suite модуля)
/// Dev_Unit::load_with_prefix('Test.Object.', 'Struct', 'Listener');
/// // Выполнить тест:
/// Dev_Unit::run(Dev_Unit::load('Test.Object'));
///     ]]></code>
///   </details>
///   <implements interface="Core.ConfigurableModuleInterface" />
class Dev_Unit implements Core_ConfigurableModuleInterface {

///   <constants>
  const VERSION               = '0.2.6';
  const TEST_METHOD_SIGNATURE = 'test_';
///   </constants>

  static protected $options = array('runner' => 'Dev.Unit.Run.Text.TestRunner');

///   <protocol name="creating">

///   <method name="initialize" scope="class">
///     <brief>Выполняет инициализацию модуля</brief>
///     <args>
///       <arg name="options" type="array" default="array()" brief="список опций модуля" />
///     </args>
///     <details>
///       <p>Поддерживаемые опции:</p>
///       <dl>
///         <dt>runner</dt><dd>модуль, реализующий среду выполнения</dd>
///       </dl>
///     </details>
///     <body>
  static public function initialize(array $options = array()) { self::options($options); }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="configuring" interface="Core.ConfigurableModuleInterface">

///   <method name="option" returns="mixed" scope="class">
///     <brief>Возвращает или устанавливает значение опции</brief>
///     <args>
///       <arg name="name" type="string" />
///       <arg name="value" type="mixed" default="null" />
///     </args>
///     <body>
  static public function option($name, $value = null) {
    $prev = isset(self::$options[$name]) ? self::$options[$name] : null;
    if ($value !== null) self::$options[$name] = $value;
    return $prev;
  }
///     </body>
///   </method>

///   <method name="options" returns="array" scope="class">
///     <brief>Возвращает или устанавливает список опций</brief>
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

///   </protocol>

///   <protocol name="performing">

///   <method name="run" returns="Dev.Unit.TestResult" scope="class">
///     <brief>Выполняет набор тестов</brief>
///     <args>
///       <arg name="tests" />
///     </args>
///     <details>
///       <p>Метод возвращает неявно создаваемый объект типа Dev.Unit.TestResult, содержащий
///          результаты тестирования.</p>
///       <p>Для запуска тестов используется среда выполнения, указанная в опции runner, по
///          умолчанию это консольный режим, реализованный в модуле Dev.Unit.Text.</p>
///     </details>
///     <body>
  static public function run(Dev_Unit_RunInterface $test) {
    Core::load(Core_Types::module_name_for(self::$options['runner']));
    return Core::make(self::$options['runner'])->run($test);
  }
///     </body>
///   </method>

///   <method name="load" returns="Dev.Unit.TestSuite" scope="class">
///     <brief>Загружает набор тестов, как правило из тестового модуля</brief>
///     <body>
///     <details>
///       <p>Метод может быть использован в двух ситуациях:</p>
///       <ol>
///         <li>загрузка набора тестов из тестового модуля</li>
///         <li>формирование набора тестов из набора уже загруженных классов.</li>
///       </ol>
///       <p>В первом случае в качестве аргументов вызова необходимо указать список тестовых
///          модулей. Будет выполнена загрузка этих модулей, получение наборов тестовых
///          сценариев модулей путем вызова метода suite() и объединение этих сценариев в единый
///          набор.</p>
///        <p>Во втором случае в качестве аргументов вызова необходимо указать список уже
///           загруженных классов, унаследованных от Dev.Unit.TestCase, из которых будет собран
///           набор тестов.</p>
///     </details>
  static public function load() {
    return self::TestLoader()->from(func_get_args())->suite;
  }
///     </body>
///   </method>

///   <method name="load_with_prefix" returns="Dev.Unit.TestSuite">
///     <brief>Создает набор тестов из классов/модулей c общим префиксом в имени.</brief>
///     <args>
///       <arg name="prefix" type="string" />
///     </args>
///     <details>
///       <p>Метод аналогичен методу load(), за исключением того, что ко всем именам модулей/классов
///          добавляется префикс, указываемый в качестве первого аргумента.</p>
///     </details>
///     <body>
  static public function load_with_prefix($prefix) {
    $args = func_get_args();
    return self::TestLoader()->
      prefix(array_shift($args))->
      from($args)->
      suite;
  }
///     </body>
///   </method>

///   <method name="load_and_run" returns="Dev.Unit.TestResult" scope="class">
///     <brief>Создает набор методов и запускает его</brief>
///     <details>
///       <p>Создает набор тестов по аналогии с методов load() и запускает получившийся набор.</p>
///       <p>Это самый простой способ выполнить тестовый модуль:</p>
///       <code><![CDATA[
/// Dev_Unit::load_and_run('Test.Object');
///       ]]></code>
///       <p>Метод возвращает объект класса Dev.Unit.TestResult, содержащий результаты
///          тестирования.</p>
///     </details>
///     <body>
  static public function load_and_run() {
    return self::run(self::TestLoader()->from(func_get_args())->suite);
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="building">

///   <method name="TestResult" returns="Dev.Unit.TestResult" scope="class">
///     <brief>Создает объект класса Dev.Unit.TestResult</brief>
///     <body>
  static public function TestResult() { return new Dev_Unit_TestResult(); }
///     </body>
///   </method>

///   <method name="TestSuite" returns="Dev.Unit.TestSuite" scope="class">
///     <brief>Создает объект класса Dev.Unit.TestSuite</brief>
///     <body>
  static public function TestSuite() { return new Dev_Unit_TestSuite(); }
///     </body>
///   </method>

///   <method name="TestLoader" returns="Dev.Unit.TestLoader" scope="class">
///     <brief>Создает объект класса Dev.Unit.TestLoader</brief>
///     <args>
///       <arg name="suite" type="Dev.Unit.TestSuite" default="null" />
///     </args>
///     <body>
  static public function TestLoader(Dev_Unit_TestSuite $suite = null) {
    return new Dev_Unit_TestLoader($suite);
  }
///     </body>
///   </method>

///   </protocol>
}
/// </class>


/// <interface name="Dev.Unit.RunInterface">
///   <brief>Интерфейс сценария тестирования</brief>
///   <details>
///     <p>Сценарий тестирования должен содержать метод run(), обеспечивающий выполнение теста. В
///        качестве аргумента метод должен принимать экземпляр объекта Dev.Unit.TestResult, в
///        который помещаются результаты тестирования. В случае, если такой объект не передается,
///        должен неявно создаваться новый объект.</p>
///   </details>
interface Dev_Unit_RunInterface {

///   <protocol name="performing">

///   <method name="run" returns="Dv.Unit.RunInterface">
///     <brief>Выполняет тестирование</brief>
///     <args>
///       <arg name="result" type="Dev.Unit.TestResult" default="null" brief="результаты тестирования"/>
///     </args>
///     <body>
  public function run(Dev_Unit_TestResult $result = null);
///     </body>
///   </method>

///   </protocol>
}
/// </interface>


/// <interface name="Dev.Unit.TestModuleInterface" extends="Core.ModuleInterface">
///   <brief>Модуль тестирования</brief>
///   <details>
///     <p>Набор сценариев тестирования удобно группировать в модули. Рекомендуется для каждого
///        модуля библиотеки создавать соответствующий тестирующий модуль в иерархии Test.</p>
///     <p>Класс модуля должен реализовывать статический метод suite(), возвращающий объeкт класса
///        Dev.Unit.TestSuite, содержащий набор сценариев тестирования модуля. Для формирования
///        набора удобнее всего использовать метод Dev_Unit::load_with_prefix, например:</p>
///     <code><![CDATA[
/// static public function suite() {
///   return Dev_Unit::load_with_prefix('Test.Object.', 'Struct', 'Listener');
/// }
///     ]]></code>
///   </details>
interface Dev_Unit_TestModuleInterface extends Core_ModuleInterface {

///   <protocol name="quering">

///   <method name="suite" returns="Dev.Unit.TestSuite" scope="class">
///     <brief>Формирует набор сценариев тестирования</brief>
///     <body>
  static public function suite();
///     </body>
///   </method>

///   </protocol>
}
/// </interface>

/// <class name="Dev.Unit.Exception" extends="Core.Exception">
class Dev_Unit_Exception extends Core_Exception {}
/// </class>

/// <class name="Dev.Unit.InvalidAssertBundleException" extends="Dev.Unit.Exception">
class Dev_Unit_InvalidAssertBundleException extends Dev_Unit_Exception {

  protected $bundle;

///   <protocol name="creating">

///   <method name="__construct">
///     <args>
///       <arg name="bundle" type="string" />
///     </args>
///     <body>
  public function __construct($bundle) {
    parent::__construct("Invalid bundle '$bundle'");
    $this->bundle = $bundle;
  }
///     </body>
///   </method>

///   </protocol>
}
/// </class>


/// <class name="Dev.Unit.FailureException" extends="Dev.Unit.Exception">
///   <brief>Исключение, генерируемое в случае невыполнения условия теста</brief>
class Dev_Unit_FailureException extends Dev_Unit_Exception {

///   <protocol name="creating">

///   <method name="__construct">
///     <brief>Конструктор</brief>
///     <args>
///       <arg name="message" type="string" brief="описание ошибки" />
///     </args>
///     <details>
///       <p>В случае, если описание ошибки не передано, или пустое, используется стандартное
///          сообщение 'assertion failed'.</p>
///     </details>
///     <body>
  public function __construct($message) {
    parent::__construct(Core::if_not((string) $message, 'assertion failed'));
  }
///     </body>
///   </method>

///   </protocol>
}
/// </class>


/// <class name="Dev.Unit.Event" extends="Object.Struct" stereotype="abstract">
///   <brief>Базовый класс событий тестирования</brief>
///   <details>
///     <p>События, возникающие в ходе выполнения тестирования -- это невыполнение тестовых условий
///        и аварийные сбои тестов.</p>
///     <p>Свойства:</p>
///     <dl>
///       <dt>case</dt><dd>строковое имя теста</dd>
///       <dt>message</dt><dd>описание события</dd>
///       <dt>file</dt><dd>путь к файлу, в котором возникло событие</dd>
///       <dt>line</dt><dd>строка, в которой возникло событие</dd>
///     </dl>
///   </details>
abstract class Dev_Unit_Event extends Object_Struct {

  protected $case;
  protected $message;
  protected $file;
  protected $line;

///   <protocol name="creating">

///   <method name="__construct" access="protected">
///     <brief>Конструктор</brief>
///     <args>
///       <arg name="case"     type="string" />
///       <arg name="message"  type="string" />
///       <arg name="location" type="string" />
///     </args>
///     <details>
///       <p>Поскольку события инициируются путем генерации исключений, конструктор недоступен
///          снаружи класса, для создания объектов необходимо использовать фабричные методы
///          производных классов, создающие экземпляр по объекту исключения, инициировавшему
///          событие.</p>
///     </details>
///     <body>
  protected function __construct($case, $message, $file, $line) {
    $this->case     = (string) $case;
    $this->message  = (string) $message;
    $this->file     = (string) $file;
    $this->line     = (int) $line;
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="supporting">

///   <method name="set_case" returns="Dev.Unit.Event" access="protected">
///     <brief>Запрещает изменение имени теста после создания объекта</brief>
///     <args>
///       <arg name="value" />
///     </args>
///     <body>
  protected function set_case($value) { throw new Core_ReadOnyPropertyException('case'); }
///     </body>
///   </method>

///   <method name="set_message" returns="Dev.Unit.Event" access="protected">
///     <brief>Запрещает изменение описания события после создания объекта</brief>
///     <args>
///       <arg name="value" />
///     </args>
///     <body>
  protected function set_message($value) { throw new Core_ReadOnlyPropertyException('message'); }
///     </body>
///   </method>

///   <method name="set_file" returns="Dev.Unit.Event" access="protected">
///     <brief>Запрещает изменение имени файла после создания объекта</brief>
///     <args>
///       <arg name="value" />
///     </args>
///     <body>
  protected function set_file($value) { throw new Core_ReadOnlyPropertyException('file'); }
///     </body>
///   </method>

///   <method name="set_line" returns="Dev.Unit.Event" access="protected">
///     <brief>Запрещает изменение номера строки после создания объекта</brief>
///     <args>
///       <arg name="value" />
///     </args>
///     <body>
  protected function set_line($value) { throw new Core_ReadOnlyPropertyException('line'); }
///     </body>
///   </method>

///   </protocol>
}
/// </class>


/// <class name="Dev.Unit.Error" extends="Dev.Unit.Event">
///   <brief>Событие тестирования: аварийная ошибка выполнения теста</brief>
class Dev_Unit_Error extends Dev_Unit_Event {

///   <protocol name="creating">

///   <method name="make_from" returns="Dev.Unit.Error" scope="class">
///     <brief>Создает объект на основании информации объектов теста и аварийного исключения</brief>
///     <args>
///       <arg name="case"      type="Dev.Unit.TestCase" brief="тест" />
///       <arg name="exception" type="Exception"         brief="исключение" />
///     </args>
///     <body>
  static public function make_from(Dev_Unit_TestCase $case, Exception $exception) {
    $t = Core::with_index($exception->getTrace(), 0);
    return new self($case->name, $exception->getMessage(), $t['file'], $t['line']);
  }
///     </body>
///   </method>

///   </protocol>
}
/// </class>


/// <class name="Dev.Unit.Failure" extends="Dev.Unit.Event">
///   <brief>Событие тестирования: невыполнение условия теста</brief>
class Dev_Unit_Failure extends Dev_Unit_Event {

///   <protocol name="creating">

///   <method name="make_from" returns="Dev.Unit.Failure" scope="class">
///     <brief>Создает объект на основании информации объектов теста и невыполнения условия теста</brief>
///     <args>
///       <arg name="case" type="Dev.Unit.TestCase" brief="тест" />
///       <arg name="exception" type="Dev.Unit.FailureException" brief="исключение" />
///     </args>
///     <body>
  static public function make_from(Dev_Unit_TestCase $case, Dev_Unit_FailureException $exception) {
    $t = Core::with_index($exception->getTrace(), 0);
    return new self($case->name, $exception->message, $t['file'], $t['line']);
  }
///     </body>
///   </method>

///   </protocol>
}
/// </class>


/// <interface name="Dev.Unit.TestResultListenerInterface">
///   <brief>Интерфейс пользовательского обработчика событий тестирования</brief>
///   <details>
///     <p>Объекты, реализующие этот интерфейс, могут быть добавлены в качестве обработчиков
///        событий выполнения тестирования к объекту класса Dev.Unit.TestResult. Так, например,
///        модуль Dev.Unit.Text определяет свой обработчик для вывода информации о ходе тестирования
///        в поток.</p>
///   </details>
interface Dev_Unit_TestResultListenerInterface {

///   <protocol name="listening">

///   <method name="on_start_test">
///     <brief>Обработчик события начала выполнения теста</brief>
///     <args>
///       <arg name="result" type="Dev.Unit.TestResult" brief="результаты тестирования" />
///       <arg name="test"   type="Dev.Unit.TestCase"   brief="тест" />
///     </args>
///     <details>
///       <p>Событие инициируется в начале выполнения каждого теста (под тестом мы понимаем
///          отдельный метод тестирования объекта класса Dev.Unit.TestCase).</p>
///     </details>
///     <body>
  public function on_start_test(Dev_Unit_TestResult $result, Dev_Unit_TestCase $test);
///     </body>
///   </method>

///   <method name="on_finish_test">
///     <brief>Обработчик события завершения выполнения теста</brief>
///     <args>
///       <arg name="result" type="Dev.Unit.TestResult"  brief="результаты тестирования"/>
///       <arg name="test"   type="Dev.Unit.TestCase"    brief="тест" />
///     </args>
///     <details>
///       <p>Событие инициируется после завершения выполнения каждого теста.</p>
///     </details>
///     <body>
  public function on_finish_test(Dev_Unit_TestResult $result, Dev_Unit_TestCase $test);
///     </body>
///   </method>

///   <method name="on_add_error">
///     <brief>Обработчик события возникновения ошибки теста</brief>
///     <args>
///       <arg name="result" type="Dev.Unit.TestResult" brief="результаты тестирования" />
///       <arg name="test"   type="Dev.Unit.TestCase"   brief="тест" />
///       <arg name="error"   type="Dev.Unit.Error"     brief="информация об ошибке тестования" />
///     </args>
///     <details>
///       <p>Событие возникает в случае возникновения ошибки, приводящей к аварийному завершению
///          выполнения теста. Такая ситуаций возникает в случае, если код теста генерирует любое
///          необрабатываемое исключение, тип которого отличен от Dev.Unit.FailureException,
///          зарезервированного для случаев невыполнения условий тестирования.</p>
///     </details>
///     <body>
  public function on_add_error(Dev_Unit_TestResult $result, Dev_Unit_TestCase $test, Dev_Unit_Error $error);
///     </body>
///   </method>

///   <method name="on_add_failure">
///     <brief>Обработчик события невыполнения условия тестирования</brief>
///     <args>
///       <arg name="result"  type="Dev.Unit.TestResult" brief="" />
///       <arg name="test"    type="Dev.Unit.TestCase" />
///       <arg name="failure" type="Dev.Unit.Failure" />
///     </args>
///     <details>
///       <p>Событие возникает в случае невыполнения условия тестирования.</p>
///     </details>
///     <body>
  public function on_add_failure(Dev_Unit_TestResult $result, Dev_Unit_TestCase $test, Dev_Unit_Failure $failure);
///     </body>
///   </method>

///   <method name="on_add_success">
///     <brief>Обработчик события успешного выполнения теста</brief>
///     <args>
///       <arg name="result" type="Dev.Unit.TestResult" />
///       <arg name="test"   type="Dev.Unit.TestCase" />
///     </args>
///     <details>
///       <p>Событие возникает в случае успешного завершения выполнения теста.</p>
///     </details>
///     <body>
  public function on_add_success(Dev_Unit_TestResult $result, Dev_Unit_TestCase $test);
///     </body>
///   </method>

///   </protocol>
}
/// </interface>


/// <class name="Dev.Unit.TestResultListener" stereotype="abstract">
///   <brief>Пустая реализация интерфейса Dev.Unit.TestResultListenerInterface</brief>
///   <implements interface="Dev.Unit.TestResultListenerInterface" />
///   <details>
///     <p>Класс предназначен для использования в качестве базового при создании собственных
///        обработчиков событий, обеспечивая возможность реализации только необходимых методов.</p>
///   </details>
abstract class Dev_Unit_TestResultListener implements Dev_Unit_TestResultListenerInterface {

///   <protocol name="listening">

///   <method name="on_start_test">
///     <brief>Обработчик события начала выполнения теста</brief>
///     <args>
///       <arg name="result" type="Dev.Unit.TestResult" />
///       <arg name="test"   type="Dev.Unit.TestCase" />
///     </args>
///     <body>
  public function on_start_test(Dev_Unit_TestResult $result, Dev_Unit_TestCase $test) {}
///     </body>
///   </method>

///   <method name="on_finish_test">
///     <brief>Обработчик события завершения выполнения теста</brief>
///     <args>
///       <arg name="result" type="Dev.Unit.TestResult" />
///       <arg name="test"   type="Dev.Unit.TestCase" />
///     </args>
///     <body>
  public function on_finish_test(Dev_Unit_TestResult $result, Dev_Unit_TestCase $test) {}
///     </body>
///   </method>

///   <method name="on_add_error">
///     <brief>Обработчик события возникновения ошибки теста</brief>
///     <args>
///       <arg name="result" type="Dev.Unit.TestResult" />
///       <arg name="test"   type="Dev.Unit.TestCase" />
///       <arg name="info"   type="Dev.Unit.Error" />
///     </args>
///     <body>
  public function on_add_error(Dev_Unit_TestResult $result, Dev_Unit_TestCase $test, Dev_Unit_Error $error) {}
///     </body>
///   </method>

///   <method name="on_add_failure">
///     <brief>Обработчик события невыполнения условия тестирования</brief>
///     <args>
///       <arg name="result" type="Dev.Unit.TestResult" />
///       <arg name="test"   type="Dev.Unit.TestCase" />
///       <arg name="info"   type="Dev.Unit.Failure" />
///     </args>
///     <body>
  public function on_add_failure(Dev_Unit_TestResult $result, Dev_Unit_TestCase $test, Dev_Unit_Failure $failure) {}
///     </body>
///   </method>

///   <method name="on_add_success">
///     <brief>Обработчик события успешного выполнения теста</brief>
///     <args>
///       <arg name="result" type="Dev.Unit.TestResult" />
///       <arg name="test"   type="Dev.Unit.TestCase" />
///     </args>
///     <body>
  public function on_add_success(Dev_Unit_TestResult $result, Dev_Unit_TestCase $test) {}
///     </body>
///   </method>

///   </protocol>

}
/// </class>


/// <class name="Dev.Unit.TestResult">
///   <brief>Результаты выполнения тестов</brief>
///   <implements interface="Core.PropertyAccessInterface" />
///   <details>
///     <p>Объекты этого класса аккумулируют информации, собираемую в ходе выполнения серии
///        тестов. Методы объекта вызываются объектами класса Dev.Unit.TestCase в ходе
///        проведения тестирования. Для выполнения пользовательской обработки событий тестирования
///        рекомендуется использовать механизм пользовательских обработчиков событий.</p>
///     <p>Свойства:</p>
///     <dl>
///       <dt>num_of_errors</dt><dd>количество аварийных ошибок тестирования;</dd>
///       <dt>num_of_failures</dt><dd>количество невыполненных условий тестирования;</dd>
///       <dt>errors</dt><dd>итератор по списку аварийных ошибок тестирования;</dd>
///       <dt>failures</dt><dd>итератор по списку невыполненных условий тестирования;</dd>
///       <dt>tests_ran</dt><dd>количество выполненных тестов;</dd>
///       <dt>should_stop</dt><dd>признак необходимости остановки выполнения тестирования.</dd>
///     </dl>
///   </details>
class Dev_Unit_TestResult implements Core_PropertyAccessInterface {

  protected $failures;
  protected $errors;

  protected $tests_ran   = 0;
  protected $should_stop = false;

  protected $listener;

///   <protocol name="creating">

///   <method name="__construct">
///     <brief>Конструктор</brief>
///     <body>
  public function __construct() {
    $this->failures  = Core::hash();
    $this->errors    = Core::hash();
    $this->listener  = Object::Listener('Dev.Unit.TestResultListenerInterface');
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="configuring">

///   <method name="listener" returns="Dev.Unit.TestResult">
///     <brief>Подключает пользовательский обработчик событий</brief>
///     <args>
///       <arg name="listener" type="Dev.Unit.TestResultListenerInterface" brief="пользовательский обработчик событий"/>
///     </args>
///     <body>
  public function listener(Dev_Unit_TestResultListenerInterface $listener) {
    $this->listener->append($listener);
    return $this;
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="performing">

///   <method name="start_test" returns="Dev.Unit.TestResult">
///     <brief>Отмечает начало выполнения очередного тестового сценария</brief>
///     <args>
///       <arg name="test" type="Dev.Unit.TestCase" />
///     </args>
///     <details>
///       <p>В реализации по умолчанию просто вызывает пользовательские обработчики событий.</p>
///     </details>
///     <body>
  public function start_test(Dev_Unit_TestCase $test) {
    $this->listener->on_start_test($this, $test);
    return $this;
  }
///     </body>
///   </method>

///   <method name="finish_test" returns="Dev.Unit.TestResult">
///     <brief>Отмечает завершение выполнения очередного тестового сценария</brief>
///     <details>
///       <p>В реализации по умолчанию увеличивает счетчик выполненных тестов и вызывает
///          пользовательские обработчики событий.</p>
///     </details>
///     <body>
  public function finish_test(Dev_Unit_TestCase $case) {
    $this->tests_ran++;
    $this->listener->on_finish_test($this, $case);
    return $this;
  }
///     </body>
///   </method>

///   <method name="add_error" returns="Dev.Unit.TestResult">
///     <brief>Добавляет информацию об аварийном сбое тестового сценария</brief>
///     <args>
///       <arg name="test" type="Dev.Unit.TestCase" brief="сценарий тестирования" />
///       <arg name="error" type="error" brief="описание ошибки" />
///     </args>
///     <details>
///       <p>Аварийное завершение тестового сценария возникает в случае генерирования в процессе
///          выполнения теста исключения, не относящегося к проверке выполнения тестов сценария.</p>
///       <p>Реализация по умолчанию сохраняет информацию о сбое и вызывает пользовательские
///          обработчики событий.</p>
///     </details>
///     <body>
  public function add_error(Dev_Unit_TestCase $test, Dev_Unit_Error $error) {
    $this->errors->append($error);
    $this->listener->on_add_error($this, $test, $error);
    return $this;
  }
///     </body>
///   </method>

///   <method name="add_failure" returns="Dev.Unit.TestResult">
///     <brief>Добавляет информацию об ошибке тестового сценария</brief>
///     <args>
///       <arg name="test" type="Dev.Unit.TestCase" brief="тестовый сценарий" />
///       <arg name="failure" type="Dev.Unit.Failure" brief="информация об ошибке" />"
///     </args>
///     <details>
///       <p>Ошибки тестового сценария возникают в случае, если не выполняется условие теста.</p>
///       <p>Реализация по умолчанию сохраняет информацию об ошибке и вызывает пользовательские
///          обработчики событий.</p>
///     </details>
///     <body>
  public function add_failure(Dev_Unit_TestCase $test, Dev_Unit_Failure $failure) {
    $this->failures->append($failure);
    $this->listener->on_add_failure($this, $test, $failure);
    return $this;
  }
///     </body>
///   </method>

///   <method name="add_success" returns="Dev.Unit.TestResult">
///     <brief>Фиксирует информацию об успешном выполнении сценария.</brief>
///     <args>
///       <arg name="test" type="Dev.Unit.TestCase" brief="тестовый сценарий" />
///     </args>
///     <details>
///       <p>Реализация по умолчанию просто вызывает пользовательские обработчики событий.</p>
///     </details>
///     <body>
  public function add_success(Dev_Unit_TestCase $test) {
    $this->listener->on_add_success($this, $test);
    return $this;
  }
///     </body>
///   </method>

///   <method name="stop" returns="Dev.Unit.TestResult">
///     <brief>Устанавливает признак необходимости прерывания выполнения теста.</brief>
///     <body>
  public function stop() {
    $this->should_stop = true;
    return $this;
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="quering">

///   <method name="was_successful" returns="boolean">
///     <brief>Проверяет, была ли выполненная последовательность тестов успешной</brief>
///     <body>
   public function was_successful() {
     return (count($this->failures) == 0) && (count($this->errors) == 0);
   }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="accessing" interface="Core.PropertyAccessInterface">

///   <method name="__get" returns="mixed">
///     <brief>Возвращает значение свойства</brief>
///     <args>
///       <arg name="property" type="string"  brief="имя свойства" />
///     </args>
///     <body>
  public function __get($property) {
    switch ($property) {
      case 'num_of_errors':
        return count($this->errors);
      case 'num_of_failures':
        return count($this->failures);
      case 'errors':
      case 'failures':
        return $this->$property->getIterator();
      case 'tests_ran':
      case 'should_stop':
        return $this->$property;
      default:
        throw new Core_MissingPropertyException($property);
    }
  }
///     </body>
///   </method>

///   <method name="__set" returns="Dev.Unit.TestResult">
///     <brief>Устанавливает значение свойства</brief>
///     <args>
///       <arg name="property" type="string" brief="имя свойства" />
///       <arg name="value"                  brief="значение свойства" />
///     </args>
///     <details>
///       <p>Все свойства объекта доступны только на чтение.</p>
///     </details>
///     <body>
  public function __set($property, $value) {  throw new Core_ReadOnlyObjectException($this); }
///     </body>
///   </method>

///   <method name="__isset" returns="boolean">
///     <brief>Проверяет установку значения свойства</brief>
///     <args>
///       <arg name="property" type="string" brief="имя свойства" />
///     </args>
///     <body>
  public function __isset($property) {
    switch ($property) {
      case 'num_of_errors':
      case 'num_of_failures':
      case 'errors':
      case 'failures':
      case 'tests_ran':
      case 'should_stop':
        return true;
      default:
        return false;
    }
  }
///     </body>
///   </method>

///   <method name="__unset" returns="Dev.Unit.TestResult">
///     <brief>Удаляет свойство</brief>
///     <args>
///       <arg name="property" type="string" brief="имя свойства" />
///     </args>
///     <details>
  public function __unset($property) { throw new Core_ReadOnlyObjectException($this); }
///     </details>
///   </method>

///   </protocol>
}
/// </class>


/// <interface name="Dev.Unit.AssertBundleModuleInterface" extends="Core.ModuleInterface">
interface Dev_Unit_AssertBundleModuleInterface extends Core_ModuleInterface {

///   <protocol name="building">

///   <method name="bundle" returns="Dev.Unit.AssertBundle" scope="class">
///     <body>
  static public function bundle();
///     </body>
///   </method>

///   </protocol>
}
/// </interface>


/// <class name="Dev.Unit.AssertBundle" stereotype="abstract">
abstract class Dev_Unit_AssertBundle {

  protected $exception;

///   <protocol name="testing">

///   <method name="set_trap" returns="Dev.Unit.TestCase" access="protected">
///     <body>
    protected function set_trap() {
      $this->exception = null;
      return $this;
    }
///     </body>
///   </method>

///   <method name="trap" returns="Dev.Unit.TestCase" access="protected">
///     <args>
///       <arg name="exception" type="Exception" />
///     </args>
///     <body>
    protected function trap(Exception $exception) {
      $this->exception = $exception;
      return $this;
    }
///     </body>
///   </method>

///   <method name="is_catch_prey" returns="boolean" access="protected">
///     <body>
    protected function is_catch_prey() {
      return (boolean) $this->exception;
    }
///     </body>
///   </method>

///   <method name="assert" returns="Dev.Unit.TestCase" access="protected">
///     <args>
///       <arg name="expr" />
///       <arg name="message" type="string" default="null" />
///     </args>
///     <body>
    protected function assert($expr, $message = null) {
      if (!$expr) throw new Dev_Unit_FailureException($message);
      return $this;
    }
///     </body>
///   </method>

///   <method name="assert_true" returns="Dev.Unit.TestCase" access="protected">
///     <args>
///       <arg name="expr" />
///       <arg name="message" type="string" default="null" />
///     </args>
///     <body>
    protected function assert_true($expr, $message = null) {
      if ($expr !== true) throw new Dev_Unit_FailureException($message);
      return $this;
    }
///     </body>
///   </method>

///   <method name="assert_false" returns="Dev.Unit.TestCase" access="protected">
///     <args>
///       <arg name="expr" />
///       <arg name="message" type="string" default="null" />
///     </args>
///     <body>
    protected function assert_false($expr, $message = null) {
      if ($expr !== false) throw new Dev_Unit_FailureException($message);
      return $this;
    }
///     </body>
///   </method>

///   <method name="assert_equal" returns="Dev.Unit.TestCase" access="protected">
///     <args>
///       <arg name="a" />
///       <arg name="b" />
///       <arg name="message" type="string" default="null" />
///     </args>
///     <body>
    protected function assert_equal($a, $b, $message = null) {

      if (!Core::equals($a, $b))
        throw new Dev_Unit_FailureException(
          $message ?
            $message :
            sprintf('failed: %s != %s', $this->stringify($a), $this->stringify($b)));
      return $this;
    }
///     </body>
///   </method>

///   <method name="assert_same" returns="Dev.Unit.TestCase" access="protected">
///     <args>
///       <arg name="a" />
///       <arg name="b" />
///       <arg name="message" type="string" default="null" />
///     </args>
///     <body>
    protected function assert_same($a, $b, $message = null) {
      $a_replace = str_replace(' ', '', str_replace("\n", '', $a));
      $b_replace = str_replace(' ', '', str_replace("\n", '', $b));
      if (!Core::equals($a_replace, $b_replace))
        throw new Dev_Unit_FailureException(
          $message ?
            $message :
            sprintf('failed: %s !~ %s', $this->stringify($a), $this->stringify($b)));
      return $this;
    }
///     </body>
///   </method>

///   <method name="assert_match" returns="Dev.Unit.TestCase" access="protected">
///     <args>
///       <arg name="regexp" type="string"  />
///       <arg name="string" type="string" />
///       <arg name="message" type="string" default="null" />
///     </args>
///     <body>"
    protected function assert_match($regexp, $string, $message = null) {
      if (!Core_Regexps::match($regexp, $string))
        throw new Dev_Unit_FailureException(
          $message ?
            $message :
            sprintf(
              "failed: %s doesn't match with %s",
              (string) $string,
              (string) $regexp));
      return $this;
    }
///     </body>
///   </method>

///   <method name="assert_class" returns="Dev.Unit.TestCase" access="protected">
///     <args>
///       <arg name="class" type="string" />
///       <arg name="object" type="object" />
///       <arg name="message" type="string" default="null" />
///     </args>
///     <body>
    protected function assert_class($class, $object, $message = null) {
      if (!Core_Types::is_subclass_of($class, $object))
        throw new Dev_Unit_FailureException(
          $message ?
            $message :
            sprintf(
              'failed: %s is not ancestor for %s',
              Core_Types::virtual_class_name_for($class),
              Core_Types::virtual_class_name_for($object)));
      return $this;
    }
///     </body>
///   </method>

///   <method name="assert_string" returns="Dev.Unit.TestCase" access="protected">
///     <args>
///       <arg name="value" />
///       <arg name="message" type="string" default="null" />
///     </args>
///     <body>
    protected function assert_string($value, $message = null) {
      if (!is_string($value))
        throw new Dev_Unit_FailureException(
          $message ?
            $message :
            sprintf('failed: %s is not a string', $this->stringify($value)));
      return $this;
    }
///     </body>
///   </method>

///   <method name="assert_number" returns="Dev.Unit.TestCase" access="protected">
///     <args>
///       <arg name="value" />
///       <arg name="message" type="string" default="null" />
///     </args>
///     <body>
    protected function assert_numeric($value, $message = null) {
      if (!is_numeric($value))
        throw new Dev_Unit_FailureException(
          $message ?
            $message :
            sprintf('failed: %s is not numeric', $this->stringify($value)));
      return $this;
    }
///     </body>
///   </method>

///   <method name="assert_float" returns="Dev.Unit.TestCase" access="protected">
///     <args>
///       <arg name="value" />
///       <arg name="message" type="string" default="null" />
///     </args>
///     <body>
    protected function assert_float($value, $message = null) {
      if (!is_float($value))
        throw new Dev_Unit_FailureException(
          $message ?
            $message :
            sprintf('failed: %s is not float', $this->stringify($value)));
      return $this;
    }
///     </body>
///   </method>

///   <method name="assert_int" returns="Dev.Unit.TestCase" access="protected">
///     <args>
///       <arg name="value" />
///       <arg name="message" type="string" default="null" />
///     </args>
///     <body>
    protected function assert_int($value, $message = null) {
      if (!is_int($value))
        throw new Dev_Unit_FailureException(
          $message ?
            $message :
            sprintf('failed: %s is not int', $this->stringify($value)));
      return $this;
    }
///     </body>
///   </method>

///   <method name="assert_array" returns="Dev.Unit.TestCase" access="protected">
///     <args>
///       <arg name="value" />
///       <arg name="message" type="string" default="null" />
///     </args>
///     <body>
    protected function assert_array($value, $message = null) {
      if (!is_array($value))
        throw new Dev_Unit_FailureException(
          $message ?
            $message :
            sprintf('failed: %s is not array', $this->stringify($value)));
      return $this;
    }
///     </body>
///   </method>

///   <method name="assert_boolean" returns="Dev.Unit.TestCase" access="protected">
///     <args>
///       <arg name="value" />
///       <arg name="message" type="string" default="null" />
///     </args>
///     <body>
    protected function assert_boolean($value, $message = null) {
      if (!is_bool($value))
        throw new Dev_Unit_FailureException(
          $message ?
            $message :
            sprintf('failed: %s is not boolean', $this->stringify($value)));
      return $this;
    }
///     </body>
///   </method>

///   <method name="assert_null" returns="Dev.Unit.TestCase" access="protected">
///     <args>
///       <arg name="value" />
///       <arg name="message" type="string" default="null" />
///     </args>
///     <body>
    protected function assert_null($value, $message = null){
      if (!is_null($value))
        throw new Dev_Unit_FailureException(
          $message ?
            $message :
            sprintf('failed: %s is not null', $this->stringify($value)));
      return $this;
    }
///     </body>
///   </method>

///   <method name="assert_object" returns="Dev.Unit.TestCase" access="protected">
///     <args>
///       <arg name="value" />
///       <arg name="message" type="string" default="null" />
///     </args>
///     <body>
    protected function assert_object($value, $message = null) {
      if (!is_object($value))
        throw new Dev_Unit_FailureException(
          $message ?
            $message :
            sprintf('failed: %s is not an object', $this->stringify($value)));
    }
///     </body>
///   </method>

///   <method name="assert_exception" returns="Dev.Unit.TestCase" access="protected">
///     <args>
///       <arg name="message" type="string" default="null" />
///     </args>
///     <body>
    protected function assert_exception($message = null) {
      if (!$this->exception)
        throw new Dev_Unit_FailureException($message ? $message : 'failed: no exception');
      return $this;
    }
///     </body>
///   </method>

///   <method name="assert_no_exception" returns="Dev.Unit.TestCase" access="protected">
///     <args>
///       <arg name="message" type="string" default="null" />
///     </args>
///     <body>
    protected function assert_no_exception($message = null) {
      if ($this->exception)
        throw new Dev_Unit_FailureException($message ? $message : 'failed: exception trapped');
      return $this;
    }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="supporting">

///   <method name="stringify" returns="string" access="protected">
///     <args>
///       <arg name="object" />
///     </args>
///     <body>
    protected function stringify($object) {
      switch (true) {
        case $object instanceof Core_StringifyInterface:
          return $object->as_string();
        case $object instanceof ArrayObject:
        case $object instanceof stdClass:
        default:
          return var_export($object, true);
        case Core_Types::is_object($object):
          return sprintf('%s(%s)', Core_Types::class_name_for($object, true), spl_object_hash($object));
      }
    }
///     </body>
///   </method>

///   </protocol>
}
/// </class>


/// <class name="Dev.Unit.TestCase" extends ="Dev.Unit.AssertBundle" stereotype="abstract">
///   <brief>Сценарий тестирования</brief>
///   <details>
///     <p>Сценарий тестирования состоит из трех основных этапов:</p>
///     <ol>
///       <li></li>
///       <li></li>
///       <li></li>
///     </ol>
///   </details>
abstract class Dev_Unit_TestCase extends Dev_Unit_AssertBundle
  implements Dev_Unit_RunInterface, Core_PropertyAccessInterface {

  private $method;
  private $asserts;


///   <protocol name="creating">

///   <method name="__construct">
///     <brief>Конструктор</brief>
///     <args>
///       <arg name="method" type="string" default="run_test" brief="имя метода тестирования" />
///     </args>
///     <body>
    public function __construct($method = 'run_test') {
      $this->asserts = new Dev_Unit_AssertBundleLoader();
      if (!method_exists($this, $this->method = (string) $method))
        throw new Core_MissingMethodException($this->method);
    }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="performing">

///   <method name="run" returns="Dev.Unit.TestResult">
///     <args>
///       <arg name="result" type="Dev.Unit.TestResult" defaul="null" />
///     </args>
///     <body>
    public function run(Dev_Unit_TestResult $result = null) {
      Core::with(
        $result = Core::if_null($result, Dev_Unit::TestResult()))->
          start_test($this);

      $is_ok = true;

      try {
        $this->before_setup();
        $this->setup();
        $this->after_setup();
      } catch (Exception $e) {
        $result->add_error($this, Dev_Unit_Error::make_from($this, $e));
        $is_ok = false;
      }

      if ($is_ok)
        try {
          call_user_func(array($this, $this->method));
        } catch (Dev_Unit_FailureException $e) {
          $result->add_failure($this, Dev_Unit_Failure::make_from($this, $e));
          $is_ok = false;
        } catch (Exception $e) {
          $result->add_error($this, Dev_Unit_Error::make_from($this, $e));
          $is_ok = false;
        }

      try {
        $this->before_teardown();
        $this->teardown();
        $this->after_teardown();
      } catch (Exception $e) {
        $result->add_error($this, Dev_Unit_Error::make_from($this, $e));
        $is_ok = false;
      }

      if ($is_ok) $result->add_success($this);
      $result->finish_test($this);

      return $this;
    }
///     </body>
///   </method>

///   <method name="before_setup" access="protected">
///     <body>
    protected function before_setup() {}
///     </body>
///   </method>

///   <method name="setup" access="protected">
///     <body>
    protected function setup() {}
///     </body>
///   </method>


///   <method name="after_setup" access="protected">
///     <body>
    protected function after_setup() {}
///     </body>
///   </method>

///   <method name="before_teardown" access="protected">
///     <body>
    protected function before_teardown() {}
///     </body>
///   </method>

///   <method name="teardown" access="protected">
///     <body>
    protected function teardown() {}
///     </body>
///   </method>

///   <method name="after_teardown" access="protected">
///     <body>
    protected function after_teardown() {}
///     </body>
///   </method>

///   </protocol>


///   <protocol name="accessing" interface="Core.PropertyAccessInterface">

///   <method name="__get" returns="mixed">
///     <args>
///       <arg name="property" type="string" />
///     </args>
///     <body>
    public function __get($property) {
      switch ($property) {
        case 'method':
        case 'asserts':
          return $this->$property;
        case 'name':
          return Core_Types::virtual_class_name_for($this).'::'.$this->method;
        default:
          throw new Core_MissingPropertyException($property);
      }
    }
///     </body>
///   </method>

///   <method name="__set" returns="Dev.Unit.TestCase">
///     <args>
///       <arg name="property" type="string" />
///       <arg name="value" />
///     </args>
///     <body>
    public function __set($property, $value) { throw new Core_ReadOnlyObjectException($this); }
///     </body>
///   </method>

///   <method name="__isset" returns="boolean">
///     <args>
///       <arg name="property" type="string" />
///     </args>
///     <body>
    public function __isset($property) {
      switch ($property) {
        case 'method':
        case 'name':
        case 'asserts':
          return true;
        default:
          return false;
      }
    }
///     </body>
///   </method>

///   <method name="__unset" returns="Dev.Unit.TestCase">
///     <args>
///       <arg name="property" type="string" />
///     </args>
///     <body>
    public function __unset($property) { throw new Core_ReadOnlyObjectException($this); }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="supporting">

///   </protocol>
}
/// </class>


/// <class name="Dev.Unit.TestSuite">
///   <implements interface="Dev.Unit.RunInterface" />
///   <implements interface="IteratorAggregate" />
///   <implements interface="Core.CountInterface" />
class Dev_Unit_TestSuite
  implements Dev_Unit_RunInterface, IteratorAggregate, Core_CountInterface {

  private $tests;

///   <protocol name="creating">

///   <method name="__construct">
///     <body>
  public function __construct() { $this->tests = Core::hash(); }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="configuring">

///   <method name="append" returns="Dev.Unit.TestSuite">
///     <args>
///       <arg name="test" type="Dev.Unit.RunInterface" />
///     </args>
///     <body>
  public function append(Dev_Unit_RunInterface $test) {
    $this->tests->append($test);
    return $this;
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="performing">

///   <method name="run" returns="Dev.Unit.TestSuite">
///     <args>
///       <arg name="result" type="Dev.Unit.TestResult" default="null" />
///     </args>
///     <body>
  public function run(Dev_Unit_TestResult $result = null) {
    $result = $result ? $result : Dev_Unit::TestResult();
    foreach ($this->tests as $test) {
      if ($result->should_stop) break;
      $test->run($result);
    }
    return $this;
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="counting" interface="Core.CountInterface">

///   <method name="count" returns="int">
///     <body>
  public function count() { return count($this->tests); }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="iterating" interface="IteratorAggregate">

///   <method name="getIterator" returns="Iterator">
///     <body>
  public function getIterator() { return $this->tests->getIterator(); }
///     </body>
///   </method>

///   </protocol>
}
/// </class>



/// <class name="Dev.Unit.TestLoader">
class Dev_Unit_TestLoader {
  private $suite;
  private $prefix = '';

///   <protocol name="creating">

  public function __construct(Dev_Unit_TestSuite $suite = null) {
    $this->suite = $suite ? $suite : Dev_Unit::TestSuite();
  }

  public function prefix($prefix) {
    $this->prefix = (string) $prefix;
    return $this;
  }

///   </protocol>

///   <protocol name="building">

///   <method name="from" returns="Dev.Unit.TestLoader">
///     <body>
  public function from() {
    $args = func_get_args();
    foreach ((Core_Types::is_iterable($args[0]) ? $args[0] : $args) as $class) {
      $class = "{$this->prefix}$class";
      if (Core_Types::class_exists($class))
        $this->from_class($class);
      else {
        try {
          Core::load($class);
        } catch (Core_ModuleException $e) {
          var_dump($e);
          Core::load(Core_Regexps::replace('{\.[a-zA-Z0-9]+$}', '', $class));
          $this->from_class($class);
          continue;
        }

        if (Core_Types::is_subclass_of('Dev.Unit.TestCase', $class))
          $this->from_class($class);
        elseif(Core_Types::is_subclass_of('Dev.Unit.TestModuleInterface', $class)) {
          $this->from_suite(call_user_func(array(Core_Types::real_class_name_for($class), 'suite')));
        }
      }
    }
    return $this;
  }
///     </body>
///   </method>

///   <method name="from_class" returns="Dev.Unit.TestLoader">
///     <args>
///       <arg name="class" type="string" />
///     </args>
///     <body>
  public function from_class($class) {
    $class = (string) $class;
    if (Core_Types::is_subclass_of('Dev.Unit.TestCase', $class)) {
      $reflection = Core_Types::reflection_for($class);
      if ($reflection->hasMethod('run_test'))
        $this->suite->append(Core::make($class));
      else {
        foreach ($reflection->getMethods() as $method) {
          if (Core_Strings::starts_with($method->getName(), Dev_Unit::TEST_METHOD_SIGNATURE))
            $this->suite->append(Core::make($class, $method->getName()));
        }
      }
    }
    return $this;
  }
///     </body>
///   </method>

///   <method name="from_module" returns="Dev.Unit.TestLoader">
///     <args>
///       <arg name="module" type="string" />
///     </args>
///     <body>
  public function from_module($module) {
    $module = (string) $module;
    if (!Core_Types::class_exists($module)) Core::load($module);
    $this->suite->append(call_user_func(array(Core_Types::real_class_name_for($module), 'suite')));
    return $this;
  }
///     </body>
///   </method>

///   <method name="from_suite" returns="Dev.Unit.TestLoader">
///     <args>
///       <arg name="suite" type="Dev.Unit.TestSuite" />
///     </args>
///     <body>
  public function from_suite(Dev_Unit_TestSuite $suite) {
    $this->suite->append($suite);
    return $this;
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="accessing" interface="Core.PropertyAccessInterface">

///   <method name="__get" returns="mixed">
///     <body>
  public function __get($property) {
    switch ($property) {
      case 'suite':
      case 'prefix':
        return $this->$property;
      default:
        throw new Core_MissingPropertyException($property);
    }
  }
///     </body>
///   </method>

///   <method name="__set" returns="mixed">
///     <args>
///       <arg name="property" type="string" />
///       <arg name="value" />
///     </args>
///     <body>
  public function __set($property, $value) {
    switch ($property) {
      case 'prefix':
        return $this->prefix($value);
      case 'suite':
        throw new Core_ReadOnlyPropertyException($property);
      default:
        throw new Core_MissingPropertyException($property);
    }
  }
///     </body>
///   </method>


///   <method name="__isset" returns="boolean">
///     <args>
///       <arg name="property" type="string" />
///     </args>
///     <body>
  public function __isset($property) {
    switch ($property) {
      case 'suite':
      case 'prefix':
        return isset($this->$property);
      default:
        return false;
    }
  }
///     </body>
///   </method>

///   <method name="__unset">
///     <args>
///       <arg name="property" type="string" />
///     </args>
///     <body>
  public function __unset($property) {
    switch ($property) {
      case 'prefix':
      case 'suite':
        throw new Core_UndestroyablePropertyException($property);
      default:
        throw new Core_MissingPropertyException($property);
    }
  }
///     </body>
///   </method>

///   </protocol>
}
/// </class>


/// <class name="Dev.Unit.AssertBundleLoader">
///   <implements interface="Core.PropertyAccessInterface" />
class Dev_Unit_AssertBundleLoader implements Core_PropertyAccessInterface {

  private $bundles = array();

///   <protocol name="accessing" interface="Core.PropertyAccessInterface">

///   <method name="__get" returns="Dev.Unit.AssertBundle">
///     <args>
///       <arg name="property" type="string" />
///     </args>
///     <body>
  public function __get($property) {
    return isset($this->bundles[$property]) ?
      $this->bundles[$property] :
      $this->load_bundle($property);
  }
///     </body>
///   </method>

///   <method name="__set" returns="Dev.Unit.AssertBundleLoader">
///     <args>
///       <arg name="property" type="string" />
///       <arg name="value" />
///     </args>
///     <body>
  public function __set($property, $value) { throw new Core_ReadOnlyObjectException($this); }
///     </body>
///   </method>

///   <method name="__isset" returns="boolean">
///     <args>
///       <arg name="property" type="string" />
///     </args>
///     <body>
  public function __isset($property) { return isset($this->bundles[$property]); }
///     </body>
///   </method>

///   <method name="__unset" returns="Dev.Unit.AssertBundleLoader">
///     <args>
///       <arg name="property" type="string" />
///     </args>
///     <body>
  public function __unset($propety) {
    unset($this->bundles[$property]);
    return $this;
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="supporting">

///   <method name="load_bundle" returns="Dev.Unit.AssertBundle" access="private">
///     <args>
///       <arg name="name" type="string" />
///     </args>
///     <body>
  private function load_bundle($name) {
    $mname  = 'Dev.Unit.Assert.'.Core_Strings::capitalize($name);
    $mclass = Core_Types::real_class_name_for($mname);

    Core::load($mname);
    if (Core_Types::is_subclass_of('Dev.Unit.AssertBundleModuleInterface', $mclass) &&
        ($bundle = call_user_func(array($mclass, 'bundle'))) instanceof Dev_Unit_AssertBundle)
      return $this->bundles[$name] = $bundle;
    else
      throw new Dev_Unit_InvalidAssertBundleException($name);
  }
///     </body>
///   </method>

///   </protocol>
}
/// </class>

/// </module>
