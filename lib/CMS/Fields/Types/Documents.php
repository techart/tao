<?php

Core::load('CMS.Fields.Types.Attaches');

class CMS_Fields_Types_Documents_Base extends CMS_Fields_Types_Attaches {
	
	protected $last_upload_files = array();
	protected $filters = array();
	protected $default_mnemocode_field =  array('mnemocode' => array('fieldLabel' => 'Код'));
	
	public function get_add_file_text($name, $data) {
		return CMS::lang()->_common->ta_adddoc;
	}

	public function filter($value, $field = 'mnemocode', $combine = 'and', $operation = 'eq', $delim = ',') {
		if (is_string($value)) $value = explode($delim, $value);
		$value = (array) $value;
		if (!isset($this->filters[$field])) $this->filters[$field] = array();
		$this->filters[$field][] = array('values' => $value, 'operation' => $operation, 'combine' => $combine);
		return $this;
	}

	public function filter_eq($value, $field = 'mnemocode', $combine = 'and', $delim = ',') {
		return $this->filter($value, $field, $combine, 'eq',  $delim);
	}

	public function filter_regexp($value, $field = 'mnemocode', $combine = 'and', $delim = ',') {
		return $this->filter($value, $field, $combine, 'regexp',  $delim);
	}

	public function reset_filters() {
		return $this->filters = array();
		return $this;
	}

	public function filelist($dir, $name, $data, $item) {
		$data = $this->files_data($name, $data, $item);
		$res = array();
		$orig_dir = $this->dir_path($item, false, $name, $data);
		if ($data['files'])
			foreach ($data['files'] as $k => $f) {
				$path = $dir . '/' . $f['name'];
				/*if (!IO_FS::exists($path) && $dir == $orig_dir)
					$this->remove_file_from_data($f, $name, $data, $item);
				else*/
					$res[$k] = $path;
					
			}
		return $res;
	}
	
	public function read_dir($dir) {
		$out = array();
		if (!is_dir($dir)) return $out;
		foreach(IO_FS::Dir($dir) as $entry) {
			if($this->validate_path($entry->path))
				$out[] = $entry->path;
		}
		return $out;
	}
	
	public function validate_path($path) {
		if (empty($path) || is_dir($path) || preg_match('{data\.serialize}', $path)) return false;
		return true;
	}
	
	public function assign_to_object($form,$object,$name,$data) {
	}
	
	public function assign_from_object($form,$object,$name,$data) {
	}
	
	protected function update_files_data($files, $name, $data, $item) {
		$files['files'] = array_values($files['files']);
		$file = IO_FS::File($this->files_data_path($name, $data, $item));
		$file->update(serialize($files));
		$file->set_permission();
	}
	
	protected function add_file_to_data($file, $name, $data, $item) {
		$files = $this->files_data($name, $data, $item);
		$find = false;
		if (isset($files['files']))
			foreach ($files['files'] as $k => $f)
				if ($f['name'] == $file['name']) {
					$files['files'][$k] = $file;
					$find = true;
				}
		if (!$find) $files['files'][] = $file;
		Events::call('admin.change');
		$this->update_files_data($files, $name, $data, $item);
		return $this;
	}

	protected function add_files_to_data($files, $name, $data, $item) {
		$dfiles = $this->files_data($name, $data, $item);
		foreach ($files as $f)
			$dfiles['files'][] = $f;
		Events::call('admin.change');
		$this->update_files_data($dfiles, $name, $data, $item);
		return $this;
	}
	
	protected function remove_file_from_data($file, $name, $data, $item) {
		$files = $this->files_data($name, $data, $item);
		foreach ($files['files'] as $k => $f) {
			if ($f['name'] == $file['name']) unset($files['files'][$k]);
		}
		Events::call('admin.change');
		$this->update_files_data($files, $name, $data, $item);
	}
	
	protected function files_data_path($name, $data, $item) {
		$dir = $this->dir_path($item, false, $name, $data);
		return $dir . '/data.serialize';
	}
	
