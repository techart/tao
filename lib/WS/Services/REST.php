<?php
/**
 * WS.REST
 *
 * @package WS\Services\REST
 * @version 0.2.0
 */
Core::load('WS', 'WS.Services.REST.URI');

//TODO: Events + CMS integration -- current_controller, current_mapper.

/**
 * @package WS\Services\REST
 */
class WS_Services_REST implements Core_ModuleInterface
{

	const VERSION = '0.2.2';

	/**
	 * @param array  $mappings
	 * @param string $default
	 *
	 * @return WS_REST_Dispatcher
	 */
	public static function Dispatcher($apptication = null, array $mappings = array(), $default = '')
	{
		return new WS_Services_REST_Dispatcher($application, $mappings, $default);
	}

	/**
	 * @return WS_REST_Application
	 */
	public static function Application()
	{
		return new WS_Services_REST_Application();
	}

	/**
	 * @param string $classname
	 * @param string $path
	 *
	 * @return WS_REST_Resource
	 */
	public static function Resource($classname, $path = '')
	{
		return new WS_Services_REST_Resource($classname, $path);
	}

	/**
	 * @param string $name
	 *
	 * @return WS_REST_Method
	 */
	public static function Method($name)
	{
		return new WS_Services_REST_Method($name);
	}

}

/**
 * @package WS\Services\REST
 */
class WS_Services_REST_Exception extends WS_Exception
{
}

/**
 * @package WS\Services\REST
 */
class WS_Services_REST_Dispatcher extends WS_MiddlewareService /* implements WS_ServiceInterface */
{

	protected $mappings = array();
	protected $default = '';
	protected $env;

	/**
	 * @param array  $mappings
	 * @param string $default
	 */
	public function __construct($application = null, array $mappings = array(), $default = null)
	{
		parent::__construct($application);
		$this->mappings($mappings);
		if ($default) {
			$this->map('default', $default);
		}
	}

	/**
	 * @param array $mappings
	 *
	 * @return WS_REST_Dispatcher
	 */
	public function mappings(array $mappings)
	{
		foreach ($mappings as $k => $v)
			$this->map($k, $v);
		return $this;
	}

	/**
	 * @param string $path
	 * @param string $classname
	 *
	 * @return WS_REST_Dispatcher
	 */
	public function map($name, $app)
	{
		if (is_string($app)) {
			$app = array('class' => $app);
		}
		if (!isset($app['prefix'])) {
			$app['prefix'] = $name;
		}
		if (!isset($app['instance'])) {
			$app['instance'] = null;
		}
		if (!isset($app['param'])) {
			$app['param'] = array();
		}
		$this->mappings[$name] = $app;
		return $this;
	}

	public function get_app($name, $parms = array())
	{
		if (isset($this->mappings[$name]['instance'])) {
			return $this->mappings[$name]['instance'];
		}
		return $this->mappings[$name]['instance'] = $this->load_application($name, $parms);
	}

	public function get_map($name)
	{
		return $this->mappings[$name];
	}

	public function update_map($name, array $app = array())
	{
		$this->mappings[$name] = array_merge($this->mappings[$name], $app);
		return $this;
	}

	/**
	 * @param WS_Environment $env
	 *
	 * @return mixed
	 */
	public function run(WS_Environment $env)
	{
		$this->env = $env;
		list($prefix, $app_name) = array('', 'default');

		$request = $env->request;
		$env = $env->spawn();

		foreach ($this->mappings as $k => $v) {
			$pp = $v['prefix'];
			if (($pp == '/' && preg_match('{^(/(?:index.[a-zA-Z0-9]+)?$)}', $env->request->path, $m) ||
				(preg_match("{^/$pp(/.*)}", $env->request->path, $m)))
			) {
				$request = clone $request; //FIXME
				$request->path(end($m));
				$env->request($request);
				list($prefix, $app_name, $app_parms) = array($pp, $k, array('match' => $m));
				break;
			}
			if ($pp == '') {
				$app = $this->get_app($k, $app_parms);
				if ($app->find($env)) {
					break;
				}
				$app = null;
			}
		}

		$app = $app ? $app : $this->get_app($app_name, $app_parms);
		return $this->create_response($app, $env);
	}

