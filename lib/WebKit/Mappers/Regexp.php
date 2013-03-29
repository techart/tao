<?php
/// <module name="WebKit.Mappers.Regexp" version="1.2.1" maintainer="timokhin@techart.ru">

Core::load('WebKit.Controller');

/// <class name="WebKit.Mappers.Regexp" stereotype="module">
///   <implements interface="Core.ModuleInterface" />
///   <depends supplier="WebKit.Mappers.Regexp.Mapper" stereotype="creates" />
class WebKit_Mappers_Regexp implements Core_ModuleInterface {

///   <constants>
  const MODULE  = 'WebKit.Mappers.Regexp';
  const VERSION = '1.2.1';
///   </constants>


///   <protocol name="building">

///   <method name="Mapper" returns="WebKit.Mappers.Regexp.Mapper" scope="class">
///     <body>
  static public function Mapper() { return new WebKit_Mappers_Regexp_Mapper(); }
///     </body>
///   </method>

///   </protocol>
}
/// </class>

/// <class name="WebKit.Mappers.Regexp.Mapper" extends="WebKit.Controller.AbstractMapper">
class WebKit_Mappers_Regexp_Mapper extends WebKit_Controller_AbstractMapper  {
  protected $rules = array();

///   <protocol name="mapping">

///   <method name="map" returns="WebKit.Mappers.Regexp.Mapper">
///     <body>
  public function map($regexp, array $parms, $defaults) {
    $this->rules[] = array($regexp, $parms, $defaults);
    return $this;
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
    if ($this->is_not_match_for($request->urn)) return null;

    if ($route = $this->route_index($request)) return $route;

    $uri = $this->clean_url($request->urn);

    foreach ($this->rules as $rule) {
      if ($match = Core_Regexps::match_with_results($rule[0], $uri)) {
        $route = WebKit_Controller::Route();
        foreach ($rule[1] as $k => $v)
          $route[$v] = isset($match[$k+1]) ? $match[$k+1] : $rule[2][$v];
        $route->merge($rule[2]);
        break;
      }
    }

    return $route ?
      $route->
        merge($this->defaults)->
        add_controller_prefix($this->options['prefix']) :
      null;
  }
///     </body>
///   </method>

///   </protocol>
}
/// </class>

/// </module>
