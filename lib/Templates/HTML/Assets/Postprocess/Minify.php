<?php
/**
 * @package Templates\HTML\Assets\Postprocess\Minify
 */

 class Templates_HTML_Assets_Postprocess_Minify implements Core_ConfigurableModuleInterface
 {
	const VERSION = '0.1.0';

	static protected $options = array(
		'minify_php_dir' => '../vendor/Minify/'
	);

	static public function initialize(array $options = array()) {
		self::options($options);
	}

	static public function option($name, $value = null) {
		if (is_null($value)) return self::$options[$name];
		return self::$options[$name] = $value;
	}

	static public function options(array $options = array()) {
		self::$options = array_merge(self::$options, $options);
	}
}

class Templates_HTML_Assets_Postprocess_Minify_CSSPostprocessor extends Templates_HTML_Assets_Postprocess_PostprocessorPaths implements Templates_HTML_Assets_Postprocess_PostprocessorInterface
{

	protected function base_dir()
	{
		$paths = Templates_HTML::option('paths');
		return $paths['css'];
	}

	public function postprocess($path, $data, $content = null)
	{
		if (!class_exists('CssMin')) {
			$dir = Templates_HTML_Assets_Postprocess_Minify::option('minify_php_dir');
			require_once($dir . 'CssMin/CssMin.php');
		}
		if ($this->filter($path, $data)) {
			if (empty($content)) {
				list($content, $path) = $this->load($path);
			}
			if (!empty($content)) {
				$m = new CssMinifier($content, $filters, $plugins);
				$content = $m->getMinified();
			}
		}
		return array($path, $content);
	}

}

class Templates_HTML_Assets_Postprocess_Minify_JSPostprocessor extends Templates_HTML_Assets_Postprocess_PostprocessorPaths implements Templates_HTML_Assets_Postprocess_PostprocessorInterface
{

	protected function base_dir()
	{
		$paths = Templates_HTML::option('paths');
		return $paths['js'];
	}

	public function postprocess($path, $data, $content = null)
	{
		if (!class_exists('JSMinPlus')) {
			$dir = Templates_HTML_Assets_Postprocess_Minify::option('minify_php_dir');
			require_once($dir . 'JSMin/JSMin.php');
		}
		if ($this->filter($path, $data)) {
			if (empty($content)) {
				list($content, $path) = $this->load($path);
			}
			if (!empty($content)) {
				$content = JSMinPlus::minify($content);
			}
		}
		return array($path, $content);
	}

}
