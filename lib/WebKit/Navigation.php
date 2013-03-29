<?php
/// <module name="WebKit.Navigation" version="1.2.0" maintainer="timokhin@techart.ru">

Core::Load('Data');

/// <class name="WebKit.Navigation" stereotype="module">
///   <implements interface="Core.ModuleInterface" />
///   <depends supplier="WebKit.Navigation.Link" stereotype="creates" />
///   <depends supplier="WebKit.Navigation.LinkSet" stereotype="creates" />
class WebKit_Navigation implements Core_ModuleInterface {

///   <constants>
  const MODULE  = 'WebKit.Navigation';
  const VERSION = '1.2.0';
///   </constants>

///   <protocol name="building">

///   <method name="Link" returns="WebKit.Navigation.Link">  
///     <args>
///       <arg name="name"  type="string" />
///       <arg name="url"   type="string" />
///       <arg name="title" type="string" />
///       <arg name="options" type="array" default="array()" />
///     </args>
///     <body>
  public function Link($title, $url, array $options = array()) {
    return new WebKit_Navigation_Link($title, $url, $options);
  }
///     </body>
///   </method>

///   <method name="LinkSet" returns="WebKit.Navigation.LinkSet">
///     <body>
  public function LinkSet() { return new WebKit_Navigation_LinkSet(); }
///     </body>
///   </method>

///   <method name="links">
///     <body>
  public function links() { return self::LinkSet(); }
///     </body>
///   </method>

///   </protocol>
}
/// </class>

