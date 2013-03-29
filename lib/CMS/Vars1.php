<?php
/// <module name="CMS.Vars" maintainer="gusev@techart.ru" version="0.0.0">

/// <class name="CMS.Vars" stereotype="module">
///   <implements interface="Core.ModuleInterface" />
///   <depends supplier="CMS.Var" stereotype="uses" />
///   <depends supplier="CMS.Var.Type.Abstract" stereotype="uses" />
class CMS_Vars1 implements Core_ModuleInterface {

///   <constants>
	const VERSION = '0.0.0';
///   </constants>

	static $types = array();
	static $cache = array();
	static $pcache = array();
	static $plugins_on_change = array();
	static $files_dir;

///   <protocol name="creating">


///   <method scope="class" name="initialize">
///     <args>
///       <arg name="config" type="array" default="array()" />
///     </args>
///     <body>
	static function initialize($config=array()) {
		self::$filed_dir = './'.Core::option('files_name').'/vars';
		foreach($config as $key => $value) self::$$key = $value;

		self::register_type(
			'CMS_Var_Type_Dir',
			'CMS_Var_Type_Integer',
			'CMS_Var_Type_String',
			'CMS_Var_Type_Text',
			'CMS_Var_Type_Html',
			'CMS_Var_Type_Array',
			'CMS_Var_Type_Mail',
			'CMS_Var_Type_HtmlP',
			'CMS_Var_Type_File'
		);

		CMS_Dumps::dumper('VARS','CMS.Dumps.Vars');

		Core::load('DB.SQL');
		
		DB_SQL::db()->

			table(DB_SQL::Table('vars')->
				maps_to('CMS.Var')->
				serial('id')->
			        columns('id', 'parent_id', 'site', 'component', 'code', 'title', 'value', 'valuesrc', 'vartype', 'parms', 'parmsrc', 'full')->
			        default_sql('select', 'find', 'insert', 'update', 'delete', 'count')->
			        sql('insert_new',	DB_SQL::Insert('parent_id','site','code','title','full','vartype','value','valuesrc','parms','parmsrc'))->
			        sql('find_by_code',	DB_SQL::Find()->where('code=:code','site=:site','parent_id=:parent_id','component=:component'))->
			        sql('update_parms',	DB_SQL::Update()->set('vartype=:vartype,code=:code,title=:title,full=:full')->where('id=:id'))->
			        sql('update_value',	DB_SQL::Update()->set('value=:value')->where('id=:id'))->
			        sql('update_value_src',	DB_SQL::Update()->set('value=:value,valuesrc=:valuesrc')->where('id=:id'))->
			        sql('update_full_value',DB_SQL::Update()->set('value=:value,valuesrc=:valuesrc,parms=:parms,parmsrc=:parmsrc')->where('id=:id'))->
			        sql('get_code',		DB_SQL::Count('code')->where('id=:id'))->
			        sql('delete_component',	DB_SQL::Delete()->where('component=:component'))->
			        sql('select_code',	DB_SQL::Select()->columns('parent_id,code')->where('id=:id'))->
			        sql('select_childs',	DB_SQL::Select()->columns('id','code')->where('parent_id=:parent_id'))->
			        sql('select_dir',	DB_SQL::Select()->where('parent_id=:parent_id','site=:site','component=:component')->order_by('IF(vartype="dir",0,1),id'))->
			        sql('select_component',	DB_SQL::Select()->where('component=:component')->order_by('parent_id,id'))->
			        sql('select_dir_common',DB_SQL::Select()->where('parent_id=:parent_id','site=:site','full=0','component=:component')->order_by('IF(vartype="dir",0,1),id'))
			);

	}
///     </body>
///   </method>

///   </protocol>

///   <protocol name="performing">

///   <method scope="class" name="on_change">
///     <args>
///       <arg name="name" type="string" />
///       <arg name="class" type="string" />
///       <arg name="method" type="string" />
///     </args>
///     <body>
	static function on_change($fc,$class,$method) {
		self::$plugins_on_change[$fc] = array($class,$method);
	}
///     </body>
///   </method>

///   <method scope="class" name="register_type" varargs="true">
///     <body>
	static function register_type() {
		$args = func_get_args();
		foreach($args as $class) {
			$instance = Core_Types::reflection_for($class)->newInstance();
			$type = $instance->type();
			self::$types[$type] = $instance;
		}
	}
///     </body>
///   </method>

