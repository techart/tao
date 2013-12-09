<?php
/**
 * Service.OpenSocial.DSL
 * 
 * @package Service\OpenSocial\DSL
 */

/**
 * @package Service\OpenSocial\DSL
 */
class Service_OpenSocial_DSL implements Service_OpenSocial_ModuleInterface {


/**
 * @return Service_OpenSocial_DSL_ClientBuilder
 */
  static public function ClientBuilder() { return new Service_OpenSocial_DSL_ClientBuilder(); }

/**
 * @param Service_OpenSocial_Client $client
 * @return Service_OpenSocial_DSL_RequestBuilder
 */
  static public function RequestBuilder(Service_OpenSocial_Client $client) {
    return new Service_OpenSocial_DSL_RequestBuilder($client);
  }

}


/**
 * @package Service\OpenSocial\DSL
 */
class Service_OpenSocial_DSL_RequestBuilder {

  protected $client;
  protected $request;


/**
 * @param Service_OpenSocial_Client $client
 */
  public function __construct(Service_OpenSocial_Client $client) {
    $this->client = $client;
    $this->request = new Service_OpenSocial_Request();
  }



/**
 * @return Service_OpenSocial_DSL_RequestBuilder
 */
  public function __call($method, $args) {
    call_user_func_array(array($this->request, $method), $args);
    return $this;
  }

/**
 * @param string $property
 * @return Service_OpenSocial_Client
 */
  public function __get($property) {
    if ($property === 'end')
      return $this->client->request($this->request);
    else
      throw new Core_MissingPropertyException($property);
  }

}

/**
 * @package Service\OpenSocial\DSL
 */
class Service_OpenSocial_DSL_ClientBuilder {

  protected $protocol = array('RPC');
  protected $auth;
  protected $format   = array('JSON');
  protected $container;
  protected $agent;



/**
 * @param string $method
 * @param array $args
 * @return Service_OpenSocial_DSL_ClientBuilder
 */
  public function __call($method, $args) {
    if (property_exists($this, $method)) $this->$method = $args;
    return $this;
  }

/**
 * @param string $property
 * @return Service_OpenSocial_Client
 */
  public function __get($property) {
    if ($property === 'end')
      return $this->make_client();
    else
      throw new Core_MissingPropertyException($property);
  }

/**
 * @param Net_Agents_HTTP_Agent $agent
 * @return Service_OpenSocial_DSL_ClientBuilder
 */
  public function agent(Net_Agents_HTTP_Agent $agent) {
    $this->agent = $agent;
    return $this;
  }

/**
 * @param string $name
 * @param string $value
 * @return Sevice_OpenSocial_DSL_ClientBuilder
 */
  public function security_token($name, $value) {
    $this->auth = array('SecurityToken', $name, $value);
    return $this;
  }

/**
 * @return Service_OpenSocial_DSL_ClientBuilder
 */
  public function RPC() {
    $this->protocol = array('RPC');
    return $this;
  }

/**
 * @return Service_OpenSocial_DSL_ClientBuilder
 */
  public function REST() {
    $this->protocol = array('REST');
    return $this;
  }



/**
 * @return Service_OpenSocial_Client
 */
  protected function make_client() {
    return new Service_OpenSocial_Client($this->make_protocol(), $this->make_container());
  }

/**
 * @return Service_OpenSocial_Protocol
 */
  protected function make_protocol() {
    if ($this->protocol[0] instanceof Service_OpenSocial_Protocol)
      return $this->protocol[0];
    else {
      Core::load($m = 'Service.OpenSocial.Protocols.'.$this->protocol[0]);
      return Core::make("$m.Protocol", $this->make_auth(), $this->make_format(), $this->agent ? $this->agent : Net_HTTP::Agent());
    }
  }

/**
 * @return Service_OpenSocial_Format
 */
  protected function make_format() {
    if ($this->format[0] instanceof Service_OpenSocial_Format)
      return $this->format[0];
    else {
      Core::load($m = 'Service.OpenSocial.Formats.'.$this->format[0]);
      return Core::make("$m.Format", array_slice($this->format, 1));
    }
  }

/**
 * @return Service_OpenSocial_AuthAdapter
 */
  protected function make_auth() {
    if ($this->auth[0] instanceof Service_OpenSocial_AuthAdapter)
      return $this->auth[0];
    else {
      Core::load($m = 'Service.OpenSocial.Auth.'.$this->auth[0]);
      return Core::amake("$m.Adapter", array_slice($this->auth, 1));
    }
  }

/**
 * @return Service_OpenSocial_Container
 */
  protected function make_container() {
    if ($this->container[0] instanceof Service_OpenSocial_Container)
      return $this->container[0];
    else {
      Core::load($m = 'Service.OpenSocial.Containers.'.$this->container[0]);
      return call_user_func_array(array(str_replace('.', '_', $m), 'container'), array_slice($this->container, 1));
    }
  }

}

