<?php
//print_r(debug_backtrace());die;
/// <module name="CMS.Navigation" maintainer="gusev@techart.ru" version="0.0.0">

Core::load('WebKit.Navigation');

/// <class name="CMS.Navigation" stereotype="module">
///   <implements interface="Core.ModuleInterface" />
///   <implements interface="ArrayAccess" />
///   <depends supplier="CMS.Navigation.LinkSet" stereotype="creates" />
class CMS_Navigation 
  implements Core_ModuleInterface, ArrayAccess {

///   <constants>
  	const MODULE  = 'CMS.Navigation';
	const VERSION = '0.0.0';
///   </constants>
	
	static $var	= 'navigation';
	protected $sets = array();
	protected $flags = array();
	protected $route_extra = array();
	
	static $uri;
	static $tpl_path = '../app/views/navigation';

///   <method name="__construct">
///     <body>
	public function __construct() {
	}
///     </body>
///   </method>

///   </protocol>


/// <protocol name="accessing">

///   <method name="flag">
///     <args>
///       <arg name="name" type="string" />
///       <arg name="value" type="mixed" default="true" />
///     </args>
///     <body>
	public function flag($name,$value=true) {
		$this->flags[$name] = $value;
		foreach($this->sets as $set) $set->switch_flags($name);
	}
///     </body>
///   </method>
	
///   <method name="is_flag" returns="boolean">
///     <args>
///       <arg name="name" type="string" />
///       <arg name="value" type="mixed" default="true" />
///     </args>
///     <body>
	public function is_flag($name,$value=true) {
		if (!isset($this->flags[$name])) return false;
		return $this->flags[$name] === $value;
	}
///     </body>
///   </method>

///   <method name="admin" returns="mixed">
///     <body>
	public function admin() {
		if (!isset($this->sets['admin'])) {
			$this->sets['admin'] = new CMS_Navigation_LinkSet();
		}
		CMS_Admin::build_embedded_admin_menu($this->sets['admin']);
		return $this->sets['admin'];
	}
///     </body>
///   </method>
	
///   <method name="offsetExists" returns="boolean">
///     <args>
///       <arg name="key" type="string" />
///     </args>
///     <body>
	public function offsetExists($key) {
		return isset($this->sets[$key]);
	}
///     </body>
///   </method>
	
///   <method name="offsetGet" returns="mixed">
///     <args>
///       <arg name="key" type="string" />
///     </args>
///     <body>
	public function offsetGet($key) {
		return $this->sets[$key];
	}
///     </body>
///   </method>
	
///   <method name="offsetSet">
///     <args>
///       <arg name="key" type="string" />
///       <arg name="value" type="mixed" />
///     </args>
///     <body>
	public function offsetSet($key,$value) {
		$this->sets[$key] = $value;
	}
///     </body>
///   </method>
	
///   <method name="offsetUnset">
///     <args>
///       <arg name="key" type="string" />
///     </args>
///     <body>
	public function offsetUnset($key) {
		unset($this->sets[$key]);
	}
///     </body>
///   </method>
	
///   <method name="level" returns="CMS.Navigation.LinkSet">
///     <args>
///       <arg name="number" type="int" />
///     </args>
///     <body>
	public function level($n) {
		return $this->sets[':default']->level($n);
	}
///     </body>
///   </method>
	
///   <method name="last_level" returns="CMS.Navigation.LinkSet">
///     <body>
	public function last_level($n=0) {
		return $this->sets[':default']->last_level($n);
	}
///     </body>
///   </method>
	
///   <method name="count" returns="int">
///     <body>
	public function count() {
		return $this->sets[':default']->count();
	}
///     </body>
///   </method>
	
///   <method name="link_by_id" returns="WebKit.Navigation.Link">
///     <args>
///       <arg name="id" type="string" />
///     </args>
///     <body>
	public function link_by_id($id) {
		return $this->sets[':default']->link_by_id($id);
	}
///     </body>
///   </method>
	
///   <method name="linkset_by_id" returns="CMS.Navigation.LinkSet">
///     <args>
///       <arg name="id" type="string" />
///     </args>
///     <body>
	public function linkset_by_id($id) {
		return $this->sets[':default']->linkset_by_id($id);
	}
///     </body>
///   </method>
	
///   <method name="selected_link" returns="WebKit.Navigation.Link">
///     <body>
	public function selected_link() {
		return $this->sets[':default']->selected_link();
	}
///     </body>
///   </method>
	
///   <method name="current_link" returns="WebKit.Navigation.Link">
///     <body>
	public function current_link() {
		return $this->sets[':default']->current_link();
	}
///     </body>
///   </method>
	
///   <method name="route" returns="CMS.Navigation.LinkSet">
///     <body>
	public function route() {
		$route = $this->sets[':default']->route();
		if (!$route&&sizeof($this->route_extra)==0) return false;
		if (!$route) $route = new CMS_Navigation_LinkSet();
		$n = $route->count();
		foreach($this->route_extra as $r) {
			$item = $r[1];
			$item['id'] = $n;
			$n++;
			$route->add($r[0],$item);
		}	
		return $route;
	}
///     </body>
///   </method>
	
///   <method name="filter" returns="CMS.Navigation.LinkSet" varargs="true">
///     <body>
	public function filter() {
		$args = func_get_args();
		$r = Core_Types::reflection_for($this->sets[':default']);
		$m = $r->getMethod('filter');
		return $m->invokeArgs($this->sets[':default'],$args);
	}	
///     </body>
///   </method>
	
	
/// </protocol>
	

/// <protocol name="performing">

///   <method name="process">
///     <args>
///       <arg name="uri" type="string" />
///     </args>
///     <body>
	public function process($uri) {
		if ($m = Core_Regexps::match_with_results('{^([^\?]+)\?}',$uri)) $uri = trim($m[1]);
		self::$uri = $uri;
		$this->sets[':default'] = new CMS_Navigation_LinkSet();
		$struct = self::struct();
        
		if (is_array($struct)) {
			foreach(array_keys($struct) as $key) {
				if (preg_match('/^set:(.+)/',$key,$m)) {
					$set = trim($m[1]);
					if (is_array($struct[$key])) {
						if (!isset($this->sets[$set])) $this->sets[$set] = new CMS_Navigation_LinkSet();
						$this->sets[$set]->process($struct[$key]);
					}	
					unset($struct[$key]);
				}
			}
			$this->sets[':default']->process($struct);
		}
	}
///     </body>
///   </method>
	
///   <method name="draw" returns="string">
///     <args>
///       <arg name="template" type="string" default="simple" />
///       <arg name="parms" type="array" default="array()" />
///     </args>
///     <body>
	public function draw($template='simple',$parms=array()) {
		return $this->sets[':default']->draw($template,$parms);
	}
///     </body>
///   </method>
	
///   <method name="add" returns="CMS.Navigation.LinkSet">
///     <args>
///       <arg name="title" type="string" />
///       <arg name="item" type="string|array" />
///     </args>
///     <body>
	public function add($title,$item) {
		return $this->sets[':default']->add($title,$item);
	}
///     </body>
///   </method>
	
///   <method name="add_to_route">
///     <args>
///       <arg name="title" type="string" />
///       <arg name="item" type="string|array" />
///     </args>
///     <body>
	public function add_to_route($title,$item) {
		if (is_string($item)) $item = array('url' => $item);
		$this->route_extra[] = array($title,$item);
	}
///     </body>
///   </method>
	

/// </protocol>

/// <protocol name="creating">

	public function setup_selected() {
	}
	
/// </protocol>
	
	
/// <protocol name="supporting">

///   <method name="struct" returns="array">
///     <body>
	protected function struct() {
		if (is_callable(CMS::$navigation_struct)) return call_user_func(CMS::$navigation_struct);
		if (is_string(self::$var)) return CMS::vars()->get(self::$var);
		return array();
	}
///     </body>
///   </method>
	
/// </protocol>
	

}
/// </class>