	static public function type($name) {
		return self::$types[$name];
	}
	
	static public function types() {
		return self::$types;
	}

///   </protocol>


///   <protocol name="accessing">


///   <method scope="class" name="set" varargs="true">
///     <body>
	static function set() {
		$args = func_get_args();
		if (sizeof($args)<2) return;
		$name = $args[0];
		$site = '__';
		$component = '';
		if (sizeof($args)==2) {
			$value = $args[1];
		}

		if (sizeof($args)>2) {
			$site = $args[1];
			$value = $args[2];
		}

		if (sizeof($args)>3) {
			$component = $args[2];
			$value = $args[3];
		}

		$var = self::get_var_by_parms($name,$site,$component);

		if (!$var) return false;
		$value = self::$types[$var->vartype]->set($var,$value);
	}
///     </body>
///   </method>

///   <method scope="class" name="title" returns="string">
///     <args>
///       <arg name="name" type="string" />
///       <arg name="site" type="string|false" default="false" />
///     </args>
///     <body>
	static function title($name,$site=false) {
	 	if (!$site) $site = CMS::site();
		$var = self::get_var_by_parms($name,$site,'');
		if (!$var) return false;
		return $var->title;
	}
///     </body>
///   </method>

///   <method scope="class" name="get">
///     <args>
///       <arg name="name" type="string" />
///       <arg name="parms" type="array|false" default="false" />
///     </args>
///     <body>
	static function get($name,$p=false) {
		$site = false;
		if (is_string($p)) $site = $p;
	 	if (!$site) $site = CMS::site();
		$var = self::get_var_by_parms($name,$site,'');
		if (!$var) return false;
		$value = self::$types[$var->vartype]->get($var,$p);
		return $value;
	}
///     </body>
///   </method>

///   <method scope="class" name="my">
///     <args>
///       <arg name="name" type="string" />
///       <arg name="parms" type="array|false" default="false" />
///     </args>
///     <body>
	static function my($name,$p=false) {
		return self::get_for_component(CMS::$current_component_name,$name,$p);
	}
///     </body>
///   </method>

///   <method scope="class" name="random">
///     <args>
///       <arg name="name" type="string" />
///     </args>
///     <body>
	static function random($name,$component='') {
		$var = self::get_var_by_parms($name,CMS::site(),$component);
		if (!$var) return false;
		while ($var->vartype=='dir') {
			$vars = (array)DB_SQL::db()->vars->select_dir($var->id,CMS::site(),$component);
			shuffle($vars);
			$var = $vars[0];
		}
		return self::$types[$var->vartype]->get($var);
	}
///     </body>
///   </method>

///   <method scope="class" name="get_list">
///     <args>
///       <arg name="name" type="array" />
///     </args>
///     <body>
	static function get_list($name,$component='',$rec=false) {
		$var = self::get_var_by_parms($name,CMS::site(),$component);
		if (!$var) return false;
		$out = array();
		while ($var->vartype=='dir') {
			$vars = (array)DB_SQL::db()->vars->select_dir($var->id,CMS::site(),$component);
			$c = 0;
			foreach($vars as $var) {
				$c++;
				$code = trim($var->code);
				if ($code=='') $code = "_$c";
				$out[$code] = self::$types[$var->vartype]->get($var);
			}
		}
		return $out;
	}
///     </body>
///   </method>


///   </protocol>


///   <protocol name="supporting">

///   <method scope="class" name="get_by_parms">
///     <args>
///       <arg name="name" type="string" />
///       <arg name="site" type="string" />
///       <arg name="component" type="string" />
///       <arg name="parms" type="array|false" default="false" />
///     </args>
///     <body>
	static function get_by_parms($name,$site,$component,$p=false) {
		$var = self::get_var_by_parms($name,$site,$component);
		if (!$var) return false;
		$value = self::$types[$var->vartype]->get($var,$p);
		return $value;
	}
///     </body>
///   </method>

///   <method scope="class" name="get_var_by_parms">
///     <args>
///       <arg name="name" type="string" />
///       <arg name="site" type="string" />
///       <arg name="component" type="string" />
///     </args>
///     <body>
	static function get_var_by_parms($name,$site,$component) {
		$cname = "$component:$name/$site";
		if (isset(self::$cache[$cname])) return self::$cache[$cname];
		$codes = explode('.',$name);
		$id = 0;
		$var = false;
		foreach($codes as $code) {
			$code = trim($code);
			if ($code!='') {
				$cparms = array(
					'parent_id' => $id,
					'code' => $code,
					'site' => $site,
					'component' => $component,
				);
				$_cparms = md5(serialize($cparms));
				if (isset(self::$pcache[$_cparms])) {
					$var = self::$pcache[$_cparms];
				}
				else {
					$var = DB_SQL::db()->vars->find_by_code($cparms);
					self::$pcache[$_cparms] = $var;
				}
				if (!$var) return false;
				$id = $var->id;
			}
		}
		self::$cache[$cname] = $var;
		return $var;
	}
///     </body>
///   </method>

///   <method scope="class" name="get_for_component">
///     <args>
///       <arg name="component" type="string" />
///       <arg name="name" type="string" />
///       <arg name="parms" type="array|false" default="false" />
///     </args>
///     <body>
	static function get_for_component($component,$name,$p=false) {
		$site = false;
		if (is_string($p)) $site = $p;
	 	if (!$site) $site = CMS::site();
		$var = self::get_var_by_parms($name,$site,strtolower($component));

		if (!$var) return false;
		$v = self::$types[$var->vartype];
		$value = self::$types[$var->vartype]->get($var,$p);

		return $value;
	}
///     </body>
///   </method>

///   <method scope="class" name="validate_parm">
///     <args>
///       <arg name="value" type="mixed" />
///     </args>
///     <body>
	static function validate_parm($value) {
		if (!is_string($value)) return $value;
		while ($m = Core_Regexps::match_with_results('{^var:(.+)$}',trim($value))) {
			$value = CMS_Vars1::get($m[1]);
		}
		return $value;
	}
///     </body>
///   </method>


	
	protected static $db;
	static public function db() {
		if (isset(self::$db)) return self::$db;
		return self::$db = new CMS_Vars1_DB();
	}


///   </protocol>

}
/// </class>

