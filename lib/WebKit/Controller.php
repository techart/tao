<?php
/**
 * WebKit.Controller
 * 
 * @package WebKit\Controller
 * @version 1.5.1
 */

Core::load('Forms', 'Templates.HTML');

/**
 * @package WebKit\Controller
 */
class WebKit_Controller implements Core_ModuleInterface {

  const MODULE  = 'WebKit.Controller';
  const VERSION = '1.5.1';



/**
 * @return WebKit_Controller_Mapper
 */
  static public function Mapper() { return new WebKit_Controller_Mapper(); }

/**
 * @return WebKit_Controller_Dispatcher
 */
  static public function Dispatcher(WebKit_Controller_AbstractMapper $mapper) {
    return new WebKit_Controller_Dispatcher($mapper);
  }

/**
 * @return WebKit_Controller_Route
 */
  static public function Route() { return new WebKit_Controller_Route(); }

/**
 * @param WebKit_Controller_AbstractController $controller
 * @param string $name
 * @return WebKit_Controller_Form
 */
  static public function Form(WebKit_Controller_AbstractController $controller, $name) {
    return new WebKit_Controller_Form($controller, $name);
  }

}


/**
 * @package WebKit\Controller
 */
class WebKit_Controller_Exception extends Core_Exception {}


/**
 * @package WebKit\Controller
 */
class WebKit_Controller_NoRouteException extends WebKit_Controller_Exception {
  protected $url;


/**
 * @param string $url
 */
  public function __construct($url) {
    $this->url = (string) $url;
    parent::__construct("No route for url: $this->url");
  }

}


/**
 * @package WebKit\Controller
 */
class WebKit_Controller_BadControllerException extends WebKit_Controller_Exception {
  protected $module;
  protected $reason;


/**
 * @param string $module
 */
  public function __construct($module, $reason = '') {
    $this->module = (string) $module;
    $this->reason = (string) $reason;
    parent::__construct("Bad controller module: $this->module ($this->reason)");
  }

}


/**
 * @package WebKit\Controller
 */
class WebKit_Controller_Route
  implements Core_PropertyAccessInterface,
             Core_IndexedAccessInterface {

  protected $args  = array();
  protected $parms = array();


/**
 * @param string $property
 * @return mixed
 */
  public function __get($property) {
    if (isset($this->parms[$property]))
      return $this->parms[$property];
    else
      throw new Core_MissingPropertyException($property);
  }

/**
 * @param string $property
 * @param  $value
 * @return mixed
 */
  public function __set($property, $value) {
    if (isset($this->parms[$property]))
      return $this->parms[$property] = $value;
    else
      throw new Core_MissingPropertyException($property);
  }

/**
 * @param string $property
 * @return boolean
 */
  public function __isset($property) { return isset($this->parms[$property]); }

/**
 * @param string $property
 */
  public function __unset($property) {
    unset($this->parms[$property]);
  }



/**
 * @param  $index
 * @return mixed
 */
  public function offsetGet($index) {
    return is_numeric($index) ? $this->args[$index] : $this->parms[$index];
  }

/**
 * @param  $index
 * @return mixed
 */
  public function offsetSet($index, $value) {
    return $index === null ?
      ($this->args[] = $value) :
      (is_numeric($index) ? $this->args[$index] = $value : $this->parms[$index] = $value);
  }

/**
 * @param  $index
 * @return boolean
 */
  public function offsetExists($index) {
    return is_numeric($index) ? isset($this->args[$index]) : isset($this->parms[$index]);
  }

/**
 * @param  $index
 */
  public function offsetUnset($index) {
    if (is_numeric($index))
      unset($this->args[$index]);
    else
      unset($this->parms[$index]);
  }



/**
 * @param string $prefix
 * @return WebKit_Controller_Route
 */
  public function add_controller_prefix($prefix) {
    if (isset($this['controller']))
      $this['controller'] = $prefix.$this['controller'];
    return $this;
  }

/**
 * @param  $data
 * @return WebKit_Controller_Route
 */
  public function merge($data) {
    foreach ($data as $k => $v)  if (!isset($this[$k])) $this[$k] = $v;
    return $this;
  }



/**
 * @return array
 */
  public function as_array() {
    $result = $this->args;
    ksort($result);
    $result[] = $this->parms;
    return $result;
  }

}


/**
 * @abstract
 * @package WebKit\Controller
 */
