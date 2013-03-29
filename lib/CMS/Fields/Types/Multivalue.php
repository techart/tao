<?php

//TODO: рефакторинг, внешний вид, сортировка

class CMS_Fields_Types_Multivalue extends CMS_Fields_AbstractField implements Core_ModuleInterface {
	const VERSION = '0.0.0';
	
	protected $assoc_mappers = array();
	
	public function __construct() {
		parent::__construct();
	}

	public function copy_value($from, $to, $name, $data) {
		$data['__item'] = $from;
		$info = $data['assoc'];
		$item_data = $this->get_item_data($name, $data);
		$item_type = CMS_Fields::type($item_data);
		$items = $this->get_items($name, $data);
		foreach ($items as $item_name => $item) {
			$item[$info['key_column']] = $to->id();
			if ($info['id_columns']) {
				foreach ( (array) $info['id_columns'] as $id) {
					$item[$id] = null;
				}
			}
			$item->insert();
		}
		return parent::copy_value($from, $to, $name, $data);
	}
	
	public function assign_from_object($form,$object,$name,$data) {
		$item_data = $this->get_item_data($name, $data);
		$item_type = CMS_Fields::type($item_data);

		foreach ($this->get_items($name, $data) as $item_name => $item) {
			$this->add_item_to_form($form, $item, $item_name, $item_data, $item_type);
		}
	}
	
	protected function add_item_to_form($form, $item, $item_name, $item_data, $item_type) {
		$item[$item_name] = $item['__data'];
		$item_data['__item'] = $item;
		CMS_Fields::form_fields($form, array($item_name => $item_data));
		$item_type->assign_from_object($form, $item, $item_name, $item_data);
	}
	
	public function get_item_data($name,$data) {
		return CMS_Fields::validate_parms($data['widget']);
	}
	
	public function get_item_name($name, $data, $num, $item) {
		return $name . '_' . $num;
	}

	public function assign_to_object($form,$object,$name,$data) {
		$data['__item'] = $object;
		$items = $this->get_items($name, $data);
		foreach (WS::env()->request[$form->name] as $iname => $value) {
			if ($this->is_item_name($iname, $name, $data)) {
				$this->update_item($iname, $name, $value, $items, $data, $form, $object);
			}
		}
	}
	
	protected function update_item($iname, $name, $value, $items, $data, $form, $object) {
		$info = $data['assoc'];
		if (isset($items[$iname])) {
			$action = 'update';
			$item = $items[$iname];
		} else {
			if (is_null($value)) return;
			$action = 'insert';
			$item = $this->get_assoc_mapper($name, $data)->make_entity();
			$item[$info['key_column']] = $object->id();
			$item['__data'] = $value;
			$item[$item[$info{'value_column'}]] = $value;
			$this->add_item_to_form($form, $item, $iname, $idata = $this->get_item_data($name, $data), CMS_Fields::type($idata));
		}
		$keys = isset($info['id_columns']) ? array() :
			array($info['key_column'] => $item[$info['key_column']], $info{'value_column'} => $item[$info{'value_column'}]);
		
		
		$item_data = $this->get_item_data($name, $data);
		$type = CMS_Fields::Type($item_data);
		$type->assign_to_object($form, $item, $iname, $item_data);
		$item[$info{'value_column'}] = $item[$iname];
		
		if ($action == 'update') $item->$action(array(), $keys);
		else $item->$action();
	}
	
	public function is_item_name($iname, $name, $data) {
		return preg_match("!{$name}_[\d]+!", $iname);
	}
	
	protected function layout_preprocess($l, $name, $data) {
		$l->use_script(CMS::stdfile_url('scripts/fields/multivalue.js'));
		$l->use_style(CMS::stdfile_url('styles/fields/multivalue.css'));
		return parent::layout_preprocess($l, $name, $data);
	}
	
	protected function preprocess($template, $name, $data) {
		$t = parent::preprocess($template, $name, $data);
		return $t->update_parm('items', $this->get_items($name, $data));
	}
	
	protected function load_main_item($id) {
		return CMS::$current_controller->load($id);
	}
	
