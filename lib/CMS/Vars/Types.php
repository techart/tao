<?php

class CMS_Vars_Types implements Core_ModuleInterface {
	const VERSION = '0.1.0';
}


interface CMS_Vars_Types_TypeInterface {
	public function type();
	public function title();
	public function create($data);
}

class CMS_Vars_Types_FieldsTypeException extends Core_Exception {

}

abstract class CMS_Vars_Types_FieldsType implements CMS_Vars_Types_TypeInterface {

	protected $fields = array();
	protected $tabs = array(
		'common' => 'Параметры'
	);

	public function check() {
		return true;
	}

	public function serialize($item) {}

	public function deserialize($item) {}

	public function __get($name) {
		if (property_exists($this, $name))
			return $this->$name;
	}

	public function create($data) {
		throw new  Core_NotImplementedException();
	}

	public function change($id, $data, $item) {
		throw new  Core_NotImplementedException();
	}

	public function set($var, $value) {
		foreach (array_keys($this->fields) as $name) {
			if (isset($value[$name]))
				$var[$name] = $value[$name];
		}
		$this->serialize($var);
		$var->update();
	}

	public function random($var) {
		return $var;
	}

	public function list_value($item) {
		throw new  Core_NotImplementedException();
	}
	
	public function __toString() {
		return $this->as_string();
	}
	
	public function as_string() {
		return $this->title();
	}

	public function get($var) {
		return $var;
	}


}

abstract class CMS_Vars_Types_FieldsTypeStorage extends CMS_Vars_Types_FieldsType {
}

abstract class CMS_Vars_Types_FieldsTypeORM extends CMS_Vars_Types_FieldsType {

	public function check() {
		$columns = CMS::orm()->vars->columns;
		$diff = array_intersect($columns, array_keys($this->fields));
		return empty($diff);
	}


	public function serialize($item) {
		$data = array();
		if (!$this->check())
			throw new CMS_Vars_Types_FieldsTypeException('The same name in the columns and fields');
		foreach (array_keys($this->fields) as $name) {
			$data[$name] = $item[$name];
		}
		$item['valuesrc'] = serialize($data); 
	}

	public function deserialize($item) {
		$data = unserialize($item['valuesrc']);
		if ($data)
			foreach ($data as $name => $value)
				$item[$name] = $value;
	}

	public function get($var) {
		$this->deserialize($var);
		$m = CMS::orm()->vars->spawn();
		$m->types()->build($this->fields)->end();
		$var->set_mapper($m);
		return $var;
	}

}

/// <class name="CMS.Var.Type.Abstract">
abstract class CMS_Vars_Types_AbstractType implements CMS_Vars_Types_TypeInterface {
	public function create($data) {
		$item = CMS::vars()->db()->make_entity();
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
		if (is_object($item)) $item->id = $id;
		$value = $data['value'];
		$rc = CMS::vars()->on_change_call($id,$value,$data);
		if (is_string($rc)) {
			$item->valuesrc = $data['valuesrc'];
			return $rc;
		}
		$item->value = $value;
		$item->update_value();
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
		$item =  CMS::vars()->db()->make_entity();
		$this->change($var->id,$data,$item);
	}

	public function set($var,$value) {	}

	public function random($var) {
		return $var;
	}
	
	public function __toString() {
		return $this->as_string();
	}
	
	public function as_string() {
		return $this->title();
	}

}
/// </class>

/// <class name="CMS.Var.Type.Dir" extends="CMS.Var.Type.Abstract">
class CMS_Vars_Types_Dir extends CMS_Vars_Types_AbstractType {
	public function type() { return 'dir'; }
	public function title() { return CMS::lang()->_vars->dir; }
	public function list_value($item) { return '<DIR>'; }
}
/// </class>

/// <class name="CMS.Var.Type.Integer" extends="CMS.Var.Type.Abstract">
class CMS_Vars_Types_Integer extends CMS_Vars_Types_AbstractType {
	public function type() { return 'int'; }
	public function title() { return CMS::lang()->_vars->int; }
	public function set($var,$value) {
		return $this->set_simple($var,$value);
	}
	public function change($id,$data,$item) {
		if (is_object($item)) $item->id = $id;
		$value = trim($data['value']);
		if (preg_match('/^\d+$/',$value)) {
			$rc = CMS::vars()->on_change_call($id,$value,$data);
			if (is_string($rc)) {
				$item->valuesrc = $data['valuesrc'];
				return $rc;
			}
			$item->value = $value;
			$item->update_value();
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
class CMS_Vars_Types_String extends CMS_Vars_Types_AbstractType {
	public function type() { return 'string'; }
	public function title() { return CMS::lang()->_vars->string; }
	public function set($var,$value) {
		return $this->set_simple($var,$value);
	}
}
/// </class>

/// <class name="CMS.Var.Type.Text" extends="CMS.Var.Type.Abstract">
class CMS_Vars_Types_Text extends CMS_Vars_Types_AbstractType {
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
class CMS_Vars_Types_Html extends CMS_Vars_Types_AbstractType {
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
class CMS_Vars_Types_HtmlP extends CMS_Vars_Types_Html {
	public function type() { return 'htmlp'; }
	public function title() { return CMS::lang()->_vars->htmp; }

	public function change($id,$data,$item) {
		if (is_object($item)) $item->id = $id;
		$parms = CMS::parse_parms($data['parmsrc']);
		$value = $data['value'];
		if (is_string($parms)) {
			$item->parmsrc = $data['parmsrc'];
			return $parms;
		}
		$rc = CMS::vars()->on_change_call($id,$value,$data);
		if (is_string($rc)) {
			$item->parmsrc = $data['parmsrc'];
			return $rc;
		}
		$item->assign(array(
			'value' => $value,
			'valuesrc' => '',
			'parms' => serialize($parms),
			'parmsrc' => $data['parmsrc'],
		));
		$item->update_full_value();
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
class CMS_Vars_Types_Mail extends CMS_Vars_Types_HtmlP {

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
class CMS_Vars_Types_Array extends CMS_Vars_Types_AbstractType {
	public function type() { return 'array'; }
	public function title() { return CMS::lang()->_vars->array; }
	public function change($id,$data,$item) {
		if (is_object($item)) $item->id = $id;
		$value = CMS::parse_parms($data['valuesrc']);
		if (is_string($value)) {
			$item->valuesrc = $data['valuesrc'];
			return $value;
		}

		else {
			$rc = CMS::vars()->on_change_call($id,$value,$data);
			if (is_string($rc)) {
				$item->valuesrc = $data['valuesrc'];
				return $rc;
			}
			$item->assign(array('value' => serialize($value), 'valuesrc' => $data['valuesrc']));
			$item->update_value();
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
		$item = CMS::vars()->db()->make_entity();
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
class CMS_Vars_Types_File extends CMS_Vars_Types_AbstractType {
	public function type() { return 'file'; }
	public function title() { return CMS::lang()->_vars->file; }

	public function get($var) {
		return new CMS_FILE_PATH_URL($var->value);
	}

	public function change($id,$data,$item) {
		if (is_object($item)) $item->id = $id;
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
			$rc = CMS::vars()->on_change_call($id,$value,$data);
			if (is_string($rc)) {
				$item->valuesrc = $data['valuesrc'];
				return $rc;
			}
			$item->value = "$dir/$name";
			$item->update_value();
		}
	}


}
/// </class>
