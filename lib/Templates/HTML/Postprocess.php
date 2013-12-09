<?php
/**
 * @package Templates\HTML\Postprocess
 */



class Templates_HTML_Postprocess implements Core_ConfigurableModuleInterface {
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
  
  static public function MinifyCSS() {
    return Core::make('Templates.HTML.Postprocess.MinifyCSSPostprocessor');
  }

  static public function MinifyJS() {
    return Core::make('Templates.HTML.Postprocess.MinifyJSPostprocessor');
  }

}

interface Templates_HTML_Postprocess_PostprocessorInterface {
  public function postprocess(&$path, &$content);
}


class Templates_HTML_Postprocess_MinifyCSSPostprocessor implements Templates_HTML_Postprocess_PostprocessorInterface {

	public function postprocess(&$path, &$content) {
    if (!class_exists('CssMin')) {
      $dir = Templates_HTML_Postprocess::option('minify_php_dir');
		  require_once($dir . 'CssMin/CssMin.php');
    }
    $m = new CssMinifier($content, $filters, $plugins);
    $content = $m->getMinified();
	}

}

class Templates_HTML_Postprocess_MinifyJSPostprocessor implements Templates_HTML_Postprocess_PostprocessorInterface {

	public function postprocess(&$path, &$content) {
    if (!class_exists('JSMinPlus')) {
      $dir = Templates_HTML_Postprocess::option('minify_php_dir');
      require_once($dir . 'JSMin/JSMin.php');
    }
    $content = JSMinPlus::minify($content);
	}

}