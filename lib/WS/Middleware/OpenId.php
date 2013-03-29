<?php
/// <module name="WS.Middleware.OpenId" maintainer="svistunov@techart.ru" version="0.1.0">
Core::load('OpenId', 'WS');

/// <class name="WS.Middleware.OpenId" stereotype="module">
///   <implements interface="Core.ModuleInterface" />
class WS_Middleware_OpenId implements Core_ConfigurableModuleInterface {
///   <constants>
  const VERSION = '0.1.0';
///   </constants>

  static protected $options = array(
    'id_name' => 'openid_url',
    'provider_pattern' => '{^/openid/([^/]+)/$}',
    'providers' => array(
      'google'	=> 'https://www.google.com/accounts/o8/id',
			'yandex'	=> 'http://openid.yandex.ru/',
			'yahoo'		=> 'http://me.yahoo.com/',
			'livejournal'	=> 'http://{ask_name}.livejournal.com',
			'mailru'	=> 'http://{ask_name}.id.{ask_domain}'
		),
  );

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
///       <arg name="client" type="OpenId.Client" />
///     </args>
///     <body>
  static public function Service(WS_ServiceInterface $application, $client) {
    return new WS_Middleware_OpenId_Service($application, $client);
  }
///     </body>
///   </method>

///   </protocol>
}
/// </class>

/// <class name="WS.Middleware.OpenId.Service" extends="WS.MiddlewareService">
class WS_Middleware_OpenId_Service extends WS_MiddlewareService {
  protected $client;
  protected $env;
  protected $provider_name;

///   <protocol name="creating">

///   <method name="__construct">
///     <brief>Конструктор</brief>
///     <args>
///       <arg name="application" type="WS.ServiceInterface" brief="объект приложения" />
///       <arg name="client" type="OpenId.Client" />
///     </args>
///     <body>
  public function __construct(WS_ServiceInterface $application , $client) {
    parent::__construct($application);
    $this->client = $client;
  }
///     </body>
///   </method>

///   </protocol>


///   <protocol name="performing">

///   <method name="run" returns="mixed">
///     <brief>Выполняет обработку запроса</brief>
///     <args>
///       <arg name="env" type="WS.Environment" brief="объект окружения" />
///     </args>
///     <body>
  public function run(WS_Environment $env) {
    $this->env = $env;
    if (!isset($env->openid)) $env->openid(new stdClass());
    $request = $env->request;
    $this->client = $this->client instanceof Core_Call ? $this->client->invoke() : $this->client;
    $this->client->request($request);
    $env->openid->client = $this->client;
    $ident = null;
    if (preg_match(WS_Middleware_OpenId::option('provider_pattern'), $request->path, $m))
      $ident = $m[1];
    else
      $ident = isset($request[WS_Middleware_OpenId::option('id_name')]);
    switch(true) {
      case !empty($ident):
        try {
          $env->request->session()->set('query', $env->request->query);
          return $this->client->redirect($this->create_redirect_url($ident));
        } catch (OpenId_Exception $e) {
          $env->openid->error = $e;
          return $this->application->run($env);
        }
      case $request['openid_mode'] == 'id_res':
        $env->openid->is_valid = $this->client->validate();
        if ($this->client->is_valid) {
          $env->openid->params = $this->client->retrieve_params();
          $env->openid->claimed_id = $this->client->claimed_id;
          $env->request->query($env->request->session()->get('query'));
          $env->request->session()->remove('query');
        }
      default:
        return $this->application->run($env);
    }
  }
///     </body>
///   </method>

///   </protocol>

  protected function create_redirect_url($ident) {
    $providers = WS_Middleware_OpenId::option('providers');
    if (!empty($providers[$ident])) {
      $url = $providers[$ident];
      $this->provider_name = $ident;
    }
    else
      $url = $ident;
    return preg_replace_callback("/{([^}]+)}/", array($this, 'url_replace'), $url);
  }
  
  protected function url_replace($m) {
    $name = $m[1];
    return $this->env->request[$name];
  }

}
/// <class>

/// </module>
