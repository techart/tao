<?php


class CMS_Controller_Factory extends CMS_Controller implements Core_ModuleInterface
{
	protected $urls;
	protected $tables;
	protected $table;
	protected $fields;
	protected $fields_data;
	protected $fields_list;
	protected $fields_form;
	protected $key_field;
	protected $orm_module;
	protected $orm_fields;
	protected $admin_fields;
	protected $admin_module;
	protected $schema_module;
	protected $component;
	protected $component_module;
	protected $admin_tabs;
	protected $item_name;

	protected function dbc()
	{
		return CMS::orm()->connection();
	}

	protected function query($query,$binds = false)
	{
		$q = $this->dbc()->prepare($query);
		if ($binds) {
			$q->bind($binds);
		}
		return $q->execute();
	}

	public function setup()
	{
		$this->orm_module = $this->getp('orm_module');
		$this->admin_module = $this->getp('admin_module');
		$this->schema_module = $this->getp('schema_module');
		$this->orm_fields = $this->getp('orm_fields');
		$this->admin_fields = $this->getp('admin_fields');
		$this->component = $this->getp('component');
		$this->component_module = "Component.{$this->component}";
		$this->admin_tabs = (bool)$this->getp('admin_tabs',0);
		$this->item_name = "{$this->orm_module}.Item";
		$this->urls = WS::env()->urls->cmscomponentfactory;
		$this->auth_realm = CMS::$admin_realm;
		return parent::setup()
			->use_views_from(CMS::views_path('admin/factory'))
			->render_defaults('tables','urls','table','fields','key_field');
	}

	public function index()
	{
		$this->prepare();
		return $this->render('index',array(
		));
	}

	public function component($table)
	{
		$this->table = $table;
		$this->prepare();
		$this->prepare_fields($table);
		if ($r = $this->create_module_file($this->component_module,$this->generate_component())) {
			return $r;
		}
		if ($r = $this->create_module_file($this->schema_module,$this->generate_schema())) {
			return $r;
		}
		if ($r = $this->create_module_file($this->admin_module,$this->generate_admin())) {
			return $r;
		}
		if ($r = $this->create_module_file($this->orm_module,$this->generate_orm())) {
			return $r;
		}
		if ($r = $this->create_module_file("Component.{$this->component}.Controller",$this->generate_controller())) {
			return $r;
		}
		return 'Компонент создан';
	}

	protected function module_paths($module)
	{
		$component = $this->component;
		$mname = str_replace("Component.$component", '', $module);
		$mname = str_replace('.', '/', $mname);
		$component_dir = CMS::component_dir($component);
		if (!$mname) {
			$file = $component_dir . "/$component.php";
		}
		else {
			$file = $component_dir ."/lib$mname.php";
		}
		return array($file,  preg_replace('{/[^/]+$}', '', $file));
	}

	protected function create_module_file($module,$content)
	{
		list($file, $dir) = $this->module_paths($module);
		if (!IO_FS::exists($dir)) {
			CMS::mkdirs($dir);
			CMS::chmod_dir($dir);
		}
		if (IO_FS::exists($file)) {
			//return "{$file} уже существует!";
		}
		$content = preg_replace('{^&lt;}','<',$content);
		file_put_contents($file,$content);
		CMS::chmod_file($file);
		return false;
	}

	public function table($table,$action=false)
	{
		$this->table = $table;
		$this->prepare();
		$this->prepare_fields($table);

		if ($action) {
			$method = "table_{$action}";
			return $this->$method($table);
		}

		return $this->render('table',array(
		));
	}

	public function table_orm($table)
	{
		return $this->generate_orm();
	}

	public function table_admin($table)
	{
		return $this->generate_admin();
	}

	public function table_schema($table)
	{
		return $this->generate_schema();
	}

	protected function generate_orm()
	{
		return $this->generate('table_orm');
	}

	protected function generate_admin()
	{
		return $this->generate('table_admin');
	}

	protected function generate_schema()
	{
		return $this->generate('table_schema');
	}

	protected function generate_component()
	{
		return $this->generate('component');
	}

	protected function generate_controller()
	{
		return $this->generate('controller');
	}

	protected function getp($name,$def='')
	{
		return isset($_GET[$name])?trim($_GET[$name]):$def;
	}

