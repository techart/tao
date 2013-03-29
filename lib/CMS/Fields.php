<?php

class CMS_Fields implements Core_ModuleInterface {

	const MODULE = 'CMS.Fields';
	const VERSION = '0.0.0';

	static $types = array();
	static $dirs = array();

	static function initialize($config) {
		//TODO: автоматически регистрировать тепы из CMS.Fields.Types.*
		CMS::field_type('input',	'CMS.Fields.Types.Input');
		CMS::field_type('hidden',	'CMS.Fields.Types.Hidden');
		CMS::field_type('radio',	'CMS.Fields.Types.Radio');
		CMS::field_type('checkbox',	'CMS.Fields.Types.Checkbox');
		CMS::field_type('select',	'CMS.Fields.Types.Select');
		CMS::field_type('textarea',	'CMS.Fields.Types.Textarea');
		CMS::field_type('sqldate',	'CMS.Fields.Types.SQLDate');
		CMS::field_type('datestr',	'CMS.Fields.Types.DateStr');
		CMS::field_type('sqldatestr',	'CMS.Fields.Types.SQLDateStr');
		CMS::field_type('upload',	'CMS.Fields.Types.Upload');
		CMS::field_type('ajaxupload',	'CMS.Fields.Types.AjaxUpload');
		CMS::field_type('subheader',	'CMS.Fields.Types.Subheader');
		CMS::field_type('email',	'CMS.Fields.Types.Email');
		CMS::field_type('attaches',	'CMS.Fields.Types.Attaches');
		CMS::field_type('image',	'CMS.Fields.Types.Image');
		CMS::field_type('protect',	'CMS.Fields.Types.Protect');
		CMS::field_type('html',		'CMS.Fields.Types.HTML');
		CMS::field_type('gallery',	'CMS.Fields.Types.Gallery');
		CMS::field_type('parms',	'CMS.Fields.Types.Parms');
		CMS::field_type('tree_select',	'CMS.Fields.Types.TreeSelect');
		CMS::field_type('multivalue',	'CMS.Fields.Types.Multivalue');
		CMS::field_type('multilink',	'CMS.Fields.Types.Multilink');
		CMS::field_type('static_multilink',	'CMS.Fields.Types.StaticMultilink');
		CMS::field_type('checkboxes',	'CMS.Fields.Types.Checkboxes');
		CMS::field_type('autocomplete',	'CMS.Fields.Types.Autocomplete');
		CMS::field_type('content',	'CMS.Fields.Types.Content');
		CMS::field_type('documents',	'CMS.Fields.Types.Documents');
		CMS::field_type('documents_grid',	'CMS.Fields.Types.DocumentsGrid');
		CMS::field_type('youtube',	'CMS.Fields.Types.Youtube');
		CMS::field_type('map_coords',	'CMS.Fields.Types.MapCoords');
		CMS::field_type('fieldset',	'CMS.Fields.Types.FieldSet');
		CMS::field_type('array',	'CMS.Fields.Types.Array');
	}

	static function type_by_class($class) {
		$class = Core_Types::virtual_class_name_for($class);
		return array_search($class, CMS::$fields_types);
	}

	static function validate_parms($parms) {
		if (is_string($parms)) $parms = array('type' => $parms);
		return $parms;
	}

	static function type($type) {
		if (is_array($type)) $type = isset($type['type'])? $type['type'] : 'input';
		if (!is_string($type)||$type=='') $type = 'input';
		$type = mb_strtolower($type);

		if (!isset(self::$types[$type])) {
			if (isset(CMS::$fields_types[$type])) {
				$module = CMS::$fields_types[$type];
			}

			else if (isset(CMS::$fields_types['input'])) {
				$module = CMS::$fields_types['input'];
			}

			else {
				throw new Exception('Fields initialization error');
			}

			$dir = IO_FS::File(Core::loader()->file_path_for($module))->dir_name;

			Core::autoload($module);

			$object = Core::make($module);
			$object->dir($dir);
			self::$types[$type] = $object;

		}
		return self::$types[$type];
	}

