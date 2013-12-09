<?php
/**
 * Log
 * 
 * @package Log
 * @version 0.1.1
 */

Core::load('IO.FS');

/**
 * @package Log
 */
class Log implements Core_ModuleInterface {

  const VERSION = '0.1.1';


  static $handlers = array(
    'stream' => 'Log.StreamHandler',
    'file'   => 'Log.FileHandler',
    'syslog' => 'Log.SyslogHandler',
    'firephp' => 'Log.FirePHP.Handler');

  static $default;


/**
 * @return Log_Dispatcher
 */
  static public function Dispatcher() { return new Log_Dispatcher(); }

/**
 * @param string $name
 * @param array $args
 * @return Log_Handler
 */
  static public function make_handler($name, array $args) {
    if (!isset(self::$handlers[$name])) throw new Log_UnknownHandlerException($name);
    return Core::amake(self::$handlers[$name], $args);
  }

/**
 * @return Log_Dispatcher
 */
  static public function logger() {
    if (!self::$default) self::$default = self::Dispatcher();
    return self::$default;
  }



/**
 * @param string|array $name
 * @param string $class
 */
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

}


/**
 * @package Log
 */
class Log_Exception extends Core_Exception {}


/**
 * @package Log
 */
class Log_UnknownHandlerException extends Log_Exception {
  protected $name;

/**
 * @param string $name
 */
  public function __construct($name) {
    $this->name = $name;
    parent::__construct("Unknown handler: $name");
  }

}


/**
 * @package Log
 */
class Log_BadMappingException extends Log_Exception {
  protected $name;
  protected $class;


/**
 * @param string $name
 * @param string $class
 */
  public function __construct($name, $class) {
    $this->name = $name;
    $this->class = $class;
    parent::__construct("Bad mapping: $name => $class");
  }
}

/**
 * @abstract
 * @package Log
 */
abstract class Log_Level {

  const CRITICAL = 50;
  const ERROR    = 40;
  const WARNING  = 30;
  const INFO     = 20;
  const DEBUG    = 10;
  const NOTSET   = 0;

  static private $names = array(
    self::DEBUG    => 'D',
    self::INFO     => 'I',
    self::WARNING  => 'W',
    self::ERROR    => 'E',
    self::CRITICAL => 'C');


/**
 * @param int $level
 * @return int
 */
  static public function normalize($level) {
    foreach (self::$names as $k => $v) if ($level <= $k) return $k;
    return 0;
  }

/**
 * @param int $level
 * @return string
 */
  static public function as_string($level) {
    return isset(self::$names[$l = self::normalize($level)]) ? self::$names[$l] : '?';
  }

}

/**
 * @package Log
 */
class Log_HandlerBuilder implements Core_CallInterface {
  protected $handler;
  protected $dispatcher;

/**
 * @param Log_Dispatcher $dispatcher
 * @param Log_Handler $handler
 */
  public function __construct(Log_Dispatcher $dispatcher, Log_Handler $handler) {
    $this->handler = $handler;
    $this->dispatcher = $dispatcher;
  }



/**
 * @param string $method
 * @param array $args
 * @return mixed
 */
  public function __call($method, $args) {
    call_user_func_array(array($this->handler, $method), $args);
    return $this;
  }



/**
 * @param string $property
 * @return mixed
 */
  public function __get($property) {
    if ($property === 'end') return $this->dispatcher;
    else return null;
  }

}

/**
 * @package Log
 */
class Log_Context implements Core_PropertyAccessInterface, Core_EqualityInterface {

