<?php
/**
 * Service.OpenSocial
 * 
 * OpenSocial-клиент
 * 
 * @package Service\OpenSocial
 * @version 0.1.1
 */

/**
 * Библиотека модулей OpenSocial
 * 
 * @package Service\OpenSocial
 */
interface Service_OpenSocial_ModuleInterface extends Core_ModuleInterface {
  const VERSION = '0.1.1';
}

Core::load('Net.HTTP', 'Object', 'Service.OpenSocial.DSL');

/**
 * Модуль Service.OpenSocial
 * 
 * @package Service\OpenSocial
 */
class Service_OpenSocial implements Service_OpenSocial_ModuleInterface {


/**
 * @return Service_OpenSocial_Client|Service_OpenSocial_DSL_ClientBuilder
 */
  static public function Client() {
    return count($args = func_get_args()) ?
      new Service_OpenSocial_Client($args[0], isset($args[1]) ? $args[1] : null) :
      new Service_OpenSocial_DSL_ClientBuilder();
  }

/**
 * @param array $options
 * @return Service_OpenSocial_Container
 */
  static public function Container(array $options) { return new Service_OpenSocial_Container($options); }

/**
 * Создает объект класса Service.OpenSocial.Request
 * 
 * @return Service_OpenSocial_Request
 */
  static public function Request() { return new Service_OpenSocial_Request(); }

/**
 * @param array $values
 * @return Service_OpenSocial_Person
 */
  static public function Person(array $values = array()) { return new Service_OpenSocial_Person($values); }

/**
 * Создает объект класса Service.OpenSocial.Group
 * 
 * @param array $values
 * @return Service_OpenSocial_Group
 */
  static public function Group(array $values = array()) { return new Service_OpenSocial_Group($values); }

/**
 * @param array $values
 * @return Service_OpenSocial_Activity
 */
  static public function Activity(array $values = array()) { return new Service_OpenSocial_Activity($values); }
/**
 * @param array $values
 * @return Service_OpenSocial_AppData
 */
  static public function AppData(array $values = array()) { return new Service_OpenSocial_AppData($values); }



/**
 * @param string $name
 * @return string
 */
  public function canonicalize($name) {
    static $names = array();
    if (!isset($names[$name]))
      $names[$name] = Core_Strings::to_camel_case($name, true);
    return $names[$name];
  }

}


/**
 * @package Service\OpenSocial
 */
class Service_OpenSocial_Exception extends Core_Exception {}


/**
 * @package Service\OpenSocial
 */
class Service_OpenSocial_Container extends Object_Struct {

  protected $name                   = '';
  protected $request_token_endpoint = '';
  protected $authorize_endpoint     = '';
  protected $access_token_endpoint  = '';
  protected $rest_endpoint          = '';
  protected $rpc_endpoint           = '';
  protected $use_method_override    = true;
  protected $use_request_body_hash  = true;


/**
 * @param array $options
 */
  public function __construct(array $options = array()) {
    foreach ($options as $k => $v) $this->__set($k, $v);
  }



/**
 * @param boolean $value
 */
  protected function set_use_request_body_hash($value) { $this->use_request_body_hash = (boolean) $value; }

/**
 * @param boolean $value
 */
  protected function set_use_method_override($value) { $this->use_method_override = (boolean) $value; }

}


/**
 * @package Service\OpenSocial
 */
class Service_OpenSocial_Operation extends Object_Const {

  const GET    = 'get';
  const CREATE = 'create';
  const DELETE = 'delete';
  const UPDATE = 'update';

