<?php
/// <module name="CLI.GetOpt" version="0.3.0" maintainer="timokhin@techart.ru">

/// <class name="CLI.GetOpt" stereotype="module">
///   <depends supplier="CLI.GetOpt.Parser" stereotype="creates" />
class CLI_GetOpt implements Core_ModuleInterface {

///   <constants>
  const VERSION = '0.3.0';
  const USAGE_FORMAT = "%6s%s %-20s  %s\n";

  const STRING = 0;
  const BOOL   = 1;
  const INT    = 2;
  const FLOAT  = 3;
///   </constants>

///   <protocol name="building">

///   <method name="Parser" returns="CLI.GetOpt.Parser" scope="class">
///     <body>
  static public function Parser() { return new CLI_GetOpt_Parser(); }
///     </body>
///   </method>

///   </protocol>
}
/// </class>


/// <class name="CLI.GetOpt.Exception" extends="Core.Exception" stereotype="exception">
class CLI_GetOpt_Exception extends Core_Exception {}
/// </class>


/// <class name="CLI.GetOpt.UnknownOptionException" extends="CLI.GetOpt.Exception">
class CLI_GetOpt_UnknownOptionException extends CLI_GetOpt_Exception {
  protected $name;

///   <protocol name="creating">

///   <method name="__construct">
///     <args>
///       <arg name="name" type="string" />
///     </args>
///     <body>
  public function __construct($name) {
    $this->name = $name;
    parent::__construct("Unknown option: $name");
  }
///     </body>
///   </method>

///   </protocol>
}
/// </class>


/// <class name="CLI.GetOpt.Parser">
///   <implements interface="IteratorAggregate" />
///   <depends supplier="CLI.GetOpt.UnknownOptionException" stereotype="throws" />
///   <depends supplier="CLI.GetOpt.UnknownTypeException" stereotype="throws" />
class CLI_GetOpt_Parser implements IteratorAggregate {

  public $script;
  public $brief = '';

