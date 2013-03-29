<?php
/// <module name="WS.REST" version="0.2.0" maintainer="timokhin@techart.ru">
Core::load('WS', 'WS.Services.REST.URI');


//TODO: Events + CMS integration -- current_controller, current_mapper.


/// <class name="WS.REST" stereotype="module">
///   <implements interface="Core.ModuleInterface" />
///   <depends supplier="WS.REST.Application" stereotype="creates" />
///   <depends supplier="WS.REST.Resource" stereotype="creates" />
///   <depends supplier="WS.REST.Method" stereotype="creates" />
class WS_Services_REST implements Core_ModuleInterface {

///   <constants>
  const VERSION = '0.2.2';
///   </constants>

///   <protocol name="building">

///   <method name="Dispatcher" scope="class" returns="WS.REST.Dispatcher">
///     <args>
///       <arg name="mappings" type="array" />
///       <arg name="default" type="string" default="''" />
///     </args>
///     <body>
  public static function Dispatcher($apptication = null, array $mappings = array(), $default = '') { return new WS_Services_REST_Dispatcher($application, $mappings, $default); }
///     </body>
///   </method>

///   <method name="Application" scope="class" returns="WS.REST.Application">
///     <body>
  public static function Application() { return new WS_Services_REST_Application(); }
///     </body>
///   </method>

///   <method name="Resource" scope="class" returns="WS.REST.Resource">
///     <args>
///       <arg name="classname" type="string" />
///       <arg name="path" type="string" default="''" />
///     </args>
///     <body>
  public static function Resource($classname, $path = '') {
    return new WS_Services_REST_Resource($classname, $path);
  }
///     </body>
///   </method>

///   <method name="Method" scope="class" returns="WS.REST.Method">
///     <args>
///       <arg name="name" type="string" />
///     </args>
///     <body>
  public static function Method($name) { return new WS_Services_REST_Method($name); }
///     </body>
///   </method>

///   </protocol>
}
/// </class>

/// <class name="WS.REST.Exception" extends="WS.Exception">
class WS_Services_REST_Exception extends WS_Exception {}
/// </class>

/// <class name="WS.REST.Dispatcher">
///   <implements interface="WS.ServiceInterface" />
class WS_Services_REST_Dispatcher extends WS_MiddlewareService /* implements WS_ServiceInterface */ {

