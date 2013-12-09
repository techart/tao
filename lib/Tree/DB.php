<?php
/**
 * @package Tree\DB
 */


// Base orm classes and interfaces

Core::load('DB.ORM');

class Tree_DB implements Core_ModuleInterface {
	const VERSION = '0.0.0';

}

abstract class Tree_DB_AbstractMapper extends DB_ORM_SQLMapper {

	abstract public function generate();

	abstract protected function map_children($item);

	abstract protected function map_siblings($item);

	abstract protected function map_descendants($item);

	abstract protected function map_parent($item);

	abstract protected function map_full_tree();
}

interface Tree_DB_EntityInterface {

	public function get_children();

	public function get_siblings($include_self = false);

	public function get_descendants();

	public function get_trail($include_self = true);

	public function get_trail_ids($include_self = true);

	public function get_parent();
}

class Tree_DB_EntitySubscriber extends Events_Subscriber {
	public function get_children($e) {
		return $e->obj->mapper->children($e->obj)->select();
	}

	public function get_siblings($include_self = false, $e = null) {
		if (is_null($e)) {
			$e = $include_self;
			$include_self = false;
		}
		if (!$e) return array();
		return $e->obj->mapper->siblings($e->obj, $include_self)->select();
	}

	public function get_descendants($e) {
		return $e->obj->mapper->descendants($e->obj)->select();
	}

	public function get_parent($e) {
		return $e->obj->mapper->parent($e->obj)->select_first();
	}

	public function get_root($e) {
		$trail = $e->obj->trail;
		return count($trail) ? reset($rtail) : array();
	}

	public function get_trail_ids($include_self = false, $e = null) {
		if (is_null($e)) {
			$e = $include_self;
			$include_self = false;
		}
		if (!$e) return array();
		$ids = array();
		foreach($e->obj->get_trail($include_self) as $t)
			$ids[] = $t->id();
		return $ids;
	}
}

abstract class Tree_DB_AbstractEntity extends DB_ORM_Entity implements Tree_DB_EntityInterface {

	public function get_children() {
		return $this->get_mapper()->children($this)->select();
	}

	public function get_siblings($include_self = false) {
		return $this->get_mapper()->siblings($this, $include_self)->select();
	}

	public function get_descendants() {
		return $this->get_mapper()->descendants($this)->select();
	}

	public function get_parent() {
		return $this->get_mapper()->parent($this)->select_first();
	}

}