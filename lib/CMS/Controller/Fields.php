<?php

Core::load('CMS.Fields');

class CMS_Controller_Fields extends CMS_Controller {

	protected $id = 0;
	protected $edit_item = null;
	protected $filtered_form_fields = array();

	protected function load($id) {
		return null;
	}

	public function name() {
		return null;
	}

	public function component() {
		return CMS::component();
	}

	public function get_from_component($name, $property = false, $default = array()) {
		$c = $this->component();
		if ($c) {
			$data = $this->component()->config($name);
			if ($data) {
				if (is_null($property)) return $data;
				if ($property === false) {
					$property = $this->name();
					if (empty($property)) return null;
				}
				if (isset($data->$property)) return $data->$property;
			}
		}
		return $default;
	}

	public function setup_config() {
		$fields = $this->get_from_component('fields');
		if (!empty($fields)) $this->form_fields	= array_merge( (array) $this->form_fields, $fields);
	}

	public function setup() {
		$this->setup_config();
		return parent::setup();
	}

	//TODO: cache this:
	protected function search_fields($from, $to, $by) {
		$res = $to;
		$weight = 0;
		$delta = 0.001;
		foreach ($from as $name => $f) {
			if (isset($f[$by]) && $f[$by]) {
				$parms = $f[$by];
				if ($parms === true) $parms = $f;
				else if (is_string($parms)) {$name = $parms; $parms = $f;}
				else $parms = array_merge($f, $parms);
				$parms['caption'] = isset($parms['caption']) ? $parms['caption'] : $f['caption'];
				$res[$name] = $parms;
			}
		}
		foreach ($res as $name => $parms) {
			if (!isset($parms['weight'])) {
				$weight += $delta;
				$parms['weight'] = $weight;
			}
			$res[$name] = $parms;
		}
		if (!empty($res)) uasort($res, array($this, 'sort_by_weight'));
		return $res;
	}

	protected function on_before_field_action() {}

	protected function on_field_item_access($item) {
		return true;
	}

	protected function fields_for_action() {
		return $this->form_fields();
	}

	protected function load_item() {
		if ($this->edit_item) return $this->edit_item;
		$item = false;
		if ($this->id > 0) {
			$item = $this->load($this->id);
			if (!$item) return $this->page_not_found();
			if ($this->on_field_item_access($item)) return 'Access denied!';
			$this->edit_item = $item;
		}
		return $item;
	}

	protected function field_action($field,$action) {
		$item = $this->load_item();

		foreach ($this->fields_for_action() as $fields) {
			if (preg_match('!(_[\d]+)$!', $field, $m) && ($multi = $fields[str_replace($m[1], '', $field)]) && $multi['type'] == 'multivalue') {
				$field_data = $multi['widget'];
			}
			else if (!isset($fields[$field])) continue;
			
			$field_data = isset($field_data) ? $field_data : $fields[$field];
			
			$type = CMS_Fields::type($field_data);
			
			$type->on_before_action($field,$field_data,$action,$item, $fields);
			//FIME:
			$data['__table'] = $this->name();
			$rc = $type->action($field,$field_data,$action,$item, $fields);
			$type->on_after_action($rc, $field,$field_data,$action,$item, $fields);
			if ($rc===false) return $this->page_not_found();
			if (is_object($rc)) return $rc;
			if ($m = Core_Regexps::match_with_results('{^location:(.+)$}',$rc)) {
				$location = trim($m[1]);
				if ($location=='edit') $location = $this->action_url('edit',$item);
				return $this->redirect_to($location);
			}
			return $rc;
		}
		return $this->page_not_found();
	}

	public function field_callback_update_item($item) {
		return $this->update($item);
	}

	public function field_action_url($field, $action, $item = false, $args = false) {
		if ($item) {
			$fields = $this->form_fields($action);
			if (isset($fields[$field])) {
				$data = $fields[$field];
				$type = CMS_Fields::type($data);
				$url = $type->action_url($field,$data,$action,$item,$args);
				if ($url) return $url;
			}
		}

		$url = $this->urls->admin_url();
		$url .= "$action/field-$field/page-$this->page/";
		if ($item) {
			$url .= "id-".$this->item_id($item)."/";
		}
		return $url;
	}

	protected $upload_fields = array();
	protected $form = false;

	protected $form_fields = array();
	protected function form_fields($action = 'edit') {
		return $this->form_fields;
	}

	protected function form_field_exists($name,$parms,$action) {
		if (isset($parms['if_component_exists'])) {
			if (!CMS::component_exists($parms['if_component_exists'])) {
				return false;
			}
		}
		return true;
	}

	protected function sort_by_weight($a, $b) {
		return $a['weight'] < $b['weight'] ? -1 : 1;
	}

	protected function filter_form_fields($action) {
		$fields = array();
		$weight = 0.0;
		$delta = 0.001;
		foreach($this->form_fields($action) as $name => $parms) {
			if ($this->form_field_exists($name,$parms,$action)) {
				$parms['__table'] = $this->name();
				if (isset($parms['items'])) {
					$parms['__items'] = $this->items_for_select($parms['items']);
				}
				if (!isset($parms['weight'])) {
					$weight += $delta;
					$parms['weight'] = $weight;
				}
				$fields[$name] = $parms;
			}
		}
		uasort($fields, array($this, 'sort_by_weight'));
		return $fields;
	}