	protected function create_response($app, $env)
	{
		if ($app) {
			$response = $app->run($env);
			if ($response->status->code == Net_HTTP::NOT_FOUND && $this->application instanceof WS_ServiceInterface) {
				return $this->application->run(WS::env());
			}
			return $response;
		} else {
			if ($this->application instanceof WS_ServiceInterface) {
				return $this->application->run($env);
			}
			return Net_HTTP::Response(Net_HTTP::NOT_FOUND);
		}

		//   if ($app) var_dump($app->run($env));
		// return $app ?
		//   $app->run($env) :
		//   ($this->application instanceof WS_ServiceInterface ? $this->application->run($env) : Net_HTTP::Response(Net_HTTP::NOT_FOUND));
	}

	/**
	 * @param WS_Environment $env
	 * @param string         $prefix
	 *
	 * @return WS_Environment
	 */
	protected function setup_env(WS_Environment $env)
	{
		return $env->app($this);
	}

	/**
	 * @param string|array $app
	 *
	 * @return WS_REST_Application
	 */
	protected function load_application($name, $parms = array())
	{
		$app = $this->mappings[$name];
		if (empty($app)) {
			return null;
		}
		$class_name = $app['class'];
		$instance = Core::amake($class_name, array($app['prefix'], array_merge($parms, $app['param'])));
		$instance->name = $name;

		if ($instance instanceof WS_Services_REST_Application) {
			return $instance;
		} else {
			throw new WS_Services_REST_Exception('Incompatible application class: ' . Core_Types::virtual_class_name_for($class_name));
		}
	}

}

/**
 * @package WS\Services\REST
 */
