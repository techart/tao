<?php
/**
 * WS.Middleware.ORM
 *
 * Сервис подключения к базе данных с использованием модуля DB.ORM
 *
 * <p>Сервис обеспечивает все последующие сервисы деревом мапперов, обеспечивающих объектно-ориентированный
 * доступ к реляционной базе данных с использованием классов модуля DB.ORM.</p>
 * <p>Все последующие сервисы в цепочке обработки запроса могут получить доступ к корневому мапперу дерева с помощью объекта
 * окружения: </p><code>$env->db</code>. Строка DSN для подключения к серверу БД может быть указана непосредственно при создании объекта
 * сервиса, или загружена конфигурационным сервисом аналогично модулю WS.Middleware.DB.
 * <p>Подразумевается, что в качестве корневого маппера используется объект класса DB.ORM.ConnectionMapper, поэтому модуль может
 * быть использован только в случаях использования одной базы данных и соответственно одного подключения. В случае, если дерево
 * мапперов использует несколько объектов подключения, реализация аналогичного модуля необходимо выполнить самостоятельно.</p>
 *
 * @package WS\Middleware\ORM
 * @version 0.2.2
 */

Core::load('DB.ORM', 'WS');

/**
 * Класс модуля
 *
 * @package WS\Middleware\ORM
 */
class WS_Middleware_ORM implements Core_ModuleInterface
{

	const VERSION = '0.2.2';

	/**
	 * Создает объект класса WS.Middleware.ORM.Service
	 *
	 * @param WS_ServiceInterface     $application
	 * @param DB_ORM_ConnectionMapper $session
	 * @param string                  $dsn
	 *
	 * @return WS_Middleware_ORM_Service
	 */
	static public function Service(WS_ServiceInterface $application, $session, $dsn = '')
	{
		return new WS_Middleware_ORM_Service($application, $session, $dsn);
	}

}

/**
 * Сервис, обеспечивающий объектно-ориентированный интерфейс к реляционной базе данных
 *
 * @package WS\Middleware\ORM
 */
class WS_Middleware_ORM_Service extends WS_MiddlewareService
{

	protected $session;
	protected $dsn;
	protected $logger;

	/**
	 * Конструктор
	 *
	 * @param WS_ServiceInterface     $application
	 * @param DB_ORM_ConnectionMapper $session
	 * @param string                  $dsn
	 */
	public function __construct(WS_ServiceInterface $application, $session, $dsn = '')
	{
		parent::__construct($application);
		$this->session = $session;
		$this->dsn = $dsn;
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
		if (is_string($this->session)) {
			$this->session = Core::make($this->session);
		}
		if (empty($env->db) && !empty($this->dsn)) {
			$env->db = new stdClass();
			$env->db->default = DB::Connection($this->dsn);
		}
		$env->orm = $this->session;
		foreach ($env->db as $name => $conn)
			$this->session->connect($conn, $name);

		if (isset($env->config->db->tables)) {
			$this->session->tables((array)$env->config->db->tables);
		}

		try {
			$result = $this->application->run($env);
		} catch (Exception $e) {
			$error = $e;
		}
		//$connection->disconnect();

		if (isset($error)) {
			throw $error;
		}

		return $result;
	}

}

