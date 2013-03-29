<?php
/// <module name="Data" version="1.4.2" maintainer="timokhin@techart.ru">
///   <brief>Модуль представляющий собой набор вспомогательных классов, для представления различных струкру данных</brief>
/// <class name="Data" stereotype="module">
///   <implements interface="Core.ModuleInterface" />
///   <depends supplier="Data.Hash" stereotype="creates" />
///   <depends supplier="Data.Struct" stereotype="creates" />
///   <depends supplier="Data.Tree" stereotype="creates" />
class Data implements Core_ModuleInterface {

///   <constants>
  const VERSION = '1.4.2';
///   </constants>

///   <protocol name="building">

///   <method name="Hash" returns="Data.Hash" scope="class" stereotype="factory">
///     <brief>Фабричный метод. возвращающий объект класса Data.Hash</brief>
///     <args>
///       <arg name="values" type="array" default="array()" brief="массив в значений" />
///     </args>
///     <body>
  static public function Hash(array $values = array()) { return new Data_Hash($values); }
///     </body>
///   </method>

///   <method name="Struct" returns="Data.Struct" scope="class" stereotype="factory">
///     <brief>Фабричный метод. возвращающий объект класса Data.Struct</brief>
///     <args>
///       <arg name="values" type="array" brief="массив значений" />
///     </args>
///     <body>
  static public function Struct(array $values) { return new Data_Struct($values); }
///     </body>
///   </method>

///   <method name="Tree" returns="Data.Tree" scope="class" stereotype="factory">
///     <brief>Фабричный метод. возвращающий объект класса Data.Tree</brief>
///     <args>
///       <arg name="values" type="array" default="array()" brief="массив значений" />
///     </args>
///     <body>
  static public function Tree(array $values = array()) { return new Data_Tree($values); }
///     </body>
///   </method>

///   <method name="Wrapper" returns="Data.Wrapper" scope="class" stereotype="factory">
///     <brief>Фабричный метод. возвращающий объект класса Data.Wrapper</brief>
///     <args>
///       <arg name="object" type="object" brief="объект, вокруг которого строиться wrapper" />
///       <arg name="attrs" type="array" brief="массив дополнительных атрибутов" />
///     </args>
///     <body>
  static public function Wrapper($object, array $attrs = array()) { return new Data_Wrapper($object, $attrs); }
///     </body>
///   </method>

///   </protocol>
}
/// </class>


/// <interface name="Data.FreezeInterface">
///   <brief>Интерфейс для объектов поддрежвающих "заморозку"</brief>
///   <details>
///     Такие объекты после выхова метода freeze() становятся read-only
///   </details>
interface Data_FreezeInterface {
///   <protocol name="freezing">

///   <method name="freeze" returns="Data.FreezableInterface">
///     <brief>Делает объект read-only</brief>
///     <body>
  public function freeze();
///     </body>
///   </method>

///   <method name="is_frozen" returns="boolean">
///   <brief>Проверяет является ли объект "замороженным"</brief>
///     <body>
  public function is_frozen();
///     </body>
///   </method>

///   </protocol>
}
/// </interface>


