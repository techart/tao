<?php
/**
 * @package CMS\Vars\ORM
 */


Core::load('CMS.ORM', 'WS', 'CMS.Vars.Fields');

class CMS_Vars_ORM implements Core_ModuleInterface {
	const VERSION = '0.1.0';
	
	static function initialize() {
		
	}
}

class CMS_Vars_ORM_Mapper extends CMS_ORM_Mapper {

	protected function setup() {
		return $this->
			table('vars')->
			classname('CMS.Vars.ORM.Entity')->
			columns('id', 'parent_id', 'site', 'component', 'code', 'title', 'value', 'valuesrc', 'vartype', 'parms', 'parmsrc', 'full', 'ord')->
			key('id')->
			order_by("IF(vartype='dir',0,1),id")->
			schema_fields(array(
				'vartype' => array('type' => 'select', 'caption' => 'Тип', 'items' => CMS::vars()->types()),
				'parent_id' => array('type' => 'tree_select', 'caption' => 'Родитель', 
							'items' => array(
								0 => 'Нет',
								Core::Call($this->spawn()->only('id', 'parent_id', 'title', 'ord')->where('vartype = :t', 'dir'), 'select_component', null)->cache(true)
							), 'flat' => true, /*'prefix' => '*'*/),
				'code' => array('caption' => 'Идентификатор', 'type' => 'varcode'),
				'title' => array('type' => 'input', 'caption' => 'Комментарий','tagparms' => array('style' => 'width: 98%')),
				'full' => array('type' => 'checkbox', 'caption' => 'Ограниченный доступ'),
			));
	}
	
	protected function map_for_code($parms) {
		if (is_string($parms)) $parms = array('code' => $parms);
		
		return $this->
			map_find_dir($parms['parent_id'], $parms['site'], $parms['component'])
			->where("code = :code", $parms['code'])
			;
	}
	
	protected function map_find_dir($parent_id, $site, $component, $full = false) {
		if (!$component) $component = '';
		$m = $this;
		if ($full !== false) $m = $m->where("full = :full", $full);
		if (!empty($site) && $site != '__')
			$m->where("site = :site", $site);
		else
			$m->where("site IN ('','__')");
		return $m
			->where("parent_id = :parent_id", $parent_id)
			//->where("site = :site", $site)
			->where("component = :component", $component)
			;
	}
	
	protected function map_find_common_dir($parent_id, $site, $component) {
		return $this->map_find_dir($parent_id, $site, $component, 0);
	}
	
	protected function map_select_component($component) {
		if (is_null($component)) $component = WS::env()->adminvars->component;
		return $this->where("component = :component", (string) $component)->order_by('parent_id,ord');
	}
	
	protected function map_for_id($parent_id, $component = '') {
		$site = CMS_Admin::get_site();
		$full = CMS::$globals['full'] ? false : 0;
		return $this->map_find_dir($parent_id, $site, $component, $full)->select();
	}
	
	//FIXME replace find
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
	
	public function insert($e) {
		if (!empty($e->id)) $this->explicit_key(true);
		return parent::insert($e);
	}
	
	public function __get($property) {
		if ($property == 'columns') return $this->options['columns'];
		return parent::__get($property);
	}

}

class CMS_Vars_ORM_Entity extends CMS_ORM_Entity {

	public function setup() {
		parent::setup();
		$this->assign(array(
			'parent_id' => 0,
			'title' => '',
			'value' => '',
			'valuesrc' => '',
			'parms' => '',
			'parmsrc' => '',
			'vartype' => '',
			'component' => '',
			'site' => '',
			'full' => 0,
			'ord' => 0
		));
		return $this;
	}
	
	public function full_code($id=false) {
		return $this->mapper->full_code($id ? $id : $this->id);
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
		$columns = empty($this->valuesrc) ? array('value') : array('value', 'valuesrc');
		return $this->update($columns);
	}
	
	public function update_full_value() {
		return $this->update(array('value', 'valuesrc', 'parms', 'parmsrc'));
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
