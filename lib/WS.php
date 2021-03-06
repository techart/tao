<?php
/**
 * WS
 *
 * Минимальный уровень абстракции обработки HTTP-запросов
 *
 * <p>Обработка запроса производится путем построения цепочки обработчиков-сервисов, реализующих стандартный интерфейс
 * WS.ServiceInterface.</p>
 * <p>Обмен информацией между сервисами производится с помощью объектов окружения класса WS.Environment. Обработчик может записать
 * какую-либо информацию в окружение, и передать его дальше по цепочке.</p>
 * <p>Кроме того, можно порождать дочерние объекты окружения, переопределяющие значения отдельных параметров родительской среды.</p>
 * <p>Использование общего окружения позволяет реализовывать так называемые middleware-сервисы. Middleware-сервис реализует различную
 * вспомогательную функциональность для основого сервиса, генерирующего объект отклика. Например, такой сервис может загружать
 * информацию о конфигурации, подключаться к базе данных, так или иначе модифицировать запрос и отклик и т.д.</p>
 * <p>Цепочка сервисов, совместно обрабатывающих запрос, формирует приложение.</p>
 *
 * @package WS
 * @version 0.2.0
 */
Core::load('Net.HTTP', 'WS.Adapters');

/**
 * Класс модуля
 *
 * @package WS
 */
class WS implements Core_ModuleInterface
{

	const VERSION = '0.2.1';

	static protected $env;

	protected static $options = array(
		'default_adapter' => 'apache',
		'fallback_adapter' => 'fpm',
		'adapter_module' => array(
			'apache' => 'WS.Adapters.Apache',
			'fpm' => 'WS.Adapters.FPM',
		)
	);

	public static function initialize($options = array())
	{
		self::$options = array_replace_recursive(self::$options, $options);
	}

	/**
	 * Создает объект класса WS.Environment
	 *
	 * @param WS_Environment $parent
	 *
	 * @return WS_Environment
	 */
	static public function Environment($parent = null)
	{
		return new WS_Environment($parent);
	}

	/**
	 * Создает объект класса WS.Runner
	 *
	 * @param WS_AdapterInterface $adapter
	 *
	 * @return WS_Runner
	 */
	static public function Runner(WS_AdapterInterface $adapter = null)
	{
		return new WS_Runner($adapter);
	}

	/**
	 * Обрабатывает текущий запрос с помощью цепочки обработчиков приложения с помощью объекта запуска класса WS.Runner
	 *
	 * @param WS_ServiceInterface $app
	 *
	 * @return WS_Runner
	 */
	static public function run(WS_ServiceInterface $app)
	{
		return self::Runner()->run($app);
	}

	static public function env()
	{
		if (is_object(self::$env)) {
			return self::$env;
		}
		self::$env = self::Environment();
		self::$env->ts = time();
		if (Core::is_cli()) {
			self::$env->request = Net_HTTP::Request();
		} else {
			self::$env->request = self::adapter()->make_request();
		}
		return self::$env;
	}

	protected static function adapter_module($fallback = false)
	{
		if (!$fallback) {
			$adapter = self::$options['default_adapter'];
		} else {
			$adapter = self::$options['fallback_adapter'];
		}
		if (!isset(self::$options['adapter_module'][$adapter])) {
			throw new WS_Exception("Adapter '$adapter' module not found");
		}
		$module = self::$options['adapter_module'][$adapter];
		Core::load($module);
		return Core_Types::real_class_name_for($module);
	}

	public static function adapter($fallback = false)
	{
		$class = self::adapter_module($fallback);
		$adapter = Core::invoke(array($class, 'adapter'));
		if (!$fallback && !$adapter->validate()) {
			return self::adapter(true);
		}
		return $adapter;
	}

}

/**
 * Базовый класс исключений модуля
 *
 * @package WS
 */
class WS_Exception extends Core_Exception
{
}

/**
 * Окружение обработчиков запросов
 *
 * <p>Представляет собой простейшее хранилище пар ключ-значение.</p>
 * <p>Для окружения может быть определено родительское окружение. В этом случае, отсутствующие в данном окружении элементы
 * будут запрашиваться из родительского окружения. Таким образом, каждый сервис может передать в следующий элемент цепочки
 * модифицированное окружение, не влияя на функционирование предыдущих элементов.</p>
 * <p>Значения элементов окружения доступны через обычные и индексные свойства. Значения элементов можно также задавать
 * /       путем вызова одноименных динамических методов. Так, приведенные ниже вызовы эквивалентны:</p>
 * <code>
 * $env->test = 'hello';
 * $env['test'] = 'hello';
 * $env->test('hello');
 * </code>
 *
 * @package WS
 */
