<?php
/**
 * @package CMS\Fields\Types\Image
 */

Core::load('CMS.Images');


class CMS_Fields_Types_Image extends CMS_Fields_AbstractField implements Core_ModuleInterface {

	const VERSION = '0.0.0';

	static public function Preset() {
		return new CMS_Fields_Types_Image_Preset();
	}

	protected $preset;

	public function __construct() {
		$this->preset = CMS_Fields_Types_Image::Preset();
		parent::__construct();
	}
	
	public function get_preset() {
		return $this->preset;
	}

	public function valid_extensions() {
		return array('jpg','jpeg','gif','png','bmp');
	}

	public function extension($s) {
		if ($m = Core_Regexps::match_with_results('{\.([^.]+)$}',$s)) return strtolower(trim($m[1]));
		return false;
	}

	protected function is_valid($s) {
		return Core_Regexps::match('{\.('.implode('|',$this->valid_extensions()).')$}i',$s);
	}

	public function form_fields($form,$name,$data) {
		return $form->input($name);
	}

		protected function stdunset($data) {
		$res = parent::stdunset($data);
		return $this->punset($res, 'upload_mods', 'presets', 'admin_preview_size');
	}

	//TODO: refactoring
	public function action($name,$data,$action,$item=false) {
		$c = CMS::$current_controller;
		$item_id = $item ? $item->id() : 0;
		if (isset($_GET['filename'])) {
			$filename = $_GET['filename'];
			if (!$this->is_valid($filename)&&$filename!='none') return false;
			$filename = str_replace('..','',$filename);
			$path = CMS::temp_dir().'/'.$filename;
		}
		

		if ($action=='right'||$action=='left') {
			$oldpath = $path;
			if ($filename=='none'&&!empty($item_id)&&isset($item[$name])&&trim($item[$name])!='') $oldpath = $this->value_to_path($item[$name]);
			$code = $this->temp_code();
			$newfile = "file-$code-$name.".$this->extension($oldpath);
			$newpath = CMS::temp_dir().'/'.$newfile;
			if (!IO_FS::exists($oldpath)) return CMS::lang()->_common->file_not_found . ': ' . $oldpath;
			Events::call('admin.change');
			Core::load('CMS.Images');
		}

		if ($action=='right') {
			CMS_Images::Image($oldpath)->turn_right()->save($newpath);
			if(IO_FS::exists($path)) IO_FS::rm($path);
			return "!$newfile";
		}

		if ($action=='left') {
			CMS_Images::Image($oldpath)->turn_left()->save($newpath);
			if(IO_FS::exists($path)) IO_FS::rm($path);
			return "!$newfile";
		}

		if ($action=='temp') {
			if (!IO_FS::exists($path)) return false;
			Core::load('Net.HTTP');
			return Net_HTTP::Download($path, false);
		}
		if ($action=='delete') {
			//AJAX way
			/*if ($filename=='none'&&$item_id>0&&isset($item[$name])&&trim($item[$name])!='') {
				$path = $this->value_to_path($item[$name]);
				$item[$name] = '';
				$item->update();
			}*/
			if (IO_FS::exists($path)) IO_FS::rm($path);
			Events::call('admin.change');
			return 'ok';
		}
		if ($action=='info') {
			if ($filename=='none') return 'ok';
			$data['__item'] = $item;
			return $this->render($name, $data,'info-ajax.phtml',array(
				'image_path' => $path,
				'name' => $name,
				'image_url' => $c->field_action_url($name,'temp',$item,array('filename' => str_replace('/','',$filename))),
				'name' => $name,
			));
			
		}
		if ($action=='preview') {
			$size = 100;
			if (isset($data['admin_preview_size'])) $size = $data['admin_preview_size'];
			if (!IO_FS::exists($path)) return false;
			Core::load('CMS.Images');
			$image = CMS_Images::Image($path)->fit($size,$size);
			$image->out();
			die;
		}
		if ($action=='upload') {
			$r = $this->action_upload($name,$data,$action,$item);
			// $this->preset->preset_on_upload($this->container($name, $data, $item));
			return $r;
		}
		return false;
	}

