<?php

Core::load('Validation');
Core::load('Net.HTTP.Session');
Core::load('Forms');

abstract class CMS_Controller_AdminTableBase extends CMS_Controller implements Core_ModuleInterface {

	const MODULE  = 'CMS.Controller.AdminTableBase';
	const VERSION = '0.0.0';
	protected $auth_realm = 'admin';

	protected $entity = false;
	protected $object = false;
	protected $dbtable = false;
	protected $orm_name = false;
	protected $orm_for_select = false;
	protected $entity_reflection = false;

	protected $title_list	= 'lang:_common:ta_list';
	protected $title_edit	= 'lang:_common:ta_title_edit';
	protected $title_add	= 'lang:_common:ta_title_add';
	protected $norows		= 'lang:_common:ta_norows';
	protected $del_confirm	= 'lang:_common:ta_del_confirm';
	protected $copy_confirm = false;

	protected $submit_add		= 'lang:_common:ta_submit_add';
	protected $submit_edit		= 'lang:_common:ta_submit_edit';
	protected $submit_mass_edit	= 'lang:_common:ta_submit_mass_edit';
	protected $button_add		= 'lang:_common:ta_button_add';
	protected $button_update	= 'lang:_common:ta_button_update';
	protected $button_filter	= '>>';
	protected $button_list		= 'lang:_common:ta_button_list';

	protected $title_add_in_list	= false;

	protected $right_button_uri	= false;
	protected $right_button_caption	= false;

	protected $right_buttons = false;
	protected $no_right_buttons = false;
	protected $right_button_list = true;

	protected $per_page 	= 20;

	protected $can_add		= true;
	protected $can_delete		= true;
	protected $can_edit		= true;
	protected $can_copy		= false;
	protected $can_massupdate	= true;

	protected $errors = false;

	protected $filters	= array();
	protected $exclude_filters = array();
	protected $filters_form	= array();
	protected $force_filters= array();
	protected $form_fields	= array();
	protected $list_fields	= array('id' => 'ID');

	protected $view_list	= 'list';
	protected $view_add		= 'add';
	protected $view_copy	= 'add';
	protected $view_edit	= 'edit';
	protected $view_form	= 'form';
	protected $view_thead	= 'thead';
	protected $view_tbody	= 'tbody';
	protected $view_errors	= 'errors';
	protected $view_attaches= 'attaches';
	protected $view_gallery = 'gallery';
	protected $view_editgallery = 'editgallery';

	protected $list_style = 'table';
	protected $list_style_parms = array('title' => 'title','announce' => 'announce','thumb'=>'file1');
	protected $thumb_size = false;
	protected $thumb_width = false;
	protected $thumb_height = false;

	protected $extra_navigator = false;
	protected $extra_navigator_on_add = false;
	protected $extra_navigator_on_edit = false;

	protected $embedded_admin = false;

	protected $add_in_list = false;

	protected $template_list = false;
	protected $template_form = false;

	protected $edit_include_before_form = false;
	protected $edit_include_after_form = false;
	protected $add_include_before_form = false;
	protected $add_include_after_form = false;
	protected $list_include_before_table = false;
	protected $list_include_after_table = false;

	protected $upload_fields = array();
	protected $form = false;
	protected $load_entity_module = false;
	protected $attaches_dir = false;
	protected $gallery_dir = false;
	protected $gallery_field = 'files';
	protected $uploads_multi_dir = true;

	protected $form_tabs = false;
	protected $tabs_parms = '{fxAutoHeight:true}';
	protected $sidebar = false;
	protected $sidebar_th = array('valign'=>'top','class' => 'sidebar');
	protected $sidebar_td = array('valign'=>'top','width' => '100%');

	protected $sidebar_width = 250;
	protected $sidebar_tree_parms = 'collapsed: true, unique: true';

	protected $messages_for_get_parms = array();
	protected $image_list_from_gallery = false;

	protected $access = false;

	protected $native_views = '../app/views';

	protected $item_before_assign = false;

	protected $layout_top		= false;
	protected $layout_bottom	= false;
	protected $layout_left		= false;
	protected $layout_right		= false;

	protected $stay_on_edit = false;

	protected function on_before_action() {}
	protected function on_before_list() {}
	protected function on_after_change() {}
	protected function on_after_change_item() {}
	protected function on_after_update_item() {}
	protected function on_after_mass_update_item() {}
	protected function on_after_insert_item() {}
	protected function on_after_copy_item($from,$to) {}
	protected function on_after_delete_item() {}
	protected function on_before_change_item() {}
	protected function on_before_update_item() {}
	protected function on_before_mass_update_item() {}
	protected function on_before_insert_item() {}
	protected function on_before_delete_item() {}
	protected function on_before_action_edit() {}
	protected function on_before_action_add() {}
	protected function on_before_action_copy() {}
	protected function on_row($row) {}
	protected function check_access() { return true; }

	static $field_types = array();


	static function initialize($config=array()) {
		foreach($config as $key => $value) self::$$key = $value;
		self::register_type('input',			new CMS_AdminTable_Field);
		self::register_type('upload',			new CMS_AdminTable_UploadField);
		self::register_type('textarea',			new CMS_AdminTable_TextareaField);
		self::register_type('html', 			self::$field_types['textarea']);
		self::register_type('parms',			new CMS_AdminTable_ParmsField);
		self::register_type('checkbox',			new CMS_AdminTable_CheckboxField);
		self::register_type('datestr', 			new CMS_AdminTable_DateStrField);
		self::register_type('icalendar', 		self::$field_types['datestr']);
		self::register_type('sqldatestr', 		new CMS_AdminTable_SQLDateStrField);
		self::register_type('select', 			new CMS_AdminTable_SelectField);
		self::register_type('relative_select',		new CMS_AdminTable_RelativeSelectField);
		self::register_type('attaches', 		new CMS_AdminTable_AttachesField);
		self::register_type('gallery', 			new CMS_AdminTable_GalleryField);
		self::register_type('multilink',		new CMS_AdminTable_MultilinkField);
		self::register_type('static_multilink',		new CMS_AdminTable_StaticMultilinkField);
		self::register_type('image', 			new CMS_AdminTable_ImageField);
	}

	static function register_type($type,$class) {
		if (!$class instanceof CMS_AdminTable_Field) return false;
		self::$field_types[$type] = $class;
		return $class;
	}

	protected function layout_left() {
		return false;
	}

	protected function layout_right() {
		return false;
	}

	protected function layout_bottom() {
		return false;
	}

	protected function layout_top() {
		return false;
	}

	public function alias() {
		$s = preg_replace('{[^a-z]+}i','',md5(preg_replace('{[^a-z]+}i','',$this->env->request->urn)));
		return $s;
	}

	public function sidebar_tree() {
		return false;
	}

	public function native_views($path) {
		$path = trim($path);
		$path = rtrim($path,'/');
		$path = rtrim($path);
		$this->native_views = $path;
		return $this;
	}