//Для совеместимости
//--------------------------------------------

class CMS_Vars extends CMS_Vars1 {}

class CMS_Vars1_DB {
	
	protected $db;
	
	public function __construct() {
		$this->db = DB_SQL::db()->vars;
	}
	
	
	public function find($id) { return $this->db->find($id);}
	
	
	public function find_dir($parent_id, $site, $component, $full = false) {
		if (!$component) $component = '';
		if ($full !== false) return $this->db->select_dir($parent_id, $site, $component);
		return $this->db->select_dir_common($parent_id, $site, $component);
	}
	
	public function for_code($cparms) {
		return $this->db->find_by_code($cparms);
	}
	
	public function for_id($parent_id, $component = '') {
		return CMS_Var::select($parent_id, $component);
	}
	
	public function make_entity() {
		return new CMS_Var();
	}
	
	public function full_code($id) {
		return CMS_Var::full_code($id);
	}
	
	public function __call($method, $args = array()) {
		return Core::invoke(array($this->db, $method), $args);
	}
	
}
//-------------------------------


/// <class name="CMS.Var" extends="DB.SQL.Entity">
class CMS_Var extends DB_SQL_Entity {

	public function select($parent_id,$component=false) {
		if (!$component) $component = '';
		$site = CMS_Admin::get_site();
		if (CMS::$globals['full']) return DB_SQL::db()->vars->select_dir(array('parent_id'=>$parent_id,'site'=>$site,'component'=>$component));
		return DB_SQL::db()->vars->select_dir_common(array('parent_id'=>$parent_id,'site'=>$site,'component'=>$component));
	}

	public function save() {
		if (!$this->component) $this->component = '';

		if ($this->id>0) {
		}

		else {
			DB_SQL::db()->vars->insert($this);
			$this->id = CMS::$db->last_insert_id();
		}
	}
	
