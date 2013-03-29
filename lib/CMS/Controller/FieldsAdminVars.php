<?php

Core::load('CMS.Controller.Table', 'Object');

class CMS_Controller_FieldsAdminVars extends CMS_Controller_Table implements Core_ModuleInterface {
	const VERSION = '0.0.0';

	// protected $orm_name = 'vars';

	public function setup() {
		if (CMS::vars()->storage_type() == 'orm')
			$this->orm_name	= 'vars';
		if (CMS::vars()->storage_type() == 'storage')
			$this->storage_name = 'vars';
		return parent::setup();
	}

	protected function action_list() { 
		return $this->access_denied();
	}

	protected function action_add() {
		return $this->access_denied();
	}

	public function list_url($page = 0) {
		return '/admin/vars/';
	}

	public function action_url($action, $p = false, $args = false, $extra = false) {
		if ($action == 'list')
			return $this->list_url();
		return parent::action_url($action, $p, $args, $extra);
	}

	protected function title_edit($item) {
		return 'Редактирование переменной "' . $item->title . '"';
	}

	protected function get_type($item = null) {
		$item = $item ? $item : $this->edit_item;
		if ($item)
			return CMS_Vars::type($item->vartype);
		return null;
	}

	protected function form_fields($action = 'edit') {
		$type = $this->get_type();
		if ($action == 'edit' && $type) {
			return $type->fields;
		}
		return $this->form_fields;
	}

	protected function form_tabs($action, $item = false) {
		$type = $this->get_type($item);
		if ($action == 'edit' && $type) {
			return $type->tabs;
		}
		return $this->form_tabs;
	}

	protected function update($item) {
		$type = $this->get_type($item);
		if ($type) $type->serialize($item);
		parent::update($item);
	}

	protected function load($id) {
		$item = parent::load($id);
		if ($item && ($type = $this->get_type($item)))
			$type->deserialize($item);
		return $item;
	}

	
}