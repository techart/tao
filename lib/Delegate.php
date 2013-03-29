<?php
/// <module name="Delegate" version="1.3.0" maintainer="timokhin@techart.ru">
Core::load('Data');

/// <class name="Delegate" stereotype="module">
///   <implements interface="Core.ModuleInterface" />
///   <depends supplier="Delegate.AggregateDelegator" stereotype="creates" />
///   <depends supplier="Delegate.FactoryDelegator" stereotype="creates" />
///   <depends supplier="Delegate.GenericFunctionDelegator" stereotype="creates" />
class Delegate implements Core_ModuleInterface {

///   <constants>
  const MODULE  = 'Delegate';
  const VERSION = '1.3.0';
///   </constants>

///   <protocol name="building">
  
///   <method name="AggregateDelegator" returns="Delegate.AggregateDelegator" scope="class" stereotype="factory">
///     <body>
  static public function AggregateDelegator() { 
    return new Delegate_AggregateDelegator(func_get_args()); 
  }
///     </body>
///   </method>

///   <method name="FactoryDelegator" returns="Delegate.FactoryDelegator" scope="class">
///     <args>
///       <arg name="defaults" type="array" default="array()" />
///       <arg name="prefix" type="string" default="" />
///     </args>
///     <body>
  static public function FactoryDelegator(array $defaults = array(), $prefix = '') {
    return new Delegate_FactoryDelegator($defaults, (string) $prefix);
  }
///     </body>
///   </method>

///   <method name="ListenerDelegator" returns="Delegate.ListenerDelegator" scope="class">
///     <args>
///       <arg name="type" type="string" default="''" />
///     </args>
///     <body>
  static public function ListenerDelegator($type = '') { return new Delegate_ListenerDelegator($type); }
///     </body>
///   </method>

///   </protocol>
}
/// </class>


/// <class name="Delegate.Exception" extends="Core.Exception" stereotype="exception">
class Delegate_Exception extends Core_Exception {}
/// </class>


/// <class name="Delegate.MissingDelegationMethodException" extends="Delegate.Exception" stereotype="exception">
class Delegate_MissingDelegationMethodException extends Delegate_Exception {
  protected $method;

///   <protocol name="creating">

///   <method name="__construct">
///     <args>
///       <arg name="method" type="string" />
///     </args>
///     <body>
  public function __construct($method) {
    $this->method = $method;
    parent::__construct("Missing method to delegate: $this->method");
  }
///     </body>
///   </method>

///   </protocol>
}
/// </class>


