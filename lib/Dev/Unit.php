<?php
/**
 * Dev.Unit
 *
 * Простейший xUnit-подобный framework для написания тестов.
 *
 * <p>Модуль построение по классической xUnit-архитектуре, описание которой легко найти в
 * сети.</p>
 *
 * @package Dev\Unit
 * @version 0.2.6
 */
Core::load('Object');

/**
 * Класс модуля
 *
 * <p>Помимо набора фабричных методов, модуль реализует простой процедурный интерфейс для
 * выполнения тестирования. Например, если у нас есть тестовый модуль Test.Object,
 * с помощью процедурного интерфейса можно выполнить следующие действия.</p>
 * <code>
 * // Загрузить набор тестов из модуля:
 * Dev_Unit::load('Test.Object');
 * // Загрузить набор тестов из модуля и выполнить его:
 * Dev_Unit::load_and_run('Test.Object');
 * // Создать набор текстов из модуля (при вызове из метода suite модуля)
 * Dev_Unit::load_with_prefix('Test.Object.', 'Struct', 'Listener');
 * // Выполнить тест:
 * Dev_Unit::run(Dev_Unit::load('Test.Object'));
 * </code>
 *
 * @package Dev\Unit
 */
class Dev_Unit implements Core_ConfigurableModuleInterface
{

	const VERSION = '0.2.6';
	const TEST_METHOD_SIGNATURE = 'test_';

	static protected $options = array('runner' => 'Dev.Unit.Run.Text.TestRunner');

	/**
	 * Выполняет инициализацию модуля
	 *
	 * @param array $options
	 */
	static public function initialize(array $options = array())
	{
		self::options($options);
	}

	/**
	 * Возвращает или устанавливает значение опции
	 *
	 * @param string $name
	 * @param mixed  $value
	 *
	 * @return mixed
	 */
	static public function option($name, $value = null)
	{
		$prev = isset(self::$options[$name]) ? self::$options[$name] : null;
		if ($value !== null) {
			self::$options[$name] = $value;
		}
		return $prev;
	}

	/**
	 * Возвращает или устанавливает список опций
	 *
	 * @param array $options
	 *
	 * @return array
	 */
	static public function options(array $options = array())
	{
		if (count($options)) {
			Core_Arrays::update(self::$options, $options);
		}
		return self::$options;
	}

	/**
	 * Выполняет набор тестов
	 *
	 * @param  $tests
	 *
	 * @return Dev_Unit_TestResult
	 */
	static public function run(Dev_Unit_RunInterface $test)
	{
		Core::load(Core_Types::module_name_for(self::$options['runner']));
		return Core::make(self::$options['runner'])->run($test);
	}

	/**
	 * Загружает набор тестов, как правило из тестового модуля
	 *
	 * @return Dev_Unit_TestSuite
	 */
	static public function load()
	{
		return self::TestLoader()->from(func_get_args())->suite;
	}

	/**
	 * Создает набор тестов из классов/модулей c общим префиксом в имени.
	 *
	 * @param string $prefix
	 *
	 * @return Dev_Unit_TestSuite
	 */
	static public function load_with_prefix($prefix)
	{
		$args = func_get_args();
		return self::TestLoader()->
			prefix(array_shift($args))->
			from($args)->
			suite;
	}

	/**
	 * Создает набор методов и запускает его
	 *
	 * @return Dev_Unit_TestResult
	 */
	static public function load_and_run()
	{
		return self::run(self::TestLoader()->from(func_get_args())->suite);
	}

	/**
	 * Создает объект класса Dev.Unit.TestResult
	 *
	 * @return Dev_Unit_TestResult
	 */
	static public function TestResult()
	{
		return new Dev_Unit_TestResult();
	}

	/**
	 * Создает объект класса Dev.Unit.TestSuite
	 *
	 * @return Dev_Unit_TestSuite
	 */
	static public function TestSuite()
	{
		return new Dev_Unit_TestSuite();
	}

	/**
	 * Создает объект класса Dev.Unit.TestLoader
	 *
	 * @param Dev_Unit_TestSuite $suite
	 *
	 * @return Dev_Unit_TestLoader
	 */
	static public function TestLoader(Dev_Unit_TestSuite $suite = null)
	{
		return new Dev_Unit_TestLoader($suite);
	}

}

/**
 * Интерфейс сценария тестирования
 *
 * <p>Сценарий тестирования должен содержать метод run(), обеспечивающий выполнение теста. В
 * качестве аргумента метод должен принимать экземпляр объекта Dev.Unit.TestResult, в
 * который помещаются результаты тестирования. В случае, если такой объект не передается,
 * должен неявно создаваться новый объект.</p>
 *
 * @package Dev\Unit
 */
