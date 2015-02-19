<?php
/**
 * Работа с навигацией сайта
 *
 * Очередная попытка ускорить/улучшить работу навигации.
 *
 * @author  Svistunov <svistunov@techart.ru>
 *
 * @package Navigation
 */

/**
 * Класс модуля
 *
 * Реализует набор фабричных методов для создания объектов
 * и несколько вспомогательных методов.
 *
 * @package Navigation
 */
class Navigation implements Core_ModuleInterface
{

	const VERSION = '0.0.1';

	static protected $options = array(
		'navigation_set_class' => 'Navigation.Set',
		'navigation_set_state_class' => 'Navigation.SetState',
		'navigation_link_class' => 'Navigation.Link',
		'navigation_controller_class' => 'Navigation.Controller'
	);

	protected static $current_controller = null;
	protected static $layout = null;

	static public function initialize($conf)
	{
		self::$options = array_merge(self::$options, $conf);
	}

	static public function layout($layout = null)
	{
		if (!is_null($layout)) {
			self::$layout = $layout;
		}
		return self::$layout;
	}

	static public function option($name, $value = null)
	{
		if (is_null($value)) {
			return self::$options[$name];
		} else {
			return self::$options[$name] = $value;
		}
	}

	public static function get_current_controller()
	{
		return self::$current_controller;
	}

	public function set_current_controller(Navigation_Controller $c)
	{
		return self::$current_controller = $c;
	}

	public static function controller()
	{
		return self::$current_controller ? self::$current_controller : self::$current_controller = Core::make(self::option('navigation_controller_class'));
	}

	public static function Set()
	{
		return Core::make(self::option('navigation_set_class'));
	}

	public static function SetState(Navigation_Set $set)
	{
		return Core::make(self::option('navigation_set_state_class'), $set);
	}

	public static function Link($values = array())
	{
		return Core::make(self::option('navigation_link_class'), $values);
	}

	public static function draw($template_name, $parms = array())
	{
		$path = 'navigation/' . $template_name;
		if (self::layout()) {
			return self::layout()->root
				->option('links', $parms['links'])
				->option('level_num', $parms['level_num'])
				->partial($path, $parms);
		} else {
			return Templates_HTML::Template($path)
				->with($parms)
				->option('links', $parms['links'])
				->option('level_num', $parms['level_num'])
				->as_string();
		}
	}

}

interface Navigation_SetInterface
{
}

/**
 * Класс для загрузки и хранения данных (ссылок)
 *
 * @package Navigation
 */
class Navigation_Set implements Navigation_SetInterface
{

	protected $uri;
	protected $flags = array();
	protected $root;
	protected $current_link = false;
	protected $search_urls = array();
	protected $current_path = array();
	protected $items_by_ids = array();
	protected $to_route = array();

	public function __construct()
	{
		$this->setup();
	}

	protected function setup()
	{
		$this->root = Navigation::Link(array('sublinks' => array(), 'root' => true, 'level' => 0));
	}

	public function set_root($links)
	{
		$this->root->sublinks = $links;
		return $this;
	}

	public function __clone()
	{
		$this->setup();
	}

	public function clone_to($links)
	{
		$new = clone $this;
		$new->set_root($links);
		//TODO: ?? Look araound $this->flags, $this->items_by_ids
		return $new;
	}

	public function __destruct()
	{
		unset($this->root);
		unset($this->flags);
		unset($this->items_by_ids);
	}

	public function process($uri, $data)
	{
		//$this->data = $data;
		$this->uri = $uri;
		$this->search_urls = array($uri);
		Events::call('tao.navigation.search_urls', $this->search_urls);
		$this->load_data($data);
		return $this;
	}

	public function flag($name)
	{
		if (is_array($this->flags[$name])) {
			array_walk($this->flags[$name], array($this, 'set_current_link'));
		}
		return $this;
	}

	public function current_link()
	{
		return $this->current_link;
	}

	public function current_path($link = null)
	{
		if (empty($link) && !empty($this->current_path)) {
			return $this->current_path;
		}
		$link = empty($link) ? $this->current_link : $link;
		$this->current_path[] = $link;
		if (!empty($link->parent)) {
			$this->current_path($link->parent);
		}
		return $this->current_path;
	}

	public function link_by_id($id)
	{
		return $this->items_by_ids[$id];
	}

	public function linkset_by_id($id)
	{
		$link = $this->link_by_id($id);
		return $link ? $link->sublinks : null;
	}

	public function is_flag($name, $value = true)
	{
		return is_array($this->flags[$name]) && $this->flags[$name][0]->selected === $value;
	}