	public function insert() {
		return $this->save();
	}
	
	public function assign(array $values = array()) {
		foreach ($values as $k => $v) $this->$k = $v;
		return $this;
	}

	public function load($id) {
		return DB_SQL::db()->vars->find($id);
	}

	public function del($id) {
		$rows = DB_SQL::db()->vars->select_childs($id);
		foreach($rows as $row) $row->del($row->id);
		DB_SQL::db()->vars->delete($id);
		CMS::rmdir(CMS_Vars1::$files_dir."/$id");
	}

	public function chparms($id,$data) {
		$data['id'] = $id;
		DB_SQL::db()->vars->update_parms($data);
	}

	public function update_value($id,$value,$valuesrc=false) {
		if (!$valuesrc) {
			DB_SQL::db()->vars->update_value(array('id'=>$id,'value'=>$value));
		}

		else {
			DB_SQL::db()->vars->update_value_src(array('id'=>$id,'value'=>$value,'valuesrc'=>$valuesrc));
		}
	}

	public function update_full_value($id,$value,$valuesrc,$parms,$parmsrc) {
		DB_SQL::db()->vars->update_full_value(array(
			'id' => $id,
			'value' => $value,
			'valuesrc' => $valuesrc,
			'parms' => $parms,
			'parmsrc' => $parmsrc,
		));
	}

	public function full_code($id=false) {
		if (!$id) $row = $this;
		else $row = DB_SQL::db()->vars->find($id);
		$out = $row->code;
		while ($row->parent_id>0) {
			$row = DB_SQL::db()->vars->find($row->parent_id);
			$out = $row->code.'.'.$out;
		}
		if (trim($row->component)!='') $out = $row->component.':'.$out;
		return $out;
	}

	public function on_change($id,$value,$data) {
		$fc = self::full_code($id);
		if (isset(CMS_Vars1::$plugins_on_change[$fc])) {
			$m = CMS_Vars1::$plugins_on_change[$fc];
			$class = trim($m[0]);
			$method = trim($m[1]);
			if ($class!=''&&$method!='') {
				$c = new ReflectionMethod($class,$method);
				return $c->invokeArgs(NULL,array($value,$fc));
			}
		}
	}

}
/// </class>




interface CMS_Var_Type_Interface {
	public function type();
	public function title();
	public function create($data);
}

/// <class name="CMS.Var.Type.Abstract">
abstract class CMS_Var_Type_Abstract implements CMS_Var_Type_Interface {
	public function create($data) {
		$item = new CMS_Var();
		$code = trim($data['code']);
		$title = trim($data['title']);
		if ($code=='') $code = 'var'.time();
		if ($title=='') $title = $this->title();
		$item->vartype = $this->type();
		$item->site = CMS_Admin::get_site();
		$item->parent_id = (int)$data['parent_id'];
		$item->code = $code;
		$item->title = $title;
		$item->component = '';
		return $item;
	}

	public function change($id,$data,$item) {
		$value = $data['value'];
		$rc = CMS_Var::on_change($id,$value,$data);
		if (is_string($rc)) {
			$item->valuesrc = $data['valuesrc'];
			return $rc;
		}
		CMS_Var::update_value($id,$value);
		return true;
	}

	public function list_value($item) {
		return trim($item->value);
	}

	public function get($var) {
		return $var->value;
	}

	public function set_simple($var,$value) {
		$data = array('value' => $value,'valuesrc' => $value);
		$item = new CMS_Var();
		$this->change($var->id,$data,$item);
	}

	public function set($var,$value) {	}

	public function random($var) {
		return $var;
	}

}
/// </class>

/// <class name="CMS.Var.Type.Dir" extends="CMS.Var.Type.Abstract">
class CMS_Var_Type_Dir extends CMS_Var_Type_Abstract {
	public function type() { return 'dir'; }
	public function title() { return CMS::lang()->_vars->dir; }
	public function list_value($item) { return '<DIR>'; }
}
/// </class>

