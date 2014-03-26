<?php
/**
 * CMS.Controller
 * 
 * @package CMS\Controller
 * @version 0.0.0
 */
Core::load('CMS.Controller.Base');
Core::load('CMS.PageNavigator');


/**
 * @package CMS\Controller
 */
class CMS_Controller extends CMS_Controller_Base implements Core_ModuleInterface {

	const MODULE  = 'CMS.Controller';
	const VERSION = '0.0.0';

	public $name;
	public $mapper;
	protected $make_uri_method = false;

	protected $application;

	protected function get_component_name() {
		return CMS::get_component_name_for($this);
	}

	public function __construct($env, $application = null) {
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
	public function setup() {
		$name = CMS::$current_component_name;
		$this->name = $name;
		$this->mapper = CMS::$current_mapper;
		parent::setup()
			->use_urls_from(CMS::$current_mapper)
			->use_views_from("../app/components/$name/views")
			->use_views_from("../app/components/$name/app/views")
			->use_layout(CMS::$layouts[$name])
		;
		
		if (CMS::$print_version || (is_object($this->env->pdf) && $this->env->pdf->active)) $this->use_layout(CMS::$print_layout);
		return $this;
	}






/**
 * @param string $reciever
 * @return CMS_Controller
 */
	protected function run_commands($chapter) {
		if (!isset(CMS::$commands[$chapter])) return $this;
		$r = Core_Types::reflection_for($this);
		foreach(CMS::$commands[$chapter] as $command) {
			$method = trim($command['method']);
			$m = $r->getMethod($method);
			$m->invokeArgs($this,$command['args']);
		}
		return $this;
	}




/**
 * @return string
 */
	public function make_uri() {
		$args = func_get_args();
		if (!$this->make_uri_method) {
			$this->make_uri_method = Core_Types::reflection_for(CMS::$current_mapper)->getMethod('make_uri');
		}
		return $this->make_uri_method->invokeArgs(CMS::$current_mapper,$args);
	}


}


/**
 * @package CMS\Controller
 */
class CMS_Mapper extends WebKit_Controller_AbstractMapper {

	protected $active_controller;
	protected $controllers = array();
	protected $path_prefix;
	protected $request;


/**
 * @param string $url
 * @return string
 */
	public function clean_url($uri) {
		$uri = parent::clean_url($uri);
		$pp = CMS::$print_prefix;
		if (is_string($pp)) {
			if ($m = Core_Regexps::match_with_results("{^/$pp/(.+)}",$uri)) {
				$uri = '/'.$m[1];
				CMS::$print_version = true;
			}
		}
		return $uri;
	}

/**
 * @return iterable
 */
	public function controllers() {
		return $this->controllers;
	}

/**
 * @param string $path
 * @return string
 */
	protected function admin_path_replace($path) {
		$path = str_replace('{admin}',CMS_admin::path(),$path);
		$path = preg_replace_callback('/\{admin:([^}]+)\}/',array($this,'admin_path_replace_cb'),$path);
		return $path;
	}


/**
 * @param WebKit_HTTP_Request $request
 * @return WebKit_Controller_Route
 */
	public function route($request) {
		$this->request = $request;
		$uri = $this->clean_url($request->urn);
		$controllers = $this->controllers();
		if (Core_Types::is_iterable($controllers)) foreach($controllers as $name => $info) {

			if (isset($info['module'])) $name = $info['module'];

			$path = trim($this->admin_path_replace($info['path']));

			if ($path!=''&&$path[0]=='{') {
				$regexp = $path;
			}

			else {
				$regexp = '{^('.$path.')(.*)$}';
			}
			$matched = false;

			if ($m = Core_Regexps::match_with_results($regexp,$uri)) {
				$this->path_prefix = $m[1];
				$path = $m[2];
				$matched = true;
			}

			if (isset($info['host'])) {
				$host = strtolower(trim($info['host']));
				if ($host!='') {
					if ($host[0]=='{') {
						if (!Core_Regexps::match($host,strtolower($request->host)))  $matched = false;
					}

					else {
						if (strtolower($request->host)!=$host) $matched = false;
					}
				}
			}

			if (isset($info['site'])&&($info['site']!=CMS::site())) {
				$matched = false;
			}


			if ($matched) {
				$this->active_controller = $name;

				if (isset($info['table-admin'])&&$info['table-admin']) $rules = array_merge(!empty($info['rules']) ? $info['rules'] : array(), array(
								//'{^$}' => array('list',1,'func' => 'list', 'parms' => 1),
	        			'{^$}' => array('default',1,'func' => 'default', 'parms' => 1),
	        			'{^list\.json$}' => array('list_json','func' => 'list_json', 'parms' => 1),
        				'{^([^/]+)/(.*)}' => array('{1}','{2}','func' => '{1}',  'parms' => '{2}'),
        			));

        			else $rules = $info['rules'];

				if (is_array($rules)) foreach($rules as $rule => $parms) {
					$match = false; if (trim($rule)!='') $match = ($mr = Core_Regexps::match_with_results(trim($rule),$path));
					if (($rule==''&&$path=='')||$match) {
						foreach($parms as $key => $value) {
							if ($mm = Core_Regexps::match_with_results('/^\{(\d+)\}$/',$value)) {
								$parms[$key] = isset($mr[$mm[1]])?$mr[$mm[1]]:null;
							}
						}
						$parms['controller'] = $name;
						return $parms;
					}

				}

				else return array('controller' => $name, 'path' => $path);
			}
		}
		return false;
	}




/**
 * @param string $param1
 * @param string $param1
 * @return string
 */
	public function admin_url($p1='',$p2='') {
		$url = $this->path_prefix;
		$p1 = trim($p1);
		if ($p1!='') $url .= "$p1/";
		$p2 = trim($p2);
		if ($p2!='') $url .= $p2;
		return $url;
	}




/**
 * @param array $matches
 * @return string
 */
	protected function admin_path_replace_cb($m) {
		return CMS::admin_path(trim($m[1]));
	}





}

class CMS_Router extends CMS_Mapper {}