  protected $http_method;


/**
 * @param string $name
 * @param int $http_method
 */
  protected function __construct($name, $http_method) {
    $this->http_method = $http_method;
    parent::__construct($name);
  }



/**
 * @return string
 */
  protected function get_http_method_name() { return Net_HTTP::method_name_for($this->http_method); }



/**
 * @param  $constant
 * @return Service_OpenSocial_Operation
 */
  static public function object($constant) { return self::object_for('Service_OpenSocial_Operation', $constant); }

/**
 * @return Service_OpenSocial_Operation
 */
  static public function GET() { return new Service_OpenSocial_Operation(self::GET, Net_HTTP::GET); }

/**
 * @return Service_OpenSocial_Operation
 */
  static public function CREATE() { return new Service_OpenSocial_Operation(self::CREATE, Net_HTTP::POST); }

/**
 * @return Service_OpenSocial_Operation
 */
  static public function UPDATE() { return new Service_OpenSocial_Operation(self::UPDATE, Net_HTTP::PUT); }

/**
 * @return Service_OpenSocial_Operation
 */
  static public  function DELETE() { return new Service_OpenSocial_Operation(self::DELETE, Net_HTTP::DELETE); }

}


/**
 * @package Service\OpenSocial
 */
class Service_OpenSocial_Service extends Object_Const {

  const PEOPLE     = 'people';
  const GROUP      = 'group';
  const ACTIVITIES = 'activities';
  const APPDATA    = 'appdata';

  protected $resource_class;


/**
 * @param OpenSocial_Service|string $constant
 * @return OpenSocial_Service
 */
  static public function object($constant) { return self::object_for('Service_OpenSocial_Service', $constant); }

/**
 * @return OpenSocial_Service
 */
  static public function PEOPLE() { return new Service_OpenSocial_Service(self::PEOPLE); }

/**
 * @return OpenSocial_Service
 */
  static public function GROUP() { return new Service_OpenSocial_Service(self::GROUP); }

/**
 * @return OpenSocial_Service
 */
  static public function ACTIVITIES() { return new Service_OpenSocial_Service(self::ACTIVITIES); }

/**
 * @return OpenSocial_Service
 */
  static public function APPDATA() { return new Service_OpenSocial_Service(self::APPDATA); }



/**
 * @return string
 */
   protected function get_rpc_name() {
     switch ($this->value) {
       case 'activities' : return 'activity';
       case 'appdata'    : return 'data';
       default           : return $this->value;
     }
   }

/**
 * @param  $data
 * @return Service_OpenSocial_Resource
 */
  public function make_resource_for($data) {
    switch ($this->value) {
      case self::PEOPLE:
        return new Service_OpenSocial_Person($data);
      case self::GROUP:
        return new Service_OpenSocial_Group($data);
      case self::ACTIVITIES:
        return new Service_OpenSocial_Activity($data);
      case self::APPDATA:
        return new Service_OpenSocial_AppData($data);
      default:
        throw new Service_OpenSocial_Exception("No resource for service: {$this->value}");
    }
  }


}


/**
 * @package Service\OpenSocial
 */
class Service_OpenSocial_Collection
  extends ArrayObject
  implements Core_PropertyAccessInterface,
             Service_OpenSocial_ResultInterface {

  protected $items_per_page = 0;
  protected $total_results  = 0;
  protected $start_index    = 0;


/**
 * @param int $total_results
 * @param int $items_per_page
 * @param int $start_index
 */
  public function __construct($total_results, $items_per_page, $start_index) {
    $this->total_results  = $total_results;
    $this->items_per_page = $items_per_page;
    $this->start_index    = $start_index;
  }




/**
 * @param Service_OpenSocial_Resource $resource
 * @return Service_OpenSocial_Collection
 */
  public function append($resource) {
    if ($resource instanceof Service_OpenSocial_Resource) {
      parent::append($resource);}
    else
      throw Service_OpenSocial_Exception("bad resource");
    return $this;
  }



/**
 * @param string $property
 * @return mixed
 */
  public function __get($property) {
    switch ($property) {
      case 'items_per_page':
      case 'total_results':
      case 'start_index':
        return $this->$property;
      default:
        throw new Core_MissingPropertyException($property);
    }
  }

/**
 * @param string $property
 * @param  $value
 * @return OpenSocial_Collection
 */
  public function __set($property, $value) { throw new Core_ReadOnlyObjectException($this); }

/**
 * @param string $property
 * @return boolean
 */
  public function __isset($property) { return isset($this->$property); }

/**
 * @param string $property
 */
  public function __unset($property) { throw new Core_ReadOnlyObjectException($this); }



/**
 * @param int $index
 * @param  $value
 */
  public function offsetSet($index, $value) {
    if (!is_null($index))
      throw new Core_ReadOnlyObjectException($this);
    else
      parent::offsetSet($index, $value);
  }

/**
 * @param index $index
 */
  public function offsetUnset($index) { throw new Core_ReadOnlyObjectException($this); }

}