  protected $parent;
  protected $values = array();


/**
 * @param array $values
 * @param null $parent
 */
  public function __construct(array $values, $parent = null) {
    $this->with($values);
    if ($parent) $this->parent($parent);
  }




/**
 * @param Log_Context $parent
 * @return Log_Context
 */
  public function parent(Log_Context $parent) {
    $this->parent = $parent;
    return $this;
  }

/**
 * @param array $values
 * @return Log_Context
 */
  public function with(array $values) {
    $this->values = array_merge($this->values, $values);
    return $this;
  }



/**
 * @param array $values
 * @return Log_Context
 */
  public function context(array $values) { return new Log_Context($values, $this); }



/**
 * @param object $message
 * @return Log_Context
 */
  protected function emit($message) {
    foreach ($this->values as $k => $v) $message->$k = $v;
    return $this->parent ? $this->parent->emit($message) : $this;
  }

/**
 * @return Log_Context
 */
  public function debug() {
    return $this->emit(Core::object(array(
      'time'  => time(),
      'body'  => func_num_args() > 1 ? func_get_args() : func_get_arg(0),
      'level' => Log_Level::DEBUG)));
  }

/**
 * @return Log_Context
 */
  public function info() {
    return $this->emit(Core::object(array(
      'time'  => time(),
      'body'  => func_num_args() > 1 ? func_get_args() : func_get_arg(0),
      'level' => Log_Level::INFO)));
  }

/**
 * @return Log_Context
 */
  public function warning() {
    return $this->emit(Core::object(array(
      'time'  => time(),
      'body'  => func_num_args() > 1 ? func_get_args() : func_get_arg(0),
      'level' => Log_Level::WARNING)));
  }

/**
 * @return Log_Context
 */
  public function error() {
    return $this->emit(Core::object(array(
      'time'  => time(),
      'body'  => func_num_args() > 1 ? func_get_args() : func_get_arg(0),
      'level' => Log_Level::ERROR)));
  }

/**
 * @return Log_Context
 */
  public function critical() {
    return $this->emit(Core::object(array(
      'time'  => time(),
      'body'  => func_num_args() > 1 ? func_get_args() : func_get_arg(0),
      'level' => Log_Level::CRITICAL)));
  }



/**
 * @return Log_Dispatcher
 */
  protected function get_dispatcher() {
    return ($this instanceof Log_Dispatcher) ? $this : $this->parent->get_dispatcher();
  }



/**
 * @param string $property
 * @return mixed
 */
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

/**
 * @param string $property
 * @param  $value
 * @return Service_Yandex_Direct_Manager_Application
 */
  public function __set($property, $value) {
    throw new Core_ReadOnlyObjectException($this);
  }

/**
 * @param string $property
 * @return boolean
 */
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

/**
 */
  public function __unset($property) {
    throw new Core_ReadOnlyObjectException($this);
  }


/**
 * @param  $to
 * @return boolean
 */
  public function equals($to) {
    return ($to instanceof self) &&
      Core::equals($this->values, $to->values) &&
      Core::equals($this->parent, $to->parent);
  }


}

/**
 * @package Log
 */
class Log_Dispatcher
  extends Log_Context
  implements Core_CallInterface {

  protected $handlers = array();


/**
 */
  public function __construct() { parent::__construct(array()); }



/**
 * @return Log_Dispatcher
 */

  public function handler(Log_Handler $handler) {
    $this->handlers[] = $handler;
    return $this;
  }



/**
 * @param string $method
 * @param array $args
 * @return mixed
 */
  public function __call($method, $args) {
    if (substr($method, 0, 3) === 'to_') {
      $h = Log::make_handler(substr($method, 3), $args);
      $h->init();
      $this->handler($h);
      return new Log_HandlerBuilder($this, $h);
    } else
      throw new Core_MissingMethodException($method);
  }



/**
 * @param object $message
 * @return Log_Dispatcher
 */
  protected function emit($message) {
    parent::emit($message);
    foreach ($this->handlers as $h) $h->emit_if_acceptable($message);
    return $this;
  }

/**
 */
  public function init() {
    foreach ($this->handlers as $h) $h->init();
    return $this;
  }

/**
 */
  public function close() { foreach ($this->handlers as $h) $h->close(); }

}


/**
 * @abstract
 * @package Log
 */
abstract class Log_Handler implements Core_PropertyAccessInterface {

  protected $filter = array();

