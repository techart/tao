<?php

Core::load('CMS.Fields.Types.Documents', 'CMS.Fields.Types.Image');

class CMS_Fields_Types_Gallery extends CMS_Fields_Types_Documents {

	protected $default_admin_size = '100x100';
	protected $mods_file_name = 'user_mods.json';
	protected $autoedit_on_upload = false;
	protected $block_on_upload = true;

	protected $preset;

	public function __construct() {
		$this->preset = CMS_Fields_Types_Image::Preset();
		parent::__construct();
	}
	
	public function get_preset() {
		return $this->preset;
	}

	public function get_add_file_text($name, $data) {
		return CMS::lang()->_common->ta_addimg;
	}
	
	public function assign_to_object($form,$object,$name,$data) {
		if (empty($form[$name])) return;
		$data = json_decode($form[$name], true);
		if (!empty($data['files'])) $this->update_files_data($data, $name, $data, $object);
	}
	
	public function assign_from_object($form,$object,$name,$data) {
		$form[$name] = json_encode($this->files_data($name, $data, $object));
	}
	
	public function validate_path($path) {
		return parent::validate_path($path) && in_array(pathinfo($path, PATHINFO_EXTENSION), $this->valid_extensions());
	}
	
	public function admin_size($data) {
		return $data['admin size'] ? $data['admin size'] : $this->default_admin_size;
	}
	
	//TODO: get form $data
	public function valid_extensions() {
		return array('jpg','jpeg','gif','png','bmp');
	}

	protected function zip_upload($fobject, $name, $data, $action, $item) {
		$res = array();
		$mtype = MIME::type_for_file($fobject->original_name);
		if ($mtype->type == 'application/zip;zip') {
			$zip = new ZipArchive();
			$ro = $zip->open($fobject->path);
			if ($ro === true) {
				$dir = CMS::temp_dir() . '/' . $fobject->name;
				$dir_object = IO_FS::mkdir($dir, null, true);
				if ($dir_object) {
					$zip->extractTo($dir);
					$objects = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS));
					foreach($objects as $k => $object){
						$base = trim($object->getBasename($object->getExtension()), '. ');
						if (!empty($base)) {
							$upload = Net_HTTP::Upload($object->getPathname(), $object->getFilename(), 
								array(
									'name' => $object->getFilename(),
									'type' => MIME::type_for_file($object->getPathname())->type,
									'tmp_name' => $object->getPathname(),
									'error' => 0,
									'size' => $object->getSize()
								));
							$res[] = $this->upload_file($upload, $name, $data, $action, $item);
						}
					}
					$dir_object->rm();
				}
				$zip->close();
			}
		}
		return $res;
	}

	protected function upload_file($fobject, $name, $data, $action, $item) {
		$res = (isset($data['zip']) && $data['zip'] === false) ? array() : $this->zip_upload($fobject, $name, $data, $action, $item);
		if (!empty($res)) return $res;
		return parent::upload_file($fobject, $name, $data, $action, $item);
	}

	protected function upload_return($name, $data, $new_file, $dir, $filename) {
		$this->preset->upload_resize($name, $data, $new_file);
		return parent::upload_return($name, $data, $new_file, $dir, $filename);
	}

	protected function add_files_to_data($files, $name, $data, $item) {
		$res = parent::add_files_to_data($files, $name, $data, $item);
		$this->preset->preset_on_upload($this->container($name, $data, $item));
		return $res;
	}
	
	public function action_left($name, $data, $action, $item = false, $fields = array()) {
		$filename = $this->dir_path($item, false,$name,$data) . '/' . $this->request('filename');
		$file = IO_FS::File($filename);
		if (!$file->exists()) Net_HTTP::not_found();
		Core::load('CMS.Images');
		$method = 'turn_' . $action;
		CMS_Images::Image($file->path)->$method()->save($file->path);
		$file->set_permission();
		Events::call('admin.change');
		return 'ok';
	}
	
	public function action_right($name, $data, $action, $item = false, $fields = array()) {
		return $this->action_left($name, $data, $action, $item, $fields);
	}
	
	public function action_caption($name, $data, $action, $item = false, $fields = array()) {
		$caption = $this->request('caption');
		$filename = $this->request('filename');
		$files = $this->files_data($name, $data, $item);
		foreach ($files['files'] as &$f) {
			if ($f['name'] == $filename)
				$f['caption'] = $caption;
		}
		$this->update_files_data($files, $name, $data, $item);
		return 'ok';
	}
	
	public function action_user_mod_upload($name, $data, $action, $item = false, $fields = array()) {
		$up = $this->request('user_mode_attachement');
		$mod = $this->request('mod');
		$file_name = $this->request('file_name');
		if ($up && $mod && $file_name) {
			$dir = $this->dir_path($item, $this->request('code'), $name, $data);
			$mod_dir = $dir . '/' . $mod;
			$path = $mod_dir . '/' . $file_name;
			IO_FS::mkdir($mod_dir, null, true)->set_permission();
			$mods_info = $this->load_mods_info($dir);
			if (move_uploaded_file($up->path, $path)) {
				$mods_info[$file_name][$mod] = 1;
				$this->save_mods_info($dir, $mods_info);
				return 'ok';
			} else {
				return 'Не удалось загрузить файл';
			}
		} else {
			return 'Переданы не верные параметры';
		}
		return 'ok';
	}
	
	public function action_user_mod_delete($name, $data, $action, $item = false, $fields = array()) {
		$mod = $this->request('mod');
		$file_name = $this->request('file_name');
		$dir = $this->dir_path($item, $this->request('code'), $name, $data);
		$path = $dir . '/' . $mod . '/' . $file_name;
		if (IO_FS::exists($path) && IO_FS::rm($path)) {
			$mods_info = $this->load_mods_info($dir);
			if (!empty($mods_info[$file_name][$mod]))
				unset($mods_info[$file_name][$mod]);
			$this->save_mods_info($dir, $mods_info);
			return 'ok';
		}
		return 'ok';
	}
	
	public function load_mods_info($dir) {
		$path = $dir . '/' . $this->mods_file_name;
		if (IO_FS::exists($path)) {
			return json_decode(IO_FS::File($path)->load(), true);
		}
		return array();
	}
	
	public function save_mods_info($dir, $mods_info) {
		$path = $dir . '/' . $this->mods_file_name;
		IO_FS::File($path)->update(json_encode($mods_info));
		return $this;
	}
	
	protected function stdunset($data) {
		$res = parent::stdunset($data);
		return $this->punset($res, 'user_mods', 'upload_mods', 'presets', 'zip');
	}

	protected function layout_preprocess($l, $name, $data) {
		$this->use_styles(CMS::stdfile_url('styles/fields/gallery.css'));
		$this->use_scripts(CMS::stdfile_url('scripts/jquery/dragsort.js'));
		//$this->use_scripts(CMS::stdfile_url('scripts/jquery/json.js'));
		$this->use_scripts(CMS::stdfile_url('scripts/fields/gallery.js'));
		$l->use_scripts(CMS::stdfile_url('scripts/jquery/block.js'));
		$id = $this->url_class();
		$code = <<<JS
		$(function () { $('.${id}.field-$name').each(function() {TAO.fields.gallery.process($(this))}) });;
JS;
		$item = $this->get_item($name, $data);
		if ($item && method_exists($item, 'is_phantom') && !$item->is_phantom()) {
			$l->append_to('js', $code);
		}
		$l->with('url_class', $id);
		return parent::layout_preprocess($l, $name, $data);
	}

	protected function default_doc_fields($name, $data) {
		return array(
			'caption' => array('fieldLabel' => 'Название'),
		);
	}

}

