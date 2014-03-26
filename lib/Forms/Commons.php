<?php
/**
 * Forms.Commons
 * 
 * Набор классов - объектное представление поле формы
 * 
 * @package Forms\Commons
 * @version 0.2.1
 */
Core::load('Time', 'Forms');

/**
 * @package Forms\Commons
 */
class Forms_Commons implements Core_ModuleInterface {

  const VERSION = '0.2.1';


/**
 * Инициализация
 * 
 */
  static public function initialize() {
  }

}


/**
 * Строковое поле
 * 
 * @package Forms\Commons
 */
class Forms_Commons_StringField extends Forms_AbstractField {


/**
 * Конструктор
 * 
 * @param string $name
 * @param string $default
 */
  public function __construct($name, $default = '') {
    parent::__construct($name, (string) $default);
  }



/**
 * Заполняет значение поля из источника $source
 * 
 * @param  $source
 * @return boolean
 */
  public function load($source) {
    if (isset($source[$this->name]))
      $this->value = (string) $source[$this->name];
    return true;
  }

/**
 * Устанавливает значение поля
 * 
 * @param  $value
 * @return string
 */
  protected function set_value($value) {
    return $this->value = (string) $value;
  }

}


/**
 * Поле пароля
 * 
 * @package Forms\Commons
 */
class Forms_Commons_PasswordField extends Forms_AbstractField {


/**
 * Конструктор
 * 
 * @param string $name
 * @param string $default
 */
  public function __construct($name) { parent::__construct($name, ''); }



/**
 * Заполняет значение поля из источника $source
 * 
 * @param  $source
 */
  public function load($source) {
    $this->value = isset($source[$this->name]) ?
      (string) $source[$this->name] : '';
    return true;
  }

/**
 * Устанавливает значение поля
 * 
 * @param  $value
 * @return mixed
 */
  protected function set_value($value) { return $this->value = (string) $value; }

}


/**
 * Текстовое поле
 * 
 * @package Forms\Commons
 */
class Forms_Commons_TextAreaField extends Forms_AbstractField {


/**
 * Конструктор
 * 
 * @param string $name
 * @param string $default
 */
  public function __construct($name, $default = '') {
    parent::__construct($name, (string) $default);
  }



/**
 * Заполняет значение поля из источника $source
 * 
 * @param  $source
 */
  public function load($source) {
    if (isset($source[$this->name]))
      $this->value = (string) $source[$this->name];
    return true;
  }

/**
 * Устанавливает значение поля
 * 
 * @param  $value
 * @return string
 */
  protected function set_value($value) { return $this->value = (string) $value; }

}

/**
 * Поле выбора
 * 
 * @package Forms\Commons
 */
class Forms_Commons_CheckBoxfield extends Forms_AbstractField {


/**
 * Конструктор
 * 
 * @param string $name
 * @param string $default
 */
  public function __construct($name, $default = false) {
    parent::__construct($name, $default ? true : false);
  }



/**
 * Заполняет значение поля из источника $source
 * 
 * @param  $source
 */
  public function load($source) {
    $this->value = isset($source[$this->name]);
    return true;
  }

/**
 * Устанавливает значение поля
 * 
 * @param  $value
 * @return mixed
 */
  protected function set_value($value) { return $this->value = $value ? true : false; }

}

/**
 * Абстрактный класс -- поле-коллекция, содержащее несколько значение
 * 
 * @abstract
 * @package Forms\Commons
 */
abstract class Forms_Commons_CollectionField
  extends    Forms_AbstractField
  implements Core_PropertyAccessInterface {


  protected $items;
  protected $index = array();

  protected $key;
  protected $attribute;
  protected $allows_null;


/**
 * Конструктор
 * 
 * @param string $name
 * @param  $items
 * @param array $options
 */
  public function __construct($name, $items, $options = array()) {
    $this->key         = isset($options['key']) ? (string)$options['key'] : 'id';
    $this->attribute   = isset($options['attribute']) ? (string)$options['attribute'] : 'title';
    $this->allows_null = isset($options['allows_null']) ? ($options['allows_null'] ? true : false) : false;
    $this->items = new ArrayObject();
    $this->items($items);
    parent::__construct($name);
  }



/**
 * Добавляет новые элементы и обновляет массив индексов
 * 
 * @param  $items
 */
  public function items($items) {
    $key = $this->key;
    foreach ($items as $k => $v)
      $this->items[$k] = $v;
    foreach ($this->items as $k => $v) $this->index[$v->$key] = $k;
    return $this;
  }



/**
 * Доступ на чтение к свойствам объекта
 * 
 * @param string $property
 * @return mixed
 */
  public function __get($property) {
    switch ($property) {
      case 'key':
      case 'items':
      case 'attribute':
      case 'allows_null':
        return $this->$property;
      default:
        return parent::__get($property);
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
      case 'key':
      case 'attribute':
      case 'items':
      case 'allows_null':
        throw new Core_ReadOnlyPropertyException($property);
      default:
        return parent::__set($property, $value);
    }
  }

/**
 * Проверяет установленно ли свойство с именем $property
 * 
 * @param string $property
 * @return boolean
 */
  public function __isset($property) {
    switch ($property) {
      case 'key':
      case 'attribute':
      case 'items':
      case 'allows_null':
        return true;
      default:
        return parent::__isset($property);
    }
  }

/**
 * Очищает свойство объекта
 * 
 * @param string $property
 */
  public function __unset($property) {
    throw $this->__isset($property) ?
      new Core_UndestroyablePropertyException($property) :
      new Core_MissingPropertyException($property);
  }


}

