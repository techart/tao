<?php
/// <module name="Config.File" maintainer="timokhin@techart.ru" version="1.0.0">

Core::load('Config', 'IO.FS');

/// <class name="Config.File" stereotype="module">
///   <implements interface="Core.ModuleInterface" />
///   <depends supplier="Config.File.IniParser" stereotype="uses" />
///   <depends supplier="Config.File.PHPParser" stereotype="uses" />
///   <depends supplier="Config.File.UnknownFormatException" stereotype="throws" />
class Config_File implements Core_ModuleInterface {

///   <constants>
  const MODULE  = 'Config';
  const VERSION = '1.0.0';
///   </constants>

  static protected $parsers = array(
    'php' => 'PHPParser', 'ini' => 'IniParser' );

///   <protocol name="building">

///   <method name="load" returns="Config.Tree">
///     <args>
///       <arg name="path" type="string" />
///       <arg name="defaults" type="Config.Tree" default="null" />
///     </args>
///     <body>
  static public function load($path, Config_Tree $defaults = null) {
    $extension = IO_FS::Path($path)->extension;
    if (isset(self::$parsers[$extension]))  {
      $class_name = 'Config_File_'.self::$parsers[$extension];
      $parser = new $class_name($path);
      return $parser->parse($defaults);
    } else throw new Config_File_UnknownFormatException($extension);
  }
///     </body>
///   </method>

///   </protocol>
}
/// </class>


/// <class name="Config.File.Exception" extends="Config.Exception" stereotype="exception">
class Config_File_Exception extends Config_Exception {}
/// </class>


/// <class name="Config.File.UnknownFormatException" extends="Config.File.Exception" stereotype="exception">
class Config_File_UnknownFormatException extends Config_File_Exception {
  protected $extension;

///   <protocol name="creating">

///   <method name="__construct">
///     <args>
///       <arg name="extension" type="string" />
///     </args>
///     <body>
  public function __construct($extension) { 
    $this->extension = (string) $extension;
    parent::__construct("Unknown config file format: $this->extension"); 
  }
///     </body>
///   </method>
 
///   </protocol>  
}
/// </class>


/// <class name="Config.File.FileNotFoundException" extends="Config.File.Exception" stereotype="exception">
class Config_File_FileNotFoundException extends Config_File_Exception {
  protected $path;

///   <protocol name="creating">

///   <method name="__construct">
///     <args>
///       <arg name="path" type="string" />
///     </args>
///     <body>
  public function __construct($path) { 
    $this->path = (string) $path;
    parent::__construct("Config file not found: $this->path");  
  }
///     </body>
///   </method>     

///   </protocol>
}
/// </class>


/// <class name="Config.File.BadConfigFileException" extends="Config.File.Exception" stereotype="exception">
class Config_File_BadConfigFileException extends Config_File_Exception {
  protected $path;

///   <protocol name="creating">

///   <method name="__construct">
///     <args>
///       <arg name="path" type="string" />
///     </args>
///     <body>
  public function __construct($path) { 
    $this->path = $path;
    parent::__construct("Bad config file format: $this->path"); 
  }
///     </body>
///   </method>

///   </protocol>
}
/// </class>


/// <class name="Config.File.AbstractParser" extends ="Config.AbstractParser">
abstract class Config_File_AbstractParser extends Config_AbstractParser {
  protected $path;

///   <protocol name="creating">

///   <method name="__construct">
///     <args>
///       <arg name="path" type="string" />
///     </args>
///     <body>
  public function __construct($path) { $this->path = (string) $path; }
///     </body>
///   </method>

///   </protocol>

}
/// </class>


/// <class name="Config.File.PHPParser" extends="Config.File.AbstractParser">
class Config_File_PHPParser extends Config_File_AbstractParser {

///   <protocol name="parsing">

///   <method name="parse" returns="Config.Tree">
///     <args>
///       <arg name="defaults" type="Config.Tree" default="null" />
///     </args>
///     <body>
  public function parse(Config_Tree $defaults = null) {
    $config = $defaults ? $defaults : Config::Tree();
    include($this->path);
    if ($config instanceof Config_Tree) return $config;
    else throw new Config_File_BadConfigFileException($this->path);
  }
///     </body>
///   </method>

///   </protocol>
}
/// </class>


/// <class name="Config.File.IniParser" extends="Config.File.AbstractParser">
class Config_File_IniParser extends Config_File_AbstractParser {

///   <protocol name="parsing">

///   <method name="parse" returns="Config.Tree">
///     <args>
///       <arg name="defaults" type="Config.Tree" default="null" />
///     </args>
///     <body>
  public function parse(Config_Tree $defaults = null) {
    $config = $defaults ? $defaults : Config::Tree();
    foreach (parse_ini_file($this->path, true) as $section => $items) {
      $branch = $config->branch($section);
      foreach ($items as $k => $v) $branch->$k = $v;
    }
    return $config;
  }
///     </body>
///   </method>

///   </protocol>
}
/// </class>

/// </module>