interface Dev_Unit_RunInterface
{

	/**
	 * Выполняет тестирование
	 *
	 * @param Dev_Unit_TestResult $result
	 *
	 * @return Dv_Unit_RunInterface
	 */
	public function run(Dev_Unit_TestResult $result = null);

}

/**
 * Модуль тестирования
 *
 * <p>Набор сценариев тестирования удобно группировать в модули. Рекомендуется для каждого
 * модуля библиотеки создавать соответствующий тестирующий модуль в иерархии Test.</p>
 * <p>Класс модуля должен реализовывать статический метод suite(), возвращающий объeкт класса
 * Dev.Unit.TestSuite, содержащий набор сценариев тестирования модуля. Для формирования
 * набора удобнее всего использовать метод Dev_Unit::load_with_prefix, например:</p>
 * <code>
 * static public function suite() {
 * return Dev_Unit::load_with_prefix('Test.Object.', 'Struct', 'Listener');
 * }
 * </code>
 *
 * @package Dev\Unit
 */
interface Dev_Unit_TestModuleInterface extends Core_ModuleInterface
{

	/**
	 * Формирует набор сценариев тестирования
	 *
	 * @return Dev_Unit_TestSuite
	 */
	static public function suite();

}

/**
 * @package Dev\Unit
 */
class Dev_Unit_Exception extends Core_Exception
{
}

/**
 * @package Dev\Unit
 */
class Dev_Unit_InvalidAssertBundleException extends Dev_Unit_Exception
{

	protected $bundle;

	/**
	 * @param string $bundle
	 */
	public function __construct($bundle)
	{
		parent::__construct("Invalid bundle '$bundle'");
		$this->bundle = $bundle;
	}

}

/**
 * Исключение, генерируемое в случае невыполнения условия теста
 *
 * @package Dev\Unit
 */
class Dev_Unit_FailureException extends Dev_Unit_Exception
{

	/**
	 * Конструктор
	 *
	 * @param string $message
	 */
	public function __construct($message)
	{
		parent::__construct(Core::if_not((string)$message, 'assertion failed'));
	}

}

/**
 * Базовый класс событий тестирования
 *
 * <p>События, возникающие в ходе выполнения тестирования -- это невыполнение тестовых условий
 * и аварийные сбои тестов.</p>
 * <p>Свойства:</p>
 * caseстроковое имя теста
 * messageописание события
 * fileпуть к файлу, в котором возникло событие
 * lineстрока, в которой возникло событие
 *
 * @abstract
 * @package Dev\Unit
 */
abstract class Dev_Unit_Event extends Object_Struct
{

	protected $case;
	protected $message;
	protected $file;
	protected $line;

	/**
	 * Конструктор
	 *
	 * @param string $case
	 * @param string $message
	 * @param string $location
	 */
	protected function __construct($case, $message, $file, $line)
	{
		$this->case = (string)$case;
		$this->message = (string)$message;
		$this->file = (string)$file;
		$this->line = (int)$line;
	}

	/**
	 * Запрещает изменение имени теста после создания объекта
	 *
	 * @param  $value
	 *
	 * @return Dev_Unit_Event
	 */
	protected function set_case($value)
	{
		throw new Core_ReadOnyPropertyException('case');
	}

	/**
	 * Запрещает изменение описания события после создания объекта
	 *
	 * @param  $value
	 *
	 * @return Dev_Unit_Event
	 */
	protected function set_message($value)
	{
		throw new Core_ReadOnlyPropertyException('message');
	}

	/**
	 * Запрещает изменение имени файла после создания объекта
	 *
	 * @param  $value
	 *
	 * @return Dev_Unit_Event
	 */
	protected function set_file($value)
	{
		throw new Core_ReadOnlyPropertyException('file');
	}

	/**
	 * Запрещает изменение номера строки после создания объекта
	 *
	 * @param  $value
	 *
	 * @return Dev_Unit_Event
	 */
	protected function set_line($value)
	{
		throw new Core_ReadOnlyPropertyException('line');
	}

}

/**
 * Событие тестирования: аварийная ошибка выполнения теста
 *
 * @package Dev\Unit
 */
class Dev_Unit_Error extends Dev_Unit_Event
{

