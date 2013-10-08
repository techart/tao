<?php
/**
 * Набор классов для валидации объектов
 *
 * @package Validation
 */

Core::load('Object');

/**
 * Класс модуля
 * 
 * @package Validation 
 */
class Validation implements Core_ModuleInterface {

/**
 * Версия модуля
 */
	const VERSION = '0.2.1';

/**
 * @var Object_Factory Фабрика тестов
 */
	static protected $test_factory;

/**
 * Инициализация.
 * 
 * @see Validation.Commons
 * @uses Object::Factory()
 * 
 * Создает фабрику тестов, которая будет производить поля зарегестрированые с помощью метода use_tests
 * Подгружает модуль Validation.Commons, содержащий стандартный набор тестов.
 * Тест в данном случае - класс производящий ту или иную проверку.
 */
	static public function initialize() {
		self::$test_factory = Object::Factory();
		Validation::use_tests(array(
			'validate_format_of'       => 'FormatTest',
			'validate_presence_of'     => 'PresenceTest',
			'validate_numericality_of' => 'NumericalityTest',
			'validate_inclusion_of'    => 'InclusionTest',
			'validate_range_of'        => 'NumericRangeTest',
			'validate_confirmation_of' => 'ConfirmationTest',
			'validate_email_for'       => 'EmailTest',
			'validate_content_type_of' => 'ContentTypeTest' ), 'Validation.Commons.');

	}

	/**
	 * Регистрирует тесты в соответствующей фабрике.
	 * 
	 * @see Object_Factory::map()
	 * 
	 * @param array $binds массив регистрируемых классов
	 * @param string $prefix префикс для регистрируемых классов
	 * 
	 */
	static public function use_tests(array $binds, $prefix = '') {
		self::$test_factory->map($binds, $prefix);
	}

	/**
	 * Регистрирует тест в соответствующей фабрике.
	 * 
	 * @see Object_Factory::map()
	 * 
	 * @param string $name имя для доступа к классу
	 * @param string $type имя для регистрируемого класса
	 * 
	 */
	static public function use_test($name, $type) { self::$test_factory->map($name, $type); }

	/**
	 * Фабричный метод
	 * 
	 * @return Validation_Validator
	 */
	static public function Validator() { return new Validation_Validator(); }

	/**
	 * Возвращает тесты, производимые фабрикой self::$test_factory
	 * 
	 * @param string $name имя теста
	 * @param array $args массив атрибутов
	 */
	static public function make_test($name, array $args) {
		return self::$test_factory->new_instance_of($name, $args);
	}
}

/**
 * Класс исключения
 * 
 * @package Validation 
 */
class Validation_Exception extends Core_Exception {}


/**
 * Класс представляющий ошибки валидации
 * 
 * @package Validation 
 */
class Validation_Errors implements Core_PropertyAccessInterface {
	
	/** @var ArrayObject Глобальные ошибки */
	protected $global_errors;
	
	/** @var ArrayObject Ошибки свойств */
	protected $property_errors;

	/** 
	 * Конструктор
	 * 
	 * Инициализирует свойства пустыми объектами ArrayObject
	 */
	public function __construct() {
		$this->global_errors   = new ArrayObject();
		$this->property_errors = new ArrayObject();
	}

	/**
	 * Записывает глобальную ошибку
	 * 
	 * @param string $message сообщение об ошибке
	 * 
	 * @return self
	 */
	public function reject($message) {
		$this->global_errors[] = (string) $message;
		return $this;
	}

	/**
	 * Записывает ошибку проверки свойства
	 * 
	 * @param string $property имя свойства
	 * @param string $message сообщение об ошибке
	 * 
	 * @return self
	 */
	public function reject_value($property, $message) {
		$this->property_errors[$property] = $message;
		return $this;
	}

	/**
	 * Проверяет есть ли ошибки
	 * 
	 * @return boolean
	 */
	public function has_errors() {
		return (count($this->global_errors) + count($this->property_errors)) > 0;
	}
	/**
	 * Проверяет есть ли ошибки относящиеся к свойству $field
	 * 
	 * @param string $field имя свойства
	 * 
	 * @return boolean
	 */
	public function has_error_for($field) {
		return isset($this->property_errors[$field]);
	}

