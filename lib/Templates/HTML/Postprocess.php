<?php


class Templates_HTML_Postprocess implements Core_ConfigurableModuleInterface {
  const VERSION = '0.1.0';
  
  static protected $options = array(
    'minify_php_dir' => '../extern/Minify/'
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
  public function postprocess(&$path, &$contnet);
}


class Templates_HTML_Postprocess_MinifyCSSPostprocessor implements Templates_HTML_Postprocess_PostprocessorInterface {
	public function postprocess(&$path, &$contnet) {
		require_once(Templates_HTML_Postprocess::option('minify_php_dir') . 'Compressor.php');
		$contnet = Minify_CSS_Compressor::process($contnet);
	}
}

class Templates_HTML_Postprocess_MinifyJSPostprocessor implements Templates_HTML_Postprocess_PostprocessorInterface {
	public function postprocess(&$path, &$contnet) {
		require_once(Templates_HTML_Postprocess::option('minify_php_dir') . 'JSMin.php');
     	$contnet =  JSMin::minify($contnet);
	}
}