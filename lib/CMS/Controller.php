<?php
/**
 * CMS.Controller
 *
 * @package CMS\Controller
 * @version 0.0.0
 */
Core::load('CMS.Router', 'CMS.Controller.Base');
Core::load('CMS.PageNavigator');

/**
 * @package CMS\Controller
 */
class CMS_Controller extends CMS_Controller_Base implements Core_ModuleInterface
{

	const MODULE = 'CMS.Controller';
	const VERSION = '0.0.0';

	public $name;
	public $mapper;
	protected $make_uri_method = false;

	protected $application;

	protected function get_component_name()
	{
		return CMS::get_component_name_for($this);
	}

	public function __construct($env, $application = null)
	{
		if (!CMS::$current_component_name) {
			$this->application = $application;
			CMS::$current_component_name = $this->get_component_name();
			CMS::$current_mapper = isset(CMS::$mappers[CMS::$current_component_name]) ?
				CMS::$mappers[CMS::$current_component_name] : $application; //Может не быть
			CMS::$current_controller = $this;
			return parent::__construct($env, $env->response);
		}
		parent::__construct($env, $application);
	}

	/**
	 * @return CMS_Controller
	 */
	public function setup()
	{
		$name = CMS::$current_component_name;
		$this->name = $name;
		$this->mapper = CMS::$current_mapper;
		parent::setup()
			->use_urls_from(CMS::$current_mapper)
			->use_views_from("../app/components/$name/views")
			->use_views_from("../app/components/$name/app/views")
			->use_layout(CMS::$layouts[$name]);

		if (CMS::$print_version || (is_object($this->env->pdf) && $this->env->pdf->active)) {
			$this->use_layout(CMS::$print_layout);
		}
		return $this;
	}

	/**
	 * @param string $reciever
	 *
	 * @return CMS_Controller
	 */
	protected function run_commands($chapter)
	{
		if (!isset(CMS::$commands[$chapter])) {
			return $this;
		}
		$r = Core_Types::reflection_for($this);
		foreach (CMS::$commands[$chapter] as $command) {
			$method = trim($command['method']);
			$m = $r->getMethod($method);
			$m->invokeArgs($this, $command['args']);
		}
		return $this;
	}

	/**
	 * @return string
	 */
	public function make_uri()
	{
		$args = func_get_args();
		if (!$this->make_uri_method) {
			$this->make_uri_method = Core_Types::reflection_for(CMS::$current_mapper)->getMethod('make_uri');
		}
		return $this->make_uri_method->invokeArgs(CMS::$current_mapper, $args);
	}

}


