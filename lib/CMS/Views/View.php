<?php
/**
 * @package CMS\Views\View
 */


Core::load('Templates.HTML');

class CMS_Views_View extends Templates_HTML_Template implements Core_ModuleInterface {

	public function parent()
	{
		return $this->copy(parent::get_path($this->name));
	}

	protected function get_path()
	{
		$paths = array(CMS::current_component_dir('app/views'), CMS::current_component_dir('views'));
		return Templates::get_path($this->name, $this->extension, $paths);
	}

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
		$paths = array_merge(array(CMS::current_component_dir('app/views'), CMS::current_component_dir('views')), $paths);
		return $paths;
	}

}

