<?php
/// <module name="WS.Auth.OpenSocial" version="0.2.0" maintainer="svistunov@techart.ru">
Core::load('WS.Auth.Session');

/// <class name="WS.Auth.OpenSocial" stereotype="module">
class WS_Auth_OpenSocial implements Core_ModuleInterface {
///   <constants>
  const VERSION = '0.2.0';
///   </constants>

///   <protocol name="building">

///   <method name="Service" returns="WS.Auth.OpenSocial.Service" scope="class">
///     <args>
///       <arg name="application" type="WS.ServiceInterface" />
///       <arg name="auth_module" type="WS.Auth.AuthModuleInterface" />
///       <arg name="auth_url" type="string" />
///     </args>
///     <body>
  static public function Service(WS_ServiceInterface $application, WS_Auth_OpenSocial_AuthModuleInterface $auth_module, $auth_url = '/auth/?url={url}') {
    return new WS_Auth_OpenSocial_Service($application, $auth_module, $auth_url);
  }
///     </body>
///   </method>

///   </protocol>
}
/// </class>

/// <class name="WS.Auth.OpenSocial.Service" extends="WS.Auth.Service">
class WS_Auth_OpenSocial_Service extends WS_Auth_Session_Service {

///   <protocol name="creating">

///   <method name="__construct">
///     <args>
///       <arg name="application" type="WS.ServiceInterface" />
///       <arg name="auth_module" type="WS.Auth.OpenSocial.AuthModuleInterface" />
///       <arg name="auth_url" type="string" />
///     </args>
///     <body>
  public function __construct(WS_ServiceInterface $application, WS_Auth_OpenSocial_AuthModuleInterface $auth_module, $auth_url = '/auth/?url={url}') {
    parent::__construct($application, $auth_module, $auth_url);
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="supporting">

///   <method name="set_user">
///     <args>
///       <arg name="env" type="WS.Environment" />
///     </args>
///     <body>
  protected function set_user(WS_Environment $env) {
    parent::set_user($env);
    if (!$env->auth->user && isset($env->request->session['remote_user_id']))
      $env->auth->user = $this->auth_module->find_remote_user($env->request->session['remote_user_id']);
  }
///     </body>
///   </method>

///   </protocol>
}
/// </class>

/// <class name="WS.Auth.OpenSocial.AuthResource" stereotype="abstract">
abstract class WS_Auth_OpenSocial_AuthResource extends WS_Auth_Session_AuthResource {
///   <protocol name="performing">

///   <method name="save_user">
///     <body>
  public function save_user($user) {
    $this->env->auth->user = $user;
    $this->env->request->session['remote_user_id'] = $user->remote_id;
  }
///     </body>
///   </method>

///   <method name="authenticate">
///     <body>
  public function authenticate() {
    if ($user = $this->env->auth->module->authenticate_remote_user($this->env)) {
      $this->save_user($user);
      return Net_HTTP::redirect_to(Core::if_not_set($this->env->request, 'url', '/'));
    }
    return $this->index();
  }
///     </body>
///   </method>

///   </protocol>
}
/// </class>

/// <interface name="WS.Auth.OpenSocial.AuthModuleInterface">
interface WS_Auth_OpenSocial_AuthModuleInterface extends WS_Auth_AuthModuleInterface {

///   <protocol name="performing">

///   <method name="authenticate_remote_user">
///     <brief>Аутентифицирует удаленного пользователя</brief>
///     <args>
///       <arg name="env" type="WS.Environment" />
///     </args>
///     <body>
  public function authenticate_remote_user(WS_Environment $env);
///     </body>
///   </method>

///   <method name="find_remote_user">
///     <brief>По идентификатору возвращает пользователя</brief>
///     <details>
///       В отличае от find_user идентифицирует удаленного пользователя.
///       При реализации не забываем про кеширование
///     </details>
///     <args>
///       <arg name="id" type="int" />
///     </args>
///     <body>
  public function find_remote_user($id);
///     </body>
///   </method>

///   </protocol>
}
/// </interface>

/// </module>