/**
 * @package Service\OpenSocial
 */
class Service_OpenSocial_ResultSet extends ArrayObject {


/**
 * @param string $index
 * @param Service_OpenSocial_ResultInterface $retuls
 */
  public function offsetSet($index, $result) { $this->result($index, $result); }



/**
 * @param string $index
 * @param Service_OpenSocial_ResultInterface $result
 * @return Service_OpenSocial_ResultSet
 */
  public function result($index, Service_OpenSocial_ResultInterface $result) {
    parent::offsetSet($index, $result);
    return $this;
  }

/**
 * @param  $value
 */
  final public function append($value) { throw  new Service_OpenSocial_Exception('Use explicit index to add result'); }

}

/**
 * @package Service\OpenSocial
 */
class Service_OpenSocial_Resource
  extends ArrayObject
  implements Core_PropertyAccessInterface,
             Core_CallInterface,
             Service_OpenSocial_ResultInterface {


/**
 * @param  $values
 */
  public function __construct($values = array()) {
    foreach ($values as $k => $v) $this[$k] = $v;
  }



/**
 * @param string $Property
 * @return mixed
 */
  public function __get($property) {
    return method_exists($this, $m = "get_$property") ?
      $this->$m() :
      (isset($this[$p = Service_OpenSocial::canonicalize($property)]) ?
        $this[$p] : null);
  }

/**
 * @param string $property
 * @param  $value
 * @return Service_OpenSocial_Resource
 */
  public function __set($property, $value) {
    method_exists($this, $m = "set_$property") ?
      $this->$m($value) : $this[Service_OpenSocial::canonicalize($property)] = $value;
    return $this;
  }

/**
 * @param string $property
 * @return Service_OpenSocial_Resource
 */
  public function __isset($property) { return isset($this[Service_OpenSocial::canonicalize($property)]); }

/**
 * @param string $property
 * @return Service_OpenSocial_Resource
 */
    public function __unset($property) {
      if (isset($this[$p = Service_OpenSocial::canonicalize($property)])) unset($this[$p]);
      return $this;
    }



/**
 * @param string $method
 * @param array $args
 * @return Service_OpenSocial_Resource
 */
  public function __call($method, $args) {
    $this->__set($method, $args[0]);
    return $this;
  }


}


/**
 * @package Service\OpenSocial
 */
class Service_OpenSocial_Person extends Service_OpenSocial_Resource {
}


/**
 * @package Service\OpenSocial
 */
class Service_OpenSocial_Group extends Service_OpenSocial_Resource {
}


/**
 * @package Service\OpenSocial
 */
class Service_OpenSocial_Activity extends Service_OpenSocial_Resource {
}


/**
 * @package Service\OpenSocial
 */
class Service_OpenSocial_AppData extends Service_OpenSocial_Resource {
}


/**
 * @package Service\OpenSocial
 */
