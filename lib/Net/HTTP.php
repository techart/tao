<?php
/**
 * Net.HTTP
 *
 * Объектное представления запроса и отклика HTTP-протокола
 *
 * @package Net\HTTP
 * @version 0.2.4
 */
Core::load('IO.FS', 'MIME', 'Time');

/**
 * @package Net\HTTP
 */
interface Net_HTTP_SessionInterface
{
}

/**
 * Класс модуля
 *
 * @package Net\HTTP
 */
class Net_HTTP implements Core_ModuleInterface
{

	const VERSION = '0.2.4';

	const DEFAULT_CACHE_LIFETIME = 86400000; // 3600*24*1000

	const GET = 1;
	const PUT = 2;
	const POST = 4;
	const DELETE = 8;
	const HEAD = 16;
	const ANY = 31;

	const OK = 200;
	const CREATED = 201;
	const ACCEPTED = 202;
	const NON_AUTHORITATIVE = 203;
	const NO_CONTENT = 204;
	const RESET_CONTENT = 205;
	const PARTIAL_CONTENT = 206;
	const MULTI_STATUS = 207;
	const MULTIPLE_CHOICES = 300;
	const MOVED_PERMANENTLY = 301;
	const FOUND = 302;
	const SEE_OTHER = 303;
	const NOT_MODIFIED = 304;
	const USE_PROXY = 305;
	const SWITCH_PROXY = 306;
	const TEMPORARY_REDIRECT = 307;
	const BAD_REQUEST = 400;
	const UNAUTHORIZED = 401;
	const PAYMENT_REQUIRED = 402;
	const FORBIDDEN = 403;
	const NOT_FOUND = 404;
	const METHOD_NOT_ALLOWED = 405;
	const NOT_ACCEPTABLE = 406;
	const PROXY_AUTHENTICATION_REQUIRED = 407;
	const REQUEST_TIMEOUT = 408;
	const CONFLICT = 409;
	const GONE = 410;
	const LENGTH_REQUIRED = 411;
	const PRECONDITION_FAILED = 412;
	const REQUEST_ENTITY_TOO_LARGE = 413;
	const REQUEST_URI_TOO_LONG = 414;
	const UNSUPPORTED_MEDIA_TYPE = 415;
	const REQUESTED_RANGE_NOT_SATISFIABLE = 416;
	const EXPECTATION_FAILED = 417;
	const INTERNAL_SERVER_ERROR = 500;
	const NOT_IMPLEMENTED = 501;
	const BAD_GATEWAY = 502;
	const SERVICE_UNAVAILABLE = 503;
	const GATEWAY_TIMEOUT = 504;
	const HTTP_VERSION_NOT_SUPPORTED = 505;

	static protected $method_names = array(
		Net_HTTP::GET => 'get', Net_HTTP::PUT => 'put', Net_HTTP::POST => 'post', Net_HTTP::DELETE => 'delete', Net_HTTP::HEAD => 'head');

	/**
	 * Создает объект HTTP-запроса
	 *
	 * @param string $uri
	 * @param array  $meta
	 *
	 * @return Net_HTTP_Request
	 */
	static public function Request($uri = '', array $meta = array())
	{
		return new Net_HTTP_Request($uri, $meta);
	}

	/**
	 * Создает объект HTTP-отклика
	 *
	 * @return Net_HTTP_Response
	 */
	static public function Response()
	{
		$args = func_get_args();
		$response = new Net_HTTP_Response();
		switch (count($args)) {
			case 0:
				return $response;
			case 1:
				if ($args[0] instanceof Net_HTTP_Response) {
					return $args[0];
				}
				if (is_int($args[0]) || $args[0] instanceof Net_HTTP_Status) {
					return $response->status($args[0]);
				} elseif (is_string($args[0]) ||
					$args[0] instanceof Iterator ||
					$args[0] instanceof IteratorAggregate
				) {
					return $response->body($args[0]);
				} elseif (is_null($args[0])) {
					return $response->status(Net_HTTP::NO_CONTENT);
				} else {
					return $response->body($args[0]);
				}
			case 2:
				return $response->
					status($args[0])->
					body($args[1]);
			default:
				return $response->
					status($args[0])->
					body($args[1])->
					headers($args[2]);
		}
	}

	static public function merge_response($res1, $res2)
	{
		$res1 = Net_HTTP::Response($res1);
		$res2 = Net_HTTP::Response($res2);
		$res = new Net_HTTP_Response();
		$res->status($res2->status->code != Net_HTTP::OK ? $res2->status : $res1->status);
		$res->headers(array_merge($res1->headers->as_array(), $res2->headers->as_array()));
		if (empty($res2->body)) {
			$res->body($res1->body);
		}
		if (empty($res1->body)) {
			$res->body($res2->body);
		}
		/*if ((is_string($res1->body) || $res1->body instanceof Core_StringifyInterface) &&
		   (is_string($res1->body) || $res1->body instanceof Core_StringifyInterface))
		   $res->body = $res1->body . $res2->body;*/
		return $res;
	}

	/**
	 * Создает объектное представление http upload
	 *
	 * @param string $tmp_path
	 * @param string $original_name
	 *
	 * @return Net_HTTP_Upload
	 */
	static public function Upload($tmp_path, $original_name, $file_array = array())
	{
		return new Net_HTTP_Upload($tmp_path, $original_name, $file_array);
	}

