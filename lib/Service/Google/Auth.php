<?php
/// <module name="Service.Google.Auth" maintainer="svistunov@techart.ru" version="0.1.0">
Core::load('Net.Agents.HTTP');

/// <class name="Service.Google.Auth" stereotype="module">
///   <implements interface="Core.ModuleInterface" />
class Service_Google_Auth implements Core_ModuleInterface {
///   <constants>
  const VERSION = '0.1.0';

  const AUTHSUB_REQUEST_URI = 'https://www.google.com/accounts/AuthSubRequest';
  const AUTHSUB_SESSION_TOKEN_URI = 'https://www.google.com/accounts/AuthSubSessionToken';
  const AUTHSUB_TOKEN_INFO_URI    = 'https://www.google.com/accounts/AuthSubTokenInfo';
  const AUTHSUB_REVOKE_TOKEN_URI  = 'https://www.google.com/accounts/AuthSubRevokeToken';


  const CLIENTLOGIN_URI = 'https://www.google.com/accounts/ClientLogin';
  const DEFAULT_SOURCE = 'Techart-TAO';
///   </constants>

///   <protocol name="building">

///   <method name="ClientLogin" scope="class">
///     <body>
  static public function ClientLogin() {
    return new Service_Google_Auth_ClientLogin();
  }
///     </body>
///   </method>

///   </protocol>
}
/// </class>

/// <class name="Service.Google.Auth.Exception" extends="Core.Exception">
class Service_Google_Auth_Exception extends Core_Exception {}
/// </class>

/// <class name="Service.Google.Auth.ClientLogin">
///   <implements interface="Core.PropertyAccessInterface" />
class Service_Google_Auth_ClientLogin implements Core_PropertyAccessInterface {

  protected $token;
  protected $error;

  protected $agent;

  protected $parameters = array(
    'Email' => '',
    'Passwd' => '',
    'service' => 'xapi',
    'accountType' => 'HOSTED_OR_GOOGLE',
    'source' => Service_Google_Auth::DEFAULT_SOURCE
  );

  private $method_to_parameters = array(
    'email' => 'Email',
    'password' => 'Passwd',
    'service' => 'service',
    'account_type' => 'accountType',
    'source' => 'source'
  );

///   <protocol name="creating">

///   <method name="__construct">
///     <body>
  public function __construct(array $parameters = array()) {
    if ($parameters) $this->parameters($parameters);
    $this->agent = Net_HTTP::Agent();
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="configuring">

///   <method name="parameter">
///     <body>
  public function parameter($name, $value) {
    $this->parameters(array($name => $value));
    return $this;
  }
///     </body>
///   </method>

///   <method name="parameters">
///     <body>
  public function parameters(array $parameters) {
    Core_Arrays::update($this->parameters, $parameters);
    return $this;
  }
///     </body>
///   </method>

///   <method name="agent">
///     <args>
///       <arg name="agent" type="Net_HTTP_AgentInterface" />
///     </args>
///     <body>
  public function agent(Net_HTTP_AgentInterface $agent) {
    $this->agent = $agent;
    return $this;
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="calling">

///   <method name="__call">
///     <args>
///       <arg name="method" type="string" />
///       <arg name="args" type="array" />
///     </args>
///     <body>
  public function __call($method, $args) {
    if (isset($this->method_to_parameters[$method])) {
      $this->parameters(array($this->method_to_parameters[$method] => $args[0]));
      return $this;
    } else throw new Core_MissingMethodException($method);
  }
///     </body>
////  </method>

///   </protocol>

///   <protocol name="performing" >

///   <method name="login" returns="boolean">
///     <args>
///       <arg name="email" type="string" />
///       <arg name="password" type="string" />
///       <arg name="service" type="string" default="'xapi'" />
///       <arg name="source" type="string" default="Service_Google_Auth::DEFAULT_SOURCE" />
///       <arg name="account_type" type="HOSTED_OR_GOOGLE" />
///     </args>
///     <body>
  public function login($email = null, $password = null) {
    foreach (array('email' => $email, 'password' => $password) as $k => $v)
      if ($v) $this->$k($v);
    $request = Net_HTTP::Request(Service_Google_Auth::CLIENTLOGIN_URI)->
      method(Net_HTTP::POST)->
      parameters($this->parameters);

    $response = $this->agent->send($request);

    if ($response->status->code !== 200)
      $this->error = $this->get_value($response, 'Error');
    else
      $this->token = $this->get_value($response, 'Auth');
    return $this;
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="supporting">

///   <method name="get_value" access="protected" returns="string|null">
///     <args>
///       <arg name="res" type="Net.HTTP.Response" />
///       <arg name="name" type="string" />
///     </args>
///     <body>
  protected function get_value(Net_HTTP_Response $res, $name) {
    return
      ($m = Core_Regexps::match_with_results('{'.$name.'=([^\n]*)}i', $res->body)) ?
      $m[1] :
      null;
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
      case 'error':
      case 'token' :
        return $this->$property;
      default :
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
    throw new Core_ReadOnlyObjectException($this);
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
      case 'error':
      case 'token' :
        return isset($this->$property);
      default :
        throw new Core_MissingPropertyException($property);
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
    throw new Core_ReadOnlyObjectException($this);
  }
///     </body>
///   </method>

///   </protocol>

}
/// </class>

/// </module>
