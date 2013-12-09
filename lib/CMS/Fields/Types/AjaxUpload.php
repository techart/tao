<?php
/**
 * @package CMS\Fields\Types\AjaxUpload
 */


class CMS_Fields_Types_AjaxUpload extends CMS_Fields_AbstractField implements Core_ModuleInterface {

	const VERSION = '0.0.0';

	public function extension($s) {
		if ($m = Core_Regexps::match_with_results('{\.([^.]+)$}',$s)) return strtolower(trim($m[1]));
		return false;
	}

	public function form_fields($form,$name,$data) {
		return $form->input($name);
	}

	public function action($name,$data,$action,$item=false) {
		$c = CMS::$current_controller;
		$item_id = $item ? $item->id() : 0;
		if (isset($_GET['filename'])) {
			$filename = $_GET['filename'];
			$filename = str_replace('..','',$filename);
			$path = CMS::temp_dir().'/'.$filename;
		}
		
		if ($action=='temp') {
			if (!IO_FS::exists($path)) return false;
			Core::load('Net.HTTP');
			return Net_HTTP::Download($path, false);
		}
		if ($action=='delete') {
			if (IO_FS::exists($path)) IO_FS::rm($path);
			return 'ok';
		}
		if ($action=='info') {
			if ($filename=='none') return 'ok';
			return $this->render($name, $data,'info-ajax.phtml',array(
				'file_path' => $path,
				'file_url' => $c->field_action_url($name,'temp',$item,array('filename' => str_replace('/','',$filename))),
				'name' => $name,
			));
			
		}
		if ($action=='upload') {
			return $this->action_upload($name,$data,$action,$item);
		}
		return false;
	}

	protected function uploaded_filename($name, $data, $file) {
		$code = $this->request('code');
		$filename =  "file-$code-$name";
		if ($m = Core_Regexps::match_with_results('{\.([^.]+)$}',$file['name']))
			$filename .= '.'.strtolower($m[1]);
		return $filename;
	}

	public function dir_path($item,$code,$name,$data) {
		return CMS::temp_dir();
	}

	protected function upload_return($name, $data, $new_file, $dir, $filename) {
		return "!$filename";
	}

	public function assign_to_object($form,$item,$name,$data) {
		$item_id = $item->id();
		$value = trim($form[$name]);
		$old = '';
		if ($item_id>0&&isset($item[$name])) {
			$old = trim($item[$name]);
		}

		if ($value!='') {
			if ($value[0]=='#') {
				$path = $this->value_to_path($old);
				if ($path&&IO_FS::exists($path)) IO_FS::rm($path);
			}
		}

		$item[$name] = $value;
	}
        
	public function assign_from_object($form,$object,$name,$data) {
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
		$from_value = $from[$name];
		$to_value = preg_replace("!/(\d+){$from->id()}/!", '/${1}' . $to->id() . '/', $from_value);
		$from_path = $this->value_to_path($from_value);
		$to_path = $this->value_to_path($to_value);
		$to[$name] = $to_value;
		$file = IO_FS::File($from_path);
		if ($file->exists()) {
			$file->copy_to($to_path);
		}
		return $this;
	}

	public function value_to_path($value) {
		$value = trim($value);
		if ($value=='') return false;
		if ($value[0]=='.'||$value[0]=='/') return $value;
		return "./$value";
	}

	public function value_to_url($value,$name=false,$item=false) {
		$value = trim($value);
		if ($value=='') return false;
		$value = preg_replace('{^\./}','',$value);
		if ($value[0]=='.'||$value[0]=='/') {
			if ($name&&$item) {
				$h = md5($value);
				$session = $this->request()->session();
				$session["download_$h"] = $value;
				$c = CMS::$current_controller;
				return $c->field_action_url($name,'download',$item,array('code'=> $h));
			}
			return false;
		}
		return "/$value";
	}

	public function process_inserted($name,$data,$item) {
		$item_id = $item->id();
		$value = trim($item[$name]);

		if ($value!='') {
			if ($value[0]=='#') {
				$uploaded = trim(substr($value,1));
				$value = '';
				if ($uploaded!='') {
					$uploaded = str_replace('..','',$uploaded);
					$uploaded = CMS::temp_dir().'/'.$uploaded;
					if (IO_FS::exists($uploaded)) {
						$ext = ''; if ($m = Core_Regexps::match_with_results('{\.([^\.]+)$}',$uploaded)) $ext = trim($m[1]);
						$filename = $this->uploaded_file_name($name,$data,$item,$ext);
						$dir = $this->uploaded_file_dir($name,$data,$item);
						if ($dir) {
							if (!IO_FS::exists($dir)) CMS::mkdirs($dir);
							$_dir = preg_replace('{^\./}','',$dir);
							copy($uploaded,"$dir/$filename");
							IO_FS::rm($uploaded);
							$value = "$_dir/$filename";
						}
					}
				}
			}
		}

		$item[$name] = $value;
		return true;
	}

	public function uploaded_file_dir($name,$data,$item) {
		$id = $item->id();
		if ($id>0) {
			$dir = $item->homedir(isset($data['private'])&&$data['private']);
			if (!$dir) return false;
			if ($dir[0]!='.'&&$dir[0]!='/') $dir = "./$dir";
			return $dir;
		}
		return false;
	}

	public function cache_dir($name,$data,$item) {
		$id = $item->id();
		if ($id>0) {
			$dir = $item->cache_dir_path($item,isset($data['private'])&&$data['private']);
			if (!$dir) return false;
			if ($dir[0]!='.'&&$dir[0]!='/') $dir = "./$dir";
			return $dir;
		}
		return false;
	}


	public function uploaded_file_name($name,$data,$item,$ext) {
		$id = $item->id();
		$dotext = $ext? ".$ext": "";
		$filename = '%{field}-%{id}-%{time}%{dotext}';
		$filename = str_replace('%{field}',$name,$filename);
		$filename = str_replace('%{id}',$id,$filename);
		$filename = str_replace('%{time}',time(),$filename);
		$filename = str_replace('%{ext}',$ext,$filename);
		$filename = str_replace('%{dotext}',$dotext,$filename);
		return $filename;
	}

	public function container_class()
	{
		//FIXME: rename class to CMS_Fields_Types_AjaxUpload_ValueContainer
		return 'CMS_Fields_Types_AjaxUpload_Container';
	}

}

//FIXME: rename class to CMS_Fields_Types_AjaxUpload_ValueContainer
class CMS_Fields_Types_AjaxUpload_Container extends CMS_Fields_ValueContainer {
	
	public function dir() {
		return $this->item->cache_dir_path(isset($this->data['private'])&&$this->data['private']);
	}
	
	public function render($args=array()) {
                $url = $this->url();
                $path = $this->path();
		if (!IO_FS::exists($path)) return '';
                $args['href'] = $url;
                return $this->template()->tags->tag('a', $args);
	}

	public function set($value) {
		if (IO_FS::exists($value)) {
			$ext = ''; if ($m = Core_Regexps::match_with_results('{\.([^\.]+)$}',$value)) $ext = strtolower(trim($m[1]));
			$filename = $this->type->uploaded_file_name($this->name,$this->data,$this->item,$ext);
			$dir = $this->type->uploaded_file_dir($this->name,$this->data,$this->item);
			if ($dir) {
				if (!IO_FS::exists($dir)) CMS::mkdirs($dir);
				$_dir = preg_replace('{^\./}','',$dir);
				copy($value,"$dir/$filename");
				IO_FS::rm($uploaded);
				$value = "$_dir/$filename";
				return parent::set($value);
			}
		}
		return $this;
	}


}