	public function template($name) {
		return "$this->native_views/$name.phtml";
	}

	protected function use_native_views() {
		return $this->use_views_from($this->native_views);
	}

	protected function entity_reflection() {
		if ($this->entity_reflection) return $this->entity_reflection;
		$this->entity_reflection = Core_Types::reflection_for(Core_Strings::replace($this->entity,'.','_'));
		return $this->entity_reflection;
	}

	public function template_path($name) {
		if ($name[0]=='.'||$name[0]=='/') return $name;
		if ($name[0]=='~') return substr($name,1);
		return $this->template($name);
	}

	protected function access_denied() {
		return $this->page_not_found();
	}

	public function user_type($callback) {
		call_user_func($callback);
	}
	
	protected function default_action($action, $args) {
		return 'list';
	}

	public function index($func,$parm) {
		if (!$this->check_access()) return $this->access_denied();
		$this->run_commands('admin_table');
		$this->use_standart_views();
		$this->render_defaults(
			'title_list','norows','can_add','can_delete','can_edit','can_copy','button_add','button_update','button_filter',
			'submit_add','submit_edit','title_edit','title_add','button_list','form_fields','list_fields','view_thead','view_tbody',
			'del_confirm','view_form','view_errors','submit_mass_edit','right_button_uri','right_button_caption','extra_navigator',
			'form_tabs','sidebar','sidebar_th','sidebar_td','template_list','template_form','right_buttons','messages_for_get_parms',
			'extra_navigator_on_add','extra_navigator_on_edit','filters_form','can_massupdate','no_right_buttons','errors',
			'sidebar_width','sidebar_tree_parms','tabs_parms','right_button_list','list_style','thumb_size','title_add_in_list',
			'add_in_list','thumb_width','thumb_height','embedded_admin','copy_confirm'
		);

		if ($this->entity) {
			if (is_string($this->load_entity_module)) Core::load($this->load_entity_module);
			else if ($this->load_entity_module) Core::load($this->entity);
		}
		$this->object = $this->new_object();
		if ($func == 'default')
			$func = $this->default_action($func, $args);
		$r = $this->on_before_action($func,$parm);
		if ($r instanceof Net_HTTP_Response) return $r;
		$action = 'action_'.$func;
		if (!in_array($func,$this->std_actions())) {
			$this->use_native_views();
		}

		else {
			if (!in_array($func,$this->enabled_std_actions())) return $this->access_denied();
		}
		return $this->$action($parm);
	}

	protected function std_actions() {
		return array('list','list_json','add','edit','copy','attaches','gallery','editgallery','del','addattache','delattache','download','massupdate','delfile','aimages','gimages','delgallery','addgallery');
	}

	protected function enabled_std_actions() {
		return $this->std_actions();
	}

	protected function orm_mapper() {
		if (!$this->orm_name) return false;
		$name = $this->orm_name;
		if (is_string($name)) return CMS::orm()->$name;
		return false;
	}

	protected function orm_mapper_for_parms($parms=array()) {
		$mapper = $this->orm_mapper();
		if (!$mapper) return false;
		if(isset($parms['limit'])) {
			$limit = (int)$parms['limit'];
			$offset = (int)$parms['offset'];
			unset($parms['limit']);
			unset($parms['offset']);
			$mapper = $mapper->spawn()->range($limit,$offset);
		}
		foreach($parms as $key => $value) if (!in_array($key,$this->exclude_filters)) $mapper = $mapper->$key($value);
		return $mapper;
	}

	protected function new_object() {
		if ($mapper = $this->orm_mapper()) return $mapper->make_entity();
		if ($tbl = $this->dbtable) return clone DB_SQL::db()->$tbl->prototype;
		return $this->entity_reflection()->newInstance();
	}

	protected function count_all($parms) {
		if ($mapper = $this->orm_mapper_for_parms($parms)) {
			if ($sub = $this->orm_for_select) $mapper = $mapper->downto($sub);
			return $mapper->count();
		}
		if ($tbl = $this->dbtable) return DB_SQL::db()->$tbl->count();
		return $this->object->count_all($parms);
	}

	protected function select_all($parms) {
		if ($mapper = $this->orm_mapper_for_parms($parms)) {
			if ($sub = $this->orm_for_select) $mapper = $mapper->downto($sub);
			return $mapper->select();
		}
		if ($tbl = $this->dbtable) return DB_SQL::db()->$tbl->select();
		return $this->object->select_all($parms);
	}

	protected function load($id) {
		if ($mapper = $this->orm_mapper()) return $mapper[$id];
		if ($tbl = $this->dbtable) return DB_SQL::db()->$tbl->find($id);
		$item = $this->object->load($id);
		return $item;
	}

	protected function delete($id) {
		if ($mapper = $this->orm_mapper()) {
			$item = $mapper[$id];
			return $mapper->delete($item);
		}
		if ($tbl = $this->dbtable) return DB_SQL::db()->$tbl->delete($id);
		return $this->object->delete($id);
	}

	protected function insert($item) {
		if ($mapper = $this->orm_mapper()) return $mapper->insert($item);
		if ($tbl = $this->dbtable) return DB_SQL::db()->$tbl->insert($item);
		return $item->insert();
	}

	protected function update($item) {
		$mapper = $this->orm_mapper();
		if ($mapper = $this->orm_mapper()) return $mapper->update($item);
		if ($tbl = $this->dbtable) return DB_SQL::db()->$tbl->update($item);
		return $item->update();
	}

	protected function item_key() {
		if ($mapper = $this->orm_mapper()) return current($mapper->options['key']);
		if ($tbl = $this->dbtable) DB_SQL::db()->tables[$tbl]->serial;
		return 'id';
	}

	public function item_id($item) {
		if ($mapper = $this->orm_mapper()) {
			$options = $mapper->options;
			$key = $options['key'];
			$key = current($key);
			return $item->$key;
		}
		if ($tbl = $this->dbtable) {
			if ($serial = DB_SQL::db()->tables[$tbl]->serial) return $item->$serial;
			return $item->id;
		}
		return $item->id();
	}

	protected function create_form($uri) {
		$form = Forms::Form('mainform')->action($uri);
		$validator = Validation::Validator();
		$use_validator = false;
		foreach($this->form_fields as $name => $parms) {

			if (isset($parms['if_component_exists'])) {
				if (!CMS::component_exists($parms['if_component_exists'])) continue;
			}

			if (isset($parms['embedded_admin'])&&$this->embedded_admin) {
				if (!$parms['embedded_admin']) continue;
			}

			$type = isset($parms['type'])?
				trim($parms['type']):'input';

			$method = 'create_form_field_'.$type;
			if (method_exists($this,$method)) {
				$this->$method($form,$name,$parms);
			}

			else if (isset(self::$field_types[$type])) {
				self::$field_types[$type]->form_field($form,$name,$parms);
				if ($dir = self::$field_types[$type]->is_upload($parms)) $this->upload_fields[$name] = $dir;
			}

			else {
				$form->input($name);
			}

			if (isset($parms['match'])) {
				if ($parms['match']=='presence') $validator->validate_presence_of($name,$parms['error_message']);
				else if ($parms['match']=='email') $validator->validate_email_for($name,$parms['error_message']);
				else if ($parms['match']=='email presence') {
					$validator->validate_presence_of($name,$parms['error_message']);
					$validator->validate_email_for($name,$parms['error_message']);
				}
				else $validator->validate_format_of($name,$parms['match'],$parms['error_message']);
				$use_validator = true;
			}
		}
		if ($use_validator) $form->validate_with($validator);
		return $form;
	}

