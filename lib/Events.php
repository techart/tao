<?php

class Events implements Core_ModuleInterface {
	const VERSION = '0.1.0';

	static protected $dispatcher;

	static public function Dispatcher() {
		if (self::$dispatcher) return self::$dispatcher;
		$args = func_get_args();
		return self::$dispatcher = Core::amake('Events.Dispatcher', $args);
	}

	static public function Event() {
		$args = func_get_args();
		return Core::amake('Events.Event', $args);
	}

	static public function add_listener() {
		$args = func_get_args();
		return Core::invoke(array(self::Dispatcher(), 'add_listener'), $args);
	}

	static public function add_once() {
		$args = func_get_args();
		return Core::invoke(array(self::Dispatcher(), 'add_once'), $args);
	}

	static public function add_with_context() {
		$args = func_get_args();
		return Core::invoke(array(self::Dispatcher(), 'add_with_context'), $args);
	}

	static public function add_subscriber() {
		$args = func_get_args();
		return Core::invoke(array(self::Dispatcher(), 'add_subscriber'), $args);
	}

	static public function add_listeners() {
		$args = func_get_args();
		return Core::invoke(array(self::Dispatcher(), 'add_listeners'), $args);
	}

	static public function call($ename, &$arg1 = null, &$arg2 = null, &$arg3 = null, &$arg4 = null, &$arg5 = null, &$arg6 = null) {
		if (!self::Dispatcher()->has_listeners($ename)) return null;
		$args = func_get_args();
		$num_args = count($args);
		if (count($args) < 1) return;
		$call_args = array($args[0], self::Event(), &$arg1, &$arg2, &$arg3, &$arg4, &$arg5, &$arg6);
		if (count($args) > 1)
			$call_args = array_merge($call_args, array_slice($args, 7));
		$call_args = array_slice($call_args, 0, $num_args + 1);
		return Core::invoke(array(self::Dispatcher(), 'dispatch'), $call_args);
	}

	static public function dispatch($ename, Events_Event $e, &$arg1 = null, &$arg2 = null, &$arg3 = null, &$arg4 = null, &$arg5 = null, &$arg6 = null) {
		$args = func_get_args();
		$num_args = count($args);
		return Core::invoke(array(self::Dispatcher(), 'dispatch'), 
			array_slice(array_merge(array($ename, $e, &$arg1, &$arg2, &$arg3, &$arg4, &$arg5, &$arg6), array_slice($args, 8)), 0, $num_args));
	}

	static public function current_event() {
		return self::Dispatcher()->current_event();
	}

}

class Events_Dispatcher {

	protected $listeners = array();
	protected $weight_delta = 0.0000000001;
	protected $curret_auto_weight = 0;
	protected $parent;
	protected $event;
	protected $subscribers = array();

	public function __construct($parent = null) {
		if ($parent instanceof Events_Dispatcher)
			$this->parent = $parent;
	}

	public function current_event() {
		return $this->event;
	}

	public function add_listeners($listeners) {
		foreach ($listeners as $ename => $lists) {
			foreach ($lists as $l) {
				$this->add_listener($ename, $l['call'], isset($l['weight']) ? $l['weight'] : 0, $l['context'], $l['once'], $l['subscriber']);
			}
		}
		return $this;
	}

	public function add_listener($ename, $call, $weight = 0, $context = null, $once = false, $subscriber = null) {
		//if (!Core_Types::is_callable($call)) return;
		if ($weight == 0) {
			$this->curret_auto_weight += $this->weight_delta;
			$weight = $this->curret_auto_weight;
		}
		$this->listeners[$ename][] = array('call' => $call, 'weight' => $weight, 'context' => $context, 'once' => $once, 'subscriber' => $subscriber);
		return $this;
	}

	public function add_once($ename, $call, $weight = 0, $context = null) {
		return $this->add_listener($ename, $call, $weight, $context, true);
	}

	public function add_with_context($ename, $call, $context = null, $weight = 0, $once = false) {
		return $this->add_listener($ename, $call, $weight, $context , $once);
	}

	public function add_subscriber(Events_SubscriberInterface $s) {
		foreach ($s->get_events() as $ename => $list) {
			$this->subscribers[$ename] = array($s, $list);
		}
		return $this;
	}

