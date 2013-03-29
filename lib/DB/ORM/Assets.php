<?php
/// <module name="DB.ORM.Assets" version="0.2.0" maintainer="timokhin@techart.ru">

Core::load('IO.FS');

/// <class name="DB.ORM.Assets" stereotype="module">
///   <implements interface="Core.ConfigurableModuleInterface" />
class DB_ORM_Assets implements Core_ConfigurableModuleInterface {

///   <constants>
  const VERSION = '0.2.0';
///   </constants>

  static protected $options = array('root' => '.', 'root_url' => '/');

///   <protocol name="creating">

///   <method name="initialize" scope="class">
///     <args>
///       <arg name="options" type="array" default="array()" />
///     </args>
///     <body>
  static public function initialize(array $options = array()) { self::options($options); }
///     </body>
///   </method>

///   <method name="options" scope="class" returns="mixed">
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

///   <method name="option" scope="class">
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

///   <method name="Collection" returns="DB.ORM.Assets.Collection" scope="class">
///     <args>
///       <arg name="items" type="array" default="array()" />
///     </args>
///     <body>
  static public function Collection(array $items = array()) {
    return new DB_ORM_Assets_Collection($items);
  }
///     </body>
///   </method>

///   </protocol>
}
/// </class>


/// <interface name="DB.ORM.Assets.AssetContainerInterface">
interface DB_ORM_Assets_AssetContainerInterface {}
/// </interface>


/// <class name="DB.ORM.Assets.Asset">
///   <implements interface="Core.PropertyAccessInterface" />
class DB_ORM_Assets_Asset
  implements Core_PropertyAccessInterface {

  protected $collection;
  protected $name;

///   <protocol name="creating">

///   <method name="__construct">
///     <args>
///       <arg name="collection" type="DB.ORM.Assets.Collection" />
///       <arg name="name" type="string" />
///     </args>
///     <body>
  public function __construct(DB_ORM_Assets_Collection $collection, $name) {
    $this->collection = $collection;
    $this->name       = $name;
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
      case 'name':
        return $this->$property;
      case 'url':
        return $this->collection->path === null ? null : DB_ORM_Assets::option('root_url').$this->collection->path.($this->name ? '/'.$this->name : '');
      case 'file':
        return IO_FS::File($this->collection->path_for($this->name));
      case 'annotation':
        return $this->collection->annotation_for($this->name);
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
      case 'name':
      case 'url':
        throw new Core_ReadOnlyPropertyException($property);
      case 'file':
        {$this->collection->store_file($value, $this->name); return $this;}
      case 'annotation':
        {$this->collection->annotate($this->name, $value); return $this;}
    }
  }
///     </body>
///   </method>

///   <method name="__isset" returns="mixed">
///     <args>
///       <arg name="property" type="string" />
///     </args>
///     <body>
  public function __isset($property) {
    switch ($property) {
      case 'name':
      case 'url':
      case 'file':
      case 'annotation':
        return true;
      default:
        return false;
    }
  }
///     </body>
///   </method>

