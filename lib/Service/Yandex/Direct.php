<?php
/// <module name="Service.Yandex.Direct" maintainer="timokhin@techart.ru" version="0.3.0">
Core::load('SOAP');

/// <class name="Service.Yandex.Direct" stereotype="module">
///   <implements interface="Core.ModuleInteface" />
class Service_Yandex_Direct implements Core_ModuleInterface {

///   <constants>
  const VERSION = '0.3.0';
///   </constants>

  static protected $options = array(
    'delay' => 5,
    'default_wsdl' => 'https://api.direct.yandex.ru/wsdl/v4/',
    'wsdl' => 'https://api.direct.yandex.ru/wsdl/v4/'
  );

  static private $api;
  static private $supress_exceptions = false;
  
///   <protocol name="creating">

///   <method name="initialize" scope="class">
///     <brief>Выполняет инициализацию модуля</brief>
///     <args>
///       <arg name="options" type="array" default="array()" brief="массив опций" />
///     </args>
///     <body>
  static public function initialize(array $options = array()) {
    return self::options($options);
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="configuring">

///   <method name="options" returns="mixed" scope="class">
///     <brief>Устанавливает значения списка опций, возвращает список значений всех опций</brief>
///     <args>
///       <arg name="options" type="array" default="array()" brief="массив опций" />
///     </args>
///     <body>
  static public function options(array $options = array()) {
    return self::$options = array_merge(self::$options, $options);
  }
///     </body>
///   </method>

///   <method name="option" returns="mixed">
///     <brief>Устанавливает опцию или возвращает ее значение</brief>
///     <args>
///       <arg name="name"  type="string"  brief="имя опции" />
///       <arg name="value" default="null" brief="значение" />
///     </args>
///     <body>
  static public function option($name, $value = null) {
    if (is_null($value))
      return self::$options[$name];
    else
      return self::$options[$name] = $value;
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="building">

///   <method name="connect" returns="Service.Yandex.Direct.APIMapper" scope="class">
///     <args>
///       <arg name="options" type="array" />
///     </args>
///     <body>
  static public function connect(array $options = array()) {
    if (!self::$api) {
      try {
        self::$api = new Service_Yandex_Direct_APIMapper($options);
      } catch (SoapException $e) {
        if (!self::supress_exceptions()) throw $e;
      }
    }
    return self::$api;
  }
///     </body>
///   </method>

///   <method name="api" returns="Service.Yandex.Direct.API" scope="class">
///     <body>
  static public function api() { return self::$api; }
///     </body>
///   </method>

///   <method name="Campaign" returns="Service.Yandex.Direct.Campaign" scope="class">
///     <body>
    static public function Campaign($entity) {
      return new Service_Yandex_Direct_Campaign($entity);
  }
///     </body>
///   </method>

///   <method name="Banner" returns="Service.Yandex.Direct.Banner" scope="class">
///     <body>
  static public function Banner($entity) {
    return new Service_Yandex_Direct_Banner($entity);
  }
///     </body>
///   </method>

///   <method name="Phrase" returns="Service.Yandex.Direct.Phrase" scope="class">
///     <body>
  static public function Phrase($entity) {
    return new Service_Yandex_Direct_Phrase($entity);
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="supporting">

  static public function supress_exceptions($flag = NULL) {
    if ($flag !== NULL) self::$supress_exceptions = (boolean) $flag;
    return self::$supress_exceptions;
  }

///   <method name="attr_name_for" returns="string" scope="class">
///     <args>
///       <arg name="name" type="string" />
///     </args>
///     <body>
  static public function attr_name_for($name) {
    static $cache = array();
    return isset($cache[$name]) ?
             $cache[$name] :
             $cache[$name] = ucfirst(Core_Strings::to_camel_case($name, false));
  }
///     </body>
///   </method>

///   </protocol>
}
/// </class>
/// <aggregation>
///   <source class="Service.Yandex.Direct" stereotype="module" multiplicity="1" />
///   <target class="Service.Yandex.Direct.APIMapper" stereotype="singletone" multiplicity="1" />
/// </aggregation>


/// <class name="Service.Yandex.Direct.Exception" extends="Core.Exception" stereotype="exception">
class Service_Yandex_Direct_Exception extends Core_Exception {}
/// </class>


/// <class name="Service.Yandex.Direct.BadCampaignScopeException" extends="Service.Yandex.Direct.Exception" stereotype="exception">
class Service_Yandex_Direct_BadCampaignScopeException extends Service_Yandex_Direct_Exception {}
/// </class>

/// <class name="Service.Yandex.Direct.BadEntityException" extends="Service.Yandex.Direct.Exception">
class Service_Yandex_Direct_BadEntityException extends Service_Yandex_Direct_Exception {}
/// </class>


/// <class name="Service.Yandex.Direct.APIConsumer" stereotype="abstract">
///   <depends supplier="Service.Yandex.Direct.APIMapper" />
abstract class Service_Yandex_Direct_APIConsumer {

///   <protocol name="supporting">

///   <method name="api" returns="Service.Yandex.Direct.APIMapper" access="protected">
///     <body>
  protected function api() { return Service_Yandex_Direct::api(); }
///     </body>
///   </method>

///   </protocol>
}
/// </class>


///  <class name="Service.Yandex.Direct.Entity" extends="Service.Yandex.Direct.APIConsumer">
///    <implements interface="Core.PropertyAccessInterface" />
///    <implements interface="Core.IndexedAccessInterface" />
class Service_Yandex_Direct_Entity extends Service_Yandex_Direct_APIConsumer
  implements Core_PropertyAccessInterface, Core_IndexedAccessInterface {

  protected $entity;

///   <protocol name="creating">

///   <method name="__construct">
///     <args>
///       <arg name="entity" type="object" />
///     </args>
///     <body>
  public function __construct($entity) {
    switch (true) {
      case $entity instanceof stdclass:
        $this->entity = $entity;
        break;
      case is_array($entity):
        $this->entity = Core::object($entity);
        break;
      default:
        throw new Service_Yandex_Direct_BadEntityException();
    }
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
    switch (true) {
      case $property === '__entity':
        return $this->entity;
      case method_exists($this, $m = "get_$property"):
        return $this->$m();
      default:
        $n = Service_Yandex_Direct::attr_name_for($property);
        return isset($this->entity->$n) ? $this->entity->$n : null;
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
    if (method_exists($this, $m = "set_$property"))
      $this->$m($value);
    else
      throw new Core_ReadOnlyObjectException($this);
  }
///     </body>
///   </method>

///   <method name="__isset" returns="boolean">
///      <args>
///        <arg name="property" type="string" />
///      </args>
///      <body>
  public function __isset($property) {
    switch (true) {
      case $property === '__entity':
        return true;
      case method_exists($this, $m = "isset_$property"):
        return $this->$m();
      case method_exists($this, $m = "get_$property"):
        return true;
      default:
        $n = Service_Yandex_Direct::attr_name_for($property);
        return isset($this->entity->$n);
    }
  }
///      </body>
///    </method>

///    <method name="__unset">
///      <args>
///        <arg name="property" type="string" />
///      </args>
///      <body>
  public function __unset($property) { return $this->__set($property, null); }
///      </body>
///    </method>

///    </protocol>

///    <protocol name="indexing" interface="Core.IndexedAccessInterface">

///    <method name="offsetGet" returns="mixed">
///      <body>
  public function offsetGet($property) {
    return isset($this->entity->$property) ? $this->entity->$property : null;
  }
///      </body>
///    </method>

///    <method name="offsetSet">
///      <args>
///        <arg name="property" type="string" />
///        <arg name="value" />
///      </args>
///      <body>
  public function offsetSet($property, $value) { throw new Core_ReadOnlyObjectException($this); }
///      </body>
///    </method>

///    <method name="offsetExists" returns="boolean">
///      <args>
///        <arg name="property" type="string" />
///      </args>
///      <body>
  public function offsetExists($property) { return isset($this->entity->$property); }
///      </body>
///    </method>

///    <method name="offsetUnset">
///      <args>
///        <arg name="property" type="string" />
///      </args>
///      <body>
  public function offsetUnset($property) { throw new Core_ReadOnlyObjectException($this); }
///      </body>
///    </method>

///    </protocol>

///   <protocol name="supporting">

///   <method name="assign" returns="Service.Yandex.Direct.Entity">
///     <args>
///       <arg name="values" type="array" />
///     </args>
///     <body>
  public function assign(array $values) {
    foreach ($values as $k => $v) $this->__set($k, $v);
    return $this;
  }
///     </body>
///   </method>

///   </protocol>
}
/// </class>


/// <class name="Service.Yandex.Direct.Collection" extends="Service.Yandex.Direct.APIConsumer" stereotype="abstract">
///   <implements interface="Core.PropertyAccessInterface" />
///   <implements interface="Core.IndexedAccessInterface" />
///   <implements interface="IteratorAggregate" />
///   <implements interface="Countable" />
///   <depends supplier="Service.Yandex.Direct.Iterator" stereotype="creates" />
abstract class Service_Yandex_Direct_Collection
  extends Service_Yandex_Direct_APIConsumer
  implements Core_PropertyAccessInterface,
             Core_IndexedAccessInterface,
             IteratorAggregate,
             Countable {

  protected $items = array();

///   <protocol name="creating">

///   <method name="__construct">
///     <args>
///       <arg name="items" />
///     </args>
///     <body>
  public function __construct($items) {
    if ($items instanceof Service_Yandex_Direct_Entity)
      $items = (array) $items;

    foreach ($items as $item) $this->items[] = $this->unwrap($item);
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
    switch (true) {
      case $property === '__items':
        return $this->items;
      case method_exists($this, $m = "get_$property"):
        return property_exists($this, $property) ?
          ($this->$property === null ? $this->$property = $this->$m() : $this->$property) : $this->$m();
      default:
        throw new Core_MissingPropertyException($property);
    }
  }
///     </body>
///   </method>

///   <method name="__set" returns="Service.Yandex.Direct.Collection">
///     <args>
///       <arg name="property" type="string" />
///       <arg name="value" />
///     </args>
///     <body>
  public function __set($property, $value) { throw new Core_ReadOnlyObjectException($this); }
///     </body>
///   </method>

///   <method name="__isset" returns="boolean">
///     <args>
///       <arg name="property" type="string" />
///     </args>
///     <body>
  public function __isset($property) {
    switch (true) {
      case $property == '__items':
        return true;
      case method_exists($this, $m = "isset_$property"):
        return $this->$m();
      case method_exists($this, "get_$property"):
        return true;
      default:
        return false;
    }
  }
///     </body>
///   </method>

///   <method name="__unset">
///     <args>
///       <arg name="property" type="string" />
///     </args>
///     <body>
  public function __unset($property) { throw new Core_ReadOnlyObjectException($this); }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="indexing" interface="Core.IndexedAccessInterface">

///   <method name="offsetGet" returns="mixed">
///     <args>
///       <arg name="index" type="int" />
///     </args>
///     <body>
  public function offsetGet($index) {
    return isset($this->items[$index]) ? $this->wrap($this->items[$index]) : null;
  }
///     </body>
///   </method>

///   <method name="offsetSet" returns="mixed">
///     <args>
///       <arg name="index" type="int" />
///     </args>
///     <body>
  public function offsetSet($index, $value) { throw new Core_ReadOnlyObjectException($this); }
///     </body>
///   </method>

///   <method name="offsetUnset">
///     <args>
///       <arg name="index" type="int" />
///     </args>
///     <body>
  public function offsetUnset($index) { throw new Core_ReadOnlyObjectException($this); }
///     </body>
///   </method>

///   <method name="offsetExists" returns="boolean">
///     <args>
///       <arg name="index" type="int" />
///     </args>
///     <body>
  public function offsetExists($index) { return isset($this->items[$index]); }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="building">

///   <method name="where" returns="Service.Yandex.Direct.Collection">
///     <args>
///       <arg name="conditions" type="array" />
///     </args>
///     <body>
  public function where(array $conditions) {
    $cc = array();
    foreach ($conditions as $k => $v) {
      $k = explode(' ', $k);
      $cc[] = array(Service_Yandex_Direct::attr_name_for($k[0]), isset($k[1]) ? $k[1] : '=', $v);
    }

    $res = array();
    $limit = count($cc);

    foreach ($this->items as $item) {
      $passed = 0;

      foreach ($cc as $cond) {
        $attr = $cond[0];

        switch ($cond[1]) {
          case '~':
            foreach ((array) $cond[2] as $v)
              if ($r = preg_match($v, (string) $item->$attr)) break;
            if ($r) $passed++;
            break;
          case '=':
            foreach ((array) $cond[2] as $v)
              if ($r = ($v == $item->$attr)) break;
            if ($r) $passed++;
            break;
          case 'in':
            if (array_search($item->$attr, $cond[2]) !== false) $passed++;
            break;
          case '!in':
            if (array_search($item->$attr, $cond[2]) === false) $passed++;
            break;
          case 'not':
            if (!isset($item->$attr) || !$item->$attr) $passed++;
            break;
        }
      }
      if ($passed == $limit) $res[] = $item;
    }

    return Core::make($this, $res);
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="performing">

///   <method name="assign" returns="Service.Yandex.Direct.Collection">
///     <args>
///       <arg name="values" type="array" />
///     </args>
///     <body>
  public function assign(array $values) {
    foreach ($this as $v) $v->assign($values);
    return $this;
  }
///     </body>
///   </method>

///   <method name="append" returns="Servuce.Yandex.Direct.Collection">
///     <args>
///     </args>
///     <body>
  public function append($item) {
    switch (true) {
      case $item instanceof Service_Yandex_Direct_Entity:
        $this->items[] = $this->unwrap($item);
        break;
      case $item instanceof stdclass:
        $this->items[] = $item;
        break;
      case is_array($item):
        $this->items[] = new stdclass($item);
        break;
      default:
        throw new Service_Yandex_Direct_BadEntityException();
    }
    return $this;
  }
///     </body>
///   </method>

///   </protocol>


///   <protocol name="supporting">

///   <method name="wrap" returns="object">
///     <args>
///       <arg name="entity" type="object" />
///     </args>
///     <body>
  protected function wrap($entity) { return $entity; }
///     </body>
///   </method>


  protected function unwrap($entity) { return $entity; }

///   <method name="get" returns="object">
///     <args>
///       <arg name="index" type="int" />
///     </args>
///     <body>
  public function get($index) { return $this[(int)$index]; }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="iterating" interface="IteratorAggregate">

///   <method name="getIterator" returns="Service.Yandex.Direct.Iterator">
///     <body>
  public function getIterator() { return new Service_Yandex_Direct_Iterator($this); }
///     </body>
///   </method>

///   </protocol>


///   <protocol name="counting" interface="Countable">

///   <method name="count" returns="int">
///     <body>
  public function count() { return count($this->items); }
///     </body>
///   </method>

///   </protocol>
}
/// </class>


/// <class name="Service.Yandex.Direct.EntityCollection" extends="Service.Yandex.Direct.Collection">
///   <depends supplier="Service.Yandex.Direct.Entity" stereotype="creates" />
class Service_Yandex_Direct_EntityCollection extends Service_Yandex_Direct_Collection {

///   <protocol name="supporting">

///   <method name="wrap" returns="Service.Yandex.Direct.Entity" access="protected">
///     <args>
///       <arg name="entity" type="object" />
///     </args>
///     <body>
  protected function wrap($entity) { return new Service_Yandex_Direct_Entity($entity); }
///     </body>
///   </method>

///   <method name="unwrap" returns="array" access="protected">
///     <args>
///       <arg name="entity" />
///     </args>
///     <body>
  protected function unwrap($entity) {
    switch (true) {
      case $entity instanceof Service_Yandex_Direct_Entity:
        return $entity->__entity;
      case is_object($entity) || is_array($entity):
        return $entity;
      default:
        throw new Service_Yandex_Direct_BadEntityException('Unknown entity type');
    }
  }
///     </body>
///   </method>

///   </protocol>
}
/// </class>


/// <class name="Service.Yandex.Direct.IndexedCollection" extends="Service.Yandex.Direct.Collection">
abstract class Service_Yandex_Direct_IndexedCollection extends Service_Yandex_Direct_EntityCollection {

  protected $ids = array();

///   <protocol name="creating">

///   <method name="__construct">
///     <args>
///       <arg name="items" type="array" />
///     </args>
///     <body>
  public function __construct(array $items) {
    parent::__construct($items);
    $this->actualize_index();
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
      case '__ids':
        return array_keys($this->ids);
      default:
        return parent::__get($property);
    }
  }
///     </body>
///   </method>

///   <method name="isset" returns="boolean">
///     <args>
///       <arg name="property" type="string" />
///     </args>
///     <body>
  public function __isset($property) {
    return $property == '__ids' || parent::__isset($property);
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="quering">

///   <method name="by_id" returns="Service.Yandex.Direct.Entity">
///     <args>
///       <arg name="id" type="int" />
///     </args>
///     <body>
  public function by_id($id) {
    return isset($this->ids[$id]) ? $this->wrap($this->items[$this->ids[$id]]) : null;
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="performing">

///   <method name="append" returns="Service.Yandex.Direct.IndexedCollection">
///     <args>
///       <arg name="item" />
///     </args>
///     <body>
  public function append($item) {
    parent::append($item);
    if ($id = $this->entity_id_for($item))
      $this->ids[$id] = count($this->items) - 1;
    return $this;
  }
///     </body>
///   </method>

///   </protocol>


///   <protocol name="supporting">

///   <method name="actualize_index" returns="Service.Yandex.Direct.IndexedCollection" access="protected">
///     <body>
  protected function actualize_index() {
    foreach ($this->items as $k => $v)
      if ($id = $this->entity_id_for($v)) $this->ids[$id] = $k;
    return $this;
  }
///     </body>
///   </method>

///   <method name="entity_id_for" returns="int" stereotype="abstract" access="protected">
///     <args>
///       <arg name="entity" type="object" />
///       <arg name="value"  default="NULL" />
///     </args>
///     <body>
  abstract protected function entity_id_for($entity, $value = NULL);
///     </body>
///   </method>

///   </protocol>
}
/// </class>


///  <class name="Service.Yandex.Direct.Iterator">
///    <implements interface="Iterator" />
class Service_Yandex_Direct_Iterator implements Iterator {

  protected $collection;
  protected $idx = 0;

///    <protocol name="creating">

///    <method name="__construct">
///      <args>
///        <arg name="collection" type="Service.Yandex.Direct.Collection" />
///      </args>
///      <body>
  public function __construct(Service_Yandex_Direct_Collection $collection) { $this->collection = $collection; }
///      </body>
///    </method>

///    </protocol>

///    <protocol name="iterating" interface="Iterator">

///    <method name="valid" returns="boolean">
///      <body>
  public function valid() { return isset($this->collection[$this->idx]); }
///      </body>
///    </method>

///    <method name="current" returns="Service.Yandex.Direct.Object">
///      <body>
  public function current() { return $this->collection[$this->idx]; }
///      </body>
///    </method>

///    <method name="next">
///      <body>
  public function next() { $this->idx++; }
///      </body>
///    </method>

///    <method name="key" returns="int">
///      <body>
  public function key() { return $this->idx; }
///      </body>
///    </method>

///    <method name="rewind">
///      <body>
  public function rewind() { $this->idx = 0; }
///      </body>
///    </method>

///    </protocol>
}
///  </class>


/// <class name="Service.Yandex.Direct.Mapper" extends="Service.Yandex.Direct.APIConsumer" stereotype="abstract">
///   <implements interface="Core.PropertyAccessInterface" />
abstract class Service_Yandex_Direct_Mapper
  extends Service_Yandex_Direct_APIConsumer
  implements Core_PropertyAccessInterface {

  protected $parent;

  public function __construct($parent = null) {
    $this->parent = $parent;
  }

///   <protocol name="accessing" interface="Core.PropertyAccessInterface">

///   <method name="__get" returns="mixed">
///     <args>
///       <arg name="property" type="string" />
///     </args>
///     <body>
  public function __get($property) {
    if (method_exists($this, $m = "get_$property"))
      return property_exists($this, $property) ?
        ($this->$property === null ? $this->$property = $this->$m() : $this->$property) : $this->$m();
    else
      throw new Core_MissingPropertyException($property);
  }
///     </body>
///   </method>

///   <method name="__set" returns="Service.Yandex.Direct.Mapper">
///     <args>
///       <arg name="property" type="string" />
///       <arg name="value" />
///     </args>
///     <body>
  public function __set($property, $value) { throw new Core_ReadOnlyObjectException($this); }
///     </body>
///   </method>

///   <method name="__isset" returns="boolean">
///     <args>
///       <arg name="property" type="string" />
///     </args>
///     <body>
  public function __isset($property) { return method_exists($this, "get_$property"); }
///     </body>
///   </method>

///   <method name="__unset">
///     <args>
///       <arg name="property" type="string" />
///     </args>
///     <body>
  public function __unset($property) { throw new Core_ReadOnlyObjectException($this); }
///     </body>
///   </method>

///   </protocol>
}
/// </class>


/// <class name="Service.Yandex.Direct.Filter" extends="Service.Yandex.Direct.APIConsumer" stereotype="abstract">
///   <implements interface="Core.CallInterface" />
abstract class Service_Yandex_Direct_Filter
  extends Service_Yandex_Direct_APIConsumer
  implements Core_CallInterface {

  private $parms = array();

///   <protocol name="calling" interface="Core.CallInterface">

///   <method name="__call" returns="Service.Yandex.Direct.Filter">
///     <args>
///       <arg name="method" type="string" />
///       <arg name="args" type="array" />
///     </args>
///     <body>
  public function __call($method, $args) {
    if (method_exists($this, $m = "set_$method"))
      $this->$m($args[0]);
    else {
      $n = Service_Yandex_Direct::attr_name_for($method);
      if (($v = $this->value_for($n, $args[0])) === null)
        throw new Core_MissingMethodException($method);
      else
        $this->parms[$n] = $v;
    }
    return $this;
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="converting">

///   <method name="as_array" returns="array">
///     <body>
  public function as_array() { return $this->parms; }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="supporting">

///   <method name="value_for" returns="mixed" access="protected">
///     <args>
///       <arg name="name" type="string" />
///       <arg name="value" />
///     </args>
///     <body>
  protected function value_for($name, $value) { return $value; }
///     </body>
///   </method>


///   </protocol>

}
/// </class>


/// <class name="Service.Yandex.Direct.APIMapper" extends="Service.Yandex.Direct.Mapper">
///   <implements interface="Core.CallInterface" />
///   <depends supplier="Service.Yandex.Direct.CampaignsMapper" stereotype="creates" />
///   <depends supplier="Service.Yandex.Direct.BannersMapper" stereotype="creates" />
///   <depends supplier="Service.Yandex.Direct.ClientsMapper" stereotype="creates" />
///   <depends supplier="Service.Yandex.Direct.ForecastsMapper" stereotype="creates" />
///   <depends supplier="Service.Yandex.Direct.RegionsCollection" stereotype="creates" />
///   <depends supplier="Service.Yandex.Direct.CategoriesCollection" streotype="creates" />
class Service_Yandex_Direct_APIMapper extends Service_Yandex_Direct_Mapper {

  protected $soap;
  protected $last_call_ts = 0;
  protected $last_error;

  protected $campaigns;
  protected $forecasts;
  protected $regions;
  protected $categories;
  protected $banners;
  protected $clients;

///   <protocol name="creating">

///   <method name="__construct">
///     <args>
///       <arg name="options" type="array" />
///     </args>
///     <body>
  public function __construct(array $options) {
    $this->soap = SOAP::Client(
      Service_Yandex_Direct::option('wsdl'),
      array_merge($options, array(
        'features' => SOAP_SINGLE_ELEMENT_ARRAYS,
        'trace' => 1)));
    if (empty($options['local_cert']) 
    		&& !empty($options['login'])
    		&& !empty($options['token'])
				&& !empty($options['application_id'])
    		) {
    	$headers = array();
			$headers[] = new SoapHeader(Service_Yandex_Direct::option('wsdl'), 'login', $options['login']);
			$headers[] = new SoapHeader(Service_Yandex_Direct::option('wsdl'), 'token', $options['token']);
			$headers[] = new SoapHeader(Service_Yandex_Direct::option('wsdl'), 'application_id', $options['application_id']); 
			$this->soap->__setSoapHeaders($headers);
    }
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="building">

///   <method name="all_campaigns" returns="Service.Yandex.Direct.CampaignsCollection">
///     <body>
  public function all_campaigns() {
    return new Service_Yandex_Direct_CampaignsCollection($this->api()->GetCampaignsList());
  }
///     </body>
///   </method>

///   <method name="campaigns_for" returns="Service.Yandex.Direct.CampaignsCollection">
///     <body>
  public function campaigns_for() {
    return new Service_Yandex_Direct_CampaignsCollection(
      $this->api()->GetCampaignsList(Core::normalize_args(func_get_args())));
  }
///     </body>
///   </method>

///   <method name="campaigns" returns="Service.Yandex.Direct.CampaignsCollection">
///     <body>
  public function campaigns($items = array()) {
    return new Service_Yandex_Direct_CampaignsCollection($items);
  }
///     </body>
///   </method>

///   <method name="balance_for" returns="Service.Yandex.Direct.BalanceCollection">
///     <body>
  public function balance_for() {
    return new Service_Yandex_Direct_BalanceCollection(
      $this->api()->GetBalance(Core::normalize_args(func_get_args())));
  }
///     </body>
///   </method>

///   <method name="all_clients" returns="Service.Yandex.Direct.ClientsCollection">
///     <body>
  public function all_clients() {
    return new Service_Yandex_Direct_ClientsCollection($this->api()->GetClientsList());
  }
///     </body>
///   </method>

///   <method name="client_named" returns="Service.Yandex.Direct.Client">
///     <args>
///       <arg name="login" type="string" />
///     </args>
///     <body>
  public function client_named($login) {
    return new Service_Yandex_Direct_Client($this->api()->GetClientInfo($login));
  }
///     </body>
///   </method>

///   <method name="find" returns="Service.Yandex.Direct.BannersCollection">
///     <body>
  public function find_banners() {
    return new Service_Yandex_Direct_BannersCollection(
      $this->api()->GetBanners(array('BannerIDS' => func_get_args())), 0);
  }
///     </body>
///   </method>

///   <method name="get_forecasts" returns="Service.Yandex.Direct.ForecastsMapper" access="protected">
///     <body>
  protected function get_forecasts() { return new Service_Yandex_Direct_ForecastsMapper(); }
///     </body>
///   </method>

///   <method name="get_regions" returns="Service.Yandex.Direct.RegionsCollection" access="protected">
///     <body>
  protected function get_regions() {
    return new Service_Yandex_Direct_RegionsCollection($this->api()->GetRegions());
  }
///     </body>
///   </method>

///   <method name="get_categories" returns="Service.Yandex.Direct.CategoriesMapper" access="protected">
///     <body>
  protected function get_categories() {
    return new Service_Yandex_Direct_CategoriesCollection($this->api()->GetRubrics());
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="calling" interface="Core.CallInterface">

///    <method name="__call" returns="mixed">
///      <args>
///        <arg name="method" type="string" />
///        <arg name="parms"  type="array" />
///      </args>
///      <body>
  public function __call($method, $args) {
    try {
      return $this->
        delay()->
        soap->__call(ucfirst(Core_Strings::to_camel_case($method)), $args);
    } catch (SoapException $e) {
      if (Service_Yandex_Direct::supress_exceptions())  {
        $this->last_error = $e;
        return null;
      } else throw $e;
    }
  }
///      </body>
///    </method>

///   </protocol>

///   <protocol name="supporting">

///   <method name="ping" returns="boolean">
///     <body>
  public function ping() { return $this->soap->PingAPI() === 1; }
///     </body>
///   </method>

///   <method name="get_version" returns="float" access="protected">
///     <body>
  protected function get_version() { return $this->GetVersion(); }
///     </body>
///   </method>

///   <method name="get_soap" returns="Soap.Client" access="protected">
///     <body>
  protected function get_soap() { return $this->soap; }
///     </body>
///   </method>

///   <method name="get_last_error" returns="SoapException" access="protected">
///     <body>
  protected function get_last_error() { return $this->last_error; }
///     </body>
///   </method>
  
///   <method name="delay" returns="Service.Yandex.Direct.APIMapper" access="private">
///     <body>
  private function delay() {
    $t = microtime();
    if (($this->last_call_ts > 0) &&
        ($d = ($t - $this->last_call_ts)) < Service_Yandex_Direct::option('delay')) sleep(Service_Yandex_Direct::option('delay') - $d);
    $this->last_call_ts = microtime();
    return $this;
  }
///     </body>
///   </method>

///   </protocol>
}
/// </class>
///   <aggregation>
///     <source class="Service.Yandex.Direct.APIMapper" stereotype="mapper" multiplicity="1" />
///     <target class="SOAP.Client" stereotype="client" multiplicity="1" />
///   </aggregation>


/// <class name="Service.Yandex.Direct.Campaign" extends="Service.Yandex.Direct.Object">
class Service_Yandex_Direct_Campaign extends Service_Yandex_Direct_Entity {

///   <protocol name="performing">

///   <method name="all_banners" returns="Service.Yandex.Direct.BannersCollection">
///     <body>
  public function all_banners() {
    return new Service_Yandex_Direct_BannersCollection(
      $this->api()->GetBanners(array('CampaignIDS' => array($this->entity->CampaignID))),
      $this->entity->CampaignID);
  }
///     </body>
///   </method>

///   <method name="save" returns="int">
///     <body>
  public function save() {
    if ($id = $this->api()->CreateOrUpdateCampaign($this->entity))
      $this->entity->CampaignID = $id;
    return $id;
  }
///     </body>
///   </method>

///   <method name="stop" returns="boolean">
///     <body>
  public function stop() { return $this->exec('StopCampaign'); }
///     </body>
///   </method>

///   <method name="resume" returns="boolean">
///     <body>
  public function resume() { return $this->exec('ResumeCampaign'); }
///     </body>
///   </method>

///   <method name="archive" returns="boolean">
///     <body>
  public function archive() { return $this->exec('ArchiveCampaign'); }
///     </body>
///   </method>

///   <method name="unarchive" returns="boolean">
///     <body>
  public function unarchive() { return $this->exec('UnarchiveCampaign'); }
///     </body>
///   </method>

///   <method name="delete" returns="boolean">
///     <body>
  public function delete() { return $this->exec('DeleteCampaign'); }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="supporting">

///   <method name="exec" returns="boolean" access="private">
///     <args>
///       <arg name="method" type="string" />
///     </args>
///     <body>
  private function exec($method) {
    return $this->api()->
      $method(Core::object(array(
        'CampaignID' => $this->entity->CampaignID))) ? true : false;
  }
///     </body>
///   </method>

///   </protocol>
}
/// </class>


/// <class name="Service.Yandex.Direct.CampaignsFilter" extends="Service.Yandex.Direct.Filter">
class Service_Yandex_Direct_CampaignsFilter extends Service_Yandex_Direct_Filter {
///   <protocol name="performing">

///   <method name="select_for" returns="Service.Yandex.Direct.CampaignsCollection">
///     <body>
  public function select_for() {
    return new Service_Yandex_Direct_CampaignsCollection(
      $this->api()->GetCampaignsListFilter(array('Logins' => func_get_args(), 'Filter' => $this->as_array())));
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="supporting">

///   <method name="value_for" returns="mixed">
///     <args>
///       <arg name="name" type="string" />
///       <arg name="value" />
///     </args>
///     <body>
  protected function value_for($name, $value) {
    switch ($name) {
      case 'StatusModerate':
      case 'StatusActivating':
      case 'StatusShow':
      case 'IsActive':
      case 'StatusArchive':
        return $value;
      default:
        return null;
    }
  }
///     </body>
///   </method>

///   </protocol>
}
/// </class>


/// <class name="Service.Yandex.Direct.CampaignsCollection" extends="Service.Yandex.Direct.IndexedCollection">
///   <depends supplier="Service.Yandex.Direct.Campaign" stereotype="creates" />
///   <depends supplier="Service.Yandex.Direct.BannersMapper" stereotype="creates" />
class Service_Yandex_Direct_CampaignsCollection extends Service_Yandex_Direct_IndexedCollection {

///   <protocol name="performing">

///   <method name="save" returns="int">
///     <body>
  public function save() {
    foreach ($this->items as &$item)
      if ($id = $this->api()->CreateOrUpdateCampaign($item))
        $this->entity_id_for($item, $id);
    $this->actualize_index();
    return $id;
  }
///     </body>
///   </method>

///   <method name="banners" returns="Service.Yandex.Direct.BannersCollection">
///     <body>
  public function all_banners() {
    return new Service_Yandex_Direct_BannersCollection(
      $this->api()->GetBanners(array('CampaignIDS' => $this->__ids)), 0);
  }
///     </body>
///   </method>

///   <method name="balance" returns="Service.Yandex.Direct.BalanceCollection" access="protected">
///     <body>
  public function balance() {
    return new Service_Yandex_Direct_BalanceCollection($this->api()->GetBalance($this->__ids));
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="supporting">

///   <method name="wrap" returns="Service.Yandex.Direct.Campaign" access="protected">
///     <args>
///       <arg name="entity" type="object" />
///     </args>
///     <body>
  protected function wrap($entity) { return new Service_Yandex_Direct_Campaign($entity); }
///     </body>
///   </method>

///   <method name="entity_id_for" returns="int" access="protected">
///     <args>
///       <arg name="entity" type="object" />
///       <arg name="value" default="NULL" />
///     </args>
///     <body>
    protected function entity_id_for($entity, $value = NULL) {
      if ($value !== NULL) $entity->CampaignID = $value;
      return $entity->CampaignID;
    }
///     </body>
///   </method>

///   </protocol>
}
/// </class>


/// <class name="Service.Yandex.Direct.Banner" extends="Service.Yandex.Direct.Entity">
///   <implements interface="Core.CallInterface" />
///   <depends supplier="Service.Yandex.Direct.PhrasesMapper" stereotype="creates" />
class Service_Yandex_Direct_Banner extends Service_Yandex_Direct_Entity {

  protected $phrases = array();

///   <method name="all_phrases" returns="Service.Yandex.Direct.PhrasesCollection">
///     <body>
  public function all_phrases() {
    $args = func_get_args();
    return new Service_Yandex_Direct_PhrasesCollection(
      count($args) ?
        $this->api()->GetBannerPhrasesFilter(array('BannerIDS' => array($this->entity->BannerID), 'FieldsNames' => Core::normalize_args($args))) :
        $this->api()->GetBannerPhrases($this->entity->BannerID));
  }
///     </body>
///   </method>

///   <protocol name="performing">

///   <method name="moderate" returns="boolean">
///     <body>
  public function moderate() { return $this->exec('ModerateBaners'); }
///     </body>
///   </method>

///   <method name="stop" returns="boolean">
///     <body>
  public function stop() { return $this->exec('StopBanners'); }
///     </body>
///   </method>

///   <method name="resume" returns="boolean">
///     <body>
  public function resume() { return $this->exec('ResumeBanners');}
///     </body>
///   </method>

///   <method name="archive" returns="boolean">
///     <body>
  public function archive() { return $this->exec('ArchiveBanners'); }
///     </body>
///   </method>

///   <method name"unarchive" returns="boolean">
///     <body>
  public function unarchive() { return $this->exec('UnarchiveBanners'); }
///     </body>
///   </method>

///   <method name="delete" returns="boolean">
///     <body>
  public function delete() { return $this->exec('DeleteBanners'); }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="supporting">

///   <method name="get_phrases" returns="Service.Yandex.Direct.PhraseCollection" access="protected">
///     <body>
  protected function get_phrases() {
    if ($this->phrases) return $this->phrases;
    if ($this->entity->BannerID)
      $this->phrases = $this->all_phrases();
    else
      $this->phrases = new Service_Yandex_Direct_PhraseCollection();

    return $this->phrases;
  }
///     </body>
///   </method>

///   <method name="exec" returns="boolean" access="private">
///     <args>
///       <arg name="method" type="string" />
///     </args>
///     <body>
  private function exec($method) {
    return $this->api->$method(
      Core::object(array(
        'CampaignID' => $this->entity->CampaignID,
        'BannerIDS'  => array($this->entity->BannerID)))) ? true : false;
  }
///       </body>
///     </method>

///   </protocol>
}
/// </class>


/// <class name="Service.Yandex.Direct.BannersCollection" extends="Service.Yandex.Direct.IndexedCollection">
///   <depends supplier="Service.Yandex.Direct.PhrasesMapper" steerotype="creates" />
///   <depends supplier="Service.Yandex.Direct.Banner" stereotype="creates" />
class Service_Yandex_Direct_BannersCollection extends Service_Yandex_Direct_IndexedCollection {

  protected $campaign_id;

///   <protocol name="creating">

///   <method name="__construct">
///     <args>
///       <arg name="items" type="array" />
///       <arg name="campaign_id" type="int" default="0" />
///     </args>
///     <body>
  public function __construct(array $items, $campaign_id = 0) {
    parent::__construct($items);
    $this->campaign_id = $campaign_id;
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="performing">

///   <method name="all_phrases" returns="Service.Yandex.Direct.PhrasesCollection">
///     <body>
  public function all_phrases() {
    $args = func_get_args();
    return new Service_Yandex_Direct_PhrasesCollection(
      count($args) ?
        $this->api()->GetBannerPhrasesFilter(array(
          'BannerIDS'   => $this->__ids,
          'FieldsNames' => Core::normalize_args($args))) :
        $this->api()->GetBannerPhrases($this->__ids));
  }
////    </body>
///   </method>

///   <method name="moderate" returns="boolean">
///     <body>
  public function moderate() { return $this->exec('ModerateBanners'); }
///     </body>
///   </method>

///   <method name="stop" returns="boolean">
///     <body>
  public function stop() { return $this->exec('StopBanners'); }
///     </body>
///   </method>

///   <method name="resume" returns="boolean">
///     <body>
  public function resume() { return $this->exec('ResumeBanners'); }
///     </body>
///   </metod>

///   <method name="archive" returns="boolean">
///     <body>
  public function archive() { return $this->exec('ArchiveBanners'); }
///     </body>
///   </method>

///   <method name="unarchive" returns="boolean">
///     <body>
  public function unarchive() { return $this->exec('UnarchiveBanners'); }
///     </body>
///   </method>

///   <method name="save" returns="array()">
///     <body>
  public function save() {
    $items = array();
    foreach ($this->items as $b)  {
      $e = clone $b->__entity;
      $e->Phrases = clone $b->phrases->__items;
      $items[] = $e;
    }
    return $this->api()->CreateOrUpdateBanners($this->items);
  }
///     </body>
///   </method>

///   <method name="delete" returns="boolean">
///     <body>
  public function delete() { return $this->exec('DeleteBanners'); }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="supporting">

///   <method name="wrap" returns="Service.Yandex.Direct.Entity" access="protected">
///     <args>
///       <arg name="entity" type="object" />
///     </args>
///     <body>
  protected function wrap($entity) { return new Service_Yandex_Direct_Banner($entity); }
///     </body>
///   </method>

///   <method name="entity_id_for" returns="int" access="protected">
///     <args>
///       <arg name="entity" />
///       <arg name="value" default="NULL" />
///     </args>
///     <body>
  protected function entity_id_for($entity, $value = NULL) {
    if ($value !== NULL) $entity->BannerID = $value;
    return $entity->BannerID;
  }
///     </body>
///   </method>

///   <method name="exec" returns="" access="private">
///     <args>
///       <arg name="action" type="string" />
///     </args>
///     <body>
  private function exec($action) {
    if ($this->campaign_id)
      return $this->api->$action(
        Core::object(array(
          'CampaignID' => $this->campaign_id,
          'BannerIDS'  => $this->__ids)));
    else
      throw new Service_Yandex_Direct_BadCampaignScopeException();
  }
///     </body>
///   </method>

///   </protocol>
}
/// </class>


/// <class name="Service.Yandex.Direct.Phrase" extends="Service.Yandex.Direct.Entity">
class Service_Yandex_Direct_Phrase extends Service_Yandex_Direct_Entity {

///   <protocol name="supporting">

///   <method name="get_id" returns="int" access="protected">
///     <body>
  protected function get_id() { return $this->entity->PhraseID; }
///     </body>
///   </method>

///   <method name="get_current_price" returns="float" access="protected">
///     <body>
  protected function get_current_price() { return (float) $this->entity->CurrentOnSearch; }
///     </body>
///   </method>

///   </protocol>
}
/// </class>


/// <class name="Service.Yandex.Direct.PhrasesCollection" extends="Service.Yandex.Direct.IndexedCollection">
///   <depends supplier="Service.Yandex.Direct.Phrase" stereotype="creates" />
///   <depends supplier="Service.Yandex.Direct.PricesCollection" stereotype="creates" />
class Service_Yandex_Direct_PhrasesCollection extends Service_Yandex_Direct_IndexedCollection {

///   <protocol name="supporting">

///   <method name="wrap" returns="Service.Yandex.Direct.PhrasesCollection">
///     <args>
///       <arg name="entity" />
///     </args>
///     <body>
  public function wrap($entity) { return new Service_Yandex_Direct_Phrase($entity); }
///     </body>
///   </method>

///   <method name="entity_id_for" returns="Service.Yandex.Direct.PhrasesCollection">
///     <args>
///       <arg name="entity" />
///       <arg name="value" default="NULL" />
///     </args>
///     <body>
    public function entity_id_for($entity, $value = NULL) {
      if ($value !== NULL) $entity->PhraseID = $value;
      return $entity->PhraseID;
    }
///     </body>
///   </method>

///   <method name="get_prices" returns="Service.Yandex.Direct.PricesCollection" access="protected">
///     <body>
  protected function get_prices() { return new Service_Yandex_Direct_PricesCollection($this); }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="quering">

///   <method name="by_phrase" returns="Service.Yandex.Direct.Phrase" access="protected">
///     <args>
///       <arg name="pattern" type="string" />
///     </args>
///     <body>
  protected function by_phrase($text) {
    foreach ($this->items as $k => $v)
      if ($text === $v->Phrase) return $this[$k];
    return null;
  }
///     </body>
///   </method>

///   <method name="by_phrase_match" returns="Service.Yandex.Direct.Phrase" access="protected">
///     <args>
///       <arg name="regexp" type="string" />
///     </args>
///     <body>
  protected function by_phrase_match($regexp) {
    foreach ($this->items as $k => $v)
      if (preg_match($pattern, $v->Phrase)) return $this[$k];
    return null;
  }
///     </body>
///   </method>

///   </protocol>
}
/// </class>


/// <class name="Service.Yandex.Direct.Price" extends="Service.Yandex.Direct.Entity">
class Service_Yandex_Direct_Price extends Service_Yandex_Direct_Entity {

///   <protocol name="supporting">

///   <method name="set_price" returns="Service.Yandex.Direct.Price">
///     <args>
///       <arg name="price" type="float" />
///     </args>
///     <body>
  protected function set_price($price) {
    $this->entity->Price = (float) $price;
    return $this;
  }
///     </body>
///   </method>

///   <method name="set_auto_broker" returns="Service,Yandex.Direct.Price">
///     <args>
///       <arg name="flag" />
///     </args>
///     <body>
  protected function set_auto_broker($flag) {
    $this->entity->AutoBroker = ($flag && $flag !== 'No') ? 'Yes' : 'No';
    return $this;
  }
///     </body>
///   </method>

///   </protocol>
}
/// </class>


/// <class name="Service.Yandex.Direct.PhrasesCollection" extends="Service.Yandex.Direct.IndexedCollection">
class Service_Yandex_Direct_PricesCollection extends Service_Yandex_Direct_IndexedCollection {

///   <protocol name="creating">

///   <method name="__construct">
///     <args>
///       <arg name="phrases" type="Service.Yandex.Direct.PhrasesCollection" />
///     </args>
///     <body>
  public function __construct(Service_Yandex_Direct_PhrasesCollection $phrases) {
    $items = array();
    foreach ($phrases->__items as $v) $items[] = new Service_Yandex_Direct_Price(Core::object(array(
      'CampaignID' => $v->CampaignID,
      'BannerID'   => $v->BannerID,
      'PhraseID'   => $v->PhraseID,
      'Price'      => $v->CurrentOnSearch,
      'AutoBroker' => $v->AutoBroker)));
    parent::__construct($items);
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="supporting">

///   <method name="entity_id_for" returns="int" access="protected">
///     <args>
///       <arg name="entity" type="object" />
///       <arg name="value" default="NULL" />
///     </args>
///     <body>
  protected function entity_id_for($entity, $value = NULL) {
    if ($value !== NULL) $entity->PhraseID = $value;
    return $entity->PhraseID;
  }
///     </body>
///   </method>

///   <method name="wrap" returns="Service.Yandex.Direct.Price" access="protected">
///     <args>
///       <arg name="entity" type="object" />
///     </args>
///     <body>
  protected function wrap($entity) { return new Service_Yandex_Direct_Price($entity); }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="performing">

///   <method name="assign" returns="Service.Yandex.Direct.PriceCollection">
///     <args>
///       <arg name="attrs" type="array" />
///     </args>
///     <body>
  public function assign(array $attrs) {
    foreach ($this as $v) $v->assign($attrs);
    return $this;
  }
///     </body>
///   </method>

///   <method name="update" returns="int">
///     <body>
  public function update() {
    return Service_Yandex_Direct::api()->UpdatePrices($this->items);
  }
///     </body>
///   </method>

///   </protocol>
}
/// </class>


/// <class name="Service.Yandex.Direct.Client" extends="Service.Yandex.Direct.Entity">
class Service_Yandex_Direct_Client extends Service_Yandex_Direct_Entity {

///   <protocol name="accessing">

///   <method name="get_id" returns="string" access="protected">
///     <body>
  protected function get_id() { return $this->entity->Login; }
///     </body>
///   </method>

///   </protocol>
}
/// </class>
/// <composition>
///   <source class="Service.Yandex.Direct.PricesCollection" stereotype="collection" multiplicity="1" />
///   <target class="Service.Yandex.Direct.Price" stereotype="price" multiplicity="N" />
/// </composition>


/// <class name="Service.Yandex.Direct.ClientsCollection" extends="Service.Yandex.Direct.IndexedCollection">
///   <depends supplier="Service.Yandex.Direct.Client" stereotype="creates" />
class Service_Yandex_Direct_ClientsCollection
  extends Service_Yandex_Direct_IndexedCollection {

///   <protocol name="supporting">

///   <method name="wrap" returns="Service.Yandex.Direct.Client" access="protected">
///     <body>
  protected function wrap($entity) { return new Service_Yandex_Direct_Client($entity); }
///     </body>
///   </method>

///   <method name="entity_id_for" returns="string" access="protected">
///     <args>
///       <arg name="entity" type="object" />
///       <arg name="value" default="NULL" />
///     </args>
///     <body>
    protected function entity_id_for($entity, $value = NULL) {
      if ($value !== NULL) $entity->Login = $value;
      return $entity->Login;
    }
///     </body>
///   </method>

///   </protocol>
}
/// </class>


/// <class name="Service.Yandex.Direct.Balance" extends="Service.Yandex.Direct.Entity">
class Service_Yandex_Direct_Balance extends Service_Yandex_Direct_Entity {

///   <protocol name="supporting">

///   <method name="get_id" returns="int" access="protected">
///     <body>
  protected function get_id() { return $this->entity->CampaignID; }
///     </body>
///   </method>

///   </protocol>
}
/// </class>


/// <class name="Service.Yandex.Direct.BalanceCollection" extends="Service.Yandex.Direct.IndexedCollection">
///   <depends supplier="Service.Yandex.Direct.Balance" stereotype="creates" />
class Service_Yandex_Direct_BalanceCollection extends Service_Yandex_Direct_IndexedCollection {

///   <protocol name="supporting">

///   <method name="wrap" returns="Service.Yandex.Direct.Balance" access="protected">
///     <args>
///       <arg name="entity" />
///     </args>
///     <body>
  protected function wrap($entity) { return new Service_Yandex_Direct_Balance($entity); }
///     </body>
///   </method>

///   <method name="entity_id_for" returns="object" access="protected">
///     <args>
///       <arg name="entity" type="object" />
///       <arg name="value " default="NULL" />
///     </args>
///     <body>
    protected function entity_id_for($entity, $value = NULL) {
      if ($value !== NULL) $entity->CampaignID = $value;
      return $entity->CampaignID;
    }
///     </body>
///   </method>

///   </protocol>
}
/// </class>


/// <class name="Service.Yandex.Direct.ForecastBuilder" extends="Service.Yandex.Direct.APIConsumer">
class Service_Yandex_Direct_ForecastBuilder extends Service_Yandex_Direct_APIConsumer {
  protected $attrs = array(
    'GeoID' => array(),
    'Phrases' => array(),
    'Categories' => array());

///   <protocol name="configuring">

///   <method name="with_geo_id" returns="Service…Yandex.Direct.ForecastBuilder">
///     <body>
  public function with_geo_id() {
    $args = func_get_args();
    return $this->update_attrs('GeoID', Core::normalize_args($args));
  }
///     </body>
///   </method>

///   <method name="with_phrases" returns="Service…Yandex.Direct.ForecastBuilder">
///     <body>
  public function with_phrases() {
    $args = func_get_args();
    return $this->update_attrs('Phrases', Core::normalize_args($args));
  }
///     </body>
///   </method>

///   <method name="with_categories" returns="Service…Yandex.Direct.ForecastBuilder">
///     <body>
  public function with_categories() {
    $args = func_get_args();
    return $this->update_attrs('Categories', Core::normalize_args($args));
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="performing">

///   <method name="create" returns="int">
///     <body>
  public function create() { return $this->api()->CreateNewForecast($this->attrs); }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="supporting">

///   <method name="update_attrs" returns="Service.Yandex.Direct.ForecastBuilder" access="private">
///     <args>
///       <arg name="name" type="string" />
///       <arg name="values" type="array" />
///     </args>
///     <body>
  private function update_attrs($name, array $values) {
    $this->attrs[$name] = array_merge($this->attrs[$name], $values);
    return $this;
  }
///     </body>
///   </method>

///   </protocol>

}
/// </class>


/// <class name="Service.Yandex.Direct.ForecastsMapper" extends="Service.Yandex.Direct.Mapper">
///   <depends supplier="Service.Yandex.Direct.ForecastBuilder" stereotype="creates" />
///   <depends supplier="Service.Yandex.Direct.Forecast" stereotype="creates" />
///   <implements interface="Core.IndexedAccessInterface" />
class Service_Yandex_Direct_ForecastsMapper
  extends Service_Yandex_Direct_Mapper
  implements Core_IndexedAccessInterface {

  protected $status    = array();
  protected $forecasts = array();
  protected $is_loaded = false;

///   <protocol name="building">

///   <method name="new_forecast" returns="Service.Yandex.Direct.ForecastBuilder">
///     <body>
  public function new_forecast() { return new Service_Yandex_Direct_ForecastBuilder(); }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="performing">

///   <method name="load" returns="Service.Yandex.Direct.Forecast">
///     <args>
///       <arg name="id" type="int" />
///     </args>
///     <body>
  public function load($id) { return new Service_Yandex_Direct_Forecast($this->api()->GetForecast($id)); }
///     </body>
///   </method>

///   <method name="check" returns="Service.Yandex.Direct.ForecastMapper">
///     <body>
  public function check() {
    foreach ($this->api()->GetForecastList() as $v) {
      $this->status[$v->ForecastID] = ($v->StatusForecast === 'Done');
    }
    $this->is_loaded = true;
    return $this;
  }
///     </body>
///   </method>

///   <method name="is_ready" returns="boolean">
///     <args>
///       <arg name="id" type="int" />
///     </args>
///     <body>
  public function is_ready($id){
    if (!$this->is_loaded) $this->check();
    return $this->status[$id];
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="indexing" interface="Core.IndexedAccessInterface">

///   <method name="offsetGet" returns="Service.Yandex.Direct.Forecast">
///     <args>
///       <arg name="index" type="int" />
///     </args>
///     <body>
  public function offsetGet($index) {
    return isset($this->forecasts[$index]) ?
      $this->forecasts[$index] :
      ($this->is_ready($index) ? $this->forecasts[$index] = $this->load($index) : null);
  }
///     </body>
///   </method>

///   <method name="offsetSet" returns="Service.Yandex.Direct.ForecastsMapper">
///     <args>
///       <arg name="index" type="int" />
///       <arg name="value" />
///     </args>
///     <body>
  public function offsetSet($index, $value) { throw new Core_ReadOnlyObjectException($this); }
///     </body>
///   </method>

///   <method name="offsetExists" returns="boolean">
///     <args>
///       <arg name="index" type="int" />
///     </args>
///     <body>
  public function offsetExists($index) { return $this->is_ready($index); }
///     </body>
///   </method>

///   <method name="offsetUnset" returns="Service.Yandex.Direct.ForecastsMapper">
///     <args>
///       <arg name="index" type="int" />
///     </args>
///     <body>
  public function offsetUnset($index) {
    if (isset($this->forecasts[$index])) unset($this->forecasts[$index]);
    return $this;
  }
///     </body>
///   </method>

///   </protocol>
}
/// </class>


/// <class name="Service.Yandex.Direct.Forecast">
class Service_Yandex_Direct_Forecast  {

  protected $phrases;
  protected $categories;
  protected $common;

///   <protocol name="creating">'

///   <method name="__construct">
///     <args>
///     </args>
///     <body>
  public function __construct($entity) {
    $f = Core::object();
    if (isset($entity->Phrases))
      $this->phrases = new Service_Yandex_Direct_EntityCollection($entity->Phrases);

    if (isset($entity->Categories))
      $this->categories = new Service_Yandex_Direct_EntityCollection($entity->Categories);

    if (isset($entity->Common))
      $this->common = new Service_Yandex_Direct_Entity($entity->Common);
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="accessing">

///   <method name="__get" returns="mixed">
///     <args>
///       <arg name="property" type="string" />
///     </args>
///     <body>
  public function __get($property) {
    switch ($property) {
      case 'phrases':
      case 'categories':
      case 'common':
        return $this->$property;
      default:
        if (isset($this->common->$property))
          return $this->common->$property;
        else
          throw new Core_MissingPropertyException($property);
    }
  }
///     </body>
///   </method>

///   <method name="__set" returns="Service.Yandex.Direct.Forecast">
///     <args>
///       <arg name="property" type="string" />
///       <arg name="value" />
///     </args>
///     <body>
  public function __set($property, $value) { throw new Core_ReadOnlyObjectException($propery); }
///     </body>
///   </method>

///   <method name="__isset" returns="boolean">
///     <args>
///       <arg name="property" type="string" />
///     </args>
///     <body>
  public function __isset($property) {
    switch ($property) {
      case 'phrases':
      case 'categories':
      case 'common':
        return isset($this->$property);
      default:
        return isset($this->common->$property);
    }
  }
///     </body>
///   </method>

///   <method name="__unset" returns="Service.Yandex.Direct.Corecast">
///     <args>
///       <arg name="property" type="string" />
///     </args>
///     <body>
  public function __unset($property) { throw new Core_ReadOnlyObjectException($propery); }
///     </body>
///   </method>

///   </protocol>

}
/// </class>
/// <composition>
///   <source class="Service.Yandex.Direct.Forecast" stereotype="forecast" multiplicity="1" />
///   <source class="Service.Yandex.Direct.EntityCollection" stereotype="part" multiplicity="N" />
/// </composition>


/// <class name="Service.Yandex.Direct.Region" extends="Service.Yandex.Direct.Entity">
class Service_Yandex_Direct_Region extends Service_Yandex_Direct_Entity {}
/// </class>


/// <class name="Service.Yandex.Direct.RegionsCollection" extends="Service.Yandex.Direct.IndexedCollection">
///   <depends supplier="Service.Yandex.Direct.Region" stereotype="creates" />
class Service_Yandex_Direct_RegionsCollection
  extends Service_Yandex_Direct_IndexedCollection {

///   <protocol name="supporting">

///   <method name="wrap" access="protected" returns="Service.Yandex.Direct.Region">
///     <args>
///       <arg name="entity" />
///     </args>
///     <body>
  protected function wrap($entity) { return new Service_Yandex_Direct_Region($entity); }
///     </body>
///   </method>

///   <method name="entity_id_for" returns="int" access="protected">
///     <args>
///       <arg name="entity" type="object" />
///       <arg name="value" default="NULL" />
///     </args>
///     <body>
    protected function entity_id_for($entity, $value = NULL) {
      if ($value !== NULL) $entity->RegionID = $value;
      return $entity->RegionID;
    }
///     </body>
///   </method>

///   </protocol>
}
/// </class>


/// <class name="Service.Yandex.Direct.Category" extends="Service.Yandex.Direct.Entity">
class Service_Yandex_Direct_Category extends Service_Yandex_Direct_Entity {}
/// </class>


/// <class name="Service.Yandex.Direct.CategoriesCollection" extends="Service.Yandex.Direct.IndexedCollection">
///   <depends supplier="Service.Yandex.Direct.Category" stereotype="creates" />
class Service_Yandex_Direct_CategoriesCollection extends Service_Yandex_Direct_IndexedCollection {

///   <protocol name="supporting">

///   <method name="wrap" returns="Service.Yandex.Direct.Category" access="protected">
///     <args>
///       <arg name="entity" type="object" />
///     </args>
///     <body>
  protected function wrap($entity) { return new Service_Yandex_Direct_Category($entity); }
///     </body>
///   </method>

///   <method name="entity_id_for" returns="int" access="protected">
///     <args>
///       <arg name="entity" type="object" />
///       <arg name="value" default="NULL" />
///     </args>
///     <body>
    protected function entity_id_for($entity, $value = NULL) {
      if ($value !== NULL) $entity->RubricID = $value;
      return $entity->RubricID;
    }
///     </body>
///   </method>

///   </protocol>
}
/// </class>

/// </module>
