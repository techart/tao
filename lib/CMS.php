<?php
/**
 * CMS
 *
 * @package CMS
 * @version 0.0.0
 */

Core::load('WS', 'Events');

/**
 * @package CMS
 */
class CMS implements Core_ModuleInterface
{
	const MODULE = 'CMS';
	const VERSION = '2.1.73';

	public static $libpath = '';
	public static $taopath = '';

	public static $config;
	public static $mappers = array();
	public static $layouts = array();
	public static $default_auth_realm = false;
	public static $disable_local_auth = true;
	public static $page_401 = '401';
	public static $page_404 = '404';
	public static $page_main = 'main';
	public static $site = '__';
	public static $site_prefix = '';
	public static $sites;
	public static $page;
	public static $defsite = '__';
	public static $default_lang = 'ru';
	public static $forced_lang = false;
	public static $defsite_title = 'Default';
	public static $default_last_modified = false;
	public static $env;
	public static $page_navigator = false;
	public static $parser_module = 'Text.Parser.Parms'; //'CMS.Parser';
	public static $wiki_parser_module = 'Text.Parser.Wiki'; //'CMS.WikiParser';
	public static $nav_module = 'CMS.Navigation';
	public static $vars_module = 'CMS.Vars2';
	public static $admin = 'admin';
	public static $admin_realm = 'admin';
	public static $www = 'www';
	public static $user_lang = false;
	public static $parser = false;
	public static $wiki_parser = false;
	public static $navigation = false;
	public static $nav_setup = false;
	public static $print_prefix = false;
	public static $print_version = false;
	public static $print_layout = 'print';
	public static $views_path = '';
	public static $app_path = '../app';
	public static $bin_path = '../bin';
	public static $files_path = '../%files%';
	public static $config_path = '../config';
	public static $logs_path = '../logs';
	public static $stdfiles_path = '';
	public static $stdfiles_cache = 'stdcache';
	public static $assets_dir = 'tao';
	public static $check_assets = true;
	public static $globals;
	public static $force_layout = false;
	public static $in_admin = false;
	public static $is_cli = false;
	public static $is_offline = null;
	public static $extra_auth = false;
	public static $after_auth = false;

	public static $temp_dir = false;

	public static $navigation_struct = false;

	public static $cfg_file = '../config/site.php';
	public static $cfg;
	public static $dbDSN;
	public static $db = false;
	public static $host = false;

	public static $index_controller = 'CMS.Controller.Index';
	public static $admin_index_controller = 'CMS.Controller.AdminIndex';

	public static $root_exception_catcher = 'CMS.ExceptionCatcher';

	public static $component_original_names = array();
	public static $component_names = array();
	public static $component_module_prefix = array();
	public static $current_component_name;
	public static $current_controller_name;
	public static $current_controller;
	public static $current_mapper;
	public static $current_route;
	public static $disable_lang = false;

	public static $plugins_before_dispatch = array();
	public static $action_handler_process = false;
	public static $commands = array();

	public static $css_files = array();
	public static $js_files = array();

	public static $fields_types = array();

	public static $restricted_realms = array();
	public static $original_uri = '/';

	public static $i_view = false;

	public static $i_stdstyles = array();
	public static $i_stdscripts = array();

	public static $registered_objects = array();
	public static $created_objects = array();
	public static $objects_builder = false;

	public static $db_connections = array();
	public static $orm_root = false;
	public static $orm_autoload = true;

	static protected $vars;

	protected static $lazy_components = false;

	static protected $web_application;
	static protected $common_application;
	static protected $dispatcher;
	static protected $enable_rest = false;
	static protected $enable_dev_components;

	protected static $is_components_loaded = false;

	static protected $listeners = array();

	static public function env()
	{
		return self::$env;
	}

	static protected function configure_paths()
	{
		$path_info = IO_FS::Path(__FILE__);
		self::$libpath = $path_info->dirname;

		self::$taopath = rtrim(self::$libpath, '/');
		self::$taopath = preg_replace('{[a-z0-9_-]+$}i', '', self::$taopath);
		self::$taopath = rtrim(self::$taopath, '/');

		self::$stdfiles_path = self::$taopath . '/files';
		self::$views_path = self::$taopath . '/views';
		self::check_assets();

		if ($m = Core_Regexps::match_with_results('{/([^/]+)$}', rtrim($_SERVER['DOCUMENT_ROOT'], '/'))) {
			CMS::$www = $m[1];
		}
	}

	static protected function configure_loader()
	{
		Core::load('Core.Loader');
		$base_path = self::$app_path;
		if (self::$enable_dev_components) {
			$base_path = str_replace('app', '(dev|app)', $base_path);
		}
		Core::loader(Core_Loader::extended())->paths(array(
				'---Component.{name}.App' => $base_path . '/components/{name}/app(/lib)?',
				'-Component.{name}' => array($base_path . '/components(/{name}/lib)?', $base_path . '/components/{name}(/lib)?'),
				'--Component.{name}' => $base_path . '/components/{name}/lib',
			)
		);
	}

	static public function ws_status_listener($response)
	{
		if ($response->body instanceof Templates_Template) {
			CMS::layout_view($response->body);
		}
	}

	static protected function add_listeners()
	{
		Events::add_listener('ws.status', array('CMS', 'ws_status_listener'));
		foreach (self::$listeners as $e => $l)
			Events::add_listener($e, $l);
	}

	/**
	 * @param array $config
	 */
	static function initialize($config = array())
	{
		try {
			self::$globals = new ArrayObject();
			foreach ($config as $key => $value)
				self::$$key = $value;

			self::add_listeners();

			foreach (array('files_path', 'stdfiles_cache', 'assets_dir') as $key)
				self::$$key = str_replace('%files%', Core::option('files_name'), self::$$key);

			self::configure_paths();

			self::configure_loader();

			Core::load('Events');
			/**
			 *
			 * @event cms.initialize.start
			 * Вызывается в начале инициализации при загрузке модуля CMS. Если возвращено значение, отличное от null, то дальнейшая инициализация произведена не будет.
			 *
			 */
			$rc = Events::call('cms.initialize.start');
			if (!is_null($rc)) {
				return $rc;
			}

			Core::load('WS.DSL');
			self::$common_application = WS_DSL::application();
			self::$web_application = WS_DSL::application();

			self::common_application()->cms_static();
			self::dummy_run();

			if (CMS::is_lazy_components()) {
				Core::option('spl_autoload', true);
				Core::register_spl_autoload();
			}

			if (!Core::option('spl_autoload')) {
				spl_autoload_register('CMS::spl_autoload');
				self::load();
			} else {
				self::load();
			}

			if (self::$enable_rest) {
				Core::load('WS.Services.REST');
				Core::load('CMS.Application');
				self::$dispatcher = new CMS_Application_Dispatcher();
				WS_DSL::add_middleware('dispatcher', self::$dispatcher);
			}

			self::common_application()
				->config(self::$cfg_file)
				->cache()
				->db()
				->orm(self::$orm_root ? self::$orm_root : 'CMS.ORM.Root')
				->cms_configure();

			//FIXME: не запускать сразу
			self::dummy_run();

			/**
			 *
			 * @event cms.initialize.ready
			 * Вызывается в конце инициализации при загрузке модуля CMS
			 *
			 */
			$rc = Events::call('cms.initialize.ready');
			if (!is_null($rc)) {
				return $rc;
			}

			Core::load(self::vars_module());

			self::application()
				->session()
				->status(array(404 => '404', 500), 'status', true)
				->cms_std()
				->auth_apache(self::build_apache_auth_module())
				->auth_basic(CMS_Handlers::AuthModule(), array('env_name' => 'admin_auth'))
				->cms_realm_auth();
		} catch (Exception $e) {
			self::root_catcher($e);
		}
	}