	protected function render_list($tpl,$parms) {
		return $this->render($tpl,$parms);
	}

	protected function action_list_json() {
		$filter = array();
		$count = (int)$this->count_all($filter);

		if (isset($this->env->request['start'])) $filter['offset'] = (int)$this->env->request['start'];
		if (isset($this->env->request['limit'])) $filter['limit'] = (int)$this->env->request['limit'];

		$rows = $this->select_all($filter);
		$m = array();
		$key = $this->item_key();
		foreach($rows as $row) {
			$this->transform_list_values($row);
			$this->on_row($row);
			$o = new StdClass();
			$o->id = $this->item_id($row);
			foreach($this->list_fields as $field => $data) {
				$o->$field = $row->$field;
			}
			$m[] = $o;
		}
		$json = json_encode($m);
		print "{success:true,count:$count,rows:$json}";
		die;
	}

	protected function list_url_session_name() {
		return 'admin-table/list-uri';
	}

	protected function action_list($parms) {
		$page_num = (int)$parms;
		if ($page_num<1) $page_num = 1;
		$this->on_before_list($page_num);
		$session = Net_HTTP_Session::Store();
		$session[$this->list_url_session_name()] = $this->request->urn;
		$filter = array();
		foreach($this->filters as $fn) if (isset($_GET[$fn])) $filter[$fn] = $_GET[$fn];
		foreach($this->force_filters as $fk => $fv) $filter[$fk] = $fv;

		$per_page = $this->per_page;
		$cnt = $this->count_all($filter);
		$num_pages = $cnt/$per_page;
		if (floor($num_pages)!=$num_pages) $num_pages = floor($num_pages)+1;
		if ($num_pages<1||$page_num>$num_pages) $num_pages = 1;
		$session['admin-table/list-page'] = $page_num;

		$filter['offset'] = ($page_num-1)*$per_page;
		$filter['limit'] = $per_page;

		$rows = $this->select_all($filter);

		$mass_update = false;
		foreach($this->list_fields as $name => &$field) {
			if (!is_array($field)) $field = array('caption' => $field);
			if (isset($field['edit'])) $mass_update = true;
		}
		foreach($rows as &$row) {
			$_id = $this->item_id($row);
			$row[':edit'] = $this->admin_url('edit',$_id);
			$row[':del'] = $this->admin_url('del',$_id);
			$row[':copy'] = $this->admin_url('copy',$_id);
			$row[':id'] = $_id;
			$this->transform_list_values($row);
			$this->on_row($row);
		}

		if ($this->add_in_list) {
			$action = $this->admin_url('add');
			$this->form = $this->create_form($action);
			$this->assign_form_from($this->object);
		}


		return $this->render_list($this->view_list,array(
			'form' => $this->form,
			'submit' => $this->submit_add,
			'filter' => $filter,
			'count' => $cnt,
			'rows' => $rows,
			'extra_navigator' => $this->extra_navigator,
			'page_navigator'=> $this->page_navigator($page_num,$num_pages,$this->admin_url('list','%')),
			'_add' => $this->admin_url('add'),
		));
	}

	protected function transform_list_values($row) {
		foreach($this->list_fields as $name => $parms) {
			if (isset($parms['type'])) {
				$type = trim($parms['type']);
				$method = "transform_list_$type";
				if (method_exists($this,$method)) $row->$name = $this->$method($row->$name);
				if (isset(self::$field_types[$type])) self::$field_types[$type]->in_list($row,$name,$parms);
			}
		}
	}

	protected function assign_form($item,$force_empty=false) {
		$this->item_before_assign = clone $item;

		$o = array();
		foreach($this->upload_fields as $field => $dir) $o[$field] = $item->$field;
		$this->form->assign_to($item);

		foreach($this->form_fields as $name => $parms) {
			$type = isset($parms['type'])?
				trim($parms['type']):'input';
			$method = "transform_type_$type";
			if (method_exists($this,$method)) $item->$name = $this->$method(isset($this->form[$name])?$this->form[$name]:false,$item,$parms);
			if (isset(self::$field_types[$type])) self::$field_types[$type]->assign_to_object($item,$this->form,$name,$parms);
		}

		foreach($this->upload_fields as $field => $dir) {
			if (is_null($item->$field)) $item->$field = $o[$field];
			if ($force_empty) $item->$field = '';
		}
	}

	protected function assign_form_from($item) {
		$this->form->assign_from($item);
		foreach($this->form_fields as $name => $parms) {
			$type = isset($parms['type'])?
				trim($parms['type']):'input';
			if (isset(self::$field_types[$type])) self::$field_types[$type]->assign_to_form($this->form,$item,$name,$parms);
		}
	}

	protected function render_add($tpl,$parms) {
		return $this->render($tpl,$parms);
	}

	protected function redirect_after_add($item) {
		return $this->admin_url('list',$this->last_list_page());
	}

	protected function action_add($id=false) {
		if (!$this->can_add) return $this->page_not_found();
		$id = (int)$id;
		$this->on_before_action_add($id);
		$action = $id>0?$this->admin_url('add',$id): $this->admin_url('add');
		$this->form = $this->create_form($action);
		$this->assign_form_from($this->object);

		if ($this->request->method_name=='post') {
			$valid = $this->form->process($this->request);

			if ($valid) {
				$errors = $this->validate_extra('add');
				if ($errors) {
					$valid = false;
					$this->errors = $errors;
				}
			}

			else if ($this->form->validator) $this->errors = $this->form->validator->errors->property_errors;

			if ($valid) {
				$item = $this->new_object();
				$this->assign_form($item,true);
				$this->on_before_change_item($item);
				$this->on_before_insert_item($item);
				$this->insert($item);
				if (sizeof($this->upload_fields)>0) {
					$this->process_uploads($item);
					$this->update($item);
				}
				$this->on_after_change('add');
				$this->on_after_change_item($item);
				$this->on_after_insert_item($item);
				Events::call('admin.change',$item);
				return $this->redirect_to($this->redirect_after_add($item));
			}
		}

		return $this->render_add($this->view_add,array(
			'form' => $this->form,
			'submit' => $this->submit_add,
			'item' => array(),
			'_list' => $this->admin_url('list',$this->last_list_page()),
		));
	}

	protected function render_copy($tpl,$parms) {
		return $this->render($tpl,$parms);
	}

	protected function redirect_after_copy($item) {
		return $this->redirect_after_add($item);
	}

