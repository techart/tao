<?php
/// <module name="Forms.Commons" version="0.2.1" maintainer="timokhin@techart.ru">
///   <brief>Набор классов - объектное представление поле формы</brief>
Core::load('Time', 'Forms');

/// <class name="Forms.Commons" stereotype="modele">
///   <implements interface="Core.ModuleInterface" />
///   <depends supplier="Forms.Commons.StringField" stereotype="creates" />
///   <depends supplier="Forms.Commons.PasswordField" stereotype="creates" />
///   <depends supplier="Forms.Commons.TextAreaField" stereotype="creates" />
///   <depends supplier="Forms.Commons.CheckBoxField" stereotype="creates" />
///   <depends supplier="Forms.Commons.DateTimeField" stereotype="creates" />
///   <depends supplier="Forms.Commons.ObjectSelectField" stereotype="creates" />
class Forms_Commons implements Core_ModuleInterface {

///   <constants>
  const VERSION = '0.2.1';
///   </constants>

///   <protocol name="creating">

///   <method name="initialize" scope="class">
///     <brief>Инициализация</brief>
///     <details>
///       Регестрирует набор классов в фабрике форм (Forms::fields)
///     </details>
///     <body>
  static public function initialize() {
  }
///     </body>
///   </method>

///   </protocol>
}
/// </class>


