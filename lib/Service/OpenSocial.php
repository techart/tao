<?php
/// <module name="Service.OpenSocial" version="0.1.1" maintainer="svistunov@techart.ru">
/// <brief>OpenSocial-клиент</brief>

/// <interface name="Service.OpenSocial.ModuleInterface" extends="Core.ModuleInterface">
///   <brief>Библиотека модулей OpenSocial</brief>
interface Service_OpenSocial_ModuleInterface extends Core_ModuleInterface {
///   <constants>
  const VERSION = '0.1.1';
///   </constants>
}
/// </interface>

Core::load('Net.HTTP', 'Object', 'Service.OpenSocial.DSL');

/// <class name="Service.OpenSocial" stereotype="module">
///   <brief>Модуль Service.OpenSocial</brief>
///   <implements interface="Service.OpenSocial.ModuleInterface" />
///   <depends supplier="Object" stereotype="uses" />
///   <depends supplier="Net.HTTP" stereotype="uses" />
///   <depends supplier="Service.OpenSocial.DSL" stereotype="uses" />
///   <depends supplier="Service.OpenSocial.Client" stereotype="creates" />
///   <depends supplier="Service.OpenSocial.DSL.ClientBuilder" stereotype="creates" />
///   <depends supplier="Service.OpenSocial.Container" stereotype="creates" />
///   <depends supplier="Service.OpenSocial.Request" stereotype="creates" />
///   <depends supplier="Service.OpenSocial.Person" stereotype="creates" />
///   <depends supplier="Service.OpenSocial.Group" stereotype="creates" />
///   <depends supplier="Service.OpenSocial.Activity" stereotype="creates" />
///   <depends supplier="Service.OpenSocial.AppData" stereotype="creates" />
class Service_OpenSocial implements Service_OpenSocial_ModuleInterface {

///   <protocol name="building">

///   <method name="Client" returns="Service.OpenSocial.Client|Service.OpenSocial.DSL.ClientBuilder" scope="class">
///     <body>
  static public function Client() {
    return count($args = func_get_args()) ?
      new Service_OpenSocial_Client($args[0], isset($args[1]) ? $args[1] : null) :
      new Service_OpenSocial_DSL_ClientBuilder();
  }
///     </body>
///   </method>

///   <method name="Container" returns="Service.OpenSocial.Container" scope="class">
///     <args>
///       <arg name="options" type="array" />
///     </args>
///     <body>
  static public function Container(array $options) { return new Service_OpenSocial_Container($options); }
///     </body>
///   </method>

///   <method name="Request" returns="Service.OpenSocial.Request" scope="class">
///     <brief>Создает объект класса Service.OpenSocial.Request</brief>
///     <body>
  static public function Request() { return new Service_OpenSocial_Request(); }
///     </body>
///   </method>

///   <method name="Person" returns="Service.OpenSocial.Person" scope="class">
///     <args>
///       <arg name="values" type="array" default="array()" brief="массив значение ресурса" />
///     </args>
///     <body>
  static public function Person(array $values = array()) { return new Service_OpenSocial_Person($values); }
///     </body>
///   </method>

///   <method name="Group" returns="Service.OpenSocial.Group" scope="class">
///     <brief>Создает объект класса Service.OpenSocial.Group</brief>
///     <args>
///       <arg name="values" type="array" default="array()" brief="массив значение ресурса" />
///     </args>
///     <body>
  static public function Group(array $values = array()) { return new Service_OpenSocial_Group($values); }
///     </body>
///   </method>

///   <method name="Activity" returns="Service.OpenSocial.Activity" scope="class">
///     <args>
///       <arg name="values" type="array" default="array()" brief="массив значение ресурса" />
///     </args>
///     <body>
  static public function Activity(array $values = array()) { return new Service_OpenSocial_Activity($values); }
///     </body>
///   </method>
///   <method name="AppData" returns="Service.OpenSocial.AppData" scope="class">
///     <args>
///       <arg name="values" type="array" default="array()" brief="массив значение ресурса" />
///     </args>
///     <body>
  static public function AppData(array $values = array()) { return new Service_OpenSocial_AppData($values); }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="supporting">

///   <method name="canonicalize" returns="string" access="private">
///     <args>
///       <arg name="name" type="string" />
///     </args>
///     <body>
  public function canonicalize($name) {
    static $names = array();
    if (!isset($names[$name]))
      $names[$name] = Core_Strings::to_camel_case($name, true);
    return $names[$name];
  }
///     </body>
///   </method>

///   </protocol>
}
/// </class>


