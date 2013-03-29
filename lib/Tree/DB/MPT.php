<?php

// Implementation of Materialized Path by separate table

Core::load('Tree.DB');

class Tree_DB_MPT implements Core_ModuleInterface {

	const VERSION = '0.1.0';

	static public function schema(&$data, $create_join_table = true) {
		if (!isset($data['columns']['parent_id'])) {
			$data['columns']['parent_id'] = array('type' => 'int', 'default' => '0', 'not null' => true);
			$data['indexes']['idx_parent_id'] = array('columns' => array('parent_id'));
		}
		if ($create_join_table && !empty($data['name'])) {
			$join_schema = array($data['name']. '_tree' => array());
			self::schema_join($join_schema['projects_tree']);
			DB_Schema::process($join_schema);
		}
	}

	static public function schema_join(&$data) {
		$data = array_merge_recursive($data, array(
			'columns' => array(
				'id' => array('type' => 'serial'),
				'entity_id' => array('type' => 'int', 'default' => '0', 'not null' => true),
				'parent_id' => array('type' => 'int', 'default' => '0', 'not null' => true),
				'level' => array('type' => 'int', 'default' => '0', 'not null' => true),
				'ord' => array('type' => 'varchar', 'length' => 10, 'default' => '', 'not null' => true)
			),
			'indexes' => array(
				array('type' => 'primary key', 'columns' => array('id')),
				'idx_entity_id' => array('columns' => array('entity_id')),
				'idx_parent_id' => array('columns' => array('parent_id')),
				'idx_level' => array('columns' => array('level')),
				'idx_ord' => array('columns' => array('ord')),
			)
		));
	}

	static function EntitySubscriber() {
		return new Tree_DB_MPT_EntitySubscriber();
	}

}

class Tree_DB_MPT_Mapper extends Tree_DB_AbstractMapper {

	public function generate() {
		$this->connection->execute('TRUNCATE ' . $this->tree_table());
		foreach ($this->spawn() as $row) {
			$this->generate_item($row);
			unset($row);
		}
		return $this;
	}

	public function generate_item($row) {
		$path = $this->generate_item_path($row);
		$ids = explode(',', $path);
		$level = count($ids) - 1;
		foreach ($ids as $pid) {
			if ($pid == $row->id())  continue;
			$this->get_tree_mapper()->make_entity(array('entity_id' => $row->id(), 'parent_id' => $pid, 'level' => $level))->insert();
		}
		if (count($ids) == 1) {
			$this->get_tree_mapper()->make_entity(array('entity_id' => $row->id(), 'parent_id' => 0, 'level' => 0))->insert();
		}
	}

	public function generate_item_path($item) {
		$parent = $item->parent;
		if (!$parent) {
			return $item->id();
		}
		return $this->generate_item_path($parent) . ',' . $item->id();
	}

	protected function map_children($item) {
		return $this->map_descendants($item)->where($this->option('tree_alias') . '.level = :level', $item->level + 1);
	}

	protected function map_siblings($item, $include_self = false) {
		$m = $this->map_join_tree()->where($this->option('tree_alias') . '.parent_id = :pid', $item->parent_id)->where($alias . '.level = :level', $item->level);
		if (!$include_self)
			$m->where(self::table_from($this). '.id <> :id', $item->id());
		return $m;
	}

	protected function map_descendants($item) {
		return $this->map_join_tree()->where($this->option('tree_alias') . '.parent_id = :id', $item->id())->map_sort_tree();
	}

	protected function map_trail($item, $include_self = true) {
		return $this->map_join_tree('parent_id')->where($this->option('tree_alias') . '.entity_id = :id', $item->id())->map_sort_tree();
	}

	protected function map_parent($item) {
		return $this->map_join_tree()->where(self::table_from($this).'.id = :id', $item->parent_id);
	}

	protected function map_sort_tree($add = '') {
		$alias = $this->option('tree_alias');
		return $this->order_by("{$alias}.level, {$alias}.ord" . (!empty($add) ? ",$add" : ''));
	}

	//TODO: join_mapper method to DB.ORM
	protected function map_join_tree($field = 'entity_id', $alias = 'tt') {
		$this->option('tree_alias', $alias);
		return $this
			->join('inner', $this->tree_table() . " {$alias}", "{$alias}.{$field} = " . self::table_from($this) . '.id' )
			->calculate(array("{$alias}.level level", "{$alias}.ord ord"));	
	}

	public function tree_table() {
		return $this->option('tree_table') ? $this->option('tree_table') : self::table_from($this) . '_tree';
	}

	protected function map_join_tree_data($field = 'entity_id', $alias = 'tt') {
		$self_table = self::table_from($this);
		return $this->join_tree($field, $alias)->where("{$alias}.parent_id = {$self_table}.parent_id");
	}

	protected function map_full_tree() {
		return $this->map_join_tree_data();
	}

	public function tree_data($item) {
		$tree = $this->get_tree_mapper()->where('entity_id = :id', $item->id)->where('parent_id = :parent_id', $item->parent_id)
			->select_first();
		$item['level'] = $tree['level'];
		$item['ord'] = $tree['ord'];
		return $tree;
	}

	public function get_tree_mapper() {
		$table = $this->tree_table();
		if (!$this->session->can_cmap($table))
			$this->session->submapper($table, 'Tree.DB.MPT.JoinMapper');
		return $this->session->$table->table($table)->spawn();
	}

}

class Tree_DB_MPT_Entity extends DB_ORM_Entity {
	protected function setup() {
		$this->enable_dispatch()->dispatcher->add_subscriber(Tree_DB_MPT::EntitySubscriber($this));
		return parent::setup();
	}
}

