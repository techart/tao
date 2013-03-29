<?php
/// <module name="Service.OAuth" maintainer="svistunov@techart.ru" version="0.1.0">
///   <breif>Модуль для работы с OAuth авторизацией</brief>
Core::load('Net.HTTP', 'Cache');

/// <class name="Service.OAuth" stereotype="module">
///   <implements interface="Core.ModuleInterface" />
class Service_OAuth implements Core_ModuleInterface {
///   <constants>
  const VERSION = '0.1.0';
///   </constants>

///   <protocol name="builder">

///   <method name="Client" scope="static" returns="Service.OAuth.Client">
///     <body>
  static public function Client() {
    $args = func_get_args();
    return Core::amake('Service.OAuth.Client', $args);
  }
///     </body>
///   </method>

  static public function SessionStore() {
    $args = func_get_args();
    return Core::amake('Service.OAuth.SessionStore', $args);
  }

///   <method name="RequestBuilder" scope="static" returns="Service.OAuth.RequestBuilder" >
///     <body>
  static public function RequestBuilder() {
    $args = func_get_args();
    return Core::amake('Service.OAuth.RequestBuilder', $args);
  }
///     </body>
///   </method>

///   <method name="HMACSHA1" returns="Service.OAuth.HMACSHA1" >
///     <body>
  static public function HMACSHA1() {
    $args = func_get_args();
    return Core::amake('Service.OAuth.HMACSHA1', $args);
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="support">

///   <method name="urlencode_rfc3986" scope="static" returns="string">
///     <args>
///       <arg name="input" type="string" />
///     </args>
///     <body>
  public static function urlencode_rfc3986($input) {
    switch (true) {
      case is_array($input):
        return array_map(array('Service_OAuth', 'urlencode_rfc3986'), $input);
      case is_scalar($input):
        return str_replace('+', ' ',
          str_replace('%7E', '~', rawurlencode($input)));
      default:
        return '';
    }
  }
///     </body>
///   </method>

///   <method name="urldecode_rfc3986" scope="static" returns="string">
///     <args>
///       <arg name="string" type="string" />
///     </args>
///     <body>
  public static function urldecode_rfc3986($string) {
    return urldecode($string);
  }
///     </body>
///   </method>

///   <method name="parse_parameters" scope="static" returns="array">
///     <args>
///       <arg name="input" type="string" />
///     </args>
///   <body>
  public static function parse_parameters($input, $delim = '&') {
    if (!isset($input) || !$input) return array();
    $pairs = explode($delim, $input);
    $parsed_parameters = array();
    foreach ($pairs as $pair) {
      $split = explode('=', $pair, 2);
      $parameter = self::urldecode_rfc3986($split[0]);
      $value = isset($split[1]) ? self::urldecode_rfc3986($split[1]) : '';
      if (isset($parsed_parameters[$parameter])) {
        if (is_scalar($parsed_parameters[$parameter]))
          $parsed_parameters[$parameter] = array($parsed_parameters[$parameter]);
        $parsed_parameters[$parameter][] = $value;
      }
      else
        $parsed_parameters[$parameter] = $value;
    }
    return $parsed_parameters;
  }
///     </body>
///   </method>

///   <method name="build_http_query" scope="static">
///     <args>
///       <arg name="params" type="array" />
///     </args>
///     <body>
  public static function build_http_query($params) {
    if (!$params) return '';
    $keys = Service_OAuth::urlencode_rfc3986(array_keys($params));
    $values = Service_OAuth::urlencode_rfc3986(array_values($params));
    $params = array_combine($keys, $values);
    uksort($params, 'strcmp');
    $pairs = array();
    foreach ($params as $parameter => $value) {
      if (is_array($value)) {
        natsort($value);
        foreach ($value as $duplicate_value) {
          $pairs[] = $parameter . '=' . $duplicate_value;
        }
      } else {
        $pairs[] = $parameter . '=' . $value;
      }
    }
    return implode('&', $pairs);
  }
///     </body>
///   </method>

///   </protocol>
}
/// </class>

/// <class name="Service.OAuth.Exception" extends="Core.Exception">
class Service_OAuth_Exception extends Core_Exception {
///   <protocol name="creating">

///   <method name="__construct">
///     <args>
///       <arg name="message" type="string" />
///       <arg name="code" type="int" defaults="null" />
///     </args>
///     <body>
  public function __construct ($message, $code = null) {
    $this->message = $message instanceof Net_HTTP_Response ?
      $this->message_for_response($message) : (string) $message;
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="supporting" >

///   <method name="message_for_response" returns="string">
///     <args>
///       <arg name="res" type="Net.HTTP.Response" />
///     </args>
///     <body>
  protected function message_for_response(Net_HTTP_Response $res) {
    return sprintf('%d:%s:%s', $res->status->code, $res->status->message, $res->body);
  }
///     </body>
///   </method>

///   </protocol>
}
/// </class>

/// <class name="Service.OAuth.SessionStore" extends="Cache.Backend">
class Service_OAuth_SessionStore extends Cache_Backend {

