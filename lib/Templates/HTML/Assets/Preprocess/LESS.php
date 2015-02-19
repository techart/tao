<?php
/**
 * @package Templates\HTML\Assets\Preprocess\LESS
 */

Core::load('Cache', 'WS');

class Templates_HTML_Assets_Preprocess_LESS implements Core_ConfigurableModuleInterface, Templates_HTML_Assets_Preprocess_PreprocessorInterface
{
	const VERSION = '0.1.0';

	protected static $options = array(
		'debug' => false,
		'less_callback' => array('Less_Cache', 'Get'),
		'less_cache_dns' => 'fs://../cache/less',
		'less_output_dir' => 'styles/less',
		'less_php_dir' => '../vendor/lessphp',
		'less_import_dirs' => array(),
		'sourse_map' => false,
		'options' => array(
			'cache_dir' => '../cache/less',
			'sourceRoot' => '/',
		),
	);

	protected $cache;
	protected $output;

	public static function initialize(array $options = array())
	{
		if (!isset($options['less_import_dirs'])) {
			$options['less_import_dirs'] = array('./styles/' => '/styles/');
		}
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

	public static function instance()
	{
		$config = (array) WS::env()->config->less;
		if (!empty($config)) {
			self::options($config);
		}
		return Core::make('Templates.HTML.Assets.Preprocess.LESS');
	}

	public function __construct()
	{
		$this->cache = Cache::connect(self::option('less_cache_dns'));
		$this->output = self::option('less_output_dir');
	}

	protected function load()
	{
		if (!class_exists('Less_Parser'))
		{
			$path = !empty(WS::env()->config->less->lessphp_dir)
				? WS::env()->config->less->lessphp_dir
				: self::option('less_php_dir');
			if (!is_file($path)) {
				throw new RuntimeException("Install LessPHP:\ncomposer \"oyejorge/less.php\": \"dev-master\" or copy lib (https://github.com/oyejorge/less.php) to $path");
			}
			require_once $path.'/lib/Less/Autoloader.php';
			Less_Autoloader::register();
		}
		IO_FS::mkdir($this->output);
	}

	public function preprocess($file, $data)
	{
		//TODO: to options
		$suffix = '.less';
		$content = null;
		if (Core_Strings::ends_with($file, $suffix)) {
			$file = Templates_HTML::css_path($file, false);
			$file_name = ltrim($file, '/\.');
			$less_file = './' . ltrim($file, '/');
			$css_file = str_replace('css.less', 'less.css', $file_name);
			$css_file = str_replace('styles/', '', $css_file);
			$css_file = './' . self::option('less_output_dir') . '/' . $css_file;
			$css_file = str_replace('//', '/', $css_file);
			$dir = dirname($css_file);
			if (!IO_FS::exists($dir)) {
				IO_FS::mkdir($dir);
			}
			$this->compile($less_file, $css_file);
			return array('/' . ltrim($css_file, '\/.'), $content);
		}
		return array($file, $content);
	}

	protected function compile($less_fname, $css_fname)
	{
		self::load();
		$options = self::option('options');
		// generate source map
		if (self::option('sourse_map')) {
			$map_file = str_replace('styles/', '', $css_fname);
			$map_file = trim(str_replace('.less.css', '.map', $map_file), '/.');
			$map_dir = './styles/less-maps/';
			$map_path = "$map_dir$map_file";
			IO_FS::mkdir(dirname($map_path));
			$options = array_merge($options, array(
				'sourceMap'         => true,
				'sourceMapWriteTo'  => $map_path,
				'sourceMapURL'      => trim($map_path, '.'),
			));
			// if is out of docroot
			if ($less_fname[0] == '/' || Core_Strings::starts_with($less_fname, '..') || Core_Strings::starts_with($less_fname, './..')) {
				$less_fname = "file://$less_fname";
				$less_fname = '.' . Templates_HTML::extern_filepath($less_fname);
			}
		}
		$options['import_dirs'] = self::option('less_import_dirs');
		$dir = dirname($css_fname);
		$url = 'http://' . WS::env()->request->host . '/';
		$args = array(array($less_fname => $url), $options);
		$css_file_name = Core::invoke(self::option('less_callback'), $args);
		$cached_file = rtrim($options['cache_dir'], '/') . '/' . $css_file_name;
		if (is_file($cached_file) && !(WS::env()->request['less_compile'] && self::option('debug'))) {
			$less_ftime = filemtime($cached_file);
			$css_ftime = false;
			if (IO_FS::exists($css_fname)){
				$css_ftime = filemtime($css_fname);
			}
			if ($css_ftime && $css_ftime >= $less_ftime) {
				return false;
			}
		}
		$css = file_get_contents($cached_file);
		IO_FS::File($css_fname)->update($css);
	}

}