	protected function get_items($name, $data) {
		$mapper = $this->get_assoc_mapper($name, $data);
		$item = isset($data['__item']) ? $data['__item'] :
			(isset($data['__item_id']) ? $this->load_main_item($data['__item_id']) : (object) array('id' => -1));
		
		if (!$mapper || $item->id <= 0)
			return array();
		
		$items = $this->query_items($data, $mapper, $item);
		if (count($items) < 1) return $items;
		$names = array();
		foreach ($items as $k => $item) $names[] = $this->get_item_name($name, $data, $k, $item);
		return array_combine($names, $items->getArrayCopy());
	}
	
	protected function query_items($data, $mapper, $item) {
		return $mapper->calculate($data['assoc']['value_column'] .' __data')
			->where("{$data['assoc']['key_column']} = :key_column", $item->id())
			->select();
	}
	
	protected function stdunset($data) {
		$res = parent::stdunset($data);
		return $this->punset($res, 'assoc', 'widget');
	}
	
	protected function get_assoc_mapper($name, $data) {
		$key = $name . ':' . serialize($data['assoc']);
		if (isset($this->assoc_mappers[$key])) return $this->assoc_mappers[$key];
		$mapper = $this->create_assoc_mapper($name, $data);
		return $this->assoc_mappers[$key] = $mapper;
	}
	
	protected function create_assoc_mapper($name, $data) {
		$info = $data['assoc'];
		if (!is_array($info) || !(isset($info['table']) || isset($info['mapper'])) || !isset($info['key_column']) || !isset($info{'value_column'}))
			return null;
		
		if (isset($info['mapper'])) {
			$mapper = is_string($info['mapper']) ? WS::env()->orm->downto($info['mapper']) : $info['mapper'];
		}	else {
			$mapper =  Core::make('DB.ORM.SQLMapper', WS::env()->orm)
				->classname('CMS.Fields.Types.Multivalue.Entity')
				->table($info['table'])
				//->key() 
				->columns($info['key_column'], $info['value_column'])
			;
		
			if ($info['id_columns']) {
				$mapper->key($info['id_columns']);
				$mapper->columns($info['id_columns']);
			}
			else {
				$mapper->key($info['key_column'], $info{'value_column'});
			}
		
			if (isset($info['ord_column'])) $mapper->order_by($info['ord_column']);
		}
		
		return $mapper->spawn();
	}
	
	
	public function action($name, $data, $action, $item = false, $fields = array()) {
		if (method_exists($this, $m = "action_$action")) return $this->$m($name, $data, $action, $item, $fields);
		return false;
	}
	
	public function action_add($name, $data, $action, $item = false, $fields = array()) {
		//TODO: refactoring
		$iname = WS::env()->request['last_name'];
		if (!empty($iname)) {
			if (!$this->is_item_name($iname, $name, $data)) return json_encode(array('status' => false, 'message' => ''));
			preg_match("!{$name}_([\d]+)!", $iname, $m);
			$num = (int) $m[1] + 1;
		} else {
			$num = 0;
		}
		
		$form = $this->create_form($item);
		$item =  $this->get_assoc_mapper($name, $data)->make_entity();
		$item_name = $name . '_' . $num;
		$item_data = $this->get_item_data($name, $data);
		
		$this->add_item_to_form($form, $item, $item_name, $item_data, $type_object = CMS_Fields::type($item_data));
		
		$template = $this->create_layout($name, $data, 'empty', 'item', array(
			'iitem' => $item,
			'item_name' => $item_name,
			'item_data' => $item_data,
			'form' => $form
		));
		$html = $template->render();
		
		return json_encode(array(
			'status' => true,
			'data' => $html,
			'eval' => trim($template['js']),
			'js' => $template->js_agregator->files_list(),
			'css' => $template->css_agregator->files_list()
		));
	}
	
	protected function create_form($item) {
		$c = CMS::$current_controller;
		return $c->create_form($c->action_url('edit',$item),'edit');
	}
	
	public function action_delete($name, $data, $action, $item = false, $fields = array()) {
		$r = WS::env()->request;
		$iname = $r['item_name'];
		$data['__item'] = $item;
		$e = $this->get_assoc_mapper($name, $data)->make_entity();
		$e['id'] = $r['item_id'];
		$e[$data['assoc']['value_column']] = $r['item_data'];
		$e[$data['assoc']['key_column']] = $item->id();
		$e->delete();
		return json_encode(array());
	}
}

class CMS_Fields_Types_Multivalue_Entity extends DB_ORM_Entity {

}