abstract class WebKit_Controller_AbstractMapper
  implements Core_IndexedAccessInterface  {

  protected $defaults = array();
  protected $options  = array('path' => '', 'prefix' => '');

  protected $index_parameters = false;


/**
 */
  public function __construct() { $this->setup(); }

/**
 */
  protected function setup() { return $this; }



/**
 * @param array $options
 * @return WebKit_Controller_AbstractMapper
 */
  public function with_options(array $options) {
    Core_Arrays::update($this->options, $options);
    return $this;
  }

/**
 * @param array $options
 * @return WebKit_Controller_AbstractMapper
 */
  public function use_options(array $options) { return $this->with_options($options); }

/**
 * @param string $path
 * @return WebKit_Controller_AbstractMapper
 */
  public function with_path($path) {
    $this->options['path'] = (string) $path;
    return $this;
  }

/**
 * @param string $prefix
 * @return WebKit_Controller_AbstractMapper
 */
  public function with_prefix($prefix) {
    $this->options['prefix'] = (string) $prefix;
    return $this;
  }

/**
 * @param array $defaults
 * @return WebKit_Controller_AbstractMapper
 */
  public function with_defaults(array $defaults) {
    Core_Arrays::merge($this->defaults, $defaults);
    return $this;
  }

/**
 * @param array $defaults
 * @return WebKit_Controller_AbstractMapper
 */
  public function use_defaults(array $defaults) { return $this->with_defaults($defaults); }



/**
 * @param array $parameters
 * @return WebKit_Controller_AbstractMapper
 */
  public function map_index(array $parameters) {
    $this->index_parameters = $parameters;
    return $this;
  }



/**
 * @abstract
 * @param WebKit_HTTP_Request $request
 * @return WebKit_Controller_Route
 */
  abstract public function route($request);



/**
 * @return index_url
 */
  public function index_url() { return $this->options['path']; }

/**
 * @param string $url
 * @return boolean
 */
  public function is_match_for($url) {
    return $this->options['path'] == '' ?
      true :
      Core_Regexps::match('{^'.$this->options['path'].'}', $url);
  }

/**
 * @param string $url
 * @return boolean
 */
  public function is_not_match_for($url) {
    return !$this->is_match_for($url);
  }



/**
 * @param string $url
 * @return string
 */
  protected function add_path($url) { return $this->options['path'].$url; }

/**
 * @param string $url
 * @param  $args
 * @return string
 */
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

/**
 * @param string $url
 * @return string
 */
  protected function clean_url($url) {
    return Core_Regexps::replace('{^'.$this->options['path'].'}', '',
      Core_Regexps::replace('{\?.*$}', '', $url));
  }

/**
 * @param WebKit_HTTP_Request $request
 * @return WebKit_Controller_Route
 */
  protected function route_index($request) {
    return ($this->index_parameters && Core_Regexps::replace('{/$}', '', $this->clean_url($request->urn)) == $this->options['path']) ?
      WebKit_Controller::Route()->
        merge($this->index_parameters)->
        add_controller_prefix($this->options['prefix']) :
      null;
  }



/**
 * @param string $option
 * @return mixed
 */
  public function offsetGet($option) {
    if (isset($this->options[$option]))
      return $this->options[$option];
    else
      throw new Core_BadIndexException($option);
  }

/**
 * @param string $option
 * @param  $value
 * @return mixed
 */
  public function offsetSet($option, $value) {
    if (isset($this->options[$option]))
      return $this->options[$option] = $value;
    else
      throw new Core_MissingIndexedPropertyException($option);
  }

/**
 * @param string $option
 * @return boolean
 */
  public function offsetExists($option) { return isset($this->options[$option]); }

/**
 * @param string $option
 * @return mixed
 */
  public function offsetUnset($option) {
    throw new Core_UndestroyableIndexedPropertyException($option);
  }

}


/**
 * @package WebKit\Controller
 */
class WebKit_Controller_Mapper extends  WebKit_Controller_AbstractMapper {

