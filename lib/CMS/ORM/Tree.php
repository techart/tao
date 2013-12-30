<?php
/**
 * @package CMS\ORM\Tree
 */


Core::load('CMS.ORM', 'Tree.DB.MP', 'Tree.DB.MPT');

class CMS_ORM_Tree implements Core_ModuleInterface {
	const VERSION = '0.0.0';
}

class CMS_ORM_Tree_MPEntity extends CMS_ORM_Entity {
	public function __construct(array $attrs = array(), $mapper = null) {
		parent::__construct($attrs, $mapper);
		$this->enable_dispatch()->dispatcher->add_subscriber(Tree_DB_MP::EntitySubscriber($this));
	}
}

class CMS_ORM_Tree_MPTEntity extends CMS_ORM_Entity {
	public function __construct(array $attrs = array(), $mapper = null) {
		parent::__construct($attrs, $mapper);
		$this->enable_dispatch()->dispatcher->add_subscriber(Tree_DB_MPT::EntitySubscriber($this));
	}
}

class CMS_ORM_Tree_MPMapper extends Tree_DB_MP_Mapper {
	protected function setup() {
		$this->shift('CMS.ORM.Mapper');
		$this->parent->table(self::table_from($this));
		$this->parent->classname($this->options['classname']);
		$this->parent->component(CMS::component_for($this));
		$this->parent->configure();
		$this->columns($this->parent->options['columns']);
		parent::setup();
	}
}

class CMS_ORM_Tree_MPTMapper extends Tree_DB_MPT_Mapper {
	protected function setup() {
		$this->shift('CMS.ORM.Mapper');
		$this->parent->table(self::table_from($this));
		$this->parent->classname($this->options['classname']);
		$this->parent->component(CMS::component_for($this));
		$this->parent->configure();
		$this->columns($this->parent->options['columns']);
		parent::setup();
	}
}