	public static function build_apache_auth_module()
	{
		$config = Config::all()->apache_auth;
		$module = Core::if_not($config->module, 'WS.Auth.Apache.RemoteAuthModule');
		$args = Core::if_not($config->module_args, array());
		$instance = Core::amake($module, $args);
		return $instance;
	}

	static public function spl_autoload($class)
	{
		if (in_array($class, array('CMS_Mapper', 'CMS_Router'))) {
			Core::load('CMS.Controller');
		}
	}

	static function dispatcher()
	{
		return self::$dispatcher;
	}

	static public function vars_module($value = false)
	{
		if (is_string($value)) {
			return self::$vars_module = $value;
		}
		return self::$vars_module;
	}

	static public function vars()
	{
		if (isset(self::$vars)) {
			return self::$vars;
		}
		self::$vars = Core::make(self::$vars_module);
		if (method_exists(self::$vars, 'setup')) {
			self::$vars->setup();
		}
		return self::$vars;
	}

	static function load()
	{
		//TODO: убрать и подгружать по необходимости
		Core::load('Events');
		Core::load('CMS.Component');
		Core::load('Templates.HTML');
		Core::load('Forms');
		Core::load('IO.FS');
		Core::load('CMS.Controller');
		Core::load('CMS.Dumps');
		Core::load('CMS.Admin');
		Core::load('Text.Insertions');
		Core::load('CMS.Insertions');
		Core::load('CMS.PageNavigator');
	}

	static public function application()
	{
		return self::$web_application;
	}

	static public function common_application()
	{
		return self::$common_application;
	}

	static protected function dummy_run($name = 'common_application')
	{
		return self::$name()->dummy_service()->run(WS::env());
	}

	static function check_assets_symlink($path)
	{
		if (is_link($path)) {
			return;
		}
		if (!function_exists('symlink')) {
			throw new CMS_Exception("Error creating symlink $path (function not exists)");
		}
		if (is_dir($path)) {
			CMS::rmdir($path);
		}
		$dir = IO_FS::File($path)->dir_name;
		CMS::mkdirs($dir);
		symlink(self::$stdfiles_path, $path);
	}

	static function check_assets()
	{
		if (!self::$check_assets) {
			return;
		}
		if (!isset($_SERVER['HTTP_HOST']) && !isset($_SERVER['REQUEST_URI'])) {
			return;
		}
		self::check_assets_symlink('./' . self::$assets_dir);
	}

	static function copy_assets()
	{
		self::copy(self::$stdfiles_path, './' . self::$assets_dir);
	}

	//TODO: сделать callback
	static protected function before_run($env)
	{
		/**
		 * @event cms.run
		 * Вызывается после инициализации всех компонентов - в начале работы CMS::run()
		 */
		Events::call('cms.run');
		$env->urls = CMS::urls();
		foreach (CMS::mappers() as $name => $mapper) {
			$env->urls->map(strtolower($name), $mapper);
		}
	}

	static public function urls()
	{
		return Core::make('CMS.Urls');
	}

	static public function mappers()
	{
		self::load_mappers_from_cache();
		return CMS::$mappers;
	}

	public static function is_lazy_components()
	{
		return self::$lazy_components;
	}

	protected static function load_mappers_from_cache()
	{
		if (!self::is_lazy_components()) {
			return;
		}
		// include files where reouters defined
		if ($files = WS::env()->cache->get('cms:url_mappers:files')) {
			foreach ($files as $f) {
				include_once($f);
			}
		}
		// get & load routers classes
		if ($classes = WS::env()->cache->get('cms:url_mappers:classes')) {
			foreach ($classes as $name => $class) {
				if (!isset(self::$mappers[$name])) {
					$reflection = new ReflectionClass($class);
					self::$mappers[$name] = $reflection->newInstance();
				}
			}
		} else {
			// load components & save routers
			Events::add_once('cms.load_components', function() {
				$mappers = CMS::$mappers;
				$classes = array();
				foreach ($mappers as $name => $mapper) {
					$classes[$name] = get_class($mapper);
				}
				WS::env()->cache->set('cms:url_mappers:classes', $classes, 0);
				$files = array();
				foreach ($mappers as $mapper) {
					$reflection = new ReflectionClass($mapper);
					$files[] = $reflection->getFileName();
				}
				WS::env()->cache->set('cms:url_mappers:files', $files, 0);
			});
			self::load_components();
		}
	}

	static function is_offline()
	{
		if (is_null(self::$is_offline)) {
			if (isset($_SERVER['HTTP_USER_AGENT'])) {
				self::$is_offline = Core_Regexps::match('{teleport}i', $_SERVER['HTTP_USER_AGENT']);
			}
			self::$is_offline = false;
		}
		return self::$is_offline;
	}

	/**
	 * Производит запуск веб-приложения
	 *
	 */
	public function run()
	{

		try {
			if (!isset(self::$component_names['CMSStockroom'])) {
				Core::load('CMS.Stockroom');
			}
			if (!isset(self::$component_names['CMSFSPages'])) {
				Core::load('CMS.FSPages');
			}
			self::before_run(CMS::env());
			// Если скрипт запущен из командной строки, то веб-приложение не запускается
			//TODO: create cli_application
			if (!isset($_SERVER['HTTP_HOST']) && !isset($_SERVER['REQUEST_URI'])) {
				CMS::$is_cli = true;
				Core::load('CMS.CLI');
				CMS_CLI::run();
				return;
			}
			if (self::$enable_rest) {
				self::application()->dispatcher();
			}

			return WS::run(
				self::application()->
					cms_action()
			);
		} catch (Exception $e) {
			self::root_catcher($e);
		}
	}

	/**
	 * Корневой перехватчик исключений
	 *
	 */
	public function root_catcher($e)
	{
		Core::load(self::$root_exception_catcher);
		$class = str_replace('.', '_', self::$root_exception_catcher);
		call_user_func(array($class, 'run'), $e);
	}

	/**
	 * Кешированный вызов. Метод run переданного модуля вызывается только один раз после модификации файла модуля.
	 *
	 */
	public function cached_run($module, $method = 'run')
	{
		$class = Core_Types::real_class_name_for($module);
		$key = "cms:cached_run:{$class}";
		if ($method != 'run') {
			$key .= "_{$method}";
		}
		$tc = (int)WS::env()->cache->get($key);
		$module_file = Core::loader()->file_path_for($module);
		$tm = filemtime($module_file);
		if ($tc < $tm) {
			Core::load($module);
			Core::call($class, $method)->invoke();
			WS::env()->cache->set($key, $tm, 0);
		}
	}