  protected $map;


/**
 */
  public function __construct() {
    $this->map = new ArrayObject();
    parent::__construct();
  }



/**
 * @param WebKit_HTTP_Request $request
 * @return WebKit_Controller_Route
 */
  public function route($request) {
    if ($route = $this->route_index($request)) return $route;

    foreach ($this->map as $name => $mapper)
      if ($route = $mapper->route($request)) break;

    return $route ? $route->merge($this->defaults) : null;
  }



/**
 * @param string $name
 * @param WebKit_Controller_AbstractMapper $mapper
 * @return mixed
 */
  public function map($name, WebKit_Controller_AbstractMapper $mapper) {
    $this->map[$name] = $mapper->with_path($this->add_path($mapper['path']));
    return $this;
  }



/**
 * @param string $property
 * @return WebKit_Controller_AbstractMapper
 */
  public function __get($property) {
    if (isset($this->map[$property]))
      return $this->map[$property];
    else
      throw new Core_MissingPropertyException($property);
  }

/**
 * @param string $property
 * @param  $value
 * @return mixed
 */
  public function __set($property, WebKit_Controller_AbstractMapper $mapper) {
    return $this->map($property, $mapper);
  }

/**
 * @param string $property
 * @return boolean
 */
  public function __isset($property) { return isset($this->map[$property]); }

/**
 * @param string $property
 */
  public function __unset($property) {
    unset($this->map[$property]);
  }

}

/**
 * @abstract
 * @package WebKit\Controller
 */
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



/**
 * @param WebKit_Environment $env
 * @param WebKit_HTTP_Response $response
 */
  public function __construct(WS_Environment $env, $response) {
    $this->env      = $env;
    $this->request  = $env->request;
    $this->response = $response;

    $this->make_name_and_views_path();

    $this->setup();
  }

/**
 * @return WebKit_Controller_AbstractController
 */
  protected function setup() { return $this; }



/**
 * @param string|WebKit_Views_TemplateView $template
 * @param array $parms
 * @return WebKit_Views_TemplateView
 */
  protected function render($template, array $parms = array()) {
    return $this->render_within_layout($this->layout, $template, $parms);
  }

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


/**
 * @param string|WebKit_Views_TemplateView $template
 * @param array $parms
 * @return WebKit_Views_TemplateView
 */
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

/**
 * @param string $layout
 * @param string|WebKit_Views_TemplateView $template
 * @param array $parms
 * @return WebKit_Views_TemplateView
 */
  protected function render_within_layout($layout, $template, array $parms = array()) {
    $view = $this->
      render_view($template, $parms, $layout);
    if (count($this->scripts)) $view->use_scripts($this->scripts);

    return $view;
  }

  public function view_exists($template) {
    return Templates_HTML::Template($this->view_path_for($template))->exists();
  }
  
  public function view_path_for($template) {
    if (Templates::is_absolute_path($template)) return $template;
    foreach ((array) $this->views_path as $path) {
      $controller_path  = Templates::add_extension("$path/$template", '.phtml');
      if (IO_FS::exists($controller_path)) return $controller_path;
    }
    return $template;
  }