	protected function action_copy($id) {
		if (!$this->can_copy) return $this->page_not_found();
		$id = (int)$id;

		$item = $this->load($id);
		$oitem = $item;
		if (!$item) return $this->page_not_found();

		$this->on_before_action_copy($item);
		$action = $this->admin_url('copy',$id);
		$this->form = $this->create_form($action);
		$this->assign_form_from($item);

		if ($this->request->method_name=='post') {
			$valid = $this->form->process($this->request);

			if ($valid) {
				$errors = $this->validate_extra('copy');
				if ($errors) {
					$valid = false;
					$this->errors = $errors;
				}
			}

			else if ($this->form->validator) $this->errors = $this->form->validator->errors->property_errors;

			if ($valid) {
				$item = $this->new_object();
				$this->assign_form($item,true);
				$this->on_before_change_item($item);
				$this->on_before_insert_item($item);
				$this->insert($item);
				if (sizeof($this->upload_fields)>0) {
					$this->process_uploads($item);
					$this->update($item);
				}
				$this->on_after_change('copy');
				$this->on_after_change_item($item);
				$this->on_after_insert_item($item);
				$this->on_after_copy_item($oitem,$item);
				Events::call('admin.change',$item);
				return $this->redirect_to($this->redirect_after_copy($item));
			}
		}

		return $this->render_copy($this->view_copy,array(
			'form' => $this->form,
			'submit' => $this->submit_add,
			'item' => $item,
			'_list' => $this->admin_url('list',$this->last_list_page()),
		));
	}

	protected function render_edit($tpl,$parms) {
		return $this->render($tpl,$parms);
	}

	protected function last_list_page() {
		$session = Net_HTTP_Session::Store();
		$page = (int)$session['admin-table/list-page'];
		if ($page==0) $page = 1;
		return $page;
	}

	protected function redirect_after_edit($item) {
		if ($this->stay_on_edit) return $this->admin_url('edit',$this->item_id($item));
		return $this->admin_url('list',$this->last_list_page());
	}

	protected function extra_validator() {
		return true;
	}

	protected function validate_extra($action) {
		$errors = $this->extra_validator();
		if (is_string($errors)) return array($errors);
		if (Core_Types::is_iterable($errors)) return $errors;
	}

	protected function action_edit($id) {
		if (!$this->can_edit) return $this->page_not_found();
		$item = $this->load($id);
		if (!$item) return $this->page_not_found();

		$r = $this->on_before_action_edit($item);
		if ($r=='denied') return $this->access_denied();

		$this->form = $this->create_form($this->admin_url('edit',$id));

		if ($this->request->method_name=='post') {
			$valid = $this->form->process($this->request);

			if ($valid) {
				$errors = $this->validate_extra('edit');
				if ($errors) {
					$valid = false;
					$this->errors = $errors;
				}
			}

			else if ($this->form->validator) $this->errors = $this->form->validator->errors->property_errors;

			if ($valid) {
				$this->assign_form($item);
				$this->process_uploads($item);
				$this->on_before_change_item($item);
				$this->on_before_update_item($item);
				$this->update($item);
				$this->on_after_change('edit');
				$this->on_after_change_item($item);
				$this->on_after_update_item($item);
				Events::call('admin.change',$item);
				return $this->redirect_to($this->redirect_after_edit($item));
			}
		}

		else {
			$this->assign_form_from($item);
		}

		return $this->render_edit($this->view_edit,array(
			'form' => $this->form,
			'submit' => $this->submit_edit,
			'item' => $item,
			'item_id' => $this->item_id($item),
			'stay_on_edit' => $this->stay_on_edit,
			'_list' => $this->redirect_after_edit($item),
		));
	}

	protected function action_download() {
		$file = $this->env->request['file'];
		return $this->download_file($file);
	}

	protected function upload_destination($id,$field,$ext) {
		$d = $this->form_fields[$field];
		$t = ''; if (isset($d['filename_with_timestamp'])&&$d['filename_with_timestamp']) $t = '-'.time();
		return "$field-$id$t$ext";
	}

	protected function process_uploads($item) {
		$id = $this->item_id($item);
		$uploaded = array();
		$ruploaded = array();
		$udirs = array();

		$tmpdir = CMS::temp_dir();

		foreach($this->upload_fields as $field => $dir) {

			if (is_string($dir)) {
				$dir = rtrim($dir,'/');
				if ($this->uploads_multi_dir) {
					$did = floor($id/100)*100;
					$dir .= "/$did";
				}
				if (!IO_FS::exists($dir)) {
					$this->mkdirs($dir);
				}
				$udirs[$field] = $dir;
			}

			if (is_object($this->form[$field])) {
				$path = $this->form[$field]->path;
				$original_name = $this->form[$field]->original_name;
				$ext = '';
				if ($m = Core_Regexps::match_with_results('{(\.[a-z0-9_]+)$}i',$original_name)) $ext = strtolower($m[1]);

				$name = $this->upload_destination($id,$field,$ext,$original_name);
				if (is_string($dir)&&$dir!='') $name = "$dir/$name";
				$name = trim($name);
				move_uploaded_file($path,$name);

				$ncname = $tmpdir.'/upl-'.md5($name).$ext;
				$ncres = @copy($name,$ncname);

				$type = self::$field_types[$this->form_fields[$field]['type']];
				if ($type) {
					$r = $type->after_upload($name,$this->form_fields[$field]);
					if ($r) $name = $r;
				}

				$this->chmod_file($name);

				$old = trim($this->item_before_assign->$field);
				if ($old!=''&&$name!=$old&&IO_FS::exists($old)) IO_FS::rm($old);

				$item->$field = $name;
				$uploaded[$field] = $ncres? $ncname : $name;
				$ruploaded[$field] = $name;
			}
		}

		foreach($udirs as $field => $dir) {
			$type = self::$field_types[$this->form_fields[$field]['type']];
			if ($type) {
				$old = trim($this->item_before_assign->$field);
				$name = rtrim($udirs[$field],'/ ').'/'.$this->upload_destination($id,$field,'.$$$','null.$$$');
				$name = $type->after_all_uploads($field,$this->form_fields[$field],$name,$uploaded,$old);
				if ($name) {
					$this->chmod_file($name);
					if ($old!=''&&$name!=$old&&IO_FS::exists($old)) IO_FS::rm($old);
					$item->$field = $name;
				}
			}
		}

		foreach($uploaded as $field => $file)
			if ($file!=$ruploaded[$field])
				@unlink($file);
	}

	protected function redirect_after_del($item) {
		return $this->admin_url('list',$this->last_list_page());
	}

	protected function action_del($id) {
		if (!$this->can_delete) return $this->page_not_found();
		$item = $this->load($id);
		if (!$item) return $this->page_not_found();
		$redirect = $this->redirect_after_del($item);
		$this->on_before_delete_item($id);
		$this->delete($id);
		$this->on_after_delete_item($id);
		$this->on_after_change('del');
		Events::call('admin.change',$item);
		return $this->redirect_to($redirect);
	}