	/**
	 * Возвращает экземпляр языкового модуля (для многоязычных сайтов)
	 *
	 * @return CMS_Lang
	 */
	static function lang($code = false, $force = false)
	{
		if (!self::$user_lang) {
			Core::load('CMS.Lang');
			self::$user_lang = new CMS_Lang();
			self::$user_lang->init_module(self::site());
		}
		if (self::$disable_lang) {
			if ($code || is_string($code)) {
				return $code;
			}
			return self::$user_lang;
		}
		if ($code || is_string($code)) {
			$code = (string)$code;
			if (Core_Regexps::match('{^[a-z0-9_]+$}i',$code)) {
				$bt = debug_backtrace(0);
				$bt = $bt[0];
				return self::$user_lang->for_file($bt['file'],$code,$force);
			}
			if ($m = Core_Regexps::match_with_results('{^([^:]+):([^:]*):([^:]+)$}', $code)) {
				$lang = trim($m[1]);
				$comp = trim($m[2]);
				$parm = trim($m[3]);
				if ($lang == 'lang') {
					$l = CMS::site_lang();
					if ($force) {
						CMS::site_set_lang($force);
					}
					$rc = self::$user_lang->$comp->$parm;
					CMS::site_set_lang($l);
					return $rc;
				}
			}
			return self::$user_lang->transform($code, $force);
		}
		return $code === false ? self::$user_lang : '';
	}
	
	static function t($text,$force=false)
	{
		if (!self::$user_lang) {
			Core::load('CMS.Lang');
			self::$user_lang = new CMS_Lang();
			self::$user_lang->init_module(self::site());
		}
		$bt = debug_backtrace(0);
		$bt = $bt[0];
		return self::$user_lang->for_file($bt['file'],$text,$force);
	}

	/**
	 * Возвращает экземпляр объекта навигации сайта
	 *
	 * @param boolean $set
	 *
	 * @return CMS_Navigation
	 */
	static function navigation($set = false)
	{
		if (!self::$navigation) {
			self::process_navigation();
		}

		if (!self::$nav_setup) {
			self::$nav_setup = true;
			self::$navigation->setup_selected();
		}
		return is_string($set) ? self::$navigation[$set] : self::$navigation;
	}

	/**
	 * Возвращает экземпляр модуля взаимодействия компонентов
	 *
	 * @return object
	 */
	static function objects()
	{
		if (!self::$objects_builder) {
			self::$objects_builder = new CMS_ObjectsBuilder();
		}
		return self::$objects_builder;
	}


	/**
	 * Возвращает подключение к базе данных
	 *
	 * @return object
	 */
//TODO: замениться на WS::env->db->name
	static function db($name = 'default')
	{
		return WS::env()->db->{$name};
	}

	/**
	 * Возвращает корневой ORM-маппер
	 *
	 * @return object
	 */
	static function orm()
	{
		if (!self::$orm_root) {
			if (WS::env()->orm) {
				self::$orm_root = WS::env()->orm;
			} else {
				Core::load('CMS.ORM');
				self::$orm_root = new CMS_ORM_Root();
			}
		}
		return self::$orm_root;
	}

	/**
	 * Определяет находимся ли мы в данный момент в админе
	 *
	 * @return boolean
	 */
	static function admin()
	{
		return self::$in_admin;
	}

	/**
	 * Возвращает имя каталога DOCUMENT_ROOT (не полный путь, а только последный подкаталог - он может по-разному называться на разный хостингах)
	 *
	 * @return string
	 */
	static function www()
	{
		$www = trim(self::$www);
		return $www == '' ? 'www' : $www;
	}

	/**
	 * Возвращает имя каталога для хранения временных файлов
	 *
	 * @return string
	 */
	static function temp_dir()
	{
		if (self::$temp_dir === true) {
			return rtrim(sys_get_temp_dir(), '/');
		}
		if (is_string(self::$temp_dir)) {
			return rtrim(self::$temp_dir, '/');
		}
		if (isset(self::$cfg->site) && $dir = self::$cfg->site->temp_dir) {
			return rtrim($dir, '/');
		}
		if (!IO_FS::exists('../' . Core::option('files_name') . '/tmp')) {
			@self::mkdirs('../' . Core::option('files_name') . '/tmp');
		}
		if (IO_FS::exists('../' . Core::option('files_name') . '/tmp')) {
			return '../' . Core::option('files_name') . '/tmp';
		}
		if (!IO_FS::exists('./' . Core::option('files_name') . '/tmp')) {
			self::mkdirs('./' . Core::option('files_name') . '/tmp');
		}
		return './' . Core::option('files_name') . '/tmp';
	}

	/**
	 * @return string
	 */
	static function site_dir()
	{
		$s = getcwd();
		$s = preg_replace('{[^/]+$}', '', $s);
		return $s;
	}

	/**
	 * Возвращает true если компонент с заданным именем зарегистрирован в системе
	 *
	 * @param string $name
	 *
	 * @return boolean
	 */
	static function component_exists($name)
	{
		return isset(self::$component_names[$name]);
	}

	/**
	 * Возвращает имя класса - компонета с заданным именем или false если такого компонента не зарегистрировано
	 *
	 * @param string $name
	 *
	 * @return string|false
	 */
	static function component_class_name($name)
	{
		if (!self::component_exists($name)) {
			return false;
		}
		return self::$component_names[$name];
	}

	/**
	 * Возвращает удаленный IP
	 *
	 * @return string
	 */
	static function ip()
	{
		if (isset($_SERVER['X_REAL_IP'])) {
			return $_SERVER['X_REAL_IP'];
		}
		if (isset($_SERVER['HTTP_X_REAL_IP'])) {
			return $_SERVER['HTTP_X_REAL_IP'];
		}
		return $_SERVER['REMOTE_ADDR'];
	}

	/**
	 * Возвращает наименование сайта или домен если не задано
	 *
	 * @param string $p
	 *
	 * @return string
	 */
	static function site_title($p = false)
	{
		$title = false;
		$titlei = self::$cfg->site->title;
		if (!$titlei) {
			$titlei = $_SERVER['HTTP_HOST'];
		}
		switch ($p) {
			case 'r':
				$title = self::$cfg->site->titler;
				break;
			case 'd':
				$title = self::$cfg->site->titled;
				break;
			case 'v':
				$title = self::$cfg->site->titlev;
				break;
			case 't':
				$title = self::$cfg->site->titlet;
				break;
			case 'p':
				$title = self::$cfg->site->titlep;
				break;
		}
		if (!$title) {
			$title = $titlei;
		}
		return $title;
	}

	/**
	 * Возвращает true если приложение исполняется на локальном проекте (не на хостинге)
	 *
	 * @return boolean
	 */
	static function is_local()
	{
		if (isset(self::$cfg->site)) {
			$local = trim(self::$cfg->site->local);
			if (self::check_yes($local)) {
				return true;
			}
		}
		return isset($_SERVER['IS_TECHART']);
	}