	static function assign_from_object($form,$object,$fields) {
		if (!$fields) return false;
		foreach($fields as $name => $data) {
			$tobj = self::type($data);
			$tobj->assign_from_object($form,$object,$name,$data);
		}
	}

	static function assign_to_object($form,$object,$fields) {
		if (!$fields) return false;
		foreach($fields as $name => $data) {
			$tobj = self::type($data);
			$tobj->assign_to_object($form,$object,$name,$data);
		}
	}

	static function form_fields($form,$fields) {
		if (!$fields) return $form;
		foreach($fields as $name => $data) {
			$tobj = self::type($data);
			$tobj->form_fields($form,$name,$data);
			$tobj->form_validator($form,$name,$data);
		}
		return $form;
	}

	static function process_form($form,$request) {
		$rc = array();
		$p = $form->process($request);
		if (!$p) $rc = $form->validator->errors->property_errors;
		return $rc;
	}

	static function form($form,$fields,$view,$layout=false) {
		if (!$fields) return $view;
		foreach($fields as $name => $data) {
			$tobj = self::type($data);
			$tobj->view($view);
			$tobj->draw_in_layout($name,$data,$layout);
		}
	}

	static public function fields_to_columns($fields, $table_name = null, &$schema = array()) {
		$schema = self::fields_to_schema($fields, $table_name, $schema);
		$res = array();
		if (isset($schema['columns'])) {
			foreach ($schema['columns'] as $name => $data) {
				if (is_string($name)) $res[] = $name;
				else if (isset($data['name'])) $res[] = $data['name'];
			}
		}
		return $res;
		return isset($schema['columns']) ? array_keys($schema['columns']) : array();
	}


	static public function fields_to_schema($fields, $table_name = null, &$schema = array()) {
		//TODO: cache
		//$schema = array();
		foreach($fields as $field_name => $data) {
			$type = self::type($data);
			$sqltype = isset($data['sqltype'])? $data['sqltype'] : $type->sqltype();
			if (isset($data['sqltypes'])) {
				foreach ($data['sqltypes'] as $sub_name => $sub_type) {
					self::column_schema($sub_name, $sub_type, $schema, $table_name, $field_name);
				}
			}
			else if (isset($data['schema'])) {
				$schema = array_merge_recursive($schema, $data['schema']);
			}
			else if ($sqltypes = $type->sqltypes($field_name,$data)) {
				foreach ($sqltypes as $sub_name => $sub_type) {
					self::column_schema($sub_name, $sub_type, $schema, $table_name, $field_name);
				}
			}
			else if ($sqltype) {
				self::column_schema($field_name, $sqltype, $schema, $table_name, $field_name);
			}
		}
		return $schema;
	}

	static function column_schema($db_name , $src, &$schema, $table_name = null, $field_name = null) {
		$type = 'text';
		$length = false;
		$size = false;
		$index = false;
		$precision = false;
		$scale = false;
		$index_length = false;
		$default = '';
		$nn = true;
		$src = trim(strtolower($src));

		if ($m = Core_Regexps::match_with_results('{^(.+)\s+index(\(\d+\))?$}',$src)) {
			$index = true;
			$index_length = trim($m[2], '() ');
			$src = trim($m[1]);
		}

		$type = $src;
		if ($m = Core_Regexps::match_with_results('{^([a-z]+)\((\d+)\)$}',$src)) {
			$type = $m[1];
			$length = trim($m[2]);
		}

		if ($type=='bigtext'||$type=='longtext') {
			$type = 'text';
			$size = 'big';
		}

		if ($type=='tinyint') {
			$type = 'int';
			$size = 'tiny';
		}

		if ($type=='price') {
			$type = 'numeric';
			$precision = 10;
			$scale = 2;
			$default = '0.00';
		}

		if ($type=='datetime') {
			$default = '0000-00-00 00:00:00';
		}

		if ($type=='int'||$type=='serial') {
			$default = '0';
		}

		if ($type=='float'||$type=='double') {
			$type = 'float';
			$default = 0.0;
		}

		$rc = array('name' => $db_name, 'type' => $type, 'not null' => $nn, 'default' => $default, 'index' => $index);
		if ($length) $rc['length'] = $length;
		if ($size) $rc['size'] = $size;
		if ($precision) $rc['precision'] = $precision;
		if ($scale) $rc['scale'] = $scale;


		if ($rc['index']) {
			$index_name = "idx_" . ($table_name ? str_replace('_', '', $table_name) : '') . "_{$db_name}";
			$icolumns = $index_length ? array(array($db_name, (int) $index_length)) : array($db_name);
			$schema['indexes'][] = array('name' => $index_name,'columns' => $icolumns);
			unset($rc['index']);
		}
		$rc['__from_field'] = $field_name;
		$schema['columns'][$db_name] = $rc;

		return $rc;
	}

}