class WS_Environment
	implements Core_PropertyAccessInterface,
	Core_IndexedAccessInterface,
	Core_CallInterface,
	Core_EqualityInterface
{

	protected $parent;
	protected $attrs = array();

	/**
	 * Конструктор
	 *
	 * @param WS_Environment $parent
	 */
	public function __construct($parent = null)
	{
		$this->parent = $parent;
	}

	/**
	 * Устанавливает значения элементов окружения
	 *
	 * @param  $values
	 *
	 * @return WS_Environment
	 */
	public function assign($values)
	{
		foreach ($values as $k => $v)
			$this->offsetSet($k, $v);
		return $this;
	}

	public function assign_if($values)
	{
		foreach ($values as $k => $v)
			if (!$this->offsetExists($k)) {
				$this->offsetSet($k, $v);
			}
		return $this;
	}

	/**
	 * Возвращает значение элемента окружения
	 *
	 * @param string $property
	 *
	 * @return mixed
	 */
	public function __get($property)
	{
		return $this->offsetGet($property);
	}

	/**
	 * Устанавливает значение элемента окружения
	 *
	 * @param string $property
	 * @param        $value
	 *
	 * @return mixed
	 */
	public function __set($property, $value)
	{
		return $this->offsetSet($property, $value);
	}

	/**
	 * Проверяет существование элемента окружения
	 *
	 * @param string $property
	 *
	 * @return boolean
	 */
	public function __isset($property)
	{
		return $this->offsetExists($property);
	}

	/**
	 * Удаляет элемент окружения
	 *
	 * @param string $property
	 */
	public function __unset($property)
	{
		return $this->offsetUnset($property);
	}

	/**
	 * Диспетчер вызовов
	 *
	 * @param string $method
	 * @param array  $args
	 *
	 * @return WS_Environment
	 */
	public function __call($method, $args)
	{
		$this->offsetSet($method, $args[0]);
		return $this;
	}

	/**
	 * Возвращает значение элемента окружения
	 *
	 * @param string $index
	 *
	 * @return mixed
	 */
	public function offsetGet($index)
	{
		$index = (string)$index;
		if (isset($this->attrs[$index])) {
			return $this->attrs[$index];
		} else {
			if ($this->parent) {
				return $this->parent->offsetGet($index);
			} else {
				return null;
			}
		}
	}

	/**
	 * Устанавливает значение элемента окружения
	 *
	 * @param string $index
	 * @param        $value
	 *
	 * @return mixed
	 */
	public function offsetSet($index, $value)
	{
		$this->attrs[(string)$index] = $value;
		return $this;
	}

	/**
	 * Проверяет существование элемента окружения
	 *
	 * @param string $index
	 *
	 * @return boolean
	 */
	public function offsetExists($index)
	{
		return isset($this->attrs[(string)$index]) ? true :
			($this->parent ? $this->parent->offsetExists($index) : false);
	}

	/**
	 * Удаляет элемент окружения
	 *
	 * @param string $index
	 */
	public function offsetUnset($index)
	{
		$index = (string)$index;
		if (isset($this->attrs[$index])) {
			unset($this->attrs[$index]);
		} else {
			if ($this->parent) {
				$this->parent->offsetUnset($index);
			} else {
				throw new Core_MissingPropertyException($index);
			}
		}
	}

	/**
	 */
	public function spawn()
	{
		return new self($this);
	}

	/**
	 * @param  $to
	 *
	 * @return boolean
	 */
	public function equals($to)
	{
		if (!($to instanceof self)) {
			return false;
		}
		if ($this->parent && !(Core::equals($this->parent, $to->parent))) {
			return false;
		}
		foreach ($this->attrs as $k => $v) {
			if (!Core::equals($v, $to[$k])) {
				return false;
			}
		}
		return true;
	}
}

/**
 * Стандартный интерфейс сервиса-обработчика запросов
 *
 * @package WS
 */
interface WS_ServiceInterface
{

	/**
	 * Выполняет обработку запроса
	 *
	 * @param WS_Environment $env
	 *
	 * @return mixed
	 */
	public function run(WS_Environment $env);

}

/**
 * Базовый класс middleware-сервиса
 *
 * <p>Содержит ссылку на сервис приложения, которому должно быть передано управление после выполения
 * операций сервиса.</p>
 * <p>Метод run реализации сервиса при это выглядит следующим образом:</p>
 * <code>
 * public function run(WS_Environment $env) {
 * действия до передачи управления дальше по цепочке, модификация $env
 * $response = $this->application->run($env);
 * действия после передачи управления дальше по цепочке, модификация $response
 * return $response;
 * }
 * </code>
 *
 * @abstract
 * @package WS
 */
abstract class WS_MiddlewareService implements WS_ServiceInterface
{

	protected $application;

	/**
	 * Конструктор
	 *
	 * @param WS_ServiceInterface $application
	 */
	public function __construct($application = null)
	{
		$this->application = $application;
	}

	public function set_application(WS_ServiceInterface $application)
	{
		$this->application = $application;
		return $this;
	}

	public function get_application()
	{
		return $this->application;
	}

}

