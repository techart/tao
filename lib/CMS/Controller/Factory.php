<?php

class CMS_Controller_Factory extends CMS_Controller implements Core_ModuleInterface {

	const MODULE  = 'CMS.Controller.Factory';
	const VERSION = '0.0.0';
	
	protected $s_module;
	protected $s_emodule;
	protected $s_orm;
	protected $s_class;
	protected $s_name;
	protected $s_rname;
	protected $s_sname;
	protected $s_table;
	protected $s_defaults;
	protected $s_where;
	protected $s_columns;
	protected $s_serial;
	protected $s_form_fields;
	protected $s_list_fields;

	protected $repository_title = 'Репозиторий компонентов';

	static $error_message_chmod = 'Не удалось установить права (chmod): %s';
	static $error_message_save = 'Не удалось записать файл: %s';


	protected $rdir;
	protected $jsv = array();

	public function setup() {
		$this->auth_realm = CMS::$admin_realm;
		return parent::setup()->use_views_from(CMS::views_path('admin/factory'));
	}

	protected function existed_components() {
		$components = array();
		foreach(CMS::$component_names as $cname => $cdata) $components[strtolower($cname)] = true;
		foreach(IO_FS::Dir(CMS::$app_path.'/components') as $f) {
			$fn = strtolower(trim(preg_replace('/\.php$/','',$f->name)));
			$components[$fn] = true;
		}

		return array_keys($components);
	}

	public function index() {
		$tables = array();
		$rows = DB_SQL::db()->connection->prepare('SHOW TABLES')->execute()->fetch_all();
		foreach($rows as $row) $tables[] = array_pop($row);
		return $this->render('index',array(
			'tables' => $tables,
			'components' => $this->existed_components(),
		));
	}

	protected function process_table($table) {
		$this->s_defaults = '';
		$this->s_where = '';
		$this->s_columns = '';
		$this->s_serial = '';
		$this->s_form_fields = '';
		$this->s_list_fields = '';

		$sfields = CMS::$db->prepare("SHOW FIELDS FROM $table")->execute()->fetch_all();
		if (sizeof($sfields)>0) {
			$fields = array();
			$columns = '';
			$c = 0;
			$serial = '';
			$defs = array();
			foreach($sfields as &$field) {
				$_name = $field['Field'];
				$defs[$_name] = "''";
				$_type = 'input';
				if ($c==0) $serial = $_name; else $columns .= ',';
				$c++;
				$columns .= "'$_name'";
				if (preg_match('{text}i',$field['Type'])) $_type = 'textarea';
				if (preg_match('{int}i',$field['Type'])) $defs[$_name] = "0";
				if (preg_match('{date}i',$field['Type'])) $defs[$_name] = "date('Y-m-d')";
				$fields[$_name] = $_type;
			}
			$defaults = '';
			$where = '';
			$form_fields = '';
			$list_fields = '';
			foreach($fields as $_field => $_type) {
				$defaults .= "\n\t\t\$this->$_field = ".$defs[$_field].";";
				$where .= "\n\t\tif (isset(\$parms['$_field'])) \$query = \$query->where('$_field=:$_field');";
				$caption = strtoupper($_field);
				$form_fields .= "\n\t\t'$_field' => array(\n\t\t\t'caption' => '$caption',\n\t\t),";
			}
			$list_fields = $form_fields;

			$this->s_defaults = $defaults;
			$this->s_columns = $columns;
			$this->s_where = $where;
			$this->s_serial = $serial;
			$this->s_form_fields = $form_fields;
			$this->s_list_fields = $list_fields;
		}
	}

	protected function gen_entity() {
		$content = file_get_contents(CMS::view('ctpl/Entity.tphp'));
		$content = str_replace('%CNAME%',$this->s_name,$content);
		$content = str_replace('%RNAME%',$this->s_rname,$content);
		$content = str_replace('%CLASS%',$this->s_class,$content);
		$content = str_replace('%MODULE%',$this->s_module,$content);
		$content = str_replace('%TABLE%',$this->s_table,$content);
		$content = str_replace('%ORM%',$this->s_orm,$content);
		$content = str_replace('%COLUMNS%',$this->s_columns,$content);
		$content = str_replace('%SERIAL%',$this->s_serial,$content);
		$content = str_replace('%DEFAULTS%',$this->s_defaults,$content);
		$content = str_replace('%WHERE%',$this->s_where,$content);
		return $content;
	}