//TODO: refactoring
abstract class CMS_Fields_AbstractField {

	protected $dir;
	protected $view;
	private $temp_code = false;



	public function __construct() {}

//CONFIGURE
//--------------------------------------------------
	public function is_upload() {
		return false;
	}

	public function sqltype() {
		return false;
	}

	public function sqltypes($name,$data) {
		return false;
	}
//--------------------------------------------------
//END CONFIGURE

//ACCESS
//--------------------------------------------------
	public function __get($p) {
		switch ($p) {
			case 'view': case 'dir':
			return $this->$p();
			case 'js_eval':
				return $this->$p;
		}
	}

	public function __set($p, $v) {
		switch ($p) {
			case 'view': case 'dir':
			return $this->$p($v);
		}
	}

	public function view($view = null) {
		if ($view instanceof Templates_HTML_Template)
			$this->view = $view;
		return $this->view;
	}

	public function dir($dir = null) {
		return is_null($dir) ? $this->dir : $this->dir = $dir;
	}
//--------------------------------------------------
//EDN ACCESS

//FORM
//--------------------------------------------------
	public function form_fields($form,$name,$data) {

		if ($langs = $this->data_langs($data)) {
			foreach($langs as $lang => $ldata) {
				$form->input($this->name_lang($name,$lang));
			}
			return $form;
		}

		else {
			return $form->input($name);
		}
	}
//--------------------------------------------------
//END FORM

	public function serialized($name,$data) {
		return array();
	}

//LANG
//--------------------------------------------------
	public function enable_multilang() {
		return false;
	}

	public function data_langs($data) {
		if (!$this->enable_multilang()) return false;
		if (!isset($data['multilang'])) return false;
		if (!$data['multilang']) return false;
		$langs = CMS::lang()->langs();
		if (!is_array($langs)) return false;
		if (count($langs)<2) return false;
		return $langs;
	}

	public function name_lang($name,$lang) {
		return "_lang__{$name}__{$lang}";
	}
//--------------------------------------------------
//END LANG

//ASSIGN
//--------------------------------------------------
	public function assign_from_object($form,$object,$name,$data) {
		if (!$this->access($name, $data, 'assign_from_object', $object, $form)) return;
		$value = is_object($object)?$object[$name]:$object;
		if ($langs = $this->data_langs($data)) {
			$values = CMS::lang()->lang_split($value);
			foreach($langs as $lang => $ldata) {
				$_name = $this->name_lang($name,$lang);
				$form[$_name] = isset($values[$lang])? $values[$lang] : '';
			}
		}
		else {
			$form[$name] = $value;
		}
	}

	public function copy_value($from, $to, $name, $data) {
		$to[$name] = $from[$name];
		return $this;
	}

	public function assign_to_object($form,$object,$name,$data) {
		if (!$this->access($name, $data, 'assign_to_object', $object, $form)) return;
		if ($langs = $this->data_langs($data)) {
			$value = '';
			foreach($langs as $lang => $ldata) {
				$_name = $this->name_lang($name,$lang);
				$_value = trim($form[$_name]);
				if ($_value!='') $value .= "%LANG{{$lang}}".$_value;
			}
			$object[$name] = $value;
		}
		else {
			$object[$name] = $form[$name];
		}

		if (isset($data['input formats']))
			$this->process_input_format($form,$object,$name,$data);
	}