  protected $format = '{time}:{level}:{body}';
  protected $time_format = '%Y-%m-%d %H:%M:%S';


/**
 */
  public function __construct() {

  }




/**
 * @param string $attr
 * @param string $op
 * @param  $value
 * @return Log_Handler
 */
  public function where($attr, $op, $value) {
    $this->filter[] = array($attr, $op, $value);
    return $this;
  }

/**
 * @param string $format
 * @return Log_Handler
 */
  public function format($format) {
    $this->format = $format;
    return $this;
  }

/**
 * @param string $format
 */
  public function time_format($format) {
    $this->time_format = $format;
    return $this;
  }





/**
 * @param  $message
 * @return Log_Handler
 */
  public function emit_if_acceptable($message) {
    if ($this->is_acceptable($message)) $this->emit($message);
    return $this;
  }

/**
 * @abstract
 * @param object $message
 * @return Log_Handler
 */
  abstract public function emit($message);

/**
 * @return Log_Handler
 */
  public function init() { return $this; }

/**
 */
  public function close() {}




/**
 * @param object $message
 * @return string
 */
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

/**
 * @param  $body
 * @return string
 */
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

/**
 * @param int $ts
 * @return string
 */
  protected function format_time($ts) { return strftime($this->time_format, $ts); }

/**
 * @param  $object
 * @return boolean
 */
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



/**
 * @param string $property
 * @return mixed
 */
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

/**
 * @param string $property
 * @param  $value
 * @return mixed
 */
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

/**
 * @param string $property
 * @return boolean
 */
  public function __isset($property) {
    switch ($property) {
      case 'format': case 'time_format': case 'filter':
        return isset($this->$property);
      default:
        return false;
    }
  }

/**
 * @param string $property
 */
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

}


/**
 * @package Log
 */
class Log_StreamHandler extends Log_Handler {

  protected $stream;


/**
 * @param IO_Stream_AbstractStream $stream
 */
  public function __construct(IO_Stream_AbstractStream $stream) {
    $this->stream = $stream;
  }



/**
 * @param  $message
 * @return Log_StreamHandler
 */
  public function emit($message) {
    $this->stream->write($this->format_message($message)."\n");
    return $this;
  }

}

/**
 * @package Log
 */
class Log_FileHandler extends Log_Handler {

  protected $path;
  protected $stream;


/**
 * @param string $path
 */
  public function __construct($path) {
    $this->path = $path;
  }



/**
 * @return Log_FileHandler
 */
  public function init() {
    if(!IO_FS::exists($dir = IO_FS::File($this->path)->dir_name))
      IO_FS::mkdir($dir, null, true);
    $this->stream = IO_FS::FileStream($this->path, 'a');
    return $this;
  }

/**
 */
  public function close() { if ($this->stream) $this->stream->close(); }

/**
 * @param  $message
 * @return Log_StreamHandler
 */
  public function emit($message) {
    if ($this->stream)
      $this->stream->write($this->format_message($message)."\n");
    return $this;
  }


}

/**
 * @package Log
 */
class Log_SyslogHandler extends Log_Handler implements Core_PropertyAccessInterface {

  protected $id;
  protected $options;
  protected $facility;
  protected $is_opened = false;


/**
 * @param string $id
 * @param int $options
 * @param int $facility
 */
  public function __construct($id = false, $options = LOG_PID, $facility = LOG_USER) {
    $this->id = $id;
    $this->options = $options;
    $this->facility = $facility;
  }



/**
 * @param string $id
 * @return Log_SyslogHandler
 */
  public function identified_as($id) {
    $this->id = $id;
    return $this;
  }

/**
 * @param int $facility
 * @return Log_SyslogHandler
 */
  public function facility($facility) {
    $this->facility = $facility;
    return $this;
  }

/**
 * @param int $options
 * @return Log_SyslogHandler
 */
  public function options($options) {
    $this->options = $options;
    return $this;
  }



/**
 * @return Log_FileHandler
 */
  public function init() {
    $this->is_opened = openlog($this->id, $this->options, $this->facility);
    return $this;
  }

/**
 */
  public function close() { closelog(); }

/**
 * @param  $message
 * @return Log_StreamHandler
 */
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



/**
 * @param string $property
 * @return mixed
 */
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

/**
 * @param string $property
 * @param  $value
 * @return mixed
 */
  public function __set($property, $value) {
    throw new Core_ReadOnlyObjectException($this);
  }

/**
 * @param string $property
 * @return boolean
 */
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

/**
 * @param string $property
 */
  public function __unset($property) {
    throw new Core_ReadOnlyObjectException($this);
  }

}

