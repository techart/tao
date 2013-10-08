<?php
/// <module name="WebKit.Mappers.Rest" version="1.2.0" maintainer="timokhin@techart.ru">

Core::load('WebKit.Controller');

/// <class name="WebKit.Mappers.Rest" stereotype="module">
///   <implements interface="Core.ModuleInterface" />
///   <depends supplier="WebKit.Mappers.Rest.Mapper" stereotype="creates" />
class WebKit_Mappers_Rest implements Core_ModuleInterface {

///   <constants>
  const MODULE  = 'WebKit.Mappers.Rest';
  const VERSION = '1.2.0';
///   </constants>

///   <protocol name="building">

///   <method name="Mapper" returns="WebKit.Mappers.Rest.Mapper" scope="class">
///     <body>
  static public function Mapper() { return new WebKit_Mappers_Rest_Mapper(); }
///     </body>
///   </method>

///   </protocol>
}
/// </class>


/// <class name="WebKit.Mappers.Rest.Mapper" extends="WebKit.Controller.AbstractMapper">
///   <implements interface="Core.CallInterface" />
class WebKit_Mappers_Rest_Mapper
  extends    WebKit_Controller_AbstractMapper
  implements Core_CallInterface {

  protected $resources      = array();
  protected $single_names   = array();
  protected $default_format = 'html';

///   <protocol name="configuring">

///   <method name="map" returns="WebKit.Mappers.Rest.Mapper">
///     <args>
///       <arg name="name"       type="string" />
///       <arg name="definition" type="array" />
///     </args>
///     <body>
  public function map($name, array $definition) {
    $this->resources[$name] = $definition;
    if (!isset($this->resources[$name]['single']) || $this->resources[$name]['single'] == $name)
      $this->resources[$name]['single'] = "single_$name";

    $this->single_names[$this->resources[$name]['single']] = $name;
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

    $uri = Core_Regexps::replace('{^/|/$}', '', $this->clean_url($request->urn));
    $uri = Core_Strings::replace($uri, '.'.($format = $this->guess_format($uri)), '');

    $current    = false;
    $action     = false;
    $nested_ids = array();

    $parts    = Core_Strings::split_by('/', $uri);
    $last_idx = count($parts) - 1;

    foreach ($parts as $idx => $part) {
      if (!$current) {
        if (isset($this->resources[$part]) &&
            !isset($this->resources[$part]['parent']))
          $current = $part;
        else
          return null;

      } else {
        if (isset($nested_ids["{$this->resources[$current]['single']}_id"])) {
          if ( isset($this->resources[$current]['instance']) &&
               isset($this->resources[$current]['instance'][$part]) &&
               Core_Strings::contains($this->resources[$current]['instance'][$part],$request->method) &&
               $idx == $last_idx ) {
            $action = $part;
            break;
          }

          if (isset($this->resources[$part]) &&
              isset($this->resources[$current]['nested']) &&
              Core_Arrays::contains($this->resources[$current]['nested'], $part))
            $current = $part;
          else
            return null;

        } else {

          if ( isset($this->resources[$current]['collection']) &&
               isset($this->resources[$current]['collection'][$part]) &&
               Core_Strings::contains($this->resources[$current]['collection'][$part], $request->method) &&
               $idx == $last_idx ) {
            $action = $part;

          } else {
            $nested_ids["{$this->resources[$current]['single']}_id"] = $part;
          }
        }
      }
    }

    $id = isset($nested_ids["{$this->resources[$current]['single']}_id"]) ?
      $nested_ids["{$this->resources[$current]['single']}_id"] :
      false;

    if (!$action) {
      switch ($request->method_name) {
        case 'get':
          $action = ($id === false) ? 'index' : 'view';
          break;
        case 'post':
          $action = 'create';
          break;
        case 'put':
          $action = 'update';
          break;
        case 'delete':
          $action = 'delete';
          break;
      }
    }
    return WebKit_Controller::Route()->
      merge(array(
        'controller' => isset($this->resources[$current]['controller']) ?
          $this->resources[$current]['controller'] :
          Core_Strings::capitalize($current).'Controller',
        'action'     => $action,
        'format'     => $format))->
      merge($id ? array($id) : array())->
      merge($nested_ids)->
      merge($this->defaults)->
      add_controller_prefix($this->options['prefix']);
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="calling" interface="Core.CallInterface">

///   <method name="__call" returns="mixed">
///     <args>
///       <arg name="name" type="string" />
///       <arg name="args" type="array" />
///     </args>
///     <body>
  public function __call($name, $args) {
    if (!Core_Regexps::match('{_url$}', $name))
      throw new Core_MissingMethodException($name);

    $name = Core_Strings::replace($name, 'single_', 'single-');
    $url      = '';
    $args     = Core_Arrays::reverse($args);
    $parms    = Core_Types::is_array($args[0]) ? Core_Arrays::shift($args) : array();
    $parts    = Core_Arrays::reverse(Core_Strings::split_by('_', Core_Regexps::replace('{_url$}', '', $name)));
    $last_idx = count($parts) - 1;
    $target   = false;

    foreach($parts as $idx => $part) {
      $part = Core_Strings::replace($part, '-', '_');
      if ($target)  {
        if (isset($this->single_names[$part])) {
          $url = '/'.$this->single_names[$part].'/'.(Core_Types::is_object($arg = Core_Arrays::shift($args)) ? $arg->id : (string) $arg).$url;
        } elseif ( $idx == $last_idx &&
                 ( isset($this->resources[$target]) &&
                   isset($this->resources[$target]['collection']) &&
                   isset($this->resources[$target]['collection'][$part])) ||
                 ( isset($this->single_names[$target]) &&
                   isset($this->resources[$this->single_names[$target]]['instance']) &&
                   isset($this->resources[$this->single_names[$target]]['instance'][$part]))) {
          $url .= "/$part";
        } else
          throw new Core_MissingMethodException($name);
      } else {
        if (isset($this->resources[$part])) {
          $url    = "/{$part}{$url}";
          $target = $part;
        } elseif (isset($this->single_names[$part])) {
          $id = Core_Arrays::shift($args);
          $url = '/'.$this->single_names[$part].'/'.( Core_Types::is_object($id) ? $id->id : (string)$id ).$url;
          $target = $part;
        } else
          throw new Core_MissingMethodException($name);
      }
    }

    return $this->add_keyword_parameters(
      $this->add_path($url).(isset($args[0]) ? '.'.$args[0] : ".{$this->default_format}"),
      $parms);
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="supporting">

///   <method name="guess_format" returns="string" access="protected">
///     <args>
///       <arg name="uri" type="string" />
///     </args>
///     <body>
  protected function guess_format($uri) {
    return ($match = Core_Regexps::match_with_results('{\.([a-z]+)$}', $uri)) ?
      $match[1] : $this->default_format;
  }
///     </body>
///   </method>

///   </protocol>
}
/// </class>

/// </module>