	/**
	 * Создает объект на основании информации объектов теста и аварийного исключения
	 *
	 * @param Dev_Unit_TestCase $case
	 * @param Exception         $exception
	 *
	 * @return Dev_Unit_Error
	 */
	static public function make_from(Dev_Unit_TestCase $case, Exception $exception)
	{
		$t = Core::with_index($exception->getTrace(), 0);
		return new self($case->name, $exception->getMessage(), $t['file'], $t['line']);
	}

}

/**
 * Событие тестирования: невыполнение условия теста
 *
 * @package Dev\Unit
 */
class Dev_Unit_Failure extends Dev_Unit_Event
{

	/**
	 * Создает объект на основании информации объектов теста и невыполнения условия теста
	 *
	 * @param Dev_Unit_TestCase         $case
	 * @param Dev_Unit_FailureException $exception
	 *
	 * @return Dev_Unit_Failure
	 */
	static public function make_from(Dev_Unit_TestCase $case, Dev_Unit_FailureException $exception)
	{
		$t = Core::with_index($exception->getTrace(), 0);
		return new self($case->name, $exception->message, $t['file'], $t['line']);
	}

}

/**
 * Интерфейс пользовательского обработчика событий тестирования
 *
 * <p>Объекты, реализующие этот интерфейс, могут быть добавлены в качестве обработчиков
 * событий выполнения тестирования к объекту класса Dev.Unit.TestResult. Так, например,
 * модуль Dev.Unit.Text определяет свой обработчик для вывода информации о ходе тестирования
 * в поток.</p>
 *
 * @package Dev\Unit
 */
interface Dev_Unit_TestResultListenerInterface
{

	/**
	 * Обработчик события начала выполнения теста
	 *
	 * @param Dev_Unit_TestResult $result
	 * @param Dev_Unit_TestCase   $test
	 */
	public function on_start_test(Dev_Unit_TestResult $result, Dev_Unit_TestCase $test);

	/**
	 * Обработчик события завершения выполнения теста
	 *
	 * @param Dev_Unit_TestResult $result
	 * @param Dev_Unit_TestCase   $test
	 */
	public function on_finish_test(Dev_Unit_TestResult $result, Dev_Unit_TestCase $test);

	/**
	 * Обработчик события возникновения ошибки теста
	 *
	 * @param Dev_Unit_TestResult $result
	 * @param Dev_Unit_TestCase   $test
	 * @param Dev_Unit_Error      $error
	 */
	public function on_add_error(Dev_Unit_TestResult $result, Dev_Unit_TestCase $test, Dev_Unit_Error $error);

	/**
	 * Обработчик события невыполнения условия тестирования
	 *
	 * @param Dev_Unit_TestResult $result
	 * @param Dev_Unit_TestCase   $test
	 * @param Dev_Unit_Failure    $failure
	 */
	public function on_add_failure(Dev_Unit_TestResult $result, Dev_Unit_TestCase $test, Dev_Unit_Failure $failure);

	/**
	 * Обработчик события успешного выполнения теста
	 *
	 * @param Dev_Unit_TestResult $result
	 * @param Dev_Unit_TestCase   $test
	 */
	public function on_add_success(Dev_Unit_TestResult $result, Dev_Unit_TestCase $test);

}

/**
 * Пустая реализация интерфейса Dev.Unit.TestResultListenerInterface
 *
 * <p>Класс предназначен для использования в качестве базового при создании собственных
 * обработчиков событий, обеспечивая возможность реализации только необходимых методов.</p>
 *
 * @abstract
 * @package Dev\Unit
 */
abstract class Dev_Unit_TestResultListener implements Dev_Unit_TestResultListenerInterface
{

	/**
	 * Обработчик события начала выполнения теста
	 *
	 * @param Dev_Unit_TestResult $result
	 * @param Dev_Unit_TestCase   $test
	 */
	public function on_start_test(Dev_Unit_TestResult $result, Dev_Unit_TestCase $test)
	{
	}

	/**
	 * Обработчик события завершения выполнения теста
	 *
	 * @param Dev_Unit_TestResult $result
	 * @param Dev_Unit_TestCase   $test
	 */
	public function on_finish_test(Dev_Unit_TestResult $result, Dev_Unit_TestCase $test)
	{
	}

	/**
	 * Обработчик события возникновения ошибки теста
	 *
	 * @param Dev_Unit_TestResult $result
	 * @param Dev_Unit_TestCase   $test
	 * @param Dev_Unit_Error      $info
	 */
	public function on_add_error(Dev_Unit_TestResult $result, Dev_Unit_TestCase $test, Dev_Unit_Error $error)
	{
	}

