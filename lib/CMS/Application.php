<?php
/**
 * @package CMS\Application
 */


class CMS_Application extends WS_Services_REST_Application implements Core_ModuleInterface {
	const VERSION = '0.0.0';

	public function find($env) {
		$res = parent::find($env);
		if ($this->is_match) {
			$this->setup_cms_data();
		} else {
			$this->clear_cms_data();
		}
		return $res;
	}
	
	//TODO: move to env
	protected function setup_cms_data() {
		CMS::$current_mapper = $this;
		CMS::$current_component_name = $this->name;
		CMS::$current_controller = $this->target_instance;
	}
	protected function clear_cms_data() {
		CMS::$current_mapper = null;
		CMS::$current_component_name = null;
		CMS::$current_controller = null;
	}
	
	protected function instantiate(WS_Services_REST_Resource $resource, WS_Environment $env, array $parms) {
		$this->setup_cms_data();
		$i = parent::instantiate($resource, $env, $parms);
		return $i;
	}

}

class CMS_Application_Dispatcher extends WS_Services_REST_Dispatcher {

  protected function create_response($app, $env) {
    return parent::create_response($app, $env);
  }

}