	protected function gen_admin_controller() {
		$content = file_get_contents(CMS::view('ctpl/AdminController.tphp'));
		$content = str_replace('%CNAME%',$this->s_name,$content);
		$content = str_replace('%RNAME%',$this->s_rname,$content);
		$content = str_replace('%MODULE%',$this->s_module,$content);
		$content = str_replace('%ORM%',$this->s_orm,$content);
		$content = str_replace('%FORMFIELDS%',$this->s_form_fields,$content);
		$content = str_replace('%LISTFIELDS%',$this->s_list_fields,$content);
		return $content;
	}


	protected function gen_admin_controller_a() {
		$content = file_get_contents(CMS::view('ctpl/AdminControllerA.tphp'));
		$content = str_replace('%EMODULE%',$this->s_emodule,$content);
		$content = str_replace('%MODULE%',$this->s_module,$content);
		$content = str_replace('%CLASS%',$this->s_class,$content);
		$content = str_replace('%ORM%',$this->s_orm,$content);
		$content = str_replace('%FORMFIELDS%',$this->s_form_fields,$content);
		$content = str_replace('%LISTFIELDS%',$this->s_list_fields,$content);
		return $content;
	}

	protected function gen_index_phtml() {
		$content = file_get_contents(CMS::view('ctpl/views/index.phtml'));
		$content = str_replace('%CNAME%',$this->s_name,$content);
		$content = str_replace('%RNAME%',$this->s_rname,$content);
		return $content;
	}

	protected function gen_component() {
		$content = file_get_contents(CMS::view('ctpl/Component.tphp'));
		$content = str_replace('%CNAME%',$this->s_name,$content);
		$content = str_replace('%RNAME%',$this->s_rname,$content);
		$content = str_replace('%cname%',$this->s_sname,$content);
		return $content;
	}

	protected function gen_controller() {
		$content = file_get_contents(CMS::view('ctpl/Controller.tphp'));
		$content = str_replace('%CNAME%',$this->s_name,$content);
		$content = str_replace('%RNAME%',$this->s_rname,$content);
		return $content;
	}

	public function component() {
		$name  = $this->request['component'];
		$table = $this->request['table'];
		$rname = $name;
		$sname = strtolower($name);

		$this->s_module = "Component.$name.DB";
		$this->s_class = "Component_$name"."_DB";
		$this->s_name = $name;
		$this->s_rname = $rname;
		$this->s_sname = $sname;
		$this->s_table = $table;
		$this->s_orm = $table;

		$this->process_table($table);

		CMS::mkdirs(CMS::$app_path."/components/$name");
		CMS::mkdirs(CMS::$app_path."/components/$name/views");

		file_put_contents(CMS::$app_path."/components/$name/DB.php",$this->gen_entity());
		CMS::chmod_file(CMS::$app_path."/components/$name/DB.php");

		file_put_contents(CMS::$app_path."/components/$name/AdminController.php",$this->gen_admin_controller());
		CMS::chmod_file(CMS::$app_path."/components/$name/AdminController.php");

		file_put_contents(CMS::$app_path."/components/$name/views/index.phtml",$this->gen_index_phtml());
		CMS::chmod_file(CMS::$app_path."/components/$name/views/index.phtml");

		file_put_contents(CMS::$app_path."/components/$name.php",$this->gen_component());
		CMS::chmod_file(CMS::$app_path."/components/$name.php");

		file_put_contents(CMS::$app_path."/components/$name/Controller.php",$this->gen_controller());
		CMS::chmod_file(CMS::$app_path."/components/$name/Controller.php");

		return $this->redirect_to($this->component_end_url($name));
	}

