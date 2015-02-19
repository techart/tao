<?php
/**
 * Service.Google.Adwords
 *
 * @package Service\Google\AdWords
 * @version 0.1.1
 */
Core::load('Service.Google.Auth', 'SOAP');

/**
 * @package Service\Google\AdWords
 */
class Service_Google_AdWords implements Core_ModuleInterface
{
	const VERSION = '0.1.2';
	const NS = "https://adwords.google.com/api/adwords/cm/v201306";
	const FMT_DATE = '%Y%m%d';
	static protected $SERVICE_SUFFIXES = array('job' => 'job', 'idea' => 'o', 'info' => 'info', 'account' => 'mcm');
	const DEFAULT_SERVICE_SUFFIX = 'cm';

	/**
	 * Возвращает wsdl адрес для сервиса
	 *
	 * @param string  $service
	 * @param boolean $sandbox
	 * @param string  $suffix
	 *
	 * @return string
	 */
	static public function wsdl_for($name, $sandbox = false)
	{
		$service = Core_Strings::to_camel_case($name);
		$s = end(explode('_', $name));
		$suffix = isset(self::$SERVICE_SUFFIXES[$s]) ?
			self::$SERVICE_SUFFIXES[$s] : self::DEFAULT_SERVICE_SUFFIX;

		return 'https://' . ($sandbox ? 'adwords-sandbox' : 'adwords') .
		'.google.com/api/adwords/' . $suffix . '/v201306/' .
		$service . 'Service?wsdl';
	}

	/**
	 * @param Service_Google_AdWords_Client $client
	 * @param string                        $wsdl
	 * @param array                         $classmap
	 * @param array                         $options
	 */
	static public function Service(Service_Google_AdWords_Client $client, $wsdl, $classmap = array(), $options = array())
	{
		return new Service_Google_AdWords_Service($client, $wsdl, $classmap);
	}

	/**
	 * Фабричный метод, возвращает объект класса Service.Google.Adwords.Client
	 *
	 * @param array $credentials
	 *
	 * @return Service_Google_Adwords_Client
	 */
	static public function Client(Service_Google_Auth_ClientLogin $auth, array $headers = array())
	{
		return new Service_Google_AdWords_Client($auth, $headers);
	}

	/**
	 * Фабричный метод, возвращает объект класса Service.Google.Adwords.Entity
	 *
	 * @param array $attrs
	 *
	 * @return Service_Google_Adwords_Entity
	 */
	static public function Entity($type = '', array $attrs = array())
	{
		return $type ? new Service_Google_AdWords_Entity($attrs, $type) : new Service_Google_AdWords_Object($attrs);
	}

	/**
	 * Фабричный метод, возвращает объект класса Service.Google.Adwords.Campaign
	 *
	 * @param array $attrs
	 *
	 * @return Service_Google_Adwords_Campaign
	 */
	static public function Selector(array $attrs = array(), $type = null)
	{
		return new Service_Google_AdWords_Selector($attrs, $type);
	}

	/**
	 * Фабричный метод, возвращает объект класса Service.Google.Adwords.Operations
	 *
	 * @return Service_Google_Adwords_Operations
	 */
	static public function Operations()
	{
		return new Service_Google_AdWords_Operations();
	}

	/**
	 * Фабричный метод, возвращает объект класса Service.Google.Adwords.Campaign
	 *
	 * @param array $attrs
	 *
	 * @return Service_Google_Adwords_Campaign
	 */
	static public function Campaign(array $attrs = array())
	{
		return new Service_Google_AdWords_Campaign($attrs);
	}

	/**
	 * Фабричный метод, возвращает объект класса Service.Google.Adwords.Budget
	 *
	 * @param array $attrs
	 *
	 * @return Service_Google_Adwords_Budget
	 */
	static public function Budget(array $attrs = array())
	{
		return new Service_Google_AdWords_Budget($attrs);
	}