/// <class name="Service.OpenSocial.Exception" extends="Core.Exception" stereotype="exception">
class Service_OpenSocial_Exception extends Core_Exception {}
/// </class>


/// <class name="Service.OpenSocial.Container" extends="Object.Struct">
class Service_OpenSocial_Container extends Object_Struct {

  protected $name                   = '';
  protected $request_token_endpoint = '';
  protected $authorize_endpoint     = '';
  protected $access_token_endpoint  = '';
  protected $rest_endpoint          = '';
  protected $rpc_endpoint           = '';
  protected $use_method_override    = true;
  protected $use_request_body_hash  = true;

///   <protocol name="creating">

///   <method name="__construct">
///     <args>
///       <arg name="options" type="array" default="array()" brief="значения опций" />"
///     </args>
///     <body>
  public function __construct(array $options = array()) {
    foreach ($options as $k => $v) $this->__set($k, $v);
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="supporting">

///   <method name="set_use_requet_body_hash" access="protected">
///     <args>
///       <arg name="value" type="boolean" />
///     </args>
///     <body>
  protected function set_use_request_body_hash($value) { $this->use_request_body_hash = (boolean) $value; }
///     </body>
///   </method>

///   <method name="set_use_requet_body_hash" access="protected">
///     <args>
///       <arg name="value" type="boolean" />
///     </args>
///     <body>
  protected function set_use_method_override($value) { $this->use_method_override = (boolean) $value; }
///     </body>
///   </method>

///   </protocol>
}
/// </class>


/// <class name="Service.OpenSocial.Operation" extends="Object.Const">
///   <depends supplier="Net.HTTP" stereotype="uses" />
class Service_OpenSocial_Operation extends Object_Const {

///   <constants>
  const GET    = 'get';
  const CREATE = 'create';
  const DELETE = 'delete';
  const UPDATE = 'update';
///   </constants>

  protected $http_method;

///   <protocol name="creating">

///   <method name="__construct">
///     <args>
///       <arg name="name" type="string" brief="строковое имя" />
///       <arg name="http_method" type="int" brief="HTTP-метод" />
///     </args>
///     <body>
  protected function __construct($name, $http_method) {
    $this->http_method = $http_method;
    parent::__construct($name);
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="supporting">

///   <method name="get_http_method_name" returns="string">
///     <body>
  protected function get_http_method_name() { return Net_HTTP::method_name_for($this->http_method); }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="building">

///   <method name="object" returns="Service.OpenSocial.Operation" scope="class">
///     <args>
///       <arg name="constant" brief="константа типа Service.OpenSocial.Operation" />
///     </args>
///     <body>
  static public function object($constant) { return self::object_for('Service_OpenSocial_Operation', $constant); }
///     </body>
///   </method>

///   <method name="GET" returns="Service.OpenSocial.Operation" scope="class">
///     <body>
  static public function GET() { return new Service_OpenSocial_Operation(self::GET, Net_HTTP::GET); }
///     </body>
///   </method>

///   <method name="CREATE" returns="Service.OpenSocial.Operation" scope="class">
///     <body>
  static public function CREATE() { return new Service_OpenSocial_Operation(self::CREATE, Net_HTTP::POST); }
///     </body>
///   </method>

///   <method name="UPDATE" returns="Service.OpenSocial.Operation" scope="class">
///     <body>
  static public function UPDATE() { return new Service_OpenSocial_Operation(self::UPDATE, Net_HTTP::PUT); }
///     </body>
///   </method>

///   <method name="DELETE" returns="Service.OpenSocial.Operation" scope="class">
///     <body>
  static public  function DELETE() { return new Service_OpenSocial_Operation(self::DELETE, Net_HTTP::DELETE); }
///     </body>
///   </method>

///   </protocol>
}
/// </class>


/// <class name="Service.OpenSocial.Service" extends="Object.Const">
///   <depends supplier="Service.OpenSocial.Person" stereotype="creates" />
///   <depends supplier="Service.OpenSocial.Group" stereotype="creates" />
///   <depends supplier="Service.OpenSocial.Activity" stereotype="creates" />
///   <depends supplier="Service.OpenSocial.AppData" stereotype="creates" />
class Service_OpenSocial_Service extends Object_Const {

///   <constants>
  const PEOPLE     = 'people';
  const GROUP      = 'group';
  const ACTIVITIES = 'activities';
  const APPDATA    = 'appdata';
///   </constants>

