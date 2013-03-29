<?php

class Text_Insertions implements Core_ConfigurableModuleInterface {
  const VERSION = '0.1.0';
  
  static protected $filters = array();
  
  static protected $options = array('filter_class' => 'Text.Insertions.Filter');
  
  static public function initialize(array $options = array()) {
    self::options($options);
  }
  
  static public function options(array $options = array()) {
    if (count($options)) Core_Arrays::update(self::$options, $options);
    return self::$options;
  }
  
  static public function option($name, $value = null) {
    if (is_null($value)) return self::$options[$name];
    return self::$optios[$name] = $value;
  }
  
  static public function filter($name = 'default') {
    return isset(self::$filters[$name]) && self::$filters[$name] instanceof Text_Insertions_FilterInterface ?
      self::$filters[$name] : self::$filters[$name] = Core::make(self::$options['filter_class']);
  }
  
  static public function register_filter() {
    $args = func_get_args();
    return call_user_func_array(array(self::filter(), 'register'), $args);
  }
  
  static public function remove_filter($name) {
    return self::filter()->remove($name);
  }
}

interface Text_Insertions_FilterInterface {
//TODO: property or index interface
  public function register();
  public function remove($name);
  public function get($name);
  public function exists($name);
  public function process($content);

}

class Text_Insertions_Filter implements Text_Insertions_FilterInterface {
  protected $register = array();
  
  protected function standartizate_name($name) {
    return strtolower($name);
  }
  
  public function register() {
		$args = func_get_args();
		foreach (Core::normalize_args($args) as $name => $call) {
		  if ($call instanceof Core_InvokeInterface) $this->register[$this->standartizate_name($name)] = $call;
		}
		return $this;
  }
  
  public function remove($name) {
    unset($this->register[$this->standartizate_name($name)]);
    return $this;
  }
  
  public function get($name) {
    return $this->register[$this->standartizate_name($name)];
  }
  
  public function exists($name) {
    return isset($this->register[$this->standartizate_name($name)]);
  }
  
  public function process($content) {
    return preg_replace_callback($this->get_pattern(), array($this,'replace_callback'), $content);
  }
  
  protected function replace_callback($m) {
    $name = $this->standartizate_name($m[1]);
    $parms = $m[2];
    $res = false;
    Events::call("cms.insertions.$name",$parms,$res);
    if (is_string($res)) return $res;
    Events::call("%{$name}{{$parms}}",$res);
    if (is_string($res)) return $res;
    Events::call("%{$m[1]}{{$parms}}",$res);
    if (is_string($res)) return $res;
    if (isset($this->register[$name])) {
      return $this->invoke($name, $parms);
    }
    return "%{$m[1]}{{$m[2]}}";
  }
  
  //TODO: смешивать параметры
  protected function invoke($name, $parms) {
    return $this->register[$name]->invoke($parms);
  }
  
  protected function get_pattern() {
    return '/%([a-z0-9_-]+)\{([^\{\}]*)\}/i';
  }

}