class Tree_DB_MPT_EntitySubscriber extends Tree_DB_EntitySubscriber {

	public function get_parent($e) {
		return $e->obj->mapper->spawn()->where('id = :pid', $e->obj->parent_id)->select_first();
	}

	public function row_set_parent_id($value, $e) {
		if (is_null($e->obj->get('old_parent_id'))) {
			$e->obj->set('old_parent_id', $e->obj->get('parent_id'));
		}
		$e->obj->set('parent_id', $value);
		return $e->obj;
	}

	public function set_parent_id($value, $e) {
		return $this->row_set_parent_id($value, $e);
	}

	public function get_trail($include_self = true, $e = null) {
		if (is_null($e)) {
			$e = $include_self;
			$include_self = false;
		}
		if (!$e) return array();
		$trail = $e->obj->get_mapper()->trail($e->obj, $include_self)->select();
		$trail = (array) $trail;
		if ($include_self)
			$trail[] = $e->obj;
		return $trail;
	}

	public function get_level($e) {
		if (!isset($e->obj->attrs['level']))
			$this->get_tree_data($e);
		return is_null($e->obj->attrs['level']) ? 0 : $e->obj->attrs['level'];
	}

	public function get_tree_data($e) {
		$data = $e->obj->get_mapper()->tree_data($e->obj);
		return $data;
	}

	public function after_insert($e) {
		if ($parent = $e->obj->parent) {
			$trail = $parent->get_trail(true);
			$level = count($trail);
			foreach ($trail as $parent) {
				$this->get_tree_mapper($e)->make_entity(array('entity_id' => $e->obj->id(), 'parent_id' => $parent->id(), 'level' => $level))->insert();
			}
		}
		else {
			$this->get_tree_mapper($e)->make_entity(array('entity_id' => $e->obj->id(), 'parent_id' => 0, 'level' => 0))->insert();
		}
		$e->obj->old_parent_id = $e->obj->parent_id;
		return true && $e->get_last_result(true);
	}

	public function after_find($e) {
		$e->obj->get_mapper()->tree_data($e->obj);
		return true && $e->get_last_result(true);
	}

	//TODO: refactor and optimize if possible
	public function after_update($e) {
		if ($e->obj->old_parent_id == $e->obj->parent_id) return true;
		$new_parent = $e->obj->parent;
		$e->obj->parent_id = $e->obj->old_parent_id;
		$old_parent = $e->obj->parent;
		$descendants = $e->obj->descendants;
		$descendants[] = $e->obj;
		foreach ($descendants as $d) {
			$ids[] = $d->id();
		}
		// Very bad case:
		// ----------
		if ($new_parent && in_array($new_parent->id(), $ids)) {
			$new_parent->parent_id = $old_parent ? $old_parent->id() : 0;
			$new_parent->update();
			$e->obj->old_parent_id = $e->obj->parent_id;
			$e->obj->parent_id = $new_parent->id();
			return $this->after_update($e);
		}
		// ----------
		$dist = ($new_parent ? $new_parent->level : -1) - ($old_parent ? $old_parent->level : -1);
		if ($old_parent) { //delete old trail data
			foreach ($old_parent->get_trail(true) as $p) {
				$this->get_tree_mapper($e)->in('entity_id', $ids)->where('parent_id = :pid', $p->id())->delete_all();
			}
		}
		else {
			$this->get_tree_mapper($e)->in('entity_id', $ids)->where('parent_id = :pid', 0)->delete_all();
		}
		if ($new_parent) { //add data for new trail
			$trail = $new_parent->get_trail(true);
			foreach ($trail as $p) {
				foreach ($descendants as $d) {
					$this->get_tree_mapper($e)->make_entity(array('entity_id' => $d->id(), 'parent_id' => $p->id(), 'level' => $d->level))->insert();
				}
			}
			$e->obj->parent_id = $new_parent->id();
		}
		else {
			$this->get_tree_mapper($e)->make_entity(array('entity_id' => $e->obj->id(), 'parent_id' => 0, 'level' => 0))->insert();
			$k = array_search($e->obj->id(), $ids);
			if ($k !== false)
				unset($ids[$k]);
		}
		//update level in descendants
		$this->get_tree_mapper($e)->in('entity_id', $ids)->update_all(array(), array('level' => "level + $dist"));
		$e->obj->old_parent_id = $e->obj->parent_id;
		return true && $e->get_last_result(true);
	}

	public function get_tree_mapper($e) {
		return $e->obj->get_mapper()->get_tree_mapper()->spawn();
	}

	public function after_delete($e) {
		foreach ($e->obj->get_mapper()->descendants($e->obj) as $des) {
			$des->delete();
			$this->get_tree_mapper($e)->where('entity_id = :eid', $des->id())->delete_all();
		}
		$this->get_tree_mapper($e)->where('entity_id = :eid', $e->obj->id())->delete_all();
		return true && $e->get_last_result(true);
	}

}

class Tree_DB_MPT_JoinMapper extends DB_ORM_SQLMapper {

	protected function setup() {
		$this
			->columns('id', 'entity_id', 'parent_id', 'level', 'ord')
			->classname('Tree.DB.MPT.JoinEntity')
			->key('id');
		return parent::setup();
	}

}

class Tree_DB_MPT_JoinEntity extends DB_ORM_Entity {

	protected function setup() {
		return $this->assign(array(
			'entity_id' => 0,
			'parent_id' => 0,
			'ord' => 0,
			'level' => 0
		));
	}

}