	public function files_data($name, $data, $item) {
		$fdata = array();
		if (is_object($item)) {
			$i_id = $item->id();
			if (empty($i_id)) return $fdata;
		}
		$file = IO_FS::File($this->files_data_path($name, $data, $item));
		if ($file->exists()) {
			$fdata = unserialize($file->load());
		}
		else {
			$dir = $file->dir_name;
			if (!IO_FS::exists($dir)) IO_FS::mkdir($dir, null, true);
			$files = $this->read_dir($file->dir_name);
			$fdata = array();
			foreach ($files as $path) {
				$fdata['files'][] = array('name' => pathinfo($path, PATHINFO_BASENAME));
			}
			$file->update(serialize($fdata));
			$file->set_permission();
		}

		if (isset($fdata['files']) && !empty($fdata['files']) && !empty($this->filters))
			$fdata['files'] = array_filter($fdata['files'], array($this, 'filter_callback'));

		return $fdata;
	}

	protected function filter_callback($fd) {
		$rc = true;
		foreach ($this->filters as $field => $filters) {
			foreach ($filters as $data) {
				if (!isset($data['values'])) continue;
				$filter_op = "filter_op_" . $data['operation'];
				$find = $this->{$filter_op}($fd[$field], $data['values']);
				if ($data['combine'] == 'or') {
					$rc = $find || $rc;
				}
				else
					$rc = $find && $rc;
			}
		}
		return $rc;
	}

	protected function filter_op_eq($value, $search) {
		return in_array($value, $search);
	}

	protected function filter_op_regexp($value, $search) {
		$rc = false;
		foreach ($search as $r)
			$rc = preg_match($r, $value) || $rc;
		return $rc;
	}

	public function container($name,$data,$item) {
		$this->reset_filters();
		return parent::container($name,$data,$item);
	}
	
	public function action($name, $data, $action, $item = false, $fields = array()) {
		if (method_exists($this, $m = 'action_' . $action))
			return $this->$m($name, $data, $action, $item, $fields);
		return parent::action($name, $data, $action, $item, $fields);
	}

	protected function files_from_request()
	{
		return $this->request('data');
	}
	
	public function action_save($name, $data, $action, $item = false, $fields = array()) {
		$files = json_decode($this->files_from_request(), true);
		if (!empty($files)) $this->update_files_data($files, $name, $data, $item);
		Events::call('admin.change');
		return 'ok';
	}
	
	public function on_after_action($result, $name,$data,$action,$item=false,$fields = array()) {
		if ($action == 'upload' && $result == 'success' && !empty($this->last_upload_files) ) {
			$files = array();
			foreach ($this->last_upload_files as $f)
				$files[] = array('name' => $f, 'date' => date('d.m.Y'));
			if (!empty($files)) $this->add_files_to_data($files, $name, $data, $item);
		}
		if ($action == 'delete' && $result != false) {
			$file = array('name' => $this->get_filename());
			$this->remove_file_from_data($file, $name, $data, $item);
		}
	}
	
	protected function uploaded_filename($name, $data, $file) {
		$filename = parent::uploaded_filename($name, $data, $file);
		$filename = mb_convert_case($filename, MB_CASE_LOWER, "UTF-8");
		$name = pathinfo($filename, PATHINFO_FILENAME);
		$ext = pathinfo($filename, PATHINFO_EXTENSION);
		$path = $name . "-" . time() . '.' . $ext;
		if (!$this->validate_path($path)) return false;
		return $this->last_upload_files[] = $path;
	}

	public function add($file, $name, $data, $item) {
		$file = is_string($file) ? array('name' => $file, 'date' => date('d.m.Y')) : $file;
		if (parent::add($file['name'], $name, $data, $item)) {
			$path = $file['name'];
			$filename = pathinfo($path, PATHINFO_BASENAME);
			$file['name'] = $filename;
			$this->add_file_to_data($file, $name, $data, $item);
			return true;
		}
		return false;
	}

	public function remove($file, $name, $data, $item) {
		$file = is_string($file) ? array('name' => $file, 'date' => date('d.m.Y')) : $file;
		if (parent::remove($file['name'], $name, $data, $item)) {
			$this->remove_file_from_data($file, $name, $data, $item);
			return true;
		}
		return false;
	}
	
}


