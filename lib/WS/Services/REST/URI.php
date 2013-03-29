<?php
/// <module name="WS.REST.URI" version="0.2.0" maintainer="timokhin@techart.ru">

/// <class name="WS.REST.URI" stereotype="module">
///   <depends supplier="WS.REST.URI.Template" stereotype="creates" />
class WS_Services_REST_URI implements Core_ModuleInterface {
///   <constants>
  const VERSION = '0.2.1';
///   </constants>

///   <protocol name="building">

///   <method name="Template" scope="class" returns="WS.URI.Template">
///     <args>
///       <arg name="template" type="string" />
///     </args>
///     <body>
  static public function Template($template) { return new WS_Services_REST_URI_Template($template); }
///     </body>
///   </method>

///   </protocol>
}
/// </class>


/// <class name="WS.REST.URI.MatchResults">
///   <implements interface="Core.PropertyAccessInterface" />
///   <implements interface="Core.IndexedAccessInterface" />
///   <implements interface="IteratorAggregate" />
class WS_Services_REST_URI_MatchResults
  implements Core_PropertyAccessInterface,
             Core_IndexedAccessInterface,
             IteratorAggregate {

  protected $parms;
  protected $tail;

///   <protocol name="creating">

///   <method name="__construct">
///     <args>
///       <arg name="parms" type="array" />
///       <arg name="tail"  type="string" default="''" />
///     </args>
///     <body>
  public function __construct(array $parms, $tail = '') {
    $this->parms = $parms;
    //$this->tail = ($tail == '/' ? '' : (string) $tail);
    //urls like '/test/11/' '/test/11.html' 'test/11/index.html' a the same
    $this->tail = (in_array($tail, array('/', '/index')) ? '' : (string) $tail);
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
      case 'tail':
      case 'parms':
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
  public function __set($property, $value) { throw new Core_ReadOnlyObjectException($property); }
///     </body>
///   </method>

///   <method name="__isset" returns="boolean">
///     <args>
///       <arg name="property" type="string" />
///     </args>
///     <body>
  public function __isset($property) {
    switch ($property) {
      case 'tail':
      case 'parms':
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
  public function __unset($property) { throw new Core_ReadOnlyObjectException($property); }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="iterating" interface="IteratorAggregate">

///   <method name="getIterator" returns="ArrayIterator">
///     <body>
  public function getIterator() { return new ArrayIterator($this->parms); }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="indexing" interface="Core.IndexedAccessInterface">

///   <method name="offsetGet" returns="mixed">
///     <args>
///       <arg name="index" type="string" />
///     </args>
///     <body>
  public function offsetGet($index) {
    return isset($this->parms[$index]) ? $this->parms[$index] : null;
  }
///     </body>
///   </method>

///   <method name="offsetSet" returns="mixed">
///     <args>
///       <arg name="index" type="string" />
///       <arg name="value" />
///     </args>
///     <body>
  public function offsetSet($index, $value) { throw new Core_ReadOnlyObjectException($this); }
///     </body>
///   </method>

///   <method name="offsetExists" returns="boolean">
///     <args>
///       <arg name="index" type="string" />
///     </args>
///     <body>
  public function offsetExists($index) { return isset($this->parms[$index]); }
///     </body>
///   </method>

///   <method name="offsetUnset">
///     <args>
///       <arg name="index" type="string" />
///     </args>
///     <body>
  public function offsetUnset($index) { throw new Core_ReadOnlyObjectException($this); }
///     </body>
///   </method>

///   </protocol>
}
/// </class>

/// <class name="WS.REST.URI.Template">
///   <implements interface="Core.StringifyInterface" />
///   <implements interface="Core.PropertyAccessInterface" />
///   <implements interface="Core.EqualityInterface" />
///   <depends supplier="WS.REST.URI.MatchResults" stereotype="creates" />
class WS_Services_REST_URI_Template
  implements Core_StringifyInterface,
             Core_PropertyAccessInterface,
             Core_EqualityInterface {

  protected $template;
  protected $regexp;
  protected $parms = array();

///   <protocol name="creating">

///   <method name="__construct">
///     <args>
///       <arg name="template" type="string" />
///     </args>
///     <body>
  public function __construct($template) { $this->parse($template); }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="processing">

///   <method name="match" returns="WS.URI.MatchResults">
///     <args>
///       <arg name="uri" type="string" />
///     </args>
///     <body>
  public function match($uri) {
    if (empty($uri)) $uri = '/index';
    if ($this->regexp && preg_match($this->regexp, $uri, $m)) {
      $values = array();
      foreach ($this->parms as $k => $n)
        if (isset($m[$k + 1])) $values[$n] = $m[$k + 1];
        $l = count($this->parms) + 1;
      return new WS_Services_REST_URI_MatchResults($values, ($l && isset($m[$l])) ? $m[$l] : '');
    }
    return null;
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
      case 'template':
      case 'regexp':
      case 'parms':
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
  public function __set($property, $value) { throw new Core_ReadOnlyObjectException($property); }
///     </body>
///   </method>

///   <method name="__isset" returns="boolean">
///     <args>
///       <arg name="property" type="string" />
///     </args>
///     <body>
  public function __isset($property) {
    switch ($property) {
      case 'template':
      case 'regexp':
      case 'parms':
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
  public function __unset($property) { throw new Core_ReadOnlyObjectException($property); }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="stringifying" interface="Core.StringifyInterface">

///   <method name="as_string" returns="string">
///     <body>
  public function as_string() { return $this->template; }
///     </body>
///   </method>

///   <method name="__toString" returns="string">
///     <body>
  public function __toString() { return $this->as_string(); }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="supporting">

///   <method name="parse" returns="WS.URI.Template" access="protected">
///     <args>
///       <arg name="template" type="string" />
///     </args>
///     <body>
  protected function parse($template) {
    $this->template = $template;
    if ($template === null)
      $this->regexp = '';
    elseif ($template == '')
      $this->regexp = '{(/.*)}';
    else
      $this->regexp = '{^/'.
                      preg_replace_callback(
                      '/{([a-z][a-zA-Z0-9_]*)(?::([^}]+))?}/',
                      array($this, 'parsing_callback'),
                      $this->template = $template).
                      '(/.*)?}';
    return $this;
  }
///     </body>
///   </method>

///   <method name="parsing_callback" returns="string">
///     <args>
///       <arg name="matches" type="array" />
///     </args>
///     <body>
  protected function parsing_callback($matches) {
    $this->parms[] = $matches[1];
    return isset($matches[2]) ? '('.$matches[2].')' : '([^/]+)';
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
    return $to instanceof self &&
      $this->template === $to->template &&
      $this->regexp === $to->regexp;
  }
///     </body>
///   </method>
///</protocol>
}
/// </class>

/// </module>