	protected function generate($template)
	{
		$template = $this->view_path_for($template);
		ob_start();
		$table = $this->table;
		include($template);
		$content = ob_get_contents();
		ob_end_clean();
		return $content;
	}

	protected function prepare()
	{
		$this->tables = array();
		foreach($this->query('SHOW TABLES')->fetch_all() as $row) {
			$table = array_pop($row);
			$this->tables[$table] = $table;
		}
	}

	protected function prepare_fields($table)
	{
		$this->fields = array();
		$this->fields_data = array();
		$this->key_field = false;
		foreach($this->query("SHOW FIELDS FROM {$table}")->fetch_all() as $row) {
			$name = $row['Field'];
			$type = $row['Type'];
			$otype = $type;

			if (!$this->key_field) {
				$this->key_field = $name;
			}

			if (Core_Regexps::match('{int}i',$type)) {
				$type = 'int';
			} elseif (Core_Regexps::match('{date}i',$type)) {
				$type = 'date';
			} elseif (Core_Regexps::match('{text}i',$type)) {
				$type = 'text';
			} else {
				$type = 'string';
			}

			$this->fields[$name] = array(
				'original' => $otype,
				'simple' => $type,
			);

			$in_list = true;
			$in_form = true;
			$ftype = false;
			$style = false;
			$caption = strtoupper($name);
			$sqltype = preg_replace('{\s+.*$}','',$otype);
			$init_value = false;

			if ($type=='string') {
				$style = 'width:100%';
				$init_value = "''";
			}

			if ($type=='text') {
				$style = 'width:100%;height:200px';
				$ftype = 'textarea';
				$in_list = false;
				$init_value = "''";
			}

			if ($type=='int') {
				$init_value = '0';
			}

			if ($type=='int'&&$name=='isactive') {
				$ftype = 'checkbox';
			}

			if ($type=='int'&&Core_Regexps::match('{date}i',$name)) {
				$ftype = 'datestr';
				$init_value = "time()";
			}

			if ($type=='date') {
				$ftype = 'sqldatestr';
				$init_value = "date('Y-m-d H:i:s')";
			}

			if ($name==$this->key_field) {
				$in_form = false;
				$sqltype = 'serial';
			}

			$data = array();

			if ($ftype) {
				$data['type'] = $ftype;
			}

			$data['sqltype'] = $sqltype;
			$data['caption'] = $caption;
			if ($init_value!==false) {
				$data['init_value'] = $init_value;
			}

			$data['in_list'] = $in_list;
			$data['in_form'] = $in_form;
			if ($in_form) {
				$data['tab'] = 'default';
				$data['style'] = $style;
				$data['weight_in_list'] = 0;
				$data['weight_in_form'] = 0;
			}

			$this->fields_data[$name] = $data;
			unset($data['in_list']);
			unset($data['in_form']);
			unset($data['weight_in_list']);
			unset($data['weight_in_form']);
			unset($data['init_value']);
			unset($data['sqltype']);
			if (!$this->admin_tabs) {
				unset($data['tab']);
			}

			if ($in_form) {
				$this->fields_form[$name] = $data;
			}

			unset($data['style']);
			unset($data['tab']);

			if ($in_list) {
				$this->fields_list[$name] = $data;
			}

		}

		//print $this->draw_array($this->fields_list,'	');
		//var_dump($this->fields_form);
		//var_dump(CMS_Fields::fields_to_schema($this->fields_data));
	}

	public function draw_array($src,$prefix='')
	{
		$out = '';
		if ($src) {
			foreach($src as $key => $value) {
				$out .= $prefix;
				$out .= "'{$key}' => ";
				if (is_string($value)) {
					if ($key=='init_value') {
						$out .= $value;
					} else {
						$out .= "'{$value}'";
					}
				} elseif (is_int($value)||is_float($value)) {
					$out .= $value;
				} elseif (is_bool($value)) {
					$out .= ($value?'true':'false');
				} elseif(is_array($value)) {
					$out .= "array(\n";
					$out .= $this->draw_array($value,"$prefix\t");
					$out .= $prefix;
					$out .= ")";
				}
				$out .= ",\n";
			}
		}
		return $out;
	}


}