class CMS_Fields_Types_Gallery_ValueContainer extends CMS_Fields_Types_Image_ModsCache implements IteratorAggregate, Countable  {

	protected $fullsize = null;

	protected $to_remake = array();

	protected $orig_files = array();
	
	public function dir() {
		return $this->type->dir_path($this->item,false,$this->name,$this->data);
	}
	
	public function path() {
		return '';
	}

	public function add($file) {
		$this->type->add($file, $this->name, $this->data, $this->item);
		return $this;
	}

	public function remove($file) {
		$this->type->remove($file, $this->name, $this->data, $this->item);
		return $this;
	}

	protected function files_names($files) {
		$res = array();
		foreach ($files as $f)
			$res[] = pathinfo($f, PATHINFO_BASENAME);
		return $res;
	}

	protected function mods_is_remake($path) {
		$mod = basename($path);
		$mods_info = $this->type->load_mods_info($this->dir());
		$files = $this->filelist();
		if (empty($files)) return false;
		$names = $this->files_names($files);
		$fn = array_combine($names, $files);
		$res = false;
		foreach ($fn as $name => $f) {
			if (IO_FS::is_dir($f)) continue;
			$search = $path . '/' . $name;
			if (is_file($search)) {
				if(!$mods_info[$name][$mod] && filemtime($search) < filemtime($f)) {
					$res = true;
					$this->to_remake[$name] = $search;
				}
			} else {
				$res = true;
				$this->to_remake[$name] = $search;
			}
		}
		return $res;
	}
	
	public function filelist($dir = null) {
		if (is_null($dir) && !empty($this->orig_files)) return $this->orig_files;
		$dir = is_null($dir) ? $this->dir() : $dir;
		$res = $this->type->filelist($dir, $this->name, $this->data, $this->item);
		if (is_null($dir)) $this->orig_files = $res;
		return $res;
	}
	
	public function mods_filelist() {
		$dir = $this->cached_path();
		return $this->filelist($dir);
	}
	
