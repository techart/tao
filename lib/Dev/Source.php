<?php
/// <module name="Dev.Source" version="0.2.0" maintainer="timokhin@techart.ru">
Core::load('IO.FS', 'XML', 'CLI');

/// <class name="Dev.Source" stereotype="module">
///   <implements interface="Core.ModuleInterface" />
///   <depends supplier="Dev.Source.Module" stereotype="creates" />
///   <depends supplier="Dev.Source.Library" stereotype="creates" />
class Dev_Source implements Core_ModuleInterface {

///   <constants>
  const MODULE  = 'Dev.Source';
  const VERSION = '0.2.1';
///   </constants>

///   <protocol name="Dev.Source">

///   <method name="Module" returns="Dev.Source.Module" scope="class">
///     <args>
///       <arg name="name" type="string" />
///     </args>
///     <body>
  static public function Module($name) { return new Dev_Source_Module($name); }
///     </body>
///   </method>

///   <method name="Library" returns="Dev.Source.Library" scope="class">
///     <body>
  static public function Library() {
    $args = func_get_args();
    return new Dev_Source_Library(((isset($args[0]) && is_array($args[0]) ? $args[0] : $args)));
  }
///     </body>
///   </method>

///   <method name="LibraryDirIterator" returns="Dev.Source.LibraryDirIterator" scope="class">
///     <args>
///       <arg name="path" type="string" />
///     </args>
///     <body>
  static public function LibraryDirIterator($path) {
    return new Dev_Source_LibraryDirIterator($path);
  }
///     </body>
///   </method>

///   </protocol>
}
/// </class>

/// <interface name="Dev.Source.LibraryIteratorInterface">
interface Dev_Source_LibraryIteratorInterface {}
/// </interface>

/// <class name="Dev.Source.Exception" extends="Core.Exception" stereotype="exception">
class Dev_Source_Exception extends Core_Exception {}
/// </class>


/// <class name="Dev.Source.InvalidSourceException" extends="Dev.Source.Exception" stereotype="exception">
class Dev_Source_InvalidSourceException extends Dev_Source_Exception {

  protected $module;
  protected $source;
  protected $errors;

///   <protocol name="creating">

///   <method name="__construct">
///     <args>
///       <arg name="module" type="string" />
///       <arg name="source" type="string" />
///       <arg name="errors" type="ArrayObject" />
///     </args>
///     <body>
  public function __construct($module, $source, ArrayObject $errors) {
    $this->module = $module;
    $this->source = $source;
    $this->errors = $errors;
    parent::__construct(Core_Strings::format('Invalid source for module %s (errors: %d)', $this->module, count($this->errors)));
  }
///     </body>
///   </method>

///   </protocol>

}
/// </class>


/// <class name="Dev.Source.Module">
///   <implements interface="Core.PropertyAccessInterface" />
///   <depends supplier="Dev.Source.InvalidSourceException" stereotype="throws" />
class Dev_Source_Module {

