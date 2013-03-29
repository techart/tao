<?php
/// <module name="Validation.Commons" version="0.2.0" maintainer="timokhin@techart.ru">
///   <brief>Модуль предоставляет набор стандартных тестов валидации</brief>

Core::load('Validation');

/// <class name="Validation.Commons" stereotype="module">
///   <implements interface="Core.ModuleInterface" />
///   <depends supplier="Validation.Commons.FormatTest" stereotype="registers" />
///   <depends supplier="Validation.Commons.PresenceTest" stereotype="registers" />
///   <depends supplier="Validation.Commons.NumericalityTest" stereotype="registers" />
///   <depends supplier="Validation.Commons.InclusionTest" stereotype="registers" />
///   <depends supplier="Validation.Commons.ConfirmationTest" stereotype="registers" />
///   <depends supplier="Validation.Commons.EmailTest" stereotype="registers" />
///   <depends supplier="Validation.Commons.ContentTypeTest" stereotype="registers" />
///   <depends supplier="Validation.Commons.NumericRangeTest" stereotype="registers" />
class Validation_Commons implements Core_ModuleInterface {

///   <constants>
  const VERSION = '0.2.0';
///   </constants>

///   <protocol name="creating">
///   <method name="initialize" scope="class">
///     <brief>Инициализация модуля</brief>
///     <body>
  static public function initialize() {
  }
///     </body>
///   </method>
///   </protocol>
}
/// </class>

