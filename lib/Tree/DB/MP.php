<?php
/**
 * @package Tree\DB\MP
 */


// Implementation of Materialized Path

Core::load('Tree.DB');

class Tree_DB_MP implements Core_ModuleInterface {

	const VERSION = '0.1.0';

	static public function schema(&$data) {
		if (!isset($data['columns']['path']))
			$data['columns']['path'] = array('type' => 'varchar', 'length' => 255, 'default' => '', 'not null' => true);
		if (!isset($data['columns']['path1']))
			$data['columns']['path1'] = array('type' => 'varchar', 'length' => 255, 'default' => '', 'not null' => true);
		if (!isset($data['columns']['level']))
			$data['columns']['level'] = array('type' => 'int', 'default' => '0', 'not null' => true);
		if (!isset($data['columns']['parent_id']))
			$data['columns']['parent_id'] = array('type' => 'int', 'default' => '0', 'not null' => true);
		if (!isset($data['columns']['ord']))
			$data['columns']['ord'] = array('type' => 'varchar', 'length' => 10, 'default' => '', 'not null' => true);

		$data['indexes']['idx_path'] = array('columns' => array(array('path', 255)));
		$data['indexes']['idx_path1'] = array('columns' => array(array('path1', 255)));
		$data['indexes']['idx_level'] = array('columns' => array('level'));
		$data['indexes']['idx_parent_id'] = array('columns' => array('parent_id'));
		$data['indexes']['idx_ord'] = array('columns' => array('ord'));
	}

	static function EntitySubscriber() {
		return new Tree_DB_MP_EntitySubscriber();
	}

}

class Tree_DB_MP_Mapper extends Tree_DB_AbstractMapper {

	protected function setup() {
		parent::setup();
		return $this
			->columns('path', 'path1', 'level', 'ord', 'parent_id')
			->option('delimiter', '.')
			;
	}

	public function generate() {
		$this->update_all(array('path' => '', 'level' => 0));
		foreach ($this as $row) {
			$this->generate_item($row);
		}
		return $this;
	}

	public function generate_item($row) {
		$path = $this->generate_item_path($row);
		$row->path = $path;
		$row->level = $row->calculate_level();
		$row->manual_setup = true;
		$row->update();
	}

	public function generate_item_path($item) {
		$parent = $item->parent;
		if (!$parent) {
			return $item->id();
		}
		return $this->generate_item_path($parent) . $this->option('delimiter') . $item->id();
	}

	protected function map_sort_tree($add = '') {
		return $this->order_by('level, ord' . (!empty($add) ? ",$add" : ''));
	}

	protected function map_parent($item) {
		return $this->where('id = :id', $item->parent_id);
	}

	protected function map_children($item) {
		return $this->descendants($item)->where('level = :level', $item->level + 1);
	}

	protected function map_siblings($item, $include_self = false) {
		$m = $this->where('parent_id = :pid', $item->parent_id);
		if (!$include_self) $m->where('id <> :cid', $item->id);
		return $m;
	}

	protected function map_descendants($item) {
		$path_column = $this->path_column($item);
		return $this->where("$path_column LIKE :pp", $item->$path_column . $this->option('delimiter') . '%');
	}

	protected function path_column($item) {
		return empty($item->path1) ? 'path' : 'path1';
	}

	protected function map_full_tree() {
		return $this;
	}

}

class Tree_DB_MP_Entity extends DB_ORM_Entity {

	protected function setup() {
		$this->enable_dispatch()->dispatcher->add_subscriber(Tree_DB_MP::EntitySubscriber($this));
		return parent::setup();
	}
}

class Tree_DB_MP_EntitySubscriber extends Tree_DB_EntitySubscriber {

	public function setup($e) {
		$e->obj->assign_attrs(array(
			'level' => 0,
			'path' => '',
			'path1' => '',
			'ord' => 0,
			'parent_id' => 0
		));
	}

	public function row_set_parent_id($value, $e) {
		if (is_null($e->obj->get('old_parent_id')))
			$e->obj->set('old_parent_id', $e->obj->get('parent_id'));
		$e->obj->set('parent_id', $value);
		return $e->obj;
	}

	public function set_parent_id($value, $e) {
		return $this->row_set_parent_id($value, $e);
	}

	public function set_path($value, $e) {
		$dl = $e->obj->mapper->option('delimiter');
		$path = !is_array($value) ? (string) $value : implode($dl, $value);
		$ids = explode($e->obj->mapper->option('delimiter'), $path);
		$ids1 = array();
		while (mb_strlen($path) >= 255) {
			$ids1[] = array_pop($ids);
			$path = implode($dl, $ids);
		}
		$ids1 = array_reverse($ids1);
		$e->obj->set('path', $path);
		$e->obj->set('path1', implode($dl, $ids1));
	}

	public function get_path($e) {
		$path1 = $e->obj->get('path1');
		if (!empty($path1)) {
			return $e->obj->get('path') . $e->obj->mapper->option('delimiter') . $path1;
		}
		return $e->obj->get('path');
	}

	public function get_trail($include_self = true, $e = null) {
		if (is_null($e)) {
			$e = $include_self;
			$include_self = false;
		}
		if (!$e) return array();
		$path = $e->obj->path;
		$ids = explode($e->obj->mapper->options['delimiter'], $e->obj->path);
		if (!$include_self) {
			$search = array_search($e->obj->id(), $ids, false);
			if ($search !== false)
				unset($ids[$search]);
		}
		return empty($ids) ? array() : $e->obj->mapper->spawn()->in('id', $ids)->order_by('level')->select();
	}

	public function calculate_level($e) {
		$path_ids = explode($e->obj->mapper->option('delimiter'), $e->obj->path);
		return max(count($path_ids) - 1, 0);
	}

	public function after_insert($e) {
		$parent = $e->obj->parent;
		if ($parent)
			$e->obj->path = $parent->path . $e->obj->mapper->option('delimiter') . $e->obj->id();
		else {
			$e->obj->path = $e->obj->id();
		}
		$e->obj->level = $e->obj->calculate_level($e);
		$e->obj->old_parent_id = $e->obj->parent_id;
		$e->obj->update(array('path', 'path1', 'level'));
		return true && $e->get_last_result(true);
	}

	public function after_update($e) {
		if ($e->obj->manual_setup || $e->obj->old_parent_id == $e->obj->parent_id) return true;
		$parent = $e->obj->parent;
		$old_descendants = $e->obj->mapper->descendants($e->obj);
		$old_level = $e->obj->level;
		$old_path = $e->obj->path;
		$ids = array();
		foreach ($e->obj->descendants as $d)
			$ids[] = $d->id();
		// Very bad case:
		// ----------
		if ($parent && in_array($parent->id(), $ids)) {
			$parent->parent_id = $e->obj->old_parent_id;
			$parent->update();
			return $this->after_update($e);
		}
		// ----------
		if ($parent) {
			$e->obj->path = $parent->path . $e->obj->mapper->option('delimiter') . $e->obj->id();
			$e->obj->level = $e->obj->calculate_level($e);
		}
		else {
			$e->obj->path = $e->obj->id();
			$e->obj->level = 0;
		}

		$e->obj->old_parent_id = $e->obj->parent_id;
		$e->obj->update(array('path', 'path1', 'level'));

		$dist = $e->obj->level - $old_level;
		$old_descendants->update_all(array(), array('level' => "level + $dist", 'path' => "REPLACE(path, '$old_path', '{$e->obj->path}')"));
		return true && $e->get_last_result(true);
	}

	public function after_delete($e) {
		return $e->obj->mapper->descendants($e->obj)->delete_all() && $e->get_last_result(true);
	}

}