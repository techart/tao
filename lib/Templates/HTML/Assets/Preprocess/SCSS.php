<?php

Core::load('WS');

class Templates_HTML_Assets_Preprocess_SCSS implements Core_ConfigurableModuleInterface, Templates_HTML_Assets_Preprocess_PreprocessorInterface
{
	const VERSION = '0.1.0';

	protected static $options = array(
		'scss_class' => 'scssc',
		'scss_cache_dir' => '../cache/scss',
		'scss_output_dir' => 'styles/scss',
		'scss_php_dir' => '../vendor/scssphp',
		'import_paths' => array(),
		'formatting' => 'scss_formatter_nested',
	);

	public static function initialize(array $options = array())
	{
		self::options($options);
	}

	public static function option($name, $value = null)
	{
		if (is_null($value)) {
			return self::$options[$name];
		}
		return self::$options[$name] = $value;
	}

	public static function options(array $options = array())
	{
		self::$options = array_merge(self::$options, $options);
	}

	public static function server($dir, $out = null, $scss = null)
	{
		Core::load('Templates.HTML.Assets.Preprocess.SCSS.Server');
		return new Templates_HTML_Assets_Preprocess_SCSS_Server($dir, $out, $scss);
	}

	public static function instance()
	{
		$config = (array)WS::env()->config->scss;
		if (!empty($config)) {
			self::options($config);
		}
		return Core::make('Templates.HTML.Assets.Preprocess.SCSS');
	}

	protected $cache_dir;
	protected $output;
	protected $server;
	protected $loaded = false;
	protected $scss = null;

	public function __construct($output = null, $cache_dir = null, $scss = null)
	{
		$this->output = is_null($output) ? self::option('scss_output_dir') : $output;
		$this->cache_dir = is_null($cache_dir) ? self::option('scss_cache_dir') : $cache_dir;
		$this->scss = $scss;
	}

	protected function build_scss($scss = null)
	{
		if (!is_null($scss)) {
			return $scss;
		}
		$class = self::option('scss_class');
		$instance = Core::make($class);
		foreach (self::option('import_paths') as $key => $value) {
			$instance->addImportPath($value);
		}
		$instance->setFormatter(self::option('formatting'));
		return $this->scssc = $instance;
	}

	protected function setup()
	{
		IO_FS::mkdir($this->output);
		IO_FS::mkdir($this->cache_dir);
		$this->build_scss($this->scssc);
		$this->server = self::server('.', $this->cache_dir, $this->scssc);
	}

	protected function load()
	{
		if ($this->loaded) {
			return true;
		}
		$this->loaded = true;
		if (!class_exists('scssc')) {
			$path = !empty(WS::env()->config->scss->scss_dir)
				? WS::env()->config->less->lessphp_dir
				: self::option('scss_php_dir');
			@include_once($path . '/scss.inc.php');
		}
		if (class_exists('scssc')) {
			$this->setup();
			return true;
		}
		return false;
	}

	public function preprocess($file, $data)
	{
		$suffix = '.scss';
		$content = null;
		if (Core_Strings::ends_with($file, $suffix)) {
			$this->load();
			if (!$this->server) {
				return array($file, $content);
			}
			// TODO: move to function
			$file = Templates_HTML::css_path($file, false);
			$file_name = ltrim($file, '/\.');
			$scss_file = './' . ltrim($file, '/');
			$css_file = str_replace('.scss', '.css', $file_name);
			$css_file = str_replace('styles/', '', $css_file);
			$css_file = './' . self::option('scss_output_dir') . '/' . $css_file;
			$css_file = str_replace('//', '/', $css_file);
			$dir = dirname($css_file);
			if (!IO_FS::exists($dir)) {
				IO_FS::mkdir($dir);
			}
			if ($this->server->needsCompile($scss_file, $css_file, $etag = '')) {
				$this->server->compile($scss_file, $css_file);
			}
			return array('/' . ltrim($css_file, '\/.'), $content);
		}
		return array($file, $content);
	}

}