	/**
	 * Фабричный метод, возвращает объект класса Service.Google.Adwords.LanguageTarget
	 *
	 * @param array $attrs
	 *
	 * @return Service_Google_Adwords_LanguageTarget
	 */
	static public function LanguageTarget(array $attrs = array())
	{
		return new Service_Google_AdWords_LanguageTarget($attrs);
	}

	/**
	 * Фабричный метод, возвращает объект класса Service.Google.Adwords.NetworkTarget
	 *
	 * @param array $attrs
	 *
	 * @return Service_Google_Adwords_NetworkTarget
	 */
	static public function NetworkTarget(array $attrs = array())
	{
		return new Service_Google_AdWords_NetworkTarget($attrs);
	}

	/**
	 * Фабричный метод, возвращает объект класса Service.Google.Adwords.AdScheduleTarget
	 *
	 * @param array $attrs
	 *
	 * @return Service_Google_Adwords_AdScheduleTarget
	 */
	static public function AdScheduleTarget(array $attrs = array())
	{
		return new Service_Google_AdWords_AdScheduleTarget($attrs);
	}

	/**
	 * Фабричный метод, возвращает объект класса Service.Google.Adwords.CityTarget
	 *
	 * @param array $attrs
	 *
	 * @return Service_Google_Adwords_CityTarget
	 */
	static public function CityTarget(array $attrs = array())
	{
		return new Service_Google_AdWords_CityTarget($attrs);
	}

	/**
	 * Фабричный метод, возвращает объект класса Service.Google.Adwords.GeoTarget
	 *
	 * @param array $attrs
	 *
	 * @return Service_Google_Adwords_GeoTarget
	 */
	static public function GeoTarget(array $attrs = array())
	{
		return new Service_Google_AdWords_GeoTarget($attrs);
	}

	/**
	 * Фабричный метод, возвращает объект класса Service.Google.Adwords.CountryTarget
	 *
	 * @param array $attrs
	 *
	 * @return Service_Google_Adwords_CountryTarget
	 */
	static public function CountryTarget(array $attrs = array())
	{
		return new Service_Google_AdWords_CountryTarget($attrs);
	}

	/**
	 * Фабричный метод, возвращает объект класса Service.Google.Adwords.MetroTarget
	 *
	 * @param array $attrs
	 *
	 * @return Service_Google_Adwords_MetroTarget
	 */
	static public function MetroTarget(array $attrs = array())
	{
		return new Service_Google_AdWords_MetroTarget($attrs);
	}

	/**
	 * Фабричный метод, возвращает объект класса Service.Google.Adwords.ProximityTarget
	 *
	 * @param array $attrs
	 *
	 * @return Service_Google_Adwords_ProximityTarget
	 */
	static public function ProximityTarget(array $attrs = array())
	{
		return new Service_Google_AdWords_ProximityTarget($attrs);
	}

	/**
	 * Фабричный метод, возвращает объект класса Service.Google.Adwords.AdGroup
	 *
	 * @param array $attrs
	 *
	 * @return Service_Google_Adwords_AdGroup
	 */
	static public function AdGroup(array $attrs = array())
	{
		return new Service_Google_AdWords_AdGroup($attrs);
	}

	/**
	 * Фабричный метод, возвращает объект класса Service.Google.Adwords.Ad
	 *
	 * @param array $attrs
	 *
	 * @return Service_Google_Adwords_Ad
	 */
	static public function Ad(array $attrs = array())
	{
		return new Service_Google_AdWords_Ad($attrs);
	}

	/**
	 * Фабричный метод, возвращает объект класса Service.Google.Adwords.Ad
	 *
	 * @param array $attrs
	 *
	 * @return Service_Google_Adwords_Ad
	 */
	static public function TextAd(array $attrs = array())
	{
		return new Service_Google_AdWords_TextAd($attrs);
	}