	/**
	 * Возвращает код текущего сайта (для многосайтовых конфигураций)
	 *
	 * @return string
	 */
	static function site()
	{
		return self::$site;
	}

	/**
	 * Возвращает язык текущего сайта (для многосайтовых конфигураций) или админа
	 *
	 * @return string
	 */
	static function site_lang()
	{
		if (self::$forced_lang) {
			return self::$forced_lang;
		}
		if (CMS::admin()) {
			return CMS_Admin::$lang;
		}
		$data = self::$sites[self::$site];
		if (!isset($data)) {
			return self::$default_lang;
		}
		if (!isset($data['lang'])) {
			return self::$default_lang;
		}
		return $data['lang'];
	}

	/**
	 * Устанавливает язык интерфейса вне зависимости от настроек текущего сайта и админа
	 *
	 */
	static function site_set_lang($lang)
	{
		self::$forced_lang = $lang;
		Core::load('CMS.Lang');
		CMS_Lang::reset();
	}

	/**
	 * Возвращает URI-префикс текущего или указанного (если передан параметр) сайта (для многосайтовых конфигураций)
	 *
	 */
	static function site_prefix($site = false)
	{
		if (!$site) {
			return self::$site_prefix;
		}
		if (isset(self::$sites[$site])) {
			$value = trim(self::$sites[$site]['prefix']);
			$value = trim($value, '');
			if ($value == '') {
				return '';
			}
			return "/$value";
		}
		return '';
	}

	/**
	 * Возвращает доменное имя указанного сайта (для многосайтовых конфигураций)
	 *
	 */
	static function site_host($site)
	{
		$rhost = self::$env->request->host;
		if (isset(self::$sites[$site])) {
			$host = trim(self::$sites[$site]['host']);
			$hostname = trim(self::$sites[$site]['hostname']);
			if ($hostname != '') {
				return $hostname;
			}
			if ($host != '' && $host[0] != '{' && $host[0] != '/') {
				return $host;
			}
		}
		return $rhost;
	}

	/**
	 * Возвращает путь к каталогу указанного компонента
	 *
	 */
	static function component_dir($component, $dir = false)
	{
		$rc = self::$app_path . '/components/' . $component;
		if ($dir) {
			$rc .= "/$dir";
		}
		return $rc;
	}

	/**
	 * Возвращает путь к каталогу текущего (в данный момент работающего) компонента
	 *
	 */
	static function current_component_dir($dir = false)
	{
		$rc = self::component_dir(self::$current_component_name);
		if ($dir) {
			$rc .= "/$dir";
		}
		return $rc;
	}

	/**
	 * Возвращает URL для скачивания статического файла из каталога текущего компонента
	 *
	 */
	static function static_url($file, $component = false)
	{
		$path = self::component_static_path($file, $component);
		$url = Templates_HTML::extern_filepath($path);
		return $url;
		// OLD
		// if (!$component) $component = self::$current_component_name;
		// $path = self::component_dir($component)."/$file";
		// $m = IO_FS::exists($path)? filemtime($path) : "0";
		// return "/component-static/$component/$file/$m/";
	}

	static function component_path($file, $component = false)
	{
		if (!$component) {
			$component = self::$current_component_name;
		}
		$app_path = self::component_dir($component) . "/app/$file";
		if (is_file($app_path)) {
			return $app_path;
		}
		$path = self::component_dir($component) . "/$file";
		return $path;
	}

	/**
	 * Возвращает конструкцию вида file://.... для файла из каталога текущего компонента
	 *
	 */
	static function component_static_path($file, $component = false)
	{
		return "file://" . self::component_path($file, $component);
	}

	/**
	 * Производит рассылку по майллисту.
	 *
	 * @param Mail_Message $mail
	 * @param array        $list
	 * @param string       $dir
	 *
	 * @return string
	 */
	static function maillist($mail, $list, $dir = '../bin/maillist')
	{
		$dir = rtrim($dir, '/');
		CMS::mkdirs("$dir/messages");
		CMS::mkdirs("$dir/recipients");
		Core::load('Mail.List');
		Mail_List::option('root', $dir);

		$emails = array();
		foreach ($list as $k => $item) {
			if (is_string($k) && is_string($item)) {
				$item = array(
					'To' => $k,
					'Unsubscribe-List' => $item,
					'UNSUBSCRIBE' => $item,
				);
			} else {
				if (is_string($item)) {
					$item = array('To' => $item);
				}
			}
			if (is_string($k) && !isset($item['To'])) {
				$item['To'] = $k;
			}
			$emails[] = $item;
		}

		Mail_List::Spawner($mail, $emails)->id(time() . rand(1111, 9999))->spawn();
	}

	/**
	 * Производит замену всех вставок (Insertions).
	 *
	 * @param string $source
	 *
	 * @return string
	 */
	static function process_insertions($src)
	{
		return Text_Insertions::filter()->process($src);
	}

	/**
	 * Парсит текст, превращая его в массив
	 *
	 * @param string $src
	 *
	 * @return array
	 */
	static function parse_parms($src)
	{
		if (!self::$parser) {
			Core::load(self::$parser_module);
			self::$parser = Core_Types::reflection_for(str_replace('.', '_', self::$parser_module))->newInstance();
		}
		return self::$parser->parse($src);
	}

	/**
	 * Производит действие, обратное parse_parms
	 *
	 * @param iterable $src
	 *
	 * @return string
	 */
	static function unparse_parms($src)
	{
		if (!self::$parser) {
			Core::load(self::$parser_module);
			self::$parser = Core_Types::reflection_for(str_replace('.', '_', self::$parser_module))->newInstance();
		}
		return self::$parser->unparse($src);
	}

	/**
	 * Трансформирует wiki-разметку в HTML
	 *
	 * @param string $src
	 *
	 * @return string
	 */
	static function parse_wiki($src, $config = array())
	{
		if (!self::$wiki_parser) {
			Core::load(self::$wiki_parser_module);
			self::$wiki_parser = Core_Types::reflection_for(str_replace('.', '_', self::$wiki_parser_module))->newInstance();
		}
		return self::$wiki_parser->parse($src, $config);
	}

	/**
	 * В переданном тексте заменяет относительные ссылки на абсолютные (в текущем домене)
	 *
	 * @param string $source
	 *
	 * @return string
	 */
	static function abs_refs($s)
	{
		$s = preg_replace_callback('{<a([^>]+)href="([^"]+)"}ism', array(self, 'abs_refs_cb'), $s);
		return $s;
	}

	/**
	 * @param string $name
	 * @param string $text
	 */
	static function log($name, $text)
	{
		if (!IO_FS::exists(self::$logs_path)) {
			self::mkdirs(self::$logs_path);
			self::chmod_dir(self::$logs_path);
		}
		$filename = self::$logs_path.'/'.$name.'.log';
		$text = trim($text);
		$f = fopen($filename, "a");
		$t = date('Y-m-d G:i:s');
		$ip = self::ip();
		fputs($f, "$t [$ip] $text\n");
		fclose($f);
		CMS::chmod_file($filename);
	}

	/**
	 * Регистрирует в системе компонент
	 *
	 * @param string                           $name
	 * @param WebKit_Controller_AbstractMapper $mapper
	 * @param                                  $layout
	 */
	static protected $components = array();

