<?php
/// <module name="WS.Auth.Session" version="0.2.0" maintainer="timokhin@techart.ru">
Core::load('WS.Auth', 'Forms');

/// <class name="WS.Auth.Session" stereotype="module">
class WS_Auth_Session implements Core_ModuleInterface {
///   <constants>
  const VERSION = '0.2.0';
///   </constants>

///   <protocol name="building">

///   <method name="Service" returns="WS.Auth.Session.Service" scope="class">
///     <args>
///       <arg name="application" type="WS.ServiceInterface" />
///       <arg name="auth_module" type="WS.Auth.AuthModuleInterface" />
///       <arg name="auth_url" type="string" />
///     </args>
///     <body>
  static public function Service(WS_ServiceInterface $application, WS_Auth_AuthFindModuleInterface $auth_module, $auth_url = '/auth/?url={url}') {
    return new WS_Auth_Session_Service($application, $auth_module, $auth_url);
  }
///     </body>
///   </method>

///   </protocol>
}
/// </class>


/// <class name="WS.Auth.Session.Service" extends="WS.Auth.Service">
class WS_Auth_Session_Service extends WS_Auth_Service {

  protected $auth_url;

///   <protocol name="creating">

///   <method name="__construct">
///     <args>
///       <arg name="application" type="WS.ServiceInterface" />
///       <arg name="auth_module" type="WS.Auth.AuthModuleInterface" />
///       <arg name="auth_url" type="string" />
///     </args>
///     <body>
  public function __construct(WS_ServiceInterface $application, WS_Auth_AuthFindModuleInterface $auth_module, $auth_url = '/auth/?url={url}') {
    parent::__construct($application, $auth_module);
    $this->auth_url = $auth_url;
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
    if (isset($env->request->session['user_id']))
      $env->auth->user = $this->auth_module->find_user($env->request->session['user_id']);
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="performing">

///   <method name="run" returns="mixed">
///     <args>
///       <arg name="env" type="WS.Environment" />
///     </args>
///     <body>
  public function run(WS_Environment $env) {
    $env->auth = Core::object(array(
      'user'   => null,
      'module' => $this->auth_module));

    $this->set_user($env);

    $uri = $env->request->url;

    try {
      $response = $this->application->run($env);
    } catch (WS_Auth_UnauthenticatedException $e) {
      $response = Net_HTTP::Response(Net_HTTP::UNAUTHORIZED);
    } catch (WS_Auth_ForbiddenException $e) {
      $response = Net_HTTP::Response(Net_HTTP::FORBIDDEN);
    }

    if ($response->status->code == Net_HTTP::UNAUTHORIZED)
      return Net_HTTP::redirect_to(Core_Strings::replace($this->auth_url, '{url}', $uri));
    else
      return $response;
  }
///       </body>
///   </method>

///   </protocol>
}
/// </class>


/// <class name="WS.Auth.Session.AuthResource" stereotype="abstract">
abstract class WS_Auth_Session_AuthResource {

  protected $env;
  protected $form;

///   <protocol name="creating">

///   <method name="__construct">
///     <args>
///       <arg name="env" type="WS.Environment" />
///     </args>
///     <body>
  public function __construct(WS_Environment $env) {
    $this->env = $env;
    $this->form = Forms::Form('auth')->
      method(Net_HTTP::POST)->
      begin_fields->
        input('login')->
        password('password')->
      end_fields;
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="performing">

///   <method name="index" returns="mixed">
///     <body>
  public function index() {
    return $this->html($this->env->auth->user ? 'authenticated' : 'login')->
      with(array('env'  => $this->env, 'form' => $this->form));
  }
///     </body>
///   </method>

///   <method name="create">
///     <body>
  public function create() {
    if ($this->form->process($this->env->request) &&
        ($user = $this->env->auth->module->authenticate($this->form['login'], $this->form['password']))) {
      $this->env->auth->user = $user;
      $this->env->request->session['user_id'] = $user->id;
      return Net_HTTP::redirect_to(Core::if_not_set($this->env->request, 'url', '/'));
    }
    return $this->html('login')->
      with(array('env' => $this->env, 'form' => $this->form));
  }
///     </body>
///   </method>

///   <method name="delete" returns="Net.HTTP.Response">
///     <body>
  public function delete() {
    $this->env->auth->user = null;
    unset($this->env->request->session['user_id']);
    return Net_HTTP::redirect_to(Core::if_not_set($this->env->request, 'url', '/'));
  }
///     </body>
///   </method>

///   <method name="html" returns="Templates.HTML.Template" access="protected">
///     <body>
  abstract protected function html($template);
///     </body>
///   </method>

///   </protocol>
}
/// </class>

/// </module>
