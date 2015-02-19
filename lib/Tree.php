<?php

/**
 * @package Tree
 */
class Tree implements Core_ModuleInterface
{
	const VERSION = '0.0.0';

	//TODO: рефакторинг
	static public function create_tree($flat, $options = array(), &$childs = array())
	{
		$rows = array();

		$id_name = isset($options['id_name']) ? $options['id_name'] : 'id';
		$title_name = isset($options['title_name']) ? $options['title_name'] : 'title';
		$parent_name = isset($options['parent_name']) ? $options['parent_name'] : 'parent_id';
		$childs_name = isset($options['childs_name']) ? $options['childs_name'] : 'childs';
		$flat_keys = isset($options['flat_keys']) ? $options['flat_keys'] : false;
		$childs_limit = isset($options['childs_limit']) ? $options['childs_limit'] : false;
		$missing_callback = isset($options['missing_callback']) ? $options['missing_callback'] : false;

		$count = 0;

		$missing_ids = array();
		foreach ($flat as $k => $row) {
			if (isset($options['process_callback'])) {
				$row = Core::invoke($options['process_callback'], array($row));
			}
			if (empty($row)) {
				continue;
			}

			if (is_string($row)) {
				$row = array($title_name => $row);
			}

			$id = (int)isset($row[$id_name]) ? $row[$id_name] : $k;

			$rows[$id] = $row;
			if (!isset($childs[$id])) {
				$childs[$id] = array();
			}

			if (isset($row[$childs_name])) {
				$childs[$id] += self::create_tree($row[$childs_name], $options, $childs);
			}

			$pid = $row[$parent_name];
			if (!is_null($pid) && $pid >= 0) {
				$pid = (int)$pid;
				if (!isset($childs[$pid])) {
					$childs[$pid] = array();
				}
				$childs[$pid][$id] = $row;
			}
		}

		foreach (array_keys($childs) as $pid) {
			if (!isset($rows[$pid]) && $pid > 0) {
				$missing_ids[] = $pid;
			}
		}

		if (!empty($missing_ids) && $missing_callback) {
			foreach ($missing_ids as $id) {
				if ($id > 0) {
					Core::invoke($missing_callback, array($id, &$childs, &$rows));
				}
			}
		}

		foreach ($rows as $id => &$row) {
			$count++;
			self::tree_row_childs($row, $id, $childs, $childs_name, $count, $childs_limit);
			if ($childs_limit && $count >= $childs_limit) {
				continue;
			}
		}
		unset($row);

		$root = array();

		$root_exists = in_array(0, array_keys($rows), true);
		foreach ($rows as $id => $row) {
			if ((intval($row[$parent_name]) == 0 && !$root_exists) || (is_null($row[$parent_name])) || $id == 0) {
				$root[$id] = $row;
			}
		}

		if (empty($root) && !$root_exists) {
			$root = $rows;
		}

		if ($flat_keys) {
			$root = array_values($root);
			array_walk($root, 'self::tree_row_flat', $childs_name);
		}
		return $root;
	}

	static protected function tree_row_flat(&$a, $key, $childs_name)
	{
		if (!empty($a[$childs_name])) {
			$a[$childs_name] = array_values($a[$childs_name]);
			array_walk($a[$childs_name], 'self::tree_row_flat', $childs_name);
		}
	}

	static protected function tree_row_childs(&$row, $id, $childs, $childs_name, &$count, $childs_limit)
	{
		$row[$childs_name] = $childs[$id];
		$count++;
		if ($childs_limit && $count >= $childs_limit) {
			return;
		}
		if ($row[$childs_name]) {
			foreach ($row[$childs_name] as $ii => &$rr) {
				self::tree_row_childs($rr, $ii, $childs, $childs_name, $count, $childs_limit);
			}
		}
		return $row;
	}

	public static function find_in_tree(&$tree, $id, $callback = null, $id_name = 'id', $sub_name = 'sub')
	{
		foreach ($tree as $i => &$item) {
			if (isset($item[$id_name]) && $item[$id_name] == $id) {
				if ($callback) {
					$item = call_user_func($callback, $item);
				}
				return $item;
			}
			if (isset($item[$sub_name])) {
				$find = self::find_in_tree($item[$sub_name], $id, $callback, $id_name, $sub_name);
				if ($find) {
					return $find;
				}
			}
		}
		return null;
	}
}