	static public function add_component_object($obj, $mapper = null, $layout = 'work')
	{
		$name = $obj->name;
		self::$components[$name] = $obj;
		if (!$mapper && method_exists($obj, 'router')) {
			$mapper = $obj->router();
		}
		if ($mapper) {
			self::add_component($name, $mapper, $layout);
		}
		if ($obj->is_auto_schema()) {
			$obj->process_schema();
		}
		Events::call('cms.add_component_object', $obj, $mapper, $layout);
	}

	static public function components()
	{
		return self::$components;
	}

	public static function load_components()
	{
		if (self::$is_components_loaded) {
			// Events::call('cms.load_components');
			return;
		}
		self::$is_components_loaded = true;
		$path = trim(self::$app_path, '/') . '/components/';
		$dirs = array_filter(glob($path . '*'), 'is_dir');
		foreach ($dirs as $path) {
			$name = basename($path);
			$component_name = 'Component.' . $name;
			if (!Core::is_loaded($component_name)) {
				Core::load($component_name);
			}
		}
		Events::call('cms.load_components');
	}

	static public function get_component_name_for($object)
	{
		$name = null;
		$class = Core_Types::real_class_name_for($object);
		$parts = explode('_', $class);
		if ($parts[0] == 'Component') {
			$name = $parts[1];
		}
		return $name;
	}

	static public function component_for($object)
	{
		$name = self::get_component_name_for($object);
		return self::component($name);
	}

	/**
	 * @param string $name
	 *
	 * @return CMS_Component|null
	 */
	public static function component($name = null)
	{
		if (is_null($name)) {
			if (self::$current_component_name) {
				$name = self::$current_component_name;
			}
		}
		$object = isset(self::$components[$name]) ? self::$components[$name] : null;
		// if (!$object) {
		// 	$object = self::load_component_if_exists($name);
		// 	return $object;
		// }
		return $object;
	}

	public static function load_component_if_exists($name)
	{
		$path = trim(self::$app_path, '/') . '/components/' . $name;
		if (is_dir($path)) {
			Core::load('Component.' . $name);
			return isset(self::$components[$name]) ? self::$components[$name] : null;
		}
		return null;
	}

	static function add_component($name, $mapper, $layout = 'work')
	{
		self::$component_original_names[strtolower($name)] = $name;
		self::$component_names[strtolower($name)] = "Component_$name";
		self::$component_module_prefix[strtolower($name)] = "Component.$name";
		self::$component_names[$name] = "Component_$name";
		if (is_string($mapper)) {
			$mapper = Core::make($mapper);
		}
		if (self::$enable_rest && ($mapper instanceof WS_Services_REST_Application || is_array($mapper))) {
			$app = is_array($mapper) ? $mapper : array();
			if (is_string($mapper)) {
				$app['class'] = $mapper;
			}
			if (is_object($mapper)) {
				$app['instance'] = $mapper;
				$mapper->name = $name;
			}
			if (!isset($app['prefix'])) {
				$app['prefix'] = Core_Strings::downcase($name);
			}
			self::$dispatcher->map($name, $app);
		} else {
			self::$mappers[$name] = $mapper;
		}
		self::$layouts[$name] = $layout;
		$dir = self::component_dir($name);
		Text_Insertions::filter()->add_views_paths(array(
				$dir . '/app/views',
				$dir . '/views',
			)
		);
		Events::call('cms.add_component', $name, $mapper, $layout);
	}

	/**
	 * Регистрирует в системе "вставку" (Insertion)
	 *
	 * @param string $class
	 * @param string $method
	 */
	static function register_insertions()
	{
		$args = func_get_args();
		$class = $args[0];
		array_shift($args);
		foreach ($args as $arg) {
			$name = $arg;
			$func = $arg;
			if ($m = Core_Regexps::match_with_results('/^(.+):(.+)$/', $arg)) {
				$name = $m[1];
				$func = $m[2];
			}
			Text_Insertions::register_filter(array(strtolower($name) => new Core_Call($class, $func)));
		}
	}

	/**
	 * Регистрирует в системе объект взаимодействия компонентов
	 *
	 * @param string $name
	 * @param string $module
	 */
	static function register_object($name, $module)
	{
		self::$registered_objects[$name] = $module;
	}

	public static function run_command($chapter, $method = null)
	{
		if (!isset(CMS::$commands[$chapter])) {
			return false;
		}
		foreach (CMS::$commands[$chapter] as $k => $info) {
			if (is_null($method) || $method == $info['method']) {
				$ename = self::event_name_for_command($chapter, $info['method']);
				$args = array_slice($info['args'], 1);
				Events::call($ename,
					$is_php_5_3 = (isset($args[0]) ? $args[0] : null),
					$is_php_5_3 = (isset($args[1]) ? $args[1] : null),
					$is_php_5_3 = (isset($args[2]) ? $args[2] : null),
					$is_php_5_3 = (isset($args[3]) ? $args[3] : null),
					$is_php_5_3 = (isset($args[4]) ? $args[4] : null),
					$is_php_5_3 = (isset($args[5]) ? $args[5] : null)
				);
			}
		}
		return true;
	}

	/**
	 * Добавляет комманду в очередь
	 *
	 * @param string $chapter
	 * @param string $command
	 */
	static function add_command()
	{
		$args = func_get_args();
		$chapter = trim($args[0]);
		$method = trim($args[1]);
		array_splice($args, 0, 2);
		if (!isset(self::$commands[$chapter])) {
			self::$commands[$chapter] = array();
		}
		self::$commands[$chapter][] = array(
			'method' => $method,
			'args' => $args,
		);
		Events::add_listener(self::event_name_for_command($chapter, $method), $args[0]);
	}

	public static function event_name_for_command($chapter, $method)
	{
		return "cms.commands.$chapter.$method";
	}

	/**
	 * Добавляет в очередь функцию, которая должна быть выполнениа непосредственно перед диспетчеризацией HTTP-запроса.
	 *
	 * @param string $class
	 * @param string $method
	 */
	static function on_before_dispatch($class, $method)
	{
		self::$plugins_before_dispatch[$class] = $method;
	}

	/**
	 * Вычисляет количество страниц и валидность номера страницы для постраничной навигации какого-либо списка
	 *
	 * @param int $count
	 * @param int $per_page
	 * @param int $page_number
	 */
	static function calc_pages($cnt, $perpage, &$page)
	{
		if ($page < 1) {
			$page = 1;
		}
		$num_pages = $cnt / $perpage;
		if (floor($num_pages) != $num_pages) {
			$num_pages = floor($num_pages) + 1;
		}
		if ($num_pages < 1) {
			$num_pages = 1;
		}
		if ($page > $num_pages) {
			$page = 1;
		}
		return $num_pages;
	}

	/**
	 * Возвращает HTML-код постраничного навигатора.
	 *
	 * @param int    $page
	 * @param int    $num_of_pages
	 * @param string $url_template
	 *
	 * @return string
	 */
	static function page_navigator($page, $numpages, $url)
	{
		return self::$page_navigator->invokeArgs(null, array($page, $numpages, $url));
	}

