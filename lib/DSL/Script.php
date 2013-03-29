<?php
/// <module name="DSL.Script" version="0.2.0" maintainer="timokhin@techart.ru">
Core::load('DSL');

/// <class name="DSL.Script" stereotype="module">
class DSL_Script implements Core_ModuleInterface {
///   <constants>
  const VERSION = '0.2.0';
///   </constants>
}
/// </class>


/// <class name="DSL.Script.Exception" extends="Core.Exception" stereotype="exception">
class DSL_Script_Exception extends Core_Exception {}
/// </class>


/// <class name="DSL.Script.Builder" extends="DSL.Builder" stereotype="abstract">
abstract class DSL_Script_Builder extends DSL_Builder {

///   <protocol name="creating">

///   <method name="__construct">
///     <args>
///       <arg name="parent" type="DSL.Script.Builder" />
///       <arg name="object" type="null" />
///     </args>
///     <body>
  public function __construct(DSL_Script_Builder $parent = null, $object = null) {
    parent::__construct($parent, Core::if_null($object, $this->make_object()));
  }
///     </body>
///   </method>

///   <method name="make_object" returns="mixed" access="protected" stereotype="abstract">
///     <body>
  abstract protected function make_object();
///     </body>
///   </method>

///   </protocol>

///   <protocol name="performing">

///   <method name="for_each" returns="DSL.Script.Builder">
///     <body>
  public function for_each_value() {
    $args = func_get_args();
    $var = array_shift($args);
    return $this->builder_for(new DSL_Script_IterationAction($var, $args));
  }
///     </body>
///   </method>

  public function for_each($var, $items, $field = '') {
    return $this->builder_for(new DSL_Script_IterationAction($var, $items, $field));
  }
///   <method name="format" returns="DSL.Script.Builder">
///     <body>
  public function format() {
    $args = func_get_args();
    $format = array_shift($args);
    $this->object->actions(new DSL_Script_FormatAction($format, $args));
    return $this;
  }
///     </body>
///   </method>

///   <method name="dump" returns="DSL.Script.Builder">
///     <args>
///       <arg name="field" type="string" />
///     </args>
///     <body>
  public function dump($field) {
    $this->object->actions(new DSL_Script_DumpAction($field));
    return $this;
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="supporting">

///   <method name="builder_for" returns="DSL.Builder">
///     <args>
///       <arg name="action" type="DSL.Script.Action" />
///     </args>
///     <body>
  protected function builder_for(DSL_Script_Action $action, $class = '') {
    return Core::make($class ? $class : Core_Types::real_class_name_for($this), $this->actions($action), $action);
  }
///     </body>
///   </method>

///   </protocol>
}
/// </class>


/// <class name="DSL.Script.Action">
///   <implements interface="Core.CallInterface" />
class DSL_Script_Action implements Core_CallInterface {

  protected $parent;
  protected $data    = array();

