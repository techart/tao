<?php
/**
 * @package CMS\Fields\Types\Youtube
 */

Core::load('CMS.Fields.Types.Image');

class CMS_Fields_Types_Youtube extends CMS_Fields_AbstractField implements Core_ModuleInterface
{
	const VERSION = '0.0.0';

	public function assign_to_object($form, $object, $name, $data)
	{
		$object[$name] = $this->is_code($form[$name]) ? $form[$name] : $this->parse_youtube_link($form[$name]);
	}

	protected function parse_youtube_link($link)
	{
		$patterns = array(
			'|youtube\.com/watch[#\?].*?v=([0-9a-zA-Z_\-]+)|i',
			'|youtube\.com/embed/([0-9a-zA-Z_\-]+)|i',
			'|youtube\.com/v/([0-9a-zA-Z_\-]+)|i',
			'|youtube\.com/\?v=([0-9a-zA-Z_\-]+)|i',
			'|youtu.be/([0-9a-zA-Z_\-]+)|i',
		);
		foreach ($patterns as $pattern) {
			preg_match($pattern, $link, $m);
			if ($m[1]) {
				return $m[1];
			}
		}
		return '';
	}

	protected function is_code($value)
	{
		return preg_match("|^[0-9a-zA-Z_\-]+$|", $value) > 0;
	}

}

class CMS_Fields_Types_Youtube_ValueContainer extends CMS_Fields_Types_Image_ModsCache
{

	protected $parms = array();
	protected $player_parms = array();
	protected $player_events = array();

	protected $mode = false;

	public function render()
	{
		if (is_null($this->value())) {
			return '';
		}

		if ($this->mode_template()) {
			$this->before_render();

			return $this->template()
				->spawn($this->type->template($this->data, $this->mode_template()))
				->with($this->template_vars())->render();
		}
		return $this->iframe()->render();
	}

	public function initialize($parms, $player_parms, $player_events)
	{
		$this->parms = $parms;
		$this->player_parms = $player_parms;
		$this->player_events = $player_events;
		return $this;
	}

	public function iframe()
	{
		$container = new CMS_Fields_Types_Youtube_IframeValueContainer($this->name, $this->data, $this->item, $this->type);
		return $container->initialize($this->parms, $this->player_parms, $this->player_events);
	}

	public function object()
	{
		$container = new CMS_Fields_Types_Youtube_ObjectValueContainer($this->name, $this->data, $this->item, $this->type);
		return $container->initialize($this->parms, $this->player_parms, $this->player_events);
	}

	public function preview()
	{
		$container = new CMS_Fields_Types_Youtube_PreviewValueContainer($this->name, $this->data, $this->item, $this->type);
		return $container->initialize($this->parms, $this->player_parms, $this->player_events);
	}

	public function size()
	{
		$parm = reset(func_get_args());
		return $this->resize($parm);
	}

	public function resize()
	{
		$sizes = $this->transform_args(func_get_args(), 'resize');
		$this->parms = array_merge($this->parms, $sizes);
		$parm = reset(func_get_args());
		return parent::resize($parm);
	}

	public function events($events)
	{
		foreach ($events as $event => $callback)
			$this->set_event($event, $callback);
		return $this;
	}

	public function event($event, $callback)
	{
		$this->set_event($event, $callback);
		return $this;
	}

	public function __call($name, $args)
	{
		$ret = false;
		switch ($name) {
			case 'rel':
			case 'autoplay':
			case 'loop':
			case 'playerapiid':
			case 'disablekb':
			case 'egm':
			case 'border':
			case 'color1':
			case 'color2':
			case 'start':
			case 'fs':
			case 'hd':
			case 'showsearch':
			case 'showinfo':
			case 'iv_load_policy':
			case 'cc_load_policy':
				$ret = $this->set_player_parm($name, $args[0]);
				break;
			default:
				$ret = call_user_func_array(array($this, $name), $args);
				break;
		}
		return $ret;
	}

	public function enablejsapi()
	{
		$this->set_parm('enablejsapi', 1);
		$this->set_player_parm('enablejsapi', 1);
		return $this;
	}

	public function enableiframeapi()
	{
		$this->set_parm('enableiframeapi', 1);
		return $this;
	}

	public function admin_preview_url($data)
	{
		return $this->path_to_url($this->admin_preview_path($data));
	}

	public function dir()
	{
		return $this->item->cache_dir_path(isset($this->data['private']) && $this->data['private']);
	}

	public function url()
	{
		return $this->cached_url();
	}

	public function path()
	{
		return $this->get_preview_dir() . '/' . $this->get_preview_file_name();
	}

	protected function mode_template()
	{
		return $this->mode ? 'render/' . $this->mode : false;
	}

	protected function parms()
	{
		return $this->parms;
	}

	protected function get_parm($parm = false)
	{
		$default_parms = $this->default_parms();
		return $this->parms[$parm] ? $this->parms[$parm] : $default_parms[$parm];
	}