	public function schema() {
		$component  = $this->request['component'];
		$table = $this->request['table'];
		if ($table) {
			Core::load('DB.Schema');
			$schema = DB_Schema::Table($this->db()->session->connection_for($table))->for_table($table)->inspect();
			$dir = CMS::$app_path."/components/$component/config";
			$file = $dir . '/' . 'schema.php';
			CMS::mkdirs($dir);
			file_put_contents($file, $this->gen_schema($schema, $table, $component));
			CMS::chmod_file($file);
		}
		return $this->redirect_to($this->schema_end_url($component));
	}

	protected function gen_schema($schema, $table, $component ) {
		$txt = var_export($schema, true);
		return <<<TXT
<?php
// Для использования достаточно в $component::initialize добавить
// Core::load('DB.Schema');
// DB_Schema::process_file(__DIR__ . '/../config/schema.php'); //путь к файлу может быть другим
return array(
'$table' => $txt,
);
TXT;
	}

	public function entity() {
		$session = Net_HTTP_Session::Store();

		if ($this->request->method_name=='post') {
			$table = $this->request['table'];
			$module = $this->request['module'];

			$session['factory/table'] = $table;
			$session['factory/module'] = $module;
		}

		else {
			$table = $session['factory/table'];
			$module = $session['factory/module'];
		}

		$this->s_table = $table;
		$this->s_module = $module;
		$this->s_sname = $table;
		$this->s_class = str_replace('.','_',$module);
		$this->process_table($table);

		header('Content-Type: text/plain');
		print $this->gen_entity();
		die;
	}

	public function ac() {
		$session = Net_HTTP_Session::Store();

		if ($this->request->method_name=='post') {
			$table = $this->request['table'];
			$module = $this->request['module'];
			$emodule = $this->request['emodule'];
			$orm = $this->request['orm'];

			$session['factory/table'] = $table;
			$session['factory/module'] = $module;
			$session['factory/emodule'] = $emodule;
			$session['factory/orm'] = $orm;
		}

		else {
			$table = $session['factory/table'];
			$module = $session['factory/module'];
			$emodule = $session['factory/emodule'];
		}

		$this->s_table = $table;
		$this->s_module = $module;
		$this->s_emodule = $emodule;
		$this->s_orm = $orm;
		$this->s_sname = $table;
		$this->s_class = str_replace('.','_',$module);
		$this->process_table($table);

		header('Content-Type: text/plain');
		print $this->gen_admin_controller_a();
		die;
	}

	public function cend($name) {
		return $this->render('cend',array(
			'name' => $name,
		));
	}

	public function schema_end($name) {
		return $this->render('schema_end',array(
			'name' => $name,
		));
	}

	protected function is_valid_entry($entry) {
		return !in_array($entry->name,CMS_Factory::$invalid_entries);
	}

	public function repository($dir) {
		$dir = trim(trim($dir,'/'));
		$this->rdir = $dir;
		$dirs = array();
		$components = array();
		$dir = CMS_Factory::$repository."/$dir";

		$title = $this->repository_title;

		if (IO_FS::exists($dir)) foreach(IO_FS::Dir($dir) as $entry) {
			if (is_dir($entry->path)&&$this->is_valid_entry($entry)) {
				$_dir = $entry->path;
				if (IO_FS::exists("$_dir/Index.php")) {
					$class = "CMS_RepositoryEntry_".$entry->name;
					include("$_dir/Index.php");
					if (class_exists($class)) {
						$_class = new $class();
						if (isset($_class->disabled)&&$_class->disabled) $_class = false;
						if ($_class) $components[$entry->name] = $_class;
					}
				}
				else if (IO_FS::exists("$_dir/.title")) {
					$dirs[$entry->name] = trim(file_get_contents("$_dir/.title"));
				}

				else {
					$dirs[$entry->name] = $entry->name;
				}
			}
		}

		ksort($dirs);
		ksort($components);

		return $this->render('repository',array(
			'title' => $title,
			'dir' => $dir,
			'rdir' => $this->rdir,
			'dirs' => $dirs,
			'components' => $components,
		));
	}

