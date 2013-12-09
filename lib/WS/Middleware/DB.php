<?php
/**
 * WS.Middleware.DB
 * 
 * Сервис подключения к базе данных
 * 
 * <p>Сервис обеспечивает все последующие сервисы объектом подключения к базе данных, доступным как элемент окружения env->db.</p>
 * <p>Сервис обеспечивает работу непосредственно с объектом подключения класса </p><code>DB.Connection</code>. Если для работы с базой используется DB.ORM,
 * необходимо использовать аналогичный сервис из модуля <code>WS.Middleware.ORM</code>.
 * <p>DSN сервера баз данных может быть указан при создании объекта сервиса, либо прочитан из объекта конфигурации, создаваемого сервисом
 * модуля </p><code>WS.Middleware.Config</code>. В этом случае DSN должен быть доступен как значение выражения <code>$env->config->db->dsn</code>.
 * 
 * @package WS\Middleware\DB
 * @version 0.2.0
 */
Core::load('DB', 'WS');

/**
 * Класс модуля
 * 
 * @package WS\Middleware\DB
 */
class WS_Middleware_DB implements Core_ModuleInterface {

  const VERSION = '0.2.1';


/**
 * Создает объект класса WS.Middleware.DB.Service
 * 
 * @param WS_ServiceInterface $application
 * @param string $dsn
 * @return WS_Middleware_DB_Service
 */
  static public function Service(WS_ServiceInterface  $application, $dsn = '') {
    return new WS_Middleware_DB_Service($application, $dsn);
  }

}


/**
 * Сервис подключения в базе данных
 * 
 * @package WS\Middleware\DB
 */
class WS_Middleware_DB_Service extends WS_MiddlewareService {

  protected $dsn;


/**
 * Конструктор
 * 
 * @param WS_ServiceInterface $application
 * @param string $dsn
 */
  public function __construct(WS_ServiceInterface $application, $dsn = '') {
    parent::__construct($application);
    $this->dsn = $dsn;
  }



/**
 * Выполняет обработку запроса
 * 
 * @param WS_Environment $env
 * @return mixed
 */
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

}

