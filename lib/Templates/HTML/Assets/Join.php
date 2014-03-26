<?php

class Templates_HTML_Assets_Join implements Core_ConfigurableModuleInterface 
{
	const VERSION = '0.1.0';

	static protected $options = array(
		'dir' => 'joins',
		'minify' => true
	);

	static public function initialize(array $options = array())
	{
		foreach(self::$options as $k => $v){
			self::$options[$k] = str_replace('%files%', Core::option('files_name'),$v);
		}
		self::options($options);
	}
	
	static public function options(array $options = array())
	{
		if (count($options)) Core_Arrays::update(self::$options, $options);
		return self::$options;
	}
	
	static public function option($name, $value = null)
	{
		$prev = isset(self::$options[$name]) ? self::$options[$name] : null;
		if ($value !== null) self::options(array($name => $value));
		return $prev;
	}

	public static function Stapler($type)
	{
		return Core::make('Templates.HTML.Assets.Join.Stapler', $type);
	}
}

class Templates_HTML_Assets_Join_Stapler
{
	protected $type;
	protected $active = true;
	protected $files = array();
	protected $group_files = array();
	protected $groups = array();

	public function __construct($type)
	{
		$this->type = $type;
	}

	public function disable()
	{
		$this->active = false;
		return $this;
	}

	public function enable()
	{
		$this->active = true;
		return $this;
	}

	public function default_fasten_data()
	{
		return array(
			'weight' => 0,
			'type' => 'app',
			'add_timestamp' => false,
			'minify' => Templates_HTML_Assets_Join::option('minify')
		);
	}

	public function fasten_group($name, $files = array(), $data = array())
	{
		$this->groups[$name] = array_merge($this->default_fasten_data(), $data);
		$this->group_files[$name] = $files;
		return $this->groups[$name];
	}

	public function find_group($file)
	{
		foreach ($this->group_files as $name => $files) {
			if (in_array($file, $files)) {
				return $name;
			}
		}
		return false;
	}

	public function find_file_in_group($name, $file)
	{
		return array_search($file, $this->group_files[$name]);
	}

	public function fasten($file, $data)
	{
		if ($gname = $this->find_group($data['original_name'])) {
			$index = $this->find_file_in_group($gname, $data['original_name']);
			$this->group_files[$gname][$index] = $file;
			return true;
		}
		if ($this->active && isset($data['join']) && $data['join'] && !Templates_HTML::is_url($file)) {
			$this->files[] = $file;
			return true;
		}
		return false;
	}

	protected function files_hash($files)
	{
		$s = '';
		foreach($files as $file) {
			$mt = @filemtime('.' . $file);
			if ($mt) {
				$s .= $file;
				$s .= $mt;
			}
		}
		return md5($s);
	}

	protected function dir($file = '')
	{
		$dir = Templates_HTML_Assets_Join::option('dir');
		$ext = pathinfo($file, PATHINFO_EXTENSION);
		$paths = Templates_HTML::option('paths');
		if (!empty($ext) && isset($paths[$ext])) {
			$dir = '/' . trim($paths[$ext] . '/' . trim($dir, PATH_SEPARATOR), '/');
		}
		if (!is_dir('.' . $dir)) {
			IO_FS::mkdir('.' . $dir, IO_FS::option('dir_mod'), true);
		}
		return "$dir/$file";
	}

	public function flash()
	{
		$result = array();
		$result['default'] = $this->flash_files($this->files);
		foreach ($this->groups as $name => $data) {
			$result[$name] = $this->flash_files($this->group_files[$name], $name, $data);
		}
		return $result;
	}

	public function check_file_for_update($file, $files = array())
	{
		return !is_file($file);
	}

	public function flash_files($files, $name = null, $data = array())
	{
		if (empty($files)) {
			return null;
		}
		$hash = $this->files_hash($files);
		if ($name) {
			$name .= "__$hash";
		} else {
			$name = $hash;
		}
		$write = false;
		$filename = $this->dir("{$name}.{$this->type}");
		$filepath = '.' . $filename;
		$content = '';
		if ($this->check_file_for_update($filepath, $files)) {
			$write = true;
			foreach($files as $file) {
				$path = '.' . $file;
				$content .= "\n\n";
				$content .= file_exists($path) ?  file_get_contents($path) : '';
				if ($this->type == 'js') {
					$content .= ';';
				}
			}
		}
		if (empty($data)) {
			$data = $this->default_fasten_data();
		}
		$data = array_merge($data, array(
			'path' => $filepath,
			'write' => $write,
			'content' => $content
		));
		return array($filename, $data);
	}


}