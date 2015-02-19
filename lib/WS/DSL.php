<?php
/**
 * WS.DSL
 *
 * Простейший builder для построения приложения из набора middleware-компонентов и обработчиков запросов
 *
 * <p>Модуль реализует класс WS.DSL.Builder, позволящий строить приложение из набора стандартных
 * компонент последовательными вызовами соответствующих методов.</p>
 * <p>
 *
 * @package WS\DSL
 * @version 0.3.1
 */

/**
 * Модуль WS.DSL
 *
 * <p>Построение приложения выполняется с помощью объектов класса WS.DSL.Builder. Класс реализует
 * Core.CallInterface, и делает это следующим образом.</p>
 * <p>Существует набор стандартных вызовов, каждый их которых приводит к созданию соответствующиего слоя
 * middleware или терминального обработчика.</p>
 * <p>Набор вызовов для создания middleware:</p>
 * environmentWS.Middleware.Environment.Service — установка элементов окружения;
 * configWS.Middleware.Config.Service — конфигурирование приложения;
 * dbWS.Middleware.DB.Service — подключение к БД;
 * ormWS.Middleware.ORM.Service — подключение к БД с использованием DB.ORM;
 * cacheWS.Middleware.Cache.Service — кеширование;
 * statusWS.Middleware.Status.Service — обработка ошибок и шаблоны HTTP-статуса;
 * templateWS.Middleware.Template.Service — принудительное преобразование шаблона в строку;
 * sessionWS.Session.Service — поддержка сессий;
 * auth_sessionWS.Auth.Session.Service — авторизации с помощью сессий;
 * auth_basicWS.Auth.Basic.Service — HTTP Basic авторизация;
 * auth_openidWS.Auth.OpenID.Service — авторизация с помощью OpenID.
 * <p>Набор вызовов для создания терминальных обработчиков:</p>
 * application_dispatcherWS.Rest.Dispatcher — диспетчер REST-приложений.
 * <p>Вызов метода, соответствующего middleware-компоненту, сохраняет информацию о его параметрах и
 * возвращает ссылку на builder. Таким образом, вызывая эти методы последовательно, можно построить необходимую
 * цепочку обработчиков. Параметры метода должны соответствовать набору параметров конструктора сервиса, без
 * первого аргумента, который всегда следующий сервис в цепочке.</p>
 * <p>Вызов метода, соответствующего терминальному обработчику, выполняет построение всей цепочки middleware-компонентов,
 * определенной ранее, завершает ее соответствующим терминальным обработчиком и возвращает получившуюся цепочку. Параметры
 * метода соответствуют параметрам обработчика.</p>
 * <p>Если необходимо указать свой собственный терминальный обработчик, это можно сделать с помощью вызова handler().</p>
 * <p>Таким образом, следующий код создаст приложение, читающее конфигурацию из файла, поключащееся к базе данных и
 * используюшее пользовательский обработчик для всего остального.</p>
 * <code>
 * $application = WS_DSL::application()->
 * config('../etc/config.php')->
 * cache('dummy://')->
 * application(new App_WS_ApplicationService());
 * </code>
 * <p>Набор поддерживаемых методов можно расширять. Для этого необходимо воспользоваться механизмом
 * конфигурирования модуля. Компоненты middleware регистрируются с помощью опции middleware,
 * терминальные обработчики — с помощью опции handlers.</p>
 * <code>
 * Core::configure('WS.DSL', array(
 * 'middleware' => array(
 * 'app_middleware' => 'App.WS.Middleware.CustomService'),
 * 'handlers'   => array(
 * 'custom_app' => 'App.WS.ApplicationService')));
 * </code>
 * <p>После этого можно использовать эти вызовы при построении приложения, например:</p>
 * <code>
 * $application = WS_DSL::application()->
 * config('../etc/config.php')->
 * app_middleware($parms)->
 * custom_app();
 * </code>
 *
 * @package WS\DSL
 */
class WS_DSL implements Core_ModuleInterface
{

	const VERSION = '0.3.1';

	const PREFIX = 'WS.Middleware';
	const SUFFIX = 'Service';

	static public $middleware = array(
		'dummy_middleware' => '.Dummy.',
		'environment' => '.Environment.',
		'firephp' => '.FirePHP.',
		'oauth' => 'Service.OAuth.Middleware.',
		'openid' => '.OpenId.',
		'config' => '.Config.',
		'db' => '.DB.',
		'orm' => '.ORM.',
		'cache' => '.Cache.',
		'status' => '.Status.',
		'template' => '.Template.',
		'session' => '.Session.',
		'pdf' => '.PDF.',
		'auth_session' => 'WS.Auth.Session.',
		'auth_basic' => 'WS.Auth.Basic.',
		'auth_opensocial' => 'WS.Auth.OpenSocial.',
		'auth_apache' => 'WS.Auth.Apache.',
	);

