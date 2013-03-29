<?php
/// <module name="Service.OAuth.Middleware" maintainer="svistunov@techart.ru" version="0.1.0">
Core::load('Service.OAuth', 'WS');

/// <class name="Service.OAuth.Middleware" stereotype="module">
///   <implements interface="Core.ModuleInterface" />
class Service_OAuth_Middleware implements Core_ConfigurableModuleInterface {
///   <constants>
  const VERSION = '0.1.0';
///   </constants>

  static protected $options = array(
    'prefix'    => '/oauth/',
    'callback'  => 'callback/',
    'return_url_name' => 'url');


///   <protocol name="creating">

///   <method name="initialize" scope="class">
///     <args>
///       <arg name="options" type="array" default="array()" brief="массив опций" />
///     </args>
///     <body>
  static public function initialize(array $options = array()) {
    self::options($options);
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="configuring">

///   <method name="options" returns="mixed" scope="class">
///     <args>
///       <arg name="options" type="array" default="array()" brief="массив опций" />
///     </args>
///     <body>
  static public function options(array $options = array()) {
    if (count($options)) Core_Arrays::update(self::$options, $options);
    return self::$options;
  }
///     </body>
///   </method>

///   <method name="option" returns="mixed">
///     <brief>Устанавливает опцию</brief>
///     <args>
///       <arg name="name" type="string" brief="название опции" />
///       <arg name="value" default="null" brief="значение" />
///     </args>
///     <body>
  static public function option($name, $value = null) {
    $prev = null;
    if (array_key_exists($name, self::$options)) {
      $prev = self::$options[$name];
      if ($value !== null) self::options(array($name => $value));
    }
    return $prev;
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="building">

///   <method name="Service" returns="Service.OAuth.Middleware" scope="class">
///     <brief>Создает объект класса Service.OAuth.Middleware</brief>
///     <args>
///       <arg name="application" type="WS.ServiceInterface" brief="объект приложения" />
///     </args>
///     <body>
  static public function Service(WS_ServiceInterface $application) {
    return new Service_OAuth_Middleware_Service($application);
  }
///     </body>
///   </method>

///   </protocol>
}
/// </class>

/// <class name="Service.OAuth.Middleware.Service" extends="WS.MiddlewareService">
class Service_OAuth_Middleware_Service extends WS_MiddlewareService {
  protected $config = array();
  protected $args = array();

///   <protocol name="creating">

///   <method name="__construct">
///     <brief>Конструктор</brief>
///     <args>
///       <arg name="application" type="WS.ServiceInterface" brief="объект приложения" />
///     </args>
///     <body>
  public function __construct(WS_ServiceInterface $application) {
    $args = func_get_args();
    parent::__construct(array_shift($args));
    $this->args = Core::normalize_args($args);
  }
///     </body>
///   </method>

///   </protocol>

  protected function config() {
    if ($this->args[0] instanceof Core_InvokeInterface)
      $this->args = $this->args[0]->invoke();
    return $this->config = array_merge($this->config, $this->args);
  }


///   <protocol name="performing">

///   <method name="run" returns="mixed">
///     <brief>Выполняет обработку запроса</brief>
///     <args>
///       <arg name="env" type="WS.Environment" brief="объект окружения" />
///     </args>
///     <body>
  public function run(WS_Environment $env) {
    $this->config();
    if (!isset($env->oauth)) $env->oauth(new stdClass());
    foreach ($this->config as $name => $c) {
      $env->oauth->$name =  (object) $c;
      if ($c['client']->is_logged_in() && !empty($c['is_login_callback']) && $c['is_login_callback'] instanceof Core_InvokeInterface) {
        $c['is_login_callback']->invoke(array($name, $env));
      }
      $url = sprintf('%s%s/', Service_OAuth_Middleware::option('prefix'), $name);
      switch($env->request->path) {
        case $url:
          return $this->login_redirect($env, $c, $url);
        case $url.Service_OAuth_Middleware::option('callback'):
          return $this->login_confirm($env, $c);
      }
    }
    return $this->application->run($env);
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="supporting">

///   <method name="redirect">
///     <args>
///       <arg name="env" type="WS.Enviroment" />
///       <arg name="c" type="array" />
///       <arg name="url" type="string" />
///     </args>
///     <body>
  protected function login_redirect($env, $c, $url) {
    $host = $env->request->scheme.'://'.$env->request->host;
    $env->request->session()->set('query', $env->request->query);
    switch(true) {
      case (!$c['client']->is_logged_in()):
        return $c['client']->login_3legged_redirect(
          $host.$url.Service_OAuth_Middleware::option('callback').'?'.$env->request->query);
      case isset($env->request[Service_OAuth_Middleware::option('return_url_name')]):
        return $this->redirect($env);
      default:
        return $this->application->run($env);
    }
  }
///     </body>
///   </method>

///   <method name="confirm">
///     <args>
///       <arg name="env" type="WS.Enviroment" />
///       <arg name="c" type="array" />
///     </args>
///     <body>
  protected function login_confirm($env, $c) {
    $env->request->query($env->request->session()->get('query'));
    $env->request->session()->remove('query');
    if ($c['client']->login_3legged_confirm($env->request)) {
      $this->load_user_data($env, $c);
      if(isset($env->request[Service_OAuth_Middleware::option('return_url_name')]))
        return $this->redirect($env);
    }
    return $this->application->run($env);
  }
///     </body>
///   </method>

  protected function load_user_data($env, $c) {
    $request = $env->request;
    if (isset($c['api_me_url'])) {
      $res = $c['client']->send(Net_HTTP::Request($c['api_me_url']));
      $data = json_decode($res->body);
      $data = isset($data->response) ? $data->response : $data;
      if (empty($data->id) && !empty($data->uid)) $data->id = $data->uid;
      if (empty($data->id) && !empty($data->user_id)) $data->id = $data->user_id;
      $env->oauth->$c['name']->user = $data;
    } else {
      $id = isset($request['user_id']) ? $request['user_id'] : $c['client']->token['user_id'];
      $name = isset($request['screen_name']) ? $request['screen_name'] : $c['client']->token['screen_name'];
      $env->oauth->$c['name']->user = (object) array('id' => $id, 'name' => $name);
    }
    $c['client']->flush_store();
    return $this;
  }

///   <method name="redirect">
///     <args>
///       <arg name="env" type="WS.Enviroment" />
///     </args>
///     <body>
  protected function redirect($env) {
    return Net_HTTP::redirect_to($env->request[Service_OAuth_Middleware::option('return_url_name')].
      '?'.$env->request->query);
  }
///     </body>
///   </method>

///   </protocol>

}
/// <class>

/// </module>