	protected function upload_validate($name, $data, $file, $new) {
		$old = $file['tmp_name'];
		$realname = trim($file['name']);
		if (!$this->is_valid($realname)) return CMS::lang()->_common->ta_image_error;
		return parent::upload_validate($name, $data, $file, $new);
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
		$this->preset->upload_resize($name, $data, $new_file);
		return "!$filename";
	}

	public function assign_to_object($form,$item,$name,$data) {
		$item_id = $item->id();
		$value = trim($form[$name]);
		$old = '';
		if (!$item->is_phantom()&&isset($item[$name])) {
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

	public function admin_preview_path($name,$data,$item) {
		if (!isset($item[$name])) return false;
		$value = trim($item[$name]);
		if ($value=='') return false;
		$dir = $this->cache_dir($name,$data,$item);
		$size = 100;
		if (isset($data['admin_preview_size'])) $size = $data['admin_preview_size'];
		$path = $this->value_to_path($value);
		if (!IO_FS::exists($path)) return false;
		$ext = ''; if ($m = Core_Regexps::match_with_results('{\.([^\.]+)$}',$value)) $ext = trim($m[1]);
		$preview_path = "$dir/$name-admin-preview.$ext";
		$create = false;
		if (IO_FS::exists($preview_path)) {
			if (filemtime($path)>filemtime($preview_path)) $create = true;
		}

		else $create = true;

		if ($create) {
			CMS::mkdirs($dir);
			Core::load('CMS.Images');
			CMS_Images::Image($path)->fit($size,$size)->save($preview_path);
		}

		return $preview_path;
	}

	public function admin_preview_url($name,$data,$item) {
		return $this->value_to_url($this->admin_preview_path($name,$data,$item));
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
							CMS::chmod_file("$dir/$filename");
							IO_FS::rm($uploaded);
							$value = "$_dir/$filename";
						}
					}
				}
			}
		}

		$item[$name] = $value;
		if (!empty($value) && method_exists($item, 'clear_cache'))
			$item->clear_cache(false);
		$this->preset->preset_on_upload($this->container($name, $data, $item));
		return true;
	}

	public function uploaded_file_dir($name,$data,$item) {
		$id = $item->id();
		if (!$item->is_phantom()) {
			$dir = $item->homedir(isset($data['private'])&&$data['private']);
			if (!$dir) return false;
			if ($dir[0]!='.'&&$dir[0]!='/') $dir = "./$dir";
			return $dir;
		}
		return false;
	}

	public function cache_dir($name,$data,$item) {
		$id = $item->id();
		if (!$item->is_phantom()) {
			$dir = $item->cache_dir_path(isset($data['private'])&&$data['private']);
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

	public function copy_value($from, $to, $name, $data) {
		$value = trim($from[$name]);
		if ($value=='') return $this;
		$container = $this->container($name,$data,$to);
		$container->set($value);
		return $this;
	}

	public function container_class() {
		//FIXME: rename class to CMS_Fields_Types_Image_ValueContainer
		return 'CMS_Fields_Types_Image_Container';
	}

	protected function layout_preprocess($l, $name, $data) {
		$item = $this->get_item($name, $data);
		$l->use_scripts(CMS::stdfile_url('scripts/fields/image.js'));
		$l->use_scripts(CMS::stdfile_url('scripts/jquery/block.js'));
		$id = $this->url_class();
		$l->with('url_class', $id);
		if (!$item->is_phantom()) {
			$code = "; $(function() { $('.{$id}.field-$name').each(function() {
		 					TAO.fields.image.process($(this));
							}
			)});";
			$l->append_to('js', $code);
		}

		Templates_HTML::add_scripts_settings(array('fields' => array(
			$name => array(
				'confirm' => CMS::lang()->_common->ta_diconfirm
			)
		)));
		
		return parent::layout_preprocess($l, $name, $data);
	}



}

abstract class CMS_Fields_Types_Image_ModsCache extends CMS_Fields_ValueContainer {
	protected $mods = array();
	protected $cached = false;
	protected $extra_file = false;

	protected $lazy_parms = array('class' => 'lazy', 'settings' => array('effect' => 'fadeIn'));
	protected $lazy = false;

	public function __construct($name,$data,$item,$type) {
		parent::__construct($name,$data,$item,$type);
		$this->lazy_parms['0gif'] = CMS::stdfile_url('images/0.gif');
	}

