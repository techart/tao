<?php
/// <module name="Object" version="0.2.1" maintainer="timokhin@techart.ru">
// TODO: избавляемся от wrapper

/// <class name="Object" stereotype="module">
///   <brief>Набор утилит для работы с объектами</brief>
///   <implements interface="Core.ModuleInterface" />
///   <details>
///   </details>
class Object implements Core_ModuleInterface {

///   <constants>
  const VERSION = '0.2.1';
///   </constants>

///   <protocol name="building">

///   <method name="AttrList" returns="Object.AttrList" scope="class">
///     <body>
  static public function AttrList() { return new Object_AttrList(); }
///     </body>
///   </method>

///   <method name="Listener" returns="Object.Listener" scope="class">
///     <args>
///       <arg name="type" type="string" default="null" />
///     </args>
///     <body>
  static public function Listener($type = null) { return new Object_Listener($type); }
///     </body>
///   </method>

///   <method name="Factory" returns="Object.Factory" scope="class">
///     <args>
///       <arg name="prefix" type="string" default="''" />
///     </args>
///     <body>
  static public function Factory($prefix = '') { return new Object_Factory($prefix); }
///     </body>
///   </method>

///   <method name="Aggregator" returns="Object.Aggregator" scope="class">
///     <body>
  static public function Aggregator() { return new Object_Aggregator(); }
///     </body>
///   </method>

///   <method name="Wrapper" returns="Object.Wrapper" scope="class">
///     <body>
  static public function Wrapper($object, array $attrs = array()) { return new Object_Wrapper($object, $attrs); }
///     </body>
///   </method>

  static public function Filter($value, $field = 'group') { return new Object_Filter($value, $field); }

///   </protocol>
}
/// </class>


/// <interface name="Object.AttrListInterface">
interface Object_AttrListInterface {

///   <protocol name="quering">

///   <method name="__attrs" returns="Object.AttrList">
///     <args>
///       <arg name="flavor" type="null" />
///     </args>
///     <body>
  public function __attrs($flavor = null);
///     </body>
///   </method>

///   </protocol>
}
/// </interface>


/// <class name="Object.Attribute" stereotype="abstract">
abstract class Object_Attribute {

  public $name;

///   <protocol name="creating">

///   <method name="__construct">
///     <args>
///       <arg name="name" type="string" />
///       <arg name="options" type="array" default="array()" />
///     </args>
///     <body>
  public function __construct($name, array $options = array()) {
    foreach ($options as $k => $v) $this->$k = $v;
    $this->name = $name;
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="quering">

///   <method name="is_object" returns="boolean">
///     <body>
  public function is_object() { return $this instanceof Object_ObjectAttribute; }
///     </body>
///   </method>

///   <method name="is_value" returns="boolean">
///     <body>
  public function is_value() { return $this instanceof Object_ValueAttribute; }
///     </body>
///   </method>

///   <method name="is_collection" returns="boolean">
///     <body>
  public function is_collection() { return $this instanceof Object_CollectionAttribute; }
///     </body>
///   </method>

///   </protocol>
}
/// </class>


/// <class name="Object.ObjectAttribute" extends="Object.Attribute">
class Object_ObjectAttribute extends Object_Attribute {}
/// </class>


/// <class name="Object.CollectionAttribute" extends="Object.Attribute">
class Object_CollectionAttribute extends Object_Attribute {}
/// </class>


/// <class name="Object.ValueAttribute" extends="Object.Attribute">
class Object_ValueAttribute extends Object_Attribute {}
/// </class>


/// <class name="Object.AttrList">
class Object_AttrList implements IteratorAggregate {

