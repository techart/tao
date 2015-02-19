<?php
/**
 * WS.Middleware.FirePHP
 *
 * @package WS\Middleware\FirePHP
 * @version 0.1.0
 */
Core::load('WS', 'Log');

/**
 * Класс модуля
 *
 * @package WS\Middleware\FirePHP
 */
class WS_Middleware_FirePHP implements Core_ModuleInterface
{

	const VERSION = '0.1.0';

	/**
	 * Создает объект класса WS.Middleware.FirePHP.Service
	 *
	 * @param WS_ServiceInterface $application
	 * @param Log_Level           $Log_level
	 *
	 * @return WS_Middleware_FirePHP_Service
	 */
	static public function Service(WS_ServiceInterface $application, $Log_level = 0)
	{
		return new WS_Middleware_FirePHP_Service($application, $log_level);
	}

}

/**
 * @package WS\Middleware\FirePHP
 */
class WS_Middleware_FirePHP_Service extends WS_MiddlewareService
{

	protected $log_level;

	/**
	 * @param WS_ServiceInterface $application
	 * @param Log_Level           $Log_level
	 */
	public function __construct(WS_ServiceInterface $application, $log_level = 0)
	{
		$this->log_level = $log_level;
		parent::__construct($application);
	}

	/**
	 * Выполняет обработку запроса
	 *
	 * @param WS_Environment $env
	 *
	 * @return mixed
	 */
	public function run(WS_Environment $env)
	{
		$handler = Log_FirePHP::Handler();
		$handler->where('level', '>=', $this->log_level);
		Log::logger()->handler($handler);
		$response = $this->application->run($env);
		$handler->dump($response);
		return $response;
	}

}

