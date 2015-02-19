<?php
/**
 * WS.REST.DSL
 *
 * @package WS\Services\REST\DSL
 * @version 0.2.0
 */

Core::load('DSL', 'WS.Services.REST');

/**
 * @package WS\Services\REST\DSL
 */
class WS_Services_REST_DSL implements Core_ModuleInterface
{

	const VERSION = '0.2.1';

	/**
	 * @param WS_REST_Application $application
	 *
	 * @return WS_REST_DSL_Application
	 */
	static public function Application(WS_Services_REST_Application $application = null)
	{
		return new WS_Services_REST_DSL_Application(null, $application);
	}

}

/**
 * @abstract
 * @package WS\Services\REST\DSL
 */
abstract class WS_Services_REST_DSL_Builder extends DSL_Builder
{
}

/**
 * @package WS\Services\REST\DSL
 */
class WS_Services_REST_DSL_Application extends WS_Services_REST_DSL_Builder
{

	protected $class_prefix = '';

	/**
	 * @param WS_REST_Application $app
	 */
	public function __construct(WS_Services_REST_DSL_Application $parent = null, WS_Services_REST_Application $app = null, $class_prefix = '')
	{
		$this->class_prefix = $class_prefix;
		parent::__construct($parent, $app ? $app : new WS_Services_REST_Application());
	}

	public function for_class_prefix($prefix)
	{
		return new self($this, $this->object, $prefix);
	}

	/**
	 * @param string $name
	 * @param string $classname
	 * @param string $path
	 */
	public function begin_resource($name, $classname, $path = '', $is_module = false)
	{
		$r = new WS_Services_REST_Resource($this->class_prefix . $classname, $path);
		$r->is_module($is_module);
		$this->object->resource($name, $r);
		return new WS_Services_REST_DSL_Resource($this, $r);
	}

}

/**
 * @package WS\Services\REST\DSL
 */
class WS_Services_REST_DSL_Resource extends WS_Services_REST_DSL_Builder
{

	protected $scope;

	/**
	 * @param WS_REST_DSL_Application $parent
	 * @param WS_REST_Resource        $resource
	 */
	public function __construct(WS_Services_REST_DSL_Builder $parent, WS_Services_REST_Resource $resource, $scope = null)
	{
		parent::__construct($parent, $resource);
		$this->scope = ($scope instanceof stdClass) ? $scope : new stdClass();
	}

	/**
	 * @param string $method
	 * @param string $path
	 * @param string $formats
	 *
	 * @return WS_REST_DSL_Resource
	 */
	public function bind($method, $path = null, $formats = null)
	{
		return $this->method($method, null, $path, $formats);
	}

	/**
	 * @param int $http_mask
	 *
	 * @return WS_REST_DSL_Resource
	 */
	public function for_methods($http_mask)
	{
		$s = clone $this->scope;
		$s->http_methods = $http_mask;
		return new WS_Services_REST_DSL_Resource($this, $this->object, $s);
	}

	/**
	 * @param string $path
	 *
	 * @return WS_REST_DSL_Resource
	 */
	public function for_path($path)
	{
		$s = clone $this->scope;
		$s->path = $path;
		return new WS_Services_REST_DSL_Resource($this, $this->object, $s);
	}

	/**
	 * @param string $formats
	 *
	 * @return WS_REST_DSL_Resource
	 */
	public function for_format($formats)
	{
		$s = clone $this->scope;
		$s->formats = $formats;
		return new WS_Services_REST_DSL_Resource($this, $this->object, $s);
	}

	/**
	 * @param string $name
	 * @param string $formats
	 *
	 * @return WS_REST_DSL_Resource
	 */
	public function get($name = 'index', $formats = null)
	{
		return $this->method($name, Net_HTTP::GET | Net_HTTP::HEAD, null, $formats);
	}

	public function any($name = 'index')
	{
		return $this->method($name, Net_HTTP::ANY, $name, '*');
	}

	/**
	 * @return WS_REST_DSL_Resource
	 */
	public function index($formats = null)
	{
		return $this->get('index', $formats);
	}

	/**
	 * @param string $path
	 * @param string $name
	 * @param string $formats
	 *
	 * @return WS_REST_DSL_Resource
	 */
	public function get_for($path, $name = '', $formats = null)
	{
		return $this->method($name, Net_HTTP::GET | Net_HTTP::HEAD, $path, $formats);
	}

	/**
	 * @param string $name
	 * @param string $formats
	 *
	 * @return WS_REST_DSL_Resource
	 */
	public function post($name = 'create', $formats = null)
	{
		return $this->method($name, Net_HTTP::POST, null, $formats);
	}

	/**
	 * @param string $path
	 * @param string $name
	 * @param string $formats
	 *
	 * @return WS_REST_DSL_Resource
	 */
	public function post_for($path, $name = '', $formats = null)
	{
		return $this->method($name, Net_HTTP::POST, $path, $formats);
	}

	/**
	 * @param string $name
	 * @param string $formats
	 *
	 * @return WS_REST_DSL_Resource
	 */
	public function put($name = 'update', $formats = null)
	{
		return $this->method($name, Net_HTTP::PUT, null, $formats);
	}

	/**
	 * @param string $path
	 * @param string $name
	 * @param string $formats
	 *
	 * @return WS_REST_DSL_Resource
	 */
	public function put_for($path, $name = '', $formats = null)
	{
		return $this->method($name, Net_HTTP::PUT, $path, $formats);
	}

	/**
	 * @param string $name
	 * @param string $formats
	 *
	 * @return WS_REST_DSL_Resource
	 */
	public function delete($name = 'delete', $formats = null)
	{
		return $this->method($name, Net_HTTP::DELETE, null, $formats);
	}

	/**
	 * @param string $path
	 * @param string $name
	 * @param string $formats
	 *
	 * @return WS_REST_DSL_Resource
	 */
	public function delete_for($path, $name = '', $formats = null)
	{
		return $this->method($name, Net_HTTP::DELETE, $path, $formats);
	}

	/**
	 * @param string $name
	 * @param int    $http_mask
	 * @param string $path
	 * @param string $formats
	 *
	 * @return WS_REST_DSL_Resource
	 */
	public function method($name, $http_mask = Net_HTTP::ANY, $path = null, $formats = null, $defaults = array())
	{
		$formats = ($formats === null) ?
			(isset($this->scope->formats) ? $this->scope->formats : array()) :
			$formats;
		$path = $path === null ? (isset($this->scope->path) ? $this->scope->path : 'index') : $path;
		$name = $name ? $name : $path;
		$http_mask = $http_mask === null ? (isset($this->scope->http_methods) ? $this->scope->http_methods : Net_HTTP::ANY) : $http_mask;

		$this->object->method(
			Core::with(
				new WS_Services_REST_Method($name)
			)->
				path($path)->
				http($http_mask)->
				defaults($defaults)->
				produces(is_array($formats) ? $formats : Core_Strings::split_by(',', (string)$formats))
		);

		return $this;
	}

	/**
	 * @param string $name
	 * @param string $path
	 *
	 * @return WS_REST_DSL_Resource
	 */
	public function sublocator($name, $path = null)
	{
		return $this->method(
			$name,
			0,
			($path === null) ?
				(isset($this->scope->path) ? $this->scope->path : $name) :
				$path
		);
	}

}

