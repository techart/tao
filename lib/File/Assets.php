<?php
/// <module name="File.Assets" version="2.0.0" maintainer="timokhin@techart.ru">

Core::load('IO.FS');

/// <class name="File.Assets" stereotype="module">
///   <implements interface="Core.ConfigurableModuleInterface" />
class File_Assets implements Core_ConfigurableModuleInterface {
///   <constants>
  const VERSION = '2.0.0';
///   </constants>

  static protected $options = array(
    'root'             => 'assets',
    'keep_originals'   => false,
    'dir_permissions'  => 0775,
    'file_permissions' => 0664 );

///   <protocol name="creating">

///   <method name="initialize" scope="class">
///     <args>
///       <arg name="options" type="array" default="array()" />
///     </args>
///     <body>
  static public function initialize(array $options = array()) { self::options($options); }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="configuring">
  
///   <method name="options" returns="mixed" scope="class">
///     <args>
///       <arg name="options" type="array" default="array()" />
///     </args>
///     <body>
  static public function options(array $options = array()) { 
    if (count($options)) Core_Arrays::update(self::$options, $options);
    return self::$options; 
  }
///     </body>
///   </method>

///   <method name="option" returns="mixed">
///     <args>
///       <arg name="name" type="string" />
///       <arg name="value" default="null" />
///     </args>
///     <body>
  static public function option($name, $value = null) {
    $prev = isset(self::$options[$name]) ? self::$options[$name] : null;
    if ($value !== null) self::options(array($name => $value));
    return $prev;
  }
///     </body>
///   </method>
 
///   </protocol>

///   <protocol name="building">

///   <method name="Collection" returns="File.Assets.Collection" scope="class">
///     <args>
///       <arg name="path" type="string" />
///     </args>
///     <body>
  static public function Collection($path) { return new File_Assets_Collection($path); }
///     </body>
///   </method>
  
///   </protocol>

///   <protocol name="quering">

///   <method name="full_path_for" returns="string" scope="class">
///     <args>
///       <arg name="path" type="string" />
///     </args>
///     <body>
  static public function full_path_for($path) { return self::$options['root'].'/'.$path; }
///     </body>
///   </method>
    
///   </protocol>
}
/// </class>

/// <class name="File.Assets.Collection">
///   <implements interface="Core.IndexedAccessInterface" />
///   <implements interface="IteratorAggregate" />
class File_Assets_Collection
  implements Core_PropertyAccessInterface, 
             Core_IndexedAccessInterface,
             IteratorAggregate {

  protected $path;
  
///   <protocol name="creating">

///   <method name="__construct">
///     <args>
///       <arg name="entity" type="DB.ORM.Entity" />
///     </args>
///     <body>
  public function __construct($path) { $this->path = $path; }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="processing">

///   <method name="store" returns="boolean">
///     <args>
///       <arg name="file" type="IO.FS.File" />
///       <arg name="name" type="string" />
///     </args>
///     <body>
  public function store(IO_FS_File $file, $name = '') { return $this->do_store($file, $name); }
///     </body>
///   </method>

///   <method name="remove" returns="boolean">
///     <body>
  public function remove($name) { return IO_FS::rm($this->full_path_for($name)); }
///     </body>
///   </method>

///   <method name="destroy">
///     <body>
  public function destroy() { return $this->rm_storage_dir(); }
///     </body>
///   </method>
  
///   </protocol>
  
///   <protocol name="iterating" interface="IteratorAggregate">

///   <method name="getIterator" returns="IO.FS.DirIterator">
///     <body>
  public function getIterator() { return new IO_FS_DirIterator(IO_FS::Dir($this->full_path)); }
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
      case 'path':
        return $this->$property;
      case 'full_path':
        return File_Assets::full_path_for($this->path);
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
      case 'path':
      case 'full_path':
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
      case 'path':
      case 'full_path':
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
    switch ($property) {
      case 'path':
      case 'full_path':
        throw new Core_UndestroyablePropertyException($property);
      default:
        throw new Core_MissingPropertyException($property);
    }
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="indexing" interface="Core.IndexedAccessInterface">

///   <method name="offsetGet">
///     <args>
///       <arg name="index" type="mixed" />
///     </args>
///     <body>
  public function offsetGet($index) {
    return IO_FS::exists($path = $this->full_path_for($index)) ?
      IO_FS::File($path) : 
      null;
  }
///     </body>
///   </method>
  
///   <method name="offsetSet" returns="mixed">
///     <args>
///       <arg name="index" type="string" />
///       <arg name="value" />
///     </args>
///     <body>
  public function offsetSet($index, $value) {
    if ($value instanceof IO_FS_File) {
      $this->do_store($value, $index ? $index : '');
      return $this;
    }
    throw new Core_InvalidArgumentTypeException('value', $value);  
  }
///     </body>
///   </method>
  
///   <method name="offsetExists" returns="boolean">
///     <args>
///       <arg name="index" type="string" />
///     </args>
///     <body>
  public function offsetExists($index) { return IO_FS::exists($this->full_path_for($index)); }
///     </body>
///   </method>
  
///   <method name="offsetUnset">
///     <args>
///       <arg name="index" type="string" />
///     </args>
///     <body>
  public function offsetUnset($index) { $this->remove($index); }
///     </body>
///   </method>
  
///   </protocol>
  
///   <protocol name="supporting">

///   <method name="do_store" returns="boolean" access="private">
///     <args>
///       <arg name="file" type="IO.FS.File" />
///       <arg name="name" type="string" default="''" />
///     </args>
///     <body>
  private function do_store(IO_FS_File $file, $name = '') {
    $method = File_Assets::option('keep_originals') ? 'copy_to' : 'move_to';
    return $this->make_storage_dir() && 
           ($stored = $file->$method($this->full_path_for($name ? $name : $file->name))) &&
           $stored->chmod(File_Assets::option('file_permissions'));
  }
///     </body>
///   </method>
  
///   <method name="path_for" returns="string" access="protected">
///     <args>
///       <arg name="name" type="string" />
///     </args>
///     <body>
  protected function path_for($name) { return $this->path.'/'.$name; }
///     </body>
///   </method>  
  
///   <method name="full_path_for" returns="string" access="protected">
///     <args>
///       <arg name="name" type="string" />
///     </args>
///     <body>
  public function full_path_for($name) {
    return File_Assets::full_path_for($this->path.'/'.$name);
  }
///     </body>
///   </method>

///   <method name="make_storage_dir" returns="boolean" access="private">
///     <body>
  private function make_storage_dir() {
    return IO_FS::mkdir($this->full_path, File_Assets::option('dir_permissions'), true);
  }
///     </body>
///   </method>
  
///   <method name="rm_storage_dir" returns="boolean" access="private">
///     <body>
  private function rm_storage_dir() {
    $rc = true;
    foreach ($this as $file) $rc = $rc && IO_FS::rm($file->path);
    return $rc && IO_FS::rm($this->full_path);
  }
///     </body>
///   </method>
  
///   </protocol>
}
/// </class>

/// </module>