/// <class name="WebKit.Navigation.LinkSet">
///   <implements interface="Core.PropertyAccessInterface" />
///   <implements interface="IteratorAggregate" />
class WebKit_Navigation_LinkSet 
  implements Core_PropertyAccessInterface, 
             IteratorAggregate {

  protected $links;

///   <protocol name="creating">

///   <method name="__construct">
///     <body>
  public function __construct() {
    $this->links = Data::Hash();
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="building">

///   <method name="link" returns="WebKit.Navigation.LinkSet"> 
///     <args>
///       <arg name="name"  type="string" />
///       <arg name="url"   type="string" />
///       <arg name="title" type="string" />
///       <arg name="arg1"  default="null" />
///       <arg name="arg2"  default="null" />
///     </args>
///     <body>
  public function link($name, $url, $title, $arg1 = null, $arg2 = null) {
    $this->links[(string) $name] = new WebKit_Navigation_Link(
      $url, $title, Core_Types::is_array($arg1) ? $arg1 : array());
    
    
    if ($sublinks = ($arg1 instanceof WebKit_Navigation_LinkSet) ? 
          $arg1 : ($arg2 instanceof WebKit_Navigation_LinkSet ? 
            $arg2 : null)) 
      $this->links[(string) $name]->sublinks($sublinks);

    return $this;
  }
///     </body>
///   </method>

///   </protocol>
  
///   <protocol name="selecting">

///   <method name="select" returns="WebKit.Navigation.LinkSet">
///     <args>
///       <arg name="name" type="string" />
///     </args>
///     <body>
  public function select($name) {
    if (isset($this->links[$name])) $this->links[$name]->select();
    return $this;
  }
///     </body>
///   </method>
  
///   </protocol>

///   <protocol name="quering">

///   <method name="selected" returns="WebKit.Navigation.Link">
///     <body>
  public function selected() {
    foreach ($this->links as $k => $v) if ($v->is_selected()) return $v;
    return null;
  }
///     </body>
///   </method>

///   <method name="has_selected_link" returns="boolean">
///     <body>
  public function  has_selected_link() {
    return $this->selected() ? true : false;
  }
///     </body>
///   </method>

///   <method name="sublink" returns="WebKit.Navigation.Link">
///     <args>
///       <arg name="name" type="string" />
///     </args>
///     <body>
  public function sublink($name) { return $this->links[$name]; }
///     </body>
///   </method>
  
///   </protocol>

///   <protocol name="accessing" interface="Core.PropertyAccessInterface">

///   <method name="__get" returns="WebKit.Navigation.Link">
///     <args>
///       <arg name="property" type="string" />
///     </args>
///     <body>
  public function __get($property) {
    return $this->sublink($property);
  }
///     </body>
///   </method>

///   <method name="__set" returns="WebKit.Navigation.Link">
///     <args>
///       <arg name="property" type="string" />
///       <arg name="value" type="WebKit.Navigation.Link" />
///     </args>
///     <body>
  public function __set($property, $value) {
    if ($value instanceof WebKit_Navigation_Link) 
      return $this->links[$property] = $value;
    else
      throw new Core_InvalidArgumentTypeException($value);
  }
///     </body>
///   </method>

///   <method name="__isset" returns="boolean">
///     <args>
///       <arg name="property" type="string" />
///     </args>
///     <body>
  public function __isset($property) {
    return isset($this->links[$property]);
  }
///     </body>
///   </method>

///   <method name="__unset">   
///     <args>
///       <arg name="property" type="string" />
///     </args>
///     <body>
  public function __unset($property) {
    unset($this->links[$index]);
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="iterating" interface="IteratorAggregate">  

///   <method name="getIterator" returns="Iterator">
///     <body>
  public function getIterator() { return $this->links->getIterator(); }
///     </body>
///   </method>
  
///   </protocol>
}
/// </class>
/// <aggregation>
///   <source class="WebKit.Navigation.LinkSet" role="set" multiplicity="1" />
///   <target class="WebKit.Navigation.Link" role="link" multiplicity="N" />
/// </aggregation>


/// <class name="WebKit.Navigation.Link">
///   <implements interface="Core.PropertyAccessInterface" />
///   <implements interface="Core.IndexedAccessInterface" />
class WebKit_Navigation_Link 
  implements Core_PropertyAccessInterface, Core_IndexedAccessInterface {

  protected $url;
  protected $title;

  protected $sublinks;

  protected $is_selected = false;
  protected $is_disabled = false;

  protected $options;

///   <protocol name="creating">

///   <method name="__construct">
///     <args>
///       <arg name="url" type="string" />
///       <arg name="title" type="string" />
///       <arg name="options" type="array" default="array()" />
///     </args>
///     <body>
  public function __construct($url, $title, array $options = array()) {
    $this->url   = $url;
    $this->title = $title;

    $this->is_disabled = (boolean) Core_Arrays::pick($options, 'disabled', false);
    $this->is_selected = (boolean) Core_Arrays::pick($options, 'selected', false);

    $this->options = Data::Hash($options);
  } 
///     </body>
///   </method>

///   </protocol>

///   <protocol name="building">

///   <method name="sublinks">
///     <args>
///       <arg name="links" type="WebKit.Navigation.LinkSet" />
///     </args>
///     <body>
  public function sublinks(WebKit_Navigation_LinkSet $links) {
    $this->sublinks = $links;
    return $this;
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="changing">

///   <method name="select" returns="WebKit.Navigation.Link">
///     <body>
  public function select() { 
    $this->is_selected = true;
    return true;
  }
///     </body>
///   </method>

///   <method name="deselect" returns="WebKit.Navigation.Link">
///     <body>
  public function deselect() { 
    $this->is_selected = false;
    return true;
  }
///     </body> 
///   </method>

///   <method name="enable" returns="WebKit.Navigation.Link">
///     <body>
  public function enable() {
    $this->is_disabled = false;
    return $this;
  }
///     </body>
///   </method>

///   <method name="disable" returns="WebKit.Navigation.Link">
///     <body>
  public function disable() {
    $this->is_disabled = true;
    return $this;
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="quering">

///   <method name="is_selected" returns="boolean">
///     <body>
  public function is_selected() {
    return  $this->is_selected || 
           ($this->sublinks && $this->sublinks->has_selected_link());
  }
///     </body>
///   </method>

///   <method name="is_disabled" returns="boolean">
///     <body>
  public function is_disabled() { return $this->is_disabled; }
///     </body>
///   </method>

///   <method name="is_enabled" returns="boolean">
///     <body>
  public function is_enabled() { return !$this->disabled; }
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
      case 'url':
      case 'title':
      case 'sublinks':
        return $this->$property;
      case 'selected':
        return $this->is_selected();
      default:
        if ($this->has_sublink($property))
          return $this->sublinks->$property;
        else
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
      case 'selected':
        return $value ? $this->select() : $this->deselect();
      case 'url':
      case 'title':
        return $this->$property = (string) $value;
      case 'sublinks':
        if ($value instanceof WebKit_Navigation_LinkSet) 
          return $this->sublinks = $value;
        else
          throw new Core_InvalidArgumentTypeException('value', $value);
      default:
        if ($this->has_sublink($property))
          throw new Core_ReadOnlyPropertyException($property);
        else
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
      case 'url':
      case 'title':
      case 'sublinks':
      case 'selected':
        return true;
      default:
        return $this->has_sublink($property);
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
      case 'url':
      case 'title':
      case 'sublinks':
      case 'selected':
        throw new Core_UndestroyablePropertyException($property);
      default:
        if ($this->has_sublink($property)) 
          unset($this->sublinks[$property]);
        else
          throw new Core_MissingPropertyException($property);
    }
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
    return isset($this->options[$index]) ? $this->options[$index] : null;
  }
///     </body>
///   </method>

///   <method name="offsetSet" returns="mixed">
///     <args>
///       <arg name="index" />
///     </args>
///     <body>
  public function offsetSet($index, $value) {
    return $this->options[$index] = $value;
  }
///     </body>
///   </method>

///   <method name="offsetExists" returns="boolean">
///     <args>
///       <arg name="index" />
///     </args>
///     <body>
  public function offsetExists($index) {
    return isset($this->options[$index]);
  }
///     </body>
///   </method>

///   <method name="offsetUnset">
///     <args>
///       <arg name="index" />
///     </args>
///     <body>
  public function offsetUnset($index) {
    unset($this->options[$index]);
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="supporting">

///   <method name="option" returns="mixed">
///     <args>
///       <arg name="option" type="string" />
///       <arg name="value" />  
///     </args>
///     <body>
  public function option($option, $value = null) {
    if ($value === null) 
      return $this->options[$option];
    else {
      $this->options[$option] = $value;
      return $this;
    }
  }
///     </body>
///   </method>

///   <method name="has_sublink" returns="boolean">
///     <args>
///       <arg name="name" type="string" />
///     </args>
///     <body>
  public function has_sublink($name) {
    return ($this->sublinks && isset($this->sublinks->$name));
  }
///     </body>
///   </method>

///   </protocol>
}
/// </class>


/// <class name="WebKit.Navigation.ControllerNavigation" stereotype="abstract">
///   <implements interface="Core.PropertyAccessInterface" />
abstract class WebKit_Navigation_ControllerNavigation {
  protected $title;
  protected $controller;
  protected $menu;

///   <protocol name="creating">

///   <method name="__construct">
///     <args>
///       <arg name="controller" type="WebKit.Controller.AbstractController" /> 
///     </args>
///     <body>
  public function __construct(WebKit_Controller_AbstractController $controller) {
    $this->controller = $controller;
    $this->setup();
  }
///     </body>
///   </method>

///   <method name="setup" returns="WebKit.Navigation.ControllerNavigation" access="protected">
///     <body>
  abstract protected function setup();
///     </body>
///   </method>

///   </protocol>
  
///   <protocol name="changing">

///   <method name="title" returns="WebKit.Navigation.ControllerNavigation" stereotype="protected">
///     <args>
///       <arg name="title" type="string" />
///     </args>
///     <body>
  public function title($title) { 
    $this->title = $title;  
    return $this;
  }
///     </body>
///   </method>

///   <method name="menu" returns="WebKit.Navigation.ControllerNavigation" stereotype="protected">
///     <args>
///       <arg name="menu" type="WebKit.Navigation.LinkSet" />
///     </args>
///     <body>
  public function menu(WebKit_Navigation_LinkSet $menu) {
    $this->menu = $menu;
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
      case 'title':
        return $this->title;
      case 'menu':
        return $this->menu;
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
      case 'title':
        return $this->title = (string) $value;
      case 'menu':
        return $this->menu($value)->menu;
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
      case 'title':
      case 'menu':
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
    switch ($property) {
      case 'title':
      case 'menu':
        throw new Core_UndestroyablePropertyException($property);
      default:
        throw new Core_MissingPropertyException($property);
    }
  }
///     </body>
///   </method>

///   </protocol>
}
/// </class>
/// <aggregation>
///   <source class="WebKit.Navigation.ControllerNavigation" role="navigation" />
///   <target class="WebKit.Navigation.LinkSet"  role="menu" />
/// </aggregation>
/// <aggregation>
///   <source class="WebKit.Navigation.ControllerNavigation" role="navigation" />
///   <target class="WebKit.Controller.AbstractController"  role="controller" />
/// </aggregation>


///  </module>