/// <class name="Delegate.AggregateDelegator">
///   <implements interface="Core.IndexedAccessInterface" />
///   <implements interface="IteratorAggregate" />
///   <implements interface="Core.CallInterface" />
///   <depends supplier="Delegate.MissingDelegationMethodException" stereotype="throws" />
class Delegate_AggregateDelegator 
  implements Core_IndexedAccessInterface, 
             IteratorAggregate,
             Core_CallInterface {

  private $fallback;
  private $methods;
  private $objects;
  private $reflections;

///   <protocol name="creating">

///   <method name="__construct">
///     <args>
///       <arg name="delegates" type="array" default="array()" />
///     </args>
///     <body>  
  public function __construct(array $delegates = array()) { 
    $this->objects = new ArrayObject();
    $this->reflections = new ArrayObject();
    $this->methods   = new ArrayObject();
    foreach ($delegates as $delegate) $this->append($delegate); 
  }
///     </body>
///   </method>

///   <method name="fallback_to" returns="Delegate.AggregateDelegator">
///     <args>
///       <arg name="fallback" type="Delegate.AggregateDelegator" />
///     </args>
///     <body>
  public function fallback_to(Delegate_AggregateDelegator $fallback) {
    $this->fallback = $fallback;
    return $this;
  }
///     </body>
///   </method>
  
///   </protocol>

///   <protocol name="building">

///   <method name="append" returns="Delegate.AggregateDelegator">
///     <args>
///       <arg name="delegate" type="object" />
///     </args>
///     <body>
  public function append($delegate, $name = null) {
    if (!Core_Types::is_object($delegate))
      throw new Core_InvalidArgumentTypeException('delegate', $delegate);

    $this->objects[$name] = $delegate;
    $this->reflections[$name] = Core_Types::reflection_for($delegate);
    return $this;
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="calling">  

///   <method name="__call" returns="mixed">
///     <args>
///       <arg name="method" type="string" />
///       <arg name="args" type="array" />
///     </args>
///     <body>
  public function __call($method, $args) {
    if (!isset($this->methods[$method])) { 
      foreach ($this->reflections as $key => $reflection)
        if ($reflection->hasMethod($method)) { 
          $this->methods[$method] = array(
            0 => $this->objects[$key],
            1 => $reflection->getMethod($method));
          break;
        }
    }

    if (isset($this->methods[$method]))
        return $this->methods[$method][1]->invokeArgs($this->methods[$method][0], $args);
    else
      if ($this->fallback)
        return $this->fallback->__call($method, $args);
      else
        throw new Delegate_MissingDelegationMethodException($method);
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="iterating" interface="IteratorAggregate">
  
///   <method name="getIterator" returns="Iterator">
///     <body>
  public function getIterator() { return $this->objects->getIterator(); }
///     </body>
///   </method>  
  
///   </protocol>

///   <protocol name="indexing" interface="Core.IndexedPropertyAccessInterface">

///   <method name="offsetGet" returns="object">
///     <args>
///       <arg name="index" />
///     </args>
///     <body>
  public function offsetGet($index) { 
    return $this->objects[$index]; 
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
  public function offsetExists($index) { return isset($this->objects[$index]); }
///     </body>
///   </method> 
  
///   <method name="offsetUnset">
///     <args>
///       <arg name="index" />
///     </args>
///     <body>
  public function offsetUnset($index) { 
    throw $this->offsetExists($index) ?
      new Core_UndestroyableIndexedPropertyException($index) :
      new Core_MissingIndexedPropertyException($index);
  }
///     </body>
///   </method>
  
///   </protocol>
}
/// </class>


/// <class name="Delegate.UnknownFactoryTypeName" extends="Delegate.Exception" stereotype="exception">
class Delegate_UnknownFactoryTypeName extends Delegate_Exception {
  protected $type;

///   <protocol name="creating">

///   <method name="__construct">
///     <args>
///       <arg name="type" type="string" />
///     </args>
///     <body>
  public function __construct($type) {
    $this->type = $type;
    parent::__construct("Unknown factory type name: $type");
  }
///     </body>
///   </method>

///   </protocol>
}
/// </class>


/// <class name="Delegate.FactoryDelegator">
///   <implements interface="Core.CallInterface" />
///   <depends supplier="Delegate.UnknownFactoryTypeName" stereotype="throws" />
class Delegate_FactoryDelegator implements Core_CallInterface {
  protected $map;
  protected $prefix;

///   <protocol name="creating">

///   <method name="__construct">
///     <args>
///       <arg name="defaults" type="array" default="array()" />
///       <arg name="prefix" type="string" default="" />
///     </args>
///     <body>
  public function __construct(array $defaults = array(), $prefix = '') {
    $this->map = new ArrayObject();
    $this->prefix = $prefix;
    $this->map_list($defaults);
  }  
///     </body>
///   </method>  

///   </protocol>

///   <protocol name="configuring">

///   <method name="map" returns="Delegate.FactoryDelegator">
///     <args>
///       <arg name="name" type="string" />
///       <arg name="type" type="string" />
///     </args>
///     <body>
  public function map($name, $type) {
    $this->map[$name] = Core_Types::reflection_for("{$this->prefix}$type");
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

///   <method name="new_instance_of" returns="mixed">
///     <args>
///       <arg name="name" type="string" />
///       <arg name="args" type="array" />
///     </args>
///     <body>
  public function new_instance_of($name, $args = null) { 
    if (isset($this->map[$name]))
      if($args != null) 
        return $this->map[$name]->newInstanceArgs($args);
      else
        return $this->map[$name]->newInstance(); 
    else 
      throw new Delegate_UnknownFactoryTypeName($name);
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
    return $this->new_instance_of($method, $args); 
  }
///     </body>
///   </method>

///   </protocol>
}
/// </class>

/// <class name="Delegate.ListenerDelegator">
class Delegate_ListenerDelegator {
  
  protected $listeners = array();
  protected $type;
  
///   <protocol name="creating">
  
///   <method name="__construct">
///     <args>
///       <arg name="type" default="null" />
///     </args>
///     <body>
  public function __construct($type = null) {
    $this->type = Core_Types::real_class_name_for($type);
  }
///     </body>
///   </method>
  
///   </protocol>  

///   <protocol name="configuring">
  
///   <method name="append" returns="Delegate.ListenerDelegator">
///     <args>
///       <arg name="listener" type="object" />
///     </args>
///     <body>
  public function append($listener) {
    if (!is_object($listener) || ($this->type && !($listener instanceof $this->type)))
      throw new Core_InvalidArgumentTypeException('listener', $listener);
      
    $this->listeners[] = array($listener, new ReflectionObject($listener));  
    return $this;
  }
///     </body>
///   </method>
  
///   </protocol>
  
///   <protocol name="calling" interface="Core.CallInterface">

///   <method name="__call" returns="Delegate.ListenerDelegator">
///     <args>
///       <arg name="method" type="string" />
///       <arg name="args" />
///     </args>
///     <body>
  public function __call($method, $args) {
    foreach ($this->listeners as $listener)
      if ($listener[1]->hasMethod($method)) 
        $listener[1]->getMethod($method)->invokeArgs($listener[0], $args);

    return $this;
  }
///     </body>
///   </method>

///   </protocol>
}
/// </class>

/// </module>