	protected function mods_process($path) {
		IO_FS::mkdir($path, null, true)->set_permission();
		Core::load('CMS.Images');
		foreach ($this->filelist() as $f) {
			$name = pathinfo($f, PATHINFO_BASENAME);
			if (isset($this->to_remake[$name])) {
				$image = CMS_Images::Image($f);
				$image->modify($this->mods);
				$file = "$path/$name";
				$image->save("$path/$name");
				IO_FS::File($file)->set_permission();
			}
		}
		$this->to_remake = array();
	}
	
	public function cached_path() {
		if (count($this->mods)==0) return $this->dir();
		$this->cache_mods();
		return $this->value_to_path($this->cached);
	}

	public function files_data() {
		return $this->type->files_data($this->name, $this->data, $this->item);
	}

	public function preset($name) {
		return $this->type->get_preset()->preset($this, $name);
	}

	public function fullsize($action) {
		$this->fullsize = $action;
		return $this;
	}
	
	public function render($type = 'render/insertion') {
		Core::load('CMS.Images');
		$template = $this->template()->spawn($this->type->template($this->data, $type));
		$mods = $this->mods;
		$fullsize_mods = array();
		$files = $this->mods_filelist();
		if ($this->fullsize) {
			$fullsize_mods = $this->mods_reset()->preset($this->fullsize)->mods;
			$original_files = $this->mods_filelist();
		}
		else {
			$original_files = $this->filelist($this->dir());
		}
		$this->mods = $mods;

		return $template->with(array(
			'mods' => $this->mods,
			'fullsize_mods' => $fullsize_mods,
			'files' => $files,
			'original_dir' => $this->dir(),
			'original_files' => $original_files,
			'files_data' => $this->type->files_data($this->name, $this->data, $this->item),
			'container' => $this
		))->render();
	}

	//TODO: optimize
	public function get($index) {
		$files = $this->files_names($this->filelist());
		$files_flat = array_values($files);
		$file = $files_flat[$index];
		$gindex = array_search($file, $files);
		if ($gindex === FALSE) return null;
		return $this->create_item($file, $gindex);
	}

	protected function create_item($file, $gindex) {
		$item = new CMS_Fields_Types_Gallery_ItemContainer($this->name, $this->data, $this->item, $this->type);
		$item->set_parent($this)->set_file($file, $gindex)->set_mods($this->mods);
		return $item;
	}

	public function offsetGet($index) {
		return $this->get($index);
	}

	public function getIterator() {
		$values = array();
		$files = $this->files_names($this->filelist());
		$files_flat = array_values($files);
		$count = count($files);
		for ($i = 0; $i < $count; $i++) {
			$file = $files_flat[$i];
			$gindex = array_search($file, $files);
			$values[$gindex] = $this->create_item($file, $gindex);
		}
        return new ArrayIterator($values);
    }

    public function count() {
    	return count($this->filelist());
    }

}

class CMS_Fields_Types_Gallery_ItemContainer extends CMS_Fields_Types_Gallery_ValueContainer implements ArrayAccess {

	protected $file_name;
	protected $index;
	protected $file_data;
	protected $parent;

	public function set_parent($parent) {
		$this->parent = $parent;
		return $this;
	}

	public function set_file($name, $index) {
		$this->file_name = $name;
		$this->index = $index;
		$data = $this->files_data();
		$this->file_data = $data['files'][$index];
		$dir = $this->dir();
		if ($this->parent->fullsize) {
			$dir = $this->mods_reset()->preset($this->parent->fullsize)->cached_path();
		}
		$this->file_data['orig_path'] = $dir . '/' . $this->file_name;
		$this->file_data['orig_url'] = $this->value_to_url($this->file_data['orig_path']);
		return $this;
	}

	public function get_file_data() {
		return $this->file_data;
	}

	public function cached_path() {
		$dir = parent::cached_path();
		$this->file_data['path'] = $dir . '/' . $this->file_name;
		$this->file_data['url'] =  $this->value_to_url($this->file_data['path']);
		return $dir;
	}

	public function set_mods($mods) {
		$this->mods = $mods;
		$this->cached = false;
		return $this;
	}

	public function filelist($dir = null) {
		$dir = is_null($dir) ? $this->dir() : $dir;
		return array($this->index => $dir . '/' . $this->file_name);
	}

	public function render($type = 'render/ul') {
		return parent::render($type);
	}

	public function offsetGet($name) {
		if (in_array($name, array('path', 'url')))
			$this->cached_path();
		if (isset($this->file_data[$name])) return $this->file_data[$name];
		return null;
	}

	public function offsetSet($name, $value) {
		throw new Core_NotImplementedException();
	}

	public function offsetUnset($name) {
		throw new Core_NotImplementedException();
	}

	public function offsetExists($name) {
		return isset($this->file_data[$name]);
	}

}
