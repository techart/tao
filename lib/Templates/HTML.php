<?php
/**
 * Templates.HTML
 * 
 * HTML шаблоны
 * 
 * @package Templates\HTML
 * @version 0.2.2
 */

Core::load('Templates', 'Object', 'Cache', 'Text.Insertions', 'Templates.HTML.Assets.Preprocess', 'Templates.HTML.Assets');

/**
 * @package Templates\HTML
 */
class Templates_HTML implements Core_ConfigurableModuleInterface  {
  const VERSION = '0.2.2';

  static protected $helpers;
  
  static protected $meta;
  
  static protected $scripts_settings = array();

  protected static $cached_paths = array();
  
  
  static protected $options = array(
    'template_class' => 'Templates.HTML.Template',
    'paths' => array('css' => 'styles', 'js' => 'scripts', '_fallback' => 'images'),
    'assets' => array('', '/tao'),
    'copy_dir' => 'copy',
    'extern_file_prefix' => 'file://',
    'css_dir' => 'styles',
    'js_dir' => 'scripts',
    'layouts_path' => 'layouts',
    'join_scripts' => false,
    'join_styles' => false,
    'add_timestamp' => true,
    'timestamp_pattern' => '%s?%d' // '/nocache/%2$d%1$s'
 );


/**
 * Инициализация модуля
 * 
 * @param array $options
 */
  static public function initialize(array $options = array()) {
    self::$helpers = Object::Aggregator()->fallback_to(Templates::helpers());
    foreach(self::$options as $k => $v) self::$options[$k] = str_replace('%files%',Core::option('files_name'),$v);
    self::options($options);
    self::use_helper('forms', 'Templates.HTML.Helpers.Forms');
    self::use_helper('tags', 'Templates.HTML.Helpers.Tags');
    self::use_helper('assets', 'Templates.HTML.Helpers.Assets');
    self::use_helper('maps', 'Templates.HTML.Helpers.Maps');
  }



/**
 * Устанавливает опции
 * 
 * @param array $options
 * @return mixed
 */
  static public function options(array $options = array()) {
    if (count($options)) Core_Arrays::update(self::$options, $options);
    return self::$options;
  }

/**
 * Устанавливает/возвращает опцию
 * 
 * @param string $name
 * @param  $value
 * @return mixed
 */
  static public function option($name, $value = null) {
    $prev = isset(self::$options[$name]) ? self::$options[$name] : null;
    if ($value !== null) self::options(array($name => $value));
    return $prev;
  }

  static public function add_scripts_settings($vars = array(), $replace = false) {
    if (!empty($vars))
      self::$scripts_settings[] = array($vars, $replace);
    return self::$scripts_settings;
  }
  
  static public function scripts_settings($vars = array(), $replace = false) {
    return self::add_scripts_settings($vars, $replace);
  }

/**
 * Регистрирует хелпер
 * 
 */
  static public function use_helpers() {
    $args = Core::normalize_args(func_get_args());
    foreach ($args as $k => $v) {
      self::$helpers->append($v, $k);
    }
  }

/**
 * @param string $name
 * @param  $helper
 */
  static public function use_helper($name, $helper) {
    self::$helpers->append($helper, $name);
  }

/**
 * @param string $name
 */
  static public function helper($name) {
    return self::$helpers[$name];
  }

/**
 * @param boolean $v
 */
  static public function join_styles($v = true) {
    self::option('join_styles', $v);
  }

/**
 * @param boolean $v
 */
  static public function join_scripts($v = true) {
    self::option('join_scripts', $v);
  }

  static public function is_url($path) {
    return preg_match('{^(http|https|ftp):}', $path);
  }

/**
 * @param stirng $path
 */
  static public function js_path($path, $copy = true) {
    return self::path('js', $path, $copy);
  }

  public static function append_assets_path($path, $position = 1)
  {
    $paths = self::option('assets');
    Core_Arrays::put($paths, $path, $position);
    $paths = array_unique($paths);
    self::option('assets', $paths);
    return $paths;
  }

/**
 * @param stirng $path
 */
  static public function css_path($path, $copy = true) {
    return self::path('css', $path, $copy);
  }

  public function reset_paths_cache()
  {
    self::$cached_paths = array();
  }