class CMS_Fields_Types_Documents extends CMS_Fields_Types_Documents_Base implements Core_ModuleInterface {
	const VERSION = '0.1.0';

	protected $autoedit_on_upload = true;
	
	protected function stdunset($data) {
		$res = parent::stdunset($data);
		return $this->punset($res, 'extend_doc_fields', 'doc_fields', 'mnemocode');
	}
	
	protected function preprocess($t, $name, $data) {
		$t->with(array('doc_fields' => $this->get_doc_fields($name, $data), 'list_fields' => $this->get_list_fields($name, $data)));
		return parent::preprocess($t, $name, $data);
	}
	
	protected function layout_preprocess($l, $name, $data) {
		$l->use_styles(
			CMS::stdfile_url('styles/fields/documents.css')
		);
		$l->use_scripts(
			CMS::stdfile_url('scripts/jquery/dform.js'),
			CMS::stdfile_url('scripts/tao/popup.js'),
			CMS::stdfile_url('scripts/fields/documents.js'),
			CMS::stdfile_url('scripts/jquery/json.js'),
			CMS::stdfile_url('scripts/jquery/tablednd.js'));
		$doc_fields = $this->get_doc_fields($name, $data);
		foreach ($doc_fields as $dname => $ddata) {
			if ($ddata['xtype'] == 'datefield' || $ddata['type'] == 'date') {
				$l->use_script(CMS::stdfile_url('scripts/jquery/ui.js'), array('weight' => -1));
				$l->use_style(CMS::stdfile_url('styles/jquery/ui.css'));
			}
		}
		$id = $this->url_class();
		$code = <<<JS
		$(function () { $('.{$id}.field-$name').each(function() {TAO.fields.documents.process($(this))}) })
JS;

		$item = $this->get_item($name, $data);
		if ($item && method_exists($item, 'is_phantom') && !$item->is_phantom()) {
			$l->append_to('js', $code);
		}
		$l->with('url_class', $id);
		Templates_HTML::add_scripts_settings(array('fields' => array(
			$name => array(
				'fields' => $doc_fields,
				'autoedit_on_upload' => isset($data['autoedit_on_upload']) ? $data['autoedit_on_upload'] : $this->autoedit_on_upload,
				)
		)));
		return parent::layout_preprocess($l, $name, $data);
	}
	
	protected function default_doc_fields($name, $data) {
		return array(
			'caption' => array('fieldLabel' => 'Название'),
			'date' => array('fieldLabel' => 'Дата', 'xtype' => 'datefield', 'format' => 'd.m.Y', 'altFormats' => 'm/d/Y|m.d.Y|d-m-Y'),
		);
	}
	
	protected function get_doc_fields($name, $data) {
		if (isset($data['doc_fields'])) return $data['doc_fields'];
		$res = array_merge($this->default_doc_fields($name, $data), isset($data['extend_doc_fields']) ? $data['extend_doc_fields'] : array());
		$mfield = null;
		if (isset($data['mnemocode'])) {
			if (is_array($data['mnemocode']))
				$mfield = $data['mnemocode'];
			else
				$mfield = $this->default_mnemocode_field;
		}
		if ($mfield)
			$res = array_merge($res, $mfield);
		return $res;
	}
	
	protected function default_list_fields($name, $data) {
		return array('date', 'caption');
	}
	
	protected function get_list_fields($name, $data) {
		if (isset($data['list_fields'])) return $data['list_fields'];
		return array_merge($this->default_list_fields($name, $data), isset($data['extend_list_fields']) ? $data['extend_list_fields'] : array());
	}
	
}


class CMS_Fields_Types_Documents_ValueContainer extends CMS_Fields_Types_Attaches_ValueContainer {
	
	public function render($type = 'render/default', $exclude_fields = array('path')) {
		$template = $this->template()->spawn($this->type->template($this->data, $type));
		$template->use_styles(CMS::stdfile_url('styles/fields/documents.css'));
		return $template->with(array(
			'files' => $this->filelist(),
			'dir' => $this->dir(),
			'files_data' => $this->type->files_data($this->name, $this->data, $this->item),
			'container' => $this,
			'exclude_fields' => $exclude_fields
		))->render();
	}
}