	public function lazy($parms = array())
	{
		$this->lazy = true;
		$this->lazy_parms = array_merge($this->lazy_parms, $parms);
		$this->template()->root->use_script(CMS::stdfile_url('scripts/jquery/lazyload.js'));
		$this->template()->add_scripts_settings(array('lazyload' => $this->lazy_parms['settings']), true);
		return $this;
	}

	public function lazy_tagargs($args)
	{
		$args['class'] = (isset($args['class']) ? $args['class'] . ' ' : '') . $this->lazy_parms['class'];
		$args['data-original'] = $args['src'];
		$args['src'] = $this->lazy_parms['0gif'];
		return $args;
	}
	
	protected function transform_args($args,$action=false) {
		$out = array();
		$w = 0;
		$h = 0;
		$color = '#FFFFFF';
		if ($m = Core_Regexps::match_with_results('{^(\d+)x(\d+)$}i',trim($args[0]))) {
			$w = (int)$m[1];
			$h = (int)$m[2];
		}
		else {
			if (is_numeric($args[0]) && is_numeric($args[1])) {
				$w = (int)$args[0];
				$h = (int)$args[1];
			}
		}

		foreach($args as $arg) {
			$arg = trim($arg);
			if ($arg!=''&&$arg[0]=='#') $color = $arg;
		}

		if ($w>0) $out['width'] = $w;
		if ($h>0) $out['height'] = $h;
		$out['color'] = $color;
		if ($action) $out['action'] = $action;
		return $out;
	}

	public function fit() {
		$this->cached = false;
		$mod = $this->transform_args(func_get_args(),'fit');
		unset($mod['color']);
		if (isset($mod['width'])&&isset($mod['height'])) $this->mods[] = $mod;
		return $this;
	}

	public function resize() {
		$this->cached = false;
		$mod = $this->transform_args(func_get_args(),'resize');
		unset($mod['color']);
		if (isset($mod['width'])&&isset($mod['height'])) $this->mods[] = $mod;
		return $this;
	}

	public function crop() {
		$this->cached = false;
		$mod = $this->transform_args(func_get_args(),'crop');
		unset($mod['color']);
		if (isset($mod['width'])&&isset($mod['height'])) $this->mods[] = $mod;
		return $this;
	}

	public function margins() {
		$this->cached = false;
		$mod = $this->transform_args(func_get_args(),'margins');
		if (isset($mod['width'])&&isset($mod['height'])) $this->mods[] = $mod;
		return $this;
	}

	public function grayscale() {
		$this->cached = false;
		$this->mods[] = array('action' => 'grayscale');
		return $this;
	}

	public function watermark($image = false,$parms = false) {
		Core::load('CMS.Images');
		if (!$image) $image = CMS_Images::$default_watermark_image;
		if (!$parms) $parms = CMS_Images::$default_watermark_parms;
		$this->cached = false;
		$this->mods[] = array('action' => 'watermark','image' => $image,'parms' => $parms);
		return $this;
	}

	public function mods_reset() {
		$this->mods = array();
		$this->cached = false;
		return $this;
	}

	protected function mods_path() {
		$dir = $this->dir();
		$filename = '';
		$original = $this->path();
		$original_name = $original;
		if ($m = Core_Regexps::match_with_results('{/([^/]+)$}',$original)) $original_name = $m[1];
		$this->extra_file = false;
		foreach($this->mods as $mod) {
			if (isset($mod['action'])) {
				$action = $mod['action'];
				$filename .= $action;
				if (isset($mod['width'])&&isset($mod['height'])) $filename .= $mod['width'].'x'.$mod['height'];
				if (isset($mod['color'])) $filename .= strtolower(str_replace('#','',trim($mod['color'])));
				if (isset($mod['image'])) {
					$_s = $mod['image'];
					$this->extra_file = $_s;
					if (isset($mod['parms'])) {
						$_s .= serialize($mod['parms']);
					}
					$_s = preg_replace('{[^\d]+}','',md5($_s));
					$_s = substr($_s,0,8);
					$filename .= $_s;
				}

			}
		}

		$filename .= '-'.$original_name;
		return trim("$dir/$filename", '-');
	}

	protected function mods_is_remake($path) {
		$original = $this->path();
		$remake = false;

		if (IO_FS::exists($path)) {
			$fmt = filemtime($path);
			if (filemtime($original)>$fmt) {
				$remake = true;
			}

			else if ($this->extra_file) {
				if (filemtime($this->extra_file)>$fmt) {
					$remake = true;
				}
			}
		}

		else $remake = true;
		return $remake;
	}
	