	/**
	 * Фабричный метод, возвращает объект класса Service.Google.Adwords.Ad
	 *
	 * @param array $attrs
	 *
	 * @return Service_Google_Adwords_AdGroupAd
	 */
	static public function AdGroupAd(array $attrs = array())
	{
		return new Service_Google_AdWords_AdGroupAd($attrs);
	}

	/**
	 * Фабричный метод, возвращает объект класса Service.Google.Adwords.Ad
	 *
	 * @param array $attrs
	 *
	 * @return Service_Google_Adwords_AdGroupAd
	 */
	static public function AdGroupCriterion(array $attrs = array())
	{
		return new Service_Google_AdWords_AdGroupCriterion($attrs);
	}

	/**
	 * Фабричный метод, возвращает объект класса Service.Google.Adwords.Ad
	 *
	 * @param array $attrs
	 *
	 * @return Service_Google_Adwords_AdGroupAd
	 */
	static public function Criterion(array $attrs = array())
	{
		return new Service_Google_AdWords_Criterion($attrs);
	}

	/**
	 * Фабричный метод, возвращает объект класса Service.Google.Adwords.Image
	 *
	 * @param array $attrs
	 *
	 * @return Service_Google_Adwords_Image
	 */
	static public function Image(array $attrs = array())
	{
		return new Service_Google_AdWords_Image($attrs);
	}

	/**
	 * Фабричный метод, возвращает объект класса Service.Google.Adwords.Video
	 *
	 * @param array $attrs
	 *
	 * @return Service_Google_Adwords_Image
	 */
	static public function Video(array $attrs = array())
	{
		return new Service_Google_AdWords_Video($attrs);
	}

	/**
	 * Фабричный метод, возвращает объект класса Service.Google.Adwords.ApiError
	 *
	 * @param array $attrs
	 *
	 * @return Service_Google_Adwords_ApiError
	 */
	static public function ApiError(array $attrs = array())
	{
		return new Service_Google_AdWords_ApiError($attrs);
	}

}

/**
 * @package Service\Google\AdWords
 */
class Service_Google_AdWords_Exception extends Core_Exception
{
	protected $reason;
	protected $type;
	protected $attrs;

	/**
	 */
	public function __construct($message = '', $code = null)
	{
		$res = explode('@', trim($message, '[] '));
		if (count($res) > 1) {
			$type_reason = explode('.', $res[0]);
			$this->type = trim($type_reason[0]);
			$this->reason = trim($type_reason[1]);
			$attrs = explode(';', $res[1]);
			if (isset($attrs[0])) {
				$this->attrs['operand'] = $attrs[0];
			}
			unset($attrs[0]);
			foreach ($attrs as $a) {
				$name_value = explode(':', $a);
				switch (count($name_value)) {
					case 1:
						$this->attrs[] = trim($a);
						break;
					case 2:
						$this->attrs[trim($name_value[0])] = trim($name_value[1]);
					default:
						$name = trim($name_value[0]);
						unset($name_value[0]);
						$this->attrs[$name] = implode(':', $name_value);
				}
			}
		}
		parent::__construct($message, $code);
	}

}

/**
 * Клиент для подключения к сервису
 *
 * @package Service\Google\AdWords
 */
class Service_Google_AdWords_Client implements Core_PropertyAccessInterface
{

	protected $auth;
	protected $headers = array();
	protected $is_sandbox = false;
	protected $services = array();

	/**
	 * Конструктор
	 *
	 * @param Service_Google_Auth_ClientLogin $auth
	 * @param array                           $headers
	 */
	public function __construct(Service_Google_Auth_ClientLogin $auth, array $headers = array())
	{
		$this->auth = $auth;
		if ($auth->token) {
			$headers['authToken'] = $auth->token;
		} else {
			throw new Service_Google_AdWords_Exception('You must login before use AdWords Service');
		}
		$this->headers($headers);
	}