	/**
	 * Строит отлик, представляющий из себя перенаправление на указанный адрес
	 *
	 * @param string $url
	 * @param        $status
	 *
	 * @return Net_HTTP_Response
	 */
	static public function redirect_to($url, $status = self::FOUND)
	{
		return Net_HTTP::Response()->
			status($status)->
			location($url);
	}

	/**
	 * @param string $url
	 * @param        $status
	 *
	 * @return Net_HTTP_Response
	 */
	static public function moved_permanently_to($url, $status = self::MOVED_PERMANENTLY)
	{
		return self::redirect_to($url, $status);
	}

	/**
	 * Строит отклик со статусом HTTP 404: NOT FOUND
	 *
	 * @return Net_HTTP_Response
	 */
	static public function not_found()
	{
		return Net_HTTP::Response(Net_HTTP::NOT_FOUND);
	}

	/**
	 * Строит отклик со статусом HTTP 404: FORBIDDEN
	 *
	 * @return Net_HTTP_Response
	 */
	static public function forbidden()
	{
		return Net_HTTP::Response(Net_HTTP::FORBIDDEN);
	}

	/**
	 * Возвращает имя HTTP-метода в виде строки по значению числовой константы
	 *
	 * @param int $method
	 *
	 * @return string
	 */
	static public function method_name_for($method)
	{
		return Core::if_not_set(self::$method_names, $method, '');
	}

	/**
	 * @param array $options
	 *
	 * @return Net_Agents_HTTP_Agent
	 */
	static public function Agent(array $options = array())
	{
		Core::load('Net.Agents.HTTP');
		return new Net_Agents_HTTP_Agent($options);
	}

	/**
	 * @param int    $code
	 * @param string $message
	 *
	 * @return Net_HTTP_Status
	 */
	static public function Status($code, $message = null)
	{
		return new Net_HTTP_Status($code, $message);
	}

	static public function Download($file, $cache = false, $cache_lifetime = Net_HTTP::DEFAULT_CACHE_LIFETIME)
	{
		return new Net_HTTP_Download($file, $cache, $cache_lifetime);
	}

}

/**
 * @package Net\HTTP
 */
class Net_HTTP_Status implements
	Core_PropertyAccessInterface,
	Core_EqualityInterface,
	Core_StringifyInterface
{

	static protected $messages = array(
		200 => 'OK',
		201 => 'CREATED',
		202 => 'ACCEPTED',
		203 => 'NON AUTHORITATIVE',
		204 => 'NO CONTENT',
		205 => 'RESET CONTENT',
		206 => 'PARTIAL CONTENT',
		207 => 'MULTI STATUS',
		300 => 'MULTIPLE CHOICES',
		301 => 'MOVED PERMANENTLY',
		302 => 'FOUND',
		303 => 'SEE OTHER',
		304 => 'NOT MODIFIED',
		305 => 'USE PROXY',
		306 => 'SWITCH PROXY',
		307 => 'TEMPORARY REDIRECT',
		400 => 'BAD REQUEST',
		401 => 'UNAUTHORIZED',
		402 => 'PAYMENT REQUIRED',
		403 => 'FORBIDDEN',
		404 => 'NOT FOUND',
		405 => 'METHOD NOT ALLOWED',
		406 => 'NOT ACCEPTABLE',
		407 => 'PROXY AUTHENTICATION_REQUIRED',
		408 => 'REQUEST TIMEOUT',
		409 => 'CONFLICT',
		410 => 'GONE',
		411 => 'LENGTH REQUIRED',
		412 => 'PRECONDITION FAILED',
		413 => 'REQUEST ENTITY TOO LARGE',
		414 => 'REQUEST URI TOO LONG',
		415 => 'UNSUPPORTED MEDIA TYPE',
		416 => 'REQUESTED RANGE NOT SATISFABLE',
		417 => 'EXPECTATION FAILED',
		500 => 'INTERNAL SERVER ERROR',
		501 => 'NOT IMPLEMENTED',
		502 => 'BAD GATEWAY',
		503 => 'SERVICE UNAVAILABLE',
		504 => 'GATEWAY TIMEOUT',
		505 => 'HTTP VERSION NOT SUPPORTED');

	protected $code;
	protected $message;

	/**
	 * @param int    $code
	 * @param string $message
	 */
	public function __construct($code, $message = null)
	{
		$this->code = (int)$code;
		$this->message = $message ? (string)$message : null;
	}

	/**
	 * Возвращает строковое сообщение, соответствующее числовому значению HTTP-статуса
	 *
	 * @return string
	 */
	protected function get_message($full = false)
	{
		if (!$this->message) {
			$code = isset(self::$messages[$this->code]) ? $this->code : 500;
			$this->message = self::$messages[$code];
		}
		return $full ? $this->as_string() : $this->message;
	}

	/**
	 */
	public function as_response()
	{
		return Net_HTTP::Response($this);
	}

	/**
	 * @return string
	 */
	public function as_string()
	{
		return sprintf('%d %s', $this->code, self::$messages[$this->code]);
	}

	/**
	 * @return string
	 */
	public function __toString()
	{
		return $this->as_string();
	}

	/**
	 * @param string $property
	 *
	 * @return mixed
	 */
	public function __get($property)
	{
		switch ($property) {
			case 'code' :
				return $this->code;
			case 'full_message':
				return $this->get_message(true);
			case 'message' :
				return $this->get_message();
			case 'is_info':
				return $this->code >= 100 && $this->code < 200;
			case 'is_success':
				return $this->code >= 200 && $this->code < 300;
			case 'is_redirect':
				return $this->code >= 300 && $this->code < 400;
			case 'is_error':
				return $this->code >= 400 && $this->code < 600;
			case 'is_client_error':
				return $this->code >= 400 && $this->code < 500;
			case 'is_server_error':
				return $this->code >= 500 && $this->code < 600;
			case 'response' :
				return $this->as_response();
			default:
				throw new Core_MissingPropertyException($property);
		}
	}

	/**
	 * @param string $property
	 * @param        $value
	 *
	 * @return mixed
	 */
	public function __set($property, $value)
	{
		switch ($property) {
			case 'message':
			case 'code':
			case 'full_message':
			case 'is_info':
			case 'is_success':
			case 'is_redirect':
			case 'is_error':
			case 'is_client_error':
			case 'is_server_error':
			case 'response' :
				throw new Core_ReadOnlyPropertyException($property);
			default:
				throw new Core_MissingPropertyException($property);
		}
	}

	/**
	 * @param string $property
	 *
	 * @return boolean
	 */
	public function __isset($property)
	{
		switch ($property) {
			case 'message':
			case 'code':
			case 'full_message':
			case 'is_info':
			case 'is_success':
			case 'is_redirect':
			case 'is_error':
			case 'is_client_error':
			case 'is_server_error':
			case 'response':
				return true;
			default:
				return false;
		}
	}

	/**
	 * @param string $property
	 */
	public function __unset($property)
	{
		switch ($property) {
			case 'message':
				return $this->message = null;
			case 'code':
			case 'full_message':
			case 'is_info':
			case 'is_success':
			case 'is_redirect':
			case 'is_error':
			case 'is_client_error':
			case 'is_server_error':
			case 'response':
				throw new Core_ReadOnlyPropertyException($property);
			default:
				throw new Core_MissingPropertyException($property);
		}
	}

	/**
	 * @param  $to
	 *
	 * @return boolean
	 */
	public function equals($to)
	{
		return
			get_class($this) == get_class($to) &&
			$this->code == $to->code;
	}

}

