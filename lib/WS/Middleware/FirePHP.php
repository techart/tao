<?php
/// <module name="WS.Middleware.FirePHP" version="0.1.0" maintainer="svistunov@techart.ru">
Core::load('WS', 'Log');

/// <class name="WS.Middleware.FirePHP" stereotype="module">
///   <brief>Класс модуля</brief>
///   <implements interface="Core.ModuleInterface" />
class WS_Middleware_FirePHP implements Core_ModuleInterface {

///   <constants>
  const VERSION = '0.1.0';
///   </constants>

///   <protocol name="building">

///   <method name="Service" returns="WS.Middleware.FirePHP.Service" scope="class">
///     <brief>Создает объект класса WS.Middleware.FirePHP.Service</brief>
///     <args>
///       <arg name="application" type="WS.ServiceInterface" brief="объект приложения" />
///       <arg name="Log_level" type="Log.Level" defaults="0" brief="уровень выводимых логов " />
///     </args>
///     <body>
  static public function Service(WS_ServiceInterface $application, $Log_level = 0) {
    return new WS_Middleware_FirePHP_Service($application, $log_level);
  }
///     </body>
///   </method>

///   </protocol>
}
/// </class>

/// <class name="WS.Middleware.FirePHP.Service" extends="WS.MiddlewareService">
class WS_Middleware_FirePHP_Service extends WS_MiddlewareService {

  protected $log_level;

///   <protocol name="creating">

///   <method name="__construct">
///     <args>
///       <arg name="application" type="WS.ServiceInterface" brief="объект приложения" />
///       <arg name="Log_level" type="Log.Level" defaults="0" brief="уровень выводимых логов " />
///     </args>
///     <body>
  public function __construct(WS_ServiceInterface $application, $log_level = 0) {
    $this->log_level = $log_level;
    parent::__construct($application);
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
    $handler = Log_FirePHP::Handler();
    $handler->where('level', '>=', $this->log_level);
    Log::logger()->handler($handler);
    $response = $this->application->run($env);
    $handler->dump($response);
    return $response;
  }
///     </body>
///   </method>

///   </protocol>
}
/// </class>

/// </module>