	protected function process_input_format($form,$object,$name,$data) {
		$request_name = $this->input_formats_name($name, $data);
		$format_name = !empty(WS::env()->request[$request_name])
			? WS::env()->request[$request_name]
			: $data['default input format'];
		if (isset($data['input formats'][$format_name]['process'])) {
			$process = $data['input formats'][$format_name]['process'];
			$object[$name] = Core::make('Text.Process')->process($object[$name], $process);
		}
	}
//--------------------------------------------------
//END ASSIGN


//CONTROLLER
//--------------------------------------------------

	public function on_before_action($name,$data,$action,$item=false,$fields = array()) {
		return false;
	}

	public function on_after_action($result, $name,$data,$action,$item=false,$fields = array()) {
		return false;
	}


	public function action($name,$data,$action,$item=false,$fields = array()) {
		if (!$this->access($name, $data, 'action', $action, $item, $fields)) return Net_HTTP::forbidden();
		if (method_exists($this, $m = 'action_' . $action)) {
			return $this->$m($name,$data,$action,$item,$fields);
		}
		return false;
	}

	public function action_url($name,$data,$action,$item=false,$args=false) {
		return false;
	}

	public function process_uploads($name,$data,$uploads,$item,$extra) {
	}

	public function process_inserted($name,$data,$item) {
		return false;
	}

	public function action_upload($name,$data,$action,$item=false) {
		$files = array_pop($_POST);
		$files = is_array($files) ? $files : array($files);
		$error = '';
		$msg = '';
		if (count($files)==0) {
			return CMS::lang()->_common->error_file_upload;
		}
		else {
			try {
				$res = $this->upload_files($files, $name, $data, $action, $item);
				if (!empty($res)) return $res[0];
			} catch (Exception $e) {
				return $e->getMessage();
			}
		}
		return CMS::lang()->_common->error_file_upload;
	}

	protected function upload_file($fobject, $name, $data, $action, $item) {
		$file = $fobject->file_array;
		$filename = $this->uploaded_filename($name, $data, $file);
		$code = $this->request('code');
		$dir = $this->dir_path($item,$code,$name,$data);
		if(!empty($file['error'])) {
			return $this->upload_error_message($file['error']);
		}
		else if (!isset($file['tmp_name'])||empty($file['tmp_name'])||$file['tmp_name']=='none') {
			throw new Exception(CMS::lang()->_common->no_file_uploaded); //TODO: Exception class
		}
		else {
			$old = $file['tmp_name'];
			$new = "$dir/$filename";
			$valid = $this->upload_validate($name, $data, $file, $new);
			if ($valid !== true)
				throw new Exception($valid);

			$eres = Events::call('cms.fields.upload', $name, $data, $file, $new);
			if (!is_null($eres) && $eres !== true) return $eres;
			$eres = Events::call("cms.fields.{$name}.upload", $data, $file, $new);
			if (!is_null($eres) && $eres !== true) return $eres;

			if (!is_dir($dir)) CMS::mkdirs($dir);
			CMS::copy($old,$new);

			return $this->upload_return($name, $data, $new, $dir, $filename);
		}
	}

	protected function upload_files($files, $name, $data, $action, $item) {
		$res = array();
		foreach ($files as $fobject) {
			$res = array_merge($res, (array) $this->upload_file($fobject, $name, $data, $action, $item));
		}
		return $res;
	}

	protected function upload_validate($name, $data, $file, $new) {
		return true;
	}

	protected function uploaded_filename($name, $data, $file) {
		return CMS::translit(preg_replace('{[\s+]+}','_',trim($file['name'])));
	}

	protected function upload_return($name, $data, $new_file, $dir, $filename) {
		return 'success';
	}

	public function dir_path($item,$code,$name,$data) {
		$item_id = $item ? $item->id() : 0;
		$dir = false;
		if ($item_id==0) {
			$dir = CMS::temp_dir()."/dir-$code";
		}
		else {
			$dir = $item->homedir(isset($data['private'])&&$data['private']);
			if ($dir=='') die;
			if ($dir[0]!='.'&&$dir[0]!='/') $dir = "./$dir";
		}
		$dir .= "/$name";
		return $dir;
	}
//--------------------------------------------------
//END CONTROLLER


//SUPPORT
//--------------------------------------------------