/**
 * Интерфейс агента для работы с HTTP
 *
 * <p>Интерфейс агента -- минимальный интерфейс, предназначенный для отправки HTTP-запроса и приема HTTP-отклика.</p>
 * <p>Реализация интерфейса может быть выполнена различным образом, например, с использованием модуля Curl: Curl.HTTP</p>
 *
 * @package Net\HTTP
 */
interface Net_HTTP_AgentInterface
{

	/**
	 * Отправляет запрос и возвращает отклик в виде объекта класса Net.HTTP.Response
	 *
	 * @param Net_HTTP_Request $request
	 *
	 * @return Net_HTTP_Response
	 */
	public function send($request);

}

/**
 * Набор полей заголовка запроса и отклика
 *
 * <p>Доступ к полям запроса осуществляется с помощью двух интерфейсов: доступа к свойствам и индексированного доступа.
 * При этом индексированный доступ соответствует строковому, а доступ к свойствам -- объектному представлению значений полей.</p>
 *
 * @package Net\HTTP
 */
class Net_HTTP_Head
	implements Core_IndexedAccessInterface,
	Core_PropertyAccessInterface,
	Core_CallInterface,
	Core_CountInterface,
	Core_EqualityInterface,
	IteratorAggregate
{

	protected $fields = array();

	/**
	 * Конструктор
	 *
	 * @param  $fields
	 */
	public function __construct($fields = array())
	{
		$this->fields($fields);
	}

	/**
	 * Добавляет новое поле
	 *
	 * @param string $name
	 * @param string $value
	 *
	 * @return Net_HTTP_Head
	 */
	public function field($name, $value)
	{
		$name = $this->canonicalize($name);

		$vals = array();
		foreach ((array)$value as $v)
			$vals[] = $v instanceof Time_DateTime ? $v->as_rfc1123() : (string)$v;

		$this->fields[$name] = array_merge(isset($this->fields[$name]) ? (array)$this->fields[$name] : array(), $vals);

		if (count($this->fields[$name]) == 1) {
			$this->fields[$name] = current($this->fields[$name]);
		}

		return $this;
	}

	/**
	 * Добавляет набор полей
	 *
	 * @param  $fields
	 *
	 * @return Net_HTTP_Head
	 */
	public function fields($fields)
	{
		foreach ($fields as $k => $v)
			$this->field($k, $v);
		return $this;
	}

	/**
	 * Возвращает строковое значение поля по его имени
	 *
	 * @param string $index
	 *
	 * @return mixed
	 */
	public function offsetGet($index)
	{
		return isset($this->fields[$name = $this->canonicalize($index)]) ? $this->fields[$name] : null;
	}

	/**
	 * Устанавливает значение поля по его имени
	 *
	 * @param string $index
	 * @param        $value
	 *
	 * @return Net_HTTP_Head
	 */
	public function offsetSet($index, $value)
	{
		$this->field($index, $value);
		return $this;
	}

	/**
	 * Проверяет наличие поля с заданным именем
	 *
	 * @param string $index
	 *
	 * @return boolean
	 */
	public function offsetExists($index)
	{
		return isset($this->fields[$this->canonicalize($index)]);
	}

	/**
	 * Удаляет поле с именем $index из заголовка
	 *
	 * @param string $index
	 *
	 * @return Net_HTTP_Head
	 */
	public function offsetUnset($index)
	{
		if (isset($this->fields[$name = $this->canonicalize($index)])) {
			unset($this->fields[$name]);
		}
		return $this;
	}

	/**
	 * Возвращает общее количество полей
	 *
	 * @return int
	 */
	public function count()
	{
		return count($this->fields);
	}

	/**
	 * Возвращает итератор по полям заголовка
	 *
	 * @return ArrayIterator
	 */
	public function getIterator()
	{
		return new ArrayIterator($this->fields);
	}

	/**
	 * Установка значения поля
	 *
	 * @param string $method
	 * @param array  $args
	 *
	 * @return Net_HTTP_Head
	 */
	public function __call($method, $args)
	{
		$this->__set($method, $args[0]);
		return $this;
	}

	/**
	 * Приводит имя поля к каноническому виду
	 *
	 * @param string $name
	 *
	 * @return string
	 */
	private function canonicalize($name)
	{
		$parts = array_map('strtolower', explode('-', trim(str_replace('_', '-', $name))));

		foreach ($parts as &$part)
			$part = preg_match('{[aeiouyAEIOUY]}', $part) ?
				ucfirst($part) :
				strtoupper($part);

		return implode('-', $parts);
	}

	/**
	 * Возвращает значение поля
	 *
	 * @param string $property
	 *
	 * @return mixed
	 */
	public function __get($property)
	{
		if ($wants_date =
			($property == 'date' ||
				Core_Strings::ends_with($property, '_date'))
		) {
			$property = preg_replace('/_date$/', '', $property);
		}

		return (isset($this->fields[$idx = $this->canonicalize($property)])) ?
			($wants_date ?
				Time::parse($this->fields[$idx], Time::FMT_RFC1123) :
				$this->fields[$idx]) :
			null;
	}

	/**
	 * Устанавливает значение поля
	 *
	 * @param string $property
	 * @param        $value
	 *
	 * @return mixed
	 */
	public function __set($property, $value)
	{
		return $this->field($property, $value);
	}

	/**
	 * Проверяет существование поля
	 *
	 * @param string $property
	 *
	 * @return boolean
	 */
	public function __isset($property)
	{
		return isset($this->fields[$this->canonicalize(preg_replace('/_date$/', '', $property))]);
	}

	/**
	 * Удаляет поле
	 *
	 * @param string $property
	 */
	public function __unset($property)
	{
		if (isset($this->fields[$idx = $this->canonicalize(preg_replace('/_date$/', '', $property))])) {
			unset($this->fields[$idx]);
		}
	}

	/**
	 * @param boolean $as_lines
	 *
	 * @return array
	 */
	public function as_array($as_lines = false)
	{
		if ($as_lines) {
			$r = array();
			foreach ($this->fields as $k => $vals)
				foreach ((array)$vals as $v)
					$r[] = "$k: $v";
			return $r;
		} else {
			return $this->fields;
		}
	}

	/**
	 * @param  $to
	 *
	 * @return boolean
	 */
	public function equals($to)
	{
		return
			get_class($this) == get_class($to) &&
			Core::equals($this->as_array(), $to->as_array());
	}

}

