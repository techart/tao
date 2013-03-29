<?php
/// <module name="Validation" version="0.2.1" maintainer="timokhin@techart.ru">
///   <brief>Модуль предоставляет классы для валидации объектов</brief>

Core::load('Object');

/// <class name="Validation" stereotype="module">
///   <implements interface="Core.ModuleInterface" />
///   <depends supplier="Validation.Validator" stereotype="creates" />
class Validation implements Core_ModuleInterface {

///   <constants>

  const VERSION = '0.2.1';
///   </constants>

  static protected $test_factory;

///   <protocol name="creating">

///   <method name="initialize" scope="class">
///     <brief>Инициализация</brief>
///     <details>
///       Создает фабрику тестов, которая будет производить поля зарегестрированые с помощью метода use_tests
///       Подгружает модуль Validation.Commons, содержащий стандартный набор тестов.
///       Тест в данном случае - класс производящий ту или иную проверку.
///     </details>
///     <body>
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
///     </body>
///   </method>

///   </protocol>

///   <protocol name="configuring">

///   <method name="use_tests" scope="class">
///     <brief>Регестрирует тест в соостветствующей фабрике</brief>
///     <args>
///       <arg name="binds" type="array" brief="массив регистрируемых классов" />
///       <arg name="prefix" type="string" default="''" brief="префикс для регистрируемых классов" />
///     </args>
///     <body>
  static public function use_tests(array $binds, $prefix = '') {
    self::$test_factory->map($binds, $prefix);
  }
///     </body>
///   </method>

///   <method name="use_test" scope="class">
///     <args>
///       <arg name="name" type="string" />
///       <arg name="type" type="string" />
///     </args>
///     <body>
  static public function use_test($name, $type) { self::$test_factory->map($name, $type); }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="building">

///   <method name="Validator" scope="class">
///     <brief>Фабричный метод, возвращает объект класса Validation.Validator</brief>
///     <body>
  static public function Validator() { return new Validation_Validator(); }
///     </body>
///   </method>

///   <method name="make_test" scope="class">
///     <brief>Возвращает тесты производимое фабрикой self::$test_factory</brief>
///     <args>
///       <arg name="name" type="string" brief="имя теста" />
///       <arg name="args" type="array" brief="массив атрибутов" />
///     </args>
///     <body>
  static public function make_test($name, array $args) {
    return self::$test_factory->new_instance_of($name, $args);
  }
///     </body>
///   </method>

///   </protocol>
}
/// </class>

/// <composition>
///   <source class="Validation" role="module" multiplicity="1" />
///   <target class="Object.Factory" role="factory" multiplicity="1" />
/// </composition>

/// <class name="Validation.Exception" extends="Core.Exception" stereotype="exception">
///   <brief>Класс исключения</brief>
class Validation_Exception extends Core_Exception {}
/// </class>


/// <class name="Validation.Errors">
///   <brief>Класс представляющий ошибки валидации</brief>
///   <implements interface="Core.PropertyAccessInterface" />
class Validation_Errors implements Core_PropertyAccessInterface {

