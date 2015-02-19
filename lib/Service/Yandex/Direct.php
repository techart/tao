<?php
/**
 * Service.Yandex.Direct
 *
 * @package Service\Yandex\Direct
 * @version 0.3.0
 */
Core::load('SOAP');

/**
 * @package Service\Yandex\Direct
 */
class Service_Yandex_Direct implements Core_ModuleInterface
{

	const VERSION = '0.3.0';

	static protected $options = array(
		'delay' => 5,
		'default_wsdl' => 'https://api.direct.yandex.ru/wsdl/v4/',
		'wsdl' => 'https://api.direct.yandex.ru/wsdl/v4/'
	);

	static private $api;
	static private $supress_exceptions = false;

	/**
	 * Выполняет инициализацию модуля
	 *
	 * @param array $options
	 */
	static public function initialize(array $options = array())
	{
		return self::options($options);
	}

	/**
	 * Устанавливает значения списка опций, возвращает список значений всех опций
	 *
	 * @param array $options
	 *
	 * @return mixed
	 */
	static public function options(array $options = array())
	{
		return self::$options = array_merge(self::$options, $options);
	}

	/**
	 * Устанавливает опцию или возвращает ее значение
	 *
	 * @param string $name
	 * @param        $value
	 *
	 * @return mixed
	 */
	static public function option($name, $value = null)
	{
		if (is_null($value)) {
			return self::$options[$name];
		} else {
			return self::$options[$name] = $value;
		}
	}

	/**
	 * @param array $options
	 *
	 * @return Service_Yandex_Direct_APIMapper
	 */
	static public function connect(array $options = array())
	{
		if (!self::$api) {
			try {
				self::$api = new Service_Yandex_Direct_APIMapper($options);
			} catch (SoapException $e) {
				if (!self::supress_exceptions()) {
					throw $e;
				}
			}
		}
		return self::$api;
	}

	/**
	 * @return Service_Yandex_Direct_API
	 */
	static public function api()
	{
		return self::$api;
	}

	/**
	 * @return Service_Yandex_Direct_Campaign
	 */
	static public function Campaign($entity)
	{
		return new Service_Yandex_Direct_Campaign($entity);
	}

	/**
	 * @return Service_Yandex_Direct_Banner
	 */
	static public function Banner($entity)
	{
		return new Service_Yandex_Direct_Banner($entity);
	}

	/**
	 * @return Service_Yandex_Direct_Phrase
	 */
	static public function Phrase($entity)
	{
		return new Service_Yandex_Direct_Phrase($entity);
	}

	static public function supress_exceptions($flag = null)
	{
		if ($flag !== null) {
			self::$supress_exceptions = (boolean)$flag;
		}
		return self::$supress_exceptions;
	}

	/**
	 * @param string $name
	 *
	 * @return string
	 */
	static public function attr_name_for($name)
	{
		static $cache = array();
		return isset($cache[$name]) ?
			$cache[$name] :
			$cache[$name] = ucfirst(Core_Strings::to_camel_case($name, false));
	}

}

/**
 * @package Service\Yandex\Direct
 */
class Service_Yandex_Direct_Exception extends Core_Exception
{
}

/**
 * @package Service\Yandex\Direct
 */
class Service_Yandex_Direct_BadCampaignScopeException extends Service_Yandex_Direct_Exception
{
}

/**
 * @package Service\Yandex\Direct
 */
class Service_Yandex_Direct_BadEntityException extends Service_Yandex_Direct_Exception
{
}

/**
 * @abstract
 * @package Service\Yandex\Direct
 */
abstract class Service_Yandex_Direct_APIConsumer
{

	/**
	 * @return Service_Yandex_Direct_APIMapper
	 */
	protected function api()
	{
		return Service_Yandex_Direct::api();
	}

}

/**
 * @package Service\Yandex\Direct
 */