	/**
	 * @param boolean $value
	 *
	 * @return Service_Google_AdWords_Client
	 */
	public function use_sandbox($value = true)
	{
		$this->is_sandbox = $value;
		return $this;
	}

	/**
	 * Устанавливет заголовки
	 *
	 * @return Service_Google_AdWords_Client
	 */
	public function headers(array $headers)
	{
		foreach ($headers as $k => $v)
			$this->header($k, $v);
		return $this;
	}

	/**
	 * @param string $key
	 * @param string $value
	 *
	 * @return Service_Google_AdWords_Client
	 */
	public function header($key, $value)
	{
		$this->headers[Core_Strings::to_camel_case($key, true)] = (string)$value;
		return $this;
	}

	/**
	 * @param  $property
	 */
	protected function build_service($property)
	{
		return Service_Google_AdWords::Service(
			$this,
			Service_Google_AdWords::wsdl_for($property, $this->is_sandbox)
		);
	}

	/**
	 * Доступ на чтение к свойствам объекта
	 *
	 * @param string $property
	 */
	public function __get($property)
	{
		switch (true) {
			case $property == 'is_sandbox':
			case $property == 'headers':
				return $this->$property;
			case $property == 'units':
				$units = 0;
				foreach ($this->services as $s)
					$units += $s->units;
				return $units;
			case $property == 'services':
				return $this->services;
			case Core_Strings::ends_with($property, '_service') :
				$property = str_replace('_service', '', $property);
				if ($this->services[$property] == null) {
					$this->services[$property] = $this->build_service($property);
				}
				return $this->services[$property];
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
		throw new Core_ReadOnlyObjectException($this);
	}

	/**
	 * @param string $property
	 *
	 * @return boolean
	 */
	public function __isset($property)
	{
		return isset($this->$property) || $property == 'units' ||
		isset($this->services[str_replace('_service', '', $property)]);
	}

	/**
	 * @param string $property
	 */
	public function __unset($property)
	{
		throw new Core_ReadOnlyObjectException($this);
	}

}

/**
 * @package Service\Google\AdWords
 */
class Service_Google_AdWords_Service implements Core_PropertyAccessInterface
{

	protected $wsdl;
	protected $classmap = array();
	protected $units = 0;

	protected $soap;

	/**
	 * @param Service_Google_AdWords_Client $client
	 * @param string                        $wsdl
	 * @param array                         $classmap
	 * @param array                         $options
	 */
	public function __construct(Service_Google_AdWords_Client $client, $wsdl, $classmap = array(), $options = array())
	{
		$this->classmap(array_merge(
				array(
					'Campaign', 'Budget', 'LanguageTarget',
					'NetworkTarget', 'AdScheduleTarget', 'CityTarget',
					'GeoTarget', 'CountryTarget', 'MetroTarget', 'ProximityTarget',
					'AdGroup', 'Ad', 'Image', 'Video', 'ApiError'
				),
				$classmap
			)
		);
		$this->wsdl($wsdl);
		$this->setup($client);
		$this->soap = $this->build_soap_client(
			$this->wsdl,
			array_merge(array(
					'classmap' => $this->classmap,
					'trace' => true,
					'features' => SOAP_SINGLE_ELEMENT_ARRAYS), $options
			),
			$client->headers
		);
	}

	/**
	 * @param Service_Google_AdWords_Client $client
	 *
	 * @return Service_Google_AdWords_Service
	 */
	protected function setup(Service_Google_AdWords_Client $client)
	{
	}

	/**
	 * @param       $wsdl
	 * @param array $options
	 * @param array $headers
	 */
	protected function build_soap_client($wsdl, $options = array(), $headers = array())
	{
		$soap = Soap::Client($wsdl, $options);
		$soap->__setSoapHeaders(new SoapHeader($this->__get('ns'), 'RequestHeader', $headers));
		return $soap;
	}

	/**
	 * @param string $wsdl
	 *
	 * @return Service_Google_AdWords_Service
	 */
	protected function wsdl($wsdl)
	{
		$this->wsdl = (string)$wsdl;
		return $this;
	}