/// <class name="CMS.Var.Type.Integer" extends="CMS.Var.Type.Abstract">
class CMS_Var_Type_Integer extends CMS_Var_Type_Abstract {
	public function type() { return 'int'; }
	public function title() { return CMS::lang()->_vars->int; }
	public function set($var,$value) {
		return $this->set_simple($var,$value);
	}
	public function change($id,$data,$item) {
		$value = trim($data['value']);
		if (preg_match('/^\d+$/',$value)) {
			$rc = CMS_Var::on_change($id,$value,$data);
			if (is_string($rc)) {
				$item->valuesrc = $data['valuesrc'];
				return $rc;
			}
			CMS_Var::update_value($id,$value);
			return true;
		}

		else {
			$item->value = $data['value'];
			return CMS::lang()->_vars->invalid_int;
		}
	}
}
/// </class>

/// <class name="CMS.Var.Type.String" extends="CMS.Var.Type.Abstract">
class CMS_Var_Type_String extends CMS_Var_Type_Abstract {
	public function type() { return 'string'; }
	public function title() { return CMS::lang()->_vars->string; }
	public function set($var,$value) {
		return $this->set_simple($var,$value);
	}
}
/// </class>

/// <class name="CMS.Var.Type.Text" extends="CMS.Var.Type.Abstract">
class CMS_Var_Type_Text extends CMS_Var_Type_Abstract {
	public function type() { return 'text'; }
	public function title() { return CMS::lang()->_vars->text; }
	public function set($var,$value) {
		return $this->set_simple($var,$value);
	}
	public function list_value($item) {
		$value = trim($item->value);
		if (mb_strlen($value)>70) {
			$value = mb_substr($value,0,70) . ' ...';
		}
		return $value;
	}
}
/// </class>

/// <class name="CMS.Var.Type.Html" extends="CMS.Var.Type.Abstract">
class CMS_Var_Type_Html extends CMS_Var_Type_Abstract {
	public function type() { return 'html'; }
	public function title() { return CMS::lang()->_vars->html; }
	public function list_value($item) {
		$value = strip_tags(trim($item->value));
		if (mb_strlen($value)>70) {
			$value = mb_substr($value,0,70) . ' ...';
		}
		return $value;
	}
}
/// </class>

/// <class name="CMS.Var.Type.HtmlP" extends="CMS.Var.Type.Html">
class CMS_Var_Type_HtmlP extends CMS_Var_Type_Html {
	public function type() { return 'htmlp'; }
	public function title() { return CMS::lang()->_vars->htmp; }

	public function change($id,$data,$item) {
		$parms = CMS::parse_parms($data['parmsrc']);
		$value = $data['value'];
		if (is_string($parms)) {
			$item->parmsrc = $data['parmsrc'];
			return $parms;
		}
		$rc = CMS_Var::on_change($id,$value,$data);
		if (is_string($rc)) {
			$item->parmsrc = $data['parmsrc'];
			return $rc;
		}
		CMS_Var::update_full_value($id,$value,'',serialize($parms),$data['parmsrc']);
		return true;
	}

	public function get($var,$values=false) {
		$rc = Data::Tree();

		$text = $var->value;
		if (Core_Types::is_iterable($values)) foreach($values as $key => $val) $text = str_replace("%{{$key}}",$val,$text);
		$rc->content = $text;

		$parms = unserialize($var->parms);
		if (!is_array($parms)) $parms = array();
		$rc->parms = $parms;

		return $rc;
	}
}
/// </class>

/// <class name="CMS.Var.Type.Mail" extends="CMS.Var.Type.HtmlP">
class CMS_Var_Type_Mail extends CMS_Var_Type_HtmlP {

	protected $attaches = array();
	protected $multipart = 'mixed';

	public function type() { return 'mail'; }
	public function title() { return CMS::lang()->_vars->mail; }
	public function create($data) {
		$item = parent::create($data);
		$item->parmsrc = "from = info@".$_SERVER['HTTP_HOST']."\nsubject = Тема письма\n";
		return $item;
	}

	protected function attaches_cb($m) {
		$src = '.'.$m[2];
		$this->multipart = 'related';
		$id = md5($src);
		$this->attaches[$id] = Mail_Message::Part()->file($src)->content_id("<$id>")->content_disposition('inline');
		return $m[1]."=\"cid:$id\"";
	}

