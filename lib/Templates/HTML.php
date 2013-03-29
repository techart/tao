<?php
/// <module name="Templates.HTML" version="0.2.2" maintainer="timokhin@techart.ru">
///   <brief>HTML шаблоны</brief>

Core::load('Templates', 'Object', 'Cache', 'Text.Insertions', 'Templates.HTML.Preprocess', 'Templates.HTML.Includes');

/// <class name="Templates.HTML" stereotype="module">
///   <implements interface="Core.ConfigurableModuleInterface" />
///   <depends supplier="Templates.HTML.Template" stereotype="creates" />
///   <depends supplier="Templates" stereotype="uses" />
class Templates_HTML implements Core_ConfigurableModuleInterface  {
///   <constants>
  const VERSION = '0.2.2';
///   </constants>

  static protected $helpers;
  
  static protected $meta;
  
  static protected $scripts_settings = array();
  
  
  static protected $options = array(
    'template_class' => 'Templates.HTML.Template',
    'assets' => '',
    'copy_dir' => '/%files%/copy',
    'extern_file_prefix' => 'file://',
    'css_dir' => 'styles',
    'js_dir' => 'scripts',
    'layouts_path' => '../app/layouts',
    'join_scripts' => false,
    'join_styles' => false,
    'add_timestamp' => true,
    'timestamp_pattern' => '%s?%d' // '/nocache/%2$d%1$s'
 );

///   <protocol name="creating">

///   <method name="initialize" scope="class">
///     <brief>Инициализация модуля</brief>
///     <args>
///       <arg name="options" type="array" default="array()" brief="массив опций" />
///     </args>
///     <body>
  static public function initialize(array $options = array()) {
    self::$helpers = Object::Aggregator()->fallback_to(Templates::helpers());
    foreach(self::$options as $k => $v) self::$options[$k] = str_replace('%files%',Core::option('files_name'),$v);
    self::options($options);
    self::use_helper('forms', 'Templates.HTML.Forms');
    self::use_helper('tags', 'Templates.HTML.Tags');
    self::use_helper('assets', 'Templates.HTML.Assets');
    self::use_helper('maps', 'Templates.HTML.Maps');
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="configuring">

///   <method name="options" returns="mixed" scope="class">
///     <brief>Устанавливает опции</brief>
///     <args>
///       <arg name="options" type="array" default="array()" brief="массив опций" />
///     </args>
///     <body>
  static public function options(array $options = array()) {
    if (count($options)) Core_Arrays::update(self::$options, $options);
    return self::$options;
  }
///     </body>
///   </method>

///   <method name="option" returns="mixed">
///     <brief>Устанавливает/возвращает опцию</brief>
///     <args>
///       <arg name="name" type="string" brief="имя опции" />
///       <arg name="value" default="null" brief="значение" />
///     </args>
///     <body>
  static public function option($name, $value = null) {
    $prev = isset(self::$options[$name]) ? self::$options[$name] : null;
    if ($value !== null) self::options(array($name => $value));
    return $prev;
  }
///     </body>
///   </method>

  static public function add_scripts_settings($vars = array(), $replace = false) {
    if (!empty($vars))
      self::$scripts_settings[] = array($vars, $replace);
    return self::$scripts_settings;
  }
  
  static public function scripts_settings($vars = array(), $replace = false) {
    return self::add_scripts_settings($vars, $replace);
  }

///   <method name="use_helpers" scope="class">
///     <brief>Регистрирует хелпер</brief>
///     <body>
  static public function use_helpers() {
    $args = Core::normalize_args(func_get_args());
    foreach ($args as $k => $v)
      if ($v instanceof Templates_HelperInterface) self::$helpers->append($v, $k);
  }
///     </body>
///   </method>

///   <method name="use_helper" scope="class">
///     <args>
///       <arg name="name" type="string" />
///       <arg name="helper" />
///     </args>
///     <body>
  static public function use_helper($name, $helper) {
    self::$helpers->append($helper, $name);
  }
///     </body>
///   </method>

///   <method name="helper" >
///     <args>
///       <arg name="name" type="string" />
///     </args>
///     <body>
  static public function helper($name) {
    return self::$helpers[$name];
  }
///     </body>
///   </method>

///   <method name="join_styles" >
///     <args>
///       <arg name="v" type="boolean" default="true" />
///     </args>
///     <body>
  static public function join_styles($v = true) {
    self::option('join_styles', $v);
  }
///     </body>
///   </method>

///   <method name="join_scripts" >
///     <args>
///       <arg name="v" type="boolean" default="true" />
///     </args>
///     <body>
  static public function join_scripts($v = true) {
    self::option('join_scripts', $v);
  }
 ///     </body>
///   </method>

  static public function is_url($path) {
    return preg_match('{^(http|https|ftp):}', $path);
  }

///   <method name="js_path" >
///     <args>
///       <arg name="path" type="stirng" />
///     </args>
///     <body>
  static public function js_path($path, $copy = true) {
    $path = self::extern_filepath($path, $copy);
    return Templates::is_absolute_path($path) || self::is_url($path) ? $path : sprintf("%s/%s/%s", self::option('assets'), self::option('js_dir') , $path);
  }
///     </body>
///   </method>

///   <method name="css_path" >
///     <args>
///       <arg name="path" type="stirng" />
///     </args>
///     <body>
  static public function css_path($path, $copy = true) {
    $path = self::extern_filepath($path, $copy);
    return Templates::is_absolute_path($path) || self::is_url($path) ? $path : sprintf("%s/%s/%s", self::option('assets'), self::option('css_dir'),  $path);
  }
///     </body>
///   </method>

///   <method name="extern_filepath" >
///     <args>
///       <arg name="path" type="stirng" />
///     </args>
///     <body>
  static public function extern_filepath($file, $copy = true) {
    $file = (string) $file;
    $prefix = self::option('extern_file_prefix');
    if (Core_Strings::starts_with($file, $prefix)) {
      $res = str_replace($prefix, '', $file);
      if ($copy == false) {
        return $res;
      }
      $ext = pathinfo($file, PATHINFO_EXTENSION);
      $mtime = @filemtime($res);
      if ($mtime) {
        $name = md5($res . $mtime);
        $dir = sprintf('.%s/%s', self::option('copy_dir'), $ext);
        if (!is_dir($dir)) IO_FS::mkdir($dir, null, true);
        $path = sprintf('%s/%s.%s', $dir, $name, $ext);
        if (IO_FS::exists($path) || IO_FS::cp($res, $path))
          return ltrim($path, '.');
      }
    }
    return $file;
  }
///     </body>
///   </method>

///   <method name="layouts_path_for">
///     <args>
///       <arg name="path" type="string" />
///     </args>
///     <body>
  static public function layouts_path_for($path) {
    return self::option('layouts_path') . '/' . ((string) $path);
  }
///     </body>
///   </method>
  
///   </protocol>

///   <protocol name="quering">

///   <method name="helpers" returns="Object.Aggregator">
///     <brief>Возвращает делигатор хелперов</brief>
///     <body>
  static public function helpers() { return self::$helpers; }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="building">

///   <method name="Template" returns="Templates.HTML.Template" scope="class">
///     <brief>Фабричный метод, возвращает объект класса Templates.HTML.Template</brief>
///     <args>
///       <arg name="name" type="string" brief="имя шаблона" />
///     </args>
///     <body>
  static public function Template($name, array $parameters = array(), $js_agregator = null, $css_agregator = null) {
  	return Core::make(self::$options['template_class'], $name, $parameters, $js_agregator, $css_agregator);
  }
///     </body>
///   </method>

  static public function meta() {
    if (is_object(self::$meta)) return self::$meta;
    return self::$meta = new Templates_HTML_Metas();
  }

///   </protocol>


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
/// </class>


/// <class name="Templates.HTML.Template" extends="Templates.NestableTemplate">
///   <brief>Класс HTML шаблона</brief>
///   <implements interface="Core.IndexedAccessInterface" />
///   <depends supplier="Templates.MissingTemplateException" stereotype="throws" />
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
  protected $enable_less = false;
  protected $no_duplicates = array();

///   <protocol name="creating">

///   <method name="__construct">
///     <brief>Конструктор</brief>
///     <args>
///       <arg name="name" type="string" brief="имя шаблона" />
///     </args>
///     <body>
  public function __construct($name, array $parameters = array(), $js_agregator = null, $css_agregator = null) {
    parent::__construct($name);
    $this->content = new ArrayObject();
    $this->setup($js_agregator, $css_agregator);
    $this->with($parameters);
  }
///     </body>
///   </method>

  public function setup($js_agregator = null, $css_agregator = null) {
    $this->agregators['js'] = ($js_agregator instanceof Templates_HTML_Includes_AgregatorInterface) ?
      $js_agregator : Templates_HTML_Includes::agregator(array(), 'js');
    $this->agregators['css'] = ($css_agregator instanceof Templates_HTML_Includes_AgregatorInterface) ?
      $css_agregator : Templates_HTML_Includes::agregator(array(), 'css');
    if ($this->enable_less) $this->enable_less();
    $this->join_scripts(Templates_HTML::option('join_scripts'));
    $this->join_styles(Templates_HTML::option('join_styles'));
  }

///   </protocol>


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

  public function enable_less() {
    if (!$this->agregators['css']->get_preprocessor('less'))
    $this->agregators['css']->add_preprocessor('less', Templates_HTML_Preprocess::less());
    return $this;
  }
  
  public function disable_less() {
    $this->agregators['css']->remove_preprocessor('less');
    return $this;
  }

  public function minify_styles($v = true) {
    if ($v) {
      Core::load('Templates.HTML.Postprocess');
      $this->agregators['css']->add_postprocessor('minify', Templates_HTML_Postprocess::MinifyCSS());
    }
    else {
      $this->agregators['css']->remove_postprocessor('minify');
    } 
    return $this;
  }

  public function minify_scripts($v = true) {
    if ($v) {
      Core::load('Templates.HTML.Postprocess');
      $this->agregators['js']->add_postprocessor('minify', Templates_HTML_Postprocess::MinifyJS());
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
      $this->use_script('/files/_assets/scripts/tao.js', array('type' => 'lib', 'weight' => -15));
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

  protected function include_files() {
      $res = '';
      foreach ($this->agregators as $type => $a) {
          $method =  $type . '_link';
          foreach ($a as $file) {
              if (method_exists($this, $method))
                  $res .= $this->$method($file);
              else
                  $res .= $this->get_helpers()->$method($file);
          }
      }
      return $res;
  }


  //TODO: deprecated alises:
  //---------------------------------
  public function within_layout($layout) {
    $names = $this->make_layout_paths($layout);
    $class = get_class($this);
    $l = new $class(Templates_HTML::layouts_path_for($names[0]));
    foreach (array_slice($names, 1) as $n) {
      $l->inside(new self(Templates_HTML::layouts_path_for($n)));
    }
    $this->inside($l);
    return $this;
  }

  public function no_layout() {
      return $this->pull();
  }

  public function with_parameters(array $parameters = array()) {
      return $this->with($parameters);
  }

  protected function make_layout_paths($path) {
    for ($res = array(), $parts  = Core_Strings::split_by('/', (string) $path);
         count($parts) > 0;
         Core_Arrays::pop($parts)) $res[] = implode('/', $parts);
    return $res;
  }

    //---------------------------------


///   <method name="inside" returns="Templates.NestableTemplate">
///     <brief>Устанавливает внутри какого шаблона находиться данный шаблон</brief>
///     <args>
///       <arg name="container" type="Templates.Text.Template" brief="шаблон-контейнер" />
///     </args>
///     <body>
  public function inside(Templates_NestableTemplate $container) {
    parent::inside($container);
    $this->agregators = $container->agregators;
    return $this;
  }
///     </body>
///   </method>

  public function spawn($name) {
    $res = Templates_HTML::Template($name);
    $res->agregators = $this->agregators;
    $res->helpers = $this->helpers;
    $res->no_duplicates = $this->no_duplicates;
    $res->spawn_from = $this->root;
    return $res;
  }


///   <protocol name="performing">

///   <method name="begin" returns="Templates.Text.Template">
///     <brief>Начинает запись блока</brief>
///     <args>
///       <arg name="block" brief="имя блока" />
///     </args>
///     <body>
  public function begin($block) {
    ob_start();
    return $this;
  }
///     </body>
///   </method>

///   <method name="end" returns="Templates.Text.Template">
///     <brief>Заканчивает запись блока и сохраняет его</brief>
///     <args>
///       <arg name="block" brief="имя блока" />
///       <arg name="prepend" type="добавить или заменить текущий блок" />
///     </args>
///     <body>
  public function end($block, $append = true) {
    $value = ob_get_clean();
    return $this->content($block, $value, $append);
    // $this->content[$block] = ($append && isset($this->content[$block]) ? $this->content[$block] : '') . $value;
    // return $this;
  }
///     </body>
///   </method>

///   <method name="content" returns="Templates.HTML.Template">
///     <brief>Создает блок и заполняет его контентом</brief>
///     <args>
///       <arg name="name"    type="string" brief="имя блока" />
///       <arg name="content" type="string" brief="контент/содержимое блока" />
///       <arg name="prepend" type="boolean" default="true" brief="добавить в начало или в конец блока $name" />
///     </args>
///     <body>
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
///     </body>
///   </method>

///   <method name="prepend_to" returns="Templates.HTML.Template">
///     <brief>Добавить контент к концу блоку</brief>
///     <args>
///       <arg name="name"    type="string" brief="имя блока" />
///       <arg name="content" type="string" brief="контент" />
///     </args>
///     <body>
  public function prepend_to($name, $content) { return $this->content($name, $content, true); }
///     </body>
///   </method>

///   <method name="append_to" returns="Templates.HTML.Template">
///     <brief>Добавить контент к началу блоку</brief>
///     <args>
///       <arg name="name"    type="string" brief="имя блока" />
///       <arg name="content" type="string" brief="контент" />
///     </args>
///     <body>
  public function append_to($name, $content) { return $this->content($name, $content, false); }
///     </body>
///   </method>

///   <method name="if_empty" returns="Templates.HTML.Template">
///     <brief>Добавляет контент к блоку, если этот блок пустой</brief>
///     <args>
///       <arg name="block" type="string" brief="имя блока" />
///       <arg name="content" type="string" brief="контент" />
///     </args>
///     <body>
  public function if_empty($block, $content) {
    if (!isset($this->content[$block])) $this->contnet($block, $content);
    return $this;
  }
///     </body>
///   </method>

//TODO: deprecated use meta
//-----------------------
///   <method name="title" returns="Templates.HTML.Template">
///     <brief>Зополняет блок title, если он пустой</brief>
///     <args>
///       <arg name="content" type="string" brief="контент" />
///     </args>
///     <body>
  public function title($content) { return $this->if_empty('title', htmlspecialchars($content)); }
///     </body>
///   </method>

///   <method name="description" returns="Templates.HTML.Template">
///     <brief>Зополняет блок description, если он пустой</brief>
///     <args>
///       <arg name="content" type="string" />
///     </args>
///     <body>
  public function description($content) { return $this->if_empty('description', htmlspecialchars($content)); }
///     </body>
///   </method>
//-----------------------

  protected function partial_cache_key($name, $parms) {
     return $this->cache_key($name, $parms, 'partial');
  } 

///   <method name="partial" returns="string">
///     <body>
  public function partial_cache($name, $params = array(), $key = null, $timeout = null) {
    $key = $key ? $key : $this->partial_cache_key($name, $params);
    return $this->cached_call($key, array($this, 'partial'), array($name, $params));
  }
///     </body>
///   </method>

///   <method name="partial" returns="string">
///     <brief>Возвращает результат шаблона с именем $__name</brief>
///     <details>
///       Кроме имени шаблона могут переданны переменные/параметры, которые будут досутпны в шаблоне $__name
///     </details>
///     <args>
///       <arg name="name" type="string" brief="имя шаблона" />
///     </args>
///     <body>
  public function partial($__name, $__params = array()) {
    Events::call('templates.partial', $this, $_name, $__params);
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
///     </body>
///   </method>

///   <method name="compose" returns="string">
///     <brief>Компанует вместе несколько вызовов partial или/и строки</brief>
///     <details>
///       В метод может быть переданно любое количество параметров
///       Если параметр - массив, то этот массив подается на вход методу partial и результат комбинируется с другими вызовами или строками
///       Если параметр - строка, то она проста добавляется к результату
///     </details>
///     <body>
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
///     </body>
///   </method>

///   <method name="tag" returns="string">
///     <brief>Формирует html-таг</brief>
///     <args>
///       <arg name="name" type="string" brief="название" />
///       <arg name="attrs" type="array" default="array()" brief="массив атрибутов" />
///       <arg name="close" type="boolean" default="true" brief="закрывать или нет таг" />
///     </args>
///     <body>
  public function tag($name, array $attrs = array(), $close = true) {
    $tag = '<'.((string) $name);

    foreach ($attrs as $k => $v)
      $tag .= ($v === true ? " $k " : ( $v === false ? '' :  " $k=\"".htmlspecialchars((string) $v).'"'));

    return $tag .= (boolean) $close ? ' />' : '>';
  }
///     </body>
///   </method>

///   <method name="content_tag" returns="string">
///     <brief>Формирует таг с контеном</brief>
///     <args>
///       <arg name="name" type="string" brief="название" />
///       <arg name="content" type="string" brief="контетн" />
///       <arg name="attrs" type="array" default="array()" brief="массив атрибутов" />
///     </args>
///     <body>
  public function content_tag($name, $content, array $attrs = array()) {
    $tag = '<'.((string) $name);

    foreach ($attrs as $k => $v) {
      $tag .= ($v === true ? " $k " : ( $v === false ? '' :  " $k=\"".htmlspecialchars($v).'"'));
    }

    return $tag .= '>'.((string) $content).'</'.((string) $name.'>');
  }
///     </body>
///   </method>

  public function attributes($attrs) {
    $tag = '';
    foreach ($attrs as $k => $v) {
      $tag .= ($v === true ? " $k " : ( $v === false ? '' :  " $k=\"".htmlspecialchars($v).'"'));
    }
    return $tag;
  }

///   <method name="link_to" returns="string">
///     <brief>Формирует таг a</brief>
///     <args>
///       <arg name="url" type="string" brief="адрес ссылки" />
///       <arg name="content" type="string" brief="контент/содержимое тага" />
///       <arg name="attrs" type="array" default="array()" brief="массив атрибутов" />
///     </args>
///     <body>
  public function link_to($url, $content, array $attrs = array()) {
    return $this->content_tag('a', $content, array_merge($attrs, array('href' => $url)));
  }
///     </body>
///   </method>

///   <method name="mail_to" returns="string">
///     <brief>Формирует mailto ссылку</brief>
///     <args>
///       <arg name="address"  type="string" brief="адрес" />
///       <arg name="body" type="string" brief="содержимое/контент тага" />
///       <arg name="attributes" type="array" default="array()" brief="массив атрибутов" />
///     </args>
///     <body>
  public function mail_to($address, $body = '', array $attributes = array()) {
    return $this->link_to("mailto:$address", ($body ? $body : $address), $attributes);
  }
///     </body>
///   </method>

///   <method name="button_to" returns="string">
///     <brief>Формирует html-форму с кнопкой, отправляющей запрос по указанному адресу</brief>
///     <details>
///       Если массив атрибутов содержит 'confirm' параметр, тогда к кнопке будет добавлено оnclick событие, выводящее окно подтверждения действия.
///     </details>
///     <args>
///       <arg name="url"        type="string"/>
///       <arg name="text"       type="string" brief="Текст кнопки" />
///       <arg name="method"     type="string" default="'get'" brief="метод формы" />
///       <arg name="attributes" type="array"  default="array()" brief="массив атрибутов" />
///     </args>
///     <body>
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
///     </body>
///   </method>

///   <method name="form_button_to" returns="string">
///     <brief>Формирует кнопку, отправляющую запрос по указанному адресу</brief>
///     <args>
///       <arg name="url"        type="string" />
///       <arg name="text"       type="string" brief="текст кнопки" />
///       <arg name="method"     type="string" default="'get'" brief="метод отправки (get|put|post|delete)" />
///       <arg name="attributes" type="array"  default="array()" brief="массив атрибутов" />
///     </args>
///     <body>
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
///     </body>
///   </method>

///   <method name="image">
///     <brief>Формирует img таг</brief>
///     <args>
///       <arg name="url" type="string" brief="url к картинке" />
///       <arg name="attrs" type="array" default="array()" brief="массив атрибутов" />
///     </args>
///     <body>
  public function image($url, array $attrs = array()) {
    return $this->tag('img', array_merge($attrs, array('src' => $url)));
  }
///     </body>
///   </method>

///   <method name="js_link" returns="string">
///     <brief>Формирует таг script ссылающийся на указанный js-скрипт</brief>
///     <args>
///       <arg name="path" type="string" brief="url путь к js файлу" />
///       <arg name="options" type="array" />
///     </args>
///     <body>
  public function js_link($path, array $options = array()) {
    return $this->content_tag('script',  '',
        array_merge(array(
            'src' => $this->js_path($path)
          ),
          $options))."\n";
  }
///     </body>
///   </method>

///   <method name="css_link" returns="string">
///     <brief>Формирует link таг ссылающийся на указанный css-файл</brief>
///     <args>
///       <arg name="path" type="string" />
///       <arg name="options" type="array" />
///     </args>
///     <body>
  public function css_link($path, array $options = array()) {
    return $this->tag('link',
        array_merge(array(
            'rel' => 'stylesheet',
            'type' => 'text/css',
            'href' => $this->css_path($path) 
          ),
          $options))."\n";
  }
///     </body>
///   </method>

  protected function js_path($path) {
    return $this->add_timestamp(Templates_HTML::js_path($path));
  }
  
  protected function add_timestamp($link) {
    $ts = @filemtime('./' . ltrim($link, '/'));
    $is_joined = Core_Strings::starts_with($link, Templates_HTML_Includes::option('join_dir'));
    return Templates_HTML::option('add_timestamp') && !empty($ts) && !$is_joined ?
      sprintf(Templates_HTML::option('timestamp_pattern'), $link, $ts) :
      $link;
  }
  
  protected function css_path($path) {
    return $this->add_timestamp(Templates_HTML::css_path($path));
  }

///   <method name="js" returns="Templates.HTML.Template">
///     <body>
  public function js($string) {
    if (empty($string)) return '';
    return $this->content_tag('script', $string);
  }
///     </body>
///   </method>

///   <method name="css" returns="Templates.HTML.Template">
///     <body>
  public function css($string) {
    if (empty($string)) return '';
    return $this->content_tag('style', $string, array('type' => 'text/css'));
  }
///     </body>
///   </method>

///   </protocol>


///   <protocol name="accessing">

///   <method name="__get" returns="mixed">
///     <brief>Доступ на чтение к свойствам объекта</brief>
///     <args>
///       <arg name="property" type="string" brief="имя свойства" />
///     </args>
///     <body>
  public function __get($property) {
    switch (true) {
      case $property == 'meta':
        return Templates_HTML::meta();
      case $property == 'no_duplicates':
        if (!empty($this->$property)) return $this->$property;
        if (!$this->is_root && !empty($this->root->$property)) return $this->root->$property;
        if ($this->spawn_from) var_dump($this->spawn_from->name);
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
///     </body>
///   </method>

///   <method name="__set" returns="mixed">
///     <brief>Доступ на запись к свойствам объекта</brief>
///     <details>
///       Выбрасывает исключение, доступ только для чтения
///     </details>
///     <args>
///       <arg name="property" type="string" brief="имя свойства" />
///       <arg name="value" brief="значение" />
///     </args>
///     <body>
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
///     </body>
///   </method>

///   <method name="__isset" returns="boolean">
///     <brief>Проверяет установленно ли свойство объекта</brief>
///     <args>
///       <arg name="property" type="string" brief="имя свойства" />
///     </args>
///     <body>
  public function __isset($property) {
    switch (true) {
      case in_array($property, array('agregators', 'include_meta', 'meta', 'allow_filtering', 'no_duplicates')):
        return true;
      case Core_Strings::ends_with($property, '_agregator'):
        $names = explode('_', $property);
        return isset($this->agregators[$names[0]]);
      default:
        return parent::__set($property, $value);
    }
  }
///     </body>
///   </method>

///   <method name="__unset">
///     <brief>Очищает свойство объекта</brief>
///     <details>
///       Выбрасывает исключение, доступ только для чтения
///     </details>
///     <args>
///       <arg name="property" type="string" brief="имя свойства объекта" />
///     </args>
///     <body>
  public function __unset($property) {
    switch (true) {
      case in_array($property, array('agregators', 'include_meta', 'meta', 'allow_filtering')):
         throw new Core_UndestroyablePropertyException($property);
      case Core_Strings::ends_with($property, '_agregator'):
        $names = explode('_', $property);
        unset($this->agregators[$names[0]]);
        return $this;
      default:
        return parent::__set($property, $value);
    }
  }
///     </body>
///   </method>

///   </protocol>

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

///   <protocol name="indexing" interface="Core.IndexedPropertyAccessInterface">

///   <method name="offsetGet" returns="mixed">
///     <brief>Возвращает содержтмое блока с именем $index</brief>
///     <args>
///       <arg name="index" type="string" brief="имя блока" />
///     </args>
///     <body>
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
///     </body>
///   </method>

///   <method name="offsetSet" returns="mixed">
///     <brief>Устанавливает содержимое блока</brief>
///     <args>
///       <arg name="index" type="string" brief="имя блока" />
///       <arg name="value" brief="значение" />
///     </args>
///     <body>
  public function offsetSet($index, $value) {
    return $this->clear($index)->content($index, (string) $value);
  }
///     </body>
///   </method>

///   <method name="offsetExists" returns="boolean">
///     <brief>Проверяет существует ли блок</brief>
///     <args>
///       <arg name="index" type="string" brief="имя блока" />
///     </args>
///     <body>
  public function offsetExists($index) { return isset($this->content[$index]); }
///     </body>
///   </method>

///   <method name="offsetUnset">
///     <brief>Удаляет блок</brief>
///     <args>
///       <arg name="index" type="string" brief="имя блока" />
///     </args>
///     <body>
  public function offsetUnset($index) { unset($this->content[$index]); }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="supporting">

///   <method name="make_confirmation" returns="string">
///     <brief>Формирует js confirm вызов</brief>
///     <args>
///       <arg name="text" type="string" />
///     </args>
///     <body>
  protected function make_confirmation($text) {
    return $text ? "confirm('".Core_Strings::replace($text, "'", "\'")."')": 'true';
  }
///     </body>
///   </method>

///   <method name="get_partial_path" returns="string" access="protected">
///     <brief>Возвращает путь до partial шаблона</brief>
///     <args>
///       <arg name="name" type="string" brief="имя шаблона" />
///     </args>
///     <body>
  protected function get_partial_path($name) {
    if (Core_Strings::starts_with($name, '!')) {
      $trace = debug_backtrace();
      foreach ($trace as $k => $t)
        if (Core_Strings::ends_with($t['file'], $this->extension)) break;
      return Templates::add_extension(dirname($trace[$k]['file']) . '/' . substr($name, 1), $this->extension);
    }
    return Templates::is_absolute_path($name) ?
      Templates::add_extension($name, $this->extension) :
      preg_replace('{/[^/]+$}', '', $this->get_path()) . '/' . Templates::add_extension($name, $this->extension);
  }
///     </body>
///   </method>

///   <method name="get_helpers" returns="Object.Aggregator" access="protected">
///     <brief>Возвращает делегатор хелперов текущего шаблона</brief>
///     <body>
  protected function get_helpers() {
    return Core::if_null($this->helpers,
      $this->container ? $this->container->get_helpers() : Templates_HTML::helpers());
  }
///     </body>
///   </method>

///   <method name="load" access="protected" returns="Templates.HTML.Template">
///     <brief>Инклюдит указанный фаил, создавая необходимые переменные</brief>
///     <body>
  protected function load($__path) {
    extract($this->get_parms());
    if (IO_FS::exists($__path))
      include($__path);
    else
      throw new Templates_MissingTemplateException($__path);
    return $this;
  }
///     </body>
///   </method>

///   <method name="render_nested" returns="string">
///     <brief>Возвращает конечный результат</brief>
///     <args>
///       <arg name="content" type="ArrayObject" default="null" brief="контент, содержащий блоки" />
///     </args>
///     <body>
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
///     </body>
///   </method>

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
    $content = $this->filter_required($content);
    return $content;
  }
  
  protected function filter_required($content) {
    $name = 'html_required:' . $this->root->name;
    $filter = Text_Insertions::filter($name);
    if (!$filter->exists('head')) {
      $filter->register(array('head' => new Core_Call($this, 'build_head')));
    }
    return $filter->process($content);
  }
  
  protected function filter_custom($content) {
    return Text_Insertions::filter()->process($content);
  }

///   </protocol>
}
/// </class>

//TODO: Iterator
/// <class name="Templates.HTML.Metas">
///   <implements interface="Core.IndexedPropertyAccessInterface" />
///   <implements interface="Core.CallInterface" />
///   <implements interface="Core.StringifyInterface" />
class Templates_HTML_Metas implements Core_PropertyAccessInterface, 
             Core_CallInterface,
             Core_StringifyInterface {
  
  protected $title = '';
  protected $http  = array();
  protected $named = array();

///   <protocol name="calling" interface="Core.CallInterface">

///   <method name="__call" returns="WebKit.Metas.MetaTags">
///     <args>
///       <arg name="method" type="string" />
///       <arg name="args"   type="array"  />
///     </args>
///     <body>
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
///     </body>
///   </method>
  
///   </protocol>

///   <protocol name="accessing" interface="Core.PropertyAccessInterface">

///   <method name="__get" returns="string">
///     <args>
///       <arg name="property" type="string" />
///     </args>
///     <body>
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
///     </body>
///   </method>
  
///   <method name="__set" returns="string">
///     <args>
///       <arg name="property" type="string" />
///       <arg name="value"    type="string" />
///     </args>
///     <body>
  public function __set($property, $value) {
    switch ($property) {
      case 'title':
        return $this->title = $value;
      default:
        $this->$property($value);
        return $value;
    }
  }
///     </body>
///   </method>
  
///   <method name="__isset" returns="boolean">
///     <args>
///       <arg name="property" type="string" />
///       <arg name="value"    type="string" />
///     </args>
///     <body>
  public function __isset($property) {
    return $property == 'title' || 
           ($this->is_http_field($name) ?
             isset($this->http[$this->http_field_name($name)]) : 
             isset($this->named[$name]));   
  }
///     </body>
///   </method>
  
///   <method name="__unset">
///     <args>
///       <arg name="property" type="string" />
///       <arg name="value"    type="string" />
///     </args>
///     <body>
  public function __unset($property) {
    if ($property == 'title') throw new Core_UndestroyablePropertyException($property);
    
    if ($this->is_http_field($property))
      if (isset($this->http[$property = $this->http_field_name($property)])) unset($this->http[$property]);
    else
      if (isset($this->named[$property])) unset($this->named[$property]);  
  }
///     </body>
///   </method>

///   </protocol>
  
///   <protocol name="stringifyng" interface="Core.StringifyInterface">

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
  
///   <method name="as_string" returns="string">
///     <body>
  public function as_string() {
    if (isset($this->http['content_type'])) {
      $result = $this->http_to_string('content_type', $this->http['content_type']);
      unset($this->http['content_type']);
    }
    
    $result = sprintf("<title>%s</title>\n", htmlspecialchars($this->title));
    
    foreach ($this->http as $name => $content)
      $result .= $this->http_to_string($name, $content);
    
    foreach ($this->named as $name => $content) 
      $result .= $this->named_to_string($name, $content);
  
    return $result;
  }
///     </body>
///   </method>
  
///   <method name="__toString" returns="string">
///     <body>
  public function __toString() { return $this->as_string(); }
///     </body>
///   </method>
  
///   </protocol>
  
///   <protocol name="supporting">

///   <method name="is_http_field" returns="boolean" access="protected">
///     <args>
///       <arg name="name" type="string" />
///     </args>
///     <body>
  protected function is_http_field($name) { return Core_Strings::starts_with($name, 'http_'); }
///     </body>
///   </method>
  
///   <method name="http_field_name" returns="string" access="protected">
///     <args>
///       <arg name="name" type="string" />
///     </args>
///     <body>
  protected function http_field_name($name) {
    return Core_Strings::downcase(
      Core_Regexps::replace('{^http_}', '', $name));
  }
///     </body>
///   </method>

///   </protocol>
  
}
/// </class> 

/// </module>