	protected function cclass($dir) {
		$dir = trim($dir,'/');
		$cname = preg_replace('{^.+/}','',$dir);
		$classname = "CMS_RepositoryEntry_$cname";
		$rdir = CMS_Factory::$repository."/$dir";
		if (!IO_FS::exists("$rdir/Index.php")) return false;
		include("$rdir/Index.php");
		if (!class_exists($classname)) return false;
		$class = new $classname();
		$class->dir = $rdir;
		$class->udir = $dir;
		$class->doc_url = $this->doc_url($dir);
		return $class;
	}

	public function doc($dir,$doc) {
		$class = $this->cclass($dir);
		if (!$class) return $this->page_not_found();
		if (!IO_FS::exists("$class->dir/doc/$doc")) return $this->page_not_found();
		$doc = file_get_contents("$class->dir/doc/$doc");

		Core::load('CMS.Text.Render');
		$r = CMS_Text_Render::renderer()
				->parm('doc',$class->doc_url)
				->render($doc);

		return $this->render('doc',array(
			'class' => $class,
			'doc' => $doc,
			'r' => $r,
		));
	}

	protected function create_install_form($class) {
		return $class->create_form($this->request->urn);
	}

	public function update($dir) {
		if ($this->env->request->method_name!='post') return $this->page_not_found();
		$class = $this->cclass($dir);
		if (!$class) return $this->page_not_found();
		if (!$class->can_update()) return $this->page_not_found();
		$class->update();
		Core::load('Net.HTTP.Session');
		$session = Net_HTTP_Session::Store();
		$session['factory/update/queries'] = $class->queries_log;
		$session['factory/update/dumps'] = $class->dumps_log;
		$session['factory/update/files'] = $class->ufiles_log;
		$session['factory/update/nfiles'] = $class->unfiles_log;
		$session['factory/update/errors'] = $class->install_errors;
		return $this->redirect_to($this->update_ok_url($class->udir));
	}

	public function uok($dir) {
		$class = $this->cclass($dir);
		if (!$class) return $this->page_not_found();

		Core::load('Net.HTTP.Session');
		$session = Net_HTTP_Session::Store();
		$class->queries_log = $session['factory/update/queries'];
		$class->dumps_log = $session['factory/update/dumps'];
		$class->ufiles_log = $session['factory/update/files'];
		$class->unfiles_log = $session['factory/update/nfiles'];
		$class->install_errors = $session['factory/update/errors'];

		return $this->render('update-ok',array('class' => $class));
	}

	public function install($dir) {
		$class = $this->cclass($dir);
		if (!$class) return $this->page_not_found();
		$class->can_install();
		if ($this->request->method_name=='post') return $this->install_process($class);

		$can_update = $class->can_update();

		$form = $this->create_install_form($class);

		return $this->render('install',array(
			'class' => $class,
			'form' => $form,
			'update_url' => str_replace('/install/','/update/',$this->env->request->urn),
			'can_update' => $can_update,
		));
	}

	public function iok($dir) {
		$class = $this->cclass($dir);
		if (!$class) return $this->page_not_found();

		Core::load('Net.HTTP.Session');
		$session = Net_HTTP_Session::Store();
		$class->queries_log = $session['factory/install/queries'];
		$class->files_log = $session['factory/install/files'];
		$class->dumps_log = $session['factory/install/dumps'];
		$class->replaces = $session['factory/install/replaces'];
		$class->install_errors = $session['factory/install/errors'];

		return $this->render('install-ok',array('class' => $class));
	}

	protected function install_process($class) {
		$form = $this->create_install_form($class);
		$valid = $form->process($this->request);
		if (!$valid) {
			if (is_object($form->validator)) {
				if (sizeof($form->validator->errors->property_errors)>0) {
					foreach($form->validator->errors->property_errors as $error) {
						$class->error($error);
					}
				}
			}
		}

		if (sizeof($class->errors)>0) {
			return $this->render('install-errors',array(
				'class' => $class,
				'form' => $form,
			));
		}

		$rc = $class->install($form);
		Core::load('Net.HTTP.Session');
		$session = Net_HTTP_Session::Store();
		$session['factory/install/queries'] = $class->queries_log;
		$session['factory/install/files'] = $class->files_log;
		$session['factory/install/dumps'] = $class->dumps_log;
		$session['factory/install/replaces'] = $class->replaces;
		$session['factory/install/errors'] = $class->install_errors;
		return $this->redirect_to($this->install_ok_url($class->udir));
	}

