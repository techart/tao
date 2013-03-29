<?php
/// <module name="OpenId" maintainer="svistunov@techart.ru" version="0.1.0">
Core::load('Net.HTTP', 'XML');

/// <class name="OpenId" stereotype="module">
///   <implements interface="Core.ModuleInterface" />
class OpenId implements Core_ModuleInterface {
///   <constants>
  const VERSION = '0.1.0';
///   </constants>

///   <protocol name="building">

///   <method name="Client" scope="static" returns="OpenId.Client">
///     <args>
///       <arg name="request" type="Net.HTTP.Request" />
///       <arg name="agent" type="mixed" default = null />
///     </args>
///     <body>
  static function Client($request = null, $agent = null) {
    return new  OpenId_Client($request, $agent);
  }
///     </body>
///   </method>

///   <method name="Version1" scope="static" return="OpenId.Version1">
///     <body>
  static function Version1() {
    return new OpenId_Version1();
  }
///     </body>
///   </method>

///   <method name="Version2" scope="static" return="OpenId.Version2">
///     <body>
  static function Version2() {
    return new OpenId_Version2();
  }
///     </body>
///   </method>

///   <method name="YadisDiscover" scope="static" return="OpenId.YadisDiscover">
///     <args>
///       <arg name="client" type="OpenId.Client" />
///       <arg name="url" type="string" />
///     </args>
///     <body>
  static function YadisDiscover(OpenId_Client $client, $url) {
    return new OpenId_YadisDiscover($client, $url);
  }
///     </body>
///   </method>

///   <method name="YadisDiscover" scope="static" return="OpenId.YadisDiscover">
///     <args>
///       <arg name="client" type="OpenId.Client" />
///       <arg name="url" type="string" />
///     </args>
///     <body>
  static function HtmlDiscover(OpenId_Client $client, $url) {
    return new OpenId_HtmlDiscover($client, $url);
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="supporting">

///   <method name="parse_html">
///     <args>
///       <arg name="content" type="string" />
///       <arg name="tag" type="string" />
///       <arg name="attr_name" type="string" />
///       <arg name="attr_value" type="string" />
///       <arg name="value_name" type="string" />
///     </args>
///     <body>
  static function parse_html($content, $tag, $attr_name, $attr_value, $value_name) {
    //TODO: оптимайз
    preg_match_all("#<{$tag}[^>]*$attr_name=['\"].*?$attr_value.*?['\"][^>]*$value_name=['\"](.+?)['\"][^>]*/?>#i", $content, $matches1);
    preg_match_all("#<{$tag}[^>]*$value_name=['\"](.+?)['\"][^>]*$attr_name=['\"].*?$attr_value.*?['\"][^>]*/?>#i", $content, $matches2);
    $result = array_merge($matches1[1], $matches2[1]);
    return empty($result) ? false : $result[0];
  }
///     </body>
///   </method>

///   </protocol>

}
/// </class>

/// <class name="OpenId_Exception" extends="Core.Exception">
class OpenId_Exception extends Core_Exception {}
/// </class>

/// <class name="OpenID.Client" >
///   <implements interface="Core.PropertyAccessInterface" />
///   <implements interface="Core.CallInterface" />
class OpenID_Client implements Core_PropertyAccessInterface, Core_CallInterface {
  protected $request;
  protected $agent;
  protected $version = null;

  protected $options = array(
    'return_url' => '',
    'trust_root' => '',
    'ax' => false,
    'sreg' => false
  );

  protected $required = array();
  protected $optional = array();

  protected $params = array();
  protected $params_prefix = array();