class Service_Yandex_Direct_Entity extends Service_Yandex_Direct_APIConsumer
	implements Core_PropertyAccessInterface, Core_IndexedAccessInterface
{

	protected $entity;

	/**
	 * @param object $entity
	 */
	public function __construct($entity)
	{
		switch (true) {
			case $entity instanceof stdclass:
				$this->entity = $entity;
				break;
			case is_array($entity):
				$this->entity = Core::object($entity);
				break;
			default:
				throw new Service_Yandex_Direct_BadEntityException();
		}
	}

	/**
	 * @param string $property
	 *
	 * @return mixed
	 */
	public function __get($property)
	{
		switch (true) {
			case $property === '__entity':
				return $this->entity;
			case method_exists($this, $m = "get_$property"):
				return $this->$m();
			default:
				$n = Service_Yandex_Direct::attr_name_for($property);
				return isset($this->entity->$n) ? $this->entity->$n : null;
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
		if (method_exists($this, $m = "set_$property")) {
			$this->$m($value);
		} else {
			throw new Core_ReadOnlyObjectException($this);
		}
	}

	/**
	 * @param string $property
	 *
	 * @return boolean
	 */
	public function __isset($property)
	{
		switch (true) {
			case $property === '__entity':
				return true;
			case method_exists($this, $m = "isset_$property"):
				return $this->$m();
			case method_exists($this, $m = "get_$property"):
				return true;
			default:
				$n = Service_Yandex_Direct::attr_name_for($property);
				return isset($this->entity->$n);
		}
	}

	/**
	 * @param string $property
	 */
	public function __unset($property)
	{
		return $this->__set($property, null);
	}

	/**
	 * @return mixed
	 */
	public function offsetGet($property)
	{
		return isset($this->entity->$property) ? $this->entity->$property : null;
	}

	/**
	 * @param string $property
	 * @param        $value
	 */
	public function offsetSet($property, $value)
	{
		throw new Core_ReadOnlyObjectException($this);
	}

	/**
	 * @param string $property
	 *
	 * @return boolean
	 */
	public function offsetExists($property)
	{
		return isset($this->entity->$property);
	}

	/**
	 * @param string $property
	 */
	public function offsetUnset($property)
	{
		throw new Core_ReadOnlyObjectException($this);
	}

	/**
	 * @param array $values
	 *
	 * @return Service_Yandex_Direct_Entity
	 */
	public function assign(array $values)
	{
		foreach ($values as $k => $v)
			$this->__set($k, $v);
		return $this;
	}

}

/**
 * @abstract
 * @package Service\Yandex\Direct
 */
abstract class Service_Yandex_Direct_Collection
	extends Service_Yandex_Direct_APIConsumer
	implements Core_PropertyAccessInterface,
	Core_IndexedAccessInterface,
	IteratorAggregate,
	Countable
{

	protected $items = array();

	/**
	 * @param  $items
	 */
	public function __construct($items)
	{
		if ($items instanceof Service_Yandex_Direct_Entity) {
			$items = (array)$items;
		}

		foreach ($items as $item)
			$this->items[] = $this->unwrap($item);
	}

	/**
	 * @param string $property
	 *
	 * @return mixed
	 */
	public function __get($property)
	{
		switch (true) {
			case $property === '__items':
				return $this->items;
			case method_exists($this, $m = "get_$property"):
				return property_exists($this, $property) ?
					($this->$property === null ? $this->$property = $this->$m() : $this->$property) : $this->$m();
			default:
				throw new Core_MissingPropertyException($property);
		}
	}

	/**
	 * @param string $property
	 * @param        $value
	 *
	 * @return Service_Yandex_Direct_Collection
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
		switch (true) {
			case $property == '__items':
				return true;
			case method_exists($this, $m = "isset_$property"):
				return $this->$m();
			case method_exists($this, "get_$property"):
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
		throw new Core_ReadOnlyObjectException($this);
	}

	/**
	 * @param int $index
	 *
	 * @return mixed
	 */
	public function offsetGet($index)
	{
		return isset($this->items[$index]) ? $this->wrap($this->items[$index]) : null;
	}

	/**
	 * @param int $index
	 *
	 * @return mixed
	 */
	public function offsetSet($index, $value)
	{
		throw new Core_ReadOnlyObjectException($this);
	}

	/**
	 * @param int $index
	 */
	public function offsetUnset($index)
	{
		throw new Core_ReadOnlyObjectException($this);
	}

	/**
	 * @param int $index
	 *
	 * @return boolean
	 */
	public function offsetExists($index)
	{
		return isset($this->items[$index]);
	}

	/**
	 * @param array $conditions
	 *
	 * @return Service_Yandex_Direct_Collection
	 */
	public function where(array $conditions)
	{
		$cc = array();
		foreach ($conditions as $k => $v) {
			$k = explode(' ', $k);
			$cc[] = array(Service_Yandex_Direct::attr_name_for($k[0]), isset($k[1]) ? $k[1] : '=', $v);
		}

		$res = array();
		$limit = count($cc);

		foreach ($this->items as $item) {
			$passed = 0;

			foreach ($cc as $cond) {
				$attr = $cond[0];

				switch ($cond[1]) {
					case '~':
						foreach ((array)$cond[2] as $v)
							if ($r = preg_match($v, (string)$item->$attr)) {
								break;
							}
						if ($r) {
							$passed++;
						}
						break;
					case '=':
						foreach ((array)$cond[2] as $v)
							if ($r = ($v == $item->$attr)) {
								break;
							}
						if ($r) {
							$passed++;
						}
						break;
					case 'in':
						if (array_search($item->$attr, $cond[2]) !== false) {
							$passed++;
						}
						break;
					case '!in':
						if (array_search($item->$attr, $cond[2]) === false) {
							$passed++;
						}
						break;
					case 'not':
						if (!isset($item->$attr) || !$item->$attr) {
							$passed++;
						}
						break;
				}
			}
			if ($passed == $limit) {
				$res[] = $item;
			}
		}

		return Core::make($this, $res);
	}

	/**
	 * @param array $values
	 *
	 * @return Service_Yandex_Direct_Collection
	 */
	public function assign(array $values)
	{
		foreach ($this as $v)
			$v->assign($values);
		return $this;
	}

	/**
	 * @return Servuce_Yandex_Direct_Collection
	 */
	public function append($item)
	{
		switch (true) {
			case $item instanceof Service_Yandex_Direct_Entity:
				$this->items[] = $this->unwrap($item);
				break;
			case $item instanceof stdclass:
				$this->items[] = $item;
				break;
			case is_array($item):
				$this->items[] = new stdclass($item);
				break;
			default:
				throw new Service_Yandex_Direct_BadEntityException();
		}
		return $this;
	}

	/**
	 * @param object $entity
	 *
	 * @return object
	 */
	protected function wrap($entity)
	{
		return $entity;
	}

	protected function unwrap($entity)
	{
		return $entity;
	}

	/**
	 * @param int $index
	 *
	 * @return object
	 */
	public function get($index)
	{
		return $this[(int)$index];
	}

	/**
	 * @return Service_Yandex_Direct_Iterator
	 */
	public function getIterator()
	{
		return new Service_Yandex_Direct_Iterator($this);
	}

	/**
	 * @return int
	 */
	public function count()
	{
		return count($this->items);
	}

}

/**
 * @package Service\Yandex\Direct
 */
class Service_Yandex_Direct_EntityCollection extends Service_Yandex_Direct_Collection
{

	/**
	 * @param object $entity
	 *
	 * @return Service_Yandex_Direct_Entity
	 */
	protected function wrap($entity)
	{
		return new Service_Yandex_Direct_Entity($entity);
	}

	/**
	 * @param  $entity
	 *
	 * @return array
	 */
	protected function unwrap($entity)
	{
		switch (true) {
			case $entity instanceof Service_Yandex_Direct_Entity:
				return $entity->__entity;
			case is_object($entity) || is_array($entity):
				return $entity;
			default:
				throw new Service_Yandex_Direct_BadEntityException('Unknown entity type');
		}
	}

}

/**
 * @package Service\Yandex\Direct
 */
abstract class Service_Yandex_Direct_IndexedCollection extends Service_Yandex_Direct_EntityCollection
{

	protected $ids = array();

	/**
	 * @param array $items
	 */
	public function __construct(array $items)
	{
		parent::__construct($items);
		$this->actualize_index();
	}

	/**
	 * @param string $property
	 *
	 * @return mixed
	 */
	public function __get($property)
	{
		switch ($property) {
			case '__ids':
				return array_keys($this->ids);
			default:
				return parent::__get($property);
		}
	}

	/**
	 * @param string $property
	 *
	 * @return boolean
	 */
	public function __isset($property)
	{
		return $property == '__ids' || parent::__isset($property);
	}

	/**
	 * @param int $id
	 *
	 * @return Service_Yandex_Direct_Entity
	 */
	public function by_id($id)
	{
		return isset($this->ids[$id]) ? $this->wrap($this->items[$this->ids[$id]]) : null;
	}

	/**
	 * @param  $item
	 *
	 * @return Service_Yandex_Direct_IndexedCollection
	 */
	public function append($item)
	{
		parent::append($item);
		if ($id = $this->entity_id_for($item)) {
			$this->ids[$id] = count($this->items) - 1;
		}
		return $this;
	}

	/**
	 * @return Service_Yandex_Direct_IndexedCollection
	 */
	protected function actualize_index()
	{
		foreach ($this->items as $k => $v)
			if ($id = $this->entity_id_for($v)) {
				$this->ids[$id] = $k;
			}
		return $this;
	}

	/**
	 * @abstract
	 *
	 * @param object $entity
	 * @param        $value
	 *
	 * @return int
	 */
	abstract protected function entity_id_for($entity, $value = null);

}

/**
 * @package Service\Yandex\Direct
 */
class Service_Yandex_Direct_Iterator implements Iterator
{

	protected $collection;
	protected $idx = 0;

	/**
	 * @param Service_Yandex_Direct_Collection $collection
	 */
	public function __construct(Service_Yandex_Direct_Collection $collection)
	{
		$this->collection = $collection;
	}

	/**
	 * @return boolean
	 */
	public function valid()
	{
		return isset($this->collection[$this->idx]);
	}

	/**
	 * @return Service_Yandex_Direct_Object
	 */
	public function current()
	{
		return $this->collection[$this->idx];
	}

	/**
	 */
	public function next()
	{
		$this->idx++;
	}

	/**
	 * @return int
	 */
	public function key()
	{
		return $this->idx;
	}

	/**
	 */
	public function rewind()
	{
		$this->idx = 0;
	}

}

/**
 * @abstract
 * @package Service\Yandex\Direct
 */
abstract class Service_Yandex_Direct_Mapper
	extends Service_Yandex_Direct_APIConsumer
	implements Core_PropertyAccessInterface
{

	protected $parent;

	public function __construct($parent = null)
	{
		$this->parent = $parent;
	}

	/**
	 * @param string $property
	 *
	 * @return mixed
	 */
	public function __get($property)
	{
		if (method_exists($this, $m = "get_$property")) {
			return property_exists($this, $property) ?
				($this->$property === null ? $this->$property = $this->$m() : $this->$property) : $this->$m();
		} else {
			throw new Core_MissingPropertyException($property);
		}
	}

	/**
	 * @param string $property
	 * @param        $value
	 *
	 * @return Service_Yandex_Direct_Mapper
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
		return method_exists($this, "get_$property");
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
 * @abstract
 * @package Service\Yandex\Direct
 */
abstract class Service_Yandex_Direct_Filter
	extends Service_Yandex_Direct_APIConsumer
	implements Core_CallInterface
{

	private $parms = array();

	/**
	 * @param string $method
	 * @param array  $args
	 *
	 * @return Service_Yandex_Direct_Filter
	 */
	public function __call($method, $args)
	{
		if (method_exists($this, $m = "set_$method")) {
			$this->$m($args[0]);
		} else {
			$n = Service_Yandex_Direct::attr_name_for($method);
			if (($v = $this->value_for($n, $args[0])) === null) {
				throw new Core_MissingMethodException($method);
			} else {
				$this->parms[$n] = $v;
			}
		}
		return $this;
	}

	/**
	 * @return array
	 */
	public function as_array()
	{
		return $this->parms;
	}

	/**
	 * @param string $name
	 * @param        $value
	 *
	 * @return mixed
	 */
	protected function value_for($name, $value)
	{
		return $value;
	}

}

/**
 * @package Service\Yandex\Direct
 */
class Service_Yandex_Direct_APIMapper extends Service_Yandex_Direct_Mapper
{

	protected $soap;
	protected $last_call_ts = 0;
	protected $last_error;

	protected $campaigns;
	protected $forecasts;
	protected $regions;
	protected $categories;
	protected $banners;
	protected $clients;

	/**
	 * @param array $options
	 */
	public function __construct(array $options)
	{
		$this->soap = SOAP::Client(
			Service_Yandex_Direct::option('wsdl'),
			array_merge($options, array(
					'features' => SOAP_SINGLE_ELEMENT_ARRAYS,
					'trace' => 1)
			)
		);
		if (empty($options['local_cert'])
			&& !empty($options['login'])
			&& !empty($options['token'])
			&& !empty($options['application_id'])
		) {
			$headers = array();
			$headers[] = new SoapHeader(Service_Yandex_Direct::option('wsdl'), 'login', $options['login']);
			$headers[] = new SoapHeader(Service_Yandex_Direct::option('wsdl'), 'token', $options['token']);
			$headers[] = new SoapHeader(Service_Yandex_Direct::option('wsdl'), 'application_id', $options['application_id']);
			$this->soap->__setSoapHeaders($headers);
		}
	}

	/**
	 * @return Service_Yandex_Direct_CampaignsCollection
	 */
	public function all_campaigns()
	{
		return new Service_Yandex_Direct_CampaignsCollection($this->api()->GetCampaignsList());
	}

	/**
	 * @return Service_Yandex_Direct_CampaignsCollection
	 */
	public function campaigns_for()
	{
		return new Service_Yandex_Direct_CampaignsCollection(
			$this->api()->GetCampaignsList(Core::normalize_args(func_get_args())));
	}

	/**
	 * @return Service_Yandex_Direct_CampaignsCollection
	 */
	public function campaigns($items = array())
	{
		return new Service_Yandex_Direct_CampaignsCollection($items);
	}

	/**
	 * @return Service_Yandex_Direct_BalanceCollection
	 */
	public function balance_for()
	{
		return new Service_Yandex_Direct_BalanceCollection(
			$this->api()->GetBalance(Core::normalize_args(func_get_args())));
	}

	/**
	 * @return Service_Yandex_Direct_ClientsCollection
	 */
	public function all_clients()
	{
		return new Service_Yandex_Direct_ClientsCollection($this->api()->GetClientsList());
	}

	/**
	 * @param string $login
	 *
	 * @return Service_Yandex_Direct_Client
	 */
	public function client_named($login)
	{
		return new Service_Yandex_Direct_Client($this->api()->GetClientInfo($login));
	}

	/**
	 * @return Service_Yandex_Direct_BannersCollection
	 */
	public function find_banners()
	{
		return new Service_Yandex_Direct_BannersCollection(
			$this->api()->GetBanners(array('BannerIDS' => func_get_args())), 0);
	}

	/**
	 * @return Service_Yandex_Direct_ForecastsMapper
	 */
	protected function get_forecasts()
	{
		return new Service_Yandex_Direct_ForecastsMapper();
	}

	/**
	 * @return Service_Yandex_Direct_RegionsCollection
	 */
	protected function get_regions()
	{
		return new Service_Yandex_Direct_RegionsCollection($this->api()->GetRegions());
	}

	/**
	 * @return Service_Yandex_Direct_CategoriesMapper
	 */
	protected function get_categories()
	{
		return new Service_Yandex_Direct_CategoriesCollection($this->api()->GetRubrics());
	}

	/**
	 * @param string $method
	 * @param array  $parms
	 *
	 * @return mixed
	 */
	public function __call($method, $args)
	{
		try {
			return $this->
				delay()->
				soap->__call(ucfirst(Core_Strings::to_camel_case($method)), $args);
		} catch (SoapException $e) {
			if (Service_Yandex_Direct::supress_exceptions()) {
				$this->last_error = $e;
				return null;
			} else {
				throw $e;
			}
		}
	}

	/**
	 * @return boolean
	 */
	public function ping()
	{
		return $this->soap->PingAPI() === 1;
	}

	/**
	 * @return float
	 */
	protected function get_version()
	{
		return $this->GetVersion();
	}

	/**
	 * @return Soap_Client
	 */
	protected function get_soap()
	{
		return $this->soap;
	}

	/**
	 * @return SoapException
	 */
	protected function get_last_error()
	{
		return $this->last_error;
	}

	/**
	 * @return Service_Yandex_Direct_APIMapper
	 */
	private function delay()
	{
		$t = microtime();
		if (($this->last_call_ts > 0) &&
			($d = ($t - $this->last_call_ts)) < Service_Yandex_Direct::option('delay')
		) {
			sleep(Service_Yandex_Direct::option('delay') - $d);
		}
		$this->last_call_ts = microtime();
		return $this;
	}

}

/**
 * @package Service\Yandex\Direct
 */
class Service_Yandex_Direct_Campaign extends Service_Yandex_Direct_Entity
{

	/**
	 * @return Service_Yandex_Direct_BannersCollection
	 */
	public function all_banners()
	{
		return new Service_Yandex_Direct_BannersCollection(
			$this->api()->GetBanners(array('CampaignIDS' => array($this->entity->CampaignID))),
			$this->entity->CampaignID);
	}

	/**
	 * @return int
	 */
	public function save()
	{
		if ($id = $this->api()->CreateOrUpdateCampaign($this->entity)) {
			$this->entity->CampaignID = $id;
		}
		return $id;
	}

	/**
	 * @return boolean
	 */
	public function stop()
	{
		return $this->exec('StopCampaign');
	}

	/**
	 * @return boolean
	 */
	public function resume()
	{
		return $this->exec('ResumeCampaign');
	}

	/**
	 * @return boolean
	 */
	public function archive()
	{
		return $this->exec('ArchiveCampaign');
	}

	/**
	 * @return boolean
	 */
	public function unarchive()
	{
		return $this->exec('UnarchiveCampaign');
	}

	/**
	 * @return boolean
	 */
	public function delete()
	{
		return $this->exec('DeleteCampaign');
	}

	/**
	 * @param string $method
	 *
	 * @return boolean
	 */
	private function exec($method)
	{
		return $this->api()->
			$method(Core::object(array(
						'CampaignID' => $this->entity->CampaignID)
				)
			) ? true : false;
	}

}

/**
 * @package Service\Yandex\Direct
 */
class Service_Yandex_Direct_CampaignsFilter extends Service_Yandex_Direct_Filter
{

	/**
	 * @return Service_Yandex_Direct_CampaignsCollection
	 */
	public function select_for()
	{
		return new Service_Yandex_Direct_CampaignsCollection(
			$this->api()->GetCampaignsListFilter(array('Logins' => func_get_args(), 'Filter' => $this->as_array())));
	}

	/**
	 * @param string $name
	 * @param        $value
	 *
	 * @return mixed
	 */
	protected function value_for($name, $value)
	{
		switch ($name) {
			case 'StatusModerate':
			case 'StatusActivating':
			case 'StatusShow':
			case 'IsActive':
			case 'StatusArchive':
				return $value;
			default:
				return null;
		}
	}

}

/**
 * @package Service\Yandex\Direct
 */
class Service_Yandex_Direct_CampaignsCollection extends Service_Yandex_Direct_IndexedCollection
{

	/**
	 * @return int
	 */
	public function save()
	{
		foreach ($this->items as &$item)
			if ($id = $this->api()->CreateOrUpdateCampaign($item)) {
				$this->entity_id_for($item, $id);
			}
		$this->actualize_index();
		return $id;
	}

	/**
	 * @return Service_Yandex_Direct_BannersCollection
	 */
	public function all_banners()
	{
		return new Service_Yandex_Direct_BannersCollection(
			$this->api()->GetBanners(array('CampaignIDS' => $this->__ids)), 0);
	}

	/**
	 * @return Service_Yandex_Direct_BalanceCollection
	 */
	public function balance()
	{
		return new Service_Yandex_Direct_BalanceCollection($this->api()->GetBalance($this->__ids));
	}

	/**
	 * @param object $entity
	 *
	 * @return Service_Yandex_Direct_Campaign
	 */
	protected function wrap($entity)
	{
		return new Service_Yandex_Direct_Campaign($entity);
	}

	/**
	 * @param object $entity
	 * @param        $value
	 *
	 * @return int
	 */
	protected function entity_id_for($entity, $value = null)
	{
		if ($value !== null) {
			$entity->CampaignID = $value;
		}
		return $entity->CampaignID;
	}

}

/**
 * @package Service\Yandex\Direct
 */
class Service_Yandex_Direct_Banner extends Service_Yandex_Direct_Entity
{

	protected $phrases = array();

	/**
	 * @return Service_Yandex_Direct_PhrasesCollection
	 */
	public function all_phrases()
	{
		$args = func_get_args();
		return new Service_Yandex_Direct_PhrasesCollection(
			count($args) ?
				$this->api()->GetBannerPhrasesFilter(array('BannerIDS' => array($this->entity->BannerID), 'FieldsNames' => Core::normalize_args($args))) :
				$this->api()->GetBannerPhrases($this->entity->BannerID));
	}

	/**
	 * @return boolean
	 */
	public function moderate()
	{
		return $this->exec('ModerateBaners');
	}

	/**
	 * @return boolean
	 */
	public function stop()
	{
		return $this->exec('StopBanners');
	}

	/**
	 * @return boolean
	 */
	public function resume()
	{
		return $this->exec('ResumeBanners');
	}

	/**
	 * @return boolean
	 */
	public function archive()
	{
		return $this->exec('ArchiveBanners');
	}

	/**
	 * @return boolean
	 */
	public function unarchive()
	{
		return $this->exec('UnarchiveBanners');
	}

	/**
	 * @return boolean
	 */
	public function delete()
	{
		return $this->exec('DeleteBanners');
	}

	/**
	 * @return Service_Yandex_Direct_PhraseCollection
	 */
	protected function get_phrases()
	{
		if ($this->phrases) {
			return $this->phrases;
		}
		if ($this->entity->BannerID) {
			$this->phrases = $this->all_phrases();
		} else {
			$this->phrases = new Service_Yandex_Direct_PhraseCollection();
		}

		return $this->phrases;
	}

	/**
	 * @param string $method
	 *
	 * @return boolean
	 */
	private function exec($method)
	{
		return $this->api->$method(
			Core::object(array(
					'CampaignID' => $this->entity->CampaignID,
					'BannerIDS' => array($this->entity->BannerID))
			)
		) ? true : false;
	}

}

/**
 * @package Service\Yandex\Direct
 */
class Service_Yandex_Direct_BannersCollection extends Service_Yandex_Direct_IndexedCollection
{

	protected $campaign_id;

	/**
	 * @param array $items
	 * @param int   $campaign_id
	 */
	public function __construct(array $items, $campaign_id = 0)
	{
		parent::__construct($items);
		$this->campaign_id = $campaign_id;
	}

	/**
	 * @return Service_Yandex_Direct_PhrasesCollection
	 */
	public function all_phrases()
	{
		$args = func_get_args();
		return new Service_Yandex_Direct_PhrasesCollection(
			count($args) ?
				$this->api()->GetBannerPhrasesFilter(array(
						'BannerIDS' => $this->__ids,
						'FieldsNames' => Core::normalize_args($args))
				) :
				$this->api()->GetBannerPhrases($this->__ids));
	}

	/**
	 * @return boolean
	 */
	public function moderate()
	{
		return $this->exec('ModerateBanners');
	}

	/**
	 * @return boolean
	 */
	public function stop()
	{
		return $this->exec('StopBanners');
	}

	/**
	 * @return boolean
	 */
	public function resume()
	{
		return $this->exec('ResumeBanners');
	}

	/**
	 * @return boolean
	 */
	public function archive()
	{
		return $this->exec('ArchiveBanners');
	}

	/**
	 * @return boolean
	 */
	public function unarchive()
	{
		return $this->exec('UnarchiveBanners');
	}

	/**
	 * @return array()
	 */
	public function save()
	{
		$items = array();
		foreach ($this->items as $b) {
			$e = clone $b->__entity;
			$e->Phrases = clone $b->phrases->__items;
			$items[] = $e;
		}
		return $this->api()->CreateOrUpdateBanners($this->items);
	}

	/**
	 * @return boolean
	 */
	public function delete()
	{
		return $this->exec('DeleteBanners');
	}

	/**
	 * @param object $entity
	 *
	 * @return Service_Yandex_Direct_Entity
	 */
	protected function wrap($entity)
	{
		return new Service_Yandex_Direct_Banner($entity);
	}

	/**
	 * @param  $entity
	 * @param  $value
	 *
	 * @return int
	 */
	protected function entity_id_for($entity, $value = null)
	{
		if ($value !== null) {
			$entity->BannerID = $value;
		}
		return $entity->BannerID;
	}

	/**
	 * @param string $action
	 */
	private function exec($action)
	{
		if ($this->campaign_id) {
			return $this->api->$action(
				Core::object(array(
						'CampaignID' => $this->campaign_id,
						'BannerIDS' => $this->__ids)
				)
			);
		} else {
			throw new Service_Yandex_Direct_BadCampaignScopeException();
		}
	}

}

/**
 * @package Service\Yandex\Direct
 */
class Service_Yandex_Direct_Phrase extends Service_Yandex_Direct_Entity
{

	/**
	 * @return int
	 */
	protected function get_id()
	{
		return $this->entity->PhraseID;
	}

	/**
	 * @return float
	 */
	protected function get_current_price()
	{
		return (float)$this->entity->CurrentOnSearch;
	}

}

/**
 * @package Service\Yandex\Direct
 */
class Service_Yandex_Direct_PhrasesCollection extends Service_Yandex_Direct_IndexedCollection
{

	/**
	 * @param  $entity
	 *
	 * @return Service_Yandex_Direct_PhrasesCollection
	 */
	public function wrap($entity)
	{
		return new Service_Yandex_Direct_Phrase($entity);
	}

	/**
	 * @param  $entity
	 * @param  $value
	 *
	 * @return Service_Yandex_Direct_PhrasesCollection
	 */
	public function entity_id_for($entity, $value = null)
	{
		if ($value !== null) {
			$entity->PhraseID = $value;
		}
		return $entity->PhraseID;
	}

	/**
	 * @return Service_Yandex_Direct_PricesCollection
	 */
	protected function get_prices()
	{
		return new Service_Yandex_Direct_PricesCollection($this);
	}

	/**
	 * @param string $pattern
	 *
	 * @return Service_Yandex_Direct_Phrase
	 */
	protected function by_phrase($text)
	{
		foreach ($this->items as $k => $v)
			if ($text === $v->Phrase) {
				return $this[$k];
			}
		return null;
	}

	/**
	 * @param string $regexp
	 *
	 * @return Service_Yandex_Direct_Phrase
	 */
	protected function by_phrase_match($regexp)
	{
		foreach ($this->items as $k => $v)
			if (preg_match($pattern, $v->Phrase)) {
				return $this[$k];
			}
		return null;
	}

}

/**
 * @package Service\Yandex\Direct
 */
class Service_Yandex_Direct_Price extends Service_Yandex_Direct_Entity
{

	/**
	 * @param float $price
	 *
	 * @return Service_Yandex_Direct_Price
	 */
	protected function set_price($price)
	{
		$this->entity->Price = (float)$price;
		return $this;
	}

	/**
	 * @param  $flag
	 *
	 * @return Service,Yandex_Direct_Price
	 */
	protected function set_auto_broker($flag)
	{
		$this->entity->AutoBroker = ($flag && $flag !== 'No') ? 'Yes' : 'No';
		return $this;
	}

}

/**
 * @package Service\Yandex\Direct
 */
class Service_Yandex_Direct_PricesCollection extends Service_Yandex_Direct_IndexedCollection
{

	/**
	 * @param Service_Yandex_Direct_PhrasesCollection $phrases
	 */
	public function __construct(Service_Yandex_Direct_PhrasesCollection $phrases)
	{
		$items = array();
		foreach ($phrases->__items as $v)
			$items[] = new Service_Yandex_Direct_Price(Core::object(array(
					'CampaignID' => $v->CampaignID,
					'BannerID' => $v->BannerID,
					'PhraseID' => $v->PhraseID,
					'Price' => $v->CurrentOnSearch,
					'AutoBroker' => $v->AutoBroker)
			));
		parent::__construct($items);
	}

	/**
	 * @param object $entity
	 * @param        $value
	 *
	 * @return int
	 */
	protected function entity_id_for($entity, $value = null)
	{
		if ($value !== null) {
			$entity->PhraseID = $value;
		}
		return $entity->PhraseID;
	}

	/**
	 * @param object $entity
	 *
	 * @return Service_Yandex_Direct_Price
	 */
	protected function wrap($entity)
	{
		return new Service_Yandex_Direct_Price($entity);
	}

	/**
	 * @param array $attrs
	 *
	 * @return Service_Yandex_Direct_PriceCollection
	 */
	public function assign(array $attrs)
	{
		foreach ($this as $v)
			$v->assign($attrs);
		return $this;
	}

	/**
	 * @return int
	 */
	public function update()
	{
		return Service_Yandex_Direct::api()->UpdatePrices($this->items);
	}

}

/**
 * @package Service\Yandex\Direct
 */
class Service_Yandex_Direct_Client extends Service_Yandex_Direct_Entity
{

	/**
	 * @return string
	 */
	protected function get_id()
	{
		return $this->entity->Login;
	}

}

/**
 * @package Service\Yandex\Direct
 */
class Service_Yandex_Direct_ClientsCollection
	extends Service_Yandex_Direct_IndexedCollection
{

	/**
	 * @return Service_Yandex_Direct_Client
	 */
	protected function wrap($entity)
	{
		return new Service_Yandex_Direct_Client($entity);
	}

	/**
	 * @param object $entity
	 * @param        $value
	 *
	 * @return string
	 */
	protected function entity_id_for($entity, $value = null)
	{
		if ($value !== null) {
			$entity->Login = $value;
		}
		return $entity->Login;
	}

}

/**
 * @package Service\Yandex\Direct
 */
class Service_Yandex_Direct_Balance extends Service_Yandex_Direct_Entity
{

	/**
	 * @return int
	 */
	protected function get_id()
	{
		return $this->entity->CampaignID;
	}

}

/**
 * @package Service\Yandex\Direct
 */
class Service_Yandex_Direct_BalanceCollection extends Service_Yandex_Direct_IndexedCollection
{

	/**
	 * @param  $entity
	 *
	 * @return Service_Yandex_Direct_Balance
	 */
	protected function wrap($entity)
	{
		return new Service_Yandex_Direct_Balance($entity);
	}

	/**
	 * @param object $entity
	 * @param        $value
	 *
	 * @return object
	 */
	protected function entity_id_for($entity, $value = null)
	{
		if ($value !== null) {
			$entity->CampaignID = $value;
		}
		return $entity->CampaignID;
	}

}

/**
 * @package Service\Yandex\Direct
 */
class Service_Yandex_Direct_ForecastBuilder extends Service_Yandex_Direct_APIConsumer
{
	protected $attrs = array(
		'GeoID' => array(),
		'Phrases' => array(),
		'Categories' => array());

	/**
	 * @return Service…Yandex_Direct_ForecastBuilder
	 */
	public function with_geo_id()
	{
		$args = func_get_args();
		return $this->update_attrs('GeoID', Core::normalize_args($args));
	}

	/**
	 * @return Service…Yandex_Direct_ForecastBuilder
	 */
	public function with_phrases()
	{
		$args = func_get_args();
		return $this->update_attrs('Phrases', Core::normalize_args($args));
	}

	/**
	 * @return Service…Yandex_Direct_ForecastBuilder
	 */
	public function with_categories()
	{
		$args = func_get_args();
		return $this->update_attrs('Categories', Core::normalize_args($args));
	}

	/**
	 * @return int
	 */
	public function create()
	{
		return $this->api()->CreateNewForecast($this->attrs);
	}

	/**
	 * @param string $name
	 * @param array  $values
	 *
	 * @return Service_Yandex_Direct_ForecastBuilder
	 */
	private function update_attrs($name, array $values)
	{
		$this->attrs[$name] = array_merge($this->attrs[$name], $values);
		return $this;
	}

}

/**
 * @package Service\Yandex\Direct
 */
class Service_Yandex_Direct_ForecastsMapper
	extends Service_Yandex_Direct_Mapper
	implements Core_IndexedAccessInterface
{

	protected $status = array();
	protected $forecasts = array();
	protected $is_loaded = false;

	/**
	 * @return Service_Yandex_Direct_ForecastBuilder
	 */
	public function new_forecast()
	{
		return new Service_Yandex_Direct_ForecastBuilder();
	}

	/**
	 * @param int $id
	 *
	 * @return Service_Yandex_Direct_Forecast
	 */
	public function load($id)
	{
		return new Service_Yandex_Direct_Forecast($this->api()->GetForecast($id));
	}

	/**
	 * @return Service_Yandex_Direct_ForecastMapper
	 */
	public function check()
	{
		foreach ($this->api()->GetForecastList() as $v) {
			$this->status[$v->ForecastID] = ($v->StatusForecast === 'Done');
		}
		$this->is_loaded = true;
		return $this;
	}

	/**
	 * @param int $id
	 *
	 * @return boolean
	 */
	public function is_ready($id)
	{
		if (!$this->is_loaded) {
			$this->check();
		}
		return $this->status[$id];
	}

	/**
	 * @param int $index
	 *
	 * @return Service_Yandex_Direct_Forecast
	 */
	public function offsetGet($index)
	{
		return isset($this->forecasts[$index]) ?
			$this->forecasts[$index] :
			($this->is_ready($index) ? $this->forecasts[$index] = $this->load($index) : null);
	}

	/**
	 * @param int $index
	 * @param     $value
	 *
	 * @return Service_Yandex_Direct_ForecastsMapper
	 */
	public function offsetSet($index, $value)
	{
		throw new Core_ReadOnlyObjectException($this);
	}

	/**
	 * @param int $index
	 *
	 * @return boolean
	 */
	public function offsetExists($index)
	{
		return $this->is_ready($index);
	}

	/**
	 * @param int $index
	 *
	 * @return Service_Yandex_Direct_ForecastsMapper
	 */
	public function offsetUnset($index)
	{
		if (isset($this->forecasts[$index])) {
			unset($this->forecasts[$index]);
		}
		return $this;
	}

}

/**
 * @package Service\Yandex\Direct
 */
class Service_Yandex_Direct_Forecast
{

	protected $phrases;
	protected $categories;
	protected $common;

	/**
	 */
	public function __construct($entity)
	{
		$f = Core::object();
		if (isset($entity->Phrases)) {
			$this->phrases = new Service_Yandex_Direct_EntityCollection($entity->Phrases);
		}

		if (isset($entity->Categories)) {
			$this->categories = new Service_Yandex_Direct_EntityCollection($entity->Categories);
		}

		if (isset($entity->Common)) {
			$this->common = new Service_Yandex_Direct_Entity($entity->Common);
		}
	}

	/**
	 * @param string $property
	 *
	 * @return mixed
	 */
	public function __get($property)
	{
		switch ($property) {
			case 'phrases':
			case 'categories':
			case 'common':
				return $this->$property;
			default:
				if (isset($this->common->$property)) {
					return $this->common->$property;
				} else {
					throw new Core_MissingPropertyException($property);
				}
		}
	}

	/**
	 * @param string $property
	 * @param        $value
	 *
	 * @return Service_Yandex_Direct_Forecast
	 */
	public function __set($property, $value)
	{
		throw new Core_ReadOnlyObjectException($propery);
	}

	/**
	 * @param string $property
	 *
	 * @return boolean
	 */
	public function __isset($property)
	{
		switch ($property) {
			case 'phrases':
			case 'categories':
			case 'common':
				return isset($this->$property);
			default:
				return isset($this->common->$property);
		}
	}

	/**
	 * @param string $property
	 *
	 * @return Service_Yandex_Direct_Corecast
	 */
	public function __unset($property)
	{
		throw new Core_ReadOnlyObjectException($propery);
	}

}

/**
 * @package Service\Yandex\Direct
 */
class Service_Yandex_Direct_Region extends Service_Yandex_Direct_Entity
{
}

/**
 * @package Service\Yandex\Direct
 */
class Service_Yandex_Direct_RegionsCollection
	extends Service_Yandex_Direct_IndexedCollection
{

	/**
	 * @param  $entity
	 *
	 * @return Service_Yandex_Direct_Region
	 */
	protected function wrap($entity)
	{
		return new Service_Yandex_Direct_Region($entity);
	}

	/**
	 * @param object $entity
	 * @param        $value
	 *
	 * @return int
	 */
	protected function entity_id_for($entity, $value = null)
	{
		if ($value !== null) {
			$entity->RegionID = $value;
		}
		return $entity->RegionID;
	}

}

/**
 * @package Service\Yandex\Direct
 */
class Service_Yandex_Direct_Category extends Service_Yandex_Direct_Entity
{
}

/**
 * @package Service\Yandex\Direct
 */
class Service_Yandex_Direct_CategoriesCollection extends Service_Yandex_Direct_IndexedCollection
{

	/**
	 * @param object $entity
	 *
	 * @return Service_Yandex_Direct_Category
	 */
	protected function wrap($entity)
	{
		return new Service_Yandex_Direct_Category($entity);
	}

	/**
	 * @param object $entity
	 * @param        $value
	 *
	 * @return int
	 */
	protected function entity_id_for($entity, $value = null)
	{
		if ($value !== null) {
			$entity->RubricID = $value;
		}
		return $entity->RubricID;
	}

}