  public static function path($type, $path, $copy = true)
  {
    $rc = Events::call('templates.asset.path', $type, $path, $copy);
    if (!is_null($rc)) {
      return $rc;
    }
    $key = "$type;$path;$copy";
    if (isset(self::$cached_paths[$key])) {
      return self::$cached_paths[$key];
    }
    $path = self::extern_filepath($path, $copy);
    if (Templates::is_absolute_path($path) || self::is_url($path)) {
      return self::$cached_paths[$key] = $path;
    }
    $urls = array();
    foreach (self::option('assets') as $asset) {
      if ($asset[0] == '.') {
        $asset = self::option('extern_file_prefix') . $asset;
      }
      $url = sprintf("%s/%s/%s", $asset, self::option("{$type}_dir") , $path);
      $url = self::extern_filepath($url, $copy);
      $urls[] = $url;
      $file = ".{$url}";
      if (is_file($file)) {
        return self::$cached_paths[$key] = $url;
      }
    }
    if (Core::option('deprecated')) {
      $file = Core::tao_deprecated_file('files/'. self::option("{$type}_dir") . '/' . $path);
      if (is_file($file)) {
        return self::$cached_paths[$key] = self::extern_filepath(self::option('extern_file_prefix') . $file, $copy);
      }
    }
    return self::$cached_paths[$key] = null;
  }

/**
 * @param stirng $path
 */
  static public function extern_filepath($file, $copy = true) {
    $file = (string) $file;
    $prefix = self::option('extern_file_prefix');
    if (Core_Strings::starts_with($file, $prefix)) {
      $res = str_replace($prefix, '', $file);
      $res_name = ltrim(str_replace(Core::tao_dir(), '', $res), '/.');
      if ($copy == false) {
        return $res;
      }
      $ext = pathinfo($file, PATHINFO_EXTENSION);
      $paths = Templates_HTML::option('paths');
      $mtime = @filemtime($res);
      if ($mtime) {
        $dir = trim(self::option('copy_dir'), '/');
        $root = $paths['_fallback'];
        if (isset($paths[$ext])) {
          $root = $paths[$ext];
        }
        $base_dir = $root . '/' . $dir;
        $path = sprintf('%s/%s', $base_dir, $res_name);
        $dir = dirname($path);
        if (!is_dir($dir)) {
          IO_FS::mkdir($dir, null, true);
        }
        $mtime_exists = @filemtime($path);
        if (($mtime > $mtime_exists && IO_FS::cp($res, $path)) || IO_FS::exists($path)) {
          return '/' . ltrim($path, '.');
        }
      } else {
        return $res;
      }
    }
    return $file;
  }
  


/**
 * Возвращает делигатор хелперов
 * 
 * @return Object_Aggregator
 */
  static public function helpers() { return self::$helpers; }



/**
 * Фабричный метод, возвращает объект класса Templates.HTML.Template
 * 
 * @param string $name
 * @return Templates_HTML_Template
 */
  static public function Template($name, array $parameters = array(), $js_agregator = null, $css_agregator = null) {
  	return Core::make(self::$options['template_class'], $name, $parameters, $js_agregator, $css_agregator);
  }

  static public function meta() {
    if (is_object(self::$meta)) return self::$meta;
    return self::$meta = new Templates_HTML_Metas();
  }



//TODO: deprecated
/*
  static public function views_path_for($path) {
    return Templates::get_path($path);
  }

  static public function exists($name) {
    return file_exists(self::views_path_for(self::file_name_for($name)));
  }

  static public function file_name_for($name) {
    return $name.(preg_match('{\.[a-zA-Z0-9]+$}', (string) $name) ? '' : '.phtml');
  }
*/

}


/**
 * Класс HTML шаблона
 * 
 * @package Templates\HTML
 */