	/**
	 * Обработчик события невыполнения условия тестирования
	 *
	 * @param Dev_Unit_TestResult $result
	 * @param Dev_Unit_TestCase   $test
	 * @param Dev_Unit_Failure    $info
	 */
	public function on_add_failure(Dev_Unit_TestResult $result, Dev_Unit_TestCase $test, Dev_Unit_Failure $failure)
	{
	}

	/**
	 * Обработчик события успешного выполнения теста
	 *
	 * @param Dev_Unit_TestResult $result
	 * @param Dev_Unit_TestCase   $test
	 */
	public function on_add_success(Dev_Unit_TestResult $result, Dev_Unit_TestCase $test)
	{
	}

}

/**
 * Результаты выполнения тестов
 *
 * <p>Объекты этого класса аккумулируют информации, собираемую в ходе выполнения серии
 * тестов. Методы объекта вызываются объектами класса Dev.Unit.TestCase в ходе
 * проведения тестирования. Для выполнения пользовательской обработки событий тестирования
 * рекомендуется использовать механизм пользовательских обработчиков событий.</p>
 * <p>Свойства:</p>
 * num_of_errorsколичество аварийных ошибок тестирования;
 * num_of_failuresколичество невыполненных условий тестирования;
 * errorsитератор по списку аварийных ошибок тестирования;
 * failuresитератор по списку невыполненных условий тестирования;
 * tests_ranколичество выполненных тестов;
 * should_stopпризнак необходимости остановки выполнения тестирования.
 *
 * @package Dev\Unit
 */
class Dev_Unit_TestResult implements Core_PropertyAccessInterface
{

	protected $failures;
	protected $errors;

	protected $tests_ran = 0;
	protected $should_stop = false;

	protected $listener;

	/**
	 * Конструктор
	 *
	 */
	public function __construct()
	{
		$this->failures = Core::hash();
		$this->errors = Core::hash();
		$this->listener = Object::Listener('Dev.Unit.TestResultListenerInterface');
	}

	/**
	 * Подключает пользовательский обработчик событий
	 *
	 * @param Dev_Unit_TestResultListenerInterface $listener
	 *
	 * @return Dev_Unit_TestResult
	 */
	public function listener(Dev_Unit_TestResultListenerInterface $listener)
	{
		$this->listener->append($listener);
		return $this;
	}

	/**
	 * Отмечает начало выполнения очередного тестового сценария
	 *
	 * @param Dev_Unit_TestCase $test
	 *
	 * @return Dev_Unit_TestResult
	 */
	public function start_test(Dev_Unit_TestCase $test)
	{
		$this->listener->on_start_test($this, $test);
		return $this;
	}

	/**
	 * Отмечает завершение выполнения очередного тестового сценария
	 *
	 * @return Dev_Unit_TestResult
	 */
	public function finish_test(Dev_Unit_TestCase $case)
	{
		$this->tests_ran++;
		$this->listener->on_finish_test($this, $case);
		return $this;
	}

	/**
	 * Добавляет информацию об аварийном сбое тестового сценария
	 *
	 * @param Dev_Unit_TestCase $test
	 * @param error             $error
	 *
	 * @return Dev_Unit_TestResult
	 */
	public function add_error(Dev_Unit_TestCase $test, Dev_Unit_Error $error)
	{
		$this->errors->append($error);
		$this->listener->on_add_error($this, $test, $error);
		return $this;
	}

	/**
	 * Добавляет информацию об ошибке тестового сценария
	 *
	 * @param Dev_Unit_TestCase $test
	 * @param Dev_Unit_Failure  $failure
	 *
	 * @return Dev_Unit_TestResult
	 */
	public function add_failure(Dev_Unit_TestCase $test, Dev_Unit_Failure $failure)
	{
		$this->failures->append($failure);
		$this->listener->on_add_failure($this, $test, $failure);
		return $this;
	}

	/**
	 * Фиксирует информацию об успешном выполнении сценария.
	 *
	 * @param Dev_Unit_TestCase $test
	 *
	 * @return Dev_Unit_TestResult
	 */
	public function add_success(Dev_Unit_TestCase $test)
	{
		$this->listener->on_add_success($this, $test);
		return $this;
	}

	/**
	 * Устанавливает признак необходимости прерывания выполнения теста.
	 *
	 * @return Dev_Unit_TestResult
	 */
	public function stop()
	{
		$this->should_stop = true;
		return $this;
	}

	/**
	 * Проверяет, была ли выполненная последовательность тестов успешной
	 *
	 * @return boolean
	 */
	public function was_successful()
	{
		return (count($this->failures) == 0) && (count($this->errors) == 0);
	}

