<?php
/**
 * @package CMS\Vars2\Storage\FS
 */


class CMS_Vars2_Storage_FS extends CMS_Vars_Storage implements Core_ModuleInterface {
	const VERSION = '0.0.0';

	static $dir = false;

	static function initialize($config=array()) {
		foreach($config as $key => $value) self::$$key = $value;
		if (!self::$dir) self::$dir = '../'.Core::option('files_name').'/vars';
	}

	public function exists($name) {
		$path = $this->path($name);
		return IO_FS::exists($path);
	}

	public function load($name) {
		if (!$this->exists($name)) {
			return false;
		}
		$file = $this->path($name);
		if (is_dir($file)) {
			$info = $this->load_dir_info($name);
			$info['_type'] = 'dir';
		} else {
			$info = $this->load_info($file);
		}
		$info['_name'] = $name;
		$var = CMS::vars()->entity($info['_type'],$info);
		return $var;
	}

	public function delete($var) {
		if (is_string($var)) {
			$var = $this->load($var);
		}
		$path = $this->path($var->name());
		if ($var->is_dir()) {
			CMS::rmdir($path);
		} else {
			IO_FS::rm($path);
		}
	}

	public function save($var) {
		if (!isset($var['_name'])) return;
		$name = $var['_name'];
		if ($var->is_dir()) {
			return $this->update_dir($name,$var->info());
		}
		$path = $this->path($name);
		$info = $var->serialize_info($var->info());
		$this->save_info($path,$info);
	}

	public function create_dir($name,$attrs) {
		$this->update_dir($name,$attrs);
	}

	public function update_dir($name,$attrs) {
		$path = $this->path($name);
		if (!IO_FS::exists($path)) CMS::mkdirs($path);
		$info = $this->load_dir_info($name);
		if (is_string($attrs)) $attrs = array('_title' => $attrs);
		foreach($info as $field => $value) {
			if (!isset($attrs[$field])) $attrs[$field] = $value;
		}
		foreach(CMS::vars()->config_fields() as $field => $data) {
			if (!isset($attrs[$field])) {
				$attrs[$field] = isset($data['default'])?$data['default']:'';
				if ($attrs[$field]=='{name}') $attrs[$field] = $name;

			}
		}
		$this->save_dir_info($name,$attrs);
	}

	public function load_dir_info($name) {
		$path = $this->path($name);
		if (!IO_FS::exists($path)) return false;
		if (!IO_FS::is_dir($path)) return false;
		$file = "$path/.info";
		$info = $this->load_info($file);
		$info['_name'] = $name;
		return $info;
	}

	public function save_dir_info($name,$info) {
		$path = $this->path($name);
		if (!IO_FS::exists($path)) return false;
		if (!IO_FS::is_dir($path)) return false;
		$file = "$path/.info";
		$this->save_info($file,$info);
	}

	public function save_info($file,$info) {
		$s = '';
		foreach($info as $field => $value) {
			if (is_array($value)) {
				$value = serialize($value);
			}

			if ($value==trim($value)&&Core_Regexps::match('{^[a-zа-я0-9\. ]*$}i',$value)) {
				$s .= "$field=$value\n";
			}

			else {
				$v = base64_encode($value);
				$s .= "$field: $v\n";
			}
		}
		file_put_contents($file,$s);
		CMS::chmod_file($file);
	}

	protected function load_info($file) {
		$info = array();
		if (IO_FS::exists($file)) {
			foreach(file($file) as $line) {
				$line = trim($line);
				if ($m = Core_Regexps::match_with_results('{^([a-z0-9_]+)=(.*)$}i',$line)) {
					$name = $m[1];
					$value = trim($m[2]);
					$info[$name] = $value;
				}
				else if ($m = Core_Regexps::match_with_results('{^([a-z0-9_]+):(.*)$}i',$line)) {
					$name = $m[1];
					$value = base64_decode(trim($m[2]));
					$info[$name] = $value;
				}
			}
		}
		return $info;
	}

	public function get_all_vars($name=false) {
		if (!is_dir($this->path($name))) return array();
		$prefix = $name? "$name." : '';
		return $this->branch($this->path($name),$prefix);
	}

	protected function branch($dir_path,$prefix='') {
		$vars = array();
		$dirs = array();
		foreach(IO_FS::Dir($dir_path) as $entry) {
			$filename = $entry->name;
			if (Core_Regexps::match('{^[a-z0-9_-]+$}i',$filename)) {
				$path = $entry->path;
				$name = $prefix.$filename;
				if (is_dir($path)) {
					if (IO_FS::exists("$path/.info")) {
						$info = $this->load_info("$path/.info");
						if ($info) {
							$var = CMS::vars()->entity('dir',$info);
							$var->vars = $this->branch($path,"{$name}.");
							$dirs[$name] = $var;
						}
					}
				} else {
					$var = $this->load($name);
					if ($var) {
						$vars[$name] = $var;
					}
				}
			}
		}
		ksort($vars);
		ksort($dirs);
		$out = array();
		$out += $vars;
		$out += $dirs;
		return $out;
	}

	protected function path($name=false) {
		$path = self::$dir;
		$site = CMS::admin()? CMS_Admin::site() : CMS::site();
		if ($site!='__') $path .= "/$site";
		if ($name) {
			$name = trim(str_replace('.','/',$name));
			$path .= "/$name";
		}
		return $path;
	}

}