/// <class name="Forms.Commons.StringField" extends="Forms.AbstractField">
///     <brief>Строковое поле</brief>
class Forms_Commons_StringField extends Forms_AbstractField {

///   <protocol name="creating">

///   <method name="__construct">
///     <brief>Конструктор</brief>
///     <args>
///       <arg name="name" type="string" brief="имя поля" />
///       <arg name="default" type="string" default="''" brief="значение по умолчанию" />
///     </args>
///     <body>
  public function __construct($name, $default = '') {
    parent::__construct($name, (string) $default);
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="supporting">

///   <method name="load" returns="boolean">
///     <brief>Заполняет значение поля из источника $source</brief>
///     <details>
///       Обычно источник - объект HTTP запроса Net.HTTP.Request
///       Приводит значение к строке
///     </details>
///     <args>
///       <arg name="source" brief="источник" />
///     </args>
///     <body>
  public function load($source) {
    if (isset($source[$this->name]))
      $this->value = (string) $source[$this->name];
    return true;
  }
///     </body>
///   </method>

///   <method name="set_value" returns="string" access="protected">
///     <brief>Устанавливает значение поля</brief>
///     <details>
///       Приводит значение к строке
///     </details>
///     <args>
///       <arg name="value" />
///     </args>
///     <body>
  protected function set_value($value) {
    return $this->value = (string) $value;
  }
///     </body>
///   </method>

///   </protocol>
}
/// </class>


/// <class name="Forms.Commons.PasswordField" extends="Forms.AbstractField">
///     <brief>Поле пароля</brief>
class Forms_Commons_PasswordField extends Forms_AbstractField {

///   <protocol name="creating">

///   <method name="__construct">
///     <brief>Конструктор</brief>
///     <args>
///       <arg name="name" type="string" brief="имя поля" />
///       <arg name="default" type="string" default="''" brief="значение по умолчанию" />
///     </args>
///     <body>
  public function __construct($name) { parent::__construct($name, ''); }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="supporting">

///   <method name="load">
///     <brief>Заполняет значение поля из источника $source</brief>
///     <details>
///       Обычно источник - объект HTTP запроса Net.HTTP.Request
///       Приводит значение к строке
///     </details>
///     <args>
///       <arg name="source" />
///     </args>
///     <body>
  public function load($source) {
    $this->value = isset($source[$this->name]) ?
      (string) $source[$this->name] : '';
    return true;
  }
///     </body>
///   </method>

///   <method name="set_value" returns="mixed" access="protected">
///     <brief>Устанавливает значение поля</brief>
///     <details>
///       Приводит значение к строке
///     </details>
///     <args>
///       <arg name="value" />
///     </args>
///     <body>
  protected function set_value($value) { return $this->value = (string) $value; }
///     </body>
///   </method>

///   </protocol>
}
/// </class>


/// <class name="Forms.Commons.TextAreaField" extends="Forms.AbstractField">
///   <brief>Текстовое поле</brief>
class Forms_Commons_TextAreaField extends Forms_AbstractField {

///   <protocol name="creating">

///   <method name="__construct">
///     <brief>Конструктор</brief>
///     <args>
///       <arg name="name" type="string" brief="имя поля" />
///       <arg name="default" type="string" default="''" brief="значение по умолчанию" />
///     </args>
///     <body>
  public function __construct($name, $default = '') {
    parent::__construct($name, (string) $default);
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="supporting">

///   <method name="load">
///     <brief>Заполняет значение поля из источника $source</brief>
///     <details>
///       Обычно источник - объект HTTP запроса Net.HTTP.Request
///       Приводит значение к строке
///     </details>
///     <args>
///       <arg name="source" />
///     </args>
///     <body>
  public function load($source) {
    if (isset($source[$this->name]))
      $this->value = (string) $source[$this->name];
    return true;
  }
///     </body>
///   </method>

///   <method name="set_value" returns="string" access="protected">
///     <brief>Устанавливает значение поля</brief>
///     <details>
///       Приводит значение к строке
///     </details>
///     <args>
///       <arg name="value" />
///     </args>
///     <body>
  protected function set_value($value) { return $this->value = (string) $value; }
///     </body>
///   </method>

///   </protocol>
}
/// </class>

/// <class name="Forms.Commons.CheckBoxfield" extends="Forms.AbstractField">
///   <brief>Поле выбора</brief>
class Forms_Commons_CheckBoxfield extends Forms_AbstractField {

///   <protocol name="creating">

///   <method name="__construct">
///     <brief>Конструктор</brief>
///     <args>
///       <arg name="name" type="string" brief="имя поля" />
///       <arg name="default" type="string" default="''" brief="значение по умолчанию" />
///     </args>
///     <body>
  public function __construct($name, $default = false) {
    parent::__construct($name, $default ? true : false);
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="supporting">

///   <method name="load">
///     <brief>Заполняет значение поля из источника $source</brief>
///     <details>
///       Обычно источник - объект HTTP запроса Net.HTTP.Request
///       Приводит значение к boolean
///     </details>
///     <args>
///       <arg name="source" />
///     </args>
///     <body>
  public function load($source) {
    $this->value = isset($source[$this->name]);
    return true;
  }
///     </body>
///   </method>

///   <method name="set_value" returns="mixed" access="protected">
///     <brief>Устанавливает значение поля</brief>
///     <details>
///       Приводит значение к boolean
///     </details>
///     <args>
///       <arg name="value" />
///     </args>
///     <body>
  protected function set_value($value) { return $this->value = $value ? true : false; }
///     </body>
///   </method>

///   </protocol>
}
/// </class>

/// <class name="Forms.Commons.CollectionField" stereotype="abstract" extends="Forms.AbstractField">
///   <brief>Абстрактный класс -- поле-коллекция, содержащее несколько значение</brief>
abstract class Forms_Commons_CollectionField
  extends    Forms_AbstractField
  implements Core_PropertyAccessInterface {


  protected $items;
  protected $index = array();

  protected $key;
  protected $attribute;
  protected $allows_null;

///   <protocol name="creating">

///   <method name="__construct">
///     <brief>Конструктор</brief>
///     <details>
///       Хэш $items содержит набор объектов
///       Массив опций $options может задавать:
///       <dl>
///         <dt>key</dt><dd>свойство объекта из $items по которому определяет ключ поля, по умолчанию 'id'</dd>
///         <dt>attribute</dt><dd>свойство объекта из $items по которому определяет атрибут поля, по умолчанию 'title'</dd>
///         <dt>allows_null</dt><dd>Булево значение определяющее может ли поле быть пустум (null)</dd>
///       </dl>
///     </details>
///     <args>
///       <arg name="name" type="string" brief="имя поля" />
///       <arg name="items" brief="хэш элементов" />
///       <arg name="options" type="array" default="array()" brief="массив опций" />
///     </args>
///     <body>
  public function __construct($name, $items, $options = array()) {
    $this->key         = isset($options['key']) ? (string)$options['key'] : 'id';
    $this->attribute   = isset($options['attribute']) ? (string)$options['attribute'] : 'title';
    $this->allows_null = isset($options['allows_null']) ? ($options['allows_null'] ? true : false) : false;
    $this->items = new ArrayObject();
    $this->items($items);
    parent::__construct($name);
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="supporting">

///   <method name="items">
///     <brief>Добавляет новые элементы и обновляет массив индексов</brief>
///     <args>
///       <arg name="items" brief="хэш элементов" />
///     </args>
///     <body>
  public function items($items) {
    $key = $this->key;
    foreach ($items as $k => $v)
      $this->items[$k] = $v;
    foreach ($this->items as $k => $v) $this->index[$v->$key] = $k;
    return $this;
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="accessing" interface="Core.PropertyAccessInterface">

///   <method name="__get" returns="mixed">
///     <brief>Доступ на чтение к свойствам объекта</brief>
///     <details>
///       <dl>
///         <dt>key</dt><dd>свойство объекта из $items по которому определяет ключ поля, по умолчанию 'id'</dd>
///         <dt>attribute</dt><dd>свойство объекта из $items по которому определяет атрибут поля, по умолчанию 'title'</dd>
///         <dt>allows_null</dt><dd>Булево значение определяющее может ли поле быть пустум (null)</dd>
///         <dt>items</dt><dd>Хэш элементов</dd>
///       </dl>
///     </details>
///     <args>
///       <arg name="property" type="string" />
///     </args>
///     <body>
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
///     </body>
///   </method>

///   <method name="__set" returns="mixed">
///     <brief>Доступ на запись к свойствам объекта</brief>
///     <details>
///       Свойства доступны только для чтение
///     </details>
///     <args>
///       <arg name="property" type="string" brief="имя свойства" />
///       <arg name="value" brief="значение" />
///     </args>
///     <body>
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
///     </body>
///   </method>

///   <method name="__isset" returns="boolean">
///     <brief>Проверяет установленно ли свойство с именем $property</brief>
///     <args>
///       <arg name="property" type="string" brief="имя свойства" />
///     </args>
///     <body>
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
///     </body>
///   </method>

///   <method name="__unset">
///     <brief>Очищает свойство объекта</brief>
///     <details>
///       Свойства объекта доступны только для чтение
///     </details>
///     <args>
///       <arg name="property" type="string" brief="имя свойства" />
///     </args>
///     <body>
  public function __unset($property) {
    throw $this->__isset($property) ?
      new Core_UndestroyablePropertyException($property) :
      new Core_MissingPropertyException($property);
  }
///     </body>
///   </method>

///   </protocol>

}
/// </class>

/// <class name="Forms.Commons.ObjectSelectField" extends="Forms.Commons.CollectionField">
///     <brief>Поле выбора одного значения из коллекции</brief>
class Forms_Commons_ObjectSelectField
  extends    Forms_Commons_CollectionField {
///   <protocol name="supporting">

///   <method name="load">
///     <brief>Заполняет значение поля из источника $source</brief>
///     <details>
///       Обычно источник - объект HTTP запроса Net.HTTP.Request
///       Выбирает по ключу элемент из хэша $this->items
///     </details>
///     <args>
///       <arg name="source" brief="источник" />
///     </args>
///     <body>
  public function load($source) {
    $v = isset($source[$this->name]) ? $source[$this->name] : null;
    $this->value = isset($v) && isset($this->index[$v]) ?
      $this->items[$this->index[$v]] : null;
    return true;
  }
///     </body>
///   </method>

///   <method name="set_value">
///     <brief>Устанавливает значение поля</brief>
///     <args>
///       <arg name="value" />
///     </args>
///     <body>
  public function set_value($value) {
    $key = $this->key;
    if ($this->allows_null && $value == null)  $this->value =  null;
    elseif (isset($value->$key) && isset($this->index[$value->$key]))
      $this->value =  $this->items[$this->index[$value->$key]];
    return $this;
  }
///     </body>
///   </method>

///   </protocol>
}
/// </class>

/// <class name="Forms.Commons.ObjectMultiSelectField" extends="Forms.Commons.CollectionField">
///   <brief>Поле выбора нескольких значений из коллекции</brief>
class Forms_Commons_ObjectMultiSelectField extends Forms_Commons_CollectionField {
///   <protocol name="supporting">

///   <method name="load">
///     <brief>Заполняет значение поля из источника $source</brief>
///     <details>
///       Обычно источник - объект HTTP запроса Net.HTTP.Request
///       Выбирает по массиву ключей элементы из хэша $this->items
///     </details>
///     <args>
///       <arg name="source" brief="источник" />
///     </args>
///     <body>
  public function load($source) {
    if (isset($source[$this->name]) && (is_array($source[$this->name]) || $source[$this->name] instanceof Traversable)) {
      $this->value = array();
      foreach ($source[$this->name] as $v)
       if (isset($this->index[$v])) {
         $item = $this->items[$this->index[$v]];
         $this->value[$item->{$this->key}] = $item;
       }
    } else {
      $this->value = array();
    }
    return true;
  }
///     </body>
///   </method>

///   <method name="set_value">
///     <brief>Устанавливает значение поля</brief>
///     <args>
///       <arg name="value" brief="значение" />
///     </args>
///     <body>
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
///     </body>
///   </method>

///   </protocol>
}
/// </class>

/// <class name="Forms.Commons.SelectField" extends="Forms.AbstractField">
///     <brief>Поле выбора одного значения из массива</brief>
///     <details>
///       В данном случае элементами коллекции-массива не являются объекты
///       Т.е. это более простой случай
///     </details>
class Forms_Commons_SelectField
  extends    Forms_AbstractField {

  protected $items;

///   <protocol name="creating">

///   <method name="__construct">
///     <brief>Конструктор</brief>
///     <args>
///       <arg name="name" type="string" brief="имя поля" />
///       <arg name="items" brief="массив значений" />
///     </args>
///     <body>
  public function __construct($name, $items) {
    $this->items = $items;
    parent::__construct($name);
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="supporting">

///   <method name="load">
///     <brief>Заполняет значение поля из источника $source</brief>
///     <details>
///       Обычно источник - объект HTTP запроса Net.HTTP.Request
///       Выбирает ключу значение из массива $this->items
///     </details>
///     <args>
///       <arg name="source" />
///     </args>
///     <body>
  public function load($source) {
    $this->value = isset($source[$this->name]) && isset($this->items[$source[$this->name]]) ?
      $source[$this->name] : null;
    return true;
  }
///     </body>
///   </method>

///   <method name="set_value">
///     <brief>Заполняет значение поля из источника $source</brief>
///     <args>
///       <arg name="value" />
///     </args>
///     <body>
  public function set_value($value) {
    return $this->value = $value;
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="accessing" interface="Core.PropertyAccessInterface">

///   <method name="__get" returns="mixed">
///     <brief>Доступ на чтение к свойствам объекта</brief>
///     <details>
///       <dl>
///         <dt>items</dt><dd>массив значений</dd>
///       </dl>
///     </details>
///     <args>
///       <arg name="property" type="string" brief="имя свойства" />
///     </args>
///     <body>
  public function __get($property) {
    switch ($property) {
      case 'items':
        return $this->$property;
      default:
        return parent::__get($property);
    }
  }
///     </body>
///   </method>

///   <method name="__set" returns="mixed">
///     <brief>Доступ на запись к свойствам объекта</brief>
///     <details>
///       Свойства доступны только на чтение
///     </details>
///     <args>
///       <arg name="property" type="string" brief="имя свойства" />
///       <arg name="value" brief="значение" />
///     </args>
///     <body>
  public function __set($property, $value) {
    switch ($property) {
      case 'items':
        throw new Core_ReadOnlyPropertyException($property);
      default:
        return parent::__set($property, $value);
    }
  }
///     </body>
///   </method>

///   <method name="__isset" returns="boolean">
///     <brief>Проверяет установленно ли свойство объекта</brief>
///     <args>
///       <arg name="property" type="string" brief="имя свойства" />
///     </args>
///     <body>
  public function __isset($property) {
    switch ($property) {
      case 'items':
        return true;
      default:
        return parent::__isset($property);
    }
  }
///     </body>
///   </method>

///   <method name="__unset">
///     <brief>Очищает свойства объекта</brief>
///     <details>
///       Свойства объекта доступны только на чтение
///     </details>
///     <args>
///       <arg name="property" type="string" brief="имя свойства" />
///     </args>
///     <body>
  public function __unset($property) {
    throw $this->__isset($property) ?
      new Core_UndestroyablePropertyException($property) :
      new Core_MissingPropertyException($property);
  }
///     </body>
///   </method>

///   </protocol>
}
/// </class>

/// <class name="Forms.Commons.DateTimeField" extends="Forms.AbstractField">
///     <brief>Поле для ввода даты и времени</brief>
///   <depends supplier="Time.DateTime" stereotype="uses" />
class Forms_Commons_DateTimeField extends Forms_AbstractField {

///   <protocol name="supporting">

///   <method name="set_value" returns="Time.DateTime">
///     <brief>Устанавливает значение поля</brief>
///     <details>
///       Приводит значение к Time.DateTime
///     </details>
///     <args>
///       <arg name="value" />
///     </args>
///     <body>
  protected function set_value($value) {
    return $this->value = ($value instanceof Time_DateTime) ? $value : null;
  }
///     </body>
///   </method>

///   <method name="load">
///     <brief>Заполняет значение поля из источника $source</brief>
///     <details>
///       Обычно источник - объект HTTP запроса Net.HTTP.Request
///       Парсит значение и преобразует в объект Time.DateTime
///     </details>
///     <args>
///       <arg name="source" brief="источник" />
///     </args>
///     <body>
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
///     </body>
///   </method>

///   </protocol>
}
/// </class>


/// <class name="Forms.Commons.UploadField" extends="Forms.AbstractField">
///   <brief>Поле upload</brief>
class Forms_Commons_UploadField extends Forms_AbstractField {

///   <protocol name="supporting">

///   <method name="load" access="protected">
///     <brief>Заполняет значение поля из источника $source</brief>
///     <details>
///       Обычно источник - объект HTTP запроса Net.HTTP.Request
///       Проверяет тип значения (Net.HTTP.Upload)
///     </details>
///     <args>
///       <arg name="source" brief="источник" />
///     </args>
///     <body>
  public function load($source) {
    // $upload = isset($source[$this->name]) ? $source[$this->name] : null;
    if (isset($source[$this->name]) && $source[$this->name] instanceof Net_HTTP_Upload) $this->value = $source[$this->name];
    return true;
  }
///     </body>
///   </method>

///   <method name="set_value" access="protected">
///     <brief>Устанавливает значение поля</brief>
///     <details>
///       Установить извне невозможно
///     </details>
///     <args>
///       <arg name="value" />
///     </args>
///     <body>
  protected function set_value($value) { return $this->value; }
///     </body>
///   </method>

///   </protocol>
}
/// </class>

/// </module>