/**
 * @param string|WebKit_Views_TemplateView $template
 * @param array $parms
 * @return WebKit_Views_TemplateView
 */
  protected function render_view($template, array $parms = array(), $layout = '') {
    $defaults = array();
    foreach ($this->render_defaults as $attr) $defaults[$attr] = $this->$attr;
    $t = ($template instanceof Templates_Template) ?
      $template :
      Templates_HTML::Template($this->view_path_for($template));
    if (!empty($layout)) $t->within_layout($layout);
    $t->partial_paths($this->views_path, $template);
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

/**
 * @return boolean
 */
  protected function render_nothing() { return false; }



/**
 * @param string $views_path
 * @return WebKit_Controller_AbstractController
 */
  public function use_views_from($views_path) {
    if (!in_array($views_path, $this->views_path))
      array_unshift($this->views_path, (string) $views_path);
    return $this;
  }

/**
 * @param string $layout
 * @return WebKit_Controller_AbstractController
 */
  public function use_layout($layout) {
    $this->layout = (string) $layout;
    return $this;
  }

  public function no_layout() {
    $this->layout = false;
    return $this;
  }

/**
 * @return WebKit_Controller_AbstractController
 */
  public function use_scripts() {
    foreach ($args = func_get_args() as $script) $this->scripts[] = (string) $script;
    return $this;
  }

/**
 * @return WebKit_Controller_AbstractController
 */
  public function before_filter() {
    $args = func_get_args();
    return $this->register_filter('before', Core_Arrays::shift($args), $args);
  }

/**
 * @return WebKit_Controller_AbstractController
 */
  public function after_filter() {
    $args = func_get_args();
    return $this->register_filter('after', Core_Arrays::shift($args), $args);
  }

/**
 * @param WebKit_Controller_AbstractMapper $mapper
 * @return WebKit_Controller_AbstractController
 */
  public function use_urls_from($mapper) {
    $this->urls = $mapper;
    return $this;
  }

/**
 * @return WebKit_Controller_AbstractController
 */
  public function complete_urls_with() {
    $this->completion_parms = func_get_args();
    return $this;
  }


/**
 * @return WebKit_Controller_AbstractController
 */
  public function render_defaults() {
    foreach ($args = func_get_args() as $arg)
      $this->render_defaults[] = (string) $arg;
    return $this;
  }



/**
 * @param WebKit_Controller_Route $route
 */
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



/**
 * @param string $type
 * @param string $filter
 * @param array $actions
 * @return WebKit_Controller_AbstractController
 */
  protected function register_filter($type, $filter, array $actions = array()) {
    $this->filters[$type][(string) $filter] = $actions;
    return $this;
  }

/**
 * @return string
 */
  protected function make_name_and_views_path() {
    $parts = Core_Strings::split_by('_',
      Core_Strings::downcase(
        Core_Regexps::replace('{Controller$}', '', Core_Types::class_name_for($this))));

    array_shift($parts);

    $this->name       = Core_Arrays::join_with('.', $parts);
    //$this->views_path = Core_Arrays::join_with('/', $parts);
  }

/**
 * @param string $type
 * @param array $args
 */
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



/**
 * @param string $file
 */
	protected function download_file($file) {
		return Net_HTTP::Download($file);
	}

/**
 * @param string $location
 */
  protected function redirect_to($location) {
    return Net_HTTP::redirect_to($location);
  }

/**
 */
  protected function moved_permanently_to($location) {
    return Net_HTTP::moved_permanently_to($location);
  }

/**
 */
  protected function page_not_found() {
    return Net_HTTP::not_found()->
      body($this->view_exists((string) Net_HTTP::NOT_FOUND) ? $this->render((string) Net_HTTP::NOT_FOUND, array('env' => $this->env)) : null);
  }

/**
 */
  protected function not_implemented() {
    return Net_HTTP::Response(Net_HTTP::NOT_IMPLEMENTED);
  }



/**
 * @param string $property
 * @return mixed
 */
  public function __get($property) {
    if (property_exists($this,$property))
      return $this->$property;
    else
      throw new Core_MissingPropertyException($property);
  }

/**
 * @param string $property
 * @param  $value
 */
  public function __set($property, $value) {
    throw $this->__isset($property) ?
      new Core_ReadOnlyPropertyException($property) :
      new Core_MissingPropertyException($property);
  }

/**
 * @param string $property
 * @return boolean
 */
  public function __isset($property) { return property_exists($this, $property); }

/**
 * @param string $property
 */
  public function __unset($property) {
    throw $this->__isset($property) ?
      new Core_UndestroyablePropertyException($property) :
      new Core_MissingPropertyException($property);
  }



/**
 * @param string $method
 * @param array $args
 * @return mixed
 */
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

}


/**
 * @package WebKit\Controller
 */
class WebKit_Controller_Dispatcher
  implements Core_PropertyAccessInterface {

  protected $mapper;


/**
 * @param WebKit_Controller_AbstractMapper $mapper
 */
  public function __construct(WebKit_Controller_AbstractMapper $mapper) {
    $this->mapper = $mapper;
  }



/**
 * @param WebKit_Environment $env
 * @param WebKit_HTTP_Response $response
 * @return mixed
 */
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



/**
 * @param string $property
 * @return mixed
 */
  public function __get($property) {
    switch ($property) {
      case 'mapper':
        return $this->$property;
      default:
        throw new Core_MissingPropertyException($property);
    }
  }

/**
 * @param string $property
 * @param  $value
 * @return mixed
 */
  public function __set($property, $value) {
    throw $this->__isset($property) ?
      new Core_ReadOnlyPropertyException($property) :
      new Core_MissingPropertyException($property);
  }

/**
 * @param string $property
 * @return boolean
 */
  public function __isset($property) {
    switch ($property) {
      case 'mapper':
        return true;
      default:
        return false;
    }
  }

/**
 * @param string $property
 */
  public function __unset($property) {
    throw $this->__isset($property) ?
      new Core_UndestroyablePropertyException($property) :
      new Core_MissingPropertyException($property);
  }

}

/**
 * @package WebKit\Controller
 */
class WebKit_Controller_Form extends Forms_Form {
  protected $controller;


/**
 * @param WebKit_Controller_AbstractController $controller
 * @param string $name
 */
  public function __construct(WebKit_Controller_AbstractController $controller, $name) {
    $this->controller = $controller;
    parent::__construct($name);
  }

}

