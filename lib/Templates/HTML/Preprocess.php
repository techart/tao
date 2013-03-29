<?php

Core::load('Cache', 'WS');

class Templates_HTML_Preprocess implements Core_ConfigurableModuleInterface {
  const VERSION = '0.1.0';
  
  static protected $options = array(
    'less_class' => 'lessc',
    'less_cache_dns' => 'fs://../cache/less',
    'less_output_dir' => 'files/less',
    'less_php_dir' => '../extern/lessphp'
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
  
  static public function less() {
    $config = (array) WS::env()->config->less;
    if (!empty($config)) self::options($config);
    return Core::make('Templates.HTML.Preprocess.LessPreprocessor');
  }
}

interface Templates_HTML_Preprocess_PreprocessorInterface {
  public function preprocess($file);
}

class Templates_HTML_Preprocess_LessPreprocessor implements Templates_HTML_Preprocess_PreprocessorInterface  {

  protected $cache;
  
  public function __construct() {
    $this->cache = Cache::connect(Templates_HTML_Preprocess::option('less_cache_dns'));
    $output = Templates_HTML_Preprocess::option('less_output_dir');
    IO_FS::mkdir($output, null,true);
  }
  
  protected function load() {
    $path = !empty(WS::env()->config->less->lessphp_dir) 
      ? WS::env()->config->less->lessphp_dir
      : Templates_HTML_Preprocess::option('less_php_dir');
    include_once($path . '/lessc.inc.php');
  }

  public function preprocess($file) {
    //TODO: to options
    $suffix = '.less';
    if (Core_Strings::ends_with($file, $suffix)) {
      $file = Templates_HTML::css_path($file, false);
      $css_file = md5($file) . '.css';
      $css_file = Templates_HTML_Preprocess::option('less_output_dir') . '/' . $css_file;
      //TODO: errors
      $this->compile('./' . $file, $css_file);
      return '/' . ltrim($css_file, '\/.');
    }
    return $file;
  }
  
  protected function compile($less_fname, $css_fname) {
    $cache = $this->cache->get($less_fname);
    if (empty($cache)) $cache = $less_fname;
    
    $new_cache = $this->cexecute($cache);
    if (!is_array($cache) || $new_cache['updated'] > $cache['updated']) {
      $this->cache->set($less_fname, $new_cache, 0);
      $f = IO_FS::File('./' . $css_fname);
      $f->update($new_cache['compiled']);
      $f->set_permission();
    }
  }
  
  //from lessc.inc.php
  protected function cexecute($in, $force = false) {
    $root = null;
    if (is_string($in)) {
      $root = $in;
    } elseif (is_array($in) and isset($in['root'])) {
      if ($force or ! isset($in['files'])) {
        $root = $in['root'];
      } elseif (isset($in['files']) and is_array($in['files'])) {
        foreach ($in['files'] as $fname => $ftime ) {
          if (!file_exists($fname) or filemtime($fname) > $ftime) {
            $root = $in['root'];
            break;
          }
        }
      }
    } else {
      return null;
    }

    if ($root !== null) {
      $this->load();
      $class = Templates_HTML_Preprocess::option('less_class') ;
      $less = Core::make($class, $root);
      $out = array();
      $out['root'] = $root;
      $out['compiled'] = $less->parse();
      $out['files'] = $less->allParsedFiles();
      $out['updated'] = time();
      return $out;
    } else {
      return $in;
    }

  }

}