  protected $session;
  
  public function __construct() {
    Core::load('Net.HTTP.Session');
    $this->session = Net_HTTP_Session::Store();
  }
  
	public function flush() {
		
	}
  

///   <method name="get" returns="mixed" stereotype="abstract">
///     <brief>Возвращает значение по ключу, если значение не установлено возвращает $default</brief>
///     <args>
///       <arg name="key" type="string" brief="ключ" />
///       <arg name="default" default="null" brief="значение по умолчанию" />
///     </args>
///     <body>
  public function get($key, $default = null) {
    return isset($this->session[$key]) ? unserialize($this->session[$key]) : $default;
  }
///     </body>
///   </method>

///   <method name="set" returns="boolean" stereotype="abstract">
///     <brief>Устанавливает значение по ключу с заданным таймаутом или с таймаутом по умолчанию</brief>
///     <args>
///       <arg name="key" type="string" brief="ключ" />
///       <arg name="value" brief="значение" />
///       <arg name="timeout" type="int" brief="время в течении которого хранится значение в кэше (сек)" />
///     </args>
///     <body>
  public function set($key, $value, $timeout = null) {
    $this->session[$key] = serialize($value);
    return $this;
  }
///     </body>
///   </method>

///   <method name="delete" returns="boolean" stereotype="abstract">
///     <brief>Удалят значение из кэша</brief>
///     <args>
///       <arg name="key" type="string" brief="ключ" />
///     </args>
///     <body>
  public function delete($key) {
    unset($this->session[$key]);
    return $this;
  }
///     </body>
///   </method>

///   <method name="has" returns="boolean">
///     <brief>Проверяет есть ли занчение с ключом $key в кэше</brief>
///     <args>
///       <arg name="key" type="string" brief="ключ" />
///     </args>
///     <body>
  public function has($key) {
    return isset($this->session[$key]);
  }
///     </body>
///   </method>

///   </protocol>
  
}
/// </class>

/// <class name="Service.OAuth.RequestBuilder">
///   <implements interface="Core.PropertyAccessInterface" />
///   <implements interface="Core.IndexedAccessInterface" />
///   <implements interface="IteratorAggregate" />
///   <implements interface="Core.CallInterface" />
class Service_OAuth_RequestBuilder implements
  Core_PropertyAccessInterface, Core_IndexedAccessInterface, IteratorAggregate,
  Core_CallInterface {

  protected $parameters = array();
  protected $request;
  protected $consumer;
  protected $token;
  static protected $version = '1.0';

///   <protocol name="creating">

///   <method name="__construct">
///     <args>
///       <arg name="request" type="Net.HTTP.Request" />
///       <arg name="consumer" type="array" defaults="array()" />
///       <arg name="token" type="array" defaults="array()" />
///       <arg name="parameters" type="array" defaults="array()" />
///     </args>
///     <body>
  public function __construct($request = null, array $consumer = array(), array $token = array(), $parameters = array()) {
    if ($request instanceof Net_HTTP_Request) {
      $this->request = $request;
      $this->add_parameters($request->parameters);
    }
    $defaults = $this->create_defaults();
    $this->set_consumer($consumer);
    $this->set_token($token);
    $this->add_parameters(array_merge($defaults, $parameters));
  }
///     </body>
///   </method>

  protected function create_defaults() {
    return array("oauth_version"   => Service_OAuth_RequestBuilder::$version,
                 "oauth_nonce"     => $this->generate_nonce(),
                 "oauth_timestamp" => $this->generate_timestamp());
  }

///   </protocol>

///   <protocol name="performing">

///   <method name="to_headers" returns="Service.OAuth.RequestBuilder" >
///     <args>
///       <arg name="realm" type="string" default="''" />
///     </args>
///     <body>
  public function to_headers($realm = '') {
    $values = ($realm) ?
      array(sprintf('OAuth realm="%s"', self::urlencode_rfc3986($realm))) :
      array('OAuth');
    foreach ($this->parameters as $k => $v) {
      if (substr($k, 0, 5) != "oauth") continue;
      if (is_array($v))
        throw new Service_OAuth_Exception('Arrays not supported in headers');
      $values[] = sprintf('%s="%s"', Service_OAuth::urlencode_rfc3986($k), Service_OAuth::urlencode_rfc3986($v));
    }
    $this->request->header('Authorization', implode(',', $values));
    return $this;
  }
///     </body>
///   </method>

///   <method name="to_parameters" returns="Service_OAuth.RequestBuilder">
///     <body>
  public function to_parameters() {
    $this->request->parameters($this->parameters);
    return $this;
  }
///     </body>
///   </method>

///   <method name="to_query" returns="Service.OAuth.RequestBuilder">
///     <body>
  public function to_query() {
    $this->request->query = Service_OAuth::build_http_query($this->parameters);
    return $this;
  }
///     </body>
///   </method>

///   <method name="" returns="Service.OAuth.RequestBuilder" >
///     <args>
///       <arg name="signature_method" type="Service.OAuth.SignatureMethod" />
///     </args>
///     <body>
  public function sign(Service_OAuth_SignatureMethod $signature_method) {
    $this->
      add_parameter(
        'oauth_signature_method', $signature_method->get_name(), false
      )->
      add_parameter(
        'oauth_signature', $signature_method->build_signature($this), false
      );
    return $this;
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
    switch(true) {
      case method_exists($this, $m = 'get_'.$property):
        return $this->$m();
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
///       <arg name="property" type="string" />
///       <arg name="value" />
///     </args>
///     <body>
  public function __set($property, $value) {
      switch(true) {
      case method_exists($this, $m = 'set_'.$property):
        return $this->$m($value);
      case $property == 'request':
        if ($value instanceof Net_HTTP_Request) $this->$property = $value;
        return $this;
      case in_array($property,'parameter', 'parameters'):
        $this->{'add_'.$property}($value);
        return $this;
      default:
        throw new Core_MissingPropertyException($property);
    }
  }
///     </body>
///   </method>

///   <method name="__isset" returns="boolean">
///     <args>
///       <arg name="property" type="string" />
///     </args>
///     <body>
  public function __isset($property) {
    return (boolean) $this->__get($property);
  }
///     </body>
///   </method>

///   <method name="__unset">
///     <args>
///       <arg name="property" type="string" />
///     </args>
///     <body>
  public function __unset($property) {
    return $this->__set($property, null);
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="indexing" interface="Core.IndexedPropertyAccessInterface">

///   <method name="offsetGet" returns="mixed">
///     <args>
///       <arg name="index" />
///     </args>
///     <body>
  public function offsetGet($index) {
    return $this->parameters[$index];
  }
///     </body>
///   </method>

///   <method name="offsetSet" returns="mixed">
///     <args>
///       <arg name="index" />
///       <arg name="value" />
///     </args>
///     <body>
  public function offsetSet($index, $value) {
    return $this->add_parameter($index, $value);
  }
///     </body>
///   </method>

///   <method name="offsetExists" returns="boolean">
///     <args>
///       <arg name="index" />
///     </args>
///     <body>
  public function offsetExists($index) {
    return isset($this->parameters[$index]);
  }
///     </body>
///   </method>

///   <method name="offsetUnset">
///     <args>
///       <arg name="index" />
///     </args>
///     <body>
  public function offsetUnset($index) {
    unset($this->parameters[$index]);
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
    $this->__set($method, $args[0]);
    return $this;
  }
///     </body>
////  </method>

///   </protocol>

///   <protocol name="iterating" interface="IteratorAggregate">

///   <method name="getIterator" returns="Iterator">
///     <body>
  public function getIterator() {
    return new ArrayIterator($this->parameters);
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="supporting">
//TODO: get_* сделать propected
///   <method name="get_signature_base_string" returns="string" >
///     <body>
  public function get_signature_base_string() {
    $parts = array(
      $this->get_normalized_http_method(),
      $this->get_normalized_http_url(),
      $this->get_signable_parameters()
    );
    $parts = Service_OAuth::urlencode_rfc3986($parts);
    return implode('&', $parts);
  }
///     </body>
///   </method>

///   <method name="get_normalized_http_method" returns="string">
///     <body>
  public function get_normalized_http_method() {
    return strtoupper($this->request->method_name);
  }
///     </body>
///   </method>

///   <method name="get_normalized_http_url" returns="string">
///     <body>
  public function get_normalized_http_url() {
    //TODO: http port
    return sprintf('%s://%s%s',
      $this->request->scheme,
      $this->request->host,
      $this->request->path
    );
  }
///     </body>
///   </method>

///   <method name="get_signable_parameters" returns="string">
///     <body>
  public function get_signable_parameters() {
    $params = $this->parameters;
    if (isset($params['oauth_signature'])) {
      unset($params['oauth_signature']);
    }
    return Service_OAuth::build_http_query($params);
  }
///     </body>
///   </method>

///   <method name="generate_timestamp" access="private" returns="int">
///     <body>
  private function generate_timestamp() {
    return time();
  }
///     </body>
///   </method>

///   <method name="generate_nonce" access="private">
///     <body>
  private function generate_nonce() {
    return md5(microtime().mt_rand());
  }
///     </body>
///   </method>

///   <method name="add_parameter" returns="Service.OAuth.SignatureMethod">
///     <args>
///       <arg name="name" type="string" />
///       <arg name="value" type="mixed" />
///       <arg name="allow_duplicates" type="boolean" default="true" />
///     </args>
///     <body>
  public function add_parameter($name, $value, $allow_duplicates = true) {
    if ($allow_duplicates && isset($this->parameters[$name])) {
      if (is_scalar($this->parameters[$name]))
        $this->parameters[$name] = array($this->parameters[$name]);
      $this->parameters[$name][] = $value;
    } else
      $this->parameters[$name] = $value;
    return $this;
  }
///     </body>
///   </method>

///   <method name="add_parameters" returns="Service.OAuth.SignatureMethod">
///     <args>
///       <arg name="params" type="array" />
///       <arg name="allow_duplicates" type="boolean" default="true" />
///     </args>
///     <body>
  public function add_parameters(array $params, $allow_duplicates = true) {
    foreach ($params as $name => $value)
      $this->add_parameter($name, $value, $allow_duplicates);
    return $this;
  }
///     </body>
///   </method>

///   <method name="set_consumer" returns="Service.OAuth.RequestBuilder">
///     <args>
///       <arg name="consumer" type="array" defaults="array()" />
///     </args>
///     <body>
  public function set_consumer(array $consumer = array()) {
    if (isset($consumer['key'])) {
      $this->consumer = $consumer;
      $this->add_parameter('oauth_consumer_key', $consumer['key']);
    }
    return $this;
  }
///     </body>
///   </method>

///   <method name="set_token" returns="Service.OAuth.RequestBuilder">
///     <args>
///       <arg name="token" type="array" defaults="array()" />
///     </args>
///     <body>
  public function set_token(array $token = array()) {
    if (isset($token['oauth_token'])) {
      $this->token = $token;
      $this->add_parameter('oauth_token', $token['oauth_token']);
    }
    return $this;
  }
///     </body>
///   </method>

///   </protocol>
}
/// </class>


class Service_OAuth_RequestBuilder2 extends Service_OAuth_RequestBuilder {

  protected function create_defaults() {
    return array();
  }

  public function sign(Service_OAuth_SignatureMethod $signature_method) {
    return $this;
  }
  
  public function set_consumer(array $consumer = array()) {
    return $this;
  }
  
  public function set_token(array $token = array()) {
    if (isset($token['access_token']) || isset($token['oauth_token']))
      return $this->add_parameter('access_token', $token['access_token'] ? $token['access_token'] : $token['oauth_token']);
    return $this;
  }
  
}

/// <class name="Service.OAuth.SignatureMethod" stereotype="absract">
abstract class Service_OAuth_SignatureMethod {
//TODO: AccessInterface to name

///   <protocol name="performing">

///   <method name="get_name" returns="string" >
///     <body>
  abstract public function get_name();
///      </body>
///   </method>

///   <method name="build_signature">
///     <args>
///       <arg name="builder" type="Service.OAuth.RequestBuilder" />
///     </args>
///     <body>
  abstract public function build_signature(Service_OAuth_RequestBuilder $builder);
///     </body>
///   </method>

///   <method name="check_signature" >
///     <args>
///       <arg name="builder" type="Service.OAuth.RequestBuilder" />
///       <arg name="signature" type="string" />
///     </args>
///     <body>
  public function check_signature($builder, $signature) {
    return $this->build_signature($builder) == $signature;
  }
///     </body>
///   </method>

///   </protocol>
}
/// </class>

/// <class name="Service.OAuth.HMACSHA1" extends="Service.OAuth.SignatureMethod" >
class Service_OAuth_HMACSHA1 extends Service_OAuth_SignatureMethod {

///   <protocol name="creating">

///   <method name="__construct">
///     <body>
  public function __construct() {

  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="performing" >

///   <method name="get_name" returns="string" >
///     <body>
  function get_name() {
    return "HMAC-SHA1";
  }
///      </body>
///   </method>

///   <method name="build_signature">
///     <args>
///       <arg name="builder" type="Service.OAuth.RequestBuilder" />
///     </args>
///     <body>
  public function build_signature(Service_OAuth_RequestBuilder $builder) {
    $base_string = $builder->signature_base_string;
    $key_parts = array(
      $builder->consumer['secret'],
      isset($builder->token) ? $builder->token['oauth_token_secret'] : ""
    );
    $key_parts = Service_OAuth::urlencode_rfc3986($key_parts);
    $key = implode('&', $key_parts);
    return base64_encode(hash_hmac('sha1', $base_string, $key, true));
  }
///      </body>
///   </method>

///   </protocol>
}
/// </class>

/// <class name="Service.OAuth.PLAINTEXT" extends="Service.OAuth.SignatureMethod" >
class Service_OAuth_PLAINTEXT extends Service_OAuth_SignatureMethod {

///   <protocol name="performing" >

///   <method name="get_name" returns="string" >
///     <body>
  public function get_name() {
    return "PLAINTEXT";
  }
///      </body>
///   </method>

///   <method name="build_signature">
///     <args>
///       <arg name="builder" type="Service.OAuth.RequestBuilder" />
///     </args>
///     <body>
  public function build_signature(Service_OAuth_RequestBuilder $builder) {
    $key_parts = array(
      $builder->consumer['secret'],
      isset($builder->token) ? $builder->token['oauth_token_secret'] : ""
    );
    $key_parts = Service_OAuth::urlencode_rfc3986($key_parts);
    $key = implode('&', $key_parts);
    return $key;
  }
///      </body>
///   </method>

///   </protocol>
}
/// </class>

/// <class name="Service.OAuth.RSASHA1" extends="Service.OAuth.SignatureMethod" stereotype="abstract" >
abstract class Service_OAuth_RSASHA1 extends Service_OAuth_SignatureMethod {

///   <protocol name="performing" >

///   <method name="get_name" returns="string" >
///     <body>
  public function get_name() {
    return "RSA-SHA1";
  }
///      </body>
///   </method>

///   <method name="fetch_public_cert" returns="string" stereotype="abstract" >
///     <args>
///       <arg name="builder" type="Service.OAuth.RequestBuilder" />
///     </args>
///     <body>
  protected abstract function fetch_public_cert(Service_OAuth_RequestBuilder $builder);
///      </body>
///   </method>

///   <method name="fetch_public_cert" returns="string" stereotype="abstract" >
///     <args>
///       <arg name="builder" type="Service.OAuth.RequestBuilder" />
///     </args>
///     <body>
  protected abstract function fetch_private_cert(Service_OAuth_RequestBuilder $builder);
///      </body>
///   </method>

///   <method name="build_signature">
///     <args>
///       <arg name="builder" type="Service.OAuth.RequestBuilder" />
///     </args>
///     <body>
  public function build_signature(Service_OAuth_RequestBuilder $builder) {
    $base_string = $rbuilder->signature_base_string;
    $cert = $this->fetch_private_cert($builder);
    $privatekeyid = openssl_get_privatekey($cert);
    $ok = openssl_sign($base_string, $signature, $privatekeyid);
    openssl_free_key($privatekeyid);
    return base64_encode($signature);
  }
///      </body>
///   </method>

///   <method name="check_signature" returns="string" stereotype="abstract" >
///     <args>
///       <arg name="builder" type="Service.OAuth.RequestBuilder" />
///       <arg name="signature" type="string" />
///     </args>
///     <body>
  public function check_signature(Service_OAuth_RequestBuilder $builder, $signature) {
    $decoded_sig = base64_decode($signature);
    $base_string = $builder->signature_base_string;
    $cert = $this->fetch_public_cert($rbuilder);
    $publickeyid = openssl_get_publickey($cert);
    $ok = openssl_verify($base_string, $decoded_sig, $publickeyid);
    openssl_free_key($publickeyid);
    return $ok == 1;
  }
///      </body>
///   </method>

///   </protocol>
}
/// </class>

/// <class name="Service.OAuth.Client">
///   <implements interface="Core.PropertyAccessInterface" />
///   <implements interface="Core.CallInterface" />
class Service_OAuth_Client implements Core_PropertyAccessInterface, Core_CallInterface {
  protected $last_response;
  protected $options = array(
    'request_token_url' => '',
    'access_token_url' => '',
    'authorize_url' => '',
    'authenticate_url' => '',
    'login_method' => '3legged',
    'sign_method' => '',
    'callback_url' => '',
    'auth_params' => array(),
    'version' => '1.0'
  );
  protected $consumer = array();
  protected $token = array();
  protected $store;
  protected $parameters = array();
  protected $proxy = array();

///   <protocol name="creating">

///   <method name="__construct">
///     <args>
///       <arg name="store" type="Cache.Backend" />
///       <arg name="consumer" type="array" />
///     </args>
///     <body>
//TODO: вместо Cache_Backend нечто своё
  public function __construct($store = null, array $consumer = array(),
     Service_OAuth_SignatureMethod $sign_method = null) {
    $this->store = $store;
    $this->consumer = $consumer;
    $this->options['sign_method'] = Service_OAuth::HMACSHA1();
  }
///     </body>
///   </method>

  public function token_key($value) {
    $this->token['oauth_token'] = $value;
    return $this;
  }

  public function token_secret($value) {
    $this->token['oauth_token_secret'] = $value;
    return $this;
  }

  public function is_version($v) {
    preg_match('{^(\d+)\.}', $this->option('version'), $m);
    return $m[1] == $v;
  }

///   </protocol>

///   <protocol name="configure">

///   <method name="using_proxy">
///     <body>
  public function using_proxy() {
    $this->proxy = func_get_args();
    return $this;
  }
///     </body>
///   </method>

///   <method name="add_parametres">
///     <args>
///       <arg name="parms" type="array" defaults="array()" />
///     </args>
///     <body>
  public function add_parametres(array $params = array()) {
    $this->parameters = array_merge($this->parametes, $params);
    return $this;
  }
///     </body>
///   </method>

///   <method name="option" returns="Service.OAuth.Client">
///     <args>
///       <arg name="name" type="string" />
///       <arg name="value" type="string" />
///     </args>
///     <body>
  public function option($name, $value = null) {
    if (is_null($value)) return $this->options[$name];
    if (isset($this->options[$name]))
      $this->options[$name] = $value;
    return $this;
  }
///     </body>
///   </method>

///   <method name="options" returns="Service.OAuth.Client">
///     <args>
///       <arg name="values" type="array" />
///     </args>
///     <body>
  public function options(array $values) {
    foreach($values as $name => $value)
      $this->option($name, $value);
    return $this;
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="performing">
//TODO: перенсти в константы имена ключей 'access_token' ...

///   <method name="is_logged_in" returns="boolean" >
///     <body>
  public function is_logged_in() {
    $r = $this->store ? $this->store->has('access_token') : true;
    if ($r && !$this->token) $this->load_token();
    if(!$r && ($req = $this->store->get('requestor_id'))) {
      $this->add_parametres(array('xoauth_requestor_id' => $req));
      $r = true;
    }
    return $r;
  }
///     </body>
///   </method>

///   <method name="logout" returns="boolean" >
///     <body>
  public function logout() {
    return $this->store->delete('access_token');
  }
///     </body>
///   </method>

///   <method name="login_2legged" returns="boolean">
///     <args>
///       <arg name="params" type="array" defaults="array()" />
///     </args>
///     <body>
  public function login_2legged(array $params = array()) {
    switch(true) {
      case isset($params['username']) && isset($params['password']):
        $this->token = $this->get_xauth_token($username, $password);
        $this->store->set('access_token', $this->token);
        return true;
      case isset($params['requestor_id']):
        $this->add_parametres(array('xoauth_requestor_id' => $params['requestor_id']));
        $this->store->set('requestor_id', $params['requestor_id']);
        return true;
      default:
        return false;
    }
  }
///     </body>
///   </method>

///   <method name="login_3legged_redirect" returns="Net.HTTP.Response" >
///     <args>
///       <arg name="callback" type="string" defaults="''" />
///       <arg name="authorize" type="boolean" defaults="true" />
///     </args>
///     <body>
  public function login_3legged_redirect($callback = '', $authorize = true) {
    $callback = $this->callback_url ? $this->callback_url : $callback;
    if ($this->is_version(1)) {
      $request_token = $this->get_request_token($callback);
      $this->store->set('request_token', $request_token);
    }
    $redirect_url = $this->get_auth_url($callback, $authorize);
    return Net_HTTP::redirect_to($redirect_url);
  }
///     </body>
///   </method>

///   <method name="login_3legged_confirm" returns="true">
///     <args>
///       <arg name="request" type="Net.HTTP.Request" />
///     </args>
///     <body>
  public function login_3legged_confirm(Net_HTTP_Request $request) {
    if ($this->is_version(1)) {
      $this->token = $this->store->get('request_token');
      if ($request['oauth_token'] != $this->token['oauth_token'])
        return false;
    } else {
      $this->token = array('oauth_token' => isset($request['code']) ? $request['code'] : $request['access_token']);
    }
    $this->token = $this->get_access_token($request['oauth_verifier']);
    $this->store->set('access_token', $this->token);
    return true;
  }
///     </body>
///   </method>

///   <method name="send" returns="Net.HTTP.Response">
///     <args>
///       <arg name="request" type="Net.HTTP.Request" />
///       <arg name="parameters" type="array" defaults="array()" />
///       <arg name="to_headers" type="boolean" defaults="false" />
///     </args>
///     <body>
  public function send(Net_HTTP_Request $request, array $parameters = array(),
    $to_headers = false) {
    $agent = Net_HTTP::Agent();
    if (count($this->proxy) > 0) $agent->using_proxy(
      $this->proxy[0], $this->proxy[1], $this->proxy[2]);
    $this->last_response = Net_HTTP::Agent()->
      send($this->build_request($request, array_merge($this->parameters, $parameters), $to_headers));
    if ($this->last_response->status->is_server_error)
      throw new Service_OAuth_Exception($this->last_response);
    else
      return $this->last_response;
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="supporting">

///   <method name="get_auth_url" returns="string">
///     <args>
///       <arg name="callback" type="string" defaults="''" />
///       <arg name="authorize" type="boolean" defaults="true" />
///     </args>
///     <body>
  public function get_auth_url($callback = '', $authorize = true) {
    $url = $this->options[$authorize ? 'authorize_url' : 'authenticate_url'];
    $params = $this->is_version(1) 
      ? array(
          'oauth_token' => $this->token['oauth_token'],
          'oauth_callback' => $callback
        )
      : array(
          'client_id' => $this->consumer['key'],
          'redirect_uri' => $callback
        );
    $params = array_merge($params, $this->option('auth_params'));
    $q = '';
    foreach ($params as $k => $v) $q .= $k . '=' . urlencode($v) . '&';
    return $url . '?' . $q;
  }
///     </body>
///   </method>

///   <method name="get_request_token" returns="array" >
///     <args>
///       <arg name="callback" type="string" defaults="''" />
///     </args>
///     <body>
  public function get_request_token($callback = '') {
    return $this->get_token(
      'request_token_url',
      $callback ? array('oauth_callback' => $callback) : array(),
      'GET'
    );
  }
///     </body>
///   </method>

///   <method name="get_access_token" returns="array" >
///     <args>
///       <arg name="verifier" type="string" defaults="''" />
///     </args>
///     <body>
  public function get_access_token($verifier = '') {
    $url = $this->option('access_token_url');
    if (empty($url)) return $this->token;
    $params = $verifier ? array('oauth_verifier' => $verifier) : array();
    if ($this->is_version(2))
      $params = array_merge($params, array(
        'client_id' => $this->consumer['key'],
        'redirect_uri' => $this->callback_url,
        'client_secret' => $this->consumer['secret'],
        'code' => $this->token['oauth_token']
      ));
    return $this->get_token('access_token_url', $params);
  }
///     </body>
///   </method>

///   <method name="get_xauth_token" returns="array" >
///     <args>
///       <arg name="username" type="string" />
///       <arg name="password" type="string" />
///     </args>
///     <body>
  public function get_xauth_token($username, $password) {
    return $this->get_token('access_token_url',
      array(
        'x_auth_username' => $username,
        'x_auth_password' => $password,
        'x_auth_mode' => 'client_auth'
    ));
  }
///     </body>
///   </method>

///   <method name="get_token" returns="array" access="protected" >
///     <args>
///       <arg name="url_name" type="string" />
///       <arg name="parameters" type="array" defaults="array()" />
///       <arg name="method" type="string" defaults="'POST'" />
///     </args>
///     <body>
  protected function get_token($url_name, array $parameters = array(), $method = 'POST') {
    $this->send(
      Net_HTTP::Request($this->options[$url_name])->method($method),
      $parameters,
      $method == 'POST'
    );
    $json = json_decode($this->last_response->body, true);
    return !empty($json) ? $json : $this->token = Service_OAuth::parse_parameters($this->last_response->body);
  }
///     </body>
///   </method>

///   <method name="build_request" returns="Net.HTTP.Request" access="protected" >
///     <args>
///       <arg name="request" type="Net.HTTP.Request" />
///       <arg name="parameters" type="array" defaults="array()" />
///       <arg name="to_headers" type="boolean" defaults="false" />
///     </args>
///     <body>
  protected function build_request(Net_HTTP_Request $request, array $parameters = array(), $to_headers = false) {
    $builder = $this->is_version(1) ? Service_OAuth::RequestBuilder($request)
      : new Service_OAuth_RequestBuilder2($request);
    $builder->
      consumer($this->consumer)->
      token($this->token)->
      add_parameters($parameters)->
      sign($this->options['sign_method'])->
      to_parameters();
    if ($to_headers) $builder->to_headers();
    return $builder->request;
  }
///     </body>
///   </method>

///   <method name="save_token" returns="boolean" >
///     <args>
///       <arg name="name" type="string" defaults="'access_token'" />
///     </args>
///     <body>
  public function save_token($name = 'access_token') {
    return $this->store->set($name, $this->token);
  }
///     </body>
///   </method>

  public function flush_store() {
    foreach (array('access_token', 'request_token', 'requestor_id') as $k) $this->store->delete($k);
    return $this;
  }

///   <method name="load_token" returns="boolean" >
///     <args>
///       <arg name="name" type="string" defaults="'access_token'" />
///     </args>
///     <body>
  public function load_token($name = 'access_token') {
    return $this->token = $this->store->get($name);
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
    switch(true) {
    case in_array($property, array('store', 'consumer', 'token', 'options')):
        return $this->$property;
      case isset($this->options[$property]):
        return $this->options[$property];
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
      switch(true) {
      case in_array($property, array('token', 'options')):
        throw new Core_ReadOnlyPropertyException($property);
      case $property == 'store' && $value instanceof Cache_Backend:
        $this->$property = $value;
        return $this;
      case $property == 'consumer' &&
           is_array($value) &&
           isset($value['key']) &&
           isset($value['secret']):
        $this->$property = $value;
        return $this;
      case $property == 'consumer_key':
        $this->consumer['key'] = (string) $value;
        return $this;
      case $property == 'consumer_secret':
        $this->consumer['secret'] = (string) $value;
        return $this;
      case isset($this->options[$property]):
        return $this->option($property, $value);
      default:
        throw new Core_MissingPropertyException($property);
    }
  }
///     </body>
///   </method>

///   <method name="__isset" returns="boolean">
///     <args>
///       <arg name="property" type="string" />
///     </args>
///     <body>
  public function __isset($property) {
    return ins_array($property, array('store', 'consumer', 'token', 'options')) ||
      isset($this->options[$property]);
  }
///     </body>
///   </method>

///   <method name="__unset">
///     <args>
///       <arg name="property" type="string" />
///     </args>
///     <body>
  public function __unset($property) {
    return $this->__set($property, null);
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
    $this->__set($method, $args[0]);
    return $this;
  }
///     </body>
////  </method>

///   </protocol>
}
/// </class>

/// </module>