  protected $resource_class;

///   <protocol name="building">

///   <method name="object" returns="OpenSocial.Service" scope="class">
///     <args>
///       <arg name="constant" type="OpenSocial.Service|string" brief="константа типа OpenSocial.Service" />
///     </args>
///     <body>
  static public function object($constant) { return self::object_for('Service_OpenSocial_Service', $constant); }
///     </body>
///   </method>

///   <method name="PEOPLE" returns="OpenSocial.Service" scope="class">
///     <body>
  static public function PEOPLE() { return new Service_OpenSocial_Service(self::PEOPLE); }
///     </body>
///   </method>

///   <method name="GROUP" returns="OpenSocial.Service" scope="class">
///     <body>
  static public function GROUP() { return new Service_OpenSocial_Service(self::GROUP); }
///     </body>
///   </method>

///   <method name="ACTIVITY" returns="OpenSocial.Service" scope="class">
///     <body>
  static public function ACTIVITIES() { return new Service_OpenSocial_Service(self::ACTIVITIES); }
///     </body>
///   </method>

///   <method name="APPDATA" returns="OpenSocial.Service" scope="class">
///     <body>
  static public function APPDATA() { return new Service_OpenSocial_Service(self::APPDATA); }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="supporting">

///   <method name="get_rpc_name" returns="string" access="protected">
///     <body>
   protected function get_rpc_name() {
     switch ($this->value) {
       case 'activities' : return 'activity';
       case 'appdata'    : return 'data';
       default           : return $this->value;
     }
   }
///     </body>
///   </method>

///   <method name="make_resource_for" returns="Service.OpenSocial.Resource">
///     <args>
///       <arg name="data" />
///     </args>
///     <body>
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
///     </body>
///   </method>


///   </protocol>
}
/// </class>


/// <class name="Service.OpenSocial.Collection" extends="ArrayObject">
///   <implements interface="Core.PropertyAccessInterface" />
///   <implements interface="Service.OpenSocial.ResultInterface" />
class Service_OpenSocial_Collection
  extends ArrayObject
  implements Core_PropertyAccessInterface,
             Service_OpenSocial_ResultInterface {

  protected $items_per_page = 0;
  protected $total_results  = 0;
  protected $start_index    = 0;

///   <protocol name="creating">

///   <method name="__construct">
///     <args>
///       <arg name="total_results"  type="int" />
///       <arg name="items_per_page" type="int" />
///       <arg name="start_index"    type="int" />
///     </args>
///     <body>
  public function __construct($total_results, $items_per_page, $start_index) {
    $this->total_results  = $total_results;
    $this->items_per_page = $items_per_page;
    $this->start_index    = $start_index;
  }
///     </body>
///   </method>

///   </protocol>


///   <protocol name="changing">

///   <method name="append" returns="Service.OpenSocial.Collection">
///     <args>
///       <arg name="resource" type="Service.OpenSocial.Resource" brief="ресурс" />
///     </args>
///     <body>
  public function append($resource) {
    if ($resource instanceof Service_OpenSocial_Resource) {
      parent::append($resource);}
    else
      throw Service_OpenSocial_Exception("bad resource");
    return $this;
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="changing">

///   <method name="__get" returns="mixed">
///     <args>
///       <arg name="property" type="string" brief="имя свойства" />
///     </args>
///     <body>
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
///     </body>
///   </method>

///   <method name="__set" returns="OpenSocial.Collection">
///     <args>
///       <arg name="property" type="string" brief="имя свойства" />
///       <arg name="value" brief="значение" />
///     </args>
///     <body>
  public function __set($property, $value) { throw new Core_ReadOnlyObjectException($this); }
///     </body>
///   </method>

///   <method name="__isset" returns="boolean">
///     <args>
///       <arg name="property" type="string" brief="имя свойства" />
///     </args>
///     <body>
  public function __isset($property) { return isset($this->$property); }
///     </body>
///   </method>

///   <method name="__unset">
///     <args>
///       <arg name="property" type="string" brief="имя свойства" />
///     </args>
///     <body>
  public function __unset($property) { throw new Core_ReadOnlyObjectException($this); }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="indexing" interface="Core.IndexedAccessInterface">

///   <method name="offsetSet">
///     <args>
///       <arg name="index" type="int" brief="индекс" />
///       <arg name="value" brief="значение" />
///     </args>
///     <body>
  public function offsetSet($index, $value) {
    if (!is_null($index))
      throw new Core_ReadOnlyObjectException($this);
    else
      parent::offsetSet($index, $value);
  }
///     </body>
///   </method>

///   <method name="offsetUnset">
///     <args>
///       <arg name="index" type="index" brief="индекс" />
///     </args>
///     <body>
  public function offsetUnset($index) { throw new Core_ReadOnlyObjectException($this); }
///     </body>
///   </method>

///   </protocol>
}
/// </class>
/// <aggregation>
///   <source class="Service.OpenSocial.Collection" stereotype="collection" multiplicity="1" />
///   <target class="Service.OpenSocial.Resource" stereotype="items" multiplicity="N" />
/// </aggregation>

/// <class name="Service.OpenSocial.ResultSet" extends="ArrayObject">
class Service_OpenSocial_ResultSet extends ArrayObject {

///   <protocol name="accessing">

///   <method name="offsetSet">
///     <args>
///       <arg name="index" type="string" />
///       <arg name="retuls" type="Service.OpenSocial.ResultInterface" />
///     </args>
///     <body>
  public function offsetSet($index, $result) { $this->result($index, $result); }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="supporting">

///   <method name="result" returns="Service.OpenSocial.ResultSet">
///     <args>
///       <arg name="index" type="string" />
///       <arg name="result" type="Service.OpenSocial.ResultInterface" />
///     </args>
///     <body>
  public function result($index, Service_OpenSocial_ResultInterface $result) {
    parent::offsetSet($index, $result);
    return $this;
  }
///     </body>
///   </method>

///   <method name="append">
///     <args>
///       <arg name="value" />
///     </args>
///     <body>
  final public function append($value) { throw  new Service_OpenSocial_Exception('Use explicit index to add result'); }
///     </body>
///   </method>

///   </protocol>
}
/// </class>
/// <aggregation>
///   <source class="Service.OpenSocial.ResultSet" stereotype="collection" multiplicity="1" />
///   <target class="Service.OpenSocial.ResultInterface" stereotype="item" multiplicity="N" />
/// </aggregation>

/// <class name="Service.OpenSocial.Resource" extends="ArrayObject">
///   <implements interface="Core.PropertyAccessInterface" />
///   <implements interface="Core.CallInterface" />
///   <implements interface="Service.OpenSocial.ResultInterface" />
class Service_OpenSocial_Resource
  extends ArrayObject
  implements Core_PropertyAccessInterface,
             Core_CallInterface,
             Service_OpenSocial_ResultInterface {

///   <protocol name="creating">

///   <method name="__construct">
///     <args>
///       <arg name="values" default="array()" />
///     </args>
///     <body>
  public function __construct($values = array()) {
    foreach ($values as $k => $v) $this[$k] = $v;
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="accessing" interface="Core.PropertyAccessInterface">

///   <method name="__get" returns="mixed">
///     <args>
///       <arg name="Property" type="string" />
///     </args>
///     <body>
  public function __get($property) {
    return method_exists($this, $m = "get_$property") ?
      $this->$m() :
      (isset($this[$p = Service_OpenSocial::canonicalize($property)]) ?
        $this[$p] : null);
  }
///     </body>
///   </method>

///   <method name="__set" returns="Service.OpenSocial.Resource">
///     <args>
///       <arg name="property" type="string" />
///       <arg name="value" />
///     </args>
///     <body>
  public function __set($property, $value) {
    method_exists($this, $m = "set_$property") ?
      $this->$m($value) : $this[Service_OpenSocial::canonicalize($property)] = $value;
    return $this;
  }
///     </body>
///   </method>

///   <method name="__isset" returns="Service.OpenSocial.Resource">
///     <args>
///       <arg name="property" type="string" />
///     </args>
///     <body>
  public function __isset($property) { return isset($this[Service_OpenSocial::canonicalize($property)]); }
///     </body>
///   </method>

///   <method name="__unset" returns="Service.OpenSocial.Resource">
///     <args>
///       <arg name="property" type="string" />
///     </args>
///     <body>
    public function __unset($property) {
      if (isset($this[$p = Service_OpenSocial::canonicalize($property)])) unset($this[$p]);
      return $this;
    }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="calling" interface="Core.CallInterface">

///   <method name="__call" returns="Service.OpenSocial.Resource">
///     <args>
///       <arg name="method" type="string" />
///       <arg name="args" type="array" />
///     </args>
///     <body>
  public function __call($method, $args) {
    $this->__set($method, $args[0]);
    return $this;
  }
///     </body>
///   </method>

///   </protocol>

}
/// </class>


/// <class name="Service.OpenSocial.Person" extends="Service.OpenSocial.Resource">
class Service_OpenSocial_Person extends Service_OpenSocial_Resource {
}
/// </class>


/// <class name="Service.OpenSocial.Group" extends="Service.OpenSocial.Resource">
class Service_OpenSocial_Group extends Service_OpenSocial_Resource {
}
/// </class>


/// <class name="Service.OpenSocial.Activity" extends="Service.OpenSocial.Resource">
class Service_OpenSocial_Activity extends Service_OpenSocial_Resource {
}
/// </class>


/// <class name="Service.OpenSocial.AppData" extends="Service.OpenSocial.Resource">
class Service_OpenSocial_AppData extends Service_OpenSocial_Resource {
}
/// </class>


/// <class name="Service.OpenSocial.Request">
///   <implements interface="Core.CallInterface" />
///   <implements interface="Core.PropertyAccessInterface" />
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

///   <protocol name="accessing">

///   <method name="__get" returns="mixed">
///     <args>
///       <arg name="property" type="string" />
///     </args>
///     <body>
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
///     </body>
///   </method>

///   <method name="__set" returns="Service.OpenSocial.Request">
///     <args>
///       <arg name="property" type="string" />
///       <arg name="value" />
///     </args>
///     <body>
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
///     </body>
///   </method>

///   <method name="__isset" returns="boolean">
///     <args>
///       <arg name="property" />
///     </args>
///     <body>
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
///     </body>
///   </method>

///   <method name="__unset" returns="Service.OpenSocial.Request">
///     <args>
///       <arg name="property" />
///     </args>
///     <body>
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
///     </body>
///   </method>


///   </protocol>

///   <protocol name="changing">

///   <method name="resource" returns="Service.OpenSocial.Request">
///     <args>
///       <arg name="resource" type="Service.OpenSocial.Resource" />
///     </args>
///     <body>
  public function resource(Service_OpenSocial_Resource $resource) {
    $this->resource = $resource;
    return $this;
  }
///     </body>
///   </method>


///   <method name="params" returns="Service.OpenSocial.Request">
///     <args>
///       <arg name="params" />
///     </args>
///     <body>
  public function params($params) {
    foreach ($params as $k => $v) $this->params[$k] = (string) $v;
    return $this;
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="calling" interface="Core.CallInterface">

///   <method name="__call" returns="mixed">
///     <args>
///       <arg name="method" type="string" />
///       <arg name="args" type="array" />
///     </args>
///     <body>
  public function __call($method, $args) { return $this->__set($method, $args[0]); }
///     </body>
///   </method>

///   </protocol>
}
/// </class>
/// <composition>
///   <source class="Service.OpenSocial.Request" stereotype="request" multiplicity="1" />
///   <target class="Service.OpenSocial.Operation" stereotype="operation" multiplicity="1" />
/// </composition>
/// <composition>
///   <source class="Service.OpenSocial.Request" stereotype="request" multiplicity="1" />
///   <target class="Service.OpenSocial.Service" stereotype="operation" multiplicity="1" />
/// </composition>


/// <class name="Service.OpenSocial.AuthAdapter" stereotype="abstract">
///   <details>Успользуется для правильного построения запроса с учетом аутентификации</details>
///   <implements interface="Core.CallInterface" />
///   <depends supplier="Net.HTTP.Request" stereotype="modifies" />
abstract class Service_OpenSocial_AuthAdapter implements Core_CallInterface {

  protected $options;

///   <protocol name="creating">

///   <method name="__construct">
///     <brief>Конструктор</brief>
///     <args>
///       <arg name="options" type="array" default="array()" brief="массив опций" />
///     </args>
///     <body>
  public function __construct(array $options = array()) { $this->options = $options; }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="building">

///   <method name="build" returns="Net.HTTP.Request">
///     <args>
///       <arg name="options" type="array" brief="массив опций" />
///       <arg name="container" type="OpenSocial.Container" brief="контаунер" />
///     </args>
///     <body>
  abstract public function authorize_request(Net_HTTP_Request $request, Service_OpenSocial_Container $container);
///     </body>
///   </method>

///   </protocol>

///   <protocol name="calling" interface="Core.CallInterface">

///   <method name="__call" returns="OpenSocial.HTTPRequestBuilder">
///     <args>
///       <arg name="method" type="string" brief="имя опции" />
///       <arg name="args"   type="array" brief="значение" />
///     </args>
///     <body>
  public function __call($method, $args) {
    if (array_key_exists($method, $this->options))
      $this->options[$method] = $args[0];
    else
      throw new Core_MissingMethodException($method);
    return $this;
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="supporting">

///   <method name="to_camel_case">
///     <args>
///       <arg name="array" type="" brief="массив" />
///     </args>
///     <body>
  protected function canonicalize($array) {
    $res = array();
    foreach($array as $k => $v) $res[Service_OpenSocial::canonicalize($k)] = $v;
    return $res;
  }
///     </body>
///   </method>

///   </protocol>
}
/// </class>


/// <class name="Service.OpenSocial.Client">
///   <implements interface="Code.IndexedAccessInterface" />
///   <implements interface="Core.PropertyAccessInterface" />
///   <depends supplier="Service.OpenSocial.DSL.RequestBuilder" stereotype="creates" />
class Service_OpenSocial_Client
  implements Core_IndexedAccessInterface,
             Core_PropertyAccessInterface {

  protected $container;
  protected $protocol;

  protected $requests;
  protected $request_counter = 0;

///   <protocol name="creating">

///   <method name="__construct">
///     <args>
///       <arg name="protocol" type="Service.OpenSocial.Protocol" brief="протокол" />
///       <arg name="container" type="Service.OpenSOcial.Container|null" brief="контейнер" />
///     </args>
///     <body>
  public function __construct(Service_OpenSocial_Protocol $protocol, Service_OpenSocial_Container $container) {
    $this->protocol  = $protocol;
    $this->container = $container;
    $this->reset();
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="performing">

///   <method name="request" returns="Service.OpenSocial.Client">
///     <args>
///       <arg name="request" type="OpenSocial.Request" brief="запрос" />
///       <arg name="id"      type="string" default="''" brief="идентификатор запроса" />
///     </args>
///     <body>
  public function request() {
    $args = func_get_args();

    if (count($args) == 0)
      return new Service_OpenSocial_DSL_RequestBuilder($this);
    else
      return $this->append_request($args[0], isset($args[1]) ? $args[1] : null);
  }
///     </body>
///   </method>

///   <method name="requests" returns="OpenSocial.Client">
///     <args>
///       <arg name="values" type="array" brief="массив запросов" />
///     </args>
///     <body>
  public function requests() {
    foreach (Core::normalize_args(func_get_args()) as $id => $request)
      $this->request($request, is_int($id) ? null : $id);
    return $this;
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="processing" >

///   <method name="send" returns="OpenSocial.ResultSet">
///     <body>
  public function send() {
    if ($this->request_counter == 0)
      throw new Service_OpenSocial_Exception('No requests');

    $result = $this->protocol->send($this->container, $this->requests);
    $this->reset();
    return $result;
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="supporting">

///   <method name="reset" returns="OpenSocial.Client">
///     <body>
  protected function reset() {
    $this->requests = array();
    $this->request_counter = 0;
    return $this;
  }
///     </body>
///   </method>

///   <method name="append_request" returns="Service.OpenSocial.Client">
///     <args>
///       <arg name="request" type="Service.OpenSocial.Request" />
///       <arg name="id" default="null" />
///     </args>
///     <body>
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
///     </body>
///   </method>

///   </protocol>

///   <protocol name="indexing" interface="Core.IndexedPropertyAccessInterface">

///   <method name="offsetGet" returns="Service.OpenSocial.Request">
///     <args>
///       <arg name="index" brief="идентификатор" />
///     </args>
///     <body>
  public function offsetGet($index) {
    return isset($this->requests[$index]) ? $this->requests[$index] : null;
  }
///     </body>
///   </method>

///   <method name="offsetSet" returns="Service.OpenSocial.Client">
///     <args>
///       <arg name="index" brief="идентификатор" />
///       <arg name="value" type="OpenSocial.Request" brief="запрос" />
///     </args>
///     <body>
  public function offsetSet($index, $value) {
    if ($value instanceof Service_OpenSocial_Request) $this->requests[$index] = $value;
    return $this;
  }
///     </body>
///   </method>

///   <method name="offsetExists" returns="boolean">
///     <args>
///       <arg name="index" brief="идентификатор" />
///     </args>
///     <body>
  public function offsetExists($index) { return isset($this->requests[$index]); }
///     </body>
///   </method>

///   <method name="offsetUnset">
///     <args>
///       <arg name="index" brief="идентификатор" />
///     </args>
///     <body>
  public function offsetUnset($index) { unset($this->requests[$index]); }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="accessing">

///   <method name="__get" returns="mixed">
///     <args>
///       <arg name="property" type="string" brief="имя свойства" />
///     </args>
///     <body>
  public function __get($property) {
    switch(true) {
      case property_exists($this, $property):
        return $this->$property;
      default:
        throw new Core_MissingPropertyException($property);
    }
  }
///     </body>
///   </method>

///   <method name="__set" returns="mixed">
///     <args>
///       <arg name="property" type="string" brief="имя свойства" />
///       <arg name="value" brief="значение" />
///     </args>
///     <body>
  public function __set($property, $value) { throw new Core_ReadOnlyObjectException($this); }
///     </body>
///   </method>

///   <method name="__isset" returns="boolean">
///     <args>
///       <arg name="property" type="string" brief="имя свойства" />
///     </args>
///     <body>
  public function __isset($property) { return isset($this->$property); }
///     </body>
///   </method>

///   <method name="__unset">
///     <args>
///       <arg name="property" type="string" brief="имя свойства" />
///     </args>
///     <body>
  public function __unset($property) { throw new Core_ReadOnlyObjectException($this); }
///     </body>
///   </method>

///   </protocol>
}
/// </class>
/// <aggregation>
///   <source class="Service.OpenSocial.Client" stereotype="client" multiplicity="1" />
///   <target class="Service.OpenSocial.Protocol" stereotype="protocol" multiplicity="1" />
/// </aggregation>
/// <aggregation>
///   <source class="Service.OpenSocial.Client" stereotype="client" multiplicity="1" />
///   <target class="Service.OpenSocial.Container" stereotype="container" multiplicity="1" />
/// </aggregation>
/// <aggregation>
///   <source class="Service.OpenSocial.Client" stereotype="client" multiplicity="1" />
///   <target class="Service.OpenSocial.Request" stereotype="requests" multiplicity="N" />
/// </aggregation>

/// <class name="Service.OpenSocial.Format" stereotype="abstract">
abstract class Service_OpenSocial_Format {

  public $name;
  public $content_type;

///   <protocol name="creating">

///   <method name="__construct">
///     <args>
///       <arg name="name" type="string" />
///       <arg name="content_type" type="string" />
///     </args>
///     <body>
  public function __construct($name, $content_type) {
    $this->name = $name;
    $this->content_type = $content_type;
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="processing">

///   <method name="encode" returns="string" stereotype="abstract">
///     <args>
///       <arg name="object" />
///     </args>
///     <body>
  abstract public function encode($object);
///     </body>
///   </method>

///   <method name="decode" returns="object" stereotype="abstract">
///     <args>
///       <arg name="string" type="string" />
///     </args>
///     <body>
  abstract public function decode($string);
///     </body>
///   </method>

///   </protocol>
}
/// </class>


/// <class name="Service.OpenSocial.Protocol" stereotype="abstract">
///   <depends supplier="Service.OpenSocial.Container" stereotype="uses" />
///   <depends supplier="Service.OpenSocial.Request" stereotype="uses" />
///   <depends supplier="Service.OpenSocial.ResultSet" stereotype="creates" />
abstract class  Service_OpenSocial_Protocol {

  protected $agent;
  protected $auth;
  protected $format;


///   <protocol name="creating">

///   <method name="__construct">
///     <args>
///       <arg name="auith" type="Service.OpenSocial.AuthAdapter" />
///       <arg name="format" type="Service.OpenSocial.Format" />
///       <arg name="agent"  type="Net.Agents.HTTP.Agent" />
///     </args>
///     <body>
  public function __construct(Service_OpenSocial_AuthAdapter $auth, Service_OpenSocial_Format $format, Net_Agents_HTTP_Agent $agent) {
    $this->auth   = $auth;
    $this->format = $format;
    $this->agent  = $agent;
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="performing">

///   <method name="send" stereotype="abstract" returns="Service.OpenSocial.ResultSet">
///     <args>
///       <arg name="container" type="Service.OpenSocial.Container" />
///       <arg name="requests"  type="array" />
///     </args>
///     <body>
  abstract public function send(Service_OpenSocial_Container $container, array $requests);
///     </body>
///   </method>

///   </protocol>

///   <protocol name="supporting">


///   </protocol>
}
/// </class>
/// <aggregation>
///   <source class="Service.OpenSocial.Protocol" stereotype="protocol" multiplicity="1" />
///   <target class="Service.OpenSocial.AuthAdapter" stereotype="authentication adapter" multiplicity="1" />
/// </aggregation>
/// <aggregation>
///   <source class="Service.OpenSocial.Protocol" stereotype="protocol" multiplicity="1" />
///   <target class="Service.OpenSocial.Format" stereotype="format" multiplicity="1" />
/// </aggregation>
/// <aggregation>
///   <source class="Service.OpenSocial.Protocol" stereotype="protocol" multiplicity="1" />
///   <target class="Net.Agents.HTTP.Agent" stereotype="agent" multiplicity="1" />
/// </aggregation>



/// <interface name="Service.OpenSocial.ResultInterface">
interface Service_OpenSocial_ResultInterface {}
/// </interface>


/// <class name="Service.OpenSocial.Error">
///   <implements interface="Service.OpenSocial.ResultInterface" />
class Service_OpenSocial_Error implements Service_OpenSocial_ResultInterface {

  public $code;
  public $message;
  public $info;

///   <protocol name="creating">

///   <method name="from_http_response" returns="Service.OpenSocial.ResultInterface" scope="class">
///     <args>
///       <arg name="response" type="Net.HTTP.Response" />
///     </args>
///     <body>
  static public function from_http_response(Net_HTTP_Response $response) {
    return new self($response->status->code, $response->status->message, $response->body);
  }
///     </body>
///   </method>

///   <method name="__construct">
///     <args>
///       <arg name="code" type="int" />
///       <arg name="message" type="string" default="''" />
///       <arg name="info" type="string" default="''" />
///     </args>
///     <body>
  public function __construct($code, $message = '', $info = '') {
    $this->code    = $code;
    $this->message = $message;
    $this->info    = $info;
  }
///     </body>
///   </method>

///   </protocol>
}
/// </class>

/// </module>
