<?php
/// <module name="WS.Auth" version="0.2.0" maintainer="timokhin@techart.ru">

Core::load('WS');

/// <class name="WS.Auth" stereotype="module">
class WS_Auth implements Core_ModuleInterface {

///   <constants>
  const VERSION = '0.2.0';
///   </constants>

///   <protocol name="performing">

///   <method name="unauthenticated" scope="class">
///     <args>
///       <arg name="realm" type="string" default="'Restricted area'" />
///     </args>
///     <body>
  static public function unauthenticated($realm = 'Restricted area') {
    throw new WS_Auth_UnauthenticatedException($realm);
  }
///     </body>
///   </method>

///   <method name="forbidden">
///     <body>
  static public function forbidden() { throw new WS_Auth_ForbiddenException(); }
///     </body>
///   </method>

///   </protocol>
}
/// </class>


/// <class name="WS.Auth.Exception" extends="Core.Exception">
class WS_Auth_Exception extends Core_Exception {}
/// </class>


/// <class name="WS.Auth.UnauthenticatedException" extends="WS.Auth.Exception">
class WS_Auth_UnauthenticatedException extends WS_Auth_Exception {

  protected $realm;

///   <protocol name="creating">

///   <method name="__construct">
///     <args>
///       <arg name="realm" type="string" default="'Restricted area'" />
///     </args>
///     <body>
  public function __construct($realm = 'Resticted area') { $this->realm = $realm; }
///     </body>
///   </method>

///   </protocol>
}
/// </class>


/// <class name="WS.Auth.ForbiddenException" extends="WS.Auth.Exception">
class WS_Auth_ForbiddenException extends WS_Auth_Exception {}
/// </class>


/// <interface name="WS.Auth.AuthModuleInterface">
interface WS_Auth_AuthModuleInterface {

///   <protocol name="performing">

///   <method name="authenticate" returns="mixed">
///     <args>
///       <arg name="login"    type="string" />
///       <arg name="password" type="string" />
///     </args>
///     <body>
  public function authenticate($login, $password);
///     </body>
///   </method>

///   </protocol>
}
/// </interface>

/// <interface name="WS.Auth.FindUserInterface">
interface WS_Auth_FindUserInterface {
///   <protocol name="performing">

///   <method name="find_user" returns="mixed">
///     <args>
///       <arg name="id" type="int" />
///     </args>
///     <body>
  public function find_user($id);
///     </body>
///   </method>

///   </protocol>
}
/// </interface>

/// <interface name="WS.Auth.AuthModuleInterface">
interface WS_Auth_AuthFindModuleInterface extends WS_Auth_AuthModuleInterface, WS_Auth_FindUserInterface {

}
/// </interface>


/// <class name="WS.Auth.Service" extends="WS.MiddlewareService" stereotype="abstract">
abstract class WS_Auth_Service extends WS_MiddlewareService {

  protected $auth_module;

///   <protocol name="creating">

///   <method name="__construct">
///     <args>
///       <arg name="application" type="WS.ServiceInterface" />
///       <arg name="auth_module" type="WS.Auth.AuthModuleInterface" />
///     </args>
///     <body>
  public function __construct(WS_ServiceInterface $application, WS_Auth_AuthModuleInterface $auth_module) {
    parent::__construct($application);
    $this->auth_module = $auth_module;
  }
///     </body>
///   </method>

///   </protocol>
}
/// </class>

/// </module>
