<?php

Core::load('Storage.File.Export', 'CMS.Vars.Fields');

class CMS_Vars_Storage extends Storage_File_Export_Type implements Core_ModuleInterface {
	const VERSION = '0.1.0';
	
	static function initialize() {
		$dirs = Storage::manager()->vars()->select(Storage::manager()->vars()->create_query()->eq('vartype', 'dir'));
		array_unshift($dirs, 'Нет');
		CMS_Vars_Storage_Entity::fields(array(
			'vartype' => array('type' => 'select', 'caption' => 'Тип', 'items' => CMS::vars()->types()),
			'parent_id' => array('type' => 'tree_select', 'caption' => 'Родитель', 'items' => $dirs),
			'code' => array('caption' => 'Идентификатор', 'type' => 'varcode'),
			'title' => array('type' => 'input', 'caption' => 'Комментарий','tagparms' => array('style' => 'width: 98%')),
			'full' => array('type' => 'checkbox', 'caption' => 'Ограниченный доступ'),
		));
	}

	public function setup() {
		return parent::setup();
		//return $this->current_query->order_by('vartype');
	}

	public function for_code($parms) {
		if (is_string($parms)) $parms = array('code' => $parms);
		$this->find_dir($parms['parent_id'], $parms['site'], $parms['component']);
		$this->current_query->eq("code", $parms['code']);
		return $this;
	}
	
	public function find_dir($parent_id, $site, $component, $full = false) {
		if (!$component) $component = '';
		$q = $this->current_query;
		if ($full !== false) $q->eq("full", $full);
		if (!empty($site) && $site != '__')
			$q->eq("site", $site);
		else
			$q->in("site", array('','__'));
		$q->eq("parent_id", $parent_id)->eq("component", $component);
		return $this;
	}
	
	public function find_common_dir($parent_id, $site, $component) {
		return $this->map_find_dir($parent_id, $site, $component, 0);
	}
	
	public function select_component($component) {
		if (is_null($component)) $component = WS::env()->adminvars->component;
		$this->current_query->eq("component", (string) $component)->order_by('parent_id')->order_by('ord');
		return $this;
	}
	
	public function for_id($parent_id, $component = '') {
		$site = CMS_Admin::get_site();
		$full = CMS::$globals['full'] ? false : 0;
		return $this->find_dir($parent_id, $site, $component, $full)->select();
	}
	
	public function full_code($id) {
		if ($id==0) return '';
		$row = $this->find($id);
		$out = $row->code;
		while ($row->parent_id>0) {
			$row = $this->find($row->parent_id);
			$out = $row->code.'.'.$out;
		}
		if (trim($row->component)!='') $out = $row->component.':'.$out;
		return $out;
	}

	public function make_entity($attrs = array()) {
		$e = new CMS_Vars_Storage_Entity($attrs);
		$e->set_storage($this);
		return $e;
	}

}

class CMS_Vars_Storage_Entity extends Storage_Entity {

	public function full_code($id=false) {
		return $this->storage->full_code($id ? $id : $this->id);
	}
	
	public function get_full_code() {
		return $this->full_code();
	}
	
	public function get_var_description() {
		return Core::make('Text.Process')->process(CMS::vars()->type($this->vartype)->list_value($this), 'plaintext');
	}
	
	public function chparms($data) {
		foreach ($this->attrs as $k => $v) {
			if (isset($data[$k])) $this[$k] = $data[$k];
		}
		$this->update();
	}
	
	public function update_value() {
		return $this->update();
		// $columns = empty($this->valuesrc) ? array('value') : array('value', 'valuesrc');
		// return $this->update($columns);
	}
	
	public function update_full_value() {
		return $this->update();
		// return $this->update(array('value', 'valuesrc', 'parms', 'parmsrc'));
	}
	
	public function del() {
		return $this->delete();
	}
	
	public function as_string() {
		return $this->title;
	}
	
	public function __toString() {
		return $this->as_string();
	}

}