	protected function convert_subscriber($ename) {
		list($s, $list) = $this->subscribers[$ename];
		if (is_array($list) && count($list) > 0 && !is_array($list[0]))
			$list = array($list);
		foreach ($list as $ldata) {
			$ldata = $this->covert_to_listener_data($s, $ldata);
			$this->add_listener($ename, $ldata['call'], $ldata['weight'], $ldata['context'], $ldata['once'], $s);
		}
		unset($this->subscribers[$ename]);
		return $this;
	}

	public function has_listeners($ename) {
		return !empty($this->listeners[$ename]) || !empty($this->subscribers[$ename]);
	}

	public function dispatch($ename, Events_Event $e, &$arg1 = null, &$arg2 = null, &$arg3 = null, &$arg4 = null, &$arg5 = null, &$arg6 = null) {
		if (!$this->has_listeners($ename)) return null;
		$e->set_name($ename);
		$this->event = $e;
		$fargs = func_get_args();
		$orig_args = array(&$arg1, &$arg2, &$arg3, &$arg4, &$arg5, &$arg6);
		$orig_args = array_slice($orig_args, 0, count($fargs) - 2);
		if (!empty($this->subscribers[$ename]))
			$this->convert_subscriber($ename);
		if (!$e->is_propagation_stopped() && !empty($this->listeners[$ename])) {
			$args = array_merge($orig_args, array($e));
			uasort($this->listeners[$ename], array($this, 'sort'));
			foreach ($this->listeners[$ename] as $k => $ldata) {
				if (!$this->process_context($e, $ldata))
					continue;
				$listener_res = Core::invoke($ldata['call'], $args);
				if ($ldata['once']) unset($this->listeners[$ename][$k]);
				if (!is_null($listener_res)) $e->set_last_result($listener_res);
				if ($e->is_propagation_stopped()) break;
			}

		}
		unset($this->event);
		if (!is_null($this->parent))
			return Core::invoke(array($this->parent, 'dispatch'), array_merge(array($ename, $e), $orig_args));
		return $e->get_last_result();
	}

	protected function process_context($e, $ldata) {
		switch(true) {
			case isset($ldata['context']):
				if ($e->is_with_context() && !Core::equals($ldata['context'], $e->get_context()))
					return false;
				if (!$e->is_with_context())
					return false;
				break;
			case isset($ldata['subscriber']):
				if ($e->is_with_context() && !$ldata['subscriber']->filter_by_context($e->get_context(), $e))
					return false;
				break;
			case $e->is_with_context():
				return false;
			default:
				if (!$e->is_with_context() && !is_null($ldata['context']))
					return false; 
		}
		return true;
	}

	protected function covert_to_listener_data($s, $data) {
		if (isset($data['call']))
			return $data;
		if (isset($data['method']))
			$data['call'] = array($s, $data['method']);
		if (isset($data[0])) {
			if (is_string($data[0]) && strpos($data[0], '::') === FALSE)
				$data['call'] = array($s, $data[0]);
			else
				$data['call'] = $data[0];
			if (isset($data[1])) $data['weight'] = $data[1];
			if (isset($data[2])) $data['context'] = $data[2];
			if (isset($data[3])) $data['once'] = $data[3];
		}
		return $data;
	}

	protected function sort($a, $b) {
		return $a['weight'] < $b['weight'] ? -1 : 1;
	}

}

class Events_Event implements Core_IndexedAccessInterface {

	protected $is_propagation_stopped = false;
	protected $data;
	protected $context;
	protected $name;
	protected $last_result = null;

	public function __construct($data = array(), $context = null) {
		$this->data = new ArrayObject($data);
		$this->context = $context;
	}

	public function stop_propagation() {
		$this->is_propagation_stopped = true;
		return $this;
	}

	public function is_propagation_stopped() {
		return $this->is_propagation_stopped;
	}

	public function get_last_result($default = null) {
		return is_null($this->last_result) ? $default : $this->last_result;
	}

	public function set_last_result($res) {
		$this->last_result = $res;
		return $this;
	}

	public function set_context($context) {
		$this->context = $context;
		return $this;
	}

	public function get_context() {
		return $this->context;
	}

	public function is_with_context() {
		return !is_null($this->context);
	}

