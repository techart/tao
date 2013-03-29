<?php
/// <module name="WS.Middleware.Config" version="0.2.0" maintainer="timokhin@techart.ru">
///   <brief>Сервис конфигурирования</brief>
///   <details>
///     <p>Сервис предназначен для обеспечения всех последующих сервисов в цепочке обработки запроса
///        информацией о конфигурации приложения.</p>
///     <p>Сервис работает с файлом конфигурации в формате Config.DSL. Все последующие сервисы в цепочке могут
///        получить информацию о конфигурации из объекта env->config.</p>
///   </details>
Core::load('WS', 'Config.DSL');

/// <class name="WS.Middleware.Config" stereotype="module">
///   <brief>Класс модуля</brief>
///   <implements interface="Core.ModuleInterface" />
class WS_Middleware_Config implements Core_ModuleInterface {

///   <constants>
  const VERSION = '0.2.0';
///   </constants>

///   <protocol name="building">

///   <method name="Service" returns="WS.Middleware.Config.Service" scope="class">
///     <brief>Создает объект класса WS.Middleware.Config.Service</brief>
///     <args>
///       <arg name="application" type="WS.ServiceInterface" brief="объект приложения" />
///       <arg name="path" brief="путь к конфигурационному файлу" />
///     </args>
///     <body>
  static public function Service(WS_ServiceInterface $application, $path) {
    return new WS_Middleware_Config_Service($application, $path);
  }
///     </body>
///   </method>

///   </protocol>
}
/// </class>


/// <class name="WS.Middleware.Config.Service" extends="WS.MiddlewareService">
///   <brief>Конфигурационный сервис</brief>
class WS_Middleware_Config_Service extends WS_MiddlewareService {

  protected $path;

///   <protocol name="creating">

///   <method name="__construct">
///     <brief>Конструктор</brief>
///     <args>
///       <arg name="application" type="WS.ServiceInterface" brief="объект приложения" />
///       <arg name="path" brief="путь к конфигурационному файлу" />
///     </args>
///     <body>
  public function __construct(WS_ServiceInterface $application, $path) {
    parent::__construct($application);
    $this->path = (string) $path;
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
    return $this->application->run(
      $env->assign_if(array(
        'config' => Config_DSL::load($this->path))));
  }
///     </body>
///   </method>

///   </protocol>
}
/// </class>

/// </module>