	protected function action_massupdate() {
		$session = Net_HTTP_Session::Store();
		$_list = trim($session[$this->list_url_session_name()]);
		if ($_list=='') $_list = $this->admin_url('list',1);
		if (!$this->can_massupdate) return $this->redirect_to($_list);
	        $ids = $this->request['ids'];
	        foreach($ids as $id) {
	        	$item = $this->load($id);
			$old = clone $item;
			if ($this->list_style=='gallery') {
				$_isactive = $this->list_field_parm('isactive','source','isactive');
				$_ord = $this->list_field_parm('ord','source','ord');
				if ($_isactive) $item->$_isactive = $this->request[$_isactive][$id];
				if ($_ord) $item->$_ord = $this->request[$_ord][$id];
			}
			else if ($this->list_style=='lent') {
				$_isactive = $this->list_field_parm('isactive','source','isactive');
				if ($_isactive) $item->$_isactive = $this->request[$_isactive][$id];
			}
			else foreach($this->list_fields as $name => $parms) {
				if (is_array($parms)&&isset($parms['edit'])) {
					$p = $parms['edit'];
					if (is_string($p)) $p = array('type'=>$p); else $p = (array)$p;
					$item->$name = $this->request[$name][$id];
				}
			}
			$cu = $this->on_before_mass_update_item($item,$old);
	        	if ($cu!==false) $this->update($item);
			$this->on_after_mass_update_item($item,$old);
	        }
		Events::call('admin.change');
	        $this->on_after_change('massupdate');
		return $this->redirect_to($_list);
	}

	protected function action_delfile($parms) {
		if ($m = Core_Regexps::match_with_results('/^(\d+)-(.+)$/',$parms)) {
			$id = $m[1];
			$field = $m[2];
			$item = $this->load($id);
			if (!$item) return $this->page_not_found();
			$file = trim($item->$field);
			if ($file!='') IO_FS::rm($file);
			$item->$field = '';
			$this->update($item);
			Events::call('admin.change',$item);
			return $this->redirect_to($this->admin_url('edit',$id));
		}
		return $this->page_not_found();
	}

	protected function iddir($id) {
		$d = floor($id/100)*100;
		return "$d/$id";
	}

	protected function action_attaches($id) {
		$attaches = array();
		if ($this->attaches_dir) {
			$dir = trim($this->attaches_dir);
			if ($dir!=''&&$dir[0]!='.'&&$dir[0]!='/') $dir = "./$dir";
			$dir = "$dir/".$this->iddir($id);
			if (IO_FS::exists($dir))
			foreach(IO_FS::Dir($dir) as $f) {
				$fp = $f->path;
				if ($m = Core_Regexps::match_with_results('{/([^/]+)$}',$fp)) $fp = $m[1];
				$attaches[$fp] = array(
					'name' => $fp,
					'path' => $f->path,
				);
			}
		}
		return $this->render($this->view_attaches,array(
			'dir' => $this->attaches_dir,
			'item_id' => $id,
			'attaches' => $attaches,
		));
	}

	protected function action_addattache($id) {
		if (!$this->attaches_dir) die;
		$id = (int)$id;
		$file = $_FILES['up'];
		$name = trim($file['name']);
		$tmp_name = trim($file['tmp_name']);
		if ($tmp_name!='') {
			$dir = "./$this->attaches_dir/".$this->iddir($id);
			$this->mkdirs($dir,0775);
			$name = strtolower($name);
			$name = trim(preg_replace('{[^a-z0-9_\.\-]}','',$name));
			if ($name=='') $name = 'noname';
			if ($name[0]=='.') $name = "noname.$name";
			move_uploaded_file($tmp_name,"$dir/$name");
			$this->chmod_file("$dir/$name");
			Events::call('admin.change');
		}
		return $this->redirect_to($this->admin_url('attaches',$id));
	}

	protected function action_delattache($id) {
		if (!$this->attaches_dir) die;
		$id = (int)$id;
		$dir = "./$this->attaches_dir/".$this->iddir($id);
		$file = "$dir/".$_GET['file'];
		@IO_FS::rm($file);
		Events::call('admin.change');
		return $this->redirect_to($this->admin_url('attaches',$id));
	}

	public function action_aimages($id) {
		if ($this->image_list_from_gallery) return $this->action_gimages($id);
		if (!$this->attaches_dir) die;
		$id = (int)$id;
		$dir = "./$this->attaches_dir/".$this->iddir($id);
		$ar = array();
		if (IO_FS::exists($dir)) {
			foreach(IO_FS::Dir($dir) as $f) {
				$fp = $f->path;
				if ($m = Core_Regexps::match_with_results('{/([^/]+)$}',$fp)) $fp = $m[1];
				$ar[] = '["'.$fp.'","'.$this->file_url($f->path).'"]';
			}
		}
		echo 'var tinyMCEImageList = new Array('.implode(',',$ar).');';
		die;
	}

	public function action_gimages($id) {
		$item = $this->load($id);
		if (!$item) return $this->page_not_found();
		$files = $item[$this->gallery_field];
		if (is_string($files)) $files = unserialize($files);
		if (!is_array($files)) $files = array();
		$m = array();
		foreach ($files as $file) {
			$name = htmlspecialchars($file['name']);
			$uri = $file['file_uri'];
			$m[] = '["'.$name.'","'.$uri.'"]';
		}
		echo 'var tinyMCEImageList = new Array('.implode(',',$m).');';
		die;
	}

	public function filters_string($in=array()) {
		$out = '';
		foreach($this->filters as $filter) {
			if (isset($in[$filter])) {
				$out .= ($out==''?'':'&') . $filter . '=' . $in[$filter];
			}
			else if (isset($_GET[$filter])&&trim($_GET[$filter])!='') {
				$out .= ($out==''?'':'&') . $filter . '=' . urlencode($_GET[$filter]);
			}
		}
		return $out;
	}

	public function admin_url($p1='',$p2='',$p3=array()) {

		$uri = $this->urls->admin_url($p1,$p2);
		$parms = '';

		if (is_array($p3)) {
			$parms = $this->filters_string($p3);
			if ($parms!='') $parms = "?$parms";
		}

		else {
			$parms = "?$p3";
			if ($p3=='') $parms = '';
		}
		if ($p1=='download') $parms = '';
		return $uri.$parms;
	}

	protected function gallery_sort_cb($a,$b) {
		if ($a['ord']>$b['ord']) return 1;
		if ($a['ord']<$b['ord']) return -1;
		return 0;
	}

	protected function action_gallery($id) {
		$item = $this->load($id);
		if (!$item) return $this->page_not_found();

		$files = $item[$this->gallery_field];
		if (is_string($files)) $files = unserialize($files);
		if (!is_array($files)) $files = array();

		CMS::gallery_sort($files);

		return $this->render($this->view_gallery,array(
			'dir' => $this->gallery_dir,
			'item_id' => $id,
			'files' => $files,
		));
	}