/**
 * Базовый абстрактный класс для запроса и отлика HTTP
 *
 * @abstract
 * @package Net\HTTP
 */
abstract class Net_HTTP_Message
	implements Core_PropertyAccessInterface,
	Core_EqualityInterface,
	Core_CallInterface
{

	protected $protocol = 'HTTP/1.1';
	protected $headers;
	protected $body;

	/**
	 * Конструктор
	 *
	 */
	public function __construct()
	{
		$this->headers = new Net_HTTP_Head();
	}

	/**
	 * Добавляет к заголовку сообщения поля из заданнго итератора
	 *
	 * @param  $headers
	 *
	 * @return Net_HTTP_Request
	 */
	public function headers($headers)
	{
		$this->headers->fields($headers);
		return $this;
	}

	/**
	 * Устанавливает поле заголовка
	 *
	 * @param string $name
	 * @param        $value
	 *
	 * @return Net_HTTP_Request
	 */
	public function header($name, $value)
	{
		$this->headers->field($name, $value);
		return $this;
	}

	/**
	 * Устанавливает имя протокола
	 *
	 * @param string $protocol
	 *
	 * @return Net_HTTP_Message
	 */
	public function protocol($protocol)
	{
		$this->protocol = (string)$protocol;
		return $this;
	}

	/**
	 * Устанавливает тело сообщения
	 *
	 * @param  $body
	 *
	 * @return Net_HTTP_Message
	 */
	public function body($body)
	{
		$this->body = $body;
		return $this;
	}

	/**
	 * @param string $method
	 * @param array  $args
	 *
	 * @return Net_HTTP_Message
	 */
	public function __call($method, $args)
	{
		$this->headers->field($method, $args[0]);
		return $this;
	}

	/**
	 * Возвращает значение свойства
	 *
	 * @param  $property
	 *
	 * @return mixed
	 */
	public function __get($property)
	{
		switch ($property) {
			case 'headers':
			case 'protocol':
			case 'body':
				return $this->$property;
			default:
				throw new Core_MissingPropertyException($property);
		}
	}

	/**
	 * Устанавливает значение свойства
	 *
	 * @param string $property
	 * @param string $value
	 *
	 * @return mixed
	 */
	public function __set($property, $value)
	{
		switch ($property) {
			case 'headers':
				throw new Core_ReadOnlyPropertyException($property);
			case 'protocol':
			case 'body':
			{
				$this->$property($value);
				return $this;
			};
			default:
				throw new Core_MissingPropertyException($property);
		}
	}

	/**
	 * Проверяет установку значения свойства
	 *
	 * @param  $property
	 *
	 * @return boolean
	 */
	public function __isset($property)
	{
		switch ($property) {
			case 'headers':
			case 'protocol':
			case 'body':
				return isset($this->$property);
			default:
				return false;
		}
	}

	/**
	 * Удаляет свойство
	 *
	 * @param string $property
	 */
	public function __unset($property)
	{
		switch ($property) {
			case 'headers':
			case 'protocol':
				throw new Core_UndestroyablePropertyException($property);
			case 'body':
				$this->body = null;
				break;
			default:
				throw new Core_MissingPropertyException($property);
		}
	}

	/**
	 * @param  $to
	 *
	 * @return boolean
	 */
	public function equals($to)
	{
		return
			get_class($to) == get_class($this) &&
			$this->body == $to->body &&
			$this->protocol == $to->protocol &&
			Core::equals($this->headers, $to->headers);
	}

}

