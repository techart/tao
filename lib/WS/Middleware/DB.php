<?php
/// <module name="WS.Middleware.DB" version="0.2.0" maintainer="timokhin@techart.ru">
///   <brief>Сервис подключения к базе данных</brief>
///   <details>
///     <p>Сервис обеспечивает все последующие сервисы объектом подключения к базе данных, доступным как элемент окружения env->db.</p>
///     <p>Сервис обеспечивает работу непосредственно с объектом подключения класса <pre>DB.Connection</pre>. Если для работы с базой используется DB.ORM,
///        необходимо использовать аналогичный сервис из модуля <pre>WS.Middleware.ORM</pre>.</p>
///     <p>DSN сервера баз данных может быть указан при создании объекта сервиса, либо прочитан из объекта конфигурации, создаваемого сервисом
///        модуля <pre>WS.Middleware.Config</pre>. В этом случае DSN должен быть доступен как значение выражения <pre>$env->config->db->dsn</pre>.</p>
///   </details>
Core::load('DB', 'WS');

/// <class name="WS.Middleware.DB" stereotype="module">
///   <brief>Класс модуля</brief>
///   <implements interface="Core.ModuleInterface" />
class WS_Middleware_DB implements Core_ModuleInterface {

///   <constants>
  const VERSION = '0.2.1';
///   </constants>

///   <protocol name="building">

///   <method name="Service" returns="WS.Middleware.DB.Service" scope="class">
///     <brief>Создает объект класса WS.Middleware.DB.Service</brief>
///     <args>
///       <arg name="application" type="WS.ServiceInterface" brief="объект приложения" />
///       <arg name="dsn" type="string" default="''" brief="строка DSN для подключения к базе данных"  />
///     </args>
///     <body>
  static public function Service(WS_ServiceInterface  $application, $dsn = '') {
    return new WS_Middleware_DB_Service($application, $dsn);
  }
///     </body>
///   </method>

///   </protocol>
}
/// </class>


/// <class name="WS.Middleware.DB.Service" extends="WS.MiddlewareService">
///   <brief>Сервис подключения в базе данных</brief>
class WS_Middleware_DB_Service extends WS_MiddlewareService {

  protected $dsn;

///   <protocol name="creating">

///   <method name="__construct">
///     <brief>Конструктор</brief>
///     <args>
///       <arg name="application" type="WS.ServiceInterface" brief="объект приложения" />
///       <arg name="dsn" type="string" default="''" brief="строка DSN для поключения к серверу БД" />
///     </args>
///     <body>
  public function __construct(WS_ServiceInterface $application, $dsn = '') {
    parent::__construct($application);
    $this->dsn = $dsn;
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
    if (empty($env->db))
      $env->db = new stdClass();
    $dsn = $this->dsn ? $this->dsn : $env->config->db->dsn;
    foreach ((array) $dsn as $name => $value) {
      if (is_numeric($name)) $name = 'default';
      if (empty( $env->db->$name)) $env->db->$name = DB::Connection($value);
    }
    
    //TODO: merge CMS::DBLogger
    /*if (isset($env->config) && $env->config->query_log->path)
      $env->db->listener(
        Dev_DB_Log::Logger(IO_FS::FileStream($env->config->query_log->path, 'a'), $env->config->query_log->explain)->
          write('= '.$env->request->url."\n\n"));
    */
    
    try {
      $result = $this->application->run($env);
    } catch (Exception $e) {
      $error = $e;
    }
    
//    foreach ($env->db as $c)
  //    $c->disconnect();
    
    if (isset($error)) throw $error;
    else return $result;
  }
///     </body>
///   </method>

///   </protocol>
}
/// </class>

/// </module>
