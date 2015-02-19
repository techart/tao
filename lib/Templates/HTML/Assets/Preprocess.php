<?php
/**
 * @package Templates\HTML\Assets\Preprocess
 */

Core::load('Cache', 'WS');

class Templates_HTML_Assets_Preprocess implements Core_ModuleInterface
{
	const VERSION = '0.1.0';

	static public function less()
	{
		Core::load('Templates.HTML.Assets.Preprocess.LESS');
		return Templates_HTML_Assets_Preprocess_LESS::instance();
	}

	static public function scss()
	{
		Core::load('Templates.HTML.Assets.Preprocess.SCSS');
		return Templates_HTML_Assets_Preprocess_SCSS::instance();
	}

}

interface Templates_HTML_Assets_Preprocess_PreprocessorInterface
{
	public function preprocess($path, $data);
}