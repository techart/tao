<?php
/**
 * Net.HTTP.Session
 * 
 * @package Net\HTTP\Session
 * @version 0.2.0
 */
Core::load('Net.HTTP');

/**
 * @package Net\HTTP\Session
 */
class Net_HTTP_Session implements Core_ModuleInterface {
  const VERSION = '0.2.1';

  static private $store;


/**
 * @return WS_Session_Store
 */
  static public function Store() {
    return isset(self::$store) ?
      self::$store : (self::$store = Core::is_cli() ? array() : new Net_HTTP_Session_Store()); }

/**
 * @return WS_Session_Store
 */
  static public function Flash(array $now = array()) { return new Net_HTTP_Session_Flash($now); }

}


/**
 * @package Net\HTTP\Session
 */
class Net_HTTP_Session_Store
  implements Net_HTTP_SessionInterface,
             Core_PropertyAccessInterface,
             Core_IndexedAccessInterface {


/**
 */
  public function __construct() {
    session_start();
  }



/**
 * @param string $name
 * @param  $default
 * @return mixed
 */
  public function get($name, $default = null) { return isset($this[$name]) ? $this[$name] : $this[$name] = $default; }

/**
 * @param string $name
 * @param  $value
 * @return this
 */
  public function set($name, $value) { $this[$name] = $value; return $this; }

/**
 * @param string $name
 * @return boolean
 */
  public function exists($name) {return isset($this[$name]); }

/**
 * @param string $name
 * @return boolean
 */
  public function remove($name) {unset($this[$name]); return $this;}

/**
 * @return void
 */
  public function commit() { session_commit(); }



/**
 * @param string $index
 * @param  $value
 * @return mixed
 */
  public function offsetSet($index, $value) { $_SESSION[$index] = $value; return $this; }

/**
 * @param string $index
 * @return mixed
 */
  public function offsetGet($index) { return $_SESSION[$index]; }

/**
 * @param string $index
 * @return boolean
 */
  public function offsetExists($index) { return isset($_SESSION[$index]); }


/**
 * @param string $index
 */
  public function offsetUnset($index) { unset($_SESSION[$index]); }



/**
 * @param string $property
 * @return mixed
 */
  public function __get($property) {
    switch ($property) {
      case 'id':           return session_id();
      case 'name':         return session_name();
      case 'cookie_parms': return session_get_cookie_params();
      case 'expire':       return session_cache_expire();
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
      case 'id': 
      case 'name': 
      case 'cookie_parms': 
      case 'expire':
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
      case 'id':
      case 'name': 
      case 'cookie_parms': 
      case 'expire':
        return true;
      default:
        return false;
    }
  }

/**
 * @param string $property
 */
  public function __unset($property) {
    throw $this->__isset($property) ?
      new Core_UndestroyablePropertyException($property) :
      new Core_MissingPropertyException($property);
  }

}

/**
 * @package Net\HTTP\Session
 */
class Net_HTTP_Session_Flash
  implements Core_IndexedAccessInterface,
             Core_PropertyAccessInterface {

  protected $now;
  protected $later;


/**
 */
  public function __construct($now = array()) {
    $this->now = $now;
    $this->later = array();
  }



/**
 * @param string $index
 * @return mixed
 */
  public function offsetGet($index) { return isset($this->now[$index]) ? $this->now[$index] : null; }

/**
 * @param string $index
 * @param  $value
 * @return mixed
 */
  public function offsetSet($index, $value) { $this->later[$index] = $value; return $this; }

/**
 * @param string $index
 * @return boolean
 */
  public function offsetExists($index) { return array_key_exists($index, $this->now); }

/**
 * @param string $index
 */
  public function offsetUnset($index) { unset($this->later[$index]); }



/**
 * @param string $property
 * @return mixed
 */
  public function __get($property) {
    switch ($property) {
      case 'now':
      case 'later':
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
  public function __set($property, $value) { throw new Core_ReadOnlyObjectException($this); }

/**
 * @param string $property
 * @return boolean
 */
  public function __isset($property) {
    switch ($property) {
      case 'now':
      case 'later':
        return isset($this->$property);
      default:
        return false;
    }
  }

/**
 * @param string $property
 */
  public function __unset($property) { throw new Core_ReadOnlyObjectException($this); }


}

