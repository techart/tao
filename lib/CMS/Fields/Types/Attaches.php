<?php
/**
 * @package CMS\Fields\Types\Attaches
 */


class CMS_Fields_Types_Attaches extends CMS_Fields_AbstractField implements Core_ModuleInterface {

	const VERSION = '0.1.0';

	protected $block_on_upload = false;

	public function filelist($dir) {
		$out = array();
		if (!is_dir($dir)) return $out;
		foreach(IO_FS::Dir($dir) as $entry) {
			$out[] = $entry->path;
		}
		return $out;
	}

	public function copy_value($from, $to, $name, $data) {
		$code = $this->temp_code();
		$from_dir = $this->dir_path($from,$code,$name,$data);
		$to_dir = $this->dir_path($to,$code,$name,$data);
		IO_FS::mkdir($to_dir, null, true);
		IO_FS::Dir($from_dir)->copy_to($to_dir);
		return parent::copy_value($from, $to, $name, $data);
	}
	
	public function get_add_file_text($name, $data) {
		return CMS::lang()->_common->ta_addfile;
	}

	public function form_fields($form,$name,$data) {
		return $form->input($name);
	}
	
	protected function get_filename() {
		$file = $this->request('file');
		if (empty($file)) {
			$fdata = json_decode(WS::env()->request->content, true);
			if (!empty($fdata['name'])) $file = $fdata['name'];
		}
		return $file;
	}
	
	protected function action_delete($name,$data,$action,$item=false) {
		$code = $this->request('code');
		$dir = $this->dir_path($item,$code,$name,$data);
		$file = $this->get_filename();
		if (empty($file)) return false;
		$path = "$dir/$file";
		if (!IO_FS::exists($path)) return false;
		@IO_FS::rm($path);
		return 'ok';
	}

	public function add($file, $name, $data, $item) {
		$dir = $this->dir_path($item, null, $name, $data);
		IO_FS::mkdir($dir, null, true);
		return IO_FS::cp($file, $dir);
	}

	public function remove($file, $name, $data, $item) {
		$dir = $this->dir_path($item, null, $name, $data);
		return IO_FS::rm($dir . '/' . $file);
	}


	public function action($name,$data,$action,$item=false) {
		$code = $this->request('code');
		$dir = $this->dir_path($item,$code,$name,$data);
		if ($action=='delete') {
			return $this->action_delete($name,$data,$action,$item);
		}
		if ($action=='download') {
			$file = $this->request('file');
			$path = "$dir/$file";
			if (!IO_FS::exists($path)) return false;
			Core::load('Net.HTTP');
			return Net_HTTP::Download($path, false);
		}
		if ($action=='reload') {
		  $t = $this->create_template($name, $data, 'files');
			return $t->with(array(
				'type_object' => $this,
				'c' => CMS::$current_controller,
				'name' => $name,
				'data' => $data,
				'item' => $item,
			))->render();
		}
		if ($action=='upload') {
			return $this->action_upload($name,$data,$action,$item);
		}
		return false;
	}

	public function action_url($name,$data,$action,$item=false,$args=false) {
		if ($action=='download'&&$item&&is_array($args)&&isset($args['file'])) {
			$code = $args['code'];
			$dir = trim($this->dir_path($item,$code,$name,$data));
			if (strlen($dir)>2&&$dir[0]=='.'&&$dir[1]=='/') {
				$dir = substr($dir,1);
				$path = "$dir/".$args['file'];
				return $path;
			}
		}
		return false;
	}

	public function assign_to_object() {}

	public function assign_from_object($form,$item,$name,$data) {
		$dir = $this->dir_path($item,$this->temp_code(),$name,$data);
		$form[$name] = $dir;
	}

	protected function preprocess($t, $name, &$data) {
		if (!isset($data['multiple'])) {
			$data['multiple'] = true;
		}
		parent::preprocess($t, $name, $data) ;
	}

	protected function layout_preprocess($l, $name, $data) {
		$l->use_scripts(CMS::stdfile_url('scripts/fields/attaches.js'));
		if ($this->block_on_upload)
			$l->use_scripts(CMS::stdfile_url('scripts/jquery/block.js'));
		if (!empty($data['__item_id']) || (!empty($data['__item']) && !empty($data['__item']->id)) ) {
			$id = $this->url_class();
			$code = <<<JS

$(function() {
$(".{$id}.field-{$name}").each(function() {TAO.fields.attaches.process($(this));});
});

JS;
			$l->append_to('js', $code);
			$l->with('url_class', $id);

			Templates_HTML::add_scripts_settings(array('fields' => array(
				$name => array(
					'confirm' => CMS::lang()->_common->ta_dfconfirm,
					'block' => $this->block_on_upload,
				)
			)));
		}
		parent::layout_preprocess($l, $name, $data);
	}

}

class CMS_Fields_Types_Attaches_ValueContainer extends CMS_Fields_ValueContainer {

	protected $filelists = array();

	public function add($file) {
		return $this->type->add($file, $this->name, $this->data, $this->item);
	}

	public function remove($file) {
		return $this->type->remove($file, $this->name, $this->data, $this->item);
	}

	public function dir() {
		return $this->type->dir_path($this->item,false,$this->name,$this->data);
	}

	public function filelist($dir = null) {
		$dir = is_null($dir) ? $this->dir() : $dir;
		if (isset($this->filelists[$dir])) return $this->filelists[$dir];
		return $this->filelists[$dir] = $this->type->filelist($dir, $this->name, $this->data, $this->item);
	}

	public function exists($dir = null) {
		return count($this->filelist($dir));
	}

	public function filelist_urls($dir = null) {
		$res = array();
		foreach ($this->filelist($dir) as $file) {
			$name = basename($file);
			if ($name)
				$res[$name] = $this->type->action_url($this->name, $this->data, 'download', $this->item, array('file' => $name, 'code' => $this->type->temp_code()));
		}
		return $res;
	}

	public function render($template = 'render/default', $parms = array()) {
		$template = $this->template()->spawn($this->type->template($this->data, $template));
		return $template->with(array(
			'files' => $this->filelist(),
			'dir' => $this->dir(),
			'filelist_urls' => $this->filelist_urls(),
			'container' => $this
		))->render();
	}
}