	public function download() {
		$file = trim($this->env->request['file']);
		if ($file=='') return $this->page_not_found();
		return $this->download_file($file);
	}


}

class CMS_RepositoryEntry {

	public $version = '0.0.0';
	public $component_id = false;
	public $component_dir = false;
	public $component_name = false;
	public $installed = false;
	public $installed_version = false;
	public $installed_id = false;

	public $title = 'Без имени';
	public $description = 'Описание отсутствует';
	public $submit_caption = 'Установить компонент';
	public $form_fields = array();
	public $dir;
	public $udir;
	public $doc_url;

	public $error_component_exists = 'Компонент <b>%s</b> уже существует';
	public $error_table_exists = 'Таблица <b>%s</b> уже есть в БД';

	public $errors = array();
	public $install_errors = array();


	public $transform_files = array('{\.php$}','{\.phtml$}','{\.css$}','{\.js$}');
	public $can_update_files = array('{\.php$}','{\.phtml$}','{\.css$}','{\.js$}');
	public $non_rewrite_files = array();
	public $no_copy = array('.git','.gitignore');
	public $install_only = array();

	public $not_install_if_table_exists = false;
	public $not_install_if_component_exists = false;

	public $freplaces = array();
	public $replaces = array();
	public $fhashes = array();

	public $queries_log = array();
	public $dumps_log = false;
	public $files_log = false;
	public $ufiles_log = false;
	public $unfiles_log = false;

	protected $tables = false;
	protected $is_update = false;

	public function replace($s1,$s2) {
		$this->replaces[$s1] = $s2;
	}

	public function freplace($s1,$s2) {
		$this->freplaces[$s1] = $s2;
	}

	public function replace_text(&$s) {
		foreach($this->replaces as $s1 => $s2) {
			$s = str_replace("%%{{$s1}}",$s2,$s);
		}
	}

	public function freplace_text(&$s) {
		foreach($this->freplaces as $s1 => $s2) {
			$s = str_replace($s1,$s2,$s);
		}
	}

	public function if_transform_file($filename) {
		if (sizeof($this->replaces)==0) return false;
		foreach($this->transform_files as $t) {
			if ($t==$filename) return true;
			if ($t[0]=='{') {
				if (Core_Regexps::match($t,$filename)) return true;
			}
		}
		return false;
	}

	public function if_can_update_file($filename) {
		foreach($this->can_update_files as $t) {
			if ($t==$filename) return true;
			if ($t[0]=='{') {
				if (Core_Regexps::match($t,$filename)) return true;
			}
		}
		return false;
	}

	public function create_form($url) {
		$form = Forms::Form('mainform')->action($url)->input('__hrandom');
		$validator = Validation::Validator();
		$use_validator = false;
		foreach($this->form_fields as $name => $parms) {
			switch($parms['type']) {
				case 'checkbox':
				case 'checkboxr':
					$form->checkbox($name);
					$form[$name] = $parms['value'];
					if ($parms['checked']) $form[$name] = 1;
					break;
				default:
					$form->input($name);
					$form[$name] = $parms['value'];
			}
			if (isset($parms['match'])) {
				if ($parms['match']=='presence') $validator->validate_presence_of($name,$parms['error_message']);
				else $validator->validate_format_of($name,$parms['match'],$parms['error_message']);
				$use_validator = true;
			}
		}
		if ($use_validator) $form->validate_with($validator);
		return $form;
	}

	public function error($message) {
		$this->errors[] = $message;
	}

	public function install_error($message) {
		$this->install_errors[] = $message;
	}

	public function table_exists($name) {
		if (!$this->tables) {
			$this->tables = array();
			$rows = DB_SQL::db()->connection->prepare('SHOW TABLES')->execute()->fetch_all();
			foreach($rows as $row) $this->tables[] = array_pop($row);
		}
		return in_array($name,$this->tables);
	}

