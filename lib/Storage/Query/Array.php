<?php
/**
 * @package Storage\Query\Array
 */

Core::load('Storage');

class Storage_Query_Array implements Core_ModuleInterface
{
	const VERSION = '0.0.0';

	static public function query()
	{
		return new Storage_Query_Array_Query();
	}

	static public function executor()
	{
		return new Storage_Query_Array_Executor();
	}
}

class Storage_Query_Array_Query extends Storage_Query
{

	public function __construct()
	{
		$this->executor = new Storage_Query_Array_Executor();
	}

	public function execute($data)
	{
		$this->executor->set_data($data);
		foreach ($this->options as $name => $parms) {
			if (method_exists($this->executor, $name)) {
				call_user_func_array(array($this->executor, $name), $parms);
			}
		}
		return $this->executor->get_data();
	}

}

class Storage_Query_Array_Executor
{

	protected $data;

	public function set_data($data)
	{
		$this->data = $data;
		return $this;
	}

	public function get_data()
	{
		return $this->data;
	}

	public function in()
	{
		$parms = func_get_args();
		foreach ($parms as $field_parms) {
			$field = $field_parms[0];
			$values = (array)$field_parms[1];
			$this->data = array_filter($this->data, function ($v) use ($field, $values) {
					return in_array($v[$field], $values);
				}
			);
		}
	}

	public function eq()
	{
		$parms = func_get_args();
		foreach ($parms as $field_parms) {
			$field = $field_parms[0];
			$value = $field_parms[1];
			$this->data = array_filter($this->data, function ($v) use ($field, $value) {
					return isset($v[$field]) && $v[$field] == $value;
				}
			);
		}
	}

	public function eq_or_none()
	{
		$parms = func_get_args();
		foreach ($parms as $field_parms) {
			$field = $field_parms[0];
			$value = $field_parms[1];
			$this->data = array_filter($this->data, function ($v) use ($field, $value) {
					return !isset($v[$field]) || $v[$field] == $value;
				}
			);
		}
	}

	public function order_by()
	{
		$parms = func_get_args();
		$count = count($parms);
		$field = $parms[0][0];
		$dir = strtolower($parms[0][1]);
		usort($this->data, function ($a, $b) use ($field, $dir, $parms, $count) {
				$af = isset($a[$field]) ? $a[$field] : null;
				$bf = isset($b[$field]) ? $b[$field] : null;
				$i = 1;
				while ($af == $bf && $i < $count) {
					$field = $parms[$i][0];
					$dir = strtolower($parms[$i][1]);
					$af = isset($a[$field]) ? $a[$field] : null;
					$bf = isset($b[$field]) ? $b[$field] : null;
					$i++;
				}
				if ($af == $bf) {
					return 0;
				}
				if (($af < $bf && $dir == 'asc') || ($af > $bf && $dir == 'desc')) {
					return -1;
				}
				if (($af < $bf && $dir == 'desc') || ($af > $bf && $dir == 'asc')) {
					return 1;
				}
				return 1;
			}
		);
		return $this;
	}

	public function range($limit, $offset = 0)
	{
		$this->data = array_slice($this->data, $offset, $limit);
		return $this;
	}

}