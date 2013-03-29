<?php
/// <module name="Log" version="0.1.1" maintainer="timokhin@techart.ru">

Core::load('IO.FS');

/// <class name="Log" stereotype="module">
///   <implements interface="Core.ModuleInterface" />
///   <depends supplier="IO.FS" stereotype="uses" />
///   <depends supplier="Log.Dispatcher" stereotype="creates" />
///   <depends supplier="Log.Handler" stereotype="creates" />
///   <depends supplier="Log.UnknownHandlerException" stereotype="throws" />
///   <depends supplier="Log.BadMappingException" stereotype="throws" />
class Log implements Core_ModuleInterface {

///   <constants>
  const VERSION = '0.1.1';
///   </constants>


  static $handlers = array(
    'stream' => 'Log.StreamHandler',
    'file'   => 'Log.FileHandler',
    'syslog' => 'Log.SyslogHandler',
    'firephp' => 'Log.FirePHP.Handler');

  static $default;

///   <protocol name="building">

///   <method name="Dispatcher" scope="class" returns="Log.Dispatcher">
///     <body>
  static public function Dispatcher() { return new Log_Dispatcher(); }
///     </body>
///   </method>

///   <method name="make_handler" returns="Log.Handler" scope="class">
///     <args>
///       <arg name="name" type="string" />
///       <arg name="args" type="array" />
///     </args>
///     <body>
  static public function make_handler($name, array $args) {
    if (!isset(self::$handlers[$name])) throw new Log_UnknownHandlerException($name);
    return Core::amake(self::$handlers[$name], $args);
  }
///     </body>
///   </method>

///   <method name="logger" returns="Log.Dispatcher" scope="class">
///     <body>
  static public function logger() {
    if (!self::$default) self::$default = self::Dispatcher();
    return self::$default;
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="configuring">

///   <method name="name" scope="class">
///     <args>
///       <arg name="name" type="string|array" />
///       <arg name="class" type="string" default="" />
///     </args>
///     <body>
  static public function map($name, $class = '') {
    switch (true) {
      case is_array($name):
        self::$handlers = array_merge(self::$handlers, $name);
        break;
      case is_string($class) && is_string($name) && $class:
        self::$handlers[$name] = $class;
        break;
      default:
        throw new Log_BadMappingException($name, $clas);
    }
  }
///     </body>
///   </method>

///   </protocol>
}
/// </class>


/// <class name="Log.Exception" extends="Core.Exception" stereotype="exception">
class Log_Exception extends Core_Exception {}
/// </class>


/// <class name="Log.UnknownHandlerException" extends="Log.Exception" stereotype="exception">
class Log_UnknownHandlerException extends Log_Exception {
  protected $name;
///   <protocol name="creating">

///   <method name="__construct">
///     <args>
///       <arg name="name" type="string" />
///     </args>
///     <body>
  public function __construct($name) {
    $this->name = $name;
    parent::__construct("Unknown handler: $name");
  }
///     </body>
///   </method>

///   </protocol>
}
/// </class>


/// <class name="Log.BadMappingException" extends="Log.Exception" stereotype="exception">
class Log_BadMappingException extends Log_Exception {
  protected $name;
  protected $class;

///   <protocol name="creating">

///   <method name="__construct">
///     <args>
///       <arg name="name" type="string" />
///       <arg name="class" type="string" />
///     </args>
///     <body>
  public function __construct($name, $class) {
    $this->name = $name;
    $this->class = $class;
    parent::__construct("Bad mapping: $name => $class");
  }
////    </body>
///   </method>
///   </protocol>
}
/// </class>

/// <class name="Log.Level" stereotype="abstract">
abstract class Log_Level {

///   <constants>
  const CRITICAL = 50;
  const ERROR    = 40;
  const WARNING  = 30;
  const INFO     = 20;
  const DEBUG    = 10;
  const NOTSET   = 0;
///   </constants>

