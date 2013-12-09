<?php
/**
 * Forms
 * 
 * В модуле определены класс формы и абстрактный класс поля
 * 
 * Форма предназначена для объектного представления HTML формы, со стандартными HTML поля ввода,
 * такими как input, checkbox и т.д.
 * 
 * @package Forms
 * @version 0.2.1
 */
Core::load('Net.HTTP', 'Validation', 'Object');

/**
 * @package Forms
 */
class Forms implements Core_ModuleInterface {

  const VERSION = '0.2.1';

  static protected $fields_factory;


/**
 * Метод инициализации
 * 
 */
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



/**
 * Регистрирует поле в соответствующей фабрике
 * 
 * @param array $binds
 * @param string $prefix
 */
  static public function register_field_types(array $binds, $prefix = '') {
    self::$fields_factory->map_list($binds, $prefix);
  }

/**
 * @param array $binds
 * @param string $prefix
 */
  static public function bind_field_types(array $binds, $prefix = '') {
    self::register_field_types($binds, $prefix);
  }



/**
 * Возвращает поле производимое фабрикой self::$fields_factory
 * 
 * @param string $type
 * @param array $args
 * @return Forms_AbstractField
 */
  static public function field($type, $args) { return self::$fields_factory->new_instance_of($type, $args); }

/**
 * @param string $name
 * @param array $args
 */
  static public function make_field($type, $args) {
    return self::field($type, $args);
  }

/**
 * Фабричный метод, возвращает объект класса Forms.Form
 * 
 * @param string $name
 * @return Forms_Form
 */
  static public function Form($name) { return new Forms_Form($name); }

}

/**
 * Класс формы
 * 
 * @package Forms
 */
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


/**
 * Конструктор
 * 
 * @param string $name
 */
  public function __construct($name) {
    $args = func_get_args();
    $this->name = (string) $name;
    $this->fields = Core::hash();
    call_user_func_array(array($this, 'setup'), array_slice($args, 1));
  }

/**
 * Метод, предназначенный для переопределения в производном классе
 * 
 * @return Forms_Form
 */
  protected function setup() { return $this; }



/**
 * Устанавливает опцию enctype в multipart/form-data
 * 
 * @return Forms_Form
 */
  public function multipart() { return $this->enctype('multipart/form-data'); }

/**
 * Проверяет объект формы валидатором $validator
 * 
 * @param Validation_Validator $validator
 * @return Forms_Form
 */
  public function validate_with(Validation_Validator $validator) {
    $this->validator = $validator;
    return $this;
  }

/**
 * Добавляет к форме поле $field
 * 
 * @param Forms_AbstractField $field
 * @return Forms_Form
 */
//TODO: remove cycle link
  public function field(Forms_AbstractField $field) {
    $this->fields[$field->name] = $field->bind_to($this);
    return $this;
  }



/**
 * Заполняет форму из $request и возвращает результат валидации
 * 
 * @param Net_HTTP_Request $request
 * @return boolean
 */
  public function process(Net_HTTP_Request $request) {
    return $this->load($request) && ($this->validator ? $this->validator->validate($this, true) : true);
  }


/**
 * @param Net_HTTP_Request $request
 * @param object $entity
 */
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


/**
 * Заполняет объект $object значениями полей формы
 * 
 * @param object $object
 * @return Forms_Form
 */
  public function assign_to($object) {
    return $this->is_indexed_object($object) ?
      $this->assign_to_indexed_object($object) :
      $this->assign_to_property_object($object);
  }

/**
 * Заполняет поля формы из объекта $object
 * 
 * @param object $object
 * @return Forms_Form
 */
  public function assign_from($object) {
    return $this->is_indexed_object($object) ?
      $this->assign_from_indexed_object($object) :
      $this->assign_from_property_object($object);
  }



/**
 * Доступ на чтение к свойствам объекта
 * 
 * @param string $property
 * @return mixed
 */
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

/**
 * Доступ на запеись к свойствам объекта
 * 
 * @param string $property
 * @param  $value
 * @return mixed
 */
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

/**
 * Проверяет установленно ли свойство с заданным именем
 * 
 * @param string $property
 * @return boolean
 */
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

/**
 * Очищает свойства объекта
 * 
 * @param string $property
 */
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



/**
 * Возвращает значение поля по ключу $index
 * 
 * @param string $index
 * @return mixed
 */
  public function offsetGet($index) {
    if (isset($this->fields[$index]))
      return $this->fields[$index]->value;
    else
      throw new Core_MissingIndexedPropertyException($index);
  }

/**
 * Устанавливает значение формы по ключу $index
 * 
 * @param string $index
 * @param  $value
 * @return mixed
 */
  public function offsetSet($index, $value) {
    if (isset($this->fields[$index])) {
      $this->fields[$index]->value = $value;
      return $this;
    }
    else
      throw new Core_MissingIndexedPropertyException($index);
  }

/**
 * Проверяет если поле с именем $index в данной форме
 * 
 * @param string $index
 * @return boolean
 */
  public function offsetExists($index) { return isset($this->fields[$index]); }