  protected $global_errors;
  protected $property_errors;

///   <protocol name="creating">

///   <method name="__construct">
///     <brief>Конструктор</brief>
///     <body>
  public function __construct() {
    $this->global_errors   = new ArrayObject();
    $this->property_errors = new ArrayObject();
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="performing">

///   <method name="reject">
///     <brief>Записывает глобальную ошибку</brief>
///     <args>
///       <arg name="message" type="string" brief="сообщение об ошибке" />
///     </args>
///     <body>
  public function reject($message) {
    $this->global_errors[] = (string) $message;
    return $this;
  }
///     </body>
///   </method>

///   <method name="reject_value">
///     <brief>Записывает ошибку проверки свойства</brief>
///     <args>
///       <arg name="property" type="string" brief="имя свойства" />
///       <arg name="message"  type="string" brief="сообщение об ошибке" />
///     </args>
///     <body>
  public function reject_value($property, $message) {
    $this->property_errors[$property] = $message;
    return $this;
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="quering">

///   <method name="has_errors" returns="boolean">
///     <brief>Проверяет есть ли ошибки</brief>
///     <body>
  public function has_errors() {
    return (count($this->global_errors) + count($this->property_errors)) > 0;
  }
///     </body>
///   </method>

///   <method name="has_error_for" returns="boolean">
///     <brief>Проверяет есть ли ошибки относящиеся к свойству $field</brief>
///     <args>
///       <arg name="field" type="string" brief="имя свойства" />
///     </args>
///     <body>
  public function has_error_for($field) {
    return isset($this->property_errors[$field]);
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="accessing" interface="Core.PropertyAccessInterface">

///   <method name="__get" returns="mixed">
///     <brief>Доступ на чтение к свойствам объекта</brief>
///     <details>
///     <dl>
///       <dt>global_errors</dt><dd> ArrayObject глобальных ошибок </dd>
///       <dt>property_errors</dt><dd> ArrayObject ошибок свойств </dd>
///     </dl>
///     </details>
///     <args>
///       <arg name="property" type="string" brief="имя свойства" />
///     </args>
///     <body>
  public function __get($property) {
    switch ($property) {
      case 'global_errors':   return $this->global_errors;
      case 'property_errors': return $this->property_errors;
      default:
        throw new Core_MissingPropertyException($property);
    }
  }
///     </body>
///   </method>

///   <method name="__set" returns="mixed">
///     <brief>Доступ на запись к свойствам объекта</brief>
///     <details>
///       Выбрасывает исключение, доступ только для чтения
///     </details>
///     <args>
///       <arg name="property" type="string" brief="имя свойства" />
///       <arg name="value" brief="значение" />
///     </args>
///     <body>
  public function __set($property, $value) { throw new Core_ReadOnlyObjectException($this); }
///     </body>
///   </method>

///   <method name="__isset" returns="boolean">
///     <brief>Проверяет истановленно ли свойство объекта</brief>
///     <args>
///       <arg name="property" type="string" brief="имя свойства" />
///     </args>
///     <body>
  public function __isset($property) {
    switch ($property) {
      case 'global_errors':
      case 'property_errors':
        return true;
      default:
        return false;
    }
  }
///     </body>
///   </method>

///   <method name="__unset">
///     <brief>Очищает свойство объекта</brief>
///     <details>
///       Выбрасывает исключение, доступ только для чтения
///     </details>
///     <args>
///       <arg name="property" type="string" brief="имя свойства" />
///     </args>
///     <body>
  public function __unset($property) { throw new Core_ReadOnlyObjectException($this); }
///     </body>
///   </method>

///   </protocol>
}
/// </class>


/// <class name="Validation.AbstractTest" stereotype="abstract">
///   <brief>Абстактный класс теста валидации</brief>
///   <depends supplier="Validation.Errors" stereotype="uses" />
abstract class Validation_AbstractTest {
///   <protocol name="performing">

///   <method name="test" returns="boolean">
///     <brief>Производит проверку объекта</brief>
///     <args>
///       <arg name="object" brief="объект проверки" />
///       <arg name="errors" type="Validation.Errors" brief="ошибки" />
///       <arg name="array_access" type="boolean" default="false" brief="флаг индексного доступа к объекту" />
///     </args>
///     <body>
  abstract public function test($object, Validation_Errors $errors, $array_access = false);
///     </body>
///   </method>

///   </protocol>

///   <protocol name="supporting">

///   <method name="value_of_attribute" returns="mixed" access="protected">
///     <brief>Возвращает значение атрибута объекта</brief>
///     <args>
///       <arg name="object" brief="объект" />
///       <arg name="attribute" type="string" brief="имя атрибута" />
///       <arg name="array_access" type="boolean" default="false" brief="флаг индексного доступа к объекту" />
///     </args>
///     <body>
  protected function value_of_attribute($object, $attribute, $array_access = false) {
    return Core_Types::is_object($object) ?
      ($array_access ? $object[$attribute] : $object->$attribute) :
      (Core_Types::is_array($object) ? $object[$attribute] : $object);
  }
///     </body>
///   </method>

///   </protocol>
}
/// </class>


/// <class name="Validation.AttributeTest" extends="Validation.AbstractTest" stereotype="abstract">
///   <brief>Тест атрибута объекта</brief>
abstract class Validation_AttributeTest extends Validation_AbstractTest {
  protected $attribute;
  protected $message;

///   <protocol name="creating">

///   <method name="__construct">
///     <brief>Конструктор</brief>
///     <args>
///       <arg name="attribute" type="string" brief="имя атрибута" />
///       <arg name="message" type="string" brief="сообщеине об ошибке" />
///     </args>
///     <body>
  public function __construct($attribute, $message) {
    $this->attribute = $attribute;
    $this->message   = $message;
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="performing">

///   <method name="test" returns="boolean">
///     <brief>Производит проверку объекта</brief>
///     <args>
///       <arg name="object" brief="объект" />
///       <arg name="errors" type="Validation.Errors" brief="ошибки" />
///       <arg name="array_access" type="boolean" default="false" brief="флаг индексного доступа к объекту" />
///     </args>
///     <body>
  public function test($object, Validation_Errors $errors, $array_access = false) {
    if (!$result = $this->do_test($this->value_of_attribute($object, $this->attribute, $array_access)))  {
      $errors->reject_value($this->attribute, $this->message);
    }
    return $result;
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="supporting">

///   <method name="do_test" returns="boolean" access="protected" stereotype="abstract">
///     <brief>Производит проверку значения атрибута</brief>
///     <args>
///       <arg name="value" brief="значение атрибута" />
///     </args>
///     <body>
  abstract protected function do_test($value);
///     </body>
///   </method>

///   </protocol>
}
/// </class>

/// <class name="Validation.Validator">
///   <brief>Валидатор</brief>
///   <depends supplier="Validations" stereotype="uses" />
class Validation_Validator {
  protected $errors;
  protected $tests;

///   <protocol name="creating">

///   <method name="__construct">
///     <brief>Конструктор</brief>
///     <body>
  public function __construct() {
    $this->errors = new Validation_Errors();
    $this->tests  = new ArrayObject();
    $this->setup();
  }
///     </body>
///   </method>

///   <method name="setup" access="protected" returns="Validation.Validator">
///     <brief>Предустановки</brief>
///     <body>
  protected function setup() { return $this; }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="performing">

///   <method name="validate" returns="boolean">
///     <brief>Проверяет объект всеми тестами</brief>
///     <args>
///       <arg name="object" brief="объект проверки" />
///       <arg name="array_access" type="boolean" default="false" brief="флаг индексного доступа к объекту" />
///     </args>
///     <body>
  public function validate($object, $array_access = false) {
    foreach ($this->tests as $test)
      $test->test($object, $this->errors, (boolean) $array_access);
    return $this->is_valid();
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="quering">

///   <method name="is_valid" returns="boolean">
///     <brief>Проверяет прошла ли валидация</brief>
///     <args>
///       <arg name="field" type="string" default="null" brief="имя свойства" />
///     </args>
///     <body>
  public function is_valid($field = null) {
    return !$this->is_invalid($field);
  }
///     </body>
///   </method>

///   <method name="is_invalid" returns="boolean">
///     <brief>Проверяет не прошла ли валидация</brief>
///     <args>
///       <arg name="field" type="string" default="null" brief="имя свойства" />
///     </args>
///     <body>
  public function is_invalid($field = null) {
    return $field ?
      $this->errors->has_error_for((string) $field) :
      $this->errors->has_errors();
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="accessing" interface="Core.PropertyAccessInterface">

///   <method name="__get" returns="mixed">
///     <brief>Доступ на чтение к свойствам объекта</brief>
///     <details>
///       <dl>
///         <dt>errors</dt><dd>ошибки Validation.Errors</dd>
///         <dt>global_errors</dt><dd>ArrayObject глобальных ошибок</dd>
///         <dt>property_errors</dt><dd>ArrayObject ошибок свойств</dd>
///       </dl>
///     </details>
///     <args>
///       <arg name="property" type="string" brief="имя свойства" />
///     </args>
///     <body>
  public function __get($property) {
    switch ($property) {
      case 'errors':          return $this->errors;
      case 'global_errors':   return $this->errors->global_errors;
      case 'property_errors': return $this->errors->property_errors;
      default:
        throw new Core_MissingPropertyException($property);
    }
  }
///     </body>
///   </method>

///   <method name="__set" returns="mixed">
///     <brief>Доступ на запись к свойствам объекта</brief>
///     <details>
///       Выбрасывает исключение, доступ только для чтения
///     </details>
///     <args>
///       <arg name="property" type="string" brief="имя свойства" />
///       <arg name="value" brief="значение" />
///     </args>
///     <body>
  public function __set($property, $value) { throw new Core_ReadOnlyObjectException($this); }
///     </body>
///   </method>

///   <method name="__isset" returns="boolean">
///     <brief>Проверяет установленно ли свойтсво</brief>
///     <args>
///       <arg name="property" type="string" brief="имя свойства" />
///     </args>
///     <body>
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
///     </body>
///   </method>

///   <method name="__unset">
///     <brief>Очищает свойство объекта</brief>
///     <args>
///       <arg name="property" type="string" brief="имя свойства" />
///     </args>
///     <body>
  public function __unset($property) { throw new Core_ReadOnlyObjectException($this); }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="calling" interface="Core.CallInterface">

///   <method name="__call" returns="Validation.Validator">
///     <brief>С помощью вызова метода можно установить тест валидации</brief>
///     <details>
///       Тесты создаются с помощью фабрики Validatios::$test_factory
///     </details>
///     <args>
///       <arg name="method" type="string" brief="имя метода" />
///       <arg name="args" type="array" brief=" массив атрибутов метода" />
///     </args>
///     <body>
  public function __call($method, $args) {
    $this->tests[] = Validation::make_test($method, $args);
    return $this;
  }
///     </body>
///   </method>

///   </protocol>
}
/// </class>
/// <composition>
///   <source class="Validation.Validator" role="validator" multiplicity="1" />
///   <target class="Validation.Errors" role="errors" multiplicity="1" />
/// </composition>

/// </module>