	protected function component_exists($name) {
		if (IO_FS::exists(CMS::$app_path."/components/$name")) return true;
		if (IO_FS::exists(CMS::$app_path."/components/$name.php")) return true;
		return false;
	}

	public function full_description() {
		if (IO_FS::exists("$this->dir/.description")) {
			$src = file_get_contents("$this->dir/.description");
			Core::load('CMS.Text.Render');
			return CMS_Text_Render::renderer()
				->parm('doc',$this->doc_url)
				->render($src)
				->result();
		}
		return $this->description;
	}

	public function ok_description() {
		if (IO_FS::exists("$this->dir/.ok")) {
			$src = file_get_contents("$this->dir/.ok");
			Core::load('CMS.Text.Render');
			$r = CMS_Text_Render::renderer()->parm('doc',$this->doc_url);
			foreach($this->replaces as $s1 => $s2) $r->parm($s1,$s2);

			return $r->render($src)->result();
		}
		return '';
	}

	protected function copy_file_process($from,$file) {
		$to   = "../$file";
		if ($this->if_transform_file($file)) {
			$content = file_get_contents($from);
			$this->replace_text($content);
			file_put_contents($to,$content);
		}

		else {
			$r = @copy($from,$to);
			if (!$r) {
				$this->install_error(sprintf(CMS_Controller_Factory::$error_message_save,$to));
				return false;
			}
		}

		$r = @chmod($to,CMS::$chmod_file);
		if (!$r) {
			$this->install_error(sprintf(CMS_Controller_Factory::$error_message_chmod,$to));
		}
		return true;
	}

	protected function copy_file_install($from,$file) {
		$to   = "../$file";

		if (sizeof($this->non_rewrite_files)>0 && IO_FS::exists($to)) {
			foreach($this->non_rewrite_files as $filename) {
				$this->freplace_text($filename);
				if ($file==$filename) return false;
				if ($filename[0]=='{'||$filename[0]=='/') {
					if (Core_Regexps::match($filename,$file)) return false;
				}
			}
		}

		$this->copy_file_process($from,$file);

		if ($this->if_can_update_file($file)) {
			$this->fhashes[$file] = md5(file_get_contents($to));
		}

		return true;
	}

	protected function uflog($s) {
		if (!$this->ufiles_log) $this->ufiles_log = array();
		$this->ufiles_log[] = $s;
	}

	protected function unflog($s) {
		if (!$this->unfiles_log) $this->unfiles_log = array();
		$this->unfiles_log[] = $s;
	}

	protected function copy_file_update($from,$file) {
		$to   = "../$file";

		$u = true;

		$fe = IO_FS::exists($to);
		$cu = $this->if_can_update_file($file);

		if (!$fe) {
			$rc = $this->copy_file_process($from,$file);
			$this->uflog($file);
			if ($rc) return false;
			if ($cu) $this->fhashes[$file] = md5(file_get_contents($to));
		}

		else if ($cu) {
			$md5_existed = md5(file_get_contents($to));
			$md5_installed = trim($this->installed->fhashes[$file]);

			if ($this->if_transform_file($file)) {
				$content = file_get_contents($from);
				$this->replace_text($content);
				$md5_new = md5($content);
			}

			else {
				$md5_new = md5(file_get_contents($from));
			}

			$this->fhashes[$file] = $md5_new;

			if ($md5_existed!=$md5_new) {
				if ($md5_installed!=$md5_existed) {
					$to .= '.upd';
				}

				if ($this->if_transform_file($file)) {
					file_put_contents($to,$content);
				}

				else {
					$r = @copy($from,$to);
					if (!$r) {
						$this->install_error(sprintf(CMS_Controller_Factory::$error_message_save,$to));
						return false;
					}

				}
				$r = @chmod($to,CMS::$chmod_file);
				if (!$r) {
					$this->install_error(sprintf(CMS_Controller_Factory::$error_message_chmod,$to));
				}


				if ($md5_installed==$md5_existed) {
					$this->uflog($file);
				}

				else {
					$this->unflog($file);
				}

			}

		}


		return true;
	}

	protected function copy_file($from,$file) {
		if ($this->is_update) {
				return $this->copy_file_update($from,$file);
		}

		else {
				return $this->copy_file_install($from,$file);
		}
	}