	/**
	 * Доступ на чтение к свойствам объекта.
	 * 
	 * @param string $property Может быть одним из двух значений: 
	 * - global_errors - возвращает ArrayObject глобальных ошибок;
	 * - property_errors - возвращает ArrayObject ошибок свойств.
	 * 
	 * @throws Core_MissingPropertyException Если параметр имеет любое другое значение.
	 * 
	 * @return ArrayObject
	 */
	public function __get($property) {
		switch ($property) {
			case 'global_errors':   return $this->global_errors;
			case 'property_errors': return $this->property_errors;
			default:
				throw new Core_MissingPropertyException($property);
		}
	}

	/**
	 * Доступ на запись к свойствам объекта.
	 * 
	 * @throws Core_ReadOnlyObjectException Всегда - доступ только для чтения.
	 */
	public function __set($property, $value) { throw new Core_ReadOnlyObjectException($this); }

	/**
	 * Проверяет установленно ли свойство объекта
	 * 
	 * @param string $property имя свойства.  Может быть одним из двух значений: 
	 * global_errors и property_errors возвращается true.
	 * Иначе - false
	 * 
	 * @return boolean
	 */
	public function __isset($property) {
		switch ($property) {
			case 'global_errors':
			case 'property_errors':
				return true;
			default:
				return false;
		}
	}

	/**
	 * Очищает свойство объекта.
	 * 
	 * @throws Core_ReadOnlyObjectException Всегда - доступ только для чтения.
	 */
	public function __unset($property) { throw new Core_ReadOnlyObjectException($this); }
}


/**
 * Абстактный класс теста валидации.
 * 
 * @package Validation
 */
abstract class Validation_AbstractTest {
	/**
	 * Производит проверку объекта
	 * 
	 * @param mixed $object объект проверки
	 * @param Validation_Errors $errors Класс представляющий ошибки валидации
	 * @param boolean $array_access флаг индексного доступа к объекту
	 * 
	 * @uses Validation_Errors
	 * @see Validation_AttributeTest::test()
	 */
	abstract public function test($object, Validation_Errors $errors, $array_access = false);

	/** 
	 * Возвращает значение атрибута объекта.
	 * 
	 * @param mixed $object Если не является объектом или массивом, то становится возвращаемым значением.
	 * @param string $attribute имя атрибута
	 * @param boolean $array_access флаг индексного доступа к объекту
	 * 
	 * @return mixed
	 */
	protected function value_of_attribute($object, $attribute, $array_access = false) {
		return Core_Types::is_object($object) ?
			($array_access ? $object[$attribute] : $object->$attribute) :
			(Core_Types::is_array($object) ? $object[$attribute] : $object);
	}
}

/**
 * Тест атрибута объекта
 * 
 * Класс является предком для всех классов модуля Commons. 
 * Каждый из классов модуля Commons переопределяется метод do_test 
 * для своих нужд.
 * 
 * @package Validation
 */
abstract class Validation_AttributeTest extends Validation_AbstractTest {
	/** @var string $attribute имя атрибута */
	protected $attribute;
  
	/** @var string $message сообщение об ошибке */
	protected $message;

	/**
	 * Конструктор
	 * 
	 * @param string $attribute имя атрибута
	 * @param string $message сообщение об ошибке
	 */
	public function __construct($attribute, $message) {
		$this->attribute = $attribute;
		$this->message   = $message;
	}

	/**
	 * Производит проверку объекта.
	 * 
	 * @uses Validation_Errors
	 * Вызывает определяемую в каждом классе-наследнике функцию do_test. 
	 * 
	 * @param mixed $object объект проверки
	 * @param Validation_Errors $errors Класс представляющий ошибки валидации
	 * @param boolean $array_access флаг индексного доступа к объекту
	 * 
	 * @return mixed
	 */
	public function test($object, Validation_Errors $errors, $array_access = false) {
		if (!$result = $this->do_test($this->value_of_attribute($object, $this->attribute, $array_access)))  {
			$errors->reject_value($this->attribute, $this->message);
		}
		return $result;
	}