	/**
	 * Преобразует в строке русские буквы в транслит
	 *
	 * @param string $value
	 *
	 * @return string
	 */
	static function translit($s)
	{
		return Core::make('Text.Process')->process($s, 'translit');
	}

	/**
	 * Возвращает true если строковой параметр равен одному из значений: '1','yes','true','on'
	 *
	 * @param string $value
	 *
	 * @return boolean
	 */
	static function check_yes($s)
	{
		return in_array($s, array('1', 'yes', 'true', 'on'));
	}

	/**
	 * Возвращает true если строковой параметр равен одному из значений: '0','none','no','false','off'
	 *
	 * @param string $value
	 *
	 * @return boolean
	 */
	static function check_no($s)
	{
		return in_array($s, array('0', 'none', 'no', 'false', 'off', ''));
	}

	/**
	 * Устанавливает мета-теги если таковые описаны в переданном хеше - в элементах meta.title, meta.description, meta.keywords
	 *
	 * @param array|Data_Hash $parms
	 */
	static function setup_meta($parms)
	{
		foreach ($parms as $name => $value)
			if (preg_match('{meta\.(.*)}', $name, $m)) {
				self::env()->meta->{$m[1]} = $value;
			}
	}

	/**
	 * @param string $template
	 * @param array  $parms
	 * @param string $layout
	 *
	 * @return CMS_Views_TemplateView
	 */
	static function render_view($tpl, $parms = array(), $layout = false)
	{
		Core::load('CMS.Views');
		$view = Templates_HTML::Template($tpl)->with($parms);
		if ($layout) {
			$view->within_layout($layout);
		}
		return $view;
	}

	/**
	 * @param string $template
	 * @param array  $parms
	 * @param string $layout
	 *
	 * @return string
	 */
	static function render($tpl, $parms = array(), $layout = false)
	{
		$view = self::render_view($tpl, $parms, $layout);
		return $view->as_string();
	}

	/**
	 * @param string $template
	 * @param array  $parms
	 *
	 * @return string
	 */
	static function render_in_page($tpl, $parms = array())
	{
		if (!self::$i_view) {
			return self::render($tpl, $parms);
		}
		return self::$i_view->partial($tpl, $parms);
	}

	static function render_in_page_cache($tpl, $parms = array())
	{
		if (!self::$i_view) {
			return self::render($tpl, $parms);
		}
		return self::$i_view->partial_cache($tpl, $parms);
	}

	/**
	 * Возвращает единицу измерения в числе и падеже, соответствующем заданному числу
	 *
	 * @param int    $number
	 * @param string $ei
	 * @param string $er
	 * @param string $mr
	 *
	 * @return string
	 */
	static function units($num, $ei, $er, $mr)
	{
		$num = (int)$num;
		if ($num > 10 && $num < 15) {
			return $mr;
		}
		$num = $num % 10;
		if ($num == 0) {
			return $mr;
		}
		if ($num == 1) {
			return $ei;
		}
		if ($num < 5) {
			return $er;
		}
		return $mr;
	}

	/**
	 * Находит в HTML-тексте незакрытые теги и закрывает их
	 *
	 * @param string $html
	 *
	 * @return string
	 */
	static function close_tags($html)
	{
		$single_tags = array('meta', 'img', 'br', 'link', 'area', 'input', 'hr', 'col', 'param', 'base');
		preg_match_all('~<([a-z0-9]+)(?: .*)?(?<![/|/ ])>~iU', $html, $result);
		$openedtags = $result[1];
		preg_match_all('~</([a-z0-9]+)>~iU', $html, $result);
		$closedtags = $result[1];
		$len_opened = count($openedtags);
		if (count($closedtags) == $len_opened) {
			return $html;
		}
		$openedtags = array_reverse($openedtags);
		for ($i = 0; $i < $len_opened; $i++) {
			if (!in_array($openedtags[$i], $single_tags)) {
				if (false !== ($key = array_search($openedtags[$i], $closedtags))) {
					unset($closedtags[$key]);
				} else {
					$html .= '</' . $openedtags[$i] . '>';
				}
			}
		}
		return $html;
	}

	/**
	 * Удаляет из HTML-текста теги (кроме IMG) и пробельные символы (в т.ч. и nbsp) для детекта пустого текста
	 *
	 * @param string $html
	 *
	 * @return string
	 */
	static function html_clear($html)
	{
		$s = strip_tags($html, '<img>');
		$s = str_ireplace('&nbsp;', '', $s);
		$s = trim($s);
		return $s;
	}

	/**
	 * Удаляет каталог рекурсивно вместе со всеми его подкаталогами и содержащимися в нем файлами
	 *
	 * @param string $dir
	 */
	static function rmdir($dir)
	{
		$fo = IO_FS::file_object_for($dir);
		if ($fo) {
			$fo->rm();
		}
	}

	/**
	 * Копирует рекурсивно вместе со всеми подкаталогами и содержащимися в нем файлами
	 *
	 * @param string $from
	 * @param string $to
	 */
	static function copy($from, $to)
	{
		IO_FS::cp($from, $to);
	}

	/**
	 * Устанавливает права на файл, созданный движком
	 *
	 * @param string $name
	 */
	static function chmod_file($name)
	{
		return IO_FS::file_object_for($name)->set_permission();
	}

	/**
	 * Устанавливает права на каталог, созданный движком
	 *
	 * @param string $name
	 */
	static function chmod_dir($name)
	{
		return IO_FS::file_object_for($name)->set_permission();
	}

	/**
	 * Создает каталог рекурсивно
	 *
	 * @param string $dir
	 */
	public function mkdirs($dirs)
	{
		return IO_FS::Dir($dirs)->create();
	}

//FIXME: НЛО прилетео и написало это:
	static $ifs_cache = array();

	/**
	 * Создает массив вида ключ=>значение на основании переданного источника
	 *
	 * @param string|iterable $source
	 *
	 * @return array
	 */

	static function items_for_select($s)
	{
		if (is_string($s)) {
			if (isset(self::$ifs_cache[$s])) {
				return self::$ifs_cache[$s];
			}
		}
		$rc = self::items_for_select_generate($s);
		if (is_string($s)) {
			self::$ifs_cache[$s] = $rc;
		}
		return $rc;
	}

