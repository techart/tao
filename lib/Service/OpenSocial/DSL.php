<?php
/// <module name="Service.OpenSocial.DSL">

/// <class name="Service.OpenSocial.DSL" stereotype="module">
///   <interface name="Service.OpenSocial.ModuleInterface" />
///   <depends supplier="Service.OpenSocial.DSL.RequestBuilder" />
///   <depends supplier="Service.OpenSocial.DSL.ClientBuilder" />
class Service_OpenSocial_DSL implements Service_OpenSocial_ModuleInterface {

///   <protocol name="creating">

///   <method name="ClientBuilder" returns="Service.OpenSocial.DSL.ClientBuilder" scope="class">
///     <body>
  static public function ClientBuilder() { return new Service_OpenSocial_DSL_ClientBuilder(); }
///     </body>
///   </method>

///   <method name="RequestBuilder" returns="Service.OpenSocial.DSL.RequestBuilder"  scope="class">
///     <args>
///       <arg name="client" type="Service.OpenSocial.Client" />
///     </args>
///     <body>
  static public function RequestBuilder(Service_OpenSocial_Client $client) {
    return new Service_OpenSocial_DSL_RequestBuilder($client);
  }
///     </body>
///   </method>

///   </protocol>
}
/// </class>


/// <class name="Service.OpenSocial.DSL.RequestBuilder">
///   <depends supplier="Service.OpenSocial.Request" stereotype="creates" />
class Service_OpenSocial_DSL_RequestBuilder {

  protected $client;
  protected $request;

///   <protocol name="creating">

///   <method name="__construct">
///     <args>
///       <arg name="client" type="Service.OpenSocial.Client" />
///     </args>
///     <body>
  public function __construct(Service_OpenSocial_Client $client) {
    $this->client = $client;
    $this->request = new Service_OpenSocial_Request();
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="supporting">

///   <method name="__call" returns="Service.OpenSocial.DSL.RequestBuilder">
///     <args>
///     </args>
///     <body>
  public function __call($method, $args) {
    call_user_func_array(array($this->request, $method), $args);
    return $this;
  }
///     </body>
///   </method>

///   <method name="__get" returns="Service.OpenSocial.Client">
///     <args>
///       <arg name="property" type="string" />
///     </args>
///     <body>
  public function __get($property) {
    if ($property === 'end')
      return $this->client->request($this->request);
    else
      throw new Core_MissingPropertyException($property);
  }
///     </body>
///   </method>

///   </protocol>
}
/// </class>
/// <aggregation>
///   <source class="Service.OpenSocial.DSL.RequestBuilder" stereotype="builder" multiplicity="1" />
///   <target class="Service.OpenSocial.Client" stereotype="client" multiplicity="1" />
/// </aggregation>
/// <composition>
///   <source class="Service.OpenSocial.DSL.RequestBuilder" stereotype="builder" multiplicity="1" />
///   <target class="Service.OpenSocial.Request" stereotype="request" multiplicity="1" />
/// </composition>

/// <class name="Service.OpenSocial.DSL.ClientBuilder">
/// <depends supplier="Service.OpenSocial.Client" stereotype="creates" />
/// <depends supplier="Net.Agents.HTTP.Agent" stereotype="uses" />
class Service_OpenSocial_DSL_ClientBuilder {

  protected $protocol = array('RPC');
  protected $auth;
  protected $format   = array('JSON');
  protected $container;
  protected $agent;


///   <protocol name="performing">

///   <method name="__call" returns="Service.OpenSocial.DSL.ClientBuilder">
///     <args>
///       <arg name="method" type="string" />
///       <arg name="args" type="array" />
///     </args>
///     <body>
  public function __call($method, $args) {
    if (property_exists($this, $method)) $this->$method = $args;
    return $this;
  }
///     </body>
///   </method>

///   <method name="__get" returns="Service.OpenSocial.Client">
///     <args>
///       <arg name="property" type="string" />
///     </args>
///     <body>
  public function __get($property) {
    if ($property === 'end')
      return $this->make_client();
    else
      throw new Core_MissingPropertyException($property);
  }
///     </body>
///   </method>

///   <method name="agent" returns="Service.OpenSocial.DSL.ClientBuilder">
///     <args>
///       <arg name="agent" type="Net.Agents.HTTP.Agent" />
///     </args>
///     <body>
  public function agent(Net_Agents_HTTP_Agent $agent) {
    $this->agent = $agent;
    return $this;
  }
///     </body>
///   </method>

///   <method name="security_token" returns="Sevice.OpenSocial.DSL.ClientBuilder">
///     <args>
///       <arg name="name" type="string" />
///       <arg name="value" type="string" />
///     </args>
///     <body>
  public function security_token($name, $value) {
    $this->auth = array('SecurityToken', $name, $value);
    return $this;
  }
///     </body>
///   </method>

///   <method name="RPC" returns="Service.OpenSocial.DSL.ClientBuilder">
///     <body>
  public function RPC() {
    $this->protocol = array('RPC');
    return $this;
  }
///     </body>
///   </method>

///   <method name="REST" returns="Service.OpenSocial.DSL.ClientBuilder">
///     <body>
  public function REST() {
    $this->protocol = array('REST');
    return $this;
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="supporting">

///   <method name="make_client" returns="Service.OpenSocial.Client" access="protected">
///     <body>
  protected function make_client() {
    return new Service_OpenSocial_Client($this->make_protocol(), $this->make_container());
  }
///     </body>
///   </method>

///   <method name="make_protocol" returns="Service.OpenSocial.Protocol" access="protected">
///     <body>
  protected function make_protocol() {
    if ($this->protocol[0] instanceof Service_OpenSocial_Protocol)
      return $this->protocol[0];
    else {
      Core::load($m = 'Service.OpenSocial.Protocols.'.$this->protocol[0]);
      return Core::make("$m.Protocol", $this->make_auth(), $this->make_format(), $this->agent ? $this->agent : Net_HTTP::Agent());
    }
  }
///     </body>
///   </method>

///   <method name="make_format" returns="Service.OpenSocial.Format" access="protected">
///     <body>
  protected function make_format() {
    if ($this->format[0] instanceof Service_OpenSocial_Format)
      return $this->format[0];
    else {
      Core::load($m = 'Service.OpenSocial.Formats.'.$this->format[0]);
      return Core::make("$m.Format", array_slice($this->format, 1));
    }
  }
///     </body>
///   </method>

///   <method name="make_auth" returns="Service.OpenSocial.AuthAdapter" access="protected">
///     <body>
  protected function make_auth() {
    if ($this->auth[0] instanceof Service_OpenSocial_AuthAdapter)
      return $this->auth[0];
    else {
      Core::load($m = 'Service.OpenSocial.Auth.'.$this->auth[0]);
      return Core::amake("$m.Adapter", array_slice($this->auth, 1));
    }
  }
///     </body>
///   </method>

///   <method name="make_container" returns="Service.OpenSocial.Container" access="protected">
///     <body>
  protected function make_container() {
    if ($this->container[0] instanceof Service_OpenSocial_Container)
      return $this->container[0];
    else {
      Core::load($m = 'Service.OpenSocial.Containers.'.$this->container[0]);
      return call_user_func_array(array(str_replace('.', '_', $m), 'container'), array_slice($this->container, 1));
    }
  }
///     </body>
///   </method>

///   </protocol>
}
/// </class>

/// </module>
