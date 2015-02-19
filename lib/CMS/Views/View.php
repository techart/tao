<?php
/**
 * @package CMS\Views\View
 */

Core::load('Templates.HTML');

class CMS_Views_View extends Templates_HTML_Template implements Core_ModuleInterface
{

	protected $is_cache_loaded = false;

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

	public function use_file(array $file, $type = null)
	{
		$path = $file['name'];
		if (!Templates_HTML::path($type, $path)) {
			if (!is_null($type)) {
				$paths = Templates_HTML::option('paths');
				$prefix = $paths[$type];
				if (!Core_Strings::starts_with($path, $prefix)) {
					$path = $prefix . '/' . ltrim($path, '/');
				}
			}
			$component = false;
			if ($file['component']) {
				$component = $file['component'];
			}
			$component_url = CMS::component_static_path($path, $component);
			$component_path = str_replace('file://', '', $component_url);
			if (is_file($component_path)) {
				$file['name'] = $component_url;
			}
		}
		return parent::use_file($file, $type);
	}

	protected function get_helpers()
	{
		$helpers = parent::get_helpers();
		$this->load_helperes_from_cache($helpers);
		return $helpers;
	}

	protected function load_helperes_from_cache($helpers)
	{
		if (!CMS::is_lazy_components()) {
			return;
		}
		if ($this->is_cache_loaded) {
			return;
		}
		$this->is_cache_loaded = true;
		if ($classes = WS::env()->cache->get('cms:viwes:helpers_classes')) {
			foreach ($classes as $name => $class) {
				$helpers->append($class, $name);
			}
		} else {
			Events::add_once('cms.load_components', function() use ($helpers) {
				WS::env()->cache->set('cms:viwes:helpers_classes', $helpers->classes, 0);
			});
			CMS::load_components();
		}
	}
}

