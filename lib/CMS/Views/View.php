<?php

Core::load('Templates.HTML');

class CMS_Views_View extends Templates_HTML_Template implements Core_ModuleInterface {

	const MODULE = 'CMS.Views.View';
	const VERSION = '0.0.0';

	protected function get_partial_path($name) {
		if (Templates::is_absolute_path($name)) return parent::get_partial_path($name);
		if($this->current_helper instanceof Templates_HelperInterface) {
			$helper = Core_Types::virtual_class_name_for($this->current_helper);
			if ($m = Core_Regexps::match_with_results('{^Component\.([^.]+)\.}', $helper)) {
				if (!Core_Regexps::match('{\.phtml$}',$name)) $name .= '.phtml';
				return CMS::component_dir($m[1],'views') . '/' . $name;
			}
		}
		return parent::get_partial_path($name);
	}

}