	public function input_formats_name($name, $data) {
		return $name. '_formats';
	}

	protected function url_class() {
		return 'field-url-' . WS::env()->request->id;
	}

	protected function punset($data) {
		$args = func_get_args();
		foreach($args as $arg) if (is_string($arg)) {
			if (isset($data[$arg])) unset($data[$arg]);
		}
		return $data;
	}

	protected function stdunset($data) {
		if (isset($data['value'])&&!$data['value']) unset($data['value']);
		return $this->punset($data,'type','caption','rcaption','comment','match','tab','help','error_message','edit_only','if_component_exists','__item','__item_id','__items','template','layout','hidden','ajax'
			,'validate_presence','validate_email','validate_match','validate_match_message','validate_confirmation','validate_confirmation_message','validate_ajax','validate_ajax_message','validate_error_message',
			'input formats', 'default input format', 'allow select format', 'template name', 'layout preprocess', 'preprocess', 'attach', 'items', 'tagparms', 'multilang',
			'in_list', 'group', 'in_filters', 'weight', '__table', 'access', 'sqltype'
		);
	}

	public function user() {
		if (Core::is_cli()) return 'cli';
		if (CMS::admin()) return 'admin-'.CMS::env()->admin_auth->user->login;
		if (CMS::$env->auth->user) return CMS::$env->auth->user->id;
		return false;
	}

	public function temp_code() {
		if (!$this->temp_code) {
			$u = $this->user();
			if (!$u) die;
			$t = date('YmdHis');
			$r = rand(1111,9999);
			$this->temp_code = "$t-$r-$u";
		}
		return $this->temp_code;
	}

	protected function upload_error_message($e) {
		switch((int)$e) {
			case 1: return 'The uploaded file exceeds the upload_max_filesize directive in php.ini';
			case 2: return 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form';
			case 3: return 'The uploaded file was only partially uploaded';
			case 4: return 'No file was uploaded.';
			case 6: return 'Missing a temporary folder';
			case 7: return 'Failed to write file to disk';
			case 8: return 'File upload stopped by extension';
		}
		return "Unknown error ($e)";
	}

	public function use_styles() {
		return $this->view->use_styles(func_get_args());
	}

	public function use_scripts() {
		return $this->view->use_scripts(func_get_args());
	}

	public function request($index = null) {
		return is_null($index) ? WS::env()->request : WS::env()->request[(string) $index];
	}

	public function access_default($name, $data, $action, $item = null, $args = array()) {
		//$args = array_slice(func_get_args(), 3);
		$rc = Events::call('cms.fields.access', $name, $data, $action, $item);
		if (!is_null($rc)) return $rc;
		$rc = Events::call('cms.fields.access.' . $action, $name, $data, $item);
		if (!is_null($rc)) return $rc;
		return true;
	}

	public function access($name, $data, $action, $item = null) {
		$args = array_slice(func_get_args(), 4);
		if (Core_Types::is_callable($data['access'])) {
			return Core::invoke($data['access'], array($this, $name, $data, $action, $item, $args));
		}
		return $this->access_default($name, $data, $action, $item, $args);
	}

//--------------------------------------------------
//END SUPPORT

// WIDGET:
//--------------------------------------------------
	public function layout($layout=false,$data=array()) {
		if ($layout&&IO_FS::exists($layout)) return $layout;
		if (isset($data['layout'])&&IO_FS::exists($data['layout'])) return $data['layout'];
		return $this->template($data, $layout ? "layout-$layout" : 'layout');
	}