class Templates_HTML_Template
  extends Templates_NestableTemplate
  implements Core_IndexedAccessInterface {

  protected $content;
  protected $extension = '.phtml';
  protected $agregators = array('css' => null, 'js' => null);
  protected $include_meta = true;
  protected $allow_filtering = true;
  protected $spawn_from;
  protected $scripts_settings = array();
  protected $enable_less = true;
  protected $enable_scss = true;
  protected $no_duplicates = array();
  protected $partial_paths = array();
  protected $use_onpage_to_file = array('js' => false, 'css' => false);
  protected $default_use_attributes = array('js' => false, 'css' => false);


/**
 * Конструктор
 * 
 * @param string $name
 */
  public function __construct($name, array $parameters = array(), $js_agregator = null, $css_agregator = null) {
    parent::__construct($name);
    $this->content = new ArrayObject();
    $this->setup($js_agregator, $css_agregator);
    $this->with($parameters);
  }

  public function setup($js_agregator = null, $css_agregator = null) {
    $this->agregators['js'] = ($js_agregator instanceof Templates_HTML_Assets_AgregatorInterface) ?
      $js_agregator : Templates_HTML_Assets::agregator(array(), 'js');
    $this->agregators['css'] = ($css_agregator instanceof Templates_HTML_Assets_AgregatorInterface) ?
      $css_agregator : Templates_HTML_Assets::agregator(array(), 'css');
    if ($this->enable_less) {
      $this->enable_less();
    }
    if ($this->enable_scss) {
      $this->enable_scss();
    }
    $this->join_scripts(Templates_HTML::option('join_scripts'));
    $this->join_styles(Templates_HTML::option('join_styles'));
  }



  public function clear($name) {
    if ($this->record)
      $this->record_call(__FUNCTION__, func_get_args());
    $this->content[$name] = '';
    return $this;
  }

  public function clear_all() {
    foreach($this->content as $k => $v) {
      $this->clear($k);
    }
    return $this;
  }

  public function no_duplicates_in($name) {
    $this->no_duplicates[$name] = true;
    return $this;
  }

  

  public function merge($obj) {
    parent::merge($obj);
    $this->include_meta = $obj->include_meta;
    $this->allow_filtering($obj->allow_filtering);
    return $this;
  }

  public function use_attrs($type, $attrs = array())
  {
    $this->default_use_attributes[$type] = $attrs;
    return $this;
  }

  public function enable_scss() {
    if (!$this->agregators['css']->get_preprocessor('scss')) {
      $this->agregators['css']->add_preprocessor('scss', 'Templates.HTML.Assets.Preprocess.SCSS');
    }
    return $this;
  }
  
  public function disable_scss() {
    $this->agregators['css']->remove_preprocessor('scss');
    return $this;
  }

  public function enable_less() {
    if (!$this->agregators['css']->get_preprocessor('less'))
    $this->agregators['css']->add_preprocessor('less', 'Templates.HTML.Assets.Preprocess.LESS');
    return $this;
  }
  
  public function disable_less() {
    $this->agregators['css']->remove_preprocessor('less');
    return $this;
  }

  public function minify_styles($v = true) {
    if ($v) {
      Core::load('Templates.HTML.Assets.Postprocess');
      $this->agregators['css']->add_postprocessor('minify', Templates_HTML_Assets_Postprocess::MinifyCSS());
    }
    else {
      $this->agregators['css']->remove_postprocessor('minify');
    } 
    return $this;
  }

  public function minify_scripts($v = true) {
    if ($v) {
      Core::load('Templates.HTML.Assets.Postprocess');
      $this->agregators['js']->add_postprocessor('minify', Templates_HTML_Assets_Postprocess::MinifyJS());
    }
    else {
      $this->agregators['js']->remove_postprocessor('minify');
    } 
    return $this;
  }

  public function allow_filtering($v = true) {
    $this->allow_filtering = $v;
    return $this;
  }

  public function join_styles_group()
  {
    $args = func_get_args();
    call_user_func_array(array($this->agregators['css'], 'join_group'), $args);
    return $this;
  }

  public function join_scripts_group()
  {
    $args = func_get_args();
    call_user_func_array(array($this->agregators['js'], 'join_group'), $args);
    return $this;
  }

  public function join_styles($v = true) {
    $this->agregators['css']->join($v);
    return $this;
  }

  public function join_scripts($v = true) {
    $this->agregators['js']->join($v);
    return $this;
  }

  public function no_join() {
      foreach(Core::normalize_args(func_get_args()) as $file)
          $this->agregators[pathinfo($file, PATHINFO_EXTENSION)]->exclude_join($file);
      return $this;
  }

  public function use_file(array $file, $type = null) {
    if ($this->record)
      $this->record_call(__FUNCTION__, func_get_args());
    if (!Templates_HTML::is_url($file['name'])) {
      $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
      if (empty($ext) && !empty($type) && !Templates::is_absolute_path($file['name'])) {
          $ext = $type;
          $file['name'] = $file['name']. '.' . $ext;
      }
      if ($type === null) $type = $ext;
    }
    if (is_array($this->default_use_attributes[$type])) {
      $file = array_merge($this->default_use_attributes[$type], $file);
    }
    if (isset($this->agregators[$type]))
      $this->agregators[$type][$file['name']] = $file;
    return $this;
  }

  public function use_script($file, array $parms = array()) {
    $parms['name'] = $file;
    return $this->use_file($parms, 'js');
  }
  
  public function use_style($file, array $parms = array()) {
    $parms['name'] = $file;
    return $this->use_file($parms, 'css');
  }

  public function use_styles() {
      foreach (Core::normalize_args(func_get_args()) as $file)
          $this->use_file(is_array($file) ? $file : array('name' => $file), 'css');
      return $this;
  }

  public function use_scripts() {
      foreach (Core::normalize_args(func_get_args()) as $file)
          $this->use_file(is_array($file) ? $file : array('name' => $file), 'js');
      return $this;
  }
  
  public function add_scripts_settings($vars = array(), $replace = false) {
    if (!empty($vars)) {
      if ($this->record)
        $this->record_call(__FUNCTION__, func_get_args());
      $this->root->scripts_settings[] = array($vars, $replace);
    }
    return $this;
  }
  
  protected function build_scripts_settings() {
    $settings = array_merge(Templates_HTML::scripts_settings(), $this->scripts_settings);
    if (!empty($settings)) {
      //FIXME: путь к файлу
      $this->use_script('/tao/scripts/tao.js', array('type' => 'lib', 'weight' => -15));
      $src = '';
      $values = array();
      foreach ($settings as $val)
        if ($val[1])
          $values = Core_Arrays::deep_merge_update($values, $val[0]);
        else
          $values = array_merge_recursive($values, $val[0]);
      $src = 'jQuery.extend(true, TAO.settings, ' . json_encode($values) . ');';
      $this['js'] = $src . $this['js'];
    }
    return '';
  }

  public function use_onpage_to_file($type= 'js', $v = true)
  {
    $this->use_onpage_to_file[$type] = $v;
    return $this;
  }

  protected function onpage_to_file($type='js')
  {
    $enable = $this->use_onpage_to_file[$type];
    if ($enable) {
      $content = $this[$type];
      $request = WS::env()->request;
      $path = 'onpage' . '/' . trim($request->path, '/.');
      $method = "{$type}_path";
      $dir = '.' .  Templates_HTML::$method($path);
      $name = md5($content);
      // $name = md5($request->query);
      $file = $dir . '/' . $name . '.' . $type;
      if (!is_file($file)) {
        IO_FS::rm($dir);
        IO_FS::mkdir($dir);
        file_put_contents($file, $content);
      }
      $this->use_file(array('name' => trim($file, '.'), 'weight' => 100000));
      $this[$type] = '';
    }
    return $this;
  }

  protected function include_files() {
      $res = '';
      $this->onpage_to_file('js');
      $this->onpage_to_file('css');
      foreach ($this->agregators as $type => $a) {
          $method =  $type . '_link';
          foreach ($a as $file_path => $file) {
              $options = !empty($file) && isset($file['attrs']) ? $file['attrs'] : array();
              $add_timestamp = !empty($file) && isset($file['add_timestamp']) ? $file['add_timestamp'] : Templates_HTML::option('add_timestamp');
              if (method_exists($this, $method))
                  $res .= $this->$method($file_path, $options, $add_timestamp);
              else
                  $res .= $this->get_helpers()->$method($file_path, $options, $add_timestamp);
          }
      }
      return $res;
  }

  protected function configure_layoute()
  {
    $this
      ->use_attrs('js', array('type' => 'lib'))
      ->use_attrs('css', array('type' => 'lib'));
    $env = Config::all()->environment;
    if ($env == 'prod' && !Core_Strings::contains($this->name, 'admin')) {
      $this
        ->use_onpage_to_file('js')
        ->use_onpage_to_file('css')
        ->no_duplicates_in('js')
        ->join_styles()->minify_styles()
        ->join_scripts()->minify_scripts();
    }
    return $this;
  }


  public function within_layout($layout) {
    if (Templates::is_absolute_path($layout)) {
      return $this->inside(Templates_HTML::Template($layout));
    }
    $template = Templates_HTML::Template(Templates_HTML::option('layouts_path') . '/' . $layout);
    return $this->inside($template);
  }

  public function no_layout() {
      return $this->pull();
  }

  public function with_parameters(array $parameters = array()) {
      return $this->with($parameters);
  }

/**
 * Устанавливает внутри какого шаблона находиться данный шаблон
 * 
 * @param Templates_Text_Template $container
 * @return Templates_NestableTemplate
 */
  public function inside(Templates_NestableTemplate $container) {
    parent::inside($container);
    foreach ($this->agregators as $name => $ag) {
      foreach ($ag as $fname => $finfo) {
        if (!isset($container->agregators[$name][$fname])) {
          $container->agregators[$name]->add_file_array($fname, $finfo);
        }
      }
    }
    $this->agregators = $container->agregators;
    return $this;
  }

  public function copy($name, $with_content = true)
  {
    $res = is_object($name) ? $name : Templates_HTML::Template($name, array(), $this->agregators['js'], $this->agregators['css']);
    // $res->agregators = $this->agregators;
    $res->helpers = $this->helpers;
    $res->no_duplicates = $this->no_duplicates;
    if ($with_content) {
      $res->content = $this->content;
    }
    return $res;
  }

  public function spawn($name) {
    $res = $this->copy($name, false);
    $res->spawn_from = $this->root;
    return $res;
  }



/**
 * Начинает запись блока
 * 
 * @param  $block
 * @return Templates_Text_Template
 */
  public function begin($block) {
    ob_start();
    return $this;
  }

/**
 * Заканчивает запись блока и сохраняет его
 * 
 * @param  $block
 * @param добавить или заменить текущий блок $prepend
 * @return Templates_Text_Template
 */
  public function end($block, $append = true) {
    $value = ob_get_clean();
    return $this->content($block, $value, $append);
    // $this->content[$block] = ($append && isset($this->content[$block]) ? $this->content[$block] : '') . $value;
    // return $this;
  }

/**
 * Создает блок и заполняет его контентом
 * 
 * @param string $name
 * @param string $content
 * @param boolean $prepend
 * @return Templates_HTML_Template
 */
  public function content($name, $content, $prepend = true) {
    if ($this->record)
        $this->record_call(__FUNCTION__, func_get_args());
    $old_content = isset($this->content[$name]) ? $this->content[$name] : '';

    $root = $this->root;
    $spawn = $this->spawn_from;
    if ( $this->no_duplicates[$name] &&
          ( $spawn && Core_Strings::contains($spawn[$name], $content) ) || (Core_Strings::contains($this[$name], $content) || Core_Strings::contains($root[$name], $content) ))
      return $this;

    if ($prepend)
      $this->content[$name] = $content.$old_content;
    else
      $this->content[$name] = $old_content.$content;
    return $this;
  }

/**
 * Добавить контент к концу блоку
 * 
 * @param string $name
 * @param string $content
 * @return Templates_HTML_Template
 */
  public function prepend_to($name, $content) { return $this->content($name, $content, true); }

/**
 * Добавить контент к началу блоку
 * 
 * @param string $name
 * @param string $content
 * @return Templates_HTML_Template
 */
  public function append_to($name, $content) { return $this->content($name, $content, false); }

/**
 * Добавляет контент к блоку, если этот блок пустой
 * 
 * @param string $block
 * @param string $content
 * @return Templates_HTML_Template
 */
  public function if_empty($block, $content) {
    if (!isset($this->content[$block])) $this->contnet($block, $content);
    return $this;
  }

//TODO: deprecated use meta
//-----------------------
/**
 * Зополняет блок title, если он пустой
 * 
 * @param string $content
 * @return Templates_HTML_Template
 */
  public function title($content) { return $this->if_empty('title', htmlspecialchars($content)); }

/**
 * Зополняет блок description, если он пустой
 * 
 * @param string $content
 * @return Templates_HTML_Template
 */
  public function description($content) { return $this->if_empty('description', htmlspecialchars($content)); }
//-----------------------

  protected function partial_cache_key($name, $parms) {
     return $this->cache_key($name, $parms, 'partial');
  } 

/**
 * @return string
 */
  public function partial_cache($name, $params = array(), $key = null, $timeout = null) {
    $key = $key ? $key : $this->partial_cache_key($name, $params);
    return $this->cached_call($key, array($this, 'partial'), array($name, $params));
  }

/**
 * Возвращает результат шаблона с именем $__name
 * 
 * @param string $name
 * @return string
 */
  public function partial($__name, $__params = array()) {
    Events::call('templates.partial', $this, $__name, $__params);
    extract(array_merge($this->get_parms(), $__params));

    if (IO_FS::exists($__path = $this->get_partial_path($__name))) {
       ob_start();
       include($__path);
       $__content = ob_get_clean();
       //return $__content;
       return $this->filter($__content);
    } else
      throw new Templates_MissingTemplateException($__path);
  }

/**
 * Компанует вместе несколько вызовов partial или/и строки
 * 
 * @return string
 */
  public function compose() {
    $r = '';
    $args = func_get_args();
    foreach ($args as $part) {
      $r .= is_array($part) ?
        call_user_func_array(array($this, 'partial'), $part) :
        (string) $part;
    }
    return $r;
  }

/**
 * Формирует html-таг
 * 
 * @param string $name
 * @param array $attrs
 * @param boolean $close
 * @return string
 */
  public function tag($name, array $attrs = array(), $close = true) {
    $tag = '<'.((string) $name);

    foreach ($attrs as $k => $v)
      $tag .= ($v === true ? " $k " : ( $v === false ? '' :  " $k=\"".htmlspecialchars((string) $v).'"'));

    return $tag .= (boolean) $close ? ' />' : '>';
  }

/**
 * Формирует таг с контеном
 * 
 * @param string $name
 * @param string $content
 * @param array $attrs
 * @return string
 */
  public function content_tag($name, $content, array $attrs = array()) {
    $tag = '<'.((string) $name);

    foreach ($attrs as $k => $v) {
      $tag .= ($v === true ? " $k " : ( $v === false ? '' :  " $k=\"".htmlspecialchars($v).'"'));
    }

    return $tag .= '>'.((string) $content).'</'.((string) $name.'>');
  }

  public function attributes($attrs) {
    $tag = '';
    foreach ($attrs as $k => $v) {
      $tag .= ($v === true ? " $k " : ( $v === false ? '' :  " $k=\"".htmlspecialchars($v).'"'));
    }
    return $tag;
  }

/**
 * Формирует таг a
 * 
 * @param string $url
 * @param string $content
 * @param array $attrs
 * @return string
 */
  public function link_to($url, $content, array $attrs = array()) {
    return $this->content_tag('a', $content, array_merge($attrs, array('href' => $url)));
  }

/**
 * Формирует mailto ссылку
 * 
 * @param string $address
 * @param string $body
 * @param array $attributes
 * @return string
 */
  public function mail_to($address, $body = '', array $attributes = array()) {
    return $this->link_to("mailto:$address", ($body ? $body : $address), $attributes);
  }

/**
 * Формирует html-форму с кнопкой, отправляющей запрос по указанному адресу
 * 
 * @param string $url
 * @param string $text
 * @param string $method
 * @param array $attributes
 * @return string
 */
  public function button_to($url, $text, $method = 'get', array $attributes = array()) {
    $confirmation = Core_Arrays::pick($attributes, 'confirm');
    return $this->
      content_tag('form',
        (($method == 'get' || $method == 'post') ? '' :
          $this->tag('input', array(
            'type' => 'hidden', 'name' => '_method', 'value' => $method ))).
        $this->content_tag('button', $text, array(
          'type' => 'submit',
          'onclick' => $confirmation ? 'return '.$this->make_confirmation($confirmation).';' : false,
        ) + $attributes),
        array(
          'action' => $url,
          'method' => ($method == 'get' || $method == 'post' ? $method : 'post')));
  }

/**
 * Формирует кнопку, отправляющую запрос по указанному адресу
 * 
 * @param string $url
 * @param string $text
 * @param string $method
 * @param array $attributes
 * @return string
 */
  public function form_button_to($url, $text, $method = 'get', array $attributes = array()) {
    $confirmation = Core_Arrays::pick($attributes, 'confirm');
    return $this->tag('input', array(
      'value'   => $text,
      'type'    => 'submit',
      'onclick' => "this.form.action='$url';".
                   'this.form.method=\''.($method == 'get' || $method == 'post' ? $method : 'post').'\';'.
                   "this.form.elements._method.value='$method';".
                   'return '.$this->make_confirmation($confirmation).';') +
      $attributes);
  }

/**
 * Формирует img таг
 * 
 * @param string $url
 * @param array $attrs
 */
  public function image($url, array $attrs = array()) {
    return $this->tag('img', array_merge($attrs, array('src' => $url)));
  }

/**
 * Формирует таг script ссылающийся на указанный js-скрипт
 * 
 * @param string $path
 * @param array $options
 * @return string
 */
  public function js_link($path, array $options = array(), $add_timestamp = true) {
    return $this->content_tag('script',  '',
        array_merge(array(
            'src' => $this->js_path($path, $add_timestamp)
          ),
          $options))."\n";
  }

/**
 * Формирует link таг ссылающийся на указанный css-файл
 * 
 * @param string $path
 * @param array $options
 * @return string
 */
  public function css_link($path, array $options = array(),  $add_timestamp = true) {
    return $this->tag('link',
        array_merge(array(
            'rel' => 'stylesheet',
            'type' => 'text/css',
            'href' => $this->css_path($path, $add_timestamp)
          ),
          $options))."\n";
  }

  protected function js_path($path, $add_timestamp = true) {
    $path = Templates_HTML::js_path($path);
    return $add_timestamp ? $this->add_timestamp($path) : $path;
  }
  
  protected function add_timestamp($link) {
    $ts = @filemtime('./' . ltrim($link, '/'));
    return !empty($ts) ?
      sprintf(Templates_HTML::option('timestamp_pattern'), $link, $ts) :
      $link;
  }
  
  protected function css_path($path, $add_timestamp = true) {
    $path = Templates_HTML::css_path($path);
    return $add_timestamp ? $this->add_timestamp($path) : $path;
  }

/**
 * @return Templates_HTML_Template
 */
  public function js($string) {
    if (empty($string)) return '';
    return $this->content_tag('script', $string);
  }

/**
 * @return Templates_HTML_Template
 */
  public function css($string) {
    if (empty($string)) return '';
    return $this->content_tag('style', $string, array('type' => 'text/css'));
  }




/**
 * Доступ на чтение к свойствам объекта
 * 
 * @param string $property
 * @return mixed
 */
  public function __get($property) {
    switch (true) {
      case $property == 'meta':
        return Templates_HTML::meta();
      case $property == 'no_duplicates':
        if (!empty($this->$property)) return $this->$property;
        if (!$this->is_root && !empty($this->root->$property)) return $this->root->$property;
        // if ($this->spawn_from) var_dump($this->spawn_from->name);
        if ($this->spawn_from) return $this->spawn_from->$property;
        return null;
      case in_array($property, array('agregators', 'include_meta', 'allow_filtering', 'content', 'scripts_settings')):
        return $this->$property;
      case Core_Strings::ends_with($property, '_agregator'):
        $names = explode('_', $property);
        if (isset($this->agregators[$names[0]]))
            return $this->agregators[$names[0]];
        else
            throw new Core_MissingPropertyException($property);
      default:
        return parent::__get($property);
    }
  }

/**
 * Доступ на запись к свойствам объекта
 * 
 * @param string $property
 * @param  $value
 * @return mixed
 */
  public function __set($property, $value) {
    switch (true) {
      case in_array($property, array('agregators', 'meta')):
        throw new Core_ReadOnlyPropertyException($property);
      case in_array($property, array('include_meta', 'allow_filtering')):
        $this->$property = (boolean) $value; 
        return $this;
      case Core_Strings::ends_with($property, '_agregator'):
        $names = explode('_', $property);
        if ($value instanceof Templates_HTML_FilesAgregatorInterface) {
            $this->agregators[$names[0]] = $value;
            return $this;
        }
        else
            throw new Core_MissingPropertyException($property);
      default:
        return parent::__set($property, $value);
    }
  }

/**
 * Проверяет установленно ли свойство объекта
 * 
 * @param string $property
 * @return boolean
 */
  public function __isset($property) {
    switch (true) {
      case in_array($property, array('agregators', 'include_meta', 'meta', 'allow_filtering', 'no_duplicates')):
        return true;
      case Core_Strings::ends_with($property, '_agregator'):
        $names = explode('_', $property);
        return isset($this->agregators[$names[0]]);
      default:
        return parent::__isset($property);
    }
  }

/**
 * Очищает свойство объекта
 * 
 * @param string $property
 */
  public function __unset($property) {
    switch (true) {
      case in_array($property, array('agregators', 'include_meta', 'meta', 'allow_filtering')):
         throw new Core_UndestroyablePropertyException($property);
      case Core_Strings::ends_with($property, '_agregator'):
        $names = explode('_', $property);
        unset($this->agregators[$names[0]]);
        return $this;
      default:
        return parent::__unset($property);
    }
  }


   public function __sleep(){
    return array_merge(parent::__sleep(), 
      array('include_meta', 'allow_filtering', 'enable_less'));
  }


  public function build_head() {
    $res = '';
    if ($this->include_meta)
      $res .= Templates_HTML::meta();
    if (isset($this->content['head'])) $res .= $this->content['head'];
    $res .= $this->build_scripts_settings();
    $res .= $this['include_files'];
    $res .= $this->js($this['js']); //TODO: сохранять в фаил
    $res .= "\n";
    $res .= $this->css($this['css']);
    $res .= "\n";
    return $res;
  }
  
  public function head_line($line) {
    $this->append_to('head', $line . "\n");
    return $this;
  }


/**
 * Возвращает содержтмое блока с именем $index
 * 
 * @param string $index
 * @return mixed
 */
  public function offsetGet($index) {
    switch (true) {
      case $index == 'include_files':
        return $this->include_files();
      case $index == 'head':
        return '%head{}';
      default:
       return isset($this->content[$index]) ? $this->content[$index] : '';
    }
    // isset($this->content[$index]) ? $this->content[$index] : '';
  }

/**
 * Устанавливает содержимое блока
 * 
 * @param string $index
 * @param  $value
 * @return mixed
 */
  public function offsetSet($index, $value) {
    return $this->clear($index)->content($index, (string) $value);
  }

/**
 * Проверяет существует ли блок
 * 
 * @param string $index
 * @return boolean
 */
  public function offsetExists($index) { return isset($this->content[$index]); }

/**
 * Удаляет блок
 * 
 * @param string $index
 */
  public function offsetUnset($index) { unset($this->content[$index]); }



/**
 * Формирует js confirm вызов
 * 
 * @param string $text
 * @return string
 */
  protected function make_confirmation($text) {
    return $text ? "confirm('".Core_Strings::replace($text, "'", "\'")."')": 'true';
  }

  public function set_patrial_paths($paths = array(), $base_name = '')
  {
    $suffix = trim(preg_replace('{/?[^/]+$}', '', $base_name), './');
    if ($base_name && $suffix) {
      foreach ($paths as $k => $p) {
          $paths[$k] = rtrim($p, '/') . '/' . $suffix;
      }
    }
    $this->partial_paths = $paths;
    return $this;
  }

  public function partial_paths($paths = array(), $base_name = '')
  {
    if (!empty($paths)) {
      $this->set_patrial_paths($paths, $base_name);
    }
    $result = $this->partial_paths;
    $base_path = rtrim(preg_replace('{/[^/]+$}', '', $this->get_path()), '/');
    if (array_search($base_path, $result) === false) {
      $result[] = $base_path;
    }
    return $result;
  }

/**
 * Возвращает путь до partial шаблона
 * 
 * @param string $name
 * @return string
 */
  protected function get_partial_path($name, $paths = array()) {
    if (Core_Strings::starts_with($name, '!')) {
      $trace = debug_backtrace();
      foreach ($trace as $k => $t)
        if (Core_Strings::ends_with($t['file'], $this->extension)) break;
      return Templates::add_extension(dirname($trace[$k]['file']) . '/' . substr($name, 1), $this->extension);
    }
    $file = Templates::add_extension($name, $this->extension);
    if (Templates::is_absolute_path($name)) {
      return $file;
    }
    $paths = array_merge($paths, $this->partial_paths(), Templates::option('templates_root'));
    foreach ($paths as $path) {
      $result = rtrim($path, '/') . '/'. $file;
      if (is_file($result)) {
        return $result;
      }
    }
    return $result;
  }

/**
 * Возвращает делегатор хелперов текущего шаблона
 * 
 * @return Object_Aggregator
 */
  protected function get_helpers() {
    return Core::if_null($this->helpers,
      $this->container ? $this->container->get_helpers() : Templates_HTML::helpers());
  }

/**
 * Инклюдит указанный фаил, создавая необходимые переменные
 * 
 * @return Templates_HTML_Template
 */
  protected function load($__path) {
    extract($this->get_parms());
    if (IO_FS::exists($__path))
      include($__path);
    else
      throw new Templates_MissingTemplateException($__path);
    return $this;
  }

/**
 * Возвращает конечный результат
 * 
 * @param ArrayObject $content
 * @return string
 */
  protected function render_nested(ArrayObject $content = null) {
    Events::call('templates.render', $this, $content);
    if ($content) $this->update_content($content);
    $this->render_self();
    if (!empty($this->spawn_from)) $this->spawn_from->update_content($this->content, array('content'));
    $rc = $this->container ?
      $this->container->render_nested($this->content) :
      $this->filter($this->content['content']);
    Events::call('templates.render_result', $rc, $this, $content);
    return $rc;
  }

  protected function update_content($content, $ignore = array()) {
    foreach ($content as $k => $v) {
      if (in_array($k, $ignore)) continue;
      if (is_string($v) && !empty($this->content[$k]))
        $this->content[$k] .= $v;
      else
        $this->content[$k] = $v;
    }
    return $this;
  }

  protected function self_content() {
    ob_start();
    $this->load($this->path);
    return ob_get_clean();
  }

  protected function render_self() {
    if ($this->cache_self) {
      $key = $this->cache_key('content', $this->parms, 'self');
      $__content = $this->cached_call($key, array($this, 'self_content'), array(), null, true);
    } else {
      $__content = $this->self_content();
    }
    $this->content['content'] = $__content; //$this->filter($__content);
  }

  protected function filter($content) {
    $content = $this->allow_filtering ? $this->filter_custom($content) : $content;
    $content = $this->root->filter_required($content);
    return $content;
  }
  
  protected function filter_required($content) {
    $name = 'html_required:' . $this->root->name;
    $filter = Text_Insertions::filter($name, false);
    if (!$filter->exists('head')) {
      $filter->register(array('head' => new Core_Call($this, 'build_head')));
    }
    return $filter->process($content, array('layout' => $this));
  }
  
  protected function filter_custom($content) {
    return Text_Insertions::filter()->process($content, array('layout' => $this));
  }

}