	static public $handlers = array(
		'dummy_service' => 'WS.Services.Dummy.Service',
		'application_dispatcher' => 'WS.Services.REST.Dispatcher');

	/**
	 * Выполняет инициализацию модуля
	 *
	 * @param array $options
	 */
	static public function initialize(array $options = array())
	{
		if (isset($options['middleware']) && is_array($options['middleware'])) {
			self::$middleware = array_merge(self::$middleware, $options['middleware']);
		}

		if (isset($options['handlers']) && is_array($options['handlers'])) {
			self::$handlers = array_merge(self::$handlers, $options['handlers']);
		}
	}

	/**
	 * @param string $name
	 * @param string $class
	 */
	static function add_middleware($name, $class)
	{
		self::$middleware[$name] = $class;
	}

	/**
	 * @param string $name
	 * @param string $class
	 */
	static function add_handler($name, $class)
	{
		self::$handlers[$name] = $class;
	}

	/**
	 * Создает объект класса WS.DSL.Builder
	 *
	 * @return WS_DSL_Builder
	 */
	static public function Builder()
	{
		return new WS_DSL_Builder();
	}

	/**
	 * Псевдоним для WS.DSL::Builder()
	 *
	 * @return WS_DSL_Builder
	 */
	static public function application()
	{
		return new WS_DSL_Builder();
	}

}

/**
 * Диспетчер динамических вызовов
 *
 * @package WS\DSL
 */
class WS_DSL_Builder implements Core_CallInterface
{

	protected $middleware = array();

	/**
	 * Создает терминальный обратчик или сохраняет информацию о middleware в очереди
	 *
	 * @param string $method
	 * @param array  $parms
	 *
	 * @return mixed
	 */
	public function __call($method, $parms)
	{
		return $this->add_middleware($method, $parms) ? $this : $this->make_handler($method, $parms);
	}

	/**
	 * @param WS_ServiceInterface $app
	 *
	 * @return WS_ServiceInterface
	 */
	public function handler(WS_ServiceInterface $app)
	{
		return $this->build_middleware($app);
	}

	public function get_middleware_parms($name)
	{
		return $this->middleware[$name]['parms'];
	}

	public function set_middleware_parms($name, array $parms)
	{
		$this->middleware[$name]['parms'] = $parms;
		return $this;
	}

	public function update_middleware_parms($name, array $parms)
	{
		$this->middleware[$name]['parms'] = array_merge($this->middleware[$name]['parms'], $parms);
		return $this;
	}

	/**
	 * Сохраняет информацию о параметрах вызова middleware в очереди
	 *
	 * @param string $method
	 * @param array  $parms
	 *
	 * @return boolean
	 */
//TODO: weight
	protected function add_middleware($method, $parms)
	{
		if (isset(WS_DSL::$middleware[$method])) {
			$this->middleware[$method] = new ArrayObject(array('class' => WS_DSL::$middleware[$method], 'parms' => $parms));
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Создает терминальный обработчик
	 *
	 * @param string $method
	 * @param array  $parms
	 */
	protected function make_handler($method, $parms)
	{
		if (isset(WS_DSL::$handlers[$method])) {
			$this->load_module_for($c = $this->complete_name(WS_DSL::$handlers[$method]));
			return $this->build_middleware(Core::amake($c, $parms));
		}
	}

	/**
	 * Строит цепочку middleware-компонент
	 *
	 * @param WS_ServiceInterface $app
	 *
	 * @return WS_ServiceInterface
	 */
	protected function build_middleware(WS_ServiceInterface $app)
	{
		foreach (array_reverse($this->middleware) as $name => $conf) {
			if ($conf['class'] instanceof WS_ServiceInterface) {
				$conf['class']->set_application($app);
				$app = $conf['class'];
			} else {
				$this->load_module_for($c = $this->complete_name((string)$conf['class']));
				$app = Core::amake($c, array_merge(array($app), $conf['parms']));
			}
		}
		$this->middleware = array();
		return $app;
	}

	/**
	 * Подгружает модуль для указанного имени класса
	 *
	 * @param string $class
	 */
	protected function load_module_for($class)
	{
		Core::load(substr($class, 0, strrpos(str_replace('..', '.', $class), '.')));
	}

	/**
	 * Выполняет развертывание имени компонента
	 *
	 * @param string $name
	 *
	 * @return string
	 */
	protected function complete_name($name)
	{
		if (Core_Strings::ends_with($name, '.')) {
			$name = $name . WS_DSL::SUFFIX;
		}
		if (Core_Strings::starts_with($name, '.')) {
			$name = WS_DSL::PREFIX . $name;
		}
		return $name;
	}

}