/**
 * Выбрасывает исключение
 * 
 * @param string $index
 */
  public function offsetUnset($index) {
    if (isset($this->fields[$index]))
      throw new Core_ReadOnlyIndexedPropertyException($index);
    else
      throw new Core_UndestroyableIndexedPropertyException($index);
  }




/**
 * С помощью вызова метода позволяет установить опции формы и свойство validator
 * 
 * @param string $method
 * @param array $args
 * @return mixed
 */
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



/**
 * Заполняет поля формы из $request
 * 
 * @param Net_HTTP_Request $request
 * @return boolean
 */
  private function load(Net_HTTP_Request $request) {
    if (isset($request[$this->name])) {
      $r = true;
      foreach ($this->fields as $f) $r = $r && $f->load($request[$this->name]);
      return $r;
    } else
      return false;
  }

/**
 * Проверяет имеется ли доступ к объекту через []
 * 
 * @param object $object
 * @return boolean
 */
  private function is_indexed_object($object) {
    return (!($object instanceof Core_PropertyAccessInterface) &&
             ($object  instanceof Core_IndexedAccessInterface || is_array($object) ||
             ($object instanceof ArrayObject)));
  }

/**
 * Заполняет объект $object через инексный интерфейс значениями полей формы
 * 
 * @param object $object
 * @return Forms_Form
 */
  private function assign_to_indexed_object($object) {
    foreach ($this->fields as $n => $f) $object[$n] = $f->value;
    return $object;
  }

/**
 * Заполняет свойства объекта $object значениями полей формы
 * 
 * @param object $object
 * @return Forms_Form
 */
  private function assign_to_property_object($object) {
    foreach ($this->fields as $n => $f) $object->$n = $f->value;
    return $object;
  }

/**
 * Заполняет поля формы из индексного объекта $object
 * 
 * @param object $object
 * @return Forms_Form
 */
  private function assign_from_indexed_object($object) {
    foreach ($this->fields as $n => $f) $f->value = $object[$n];
    return $this;
  }

/**
 * Заполняет поля формы из объекта $object
 * 
 * @param object $object
 * @return Forms_Form
 */
  private function assign_from_property_object($object) {
    foreach ($this->fields as $n => $f) $f->value = $object->$n;
    return $this;
  }

}

/**
 * Класс предназначенный для DSL построения полей формы
 * 
 * @package Forms
 */
class Forms_FieldsBuilder implements Core_CallInterface {

  protected $form;


/**
 * Конструктор
 * 
 * @param Forms_Form $form
 */
  public function __construct(Forms_Form $form) {
    $this->form = $form;
  }



/**
 * С помощью вызыва метода добавляем новое поле из фабрики Forms::field в форму
 * 
 * @param string $method
 * @param array $args
 * @return Forms_FieldsBuilder
 */
  public function __call($method, $args) {
    $this->form->field(Forms::field($method, $args));
    return $this;
  }



/**
 * Доступ на чтение к полям объекта
 * 
 * @param string $property
 * @return mixed
 */
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

}


/**
 * Абстрактный класс поля формы
 * 
 * @abstract
 * @package Forms
 */
abstract class Forms_AbstractField
  implements Core_PropertyAccessInterface,
  Core_EqualityInterface {

    protected $name;
    protected $value;
    protected $form;


/**
 * Конструктор
 * 
 * @param string $name
 * @param  $default
 */
  public function __construct($name, $default = null) {
    $this->name  = $name;
    $this->value = $default;
  }



/**
 * Привязывает поле к форме
 * 
 * @param WebKit_Forms_Form $form
 * @return WebKit_Forms_AbstractField
 */
  public function bind_to($form) {
    $this->form = $form;
    return $this;
  }

/**
 * @param WebKit_Forms_Form $form
 * @return WebKit_Forms_AbstractField
 */
  public function tie_to($form) {
    return $this->bind($form);
  }

  public function unbind() {
    $this->form = null;
    return $this;
  }

/**
 * @return WebKit_Forms_AbstractField
 */
  public function untie() {
  return $this->unbind();
  }



/**
 * Валидирует поле формы
 * 
 * @return boolean
 */
  public function is_valid() {
    return ($this->form && $this->form->validator) ?  $this->form->validator->is_valid($this->name) : true;
  }



/**
 * Доступ на чтение к свойствам объекта
 * 
 * @param string $property
 * @return mixed
 */
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

/**
 * Доступ на запись к свойствам объекта
 * 
 * @param string $property
 * @param  $value
 * @return mixed
 */
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

/**
 * Проверяет установленно ли свойство
 * 
 * @param string $property
 * @return boolean
 */
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

/**
 * Выбрасывает исключение
 * 
 * @param string $property
 */
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



/**
 * Заполняет значение поля из объекта $from
 * 
 * @abstract
 * @param  $from
 */
  abstract public function load($from);

/**
 * Возвращает значение поля
 * 
 * @return mixed
 */
  protected function get_value() { return $this->value; }

/**
 * Устанавливает значение поля
 * 
 * @param  $value
 * @return mixed
 */
  protected function set_value($value) { $this->value = $value; return $this; }


/**
 * @param  $to
 * @return boolean
 */
  public function equals($to) {
    return $to instanceof self &&
      $this->name == $to->name &&
      $this->value == $to->value &&
      $this->form === $to->form;
  }
}
