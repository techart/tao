<?php
/**
 * WS.Middleware.Config
 *
 * Сервис конфигурирования
 *
 * <p>Сервис предназначен для обеспечения всех последующих сервисов в цепочке обработки запроса
 * информацией о конфигурации приложения.</p>
 * <p>Сервис работает с файлом конфигурации в формате Config.DSL. Все последующие сервисы в цепочке могут
 * получить информацию о конфигурации из объекта env->config.</p>
 *
 * @package WS\Middleware\Config
 * @version 0.2.0
 */
Core::load('WS', 'Events');

/**
 * Класс модуля
 *
 * @package WS\Middleware\Config
 */
class WS_Middleware_Config implements Core_ModuleInterface
{

	const VERSION = '0.2.0';

	/**
	 * Создает объект класса WS.Middleware.Config.Service
	 *
	 * @param WS_ServiceInterface $application
	 * @param                     $path
	 *
	 * @return WS_Middleware_Config_Service
	 */
	static public function Service(WS_ServiceInterface $application, $path)
	{
		return new WS_Middleware_Config_Service($application, $path);
	}

}

/**
 * Конфигурационный сервис
 *
 * @package WS\Middleware\Config
 */
class WS_Middleware_Config_Service extends WS_MiddlewareService
{

	protected $path;

	/**
	 * Конструктор
	 *
	 * @param WS_ServiceInterface $application
	 * @param                     $path
	 */
	public function __construct(WS_ServiceInterface $application, $path)
	{
		parent::__construct($application);
		$this->path = (string)$path;
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
		$config = Config::all();
		$env->assign_if(array(
				'config' => $config
			)
		);
		Events::call('ws.config', $config);
		return $this->application->run($env);
	}

}

