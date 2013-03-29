<?php

class CMS_Fields_Types_Upload extends CMS_Fields_AbstractField implements Core_ModuleInterface {

	const VERSION = '0.0.0';

	public function form_fields($form,$name,$data) {
		$form->enctype("multipart/form-data");
		return $form->upload($name);
	}

	public function view_value($value,$name,$data) {
		$path = is_string($value)? $value : $value[$name];
		$path = trim($path);
		if ($path=='') return '';
		$caption = CMS::lang()->_common->ta_download;
		$url = $this->uploaded_url($path);
		return "<a href='$url'>$caption</a>";
	}

	public function is_upload() {
		return true;
	}

	public function action($name,$data,$action,$item) {
		$file = $this->uploaded_path($item[$name]);
		if ($action=='delete') {
			@IO_FS::rm($file);
			$item[$name] = '';
			CMS::$current_controller->field_callback_update_item($item);
			return 'ok';
		}
		if ($action=='download') {
			$path = $this->uploaded_path($file);
			return Net_HTTP::Download($path);
		}
		return false;
	}

	public function process_uploads($name,$parms,$uploads,$item,$extra=array()) {
		if (!isset($uploads[$name])) return;
		$file = $uploads[$name];

		if($extra) {
			$dir = $extra['homedir'];
			if (isset($parms['private']) && $parms['private']) $dir = $extra['private_homedir'];
		}

		if (isset($parms['dir'])) $dir = $parms['dir'];
		$dir = rtrim($dir,'/');
		if (isset($parms['subdir'])) $dir .= '/'.$parms['subdir'];
		$dir = rtrim($dir,'/');
		CMS::mkdirs($dir);

		$filename = '%{field}-%{id}-%{time}%{dotext}';
		if (isset($parms['filename'])) $filename = $parms['filename'];

		$original_name = $file['original_name'];
		$original_name_we = $file['original_name_we'];
		$ext = $file['ext'];
		$dotext = $file['dotext'];

		$newfile = $filename;
		$newfile = str_replace('%{field}',$name,$newfile);
		$newfile = str_replace('%{id}',$item->id(),$newfile);
		$newfile = str_replace('%{time}',time(),$newfile);
		$newfile = str_replace('%{ext}',$ext,$newfile);
		$newfile = str_replace('%{dotext}',$dotext,$newfile);
		$newfile = str_replace('%{name}',$this->validate_filename($original_name),$newfile);
		$newfile = str_replace('%{namewe}',$this->validate_filename($original_name_we),$newfile);
		$newfile = "$dir/$newfile";

		$newpath = $this->uploaded_path($newfile);
		$uplpath = $this->uploaded_path($file['uplpath']);

		copy($uplpath,$newpath);
		CMS::chmod_file($newpath);

		if($extra) {
			$oldfile = isset($extra['old_item'][$name])?trim($extra['old_item'][$name]):'';
			$oldpath = $this->uploaded_path($oldfile);
			if ($oldpath && $oldfile!=$newfile) IO_FS::rm($oldpath);
		}

		$item[$name] = $newfile;

	}

	protected function uploaded_path($path) {
		$path = trim($path);
		if ($path=='') return false;
		if ($path[0]=='/'||$path[0]=='.') return $path;
		return "./$path";
	}

	protected function uploaded_url($path) {
		$path = trim($path);
		if ($path=='') return false;
		if ($path[0]=='/'||$path[0]=='.') return $path;
		return "/$path";
	}

	protected function validate_filename($name) {
		$name = trim($name);
		$name = preg_replace('{\s+}sm',' ',$name);
		$name = str_replace(' ','_',$name);
		$name = CMS::translit($name);
		return $name;
	}

	public function assign_to_object() {}
	public function assign_from_object() {}

	public function copy_value($from, $to, $name, $data) {
		$from_value = $from[$name];
		$to_value = preg_replace("!/(\d+){$from->id()}/!", '/${1}' . $to->id() . '/', $from_value);
		$to[$name] = $to_value;
		$file = IO_FS::File($from_value);
		if ($file->exists()) {
			$file->copy_to($to_value);
		}
		return $this;
	}

}