	protected function set_current_link($link = null)
	{
		if (!empty($link)) {
			if (empty($this->current_link)) {
				$this->current_link = $link;
			}
			$link->selected = true;
			if (!empty($link->parent)) {
				$this->set_current_link($link->parent);
			}
		}
	}

	public function add($title, $item, $parent = null)
	{
		$this->load_data(array($title => $item), 0, $parent);
		return $this;
	}

	public function load_data($data, $level = 0, $parent = null)
	{
		//TODO: cache
		if (is_null($parent)) {
			$parent = $this->root;
		}
		foreach ($data as $title => $item) {
			if (!$item instanceof Navigation_Link) {
				$link = $this->read_item($title, $item);
			}
			if (count($link->sublinks_array) > 0) {
				$this->load_data($link->sublinks_array, $level + 1, $link);
			}
			//if (isset($this->items_by_ulrs[$link->url])) continue;//????????
			$this->add_item($link, $level, $parent);
		}
		return $this;
	}

	public function read_item($title, $item)
	{
		$title = trim((string) $title);
		if (is_string($item)) {
			$item = array('url' => trim($item));
		}
		$item['title'] = $title;
		$url = !empty($item['url']) ? $item['url'] : $item['uri'];
		$item['url'] = $item['uri'] = $url;
		if (isset($item['sub'])) {
			$sublinks = $item['sub'];
			unset($item['sub']);
		} else {
			$sublinks = array();
		}
		$item['sublinks'] = array();
		foreach ($sublinks as $key => $value) {
			$title = is_string($key) ? $key : $value['title'];
			$item['sublinks'][$title] = $value;
		}
		if (!isset($item['level'])) {
			$item['level'] = 0;
		}
		$link = Navigation::Link($item);
		return $link;
	}

	public function check_current_link($link)
	{
		foreach ($this->search_urls as $uri) {
			if ($link->url == $uri || (isset($link->match) && preg_match($link->match, $uri))) {
				return true;
			}
		}
		return false;
	}

	public function add_item($link, $level, $parent = null)
	{
		//$this->items_by_ulrs[$link->url] = true;
		if (is_null($parent)) {
			$parent = $this->root;
		}
		if (!$link) {
			return;
		}
		$link->level = $level;
		if ($link->disabled) {
			return $this;
		}
		if ($parent) {
			$parent->add_sublink($link->title, $link);
			$link->parent = $parent;
			if ($link->selected) {
				$this->set_current_link($parent);
			}
		}
		if (isset($link->id)) {
			$this->items_by_ids[$link->id] = $link;
		}
		if ($this->check_current_link($link)) {
			$this->set_current_link($link);
		}

		if (isset($link->flag)) {
			$this->flags[$link->flag][] = $link;
		}
	}

	public function __get($name)
	{
		if (property_exists($this, $name)) {
			return $this->$name;
		}
		return null;
	}

	public function add_to_route($title, $item)
	{
		$this->to_route[] = $this->read_item($title, $item);
		return $this;
	}

}

/**
 * Класс представляющий собой ссылку
 *
 * @package Navigation
 */
class Navigation_Link extends stdClass implements IteratorAggregate
{

	protected $sublinks;

	public function __construct($values = array())
	{
		foreach ($values as $k => $v)
			$this->$k = $v;
		$this->sublinks = new ArrayObject($this->sublinks ? $this->sublinks : array());
	}

	public function is_selected()
	{
		return $this->selected;
	}

	public function option($name)
	{
		return $this->$name;
	}

	public function sublinks()
	{
		if (count($this->sublinks)) {
			return Navigation::SetState(Navigation::get_current_controller()->get_current_set()->clone_to($this->sublinks));
		}
		return null;
	}

	public function add_sublink($title, $link)
	{
		$this->sublinks[$title] = $link;
		return $this;
	}

	public function __get($name)
	{
		switch ($name) {
			case 'sublinks':
				return $this->sublinks();
			case 'sublinks_array':
				return $this->sublinks;
			default:
				return null;
		}
	}

	public function __set($name, $value)
	{
		$this->$name = $value;
		return $this;
	}

	public function __unset($name)
	{
		return $this->$name = null;
	}

	public function __isset($name)
	{
		return isset($this->$name);
	}

	public function draw($template_name = 'simple', $params = array())
	{
		return Navigation::draw($template_name, array_merge(
				array('links' => new ArrayObject(array($this)), 'level_num' => $this->level), $params
			)
		);
	}

	public function getIterator()
	{
		$links = array();
		$sub = $this->sublinks();
		if ($sub) {
			$links = $sub->get_links();
		}
		return new ArrayIterator($links);
	}

}

/**
 * Класс работающий с данными (Navigation_Set): фильтрация, отрисовка и т.п.
 *
 * @package Navigation
 */
class Navigation_SetState implements IteratorAggregate
{

	protected $set;