/**
 * HTTP-запрос
 *
 * @package Net\HTTP
 */
class Net_HTTP_Request
	extends Net_HTTP_Message
	implements Core_PropertyAccessInterface, Core_IndexedAccessInterface
{

	protected $uri;
	protected $id;

	protected $method = Net_HTTP::GET;

	protected $meta = array();

	protected $parameters = array();
	protected $query = array();

	protected $session;

	protected $content = null;

	/**
	 * Конструктор
	 *
	 * @param string $uri
	 * @param array  $meta
	 */
	public function __construct($uri = '', array $meta = array())
	{
		parent::__construct();
		$uri = trim($uri);
		if ($uri) {
			$this->uri($uri);
		}
		$this->meta = $meta;
	}

	/**
	 * Устанавливает параметры запроса
	 *
	 * @param array $parameters
	 *
	 * @return Net_HTTP_Request
	 */
	public function parameters(array $parameters)
	{
		Core_Arrays::deep_merge_update_inplace($this->parameters, $parameters);
		return $this;
	}

	/**
	 * @param array $parameters
	 *
	 * @return Net_HTTP_Request
	 */
	public function query_parameters(array $parameters)
	{
		foreach ($parameters as $k => $v)
			$this->query[$k] = $v;
		return $this;
	}

	/**
	 * Устанавливает объект сессии
	 *
	 * @param Net_HTTP_SessionInterface $session
	 *
	 * @return Net_HTTP_Request
	 */
	public function session($session = null)
	{
		if ($session instanceof Net_HTTP_SessionInterface) {
			$this->session = $session;
			return $this;
		} else {
			if (empty($this->session)) {
				Core::load('Net.HTTP.Session');
				$this->session = Net_HTTP_Session::Store();
			}
			return $this->session;
		}
	}

	/**
	 * Устанавливает URI запроса
	 *
	 * @param string $uri
	 *
	 * @return Net_HTTP_Request
	 */
	public function uri($uri)
	{
		$this->uri = ($parsed = @parse_url((string)$uri)) ? $parsed : parse_url('/');

		//if ($this->uri['host']) $this->headers->host($this->uri['host']);
//TODO: проверить обнуление query параметров
		if ($parsed && isset($parsed['query'])) {
			$this->parse_query($parsed['query']);
		}
		return $this;
	}

	/**
	 * Устанавливает метод HTTP-запроса
	 *
	 * @param int|string $method
	 *
	 * @return Net_HTTP_Request
	 */
	public function method($method)
	{
		switch (is_string($method) ? strtolower($method) : $method) {
			case 'get':
			case Net_HTTP::GET:
				$this->method = Net_HTTP::GET;
				break;
			case 'put':
			case Net_HTTP::PUT:
				$this->method = Net_HTTP::PUT;
				break;
			case 'post':
			case Net_HTTP::POST:
				$this->method = Net_HTTP::POST;
				break;
			case 'delete':
			case Net_HTTP::DELETE:
				$this->method = Net_HTTP::DELETE;
				break;
			case 'head':
			case Net_HTTP::HEAD:
				$this->method = Net_HTTP::HEAD;
				break;
		}
		return $this;
	}

	public function is_post()
	{
		return $this->method == NET_HTTP::POST;
	}

	public function is_get()
	{
		return $this->method == NET_HTTP::GET;
	}

	public function is_delete()
	{
		return $this->method == NET_HTTP::DELETE;
	}

	public function is_head()
	{
		return $this->method == NET_HTTP::HEAD;
	}

	public function server($name)
	{
		return isset($_SERVER[$name]) ? $_SERVER[$name] : null;
	}

	/**
	 * Возвращает значение свойства
	 *
	 * @param string $property
	 *
	 * @return mixed
	 */
	public function __get($property)
	{
		switch ($property) {
			case 'scheme':
			case 'host':
			case 'path':
			case 'port':
			case 'user':
			case 'pass':
				return $this->uri[$property];
			case 'query':
				return $this->urlencode($this->query);
			case 'headers':
			case 'session':
			case 'meta':
			case 'body':
				return $this->$property;
			case 'content':
				return $this->$property ? $this->$property : ($this->$property = @file_get_contents('php://input'));
			case 'parameters':
				return Core_Arrays::merge($this->query, $this->parameters);
			case 'post_data':
				return $this->urlencode($this->parameters);
			case 'url':
				return $this->compose_url();
			case 'uri':
				return $this->compose_uri();
			case 'urn':
				return $this->compose_urn();
			case 'method_name':
			case 'method':
				return Net_HTTP::method_name_for($this->method);
			case 'method_code':
				return $this->method;
			case 'id':
				if ($this->id) {
					return $this->id;
				}
				Core::load('Text.Process');
				$id = Text_Process::process($this->path, 'translit');
				$id = trim(preg_replace('{[^a-zA-Z0-9]+}ui', '_', $id), '_');
				return $this->id = $id;
			default:
				return parent::__get($property);
		}
	}

	/**
	 * Устанавливает значение свойства
	 *
	 * @param string $property
	 * @param        $value
	 *
	 * @return mixed
	 */
	public function __set($property, $value)
	{
		switch ($property) {
			case 'path':
			case 'host':
			case 'scheme':
			case 'method':
			case 'uri':
			case 'session':
			case 'body':
				$this->$property($value);
				return $this;
			case 'query':
				$this->parse_query($value);
				return $this;
			case 'post_data':
			case 'parameters':
			case 'meta':
			case 'method_name':
			case 'method_code':
			case 'content':
			case 'urn':
			case 'url':
			case 'id':
				throw new Core_ReadOnlyPropertyException($property);
			default:
				return parent::__set($property, $value);
		}
	}

	/**
	 * Проверяет установку значения свойства
	 *
	 * @param string $property
	 *
	 * @return boolean
	 */
	public function __isset($property)
	{
		switch ($property) {
			case 'scheme':
			case 'host':
			case 'path':
				return isset($this->uri[$property]);
			case 'body':
				return isset($this->body);
			case 'uri':
			case 'urn':
			case 'url':
			case 'id':
				return true;
			case 'content':
				return !is_null($this->$property);
			case 'query':
			case 'session':
			case 'meta':
			case 'parameters':
			case 'method':
			case 'headers':
				return isset($this->$property);
			case 'post_data':
				return true;
			case 'method_name':
			case 'method_code':
				return isset($this->method);
			default:
				return parent::__isset($property);
		}
	}

	/**
	 * Удаляет свойство
	 *
	 * @param string $property
	 */
	public function __unset($property)
	{
		switch ($property) {
			case 'scheme':
			case 'host':
			case 'path':
			case 'query':
			case 'uri':
			case 'session':
			case 'meta':
			case 'parameters':
			case 'post_data':
			case 'method':
			case 'method_name':
			case 'method_code':
			case 'headers':
			case 'content':
			case 'urn':
			case 'url':
			case 'id':
				throw new Core_UndestroyablePropertyException($property);
			case 'body':
				$this->body = null;
				break;
			default:
				throw new Core_MissingPropertyExeception($property);
		}
	}

	/**
	 * Диспетчер динамических вызовов
	 *
	 * @param string $method
	 * @param array  $args
	 *
	 * @return mixed
	 */
	public function __call($method, $args)
	{
		switch ($method) {
			case 'path':
			case 'host':
			case 'scheme':
				$this->uri[$method] = (string)$args[0];
				return $this;
			case 'query':
				$this->parse_query($args[0]);
			default:
				return parent::__call($method, $args);
		}
	}




	/**
	 * Доступ к параметрам запроса GET POST и т.д.
	 *
	 * @param string $index
	 *
	 * @return mixed
	 */
//TODO: separate method for cookie, get, post + read all values on creation
	public function offsetGet($index)
	{
		switch (true) {
			case isset($this->parameters[$index]) :
				return $this->parameters[$index];
			case isset($this->query[$index]) :
				return $this->query[$index];
			case isset($_COOKIE[$index]) :
				return $_COOKIE[$index];
			case isset($_POST[$index]) :
				return $_POST[$index];
			case isset($_GET[$index]) :
				return $_GET[$index];
			default:
				return null;
		}
	}

	/**
	 * Устанавливает параметр запроса
	 *
	 * @param string $index
	 *
	 * @return mixed
	 */
	public function offsetSet($index, $value)
	{
		if (isset($this->query[$index])) {
			$this->query[$index] = $value;
		} else {
			$this->parameters[$index] = $value;
		}
		return $this;
	}

	/**
	 * Проверяет установку значения параметра запроса
	 *
	 * @param string $index
	 *
	 * @return mixed
	 */
	public function offsetExists($index)
	{
		return isset($this->parameters[$index]) ||
		isset($this->query[$index]) ||
		isset($_COOKIE[$index]) ||
		isset($_POST[$index]) ||
		isset($_GET[$index]);
	}

	/**
	 * Удаляет установленный параметр
	 *
	 * @param string $index
	 *
	 * @return mixed
	 */
	public function offsetUnset($index)
	{
		unset($this->parameters[$index]);
		return $this;
	}

	/**
	 * Проверяет является ли запрос xhr запросом
	 *
	 * @return boolean
	 */
	public function is_xhr()
	{
		return isset($this->headers['X_REQUESTED_WITH']) && $this->headers['X_REQUESTED_WITH'] == 'XMLHttpRequest';
	}

	/**
	 * Формирует строку URI на основе результатов парсинга URI, указанного при создании объекта
	 *
	 * @return string
	 */
	protected function compose_url()
	{
		$uri = (object)$this->uri;
		return
			($uri->scheme ? "$uri->scheme://" : '') .
			($uri->user ? $uri->user . ($uri->pass ? ":$uri->pass" : '') . '@' : '') .
			($uri->host ? $uri->host . ($uri->port ? ":$uri->port" : '') : '') .
			$this->compose_urn();
	}

	/**
	 * Формирует строку URI на основе результатов парсинга URI, указанного при создании объекта
	 *
	 * @return string
	 */
	protected function compose_uri()
	{
		return $this->compose_urn();
	}

	/**
	 * Формирует строку URN на основе результатов парсинга URI, указанного при создании объекта
	 *
	 * @return string
	 */
	protected function compose_urn()
	{
		$query = $this->urlencode(
			$this->method == Net_HTTP::GET ?
				array_merge($this->query, $this->parameters) :
				$this->query
		);
		return $this->uri['path'] . ($query ? '?' . $query : '');
	}

	/**
	 * @param array $values
	 *
	 * @return string
	 */
	protected function urlencode(array $values)
	{
		$r = array();
		foreach ($values as $k => $v)
			$r[] = urlencode($k) . '=' . urlencode($v);
		return implode('&', $r);
	}

	/**
	 * @param string $query
	 *
	 * @return Net_HTTP_Request
	 */
	protected function parse_query($query)
	{
		$this->query = array();
		if ($query) {
			foreach (explode('&', $query) as $p) {
				list($k, $v) = explode('=', $p);
				if ($k) {
					$this->query[urldecode($k)] = urldecode($v);
				}
			}
		}
		return $this;
	}

}