class Service_OpenSocial_Request
  implements Core_CallInterface,
             Core_PropertyAccessInterface {

  protected $service;
  protected $operation;

  protected $id       = '';
  protected $user_id  = '@me';
  protected $group_id = '@self';
  protected $app_id   = '';
  protected $resource_id = '';

  protected $params = array();

  protected $resource;


/**
 * @param string $property
 * @return mixed
 */
  public function __get($property) {
    switch ($property) {
      case 'id':
      case 'user_id':
      case 'group_id':
      case 'app_id':
      case 'service':
      case 'operation':
      case 'resource':
      case 'params':
      case 'resource_id':
        return $this->$property;
      default:
        return isset($this->params[$property]) ? $this->params[$property] : null;
    }
  }

/**
 * @param string $property
 * @param  $value
 * @return Service_OpenSocial_Request
 */
  public function __set($property, $value) {
    switch ($property) {
      case 'id':
      case 'user_id':
      case 'group_id':
      case 'app_id':
      case 'resource_id':
        $this->$property = (string) $value;
        break;
      case 'service':
        $this->service = Service_OpenSocial_Service::object($value);
        break;
      case 'operation':
        $this->operation = Service_OpenSocial_Operation::object($value);
        break;
      case 'resource':
        $this->resource($value);
        break;
      default:
        $this->params[$property] = $value;
    }
    return $this;
  }

/**
 * @param  $property
 * @return boolean
 */
  public function __isset($property) {
    switch ($property) {
      case 'id':
      case 'user_id':
      case 'group_id':
      case 'app_id':
      case 'service':
      case 'operation':
      case 'resource':
      case 'resource_id':
        return isset($this->$property);
      case 'params':
        return count($this>params) > 0;
      default:
        return isset($this->params[$property]);
    }
  }

/**
 * @param  $property
 * @return Service_OpenSocial_Request
 */
  public function __unset($property) {
    switch ($property) {
      case 'id':
      case 'user_id':
      case 'group_id':
      case 'app_id':
      case 'resource_id':
        $this->$property = '';
        break;
      case 'service':
      case 'operation':
      case 'resource':
        $this->$property = null;
        break;
      default:
        if (isset($this->params[$property])) unset($this->params[$property]);
    }
    return $this;
  }




/**
 * @param Service_OpenSocial_Resource $resource
 * @return Service_OpenSocial_Request
 */
  public function resource(Service_OpenSocial_Resource $resource) {
    $this->resource = $resource;
    return $this;
  }


/**
 * @param  $params
 * @return Service_OpenSocial_Request
 */
  public function params($params) {
    foreach ($params as $k => $v) $this->params[$k] = (string) $v;
    return $this;
  }



/**
 * @param string $method
 * @param array $args
 * @return mixed
 */
  public function __call($method, $args) { return $this->__set($method, $args[0]); }

}


/**
 * Успользуется для правильного построения запроса с учетом аутентификации
 * 
 * @abstract
 * @package Service\OpenSocial
 */
abstract class Service_OpenSocial_AuthAdapter implements Core_CallInterface {

  protected $options;


/**
 * Конструктор
 * 
 * @param array $options
 */
  public function __construct(array $options = array()) { $this->options = $options; }



/**
 * @param array $options
 * @param OpenSocial_Container $container
 * @return Net_HTTP_Request
 */
  abstract public function authorize_request(Net_HTTP_Request $request, Service_OpenSocial_Container $container);



/**
 * @param string $method
 * @param array $args
 * @return OpenSocial_HTTPRequestBuilder
 */
  public function __call($method, $args) {
    if (array_key_exists($method, $this->options))
      $this->options[$method] = $args[0];
    else
      throw new Core_MissingMethodException($method);
    return $this;
  }



/**
 * @param  $array
 */
  protected function canonicalize($array) {
    $res = array();
    foreach($array as $k => $v) $res[Service_OpenSocial::canonicalize($k)] = $v;
    return $res;
  }

}


/**
 * @package Service\OpenSocial
 */
