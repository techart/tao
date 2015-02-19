<?php
/**
 * @package CMS\Fields\Types\Wiki
 */

Core::load('CMS.Fields.Types.HTML', 'CMS.Redactor');

class CMS_Fields_Types_Wiki extends CMS_Fields_Types_HTML implements Core_ModuleInterface
{

	protected $default_editor = 'epic';

	protected $fallback_editor = 'textarea';

	protected function get_editor($name, $data)
	{
		if (isset($data['redactor'])) {
			return CMS_Redactor::get_editor($data['redactor']);
		}
		$main = CMS_Redactor::get_editor($this->default_editor);
		return $main->is_installed() ? $main : CMS_Redactor::get_editor($this->fallback_editor);
	}

}