/**
 * HTTP отклик
 *
 * @package Net\HTTP
 */
class Net_HTTP_Response
	extends Net_HTTP_Message
	implements Core_PropertyAccessInterface,
	Core_IndexedAccessInterface,
	Core_EqualityInterface,
	Core_CallInterface
{

	protected $status;
	protected $url = '';

	/**
	 */
	public function __construct()
	{
		$this->status = Net_HTTP::Status(Net_HTTP::OK);
		parent::__construct(); //TODO:
	}

	/**
	 * @param string $string
	 *
	 * @return Net_HTTP_Response
	 */
	static public function from_string(&$body, &$header)
	{
		$response = new Net_HTTP_Response();
		$header = preg_replace('!HTTP/\d.\d\s+100\s+Continue\s+!', '', $header);
		$m = array();
		$status = '';

		foreach (explode("\n", $header) as $line) {
			if (!$status && preg_match('{^HTTP/\d\.\d\s+(\d+(?:\s+.+))}', $line, $m)) {
				$status = $m[1];
			}
			if (preg_match("{([^:]+):\s*(.*)}", $line, $m)) {
				$response->header($m[1], $m[2]);
			}
		}
		list($code, $message) = explode(" ", preg_replace('{\s+}', ' ', (string)$status), 2);
		return $response->
			status($code, trim($message))->
			body($body);
	}

	/**
	 * Устанавливает статус отклика
	 *
	 * @param Net_HTTP_Status $status
	 *
	 * @return Net_HTTP_Response
	 */
	public function status($code, $message = null)
	{
		$this->status = $code instanceof Net_HTTP_Status ? $code : Net_HTTP::Status($code, $message);
		return $this;
	}

	public function url($url)
	{
		$this->url = $url;
		return $url;
	}

	/**
	 * Диспетчер вызовов
	 *
	 * @param string $method
	 * @param array  $args
	 *
	 * @return Net_HTTP_Response
	 */
	public function __call($method, $args)
	{
		$this->headers->__call($method, $args);
		return $this;
	}

	/**
	 * Возвращает значение свойства
	 *
	 * @param string $property
	 *
	 * @return mixed
	 */
	public function __get($property)
	{
		switch ($property) {
			case 'status':
			case 'url':
				return $this->$property;
			default:
				return parent::__get($property);
		}
	}

	/**
	 * Устанавливает значение свойства
	 *
	 * @param string $property
	 * @param        $value
	 *
	 * @return mixed
	 */
	public function __set($property, $value)
	{
		switch ($property) {
			case 'status':
			case 'url':
				$this->$property($value);
				return $this;
			default:
				return parent::__set($property, $value);
		}
	}

	/**
	 * Проверяет установку значения свойства
	 *
	 * @param string $property
	 *
	 * @return boolean
	 */
	public function __isset($property)
	{
		switch ($property) {
			case 'status':
			case 'url':
				return true;
			default:
				return parent::__isset($property);
		}
	}

	/**
	 * Удаляет свойство
	 *
	 * @param string $property
	 */
	public function __unset($property)
	{
		switch ($property) {
			case 'status':
			case 'url':
				throw new Core_UndestroyablePropertyException($property);
			default:
				parent::__unset($property);
		}
	}

	/**
	 * @param  $to
	 *
	 * @return boolean
	 */
	public function equals($to)
	{
		return
			get_class($this) == get_class($to) &&
			Core::equals($this->status, $to->status) && parent::equals($to);
	}

	/**
	 * @param string $index
	 *
	 * @return mixed
	 */
	public function offsetGet($index)
	{
		return $this->headers[$index];
	}

	/**
	 * @param string $index
	 *
	 * @return mixed
	 */
	public function offsetSet($index, $value)
	{
		$this->headers[$index] = $value;
		return $this;
	}

	/**
	 * @param string $index
	 *
	 * @return mixed
	 */
	public function offsetExists($index)
	{
		return isset($this->headers[$index]);
	}

	/**
	 * @param string $index
	 *
	 * @return mixed
	 */
	public function offsetUnset($index)
	{
		unset($this->headers[$index]);
	}

}

