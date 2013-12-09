<?php
/**
 * DB.ORM.Assets
 * 
 * @package DB\ORM\Assets
 * @version 0.2.0
 */

Core::load('IO.FS');

/**
 * @package DB\ORM\Assets
 */
class DB_ORM_Assets implements Core_ConfigurableModuleInterface {

  const VERSION = '0.2.0';

  static protected $options = array('root' => '.', 'root_url' => '/');


/**
 * @param array $options
 */
  static public function initialize(array $options = array()) { self::options($options); }

/**
 * @param array $options
 * @return mixed
 */
  static public function options(array $options = array()) {
    if (count($options)) Core_Arrays::update(self::$options, $options);
    return self::$options;
  }

/**
 * @param string $name
 * @param  $value
 */
  static public function option($name, $value = null) {
    $prev = isset(self::$options[$name]) ? self::$options[$name] : null;
    if ($value !== null) self::options(array($name => $value));
    return $prev;
  }

/**
 * @param array $items
 * @return DB_ORM_Assets_Collection
 */
  static public function Collection(array $items = array()) {
    return new DB_ORM_Assets_Collection($items);
  }

}


/**
 * @package DB\ORM\Assets
 */
interface DB_ORM_Assets_AssetContainerInterface {}


/**
 * @package DB\ORM\Assets
 */
class DB_ORM_Assets_Asset
  implements Core_PropertyAccessInterface {

  protected $collection;
  protected $name;


/**
 * @param DB_ORM_Assets_Collection $collection
 * @param string $name
 */
  public function __construct(DB_ORM_Assets_Collection $collection, $name) {
    $this->collection = $collection;
    $this->name       = $name;
  }



/**
 * @param string $property
 * @return mixed
 */
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

/**
 * @param string $property
 * @param  $value
 * @return mixed
 */
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

/**
 * @param string $property
 * @return mixed
 */
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

/**
 * @param string $property
 * @return mixed
 */
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

}


/**
 * @package DB\ORM\Assets
 */
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


/**
 * @param string $path
 * @param array $items
 */
  public function __construct(array $items = array()) {
    $this->items = $items;
  }

/**
 * @param string $path
 */
  public function path($path) {
    $this->path = $path;
    return $this;
  }



/**
 * @param string $index
 * @return mixed
 */
  public function offsetGet($index) {
    return $this->offsetExists($index) ?  new DB_ORM_Assets_Asset($this, $index) : null;
  }

/**
 * @param value $index
 * @param  $value
 * @return mixed
 */
  public function offsetSet($index, $value) {
    $this->store_as($index, $value, '');
    return $this;
  }

/**
 * @param  $index
 * @return boolean
 */
  public function offsetExists($index) { return isset($this->items[$index]); }

/**
 * @param string $index
 */
  public function offsetUnset($index) { $this->remove($index); }



/**
 * @param string $name
 * @return mixed
 */
  public function annotation_for($name) { return $this->items[$name]; }

/**
 * @param string $name
 * @return string
 */
  public function path_for($name) {
    return $this->path === null ?
      null :
      DB_ORM_Assets::option('root').'/'.$this->path.($name ? '/'.$name : '');
  }



/**
 * @return int
 */
  public function count() { return count($this->items); }



/**
 * @param string $name
 * @param string $annotation
 * @return DB_ORM_Assets_Collection
 */
  public function annotate($name, $annotation) {
    $this->items[$name] = $annotation;
    return $this;
  }

/**
 * @return DB_ORM_Assets_Collection
 */
  public function store_file($file, $name) {
    if ($file instanceof IO_FS_File) $this->added[$name] = $file;
    return $this;
  }

/**
 * @param string $name
 * @param IO_FS_File $file
 * @param string $annotation
 * @return DB_ORM_Assets_Collection
 */
  public function store_as($name, $file, $annotation = '') {
    $this->
      annotate($name, $annotation)->
      store_file($file, $name);
    return $this;
  }

/**
 * @param IO_FS_File $file
 * @param string $annotation
 * @return DB_ORM_Assets_Collection
 */
  public function store($file, $annotation = '') { return $this->store_as($file->name, $file, $annotation); }

/**
 * @param string $index
 * @return DB_ORM_Assets_Collection
 */
  public function remove($index) {
    if (isset($this->items[$index])) {
      unset($this->items[$index]);
      $this->removed[] = $index;
    }
    return $this;
  }

/**
 * @return DB_ORM_Assets_Collection
 */
  public function destroy() {
    if ($this->path !== null) {
      foreach (array_keys($this->items) as $name) IO_FS::rm($this->path_for($name));
      IO_FS::rm($this->path_for(''));
    }
    return $this;
  }

/**
 * @return DB_ORM_Assets_Collection
 */
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




/**
 * @param string $property
 * @return mixed
 */
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

/**
 * @param string $property
 * @param  $value
 * @return mixed
 */
  public function __set($property, $value) { throw new Core_ReadOnlyObjectException($this); }

/**
 * @param string $property
 * @return boolean
 */
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

/**
 * @param string $property
 */
  public function __unset($property) { throw new Core_ReadOnlyObjectException($this); }



/**
 */
  public function rewind() {
    reset($this->items);
    $this->current = key($this->items);
  }

/**
 * @return string
 */
  public function key() { return $this->current; }

/**
 * @return boolean
 */
  public function valid() { return $this->current ? true : false; }

/**
 */
  public function next() { $this->current = next($this->items) !== false ? key($this->items) : null; }

/**
 * @return P2_DB_EntityAsset
 */
  public function current() { return new DB_ORM_Assets_Asset($this, $this->current); }



/**
 * @return boolean
 */
  protected function make_storage() { return IO_FS::mkdir($this->path, 0777, true); }

}