	/**
	 * @param array $classmap
	 *
	 * @return Service_Google_AdWords_Service
	 */
	protected function classmap(array $mappings, $prefix = 'Service.Google.AdWords.', $default_class = 'Service.Google.AdWords.Entity')
	{
		foreach ($mappings as $k => $v) {
			if (is_numeric($k)) {
				$this->classmap[$v] = Core_Types::real_class_name_for(
					Core_Types::class_exists($class = $prefix . $v) ?
						$class :
						$default_class
				);
			} else {
				$this->classmap[$k] = $v;
			}
		}
		return $this;
	}

	/**
	 * @param Service_Google_AdWords_Selector $selector
	 *
	 * @return mixed
	 */
	public function get(Service_Google_AdWords_Selector $selector = null, $selector_name = 'serviceSelector')
	{
		$selector = $selector ? $selector : Service_Google_AdWords::Selector()->fields(array('id', 'name'));
		try {
			$result = $this->soap->__soapCall(
				'get',
				array(array($selector_name => $selector->for_soap())),
				array(),
				array(),
				$out_headers
			);
		} catch (SoapFault $e) {
			throw new Service_Google_AdWords_Exception($e->getMessage());
		}
		$this->units += $out_headers['ResponseHeader']->units;
		return $result->rval;
	}

	/**
	 * @param  $operations
	 *
	 * @return mixed
	 */
	public function mutate(Service_Google_AdWords_Operations $operations)
	{
		try {
			$result = $this->soap->__soapCall(
				'mutate',
				array(array('operations' => $operations->for_soap())),
				array(),
				array(),
				$out_headers
			);
		} catch (SoapFault $e) {
			throw new Service_Google_AdWords_Exception($e->getMessage());
		}
		$this->units += $out_headers['ResponseHeader']->units;
		return $result->rval;
	}

	/**
	 * @param string $property
	 *
	 * @return mixed
	 */
	public function __get($property)
	{
		switch ($property) {
			case 'wsdl':
			case 'units':
			case 'soap':
				return $this->$property;
			case 'ns':
				return str_replace('adwords-sandbox', 'adwords', preg_replace('{/\w+\?wsdl}', '', $this->wsdl));
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
			case 'wsdl':
				$this->$property($value);
				return $this;
			case 'units':
			case 'soap':
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
			case 'wsdl':
			case 'units':
			case 'soap':
				return isset($this->$property);
			default:
				return false;
		}
	}

	/**
	 * @param string $property
	 */
	public function __unset($property)
	{
		return $this->__set($property, null);
	}

}

/**
 * @package Service\Google\AdWords
 */
class Service_Google_AdWords_Operations implements Core_EqualityInterface, Core_IndexedAccessInterface, IteratorAggregate
{
	protected $attrs = array();

	/**
	 * @param Service_Google_AdWords_Object $operand
	 *
	 * @return Service_Google_AdWords_Operation
	 */
	public function add($operand)
	{
		$this->attrs[] = array('operator' => 'ADD', 'operand' => $operand);
		return $this;
	}

	/**
	 * @param Service_Google_AdWords_Object $operand
	 *
	 * @return Service_Google_AdWords_Operation
	 */
	public function remove($operand)
	{
		$this->attrs[] = array('operator' => 'REMOVE', 'operand' => $operand);
		return $this;
	}

	/**
	 * @param Service_Google_AdWords_Object $operand
	 *
	 * @return Service_Google_AdWords_Operation
	 */
	public function set($operand)
	{
		$this->attrs[] = array('operator' => 'SET', 'operand' => $operand);
		return $this;
	}

	/**
	 * @return Iterator
	 */
	public function getIterator()
	{
		return new ArrayIterator($this->attrs);
	}

