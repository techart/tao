<?php
/**
 * WS.Middleware.Environment
 * 
 * Устанавливает параметры окружения
 * 
 * <p>Модуль предназначен для ситуаций, когда необходимо установить набор параметров окружения при
 * создании цепочки сервисов приложения.</p>
 * 
 * @package WS\Middleware\Environment
 * @version 0.2.0
 */

Core::load('WS');

/**
 * Класс модуля
 * 
 * @package WS\Middleware\Environment
 */
class WS_Middleware_Environment implements Core_ModuleInterface {

  const VERSION = '0.2.1';


/**
 * Создает объект класса WS.Middleware.Environment.Service
 * 
 * @param WS_ServiceInterface $application
 * @param array $values
 * @param boolean $spawn
 * @return WS_Middleware_Environment_Service
 */
  static public function Service(WS_ServiceInterface $application, array $values, $spawn = false) {
    return new WS_Middleware_Environment_Service($application, $values, $spawn);
  }

}


/**
 * Middleware-сервис установки параметров окружения
 * 
 * @package WS\Middleware\Environment
 */
class WS_Middleware_Environment_Service extends WS_MiddlewareService {

  protected $values;
  protected $spawn;


/**
 * Конструктор
 * 
 * @param WS_ServiceInterface $application
 * @param array $values
 * @param boolean $spawn
 */
  public function __construct(WS_ServiceInterface $application, array $values, $spawn = false) {
    parent::__construct($application);
    $this->values = $values;
    $this->spawn  = $spawn;
  }



/**
 * Выполняет обработку запроса
 * 
 * @param WS_Environment $env
 * @return mixed
 */
  public function run(WS_Environment $env) {
    if ($this->spawn) $env = WS::Environment($env);

    foreach ($this->values as $k => $v) $env[$k] = $v;

    return $this->application->run($env);
  }

}

