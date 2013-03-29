<?php
/// <module name="WS.REST.DSL" version="0.2.0" maintainer="timokhin@techart.ru">

Core::load('DSL', 'WS.Services.REST');

/// <class name="WS.REST.DSL" stereotype="module">
class WS_Services_REST_DSL implements Core_ModuleInterface {

///   <constants>
  const VERSION = '0.2.1';
///   </constants>

///   <protocol name="creating">

///   <method name="Application" returns="WS.REST.DSL.Application">
///     <args>
///       <arg name="application" type="WS.REST.Application" default="null" />
///     </args>
///     <body>
  static public function Application(WS_Services_REST_Application $application = null) {
    return new WS_Services_REST_DSL_Application(null, $application);
  }
///     </body>
///   </method>

///   </protocol>
}
/// </class>


/// <class name="WS.REST.DSL.Builder" extends="DSL.Builder" stereotype="abstract">
abstract class WS_Services_REST_DSL_Builder extends DSL_Builder {}
/// </class>

/// <class name="WS.REST.DSL.Application" extends="DSL.Builder">
class WS_Services_REST_DSL_Application extends WS_Services_REST_DSL_Builder {

  protected $class_prefix = '';

///   <protocol name="creating">

///   <method name="__construct">
///     <args>
///       <arg name="app" type="WS.REST.Application" default="null" />
///     </args>
///     <body>
  public function __construct(WS_Services_REST_DSL_Application $parent = null, WS_Services_REST_Application $app = null, $class_prefix = '') {
    $this->class_prefix = $class_prefix;
    parent::__construct($parent, $app ? $app : new WS_Services_REST_Application());
  }
///     </body>
///   </method>

  public function for_class_prefix($prefix) {
    return new self($this, $this->object, $prefix);
  }


///   </protocol>

///   <protocol name="building">

///   <method name="begin_resource">
///     <args>
///       <arg name="name" type="string" />
///       <arg name="classname" type="string" />
///       <arg name="path" type="string" default="''" />
///     </args>
///     <body>
   public function begin_resource($name, $classname, $path = '', $is_module = false) {
      $r = new WS_Services_REST_Resource($this->class_prefix.$classname, $path);
      $r->is_module($is_module);
      $this->object->resource($name, $r);
      return new WS_Services_REST_DSL_Resource($this, $r);
   }
///     </body>
///   </method>

///   </protocol>
}
/// </class>

/// <class name="WS.REST.DSL.Resource" extends="WS.REST.DSL.Builder">
class WS_Services_REST_DSL_Resource extends WS_Services_REST_DSL_Builder {

