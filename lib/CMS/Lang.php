<?php

/**
 * @package CMS\Lang
 */
class CMS_Lang implements Core_ModuleInterface
{

	const MODULE = 'CMS.Lang';
	const VERSION = '0.0.0';

	static $modules = array();
	static $components_classes = array();
	static $langs = array();
	static $file_messages = array();

	protected $module = false;

	static function initialize($config = array())
	{
		foreach ($config as $key => $value) {
			if ($key == 'langs') {
				self::$$key = $value;
			} else {
				$module = $value;
				if (is_array($value)) {
					self::$langs[$key] = $value;
					$module = isset($value['module']) ? $value['module'] : false;
				}
				if ($module) {
					self::$modules[$key] = $module;
				}
			}
		}
	}

	static function reset()
	{
		self::$components_classes = array();
	}

	public function langs()
	{
		return self::$langs;
	}

	static function lang_select_items($with_all = true)
	{
		$out = array();
		if ($with_all) {
			$out['all'] = CMS::lang()->_common->all_languages;
		}
		foreach (self::langs() as $lang => $data) {
			if (is_string($data)) {
				$data = array('caption' => $data);
			}
			$out[$lang] = $data['caption'];
		}
		return $out;
	}

	public function init_module($site)
	{
		$module_name = isset(self::$modules[$site]) ? trim(self::$modules[$site]) : '';
		if ($module_name != '') {
			Core::load($module_name);
			$class = str_replace('.', '_', $module_name);
			$this->module = new $class();
		}
	}

	public function register_component($name, $lang_object) 
	{
		self::$components_classes[$name] = $lang_object;
	}

	protected function component_class($name)
	{
		$name = trim($name);
		$name = strtolower($name);
		if (isset(self::$components_classes[$name])) {
			return self::$components_classes[$name];
		}
		if (CMS::component_exists($name) || $name[0] == '_') {
			$lang = CMS::site_lang();
			$lang = ucfirst($lang);

			if ($m = Core_Regexps::match_with_results('{^_(.+)$}', $name)) {
				$name = trim($m[1]);
				$module = 'CMS.Lang.' . ucfirst($name) . '.' . $lang;
			} else {
				$module = CMS::$component_module_prefix[$name] . ".Lang.$lang";
			}

			$class = str_replace('.', '_', $module);
			try {
				@Core::load($module);
			} catch (Core_ModuleNotFoundException $e) {
				return false;
			}

			if (!class_exists($class)) {
				return false;
			}
			$object = new $class;
			self::$components_classes[$name] = $object;
			return $object;
		}
		return false;
	}
	
	protected function components_dir()
	{
		static $components_dir = false;
		if ($components_dir) {
			return $components_dir;
		}
		$droot = getcwd();
		$cdir = CMS::component_dir('');
		chdir($cdir);
		$components_dir = getcwd();
		chdir($droot);
		return $components_dir;
	}
	
	protected function add_lang_file_if_exists(&$files,$path)
	{
		if (is_file($path)) {
			$files[] = $path;
		}
	}
	