/**
 * Адаптер, выполняющий построение объекта запроса
 *
 * <p>Адаптер формирует объект запроса класса Net.HTTP.Request на основе данных, хранящихся в стандартных
 * переменных PHP ($_SERVER, $_GET, $_POST и т.д.).</p>
 *
 * @package WS
 */
interface WS_AdapterInterface
{

	/**
	 * @return Net_HTTP_Request
	 */
	public function make_request();

	/**
	 * @param Net_HTTP_Response $response
	 */
	public function process_response(Net_HTTP_Response $response);

}

abstract class WS_AdapterAbstract implements WS_AdapterInterface
{
	/**
	 * @return Net_HTTP_Request
	 */
	public function make_request()
	{
		Core_Arrays::deep_merge_update_inplace($_POST, array_filter($this->current_uploads()));

		return Net_HTTP::Request(
			((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') ? 'https://' : 'http://') .
			$_SERVER['HTTP_HOST'] .
			$_SERVER['REQUEST_URI'], array('REMOTE_ADDR' => $_SERVER['REMOTE_ADDR'])
		)->
			method((isset($_POST['_method']) && $_POST['_method']) ? $_POST['_method'] : $_SERVER['REQUEST_METHOD'])->
			headers($this->headers());
	}

	public function validate()
	{
		return true;
	}

	/**
	 * @param Net_HTTP_Response $response
	 */
	public function process_response(Net_HTTP_Response $response, $quiet = false)
	{
		ob_start();

		$body = $response->body;

		if (Core_Types::is_iterable($body)) {
			foreach ($body as $line)
				print($line);
		} else {
			print $body instanceof Core_StringifyInterface ?
				$body->as_string() : (string)$body;
		}

		$response->body = ob_get_contents();
		ob_end_clean();
		if (!$quiet) {
			Events::call('ws.response', $response);
		}

		if ((int)$response->status->code != 200) {
			header('HTTP/1.0 ' . $response->status);
		}
		foreach ($response->headers->as_array(true) as $v) {
			header($v);
		}
		print $response->body;
	}

	abstract protected function headers();

	/**
	 */
	protected function current_uploads()
	{
		$files = array();
		foreach ($_FILES as $name => $file) {
			if (is_array($file['error'])) {
				$files[$name] = array_shift($file);
				foreach ($file as $v) {
					$files[$name] = Core_Arrays::deep_merge_append($files[$name], $v);
				}
			} else {
				$files[$name] = array_values($file);
			}
		}
		$this->create_objects($files);
		return $files;
	}

	/**
	 * @param array $nfiles
	 */
	protected function create_objects(array &$files)
	{
		foreach ($files as $name => &$file) {
			if (is_array($file)) {
				if (count($file) == 5 && is_string($file[0]) && is_string($file[1]) && is_string($file[2]) && is_int($file[3]) && is_int($file[4])) {
					if ($file[3] === UPLOAD_ERR_OK) {
						$file = Net_HTTP::Upload($file[2], $file[0], array('name' => $file[0], 'type' => $file[1], 'tmp_name' => $file[2], 'error' => $file[3], 'size' => $file[4]));
					} else {
						$file = null;
					}
				} else {
					$this->create_objects($file);
				}
			}
		}
	}

}

/**
 * Выполняет обработку текущего запроса с помощью заданного приложения
 *
 * <p>Обработка выполняется следующим образом:</p>
 * <ol><li>если объект-адаптер не был передан, создается экземпляр класса WS.Adapters.Apache;</li>
 * <li>адаптер формирует объект запроса класса Net.HTTP.Request;</li>
 * <li>создается объект окружения класса WS.Environment, сформированный объект запроса записывается
 * в него под именем $request;</li>
 * <li>вызов метода run() инициирует обработку запроса переданной в качестве параметра цепочкой обработчиков;</li>
 * <li>цепочка обработчиков формирует объект отклика класса Net.HTTP.Response;</li>
 * <li>объект отклика обрабатывается адаптером, выполняя все необходимые действия по выдаче отклика (заголовки, вывод
 * файла тела отклика и т.д.).</li>
 * </ol>
 *
 * @package WS
 */
class WS_Runner
{

	protected $adapter;

	/**
	 * Конструктор
	 *
	 * @param WS_AdapterInterface $adapter
	 */
	public function __construct(WS_AdapterInterface $adapter = null)
	{
		$this->adapter = Core::if_null($adapter, WS::adapter());
	}

	/**
	 * Запускает приложение
	 *
	 * @param WS_ServiceInterface $app
	 *
	 * @return WS_Runner
	 */
	public function run(WS_ServiceInterface $app)
	{
		$environment = WS::env();
		$environment->request = $this->adapter->make_request();

		$rc = Events::call('ws.run');
		if (!is_null($rc)) {
			return $rc;
		}

		$environment->response = Net_HTTP::Response();
		$body = $app->run($environment);

		$response = Net_HTTP::merge_response($body, $environment->response);

		return $this->adapter->process_response($response);
	}

}



