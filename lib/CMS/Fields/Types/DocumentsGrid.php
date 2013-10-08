<?php

Core::load('CMS.Fields.Types.Documents');

class CMS_Fields_Types_DocumentsGrid extends CMS_Fields_Types_Documents_Base implements Core_ModuleInterface {
	const VERSION = '0.1.0';
	
	protected function stdunset($data) {
		$res = parent::stdunset($data);
		return $this->punset($res, 'fields');
	}
	
	protected function preprocess($t, $name, $data) {
		return parent::preprocess($t, $name, $data);
	}
	
	protected function layout_preprocess($l, $name, $data) {

		$l->use_styles(
			CMS::stdfile_url('styles/SlickGrid/slick.grid.css'),
			CMS::stdfile_url('styles/SlickGrid/slick-default-theme.css'),
			CMS::stdfile_url('styles/SlickGrid/slick.css'),
			CMS::stdfile_url('styles/jquery/ui.css'),
			CMS::stdfile_url('styles/fields/documents-grid.css')
		);
		$l->use_scripts(
			CMS::stdfile_url('scripts/fields/documents-grid.js'),
			CMS::stdfile_url('scripts/jquery/ui.js'),
			CMS::stdfile_url('scripts/jquery/event.drag.js'),
			CMS::stdfile_url('scripts/jquery/event.drop.js'),
			CMS::stdfile_url('scripts/SlickGrid/slick.core.js'),
			CMS::stdfile_url('scripts/SlickGrid/slick.formatters.js'),
			CMS::stdfile_url('scripts/SlickGrid/slick.editors.js'),
			CMS::stdfile_url('scripts/SlickGrid/slick.grid.js'),
			CMS::stdfile_url('scripts/SlickGrid/plugins/slick.rowmovemanager.js'),
			CMS::stdfile_url('scripts/SlickGrid/plugins/slick.rowselectionmodel.js'),
			CMS::stdfile_url('scripts/tao/data.js')
		);
		$id = $this->url_class();
		$code = <<<JS
		$(window).load(function () { $('.{$id}.field-$name').each(function() {TAO.fields.documents_grid.process($(this))}) })
JS;
		$l->append_to('js', $code);
		$l->with('url_class', $id);
		Templates_HTML::add_scripts_settings(array('fields' => array(
			$name => array(
				'fields' => $this->get_fields($name, $data),
				'api' => array(
					'read' => CMS::$current_controller->field_action_url($name, 'load', $data['__item']),
					'update' => CMS::$current_controller->field_action_url($name, 'update', $data['__item']),
					'destroy' => CMS::$current_controller->field_action_url($name, 'delete', $data['__item']),
					'save' => CMS::$current_controller->field_action_url($name, 'save', $data['__item']),
				)
			)
		)));
		return parent::layout_preprocess($l, $name, $data);
	}
	
	protected function action_load($name, $data, $action, $item = false, $fields = array()) {
		return json_encode($this->load_data($name, $data, $item));
	}

	protected function files_from_request()
	{
		return WS::env()->request->content;
	}
	
	protected function load_data($name, $data, $item) {
		$fdata = $this->files_data($name, $data, $item);
		$files = $this->container($name, $data, $item)->filelist();
		if (empty($fdata['files'])) return $fdata;
		foreach ($fdata['files'] as $k => $ffdata) {
			if (empty($ffdata['caption']))
				$ffdata['caption'] = $ffdata['name'];
				$ffdata['path'] = CMS::$current_controller->field_action_url($name,'download',$item,array('code'=>$this->temp_code(),'file' => $ffdata['name']));
				// trim($files[$k], ' .');
			//$ffdata['index'] = $k;
			$fdata['files'][$k] = $ffdata;
		}
		$fdata['files'] = array_values($fdata['files']);
		return $fdata;
	}
	
	protected function action_update($name, $data, $action, $item = false, $fields = array()) {
		$fdatas = json_decode(WS::env()->request->content, true);
		$single = isset($fdatas['name']);
		$fdatas = $single ? array($fdatas) : $fdatas;
		$files = $single ? $this->files_data($name, $data, $item) : array('files');
		foreach ($fdatas as $fk => $fdata) {
			if (isset($fdata['date'])) $fdata['date'] = date('d.m.Y', strtotime($fdata['date']));
			if ($single) {
				foreach ($files['files'] as $k => $f) {
					if ($f['name'] == $fdata['name']) $files['files'][$k] = $fdata;
				}
			}
			else
				$files['files'][$fk] = $fdata;
		}
		Events::call('admin.change');
		$this->update_files_data($files, $name, $data, $item);
		return json_encode(array('success' => true, 'items' => $fdatas));
	}
	
	protected function action_delete($name, $data, $action, $item = false, $fields = array()) {
		$res = parent::action_delete($name, $data, $action, $item, $fields);
		Events::call('admin.change');
		if ($res == 'ok') return json_encode(array('success' => true));
	}
	
	protected function get_fields($name, $data) {
		if (isset($data['fields'])) return $data['fields'];
		return array_merge($this->default_fields($name, $data), isset($data['fields']) ? $data['fields'] : array());
	}
	
	protected function default_fields___($name, $data) {
		return array(
			'name' => array(),
			'path' => array(),
			'date' => array(
				'column' => array(
					'text' => 'Дата',
					'xtype' => 'datecolumn',
					'format' => 'd.m.Y',
					'editable' => true,
					'editor' => array(
						'xtype' => 'datefield',
						'format' => 'd.m.Y',
						'altFormats' => 'm/d/Y|m.d.Y|d-m-Y',
					),
					'flex' => 0
				),
				'store' => array('type' => 'date', 'dateFormat' => 'd.m.Y')
			),
			'caption' => array(
				'column' => array(
					'text' => 'Название',
					'editor' => 'textfield',)
			),
		);
	}

	protected function default_fields($name, $data)
	{
		return array(
			'name' => array(),
			'path' => array(),
			'date' => array(
				'caption' => 'Дата',
				'type' => 'date',
				'editorOptions' => array('dateFormat' => 'dd.mm.yy'),
				'editor' => 'Slick.Editors.Date',
				'maxWidth' => 120,
				'resizable' => false
			),
			'caption' => array(
				'caption' => 'Название',
				'editor' => 'Slick.Editors.Text',
			),
		);
	}
}

class CMS_Fields_Types_DocumentsGrid_ValueContainer extends CMS_Fields_Types_Documents_ValueContainer {}
