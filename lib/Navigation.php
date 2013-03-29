<?php
//TODO: refactoring!!!!!!!!! + optimize link references
//TODO: insersions
class Navigation implements Core_ModuleInterface {
  const VERSION = '0.0.1';
  
  static protected $options = array('navigation_set_class' => 'Navigation.Set',
    'navigation_set_state_class' => 'Navigation.SetState');
  
  static public function initialize($conf) {self::$options = array_merge(self::$options, $conf);}
  static public function option($name, $value = null) {
    if (is_null($value)) return self::$options[$name];
    else return self::$options[$name] = $value;
  }
  
  static public function controller() {return new Navigation_Controller();}
  
  static public function Set() { return Core::make(self::option('navigation_set_class')); }

  static public function SetState(Navigation_Set $set) {return Core::make(self::option('navigation_set_state_class'), $set);}

  static public function Link($values = array()) {return new Navigation_Link($values);}
}

interface Navigation_SetInterface {}

class Navigation_Set implements Navigation_SetInterface {

  //protected $data;
  protected $uri;
  protected $flags = array();
  protected $root;
  protected $current_link = false;
  protected $current_path = array();
  //protected $items_by_ulrs = array();
  protected $items_by_ids = array();
  
  public function __construct() {
    $this->root = (object) array('sublinks' => array(), 'root' => true);
  }
  
  public function __destruct() {
    //unset($this->data);
    unset($this->root);
    //unset($this->items_by_ulrs);
    unset($this->flags);
    unset($this->items_by_ids);
  }
  
  public function process($uri, $data) {
    //$this->data = $data;
    $this->uri = $uri;
    $this->load_data($data);
    return $this;
  }
  
  public function flag($name) {
    if (is_array($this->flags[$name]))
      array_walk($this->flags[$name], array($this, 'set_current_link'));
    return $this;
  }
  
  public function current_link() {
    return $this->current_link;
  }
  
  public function current_path($link = null) {
    if (empty($link) && !empty($this->current_path))
      return $this->current_path;
    $link = empty($link) ? $this->current_link : $link;
    $this->current_path[] = $link;
    if (!empty($link->parent))
      $this->current_path($link->parent);
    return $this->current_path;
  }
  
  public function link_by_id($id) {
    return $this->items_by_ids[$id];
  }
  
  public function link_set_by_id($id) {
    return $this->link_by_id($id)->sublinks;
  }
  
  public function is_flag($name, $value = true) {
    return is_array($this->flags[$name]) && $this->flags[$name][0]->selected === $value;
  }
  
  protected function set_current_link($link = null) {
    if (!empty($link)) {
      if (empty($this->current_link))
        $this->current_link = $link;
      $link->selected = true;
      if (!empty($link->parent))
        $this->set_current_link($link->parent);
    }
  }
  
  public function add($title, $item, $parent = null) {
    $this->load_data(array($title => $item), 0, $parent);
    return $this;
  }
  
  public function load_data($data, $level = 0, $parent = null) {
    //TODO: cache
    if (is_null($parent))
      $parent = $this->root;
    foreach ($data as $title => $item) {
      $link = $this->read_item($title, $item);
      if (isset($link->sublinks)) {
        /*&& $this->current_level >= $level*/
        $this->load_data($link->sublinks, $level + 1, $link);
      }
      //if (isset($this->items_by_ulrs[$link->url])) continue;//????????
      $this->add_item($link, $level, $parent);
    }
    return $this;
  }
  
  public function read_item($title, $item) {
    if (is_string($item)) $item = array('url' => trim($item));
    $item['title'] = $title; 
    $url = !empty($item['url']) ? $item['url'] : $item['uri'];
    $item['url'] = $item['uri'] = $url;
    if (isset($item['sub'])) {
      $item['sublinks'] = $item['sub'];
      unset($item['sub']);
    } else $item['sublinks'] = array();
    $link = Navigation::Link($item);
    return $link;
  }
  
  public function add_item($link, $level, $parent = null) {
    //$this->items_by_ulrs[$link->url] = true;
    if ($link->disabled) return $this;
    if ($parent) {
      if (!is_array($parent->sublinks)) $parent->sublinks = array();
      $parent->sublinks[$link->title] = $link;
      $link->parent = $parent;
      if ($link->selected) $this->set_current_link($parent);
    }
    if (isset($link->id)) $this->items_by_ids[$link->id] = $link;
    if ($link->url == $this->uri ||
        (isset($link->match) && preg_match($link->match, $this->uri))
    ) {
      $this->set_current_link($link);
    }
      
    if (isset($link->flag)) $this->flags[$link->flag][] = $link;
  }

