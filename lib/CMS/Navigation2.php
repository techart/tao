<?php
/// <module name="CMS.Navigation2" maintainer="gusev@techart.ru" version="0.0.0">

/// <class name="CMS.Navigation2" stereotype="module">
///   <implements interface="Core.ModuleInterface" />
///   <implements interface="ArrayAccess" />
class CMS_Navigation2
  implements Core_ModuleInterface, ArrayAccess {

///   <constants>
  	const MODULE  = 'CMS.Navigation2';
	const VERSION = '0.0.0';
///   </constants>
	
	static $var	= 'navigation';
	static $important_flags = false;
	static $important_matches = false;

	protected $sets;
	protected $flags;
	protected $route_extra;
	
	static $uri;
	static $tpl_path = '../app/views/navigation';

	static function initialize($config=array()) {
		foreach($config as $key => $value) self::$$key = $value;
	}


///   <method name="__construct">
///     <body>
	public function __construct() {
		$this->sets = new ArrayObject();
		$this->flags = new ArrayObject();
		$this->route_extra = new ArrayObject();
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
			$this->sets['admin'] = new CMS_Navigation_Node();
		}
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
	public function reverse() {
		return $this->sets[':default']->reverse();
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
		if (!$route) $route = new CMS_Navigation_Node();
		$route = $route->reverse();
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
		$this->sets[':default'] = new CMS_Navigation_Node();
		$struct = self::struct();
        
		if (is_array($struct)) {
			foreach(array_keys($struct) as $key) {
				if (preg_match('/^set:(.+)/',$key,$m)) {
					$set = trim($m[1]);
					if (is_array($struct[$key])) {
						if (!isset($this->sets[$set])) $this->sets[$set] = new CMS_Navigation_Node();
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









class CMS_Navigation_Node extends ArrayObject {

	static $idc = 0;
	static $idcache;
	protected $sub_checked = false;
	protected $selected_cache = 0;
	public $level_num = 0;

	public function __construct($title=false,$data=false) {
		if (isset($data['title'])) {
			$title = trim($data['title']);
			$data['title'] = CMS::lang($title);
		}
		
		else {
			$title = CMS::lang($title);
		}
		$this['sub'] = false;
		$this['title'] = $title;
		
		if (is_string($data)) $this['url'] = $data;
		else if ($data) foreach($data as $key => $value) $this[$key] = $value;
		if (!isset($this['url'])&&isset($this['uri'])) $this['url'] = $this['uri'];
		if (!isset($this['id'])) {
			self::$idc++;
			$this['id'] = '__def'.self::$idc;
		}
		if (isset($this['navigation_id'])) $this['id'] = trim($this['navigation_id']);
	}

	public function __get($name) {
		switch($name) {
			case 'url':
			case 'id':
			case 'title':
				return $this[$name]; break;
			case 'sublinks':
				return  ($rc = $this->sub())?$rc:new ArrayObject();
				break;
			case 'selected':
				return $this->is_selected();
				break;
		}
	}

	public function sublinks() {
		return $this->sublinks;
	}

	public function link_by_id($id,$recursive=true) {
		if (!$recursive) return $this->find_link_by_id($id,false);
		if (isset(self::$idcache[$id])) return self::$idcache[$id];
		$link = $this->find_link_by_id($id,$recursive);
		if ($link) self::$idcache[$id] = $link;
		return $link;
	}

	protected function find_link_by_id($id,$recursive=true) {
		if ($this['id']==$id) return $this;
		if ($this->sub()) {
			foreach($this->sub() as $link) if ($link['id']==$id) return $link;
			if ($recursive)
				foreach($this->sub() as $link) if ($rc = $link->link_by_id($id)) return $rc;
		}
		return false;
	}

	public function linkset_by_id($id) {
		return $this->link_by_id($id);
	}

	public function selected_link() {
		if ($this->sub()) 
			foreach($this->sub() as $link)
				if ($link->is_selected()) return $link;
		return false;
	}

	public function current_link() {
		$link = $this->selected_link();
		if (!$link) return false;
		if ($this->sub())
			foreach($this->sub() as $slink)
				if ($rc = $slink->current_link()) return $rc;
		return $link;
	}

	public function route($n=0) {
		$link = $this->selected_link();
		if (!$link) return false;
		$route = $link->route($n+1);
		if (!$route) $route = new CMS_Navigation_Node();
		$route['sub'][$n] = $link;
		return $route;
	}

	public function is_selected() {
		if ($this->selected_cache<0) return false;
		if ($this->selected_cache>0) return true;

		$sel = $this->check_selected();
		$this->selected_cache = $sel? 1 : -1;
		return $sel;
	}

	public function select() {
		$this->selected_cache = 1;
	}

	protected function check_selected() {
		if (CMS_Navigation2::$uri==$this->url) return true;

		if (isset($this['flag'])) {
			if (CMS::$navigation->is_flag($this['flag'])) return true;
			if (CMS_Navigation2::$important_flags) return false;
		}

		if (isset($this['match'])) {
			if (preg_match($this['match'],CMS_Navigation2::$uri)) return true;
			if (CMS_Navigation2::$important_matches) return false;
		}

		//if (isset($this['flag'])&&CMS::$navigation->is_flag($this['flag'])) return true;
		//if (isset($this['flag'])) return CMS::$navigation->is_flag($this['flag']);
		//if (isset($this['match'])&&preg_match($this['match'],CMS_Navigation2::$uri)) return true;
		if ($this->sub()) foreach($this->sub() as $link) if ($link->is_selected()) return true;
		return false;
	}

	public function sub() {
		if ($this->sub_checked) return $this['sub'];
		if (!$this['sub']) return false;
		$sub = $this['sub'];
		if (is_string($sub)) {
			$sub = trim($sub);
			$_component = $sub;
			$_parms = $this['url'];
			if ($m = Core_Regexps::match_with_results('{^([^\s]+)\s+(.+)$}',$sub)) {
				$_component = trim($m[1]);
				$_parms = trim($m[2]);
			}
			if (CMS::component_exists($_component)) {
				$_class = CMS::$component_names[$_component];
				$_classref = Core_Types::reflection_for($_class);
				$sub = $_classref->hasMethod('navigation_tree')? $_classref->getMethod('navigation_tree')->invokeArgs(NULL,array($_parms,$this)) : false;
			}
		}
		$_sub = false;
		if ($sub) {
			$_sub = new ArrayObject();
			foreach($sub as $key => $item) {
				if ($item instanceof CMS_Navigation_Node) {
					$_sub[$key] = $item;
				}
				else if ((int)$item['disabled']==0) {
					$_sub[$key] = new CMS_Navigation_Node($key,$item);
				}
				$_sub[$key]->level_num = $this->level_num+1;
			}
		}
		$this['sub'] = $_sub;
		$this->sub_checked = true;
		return $this['sub'];
	}

	public function count() {
		if (!$this->sub()) return 0;
	    	return sizeof($this->sub());
	}

	public function add($title,$item) {
		if (isset($item['title'])) {
			$title = trim($item['title']);
			$item['title'] = CMS::lang($title);
		}
		$title = CMS::lang($title);
		if (!$this['sub']) $this['sub'] = new ArrayObject();
		if ($item instanceof CMS_Navigation_Node) $this['sub'][$title] = $item;
		else {
			if (is_string($item)) $item = array('url'=>$item);
			$url = $item['url'];
			Events::call('cms.navigation.add',$title,$item,$url);
			$item['url'] = $url;
			if (!isset($item['disabled'])||(!$item['disabled']&&CMS::check_no($item['disabled']))) {
				$this['sub'][$title] = new CMS_Navigation_Node($title,$item);
			}
		}
	}

	public function level($n) {
		if ($n<1) return $this;
		if ($this->sub()) {
			foreach($this->sub() as $title => $link) {
				if ($link->is_selected()) {
					$rc = $link->level($n-1);
					if (!$rc->sub()) return false;
					return $rc;
				}
			}
		}
		return false;
	}

	public function filter() {
		$args = func_get_args();
		$out = new CMS_Navigation_Node();
		$out['sub'] = new ArrayObject();
		if ($this->sub()) foreach($this->sub() as $link) {
			$valid = true;
			foreach($args as $arg) {
				if (is_string($arg)&&$arg[0]=='!') {
					$arg = substr($arg,1);
					if ($link[$arg]) $valid = false;
				}
				else if (!$link[$arg]) $valid = false;
			}
			if ($valid) $out['sub'][] = $link;
		}
		return $out;
	}

	public function reverse() {
		if (!$this->sub()) return $this;
		$out = new CMS_Navigation_Node();
		$rev = array_reverse((array)$this->sub());
		foreach($rev as $link) $out['sub'][] = $link;
		return $out;
	}

	public function switch_flags() {
	}

	public function last_level() {
		$link = $this->selected_link();
		if (!$link) return $this;
		if (!$link->sub()) return $this;
		return $link->last_level();
	}


	public function draw($template='simple',$parms=array()) {
		//var_dump($this);
		$this->links = $this->sub();
		if (!$this->links) $this->links = new ArrayObject();
		ob_start();
		$filename1 = CMS_Navigation2::$tpl_path."/$template.phtml";
		$filename2 = CMS::view("navigation/$template.phtml");
		if (IO_FS::exists($filename1)) {
			include($filename1);
		}
		else if (IO_FS::exists($filename2)) {
			include($filename2);
		}
		return ob_get_clean();
	}


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


}





/// </module>

