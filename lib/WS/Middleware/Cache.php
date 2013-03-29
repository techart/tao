<?php
/// <module name="WS.Middleware.Cache" version="0.2.2" maintainer="timokhin@techart.ru">
/// <brief>Сервис кеширования</brief>
/// <details>
///   <p>Сервис выполняет двойную функцию. Во-первых, он создает экземпляр объекта кеширования и записывает его в объект окружения,
///      таким образом, все последующие сервисы в цепочке могут использовать этот объект. Во-вторых, сервис может кешировать полный отклик
///      для различных адресов, определяемых набором регулярных выражений, используя этот объект кеширования.</p>
///   <p>Если параметры сервиса не указаны явно при его создании, он пытается получить их из элемента окружения, соответствующего объекту, описывающему
///      конфигурацию приложения, <pre>$env->config->cache</pre>. Поэтому рекомендуется подключать этот сервис после сервиса конфигурирования, WS.Middleware.Config.</p>
///   <p>Закешированные объекты отклика сохраняются в базе данных кеша под именами <pre>ws.middleware.cache.pages:page_url</pre>.</p>
/// </details>
Core::load('Cache', 'WS');

/// <class name="WS.Middleware.Cache" stereotype="module">
///   <brief>Класс модуля</brief>
///   <implements interface="Core.ModuleInterface" />
class WS_Middleware_Cache implements Core_ModuleInterface {

///   <constants>
  const VERSION = '0.2.2';
///   </constants>

///   <protocol name="building">

///   <method name="Service" returns="WS.Middleware.Cache.Service" scope="class">
///     <brief>Создает объект класса WS.Middleware.Cache.Service</brief>
///     <args>
///       <arg name="application" type="WS.ServiceInterface" brief="объект приложения" />
///       <arg name="dsn" type="string" default="''" brief="DSN объекта кеширования" />
///       <arg name="urls" type="array()" brief="набор выражений, определяющих адреса, отклик для которых должен кешироваться" />
///     </args>
///     <body>
  static public function Service(WS_ServiceInterface  $application) {
    $args = func_get_args();
    return Core::amake('WS.Middleware.Cache.Service', $args);
  }
///     </body>
///   </method>

///   </protocol>
}
/// </class>


/// <class name="WS.Middleware.Cache.Service" extends="WS.MiddlewareService">
///   <brief>Кеширующий сервис</brief>
class WS_Middleware_Cache_Service extends WS_MiddlewareService {

  protected $dsn;
  protected $urls = array();
  protected $timeout;
  protected $tagged = null;

///   <protocol name="creating">

///   <method name="__construct">
///     <brief>Конструктор</brief>
///     <args>
///       <arg name="application" type="WS.ServiceInterface" brief="приложение" />
///       <arg name="dsn" type="string" default="''" brief="DSN объекта кеширования" />
///       <arg name="urls" type="array()" brief="набор выражений, определяющих адреса, отклик для которых должен кешироваться" />
///     </args>
///     <body>
  public function __construct(WS_ServiceInterface $application) {
    parent::__construct($application);
    $args = func_get_args();
    foreach (array_slice($args, 1) as $arg)
      switch (true) {
        case is_bool($arg):
          $this->tagged = $arg;
          break;
        case is_array($arg):
          $this->urls = $arg;
          break;
        case is_int($arg):
          $this->timeout = $arg;
          break;
        case is_string($arg):
          $this->dsn = $arg;
          break;
      }
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
//  TODO: поддержка нескольких доменов
//  TODO: вынести 'ws.middlweware.cache.pages:' в опции модуля

//TODO: default timeout !!!!!
  public function run(WS_Environment $env) {
    $dsn = $this->dsn ? $this->dsn : $env->config->cache->dsn;
    if (empty($dsn)) $dsn = 'dummy://';
    $timeout = $this->timeout ? $this->timeout : (isset($env->config->cache->timeout)? $env->config->cache->timeout : null);
    $tagged = !is_null($this->tagged) ? $this->tagged : (isset($env->config->cache->tagged)? $env->config->cache->tagged : null);
    $env->cache = Cache::connect($dsn, $timeout);
    if ($tagged) {
      Core::load('Cache.Tagged');
      $env->cache = Cache_Tagged::Client($env->cache);
    }
    $response = null;

    foreach ($this->urls as $regexp => $timeout) {
      if (preg_match($regexp, $env->request->path)) {
        if (($response = $env->cache->get('ws.middlweware.cache.pages:'.$env->request->url)) === null) {
          $response = $this->application->run($env);
          $env->cache->set('ws.middleware.cache.pages:'.$env->request->url, $response, $timeout);
        }
        break;
      }
    }
    return $response ? $response : $this->application->run($env);
  }
///     </body>
///   </method>

///   </protocol>
}
/// </class>

/// </module>