  public function __get($name) {
    if (property_exists($this, $name))
        return $this->$name;
    return null;
  }

}

class Navigation_Link extends stdClass {

  public function __construct($values = array()) {
    foreach ($values as $k => $v)
      $this->$k = $v;
  }

  public function is_selected() {
    return $this->selected;
  }

}

class Navigation_SetState {

  protected $set;

  protected $filters = array();
  protected $not_filters = array('url', 'match', 'flag', 'sub', 'uri');
  protected $route = false;
  protected $to_route = array();
  protected $current_level = 0;


  public function __construct(Navigation_Set $set) {
    $this->set = $set;
  }

  public function __call($method, $args) {
    $r = call_user_func_array(array($this->set, $method), $args);
    if ($r instanceof Navigation_Set)
      return $this;
    return $r;
  }

  public function __get($name) {
    if ($name == 'set')
      return $this->set;
    return $this->set->__get($name);
  }


  public function selected_link() {
    $path = $this->current_path();
    return $path[(count($path)-2) - $this->current_level];
  }

  public function filter() {
    foreach (Core::normalize_args(func_get_args()) as $arg) {
      $this->filters[str_replace('!', '', $arg)] = !Core_Strings::starts_with($arg, '!');
    }
    return $this;
  }
  
  public function add_filter($name, $value) {
    $this->filters[$name] = $value;
  }

  public function level($n) {
    $this->current_level = $n;
    //TODO: optimize or remove:
    if ($this->count() < 1) return null;
    return $this;
  }

  public function draw($template_name = 'simple', $params = array()) {
    $links = new ArrayObject($this->get_links());
    return Templates_HTML::Template('navigation/' . $template_name)->
      with($params)->
      option('links', $links)->
      with('links', $links)->as_string();
  }

  public function route() {
    $this->route = true;
    //TODO: optimize or remove:
    if ($this->count() < 1) return null;
    return $this;
  }

  public function add_to_route($title, $item) {
    $this->to_route[] = $this->read_item($title, $item);
    return $this;
  }

  public function get_links() {
    $path = $this->set->current_path();
    if ($this->route) {
      $values = array_reverse($path);
      if ($this->current_level == 0) unset($values[0]);
      return array_merge($values, $this->to_route);
    }
    $links = $this->current_level == 0 ?
        $this->set->root->sublinks :
        $path[count($path) - 1 - $this->current_level]->sublinks;
    return is_array($links) ? array_filter($links, array($this, 'filter_links')) : array();
  }

  protected function filter_links($link) {
    foreach ($this->filters as $f => $v) {
      $linkv = isset($link->$f) ? $link->$f : false;
      return $linkv == $v;
    }
    return true;
  }

  public function count() {
    return count($this->get_links());
  }

  public function reset() {
    return $this;
  }



}

class Navigation_Controller implements Core_IndexedAccessInterface {
  protected $sets = array();
  protected $default_set;
  protected $current_set;
  
  public function __construct($default_set = 'default') {
    $this->default_set = $this->current_set = $default_set;
    $this->add_set($this->default_set);
  }
  
  public function add_set($name, $set = null) {
    if (is_null($set)) $set = Navigation::Set();
    if ($set instanceof Navigation_SetInterface)
      $this->sets[$name] = $set;
    return Navigation::SetState($set);
  }
  
  public function get_current_set() {
    return $this->sets[$this->current_set];
  }
  
  public function offsetGet($name) { return $this->sets[$name]; }
  public function offsetExists($name) { return isset($this->sets[$name]); }
  public function offsetSet($name, $value) { $this->add_set($name, $value); }
  public function offsetUnset($name) { unset($this->sets[$name]); }
  
  
  public function __call($method, $args) {
    $wrapper = Navigation::SetState($this->get_current_set());
    if (method_exists($wrapper, $method) || method_exists($wrapper->set, $method))
      return call_user_func_array(array($wrapper, $method), $args);
    if (isset($this->sets[$method]))
      return Navigation::SetState($this->sets[$method]);
    else
      return $this->add_set($method, $args[0]);
  }

  public function __get($name) {
    $wrapper = Navigation::SetState($this->get_current_set());
    return $wrapper->$name;
  }

}