	/**
	 * @return array
	 */
	public function for_soap()
	{
		$res = array();
		foreach ($this->attrs as $k => $v) {
			$res[$k] = array('operator' => $v['operator'], 'operand' =>
				method_exists($v['operand'], 'for_soap') ? $v['operand']->for_soap() : $v['operand']);
		}
		return $res;
	}

	/**
	 * @param  $index
	 *
	 * @return mixed
	 */
	public function offsetGet($index)
	{
		return $this->attrs[$index];
	}

	/**
	 * @param  $index
	 * @param  $value
	 *
	 * @return mixed
	 */
	public function offsetSet($index, $value)
	{
		throw new Core_ReadOnlyIndexedPropertyException($index);
	}

	/**
	 * @param  $index
	 *
	 * @return boolean
	 */
	public function offsetExists($index)
	{
		return isset($this->attrs[$index]);
	}

	/**
	 * @param  $index
	 */
	public function offsetUnset($index)
	{
		return $this->offsetSet($index, null);
	}

	/**
	 * @param  $to
	 *
	 * @return boolean
	 */
	public function equals($to)
	{
		return ($to instanceof self) &&
		Core::equals($this->as_array(), $to->as_array());
	}

}

/**
 * Базовый класс для soap объектов
 *
 * @package Service\Google\AdWords
 */
class Service_Google_AdWords_Object implements
	Core_PropertyAccessInterface, Core_IndexedAccessInterface, Core_CallInterface, IteratorAggregate, Core_EqualityInterface
{

	protected $attrs = array();

	/**
	 * Конструктор
	 *
	 * @param array $attrs
	 */
	public function __construct(array $attrs = array())
	{
		foreach ($attrs as $k => $v)
			$this->__set($k, $v);
	}

	/**
	 * Возвращает итератор
	 *
	 * @return Iterator
	 */
	public function getIterator()
	{
		return new ArrayIterator($this->attrs);
	}

	/**
	 * Доступ на чтение к свойствам объекта
	 *
	 * @param string $property
	 */
	public function __get($property)
	{
		switch (true) {
			case method_exists($this, $m = "get_$property"):
				return $this->$m();
			case $this->is_date_property($property):
				return Time::DateTime($this->attrs[$this->attr_name($property)]);
			default:
				return $this->attrs[$this->attr_name($property)];
		}
	}

	/**
	 * Доступ на запись к свойствам объекта
	 *
	 * @param string $property
	 */
	public function __set($property, $value)
	{
		switch (true) {
			case method_exists($this, $m = "set_$property"):
				$this->$m($value);
				break;
			case ($value instanceof Time_DateTime):
				$this->attrs[$this->attr_name($property)] = $value->format(Service_Google_AdWords::FMT_DATE);
				break;
			default:
				$this->attrs[$this->attr_name($property)] = $value;
				break;
		}
		return $this;
	}

	/**
	 * Проверяет установленно ли свойство объекта
	 *
	 * @param string $property
	 */
	public function __isset($property)
	{
		return isset($this->attrs[$this->attr_name($property)]);
	}

	/**
	 * Очищает свойство объекта
	 *
	 * @param string $property
	 */
	public function __unset($property)
	{
		unset($this->attrs[$property]);
	}

	/**
	 * Установка свойства объекта через вызов метода
	 *
	 * @param string $method
	 * @param array  $parms
	 */
	public function __call($method, $parms)
	{
		$this->__set($method, count($parms) > 1 ? $parms : $parms[0]);
		return $this;
	}

	/**
	 * Индексный доступ на чтение к свойствам объекта, без CamelCase преобразование и ковертации
	 *
	 * @param string $name
	 */
	public function offsetGet($name)
	{
		return $this->attrs[$name];
	}

	/**
	 * Индексный доступ на запись к свойствам обхекта
	 *
	 * @param string $name
	 * @param        $value
	 */
	public function offsetSet($name, $value)
	{
		$this->attrs[$name] = $value;
		return $this;
	}

	/**
	 * Проверяет существование свойства
	 *
	 * @param string $name
	 */
	public function offsetExists($name)
	{
		return array_key_exists($name, $this->attrs);
	}

	/**
	 * Очищает свойство объекта
	 *
	 * @param string $name
	 */
	public function offsetUnset($name)
	{
		unset($this->attrs[$property]);
	}

	/**
	 * Конвертирует имя свойства
	 *
	 * @param string $property
	 */
	protected function attr_name($property)
	{
		return Core_Strings::to_camel_case($property, true);
	}

	/**
	 * @param  $values
	 *
	 * @return Service_Google_AdWords_Object
	 */
	public function update_with($values)
	{
		foreach ($values as $k => $v)
			$this->__set($k, $v);
		return $this;
	}

	/**
	 * @param string $property
	 *
	 * @return boolean
	 */
	protected function is_date_property($property)
	{
		return Core_Strings::ends_with($property, 'date');
	}

	/**
	 * Преобразует Service.Google.AdWords.Object в массив для soap
	 *
	 */
	public function for_soap()
	{
		return $this->as_array();
	}

	/**
	 * Преобразует Service.Google.AdWords.Object в массив с учетом вложенных массивов
	 *
	 */
	public function as_array()
	{
		$result = array();
		foreach ($this as $k => $v)
			$result[$k] = $this->convert_element($v);
		return $result;
	}

	/**
	 * @param  $element
	 */
	protected function convert_element($element)
	{
		switch (true) {
			case method_exists($element, 'as_array'):
				return $element->as_array();
			case is_array($element):
				return Core::with(new Service_Google_AdWords_Object($element))->as_array();
			default:
				return $element;
		}
	}

	/**
	 * @param  $to
	 *
	 * @return boolean
	 */
	public function equals($to)
	{
		return ($to instanceof self) &&
		Core::equals($this->as_array(), $to->as_array());
	}
}

/**
 * @package Service\Google\AdWords
 */
class Service_Google_AdWords_Selector extends Service_Google_AdWords_Object
{