	protected $filters = array();
	protected $not_filters = array('url', 'match', 'flag', 'sub', 'uri');
	protected $route = false;
	protected $current_level = 0;

	public function __construct(Navigation_Set $set)
	{
		$this->set = $set;
	}

	public function __call($method, $args)
	{
		$r = call_user_func_array(array($this->set, $method), $args);
		if ($r instanceof Navigation_Set) {
			return $this;
		}
		return $r;
	}

	public function __get($name)
	{
		if ($name == 'set') {
			return $this->set;
		}
		if ($name == 'level') {
			return $this->current_level;
		}
		return $this->set->__get($name);
	}

	public function selected_link()
	{
		$path = $this->current_path();
		return $path[(count($path) - 2) - $this->current_level];
	}

	public function filter()
	{
		foreach (Core::normalize_args(func_get_args()) as $arg) {
			$this->filters[str_replace('!', '', $arg)] = !Core_Strings::starts_with($arg, '!');
		}
		return $this;
	}

	public function add_filter($name, $value)
	{
		$this->filters[$name] = $value;
		return $this;
	}

	public function level($n)
	{
		$this->current_level = $n;
		//TODO: optimize or remove:
		if ($this->count() < 1) {
			return null;
		}
		return $this;
	}

	public function draw($template_name = 'simple', $params = array())
	{
		$links = $this->get_links();
		$level = 0;
		if (!empty($links)) {
			$level = reset($links)->level;
		}
		return Navigation::draw($template_name, array_merge(
				array('links' => new ArrayObject($links), 'level_num' => $level), $params
			)
		);
	}

	public function route()
	{
		$this->route = true;
		//TODO: optimize or remove:
		if ($this->count() < 1) {
			return null;
		}
		return $this;
	}

	public function get_links()
	{
		$path = $this->set->current_path();
		$links = array();
		if ($this->route) {
			$values = array_reverse($path);
			if ($this->current_level == 0) {
				unset($values[0]);
			}
			$links = array_merge($values, $this->to_route);
		} else {
			$links = $this->current_level == 0 ?
				$this->set->root->sublinks_array :
				$path[count($path) - 1 - $this->current_level]->sublinks_array;
			if (is_object($links)) {
				$links = (array)$links;
			}
		}
		return is_array($links) ? array_filter($links, array($this, 'filter_links')) : array();
	}

	protected function filter_links($link)
	{
		$result = true;
		foreach ($this->filters as $f => $v) {
			$linkv = isset($link->$f) ? $link->$f : false;
			$result = $result && $linkv == $v;
		}
		return $result;
	}

	public function count()
	{
		return count($this->get_links());
	}

	public function reset()
	{
		return $this;
	}

	public function getIterator()
	{
		return new ArrayIterator($this->get_links());
	}

}

/**
 * Класс контроллер для работы с разными наборами ссылок
 *
 * @package Navigation
 */
class Navigation_Controller implements Core_IndexedAccessInterface
{
	protected $sets = array();
	protected $default_set;
	protected $current_set;

	public function __construct($default_set = 'default')
	{
		$this->default_set = $this->current_set = $default_set;
		$this->add_set($this->default_set);
	}

	public function add_set($name, $set = null)
	{
		if (is_null($set)) {
			$set = Navigation::Set();
		}
		if ($set instanceof Navigation_SetInterface) {
			$this->sets[$name] = $set;
		}
		return Navigation::SetState($set);
	}

	public function get_current_set()
	{
		return $this->sets[$this->current_set];
	}

	public function get_current()
	{
		return Navigation::SetState($this->sets[$this->current_set]);
	}

	public function offsetGet($name)
	{
		return $this->sets[$name];
	}

	public function offsetExists($name)
	{
		return isset($this->sets[$name]);
	}

	public function offsetSet($name, $value)
	{
		$this->add_set($name, $value);
	}

	public function offsetUnset($name)
	{
		unset($this->sets[$name]);
	}

	public function __call($method, $args)
	{
		$wrapper = Navigation::SetState($this->get_current_set());
		if (method_exists($wrapper, $method) || method_exists($wrapper->set, $method)) {
			return call_user_func_array(array($wrapper, $method), $args);
		}
		if (isset($this->sets[$method])) {
			return Navigation::SetState($this->sets[$method]);
		} else {
			if (isset($args[0]) && $args[0] instanceof Navigation_Set) {
				return $this->add_set($method, $args[0]);
			} else {
				if (!isset($args[0])) {
					return $this->add_set($method);
				} else {
					throw new Core_MissingMethodException($method);
				}
			}
		}
	}

	public function __get($name)
	{
		$wrapper = Navigation::SetState($this->get_current_set());
		return $wrapper->$name;
	}

}