/**
 * Объектное представление HTTP upload
 *
 * <p>Класс реализует стандартные файловые объекты, соответствующие временным файлам HTTP upload,
 * с дополнительным хранением информации об оригинальном имени файла.</p>
 *
 * @package Net\HTTP
 */
class Net_HTTP_Upload
	extends IO_FS_File
	implements Core_PropertyAccessInterface
{

	protected $original_name;
	protected $file_array;

	/**
	 * Конструктор
	 *
	 * @param string $tmp_name
	 * @param string $original_name
	 */
	public function __construct($tmp_path, $original_name, $file_array = array())
	{
		$this->original_name = IO_FS::Path($original_name)->basename;
		$this->file_array = $file_array;
		parent::__construct($tmp_path);
	}

	/**
	 * Возвращает значение свойства
	 *
	 * @param string $property
	 *
	 * @return mixed
	 */
	public function __get($property)
	{
		switch ($property) {
			case 'original_name':
				return $this->original_name;
			case 'file_array':
				return $this->file_array;
			default:
				return parent::__get($property);
		}
	}

	/**
	 * Устанавливает значение свойства
	 *
	 * @param string $property
	 * @param        $value
	 *
	 * @return mixed
	 */
	public function __set($property, $value)
	{
		switch ($property) {
			case 'original_name':
			case 'file_array':
				throw new Core_ReadOnlyPropertyException($property);
			default:
				return parent::__set($property, $value);
		}
	}

	/**
	 * Проверяет наличие значения свойства
	 *
	 * @param string $property
	 *
	 * @return boolean
	 */
	public function __isset($property)
	{
		switch ($property) {
			case 'original_name':
			case 'file_array':
				return true;
			default:
				return parent::__isset($property);
		}
	}

	/**
	 * Удаляет свойство
	 *
	 * @param string $property
	 */
	public function __unset($property)
	{
		switch ($property) {
			case 'original_name':
			case 'file_array':
				throw new Core_UndestroyablePropertyException($property);
			default:
				parent::__unset($property);
		}
	}

	/**
	 * Возвращает MIME-тип файла
	 *
	 * @return string
	 */
	protected function get_mime_type()
	{
		return $this->mime_type ?
			$this->mime_type :
			$this->mime_type = MIME::type_for_file($this->original_name);
	}

}