	protected function lang_messages_for($file,$lang)
	{
		$dir = CMS::site_dir();
		if (strpos($file,$dir)===0) {
			$rfile = substr($file,strlen($dir));
		}
		
		else {
			return false;
		}
		$files = array();
		
		if (strpos($rfile,'tao/')===0) {
			$rfile = substr($rfile,4);
			$this->add_lang_file_if_exists($files,CMS::tao_path("lang/{$rfile}/{$lang}.php"));
		}
		
		$components_dir = $this->components_dir();
		if (strpos($file,$components_dir)===0) {
			$cfile = substr($file,strlen($components_dir));
			if ($m = Core_Regexps::match_with_results('{^/([^/]+)/(.+)$}',$cfile)) {
				$component = $m[1];
				$cfile = $m[2];
				$this->add_lang_file_if_exists($files,CMS::component_dir($component,"app/lang/{$lang}.php"));
				$this->add_lang_file_if_exists($files,CMS::component_dir($component,"lang/{$lang}.php"));
				$this->add_lang_file_if_exists($files,CMS::component_dir($component,"app/lang/{$cfile}/{$lang}.php"));
				$this->add_lang_file_if_exists($files,CMS::component_dir($component,"lang/{$cfile}/{$lang}.php"));
			}
		}
		
		$this->add_lang_file_if_exists($files,CMS::app_path("lang/{$lang}.php"));
		$this->add_lang_file_if_exists($files,CMS::app_path("lang/{$rfile}/{$lang}.php"));
		
		if (count($files)==0) {
			return false;
		}
		
		$messages = array();
		foreach($files as $file) {
			$fm = include($file);
			if (is_array($fm)) {
				$messages = array_merge($messages,$fm);
			}
		}
		
		if (count($messages)==0) {
			return false;
		}
		
		return $messages;
	}
	
	public function for_file($file,$code,$force=false)
	{
		$lang = $force? $force : CMS::site_lang();
		$lc = "{$lang}:{$file}";
		if (!isset(self::$file_messages[$lc])) {
			$messages = $this->lang_messages_for($file,$lang);
			self::$file_messages[$lc] = $messages;
		}
		$messages = self::$file_messages[$lc];
		if ($messages&&isset($messages[$code])) {
			return $messages[$code];
		}
		return $code;
	}
	
	public function transform($s, $force = false)
	{
		$langs = $this->split($s);
		if (is_string($langs)) {
			return $langs;
		}
		$lang = CMS::site_lang();
		$rs = '';
		if ($force) {
			if (isset($langs[$force])) {
				return $langs[$force];
			}
			return '';
		}
		if (isset($langs[$lang])) {
			$rs = trim($langs[$lang]);
		}
		if ($rs == '' && isset($langs['default'])) {
			$rs = trim($langs['default']);
		}
		if ($rs == '' && isset($langs[CMS::$default_lang])) {
			$rs = trim($langs[CMS::$default_lang]);
		}
		if ($rs == '') {
			$rs = $this->first_filled($langs);
		}
		return $rs;
	}

	public function first_filled(&$array)
	{
		foreach ($array as $v)
			if (trim($v) != '') {
				return trim($v);
			}
		return '';
	}

	public function lang_split($s)
	{
		$values = $this->split($s);
		$langs = $this->langs();
		$rc = array();
		foreach ($langs as $lang => $ldata) {
			if (is_string($values)) {
				if ($lang == CMS::$default_lang) {
					$rc[$lang] = $values;
				} else {
					$rc[$lang] = '';
				}
			} else {
				if (is_array($values)) {
					if (isset($values[$lang])) {
						$rc[$lang] = $values[$lang];
					} else {
						$rc[$lang] = '';
					}

				}
			}
		}
		return $rc;
	}

	public function split($s, $lang = 'default')
	{
		$s = trim($s);
		if ($s == '') {
			return $s;
		}
		if (strpos($s, '%LANG{') === false) {
			return $s;
		}
		if ($m = Core_Regexps::match_with_results('/^(.*?)%LANG\{([a-z]+)\}(.*)$/ism', $s)) {
			$langs = array();
			$langs[$lang] = trim($m[1]);
			$next = trim($m[2]);
			$_langs = $this->split($m[3], $next);
			if (is_string($_langs)) {
				$langs[$next] = $_langs;
			} else {
				$langs = array_merge($langs, $_langs);
			}
			return $langs;
		}
		return $s;
	}

	public function __get($name)
	{
		if ($module = $this->component_class($name)) {
			return $module;
		}
		if (!$this->module) {
			return false;
		}
		return $this->module->$name;
	}

	public function __call($name, $args)
	{
		if (!$this->module) {
			return false;
		}
		$method = new ReflectionMethod($this->module, $name);
		return $method->invokeArgs($this->module, $args);
	}

}

