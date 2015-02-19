<?php
/**
 * @package CMS\Redactor\Helper
 */

Core::load('Templates.HTML');

class CMS_Redactor_Helper implements Core_ModuleInterface
{

	static public function initialize()
	{
		Templates_HTML::use_helper('redactor', 'CMS.Redactor.Helper');
	}

	public function add($template, $editor, $selector = '')
	{
		$editor->process_template($template, $selector);
		return '';
	}

	public function attach_to($template, $editor, $selector)
	{
		$editor->attach_to($template, $selector);
		return '';
	}
}