	public function template($data=array(), $name = 'template') {
		static $template = array();

		if (Templates::is_absolute_path($name)) return $name;

		if (isset($data['template'])) {
			$data_templates = is_string($data['template']) ? array('template' => $data['template']) : $data['template'];
			if (isset($data_templates[$name]) && IO_FS::exists($data_templates[$name]))
				return $data_templates[$name];
		}

		$type = isset($data['type'])? $data['type'] : 'input';
		$key = $type . '_' . $name;
		if (isset($template[$key])) return $template[$key];

		/*if ($name == 'template' && isset($data['template name']))
		  $name = $data['template name'];*/
		$file = $this->dir() . "/$name.phtml";

		if (IO_FS::exists($file)) return $template[$key] = $file;

		$types = array($type);
		$parents = Core_Types::class_hierarchy_for($this);
		if (count($parents) >= 3)
			foreach (array_slice($parents, 1, count($parents) - 2) as $p) {
				$type = CMS_Fields::type_by_class($p);
				if (empty($type))
					$type = CMS_Fields::type_by_class(Core_Types::module_name_for($p));
				$types[] = $type;
			}

		foreach ($types as $t) {
			if (empty($t)) continue;
			if (IO_FS::exists($file = Templates::get_path('fields/' . $t . '/' . $name, '.phtml')))
				return $template[$key] = $file;
		}

		if (IO_FS::exists($file = Templates::get_path('fields/' . $name, '.phtml')))
			return $template[$key] = $file;

		return $template[$key] = $name;
	}

	public function caption_content($name,$data) {
		return false;
	}

	public function draw($name,$data, $template = 'template', $parms = array()) {
		return $this->render($name,$data, $template, $parms);
	}

	public function render($name,$data, $template = 'template', $parms = array()) {
		if ($this->access($name, $data, 'render', $data['__item'], $template)) {
			$template = $this->create_template($name,$data, $template, $parms);
			$this->layout_preprocess($template, $name, $data);
			return $template->render();
		}
	}

	public function create_template($name,$data, $template = 'template', $parms = array()) {
		$template = $this->template($data, $template);
		//FIXME
		$t = !empty($this->view) ? $this->view->spawn($template) : Templates_HTML::Template($template);

		$t->with(array('template' => $template));
		$t->with($parms);
		$this->preprocess($t, $name, $data);
		return $t;
	}

	public function create_layout($name,$data,$layout=false, $template = 'template', $parms = array()) {
		$layout = $this->layout($layout,$data);
		$template = $this->template($data, $template);
		//FIXME
		$l = !empty($this->view) ? $this->view->spawn($layout) : Templates_HTML::Template($layout);
		$l->no_duplicates_in('js')->no_duplicates_in('css');

		$l = $l->with(array(
			'template' => $template,
			'layout' => $layout,
		));
		$l->with($parms);
		$this->layout_preprocess($l, $name, $data);
		$this->preprocess($l, $name, $data);
		return $l;
	}

	public function draw_in_layout($name,$data,$layout=false, $template = 'template', $parms = array()) {
		print $this->render_in_layout($name,$data,$layout);
	}

	public function render_in_layout($name,$data,$layout=false, $template = 'template', $parms = array()) {
		if ($this->access($name, $data, 'render_in_layout', $data['__item'], $layout, $template))
			return $this->create_layout($name,$data,$layout, $template, $parms)->render();
	}

	public function get_item($name, $data) {
		return $data['__item'];
	}

	public function tagparms($name, $data) {
		$r = array_merge(isset($data['tagparms']) ? $data['tagparms'] : array(), $this->stdunset($data));
		$this->validator_tagparms($name, $data, $r);
		return $r;
	}

	protected function preprocess($template, $name, $data) {
		//$form = $this->view->helpers['forms']->form;
		if (isset($template->parms['form'])) {
			$form = $template->helpers['forms']->form = $template->parms['form'];
		}
		else {
			$form = $template->helpers['forms']->form;
		}
		$tagparms = $this->tagparms($name, $data);
		$tagparms['data-field-name'] = $name;
		$item = isset($data['__item'])? $data['__item'] : (isset($data['item']) ? $data['item'] : false);
		$item_id = isset($data['__item_id'])? $data['__item_id'] : 0;
		$template->with(array(
			'form' => $form,
			'tagparms' => $tagparms,
			'name' => $name,
			'_name' => $name,
			'data' => $data,
			'type_object' => $this,
			'item' => $item,
			'item_id' => $item_id
		));
		if (isset($data['preprocess']))
			Core::invoke($data['preprocess'], array($template, $name, $data));
		return $template->allow_filtering(false);
	}

