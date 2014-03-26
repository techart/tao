<?php
/**
 * @package Templates\HTML\Assets\Preprocess\LESS
 */


Core::load('Cache', 'WS');

class Templates_HTML_Assets_Preprocess_LESS implements Core_ConfigurableModuleInterface, Templates_HTML_Assets_Preprocess_PreprocessorInterface
{
  const VERSION = '0.1.0';
  
  protected static $options = array(
    'less_class' => 'lessc',
    'less_cache_dns' => 'fs://../cache/less',
    'less_output_dir' => 'styles/less',
    'less_php_dir' => '../vendor/lessphp'
  );

  protected $cache;
  protected $output;
  
  public static function initialize(array $options = array())
  {
    self::options($options);
  }
  
  public static function option($name, $value = null)
  {
    if (is_null($value)) return self::$options[$name];
    return self::$options[$name] = $value;
  }
  
  public static function options(array $options = array())
  {
    self::$options = array_merge(self::$options, $options);
  }
  
  public static function instance()
  {
    $config = (array) WS::env()->config->less;
    if (!empty($config)) self::options($config);
    return Core::make('Templates.HTML.Assets.Preprocess.LESS');
  }
  
  public function __construct()
  {
    $this->cache = Cache::connect(self::option('less_cache_dns'));
    $this->output = self::option('less_output_dir');
  }
  
  protected function load()
  {
    if (!class_exists('lessc')) {
      $path = !empty(WS::env()->config->less->lessphp_dir) 
          ? WS::env()->config->less->lessphp_dir
          : self::option('less_php_dir');
      include_once($path . '/lessc.inc.php');
    }
    IO_FS::mkdir($this->output);
  }

  public function preprocess($file, $data)
  {
    //TODO: to options
    $suffix = '.less';
    $content = null;
    if (Core_Strings::ends_with($file, $suffix)) {
      $file = Templates_HTML::css_path($file, false);
      $file_name = ltrim($file, '/\.');
      $less_file = './' . ltrim($file, '/');
      $css_file = str_replace('css.less', 'less.css', $file_name);
      $css_file = str_replace('styles/', '', $css_file);
      $css_file = './' . self::option('less_output_dir') . '/' . $css_file;
      $css_file = str_replace('//', '/', $css_file);
      $dir = dirname($css_file);
      if (!IO_FS::exists($dir)) {
        IO_FS::mkdir($dir);
      }
      //TODO: errors
      $this->compile($less_file, $css_file);
      return array('/' . ltrim($css_file, '\/.'), $content);
    }
    return array($file, $content);
  }
  
  protected function compile($less_fname, $css_fname)
  {
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
  protected function cexecute($in, $force = false)
  {
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
      $class = self::option('less_class') ;
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