	protected function copy_dir($dir) {
		$ls = new ArrayObject();
		
		if ($this->is_update) {
			$_dir = str_replace("$this->dir/files/",'',$dir);
			if (in_array($_dir,$this->install_only)) return $ls;
		}
		
		foreach(IO_FS::Dir($dir) as $entry) {
			
			foreach($this->no_copy as $_nc) {
				if ($entry->name==$_nc) {
					continue 2;
				}
			}
			
			$newpath = str_replace("$this->dir/files/",'',$entry->path);
			$this->freplace_text($newpath);
			$newpath = preg_replace('{^www/}',CMS::www().'/',$newpath);
			$newname = $entry->name;
			$this->freplace_text($newname);

			if (is_dir($entry->path)) {
				CMS::mkdirs("../$newpath");
				$ls[$newname] = $this->copy_dir($entry->path);
			}

			else {
				$rc = $this->copy_file($entry->path,$newpath);
				if ($rc) $ls[$newname] = $newname;
			}
		}
		return $ls;
	}

	protected function copy_files() {
		$this->files_log = $this->copy_dir("$this->dir/files");
	}

	public function string_exists_in($m) {
		if (is_string($m)) return true;
		if (Core_Types::is_iterable($m)) foreach($m as $item) if ($this->string_exists_in($item)) return true;
		return false;
	}

	protected function load_dump_from_file($file) {
		Core::load('CMS.Dumps');
		CMS_Dumps::load($file);
		if (!$this->dumps_log) $this->dumps_log = array();
		$this->dumps_log[] = $file;
	}

	protected function load_dump($file) {
		$this->load_dump_from_file("$this->dir/$file");
	}


	public function query($s) {
		$s = trim($s);
		if ($s=='') return;
		$log = true;
		if ($s[0]=='@') {
			$s = substr($s,1);
			$log = false;
		}

		$this->replace_text($s);
		$s = rtrim(trim($s),';');

		if ($log) $this->queries_log[] = $s;
		DB_SQL::db()->connection->execute($s);
	}

	protected function load_sql_from_file($file) {
		$query = '';
		foreach(IO_FS::File($file) as $line) {
			$_line = trim($line);
			if ($_line!='') {
				if (substr($_line,0,2)=='--') continue;
				if (substr($_line,0,2)=='/*') continue;

				if (Core_Regexps::match('/^@*(drop|create|alter|delete|update|select|insert)\s/i',$line)) {
					if (trim($query)!='') $this->query($query);
					$query = $line;
				}

				else $query .= "$line";
			}
		}
		if (trim($query)!='') $this->query($query);
	}

	protected function run_php_file($file) {
		include($file);
	}

	protected function run_files($dir) {
		if (!file_exists("$this->dir/$dir")) return false;
		if (!is_dir("$this->dir/$dir")) return false;

		$files = array();
		foreach(IO_FS::Dir("$this->dir/$dir") as $entry) $files[] = $entry->path;
		sort($files);
		foreach($files as $file) {
			if (is_file($file)) {
				$ext = pathinfo($file);
				$ext = $ext['extension'];

				switch($ext) {
					case 'dmp':
						$this->load_dump_from_file($file);
						break;
					case 'sql':
						$this->load_sql_from_file($file);
						break;
					case 'php':
						$this->run_php_file($file);
						break;
				}
			}
		}

	}

	protected function setup_from_form($form) {
	}

	protected function component_code() {
		if (!$this->component_id) return false;
		$name = $this->component_name;
		if (!$name) $name = $this->component_dir;
		if (!$name) trim($name = $this->title);
		$name = CMS::translit($name);
		$name = preg_replace('{[^a-z0-9]+}i','-',$name);
		if ($name!='') $name .= '-';
		$name .= $this->component_id;
		return $name;
	}

	protected function info_file() {
		$code = $this->component_code();
		if (!$code) return false;
		$dir = CMS::$app_path."/components/.info";
		if (!IO_FS::exists($dir)) CMS::mkdirs($dir);
		return "$dir/$code";
	}


