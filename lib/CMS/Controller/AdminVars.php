<?php

/**
 * @package CMS\Controller\AdminVars
 */
class CMS_Controller_AdminVars extends CMS_Controller implements Core_ModuleInterface
{
	const MODULE = 'CMS.Controller.AdminVars';
	const VERSION = '0.0.0';

	protected $_item;
	protected $component = false;
	protected $use_tinymce = true;

	public function setup()
	{
		$this->auth_realm = CMS::$admin_realm;
		if (isset(CMS::$cfg->vars->use_tinymce)) {
			$s = trim(CMS::$cfg->vars->use_tinymce);
			if ($s == '' || $s == '0') {
				$this->use_tinymce = false;
			}
		}
		return parent::setup()->use_views_from(CMS::views_path('admin/vars'));
	}

	public function index($parms)
	{
		$id = (int)$parms['id'];
		if ($id == 0) {
			return $this->dir(0, $parms['component']);
		}
		if ($id > 0) {
			$this->_item = CMS::vars()->db()->find($id);
			if ($this->_item->vartype == 'dir') {
				return $this->dir($id, $parms['component']);
			} else {
				$component = $parms['component'];
				$this->component = $component;
				return $this->render('index', array(
						'title' => $this->get_title($this->_item),
						'id' => $id,
						'item' => $this->_item,
						'component' => $component,
						'_change' => $this->make_uri('change', $id),
						'_addfile' => $this->make_uri('addfile', $id),
						'_up' => $this->make_uri('dir', $this->_item->parent_id),
					)
				);
			}
		}

		return $this->page_not_found();
	}

	public function loaddump()
	{
		CMS_Dumps::load_from_post();
		return $this->redirect_to($this->index_url());
	}

	public function dump($parms)
	{
		$component = trim($parms['component']);
		Core::load('CMS.Dumps.Vars');
		CMS_Dumps_Vars::dump($component);
		die;
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

	public function dir($id, $component = false)
	{
		$this->component = $component;
		$rows = CMS::vars()->db()->for_id($id, $component);
		foreach ($rows as $key => &$row) {
			$row[':del'] = $this->make_uri('del', $row->id);
			$row[':edit'] = $this->make_uri('dir', $row->id);
			$row[':parms'] = $this->make_uri('parms', $row->id);
			$row->list_value = CMS::vars()->type($row->vartype)->list_value($row);
		}

		$_up = '';

		if ($id == 0) {
			$item = CMS::vars()->db()->make_entity();
			$item->id = 0;
			$item->vartype = 'dir';
			$item->full_code = '';
			$item->title = CMS::lang()->_vars->root_dir;
		} else {
			$item = $this->_item;
			$prefix = CMS::vars()->db()->full_code($id);
			foreach ($rows as $key => &$row) {
				$row->code = $prefix . '.' . $row->code;
			}
			$_up = $this->make_uri('dir', $item->parent_id);
		}

		return $this->render('index', array(
				'title' => $this->get_title($item),
				'id' => $id,
				'rows' => $rows,
				'item' => $item,
				'component' => $component,
				'_up' => $_up,
				'_add' => $this->make_uri('add', $id),
			)
		);
	}

	public function add($parms)
	{
		$parent_id = (int)$parms['id'];
		$component = $parms['component'];
		$this->component = $component;
		$vartype = trim($this->request['vartype']);
		$data = $_POST;
		$data['parent_id'] = $parent_id;
		$item = CMS::vars()->type($vartype)->create($data);
		$item->component = (string)$component;
		$item->full = isset($data['full']) ? (int)$data['full'] : 0;
		$item->insert();
		Events::call('admin.change', $item);
		return $this->redirect_to($this->make_uri('dir', $parent_id));
	}

	public function addfile($parms)
	{
		$id = (int)$parms['id'];
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

	public function delfile($parms)
	{
		$id = (int)$parms['id'];
		$file = $_GET['file'];
		@IO_FS::rm($file);
		Events::call('admin.change');
		return $this->redirect_to($this->make_uri('attaches', $id));
	}

	public function imagelist($parms)
	{
		$id = (int)$parms['id'];
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

	public function attaches($parms)
	{
		$id = (int)$parms['id'];
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

	public function del($parms)
	{
		$id = (int)$parms['id'];
		$item = CMS::vars()->db()->find($id);
		$item->delete();
		Events::call('admin.change', $item);
		return $this->redirect_to($this->make_uri('dir', $item->parent_id));
	}

	public function parms($parms)
	{
		$id = (int)$parms['id'];
		$item = CMS::vars()->db()->find($id);
		$component = $parms['component'];
		$this->component = $component;
		return $this->render('parms', array(
				'title' => $this->get_title($item),
				'id' => $id,
				'item' => $item,
				'_chparms' => $this->make_uri('chparms', $id),
			)
		);
	}

	public function chparms($parms)
	{
		$id = (int)$parms['id'];
		$item = CMS::vars()->db()->find($id);
		$item->chparms($this->request);
		//CMS_Var::chparms($id,$this->request); 
		$uri = $this->make_uri('dir', $item->parent_id);
		Events::call('admin.change', $item);
		return $this->redirect_to($uri);
	}

	public function change($parms)
	{
		$id = (int)$parms['id'];
		$item = CMS::vars()->db()->find($id);
		$rc = CMS::vars()->type($item->vartype)->change($id, $this->request, $item);
		Events::call('admin.change', $item);
		if (is_string($rc)) {
			$component = $parms['component'];
			$this->component = $component;
			return $this->render('index', array(
					'title' => $this->get_title($item),
					'id' => $id,
					'error' => $rc,
					'item' => $item,
					'_change' => $this->make_uri('change', $id),
					'_addfile' => $this->make_uri('addfile', $id),
					'_up' => $this->make_uri('dir', $item->parent_id),
				)
			);
		} else {
			return $this->redirect_to($this->make_uri('dir', $item->parent_id));
		}
	}

} 