	protected function current_lang() {
		$current_lang = CMS::$default_lang;
		if (isset($_COOKIE['admin-field-lang'])) $current_lang = $_COOKIE['admin-field-lang'];
		return $current_lang;
	}


	protected function layout_preprocess($l, $name, $data) {
		$form_name = $l->helpers['forms']->form->name;
		$this->attach($l, $name, $data);
		if (!isset($data['caption'])) $data['caption'] = '';
		$caption = trim(CMS::lang($data['caption']));
		$rcaption = '';
		if (isset($data['rcaption'])) {
			$caption = '';
			$rcaption = trim(CMS::lang($data['rcaption']));
		}
		if ($caption=='') $caption = '&nbsp;';
		else $caption .= ':';
		
		Events::call("cms.fields.$form_name.$name.caption",$caption,$rcaption);
		Events::call("cms.fields.$form_name.caption",$caption,$rcaption);

		$ccont = $this->caption_content($name,$data);
		if (!$ccont) $ccont = $l->forms->label($name, $caption, array('class' => "label-$name left"));

		if (isset($data['allow select format']) && $data['allow select format'] && isset($data['input formats'])) {
			$select_formats = array();
			foreach ($data['input formats'] as $name => $f) {
				$select_formats[$name] = $f['display name'];
			}
			$l->update_parm('selected_formats', $select_formats);
		}


		$l->with(array(
			'caption' => $caption,
			'rcaption' => $rcaption,
			'current_lang' => $this->current_lang(),
			'ccont' => $ccont
		));

		if (isset($data['layout preprocess']))
			Core::invoke($data['layout preprocess'], array($l, $name, $data));
		return $l;
	}

	protected function attach($template, $name, $data) {
		if (isset($data['attach'])) {
			$method_by_name = array('js' => 'use_scripts', 'css' => 'use_styles');
			foreach ($data['attach'] as $name => $data) {
				$template->{$method_by_name[$name]}($data);
			}
		}
	}

//END WIDGET
//--------------------------------------------------

//VALIDATION
//--------------------------------------------------

	public function validator($form) {
		if (!$form->validator) {
			$validator = Validation::Validator();
			$form->validate_with($validator);
		}
		return $form->validator;
	}

	public function form_validator($form,$name,$data) {
		if (isset($data['validate_presence'])) {
			$this->validator($form)->validate_presence_of($name,$data['validate_presence']);
		}
		if (isset($data['validate_email'])) {
			$this->validator($form)->validate_email_for($name,$data['validate_email']);
		}
		if (isset($data['validate_match'])&&isset($data['validate_match_message'])) {
			$this->validator($form)->validate_format_of($name,$data['validate_match'],$data['validate_match_message']);
		}
		if (isset($data['validate_confirmation'])&&isset($data['validate_confirmation_message'])) {
			$this->validator($form)->validate_confirmation_of($name,$data['validate_confirmation'],$data['validate_confirmation_message']);
		}
		return $form;
	}

	public function validate_email($email) {
		return Core_Regexps::match('{'.$this->email_regexp().'}',$email);
	}

	public function email_regexp() {
		return '^$|^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,4}$';
	}