  protected $options;


///   <protocol name="configuring">

///   <method name="string_option" returns="CLI.GetOpt.Parser">
///     <args>
///       <arg name="name" type="string" />
///       <arg name="short" type="string" />
///       <arg name="long" type="string" />
///       <arg name="comment" type="string" />
///     </args>
///     <body>
  public function string_option($name, $short, $long, $comment) {
    return $this->option(CLI_GetOpt::STRING, $name, $short, $long, $comment);
  }
///     </body>
///   </method>

///   <method name="int_option" returns="CLI.GetOpt.Parser">
///     <args>
///       <arg name="name" type="string" />
///       <arg name="short" type="string" />
///       <arg name="long" type="string" />
///       <arg name="comment" type="string" />
///     </args>
///     <body>
  public function int_option($name, $short, $long, $comment) {
    return $this->option(CLI_GetOpt::INT, $name, $short, $long, $comment);
  }
///     </body>
///   </method>

///   <method name="float_option" returns="CLI.GetOpt.Parser">
///     <args>
///       <arg name="name" type="string" />
///       <arg name="short" type="string" />
///       <arg name="long" type="string" />
///       <arg name="comment" type="string" />
///     </args>
///     <body>
  public function float_option($name, $short, $long, $comment) {
    return $this->option(CLI_GetOpt::FLOAT, $name, $short, $long, $comment);
  }
///     </body>
///   </method>


///   <method name="boolean_option" returns="CLI.GetOpt.Parser">
///     <args>
///       <arg name="name" type="string" />
///       <arg name="short" type="string" />
///       <arg name="long" type="string" />
///       <arg name="comment" type="string" />
///       <arg name="value" type="boolean" default="true" />
///     </args>
///     <body>
  public function boolean_option($name, $short, $long, $comment, $value = true) {
    return $this->option(CLI_GetOpt::BOOL, $name, $short, $long, $comment, (boolean) $value);
  }
///     </body>
///   </method>

///   <method name="brief" returns="CLI.GetOpt.Parser">
///     <args>
///       <arg name="text" type="string" />
///     </args>
///     <body>
  public function brief($text) {
    $this->brief = $text;
    return $this;
  }
///     </body>
///   </method>

///   </protocol>
  
///   <protocol name="performing">

///   <method name="parse" returns="object">
///     <args>
///     </args>
///     <body>
  public function parse(array &$argv, $config = null) {
    if ($config === null) $config = Core::object();

    $this->script = array_shift($argv);

    while (count($argv) > 0) {
      $arg = $argv[0];
      if ($parsed = $this->parse_option($arg)) {
        if ($option = $this->lookup_option($parsed[0]))
          $this->set_option($config, $option, $parsed[1]);
        else
          throw new CLI_GetOpt_UnknownOptionException($parsed[0]);
        array_shift($argv);
      } else break;
    }

    return $config;
  }
///     </body>
///   </method>

///   <method name="usage_text" returns="string">
///     <body>
  public function usage_text() {
    $text = "{$this->brief}\n";
    foreach ($this as $o) 
      $text .= sprintf(CLI_GetOpt::USAGE_FORMAT,
        $o->short, $o->short ? ',' : '', $o->long, $o->comment);
    return $text;
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="supporting">

///   <method name="option" returns="CLI.GetOpt.Parser" access="private">
///     <args>
///       <arg name="name" type="string" />
///       <arg name="short" type="string" />
///       <arg name="long" type="string" />
///       <arg name="comment" type="string" />
///       <arg name="value" default="null" />
///     </args>
///     <body>
  private function option($type, $name, $short, $long, $comment, $value = null) {
    $o = Core::object();
    $o->name    = $name;
    $o->short   = $short;
    $o->long    = $long;
    $o->type    = $type;
    $o->comment = $comment;
    $o->value   = $value;
    $this->options[] = $o;
    return $this;
  }
///     </body>
///   </method>

///   <method name="parse_option" returns="array|false" access="protected">
///     <args>
///       <arg name="arg" type="string" />
///     </args>
///     <body>
  protected function parse_option($arg) {
    switch (true) {
      case $m = Core_Regexps::match_with_results('{^(--[a-zA-Z][a-zA-Z0-9-]*)(?:=(.*))?$}', $arg):
        return isset($m[2]) ? array($m[1], $m[2]) : array($m[1], null);
      case $m = Core_Regexps::match_with_results('{^(-[a-zA-Z0-9])(.*)}', $arg):
        return isset($m[2]) ? array($m[1], $m[2]) : array($m[1], null);
      default:
        return false;
    }
  }
///     </body>
///   </method>

///   <method name="lookup_option" returns="object" access="protected">
///     <args>
///       <arg name="name" type="string" />
///     </args>
///     <body>
  protected function lookup_option($name) {
    foreach ($this->options as $o) 
      if ($o->short == $name || $o->long == $name) return $o;
  }
///     </body>
///   </method>

///   <method name="set_option" returns="CLI.GetOpt.Parser" access="protected">
///     <args>
///     </args>
///     <body>
  protected function set_option($config, $option, $value) {
    $path = explode('.', $option->name);
    $attr = array_pop($path); 
    if ($value == '' || is_null($value)) $value = $option->value;
    $c = $config;
    foreach ($path as $p) $c = isset($c->$p) ? $c->$p : $c->$p = Core::object();
    switch ($option->type) {
      case CLI_GetOpt::STRING: 
        $c->$attr = (string) $value;
        break;
      case CLI_GetOpt::BOOL:
        $c->$attr = (boolean) $value;
        break;
      case CLI_GetOpt::INT:
        $c->$attr = (int) $value;
        break;
      case CLI_GetOpt::FLOAT:
        $c->$attr = (float) $value;
        break;
    }
    return $this;
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="iterating" interface="IteratorAggregate">

///   <method name="getIterator" returns="ArrayIterator">
///     <body>
  public function getIterator() { return new ArrayIterator($this->options); }
///     </body>
///   </method>

///   </protocol>
}
/// </class>

/// </module>