  protected $scope;

///   <protocol name="creating">

///   <method name="__construct">
///     <args>
///       <arg name="parent" type="WS.REST.DSL.Application" />
///       <arg name="resource" type="WS.REST.Resource" />
///     </args>
///     <body>
  public function __construct(WS_Services_REST_DSL_Builder $parent, WS_Services_REST_Resource $resource, $scope = null) {
    parent::__construct($parent, $resource);
    $this->scope = ($scope instanceof stdClass) ? $scope : new stdClass();
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="building">

///   <method name="bind" returns="WS.REST.DSL.Resource">
///     <args>
///       <arg name="method" type="string" />
///       <arg name="path" type="string" default="null" />
///       <arg name="formats" type="string" default="null" />
///     </args>
///     <body>
  public function bind($method, $path = null, $formats = null) {
    return $this->method($method, null, $path, $formats);
  }
///     </body>
///   </method>

///   <method name="for_methods" returns="WS.REST.DSL.Resource">
///     <args>
///       <arg name="http_mask" type="int" />
///     </args>
///     <body>
  public function for_methods($http_mask) {
    $s = clone $this->scope;
    $s->http_methods = $http_mask;
    return new WS_Services_REST_DSL_Resource($this, $this->object, $s);
  }
///     </body>
///   </method>

///   <method name="for_path" returns="WS.REST.DSL.Resource">
///     <args>
///       <arg name="path" type="string" />
///     </args>
///     <body>
  public function for_path($path) {
    $s = clone $this->scope;
    $s->path = $path;
    return new WS_Services_REST_DSL_Resource($this, $this->object, $s);
  }
///     </body>
///   </method>

///   <method name="for_format" returns="WS.REST.DSL.Resource">
///     <args>
///       <arg name="formats" type="string" />
///     </args>
///     <body>
  public function for_format($formats) {
    $s = clone $this->scope;
    $s->formats = $formats;
    return new WS_Services_REST_DSL_Resource($this, $this->object, $s);
  }
///     </body>
///   </method>

///   <method name="get" returns="WS.REST.DSL.Resource">
///     <args>
///       <arg name="name" type="string" default="index" />
///       <arg name="formats" type="string" default="null" />
///     </args>
///     <body>
  public function get($name = 'index', $formats = null) {
    return $this->method($name, Net_HTTP::GET | Net_HTTP::HEAD, null, $formats);
  }
///     </body>
///   </method>

  public function any($name = 'index') {
    return $this->method($name, Net_HTTP::ANY, $name, '*');
  }

///   <method name="index" returns="WS.REST.DSL.Resource">
///     <args>
///     </args>
///     <body>
  public function index($formats = null) {
    return $this->get('index', $formats);
  }
///     </body>
///   </method>

///   <method name="get_for" returns="WS.REST.DSL.Resource">
///     <args>
///       <arg name="path" type="string" />
///       <arg name="name" type="string" default="''" />
///       <arg name="formats" type="string" default="null" />
///     </args>
///     <body>
  public function get_for($path, $name = '', $formats = null) {
    return $this->method($name, Net_HTTP::GET | Net_HTTP::HEAD, $path, $formats);
  }
///     </body>
///   </method>

///   <method name="post" returns="WS.REST.DSL.Resource">
///     <args>
///       <arg name="name" type="string" default="index" />
///       <arg name="formats" type="string" default="null" />
///     </args>
///     <body>
  public function post($name = 'create', $formats = null) {
    return $this->method( $name, Net_HTTP::POST, null, $formats);
  }
///     </body>
///   </method>

///   <method name="get_for" returns="WS.REST.DSL.Resource">
///     <args>
///       <arg name="path" type="string" />
///       <arg name="name" type="string" default="''" />
///       <arg name="formats" type="string" default="null" />
///     </args>
///     <body>
  public function post_for($path, $name = '', $formats = null) {
    return $this->method($name, Net_HTTP::POST, $path, $formats);
  }
///     </body>
///   </method>

///   <method name="put" returns="WS.REST.DSL.Resource">
///     <args>
///       <arg name="name" type="string" default="index" />
///       <arg name="formats" type="string" default="null" />
///     </args>
///     <body>
  public function put($name = 'update', $formats = null) {
    return $this->method( $name, Net_HTTP::PUT, null, $formats);
  }
///     </body>
///   </method>

///   <method name="get_for" returns="WS.REST.DSL.Resource">
///     <args>
///       <arg name="path" type="string" />
///       <arg name="name" type="string" default="''" />
///       <arg name="formats" type="string" default="null" />
///     </args>
///     <body>
  public function put_for($path, $name = '', $formats = null) {
    return $this->method($name, Net_HTTP::PUT, $path, $formats);
  }
///     </body>
///   </method>

///   <method name="delete" returns="WS.REST.DSL.Resource">
///     <args>
///       <arg name="name" type="string" default="index" />
///       <arg name="formats" type="string" default="null" />
///     </args>
///     <body>
  public function delete($name = 'delete', $formats = null) {
    return $this->method($name, Net_HTTP::DELETE, null, $formats);
  }
///     </body>
///   </method>

///   <method name="delete_for" returns="WS.REST.DSL.Resource">
///     <args>
///       <arg name="path" type="string" />
///       <arg name="name" type="string" default="''" />
///       <arg name="formats" type="string" default="null" />
///     </args>
///     <body>
  public function delete_for($path, $name = '', $formats = null) {
    return $this->method($name, Net_HTTP::DELETE, $path, $formats);
  }
///     </body>
///   </method>

///   <method name="method" returns="WS.REST.DSL.Resource">
///     <args>
///       <arg name="name" type="string" />
///       <arg name="http_mask" type="int" default="Net_HTTP::ANY" />
///       <arg name="path" type="string" default="null" />
///       <arg name="formats" type="string" default="null" />
///     </args>
///     <body>
  public function method($name, $http_mask = Net_HTTP::ANY, $path = null, $formats = null, $defaults = array()) {
    $formats = ($formats === null) ?
      (isset($this->scope->formats) ? $this->scope->formats : array() ) :
      $formats;
    $path = $path === null ? (isset($this->scope->path) ? $this->scope->path : 'index') : $path;
    $name = $name ? $name : $path;
    $http_mask = $http_mask === null ? (isset($this->scope->http_methods) ? $this->scope->http_methods : Net_HTTP::ANY) : $http_mask;

    $this->object->method(
      Core::with(
        new WS_Services_REST_Method($name))->
          path($path)->
          http($http_mask)->
          defaults($defaults)->
          produces(is_array($formats) ? $formats : Core_Strings::split_by(',', (string) $formats)));

    return $this;
  }
///     </body>
///   </method>

///   <method name="sublocator" returns="WS.REST.DSL.Resource">
///     <args>
///       <arg name="name" type="string" />
///       <arg name="path" type="string" default="null" />
///     </args>
///     <body>
  public function sublocator($name, $path = null) {
    return $this->method(
     $name,
     0,
     ($path === null) ?
       (isset($this->scope->path) ? $this->scope->path : $name) :
       $path );
  }
///     </body>
///   </method>

///   </protocol>
}
/// </class>

/// </module>
