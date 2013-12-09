<?php
/**
 * WebKit.Mappers.Regexp
 * 
 * @package WebKit\Mappers\Regexp
 * @version 1.2.1
 */

Core::load('WebKit.Controller');

/**
 * @package WebKit\Mappers\Regexp
 */
class WebKit_Mappers_Regexp implements Core_ModuleInterface {

  const MODULE  = 'WebKit.Mappers.Regexp';
  const VERSION = '1.2.1';



/**
 * @return WebKit_Mappers_Regexp_Mapper
 */
  static public function Mapper() { return new WebKit_Mappers_Regexp_Mapper(); }

}

/**
 * @package WebKit\Mappers\Regexp
 */
class WebKit_Mappers_Regexp_Mapper extends WebKit_Controller_AbstractMapper  {
  protected $rules = array();


/**
 * @return WebKit_Mappers_Regexp_Mapper
 */
  public function map($regexp, array $parms, $defaults) {
    $this->rules[] = array($regexp, $parms, $defaults);
    return $this;
  }



/**
 * @param WebKit_HTTP_Request $request
 * @return WebKit_Controller_Route
 */
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

}

