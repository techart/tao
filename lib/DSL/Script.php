<?php
/**
 * DSL.Script
 *
 * @package DSL\Script
 * @version 0.2.0
 */
Core::load('DSL');

/**
 * @package DSL\Script
 */
class DSL_Script implements Core_ModuleInterface
{
	const VERSION = '0.2.0';
}

/**
 * @package DSL\Script
 */
class DSL_Script_Exception extends Core_Exception
{
}

/**
 * @abstract
 * @package DSL\Script
 */
abstract class DSL_Script_Builder extends DSL_Builder
{

	/**
	 * @param DSL_Script_Builder $parent
	 * @param null               $object
	 */
	public function __construct(DSL_Script_Builder $parent = null, $object = null)
	{
		parent::__construct($parent, Core::if_null($object, $this->make_object()));
	}

	/**
	 * @abstract
	 * @return mixed
	 */
	abstract protected function make_object();

	/**
	 * @return DSL_Script_Builder
	 */
	public function for_each_value()
	{
		$args = func_get_args();
		$var = array_shift($args);
		return $this->builder_for(new DSL_Script_IterationAction($var, $args));
	}

	public function for_each($var, $items, $field = '')
	{
		return $this->builder_for(new DSL_Script_IterationAction($var, $items, $field));
	}

	/**
	 * @return DSL_Script_Builder
	 */
	public function format()
	{
		$args = func_get_args();
		$format = array_shift($args);
		$this->object->actions(new DSL_Script_FormatAction($format, $args));
		return $this;
	}

	/**
	 * @param string $field
	 *
	 * @return DSL_Script_Builder
	 */
	public function dump($field)
	{
		$this->object->actions(new DSL_Script_DumpAction($field));
		return $this;
	}

	/**
	 * @param DSL_Script_Action $action
	 *
	 * @return DSL_Builder
	 */
	protected function builder_for(DSL_Script_Action $action, $class = '')
	{
		return Core::make($class ? $class : Core_Types::real_class_name_for($this), $this->actions($action), $action);
	}

}

/**
 * @package DSL\Script
 */
class DSL_Script_Action implements Core_CallInterface
{

	protected $parent;
	protected $data = array();

	private $binds = array();
	private $actions = array();

	/**
	 * @param DSL_Script_Action $parent
	 *
	 * @return mixed
	 */
	public function exec(DSL_Script_Action $parent = null)
	{
		$this->parent = $parent;

		foreach ($this->binds as $k => $v) {
			list($obj, $attr) = explode('.', $v);
			$this->data[$k] = $attr ?
				$parent->get($obj)->$attr :
				$parent->get($obj);
		}

		$rc = $this->action();
		$this->parent = null;
		return $rc;
	}

	/**
	 */
	protected function action()
	{
		return $this->exec_actions();
	}

	/**
	 * @param string  $name
	 * @param         $value
	 * @param boolean $strict
	 *
	 * @return DSL_Script_Action
	 */
	public function set($name, $value, $strict = false)
	{
		if ($strict) {
			if (array_key_exists($name, $this->data)) {
				$this->data[$name] = $value;
			} else {
				if ($this->parent) {
					$this->parent->set($name, $value, $strict);
				} else {
					throw new DSL_Script_Exception("unable to strict set for $name");
				}
			}
		} else {
			$this->data[$name] = $value;
		}
		return $this;
	}

	/**
	 * @param string $name
	 *
	 * @return mixed
	 */
	public function get($name)
	{
		return (array_key_exists($name, $this->data)) ?
			$this->data[$name] :
			($this->parent ? $this->parent->get($name) : null);
	}

	/**
	 * @return DSL_Script_Action
	 */
	public function bind($var, $src = '')
	{
		$this->binds[$var] = $src ? $src : $var;
		return $this;
	}

	/**
	 * @return DSL_Script_Action
	 */
	public function with()
	{
		$args = func_get_args();
		$num = count($args);
		for ($i = 0; $i < $num - 1; $i++)
			$this->data[$args[$i]] = $args[$i + 1];
		return $this;
	}

	/**
	 * @return DSL_Script_Action
	 */
	public function actions()
	{
		$args = func_get_args();
		foreach ($args as $a)
			if ($a instanceof DSL_Script_Action) {
				$this->actions[] = $a;
			}
		return $this;
	}

	/**
	 * @return mixed
	 */
	protected function exec_actions()
	{
		$rc = $this;
		foreach ($this->actions as $a)
			$rc = $a->exec($this);
		return $rc;
	}

	/**
	 * @param string $method
	 * @param array  $args
	 *
	 * @return SDL_Script_Action
	 */
	public function __call($method, $args)
	{
		$this->set($method, $args[0]);
		return $this;
	}

}

/**
 * @package DSL\Script
 */
class DSL_Script_IterationAction extends DSL_Script_Action
{

	private $name;
	private $items;
	private $field;

	/**
	 * @param string $name
	 * @param mixed  $items
	 */
	public function __construct($name, $items, $field = '')
	{
		$this->name = $name;
		$this->items = $items;
		$this->field = $field;
	}

	/**
	 * @return mixed
	 */
	protected function action()
	{
		foreach ($this->items as $item) {
			$this->set($this->name, $this->field ? $item->{$this->field} : $item);
			$rc = $this->exec_actions();
			if (!$rc) {
				break;
			}
		}
		return $rc;
	}

}

/**
 * @package DSL\Script
 */
class DSL_Script_FormatAction extends DSL_Script_Action
{

	private $format;
	private $fields;

	/**
	 * @param string $format
	 * @param        $fields
	 */
	public function __construct($format, $fields)
	{
		$this->format = $format;
		$this->fields = $fields;
	}

	/**
	 * @return boolean
	 */
	protected function action()
	{
		$parms = array($this->format);
		foreach ($this->fields as $f) {
			list($obj, $attr) = explode('.', $f, 2);
			$parms[] = $attr ? $this->get($obj)->$attr : $this->get($obj);
		}
		call_user_func_array('printf', $parms);
		return true;
	}

}

/**
 * @package DSL\Script
 */
class DSL_Script_DumpAction extends DSL_Script_Action
{
	private $field;

	public function __construct($field)
	{
		$this->field = $field;
	}

	/**
	 * @param  $action
	 */
	protected function action()
	{
		var_dump($this->get($this->field));
	}

}

