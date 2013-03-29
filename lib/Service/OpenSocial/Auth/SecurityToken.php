<?php
/// <module name="Service.OpenSocial.Auth.SecurityToken" maintainer="svistunov@techart.ru" version="0.1.0">

/// <class name="Service.OpenSocial.Auth.SecurityToken" stereotype="module">
///   <implements interface="Service.OpenSocial.ModuleInterface" />
///   <depends supplier="Service.OpenSocial.Auth.SecurityToken.Adapter" stereotype="creates" />
class Service_OpenSocial_Auth_SecurityToken implements Service_OpenSocial_ModuleInterface {

///   <protocol name="creating">

///   <method name="Adapter" returns="Service.OpenSocial.Auth.SecurityToken.Adapter">
///     <args>
///       <arg name="token_name" type="string" />
///       <arg name="token_value" type="string" />
///     </args>
///     <body>
  static public function Adapter($token_name, $token_value) {
    return new Service_OpenSocial_Auth_SecurityToken_Adapter($token_name, $token_value);
  }
///     </body>
///   </method>

///   </protocol>
}
/// </class>


/// <class name="Service.OpenSocial.Auth.SecurityToken.Adapter" extends="Service.OpenSocial.AuthAdapter">
class Service_OpenSocial_Auth_SecurityToken_Adapter extends Service_OpenSocial_AuthAdapter {

///   <protocol name="creating">

///   <method name="__construct">
///     <args>
///     </args>
///     <body>
  public function __construct($token_name, $token_value) {
    parent::__construct(array('st_name' => $token_name, 'st_value' => $token_value));
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="building">

///   <method name="make_request">
///     <args>
///       <arg name="options" type="array" />
///       <arg name="container" type="Service.OpenSocial.Container" />
///     </args>
///     <body>
  public function authorize_request(Net_HTTP_Request $request, Service_OpenSocial_Container $container) {
    return $request->query_parameters(array($this->options['st_name'] => $this->options['st_value']));
  }
///     </body>
///   </method>

///   </protocol>
}
/// </class>

/// </module>