	protected function save_info() {
		if ($filename = $this->info_file()) {
			$info = "id $this->component_id\nversion $this->version\n";
			foreach($this->fhashes as $file => $hash) {
				$info .= "hash $hash $file\n";
			}
			file_put_contents($filename,$info);
			CMS::chmod_file($filename);
		}
	}

	protected function load_info() {
		$filename = $this->info_file();
		if (!$filename) return false;

		if ($this->component_id&&$this->component_dir) {
			$of = CMS::$app_path."/components/$this->component_dir/.info";
			if (IO_FS::exists($of)) {
				copy($of,$filename);
				CMS::chmod_file($filename);
				unlink($of);
			}
		}

		if (!file_exists($filename)) return false;
		$info = new stdClass();
		$fhashes = array();
		foreach(IO_FS::File($filename) as $line) {
			if ($m = Core_Regexps::match_with_results('/^id\s+(.+)$/',$line)) {
				$info->id = trim($m[1]);
				if ($this->component_id==$info->id) $this->installed_id = $info->id;
			}
			if ($m = Core_Regexps::match_with_results('/^version\s+(.+)$/',$line)) {
				$info->version = trim($m[1]);
				$this->installed_version = $info->version;
			}
			if ($m = Core_Regexps::match_with_results('/^hash\s+([^\s]+)\s+(.+)$/',$line)) $fhashes[trim($m[2])] = trim($m[1]);
		}
		$info->fhashes = $fhashes;
		return $info;
	}

	public function install($form) {
		if (!isset($this->replaces['version'])) $this->replaces['version'] = $this->version;

		$this->setup_from_form($form);
		$this->run_files('actions');
		$this->run_files('install');
		$this->copy_files();
		$this->save_info();
	}

	protected function cver($ver) {
		$out = '';
		foreach(explode('.',$ver) as $v) {
			$v = trim($v);
			while(strlen($v)<5) $v = "0$v";
			$out .= $v;
		}
		return $out;
	}

	protected function vcompare($v1,$v2) {
		if ($v1==$v2) return 0;
		$v1 = $this->cver($v1);
		$v2 = $this->cver($v2);
		if ($v1>$v2) return 1;
		if ($v1<$v2) return -1;
		return 0;
	}

	public function can_update() {
		$info = $this->load_info();
		if ($info) {
			if ($this->component_id==$info->id&&$this->vcompare($this->version,$info->version)==1) {
				$this->installed = $info;
				return true;
			}
		}
		return false;
	}

	public function update() {
		$this->run_files('actions');
		$this->run_files('update');
		if (!isset($this->replaces['version'])) $this->replaces['version'] = $this->version;
		$this->is_update = true;
		if (file_exists("$this->dir/versions")&&is_dir("$this->dir/versions")) {
			$versions = array();
			foreach(IO_FS::Dir("$this->dir/versions") as $v) $versions[] = $v->name;
			usort($versions,array($this,'vcompare'));
			foreach($versions as $version) {
				if ($this->vcompare($version,$this->installed->version)==1)
					$this->run_files("versions/$version");
			}
		}
		$this->copy_files();
		$this->save_info();
	}

	public function can_install() {
		if ($this->not_install_if_component_exists===true) $this->not_install_if_component_exists = $this->component_dir;
		if (is_string($this->not_install_if_component_exists)&&$this->component_exists(($this->not_install_if_component_exists))) $this->error(sprintf($this->error_component_exists,$this->not_install_if_component_exists));
		if (Core_Types::is_iterable($this->not_install_if_component_exists))
			foreach($this->not_install_if_component_exists as $name)
				if ($this->component_exists($name)) $this->error(sprintf($this->error_component_exists,$name));

		if (is_string($this->not_install_if_table_exists)&&$this->table_exists(($this->not_install_if_table_exists))) $this->error(sprintf($this->error_table_exists,$this->not_install_if_table_exists));
		if (Core_Types::is_iterable($this->not_install_if_table_exists))
			foreach($this->not_install_if_table_exists as $name)
				if ($this->table_exists($name)) $this->error(sprintf($this->error_table_exists,$name));
	}

}