class Service_OpenSocial_Client
  implements Core_IndexedAccessInterface,
             Core_PropertyAccessInterface {

  protected $container;
  protected $protocol;

  protected $requests;
  protected $request_counter = 0;


/**
 * @param Service_OpenSocial_Protocol $protocol
 * @param Service_OpenSOcial_Container|null $container
 */
  public function __construct(Service_OpenSocial_Protocol $protocol, Service_OpenSocial_Container $container) {
    $this->protocol  = $protocol;
    $this->container = $container;
    $this->reset();
  }



/**
 * @param OpenSocial_Request $request
 * @param string $id
 * @return Service_OpenSocial_Client
 */
  public function request() {
    $args = func_get_args();

    if (count($args) == 0)
      return new Service_OpenSocial_DSL_RequestBuilder($this);
    else
      return $this->append_request($args[0], isset($args[1]) ? $args[1] : null);
  }

/**
 * @param array $values
 * @return OpenSocial_Client
 */
  public function requests() {
    foreach (Core::normalize_args(func_get_args()) as $id => $request)
      $this->request($request, is_int($id) ? null : $id);
    return $this;
  }



/**
 * @return OpenSocial_ResultSet
 */
  public function send() {
    if ($this->request_counter == 0)
      throw new Service_OpenSocial_Exception('No requests');

    $result = $this->protocol->send($this->container, $this->requests);
    $this->reset();
    return $result;
  }



/**
 * @return OpenSocial_Client
 */
  protected function reset() {
    $this->requests = array();
    $this->request_counter = 0;
    return $this;
  }

/**
 * @param Service_OpenSocial_Request $request
 * @param  $id
 * @return Service_OpenSocial_Client
 */
  public function append_request(Service_OpenSocial_Request $request, $id = null) {
    $this->request_counter++;
    if (is_null($id)) {
      if (isset($request->id) && $request->id !== '')
        $this->requests[$request->id] = $request;
      else
        $this->requests[$this->request_counter] = $request->id($this->request_counter);
    } else
      $this->requests[$id] = $request->id($id);
    return $this;
  }



/**
 * @param  $index
 * @return Service_OpenSocial_Request
 */
  public function offsetGet($index) {
    return isset($this->requests[$index]) ? $this->requests[$index] : null;
  }

/**
 * @param  $index
 * @param OpenSocial_Request $value
 * @return Service_OpenSocial_Client
 */
  public function offsetSet($index, $value) {
    if ($value instanceof Service_OpenSocial_Request) $this->requests[$index] = $value;
    return $this;
  }

/**
 * @param  $index
 * @return boolean
 */
  public function offsetExists($index) { return isset($this->requests[$index]); }

/**
 * @param  $index
 */
  public function offsetUnset($index) { unset($this->requests[$index]); }



/**
 * @param string $property
 * @return mixed
 */
  public function __get($property) {
    switch(true) {
      case property_exists($this, $property):
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
  public function __isset($property) { return isset($this->$property); }

/**
 * @param string $property
 */
  public function __unset($property) { throw new Core_ReadOnlyObjectException($this); }

}

/**
 * @abstract
 * @package Service\OpenSocial
 */
abstract class Service_OpenSocial_Format {

  public $name;
  public $content_type;


/**
 * @param string $name
 * @param string $content_type
 */
  public function __construct($name, $content_type) {
    $this->name = $name;
    $this->content_type = $content_type;
  }



/**
 * @abstract
 * @param  $object
 * @return string
 */
  abstract public function encode($object);

/**
 * @abstract
 * @param string $string
 * @return object
 */
  abstract public function decode($string);

}


/**
 * @abstract
 * @package Service\OpenSocial
 */
abstract class  Service_OpenSocial_Protocol {

  protected $agent;
  protected $auth;
  protected $format;



/**
 * @param Service_OpenSocial_AuthAdapter $auith
 * @param Service_OpenSocial_Format $format
 * @param Net_Agents_HTTP_Agent $agent
 */
  public function __construct(Service_OpenSocial_AuthAdapter $auth, Service_OpenSocial_Format $format, Net_Agents_HTTP_Agent $agent) {
    $this->auth   = $auth;
    $this->format = $format;
    $this->agent  = $agent;
  }



/**
 * @abstract
 * @param Service_OpenSocial_Container $container
 * @param array $requests
 * @return Service_OpenSocial_ResultSet
 */
  abstract public function send(Service_OpenSocial_Container $container, array $requests);




}



/**
 * @package Service\OpenSocial
 */
interface Service_OpenSocial_ResultInterface {}


/**
 * @package Service\OpenSocial
 */
class Service_OpenSocial_Error implements Service_OpenSocial_ResultInterface {

  public $code;
  public $message;
  public $info;


/**
 * @param Net_HTTP_Response $response
 * @return Service_OpenSocial_ResultInterface
 */
  static public function from_http_response(Net_HTTP_Response $response) {
    return new self($response->status->code, $response->status->message, $response->body);
  }

/**
 * @param int $code
 * @param string $message
 * @param string $info
 */
  public function __construct($code, $message = '', $info = '') {
    $this->code    = $code;
    $this->message = $message;
    $this->info    = $info;
  }

}