	static function items_for_select_generate($s)
	{

		if (Core_Types::is_callable($s)) {
			$s = Core::invoke($s);
		}

		if (is_string($s)) {
			if ($m = Core_Regexps::match_with_results('/^:(.+)$/', $s)) {
				$method = trim($m[1]);
				$s = CMS::$current_controller->$method();
			}
		}
		if (Core_Types::is_iterable($s)) {
			$items = array();
			foreach ($s as $k => $v) {
				if ($v == '' && (is_string($k) && Core_Regexps::match('/^(var|db|orm)/', $k))) {
					$items += self::items_for_select($k);
				} elseif ($v instanceof DB_ORM_Mapper) {
					$items += self::items_for_select($v->select());
				} else {
					if ($v instanceof DB_ORM_Entity) {
						$items[$v->id()] = $v;
					} else {
						if (is_int($k) && (Core_Types::is_callable($v) || (is_string($v) && Core_Regexps::match('/^(var|db|orm)/', $v)))) {
							$items += self::items_for_select($v);
						} else {
							$items[$k] = $v;
						}
					}
				}
			}
			return $items;
		} else {
			if ($m = Core_Regexps::match_with_results('/^var:(.+)$/', $s)) {
				return self::get_var_value($m[1]);
			} else {
				if ($m = Core_Regexps::match_with_results('/^orm:(.+)$/', $s)) {
					$items = array();
					foreach (self::orm()->downto($m[1]) as $row)
						$items[$row->id] = $row;
					return $items;
				} else {
					if ($m = Core_Regexps::match_with_results('/^(.+)::(.+)$/', $s)) {
						$class = str_replace('.', '_', trim($m[1]));
						$method = trim($m[2]);
						$ref = new ReflectionMethod($class, $method);
						return $ref->invoke(null);
					} else {
						if ($m = Core_Regexps::match_with_results('/^db:([a-z0-9_]+)(.*)$/i', $s)) {
							$table = $m[1];
							$s = $m[2];
							$value = 'id';
							$title = 'title';
							$query = 'select';
							if ($m = Core_Regexps::match_with_results('/^->([a-z0-9_]+)(.*)$/', $s)) {
								$query = $m[1];
								$s = $m[2];
							}
							if ($m = Core_Regexps::match_with_results('/^\((.+),(.+)\)$/', $s)) {
								$value = $m[1];
								$title = $m[2];
							}
							$rows = DB_SQL::db()->$table->$query->run();
							$items = array();
							foreach ($rows as $row)
								$items[$row->$value] = $row->$title;
							return $items;
						} else {
							return array();
						}
					}
				}
			}
		}
	}

	/**
	 * Сортирует объекты в галерее в соответствии с установленным порядком
	 *
	 * @param array $source
	 *
	 * @return array
	 */
	static function gallery_sort(&$ar)
	{
		uasort($ar, array('CMS', 'gallery_sort_cb'));
		return $ar;
	}

	/** --------------------------------------------------------------- */

	/**
	 * Переводит дату из строкового представления в timestamp
	 *
	 * @param string $source
	 *
	 * @return int
	 */
	static function s2date($in)
	{
		return Time_DateTime::s2date($in);
	}

	static function validate_dmy(&$d, &$m, &$y)
	{
		$d = (int)$d;
		if ($d > 31) {
			$d = 1;
		}
		if ($d < 10) {
			$d = "0$d";
		}
		$m = (int)$m;
		if ($m > 12) {
			$m = 1;
		}
		if ($m < 10) {
			$m = "0$m";
		}
		$y = (int)$y;
		if ($y <= 20) {
			$y = 2000 + $y;
		}
		if ($y >= 21 && $y < 100) {
			$y = 1900 + $y;
		}
		if ($y < 1000) {
			$y = "0$y";
		}
	}

	static function validate_2d($s)
	{
		$s = trim(Core_Regexps::replace('{[^\d]+}', '', $s));
		$s = substr($s, 0, 2);
		while (strlen($s) < 2)
			$s = "0$s";
		return $s;
	}

	/**
	 * Переводит дату из строкового представления в формат SQL DATE
	 *
	 * @param string $source
	 *
	 * @return string
	 */
	static function s2sqldate($in)
	{
		return Time_DateTime::s2sqldate($in);
	}

	/**
	 * Переводит дату/время в timestamp
	 *
	 * @param string|int $source
	 *
	 * @return int
	 */
	static function datetime2timestamp($time)
	{
		return Time_DateTime::datetime2timestamp($time);
	}

	/**
	 * Суммирует дату/время с секундами
	 *
	 * @param string $datetime
	 * @param int    $sec
	 *
	 * @return string
	 */
	static function datetime_add($datetime, $sec)
	{
		return Time_DateTime::datetime_add($datetime, $sec);
	}

	/**
	 * Форматирует SQL DATE/DATETIME в соответствии с переданным форматом
	 *
	 * @param string     $format
	 * @param string|int $time
	 *
	 * @return string
	 */
	static function sqldateformat($format, $time)
	{
		return Time_DateTime::sqldateformat($format, $time);
	}

	/**
	 * Форматирует дату/время в соответствии с переданным форматом
	 *
	 * @param string     $format
	 * @param string|int $time
	 *
	 * @return string
	 */
	static function date($format, $time)
	{
		return self::sqldateformat($format, $time);
	}

	/**
	 * Возвращает дату на "человеческом" языке - строку вида "11 ноября 2011".
	 *
	 * @param string|int $time
	 *
	 * @return string
	 */
	static function date_in_human_language($time)
	{
		$time = self::datetime2timestamp($time);
		return self::lang()->_common->date_in_human_language($time);
	}

	/**
	 * Возвращает timestamp, соответствующий началу сегодняшних суток
	 *
	 * @return int
	 */
	static function today()
	{
		return Time::now()->setTime(0, 0, 0)->ts;
	}

	/** --------------------------------------------------------------- */

	/**
	 * Возвращает URI, приведенный в соответствие с текущим местоположением админа
	 *
	 * @param string $path
	 *
	 * @return string
	 */
	static function admin_path($path = '')
	{
		$path = trim(trim($path, '/'));
		$admin = CMS_Admin::path();
		if ($path == '') {
			if ($admin == '') {
				return '/';
			}
			return "/$admin/";
		}
		return "/$admin/$path/";
	}

	/**
	 * Определяет, является ли данный HTTP-запрос запросом к админу
	 *
	 * @param WebKit_HTTP_Request $request
	 *
	 * @return boolean
	 */
	static function is_admin_request($request)
	{
		if (CMS_Admin::$host && CMS_Admin::$host != $request->host) {
			return false;
		}
		if (strpos($request->urn, self::admin_path()) === 0) {
			return true;
		}
		return false;
	}

	/**
	 * @param string $hash
	 * @param string $password
	 *
	 * @return boolean
	 */
	static function check_password($hash, $password)
	{
		if (md5($password) == $hash) {
			return true;
		}
		return false;
	}

	/**
	 * @param string|false $path
	 */
	static function views_path($path = false)
	{
		return Templates::get_path($path);
		// return self::$views_path.($path?"/$path":"");
	}

	/**
	 * @param string|false $path
	 */
	static function app_path($path = false)
	{
		return self::$app_path . ($path ? "/$path" : "");
	}

	/**
	 * @param string|false $path
	 */
	static function tao_path($path = false)
	{
		return self::$taopath . ($path ? "/$path" : "");
	}

	/**
	 * @param string $name
	 */
	static function view($name)
	{
		return Templates::get_path($name);
	}

	static function tao_view($name = false)
	{
		$path = self::$views_path;
		if ($name) {
			$path .= "/{$name}";
		}
		return $path;
	}

	/**
	 * @param string $name
	 */
	static function app_view($name)
	{
		return self::$app_path . "/views/$name";
	}

	/**
	 * @param string $name
	 * @param string $ext
	 */
	static function stdfile($name)
	{
		return self::$assets_dir . "/$name";
	}