	protected function action_delgallery($id) {
		$fid = (int)$_GET['file'];
		$item = $this->load($id);
		if (!$item) return $this->page_not_found();
		$files = $item[$this->gallery_field];
		if (is_string($files)) $files = unserialize($files);
		if (!is_array($files)) $files = array();

		@IO_FS::rm($files[$fid]['file_path']);
		@IO_FS::rm($files[$fid]['preview_path']);

		unset($files[$fid]);
		$item[$this->gallery_field] = $files;
		$this->update($item);
		Events::call('admin.change',$item);
		return $this->redirect_to($this->admin_url('gallery',$id));
	}

	protected function action_editgallery($id) {
		$fid = (int)$_GET['file'];
		$item = $this->load($id);
		if (!$item) return $this->page_not_found();
		$files = $item[$this->gallery_field];
		if (is_string($files)) $files = unserialize($files);
		if (!is_array($files)) $files = array();


		$efile = $files[$fid];

		if ($this->request->method_name=='post') {

			$name = trim($this->request['name']); if ($name=='') $name = 'noname';
			$ord = trim($this->request['ord']);
			$alias = trim($this->request['alias']);
			$file = $_FILES['up'];
			$preview = $_FILES['pr'];
			$dir = "/$this->gallery_dir/".$this->iddir($id);
			$this->mkdirs(".$dir",0777);
			$files[$fid]['ord'] = $ord;
			$files[$fid]['name'] = $name;
			$files[$fid]['alias'] = $alias;

			if (trim($file['tmp_name'])!='') {
				$file_name = $file['name'];
				$ext = ''; if (preg_match('/\.([a-z0-9_-]+)$/i',$file_name,$m)) $ext = strtolower($m[1]);
				$_ext = $ext==''? '' : ".$ext";
				$file_name_save = "$fid$_ext";
				$path_name_save = "$dir/$file_name_save";
				move_uploaded_file($file['tmp_name'],".$path_name_save");
				$this->chmod_file(".$path_name_save");
				$files[$fid]['file_ext'] = $ext;
				$files[$fid]['file_name_original'] = $file_name;
				$files[$fid]['file_name'] = $file_name_save;
				$files[$fid]['file_path'] = ".$path_name_save";
				$files[$fid]['file_uri'] = $path_name_save;
			}


			if (trim($preview['tmp_name'])!='') {
				$file_name = $preview['name'];
				$ext = ''; if (preg_match('/\.([a-z0-9_-]+)$/i',$file_name,$m)) $ext = strtolower($m[1]);
				$_ext = $ext==''? '' : ".$ext";
				$file_name_save = "p$fid$_ext";
				$path_name_save = "$dir/$file_name_save";
				move_uploaded_file($preview['tmp_name'],".$path_name_save");
				$this->chmod_file(".$path_name_save");
				$files[$fid]['preview_ext'] = $ext;
				$files[$fid]['preview_name_original'] = $file_name;
				$files[$fid]['preview_name'] = $file_name_save;
				$files[$fid]['preview_path'] = ".$path_name_save";
				$files[$fid]['preview_uri'] = $path_name_save;
			}

			$item[$this->gallery_field] = $files;
			$this->update($item);
			Events::call('admin.change',$item);
			return $this->redirect_to($this->admin_url('gallery',$id));
		}
		return $this->render($this->view_editgallery,array(
			'dir' => $this->gallery_dir,
			'item_id' => $id,
			'files' => $files,
			'file' => $efile,
			'file_id' => $fid,
		));

	}


	protected function action_addgallery($id) {
		$item = $this->load($id);
		if (!$item) return $this->page_not_found();

		$files = $item[$this->gallery_field];
		if (is_string($files)) $files = unserialize($files);
		if (!is_array($files)) $files = array();

		$name = trim($this->request['name']); if ($name=='') $name = 'noname';
		$ord = trim($this->request['ord']);
		$alias = trim($this->request['alias']);
		$file = $_FILES['up'];
		$preview = $_FILES['pr'];
		if (trim($file['tmp_name'])!='') {

			$inc = $item[$this->gallery_field.'_autoinc']+1;
			$item[$this->gallery_field.'_autoinc'] = $inc;
			$dir = "/$this->gallery_dir/".$this->iddir($id);
			$this->mkdirs(".$dir",0775);
			$file_name = $file['name'];
			$ext = ''; if ($m = Core_Regexps::match_with_results('/\.([a-z0-9_-]+)$/i',$file_name,$m)) $ext = strtolower($m[1]);
			$_ext = $ext==''? '' : ".$ext";
			$file_name_save = "$inc$_ext";
			$path_name_save = "$dir/$file_name_save";
			move_uploaded_file($file['tmp_name'],".$path_name_save");
			$this->chmod_file(".$path_name_save");
			$files[$inc]['name'] = $name;
			$files[$inc]['ord'] = $ord;
			$files[$inc]['alias'] = $alias;
			$files[$inc]['file_ext'] = $ext;
			$files[$inc]['file_name_original'] = $file_name;
			$files[$inc]['file_name'] = $file_name_save;
			$files[$inc]['file_path'] = ".$path_name_save";
			$files[$inc]['file_uri'] = $path_name_save;

			if (trim($preview['tmp_name'])!='') {
				$file_name = $preview['name'];
				$ext = ''; if (preg_match('/\.([a-z0-9_-]+)$/i',$file_name,$m)) $ext = strtolower($m[1]);
				$_ext = $ext==''? '' : ".$ext";
				$file_name_save = "p$inc$_ext";
				$path_name_save = "$dir/$file_name_save";
				move_uploaded_file($preview['tmp_name'],".$path_name_save");
				$this->chmod_file(".$path_name_save");
				$files[$inc]['preview_ext'] = $ext;
				$files[$inc]['preview_name_original'] = $file_name;
				$files[$inc]['preview_name'] = $file_name_save;
				$files[$inc]['preview_path'] = ".$path_name_save";
				$files[$inc]['preview_uri'] = $path_name_save;
			}

			$item[$this->gallery_field] = $files;
			$this->update($item);
			Events::call('admin.change',$item);
		}

		return $this->redirect_to($this->admin_url('gallery',$id));

	}

	public function type_template($type) {
		if (isset(self::$field_types[$type])) return $this->field_template_path(self::$field_types[$type]->template());
		return $this->field_template_path($type);
	}

	public function type_full_template($type) {
		if (isset(self::$field_types[$type])) return $this->field_template_path(self::$field_types[$type]->full_template());
		return $this->field_template_path($type);
	}

	public function type_container_class($type,$parms) {
		if (isset(self::$field_types[$type])) return self::$field_types[$type]->container_class($parms);
		return 'input-text';
	}

	public function list_field_parm($field,$parm,$def = false) {
		$data = isset($this->list_fields[$field])?
			$this->list_fields[$field]:false;
		if (!$data) return $def;
		if (is_string($data)) $data = array('caption' => $data);
		if ($parm=='source'&&!isset($data[$parm])&&isset($data['caption'])) return $data['caption'];
		if (!isset($data[$parm])) return $def;
		return $data[$parm];
	}