  private $identity;
  private $claimed_id;
  private $identifier_select = false;
  private $server;
  protected $is_valid = false;

///   <protocol name="creating">

///   <method name="__construct">
///     <args>
///       <arg name="request" type="Net.HTTP.Request|null" default="null"  />
///       <arg name="agent" type="mixed" default = null />
///     </args>
///     <body>
  public function __construct($request = null, $agent = null) {
    if ($request instanceof Net_HTTP_Request)
      $this->request($request)->
        option('return_url', $request->scheme.'://'.$request->host);
    $this->agent = $agent instanceof Net_HTTP_AgentInterface ? $agent : Net_HTTP::Agent();
    $this->params['ax'] = new OpenId_AxParams();
    $this->params['sreg'] = new OpenId_SregParams();
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="configuring">

///   <method name="add_params_entity">
///     <args>
///       <arg name="name" type="string" />
///       <arg name="object" type="OpenId.Params" />
///     </args>
///     <body>
  public function add_params_entity($name, OpenId_Params $object) {
    $this->params[$name] = $object;
    return $this;
  }
///     </body>
///   </method>

///   <method name="required_params">
///     <args>
///       <arg name="names" type="array" />
///     </args>
///     <body>
  public function required_params(array $names) {
    $this->required = array_merge($this->required, $names);
    return $this;
  }
///     </body>
///   </method>

///   <method name="optional_params">
///     <args>
///       <arg name="names" type="array" />
///     </args>
///     <body>
  public function optional_params(array $names) {
    $this->optional = array_merge($this->optional, $names);
    return $this;
  }
///     </body>
///   </method>

///   <method name="param">
///     <args>
///       <arg name="name" type="string" />
///       <arg name="schema" type="string" default="''" />
///       <arg name="required" type="boolean" default="true" />
///     </args>
///     <body>
  public function param($name, $schema = '', $required = true, $prefix = null) {
    $type = $required ? 'required' : 'optional';
    $this->{$type}[$name] = $schema ? $schema : $name;
    if (!is_null($prefix)) $this->params_prefix[$name] = $prefix;
    return $this;
  }
///     </body>
///   </method>

///   <method name="p">
///     <body>
  public function p() {
    $args = func_get_args();
    return call_user_func_array(array($this, 'param'), $args);
  }
///     </body>
///   </method>

///   <method name="option">
///     <args>
///       <arg name="name" type="string" />
///       <arg name="value" type="mixed" default="null" />
///     </args>
///     <body>
  public function option($name, $value = null) {
    if (is_null($value))
      return $this->options[$name];
    $this->options[$name] = $value;
    return $this;
  }
///     </body>
///   </method>

///   <method name="options">
///     <args>
///       <arg name="values" type="array" default="array()" />
///     </args>
///     <body>
  public function options($values = array()) {
    foreach ($values as $name => $value)
      $this->options($name, $value);
    return $this;
  }
///     </body>
///   </method>

///   <method name="request">
///     <args>
///       <arg name="request" type="Net.HTTP.Request" />
///     </args>
///     <body>
  public function request(Net_HTTP_Request $request) {
    $this->request = $request;
    $this->option('trust_root', $p = $request->scheme.'://'.$request->host);
    if (!Core_Strings::starts_with($url = $this->option('return_url'), 'http'))
      $this->option('return_url', $p.$url);
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
      case in_array($property,
          array('server', 'version', 'agent', 'identity', 'request', 'params_prefix',
                'claimed_id', 'required', 'optional', 'params', 'is_valid')):
        return $this->$property;
      case Core_Strings::ends_with($property, '_params'):
        $property = str_replace('_params', '', $property);
        return $this->params[$property];
      case in_array($property, array('return_url', 'trust_root', 'ax', 'sreg')):
        return $this->option($property);
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
      case in_array($property, array('identity', 'claimed_id')):
        $this->$property = $this->normalize_identity($value);
        return $this;
      case $property == 'request':
        if ($value instanceof Net_HTTP_Request)
          $this->request($value);
        return $this;
      case $property == 'server':
        $this->$property = (string) $value;
        return $this;
      case $property == 'version':
        if ($value instanceof OpenId_Version) $this->version = $value;
        return $this;
      case $property == 'identifier_select':
        $this->$property = (boolean) $value;
        return $this;
      case $property =='agent' || $property == 'params_prefix':
        throw new Core_ReadOnlyPropertyException($property);
      case $property == 'return_url':
        if (!Core_Strings::starts_with($value, 'http') && $this->request)
          $value = $this->request->scheme.'://'.$this->request->host.$value;
        $this->option($property, $value);
        return $this;
      case in_array($property, array('trust_root', 'ax', 'sreg')):
        $this->option($property, $value);
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
    return in_array($property, array('server', 'version', 'agent', 'identity', 'params_prefix',
                'claimed_id', 'required', 'optional', 'params', 'identifier_select'));
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

///   <protocol name="performing" access="propected">

///   <method name="discover" returns="string">
///     <args>
///       <arg name="url" type="string" />
///     </args>
///     <body>
  protected function discover($url) {
    if (!preg_match('{^https?:}', $url))
      $url = "https://xri.net/$url";
    if (Core_Strings::contains($url, '@gmail') || Core_Strings::contains($url, 'google.com'))
      $url = 'https://www.google.com/accounts/o8/id';
    else if (Core_Strings::contains($url, 'yahoo.com'))
      $url = 'https://me.yahoo.com/a/';
    switch (true) {
      case $r = OpenId::YadisDiscover($this, $url)->search():
        return $r;
      case $r =  OpenId::HtmlDiscover($this, $url)->search():
        return $r;
      default:
        throw new OpenId_Exception("Server can't be found");
    }
  }
///     </body>
///   </method>

///   <method name="redirect">
///     <args>
///       <arg name="identity" type="string" />
///       <arg name="identifier_select" type="boolean" default="false" />
///     </args>
///     <body>
  public function redirect($identity, $identifier_select = false) {
    $this->identity = $this->claimed_id = $this->normalize_identity($identity);
    if (!$this->server || !$this->version)
      $this->discover($this->identity);
    return $this->version->redirect($this, $identifier_select ? $identifier_select : $this->identifier_select);
  }
///     </body>
///   </method>

///   <method name="validate" returns="boolean">
///     <body>
  public function validate() {
    $this->claimed_id = isset($this->request['openid_claimed_id']) ?
      $this->request['openid_claimed_id']: $this->request['openid_identity'];
    $parms = array();
    if (isset($this->request['openid_op_endpoint']))
      $parms['openid.ns'] = 'http://specs.openid.net/auth/2.0';
    foreach (explode(',', $this->request['openid_signed']) as $item) {
      $value = $this->request['openid_'.str_replace('.','_',$item)];
      $parms['openid.'.$item] = get_magic_quotes_gpc() ? stripslashes($value) : $value;
    }
    $this->discover($this->request['openid_identity']);
    $response = $this->agent->send(Net_HTTP::Request($this->server)->
      parameters(array_merge($parms, array(
          'openid.assoc_handle' => $this->request['openid_assoc_handle'],
          'openid.signed'       => $this->request['openid_signed'],
          'openid.sig'          => $this->request['openid_sig'],
          'openid.mode'         => 'check_authentication'
      )))->
      method('POST')
    );
    return $this->is_valid = preg_match('/is_valid\s*:\s*true/i', $response->body);
  }
///     </body>
///   </method>

///   <method name="retrieve_params" returns="array">
///     <body>
  public function retrieve_params() {
    if ($this->version && $this->is_valid)
      return $this->version->retrieve_params($this, $this->request);
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="supporting">

///   <method name="normalize_identity" returns="string" >
///     <args>
///       <arg name="value" type="string" />
///     </args>
///     <body>
  private function normalize_identity($value) {
    if (strlen($value = trim($value))) {
      if (preg_match('{^xri:/*}i', $value, $m)) {
        $value = substr($value, strlen($m[0]));
      } elseif (!preg_match('/^(?:[=@+\$!\(]|https?:)/i', $value)) {
        $value = "http://$value";
      }
      if (preg_match('#^https?://[^/]+$#i', $value, $m)) {
        $value .= '/';
      }
    }
    return $value;
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
    $this->__set($method, count($args) > 1 ? $args : $args[0]);
    return $this;
  }
///     </body>
////  </method>

///   </protocol>
}
/// </class>

/// <class name="OpenId.Version" stereotype="abstract" >
abstract class OpenId_Version {

///   <protocol name="creating">

///   <method name="__construct">
///     <body>
  public function __construct() {}
///     </body>
///   </protocol>

///   <protocol name="performing">

///   <method name="redirect" stereotype="abstract">
///     <args>
///       <arg name="client" type="OpenId.Client" />
///       <arg name="identifier_select" type="boolean" default="false" />
///     </args>
///     <body>
  abstract public function redirect(OpenId_Client $client, $identifier_select = false);
///     </body>
///   </method>

///   <method name="retrieve_params" stereotype="abstract">
///     <args>
///       <arg name="client" type="OpenId.Client" />
///       <arg name="request" type="Net.HTTP.Request" />
///     </args>
///     <body>
  abstract public function retrieve_params(OpenId_Client $client, Net_HTTP_Request $request);
///     </body>
///   </method>

///   </protocol>
}
/// </class>

/// <class name="OpenId.Version1" extends="OpenId.Version" >
class OpenId_Version1 extends OpenId_Version {
///   <protocol name="performing">

///   <method name="redirect" stereotype="abstract">
///     <args>
///       <arg name="client" type="OpenId.Client" />
///       <arg name="identifier_select" type="boolean" default="false" />
///     </args>
///     <body>
  public function redirect(OpenId_Client $client, $identifier_select = false) {
    $return_url = $client->option('return_url').($client->identity != $client->claimed_id ?
      ((strpos($client->option('return_url'), '?') ? '&' : '?').'openid.claimed_id='.$client->claimed_id) : '');
    $params = array(
      'openid.return_to'  => $return_url,
      'openid.mode'       => 'checkid_setup',
      'openid.identity'   => $client->identity,
      'openid.trust_root' => $client->option('trust_root'),
    );
    if ($client->option('sreg'))
      $params = array_merge($params, $client->sreg_params->build($client->required, $client->optional, $client));
    return Net_HTTP::redirect_to(Net_HTTP::Request($client->server)->parameters($params)->url);
  }
///     </body>
///   </method>

///   <method name="retrieve_params" stereotype="abstract">
///     <args>
///       <arg name="client" type="OpenId.Client" />
///       <arg name="request" type="Net.HTTP.Request" />
///     </args>
///     <body>
  public function retrieve_params(OpenId_Client $client, Net_HTTP_Request $request) {
    return $client->sreg_params->retrieve($request, $client);
  }
///     </body>
///   </method>

///   </protocol>
}
/// </class>

/// <class name="OpenId.Version2" extends="OpenId.Version" >
class OpenId_Version2 extends OpenId_Version {
///   <protocol name="performing">

///   <method name="redirect" stereotype="abstract">
///     <args>
///       <arg name="client" type="OpenId.Client" />
///       <arg name="identifier_select" type="boolean" default="false" />
///     </args>
///     <body>
  public function redirect(OpenId_Client $client, $identifier_select = false) {
    //TODO: в константы
    $id_select = 'http://specs.openid.net/auth/2.0/identifier_select';
    $params = array(
      'openid.ns'          => 'http://specs.openid.net/auth/2.0',
      'openid.return_to'  => $client->option('return_url'),
      'openid.mode'       => 'checkid_setup',
      'openid.identity'   => $client->identity,
      'openid.realm' => $client->option('trust_root'),
    );
    if ($identifier_select)
      $params['openid.identity'] = $params['openid.claimed_id'] = $id_select;
    else {
      $params['openid.identity'] = $client->identity;
      $params['openid.claimed_id'] = $client->claimed_id;
    }
    foreach ($client->params as $name => $p)
      if ($client->option($name))
        $params = array_merge($params, $p->build($client->required, $client->optional, $client));
    return Net_HTTP::redirect_to(Net_HTTP::Request($client->server)->parameters($params)->url);
  }
///     </body>
///   </method>

///   <method name="retrieve_params" stereotype="abstract">
///     <args>
///       <arg name="client" type="OpenId.Client" />
///       <arg name="request" type="Net.HTTP.Request" />
///     </args>
///     <body>
  public function retrieve_params(OpenId_Client $client, Net_HTTP_Request $request) {
    $res = array();
    foreach ($client->params as $name => $p)
      $res += $p->retrieve($request, $client);
    return $res;
  }
///     </body>
///   </method>

///   </protocol>
}
/// </class>

/// <class name="OpenId.Discover" stereotype="abstract" >
abstract class OpenId_Discover {
  protected $client;
  protected $url;

///   <protocol name="creating">

///   <method name="__construct">
///     <args>
///       <arg name="client" type="OpenId.Client" />
///       <arg name="url" type="string" />
///     </args>
///     <body>
  public function __construct(OpenId_Client $client, $url) {
    $this->client = $client;
    $this->url = $url;
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="performing">

///   <method name="search" stereotype="abstract">
///     <body>
  abstract public function search();
///     </body>
///   </method>

///   </protocol>
}
/// </class>

/// <class name="OpenId.YadisDiscover" extends="OpenId.Discover" >
class OpenId_YadisDiscover extends OpenId_Discover {

///   <protocol name="performing">

///   <method name="search" stereotype="abstract">
///     <body>
  public function search() {
    $res = $this->client->agent->send(Net_HTTP::Request($this->url));
    if (Core_Strings::starts_with($res->headers['Content-Type'], 'application/xrds+xml'))
      return $this->parse($res->body);
    $url = ($h = $res->headers['X-XRDS-Location']) ? $h :
      OpenId::parse_html($res->body, 'meta', 'http-equiv', 'X-XRDS-Location', 'value');
    if ($url)
      return $this->parse($this->client->agent->send(Net_HTTP::Request($url))->body);
    return false;
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="suppoerting" >

///   <method name="parse" access="protected" >
///     <args>
///       <arg name="xrds" type="string" />
///     </args>
///     <body>
  protected function parse($xrds) {
    $xml = XML::load($xrds);
    if (count(XML::errors()) > 0)
      return false;
    $l = $xml->getElementsByTagName('Service');
    if ($l->length == 0) return false;
    $priority = 100000;
    $index = 0;
    foreach ($l as $ind => $service) {
      $p = (int) $service->getAttribute('priority');
      if ($p < $priority) {
        $priority = $p;
        $index = $ind;
      }
    }
    $el = $l->item($index);
    return $this->parse_service($el);
  }
///     </body>
///   </method>

///   <method name="parse_service" access="protected" retunrns="boolean">
///     <args>
///       <arg name="el" type="DOM_Element" />
///     </args>
///     <body>
  protected function parse_service($el) {
    if (!$this->parse_uri($el)) return false;
    $this->parse_type($el);
    $this->parse_delegate($el);
    return true;
  }
///     </body>
///   </method>

///   <method name="parse_delegate" >
///     <args>
///       <arg name="el" type="DOM_Element" />
///     </args>
///     <body>
  protected function parse_delegate($el) {
    switch (true) {
      case $this->client->version instanceof OpenId_Version1:
        $ids = $el->getElementsByTagName('LocalID');
        if($ids->length == 0) $ids = $el->getElementsByTagName('CanonicalID');
        break;
      case $this->client->version instanceof OpenId_Version2:
        $ids = $el->getElementsByTagName('openid:Delegate');
        break;
    }
    if ($ids->length > 0)
      $this->client->identity = $ids->item(0)->textContent;
  }
///     </body>
///   </method>

///   <method name="parse_type" access="protected">
///     <args>
///       <arg name="el" type="DOM_Element" />
///     </args>
///     <body>
  protected function parse_type($el) {
    foreach ($el->getElementsByTagName('Type') as $t) {
      $value = $t->textContent;
      switch ($value) {
        case 'http://specs.openid.net/auth/2.0/signon':
        case 'http://specs.openid.net/auth/2.0/':
          $this->client->version = OpenId::Version2();
          break;
        case 'http://specs.openid.net/auth/2.0/server':
          $this->client->version = OpenId::Version2();
          $this->client->identifier_select = true;
          break;
        case 'http://openid.net/signon/1.1':
        case 'http://openid.net/signon/1.0':
          $this->client->version = OpenId::Version1();
          break;
        case 'http://openid.net/srv/ax/1.0':
        case 'http://openid.net/srv/ax/1.1':
          if (!Core_Strings::contains($this->client->server, 'myopenid.com'))
            $this->client->option('ax', true);
          break;
        case 'http://openid.net/sreg/1.0':
        case 'http://openid.net/extensions/sreg/1.1':
          $this->client->option('sreg', true);
          break;
      }
    }
  }
///     </body>
///   </method>

///   <method name="parse_uri" access="protected">
///     <args>
///       <arg name="el" type="DOM_Element" />
///     </args>
///     <body>
  protected function parse_uri($el) {
    $uri_elements = $el->getElementsByTagName('URI');
    if ($uri_elements->length == 0) return false;
    if (!($server = $uri_elements->item(0)->textContent))
      return false;
    $this->client->server = $server;
    return true;
  }
///     </body>
///   </method>

///   </protocol>

}
/// </class>

/// <class name="OpenId.HtmlDiscover" extends="OpenId.Discover" >
class OpenId_HtmlDiscover extends OpenId_Discover {
///   <protocol name="performing">

///   <method name="search" stereotype="abstract">
///     <body>
  public function search() {
    $content = $this->client->agent->send(Net_HTTP::Request($this->url))->body;
    foreach (array(
      array('server' => 'openid2.provider', 'delegate' => 'openid2.local_id', 'version' => 2),
      array('server' => 'openid.server', 'delegate' => 'openid.delegate', 'version' => 1)
    ) as $v) {
      $server   = OpenId::parse_html($content, 'link', 'rel', $v['server'],   'href');
      $delegate = OpenId::parse_html($content, 'link', 'rel', $v['delegate'], 'href');
      if ($server) {
        $this->client->server = $server;
        if ($delegate) $this->client->identity = $delegate;
        $this->client->version = Core::make('OpenId.Version'.$v['version']);
        return true;
      }
    }
    return false;
  }
///     </body>
///   </method>

///   </protocol>
}
/// </class>

// http://www.axschema.org/types/

/// <class name="OpenId.Params" stereotype="abstract" >
abstract class OpenId_Params {
///   <protocol name="performing">

///   <method name="build" stereotype="absract" >
///     <args>
///       <arg name="required" type="array" />
///       <arg name="optional" type="array" />
///       <arg name="client" type="OpenId.Client" />
///     </args>
///     <body>
  abstract public function build($required, $optional, $client);
///     <body>
///   </method>

///   <method name="retrieve" stereotype="abstract" >
///     <args>
///       <arg name="request" type="Net.HTTP.Request" />
///       <arg name="client" type="OpenId.Client" />
///     </args>
///     <body>
  abstract public function retrieve(Net_HTTP_Request $request, OpenId_Client $client);
///     </body>
///   </method>

///   </protocol>

///   <protocol name="supporting">

///   <method name="is_date" access="protected" >
///     <args>
///       <arg name="ax_name" type="string" />
///       <arg name="value" type="mixed" />
///     </args>
///     <body>
  protected function is_date($ax_name, $value) {
    return Core_Strings::ends_with($ax_name, 'Date') || Core_Strings::ends_with($ax_name, 'date');
  }
///     </body>
///   </method>

///   </protocol>
}
/// </class>

/// <class name="OpenId.AxParams" extends="OpenId.Params" >
class OpenId_AxParams extends OpenId_Params {
///   <protocol name="performing">

///   <method name="build" stereotype="absract" >
///     <args>
///       <arg name="required" type="array" />
///       <arg name="optional" type="array" />
///       <arg name="client" type="OpenId.Client" />
///     </args>
///     <body>
  public function build($required, $optional, $client) {
    $params = array();
    if ($required || $optional) {
      $params['openid.ns.ax'] = 'http://openid.net/srv/ax/1.0';
      $params['openid.ax.mode'] = 'fetch_request';
      $al_required = array();
      $al_optional = array();
      $counts = array();
      foreach (array('required', 'optional') as $type) {
        $al_type = 'al_'.$type;
        foreach ($$type as $alias => $field) {
          if (is_int($alias)) $alias = strtr($field, '/', '_');
          $params['openid.ax.type.'.$alias] = (isset($client->params_prefix[$alias]) ?
            $alias : 'http://axschema.org/').$field;
          $count_name = 'openid.ax.count.'.$alias;
          if (empty($counts[$count_name]))  $counts[$count_name] = 0;
          $counts[$count_name] += 1;
          ${$al_type}[] = $alias;
        }
      }
      foreach ($counts as $name => $value)
        if ($value > 1) $params[$name] = $value;
      if ($al_required) $params['openid.ax.required'] = implode(',', $al_required);
      if ($al_optional) $params['openid.ax.if_available'] = implode(',', $al_optional);
    }
    return $params;
  }
///     <body>
///   </method>

///   <method name="retrieve" stereotype="abstract" >
///     <args>
///       <arg name="request" type="Net.HTTP.Request" />
///       <arg name="client" type="OpenId.Client" />
///     </args>
///     <body>
  public function retrieve(Net_HTTP_Request $request, OpenId_Client $client) {
    $alias = '';
    if (isset($request['openid.ns.ax']) &&
        $request['openid.ns.ax'] == 'http://openid.net/srv/ax/1.0')
          $alias = 'ax';
    else {
      foreach ($request->parameters as $key => $val)
        if (Core_Strings::starts_with($key, 'openid.ns.') &&
          $val == 'http://openid.net/srv/ax/1.0') {
            $alias = substr($key, strlen('openid.ns.'));
            break;
        }
    }
    if (!$alias) return array();
    $value_prefix = 'openid.'.$alias.'.value.';
    $type_prefix = 'openid.'.$alias.'.type.';
    $res = array();
    foreach ($request->parameters as $k => $v)
      if (Core_Strings::starts_with($k, $value_prefix) &&
        ($name = substr($k, strlen($value_prefix))) &&
         ($type = $request->parameters[$type_prefix.$name]))
        $res[$name] = trim($this->is_date(Core::with_index(parse_url($type), 'path'), $v) ?
          Time::DateTime($v) : $v);
    return $res;
  }
///     </body>
///   </method>

///   </protocol>
}
/// </class>

/// <class name="OpenId.SregParams" extends="OpenId.Params" >
class OpenId_SregParams extends OpenId_Params {

   static protected $ax_to_sreg = array(
        'namePerson/friendly'     => 'nickname',
        'contact/email'           => 'email',
        'namePerson'              => 'fullname',
        'birthDate'               => 'dob',
        'person/gender'           => 'gender',
        'contact/postalCode/home' => 'postcode',
        'contact/country/home'    => 'country',
        'pref/language'           => 'language',
        'pref/timezone'           => 'timezone'
        );

///   <protocol name="performing">

///   <method name="build" stereotype="absract" >
///     <args>
///       <arg name="required" type="array" />
///       <arg name="optional" type="array" />
///       <arg name="client" type="OpenId.Client" />
///     </args>
///     <body>
  public function build($required = array(), $optional = array(), $client) {
    $params = array();
    if ($required || $optional) $params['openid.ns.sreg'] =
      'http://openid.net/extensions/sreg/1.1';
    foreach (array('required', 'optional') as $type)
      if ($$type)
        $params['openid.sreg.'.$type] = implode(',', $this->build_part($$type));
    return $params;
  }
///     <body>
///   </method>

///   <method name="retrieve" stereotype="abstract" >
///     <args>
///       <arg name="request" type="Net.HTTP.Request" />
///       <arg name="client" type="OpenId.Client" />
///     </args>
///     <body>
  protected function build_part($names) {
    $vals = array();
    foreach ($names as $n) {
      if (isset(self::$ax_to_sreg[$n]))
         $vals[] = self::$ax_to_sreg[$n];
    }
    return $vals;
  }
///     <body>
///   </method>

///   <method name="retrieve" stereotype="abstract" >
///     <args>
///       <arg name="request" type="Net.HTTP.Request" />
///       <arg name="client" type="OpenId.Client" />
///     </args>
///     <body>
  public function retrieve(Net_HTTP_Request $request, OpenId_Client $client) {
    $values = array();
    $sreg_to_ax = array_flip(self::$ax_to_sreg);
    $schema_to_name = array_flip(array_merge($client->optional, $client->required));
    $prefix = 'openid.sreg.';
    foreach ($request->parameters as $k => $v)
      if (Core_Strings::starts_with($k, $prefix) &&
        ($name = substr($k, strlen($prefix))) &&
        isset($sreg_to_ax[$name])) {
        $ax = $sreg_to_ax[$name];
        $alias = $schema_to_name[$ax];
        $values[$alias] = trim($this->is_date($ax, $v) ? Time::DateTime($v) : urldecode($v));
      }
    return $values;
  }
///     </body>
///   </method>

///   </protocol>
}
/// </class>

/// </module>