  protected $attrs = array();
  protected $parent;

///   <protocol name="configuring">

///   <method name="extend" returns="Object.AttrList">
///     <args>
///       <arg name="parent" type="Object.AttrList" />
///     </args>
///     <body>
  public function extend(Object_AttrList $parent) {
    $this->parent = $parent;
    return $this;
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="configuring">

///   <method name="object" returns="Object.AttrList">
///     <args>
///       <arg name="name"    type="string" />
///       <arg name="options" type="array" default="array()" />
///     </args>
///     <body>
  public function object($name, $type, array $options = array()) {
    foreach ((array) $name as $n)
      $this->attribute(
        new Object_ObjectAttribute(
          $n,
          array_merge($options, array('type' => $type))));
    return $this;
  }
///     </body>
///   </method>

///   <method name="collection" returns="Object.AttrList">
///     <args>
///       <arg name="name" type="string" />
///       <arg name="items" type="string" />
///       <arg name="options" type="array" default="array()" />
///     </args>
///     <body>
  public function collection($name, $items = null, array $options = array()) {
    foreach ((array) $name as $n)
      $this->attribute(
        new Object_CollectionAttribute(
          $name,
          array_merge($options, array('items' => $items))));
    return $this;
  }
///     </body>
///   </method>

///   <method name="value" returns="Object.AttrList">
///     <args>
///       <arg name="name" type="string" />
///       <arg name="options"  default="array()" />
///     </args>
///     <body>
  public function value($name, $options = array()) {
    foreach ((array) $name as $n)
      $this->attribute(
        new Object_ValueAttribute(
          $n, is_string($options) ? array('type' => $options) : (array) $options));
    return $this;
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="supporting">

///   <method name="attribute" returns="Obejct.AttrList" access="protected">
///     <args>
///       <arg name="attr" type="Object.Attribute" />
///     </args>
///     <body>
  protected function attribute(Object_Attribute $attr) {
    $this->attrs[$attr->name] = $attr;
    return $this;
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="iterating" interface="IteratorAggregate">

///   <method name="getIterator" returns="AppendIterator">
///     <body>
  public function getIterator() {
    $iterator = new AppendIterator();
    if (isset($this->parent)) $iterator->append($this->parent->getIterator());
    $iterator->append(new ArrayIterator($this->attrs));
    return $iterator;
  }
///     </body>
///   </method>

///   </protocol>
}
/// </class>

/// <class name="Object.BadConstException" extends="Core.Exception" stereotype="exception">
class Object_BadConstException extends Core_Exception {

  protected $value;

///   <protocol name="creating">

///   <method name="__construct">
///     <args>
///       <arg name="value" />
///     </args>
///     <body>
  public function __construct($value) { parent::__construct("Bad constant value: $value"); }
///     </body>
///   </method>

///   </protocol>
}
/// </class>

/// <class name="Object.Const" stereotype="abstract">
///   <brief>Базовый класс для констант</brief>
///   <implements interface="Core.StringifyInterface" />
///   <implements interface="Core.PropertyAccessInterface" />
abstract class Object_Const
  implements Core_StringifyInterface, Core_EqualityInterface {

  protected $value;

///   <protocol name="creating">

///   <method name="__construct">
///     <brief>Конструктор</brief>
///     <args>
///       <arg name="value" type="string" brief="значение константы" />
///     </args>
///     <body>
  protected function __construct($value) { $this->value = $value; }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="building">

///   <method name="object" returns="Object.Const" scope="class">
///     <brief>Возвращает объектное представление константы</brief>
///     <args>
///       <arg name="value" brief="константа типа, для которой необходимо получить объектное представлениен" />
///     </args>
///     <body>
  abstract static function object($value);
///     </body>
///   </method>

///   </protocol>

///   <protocol name="stringifying" interface="Core.StringifyInterface">

///   <method name="as_string" returns="string">
///     <brief>Возвращает строковое представление константы типа</brief>
///     <body>
  public function as_string() { return (string) $this->value; }
///     </body>
///   </method>

///   <method name="__toString" returns="string">
///     <brief>Возвращает строковое представление константы</brief>
///     <body>
  public function __toString() { return $this->as_string(); }
///     </body>
///   </method>

///   </protocol>

  public function equals($to) {
    return ($to instanceof $this) && ($this instanceof $to) && $to->value = $this->value;
  }

///   <protocol name="accessing" interface="Core.PropertyAccessInterface">

///   <method name="__get" returns="mixed">
///     <brief>Возвращает значение атрибута константы</brief>
///     <args>
///       <arg name="property" type="string" brief="имя атрибута" />
///     </args>
///     <body>
  public function __get($property) {
    switch (true) {
      case $property == 'value':                       return $this->value;
      case property_exists($this, $property):          return $this->$property;
      case method_exists($this, $m = "get_$property"): return $this->$m();
      default:
        throw new Core_MissingPropertyException($property);
    }
  }
///     </body>
///   </method>

///   <method name="__set" returns="mixed">
///     <brief>Установка значения атрибута константы</brief>
///     <args>
///       <arg name="property" type="string" brief="Имя атрибута" />
///       <arg name="value" brief="Значение атрибута" />
///     </args>
///     <details>
///       <p>Изменение значений атрибутов запрещено.</p>
///     </details>
///     <body>
  public function __set($property, $value) { throw new Core_ReadOnlyObjectException($this); }
///     </body>
///   </method>

///   <method name="__isset" returns="boolean">
///     <brief>Проверяет установку атрибута константы</brief>
///     <body>
  public function __isset($property) {
    return $property == 'value' || isset($this->$property) || method_exists($this, "get_$property");
  }
///     </body>
///   </method>

///   <method name="__unset">
///     <brief>Удаляет атрибут константы</brief>
///     <args>
///       <arg name="property" type="string" />
///     </args>
///     <body>
  public function __unset($propety) { throw new Core_ReadOnlyObjectException($this); }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="supporting">

///   <method name="value">
///     <body>
  public function value() { return $this->value; }
///     </body>
///   </method>

///   <method name="object_for" returns="OpenSocial.Type">
///     <brief>Возвращает объект константы по значению константы и имени класса объекта</brief>
///     <args>
///       <arg name="class" type="string" brief="имя класса объекта" />
///       <arg name="value" type="string|Object.Const" brief="константа" />
///       <arg name="cardinality" type="int" default="0" />
///     </args>
///     <body>
  static protected function object_for($class, $value, $cardinality = 0) {
    switch (true) {
      case $value instanceof $class:
        return $value;
      case is_string($value) && method_exists($class, $m = strtoupper((string) $value)):
        return  call_user_func(array($class, $m));
      case is_int($value) && ($value >= 0) && $value < $cardinality:
        return new $class($value);
      default:
        throw new Object_BadConstException($value);
    }
  }
///     </body>
///   </method>

///   </protocol>
}
/// </class>

/// <class name="Object.Struct">
///   <brief>Класс представляет собой структуру с расширенными возможностями</brief>
///   <implements interface="Core.PropertyAccessInterface" />
///   <implements interface="Core.CallInterface" />
class Object_Struct
  implements Core_PropertyAccessInterface,
             Core_CallInterface,
             Core_EqualityInterface {

///   <protocol name="accessing" interface="Core.PropertyAccessInterface">

///   <method name="__get" returns="mixed">
///     <brief>Доступ на чтение к свойствам объекта</brief>
///     <details>
///       Если существует метод get_$property, где $property - имя свойства,
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
///       Если существует метод set_$property, где $property - имя свойства,
///       то значение устанавливается с помощью этого метода,
///        иначе устанавливается значение обычного свойства объекта, если оно существует
///     </details>
///     <args>
///       <arg name="property" type="string" />
///       <arg name="value" />
///     </args>
///     <body>
  public function __set($property, $value) {
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

    switch (true) {
      case method_exists($this, $m = "set_{$property}"):
        call_user_func(array($this, $m), null);
        break;
      case property_exists($this, $property):
        $this->$property = null;
        break;
      default:
        throw new Core_MissingPropertyException($property);
    }
    return $this;
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="calling" interface="Core.CallInterface">

///   <method name="__call" returns="Data.Object">
///     <brief>Устанавливает свойство объекта с помощью вызова метода с именем свойства</brief>
///     <args>
///       <arg name="method" type="string" brief="имя метода-свойства" />
///       <arg name="args"   type="array()" brief="аргументы вызова" />
///     </args>
///     <body>
  public function __call($method, $args) {
    $this->__set($method, $args[0]);
    return $this;
  }
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
      if (($name = $v->getName()) != '_frozen') $result[] = $v->getName();
    return $result;
  }
///     </body>
///   </method>
///   </protocol>

///   <protocol name="equality">
///   <method name="equals" returns="boolean">
///     <brief>Сравнивает два Data.Object, не учитывая динамические свойства get_ set_</brief>
///     <args>
///       <arg name="with" type="Data.Object" brief="с кем сравниваем" />
///     </args>
///     <body>
  public function equals($with) {
    if (!($with instanceof Object_Struct) ||
        !Core::equals($p = $this->get_properties(), $with->get_properties()))
      return false;

    foreach ($p as $v) if (!Core::equals($this->$v, $with->$v)) return false;

    return true;
  }
///     </body>
///   </method>

///   </protocol>

}
/// </class>


/// <class name="Object.AbstractDelegator" stereotype="abstract">
///   <implements interface="IteratorAggregate" />
///   <implements interface="Core.CallInterface" />
///   <implements interface="Core.IndexedAccessInterface" />
abstract class Object_AbstractDelegator
  implements IteratorAggregate, Core_CallInterface, Core_IndexedAccessInterface {

  protected $delegates   = array();
  protected $reflections = array();
  protected $classes = array();
  protected $last_index = 0;

///   <protocol name="creating">

///   <method name="__construct">
///     <args>
///       <arg name="delegates" type="array" />
///     </args>
///     <body>
  public function __construct(array $delegates = array()) {
    foreach ($delegates as $d) $this->append($d);
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="configuring">

///   <method name="append_object" returns="Object.AbstractDelegator">
///     <body>
  protected function append_object($object, $index = null) {
    if (!is_string($index)) {
      $this->delegates[$this->last_index]   = $object;
      $this->reflections[$this->last_index] = new ReflectionObject($object);
      $this->last_index++;
    } else {
      $this->delegates[$index]   = $object;
      $this->reflections[$index] = new ReflectionObject($object);
    }

    return $this;
  }
///     </body>
///   </method>

///   <method name="append" returns="Object.AbstractDelegator">
///     <body>
  public function append($instance, $index = null) {
    if (is_string($instance))
      if (!is_string($index)) {
        $this->classes[$this->last_index] = $instance;
        $this->last_index++;
      }
      else $this->classes[$index] = $instance;
    else $this->append_object($instance, $index);
  }
///     </body>
///   </method>

///   <method name="remove" returns="Object.AbstractDelegator">
///     <body>
  public function remove($index) {
    if (isset($this->delegates[$index]))
      unset($this->delegates[$index]);
    if (isset($this->classes[$index]))
      unset($this->classes[$index]);
    return $this;
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="iterating" interface="IteratorAggregate">

///   <method name="getIterator" returns="ArrayIterator">
///     <body>
  public function getIterator() {
    foreach ($this->classes as $index => $class)
      $this->append_object(Core::make($class), $index);
    $this->classes = array();
    return new ArrayIterator($this->delegates);
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="indexing">

///   <method name="offsetGet" returns="object">
///     <args>
///       <arg name="index" />
///     </args>
///     <body>
  public function offsetGet($index) {
    switch (true) {
      case isset($this->delegates[$index]):
        return $this->delegates[$index];
      case isset($this->classes[$index]):
        $this->append_object(Core::make($this->classes[$index]), $index);
        unset($this->classes[$index]);
        return $this->delegates[$index];
    }
    return null;
  }
///     </body>
///   </method>

///   <method name="offsetSet" returns="object">
///     <args>
///       <arg name="index" />
///     </args>
///     <body>
  public function offsetSet($index, $value) {
    $this->append($value, $index);
    return $this;
  }
///     </body>
///   </method>

///   <method name="offsetExists" returns="boolean">
///     <args>
///       <arg name="index" />
///     </args>
///     <body>
  public function offsetExists($index) { return isset($this->delegates[$index]) || isset($this->classes[$index]); }
///     </body>
///   </method>

///   <method name="offsetUnset">
///     <args>
///       <arg name="index" />
///     </args>
///     <body>
  public function offsetUnset($index) {
    return $this->remove($index);
  }
///     </body>
///   </method>

///   </protocol>


}
/// </class>


/// <class name="Object.Listener" extends="Object.AbstractDelegator">
///   <implements interface="Core.PropertyAccessInterface" />
///   <brief>Делегирует вызов списку объектов</brief>
///   <details>
///     <p>Позволяет уведомлять объекты-слушатели о произошедших событиях. </p>
///   </details>
class Object_Listener extends Object_AbstractDelegator {

  protected $type;

///   <protocol name="creating">

///   <method name="__construct">
///     <args>
///       <arg name="type" type="string" default="null" />
///     </args>
///     <body>
  public function __construct($type = null, array $listeners = array()) {
    if ($type) $this->type = Core_Types::real_class_name_for($type);
    parent::__construct($listeners);
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="configuring">

///   <method name="append" returns="Object.Listener">
///     <args>
///       <arg name="listener" type="object" />
///     </args>
///     <body>
  public function append($listener, $index = null) {
    if (!$this->type || ($listener instanceof $this->type))
      return parent::append($listener, $index);
    else
      throw new Core_InvalidArgumentTypeException('listener', $listener);
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="calling" interface="Core.CallInterface">

///   <method name="__call" returns="mixed">
///     <args>
///       <arg name="method" type="string" />
///       <arg name="args" type="array" />
///     </args>
///     <body>
  public function __call($method, $args) {
    foreach ($this as $k => $v)
      if ($this->reflections[$k]->hasMethod($method)) $this->reflections[$k]->getMethod($method)->invokeArgs($v, $args);
    return $this;
  }
///     </body>
///   </method>

///   </protocol>
}
/// </class>


/// <class name="Object.Aggregator" extends="Object.AbstractDelegator">
class Object_Aggregator extends Object_AbstractDelegator {

  private $methods;
  private $fallback;

///   <protocol name="configuring">

///   <method name="fallback_to" returns="Object.Aggregator">
///     <body>
  public function fallback_to(Object_Aggregator $fallback) {
    $this->fallback = $fallback;
    return $this;
  }
///     </body>
///   </method>

    public function clear_fallback() {
        $this->fallback = null;
        return $this;
    }

///   </protocol>

///   <protocol name="calling" interface="Core.CallInterface">

///   <method name="__call" returns="mixed">
///     <args>
///       <arg name="method" type="string" />
///       <arg name="args"   type="array" />
///     </args>
///     <body>
  public function __call($method, $args) {
    if (!isset($this->methods[$method])) {
      foreach ($this as $k => $d) {
        $r = $this->reflections[$k];
        if ($r->hasMethod($method)) {
          $this->methods[$method] = array($d, $r->getMethod($method));
          break;
        }
      }
    }
    switch (true) {
      case isset($this->methods[$method]):
        return $this->methods[$method][1]->invokeArgs($this->methods[$method][0], $args);
      case $this->fallback:
        return $this->fallback->__call($method, $args);
      default:
        throw new Core_MissingMethodException($method);
    }
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="indexing">

///   <method name="offsetGet" returns="object">
///     <args>
///       <arg name="index" />
///     </args>
///     <body>
  public function offsetGet($index) {
    $from_parent  = parent::offsetGet($index);
    if (!empty($from_parent)) return $from_parent;
    if ($this->fallback instanceof self)
        return $this->fallback[$index];
    throw new Core_MissingIndexedPropertyException($index);
  }
///     </body>
///   </method>

///   <method name="offsetSet" returns="object">
///     <args>
///       <arg name="index" />
///     </args>
///     <body>
  public function offsetSet($index, $value) {
    return parent::offsetSet($index, $value);
  }
///     </body>
///   </method>

///   <method name="offsetExists" returns="boolean">
///     <args>
///       <arg name="index" />
///     </args>
///     <body>
  public function offsetExists($index) {
    return parent::offsetExists($index) || (($this->fallback instanceof self) && isset($this->fallback[$index])); }
///     </body>
///   </method>

///   <method name="offsetUnset">
///     <args>
///       <arg name="index" />
///     </args>
///     <body>
  public function offsetUnset($index) {
    parent::offsetUnset($index);
    if (isset($this->fallbak[$index]))
        unset($this->fallbak[$index]);
  }
///     </body>
///   </method>

///   </protocol>

}
/// </class>


/// <class name="Object.Factory">
///   <implements interface="Core.CallInterface" />
class Object_Factory implements Core_CallInterface {

  private $map = array();
  private $prefix;


///   <protocol name="creating">

///   <method name="__construct">
///     <args>
///       <arg name="defaults" type="array" default="array()" />
///       <arg name="prefix" type="string" default="''" />
///     </args>
///     <body>
  public function __construct($prefix = '') {
    $this->prefix = $prefix;
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="configuring">

///   <method name="map" returns="Object.Factory">
///     <args>
///       <arg name="name" type="string" />
///       <arg name="type" type="string" default="null" />
///     </args>
///     <body>
  public function map($name, $type = null) {
    switch (true) {
      case is_array($name):
        $prefix = ($type === null ? $this->prefix : (string) $type);
        foreach ($name as $k => $v) $this->map[$k] = "$prefix$v";
        break;
      case is_string($name):
        if ($type)
          $this->map[$name] = "{$this->prefix}$type";
        else
          throw new Core_InvalidArgumentTypeException('type', $type);
        break;
      default:
        throw new Core_InvalidArgumentTypeException('name', $name);
    }
    return $this;
  }
///     </body>
///   </method>

///   <method name="map_list" returns="Delegate.FactoryDelegator">
///     <args>
///       <arg name="maps" type="array" />
///       <arg name="prefix" type="string" default="" />
///     </args>
///     <body>
  public function map_list(array $maps, $prefix = '') {
    foreach ($maps as $k => $v) $this->map($k, "$prefix$v");
    return $this;
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="building">

///   <method name="new_instance_of" returns="object">
///     <args>
///     </args>
///     <body>
  public function new_instance_of($name, $args = array()) {
    if (isset($this->map[$name]))
      return Core::amake($this->map[$name], $args);
    else
      throw new Core_InvalidArgumentValueException('name', $name);
  }
///     </body>
///   </method>

///   <method name="__call" returns="object">
///     <args>
///       <arg name="method" type="string" />
///       <arg name="args"   type="array" />
///     </args>
///     <body>
  public function __call($method, $args) { return $this->new_instance_of($method, $args); }
///     </body>
///   </method>

///   </protocol>
}
/// </class>


/// <class name="Object.Wrapper">
///   <implements interface="Core.PropertyAccessInterface" />
///   <implements interface="Core.CallInterface" />
class Object_Wrapper
  implements Core_PropertyAccessInterface, Core_CallInterface {

  protected $object;
  protected $attrs = array();

///   <protocol name="creating">

///   <method name="__construct">
///     <args>
///       <arg name="object" type="object" />
///       <arg name="attrs"  type="array"  />
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
///     <args>
///       <arg name="property" type="string" />
///     </args>
///     <body>
  public function __get($property) {
    switch ($property) {
      case '__object':
        return $this->object;
      case '__attrs':
        return $this->attrs;
      default:
        return array_key_exists($property, $this->attrs) ?
          $this->attrs[$property] : $this->object->$property;
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
    if (array_key_exists($property, $this->attrs))
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
///     <args>
///       <arg name="property" type="string"  />
///     </args>
///     <body>
  public function __unset($property) {
    if (array_key_exists($property, $this->attrs))
      unset($this->attrs[$property]);
    else
      unset($this->object->$property);
    return $this;
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="calling">

///   <method name="__call">
///     <args>
///       <arg name="method" type="string" />
///       <arg name="args" type="array" />
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

class Object_Filter {

  protected $field;
  protected $value;

  public function __construct($value, $field = 'group') {
    $this->field = $field;
    $this->value = $value;
  }

  public function filter($e) {
    return $e[$this->field] == $this->value;
  }
}

/// </module>