  protected $mappings = array();
  protected $default  = '';
  protected $env;

///   <protocol name="creating">

///   <method name="__constructor">
///     <args>
///       <arg name="mappings" type="array" />
///       <arg name="default" type="string" default="''" />
///     </args>
///     <body>
  public function __construct($application = null, array $mappings = array(), $default = null) {
    parent::__construct($application);
    $this->mappings($mappings);
    if ($default)
      $this->map('default', $default);
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="configuring">

///   <method name="mappings" returns="WS.REST.Dispatcher">
///     <args>
///       <arg name="mappings" type="array" />
///     </args>
///     <body>
  public function mappings(array $mappings) {
    foreach ($mappings as $k => $v) $this->map($k, $v);
    return $this;
  }
///     </body>
///   </method>

///   <method name="map" returns="WS.REST.Dispatcher">
///     <args>
///       <arg name="path" type="string" />
///       <arg name="classname" type="string" />
///     </args>
///     <body>
  public function map($name, $app) {
    if (is_string($app)) $app = array('class' => $app);
    if (!isset($app['prefix'])) $app['prefix'] = $name;
    if (!isset($app['instance'])) $app['instance'] = null;
    if (!isset($app['param'])) $app['param'] = array();
    $this->mappings[$name] = $app;
    return $this;
  }
///     </body>
///   </method>

  public function get_app($name, $parms = array()) {
    if (isset($this->mappings[$name]['instance']))
      return $this->mappings[$name]['instance'];
    return $this->mappings[$name]['instance'] = $this->load_application($name, $parms);
  }
  
  public function get_map($name) {
    return $this->mappings[$name];
  }
  
  public function update_map($name, array $app = array()) {
    $this->mappings[$name] = array_merge($this->mappings[$name], $app);
    return $this;
  }
  

///   </protocol>

///   <protocol name="performing">

///   <method name="run" returns="mixed">
///     <args>
///       <arg name="env" type="WS.Environment" />
///     </args>
///     <body>
  public function run(WS_Environment $env) {
    $this->env = $env;
    list($prefix, $app_name) = array('', 'default');
    
    $request = $env->request;
    $env = $env->spawn();
    
    foreach ($this->mappings as $k => $v) {
      $pp = $v['prefix'];
      if (($pp == '/' && preg_match('{^(/(?:index.[a-zA-Z0-9]+)?$)}', $env->request->path, $m) ||
          (preg_match("{^/$pp(/.*)}", $env->request->path, $m)))) {
        $request = clone $request;//FIXME
        $request->path(end($m));
        $env->request($request);
        list($prefix, $app_name, $app_parms) = array($pp, $k, array('match' => $m));
        break;
      }
      if ($pp == '') {
        $app = $this->get_app($k, $app_parms);
        if ($app->find($env)) break;
        $app = null;
      }
    }
    
    $app = $app ? $app : $this->get_app($app_name, $app_parms);
    return $this->create_response($app, $env);
  }
///     </body>
///   </method>

  protected function create_response($app, $env) {
      if ($app) {
        $response = $app->run($env);
        if ($response->status->code == Net_HTTP::NOT_FOUND && $this->application instanceof WS_ServiceInterface)
          return $this->application->run(WS::env());
        return $response;
      } else {
        if ($this->application instanceof WS_ServiceInterface)
          return $this->application->run($env);
        return Net_HTTP::Response(Net_HTTP::NOT_FOUND);
      }

    //   if ($app) var_dump($app->run($env));
    // return $app ?
    //   $app->run($env) :
    //   ($this->application instanceof WS_ServiceInterface ? $this->application->run($env) : Net_HTTP::Response(Net_HTTP::NOT_FOUND));
  }

///   </protocol>

///   <method name="setup_env" returns="WS.Environment" access="protected">
///     <args>
///       <arg name="env" type="WS.Environment" />
///       <arg name="prefix" type="string" />
///     </args>
///     <body>
  protected function setup_env(WS_Environment $env) { return $env->app($this); }
///     </body>
///   </method>


///   <protocol name="supporting">

///   <method name="load_application" returns="WS.REST.Application" access="protected">
///     <args>
///       <arg name="app" type="string|array" />
///     </args>
///     <body>
  protected function load_application($name, $parms = array()) {
    $app = $this->mappings[$name];
    if (empty($app)) return null;
    $class_name = $app['class'];
    $instance = Core::amake($class_name, array($app['prefix'], array_merge($parms, $app['param'])));
    $instance->name = $name;

     if ($instance instanceof WS_Services_REST_Application)
       return $instance;
     else
       throw new WS_Services_REST_Exception('Incompatible application class: '.Core_Types::virtual_class_name_for($class_name));
  }
///     </body>
///   </method>

///   </protocol>
}
/// </class>


/// <class name="WS.REST.Application">
///   <implements interface="WS.ServiceInterface" />
///   <implements interface="Core.PropertyAccessInterface" />
///   <depends supplier="WS.REST.URI.MatchResults" stereotype="uses" />
class WS_Services_REST_Application
  implements WS_ServiceInterface, Core_PropertyAccessInterface {

  const LOOKUP_LIMIT = 20;
  const DEFAULT_CONTENT_TYPE = 'text/html';

  protected $resources = array();
  protected $classes   = array();
  
  protected $format = null;
  

  protected $media_types = array(
    'html' => 'text/html',
    'js'   => 'text/javascript',
    'json' => 'application/json',
    'xml'  => 'application/xml',
    'rss'  => 'application/xhtml+xml');

  protected $default_format = 'html';

  protected $prefix = '';

  protected $options = array();
  protected $is_match = false;
  protected $match;
  protected $target_resource;
  protected $target_method;
  protected $target_instance;

  protected $callback_result;
  
  protected $name;

///   <protocol name="creating">

///   <method name="__construct">
///     <args>
///       <arg name="options" type="array" default="array()" />
///     </args>
///     <body>
  public function __construct($prefix = '', array $options = array()) {
    $this->prefix = $prefix;
    $this->options = $this->default_options();
    $this->setup($options);
  }
///     </body>
///   </method>

///   <method name="default_options" returns="array" access="protected">
///     <body>
  protected function default_options() { return array(); }
///     </body>
///   </method>

///   <method name="setup" access="protected">
///     <args>
///       <arg name="options" type="array" default="array()" />
///     </args>
///     <body>
  protected function setup(array $options = array()) {}
///     </body>
///   </method>

	protected function options(array $options = array()) {
		$this->options = array_merge($this->options, $options);
		return $this;
	}

///   </protocol>

///   <protocol name="configuring">

///   <method name="media_type" returns="WS.REST.Application">
///     <args>
///       <arg name="format" type="string" />
///       <arg name="content_type" type="string" />
///     </args>
///     <body>
  public function media_type($format, $content_type, $is_default = false) {
    $this->media_types[$format] = $content_type;
    if ($is_default) $this->default_format = $format;
    return $this;
  }
///     </body>
///   </method>

///   <method name="media_type_for" returns="string">
///     <args>
///       <arg name="format" type="string" />
///     </args>
///     <body>
  public function media_type_for($format) { return $this->media_types[$format]; }
///     </body>
///   </method>

///   <method name="format_for" returns="string">
///     <args>
///       <arg name="media_type" type="string" />
///     </args>
///     <body>
  public function format_for($media_type) { return array_search($media_type, $this->media_types); }
///     </body>
///   </method>

///   <method name="resource" returns="WS.REST.Application">
///     <args>
///       <arg name="name" type="string" />
///       <arg name="resource" type="WS.REST.Resource" />
///     </args>
///     <body>
  public function resource($name, WS_Services_REST_Resource $resource) {
    $this->resources[$name] = $resource;
    $this->classes[Core_Types::real_class_name_for($resource->classname)] = $resource;
    return $this;
  }
///     </body>
///   </method>

  public function get_resource($name) {
    return $this->resources[$name];
  }
  
///   </protocol>

///   <protocol name="performing">

///   <method name="run" returns="mixed">
///     <args>
///       <arg name="env" type="WS.Environment" />
///     </args>
///     <body>
  public function run(WS_Environment $env) {
    $this->before_run($env->app($this));
    
    $this->find($env);
    
    return $this->is_match() ? $this->create_response($env) : Net_HTTP::Response(Net_HTTP::NOT_FOUND);
  }
///     </body>
///   </method>


  public function create_response($env) {
    if (!empty($this->callback_result)) return $this->callback_result;
    return Core::with(($this->target_instance && $this->target_method) ?
      ( ($result = $this->execute(
           $this->target_instance,
           $this->target_method->name,
           $env,
           $this->match ? $this->match->parms : array(), $this->format, $this->target_method->defaults)) instanceof Net_HTTP_Response ?
        $result : Net_HTTP::Response($result) )  :
      Net_HTTP::Response(Net_HTTP::NOT_FOUND))->
        content_type(!empty($this->format) ? $this->format : self::DEFAULT_CONTENT_TYPE);
  }

  public function find($env) {
    if ($this->is_match) return $this->is_match;
    $this->clear();

    list($uri, $extension) = $this->canonicalize($env->request->path);

    $accept_formats = $this->parse_formats($env->request);
    
    list($target_resource, $target_method, $target_instance) = array(null, null, null);

    // Le ballet de la Merlaison, mouvement 1
    foreach ($this->resources as $resource)
      if (($match = $resource->path->match($uri)) ) {
        $target_resource = $resource;
        $uri = $match->tail;
        //FIXME: убрать в match
        //if (empty($uri)) $uri = '/index';
        break;
      }

    // Le ballet de la Merlaison, mouvement 2
    if ($target_resource) {
      $target_instance = $this->instantiate($target_resource, $env, $match->parms);
      $ai = $this->after_instantiate($target_instance);
      for ($i = 0; $target_resource && ($i < self::LOOKUP_LIMIT); $i++) {
        foreach ($target_resource->methods as $method) {
          if (($method->path && ($match = $method->path->match($uri))) || (!$uri && !$method->path)) {
            if ($method->http_mask) {
              if (($method->http_mask & $env->request->method_code) &&
                  !$match->tail &&
                  (($format = $this->can_produce($target_resource, $method, $accept_formats, $extension)) !== false)) {
                $target_method = $method;
                break;
              }
            } else {
              if ($target_instance = $this->execute($target_instance, $method->name, $env, $match->parms)) {
                $ai = $this->after_instantiate($target_instance);
                $target_resource = $this->lookup_resource_for($target_instance);
                $uri = $match->tail;
                //FIXME: убрать в match
                //if (empty($uri)) $uri = '/index';
              } else {
                $target_resource = null;
              }
              break;
            }
          }
        }
        if ($target_method) break;
      }
    }

    $this->callback_result = $ai;
    $this->target_resource = $target_resource;
    $this->target_method = $target_method;
    $this->target_instance = $target_instance;
    $this->format = $format;
    $this->match = $match;
    $this->is_match = $target_resource && $target_method;
    return $this->is_match;
  }

  public function is_match() {
    return $this->is_match;
  }
  
  public function clear() {
    $this->is_match = false;
    $this->target_resource = null;
    $this->target_method = null;
    $this->target_instance = null;
    $this->match = null;
    $this->format = null;
    return $this;
  }
  

///   </protocol>

///   <protocol name="supporting">

///   <method name="can_produce">
///     <args>
///       <arg name="resource" type="WS.REST.Resource" />
///       <arg name="method" type="WS.REST.Method" />
///       <arg name="accept_formats" type="array" />
///       <arg name="exstension" type="string" />
///     </args>
///     <body>
    protected function can_produce(WS_Services_REST_REsource $resource, WS_Services_REST_Method $method, $accept_formats, $extension) {
    $formats = array_merge($resource->formats, $method->formats);
    
    $any_format = false;
    if (array_search('*', $formats) !== false) $any_format = true;
    
    if ($extension)
      return $any_format || in_array($extension, $formats) ? $this->media_types[$extension] : false;
    
    
    foreach ($accept_formats as $accept_type => $q) {
      if ($any_format) return $accept_type;
      foreach ($formats as $format) {
        if (isset($this->media_types[$format])) {
          $type = $this->media_types[$format];
          if (preg_match('{^'.str_replace('*', '.+', str_replace('+', '\+', $type)).'$}', $accept_type)) return $accept_type;
          if (preg_match('{^'.str_replace('*', '.+', str_replace('+', '\+', $accept_type)).'$}', $type)) return $type;
        }
      }
    }

    if (in_array($this->default_format, $formats)) return $this->media_types[$this->default_format];
    return false;
  }
///     </body>
///   </method>

///   <method name="before_run" access="protected">
///     <args>
///       <arg name="env" type="WS.Environment" />
///     </args>
///     <body>
  protected function before_run(WS_Environment $env) { }
///     </body>
///   </method>

///   <method name="after_instantiate" access="protected">
///     <args>
///       <arg name="resource" />
///     </args>
///     <body>
  protected function after_instantiate($resource) { }
///     </body>
///   </method>

///   <method name="parse_formats">
///     <args>
///       <arg name="request" type="" />
///     </args>
///     <body>
  protected function parse_formats($request) {
    $formats = array();
    if (!$request->headers->accept) return array();
    foreach (explode(',', $request->headers->accept) as $index => $accept) {
      if (strpos($accept, '*/*') !== false) continue;
      $split = preg_split('/;\s*q=/', $accept);
      if (count($split) > 0) $formats[trim($split[0])] = (float) Core::if_not_set($split, 1, 1.0);
    }
    arsort($formats);
    return $formats;
  }
///     </body>
///   </method>

///   <method name="canonicalize" returns="array" access="protected">
///     <args>
///       <arg name="uri" type="string" />
///       <arg name="default_format" type="string" default="'html'" />
///     </args>
///     <body>
  protected function canonicalize($uri) {
    switch (true) {
      case $uri[strlen($uri) -1] == '/':
        return array("{$uri}index", null);
      case preg_match('{\.([a-zA-z0-9]+)$}', $uri, $m):
        return array(preg_replace('{\.'.$m[1].'$}', '', $uri), $m[1]);
      default:
        return array("$uri/index", null);
    }

  }
///     </body>
///   </method>


///   <method name="lookup_resource_for" returns="WS.REST.Resource">
///     <args>
///       <arg name="object" />
///     </args>
///     <body>
  protected function lookup_resource_for($object) {
    foreach (Core_Types::class_hierarchy_for($object) as $classname) {
      if (isset($this->classes[$classname])) return $this->classes[$classname];
    }
    return null;
  }
///     </body>
///   </method>

///   <method name="execute" returns="mixed" access="protected">
///     <args>
///       <arg name="instance" type="object" />
///       <arg name="method" type="string" />
///       <arg name="env" type="WS.Environment" />
///       <arg name="parms" type="array" />
///     </args>
///     <body>
  protected function execute($instance, $method, WS_Environment $env, array $parms, $format = null, $defaults = array()) {
    $reflection = new ReflectionMethod($instance, $method);
    return $reflection->invokeArgs($instance, $this->make_args($reflection->getParameters(), $env, $parms, $format, $defaults));
  }
///     </body>
///   </method>

///   <method name="instantiate" returns="mixed">
///     <args>
///       <arg name="classname" type="string" />
///       <arg name="env" type="WS.Environment" />
///       <arg name="parms" type="array" />
///     </args>
///     <body>
  protected function instantiate(WS_Services_REST_Resource $resource, WS_Environment $env, array $parms) {
    if ($resource->need_load) {
      Core::autoload($resource->classname);
		  // Core::load($resource->is_module ?
		  //   $resource->classname :
		  //   Core_Types::module_name_for($resource->classname));
    }

    $r = new ReflectionClass(Core_Types::real_class_name_for($resource->classname));

    return ($c = $r->getConstructor()) ?
      $r->newInstanceArgs($this->make_args($c->getParameters(), $env, $parms)) :
      $r->newInstance();
  }
///     </body>
///   </method>

///   <method name="make_args" returns="array">
///     <args>
///       <arg name="args"  type="array" />
///       <arg name="env"   type="WS.Environment" />
///       <arg name="parms" type="array" />
///     </args>
///     <body>
  protected function make_args(array $args, WS_Environment $env, array $parms, $format = null, $defaults = array()) {
    $vals = array();
    $parms = array_merge($defaults, $parms);
    foreach ($args as $arg) {
      $name = $arg->getName();
      switch ($name) {
        case 'application': $vals[] = $this;                     break;
        case 'env':         $vals[] = $env;                      break;
        case 'format':      $vals[] = $this->format_for($format);                   break;
        case 'parameters':
        case 'parms':       $vals[] = $env->request->parameters; break;
        case 'request':     $vals[] = $env->request;             break;
        default:
          if (isset($parms[$name]))                $vals[] = $parms[$name];
          elseif (isset($env->request[$name]))     $vals[] = $env->request[$name];
          elseif ($arg->isDefaultValueAvailable()) $vals[] = $arg->getDefaultValue();
          else                                     $vals[] = null;
      }
    }
    return $vals;
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
    switch (true) {
      case property_exists($this, $property): return $this->$property;
      case method_exists($this, $m = 'get_'.$property): return $this->$m();
      case array_key_exists($property, $this->options): return $this->options[$property];
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
    if ($property == 'name') return $this->$property = (string) $name;
    throw property_exists($this, $property) ||
          method_exists($this, 'get_'.$property) ||
          array_key_exists($property, $this->options) ?
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
    return property_exists($this, $property) ||
           method_exists($this, 'isset_'.$property) ||
           method_exists($this, 'get_'.$property) ||
           isset($this->options[$property]);
  }
///     </body>
///   </method>

///   <method name="__unset">
///     <args>
///       <arg name="property" type="string" />
///     </args>
///     <body>
  public function __unset($property) {
    throw property_exists($this, $property) ||
          method_exists($this, 'get_'.$property) ||
          array_key_exists($property, $this->options) ?
      new Core_ReadOnlyPropertyException($property) :
      new Core_MissingPropertyException($property);
  }
///     </body>
///   </method>

///   </protocol>
}
/// </class>


/// <composition>
///   <source class="WS.REST.Application" role="Application" multiplicity="1" />
///   <target class="WS.REST.Resource" role="Resources" multiplicity="N" />
/// </composition>

/// <class name="WS.REST.Resource">
///   <implements interface="Core.PropertyAccessInterface" />
///   <implements interface="IteratorAggregate" />
///   <implements interface="Core.EqualityInterface" />
class WS_Services_REST_Resource implements
  Core_PropertyAccessInterface, IteratorAggregate, Core_EqualityInterface {

  protected $classname;
  protected $is_module = false;
  protected $need_load = true;
  protected $path;
  protected $methods     = array();
  protected $formats     = array();

///   <protocol name="creating">

///   <method name="__construct">
///     <args>
///       <arg name="classname" type="string" />
///       <arg name="path" type="string" default="''" />
///     </args>
///     <body>
  public function __construct($classname, $path = '') {
    $this->classname($classname);
    $this->path($path);
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="configuring">

///   <method name="produces" returns="WS.REST.Resource" varargs="true">
///     <body>
  public function produces() {
    foreach (Core::normalize_args(func_get_args()) as $format) $this->formats[] = trim((string) $format);
    return $this;
  }
///     </body>
///   </method>

///   <method name="method" returns="WS.REST.Resource">
///     <args>
///       <arg name="method" type="WS.REST.Method" />
///     </args>
///     <body>
  public function method(WS_Services_REST_Method $method) {
    $this->methods[$method->name] = $method;
    return $this;
  }
///     </body>
///   </method>

  public function get_method($name) {
    return $this->methods[$name];
  }
  
  public function classname($classname) {
    $this->classname = (($this->is_module = $classname[0] == '-') ? substr($classname, 1) : $classname);
    return $this;
  }
  
  public function need_load($v = true) {
    $this->need_load = (boolean) $v;
    return $this;
  }

  public function is_module($v = true) {
    $this->is_module = $v;
    return $this;
  }
  
  public function path($path) {
    $this->path = WS_Services_REST_URI::Template($path);
    return $this;
  }
  
///   </protocol>

///   <protocol name="iterating" interface="IteratorAggregate">

///   <method name="getIterator" returns="ArrayIterator">
///     <body>
  public function getIterator() { return new ArrayIterator($this->methods); }
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
      case 'path':
      case 'classname':
      case 'methods':
      case 'formats':
      case 'is_module':
      case 'need_load':
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
  public function __set($property, $value) { throw new Core_ReadOnlyObjectException($this); }
///     </body>
///   </method>

///   <method name="__isset" returns="boolean">
///     <args>
///       <arg name="property" type="string" />
///     </args>
///     <body>
  public function __isset($property) {
    switch ($property) {
      case 'path':
      case 'classname':
      case 'methods':
      case 'formats':
      case 'is_module':
      case 'need_load':
        return isset($this->$property);
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
  public function __unset($property) { throw new Core_ReadOnlyObjectException($this); }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="quering">
///   <method name="equals" returns="boolean">
///     <args>
///       <arg name="to" />
///     </args>
///     <body>
  public function equals($to) {
    return get_class($this) == get_class($to) &&
      $this->classname == $to->classname &&
      $this->is_module == $to->is_module &&
      Core::equals($this->path, $to->path) &&
      Core::equals($this->methods, $to->methods) &&
      Core::equals($this->formats, $to->formats);
  }
///     </body>
///   </method>
///</protocol>
}
/// </class>

/// <aggregation>
///   <source class="WS.REST.Resource" role="resource" multiplicity="1" />
///   <target class="WS.REST.URI.Template" role="path" multiplicity="1" />
/// </aggregation>

/// <composition>
///   <source class="WS.REST.Resource" role="Resource" multiplicity="1" />
///   <target class="WS.REST.Method" role="Methods" multiplicity="N" />
/// </composition>


/// <class name="WS.REST.Method">
///   <implements interface="Core.PropertyAccessInterface" />
///   <implements interface="Core.EqualityInterface>" />
class WS_Services_REST_Method
  implements Core_PropertyAccessInterface, Core_EqualityInterface {

  protected $name;
  protected $http_mask = 0;
  protected $path = null;
  protected $formats = array('html');
  protected $defaults = array();


///   <protocol name="creating">

///   <method name="__construct">
///     <args>
///       <arg name="name" type="string" />
///     </args>
///     <body>
  public function __construct($name) { $this->name = $name; }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="configuring">

  public function defaults($value) {
    $this->defaults = $value;
    return $this;
  }

///   <method name="produces" returns="WS.REST.Method" varargs="true">
///     <body>
  public function produces() {
    foreach (Core::normalize_args(func_get_args()) as $format) $this->formats[] = trim((string) $format);
    return $this;
  }
///     </body>
///   </method>

///   <method name="http" returns="WS.REST.Method">
///     <args>
///       <arg name="mask" type="int" />
///     </args>
///     <body>
  public function http($mask) {
    $this->http_mask = (int)$mask;
    return $this;
  }
///     </body>
///   </method>

///   <method name="path" returns="WS.REST.Method">
///     <args>
///       <arg name="path" type="string" />
///     </args>
///     <body>
  public function path($path) {
    $this->path = ($path instanceof WS_Services_REST_URI_Template) ?
      $path :
      WS_Services_REST_URI::Template((string) $path);
    return $this;
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
      case 'name':
      case 'http_mask':
      case 'path':
      case 'formats':
      case 'defaults':
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
    switch ($property) {
      case 'name':
      case 'formats':
        throw new Core_ReadOnlyPropertyException($property);
      case 'http_mask':
        {$this->http_mask = (int) $value; return $this;}
      case 'path':
        $this->path($value);
        return $this;
      default:
        throw new Core_MissingPropertyException($property);
    }
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
      case 'name':
      case 'http_mask':
      case 'path':
      case 'formats':
        return isset($this->$property);
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
    switch ($property) {
      case 'name':
      case 'http_mask':
      case 'path':
      case 'formats':
        throw new Core_UndestroyablePropertyException($property);
      default:
        throw new Core_MissingPropertyException($property);
    }
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="quering">
///   <method name="equals" returns="boolean">
///     <args>
///       <arg name="to" />
///     </args>
///     <body>
  public function equals($to) {
    return get_class($this) === get_class($to) &&
      $this->name == $to->name &&
      $this->http_mask == $to->http_mask &&
      Core::equals($this->path, $to->path) &&
      Core::equals($this->formats, $to->formats);
  }
///     </body>
///   </method>
///</protocol>

}
/// </class>

/// <aggregation>
///   <source class="WS.REST.Method" role="method" multiplicity="1" />
///   <target class="WS.REST.URI.Template" role="path" multiplicity="0..1" />
/// </aggregation>


/// </module>