  private   $binds   = array();
  private   $actions = array();

///   <protocol name="performing">

///   <method name="exec" returns="mixed">
///     <args>
///       <arg name="parent" type="DSL.Script.Action" />
///     </args>
///     <body>
  public function exec(DSL_Script_Action $parent = null) {
    $this->parent = $parent;

    foreach ($this->binds as $k => $v) {
      list($obj, $attr) = explode('.', $v);
      $this->data[$k] = $attr ?
        $parent->get($obj)->$attr:
        $parent->get($obj);
    }

    $rc = $this->action();
    $this->parent = null;
    return $rc;
  }
///     </body>
///   </method>

///   <method name="action" access="protected">
///     <body>
  protected function action() { return $this->exec_actions(); }
///     </body>
///   </method>

///   <method name="set" returns="DSL.Script.Action">
///     <args>
///       <arg name="name" type="string" />
///       <arg name="value" />
///       <arg name="strict" type="boolean" default="false" />
///     </args>
///     <body>
  public function set($name, $value, $strict = false) {
    if ($strict) {
      if (array_key_exists($name, $this->data))
        $this->data[$name] = $value;
      else {
        if ($this->parent)
          $this->parent->set($name, $value, $strict);
        else
          throw new DSL_Script_Exception("unable to strict set for $name");
      }
    } else {
      $this->data[$name] = $value;
    }
    return $this;
  }
///     </body>
///   </method>

///   <method name="get" returns="mixed">
///     <args>
///       <arg name="name" type="string" />
///     </args>
///     <body>
  public function get($name) {
    return (array_key_exists($name, $this->data)) ?
      $this->data[$name] :
      ( $this->parent ? $this->parent->get($name) : null );
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="configuring">

///   <method name="bind" returns="DSL.Script.Action">
///     <body>
  public function bind($var, $src = '') {
    $this->binds[$var] = $src ? $src : $var;
    return $this;
  }
///     </body>
///   </method>

///   <method name="with" returns="DSL.Script.Action">
///     <body>
  public function with() {
    $args = func_get_args();
    $num  = count($args);
    for ($i = 0; $i < $num - 1; $i++) $this->data[$args[$i]] = $args[$i + 1];
    return $this;
  }
///     </body>
///   </method>

///   <method name="actions" returns="DSL.Script.Action">
///     <body>
  public function actions() {
    $args = func_get_args();
    foreach ($args as $a) if ($a instanceof DSL_Script_Action) $this->actions[] = $a;
    return $this;
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="supporting">

///   <method name="exec_actions" returns="mixed" access="protected">
///     <body>
  protected function exec_actions() {
    $rc = $this;
    foreach ($this->actions as $a) $rc = $a->exec($this);
    return $rc;
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="calling">

///   <method name="__call" returns="SDL.Script.Action">
///     <args>
///       <arg name="method" type="string" />
///       <arg name="args" type="array" />
///     </args>
///     <body>
  public function __call($method, $args) {
    $this->set($method, $args[0]);
    return $this;
  }
///     </body>
///   </method>

///   </protocol>
}
/// </class>


/// <class name="DSL.Script.IterationAction" extends="DSL.Script.Action">
class DSL_Script_IterationAction extends DSL_Script_Action {

  private $name;
  private $items;
  private $field;

///   <protocol name="creating">

///   <method name="__construct">
///     <args>
///       <arg name="name"  type="string" />
///       <arg name="items" type="mixed" />
///     </args>
///     <body>
  public function __construct($name, $items, $field = '') {
    $this->name  = $name;
    $this->items = $items;
    $this->field = $field;
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="performing">

///   <method name="action" access="protected" returns="mixed">
///     <body>
  protected function action() {
    foreach ($this->items as $item) {
      $this->set($this->name, $this->field ? $item->{$this->field} : $item);
      $rc = $this->exec_actions();
      if (!$rc) break;
    }
    return $rc;
  }
///     </body>
///   </method>

///   </protocol>
}
/// </class>


/// <class name="DSL.Script.FormatAction" extends="DSL.ScriptAction">
class DSL_Script_FormatAction extends DSL_Script_Action {

  private $format;
  private $fields;

///   <protocol name="creating">

///   <method name="__construct">
///     <args>
///       <arg name="format" type="string" />
///       <arg name="fields" />
///     </args>
///     <body>
  public function __construct($format, $fields) {
    $this->format = $format;
    $this->fields = $fields;
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="performing">

///   <method name="action" returns="boolean">
///     <body>?
  protected function action() {
    $parms = array($this->format);
    foreach ($this->fields as $f) {
      list($obj, $attr) = explode('.', $f, 2);
      $parms[] = $attr ? $this->get($obj)->$attr : $this->get($obj);
    }
    call_user_func_array('printf', $parms);
    return true;
  }
///     </body>
///   </method>

///   </protocol>
}
/// </class>


/// <class name="DSL.Script.DumpAction" extends="DSL.Script.Action">
class DSL_Script_DumpAction extends DSL_Script_Action {
  private $field;

///   <protocol name="creating">

  public function __construct($field) { $this->field = $field; }

///   </protocol>

///   <protocol name="performing">

///   <method name="action" access="protected">
///     <args>
///       <arg name="action" />
///     </args>
///     <body>
  protected function action() { var_dump($this->get($this->field)); }
///     </body>
///   </method>

///   </protocol>
}
/// </class>

/// </module>
