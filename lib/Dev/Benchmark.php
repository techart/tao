<?php
/// <module name="Dev.Benchmark" version="0.1.2" maintainer="timokhin@techart.ru">

Core::load('Object');

/// <class name="Dev.Benchmark" stereotype="module">
///   <implements interface="Core.ModuleInterface" />
///   <depends supplier="Dev.Benchmark.Timer" stereotype="creates" />
class Dev_Benchmark implements Core_ModuleInterface {

///   <constants>
const MODULE  = 'Dev.Benchmark';
const VERSION = '0.1.2';
///   </constants>

///   <protocol name="building">

///   <method name="Timer" returns="Dev.Benchmark.Timer" scope="class">
///     <body>
static public function Timer() { return new Dev_Benchmark_Timer(); }
///     </body>
///   </method>

///   <method name="start" returns="Dev.Benchmark.Timer" scope="class">
///     <body>
static public function start() { return Core::with(new Dev_Benchmark_Timer())->start(); }
///     </body>
///   </method>

///   </protocol>
}
/// </class>

/// <class name="Dev.Benchmark.Event" extends="Object.Struct">
class Dev_Benchmark_Event extends Object_Struct {
  protected $time;
  protected $note;
  protected $lap;
  protected $cumulative;
  protected $percentage;

///   <protocol name="creating">

///   <method name="__construct">
///     <args>
///       <arg name="time" />
///       <arg name="note" type="string" />
///       <arg name="lap" />
///       <arg name="cumulative" />
///       <arg name="percentage" />
///     </args>
///     <body>
  public function __construct($time, $note, $lap, $cumulative, $percentage) {
    $this->time = $time;
    $this->note = (string) $note;
    $this->lap  = $lap;
    $this->cumulative = $cumulative;
    $this->percentage = $percentage;
  }
///     </body>
///   </method>

/// </protocol>
}
/// </class>

/// <class name="Dev.Benchmark.Timer">
///   <implements interface="Core.StringifyInterface" />
///   <implements interface="Core.PropertyAccessInterface" />
class Dev_Benchmark_Timer implements
  Core_StringifyInterface,
  Core_PropertyAccessInterface {

  protected $started_at;
  protected $events = array();
  protected $stopped_at;

///   <protocol name="performing">

///   <method name="start">
///     <body>
  public function start() {
    $this->events = array();
    $this->stopped_at = null;
    $this->started_at = microtime(true);
    return $this;
  }
///     </body>
///   </method>

///   <method name="lap" returns="Dev.Benchmark.Timer">
///     <body>
  public function lap($note = '') {
    $this->events[] = array(microtime(true), (string) $note);
    return $this;
  }
///     </body>
///   </method>

///   <method name="stop" returns="Dev.Benchmark.Timer">
///     <body>
  public function stop() {
    if ($this->stopped_at === null) $this->stopped_at = microtime(true);
    $this->events[] = array($this->stopped_at, '_stop_');
    return $this;
  }
///     </body>
///   </method>

///   <method name="repeat">
///     <args>
///       <arg name="limit"   type="int" />
///       <arg name="call"    type="array" />
///       <arg name="note" type="string" default="''" />
///     </args>
///     <body>
  public function repeat($note, $times, $call, $args = array()) {
    if (!$this->started_at) $this->start();
    for ($i = 0; $i < $times; $i++)  call_user_func_array($call, $args);
    return $this->lap($note);
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="stringifying" interface="Core.StringifyInterface">

///   <method name="__toString" returns="string">
///     <body>
  public function __toString() { return $this->as_string(); }
///     </body>
///   </method>

///   <method name="as_string" returns="string">
///     <body>
  public function as_string() {
    $result = sprintf("    # NAME                             TIME    CUMULATIVE PERCENTAGE\n");

    foreach ($this->get_events() as $k => $v) {
      $result .= sprintf(
        " %4d %-28s %8.3f      %8.3f   %7.3f%%\n",
        $k, $v->note, $v->lap, $v->cumulative, $v->percentage);
    }

    return $result;
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
      case 'started_at':
      case 'stopped_at':
        return $this->$property;
      case 'total_time':
        return $this->stopped_at - $this->started_at;
      case 'events':
        return $this->get_events();
      default:
        throw new Core_MissingPropertyException($property);
    }
  }
///     </body>
///   </method>

///   <method name="__set" returns="mixed">
///     <args>
///       <arg name="property" type="string" />
///       <arg name="value"  />
///     </args>
///     <body>
  public function __set($property, $value) {
    throw $this->__isset($property) ?
      new Core_ReadOnlyPropertyException($property) :
      new Core_MissingPropertyException($property);
  }
///     </body>
///   </method>

///   <method name="__isset" returns="boolean">
///     <args>
///       <arg name="property" type="string" />
///     </args>
///     <body>
  public function __isset($property) {
  switch ($property) {
    case 'started_at':
    case 'stopped_at':
    case 'total_time':
    case 'events':
    case 'summary':
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
  public function __unset($property) {
    throw $this->__isset($property) ?
      new Core_UndestroyablePropertyException($property) :
      new Core_MissingPropertyException($property);
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="supporting">

///   <method name="get_events" returns="ArrayObject" access="private">
///     <body>
  private function get_events() {
    if (!$this->started_at) $this->start();
    if (!$this->stopped_at) $this->stop();

    $total  = $this->stopped_at - $this->started_at;

    $result = new ArrayObject();

    foreach ($this->events as $k => $v) {
      $result->append(new Dev_Benchmark_Event(
        $v[0], $v[1],
        ($lap = ($v[0] - ($k > 0 ? $this->events[$k-1][0] : $this->started_at))),
        $v[0] - $this->started_at,
        $lap/$total*100 ));
    }
    return $result;
  }
///     </body>
///   </method>

///   </protocol>
}
/// </class>

/// </module>