/// <class name="Validation.Commons.FormatTest" extends="Validation.AttributeTest">
///   <brief>Проверяет атрибут на соответствие регулярному выражению</brief>
class Validation_Commons_FormatTest extends Validation_AttributeTest {
  protected $regexp;

///   <protocol name="creating">

///   <method name="__construct">
///     <brief>Конструктор</brief>
///     <args>
///       <arg name="attribute" type="string" brief="имя атрибута" />
///       <arg name="regexp"    type="string" brief="регулярное выражение" />
///       <arg name="message"   type="string" brief="сообщение об ошибке" />
///     </args>
///     <body>
  public function __construct($attribute, $regexp, $message) {
    $this->regexp = $regexp;
    parent::__construct($attribute, $message);
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="supporting">

///   <method name="do_test" returns="boolean" access="protected">
///     <brief>Производит проверку значения атрибута</brief>
///     <args>
///       <arg name="value" brief="значение атрибута" />
///     </args>
///     <body>
  protected function do_test($value) {
    return Core_Regexps::match($this->regexp, $value);
  }
///     </body>
///   </method>

///   </protocol>
}
/// </class>


/// <class name="Validation.Commons.EmailTest" extends="Validation.Commons.FormatTest">
///   <brief>Проверяет атрибута на соответствие Email адресу</brief>
class Validation_Commons_EmailTest extends Validation_Commons_FormatTest {

///   <protocol name="creating">

///   <method name="__construct">
///     <brief>Конструктор</brief>
///     <args>
///       <arg name="attribute" type="string" brief="имя атрибута" />
///       <arg name="message"   type="string" brief="сообщение об ошибке" />
///     </args>
///     <body>
  public function __construct($attribute, $message) {
    parent::__construct($attribute, '{^$|^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,4}$}' , $message);
  }
///     </body>
///   </method>

///   </protocol>
}
/// </class>


/// <class name="Validation.Commons.ConfirmationTest" extends="Validation.AbstractTest">
///   <brief>Проверяет на соответствие два атрибута объекта</brief>
class Validation_Commons_ConfirmationTest extends Validation_AbstractTest {
  protected $attribute;
  protected $confirmation;
  protected $message;

///   <protocol name="creating">

///   <method name="__construct">
///     <brief>Конструктор</brief>
///     <args>
///       <arg name="attribute" type="string" brief="имя атрибута" />
///       <arg name="confirmation" type="string" brief="имя атрибута-соответствия" />
///       <arg name="message" type="string" brief="сообщение об ошибке" />
///     </args>
///     <body>
  public function __construct($attribute, $confirmation, $message) {
    $this->attribute    = $attribute;
    $this->confirmation = $confirmation;
    $this->message      = $message;
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="performing">

///   <method name="test" returns="boolean">
///     <brief>Производит проверку</brief>
///     <args>
///       <arg name="object" brief="объект" />
///       <arg name="errors" type="Validation.Errors" brief="ошибки" />
///       <arg name="array_access" type="boolean" default="false" brief="флаг индексного доступа к объекту" />
///     </args>
///     <body>
  public function test($object, Validation_Errors $errors, $array_access = false) {
    if ($this->value_of_attribute($object, $this->attribute, $array_access) != $this->value_of_attribute($object, $this->confirmation, $array_access)) {
      $errors->
        reject_value($this->attribute, $this->message);
      return false;
    }
    return true;
  }
///     </body>
///   </method>

///   </protocol>
}
/// </class>


/// <class name="Validation.Commons.ContentTypeTest" extends="Validation.AttributeTest">
///   <brief>Проверяет content type файла (IO.FS.File) </brief>
class Validation_Commons_ContentTypeTest extends Validation_AttributeTest {
  protected $content_type;

///   <protocol name="creating">

///   <method name="__construct">
///     <brief>Конструктор</brief>
///     <args>
///       <arg name="attribute"    type="string" brief="имя атрибута" />
///       <arg name="content_type" type="string" />
///       <arg name="message"      type="string" brief="сообщение об ошибке" />
///     </args>
///     <body>
  public function __construct($attribute, $content_type, $message) {
    $this->content_type = $content_type;
    parent::__construct($attribute, $message);
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="supporting">

///   <method name="do_test" returns="boolean" access="protected">
///     <brief>Проверяет content type</brief>
///     <args>
///       <arg name="value" brief="значение атрибута" />
///     </args>
///     <body>
  protected function do_test($value) {
    return ($value instanceof IO_FS_File) &&
           Core_Regexps::match("^$this->content_type", $value->content_type);
  }
///     </body>
///   </method>

///   </protocol>
}
/// </class>


/// <class name="Validation.Commons.PresenceTest" extends="Validation.AttributeTest">
///   <brief>Проверяет атрибут на незаполненность</brief>
class Validation_Commons_PresenceTest extends Validation_AttributeTest {
///   <protocol name="supporting">
///   <method name="do_test" returns="boolean" access="protected">
///     <brief>Проверяет атрибут на отсутствие значения</brief>
///     <args>
///       <arg name="value" brief="значение атрибута" />
///     </args>
///     <body>
  protected function do_test($value) { return $value ? true : false; }
///     </body>
///   </method>
///   </protocol>
}
/// </class>

/// <class name="Validation.Commons.NumericalityTest" extends="Validation.AttributeTest">
///   <brief>Проверяет значение атрибута на принадлежность к числам</brief>
class Validation_Commons_NumericalityTest extends Validation_AttributeTest {
///   <protocol name="supporting">

///   <method name="do_test" returns="boolean" access="protected">
///     <brief>Осуществляет проверку</brief>
///     <args>
///       <arg name="value" brief="значение атрибута" />
///     </args>
///     <body>
  protected function do_test($value) { return $value == NULL || Core_Types::is_number($value); }
///     </body>
///   </method>

///   </protocol>
}
/// </class>

/// <class name="Validation.Commons.NumericRangeTest" extends="Validation.AttributeTest">
///   <brief>Проверяет атрибут на вхождение в заданный интервал</brief>
class Validation_Commons_NumericRangeTest extends Validation_AttributeTest {
  protected $from;
  protected $to;

///   <protocol name="creating">

///   <method name="__construct">
///     <brief>Конструктор</brief>
///   <args>
///     <arg name="name"    type="number" brief="имя атрибута" />
///     <arg name="from"    type="number" brief="начало интервала" />
///     <arg name="to"      type="number" brief="конеуц интервала" />
///     <arg name="message" type="string" brief="сообщение об ошибке" />
///   </args>
///   <body>
  public function __construct($name, $from, $to, $message) {
    $this->from = $from;
    $this->to   = $to;
    parent::__construct($name, $message);
  }
///   </body>
///   </method>

///   </protocol>

///   <protocol name="supporting">

///   <method name="do_test" returns="boolean" access="protected">
///     <brief>Производит проверку</brief>
///     <args>
///       <arg name="value" brief="значение атрибута" />
///     </args>
///     <body>
  protected function do_test($value) {
    return Core_Types::is_number($value) && ($value >= $this->from) && ($value <= $this->to);
  }
///     </body>
///   </method>

///   </protocol>
}
/// </class>

/// <class name="Validation.Commons.InclusionTest" extends="Validation.AttributeTest">
///   <brief>Проверяет вхождение значения атрибута в заданный набор значений</brief>
class Validation_Commons_InclusionTest extends Validation_AttributeTest {
  protected $values;
  protected $options = array( 'attribute' => false );

///   <protocol name="creating">

///   <method name="__construct">
///     <args>
///       <arg name="attribute" type="string" brief="имя атрибута" />
///       <arg name="values" brief="набор значений" />
///       <arg name="message" brief="сообщение об ошибке" />
///       <arg name="options" type="array" default="array()" brief="массив атрибутов" />
///     </args>
///     <body>
  public function __construct($attribute, $values, $message, array $options = array()) {
    $this->values = $values;
    Core_Arrays::update($this->options, $options);
    parent::__construct($attribute, $message);
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="supporting">

///   <method name="do_test" returns="boolean" access="protected">
///     <brief>Осуществляет проверку</brief>
///     <args>
///       <arg name="value" type="string" brief="значение атрибута" />
///     </args>
///     <body>
  protected function do_test($value) {
    foreach ($this->values as $v)
      if ($this->is_equal($v, $value)) return true;
    return false;
  }
///     </body>
///   </method>

///   <method name="is_equal" returns="boolean" access="protected">
///     <args>
///       <arg name="arg1" />
///       <arg name="arg2" />
///     </args>
///     <body>
  protected function is_equal($arg1, $arg2) {
    return ($attribute = $this->options['attribute']) ?
      $arg1->$attribute == $arg2->$attribute :
      $arg1 == $arg2;
  }
///     </body>
///   </method>

/// </protocol>
}
/// </class>

/// </module>