///   <method name="__unset" returns="mixed">
///     <args>
///       <arg name="property" type="string" />
///     </args>
///     <body>
  public function __unset($property) {
    switch ($property) {
      case 'name':
      case 'url':
      case 'file':
      case 'annotation':
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


/// <class name="DB.ORM.Assets.Collection">
///   <implements interface="Core.IndexedAccessInterface" />
///   <implements interface="Iterator" />
///   <implements interface="Core.PropertyAccessInterface" />
class DB_ORM_Assets_Collection
  implements Core_IndexedAccessInterface,
             Core_PropertyAccessInterface,
             Core_CountInterface,
             Iterator {

  protected $path;

  protected $items   = array();

  protected $current;

  protected $added   = array();
  protected $removed = array();

///   <protocol name="creating">

///   <method name="__construct">
///     <args>
///       <arg name="path" type="string" />
///       <arg name="items" type="array" />
///     </args>
///     <body>
  public function __construct(array $items = array()) {
    $this->items = $items;
  }
///     </body>
///   </method>

///   <method name="path">
///     <args>
///       <arg name="path" type="string" />
///     </args>
///     <body>
  public function path($path) {
    $this->path = $path;
    return $this;
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="indexing">

///   <method name="offsetGet" returns="mixed">
///     <args>
///       <arg name="index" type="string" />
///     </args>
///     <body>
  public function offsetGet($index) {
    return $this->offsetExists($index) ?  new DB_ORM_Assets_Asset($this, $index) : null;
  }
///     </body>
///   </method>

///   <method name="offsetSet" returns="mixed">
///     <args>
///       <arg name="index" type="value" />
///       <arg name="value" />
///     </args>
///     <body>
  public function offsetSet($index, $value) {
    $this->store_as($index, $value, '');
    return $this;
  }
///     </body>
///   </method>

///   <method name="offsetExists" returns="boolean">
///     <args>
///       <arg name="index" />
///     </args>
///     <body>
  public function offsetExists($index) { return isset($this->items[$index]); }
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

///   <protocol name="quering">

///   <method name="annotation_for" returns="mixed">
///     <args>
///       <arg name="name" type="string" />
///     </args>
///     <body>
  public function annotation_for($name) { return $this->items[$name]; }
///     </body>
///   </method>

///   <method name="path_for" returns="string">
///     <args>
///       <arg name="name" type="string" />
///     </args>
///     <body>
  public function path_for($name) {
    return $this->path === null ?
      null :
      DB_ORM_Assets::option('root').'/'.$this->path.($name ? '/'.$name : '');
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="counting" interface="Core.CountInterface">

///   <method name="count" returns="int">
///     <body>
  public function count() { return count($this->items); }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="performing">

///   <method name="annotate" returns="DB.ORM.Assets.Collection">
///     <args>
///       <arg name="name" type="string" />
///       <arg name="annotation" type="string" />
///     </args>
///     <body>
  public function annotate($name, $annotation) {
    $this->items[$name] = $annotation;
    return $this;
  }
///     </body>
///   </method>

///   <method name="store_file" returns="DB.ORM.Assets.Collection">
///     <body>
  public function store_file($file, $name) {
    if ($file instanceof IO_FS_File) $this->added[$name] = $file;
    return $this;
  }
///     </body>
///   </method>

///   <method name="store_as" returns="DB.ORM.Assets.Collection">
///     <args>
///       <arg name="name" type="string" />
///       <arg name="file" type="IO.FS.File" />
///       <arg name="annotation" type="string" default="''" />
///     </args>
///     <body>
  public function store_as($name, $file, $annotation = '') {
    $this->
      annotate($name, $annotation)->
      store_file($file, $name);
    return $this;
  }
///     </body>
///   </method>

///   <method name="store" returns="DB.ORM.Assets.Collection">
///     <args>
///       <arg name="file" type="IO.FS.File" />
///       <arg name="annotation" type="string" default="''" />
///     </args>
///     <body>
  public function store($file, $annotation = '') { return $this->store_as($file->name, $file, $annotation); }
///     </body>
///   </method>

///   <method name="remove" returns="DB.ORM.Assets.Collection">
///     <args>
///       <arg name="index" type="string" />
///     </args>
///     <body>
  public function remove($index) {
    if (isset($this->items[$index])) {
      unset($this->items[$index]);
      $this->removed[] = $index;
    }
    return $this;
  }
///     </body>
///   </method>

///   <method name="destroy" returns="DB.ORM.Assets.Collection">
///     <body>
  public function destroy() {
    if ($this->path !== null) {
      foreach (array_keys($this->items) as $name) IO_FS::rm($this->path_for($name));
      IO_FS::rm($this->path_for(''));
    }
    return $this;
  }
///     </body>
///   </method>

///   <method name="sync" returns="DB.ORM.Assets.Collection">
///     <body>
  public function sync() {
    if ($this->path !== null) {
      IO_FS::mkdir($this->path_for(''), 0777, true);
      foreach ($this->removed as $name) IO_FS::rm($this->path_for($name));
      foreach ($this->added as $name => $file) {
        $stored = $file->copy_to($this->path_for($name));
        $stored->chmod(0666);
      }
    }
    return $this;
  }
///     </body>
///   </method>

///   </protocol>


///   <protocol name="accessing" type="Core.PropertyAccessInterface">

///   <method name="__get" returns="mixed">
///     <args>
///       <arg name="property" type="string" />
///     </args>
///     <body>
  public function __get($property) {
    switch ($property) {
      case 'path':
        return $this->$property;
      case 'annotations':
      case 'items':
        return $this->items;
      case 'dir':
        return IO_FS::Dir($this->path);
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
  public function __set($property, $value) { throw new Core_ReadOnlyObjectException($this); }
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
      case 'annotations':
      case 'dir':
      case 'items':
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

///   <protocol name="iterating" interface="Iterator">

///   <method name="rewind">
///     <body>
  public function rewind() {
    reset($this->items);
    $this->current = key($this->items);
  }
///     </body>
///   </method>

///   <method name="key" returns="string">
///     <body>
  public function key() { return $this->current; }
///     </body>
///   </method>

///   <method name="valid" returns="boolean">
///     <body>
  public function valid() { return $this->current ? true : false; }
///     </body>
///   </method>

///   <method name="next">
///     <body>
  public function next() { $this->current = next($this->items) !== false ? key($this->items) : null; }
///     </body>
///   </method>

///   <method name="current" returns="P2.DB.EntityAsset">
///     <body>
  public function current() { return new DB_ORM_Assets_Asset($this, $this->current); }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="supporting">

///   <method name="make_storage" returns="boolean">
///     <body>
  protected function make_storage() { return IO_FS::mkdir($this->path, 0777, true); }
///     </body>
///   </method>

///   </protocol>
}
/// </class>

/// </module>
