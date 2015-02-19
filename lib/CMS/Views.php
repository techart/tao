<?php
/**
 * @package CMS\Views
 */

Core::load('Templates.HTML');

class CMS_Views implements Core_ModuleInterface
{

	const MODULE = 'CMS.Views';
	const VERSION = '0.0.0';

	static public function TemplateView($template, array $parameters = array())
	{
		return Templates_HTML::Template($template, $parameters);
	}

}

