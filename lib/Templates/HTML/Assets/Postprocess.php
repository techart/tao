<?php

/**
 * @package Templates\HTML\Assets\Postprocess
 */
class Templates_HTML_Assets_Postprocess implements Core_ModuleInterface
{

	const VERSION = '0.1.0';

	static public function MinifyCSS()
	{
		return Core::make('Templates.HTML.Assets.Postprocess.Minify.CSSPostprocessor');
	}

	static public function MinifyJS()
	{
		return Core::make('Templates.HTML.Assets.Postprocess.Minify.JSPostprocessor');
	}

}

interface Templates_HTML_Assets_Postprocess_PostprocessorInterface
{
	public function postprocess($path, $data, $content = null);
}

abstract class Templates_HTML_Assets_Postprocess_PostprocessorPaths
{

	protected function escape_path($path)
	{
		return ltrim($path, '\/.');
	}

	protected function filter($path, $data)
	{
		return isset($data['minify']) && $data['minify'];
	}

	protected function load($path)
	{
		$minify_path = './' . $this->escape_path($this->find_path($path));
		$path = './' . $this->escape_path($path);
		if (@filemtime($minify_path) >= @filemtime($path)) {
			return array(null, '/' . $this->escape_path($minify_path));
		}
		$content = file_exists($path) ? file_get_contents($path) : '';
		return array($content, '/' . $this->escape_path($minify_path));
	}

	protected function base_dir()
	{
		return '';
	}

	protected function find_path($path)
	{
		$base = $this->base_dir();
		$result = str_replace($base, "$base/minify", $path);
		return $result;
	}
}