	protected function set_parm($parm, $value)
	{
		if ($value === null) {
			unset($this->parms[$parm]);
		}
		$this->parms[$parm] = $value;
	}

	protected function get_player_parm($parm = false)
	{
		return $this->player_parms[$parm];
	}

	protected function set_player_parm($parm, $value)
	{
		if ($value === null) {
			unset($this->player_parms[$parm]);
		}
		$this->player_parms[$parm] = $value;
		return $this;
	}

	protected function get_player_parms_str()
	{
		$vars = '';
		foreach ($this->player_parms as $var_name => $var_value)
			$vars[] = $var_name . "=" . $var_value;
		return $vars ? '?' . implode('&', $vars) : $vars;
	}

	protected function get_events()
	{
		return $this->player_events;
	}

	protected function get_event($event = false)
	{
		return $this->player_events[$event];
	}

	protected function set_event($event, $callback)
	{
		if ($callback === null) {
			unset($this->player_events[$event]);
		}
		$this->player_events[$event] = $callback;
	}

	protected function create_parms_array($parms)
	{
		foreach ($parms as $parm) {
			if (!is_null($this->get_parm($parm))) {
				$ret[$parm] = $this->get_parm($parm);
			}
		}
		return $ret;
	}

	protected function admin_preview_path($data)
	{
		//if (!isset($this->value())) return false;
		$value = trim($this->value());
		if ($value == '') {
			return false;
		}
		$dir = $this->cache_dir($name, $data, $item);
		$size = 200;
		if (isset($data['admin_preview_size'])) {
			$size = $data['admin_preview_size'];
		}
		$url = $this->get_preview_url();
		$path = $this->original_preview_path();
		if (!IO_FS::exists($path)) {
			return false;
		}
		$preview_path = "$dir/{$this->name}-admin-preview.jpg";
		$create = false;
		if (IO_FS::exists($preview_path)) {
			if (filemtime($path) > filemtime($preview_path)) {
				$create = true;
			}
		} else {
			$create = true;
		}

		if ($create) {
			CMS::mkdirs($dir);
			Core::load('CMS.Images');
			CMS_Images::Image($path)->fit($size, $size)->save($preview_path);
		}

		return $preview_path;
	}

	protected function cache_dir($data)
	{
		$id = $this->item->id();
		if ($id > 0) {
			$dir = $this->item->cache_dir_path($item, isset($data['private']) && $data['private']);
			if (!$dir) {
				return false;
			}
			if ($dir[0] != '.' && $dir[0] != '/') {
				$dir = "./$dir";
			}
			return $dir;
		}
		return false;
	}

	protected function uploaded_file_dir()
	{
		$id = $this->item->id();
		if ($id > 0) {
			$dir = $this->item->homedir(isset($this->data['private']) && $this->data['private']);
			if (!$dir) {
				return false;
			}
			if ($dir[0] != '.' && $dir[0] != '/') {
				$dir = "./$dir";
			}
			return $dir;
		}
		return false;
	}

	protected function uploaded_file_url($filename)
	{
		return $this->path_to_url($this->uploaded_file_dir() . '/' . $filename);
	}

	protected function path_to_url($path)
	{
		return '/' . preg_replace('{^\./}', '', $path);
	}

	protected function get_preview_dir()
	{
		return $this->uploaded_file_dir();
		//return $this->preview_location;
	}

	protected function get_preview_url()
	{
		if (!IO_FS::exists($this->path())) {
			$this->cached_preview();
		}

		return $this->cached_url();
	}

	protected function cached_url()
	{
		if (count($this->mods) == 0) {
			return $this->original_preview_url();
		}
		$this->cache_mods();
		return $this->path_to_url($this->cached);
	}

	protected function original_preview_url()
	{
		return $this->uploaded_file_url($this->get_preview_file_name());
	}

	protected function original_preview_path()
	{
		return $this->uploaded_file_dir() . '/' . $this->get_preview_file_name();
	}

	protected function get_preview_file_name()
	{
		return $this->name . '-' . $this->item->id . '-' . $this->value() . '.jpg';
	}

	protected function get_embed_video_url()
	{
		return 'http://www.youtube.com/embed/' . $this->value() . $this->get_player_parms_str();
	}

	protected function get_video_url()
	{
		return 'http://www.youtube.com/v/' . $this->value() . $this->get_player_parms_str();
	}

	protected function get_youtube_preview_url()
	{
		return 'http://img.youtube.com/vi/' . $this->value() . '/0.jpg';
	}

	protected function get_preview_src_from_youtube()
	{
		return Net_HTTP::Agent()->send(Net_HTTP::Request($this->get_youtube_preview_url()))->body;
	}

	protected function cached_preview()
	{
		if (!IO_FS::exists($this->get_preview_dir())) {
			CMS::mkdirs($this->get_preview_dir());
		}
		$file = IO_FS::File($this->path());
		$file->update($this->get_preview_src_from_youtube());
		$file->close();
	}