//TODO: Iterator
/**
 * @package Templates\HTML
 */
class Templates_HTML_Metas implements Core_PropertyAccessInterface, 
             Core_CallInterface,
             Core_StringifyInterface {
  
  protected $title = '';
  protected $http  = array();
  protected $named = array();


/**
 * @param string $method
 * @param array $args
 * @return WebKit_Metas_MetaTags
 */
  public function __call($method, $args) {
    switch ($method) {
      case 'title':
        $this->title = $args[0];
        break;
      default:
        $this->is_http_field($method) ?
          $this->http[$this->http_field_name($method)] = $args[0] :
          $this->named[$method] = $args[0];
        break;
    }
    return $this;
  }
  


/**
 * @param string $property
 * @return string
 */
  public function __get($property) {
    switch ($property) {
      case 'title':
        return $this->title;
      default:
        return $this->is_http_field($property) ?
          (isset($this->http[$property = $this->http_field_name($property)]) ? $this->http[$property] : null) : 
          (isset($this->named[$property]) ? $this->named[$property] : null);
    }
  }
  
/**
 * @param string $property
 * @param string $value
 * @return string
 */
  public function __set($property, $value) {
    switch ($property) {
      case 'title':
        return $this->title = $value;
      default:
        $this->$property($value);
        return $value;
    }
  }
  
/**
 * @param string $property
 * @param string $value
 * @return boolean
 */
  public function __isset($property) {
    return $property == 'title' || 
           ($this->is_http_field($name) ?
             isset($this->http[$this->http_field_name($name)]) : 
             isset($this->named[$name]));   
  }
  
/**
 * @param string $property
 * @param string $value
 */
  public function __unset($property) {
    if ($property == 'title') throw new Core_UndestroyablePropertyException($property);
    
    if ($this->is_http_field($property))
      if (isset($this->http[$property = $this->http_field_name($property)])) unset($this->http[$property]);
    else
      if (isset($this->named[$property])) unset($this->named[$property]);  
  }

  

  protected function http_to_string($name, $content) {
    return sprintf("<meta http-equiv=\"%s\" content=\"%s\" />\n", 
        htmlspecialchars(
          Core_Arrays::join_with('-', 
            Core_Arrays::map('return ucfirst(strtolower($x));',
              Core_Strings::split_by('_', $name)))), 
        htmlspecialchars($content));
  }
  
  protected function named_to_string($name, $content) {
    return sprintf("<meta name=\"%s\" content=\"%s\" />\n", htmlspecialchars($name), htmlspecialchars($content));
  }
  
/**
 * @return string
 */
  public function as_string() {
    $result = '';
    if (isset($this->http['content_type'])) {
      $result = $this->http_to_string('content_type', $this->http['content_type']);
      unset($this->http['content_type']);
    }
    
    $result .= sprintf("<title>%s</title>\n", htmlspecialchars($this->title));
    
    foreach ($this->http as $name => $content)
      $result .= $this->http_to_string($name, $content);
    
    foreach ($this->named as $name => $content) 
      $result .= $this->named_to_string($name, $content);
  
    return $result;
  }
  
/**
 * @return string
 */
  public function __toString() { return $this->as_string(); }
  
  

/**
 * @param string $name
 * @return boolean
 */
  protected function is_http_field($name) { return Core_Strings::starts_with($name, 'http_'); }
  
/**
 * @param string $name
 * @return string
 */
  protected function http_field_name($name) {
    return Core_Strings::downcase(
      Core_Regexps::replace('{^http_}', '', $name));
  }

  
}