	public function get_layout($type=false) {
		if (!$this->layout_top) $this->layout_top = $this->layout_top();
		if (!$this->layout_bottom) $this->layout_bottom = $this->layout_bottom();
		if (!$this->layout_left) $this->layout_left = $this->layout_left();
		if (!$this->layout_right) $this->layout_right = $this->layout_right();

		if ($type=='top') return $this->layout_top;
		if ($type=='bottom') return $this->layout_bottom;
		if ($type=='left') return $this->layout_left;
		if ($type=='right') return $this->layout_right;

		if (!$this->layout_top && !$this->layout_bottom && !$this->layout_left && !$this->layout_right) return false;
		return true;
	}

	protected function layout_tree($data) {
		return new CMS_AdminTable_LayoutTree($data);
	}

	protected function layout_menu($data) {
		return new CMS_AdminTable_LayoutMenu($data);
	}

        // ------------------------------------------------------------------------------

	abstract public function items_for_select($s);
	abstract protected function parse_parms($in);
	abstract protected function use_standart_views();
	abstract public function standart_template($tpl);
	abstract protected function page_navigator($page_num,$num_pages,$tpl);
	abstract protected function mkdirs($dir);
	abstract protected function chmod_file($name);
	abstract protected function file_url($path);
	abstract protected function field_template_path($name);

}

class CMS_Controller_AdminTable_Exception extends Core_Exception {}

class CMS_Controller_AdminTable_FieldException extends CMS_Controller_AdminTable_Exception {
	public function __construct($name) {
		parent::__construct("Bad field definition '$name'");
	}
}


class CMS_AdminTable_Field {

	public function is_upload() { return false; }

	public function after_upload($filename) { return false; }

	public function after_all_uploads($name,$parms,$filename,$uploaded) { return false; }

	public function in_list($row,$name,$parms) {}

	public function assign_to_form(Forms_Form $form,$object,$name,$parms) {}

	public function assign_to_object($object,Forms_Form $form,$name,$parms) {}

	public function container_class() {
		return 'input-text';
	}

	public function full_template() {
		return 'default-layout';
	}

	public function template() {
		return 'default-input';
	}

	public function form_field($form,$name) {
		return $form->input($name);
	}

	public function jsvalidation($form,$name,$parms) {
		$match = trim($parms['match']);
		$error_message = trim($parms['error_message']);
		$caption = trim($parms['caption']);
		if ($caption=='') $caption = $name;
		if ($error_message=='') $error_message = "Error: $caption";
		if ($match!=''&&$match[0]=='/') {
			$id = $form->name . '_' . $name;
			return "if (!$('#$id').get(0).value.match($match)) { return wrong_field_value('$id','".htmlspecialchars($error_message)."'); }";
		}
		return '';
	}

}


class CMS_AdminTable_AttachesField extends CMS_AdminTable_Field {
	public function container_class() { return 'input-attaches'; }
	public function template() { return 'attaches'; }
	public function form_field($form,$name) {}
}

class CMS_AdminTable_GalleryField extends CMS_AdminTable_Field {
	public function container_class() { return 'input-gallery'; }
	public function template() { return 'gallery'; }
	public function form_field($form,$name) {}
}


class CMS_AdminTable_UploadField extends CMS_AdminTable_Field {
	public function is_upload($parms) { return is_string($parms['dir'])? trim($parms['dir']):true; }
	public function container_class() { return 'input-upload';}
	public function template() { return 'upload'; }
	public function form_field($form,$name) {
		$form->upload($name);
		$form->enctype("multipart/form-data");
	}
	public function after_upload($filename,$parms) {
		if (!isset($parms['after_upload'])) return false;
		$f = $parms['after_upload'];
		if (is_callable($f)) return call_user_func($f,$filename,$parms);
		return false;
	}
}


class CMS_AdminTable_DateStrField extends CMS_AdminTable_Field {
	protected function format_datetime($value,$parms) {
		$value = (int)$value;
		if ($value==0) return '';
		$format = 'd.m.Y';
		$wt = isset($parms['with_time'])&&$parms['with_time'];
		$ws = isset($parms['with_seconds'])&&$parms['with_seconds'];

		if ($wt||$ws) $format .= ' - G:i';
		if ($ws) $format .= ':s';
		return CMS::date($format,$value);
	}
	public function in_list($row,$name,$parms) {
		$row->$name = $this->format_datetime($row->$name,$parms);
	}
	public function assign_to_form(Forms_Form $form,$object,$name,$parms) {
		$form[$name] = $this->format_datetime($object->$name,$parms);
	}
	public function assign_to_object($object,Forms_Form $form,$name,$parms) {
		$object->$name = CMS::s2date($form[$name]);
	}
	public function template() { return 'datestr';	}
}

class CMS_AdminTable_SQLDateStrField extends CMS_AdminTable_DateStrField {
	protected function format_datetime($value,$parms) {
		if ($value=='0000-00-00') return '';
		$format = 'd.m.Y';
		if ((isset($parms['with_time'])&&$parms['with_time'])||(isset($parms['with_seconds'])&&$parms['with_seconds'])) $format .= ' - G:i';
		if (isset($parms['with_seconds'])&&$parms['with_seconds']) $format .= ':s';
		return CMS::date($format,$value);
	}
	public function assign_to_object($object,Forms_Form $form,$name,$parms) {
		$object->$name = CMS::s2sqldate($form[$name]);
	}
}

class CMS_AdminTable_TextareaField extends CMS_AdminTable_Field {
	public function container_class($parms) {
		$class = 'input-textarea';
		if (isset($parms['use-tab-key'])&&$parms['use-tab-key']) $class .= ' use-tab-key';
		if (isset($parms['resizer'])&&$parms['resizer']) $class .= ' textarea-resizer';
		return $class;
	}
	public function template() { return 'textarea';	}
	public function form_field($form,$name) { return $form->textarea($name); }
}

class CMS_AdminTable_ParmsField extends CMS_AdminTable_TextareaField {
	public function assign_to_object($object,Forms_Form $form,$name,$parms) {
		if (isset($parms['parse_to'])) {
			$f = $parms['parse_to'];
			$object->$f = CMS::parse_parms($form[$name]);
		}
		if (isset($parms['serialize_to'])) {
			$f = $parms['serialize_to'];
			$object->$f = serialize(CMS::parse_parms($form[$name]));
		}
	}
	public function container_class($parms) { return parent::container_class($parms).' use-tab-key'; }
}

class CMS_AdminTable_CheckboxField extends CMS_AdminTable_Field {
	public function container_class() { return 'input-checkbox'; }
	public function template() { return 'checkbox';	}
	public function form_field($form,$name) { return $form->checkbox($name); }
}

