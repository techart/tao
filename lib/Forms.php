<?php
/// <module name="Forms" version="0.2.1" maintainer="timokhin@techart.ru">
///   <brief>В модуле определены класс формы и абстрактный класс поля</brief>
///   <details>
///     Форма предназначена для объектного представления HTML формы, со стандартными HTML поля ввода,
///     такими как input, checkbox и т.д.
///   </details>
Core::load('Net.HTTP', 'Validation', 'Object');

/// <class name="Forms" stereotype="module">
class Forms implements Core_ModuleInterface {

///   <constants>
  const VERSION = '0.2.1';
///   </constants>

  static protected $fields_factory;

///   <protocol name="creating">

///   <method name="initialize" scope="class">
///     <brief>Метод инициализации</brief>
///     <details>
///       Создает фабрику полей, которая будет производить поля зарегестрированые с помощью метода register_field_types
///       Подгружает модуль Forms.Commons, содержащий стандартный набор полей для формы.
///     </details>
///     <body>
  static public function initialize() {
    self::$fields_factory = Object::Factory();
    Forms::register_field_types(array(
      'input'                  => 'StringField',
      'password'               => 'PasswordField',
      'textarea'               => 'TextAreaField',
      'checkbox'               => 'CheckBoxField',
      'datetime'               => 'DateTimeField',
      'object_select'          => 'ObjectSelectField',
      'object_multi_select'    => 'ObjectMultiSelectField',
      'select'                 => 'SelectField',
      'upload'                 => 'UploadField'
      ), 'Forms.Commons.');
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="configuring">

///   <method name="register_field_types">
///     <brief>Регистрирует поле в соответствующей фабрике</brief>
///     <args>
///       <arg name="binds" type="array" brief="массив регистрируемых классов" />
///       <arg name="prefix" type="string" default="''" brief="префикс для регистрируемых классов" />
///     </args>
///     <body>
  static public function register_field_types(array $binds, $prefix = '') {
    self::$fields_factory->map_list($binds, $prefix);
  }
///     </body>
///   </method>

///   <method name="bind_field_types" scope="class">
///     <args>
///       <arg name="binds" type="array" />
///       <arg name="prefix" type="string" default="''" />
///     </args>
///     <body>
  static public function bind_field_types(array $binds, $prefix = '') {
    self::register_field_types($binds, $prefix);
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="building">

///   <method name="field" scope="class" returns="Forms.AbstractField">
///     <brief>Возвращает поле производимое фабрикой self::$fields_factory</brief>
///     <args>
///       <arg name="type" type="string" brief="тип поля" />
///       <arg name="args" type="array" brief="массив аргументов" />
///     </args>
///     <body>
  static public function field($type, $args) { return self::$fields_factory->new_instance_of($type, $args); }
///     </body>
///   </method>

///   <method name="make_field" scope="class">
///     <args>
///       <arg name="name" type="string" />
///       <arg name="args" type="array" />
///     </args>
///     <body>
  static public function make_field($type, $args) {
    return self::field($type, $args);
  }
///     </body>
///   </method>

///   <method name="Form" returns="Forms.Form" scope="class">
///     <brief>Фабричный метод, возвращает объект класса Forms.Form</brief>
///     <args>
///       <arg name="name" type="string" brief="имя формы" />
///     </args>
///     <body>
  static public function Form($name) { return new Forms_Form($name); }
///     </body>
///   </method>

///   </protocol>
}
/// </class>

/// <class name="Forms.Form">
///     <brief>Класс формы</brief>
///   <implements interface="Core.PropertyAccessInterface" />
///   <implements interface="Core.IndexedAccessInterface" />
///   <implements interface="Core.CallInterface" />
class Forms_Form
  implements Core_PropertyAccessInterface,
             Core_IndexedAccessInterface,
             Core_CallInterface {

  protected $name;
  protected $fields;
  protected $validator;

  protected $options = array(
    'action'  => '',
    'method'  => Net_HTTP::POST,
    'enctype' => 'application/x-www-form-urlencoded' );

///   <protocol name="creating">

///   <method name="__construct">
///     <brief>Конструктор</brief>
///     <details>
///       После установки свойств вызывает метод setup
///     </details>
///     <args>
///       <arg name="name" type="string" brief="имя формы" />
///     </args>
///     <body>
  public function __construct($name) {
    $args = func_get_args();
    $this->name = (string) $name;
    $this->fields = Core::hash();
    call_user_func_array(array($this, 'setup'), array_slice($args, 1));
  }
///     </body>
///   </method>

///   <method name="setup" returns="Forms.Form" access="protected">
///     <brief>Метод, предназначенный для переопределения в производном классе </brief>
///     <body>
  protected function setup() { return $this; }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="configuring">

///   <method name="multipart" returns="Forms.Form">
///     <brief>Устанавливает опцию enctype в multipart/form-data </brief>
///     <body>
  public function multipart() { return $this->enctype('multipart/form-data'); }
///     </body>
///   </method>

///   <method name="validate_with" returns="Forms.Form">
///     <brief>Проверяет объект формы валидатором $validator</brief>
///     <args>
///       <arg name="validator" type="Validation.Validator" brief="валидатор" />
///     </args>
///     <body>
  public function validate_with(Validation_Validator $validator) {
    $this->validator = $validator;
    return $this;
  }
///     </body>
///   </method>

///   <method name="field" returns="Forms.Form">
///     <brief>Добавляет к форме поле $field</brief>
///     <args>
///       <arg name="field" type="Forms.AbstractField" brief="поле" />
///     </args>
///     <body>
//TODO: remove cycle link
  public function field(Forms_AbstractField $field) {
    $this->fields[$field->name] = $field->bind_to($this);
    return $this;
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="processing">

///   <method name="process" returns="boolean">
///     <brief>Заполняет форму из $request и возвращает результат валидации</brief>
///     <args>
///       <arg name="request" type="Net.HTTP.Request" brief="объект HTTP запроса" />
///     </args>
///     <body>
  public function process(Net_HTTP_Request $request) {
    return $this->load($request) && ($this->validator ? $this->validator->validate($this, true) : true);
  }
///     </body>
///   </method>


///   <method name="process_entity">
///     <args>
///       <arg name="request" type="Net.HTTP.Request" />
///       <arg name="entity"  type="object" />
///     </args>
///     <body>
  public function process_entity(Net_HTTP_Request $request, $entity) {
    if ($this->load($request)) {
      $entity = $this->assign_to($entity);
      return $this->validator ?
        $this->validator->validate($entity, $this->is_indexed_object($entity)) :
        //$this->validator->validate($this->assign_to($entity), $this->is_indexed_object($entity)) :
        true;
    }
    return false;
  }
///     </body>
///   </method>


///   <method name="assign_to" returns="Forms.Form">
///     <brief>Заполняет объект $object значениями полей формы</brief>
///     <args>
///       <arg name="object" type="object" brief="объект" />
///     </args>
///     <body>
  public function assign_to($object) {
    return $this->is_indexed_object($object) ?
      $this->assign_to_indexed_object($object) :
      $this->assign_to_property_object($object);
  }
///     </body>
///   </method>

///   <method name="assign_from" returns="Forms.Form">
///     <brief>Заполняет поля формы из объекта $object</brief>
///     <args>
///       <arg name="object" type="object" brief="объект" />
///     </args>
///     <body>
  public function assign_from($object) {
    return $this->is_indexed_object($object) ?
      $this->assign_from_indexed_object($object) :
      $this->assign_from_property_object($object);
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="accessing" interface="Core.PropertyAccessInterface">

///   <method name="__get" returns="mixed">
///     <brief>Доступ на чтение к свойствам объекта</brief>
///     <details>
///       <dl>
///         <dt>name</dt><dd>имя формы</dd>
///         <dt>fields</dt><dd>поля формы</dd>
///         <dt>validator</dt><dd>валидатор</dd>
///         <dt>options</dt><dd>массив опций</dd>
///         <dt>errors, property_errors, global_errors</dt><dd>возвращает ошибки валидатора</dd>
///         <dt>begin_fields</dt><dd>возвращает Forms.FieldsBuilder для DSL построения полей формы </dd>
///         <dt>по умолчанию</dt><dd>возвращает опцию из массива $this->options</dd>
///       </dl>
///     </details>
///     <args>
///       <arg name="property" type="string" brief="имя свойства" />
///     </args>
///     <body>
  public function __get($property) {
    switch ($property) {
      case 'name':
      case 'fields':
      case 'validator':
      case 'options':
        return $this->$property;
      case 'action':
        return $this->options[$property];
      case 'errors':
      case 'property_errors':
      case 'global_errors':
        return ($this->validator && $this->validator->is_invalid()) ? $this->validator->$property : null;
      case 'begin_fields':
        return new Forms_FieldsBuilder($this);
      default:
        if (isset($this->options[$property]))
          return $this->options[$property];
        else
          throw new Core_MissingPropertyException($property);
    }
  }
///     </body>
///   </method>

///   <method name="__set" returns="mixed">
///     <brief>Доступ на запеись к свойствам объекта</brief>
///     <details>
///       Доступно только поле validator
///     </details>
///     <args>
///       <arg name="property" type="string" />
///       <arg name="value" brief="имя свойства" />
///     </args>
///     <body>
  public function __set($property, $value) {
    switch ($property) {
      case 'action':
        return $this->options[$property] = $value;
      case 'name':
      case 'fields':
      case 'options':
      case 'errors':
      case 'property_errors':
      case 'global_errors':
        throw new Core_ReadOnlyPropertyException($property);
      case 'validator':
        $this->validate_with($value);
        return $this;
      default:
        if (isset($this->options[$property]))
          $this->options[$property] = $value;
    }
    return $this;
  }
///     </body>
///   </method>

///   <method name="__isset" returns="boolean">
///     <brief>Проверяет установленно ли свойство с заданным именем</brief>
///     <args>
///       <arg name="property" type="string" brief="имя свойства" />
///     </args>
///     <body>
  public function __isset($property) {
    switch ($property) {
      case 'name':
      case 'fields':
      case 'options':
        return true;
      case 'validator':
        return isset($this->validator);
      case 'errors':
        return isset($this->validator) && $this->validator->is_invalid();
      case 'property_errors':
      case 'global_errors':
        return isset($this->validator) &&
               $this->validator->is_invalid() &&
               isset($this->validator->errors->$property);
      default:
        return isset($this->options[$property]);
    }
  }
///     </body>
///   </method>

///   <method name="__unset">
///     <brief>Очищает свойства объекта</brief>
///     <args>
///       <arg name="property" type="string" />
///     </args>
///     <body>
  public function __unset($property) {
    switch ($property) {
      case 'name':
      case 'fields':
      case 'options':
      case 'errors':
      case 'property_errors':
      case 'global_errors':
        throw new Core_UndestroyablePropertyException($property);
      case 'validator':
        unset($this->validator);
        return $this;
      default:
        if (isset($this->options[$property]))
          unset($this->options[$property]);
        else
          throw new Core_MissingPropertyException($property);
    }
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="indexing" interface="Core.IndexedAccessInterface">

///   <method name="offsetGet" returns="mixed">
///     <brief>Возвращает значение поля по ключу $index</brief>
///     <args>
///       <arg name="index" type="string" brief="ключ" />
///     </args>
///     <body>
  public function offsetGet($index) {
    if (isset($this->fields[$index]))
      return $this->fields[$index]->value;
    else
      throw new Core_MissingIndexedPropertyException($index);
  }
///     </body>
///   </method>

///   <method name="offsetSet" returns="mixed">
///     <brief>Устанавливает значение формы по ключу $index</brief>
///     <args>
///       <arg name="index" type="string" brief="ключ" />
///       <arg name="value" brief="значение" />
///     </args>
///     <body>
  public function offsetSet($index, $value) {
    if (isset($this->fields[$index])) {
      $this->fields[$index]->value = $value;
      return $this;
    }
    else
      throw new Core_MissingIndexedPropertyException($index);
  }
///     </body>
///   </method>

///   <method name="offsetExists" returns="boolean">
///     <brief>Проверяет если поле с именем $index в данной форме </brief>
///     <args>
///       <arg name="index" type="string" brief="имя поля" />
///     </args>
///     <body>
  public function offsetExists($index) { return isset($this->fields[$index]); }
///     </body>
///   </method>

///   <method name="offsetUnset">
///     <brief>Выбрасывает исключение</brief>
///     <args>
///       <arg name="index" type="string" />
///     </args>
///     <body>
  public function offsetUnset($index) {
    if (isset($this->fields[$index]))
      throw new Core_ReadOnlyIndexedPropertyException($index);
    else
      throw new Core_UndestroyableIndexedPropertyException($index);
  }
///     </body>
///   </method>

///   </protocol>


///   <protocol name="calling">

///   <method name="__call" returns="mixed">
///     <brief>С помощью вызова метода позволяет установить опции формы и свойство validator</brief>
///     <args>
///       <arg name="method" type="string" brief="имя метода" />
///       <arg name="args" type="array" brief="атрибуты метода" />
///     </args>
///     <body>
  public function __call($method, $args) {
    switch (true) {
      case $method == 'validator':
        return $this->validate_with($args[0]);
      case isset($this->options[$method]):
          $this->options[$method] = $args[0];
          return $this;
      default:
        return $this->field(Forms::field($method, $args));
    }
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="supporting">

///   <method name="load" returns="boolean" access="private">
///     <brief>Заполняет поля формы из $request</brief>
///     <args>
///       <arg name="request" type="Net.HTTP.Request" brief="объект HTTP запроса" />
///     </args>
///     <body>
  private function load(Net_HTTP_Request $request) {
    if (isset($request[$this->name])) {
      $r = true;
      foreach ($this->fields as $f) $r = $r && $f->load($request[$this->name]);
      return $r;
    } else
      return false;
  }
///     </body>
///   </method>

///   <method name="is_indexed_object" returns="boolean" access="private">
///     <brief>Проверяет имеется ли доступ к объекту через []</brief>
///     <args>
///       <arg name="object" type="object" brief="объект" />
///     </args>
///     <body>
  private function is_indexed_object($object) {
    return (!($object instanceof Core_PropertyAccessInterface) &&
             ($object  instanceof Core_IndexedAccessInterface || is_array($object) ||
             ($object instanceof ArrayObject)));
  }
///     </body>
///   </method>

///   <method name="assign_to_indexed_object" returns="Forms.Form" access="private">
///     <brief>Заполняет объект $object через инексный интерфейс значениями полей формы</brief>
///     <args>
///       <arg name="object" type="object" brief="объект" />
///     </args>
///     <body>
  private function assign_to_indexed_object($object) {
    foreach ($this->fields as $n => $f) $object[$n] = $f->value;
    return $object;
  }
///     </body>
///   </method>

///   <method name="assign_to_property_object" returns="Forms.Form" access="private">
///     <brief>Заполняет свойства объекта $object значениями полей формы</brief>
///     <args>
///       <arg name="object" type="object" brief="объект" />
///     </args>
///     <body>
  private function assign_to_property_object($object) {
    foreach ($this->fields as $n => $f) $object->$n = $f->value;
    return $object;
  }
///     </body>
///   </method>

///   <method name="assign_from_indexed_object" returns="Forms.Form">
///     <brief>Заполняет поля формы из индексного объекта $object</brief>
///     <args>
///       <arg name="object" type="object" brief="объект" />
///     </args>
///     <body>
  private function assign_from_indexed_object($object) {
    foreach ($this->fields as $n => $f) $f->value = $object[$n];
    return $this;
  }
///     </body>
///   </method>

///   <method name="assign_from_property_object" returns="Forms.Form" access="private">
///     <brief>Заполняет поля формы из объекта $object</brief>
///     <args>
///       <arg name="object" type="object" brief="объект" />
///     </args>
///     <body>
  private function assign_from_property_object($object) {
    foreach ($this->fields as $n => $f) $f->value = $object->$n;
    return $this;
  }
///   </body>
///  </method>

///   </protocol>
}
/// </class>

/// <class name="Forms.FieldsBuilder">
///   <brief>Класс предназначенный для DSL построения полей формы</brief>
///   <implements interface="Core.CallInterface" />
class Forms_FieldsBuilder implements Core_CallInterface {

  protected $form;

///   <protocol name="creating">

///   <method name="__construct">
///     <brief>Конструктор</brief>
///     <args>
///       <arg name="form" type="Forms.Form" brief="форма" />
///     </args>
///     <body>
  public function __construct(Forms_Form $form) {
    $this->form = $form;
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="calling" interface="Core.CallInterface">

///   <method name="__call" returns="Forms.FieldsBuilder">
///     <brief>С помощью вызыва метода добавляем новое поле из фабрики Forms::field в форму </brief>
///     <args>
///       <arg name="method" type="string" brief="имя метода" />
///       <arg name="args"   type="array" brief="атрибуты метода" />
///     </args>
///     <body>
  public function __call($method, $args) {
    $this->form->field(Forms::field($method, $args));
    return $this;
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="supporting">

///   <method name="__get" returns="mixed">
///     <brief>Доступ на чтение к полям объекта</brief>
///     <args>
///       <arg name="property" type="string" brief="имя свойства" />
///     </args>
///     <body>
  public function __get($property) {
    switch ($property) {
      case 'form':
      case 'end':
      case 'end_fields':
        return $this->form;
      default:
        return $this->$property;
    }
  }
///     </body>
///   </method>

///   </protocol>
}
/// </class>


/// <class name="Forms.AbstractField" stereotype="abstract">
///   <brief>Абстрактный класс поля формы</brief>
///   <implements interface="Core.PropertyAccessInterface" />
///   <implements interface="Core.EqualityInterface" />
abstract class Forms_AbstractField
  implements Core_PropertyAccessInterface,
  Core_EqualityInterface {

    protected $name;
    protected $value;
    protected $form;

///   <protocol name="creating">

///   <method name="__construct">
///     <brief>Конструктор</brief>
///     <args>
///       <arg name="name" type="string" brief="имя поля" />
///       <arg name="default" default="null" brief="значение по умолчанию" />
///     </args>
///     <body>
  public function __construct($name, $default = null) {
    $this->name  = $name;
    $this->value = $default;
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="connecting">

///   <method name="bind_to" returns="WebKit.Forms.AbstractField">
///     <brief>Привязывает поле к форме</brief>
///     <args>
///       <arg name="form" type="WebKit.Forms.Form" />
///     </args>
///     <body>
  public function bind_to($form) {
    $this->form = $form;
    return $this;
  }
///     </body>
///   </method>

///   <method name="tie_to" returns="WebKit.Forms.AbstractField">
///     <args>
///       <arg name="form" type="WebKit.Forms.Form" />
///     </args>
///     <body>
  public function tie_to($form) {
    return $this->bind($form);
  }
///     </body>
///   </method>

  public function unbind() {
    $this->form = null;
    return $this;
  }

///   <method name="untie" returns="WebKit.Forms.AbstractField">
///     <body>
  public function untie() {
  return $this->unbind();
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="validating">

///   <method name="is_valid" returns="boolean">
///     <brief>Валидирует поле формы</brief>
///     <body>
  public function is_valid() {
    return ($this->form && $this->form->validator) ?  $this->form->validator->is_valid($this->name) : true;
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="accessing" interface="Core.PropertyAccessInterface">

///   <method name="__get" returns="mixed">
///     <brief>Доступ на чтение к свойствам объекта</brief>
///     <details>
///       <dl>
///         <dt>name</dt><dd>имя поля</dd>
///         <dt>form</dt><dd>форма</dd>
///         <dt>value</dt><dd>значение поля</dd>
///       </dl>
///     </details>
///     <args>
///       <arg name="property" type="string" brief="имя свойства" />
///     </args>
///     <body>
  public function __get($property) {
    switch ($property) {
      case 'name':
      case 'form':
        return $this->$property;
      case 'value':
        return $this->get_value();
      default:
        throw new Core_MissingPropertyException($property);
    }
  }
///     </body>
///   </method>

///   <method name="__set" returns="mixed">\
///     <brief>Доступ на запись к свойствам объекта</brief>
///     <details>
///       Доступно только свойство value, которое устанавливается с помощью абстрактного метода set_value
///     </details>
///     <args>
///       <arg name="property" type="string" brief="имя свойства" />
///       <arg name="value" brief="значение" />
///     </args>
///     <body>
  public function __set($property, $value) {
    switch ($property) {
      case 'value':
        $this->set_value($value);
        break;
      case 'name':
      case 'form':
        throw new Core_ReadOnlyPropertyException($property);
      default:
        throw new Core_MissingPropertyException($property);
    }
    return $this;
  }
///     </body>
///   </method>

///   <method name="__isset" returns="boolean">
///     <brief>Проверяет установленно ли свойство</brief>
///     <args>
///       <arg name="property" type="string" brief="имя свойства" />
///     </args>
///     <body>
  public function __isset($property) {
    switch ($property) {
      case 'name':
      case 'form':
        return isset($this->property);
      case 'value':
        return $this->get_value() !== NULL;
      default:
        return false;
    }
  }
///     </body>
///   </method>

///   <method name="__unset">
///     <brief>Выбрасывает исключение</brief>
///     <details>
///       Очистка свойства недоступна
///     </details>
///     <args>
///       <arg name="property" type="string" />
///     </args>
///     <body>
  public function __unset($property) {
    switch ($property) {
      case 'name':
      case 'form':
      case 'value':
        throw new Core_UndestroyablePropertyException($property);
      default:
        throw new Core_MissingPropertyException($property);
    }
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="supporting">

///   <method name="load" stereotype="abstract">
///     <brief>Заполняет значение поля из объекта $from</brief>
///     <args>
///       <arg name="from" />
///     </args>
///     <body>
  abstract public function load($from);
///     </body>
///   </method>

///   <method name="get_value" returns="mixed" access="protected">
///     <brief>Возвращает значение поля</brief>
///     <body>
  protected function get_value() { return $this->value; }
///     </body>
///   </method>

///   <method name="set_value" returns="mixed">
///     <brief>Устанавливает значение поля</brief>
///     <args>
///       <arg name="value" />
///     </args>
///     <body>
  protected function set_value($value) { $this->value = $value; return $this; }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="quering">
///   <method name="equals" returns="boolean">
///     <args>
///       <arg name="to" />
///     </args>
///     <body>
  public function equals($to) {
    return $to instanceof self &&
      $this->name == $to->name &&
      $this->value == $to->value &&
      $this->form === $to->form;
  }
///     </body>
///   </method>
///</protocol>
}
/// </class>
/// </module>
