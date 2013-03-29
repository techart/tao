<?php
/// <module name="WebKit.Controller" version="1.5.1" maintainer="timokhin@techart.ru">

Core::load('Forms', 'Templates.HTML');

/// <class name="WebKit.Controller" stereotype="module">
///   <implements interface="Core.ModuleInterface" />
///   <depends supplier="WebKit.Controller.Mapper" stereotype="creates" />
///   <depends supplier="WebKit.Controller.Dispatcher" stereotype="creates" />
///   <depends supplier="WebKit.Controller.Route" stereotype="creates" />
///   <depends supplier="WebKit.Controller.Form" stereotype="creates" />
class WebKit_Controller implements Core_ModuleInterface {

///   <constants>
  const MODULE  = 'WebKit.Controller';
  const VERSION = '1.5.1';
///   </constants>


///   <protocol name="creating">

///   <method name="Mapper" returns="WebKit.Controller.Mapper">
///     <body>
  static public function Mapper() { return new WebKit_Controller_Mapper(); }
///     </body>
///   </method>

///   <method name="Dispatcher" returns="WebKit.Controller.Dispatcher">
///     <body>
  static public function Dispatcher(WebKit_Controller_AbstractMapper $mapper) {
    return new WebKit_Controller_Dispatcher($mapper);
  }
///     </body>
///   </method>

///   <method name="Route" returns="WebKit.Controller.Route">
///     <body>
  static public function Route() { return new WebKit_Controller_Route(); }
///     </body>
///   </method>

///   <method name="Form" returns="WebKit.Controller.Form">
///     <args>
///       <arg name="controller" type="WebKit.Controller.AbstractController" />
///       <arg name="name"       type="string" />
///     </args>
///     <body>
  static public function Form(WebKit_Controller_AbstractController $controller, $name) {
    return new WebKit_Controller_Form($controller, $name);
  }
///     </body>
///   </method>

///   </protocol>
}
/// </class>


/// <class name="WebKit.Controller.Exception" extends="WebKit.Exception" stereotype="exception">
class WebKit_Controller_Exception extends Core_Exception {}
/// </class>


/// <class name="WebKit.Controller.NoRouteException" extends="WebKit.Controller.Exception" stereotype="exception">
class WebKit_Controller_NoRouteException extends WebKit_Controller_Exception {
  protected $url;

///   <protocol name="creating">

///   <method name="__construct">
///     <args>
///       <arg name="url" type="string" />
///     </args>
///     <body>
  public function __construct($url) {
    $this->url = (string) $url;
    parent::__construct("No route for url: $this->url");
  }
///     </body>
///   </method>

///   </protocol>
}
/// </class>


/// <class name="WebKit.Controller.BadControllerException" extends="WebKit.Controller.Exception" stereotype="exception">
class WebKit_Controller_BadControllerException extends WebKit_Controller_Exception {
  protected $module;
  protected $reason;

///   <protocol name="creating">

///   <method name="__construct">
///     <args>
///       <arg name="module" type="string" />
///     </args>
///     <body>
  public function __construct($module, $reason = '') {
    $this->module = (string) $module;
    $this->reason = (string) $reason;
    parent::__construct("Bad controller module: $this->module ($this->reason)");
  }
///     </body>
///   </method>

///   </protocol>
}
/// </class>