class CMS_AdminTable_SelectField extends CMS_AdminTable_Field {
	static $items_cache = array();
	public function container_class() { return 'input-select'; }
	public function template() { return 'select';	}
	public function in_list($row,$name,$parms) {
		$iname = $parms['items'];
		if (is_string($iname)) {
			if (isset(self::$items_cache[$iname])) {
				$items = self::$items_cache[$iname];
			}

			else {
				$items = CMS::items_for_select($iname);
				self::$items_cache[$iname] = $items;
			}
		}
		else $items = CMS::items_for_select($iname);
		$row->$name = $items[$row->$name];
	}
}

class CMS_AdminTable_MultilinkField extends CMS_AdminTable_Field {
	public function container_class() { return 'input-multilink'; }
	public function template() { return 'multilink';	}

	public function form_field($form,$name,$parms) {
		$items = CMS::items_for_select($parms['items']);
		foreach($items as $key => $data) {
			if (Core_Types::is_iterable($data))
				foreach($data as $key => $value) $form->checkbox($name.$key);
			else
				$form->checkbox($name.$key);
		}
	}
}

class CMS_AdminTable_StaticMultilinkField extends CMS_AdminTable_MultilinkField {

	public function assign_to_form(Forms_Form $form,$object,$name,$parms) {
		$values = explode(',',$object->$name);
		foreach($values as $key) {
			$key = trim($key);
			if ($key!='') {
				$fname = $name.$key;
				if (isset($form[$fname])) $form[$fname] = 1;
			}
		}
	}

	public function assign_to_object($object,Forms_Form $form,$name,$parms) {
		$values = array();
		$items = CMS::items_for_select($parms['items']);
		foreach($items as $key => $data) {
			$fname = $name.$key;
			if ($form[$fname]) $values[] = $key;
		}
		$object->$name = implode(',',$values);
	}


}


class CMS_AdminTable_RelativeSelectField extends CMS_AdminTable_SelectField {
	public function template() { return 'relative-select';	}
}

class CMS_AdminTable_ImageField extends CMS_AdminTable_UploadField {
	public function template() { return 'image'; }

	protected function wg($img,$parms) {
		if (isset($parms['grayscale'])&&$parms['grayscale']) $img->grayscale();
		if (isset($parms['watermark'])) {
			$w = $parms['watermark'];
			if (is_string($w)) $w = array('file' => $w,'mode' => '5');
			if (is_array($w)) {
				$file = $w['file'];
				unset($w['file']);
				$img->watermark($file,$w);
			}
		}
		return $img;
	}

	public function resize_image($filename,$parms) {
		Core::load('CMS.Images');

		list($w,$h) = CMS_Images::size($filename);
		if (!$w||!$h) return;
		$nw = $w;
		$nh = $h;



		if (isset($parms['crop'])) {
			list($rw,$rh) = CMS_Images::string2sizes($parms['crop']);
			$rw = (int)$rw;
			$rh = (int)$rh;
			if ($rw!=$w||$rh!=$h||isset($parms['grayscale'])||isset($parms['watermark'])) {
				$img = CMS_Images::Image($filename);
				if ($rw!=$w||$rh!=$h) $img = $img->crop($rw,$rh);
				$img = $this->wg($img,$parms);
				$img->save($filename);
			}
			return;
		}

		if (isset($parms['margins'])) {
			list($rw,$rh) = CMS_Images::string2sizes($parms['margins']);
			$rw = (int)$rw;
			$rh = (int)$rh;
			$color = '#FFFFFF';
			if (isset($parms['margins_color'])) $color = $parms['margins_color'];
			if ($rw!=$w||$rh!=$h||isset($parms['grayscale'])||isset($parms['watermark'])) {
				$img = CMS_Images::Image($filename);
				if ($rw!=$w||$rh!=$h) $img = $img->fit_with_margins($rw,$rh,$color);
				$img = $this->wg($img,$parms);
				$img->save($filename);
			}
			return;
		}

		$fit = true;

		if (isset($parms['resize'])) {
			list($rw,$rh) = CMS_Images::string2sizes($parms['resize']);
			$rw = (int)$rw;
			$rh = (int)$rh;
			if ($rw>0) $nw = $rw;
			if ($rh>0) $nh = $rh;
			$fit = false;
		}

		else if (isset($parms['fit'])) {
			list($rw,$rh) = CMS_Images::string2sizes($parms['fit']);
			$rw = (int)$rw;
			$rh = (int)$rh;
			if ($rw<$nw) $nw = $rw;
			if ($rh<$nh) $nh = $rh;
			$fit = true;
		}

		if ($nw!=$w||$nh!=$h) {
			$img = CMS_Images::Image($filename)->resize($nw,$nh,$fit);
			$img = $this->wg($img,$parms);
			$img->save($filename);
			return;
		}

		if (isset($parms['grayscale'])||isset($parms['watermark'])) {
			$img = CMS_Images::Image($filename);
			$img = $this->wg($img,$parms);
			$img->save($filename);
		}

	}

	public function after_upload($filename,$parms) {
		$this->resize_image($filename,$parms);
		return parent::after_upload($filename,$parms);
	}


	public function after_all_uploads($name,$parms,$filename,$uploaded,$old='') {
		$from = isset($parms['thumb_from'])?
			trim($parms['thumb_from']):'';
		if ($from!='') {
			if (isset($parms['not_recreate_thumb'])&&$parms['not_recreate_thumb']&&$old!='') return;
			if (!isset($uploaded[$name])&&isset($uploaded[$from])) {
				$src = $uploaded[$from];
				$ext = false;
				if ($m = Core_Regexps::match_with_results('{\.(jpg|gif|png|jpeg|bmp)$}i',$src)) {
					$ext = strtolower($m[1]);
					if ($ext=='jpeg') $ext = 'jpg';
				}
				if ($ext) {
					$filename = str_replace('$$$',$ext,$filename);
					copy($src,$filename);
					$this->resize_image($filename,$parms);
					return $filename;
				}
			}
		}
	}

}


class CMS_AdminTable_LayoutElement {

	protected $data;
	protected $html = false;

	public function __construct($data) {
		$this->data = $data;
	}

	public function styles() { return array(); }
	public function scripts() { return array(); }

	public function generate() {
		ob_start();
		print_r($this->data);
		$rc = ob_get_contents();
		ob_end_clean();
		return $rc;
	}

	public function html() {
		if (!$this->html) $this->html = $this->generate();
		return $this->html;
	}

}



class CMS_AdminTable_LayoutTree extends CMS_AdminTable_LayoutElement {

	public function styles() { return array(CMS::stdfile_url('styles/tree.css')); }
	public function scripts() { return array('jquery.js',CMS::stdfile_url('scripts/tree.js')); }

	public function generate() {
		$tree = $this->data;
		ob_start();
		include(CMS::view('admin/table/layout-tree.phtml'));
		$rc = ob_get_contents();
		ob_end_clean();
		return $rc;
	}

}

class CMS_AdminTable_LayoutMenu extends CMS_AdminTable_LayoutElement {

}