/**
 * @package Net\HTTP
 */
class Net_HTTP_Download extends Net_HTTP_Response
{

	protected $file;
	protected $cache = false;
	protected $cache_lifetime;
	protected $range = false;

	public function __construct($file, $cache = false, $cache_lifetime = Net_HTTP::DEFAULT_CACHE_LIFETIME)
	{
		parent::__construct();
		$this->file = IO_FS::File($file);
		$this->cache = $cache;
		$this->cache_lifetime = $cache_lifetime;
		$this->setup();
	}

	protected function read_file()
	{
		global $_SERVER;
		$stream = $this->file->open('rb');
		if (!empty($_SERVER["HTTP_RANGE"])) {
			$range = $_SERVER["HTTP_RANGE"];
			$range = str_replace("bytes=", "", $range);
			$range = str_replace("-", "", $range);
			if ($range) {
				$stream->seek($range);
			}
			$this->range = $range;
		}
		$this->body = $stream->read_chunk($this->file->size);
		$stream->close();
	}

	protected function set_headers()
	{
		$ftime = $this->file->stat->mtime->timestamp;
		if ($this->cache) {
			$ftime = 1000000;
		}
		$ftime = gmdate("D, d M Y H:i:s T", $ftime);
		$etime = gmdate("D, d M Y H:i:s T", time() + $this->cache_lifetime);
		$fsize = $this->file->size;
		if ($this->range) {
			$this->status(Net_HTTP::PARTIAL_CONTENT);
			$this['Accept-Ranges'] = 'bytes';
			$this['Content-Range'] = "bytes {$this->range}-" . ($fsize - 1) . "/" . $fsize;
		}
		$name = str_replace(',', '_', $this->file->name);
		$this->
			content_type($this->file->content_type)->
			expires($etime)->
			last_modified($ftime)->
			content_length($fsize - (int)$this->range)->
			content_disposition("inline; filename={$name}")->
			content_transfer_encoding('binary')->
			cache_control("public,private,max-age=1000000")->
			pragma("public");
	}

	protected function setup()
	{
		$this->read_file();
		$this->set_headers();
	}

}