/// <class name="WebKit.Controller.Route">
///   <implements interface="Core.IndexedPropertyInterface" />
class WebKit_Controller_Route
  implements Core_PropertyAccessInterface,
             Core_IndexedAccessInterface {

  protected $args  = array();
  protected $parms = array();

///   <protocol name="accessing" interface="Core.PropertyAccessInterface">

///   <method name="__get" returns="mixed">
///     <args>
///       <arg name="property" type="string" />
///     </args>
///     <body>
  public function __get($property) {
    if (isset($this->parms[$property]))
      return $this->parms[$property];
    else
      throw new Core_MissingPropertyException($property);
  }
///     </body>
///   </method>

///   <method name="__set" returns="mixed">
///     <args>
///       <arg name="property" type="string" />
///       <arg name="value" />
///     </args>
///     <body>
  public function __set($property, $value) {
    if (isset($this->parms[$property]))
      return $this->parms[$property] = $value;
    else
      throw new Core_MissingPropertyException($property);
  }
///     </body>
///   </method>

///   <method name="__isset" returns="boolean">
///     <args>
///       <arg name="property" type="string" />
///     </args>
///     <body>
  public function __isset($property) { return isset($this->parms[$property]); }
///     </body>
///   </method>

///   <method name="__unset">
///     <args>
///       <arg name="property" type="string" />
///     </args>
///     <body>
  public function __unset($property) {
    unset($this->parms[$property]);
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="indexing" interface="Core.IndexedPropertyInterface">

///   <method name="offsetGet" returns="mixed">
///     <args>
///       <arg name="index" />
///     </args>
///     <body>
  public function offsetGet($index) {
    return is_numeric($index) ? $this->args[$index] : $this->parms[$index];
  }
///     </body>
///   </method>

///   <method name="offsetSet" returns="mixed">
///     <args>
///       <arg name="index" />
///     </args>
///     <body>
  public function offsetSet($index, $value) {
    return $index === null ?
      ($this->args[] = $value) :
      (is_numeric($index) ? $this->args[$index] = $value : $this->parms[$index] = $value);
  }
///     </body>
///   </method>

///   <method name="offsetExists" returns="boolean">
///     <args>
///       <arg name="index" />
///     </args>
///     <body>
  public function offsetExists($index) {
    return is_numeric($index) ? isset($this->args[$index]) : isset($this->parms[$index]);
  }
///     </body>
///   </method>

///   <method name="offsetUnset">
///     <args>
///       <arg name="index" />
///     </args>
///     <body>
  public function offsetUnset($index) {
    if (is_numeric($index))
      unset($this->args[$index]);
    else
      unset($this->parms[$index]);
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="changing">

///   <method name="add_controller_prefix" returns="WebKit.Controller.Route">
///     <args>
///       <arg name="prefix" type="string" />
///     </args>
///     <body>
  public function add_controller_prefix($prefix) {
    if (isset($this['controller']))
      $this['controller'] = $prefix.$this['controller'];
    return $this;
  }
///     </body>
///   </method>

///   <method name="merge" returns="WebKit.Controller.Route">
///     <args>
///       <arg name="data" />
///     </args>
///     <body>
  public function merge($data) {
    foreach ($data as $k => $v)  if (!isset($this[$k])) $this[$k] = $v;
    return $this;
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="converting">

///   <method name="as_array" returns="array">
///     <body>
  public function as_array() {
    $result = $this->args;
    ksort($result);
    $result[] = $this->parms;
    return $result;
  }
///     </body>
///   </method>

///   </protocol>
}
/// </class>


/// <class name="WebKit.Controller.AbstractMapper" stereotype="abstract">
///   <depends supplier="WebKit.HTTP.Request" stereotype="analyses" />
///   <depends supplier="WebKit.Controller.Route" stereotype="creates" />
///   <implements interface="Core.IndexedPropertyAccess" />
abstract class WebKit_Controller_AbstractMapper
  implements Core_IndexedAccessInterface  {

  protected $defaults = array();
  protected $options  = array('path' => '', 'prefix' => '');

  protected $index_parameters = false;

///   <protocol name="creating">

///   <method name="__construct">
///     <body>
  public function __construct() { $this->setup(); }
///     </body>
///   </method>

///   <method name="setup" access="protected">
///     <body>
  protected function setup() { return $this; }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="configuring">

///   <method name="with_options" returns="WebKit.Controller.AbstractMapper">
///     <args>
///       <arg name="options" type="array" />
///     </args>
///     <body>
  public function with_options(array $options) {
    Core_Arrays::update($this->options, $options);
    return $this;
  }
///     </body>
///   </method>

///   <method name="use_options" returns="WebKit.Controller.AbstractMapper">
///     <args>
///       <arg name="options" type="array" />
///     </args>
///     <body>
  public function use_options(array $options) { return $this->with_options($options); }
///     </body>
///   </method>

///   <method name="with_path" returns="WebKit.Controller.AbstractMapper">
///     <args>
///       <arg name="path" type="string" />
///     </args>
///     <body>
  public function with_path($path) {
    $this->options['path'] = (string) $path;
    return $this;
  }
///   </body>
///   </method>

///   <method name="with_prefix" returns="WebKit.Controller.AbstractMapper">
///     <args>
///       <arg name="prefix" type="string" />
///     </args>
///     <body>
  public function with_prefix($prefix) {
    $this->options['prefix'] = (string) $prefix;
    return $this;
  }
///     </body>
///   </method>

///   <method name="with_defaults" returns="WebKit.Controller.AbstractMapper">
///     <args>
///       <arg name="defaults" type="array" />
///     </args>
///     <body>
  public function with_defaults(array $defaults) {
    Core_Arrays::merge($this->defaults, $defaults);
    return $this;
  }
///     </body>
///   </method>

///   <method name="use_defaults" returns="WebKit.Controller.AbstractMapper">
///     <args>
///       <arg name="defaults" type="array" />
///     </args>
///     <body>
  public function use_defaults(array $defaults) { return $this->with_defaults($defaults); }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="mapping">

///   <method name="map_index" returns="WebKit.Controller.AbstractMapper">
///     <args>
///       <arg name="parameters" type="array" />
///     </args>
///     <body>
  public function map_index(array $parameters) {
    $this->index_parameters = $parameters;
    return $this;
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="routing">

///   <method name="route" returns="WebKit.Controller.Route" stereotype="abstract">
///     <args>
///       <arg name="request" type="WebKit.HTTP.Request" />
///     </args>
///     <body>
  abstract public function route($request);
///     </body>
///   </method>

///   </protocol>

///   <protocol name="quering">

///   <method name="index_url" returns="index_url">
///     <body>
  public function index_url() { return $this->options['path']; }
///     </body>
///   </method>

///   <method name="is_match_for" returns="boolean">
///     <args>
///       <arg name="url" type="string" />
///     </args>
///     <body>
  public function is_match_for($url) {
    return $this->options['path'] == '' ?
      true :
      Core_Regexps::match('{^'.$this->options['path'].'}', $url);
  }
///     </body>
///   </method>

///   <method name="is_not_match_for" returns="boolean">
///     <args>
///       <arg name="url" type="string" />
///     </args>
///     <body>
  public function is_not_match_for($url) {
    return !$this->is_match_for($url);
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="supporting">

///   <method name="add_path" returns="string" access="protected">
///     <args>
///       <arg name="url" type="string" />
///     </args>
///     <body>
  protected function add_path($url) { return $this->options['path'].$url; }
///     </body>
///   </method>

///   <method name="add_keyword_parameters" returns="string" access="protected">
///     <args>
///       <arg name="url" type="string" />
///       <arg name="args" />
///     </args>
///     <body>
  protected function add_keyword_parameters($url, $args) {
    if (!$args) return $url;

    $parms = array();

    foreach ($args as $k => $v) {
      if ($v === null)
        continue;
      else
        $parms[] = ($k[0] == '-') ?
          Core_Strings::substr($k, 1)."=$v" :
          "$k=".urlencode($v);
    }
    return $url.'?'.Core_Arrays::join_with('&', $parms);
  }
///     </body>
///   </method>

///   <method name="clean_url" returns="string" access="protected">
///     <args>
///       <arg name="url" type="string" />
///     </args>
///     <body>
  protected function clean_url($url) {
    return Core_Regexps::replace('{^'.$this->options['path'].'}', '',
      Core_Regexps::replace('{\?.*$}', '', $url));
  }
///     </body>
///   </method>

///   <method name="route_index" returns="WebKit.Controller.Route" access="protected">
///     <args>
///       <arg name="request" type="WebKit.HTTP.Request" />
///     </args>
///     <body>
  protected function route_index($request) {
    return ($this->index_parameters && Core_Regexps::replace('{/$}', '', $this->clean_url($request->urn)) == $this->options['path']) ?
      WebKit_Controller::Route()->
        merge($this->index_parameters)->
        add_controller_prefix($this->options['prefix']) :
      null;
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="indexing" interface="Core.IndexedAccessInterface">

///   <method name="offsetGet" returns="mixed">
///     <args>
///       <arg name="option" type="string" />
///     </args>
///     <body>
  public function offsetGet($option) {
    if (isset($this->options[$option]))
      return $this->options[$option];
    else
      throw new Core_BadIndexException($option);
  }
///     </body>
///   </method>

///   <method name="offsetSet" returns="mixed">
///     <args>
///       <arg name="option" type="string" />
///       <arg name="value" />
///     </args>
///     <body>
  public function offsetSet($option, $value) {
    if (isset($this->options[$option]))
      return $this->options[$option] = $value;
    else
      throw new Core_MissingIndexedPropertyException($option);
  }
///     </body>
///   </method>

///   <method name="offsetExists" returns="boolean">
///     <args>
///       <arg name="option" type="string" />
///     </args>
///     <body>
  public function offsetExists($option) { return isset($this->options[$option]); }
///     </body>
///   </method>

///   <method name="offsetUnset" returns="mixed">
///     <args>
///       <arg name="option" type="string" />
///     </args>
///     <body>
  public function offsetUnset($option) {
    throw new Core_UndestroyableIndexedPropertyException($option);
  }
///     </body>
///   </method>

///   </protocol>
}
/// </class>


/// <class name="WebKit.Controller.Mapper" extends="WebKit.Controller.AbstractMapper">
class WebKit_Controller_Mapper extends  WebKit_Controller_AbstractMapper {

  protected $map;

///   <protocol name="creating">

///   <method name="__construct">
///     <body>
  public function __construct() {
    $this->map = new ArrayObject();
    parent::__construct();
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="routing">

///   <method name="route" returns="WebKit.Controller.Route">
///     <args>
///       <arg name="request" type="WebKit.HTTP.Request" />
///     </args>
///     <body>
  public function route($request) {
    if ($route = $this->route_index($request)) return $route;

    foreach ($this->map as $name => $mapper)
      if ($route = $mapper->route($request)) break;

    return $route ? $route->merge($this->defaults) : null;
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="mapping">

///   <method name="map" returns="mixed">
///     <args>
///       <arg name="name" type="string" />
///       <arg name="mapper" type="WebKit.Controller.AbstractMapper" />
///     </args>
///     <body>
  public function map($name, WebKit_Controller_AbstractMapper $mapper) {
    $this->map[$name] = $mapper->with_path($this->add_path($mapper['path']));
    return $this;
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="accessing" interface="Core.PropertyAccessInterface">

///   <method name="__get" returns="WebKit.Controller.AbstractMapper">
///     <args>
///       <arg name="property" type="string" />
///     </args>
///     <body>
  public function __get($property) {
    if (isset($this->map[$property]))
      return $this->map[$property];
    else
      throw new Core_MissingPropertyException($property);
  }
///     </body>
///   </method>

///   <method name="__set" returns="mixed">
///     <args>
///       <arg name="property" type="string" />
///       <arg name="value" />
///     </args>
///     <body>
  public function __set($property, WebKit_Controller_AbstractMapper $mapper) {
    return $this->map($property, $mapper);
  }
///     </body>
///   </method>

///   <method name="__isset" returns="boolean">
///     <args>
///       <arg name="property" type="string" />
///     </args>
///     <body>
  public function __isset($property) { return isset($this->map[$property]); }
///     </body>
///   </method>

///   <method name="__unset">
///     <args>
///       <arg name="property" type="string" />
///     </args>
///     <body>
  public function __unset($property) {
    unset($this->map[$property]);
  }
///     </body>
///   </method>

///   </protocol>
}
/// </class>
/// <aggregation>
///   <source class="WebKit.Controller.Mapper" role="parent" multiplicity="1" />
///   <target class="WebKit.Controller.AbstractMapper" role="child" multiplicity="N" />
/// </aggregation>

/// <class name="WebKit.Controller.AbstractController" stereotype="abstract">
///   <implements interface="Core.PropertyAccessInterface" />
///   <implements interface="Core.CallInterface" />
abstract class WebKit_Controller_AbstractController
  implements Core_PropertyAccessInterface, Core_CallInterface {

  protected $name;
  protected $env;
  protected $request;
  protected $response;
  protected $urls;
  protected $action = false;

  private $views_path = array();
  private $layout  = false;
  private $scripts = array();
  private $completion_parms = array();
  private $render_defaults = array();
  private $filters = array(
    'before' => array(),
    'after'  => array());


///   <protocol name="creating">

///   <method name="__construct">
///     <args>
///       <arg name="env"     type="WebKit.Environment" />
///       <arg name="response" type="WebKit.HTTP.Response" />
///     </args>
///     <body>
  public function __construct(WS_Environment $env, $response) {
    $this->env      = $env;
    $this->request  = $env->request;
    $this->response = $response;

    $this->make_name_and_views_path();

    $this->setup();
  }
///     </body>
///   </method>

///   <method name="setup" returns="WebKit.Controller.AbstractController" access="protected">
///     <body>
  protected function setup() { return $this; }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="rendering">

///   <method name="render" returns="WebKit.Views.TemplateView" access="protected">
///     <args>
///       <arg name="template" type="string|WebKit.Views.TemplateView" />
///       <arg name="parms"    type="array" default="array()" />
///     </args>
///     <body>
  protected function render($template, array $parms = array()) {
    return $this->render_within_layout($this->layout, $template, $parms);
  }
///     </body>
///   </method>

  protected function init_pdf() {
    if (!class_exists('mPDF')) {
      if (isset($this->env->config->pdf) && !empty($this->env->config->pdf->mpdf_dir)) {
        include($this->env->config->pdf->mpdf_dir);
      }
    }
    $mpdf = new mPDF();
    if (isset($this->env->config->pdf->options))
      foreach ($this->env->config->pdf->options as $name => $v)
        $mpdf->$name = $v;
    return $mpdf;
  }


///   <method name="render_pdf" returns="WebKit.Views.TemplateView" access="protected">
///     <args>
///       <arg name="template" type="string|WebKit.Views.TemplateView" />
///       <arg name="parms"    type="array" default="array()" />
///     </args>
///     <body>
  protected function render_pdf($template, array $parms = array(), $file = null, $save = false) {
    $t = $this->render_within_layout($this->layout, $template, $parms);
    $mpdf = $this->init_pdf();
    $mpdf->WriteHTML((string) $t);
    if ($save) {
      if (!IO_FS::exists($file))
        $mpdf->Output($file, 'F');
      return Net_HTTP::Download($file);
    }
    else {
      $file_name = basename($file);
      $mpdf->Output($file_name ? $file_name : 'output.pdf', 'D');
      return;
    }
  }
///     </body>
///   </method>

///   <method name="render_within_layout" returns="WebKit.Views.TemplateView" access="protected">
///     <args>
///       <arg name="layout"   type="string" />
///       <arg name="template" type="string|WebKit.Views.TemplateView" />
///       <arg name="parms"    type="array" default="array()" />
///     </args>
///     <body>
  protected function render_within_layout($layout, $template, array $parms = array()) {
    $view = $this->
      render_view($template, $parms, $layout);
    if (count($this->scripts)) $view->use_scripts($this->scripts);

    return $view;
  }
///     </body>
///   </method>

  public function view_exists($template) {
    return Templates_HTML::Template($this->view_path_for($template))->exists();
  }
  
  protected function view_path_for($template) {
    if (Templates::is_absolute_path($template)) return $template;
    foreach ((array) $this->views_path as $path) {
      $controller_path  = Templates::add_extension("$path/$template", '.phtml');
      if (IO_FS::exists($controller_path)) return $controller_path;
    }
    return $template;
  }

///   <method name="render_view" returns="WebKit.Views.TemplateView" access="protected">
///     <args>
///       <arg name="template" type="string|WebKit.Views.TemplateView" />
///       <arg name="parms"    type="array" default="array()" />
///     </args>
///     <body>
  protected function render_view($template, array $parms = array(), $layout = '') {
    $defaults = array();
    foreach ($this->render_defaults as $attr) $defaults[$attr] = $this->$attr;
    $t = ($template instanceof Templates_Template) ?
      $template :
      Templates_HTML::Template($this->view_path_for($template));
    if (!empty($layout)) $t->within_layout($layout);
    $t->root->
      with(array(
        'c'           => $this,
        'controller'  => $this,
        'e'           => $this->env,
        'env'         => $this->env,
        'environment' => $this->env,
        'request'     => $this->request,
        'r'           => $this->response,
        'response'    => $this->response))->
      with($defaults)->
      with($parms);
    return $t;
  }
///     </body>
///   </method>

///   <method name="render_nothing" returns="boolean" access="protected">
///     <body>
  protected function render_nothing() { return false; }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="configuring">

///   <method name="use_views_from" returns="WebKit.Controller.AbstractController">
///     <args>
///       <arg name="views_path" type="string" />
///     </args>
///     <body>
  public function use_views_from($views_path) {
    if (!in_array($views_path, $this->views_path))
      array_unshift($this->views_path, (string) $views_path);
    return $this;
  }
///     </body>
///   </method>

///   <method name="use_layout" returns="WebKit.Controller.AbstractController">
///     <args>
///       <arg name="layout" type="string" />
///     </args>
///     <body>
  public function use_layout($layout) {
    $this->layout = (string) $layout;
    return $this;
  }
///     </body>
///   </method>

  public function no_layout() {
    $this->layout = false;
    return $this;
  }

///   <method name="use_scripts" returns="WebKit.Controller.AbstractController">
///     <body>
  public function use_scripts() {
    foreach ($args = func_get_args() as $script) $this->scripts[] = (string) $script;
    return $this;
  }
///     </body>
///   </method>

///   <method name="before_filter" returns="WebKit.Controller.AbstractController" varargs="true">
///     <body>
  public function before_filter() {
    $args = func_get_args();
    return $this->register_filter('before', Core_Arrays::shift($args), $args);
  }
///     </body>
///   </method>

///   <method name="after_filter" returns="WebKit.Controller.AbstractController" varargs="true">
///     <body>
  public function after_filter() {
    $args = func_get_args();
    return $this->register_filter('after', Core_Arrays::shift($args), $args);
  }
///     </body>
///   </method>

///   <method name="use_urls_from" returns="WebKit.Controller.AbstractController">
///     <args>
///       <arg name="mapper" type="WebKit.Controller.AbstractMapper" />
///     </args>
///     <body>
  public function use_urls_from($mapper) {
    $this->urls = $mapper;
    return $this;
  }
///     </body>
///   </method>

///   <method name="complete_urls_with" returns="WebKit.Controller.AbstractController" varargs="true">
///     <body>
  public function complete_urls_with() {
    $this->completion_parms = func_get_args();
    return $this;
  }
///     </body>
///   </method>


///   <method name="render_defaults" returns="WebKit.Controller.AbstractController" varargs="true">
///     <body>
  public function render_defaults() {
    foreach ($args = func_get_args() as $arg)
      $this->render_defaults[] = (string) $arg;
    return $this;
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="performing">

///   <method name="dispatch">
///     <args>
///       <arg name="route" type="WebKit.Controller.Route" />
///     </args>
///     <body>
  public function dispatch(WebKit_Controller_Route $route) {
    $this->action = $route['action'];
    $res = $this->run_filters('before', $route);
    if (!empty($res)) return $res;
    $result = Core_Types::reflection_for($this)->
       getMethod($route['action'])->
       invokeArgs($this, $route->as_array());
    if ($result === null) $result = $this->render($route['action']);
    $this->run_filters('after', $result);
    return $result;
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="supporting">

///   <method name="register_filter" returns="WebKit.Controller.AbstractController">
///     <args>
///       <arg name="type" type="string" />
///       <arg name="filter" type="string" />
///       <arg name="actions" type="array" default="array()" />
///     </args>
///     <body>
  protected function register_filter($type, $filter, array $actions = array()) {
    $this->filters[$type][(string) $filter] = $actions;
    return $this;
  }
///     </body>
///   </method>

///   <method name="make_name_and_views_path" returns="string" access="protected">
///     <body>
  protected function make_name_and_views_path() {
    $parts = Core_Strings::split_by('_',
      Core_Strings::downcase(
        Core_Regexps::replace('{Controller$}', '', Core_Types::class_name_for($this))));

    array_shift($parts);

    $this->name       = Core_Arrays::join_with('.', $parts);
    //$this->views_path = Core_Arrays::join_with('/', $parts);
  }
///     </body>
///   </method>

///   <method name="run_filters" access="protected">
///     <args>
///       <arg name="type" type="string" />
///       <arg name="args" type="array" />
///     </args>
///     <body>
  public function run_filters($type, $args) {
    foreach ($this->filters[$type] as $filter => $rules) {
      $run_filter = true;

      foreach ($rules as $rule) {
        switch ($rule) {
          case '+all':
            $run_filter = true;
            break;
          case '-all':
            $run_filter = false;
            break;
          default:
            if ($rule == "-$this->action")
              $run_filter = false;
            elseif ($rule == "+$this->action")
              $run_filter = true;
        }
      }
      if ($run_filter) {
        $res = $this->$filter($args);
        if (is_object($res)) return $res;
      }
    }
    return null;
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="navigating">

///   <method name="download_file">
///     <args>
///       <arg name="file" type="string" />
///     </args>
///     <body>
	protected function download_file($file) {
		return Net_HTTP::Download($file);
	}
///     </body>
///   </method>

///   <method name="redirect_to" access="protected">
///     <args>
///       <arg name="location" type="string" />
///     </args>
///     <body>
  protected function redirect_to($location) {
    return Net_HTTP::redirect_to($location);
  }
///     </body>
///   </method>

///   <method name="moved_permanently_to" access="protected">
///     <body>
  protected function moved_permanently_to($location) {
    return Net_HTTP::moved_permanently_to($location);
  }
///     </body>
///   </method>

///   <method name="page_not_found" access="protected">
///     <body>
  protected function page_not_found() {
    return Net_HTTP::not_found()->
      body($this->view_exists((string) Net_HTTP::NOT_FOUND) ? $this->render((string) Net_HTTP::NOT_FOUND, array('env' => $this->env)) : null);
  }
///     </body>
///   </method>

///   <method name="not_implemented" access="protected">
///     <body>
  protected function not_implemented() {
    return Net_HTTP::Response(Net_HTTP::NOT_IMPLEMENTED);
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="accessing" interface="Core.PropertyAccessInterface">

///   <method name="__get" returns="mixed">
///     <args>
///       <arg name="property" type="string" />
///     </args>
///     <body>
  public function __get($property) {
    if (property_exists($this,$property))
      return $this->$property;
    else
      throw new Core_MissingPropertyException($property);
  }
///     </body>
///   </method>

///   <method name="__set">
///     <args>
///       <arg name="property" type="string" />
///       <arg name="value" />
///     </args>
///     <body>
  public function __set($property, $value) {
    throw $this->__isset($property) ?
      new Core_ReadOnlyPropertyException($property) :
      new Core_MissingPropertyException($property);
  }
///     </body>
///   </method>

///   <method name="__isset" returns="boolean">
///     <args>
///       <arg name="property" type="string" />
///     </args>
///     <body>
  public function __isset($property) { return property_exists($this, $property); }
///     </body>
///   </method>

///   <method name="__unset">
///     <args>
///       <arg name="property" type="string" />
///     </args>
///     <body>
  public function __unset($property) {
    throw $this->__isset($property) ?
      new Core_UndestroyablePropertyException($property) :
      new Core_MissingPropertyException($property);
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="calling" interface="Core.CallInterface">

///   <method name="__call" returns="mixed" >
///     <args>
///       <arg name="method" type="string" />
///       <arg name="args" type="array" />
///     </args>
///     <body>
  public function __call($method, $args) {
    if (Core_Strings::ends_with($method, '_url'))  {

      $redirect = false;

      // TODO: Ugly hack, needs refactoring
      if (Core_Strings::starts_with($method, 'redirect_to_')) {
        $redirect = true;
        $method   = Core_Strings::substr($method, 12);
      }

      $parms = array();

      foreach ($this->completion_parms as $name)
        if (isset($this->request[$name])) $parms[$name] = $this->request[$name];

      if (count($args) && is_array($args[count($args) - 1])) {
        if (count($parms)) $args[count($args) - 1] = Core_Arrays::merge($parms, $args[count($args) - 1]);
      } else {
        if (count($parms)) $args[] = $parms;
      }

      $url = method_exists($this->urls, $method) ?
        Core_Types::reflection_for($this->urls)->getMethod($method)->invokeArgs($this->urls, $args) :
        $this->urls->__call($method, $args);

      return $redirect ? $this->redirect_to($url) : $url;
    } else
      throw new Core_MissingMethodException($method);
  }
///     </body>
///   </method>

///   </protocol>
}
/// </class>


/// <class name="WebKit.Controller.Dispatcher">
///   <implements interface="Core.PropertyAccessInterface" />
///   <depends supplier="WebKit.Environment" stereotype="reads" />
///   <depends supplier="WebKit.HTTP.Response" stereotype="fills" />
///   <depends supplier="WebKit.Controller.BadControllerException" stereotype="throws" />
///   <depends supplier="WebKit.Controller.NoRouteException" stereotype="throws" />
///   <depends supplier="WebKit.Controller.AbstractController" stereotype="runs" />
class WebKit_Controller_Dispatcher
  implements Core_PropertyAccessInterface {

  protected $mapper;

///   <protocol name="creating">

///   <method name="__construct">
///     <args>
///       <arg name="mapper" type="WebKit.Controller.AbstractMapper" />
///     </args>
///     <body>
  public function __construct(WebKit_Controller_AbstractMapper $mapper) {
    $this->mapper = $mapper;
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="performing">

///   <method name="dispatch" returns="mixed">
///     <args>
///       <arg name="env"      type="WebKit.Environment" />
///       <arg name="response" type="WebKit.HTTP.Response" />
///     </args>
///     <body>
  public function dispatch(WS_Environment $env, $response) {
    if ($route = $this->mapper->route($env->request)) {
      try {
        Core::load($module = $route['controller']);
        return Core_Types::reflection_for($module)->
          newInstance($env, $response)->
            dispatch($route);

      } catch (Core_ModuleException $e) {
        throw new WebKit_Controller_BadControllerException($module, $e->getMessage());
      } catch (ReflectionException $e) {
        throw new WebKit_Controller_BadControllerException($module, $e->getMessage());
      }
    } else
      return Net_HTTP::not_found();
      //throw new WebKit_Controller_NoRouteException($env->request->urn);
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="accessing" interface="Core.PropertyAccessInterface">

///   <method name="__get" returns="mixed">
///     <args>
///       <arg name="property" type="string" />
///     </args>
///     <body>
  public function __get($property) {
    switch ($property) {
      case 'mapper':
        return $this->$property;
      default:
        throw new Core_MissingPropertyException($property);
    }
  }
///     </body>
///   </method>

///   <method name="__set" returns="mixed">
///     <args>
///       <arg name="property" type="string" />
///       <arg name="value" />
///     </args>
///     <body>
  public function __set($property, $value) {
    throw $this->__isset($property) ?
      new Core_ReadOnlyPropertyException($property) :
      new Core_MissingPropertyException($property);
  }
///     </body>
///   </method>

///   <method name="__isset" returns="boolean">
///     <args>
///       <arg name="property" type="string" />
///     </args>
///     <body>
  public function __isset($property) {
    switch ($property) {
      case 'mapper':
        return true;
      default:
        return false;
    }
  }
///     </body>
///   </method>

///   <method name="__unset">
///     <args>
///       <arg name="property" type="string" />
///     </args>
///     <body>
  public function __unset($property) {
    throw $this->__isset($property) ?
      new Core_UndestroyablePropertyException($property) :
      new Core_MissingPropertyException($property);
  }
///     </body>
///   </method>

///   </protocol>
}
/// </class>
/// <aggregation>
///   <source class="WebKit.Controller.Dispatcher" role="dispatcher" multiplicity="1" />
///   <target class="WebKit.Controller.AbstractMapper" role="mapper" multiplicity="1" />
/// </aggregation>

/// <class name="WebKit.Controller.Form" extends="WebKit.Forms.Form">
class WebKit_Controller_Form extends Forms_Form {
  protected $controller;

///   <protocol name="creating">

///   <method name="__construct">
///     <args>
///       <arg name="controller" type="WebKit.Controller.AbstractController" />
///       <arg name="name"       type="string" />
///     </args>
///     <body>
  public function __construct(WebKit_Controller_AbstractController $controller, $name) {
    $this->controller = $controller;
    parent::__construct($name);
  }
///     </body>
///   </method>

///   </protocol>
}
/// </class>
/// <aggregation name="belongs">
///   <source class="WebKit.Controller.Form" role="form" multiplicity="1" />
///   <target class="WebKit.Controller.AbstractController" role="controller" multiplicity="1" />
/// </aggregation>

/// </module>