  static private $names = array(
    self::DEBUG    => 'D',
    self::INFO     => 'I',
    self::WARNING  => 'W',
    self::ERROR    => 'E',
    self::CRITICAL => 'C');

///   <protocol name="supporting">

///   <method name="normalize" returns="int" scope="class">
///     <args>
///       <arg name="level" type="int" />
///     </args>
///     <body>
  static public function normalize($level) {
    foreach (self::$names as $k => $v) if ($level <= $k) return $k;
    return 0;
  }
///     </body>
///   </method>

///   <method name="as_string" returns="string" scope="class">
///     <args>
///       <arg name="level" type="int" />
///     </args>
///     <body>
  static public function as_string($level) {
    return isset(self::$names[$l = self::normalize($level)]) ? self::$names[$l] : '?';
  }
///     </body>
///   </method>

///   </protocol>
}
/// </class>

/// <class name="Log.HandlerBuilder">
///   <implements interface="Core.CallInterface" />
class Log_HandlerBuilder implements Core_CallInterface {
  protected $handler;
  protected $dispatcher;

///   <protocol name="creating">
///   <method name="__construct">
///     <args>
///       <arg name="dispatcher" type="Log.Dispatcher" />
///       <arg name="handler"    type="Log.Handler" />
///     </args>
///     <body>
  public function __construct(Log_Dispatcher $dispatcher, Log_Handler $handler) {
    $this->handler = $handler;
    $this->dispatcher = $dispatcher;
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
    call_user_func_array(array($this->handler, $method), $args);
    return $this;
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="supporting">

///   <method name="__get" returns="mixed">
///     <args>
///       <arg name="property" type="string" />
///     </args>
///     <body>
  public function __get($property) {
    if ($property === 'end') return $this->dispatcher;
    else return null;
  }
///     </body>
///   </method>

///   </protocol>
}
/// </class>
/// <aggregation>
///   <source class="Log.HandlerBuilder" stereotype="builder" multiplicity="1" />
///   <target class="Log.Dispatcher" stereotype="parent" multiplicity="1" />
/// </aggregation>
/// <aggregation>
///   <source class="Log.HandlerBuilder" stereotype="builder" multiplicity="1" />
///   <target class="Log.Handler" stereotype="target" multiplicity="1" />
/// </aggregation>

/// <class name="Log.Context">
///   <implements interface="Core.PropertyAccessInterface" />
///   <depends supplier="Log.Level" stereotype="uses" />
class Log_Context implements Core_PropertyAccessInterface, Core_EqualityInterface {