/// <class name="Data.Hash">
///   <brief>Класс расширяющий стандартный класс ArrayObject</brief>
///   <implements interface="Data.FreezeInterface" />
class Data_Hash
  extends ArrayObject
  implements Data_FreezeInterface, Core_EqualityInterface, Core_IndexedAccessInterface {

  const JOIN_ALL      = 0;
  const JOIN_EXISTING = 1;
  const JOIN_MISSING  = 2;

  protected $frozen  = false;
  protected $default = null;

///   <protocol name="creating">

///   <method name="recursive" scope="class" returns="Data.Hash">
///     <brief>Возвращает Data.Hash рекурсивно сформированный из входного массива $values</brief>
///     <args>
///       <arg name="values" type="array" default="array" brief="массив значений" />
///     </args>
///     <body>
  static public function recursive(array $values) {
    $h = new Data_Hash();
    self::convert_arrays($values, $h);
    return $h;
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="configuring">

///   <method name="defaults_to" returns="Data.Hash">
///     <brief>Устанавливает значения по умолчанию</brief>
///     <args>
///       <arg name="values" brief="значения по умолчанию" />
///     </args>
///     <body>
  public function defaults_to($value) {
    $this->default = $value;
    return $this;
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="freezing" interface="Data.FreezeInterface">

///   <method name="freeze" returns="Data.Hash">
///     <brief>Делает объект read-only</brief>
///     <body>
  public function freeze() {
    $this->frozen = true;
    return $this;
  }
///     </body>
///   </method>

///   <method name="is_frozen" returns="boolean">
///   <brief>Проверяет является ли объект "замороженным". т.е. read-only</brief>
///     <body>
  public function is_frozen() { return $this->frozen; }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="changing">

///   <method name="append" returns="Data.Hash">
///     <brief>Добавляет новое значение</brief>
///     <args>
///       <arg name="value" brief="значение" />
///       <arg name="index" brief="ключ" />
///     </args>
///     <body>
  public function append($value, $index = null) {
    if ($this->is_frozen()) throw new Core_ReadOnlyObjectException($this);
    else {
      $index === null ? parent::append($value) : $this[$index] = $value;
      return $this;
    }
  }
///     </body>
///   </method>

///   <method name="delete" returns="Data.Hash">
///   <brief>Удаляет объект $object из хэша</brief>
///     <args>
///       <arg name="object" brief="объект" />
///     </args>
///     <body>
  public function delete($object) {
    if ($this->is_frozen())
      throw new Core_ReadOnlyObjectException($this);
    else {
      $is_object = Core_Types::is_object($object);
      foreach ($this as $k => &$v)
        if (($is_object && $v === $object) || (!$is_object && $v == $object)) unset($this[$k]);
      return $this;
    }
  }
///     </body>
///   </method>

///   <method name="merge_with" returns="Data.Hash">
///     <brief>Добавляет и замещает значения хэша из источника $source</brief>
///     <args>
///       <arg name="source" brief="массив - источник" />
///     </args>
///     <body>
  public function merge_with($source) { return $this->compose_with($source, self::JOIN_ALL); }
///     </body>
///   </method>

///   <method name="update_from" returns="Data.Hash">
///     <brief>Замещает уже установленные значения хэша из источника $source, и не добавляет новых</brief>
///     <args>
///       <arg name="source" brief="массив - источник" />
///     </args>
///     <body>
  public function update_from($source) { return $this->compose_with($source, self::JOIN_EXISTING); }
///     </body>
///   </method>

///   <method name="expand_with" returns="Data.Hash">
///     <brief>Добавляет только те значения, которые не установлены в хэше, ничего не замещает</brief>
///     <args>
///       <arg name="source" brief="массив - источник" />
///     </args>
///     <body>
  public function expand_with($source) { return $this->compose_with($source, self::JOIN_MISSING); }
///     </body>
///   </method>

///   <method name="append_from" returns="Data.Hash">
///     <brief>Добавляет к хэшу новые значения из итерируемого источника $source</brief>
///     <args>
///       <arg name="source" />
///     </args>
///     <body>
  public function append_from($source) {
    foreach ($source as $k => $v) {
      if (is_numeric($k)) $this[] = $v;
      else                $this[$k] = $v;
    }
    return $this;
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="indexing" interface="Core.IndexedAccessInterface">

///   <method name="offsetGet" returns="mixed">
///     <brief>Индексный доступ на чтение к свойствам объекта, т.е. к значениям хэша</brief>
///     <args>
///       <arg name="index" brief="индекс" />
///     </args>
///     <body>
  public function offsetGet($index) { return isset($this[$index]) ? parent::offsetGet($index) : $this->default; }
///     </body>
///   </method>

///   <method name="offsetSet" returns="mixed">
///     <brief>Индексный доступ на запись к свойствам объекта, т.е. к значениям хэша</brief>
///     <args>
///       <arg name="index" brief="индекс" />
///       <arg name="value" brief="значение" />
///     </args>
///     <body>
  public function offsetSet($index, $value) {
    if ($this->frozen)
      throw new Core_ReadOnlyObjectException($this);
    else
      parent::offsetSet($index, $value);
    return $this;
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="supporting">

///   <method name="convert_arrays" returns="Data.Hash" access="protected" scope="class">
///     <brief>Рекурсивно конвертирует массив в хэш</brief>
///     <args>
///       <arg name="source" brief="массив источник" />
///       <arg name="target" type="Data.Hash" brief="Хэш - назначение" />
///     </args>
///     <body>
  static protected function convert_arrays(&$source, Data_Hash $target) {
    foreach ($source as $k => &$v) {
      if (Core_Types::is_array($v))
        self::convert_arrays($v, $target[$k] = Data::Hash());
      else
        $target[$k] = $v;
    }
    return $target;
  }
///     </body>
///   </method>

///   <method name="compose_with" returns="Data.Hash" access="protected">
///     <brief>Добавляет новые элементы в хэш</brief>
///     <args>
///       <arg name="source" brief="массив - источник" />
///       <arg name="operation" type="constant" brief="тип объединения" />
///     </args>
///     <body>
  protected function compose_with($source, $operation = self::JOIN_ALL) {
    foreach ((($source instanceof Data_Hash) ? $source : Data::Hash((array) $source)) as $k => $v)
      if ($this[$k] instanceof Data_Hash && $v instanceof Data_Hash)
        $this[$k]->compose_with($v, $operation);
      else
        switch ($operation) {
          case self::JOIN_EXISTING:
            if (isset($this[$k])) $this[$k] = $v;
            break;
          case self::JOIN_MISSING:
            if (!isset($this[$k])) $this[$k] = $v;
            break;
          case self::JOIN_ALL:
          default:
            $this[$k] = $v;
        }
    return $this;
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="match" interface="Core.EqualityInterface">

///   <method name="equals" returns="boolean">
///     <brief>Сравнивает два хэша между собой</brief>
///     <args>
///       <arg name="to" />
///     </args>
///     <body>
  public function equals($to) {

    if (!($to instanceof Data_Hash) || (count($this) != count($to)))
      return false;

    foreach ($this as $k => &$v) {
      if (!Core::equals($v, $to[$k])) return false;
    }
    return true;
  }
///       </body>
///     </method>

///   </protocol>
}
/// </class>


/// <class name="Data.Tree">
///   <brief>Класс представляющий собой дерево</brief>
///   <implements interface="Core.PropertyAccessInterface" />
///   <implements interface="Data.FreezeInterface" />
class Data_Tree
  implements Core_PropertyAccessInterface,
             Data_FreezeInterface {

  protected $data = array();
  protected $_frozen = false;

///   <protocol name="creating">

///   <method name="__construct">
///     <brief>Конструктор</brief>
///     <args>
///       <arg name="values" type="array" default="array()" brief="массив значений" />
///     </args>
///     <body>
  public function __construct(array $values = array()) {
    foreach ($values as $k => &$v) $this->data[(string) $k] = $v;
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="building">

///   <method name="branch" returns="Data.Tree">
///     <brief>Создает новую ветку</brief>
///   <details>
///     Если значение с именем ветки $name не установлено, то порождает новый объект класса Data.Tree,
///     заполняя его переданными значениями
///   </details>
///     <args>
///       <arg name="name" type="string" brief="имя ветки" />
///       <arg name="items" type="array" default="array()" brief="массив значений" />
///     </args>
///     <body>
  public function branch($name, array $items = array()) {
    $name = (string) $name;

    if (!isset($this->data[$name])) {
      $class_name = Core_Types::real_class_name_for($this);
      $this->data[$name] = new $class_name();
      foreach ($items as $k => &$v) $this->data[$name]->$k = $v;
      return $this->data[$name];
    }

    throw new Core_ReadOnlyPropertyException($name);
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="accessing" interface="Core.PropertyAccessInterface">

///   <method name="__get" returns="mixed">
///     <brief>Доступ на чтение к свойствам объекта</brief>
///     <args>
///       <arg name="property" type="string" brief="имя свойства" />
///     </args>
///     <body>
  public function __get($property) {
    return isset($this->data[$property]) ? $this->data[$property] : null;
  }
///     </body>
///   </method>

///   <method name="__set" returns="mixed">
///     <brief>Доступ на запись к свойствам объекта</brief>
///     <args>
///       <arg name="property" type="string" brief="имя свойства" />
///       <arg name="value" brief="значение" />
///     </args>
///     <body>
  public function __set($property, $value) {
    if ($this->_frozen)  throw new Core_ReadOnlyObjectException($this);

    if (isset($this->data[$property]) && ($this->data[$property] instanceof Data_Tree))
      throw new Core_ReadOnlyPropertyException($property);
    else
      $this->data[$property] = $value;
    return $this;
  }
///     </body>
///   </method>


///   <method name="__isset" returns="boolean">
///     <brief>Проверяет установлено ли свойство объекта с именем $property</brief>
///     <args>
///       <arg name="property" type="string" brief="имя свойства" />
///     </args>
///     <body>
  public function __isset($property) { return isset($this->data[$property]); }
///     </body>
///   </method>

///   <method name="__unset">
///     <brief>Очищает свойство объекта</brief>
///     <args>
///       <arg name="property" type="string" brief="имя свойства" />
///     </args>
///     <body>
  public function __unset($property) {
    if ($this->_frozen)
      throw new Core_ReadOnlyObjectException($this);

    unset($this->data[$property]);
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="freezing" interface="Data.FreezeInterface">

///   <method name="freeze" returns="Data.Tree">
///     <brief>Делает объект read-only</brief>
///     <body>
  public function freeze() {
    $this->_frozen = true;
    return $this;
  }
///     </body>
///   </method>

///   <method name="is_frozen" returns="boolean">
///     <brief>Проверяет "заморожен" ли объект</brief>
///     <body>
  public function is_frozen() { return $this->_frozen; }
///     </body>
///   </method>

///   </protocol>
}
/// </class>


/// <class name="Data.AbstractStruct" stereotype="abstract">
///     <brief>Абстрактный класс рпедставляющий собой структуру</brief>
abstract class Data_AbstractStruct
  implements Core_PropertyAccessInterface,
             Data_FreezeInterface,
             Core_CallInterface {

  protected $_frozen = false;

///   <protocol name="accessing" interface="Core.PropertyAccessInterface">

///   <method name="__get" returns="mixed">
///     <brief>Доступ на чтение к свойств структуры</brief>
///     <args>
///       <arg name="property" type="string" brief="имя свойства" />
///     </args>
///     <body>
  public function __get($property) {
    if (property_exists($this, $property)) return $this->$property;
    else throw new Core_MissingPropertyException($property);
  }
///     </body>
///   </method>

///   <method name="__set" returns="mixed">
///     <brief>Доступ на запись к свойств структуры</brief>
///     <details>
///       Если есть метод вида: set_$property, где $property - имя свойсва,то
///       свойства устанавливается с помощью этого метода,
///       иначе устанавливается обычное свойство объекта
///     </details>
///     <args>
///       <arg name="property" type="string" brief="имя свойства" />
///       <arg name="value" brief="значение" />
///     </args>
///     <body>
  public function __set($property, $value) {
    if ($this->_frozen) throw new Core_ReadOnlyObjectException($this);
    if (method_exists($this, "set_$property")) {
      $method_name = "set_$property";
      return $this->$method_name($value);
    }
    if (property_exists($this, $property)) {$this->$property = $value; return $this;}
    throw new Core_MissingPropertyException($property);
  }
///     </body>
///   </method>

///   <method name="__isset" returns="boolean">
///     <brief>Проверяет устанавлено ли свойство структуры</brief>
///     <args>
///       <arg name="property" type="string" brief="имя свойства" />
///     </args>
///     <body>
  public function __isset($property) { return isset($this->$property); }
///     </body>
///   </method>

///   <method name="__unset">
///     <brief>Выбрасывает исключение, очищение свойст недоступно</brief>
///     <args>
///       <arg name="property" type="string" />
///     </args>
///     <body>
  public function __unset($property) {
    throw property_exists($this, $property) ?
      new Core_UndestroyablePropertyException($property) :
      new Core_MissingPropertyException($property);
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="freezing" interface="Data.FreezeInterface">

///   <method name="freeze" returns="Data.Struct">
///     <brief>Делает объект read-only</brief>
///     <body>
  public function freeze() {
    $this->_frozen = true;
    return $this;
  }
///     </body>
///   </method>

///   <method name="is_frozen" returns="boolean">
///     <brief>Проверяет является ли объект read-only</brief>
///     <body>
  public function is_frozen() { return $this->_frozen; }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="calling" interface="Core.CallInterface">

///   <method name="__call" returns="mixed">
///     <brief>Устанавливает значение свойства структуры с помощью вызова метода объекта</brief>
///     <details>
///       Если свойство с именем $method присутствует в объекте,
///       то этому свойству устанавливается значение первого аргумента метода
///    </details>
///     <args>
///       <arg name="method" type="string" brief="имя метода" />
///       <arg name="args" type="array" brief="массив параметров" />
///     </args>
///     <body>
  public function __call($method, $args) {
    if (property_exists($this, $method)) {
      $this->$method = $args[0];
      return $this;
    } else
      throw new Core_MissingMethodException($method);
  }
///     </body>
///   </method>

///   </protocol>
}
/// </class>


/// <class name="Data.Struct">
///   <brief>Класс представляющий собой структуру</brief>
///   <implements interface="Core.PropertyAccessInterface" />
///   <implements interface="Data.FreezeInterface" />
class Data_Struct
  implements Core_PropertyAccessInterface,
             Data_FreezeInterface {

  protected $values = array();
  protected $frozen = false;

///   <protocol name="creating">

///   <method name="__construct">
///     <brief>Конструктор</brief>
///     <args>
///       <arg name="values" type="array" brief="массив значений" />
///     </args>
///     <body>
  public function __construct(array $values) {
    foreach ($values as $k => &$v) $this->values[(string) $k] = $v;
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="accessing" interface="Core.PropertyAccessInterface">

///   <method name="__get" returns="mixed">
///     <brief>Доступ на чтение к свойствам структуры</brief>
///     <args>
///       <arg name="property" type="string" brief="имя свойства" />
///     </args>
///     <body>
  public function __get($property) {
    if (array_key_exists($property, $this->values))
      return $this->values[$property];
    else
      throw new Core_MissingPropertyException($property);
  }
///     </body>
///   </method>

///   <method name="__set" returns="mixed">
///     <brief>Доступ на запись к свойствам структуры</brief>
///     <args>
///       <arg name="property" type="string" brief="имя свойства" />
///       <arg name="value" brief="значение" />
///     </args>
///     <body>
  public function __set($property, $value) {
    if ($this->frozen) throw new Core_ReadOnlyObjectException($this);

    if (array_key_exists($property, $this->values))
      {$this->values[$property] = $value; return $this;}
    else
      throw new Core_MissingPropertyException($property);
  }
///     </body>
///   </method>

///   <method name="__isset" returns="boolean">
///     <brief>Проверяет установленно ли свойтсво структуры с именем $property</brief>
///     <args>
///       <arg name="property" type="string" brief="имя свойства" />
///      </args>
///     <body>
  public function __isset($property) {
    return isset($this->values[$property]);
  }
///     </body>
///   </method>

///   <method name="__unset">
///     <brief>Выбрасывает исключение</brief>
///     <args>
///       <arg name="property" type="string" />
///     </args>
///     <body>
  public function __unset($property) {
    throw (array_key_exists($property, $this->values)) ?
      new Core_UndestroyablePropertyException($property) :
      new Core_MissingPropertyException($property);
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="changing">

///   <method name="assign_from" returns="Data.Struct">
///     <brief>Обновляет значения структуры из источника $source, делая Core_Arrays::update</brief>
///     <args>
///       <arg name="source" />
///     </args>
///     <body>
  public function assign_from($source) {
    Core_Arrays::update($this->values, $source);
    return $this;
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="freezing" interface="Data.FreezeInterface">

///   <method name="freeze" returns="Data.Struct">
///     <brief>Делает объект read-only</brief>
///     <body>
  public function freeze() {
    $this->frozen = true;
    return $this;
  }
///     </body>
///   </method>

///   <method name="is_frozen" returns="boolean">
///     <brief>Проверяет является ли объект "замороженным", т.е. read-only</brief>
///     <body>
  public function is_frozen() { return $this->frozen; }
///     </body>
///   </method>

///   </protocol>
}
/// </class>


/// <class name="Data.Object" stereotype="abstract">
///   <brief>Класс представляет собой структуру с расширенными возможностями</brief>
///   <implements interface="Core.PropertyAccessInterface" />
///   <implements interface="Core.CallInterface" />
///   <implements interface="Data.FreezeInterface" />
abstract class Data_Object
  implements Core_PropertyAccessInterface,
             Core_CallInterface,
             Data_FreezeInterface,
             Core_EqualityInterface {

  protected $_frozen = false;

///   <protocol name="accessing" interface="Core.PropertyAccessInterface">

///   <method name="__get" returns="mixed">
///     <brief>Доступ на чтение к свойствам объекта</brief>
///     <details>
///       Если существует метод get_$property, где $property - имя свойства, то
///       то возвращается результат этого метода,
///        иначе возвращается значение обычного свойства объекта, если оно существует
///     </details>
///     <args>
///       <arg name="property" type="string" brief="имя свойства" />
///     </args>
///     <body>
  public function __get($property) {
    if (method_exists($this, $method = "get_{$property}"))
      return $this->$method();
    elseif (property_exists($this, $property))
      return $this->$property;
    else
      throw new Core_MissingPropertyException($property);
  }
///     </body>
///   </method>

///   <method name="__set" returns="mixed">
///     <brief>Доступ на запись к свойствам объекта</brief>
///     <details>
///       Если существует метод set_$property, где $property - имя свойства, то
///       то значение устанавливается с помощью этого метода,
///        иначе устанавливается значение обычного свойства объекта, если оно существует
///     </details>
///     <args>
///       <arg name="property" type="string" />
///       <arg name="value" />
///     </args>
///     <body>
  public function __set($property, $value) {
    if ($this->_frozen) throw new Core_ReadOnlyObjectException($this);
    if (method_exists($this, $method = "set_{$property}"))
        return $this->$method($value);
    elseif (property_exists($this, $property))
      {$this->$property = $value; return $this;}
    else
      throw new Core_MissingPropertyException($property);
  }
///     </body>
///   </method>

///   <method name="__isset" returns="boolean">
///     <brief>Проверяется существует ли свойство с именем $property</brief>
///     <args>
///       <arg name="property" type="string" brief="имя свойства" />
///     </args>
///     <body>
  public function __isset($property) {
    return method_exists($this, "get_{$property}") ||
           (property_exists($this, $property) && isset($this->$property));
  }
///     </body>
///   </method>

///   <method name="__unset">
///     <brief>Выбрасывает исключение</brief>
///     <args>
///       <arg name="property" type="string" />
///     </args>
///     <body>
  public function __unset($property) {
    throw (method_exists($this, "get_{$property}") || property_exists($this, $property)) ?
      new Core_UndestroyablePropertyException($property) :
      new Core_MissingPropertyException($property);
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="calling" interface="Core.CallInterface">

///   <method name="__call" returns="Data.Object">
///     <brief>Устанавливает свойство объекта с помощью вызова метода с именем свойства</brief>
///     <args>
///       <arg name="method" type="string" brief="имя метода-свойства" />
///       <arg name="args"   type="array()" brief="аргументы метода" />
///     </args>
///     <body>
  public function __call($method, $args) {
    if ($this->_frozen) throw new Core_ReadOnlyObjectException($this);

    if (method_exists($this, $set_method = "set_{$method}"))
      $this->$set_method($args[0]);
    elseif (property_exists($this, $method))
      $this->$method = $args[0];
    else
      throw new Core_MissingMethodException($method);

    return $this;
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="freezing" interface="Data.FreezeInterface">

///   <method name="freeze" returns="Data.Object">
///     <brief>Делает объект read-only</brief>
///     <body>
  public function freeze() { $this->_frozen = true; return $this; }
///     </body>
///   </method>

///   <method name="is_frozen" returns="boolean">
///     <brief>Проверяет является ли объект "замороженным", т.е. read-only</brief>
///     <body>
  public function is_frozen() { return $this->_frozen; }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="supporting">
///   <method name="get_properties" access="private" returns="array">
///     <brief>Возвращает массив всех свойств объекта</brief>
///     <body>
  private function get_properties() {
    $result = array();
    foreach (Core::with(new ReflectionObject($this))->getProperties() as $v)
      if(($name = $v->getName()) != '_frozen') $result[] = $v->getName();
    return $result;
  }
///     </body>
///   </method>
///   </protocol>

///   <protocol name="equality">
///   <method name="equals" returns="boolean">
///     <brief>Сравнивает два Data.Object, не учитывая динапические свойства get_ set_</brief>
///     <args>
///       <arg name="with" type="Data.Object" brief="с кем сравниваем" />
///     </args>
///     <body>
  public function equals($with) {
    if (!($with instanceof Data_Object) ||
        !Core::equals($p = $this->get_properties(), $with->get_properties()))
      return false;

    foreach($p as $v) if(!Core::equals($this->$v, $with->$v)) return false;

    return true;
  }
///     </body>
///   </method>

///   </protocol>
}
/// </class>

/// <class name="Data.Wrapper">
///   <brief>Обертка над объектом</brief>
///   <details>
///     Добавляет к объекту новые свойства, не изменяя самого объекта
///   </details>
///   <implements interface="Core.PropertyAccessInterface" />
///   <implements interface="Core.CallInterface" />
class Data_Wrapper
  implements Core_PropertyAccessInterface,
             Core_CallInterface {

  protected $object;
  protected $attrs = array();

///   <protocol name="creating">

///   <method name="__construct">
///     <brief>Конструктор</brief>
///     <args>
///       <arg name="object" type="object" brief="объект над которым строиться wrapper" />
///       <arg name="attrs" type="array" brief="массив дополнительных свойст, которые добавляются к объекту" />
///     </args>
///     <body>
  public function __construct($object, array $attrs) {
    $this->object = $object;
    $this->attrs  = $attrs;
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="accessing" interface="Core.PropertyAccessInterface">

///   <method name="__get" returns="mixed">
///     <brief>Доступ на чтение к свойствам объекта</brief>
///     <details>
///       Перенаправляет все обращения к $this->object, кроме тех, которые есть в $this->attrs
///       Так же доступны свойства __object и __attrs
///     </details>
///     <args>
///       <arg name="property" type="string" brief="имя свойства" />
///     </args>
///     <body>
  public function __get($property) {
    switch ($property) {
      case '__object':
        return $this->object;
      case '__attrs':
        return $this->attrs;
      default:
        return array_key_exists($property, $this->attrs) ? $this->attrs[$property] : $this->object->$property;
    }
  }
///     </body>
///   </method>

///   <method name="__set" returns="mixed">
///     <brief>Доступ на запись к свойствам объекта</brief>
///     <details>
///       Перенаправляет все обращения к $this->object, кроме тех, которые есть в $this->attrs
///     </details>
///     <args>
///       <arg name="property" type="string" />
///       <arg name="value" />
///     </args>
///     <body>
  public function __set($property, $value) {
    if(array_key_exists($property, $this->attrs))
      $this->attrs[$property] = $value;
    else
      $this->object->$property = $value;
    return $this;
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
    return isset($this->attrs[$property]) || isset($this->object->$property);
  }
///     </body>
///   </method>

///   <method name="__unset">
///     <brief>Очищает свойство объекта</brief>
///     <details>
///       Если свойство из $this->attrs, то выбрасывает исключение, иначе перенаправляет к $this->object
///     </details>
///     <args>
///       <arg name="property" type="string" brief="имя свойства объекта" />
///     </args>
///     <body>
  public function __unset($property) {
    if (isset($this->attrs[$property]))
      throw new Core_UndestroyablePropertyException($property);
    else
      unset($this->object);
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="calling">

///   <method name="__call">
///     <brief>Пренаправляет все вызовы к $this->object</brief>
///     <args>
///       <arg name="method" type="string" brief="имя метода" />
///       <arg name="args" type="array" brief="массив атрубутов" />
///     </args>
///     <body>
  public function __call($method, $args) {
    return call_user_func_array(array($this->object, $method), $args);
  }
///     </body>
///  </method>

///   </protocol>
}
/// </class>

/// </module>