/// <class name="CMS.Navigation.LinkSet" extends="WebKit.Navigation.LinkSet">
class CMS_Navigation_LinkSet extends WebKit_Navigation_LinkSet {

	protected $level_num = 0;

///   <protocol name="creating">

///   <method name="__construct">
///     <body>
	public function __construct() {
		$this->links = new ArrayObject();
	}
///     </body>
///   </method>

///   </protocol>




/// <protocol name="accessing">

///   <method name="count" returns="int">
///     <body>
	public function count() {
	   	return sizeof($this->links);
	}
///     </body>
///   </method>

///   <method name="sublinks" returns="int">
///     <body>
	public function sublinks() {
		return $this->links;
	}
///     </body>
///   </method>


///   <method name="route" returns="CMS.Navigation.LinkSet">
///     <body>
	public function route($n=0) {
		$link = $this->selected_link();
		if (!$link) return false;
		$route = false;
		if ($link->sublinks) $route = $link->sublinks->route($n+1);
		if (!$route) $route = new CMS_Navigation_LinkSet();
		$route->links[$n] = $link;
		return $route;
	}
///     </body>
///   </method>
	
///   <method name="level" returns="CMS.Navigation.LinkSet">
///     <args>
///       <arg name="number" type="int" />
///     </args>
///     <body>
	public function level($n) {
		if ($n<1) return $this;
		if (sizeof($this->links)>0) {
			foreach($this->links as $link) {
				if ($link->is_selected()) {
					$rc = $link->sublinks? $link->sublinks->level($n-1) : false;
					if ($rc&&$rc->links) {
						if ($rc->links->count()==0) return false;
					}
					return $rc;
				}
			}
		}
		return false;
	}
///     </body>
///   </method>
	
///   <method name="selected_link" returns="WebKit.Navigation.Link">
///     <body>
	public function selected_link() {
		foreach($this->links as $link) {
			if ($link->is_selected()) {
				return $link;
			}	
		}
		return false;
	}
///     </body>
///   </method>
	
///   <method name="current_link" returns="WebKit.Navigation.Link">
///     <body>
	public function current_link($parent=false) {
		$sl = $this->selected_link();
		if (!$sl) return false;
		$sl['parent'] = $parent;
		if ($sl->sublinks) {
			$sl2 = $sl->sublinks->current_link($sl);
			if ($sl2) {
				return $sl2;
			}	
		}
		$sl['container'] = $this;
		return $sl;
	}
///     </body>
///   </method>
	
///   <method name="link_by_id" returns="WebKit.Navigation.Link">
///     <args>
///       <arg name="id" type="string" />
///     </args>
///     <body>
	public function link_by_id($id) {
		if (isset($this->links[$id])) {
			return $this->links[$id];
		}
		
		foreach($this->links as $link) {
			if ($link->sublinks) {
				$l = $link->sublinks->link_by_id($id);
				if ($l) return $l;
			}
		}
		
		return false;
	}
///     </body>
///   </method>
	
///   <method name="linkset_by_id" returns="CMS.Navigation.LinkSet">
///     <args>
///       <arg name="id" type="string" />
///     </args>
///     <body>
	public function linkset_by_id($id) {
		$link = $this->link_by_id($id);
		if (!$link) return false;
		if (!$link->sublinks) $link->sublinks = new CMS_Navigation_LinkSet();
		return $link->sublinks;
	}
///     </body>
///   </method>
	
	
///   <method name="filter" returns="CMS.Navigation.LinkSet" varargs="true">
///     <body>
	public function filter() {
		$args = func_get_args();
		$out = new CMS_Navigation_LinkSet();
		foreach($this->links as $link) {
			$valid = true;
			foreach($args as $arg) {
				if (is_string($arg)&&$arg[0]=='!') {
					$arg = substr($arg,1);
					if ($link[$arg]) $valid = false;
				}
				else if (!$link[$arg]) $valid = false;
			}	
			if ($valid) $out->links[] = $link;
		}
		return $out;
	}
///     </body>
///   </method>
	
///   <method name="reverse" returns="CMS.Navigation.LinkSet">
///     <body>
	public function reverse() {
		$out = new CMS_Navigation_LinkSet();
		$rev = array_reverse((array)$this->links);
		foreach($rev as $link) {
			$out->links[] = $link;
		}
		return $out;
	}
///     </body>
///   </method>
	
/// </protocol>
	
	
/// <protocol name="performing">

///   <method name="process">
///     <args>
///       <arg name="data" type="iterable" />
///     </args>
///     <body>
	public function process($data) {
		foreach($data as $title => $item) {
			if (is_string($item)&&trim($item)==''&&$m = Core_Regexps::match_with_results('{^\%(.+)$}',trim($title))) {
				$_component = trim($m[1]);
				$_parms = false;
				if ($m = Core_Regexps::match_with_results('{^([^\s]+)\s+(.+)$}',$_component)) {
					$_component = $m[1];
					$_parms = trim($m[2]);
				}
				if (CMS::component_exists($_component)) {
					$_class = CMS::$component_names[$_component];
					$_classref = Core_Types::reflection_for($_class);
					$links = $_classref->hasMethod('navigation_tree')? $_classref->getMethod('navigation_tree')->invokeArgs(NULL,array($_parms)) : array();
					foreach($links as $k => $v) {
						if (is_string($v)) $v = array('url' => $v);
						$v["from-$_component"] = 1;
						$links[$k] = $v;
					}
					$this->process($links);
				}
				
			}
			else $this->add($title,$item);
		}
	}
///     </body>
///   </method>
	
///   <method name="add" returns="CMS.Navigation.LinkSet">
///     <args>
///       <arg name="title" type="string" />
///       <arg name="item" type="string|array" />
///     </args>
///     <body>
	public function add($title,$item) {
		if (!Core_Types::is_iterable($item)) $item = array('uri' => $item, 'url' => $item);
		if (isset($item['title'])) {
			$title = $item['title'];
		}
		$title = CMS::lang($title);
		
		
		
		//Events::dispatch('cms.navigation.add', $ev = Events::Event(array('title' => $title, 'data' => $item, 'url' => $item['url'])));
		//$title = $ev['title'];
		//$item = $ev['data'];
		//$item['url'] = $ev['url'];
		
		$url = $item['url'];
		Events::call('cms.navigation.add',$title,$item,$url);
		$item['url'] = $url;
		
		$access = isset($item['access'])?trim($item['access']):'';
		if ($access!=''&&!CMS::check_globals_or($access)) return $this;

		if (isset($item['disabled'])) if (CMS::check_yes($item['disabled'])) return $this;
		
		$uri = '';
		if (isset($item['uri'])) $uri = $item['uri'];
		if (isset($item['url'])) $uri = $item['url'];
		$id = isset($item['id'])? $item['id'] : md5($title.$uri);
		if (isset($item['navigation_id'])) $id = trim($item['navigation_id']);
		$selected = false;
		$disabled = false;
		if (isset($item['match'])) {
			if (preg_match($item['match'],CMS_Navigation::$uri)) {
				$selected = true;
			}	
		}
		if (isset($item['flag'])) {
			if (CMS::$navigation->is_flag($item['flag'])) $selected = true;
		}
		if ($uri==CMS_Navigation::$uri) $selected = true;
		$item['selected'] = $selected;
		$item['disabled'] = $disabled;
		
		$sub = isset($item['sub'])?$item['sub']:null;
		
		if (is_string($sub)) {
			$sub = trim($sub);
			$_component = $sub;
			$_parms = $uri;
			if ($m = Core_Regexps::match_with_results('{^([^\s]+)\s+(.+)$}',$sub)) {
				$_component = trim($m[1]);
				$_parms = trim($m[2]);
			}
			if (CMS::component_exists($_component)) {
				$_class = CMS::$component_names[$_component];
				$_classref = Core_Types::reflection_for($_class);
				$sub = $_classref->hasMethod('navigation_tree')? $_classref->getMethod('navigation_tree')->invokeArgs(NULL,array($_parms,$item)) : false;
			}
		}
		
		if (Core_Types::is_iterable($sub)) {
			$set = new CMS_Navigation_LinkSet();
			$set->level_num = $this->level_num+1;
			$set->process($sub);
			$this->link($id,$uri,$title,$item,$set);
		}
		else {
			$this->link($id,$uri,$title,$item);
		}	
		
		return $this;
	}
///     </body>
///   </method>
	
///   <method name="draw" returns="string">
///     <args>
///       <arg name="template" type="string" default="simple" />
///       <arg name="parms" type="array" default="array()" />
///     </args>
///     <body>
	public function draw($template='simple',$parms=array()) {
		ob_start();
		$filename1 = CMS_Navigation::$tpl_path."/$template.phtml";
		$filename2 = CMS::view("navigation/$template.phtml");
		if (IO_FS::exists($filename1)) {
			include($filename1);
		}
		else if (IO_FS::exists($filename2)) {
			include($filename2);
		}
		return ob_get_clean();
	}
///     </body>
///   </method>
	
	

/// </protocol>
	

	
/// <protocol name="supporting">

///   <method name="switch_flags">
///     <args>
///       <arg name="noforce" type="boolean" default="false" />
///     </args>
///     <body>
	public function switch_flags($v=false) {
		if (sizeof($this->links)>0) {
			foreach($this->links as $link) {
				$flag =  trim($link['flag']);
				if ($flag!='') if ((!$v||($flag==$v))) {
					if (CMS::navigation()->is_flag($flag)) $link->select(); else $link->deselect();
				}
				
				if ($link->sublinks) $link->sublinks->switch_flags($v);
			}
		}
	}
///     </body>
///   </method>

	
///   <method name="last_level" returns="CMS.Navigation.LinkSet">
///     <args>
///       <arg name="nunber" type="int" />
///     </args>
///     <body>
	public function last_level($n) {
		$levels = array();
		$levels[0] = $this;
		$c = 1;
		$link = $this->selected_link();
		if (!$link) return false;
		while ($link) {
			if (!$link->sublinks) break;
			$sl = $link->sublinks->selected_link();
			if (!$sl) break;
			$levels[$c] = $link->sublinks;
			$link = $sl;
			$c++;
		}
		if (sizeof($levels)<$n+1) return false;
		return $levels[sizeof($levels)-1-$n];
	}
///     </body>
///   </method>
	
	
	
	
/// </protocol>
	


}
/// </class>

/// </module>