	protected function mods_process($path) {
		$dir = $this->dir();
		$original = $this->path();
		if (!IO_FS::exists($original)) return $this;
		CMS::mkdirs($dir);
		Core::load('CMS.Images');
		$image = CMS_Images::Image($original);
		$image->modify($this->mods);
		$image->save($path);
	}

	protected function cache_mods() {
		if (count($this->mods)==0) return;
		$path = $this->mods_path();
		if ($this->mods_is_remake($path)) {
			$this->mods_process($path);
		}
		$this->cached = $path;
	}
	
	abstract public function dir();
	
	public function cached_path() {
		if (count($this->mods)==0) return $this->path();
		$this->cache_mods();
		return $this->value_to_path($this->cached);
	}
}

//FIXME: rename class to CMS_Fields_Types_Image_ValueContainer
class CMS_Fields_Types_Image_Container extends CMS_Fields_Types_Image_ModsCache {
	
	public function dir() {
		return $this->item->cache_dir_path(isset($this->data['private'])&&$this->data['private']);
	}

	public function preset($name) {
		return $this->type->get_preset()->preset($this, $name);
	}
	
	public function value() {
		$value = trim(parent::value());
		if ($value==''&&isset($this->data['default_image'])) return $this->data['default_image'];
		return $value;
	}
	
	protected function cached_url() {
		if (count($this->mods)==0) return parent::url();
		$this->cache_mods();
		return $this->value_to_url($this->cached);
	}
	
	public function url() {
		return $this->cached_url();
	}

	public function render($args=array()) {
		$url = $this->cached_url();
		$path = $this->cached_path();
		if (!IO_FS::exists($path)) return '';
		if (!isset($args['width']) && !isset($args['height'])) {
			$sz = getImageSize($path);
			if (!isset($args['width'])) $args['width'] = $sz[0];
			if (!isset($args['height'])) $args['height'] = $sz[1];
		}
		$args['src'] = $url;
		if ($this->lazy) {
			$args = $this->lazy_tagargs($args);
		}
		return $this->template()->tags->tag('img' ,$args);
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


class CMS_Fields_Types_Image_Preset {

	public static $config = 'gallery';

	static public function parse_mods($value) {
		return CMS_Images::parse_modifiers($value);
	}

	public static function apply_mods($object, $mods)
	{
		foreach ($mods as $method => $data) {
			$action = isset($data['action']) ? $data['action'] : $method;
			unset($data['action']);
			$args = $data;
			call_user_func_array(array($object, $action),  $args);
		}
	}

	static public function upload_resize($name, $data, $new_file) {
		$upload_mods = self::value($data, 'upload_mods');
		if (!empty($upload_mods)) {
			$mods = self::parse_mods($upload_mods);
			if (!empty($mods)) {
				Core::load('CMS.Images');
				$im = CMS_Images::Image($new_file);
				self::apply_mods($im, $mods);
				$im->save($new_file);
				IO_FS::File($new_file)->set_permission();
			}
		}	
	}

	static public function preset($container, $pname) {
		$mods = array();
		$presets = self::value($container->data, 'presets');
		if (isset($presets[$pname])) {
			$mods = self::parse_mods($presets[$pname]);
		} else {
			$mods = self::parse_mods($pname);
		}
		self::apply_mods($container, $mods);
		return $container;
	}

	static public function preset_on_upload($container) {
		$presets = self::value($container->data, 'presets');
		if (!empty($presets)) {
			foreach ($presets as $p => $action) {
				$container->mods_reset();
				self::preset($container, $p)->cached_path();
			}
		}
	}

	static public function value($data, $property)
	{
		$value = array();
		$config = Config::all();
		$name = self::$config;
		$to_merge = array();
		if (isset($config->$name) && isset($config->$name->$property)) {
			$to_merge[] = $config->$name->$property;
		}
		if (isset($data[$property])) {
			$to_merge[] = $data[$property];
		}
		foreach ($to_merge as $tmp) {
			if (is_array($tmp)) {
				$value = array_merge($value, $tmp);
			} else {
				$value = $tmp;
			}
		}
		return $value;
	}



}