	/**
	 * @param string $name
	 * @param string $ext
	 */
	static function stdfile_url($name)
	{
		return "/" . self::$assets_dir . "/$name";
	}

	/**
	 * @param string $name
	 */
	static function stdstyle($name, $replace = false)
	{
		if (isset(self::$i_stdstyles[$name])) {
			return '';
		}
		self::$i_stdstyles[$name] = true;
		$url = self::stdfile_url("styles/$name");
		return "<link rel=\"stylesheet\" type=\"text/css\" href=\"$url\" />";

	}

	/**
	 * @param string $name
	 */
	static function stdscript($name, $replace = false)
	{
		if (isset(self::$i_stdscripts[$name])) {
			return '';
		}
		self::$i_stdscripts[$name] = true;
		$url = self::stdfile_url("scripts/$name");
		return "<script type=\"text/javascript\" src=\"$url\"></script>";

	}

	/**
	 * @param string $filename
	 *
	 * @return string
	 */
	static function file_url($file)
	{
		if ($m = Core_Regexps::match_with_results('{^\./(.+)$}', $file)) {
			return '/' . $m[1];
		}
		return '#';
	}

	/**
	 * @param string $tpl
	 * @param array  $parms
	 * @param string $layout
	 *
	 * @return string
	 */
	static function render_mail($tpl, $parms, $layout = 'mail')
	{
		return self::render($tpl, $parms, $layout);
	}

	/**
	 * @param string|false $uri
	 */
	static function process_navigation($uri = false)
	{
		if (!empty(self::$navigation)) {
			return self::$navigation;
		}
		if (!$uri) {
			$uri = WS::env()->request->path;
		}
		Core::load(self::$nav_module);
		self::$navigation = Core::make(self::$nav_module);
		self::$navigation->process($uri);
		if (method_exists(self::$navigation, 'layout')) {
			self::$navigation->layout(CMS::layout_view());
		}

	}

	/**
	 * @param string $vars
	 *
	 * @return boolean
	 */
	static function check_globals_or($s)
	{
		if (self::$globals['full']) {
			return true;
		}
		Core::load('Text');
		foreach (Text::Tokenizer($s, ',') as $item) {
			$item = trim($item);
			if ($item != '') {
				if (self::$globals[$item]) {
					return true;
				}
			}
		}
		return false;
	}

	/**
	 * @param array $matches
	 *
	 * @return string
	 */
	static function abs_refs_cb($m)
	{
		$s = $m[1];
		$ref = trim($m[2]);
		if (!Core_Regexps::match('{^http://}', $ref)) {
			$ref = ltrim($ref, '.');
			$ref = ltrim($ref, '/');
			$ref = "http://" . CMS::host() . '/' . $ref;
		}
		return "<a$s" . "href=\"$ref\"";
	}

	/**
	 * @param string $name
	 *
	 * @return mixed
	 */
	static function get_var_value($name)
	{
		$site = false;
		if ($m = Core_Regexps::match_with_results('{^(.+)/([^/]+)$}', $name)) {
			$name = trim($m[1]);
			$site = trim($m[2]);
			if ($site == '*') {
				$site = CMS_Admin::site();
			}
		}
		return CMS::vars()->get($name, $site);
	}

	/**
	 * @param array $arg1
	 * @param array $arg2
	 *
	 * @return int
	 */
	static function gallery_sort_cb($a, $b)
	{
		if ($a['ord'] > $b['ord']) {
			return 1;
		}
		if ($a['ord'] < $b['ord']) {
			return -1;
		}
		return 0;
	}

	/**
	 * @param WebKit_Views_TemplateView|false $view
	 *
	 * @return WebKit_Views_TemplateView|false
	 */
	static function layout_view($view = false)
	{
		if ($view) {
			self::$i_view = $view;
		}
		return self::$i_view;
	}

	/**
	 */
	static function use_styles()
	{
		$args = func_get_args();
		foreach ($args as $arg)
			self::$i_view->use_styles($arg);
	}

	/**
	 */
	static function use_helper($name, $helper)
	{
		Templates_HTML::use_helper($name, $helper);
	}

	/**
	 */
	static function field_type($name, $module)
	{
		self::$fields_types[$name] = $module;
	}

	/**
	 */
	static function use_scripts()
	{
		$args = func_get_args();
		foreach ($args as $arg)
			self::$i_view->use_scripts($arg);
	}

	/**
	 * @return string
	 */
	static function host()
	{
		if (self::$host) {
			return self::$host;
		}
		return $_SERVER['HTTP_HOST'];
	}

	/**
	 * @return string
	 */
	static function user_login()
	{
		$headers = getAllHeaders();
		$auth = trim($headers['Authorization']);
		if ($m = Core_Regexps::match_with_results('{^Basic\s+(.+)$}', $auth)) {
			$auth = Core_Strings::decode64($m[1]);
			if ($m = Core_Regexps::match_with_results('{^([^:]+):}', $auth)) {
				$login = trim($m[1]);
				if ($login != '') {
					return $login;
				}
			}
		}
		return false;
	}

	/**
	 * @return array
	 */
	static function cfg()
	{
		return self::$cfg;
	}

	/**
	 * @param Component_Pages_Entity|string $page
	 * @param array                         $parms
	 * @param string                        $layout
	 *
	 * @return Mail_Message
	 */
	static function page2mail($page, $parms = array(), $layout = 'mail')
	{
		if (!is_object($page)) {
			$page = Component_Pages_Entity::find_by_url($page);
		}
		if (!$page) {
			return false;
		}
		if (!$page->isactive) {
			return false;
		}
		Core::load('Mail.Message');
		$mail = Mail::Message()->content_type('text/html; charset=utf-8');
		$subject = trim($page->parms['subject']);
		if ($subject != '') {
			$mail->subject($subject);
		}
		$from = trim($page->parms['from']);
		if ($from != '') {
			$mail->from($from);
		}
		$content = $page->content;
		foreach ($parms as $parm => $value)
			$content = str_replace("%{" . $parm . "}", $value, $content);
		$body = self::render_mail('empty', array('content' => $content));
		$body = self::abs_refs($body);
		$mail->html($body);
		return $mail;
	}

}

class CMS_PlugObject
{

	public function __get($name)
	{
		return false;
	}

	public function __set($name, $value)
	{
		return false;
	}

	public function __call($name, $args)
	{
		return false;
	}

}

class CMS_ObjectsBuilder
{

	public function __get($name)
	{

		if (isset(CMS::$created_objects[$name])) {
			return CMS::$created_objects[$name];
		}

		$class = 'CMS_PlugObject';

		if (isset(CMS::$registered_objects[$name])) {
			if (is_object(CMS::$registered_objects[$name])) {
				 CMS::$created_objects[$name] = CMS::$registered_objects[$name];

			} else {
				$module = trim(CMS::$registered_objects[$name]);
				Core::load($module);
				CMS::$created_objects[$name] = Core::make($module);
			}
		} else {
			CMS::$created_objects[$name] = Core::make($class);
		}

		return CMS::$created_objects[$name];
	}

}

class CMS_Exception extends Exception
{
}