	public function validator_tagparms($name,$data,&$tagparms) {
		$rc = false;
		if (isset($data['validate_ajax'])) {
			$rc = true;
			$tagparms['data-validate-ajax'] = trim($data['validate_ajax']);
		}
		if (isset($data['validate_presence'])) {
			$rc = true;
			$tagparms['data-validate-presence'] = htmlspecialchars($data['validate_presence']);
		}
		if (isset($data['validate_email'])) {
			$rc = true;
			$tagparms['data-validate-match'] = $this->email_regexp();
			$tagparms['data-validate-match-message'] = $data['validate_email'];
			$tagparms['data-validate-match-mods'] = '';
		}
		if (isset($data['validate_match'])&&isset($data['validate_match_message'])) {
			$rc = true;
			$match = trim($data['validate_match']);
			$mods = '';
			if ($match[0]=='/'||$match[0]=='{') {
				$mods = '';
				if ($m = Core_Regexps::match_with_results('!^(?:{|/)(.+)(?:}|/)([a-z]*)$!',$match)) {
					$match = $m[1];
					$mods = $m[2];
				}
			}
			$tagparms['data-validate-match'] = $match;
			$tagparms['data-validate-match-mods'] = $mods;
			$tagparms['data-validate-match-message'] = htmlspecialchars($data['validate_match_message']);
		}
		if ($rc) {
			$class = isset($tagparms['class'])? $tagparms['class'] : '';
			$class = trim("$class validable");
			$tagparms['class'] = $class;
		}
		return $rc;
	}

//--------------------------------------------------
//END VALIDATION

//DISPLAY
//--------------------------------------------------
	public function view_value($value,$name,$data) {
		if (is_object($value)) $value = $value[$name];
		if (is_string($value)&&$value!=''&&$this->enable_multilang()) {
			$value = CMS::lang($value);
		}
		return $value;
	}

	protected function container_class() {
		$class = get_class($this).'_ValueContainer';
		if (class_exists($class)) return $class;
		return 'CMS.Fields.ValueContainer';
	}

	public function container($name,$data,$item) {
		$class = $this->container_class();
		if (isset($data['container']) && Core_Types::is_subclass_of('CMS.Fields.ValueContainer', $data['container']))
			$class = $data['container'];
		return Core::make($class, $name,$data,$item,$this);
	}
//--------------------------------------------------
//END DISPLAY
}

class CMS_Fields_ValueContainer {

	protected $name;
	protected $data;
	protected $item;
	protected $type;
	protected $forced_lang = false;

	public function __construct($name,$data,$item,$type) {
		$this->name = $name;
		$this->data = $data;
		$this->item = $item;
		$this->type = $type;
	}

	protected function template() {
		$template = CMS::layout_view();
		if (!$template) $template = Templates_HTML::Template('empty');
		return $template;
	}

	public function value() {
		return $this->item->{$this->name};
	}

	public function set($value) {
		$this->item->{$this->name} = $value;
		return $this;
	}

	public function lang($lang=false) {
		$this->forced_lang = $lang? $lang : CMS::site_lang();
		return $this;
	}

	public function change_data(array $data) {
		$this->data = array_merge($this->data, $data);
		return $this;
	}

	public function draw_in_layout($layout = false, $template = 'template', $parms = array()) {
		return $this->type->draw_in_layout($this->name, $this->data, $layout, $template, $parms);
	}

	public function render() {
		if (!$this->type->access($this->name, $this->data, 'container_render', $this->item, $this)) return '';
		$value = $this->value();
		if (is_null($value)) return '';
		if (!is_string($value)) return print_r($value,true);
		if (is_string($value) && $this->type->enable_multilang()) {
			if ($this->forced_lang) {
				$value = CMS::lang($value,$this->forced_lang);
			}
			else {
				$value = CMS::lang($value);
			}
		}
		return $value;
	}

	protected function value_to_path($value) {
		if ($value=='') return false;
		if ($value[0]=='.'||$value[0]=='/') return $value;
		return "./$value";
	}

	public function path() {
		return $this->value_to_path(trim($this->value()));
	}

	public function value_to_url($value) {
		if ($value=='') return false;
		$value = preg_replace('{^\./}','',$value);
		if ($value[0]=='.'||$value[0]=='/') {
			$c = CMS::$current_controller;
			return $c->download_url($value,$this->item,$this->name,$this->data);
		}
		return "/$value";
	}

	public function url() {
		return $this->value_to_url(trim($this->value()));
	}

	public function __toString() {
		return (string) $this->render();
	}

	public function __get($name) {
		if (isset($this->$name)) return $this->$name;
		throw new Core_MissingPropertyException($name);
	}

	public function access($action) {
		return $this->type->access($this->name, $this->data, $action, $this->item);
	}

	public function __call($method, $args) {
		if (method_exists($this->type, $method)) {
			call_user_func_array(array($this->type, $method), $args);
			return $this;
		}
		throw new Core_MissingMethodException($method);
	}

}