	public function fields(array $data = array())
	{
		foreach ($data as &$v)
			$v = Core_Strings::capitalize($v);
		return $this->__call('fields', array($data));
	}

}

/**
 * @package Service\Google\AdWords
 */
class Service_Google_AdWords_Entity extends Service_Google_AdWords_Object
{

	protected $type;

	/**
	 * @param array $attrs
	 */
	public function __construct(array $attrs = array(), $type = '')
	{
		if ($type) {
			$this->type = $type;
		}
		parent::__construct($attrs);
	}

	/**
	 * @return SoapVar
	 */
	protected function get___value()
	{
		return new SoapVar($this->as_array(), 0, $this->get___type(), Service_Google_AdWords::NS);
	}

	/**
	 * @return string
	 */
	public function get___type()
	{
		return $this->type ? $this->type : $this->wsdl_type();
	}

	/**
	 * @param  $value
	 */
	public function set___type($value)
	{
		$this->type = (string)$value;
		return $this;
	}

	/**
	 */
	protected function wsdl_type()
	{
		preg_match('{_([^_]+)$}', get_class($this), $m);
		return $m[1];
	}

	/**
	 */
	public function for_soap()
	{
		return $this->__value;
	}

	/**
	 */
	protected function convert_element($element)
	{
		switch (true) {
			case $element instanceof self:
				return $element->__value;
			case method_exists($element, 'as_array'):
				return $element->as_array();
			case is_array($element):
				return Core::with(new Service_Google_AdWords_Entity($element))->as_array();
			default:
				return $element;
		}
	}

}

/**
 * @package Service\Google\AdWords
 */
class Service_Google_AdWords_DateRange extends Service_Google_AdWords_Entity
{
	protected $attrs = array(
		'min' => null,
		'max' => null
	);