	protected function items_for_select($items) {
		return CMS::items_for_select($items);
	}

	public function create_form($url, $action = 'edit') {
		$form = Forms::Form('mainform')->action($url);
		$this->filtered_form_fields = $this->filter_form_fields($action);
		CMS_Fields::form_fields($form,$this->filtered_form_fields);
		foreach($this->filtered_form_fields as $name => $parms) {
			$type = CMS_Fields::type($parms);
			if ($type->is_upload()) $this->upload_fields[$name] = $parms;
		}
		$this->form = $form;
		return $form;
	}

	protected $item_before_assign;

	protected function form_to_item($item) {
		$this->item_before_assign = clone $item;
		foreach($this->filtered_form_fields as $name => $parms) {
			$type = CMS_Fields::type($parms);
			$type->assign_to_object($this->form,$item,$name,$parms);
		}
	}

	protected function item_to_form($item) {
		foreach($this->filtered_form_fields as $name => $parms) {
			$type = CMS_Fields::type($parms);
			$parms['__item_id'] = $this->item_id($item);
			$parms['__item'] = $item;
			$type->assign_from_object($this->form,$item,$name,$parms);
		}
	}

	protected function process_form($item) {
		return CMS_Fields::process_form($this->form,$this->env->request);
	}


	protected function uploaded_path($path) {
		$path = trim($path);
		if ($path=='') return false;
		if ($path[0]=='/'||$path[0]=='.') return $path;
		return "./$path";
	}

	protected function validate_filename($name) {
		$name = trim($name);
		$name = preg_replace('{\s+}sm',' ',$name);
		$name = str_replace(' ','_',$name);
		$name = CMS::translit($name);
		return $name;
	}

	protected $uploaded_files = array();

	protected function process_uploads($item) {
		if (!isset($_FILES[$this->form->name])) return;
		$dir = CMS::temp_dir();
		$files = $_FILES[$this->form->name];
		foreach($files['tmp_name'] as $field => $path) {
			$t = time();
			$path = trim($path);
			if ($path=='') {
				continue;
			}
			$original_name = $files['name'][$field];
			$original_name_we = $original_name;
			$ext = '';
			$dotext = '';
			if ($m = Core_Regexps::match_with_results('{^(.*)\.([a-z0-9_]+)$}i',$original_name)) {
				$original_name_we = strtolower($m[1]);
				$ext = $m[2];
				$dotext = ".$ext";
			}
			$uplname = "uploaded-$field-$t$dotext";
			$uplpath = "$dir/$uplname";
			move_uploaded_file($path,$uplpath);
			CMS::chmod_file($uplpath);

			$this->uploaded_files[$field] = array(
				'original_name' => $original_name,
				'original_name_we' => $original_name_we,
				'ext' => $ext,
				'dotext' => $dotext,
				'uplname' => $uplname,
				'uplpath' => $uplpath,
				'size' => $files['size'][$field],
				'type' => $files['type'][$field],
			);

		}

		$uploads_extra = array(
				'old_item' => $this->item_before_assign,
				'id' => $this->item_id($item),
				'homedir' => $this->item_homedir($item),
				'private_homedir' => $this->item_homedir($item,true),
				'controller' => $this,
				'form' => $form,
				'form_fields' => $this->filtered_form_fields,
		);

		foreach($this->filtered_form_fields as $field => $parms) {
			$type = CMS_Fields::type($parms);
			$type->process_uploads($field,$parms,$this->uploaded_files,$item,$uploads_extra);
		}


		
		foreach($this->uploaded_files as $fdata) {
			IO_FS::rm($fdata['uplpath']);
		}

	}

	protected function process_inserted_item($item) {
		$rc = false;
		foreach($this->filtered_form_fields as $name => $data) {
			$type = CMS_Fields::type($data);
			$r = $type->process_inserted($name,$data,$item);
			if ($r) $rc = true;
		}
		return $rc;
	}

	//Example of usage
	protected $form_url = '';
	protected $form_redirect_url = '';
	protected $form_template = 'form';

	protected function action_form() {
		$item = $this->load($this->id);
		if (!$item) return $this->page_not_found();
		$this->edit_item = $item;

		$this->create_form($this->form_url);
		$this->item_to_form($item);

		$errors = array();
		if ($this->env->request->method_name=='post') {
			$errors = $this->process_form($item);
			if (sizeof($errors)==0) {
				$this->form_to_item($item);
				$this->process_inserted_item($item);
				if (count($this->upload_fields)>0) $this->process_uploads($item);
				$item->update();
				return $this->redirect_to($this->form_redirect_url);
			}
		}

		return $this->render($this->form_template, array(
			'form' => $this->form,
			'form_fields' => $this->filtered_form_fields,
			'item' => $item,
			'item_id' => $this->id,
			'errors' => $errors,
		));
	}

}