<?php
/**
 * @package CMS\Controller\TreeAdminVars
 */

Core::load('CMS.Controller.TreeTable');

class CMS_Controller_TreeAdminVars extends CMS_Controller_TreeTable implements Core_ModuleInterface
{

	const VERSION = '0.0.0';

	protected static $options = array();

	protected $title_list = 'Настройки';
	protected $title_add = 'Добавление';
	protected $title_edit = 'Редактирование';
	protected $disbale_drag = array('admin', 'admin.title', 'admin.navigation');
	protected $js_app_file;
	protected $js_config = array();

	protected $use_tinymce = true;

	protected $component;

	// protected $orm_name	= 'vars';

	static public function initialize($config = array())
	{
		self::options($config);
	}

	static public function options($config = array())
	{
		self::$options = Core_Arrays::deep_merge_update(self::$options, $config);
	}

	static public function option($name)
	{
		return self::$option[$name];
	}

	protected function merge_options()
	{
		foreach (self::$options as $k => $v)
			if (!empty($v) && property_exists($this, $k)) {
				$this->$k = $v;
			}
	}

	public function setup()
	{
		if (CMS::vars()->storage_type() == 'orm') {
			$this->orm_name = 'vars';
		}
		if (CMS::vars()->storage_type() == 'storage') {
			$this->storage_name = 'vars';
		}

		$this->merge_options();

		Templates_HTML::add_scripts_settings(array('adminvars' => $this->js_config));

		$this->auth_realm = CMS::$admin_realm;
		if (isset(CMS::$cfg->vars->use_tinymce)) {
			$s = trim(CMS::$cfg->vars->use_tinymce);
			if ($s == '' || $s == '0') {
				$this->use_tinymce = false;
			}
		}

		$this
			->use_views_from(CMS::views_path('admin/vars'))
			->use_views_from(CMS::views_path('admin/vars2'));

		if (CMS::vars()->storage_type() == 'orm') {
			$types = WS::env()->orm->vars->types();
			$this->form_fields = $types->fields;
			$types->end();
		}

		if (CMS::vars()->storage_type() == 'storage') {
			$this->form_fields = $this->storage()->make_entity()->fields();
			// var_dump($this->form_fields);
		}

		$this->component = WS::env()->adminvars->component;

		//TODO: configure fields
		if (empty($this->tree_fields)) {
			$this->tree_fields = array(
				'title' => array(
					'caption' => 'Комментарий',
					'flex' => 1
				),
				'var_description' => array(
					'caption' => 'Тип',
					'flex' => 1
				),
				'full_code' => array(
					'caption' => 'Идентификатор',
					'width' => 200
				),
			);
		}

		return parent::setup();
	}

	public function form_fields($action = 'list')
	{
		$fields = parent::form_fields($action);
		if ($action == 'edit') {
			unset($fields['parent_id']);
		}
		if ($action == 'add') {
			if ($this->request['parent_id']) {
				$fields['parent_id']['type'] = 'hidden';
				$fields['parent_id']['value'] = $this->request['parent_id'];
			}
		}
		return $fields;
	}

	public function app_js_file()
	{
		return CMS::stdfile_url('scripts/admin/vars.js');
	}

	protected function render_tree()
	{
		$t = parent::render_tree();
		$t->use_style(CMS::stdfile_url('styles/vars.css'));
		return $t;
	}

	protected function tree_data_row_extra_fields($entity, $row)
	{
		$res = parent::tree_data_row_extra_fields($entity, $row);
		$type = CMS_Vars::type($entity->vartype);
		if ($type instanceof CMS_Vars_Types_FieldsType) {
			$res['edit_parms'] = 'parms/edit/id-' . $entity->id() . '/';
		} else {
			$res['edit_parms'] = $entity->id();
		}
		$res['add'] = $this->action_url('add', null, array('parent_id' => $entity->id()));
		//$res['expanded'] = $entity->vartype == 'dir';
		$res['iconCls'] = 'tao-tree-icon-var-' . strtolower($entity->vartype) . ' tao-tree-icon-var' .
			($entity->full ? ' tao-tree-icon-var-full' : '');
		$res['vartype'] = $entity->vartype;
		if ((is_bool($this->disbale_drag) && $this->disbale_drag) || (is_array($this->disbale_drag) && in_array($entity->full_code, $this->disbale_drag))) {
			$res['allowDrag'] = false;
		}
		return $res;
	}