/**
 * Поле выбора одного значения из коллекции
 * 
 * @package Forms\Commons
 */
class Forms_Commons_ObjectSelectField
  extends    Forms_Commons_CollectionField {

/**
 * Заполняет значение поля из источника $source
 * 
 * @param  $source
 */
  public function load($source) {
    $v = isset($source[$this->name]) ? $source[$this->name] : null;
    $this->value = isset($v) && isset($this->index[$v]) ?
      $this->items[$this->index[$v]] : $this->value;
    return true;
  }

/**
 * Устанавливает значение поля
 * 
 * @param  $value
 */
  public function set_value($value) {
    $key = $this->key;
    if ($this->allows_null && $value == null)  $this->value =  null;
    elseif (isset($value->$key) && isset($this->index[$value->$key]))
      $this->value =  $this->items[$this->index[$value->$key]];
    return $this;
  }

}

/**
 * Поле выбора нескольких значений из коллекции
 * 
 * @package Forms\Commons
 */
class Forms_Commons_ObjectMultiSelectField extends Forms_Commons_CollectionField {

/**
 * Заполняет значение поля из источника $source
 * 
 * @param  $source
 */
  public function load($source) {
    $this->value = array();
    if (isset($source[$this->name]) && (is_array($source[$this->name]) || $source[$this->name] instanceof Traversable)) { 
      foreach ($source[$this->name] as $v)
       if (isset($this->index[$v])) {
         $item = $this->items[$this->index[$v]];
         $this->value[$item->{$this->key}] = $item;
       }
    }
    return true;
  }

/**
 * Устанавливает значение поля
 * 
 * @param  $value
 */
  public function set_value($value) {
    $key = $this->key;
    if (is_array($value) || $value instanceof Traversable) {
      $this->value = array();
      foreach ($value as $v)
        if (isset($this->index[$v->$key])) {
         $item = $this->items[$this->index[$v->$key]];
         $this->value[$item->{$this->key}] = $item;
      }
    }
    return $this;
  }

}

/**
 * Поле выбора одного значения из массива
 * 
 * В данном случае элементами коллекции-массива не являются объекты
 * Т.е. это более простой случай
 * 
 * @package Forms\Commons
 */
class Forms_Commons_SelectField
  extends    Forms_AbstractField {

  protected $items;


/**
 * Конструктор
 * 
 * @param string $name
 * @param  $items
 */
  public function __construct($name, $items) {
    $this->items = $items;
    parent::__construct($name);
  }



/**
 * Заполняет значение поля из источника $source
 * 
 * @param  $source
 */
  public function load($source) {
    if (isset($source[$this->name])) {
      $this->value = $source[$this->name];
    }
    return true;
  }

/**
 * Заполняет значение поля из источника $source
 * 
 * @param  $value
 */
  public function set_value($value) {
    return $this->value = $value;
  }



/**
 * Доступ на чтение к свойствам объекта
 * 
 * @param string $property
 * @return mixed
 */
  public function __get($property) {
    switch ($property) {
      case 'items':
        return $this->$property;
      default:
        return parent::__get($property);
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
      case 'items':
        throw new Core_ReadOnlyPropertyException($property);
      default:
        return parent::__set($property, $value);
    }
  }

/**
 * Проверяет установленно ли свойство объекта
 * 
 * @param string $property
 * @return boolean
 */
  public function __isset($property) {
    switch ($property) {
      case 'items':
        return true;
      default:
        return parent::__isset($property);
    }
  }

/**
 * Очищает свойства объекта
 * 
 * @param string $property
 */
  public function __unset($property) {
    throw $this->__isset($property) ?
      new Core_UndestroyablePropertyException($property) :
      new Core_MissingPropertyException($property);
  }

}

/**
 * Поле для ввода даты и времени
 * 
 * @package Forms\Commons
 */
class Forms_Commons_DateTimeField extends Forms_AbstractField {


/**
 * Устанавливает значение поля
 * 
 * @param  $value
 * @return Time_DateTime
 */
  protected function set_value($value) {
    return $this->value = ($value instanceof Time_DateTime) ? $value : null;
  }

/**
 * Заполняет значение поля из источника $source
 * 
 * @param  $source
 */
  public function load($source) {
    if (isset($source[$this->name]) && (Core_Types::is_array($source[$this->name]) || $source[$this->name] instanceof ArrayAccess)) {
      $parts = array();
      foreach (array('year' => false, 'month' => 1, 'day' => 1, 'hour' => 0, 'minute' => 0) as $k => $v) {
        if (isset($source[$this->name][$k]) && $source[$this->name][$k] != '')
          $parts[$k] = (int) $source[$this->name][$k];
        elseif ($v !== false)
          $parts[$k] = $v;
        else {
          $parts = false;
          break;
        }
      }
      $this->value = $parts ? Time::compose($parts['year'], $parts['month'], $parts['day'], $parts['hour'], $parts['minute']): null;
    }
    return true;
  }

}


/**
 * Поле upload
 * 
 * @package Forms\Commons
 */
class Forms_Commons_UploadField extends Forms_AbstractField {


/**
 * Заполняет значение поля из источника $source
 * 
 * @param  $source
 */
  public function load($source) {
    // $upload = isset($source[$this->name]) ? $source[$this->name] : null;
    if (isset($source[$this->name]) && $source[$this->name] instanceof Net_HTTP_Upload) $this->value = $source[$this->name];
    return true;
  }

/**
 * Устанавливает значение поля
 * 
 * @param  $value
 */
  protected function set_value($value) { return $this->value; }

}