	/**
	 * Возвращает значение свойства
	 *
	 * @param string $property
	 *
	 * @return mixed
	 */
	public function __get($property)
	{
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

	/**
	 * Устанавливает значение свойства
	 *
	 * @param string $property
	 * @param        $value
	 *
	 * @return Dev_Unit_TestResult
	 */
	public function __set($property, $value)
	{
		throw new Core_ReadOnlyObjectException($this);
	}

	/**
	 * Проверяет установку значения свойства
	 *
	 * @param string $property
	 *
	 * @return boolean
	 */
	public function __isset($property)
	{
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

	/**
	 * Удаляет свойство
	 *
	 * @param string $property
	 *
	 * @return Dev_Unit_TestResult
	 */
	public function __unset($property)
	{
		throw new Core_ReadOnlyObjectException($this);
	}

}

/**
 * @package Dev\Unit
 */
interface Dev_Unit_AssertBundleModuleInterface extends Core_ModuleInterface
{

	/**
	 * @return Dev_Unit_AssertBundle
	 */
	static public function bundle();

}

/**
 * @abstract
 * @package Dev\Unit
 */
abstract class Dev_Unit_AssertBundle
{

	protected $exception;

	/**
	 * @return Dev_Unit_TestCase
	 */
	protected function set_trap()
	{
		$this->exception = null;
		return $this;
	}

	/**
	 * @param Exception $exception
	 *
	 * @return Dev_Unit_TestCase
	 */
	protected function trap(Exception $exception)
	{
		$this->exception = $exception;
		return $this;
	}

	/**
	 * @return boolean
	 */
	protected function is_catch_prey()
	{
		return (boolean)$this->exception;
	}

	/**
	 * @param        $expr
	 * @param string $message
	 *
	 * @return Dev_Unit_TestCase
	 */
	protected function assert($expr, $message = null)
	{
		if (!$expr) {
			throw new Dev_Unit_FailureException($message);
		}
		return $this;
	}

	/**
	 * @param        $expr
	 * @param string $message
	 *
	 * @return Dev_Unit_TestCase
	 */
	protected function assert_true($expr, $message = null)
	{
		if ($expr !== true) {
			throw new Dev_Unit_FailureException($message);
		}
		return $this;
	}

	/**
	 * @param        $expr
	 * @param string $message
	 *
	 * @return Dev_Unit_TestCase
	 */
	protected function assert_false($expr, $message = null)
	{
		if ($expr !== false) {
			throw new Dev_Unit_FailureException($message);
		}
		return $this;
	}

	/**
	 * @param        $a
	 * @param        $b
	 * @param string $message
	 *
	 * @return Dev_Unit_TestCase
	 */
	protected function assert_equal($a, $b, $message = null)
	{

		if (!Core::equals($a, $b)) {
			throw new Dev_Unit_FailureException(
				$message ?
					$message :
					sprintf('failed: %s != %s', $this->stringify($a), $this->stringify($b)));
		}
		return $this;
	}

	/**
	 * @param        $a
	 * @param        $b
	 * @param string $message
	 *
	 * @return Dev_Unit_TestCase
	 */
	protected function assert_same($a, $b, $message = null)
	{
		$a_replace = str_replace(' ', '', str_replace("\n", '', $a));
		$b_replace = str_replace(' ', '', str_replace("\n", '', $b));
		if (!Core::equals($a_replace, $b_replace)) {
			throw new Dev_Unit_FailureException(
				$message ?
					$message :
					sprintf('failed: %s !~ %s', $this->stringify($a), $this->stringify($b)));
		}
		return $this;
	}

	/**
	 * @param string $regexp
	 * @param string $string
	 * @param string $message
	 *
	 * @return Dev_Unit_TestCase
	 */
	protected function assert_match($regexp, $string, $message = null)
	{
		if (!Core_Regexps::match($regexp, $string)) {
			throw new Dev_Unit_FailureException(
				$message ?
					$message :
					sprintf(
						"failed: %s doesn't match with %s",
						(string)$string,
						(string)$regexp
					));
		}
		return $this;
	}

	/**
	 * @param string $class
	 * @param object $object
	 * @param string $message
	 *
	 * @return Dev_Unit_TestCase
	 */
	protected function assert_class($class, $object, $message = null)
	{
		if (!Core_Types::is_subclass_of($class, $object)) {
			throw new Dev_Unit_FailureException(
				$message ?
					$message :
					sprintf(
						'failed: %s is not ancestor for %s',
						Core_Types::virtual_class_name_for($class),
						Core_Types::virtual_class_name_for($object)
					));
		}
		return $this;
	}

	/**
	 * @param        $value
	 * @param string $message
	 *
	 * @return Dev_Unit_TestCase
	 */
	protected function assert_string($value, $message = null)
	{
		if (!is_string($value)) {
			throw new Dev_Unit_FailureException(
				$message ?
					$message :
					sprintf('failed: %s is not a string', $this->stringify($value)));
		}
		return $this;
	}

	/**
	 * @param        $value
	 * @param string $message
	 *
	 * @return Dev_Unit_TestCase
	 */
	protected function assert_numeric($value, $message = null)
	{
		if (!is_numeric($value)) {
			throw new Dev_Unit_FailureException(
				$message ?
					$message :
					sprintf('failed: %s is not numeric', $this->stringify($value)));
		}
		return $this;
	}

	/**
	 * @param        $value
	 * @param string $message
	 *
	 * @return Dev_Unit_TestCase
	 */
	protected function assert_float($value, $message = null)
	{
		if (!is_float($value)) {
			throw new Dev_Unit_FailureException(
				$message ?
					$message :
					sprintf('failed: %s is not float', $this->stringify($value)));
		}
		return $this;
	}

	/**
	 * @param        $value
	 * @param string $message
	 *
	 * @return Dev_Unit_TestCase
	 */
	protected function assert_int($value, $message = null)
	{
		if (!is_int($value)) {
			throw new Dev_Unit_FailureException(
				$message ?
					$message :
					sprintf('failed: %s is not int', $this->stringify($value)));
		}
		return $this;
	}

	/**
	 * @param        $value
	 * @param string $message
	 *
	 * @return Dev_Unit_TestCase
	 */
	protected function assert_array($value, $message = null)
	{
		if (!is_array($value)) {
			throw new Dev_Unit_FailureException(
				$message ?
					$message :
					sprintf('failed: %s is not array', $this->stringify($value)));
		}
		return $this;
	}

	/**
	 * @param        $value
	 * @param string $message
	 *
	 * @return Dev_Unit_TestCase
	 */
	protected function assert_boolean($value, $message = null)
	{
		if (!is_bool($value)) {
			throw new Dev_Unit_FailureException(
				$message ?
					$message :
					sprintf('failed: %s is not boolean', $this->stringify($value)));
		}
		return $this;
	}

	/**
	 * @param        $value
	 * @param string $message
	 *
	 * @return Dev_Unit_TestCase
	 */
	protected function assert_null($value, $message = null)
	{
		if (!is_null($value)) {
			throw new Dev_Unit_FailureException(
				$message ?
					$message :
					sprintf('failed: %s is not null', $this->stringify($value)));
		}
		return $this;
	}

	/**
	 * @param        $value
	 * @param string $message
	 *
	 * @return Dev_Unit_TestCase
	 */
	protected function assert_object($value, $message = null)
	{
		if (!is_object($value)) {
			throw new Dev_Unit_FailureException(
				$message ?
					$message :
					sprintf('failed: %s is not an object', $this->stringify($value)));
		}
	}

	/**
	 * @param string $message
	 *
	 * @return Dev_Unit_TestCase
	 */
	protected function assert_exception($message = null)
	{
		if (!$this->exception) {
			throw new Dev_Unit_FailureException($message ? $message : 'failed: no exception');
		}
		return $this;
	}

	/**
	 * @param string $message
	 *
	 * @return Dev_Unit_TestCase
	 */
	protected function assert_no_exception($message = null)
	{
		if ($this->exception) {
			throw new Dev_Unit_FailureException($message ? $message : 'failed: exception trapped');
		}
		return $this;
	}

	/**
	 * @param  $object
	 *
	 * @return string
	 */
	protected function stringify($object)
	{
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

}

/**
 * Сценарий тестирования
 *
 * <p>Сценарий тестирования состоит из трех основных этапов:</p>
 * <ol><li>
 * <li>
 * <li>
 * </ol>
 *
 * @abstract
 * @package Dev\Unit
 */
abstract class Dev_Unit_TestCase extends Dev_Unit_AssertBundle
	implements Dev_Unit_RunInterface, Core_PropertyAccessInterface
{

	private $method;
	private $asserts;

	/**
	 * Конструктор
	 *
	 * @param string $method
	 */
	public function __construct($method = 'run_test')
	{
		$this->asserts = new Dev_Unit_AssertBundleLoader();
		if (!method_exists($this, $this->method = (string)$method)) {
			throw new Core_MissingMethodException($this->method);
		}
	}

	/**
	 * @param Dev_Unit_TestResult $result
	 *
	 * @return Dev_Unit_TestResult
	 */
	public function run(Dev_Unit_TestResult $result = null)
	{
		Core::with(
			$result = Core::if_null($result, Dev_Unit::TestResult())
		)->
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

		if ($is_ok) {
			try {
				call_user_func(array($this, $this->method));
			} catch (Dev_Unit_FailureException $e) {
				$result->add_failure($this, Dev_Unit_Failure::make_from($this, $e));
				$is_ok = false;
			} catch (Exception $e) {
				$result->add_error($this, Dev_Unit_Error::make_from($this, $e));
				$is_ok = false;
			}
		}

		try {
			$this->before_teardown();
			$this->teardown();
			$this->after_teardown();
		} catch (Exception $e) {
			$result->add_error($this, Dev_Unit_Error::make_from($this, $e));
			$is_ok = false;
		}

		if ($is_ok) {
			$result->add_success($this);
		}
		$result->finish_test($this);

		return $this;
	}

	/**
	 */
	protected function before_setup()
	{
	}

	/**
	 */
	protected function setup()
	{
	}

	/**
	 */
	protected function after_setup()
	{
	}

	/**
	 */
	protected function before_teardown()
	{
	}

	/**
	 */
	protected function teardown()
	{
	}

	/**
	 */
	protected function after_teardown()
	{
	}

	/**
	 * @param string $property
	 *
	 * @return mixed
	 */
	public function __get($property)
	{
		switch ($property) {
			case 'method':
			case 'asserts':
				return $this->$property;
			case 'name':
				return Core_Types::virtual_class_name_for($this) . '::' . $this->method;
			default:
				throw new Core_MissingPropertyException($property);
		}
	}

	/**
	 * @param string $property
	 * @param        $value
	 *
	 * @return Dev_Unit_TestCase
	 */
	public function __set($property, $value)
	{
		throw new Core_ReadOnlyObjectException($this);
	}

	/**
	 * @param string $property
	 *
	 * @return boolean
	 */
	public function __isset($property)
	{
		switch ($property) {
			case 'method':
			case 'name':
			case 'asserts':
				return true;
			default:
				return false;
		}
	}

	/**
	 * @param string $property
	 *
	 * @return Dev_Unit_TestCase
	 */
	public function __unset($property)
	{
		throw new Core_ReadOnlyObjectException($this);
	}

}

/**
 * @package Dev\Unit
 */
class Dev_Unit_TestSuite
	implements Dev_Unit_RunInterface, IteratorAggregate, Core_CountInterface
{

	private $tests;

	/**
	 */
	public function __construct()
	{
		$this->tests = Core::hash();
	}

	/**
	 * @param Dev_Unit_RunInterface $test
	 *
	 * @return Dev_Unit_TestSuite
	 */
	public function append(Dev_Unit_RunInterface $test)
	{
		$this->tests->append($test);
		return $this;
	}

	/**
	 * @param Dev_Unit_TestResult $result
	 *
	 * @return Dev_Unit_TestSuite
	 */
	public function run(Dev_Unit_TestResult $result = null)
	{
		$result = $result ? $result : Dev_Unit::TestResult();
		foreach ($this->tests as $test) {
			if ($result->should_stop) {
				break;
			}
			$test->run($result);
		}
		return $this;
	}

	/**
	 * @return int
	 */
	public function count()
	{
		return count($this->tests);
	}

	/**
	 * @return Iterator
	 */
	public function getIterator()
	{
		return $this->tests->getIterator();
	}

}

/**
 * @package Dev\Unit
 */
class Dev_Unit_TestLoader
{
	private $suite;
	private $prefix = '';

	public function __construct(Dev_Unit_TestSuite $suite = null)
	{
		$this->suite = $suite ? $suite : Dev_Unit::TestSuite();
	}

	public function prefix($prefix)
	{
		$this->prefix = (string)$prefix;
		return $this;
	}

	/**
	 * @return Dev_Unit_TestLoader
	 */
	public function from()
	{
		$args = func_get_args();
		foreach ((Core_Types::is_iterable($args[0]) ? $args[0] : $args) as $class) {
			$class = "{$this->prefix}$class";
			if (Core_Types::class_exists($class)) {
				$this->from_class($class);
			} else {
				try {
					Core::load($class);
				} catch (Core_ModuleException $e) {
					var_dump($e);
					Core::load(Core_Regexps::replace('{\.[a-zA-Z0-9]+$}', '', $class));
					$this->from_class($class);
					continue;
				}

				if (Core_Types::is_subclass_of('Dev.Unit.TestCase', $class)) {
					$this->from_class($class);
				} elseif (Core_Types::is_subclass_of('Dev.Unit.TestModuleInterface', $class)) {
					$this->from_suite(call_user_func(array(Core_Types::real_class_name_for($class), 'suite')));
				}
			}
		}
		return $this;
	}

	/**
	 * @param string $class
	 *
	 * @return Dev_Unit_TestLoader
	 */
	public function from_class($class)
	{
		$class = (string)$class;
		if (Core_Types::is_subclass_of('Dev.Unit.TestCase', $class)) {
			$reflection = Core_Types::reflection_for($class);
			if ($reflection->hasMethod('run_test')) {
				$this->suite->append(Core::make($class));
			} else {
				foreach ($reflection->getMethods() as $method) {
					if (Core_Strings::starts_with($method->getName(), Dev_Unit::TEST_METHOD_SIGNATURE)) {
						$this->suite->append(Core::make($class, $method->getName()));
					}
				}
			}
		}
		return $this;
	}

	/**
	 * @param string $module
	 *
	 * @return Dev_Unit_TestLoader
	 */
	public function from_module($module)
	{
		$module = (string)$module;
		if (!Core_Types::class_exists($module)) {
			Core::load($module);
		}
		$this->suite->append(call_user_func(array(Core_Types::real_class_name_for($module), 'suite')));
		return $this;
	}

	/**
	 * @param Dev_Unit_TestSuite $suite
	 *
	 * @return Dev_Unit_TestLoader
	 */
	public function from_suite(Dev_Unit_TestSuite $suite)
	{
		$this->suite->append($suite);
		return $this;
	}

	/**
	 * @return mixed
	 */
	public function __get($property)
	{
		switch ($property) {
			case 'suite':
			case 'prefix':
				return $this->$property;
			default:
				throw new Core_MissingPropertyException($property);
		}
	}

	/**
	 * @param string $property
	 * @param        $value
	 *
	 * @return mixed
	 */
	public function __set($property, $value)
	{
		switch ($property) {
			case 'prefix':
				return $this->prefix($value);
			case 'suite':
				throw new Core_ReadOnlyPropertyException($property);
			default:
				throw new Core_MissingPropertyException($property);
		}
	}

	/**
	 * @param string $property
	 *
	 * @return boolean
	 */
	public function __isset($property)
	{
		switch ($property) {
			case 'suite':
			case 'prefix':
				return isset($this->$property);
			default:
				return false;
		}
	}

	/**
	 * @param string $property
	 */
	public function __unset($property)
	{
		switch ($property) {
			case 'prefix':
			case 'suite':
				throw new Core_UndestroyablePropertyException($property);
			default:
				throw new Core_MissingPropertyException($property);
		}
	}

}

/**
 * @package Dev\Unit
 */
class Dev_Unit_AssertBundleLoader implements Core_PropertyAccessInterface
{

	private $bundles = array();

	/**
	 * @param string $property
	 *
	 * @return Dev_Unit_AssertBundle
	 */
	public function __get($property)
	{
		return isset($this->bundles[$property]) ?
			$this->bundles[$property] :
			$this->load_bundle($property);
	}

	/**
	 * @param string $property
	 * @param        $value
	 *
	 * @return Dev_Unit_AssertBundleLoader
	 */
	public function __set($property, $value)
	{
		throw new Core_ReadOnlyObjectException($this);
	}

	/**
	 * @param string $property
	 *
	 * @return boolean
	 */
	public function __isset($property)
	{
		return isset($this->bundles[$property]);
	}

	/**
	 * @param string $property
	 *
	 * @return Dev_Unit_AssertBundleLoader
	 */
	public function __unset($propety)
	{
		unset($this->bundles[$property]);
		return $this;
	}

	/**
	 * @param string $name
	 *
	 * @return Dev_Unit_AssertBundle
	 */
	private function load_bundle($name)
	{
		$mname = 'Dev.Unit.Assert.' . Core_Strings::capitalize($name);
		$mclass = Core_Types::real_class_name_for($mname);

		Core::load($mname);
		if (Core_Types::is_subclass_of('Dev.Unit.AssertBundleModuleInterface', $mclass) &&
			($bundle = call_user_func(array($mclass, 'bundle'))) instanceof Dev_Unit_AssertBundle
		) {
			return $this->bundles[$name] = $bundle;
		} else {
			throw new Dev_Unit_InvalidAssertBundleException($name);
		}
	}

}