	protected function tree_mapper($root = 0)
	{
		$mapper = parent::tree_mapper($root);
		$mapper = $mapper->where('component = :component', (string)$this->component);
		return $mapper;
	}

	protected function on_before_action($action)
	{
		if ($action == 'tree' && !empty($this->component)) {
			$this->title_list = $this->get_title(null);
		}
	}

	public function action_parms($id, $parms = array())
	{
		$id = (int)$id;
		$item = $parms['item'];
		if ($id < 1) {
			return $this->default();
		} //FIXME
		if (!$item) {
			$item = CMS::vars()->db()->find($id);
		}
		return $this->render('index', array(
				'title' => $this->get_title($item),
				'id' => $id,
				'item' => $item,
				'error' => $parms['error'],
				'component' => $this->component,
				'_change' => $this->make_url('change', $id),
				'_addfile' => $this->make_url('addfile', $id),
				'_up' => $this->make_url(),
			)
		)->use_style(CMS::stdfile_url('styles/admin/table.css'));
	}

	public function action_change()
	{
		$id = (int)$this->args[0];
		$item = CMS::vars()->db()->find($id);
		$rc = CMS::vars()->type($item->vartype)->change($id, $this->request, $item);
		Events::call('admin.change', $item);
		if (is_string($rc)) {
			return $this->action_parms($id, array('item' => $item, 'error' => $rc));
		} else {
			return $this->redirect_to($this->make_uri());
		}

	}

	protected function get_title($item)
	{
		$title = false;
		if (!$this->component) {
			return false;
		}
		$component_class_name = CMS::component_class_name($this->component);
		if ($component_class_name) {
			$title = Core_Types::reflection_for($component_class_name)->getStaticPropertyValue('admin_vars_title', false);
			if (!$title) {
				$title = "Настройки: $component_class_name";
			}
			if ($item->id > 0 && trim($item->title) != '') {
				$title .= ': ' . $item->title;
			}
		}
		return $title;
	}

	public function action_attaches()
	{
		$id = (int)$this->args[0];
		$attaches = array();
		if (IO_FS::exists("./" . Core::option('files_name') . "/vars/$id")) {
			foreach (IO_FS::Dir("./" . Core::option('files_name') . "/vars/$id") as $f) {
				$fp = $f->path;
				$attaches[$fp] = array(
					'name' => $fp,
					'path' => $f->path,
				);
			}
		}
		return $this->render('attaches', array(
				'id' => $id,
				'attaches' => $attaches,
			)
		);
	}

	public function action_addfile()
	{
		$id = (int)$this->args[0];
		$file = $_FILES['up'];
		$name = trim($file['name']);
		$tmp_name = trim($file['tmp_name']);
		if ($tmp_name != '') {
			$dir = "./" . Core::option('files_name') . "/vars/$id";
			CMS::mkdirs($dir);
			$name = strtolower($name);
			$name = trim(preg_replace('{[^a-z0-9_\.\-]}', '', $name));
			if ($name == '') {
				$name = 'noname';
			}
			if ($name[0] == '.') {
				$name = "noname.$name";
			}
			move_uploaded_file($tmp_name, "$dir/$name");
			CMS::chmod_file("$dir/$name");
			Events::call('admin.change');
		}
		return $this->redirect_to($this->make_uri('attaches', $id));
	}

	public function action_delfile()
	{
		$id = (int)$this->args[0];
		$file = $_GET['file'];
		@IO_FS::rm($file);
		Events::call('admin.change');
		return $this->redirect_to($this->make_uri('attaches', $id));
	}

	public function action_imagelist()
	{
		$id = (int)$this->args[0];
		$ar = array();
		if (IO_FS::exists("./" . Core::option('files_name') . "/vars/$id")) {
			foreach (IO_FS::Dir("./" . Core::option('files_name') . "/vars/$id") as $f) {
				$fp = $f->path;
				if ($m = Core_Regexps::match_with_results('{/([^/]+)$}', $fp)) {
					$fp = $m[1];
				}
				$ar[] = '["' . $fp . '","' . CMS::file_url($f->path) . '"]';
			}
		}

		echo 'var tinyMCEImageList = new Array(' . implode(',', $ar) . ');';
		die;
	}

	public function action_chparms()
	{
		$id = (int)$this->args[0];
		$item = CMS::vars()->db()->find($id);
		$item->chparms($this->request);
		//CMS_Var::chparms($id,$this->request); 
		$uri = $this->make_uri('dir', $item->parent_id);
		Events::call('admin.change', $item);
		return $this->redirect_to($uri);
	}

}