	/** 
	 * Производит проверку значения атрибута.
	 * 
	 * Должен быть переопределен в каждом конкретном классе проверки.
	 * 
	 * @param mixed $value Значение атрибута
	 */
	abstract protected function do_test($value);
}

/**
 * Валидатор
 * 
 * @package Validation
 */
class Validation_Validator {

	/** @var Validation_Errors ошибки валидации */
	protected $errors;
	
	/** @var ArrayObject Набор тестов */
	protected $tests;

	/** 
	 * Конструктор
	 * 
	 * Инициализирует свойства пустыми объектами
	 */
	public function __construct() {
		$this->errors = new Validation_Errors();
		$this->tests  = new ArrayObject();
		$this->setup();
	}

	/**
	 * Предустановки
	 * 
	 * @return self
	 */
	protected function setup() { return $this; }

	/**
	 * Проверяет объект всеми тестами
	 * 
	 * @param object $object объект проверки
	 * @param boolean $array_access флаг индексного доступа к объекту
	 * 
	 * @return boolean Успешно прошли тесты или нет
	 */
	public function validate($object, $array_access = false) {
		foreach ($this->tests as $test)
			$test->test($object, $this->errors, (boolean) $array_access);
			
		return $this->is_valid();
	}

	/**
	 * Проверяет прошла ли валидация.
	 * 
	 * Возвращает значение, обратное значению is_invalid.
	 * 
	 * @param string $field имя свойства
	 * 
	 * @return boolean
	 */
	public function is_valid($field = null) {
		return ! $this->is_invalid($field);
	}

	/**
	 * Проверяет прошла ли валидация.
	 * 
	 * Возвращает значение, обратное значению is_valid.
	 * 
	 * @param string $field имя свойства
	 * 
	 * @return boolean
	 */
	public function is_invalid($field = null) {
		return $field ?
			$this->errors->has_error_for((string) $field) :
			$this->errors->has_errors();
	}

	/**
	 * Доступ на чтение к свойствам объекта.
	 * 
	 * @param string $property Может иметь следующие значения:
	 * - errors - вернет Validation_Errors,
	 * - global_errors - вернет ArrayObject глобальных ошибок,
	 * - property_errors - вернет ArrayObject ошибок свойств.
	 * 
	 * @throws Core_MissingPropertyException Если любое другое значение
	 * 
	 * @return Validation_Errors|ArrayObject
	 */
	public function __get($property) {
		switch ($property) {
			case 'errors':          return $this->errors;
			case 'global_errors':   return $this->errors->global_errors;
			case 'property_errors': return $this->errors->property_errors;
			default:
				throw new Core_MissingPropertyException($property);
		}
	}

	/**
	 * Доступ на запись к свойствам объекта
	 * 
	 * @throws Core_ReadOnlyObjectException Всегда - разрешен доступ только для чтения.
	 */
	public function __set($property, $value) { throw new Core_ReadOnlyObjectException($this); }

	/**
	 * Проверяет установленно ли свойcтво.
	 * 
	 * @param string $property Если имеет значения:
	 * - errors,
	 * - global_errors,
	 * - property_errors
	 * то вернет true. Во всех остальных случаях false.
	 * 
	 * @return boolean
	 */
	public function __isset($property) {
		switch ($property) {
			case 'errors':
			case 'global_errors':
			case 'property_errors':
				return true;
			default:
				return false;
		}
	}

	/**
	 * Очищает свойство объекта
	 * 
	 * @throws Core_ReadOnlyObjectException Всегда - разрешен доступ только для чтения.
	 */
	public function __unset($property) { throw new Core_ReadOnlyObjectException($this); }

	/**
	 * С помощью вызова метода можно установить тест валидации.
	 * 
	 * @see Validation::make_test()
	 * @see Validation::initialize()
	 * 
	 * @example testCreateExample.inc Пример создания теста объекта на пустое значение.
	 * 
	 * @param string $method имя метода
	 * @param array $args массив атрибутов метода - эти параметры передадутся конструктору теста.
	 * 
	 * @return self
	 */
	public function __call($method, $args) {
		$this->tests[] = Validation::make_test($method, $args);
		return $this;
	}
}