	/**
	 * @param Time_DateTime $min
	 * @param TimeDateTime  $max
	 */
	public function __construct(Time_DateTime $min, Time_DateTime $max)
	{
		$this->set_min($min)->set_max($max);
	}

	/**
	 * @return Time_DateTime
	 */
	protected function get_max()
	{
		return Time::DateTime($this->attrs['max']);
	}

	/**
	 * @return Time_DateTime
	 */
	protected function get_min()
	{
		return Time::DateTime($this->attrs['min']);
	}

	/**
	 * @param Time_DateTime $value
	 *
	 * @return Time_DateTime
	 */
	protected function set_max(Time_DateTime $value)
	{
		$this->attrs['max'] = $value->format(Service_Google_Adwords::FMT_DATE);
		return $this;
	}

	/**
	 * @param Time_DateTime $value
	 *
	 * @return Time_DateTime
	 */
	protected function set_min(Time_DateTime $value)
	{
		$this->attrs['min'] = $value->format(Service_Google_Adwords::FMT_DATE);
		return $this;
	}

}

/**
 * @package Service\Google\AdWords
 */
class Service_Google_AdWords_Campaign extends Service_Google_AdWords_Entity
{
}

/**
 * @package Service\Google\AdWords
 */
class Service_Google_AdWords_Budget extends Service_Google_AdWords_Entity
{
}

/**
 * @package Service\Google\AdWords
 */
class Service_Google_AdWords_LanguageTarget extends Service_Google_AdWords_Entity
{
}

/**
 * @package Service\Google\AdWords
 */
class Service_Google_AdWords_NetworkTarget extends Service_Google_AdWords_Entity
{
}

/**
 * @package Service\Google\AdWords
 */
class Service_Google_AdWords_AdScheduleTarget extends Service_Google_AdWords_Entity
{
}

/**
 * @package Service\Google\AdWords
 */
class Service_Google_AdWords_CityTarget extends Service_Google_AdWords_Entity
{
}

/**
 * @package Service\Google\AdWords
 */
class Service_Google_AdWords_GeoTarget extends Service_Google_AdWords_Entity
{
}

/**
 * @package Service\Google\AdWords
 */
class Service_Google_AdWords_CountryTarget extends Service_Google_AdWords_Entity
{
}

/**
 * @package Service\Google\AdWords
 */
class Service_Google_AdWords_MetroTarget extends Service_Google_AdWords_Entity
{
}

/**
 * @package Service\Google\AdWords
 */
class Service_Google_AdWords_ProximityTarget extends Service_Google_AdWords_Entity
{
}

/**
 * @package Service\Google\AdWords
 */
class Service_Google_AdWords_AdGroup extends Service_Google_AdWords_Entity
{
}

/**
 * @package Service\Google\AdWords
 */
class Service_Google_AdWords_Ad extends Service_Google_AdWords_Entity
{
}

/**
 * @package Service\Google\AdWords
 */
class Service_Google_AdWords_TextAd extends Service_Google_AdWords_Entity
{
}

/**
 * @package Service\Google\AdWords
 */
class Service_Google_AdWords_AdGroupAd extends Service_Google_AdWords_Entity
{
}

/**
 * @package Service\Google\AdWords
 */
class Service_Google_AdWords_AdGroupCriterion extends Service_Google_AdWords_Entity
{
}

/**
 * @package Service\Google\AdWords
 */
class Service_Google_AdWords_Criterion extends Service_Google_AdWords_Entity
{
}

/**
 * Класс для маппинга
 *
 * @package Service\Google\AdWords
 */
class Service_Google_AdWords_Image extends Service_Google_AdWords_Entity
{
}

/**
 * Класс для маппинга
 *
 * @package Service\Google\AdWords
 */
class Service_Google_AdWords_Video extends Service_Google_AdWords_Entity
{
}

/**
 * Класс для маппинга
 *
 * @package Service\Google\AdWords
 */
class Service_Google_AdWords_ApiError extends Service_Google_AdWords_Entity
{
}