	public function get($var,$values=false) {
		Core::load('Mail');
		if (!Core_Types::is_iterable($values)) $values = array();
		$mailtext = $var->value;
		if (Core_Types::is_iterable($values)) foreach($values as $key => $val) $mailtext = str_replace("%{{$key}}",$val,$mailtext);
		$body = CMS::render_mail('empty',array('content'=>$mailtext));


		$this->multipart = false;
		$this->attaches = array();
		$body = preg_replace_callback('{(src)="(/[^"]+)"}',array($this,'attaches_cb'),$body);

		$parms = unserialize($var->parms);

		$mail = Mail::Message()
			->subject($parms['subject'])
			->from($parms['from'])
			->to($parms['to']);

		if (!$this->multipart) {
			$mail->html($body);
		}

		else {
			if ($this->multipart=='mixed') $mail->multipart_mixed();
			if ($this->multipart=='related') $mail->multipart_related();
			$mail->html_part($body);
			foreach($this->attaches as $id => $part) $mail->part($part);
		}
		return $mail;
	}
}
/// </class>


/// <class name="CMS.Var.Type.Array" extends="CMS.Var.Type.Abstract">
class CMS_Var_Type_Array extends CMS_Var_Type_Abstract {
	public function type() { return 'array'; }
	public function title() { return CMS::lang()->_vars->array; }
	public function change($id,$data,$item) {
		$value = CMS::parse_parms($data['valuesrc']);
		if (is_string($value)) {
			$item->valuesrc = $data['valuesrc'];
			return $value;
		}

		else {
			$rc = CMS_Var::on_change($id,$value,$data);
			if (is_string($rc)) {
				$item->valuesrc = $data['valuesrc'];
				return $rc;
			}
			CMS_Var::update_value($id,serialize($value),$data['valuesrc']);
			return true;
		}
	}
	public function list_value($item) {
		$value = unserialize($item->value);
		if (is_array($value)) {
			return 'Array('.sizeof($value).')';
		}
		return 'NULL';
	}

	public function get($var) {
		return unserialize($var->value);
	}

	public function set($var,$value) {
		$src = CMS::unparse_parms($value);
		$data = array('value' => serialize($value),'valuesrc' => $src);
		$item = new CMS_Var();
		$this->change($var->id,$data,$item);
	}


}
/// </class>

/// <class name="CMS.FILE.PATH.URL">
class CMS_FILE_PATH_URL {
	protected $name;
	public function __construct($name) { $this->name = $name; }
	public function path() { return $this->name; }
	public function filename() {
		if ($m = Core_Regexps::match_with_results('{/([^/]+)$}',$this->name)) return $m[1];
		return $this->name;
	}
	public function url() { return CMS::file_url($this->name); }
}
/// </class>

/// <class name="CMS.Var.Type.File" extends="CMS.Var.Type.Abstract">
///   <depends supplier="CMS.FILE.PATH.URL" stereotype="creates" />
class CMS_Var_Type_File extends CMS_Var_Type_Abstract {
	public function type() { return 'file'; }
	public function title() { return CMS::lang()->_vars->file; }

	public function get($var) {
		return new CMS_FILE_PATH_URL($var->value);
	}

	public function change($id,$data,$item) {
		$file = $_FILES['value'];
		$name = trim($file['name']);
		$tmp_name = trim($file['tmp_name']);
		if ($tmp_name!='') {
			$dir = "./".Core::option('files_name')."/vars/$id";
			CMS::mkdirs($dir,0775);
			foreach (IO_FS::Dir($dir) as $f) @IO_FS::rm($f->path);
			$name = CMS::translit(mb_strtolower($name));
			$name = preg_replace('{\s+}','_',$name);
			$name = trim(preg_replace('{[^a-z0-9_\.\-]}','',$name));
			if ($name=='') $name = 'noname';
			if ($name[0]=='.') $name = "noname.$name";
			move_uploaded_file($tmp_name,"$dir/$name");
			chmod("$dir/$name",0775);
			$rc = CMS_Var::on_change($id,$value,$data);
			if (is_string($rc)) {
				$item->valuesrc = $data['valuesrc'];
				return $rc;
			}
			CMS_Var::update_value($id,"$dir/$name");
		}
	}


}
/// </class>

/// </module>
