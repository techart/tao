<?php
/// <module name="Service.Recaptcha" version="0.3.0" maintainer="svistunov@techart.ru">
Core::load('Net.HTTP');

/// <class name="Service.Recaptcha" stereotype="module">
///   <implements interface="Core.ModuleInterface" />
///   <depends supplier="Service.Recaptcha.Client" stereotype="creates" />
class Service_Recaptcha implements Core_ModuleInterface {

///   <constants>
  const VERSION = '0.3.0';
///   </constants>

  const URL = 'http://www.google.com/recaptcha/api';

///   <protocl name="creating">

///   <method name="Client" returns="Service.Recaptch.Client" scope="class">
///     <args>
///       <arg name="pubkey"  type="string" />
///       <arg name="privkey" type="string" />
///       <arg name="agent"   type="Net.HTTP.AgentInterface" default="null" />
///     </args>
///     <body>
  static public function Client($pubkey, $privkey, $agent = null) {
    return new Service_Recaptcha_Client($pubkey, $privkey, $agent);
  }
///     </body>
///   </method>

///   </protocol>
}
/// </class>


/// <class name="Service.Recaptcha.Client">
///   <implements interface="Core.PropertyAccessInterface" />
///   <implements interface="Core.StringifyInterface" />
class Service_Recaptcha_Client
  implements Core_PropertyAccessInterface,
             Core_StringifyInterface  {

  protected $pubkey;
  protected $privkey;

  protected $messages = array();
  protected $error = null;

  protected $agent;

///   <protocol name="creating">

///   <method name="__construct">
///     <args>
///       <arg name="pubkey" type="string" />
///       <arg name="privkey" type="string" />
///       <arg name="agent" type="Net.HTTP.AgentInterface" default="null" />
///     </args>
///     <body>
  public function __construct($pubkey, $privkey, $agent = null) {
    $this->pubkey = $pubkey;
    $this->privkey = $privkey;
    $this->agent($agent ? $agent : Net_HTTP::Agent());
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="configuring">

///   <method name="messages" returns="Service.Recaptcha.Client">
///     <args>
///       <arg name="values" type="array" />
///     </args>
///     <body>
  public function messages(array $values) {
    $this->messages = $values;
    return $this;
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="performing">

///   <method name="is_valid" returns="boolean">
///     <args>
///       <arg name="r" type="Net.HTTP.Request" />
///     </args>
///     <body>
  public function is_valid(Net_HTTP_Request $r) {
    $this->error = false;

    $response =
      $this->agent->
        send(
          Net_HTTP::Request(Service_Recaptcha::URL.'/verify')->
          method(Net_HTTP::POST)->
          parameters(array(
            'privatekey' => $this->privkey,
            'remoteip'   => $r->meta['REMOTE_ADDR'],
            'challenge'  => $r['recaptcha_challenge_field'],
            'response'   => $r['recaptcha_response_field'])));


    if ($response->status->is_success) {
      $lines = explode("\n", $response->body);
      if (trim($lines[0]) == 'true')
        return true;
      else {
        $this->error = $lines[1];
        return false;
      }
    } else {
      $this->error = 'Unknown error';
      return false;
    }
  }
///     </body>
///   </method>

///   <method name="html" returns="string">
///     <body>
  public function html() {
    $key = urlencode($this->pubkey);
    $rror = $this->error ? '&amp;error='.$this->error : '';
    return
      '<script type="text/javascript" src="'.Service_Recaptcha::URL.'/challenge?k='.$key.$error.'"></script>'.
      '<noscript>'.
      '<iframe src="'.Service_Recaptcha::URL.'/noscript?k='.$key.$error.'" height="300" width="500" frameborder="0"></iframe><br>'.
      '<textarea name="recaptcha_challenge_field" rows="3" cols="40">'.
      '</textarea>'.
      '<input type="hidden" name="recaptcha_response_field" value="manual_challenge">'.
      '</noscript>';
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="stringifying">

///   <method name="as_string" returns="string">
///     <body>
  public function as_string() { return $this->html(); }
///     </body>
///   </method>

///   <method name="__toString" returns="string">
///     <body>
    public function __toString() { return $this->html(); }
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
        return $this->$property;
      case 'message':
        return $this->get_message();
      default:
        throw new Core_MissingPropertyException($property);
    }
  }
///     </body>
///   </method>

///   <method name="__set">
///     <args>
///       <arg name="property" type="string" />
///       <arg name="value" />
///     </args>
///     <body>
  public function __set($property, $value) { throw new Core_ReadOnlyObjectException($this); }
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
      case 'message':
        return isset($this->error);
      default:
        return false;
    }
  }
///     </body>
///   </method>

///   <method name="__unset">
///     <args>
///       <arg name="property" type="string" />
///     </args>
///     <body>
  public function __unset($property) { throw new Core_ReadOnlyObjectException($this); }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="supporting">

///   <method name="get_message" returns="string" access="protected">
///     <body>
  protected function get_message() {
    return ($this->error && isset($this->messages[$this->error])) ?
      $this->messages[$this->error] :
      (is_null($this->error) ? '' : $this->error);
  }
///     </body>
///   </method>

///   <method name="agent" returns="Service.Recaptcha.Client" access="protected">
///     <body>
  protected function agent(Net_HTTP_AgentInterface $agent) {
    $this->agent = $agent;
    return $this;
  }
///     </body>
///   </method>

///   </protocol>

}
/// </class>