class WS_Services_REST_Application
	implements WS_ServiceInterface, Core_PropertyAccessInterface
{

	const LOOKUP_LIMIT = 20;
	const DEFAULT_CONTENT_TYPE = 'text/html';

	protected $resources = array();
	protected $classes = array();

	protected $format = null;
	protected $extension = null;

	protected $media_types = array(
		'html' => 'text/html',
		'js' => 'text/javascript',
		'json' => 'application/json',
		'xml' => 'application/xml',
		'rss' => 'application/xhtml+xml');

	protected $default_format = 'html';

	protected $prefix = '';

	protected $options = array();
	protected $is_match = false;
	protected $match;
	protected $target_resource;
	protected $target_method;
	protected $target_instance;

	protected $callback_result;

	protected $name;

	/**
	 * @param array $options
	 */
	public function __construct($prefix = '', array $options = array())
	{
		$this->prefix = $prefix;
		$this->options = $this->default_options();
		$this->setup($options);
	}

	/**
	 * @return array
	 */
	protected function default_options()
	{
		return array();
	}

	/**
	 * @param array $options
	 */
	protected function setup(array $options = array())
	{
	}

	protected function options(array $options = array())
	{
		$this->options = array_merge($this->options, $options);
		return $this;
	}

	/**
	 * @param string $format
	 * @param string $content_type
	 *
	 * @return WS_REST_Application
	 */
	public function media_type($format, $content_type, $is_default = false)
	{
		$this->media_types[$format] = $content_type;
		if ($is_default) {
			$this->default_format = $format;
		}
		return $this;
	}

	/**
	 * @param string $format
	 *
	 * @return string
	 */
	public function media_type_for($format)
	{
		return $this->media_types[$format];
	}

	/**
	 * @param string $media_type
	 *
	 * @return string
	 */
	public function format_for($media_type)
	{
		return array_search($media_type, $this->media_types);
	}

	/**
	 * @param string           $name
	 * @param WS_REST_Resource $resource
	 *
	 * @return WS_REST_Application
	 */
	public function resource($name, WS_Services_REST_Resource $resource)
	{
		$this->resources[$name] = $resource;
		$this->classes[Core_Types::real_class_name_for($resource->classname)] = $resource;
		return $this;
	}

	public function get_resource($name)
	{
		return $this->resources[$name];
	}

	/**
	 * @param WS_Environment $env
	 *
	 * @return mixed
	 */
	public function run(WS_Environment $env)
	{
		$this->before_run($env->app($this));

		$this->find($env);

		return $this->is_match() ? $this->create_response($env) : Net_HTTP::Response(Net_HTTP::NOT_FOUND);
	}

	public function create_response($env)
	{
		if (!empty($this->callback_result)) {
			return $this->callback_result;
		}
		return Core::with(($this->target_instance && $this->target_method) ?
				(($result = $this->execute(
					$this->target_instance,
					$this->target_method->name,
					$env,
					$this->match ? $this->match->parms : array(), $this->format, $this->target_method->defaults
				)) instanceof Net_HTTP_Response ?
					$result : Net_HTTP::Response($result)->content_type(!empty($this->format) ? $this->format : self::DEFAULT_CONTENT_TYPE)) :
				Net_HTTP::Response(Net_HTTP::NOT_FOUND)
		);
	}

	public function find($env)
	{
		if ($this->is_match) {
			return $this->is_match;
		}
		$this->clear();

		list($uri, $extension) = $this->canonicalize($env->request->path);

		$accept_formats = $this->parse_formats($env->request);

		list($target_resource, $target_method, $target_instance) = array(null, null, null);

		// Le ballet de la Merlaison, mouvement 1
		foreach ($this->resources as $resource)
			if (($match = $resource->path->match($uri))) {
				$target_resource = $resource;
				$uri = $match->tail;
				//FIXME: убрать в match
				//if (empty($uri)) $uri = '/index';
				break;
			}

		// Le ballet de la Merlaison, mouvement 2
		if ($target_resource) {
			$target_instance = $this->instantiate($target_resource, $env, $match->parms);
			$ai = $this->after_instantiate($target_instance);
			for ($i = 0; $target_resource && ($i < self::LOOKUP_LIMIT); $i++) {
				foreach ($target_resource->methods as $method) {
					if (($method->path && ($match = $method->path->match($uri))) || (!$uri && !$method->path)) {
						if ($method->http_mask) {
							if (($method->http_mask & $env->request->method_code) &&
								!$match->tail &&
								(($format = $this->can_produce($target_resource, $method, $accept_formats, $extension)) !== false)
							) {
								$target_method = $method;
								break;
							}
						} else {
							if ($target_instance = $this->execute($target_instance, $method->name, $env, $match->parms, $extension)) {
								$ai = $this->after_instantiate($target_instance);
								$target_resource = $this->lookup_resource_for($target_instance);
								$uri = $match->tail;
								//FIXME: убрать в match
								//if (empty($uri)) $uri = '/index';
							} else {
								$target_resource = null;
							}
							break;
						}
					}
				}
				if ($target_method) {
					break;
				}
			}
		}

		$this->callback_result = $ai;
		$this->target_resource = $target_resource;
		$this->target_method = $target_method;
		$this->target_instance = $target_instance;
		$this->format = $format;
		$this->extension = $extension;
		$this->match = $match;
		$this->is_match = $target_resource && $target_method;
		return $this->is_match;
	}

	public function is_match()
	{
		return $this->is_match;
	}

	public function clear()
	{
		$this->is_match = false;
		$this->target_resource = null;
		$this->target_method = null;
		$this->target_instance = null;
		$this->match = null;
		$this->format = null;
		$this->extension = null;
		return $this;
	}

	/**
	 * @param WS_REST_Resource $resource
	 * @param WS_REST_Method   $method
	 * @param array            $accept_formats
	 * @param string           $exstension
	 */
	protected function can_produce(WS_Services_REST_REsource $resource, WS_Services_REST_Method $method, $accept_formats, $extension)
	{
		$formats = array_merge($resource->formats, $method->formats);

		$any_format = false;
		if (array_search('*', $formats) !== false) {
			$any_format = true;
		}

		if ($extension) {
			return $any_format || in_array($extension, $formats) ? $this->media_types[$extension] : false;
		}

		foreach ($accept_formats as $accept_type => $q) {
			if ($any_format) {
				return $accept_type;
			}
			foreach ($formats as $format) {
				if (isset($this->media_types[$format])) {
					$type = $this->media_types[$format];
					if (preg_match('{^' . str_replace('*', '.+', str_replace('+', '\+', $type)) . '$}', $accept_type)) {
						return $accept_type;
					}
					if (preg_match('{^' . str_replace('*', '.+', str_replace('+', '\+', $accept_type)) . '$}', $type)) {
						return $type;
					}
				}
			}
		}

		if (in_array($this->default_format, $formats)) {
			return $this->media_types[$this->default_format];
		}
		return false;
	}

	/**
	 * @param WS_Environment $env
	 */
	protected function before_run(WS_Environment $env)
	{
	}

	/**
	 * @param  $resource
	 */
	protected function after_instantiate($resource)
	{
		if (method_exists($resource, 'run_filters')) {
			return $resource->run_filters('before', array());
		}
	}

	/**
	 * @param  $request
	 */
	protected function parse_formats($request)
	{
		$formats = array();
		if (!$request->headers->accept) {
			return array();
		}
		foreach (explode(',', $request->headers->accept) as $index => $accept) {
			if (strpos($accept, '*/*') !== false) {
				continue;
			}
			$split = preg_split('/;\s*q=/', $accept);
			if (count($split) > 0) {
				$formats[trim($split[0])] = (float)Core::if_not_set($split, 1, 1.0);
			}
		}
		arsort($formats);
		return $formats;
	}

	/**
	 * @param string $uri
	 * @param string $default_format
	 *
	 * @return array
	 */
	protected function canonicalize($uri)
	{
		switch (true) {
			case $uri[strlen($uri) - 1] == '/':
				return array("{$uri}index", null);
			case preg_match('{\.([a-zA-z0-9]+)$}', $uri, $m):
				return array(preg_replace('{\.' . $m[1] . '$}', '', $uri), $m[1]);
			default:
				return array("$uri/index", null);
		}

	}

	/**
	 * @param  $object
	 *
	 * @return WS_REST_Resource
	 */
	protected function lookup_resource_for($object)
	{
		foreach (Core_Types::class_hierarchy_for($object) as $classname) {
			if (isset($this->classes[$classname])) {
				return $this->classes[$classname];
			}
		}
		return null;
	}

	/**
	 * @param object         $instance
	 * @param string         $method
	 * @param WS_Environment $env
	 * @param array          $parms
	 *
	 * @return mixed
	 */
	protected function execute($instance, $method, WS_Environment $env, array $parms, $format = null, $defaults = array(), $extension = null)
	{
		$reflection = new ReflectionMethod($instance, $method);
		return $reflection->invokeArgs($instance, $this->make_args($reflection->getParameters(), $env, $parms, $format, $defaults, $extension));
	}

	/**
	 * @param string         $classname
	 * @param WS_Environment $env
	 * @param array          $parms
	 *
	 * @return mixed
	 */
	protected function instantiate(WS_Services_REST_Resource $resource, WS_Environment $env, array $parms)
	{
		if ($resource->need_load) {
			Core::autoload($resource->classname);
			// Core::load($resource->is_module ?
			//   $resource->classname :
			//   Core_Types::module_name_for($resource->classname));
		}

		$r = new ReflectionClass(Core_Types::real_class_name_for($resource->classname));

		return ($c = $r->getConstructor()) ?
			$r->newInstanceArgs($this->make_args($c->getParameters(), $env, $parms)) :
			$r->newInstance();
	}

	/**
	 * @param array          $args
	 * @param WS_Environment $env
	 * @param array          $parms
	 *
	 * @return array
	 */
	protected function make_args(array $args, WS_Environment $env, array $parms, $format = null, $defaults = array(), $extension = null)
	{
		$vals = array();
		$parms = array_merge($defaults, $parms);
		foreach ($args as $arg) {
			$name = $arg->getName();
			switch ($name) {
				case 'application':
					$vals[] = $this;
					break;
				case 'env':
					$vals[] = $env;
					break;
				case 'format':
					$vals[] = $this->format_for($format);
					break;
				case 'parameters':
				case 'parms':
					$vals[] = $env->request->parameters;
					break;
				case 'request':
					$vals[] = $env->request;
					break;
				case 'extension':
					$vals[] = $env->request;
					break;
				default:
					if (isset($parms[$name])) {
						$vals[] = $parms[$name];
					} elseif (isset($env->request[$name])) {
						$vals[] = $env->request[$name];
					} elseif ($arg->isDefaultValueAvailable()) {
						$vals[] = $arg->getDefaultValue();
					} else {
						$vals[] = null;
					}
			}
		}
		return $vals;
	}

	/**
	 * @param string $property
	 *
	 * @return mixed
	 */
	public function __get($property)
	{
		switch (true) {
			case property_exists($this, $property):
				return $this->$property;
			case method_exists($this, $m = 'get_' . $property):
				return $this->$m();
			case array_key_exists($property, $this->options):
				return $this->options[$property];
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
		if ($property == 'name') {
			return $this->$property = (string)$name;
		}
		throw property_exists($this, $property) ||
		method_exists($this, 'get_' . $property) ||
		array_key_exists($property, $this->options) ?
			new Core_ReadOnlyPropertyException($property) :
			new Core_MissingPropertyException($property);
	}

	/**
	 * @param string $property
	 *
	 * @return boolean
	 */
	public function __isset($property)
	{
		return property_exists($this, $property) ||
		method_exists($this, 'isset_' . $property) ||
		method_exists($this, 'get_' . $property) ||
		isset($this->options[$property]);
	}

	/**
	 * @param string $property
	 */
	public function __unset($property)
	{
		throw property_exists($this, $property) ||
		method_exists($this, 'get_' . $property) ||
		array_key_exists($property, $this->options) ?
			new Core_ReadOnlyPropertyException($property) :
			new Core_MissingPropertyException($property);
	}

}

/**
 * @package WS\Services\REST
 */
class WS_Services_REST_Resource implements
	Core_PropertyAccessInterface, IteratorAggregate, Core_EqualityInterface
{

	protected $classname;
	protected $is_module = false;
	protected $need_load = true;
	protected $path;
	protected $methods = array();
	protected $formats = array();

	/**
	 * @param string $classname
	 * @param string $path
	 */
	public function __construct($classname, $path = '')
	{
		$this->classname($classname);
		$this->path($path);
	}

	/**
	 * @return WS_REST_Resource
	 */
	public function produces()
	{
		foreach (Core::normalize_args(func_get_args()) as $format)
			$this->formats[] = trim((string)$format);
		return $this;
	}

	/**
	 * @param WS_REST_Method $method
	 *
	 * @return WS_REST_Resource
	 */
	public function method(WS_Services_REST_Method $method)
	{
		$this->methods[$method->name] = $method;
		return $this;
	}

	public function get_method($name)
	{
		return $this->methods[$name];
	}

	public function classname($classname)
	{
		$this->classname = (($this->is_module = $classname[0] == '-') ? substr($classname, 1) : $classname);
		return $this;
	}

	public function need_load($v = true)
	{
		$this->need_load = (boolean)$v;
		return $this;
	}

	public function is_module($v = true)
	{
		$this->is_module = $v;
		return $this;
	}

	public function path($path)
	{
		$this->path = WS_Services_REST_URI::Template($path);
		return $this;
	}

	/**
	 * @return ArrayIterator
	 */
	public function getIterator()
	{
		return new ArrayIterator($this->methods);
	}

	/**
	 * @param string $property
	 *
	 * @return mixed
	 */
	public function __get($property)
	{
		switch ($property) {
			case 'path':
			case 'classname':
			case 'methods':
			case 'formats':
			case 'is_module':
			case 'need_load':
				return $this->$property;
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
		switch ($property) {
			case 'path':
			case 'classname':
			case 'methods':
			case 'formats':
			case 'is_module':
			case 'need_load':
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
		throw new Core_ReadOnlyObjectException($this);
	}

	/**
	 * @param  $to
	 *
	 * @return boolean
	 */
	public function equals($to)
	{
		return get_class($this) == get_class($to) &&
		$this->classname == $to->classname &&
		$this->is_module == $to->is_module &&
		Core::equals($this->path, $to->path) &&
		Core::equals($this->methods, $to->methods) &&
		Core::equals($this->formats, $to->formats);
	}
}

/**
 * @package WS\Services\REST
 */
class WS_Services_REST_Method
	implements Core_PropertyAccessInterface, Core_EqualityInterface
{

	protected $name;
	protected $http_mask = 0;
	protected $path = null;
	protected $formats = array('html');
	protected $defaults = array();

	/**
	 * @param string $name
	 */
	public function __construct($name)
	{
		$this->name = $name;
	}

	public function defaults($value)
	{
		$this->defaults = $value;
		return $this;
	}

	/**
	 * @return WS_REST_Method
	 */
	public function produces()
	{
		foreach (Core::normalize_args(func_get_args()) as $format)
			$this->formats[] = trim((string)$format);
		return $this;
	}

	/**
	 * @param int $mask
	 *
	 * @return WS_REST_Method
	 */
	public function http($mask)
	{
		$this->http_mask = (int)$mask;
		return $this;
	}

	/**
	 * @param string $path
	 *
	 * @return WS_REST_Method
	 */
	public function path($path)
	{
		$this->path = ($path instanceof WS_Services_REST_URI_Template) ?
			$path :
			WS_Services_REST_URI::Template((string)$path);
		return $this;
	}

	/**
	 * @param string $property
	 *
	 * @return mixed
	 */
	public function __get($property)
	{
		switch ($property) {
			case 'name':
			case 'http_mask':
			case 'path':
			case 'formats':
			case 'defaults':
				return $this->$property;
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
			case 'name':
			case 'formats':
				throw new Core_ReadOnlyPropertyException($property);
			case 'http_mask':
			{
				$this->http_mask = (int)$value;
				return $this;
			}
			case 'path':
				$this->path($value);
				return $this;
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
			case 'name':
			case 'http_mask':
			case 'path':
			case 'formats':
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
		switch ($property) {
			case 'name':
			case 'http_mask':
			case 'path':
			case 'formats':
				throw new Core_UndestroyablePropertyException($property);
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
		return get_class($this) === get_class($to) &&
		$this->name == $to->name &&
		$this->http_mask == $to->http_mask &&
		Core::equals($this->path, $to->path) &&
		Core::equals($this->formats, $to->formats);
	}

}



