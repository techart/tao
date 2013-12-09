<?php
/**
 * WS.REST.URI
 * 
 * @package WS\Services\REST\URI
 * @version 0.2.0
 */

/**
 * @package WS\Services\REST\URI
 */
class WS_Services_REST_URI implements Core_ModuleInterface {
  const VERSION = '0.2.1';


/**
 * @param string $template
 * @return WS_URI_Template
 */
  static public function Template($template) { return new WS_Services_REST_URI_Template($template); }

}


/**
 * @package WS\Services\REST\URI
 */
class WS_Services_REST_URI_MatchResults
  implements Core_PropertyAccessInterface,
             Core_IndexedAccessInterface,
             IteratorAggregate {

  protected $parms;
  protected $tail;


/**
 * @param array $parms
 * @param string $tail
 */
  public function __construct(array $parms, $tail = '') {
    $this->parms = $parms;
    //$this->tail = ($tail == '/' ? '' : (string) $tail);
    //urls like '/test/11/' '/test/11.html' 'test/11/index.html' a the same
    $this->tail = (in_array($tail, array('/', '/index')) ? '' : (string) $tail);
  }



/**
 * @param string $property
 * @return mixed
 */
  public function __get($property) {
    switch ($property) {
      case 'tail':
      case 'parms':
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
  public function __set($property, $value) { throw new Core_ReadOnlyObjectException($property); }

/**
 * @param string $property
 * @return boolean
 */
  public function __isset($property) {
    switch ($property) {
      case 'tail':
      case 'parms':
        return true;
      default:
        return false;
    }
  }

/**
 * @param string $property
 */
  public function __unset($property) { throw new Core_ReadOnlyObjectException($property); }



/**
 * @return ArrayIterator
 */
  public function getIterator() { return new ArrayIterator($this->parms); }



/**
 * @param string $index
 * @return mixed
 */
  public function offsetGet($index) {
    return isset($this->parms[$index]) ? $this->parms[$index] : null;
  }

/**
 * @param string $index
 * @param  $value
 * @return mixed
 */
  public function offsetSet($index, $value) { throw new Core_ReadOnlyObjectException($this); }

/**
 * @param string $index
 * @return boolean
 */
  public function offsetExists($index) { return isset($this->parms[$index]); }

/**
 * @param string $index
 */
  public function offsetUnset($index) { throw new Core_ReadOnlyObjectException($this); }

}

/**
 * @package WS\Services\REST\URI
 */
class WS_Services_REST_URI_Template
  implements Core_StringifyInterface,
             Core_PropertyAccessInterface,
             Core_EqualityInterface {

  protected $template;
  protected $regexp;
  protected $parms = array();


/**
 * @param string $template
 */
  public function __construct($template) { $this->parse($template); }



/**
 * @param string $uri
 * @return WS_URI_MatchResults
 */
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



/**
 * @param string $property
 * @return mixed
 */
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

/**
 * @param string $property
 * @param  $value
 * @return mixed
 */
  public function __set($property, $value) { throw new Core_ReadOnlyObjectException($property); }

/**
 * @param string $property
 * @return boolean
 */
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

/**
 * @param string $property
 */
  public function __unset($property) { throw new Core_ReadOnlyObjectException($property); }



/**
 * @return string
 */
  public function as_string() { return $this->template; }

/**
 * @return string
 */
  public function __toString() { return $this->as_string(); }



/**
 * @param string $template
 * @return WS_URI_Template
 */
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

/**
 * @param array $matches
 * @return string
 */
  protected function parsing_callback($matches) {
    $this->parms[] = $matches[1];
    return isset($matches[2]) ? '('.$matches[2].')' : '([^/]+)';
  }


/**
 * @param  $to
 * @return boolean
 */
  public function equals($to) {
    return $to instanceof self &&
      $this->template === $to->template &&
      $this->regexp === $to->regexp;
  }
}