  protected $name;
  protected $file;
  protected $xml;

///   <protocol name="creating">

///   <method name="__construct">
///     <args>
///       <arg name="name" type="string" />
///     </args>
///     <body>
  public function __construct($name) {
    $this->name = $name;
    $this->file = IO_FS::File(Core::loader()->file_path_for($name));
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="supporting">

///   <method name="load" access="protected" returns="Dev.Source.Module">
///     <args>
///       <arg name="reload" type="boolean" />
///     </args>
///     <body>
  protected function load($reload = false) {
    if (!$this->xml || $reload) {

      $is_cdata = false;
      $text   = '';
      $is_ignore = false;

      foreach ($this->file->open('r')->text() as $line) {

        if (preg_match('{^///\s+<ignore>}', $line)) $is_ignore = true;
        if (preg_match('{^///\s+</ignore>}', $line)) {$is_ignore = false; $text .= "\n"; continue;}

        if (preg_match('{^\s*$|^<\?php|^\?>}', $line) || $is_ignore) {$text .= "\n"; continue;}


        if (preg_match('{^///(.*)$}', $line, $m)) {
          $text .= ($is_cdata ? "]]>\n" : '').$m[1]."\n";
          $is_cdata = false;
        } else {
          if ($is_cdata) $text .= "\n".rtrim($line);
          else {
            $text .= '<![CDATA['.rtrim($line);
           $is_cdata = true;
          }
        }
      }

      if (!($this->xml = Core::with($loader = XML::Loader())->load($text)))
        throw new Dev_Source_InvalidSourceException($this->name, $text, $loader->errors);
    }
    return $this->xml;
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
      case 'xml':
        return $this->load();
      case 'file':
      case 'name':
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
    throw ($this->__isset($property)) ?
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
      case 'xml':
      case 'file':
      case 'name':
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
    throw ($this->__isset($property)) ?
      new Core_UndestroyablePropertyException($property) :
      new Core_MissingPropertyException($property);
  }
///     </body>
///   </method>

///   </protocol>

}
/// </class>


/// <class name="Dev.Source.Library">
///   <implements interface="Core.PropertyAccessInterface" />
///   <implements interface="Dev.Source.LibraryIteratorInterface" />
//    <implements interface="IteratorAggregate" />
class Dev_Source_Library
  implements Core_PropertyAccessInterface,
             Dev_Source_LibraryIteratorInterface,
             IteratorAggregate {

  protected $modules;
  protected $xml;

///   <protocol name="creating">

///   <method name="__construct">
///     <body>
  public function __construct() {
    $this->modules = Core::hash();

    $args = func_get_args();
    foreach (((isset($args[0]) && is_array($args[0]) ? $args[0] : $args)) as $module) {
      $this->module($module);
    }

  }
///     </body>
///   </method>

///   <method name="module" returns="Dev.Source.Library">
///     <args>
///       <arg name="module" />
///     </args>
///     <body>
  public function module($module) {
    $module = ($module instanceof Dev_Source_Module) ?
        $module :
        Dev_Source::Module((string) $module);

    $this->modules[$module->name] = $module;
    return $this;
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="iterating" interface="IteratorAggregate">

///   <method name="getIterator" returns="Iterator">
///     <body>
  public function getIterator() { return $this->modules->getIterator(); }
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
      case 'xml':
        return $this->load();
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
      case 'xml':
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

///   <protocol name="supporting">

///   <method name="load" returns="Dev.Source.Library">
///     <args>
///       <arg name="reload" type="boolean" />
///     </args>
///     <body>
  public function load($reload = false) {
    if (!$this->xml || $reload) {
      $library = Core::with(
        $this->xml = new DOMDocument())->
          appendChild(new DOMElement('library'));

      foreach ($this->modules as $module)
        if ($module->xml)
          $library->appendChild(
            $this->xml->importNode($module->xml->documentElement, true));
    }
    return $this->xml;
  }
///     </body>
///   </method>

///   </protocol>
}
/// </class>

/// <class name="Dev.Source.LibraryDirIterator">
///   <implements interface="Iterator" />
///   <implements interface="Dev.Source.LibraryIteratorInterface" />
class Dev_Source_LibraryDirIterator
  implements Iterator,
             Dev_Source_LibraryIteratorInterface {

  protected $path;
  protected $current;
  protected $dir_iterator;
///   <protocol name="creating">
///   <method name="__construct">
///     <args>
///       <arg name="path" type="string" />
///     </args>
///     <body>
  public function __construct($path) {
    $this->path = (string) $path;
    $this->dir_iterator = IO_FS::Dir($this->path)->query(
      IO_FS::Query()->glob('*.php')->recursive(true));
    $this->dir_iterator->rewind();
    $this->current = Dev_Source::Module($this->module_name($this->dir_iterator->current()->path));
  }
///     </body>
///   </method>
///   </protocol>

///   <protocol name="suppotring">

///   <method name="module_name" returns="string">
///     <args>
///       <arg name="path" type="string" />
///     </args>
///     <body>
  protected function module_name($path) {
    return Core_Strings::replace(
      Core_Strings::replace(
      Core_Strings::replace($path, $this->path."/", ''), '.php', ''), '/', '.');
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="iterating">

///   <method name="rewind">
///     <body>
  public function rewind() { $this->dir_iterator->rewind(); }
///     </body>
///   </method>

///   <method name="current" returns="mixed">
///     <body>
  public function current() { return $this->current; }
///     </body>
///   </method>

///   <method name="key" returns="string">
///     <body>
  public function key() { return $this->current->name; }
///     </body>
///   </method>

///   <method name="next">
///     <body>
  public function next() {
    $this->dir_iterator->next();
    if ($this->dir_iterator->valid())
    $this->current = Dev_Source::Module(
      $this->module_name($this->dir_iterator->current()->path));
    else return null;
  }
///     </body>
///   </method>

///   <method name="valid" returns="boolean">
///     <body>
  public function valid() { return $this->dir_iterator->valid(); }
///     </body>
///   </method>

/// </protocol>

}
/// </class>

/// <aggregation>
///   <source class="Dev.Source.Library" role="library" multiplicity="1" />
///   <target class="Dev.Source.Module"  role="module"  multiplicity="N" />
/// </aggregation>

/// </module>
?>