	protected function before_render()
	{
		if (!$this->get_player_parm('enablejsapi') && !$this->get_player_parm('enableiframeapi') && $this->get_events()) {
			$this->set_player_parm('enablejsapi', 1);
		}
		if (($this->get_player_parm('enablejsapi') || $this->get_player_parm('enableiframeapi')) && !$this->get_player_parm('playerapiid')) {
			$this->set_player_parm('playerapiid', $this->get_api_player_id());
		}
		$this->set_src();
	}

	protected function get_api_player_id()
	{
		return $this->get_player_parm('playerapiid') ? $this->get_player_parm('playerapiid') : 'youtube_player_' . time();
	}

	protected function set_src()
	{
		return false;
	}

	protected function default_parms()
	{
		return false;
	}

}

class CMS_Fields_Types_Youtube_IframeValueContainer extends CMS_Fields_Types_Youtube_ValueContainer
{
	protected $mode = 'iframe';

	protected function default_parms()
	{
		return array('frameborder' => '0', 'allowfullscreen' => 'true', 'width' => 640, 'height' => 390);
	}

	protected function iframe_tag_attrs()
	{
		return array('src', 'width', 'height', 'frameborder', 'allowfullscreen');
	}

	protected function before_render()
	{
		parent::before_render();
		if ($this->get_parm('enablejsapi')) {
			$this->set_parm('enableiframeapi', 1);
		}
	}

	protected function template_vars()
	{
		$parms_for_render = array(
			'attrs' => $this->create_parms_array($this->iframe_tag_attrs(), $this->parms),
			'parms' => $this->parms(),
		);
		if ($this->get_parm('enableiframeapi') || $this->get_player_parm('enablejsapi')) {
			$parms_for_render['player_parms'] = $this->get_player_parms();
			$parms_for_render['player_api_id'] = $this->get_api_player_id();
		}
		if ($this->get_player_parm('enablejsapi')) {
			$parms_for_render['attrs']['id'] = $this->get_api_player_id();
		}

		return $parms_for_render;
	}

	protected function get_player_parms()
	{
		return array(
			'width' => $this->get_parm('width'),
			'height' => $this->get_parm('height'),
			'videoId' => $this->value,
			'playerVars' => $this->get_player_parms(),
			'events' => $this->get_events()
		);
	}

	protected function set_src()
	{
		$this->set_parm('src', $this->get_embed_video_url());
	}

}

class CMS_Fields_Types_Youtube_ObjectValueContainer extends CMS_Fields_Types_Youtube_ValueContainer
{
	protected $mode = 'object';

	protected function default_parms()
	{
		return array('frameborder' => '0', 'allowfullscreen' => 'true', 'allowscriptaccess' => 'always', 'type' => 'application/x-shockwave-flash');
	}

	protected function object_tag_attrs()
	{
		return array('width', 'height', 'data');
	}

	protected function object_params_tags()
	{
		return array('movie', 'allowfullscreen', 'allowscriptaccess');
	}

	protected function object_embeg_tag_attrs()
	{
		return array('src', 'type', 'width', 'height', 'allowscriptaccess', 'allowfullscreen');
	}

	protected function template_vars()
	{
		$parms_for_render = array(
			'object_attrs' => $this->create_parms_array($this->object_tag_attrs()),
			'object_params_tags' => $this->create_parms_array($this->object_params_tags()),
			'embed_attrs' => $this->create_parms_array($this->object_embeg_tag_attrs()),
			'events' => $this->get_events()
		);
		if ($this->get_player_parm('enablejsapi')) {
			$parms_for_render['player_api_id'] = $this->get_api_player_id();
			$parms_for_render['object_attrs']['id'] = $this->get_api_player_id();
		}
		return $parms_for_render;
	}

	protected function set_src()
	{
		$this->set_parm('src', $this->get_video_url($value));
		$this->set_parm('movie', $this->get_video_url($value));
		$this->set_parm('data', $this->get_video_url($value));
	}
}

class CMS_Fields_Types_Youtube_PreviewValueContainer extends CMS_Fields_Types_Youtube_ValueContainer
{
	protected $mode = 'preview';

	protected function preview_attrs()
	{
		return array('src', 'width', 'height');
	}

	protected function template_vars()
	{
		return array(
			'parms' => $this->create_parms_array($this->preview_attrs()),
		);
	}

	protected function set_src()
	{
		$this->set_parm('src', $this->get_preview_url());
	}

	protected function before_render()
	{
		parent::before_render();

		$path = $this->cached_path();
		if (!IO_FS::exists($path)) {
			return '';
		}
		if ($this->get_parm('width') === null || $this->get_parm('height') === null) {
			$sz = getImageSize($path);
			if ($this->get_parm('width') === null) {
				$this->set_parm('width', $sz[0]);
			}
			if ($this->get_parm('height') === null) {
				$this->set_parm('height', $sz[1]);
			}
		}
	}

}