	public function get_name() {
		return $this->name;
	}

	public function set_name($name) {
		$this->name = $name;
		return $this;
	}

	public function call($ename = null, &$arg1 = null, &$arg2 = null, &$arg3 = null, &$arg4 = null, &$arg5 = null, &$arg6 = null) {
		if (!is_null($ename)) $this->set_name($ename);
		$args = func_get_args();
		$num_args = count($args);
		$args = array_merge(array($this->get_name(), $this, &$arg1, &$arg2, &$arg3, &$arg4, &$arg5, &$arg6), array_slice($args,7));
		$args = array_slice(0, $num_args);
		return Core::invoke(array('Events', 'dispatch'), $args);
	}

	public function offsetGet($k) {
		return $this->data[$k];
	}

	public function offsetSet($k, $v) {
		$this->data[$k] = $v;
		return $this;
	}

	public function offsetExists($k) {
		return isset($this->data[$k]);
	}

	public function offsetUnset($k) {
		unset($this->data[$k]);
	}

	public function __call($m, $args) {
		$value = $args[0];
		if (is_null($value)) {
			if (method_exists($this, $method = 'get_' . $m))
				return $this->$method();
			return $this->offsetGet($m);
		}
		else {
			if (method_exists($this, $method = 'set_' . $m))
				return $this->$method($value);
			return $this->offsetSet($m, $value);
		}
		return $this;
	}
	
}

/*class Events_Listener {
	
}*/

interface Events_SubscriberInterface {
	public function get_events();
	public function filter_by_context($context, $event);
}

abstract class Events_SubscriberAbstract implements Events_SubscriberInterface {
	public function get_events() {
		return array();
	}
	public function filter_by_context($context, $event) {
		return true;
	}
}


class Events_Observer {

  protected $dispatcher;
  protected $enable_dispatch = true;

  public function __construct() {
    $this->dispatcher = new Events_Dispatcher();
  }

  public function get_dispatcher() {
    return $this->dispatcher;
  }

  public function enable_dispatch() {
    $this->enable_dispatch = true;
    return $this;
  }

  public function disable_dispatch() {
    $this->enable_dispatch = false;
    return $this;
  }

  //TODO: To Events_Dispatcher
  public function dispatch($name, $e = null, &$arg1 = null, &$arg2 = null, &$arg3 = null, &$arg4 = null, &$arg5 = null, &$arg6 = null) {
    if (!$this->enable_dispatch) return;
    if (!$this->dispatcher->has_listeners($name)) return;
    if (is_null($e)) $e = Events::Event();
    $args = array($name, $e, &$arg1, &$arg2, &$arg3, &$arg4, &$arg5, &$arg6);
    $fargs = func_get_args();
    $num_args = count($fargs);
    if ($num_args == 1) $num_args = 2;
    $e->obj = $this;
    return Core::invoke(array($this->dispatcher, 'dispatch'), array_slice($args, 0, $num_args));
  }

  public function dispatch_res($name, $default = null, $e = null, &$arg1 = null, &$arg2 = null, &$arg3 = null, &$arg4 = null, &$arg5 = null, &$arg6 = null) {
    if (!$this->enable_dispatch) return $default;
    if (!$this->dispatcher->has_listeners($name)) return $default;
    $args = array($name, $e, &$arg1, &$arg2, &$arg3, &$arg4, &$arg5, &$arg6);
    $fargs = func_get_args();
    $num_args = count($fargs);
    if ($num_args > 1) $num_args--;
    $res = Core::invoke(array($this, 'dispatch'), array_slice($args, 0, $num_args));
    if (is_null($res)) return $default;
    return $res;
  }

}


class Events_Subscriber extends Events_SubscriberAbstract {

  protected $events = array();

  public function __construct() {
    $this->autodiscover();
  }

  public function autodiscover() {
    $ref = new ReflectionClass($this);
    $methods = $ref->getMethods(ReflectionMethod::IS_PUBLIC);
    $class = get_class($this);
    foreach ($methods as $m) {
      $name = $m->name;
      if ($m->getDeclaringClass()->name != 'Events_Subscriber') {
        $this->events[$name] = array($name);
      }
    }
  }

  public function get_events() {
      return $this->events;
  }

}
