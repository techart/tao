<?php

Core::load('Templates.HTML');

class CMS_Views_View extends Templates_HTML_Template implements Core_ModuleInterface {

	const MODULE = 'CMS.Views.View';
	const VERSION = '0.0.0';

	public function partial_paths($paths = array(), $base_name = '')
	{
		$paths = parent::partial_paths($paths, $base_name);
		if ($this->current_helper instanceof Templates_HelperInterface) {
			$cname = CMS::get_component_name_for($this->current_helper);
			if ($cname) {
				$component_paths = array();
				foreach (array('app/views', 'views') as $v) {
					$component_paths[] = rtrim(CMS::component_dir($cname, $v), '/');
				}
				$paths = array_merge($component_paths, $paths);
			}
		}
		return $paths;
	}

}