  protected $parent;
  protected $values = array();

///   <protocol name="creating">

///   <method name="__construct">
///     <args>
///       <arg name="values" type="array" />
///       <arg name="parent" type="null" />
///     </args>
///     <body>
  public function __construct(array $values, $parent = null) {
    $this->with($values);
    if ($parent) $this->parent($parent);
  }
///     </body>
///   </method>

///   </protocol>


///   <protocol name="configuring">

///   <method name="parent" returns="Log.Context">
///     <args>
///       <arg name="parent" type="Log.Context" />
///     </args>
///     <body>
  public function parent(Log_Context $parent) {
    $this->parent = $parent;
    return $this;
  }
///     </body>
///   </method>

///   <method name="with" returns="Log.Context">
///     <args>
///       <arg name="values" type="array" />
///     </args>
///     <body>
  public function with(array $values) {
    $this->values = array_merge($this->values, $values);
    return $this;
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="building">

///   <method name="context" returns="Log.Context">
///     <args>
///       <arg name="values" type="array" />
///     </args>
///     <body>
  public function context(array $values) { return new Log_Context($values, $this); }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="performing">

///   <method name="emit" returns="Log.Context">
///     <args>
///       <arg name="message" type="object" />
///     </args>
///     <body>
  protected function emit($message) {
    foreach ($this->values as $k => $v) $message->$k = $v;
    return $this->parent ? $this->parent->emit($message) : $this;
  }
///     </body>
///   </method>

///   <method name="debug" returns="Log.Context">
///     <body>
  public function debug() {
    return $this->emit(Core::object(array(
      'time'  => time(),
      'body'  => func_num_args() > 1 ? func_get_args() : func_get_arg(0),
      'level' => Log_Level::DEBUG)));
  }
///     </body>
///   </method>

///   <method name="info" returns="Log.Context">
///     <body>
  public function info() {
    return $this->emit(Core::object(array(
      'time'  => time(),
      'body'  => func_num_args() > 1 ? func_get_args() : func_get_arg(0),
      'level' => Log_Level::INFO)));
  }
///     </body>
///   </method>

///   <method name="warning" returns="Log.Context">
///     <body>
  public function warning() {
    return $this->emit(Core::object(array(
      'time'  => time(),
      'body'  => func_num_args() > 1 ? func_get_args() : func_get_arg(0),
      'level' => Log_Level::WARNING)));
  }
///     </body>
///   </method>

///   <method name="error" returns="Log.Context">
///     <body>
  public function error() {
    return $this->emit(Core::object(array(
      'time'  => time(),
      'body'  => func_num_args() > 1 ? func_get_args() : func_get_arg(0),
      'level' => Log_Level::ERROR)));
  }
///     </body>
///   </method>

///   <method name="critical" returns="Log.Context">
///     <body>
  public function critical() {
    return $this->emit(Core::object(array(
      'time'  => time(),
      'body'  => func_num_args() > 1 ? func_get_args() : func_get_arg(0),
      'level' => Log_Level::CRITICAL)));
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="supporting">

///   <method name="get_dispatcher" returns="Log.Dispatcher" access="protected">
///     <body>
  protected function get_dispatcher() {
    return ($this instanceof Log_Dispatcher) ? $this : $this->parent->get_dispatcher();
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
      case 'parent': case 'values':
        return $this->$property;
      case 'dispatcher':
        return $this->get_dispatcher();
      default:
        throw new Core_MissingPropertyException($property);
    }
  }
///     </body>
///   </method>

///   <method name="__set" returns="Service.Yandex.Direct.Manager.Application">
///     <args>
///       <arg name="property" type="string" />
///       <arg name="value" />
///     </args>
///     <body>
  public function __set($property, $value) {
    throw new Core_ReadOnlyObjectException($this);
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
      case 'parent': case 'values':
        return isset($this->$property);
      case 'dispatcher':
        return true;
      default:
        return false;
    }
  }
///     </body>
///   </method>

///   <method name="property" type="string">
///     <args>
///     </args>
///     <body>
  public function __unset($property) {
    throw new Core_ReadOnlyObjectException($this);
  }
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
    return ($to instanceof self) &&
      Core::equals($this->values, $to->values) &&
      Core::equals($this->parent, $to->parent);
  }
///     </body>
///   </method>
///</protocol>


}
/// </class>
/// <aggregation>
///   <source class="Log.Context" stereotype="context" multiplicity="1" />
///   <target class="Log.Context" stereotype="parent" multiplicity="1" />
/// </aggregation>

/// <class name="Log.Dispatcher" extends="Log.Context">
///   <implements interface="Core.CallInterface" />
///   <depends supplier="Log.HandlerBuilder" stereotype="creates" />
class Log_Dispatcher
  extends Log_Context
  implements Core_CallInterface {

  protected $handlers = array();

///   <protocol name="creating">

///   <method name="__construct">
///     <body>
  public function __construct() { parent::__construct(array()); }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="configuring">

///   <method name="handler" returns="Log.Dispatcher">
///     <args>

///     </args>
///     <body>
  public function handler(Log_Handler $handler) {
    $this->handlers[] = $handler;
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
    if (substr($method, 0, 3) === 'to_') {
      $h = Log::make_handler(substr($method, 3), $args);
      $h->init();
      $this->handler($h);
      return new Log_HandlerBuilder($this, $h);
    } else
      throw new Core_MissingMethodException($method);
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="performing">

///   <method name="emit" returns="Log.Dispatcher">
///     <args>
///       <arg name="message" type="object" />
///     </args>
///     <body>
  protected function emit($message) {
    parent::emit($message);
    foreach ($this->handlers as $h) $h->emit_if_acceptable($message);
    return $this;
  }
///     </body>
///   </method>

///   <method name="init" return="Log.Dispatcher">
///     <body>
  public function init() {
    foreach ($this->handlers as $h) $h->init();
    return $this;
  }
///     </body>
///   </method>

///   <method name="close">
///     <body>
  public function close() { foreach ($this->handlers as $h) $h->close(); }
///     </body>
///   </method>

///   </protocol>
}
/// </class>
/// <aggregation>
///   <source class="Log.Dispatcher" stereotype="dispatcher" multiplicity="1" />
///   <target class="Log.Handler" stereotype="handler" multiplicity="N" />
/// </aggregation>


/// <class name="Log.Handler" stereotype="abstract">
///   <depends supplier="Log.Level" stereotype="uses" />
///   <implements interface="Core.PropertyAccessInterface" />
abstract class Log_Handler implements Core_PropertyAccessInterface {

  protected $filter = array();

  protected $format = '{time}:{level}:{body}';
  protected $time_format = '%Y-%m-%d %H:%M:%S';

///   <protocol name="creating">

///   <method name="__construct">
///     <body>
  public function __construct() {

  }
///     </body>
///   </method>

///   </protocol>


///   <protocol name="configuring">

///   <method name="where" returns="Log.Handler">
///     <args>
///       <arg name="attr" type="string" />
///       <arg name="op"   type="string" />
///       <arg name="value" />
///     </args>
///     <body>
  public function where($attr, $op, $value) {
    $this->filter[] = array($attr, $op, $value);
    return $this;
  }
///     </body>
///   </method>

///   <method name="format" returns="Log.Handler">
///     <args>
///       <arg name="format" type="string" />
///     </args>
///     <body>
  public function format($format) {
    $this->format = $format;
    return $this;
  }
///     </body>
///   </method>

///   <method name="time_format">
///     <args>
///       <arg name="format" type="string" />
///     </args>
///     <body>
  public function time_format($format) {
    $this->time_format = $format;
    return $this;
  }
///     </body>
///   </method>


///   </protocol>


///   <protocol name="performing">

///   <method name="emit_if_acceptable" returns="Log.Handler">
///     <args>
///       <arg name="message" />
///     </args>
///     <body>
  public function emit_if_acceptable($message) {
    if ($this->is_acceptable($message)) $this->emit($message);
    return $this;
  }
///     </body>
///   </method>

///   <method name="emit" returns="Log.Handler" stereotype="abstract">
///     <args>
///       <arg name="message" type="object" />
///     </args>
///     <body>
  abstract public function emit($message);
///     </body>
///   </method>

///   <method name="init" returns="Log.Handler">
///     <body>
  public function init() { return $this; }
///     </body>
///   </method>

///   <method name="close">
///     <body>
  public function close() {}
///     </body>
///   </method>


///   </protocol>

///   <protocol name="supporting">

///   <method name="format_message" returns="string" access="protected">
///     <args>
///       <arg name="message" type="object" />
///     </args>
///     <body>
  protected function format_message($message) {

    $s = array();
    $r = array();

    foreach ($message as $k => $v) {
      switch ($k) {
        case 'time':
          $s[] = '{time}';
          $r[] = $this->format_time($v);
          break;
        case 'body':
          $s[] = '{body}';
          $r[] = $this->format_body($v);
          break;
        case 'level':
          $s[] = '{level}';
          $r[] = Log_Level::as_string((int) $v);
          break;
        default:
          $s[] = "{{$k}}";
          $r[] = (string) $v;
          break;
      }
    }
    return str_replace($s, $r, $this->format);
  }
///     </body>
///   </method>

///   <method name="format_body" returns="string" access="protected">
///     <args>
///       <arg name="body" />
///     </args>
///     <body>
  protected function format_body($body) {
    switch (true) {
      case is_array($body) && Core_Strings::contains($body[0], '%'):
        return vsprintf($body[0], array_slice($body, 1));
      case is_object($body) && !method_exists($body, '__toString'):
      case is_array($body):
        return var_export($body, true);
      default:
        return (string) $body;
    }
  }
///     </body>
///   </method>

///   <method name="format_time" returns="string" access="protected">
///     <args>
///       <arg name="ts" type="int" />
///     </args>
///     <body>
  protected function format_time($ts) { return strftime($this->time_format, $ts); }
///     </body>
///   </method>

///   <method name="is_acceptable" returns="boolean" access="protected">
///     <args>
///       <arg name="object" />
///     </args>
///     <body>
  protected function is_acceptable($object) {
    $passed = 0;
    foreach ($this->filter as $c) {
      list($attr, $op, $val) = $c;
      if (!isset($object->$attr)) return false;
      switch ($op) {
        case '=':
          if ($object->$attr == $val) $passed++;
          break;
        case '<':
          if ($object->$attr < $val)  $passed++;
          break;
        case '>':
          if ($object->$attr > $val)  $passed++;
          break;
        case '<=':
          if ($object->$attr <= $val) $passed++;
          break;
        case '>=':
          if ($object->$attr >= $val) $passed++;
          break;
        case '~':
          foreach ((array) $val as $v)
            if ($r = preg_match($v, (string) $object->$attr)) break;
          if ($r) $passed++;
          break;
        case 'in':
          if (array_search($object->$attr, $val) !== false) $passed++;
          break;
        case '!in':
          if (array_search($object->$attr, $val) === false) $passed++;
          break;
      }
    }
    return ($passed == count($this->filter));
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
      case 'format':
      case 'time_format':
      case 'filter':
        return $this->$property;
      default:
        throw new Core_MissingPropertyException($property);
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
    switch ($property) {
      case 'format':
      case 'time_format':
        return $this->$property($value);
      case 'filter':
        throw new Core_ReadOnlyPropertyException($property);
      default:
        throw new Core_MissingPropertyException($property);
    }
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
      case 'format': case 'time_format': case 'filter':
        return isset($this->$property);
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
    switch ($property) {
      case 'format':
      case 'time_format':
        return $this->$property(null);
      case 'filter':
        throw new Core_ReadOnlyPropertyException($property);
      default:
        throw new Core_MissingPropertyException($property);
    }
  }
///     </body>
///   </method>

///   </protocol>
}
/// </class>


/// <class name="Log.StreamHandler" extends="Log.Handler">
class Log_StreamHandler extends Log_Handler {

  protected $stream;

///   <protocol name="creating">

///   <method name="__construct">
///     <args>
///       <arg name="stream" type="IO.Stream.AbstractStream" />
///     </args>
///     <body>
  public function __construct(IO_Stream_AbstractStream $stream) {
    $this->stream = $stream;
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="performing">

///   <method name="emit" returns="Log.StreamHandler">
///     <args>
///       <arg name="message" />
///     </args>
///     <body>
  public function emit($message) {
    $this->stream->write($this->format_message($message)."\n");
    return $this;
  }
///     </body>
///   </method>

///   </protocol>
}
/// </class>
/// <aggregation>
///   <source class="Log.StreamHandler" stereotype="handler" multiplicity="1" />
///   <target class="IO.Stream.AbstractStream" stereotype="stream" multiplicity="1" />
/// </aggregation>

/// <class name="Log.FileHandler" extends="Log.Handler">
class Log_FileHandler extends Log_Handler {

  protected $path;
  protected $stream;

///   <protocol name="creating">

///   <method name="__construct">
///     <args>
///       <arg name="path" type="string" />
///     </args>
///     <body>
  public function __construct($path) {
    $this->path = $path;
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="performing">

///   <method name="init" returns="Log.FileHandler">
///     <body>
  public function init() {
    if(!IO_FS::exists($dir = IO_FS::File($this->path)->dir_name))
      IO_FS::mkdir($dir, null, true);
    $this->stream = IO_FS::FileStream($this->path, 'a');
    return $this;
  }
///     </body>
///   </method>

///   <method name="close">
///     <body>
  public function close() { if ($this->stream) $this->stream->close(); }
///     </body>
///   </method>

///   <method name="emit" returns="Log.StreamHandler">
///     <args>
///       <arg name="message" />
///     </args>
///     <body>
  public function emit($message) {
    if ($this->stream)
      $this->stream->write($this->format_message($message)."\n");
    return $this;
  }
///     </body>
///   </method>


///   </protocol>
}
/// </class>
/// <composition>
///   <source class="Log.FileHandler" stereotype="handler" multiplicity="1" />
///   <target class="Io.FS.FileStream" streotype="stream" multiplicity="1" />
/// </composition>

/// <class name="Log.SyslogHandler" extends="Log.Handler">
///   <implements interface="Core.PropertyAccessInterface" />
class Log_SyslogHandler extends Log_Handler implements Core_PropertyAccessInterface {

  protected $id;
  protected $options;
  protected $facility;
  protected $is_opened = false;

///   <protocol name="creating">

///   <method name="__construct">
///     <args>
///       <arg name="id" type="string" />
///       <arg name="options" type="int" default="LOG_PID" />
///       <arg name="facility" type="int" default="LOG_USER" />
///     </args>
///     <body>
  public function __construct($id = false, $options = LOG_PID, $facility = LOG_USER) {
    $this->id = $id;
    $this->options = $options;
    $this->facility = $facility;
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="configuring">

///   <method name="identified_as" returns="Log.SyslogHandler">
///     <args>
///       <arg name="id" type="string" />
///     </args>
///     <body>
  public function identified_as($id) {
    $this->id = $id;
    return $this;
  }
///     </body>
///   </method>

///   <method name="facility" returns="Log.SyslogHandler">
///     <args>
///       <arg name="facility" type="int" />
///     </args>
///     <body>
  public function facility($facility) {
    $this->facility = $facility;
    return $this;
  }
///     </body>
///   </method>

///   <method name="options" returns="Log.SyslogHandler">
///     <args>
///       <arg name="options" type="int" />
///     </args>
///     <body>
  public function options($options) {
    $this->options = $options;
    return $this;
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="performing">

///   <method name="init" returns="Log.FileHandler">
///     <body>
  public function init() {
    $this->is_opened = openlog($this->id, $this->options, $this->facility);
    return $this;
  }
///     </body>
///   </method>

///   <method name="close">
///     <body>
  public function close() { closelog(); }
///     </body>
///   </method>

///   <method name="emit" returns="Log.StreamHandler">
///     <args>
///       <arg name="message" />
///     </args>
///     <body>
  public function emit($message) {
    static $priorities = array(
      Log_Level::DEBUG    => LOG_DEBUG,
      Log_Level::INFO     => LOG_NOTICE,
      Log_Level::WARNING  => LOG_WARNING,
      Log_Level::ERROR    => LOG_ERR,
      Log_Level::CRITICAL => LOG_CRIT );

    if (isset($priorities[$l = Log_Level::normalize($message->level)]))
      syslog($priorities[$l], $this->format_body($message->body));
    return $this;
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
      case 'facility':
      case 'options':
      case 'id':
        return $this->$property;
      default:
        throw new Core_ReadOnlyObjectException($this);
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
    throw new Core_ReadOnlyObjectException($this);
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
      case 'facility':
      case 'options':
      case 'id':
        return isset($this->$property);
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
    throw new Core_ReadOnlyObjectException($this);
  }
///     </body>
///   </method>

///   </protocol>
}
/// </class>

/// </module>
