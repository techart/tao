<?php
/// <module name="WS.Middleware.Environment" version="0.2.0" maintainer="timokhin@techart.ru">
/// <brief>Устанавливает параметры окружения</brief>
/// <details>
///   <p>Модуль предназначен для ситуаций, когда необходимо установить набор параметров окружения при
///      создании цепочки сервисов приложения.</p>
/// </details>

Core::load('WS');

/// <class name="WS.Middleware.Environment" stereotype="module">
///   <implements interface="Core.ModuleInterface" />
///   <depends supplier="WS.Middleware.Environment.Service" stereotype="creates" />
///   <brief>Класс модуля</brief>
class WS_Middleware_Environment implements Core_ModuleInterface {

///   <constants>
  const VERSION = '0.2.1';
///   </constants>

///   <protocol name="building">

///   <method name="Service" returns="WS.Middleware.Environment.Service">
///     <brief>Создает объект класса WS.Middleware.Environment.Service</brief>
///     <args>
///       <arg name="application" type="WS.ServiceInterface" brief="приложение" />
///       <arg name="values" type="array" brief="параметры окружения" />
///       <arg name="spawn" type="boolean" default="false" brief="признак необходимости создания дочернего окружения" />
///     </args>
///     <body>
  static public function Service(WS_ServiceInterface $application, array $values, $spawn = false) {
    return new WS_Middleware_Environment_Service($application, $values, $spawn);
  }
///     </body>
///   </method>

///   </protocol>
}
/// </class>


/// <class name="WS.Middleware.Environment.Service" extends="WS.MiddlewareService">
///   <brief>Middleware-сервис установки параметров окружения</brief>
class WS_Middleware_Environment_Service extends WS_MiddlewareService {

  protected $values;
  protected $spawn;

///   <protocol name="creating">

///   <method name="__construct">
///     <brief>Конструктор</brief>
///     <args>
///       <arg name="application" type="WS.ServiceInterface" brief="приложение" />
///       <arg name="values" type="array" brief="параметры окружения" />
///       <arg name="spawn" type="boolean" default="false" brief="признак необходимости создания дочернего окружения" />
///     </args>
///     <body>
  public function __construct(WS_ServiceInterface $application, array $values, $spawn = false) {
    parent::__construct($application);
    $this->values = $values;
    $this->spawn  = $spawn;
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
    if ($this->spawn) $env = WS::Environment($env);

    foreach ($this->values as $k => $v) $env[$k] = $v;

    return $this->application->run($env);
  }
///     </body>
///   </method>

///   </protocol>
}
/// </class>

/// </module>
