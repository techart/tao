<?php
/// <module name="CMS.Handlers" maintainer="gusev@techart.ru" version="0.0.0">

//TODO: refactoring

Core::load('WS.Auth');

/// <class name="CMS.Handlers" stereotype="module">
///   <implements interface="Core.ModuleInterface" />
///   <depends supplier="CMS.StatusHandler" stereotype="creates" />
///   <depends supplier="CMS.StdControlsHandler" stereotype="creates" />
///   <depends supplier="CMS.ActionHandler" stereotype="creates" />
///   <depends supplier="CMS.AuthModule" stereotype="creates" />
///   <depends supplier="CMS.Auth.Basic.Handler" stereotype="creates" />
class CMS_Handlers implements Core_ModuleInterface {
///   <constants>
	const MODULE  = 'CMS.Handlers';
	const VERSION = '0.0.0';
///   </constants>

///   <protocol name="building">

///   <method scope="class" name="StdControlsHandler" returns="CMS.StdControlsHandler">
///     <body>
	static function StdControlsHandler(WS_ServiceInterface $application) { return new CMS_Handlers_StdControlsHandler($application); }
///     </body>
///   </method>

///   <method scope="class" name="ActionHandler" returns="CMS.ActionHandler">
///     <body>
	static function ActionHandler() { return new CMS_Handlers_ActionHandler(); }
///     </body>
///   </method>

///   <method scope="class" name="AuthModule" returns="CMS.AuthModule">
///     <body>
	static function AuthModule() {
		return new CMS_Handlers_AuthModule();
	}
///     </body>
///   </method>

///   </protocol>


}
/// </class>

/// <class name="CMS.AuthModule" extends="WebKit.Auth.AbstractAuthModule">
class CMS_Handlers_AuthModule implements WS_Auth_AuthModuleInterface {

/// <protocol name="performing">
///   <method name="authenticate" returns="Data.Tree|false">
///     <args>
///       <arg name="login" type="string" />
///       <arg name="password" type="string" />
///     </args>
///     <body>
	public function authenticate($login,$password) {
		$login = trim($login);
		$password = trim($password);

		if ($login!=''&&$password!='') {
			$user = new stdClass();
			$user->login = $login;
			$user->password = $password;
			return $user;
		}

		return false;
	}
///     </body>
///   </method>
/// </protocol>

}
/// </class>


/// <class name="CMS.StdControlsHandler" extends="WebKit.Handlers.HTTPStatusHandler">
///   <depends supplier="CMS.Admin" stereotype="uses" />
///   <depends supplier="CMS.Protect" stereotype="uses" />
///   <depends supplier="WebKit.Controller.NoRouteException" stereotype="catches" />
class CMS_Handlers_StdControlsHandler extends WS_MiddlewareService {

/// <protocol name="creating">
///   <method name="__construct">
///     <args>
///       <arg name="application" type="WebKit.AbstractHandler" />
///     </args>
///     <body>
	public function __construct(WS_ServiceInterface $application) {
		parent::__construct($application);
	}
///     </body>
///   </method>
/// </protocol>

/// <protocol name="performing">
///   <method name="process" returns="Iterator">
///     <args>
///       <arg name="env" type="WebKit.Environment" />
///       <arg name="response" type="WebKit.HTTP.Response" />
///     </args>
///     <body>
	public function run(WS_Environment $env) {
		$response = $env->response;
		
		$uri = $env->request->urn;
		$rs = Events::call('cms.actions.dispatch',$uri);
		if (!is_null($rs)) return $rs;
		
		if ($m = Core_Regexps::match_with_results('{^/cms-actions/(.+)}',$uri)) {
			$action = preg_replace('{\?.+$}','',$m[1]);
			if ($m = Core_Regexps::match_with_results('{^files/(.+)/(\d+)/$}',$action)) {
				$file = CMS::stdfile(trim($m[1]));
				if (!IO_FS::exists($file)) {
					return $response->status(Net_HTTP::NOT_FOUND);
				}
				return Net_HTTP::Download($file);
			}
			if ($m = Core_Regexps::match_with_results('{^subsite/(.+)}',$action)) {
				$site = $m[1];
				CMS_Admin::set_site($site);
				$path = CMS::$admin;
				header("location: /$path/");
				die;
			}
			if ($m = Core_Regexps::match_with_results('{^help/([^/]+)/([^/]+)/(.+)/$}',$action)) {
				$stddir = CMS::$taopath.'/views/help';
				$file = "$stddir/en/_notfound";
				if ($m[1]=='_app') {
					$dir = trim($m[2]);
					$f = trim($m[3]);
					$f = CMS::app_path("help/$dir/$f");
					if (file_exists($f)) $file = $f;
				}
				else {
					$lang = trim($m[1]);
					$component = trim($m[2]);
					$help = trim($m[3]);
					$dir = $component=='_std'? $stddir : CMS::app_path("components/$component/help");
					$file = "$dir/$lang/$help";
					if (!file_exists($file)) {
						$file = "$dir/$help";
						if (!file_exists($file)) {
							$file = "$dir/ru/$help";
							if (!file_exists($file)) {
								$file = "$stddir/$lang/_notfound";
								if (!file_exists($file)) "$stddir/en/_notfound";
							}
						}
					}
				}
				$content = CMS::parse_wiki(file_get_contents($file));
				include("$stddir/screen.phtml");
				die;
			}
		}
		if (md5($uri)=='5dc55ea23076fc98474b89cfd51ef6f2') die($uri.' ok');
		
//FIXME: move to status middleware
		try {
			$r = $this->application->run($env);
			if ($r instanceof Net_HTTP_Response && $r->status->code == 404) {
				return $this->not_found($env->request->urn, $env, $r);
			}
			return $r;
		} catch (WebKit_Controller_NoRouteException $e) {
			return $this->not_found($e->url, $env, $response);
		}
	}
///     </body>
///   </method>
/// </protocol>

	protected function not_found($uri, $env , $r) {
			if ($m = Core_Regexps::match_with_results('{^([^?]+)\?}',$uri)) $uri = $m[1];
			if ($m = Core_Regexps::match_with_results('{^/digital-protect/(.*)}',$uri)) {
				Core::load('CMS.Protect');
				CMS_Protect::draw($m[1]);
				die;
			}
			if ($m = Core_Regexps::match_with_results('{^/check-digital-protect/([^/]+)/(.*)}',$uri)) {
				Core::load('CMS.Protect');
				CMS_Protect::check($m[1],$m[2]);
				die;
			}
			return $r;
	}

}
/// </class>

class CMS_Handlers_Static extends WS_MiddlewareService {

	public function run(WS_Environment $env) {
		if (isset($_SERVER['REQUEST_URI']) && $uri = $_SERVER['REQUEST_URI']) {
			if (strpos($uri,'/component-static/')===0) {
				$uri = substr($uri,18);
				if ($m = Core_regexps::match_with_results('{^([^/]+)/(.+(css|js|gif|jpg|png))/(\d+)/$}',$uri)) {
					$component = $m[1];
					$file = $m[2];
					$path = CMS::component_dir($component,$file);
					if (IO_FS::exists($path)) {
						Core::load('WS.Adapters');
						$adapter = WS_Adapters::apache();
						$adapter->process_response(Net_HTTP::Download($path,true));
						exit();
					}
				}
			}
		}
		return $this->application->run($env);
	}

}

class CMS_Handlers_Configure extends WS_MiddlewareService {

	public function run(WS_Environment $env) {
		CMS::$env = $env;
		CMS::$page = $env;
		$env->cms = new stdClass();
		Core::load('Templates.HTML');
		$env->meta = Templates_HTML::meta();
		$env->mappers = CMS::$mappers;
		$env->auth = new stdClass();
		$env->auth->user = false;
		CMS::$cfg = $env->config;
		if ($env->db->default) {
			CMS::$db = $env->db->default;
			//if (class_exists('DB_SQL', false))
				DB_SQL::db()->connect(CMS::$db);
		}
		Templates_HTML::use_helper('fields','CMS.Fields.Helper');
		Templates_HTML::use_helper('cms','CMS.Helper');
		Templates::option('templates_root', array_merge(Templates::option('templates_root'), array(CMS::$views_path)));
		return $this->application->run($env);
	}
}

class CMS_Handlers_RestrictedRealms extends WS_MiddlewareService {

	public function run(WS_Environment $env) {
		if (!empty($env->config->restricted))
			$restricted = $env->config->restricted;
		else {
			Core::load('Config.DSL');
			$restricted = (array) Config_DSL::load('../config/restricted.php')->restricted;
		}
		$erealms = isset($env->restricted_realms)? $env->restricted_realms : array();
		foreach($restricted as $realm => $realm_data) {
			$erealms[$realm] = (array) $realm_data;
			CMS::$restricted_realms[$realm] = (array) $realm_data;
		}
		$env->restricted_realms = $erealms;
		return $this->application->run($env);
	}

}

class CMS_Handlers_RealmAuth extends WS_MiddlewareService {

	static protected $realms = array();

	public static function access($realm, $extra_auth_callback = null) {

		if (isset(self::$realms[$realm]) && self::$realms[$realm])
			return self::$realms[$realm];

		$data = false;
		if (isset(CMS::$restricted_realms[$realm])) {
			$data = CMS::$restricted_realms[$realm];
		}
		else {
			return self::$realms[$realm] = array('empty' => true);
		}
		
		if (!$data) return self::$realms[$realm] = false;
		
		if ($realm==CMS::$admin_realm) {
			CMS::$in_admin = true;
		}

		/*if (isset($data['layout'])) {
			$this->use_layout($data['layout']);
		}*/
			
		if (isset($data['page_404'])) {
			CMS::$page_404 = $data['page_404'];
		}
			
		if (isset($data['navigation_var'])) {
			Core::load(CMS::$nav_module);
			Core_Types::reflection_for(CMS::$nav_module)->setStaticPropertyValue('var',$data['navigation_var']);
		}

		$user = false;
		if (isset($data['auth_type'])&&$data['auth_type']=='basic'||!isset($data['auth_type'])) {
			$user = WS::env()->admin_auth->user;
		}

		if ($user) {
			$client = false;
			$access = false;
			$mp = false;

			self::passwords($data, $user, $client, $access, $mp);

			Core::load('Net.HTTP.Session');
			$session = Net_HTTP_Session::Store();
			if (!$access&&isset($session['auth_url_access'])&&$session['auth_url_access']) {
				$access = true;
				$mp = $session['auth_url_parms'];
			}

			if (!$access&&isset($data['auth_url'])) {
				Core::load('Net.Agents.HTTP');
				$url = $data['auth_url'];
				$agent = Net_HTTP::Agent();
				$res = $agent->with_credentials($user->login,$user->password)->send(Net_HTTP::Request($url));
				if ($res->status->code=='200') {
					$r = trim($res->body);
					if ($r=='ok'||$r=='ok:') {
						$access = true;
					}
					else if ($m = Core_Regexps::match_with_results('{^ok:(.+)$}',$r)) {
						$access = true;
						$mp = trim($m[1]);
					}
				}
				if ($access) {
					$session['auth_url_access'] = true;
					$session['auth_url_parms'] = $mp;
				}
			}


			if (!$access) {
				$args = array($user->login,$user->password,$realm);
				if (Core_Types::is_callable($extra_auth_callback))
					$extra_auth = Core::invoke($extra_auth_callback, $args);
				else
					$extra_auth = self::extra_auth($args[0],$args[1],$args[2]);
				if ($extra_auth||Core_Types::is_iterable($extra_auth)) {
					$mp = $extra_auth;
					$access = true;
				}
				else return self::$realms[$realm] = false;
			}

			$auth_parms = self::auth_parms($mp, $client);

			if ($access) return self::$realms[$realm] = array('data' => $data, 'auth_parms' => $auth_parms);	
		}
		else {
			return self::$realms[$realm] = false;
		}
		return self::$realms[$realm] = false;
	}

	public function run(WS_Environment $env) {
		//TODO: config for realms (always, url, domain, callback, ...)
		self::access(CMS::$default_auth_realm);
		return $this->application->run($env);
	}

	static protected function auth_parms($mp, $client) {
		$auth_parms = array();
		if ($mp) {
			if (is_string($mp)) $mp = explode(',',$mp);
			if (Core_Types::is_iterable($mp)) foreach($mp as $_mp) {
				$_mp = trim($_mp);
				if ($_mp!='') {
					$_v = true;
					if ($m = Core_Regexps::match_with_results('{^([^=]+)=(.+)$}',$_mp)) {
						$_mp = trim($m[1]);
						$_v  = trim($m[2]);
					}
					
					if ($_mp=='lang') {
						CMS::site_set_lang($_v);
					}
					
					if ($_mp=='admin_sites') {
						$_asites = explode('|',$_v);
						$_v = array();
						$_las = '__';
						foreach($_asites as $_asite) {
							$_asite = trim($_asite);
							if ($_asite!='') {
								$_v[$_asite] = $_asite;
								$_las = $_asite;
							}
						}
						if (CMS::admin()) {
							if (!isset($_v[CMS_Admin::site()])) {
								header("location: /cms-actions/subsite/$_las");
								die;
							}
						}
					}
					
					CMS::$globals[$_mp] = $_v;
					$auth_parms[$_mp] = $_v;
				}
			}
			if ($client) CMS::$globals['full'] = false;
		}
		return $auth_parms;
	}

	static protected function passwords($data, $user, &$client, &$access, &$mp) {
		foreach($data['passwords'] as $p) {
			$mp = false;
			$p = trim($p);
			if ($m = Core_Regexps::match_with_results('{^([^/]+)/(.*)$}',$p)) {
				$p = trim($m[1]);
				$mp = trim($m[2]);
			}
			if ($m = Core_Regexps::match_with_results('{^([^:]+):(.+)$}',$p)) {
				$login = trim($m[1]);
				$password = trim($m[2]);
			
				if (CMS::is_local()&&CMS::$disable_local_auth) {
					$clogin = trim(CMS::$cfg->client->login);
					if ($user->login==$clogin) $client = true;
					$access = true;
					break;
				}
			
				if ($user->login==$login && md5($user->password)==$password) {
					$access = true;
					break;
				}
			}
		}
	}

	static protected function extra_auth($login,$password,$realm) {
		if (is_callable(CMS::$extra_auth)) return call_user_func(CMS::$extra_auth,$login,$password,$realm);
		return false;
	}
	

}


/// <class name="CMS.ActionHandler" extends="WebKit.AbstractHandler">
///   <depends supplier="CMS.Controller.Index" stereotype="uses" />
///   <depends supplier="CMS.Controller.AdminIndex" stereotype="uses" />
class CMS_Handlers_ActionHandler implements WS_ServiceInterface {

	protected $env;
	protected $response;

/// <protocol name="performing">
///   <method name="process" returns="Iterator">
///     <args>
///       <arg name="env" type="WebKit.Environment" />
///       <arg name="response" type="WebKit.HTTP.Response" />
///     </args>
///     <body>
	public function run(WS_Environment $env) {
		$response = $env->response;
		$this->env = $env;
		$this->response = $response;
		$response['X-Powered-CMS'] = 'Techart CMS '.CMS::VERSION;
		
		foreach(CMS::$plugins_before_dispatch as $class => $method) {
			$r = new ReflectionMethod($class,$method);
			$r->invoke(NULL);
		}

		if (is_callable(CMS::$action_handler_process)) {
			$rc = call_user_func(CMS::$action_handler_process,$this,$env,$response);
		}
		else {
			$rc = $this->process_app($env,$response);
		}
		//foreach($response as $h => $v) var_dump($h,$v);
		//var_dump($response->status);die;
		return $rc;
	}
///     </body>
///   </method>



/// <protocol name="performing">
///   <method name="process_app" returns="Iterator">
///     <args>
///       <arg name="env" type="WebKit.Environment" />
///       <arg name="response" type="WebKit.HTTP.Response" />
///     </args>
///     <body>
	public function process_app(WS_Environment $env, $response) {

		$uri = $env->request->urn;
		$original_uri = $uri;
		CMS::$original_uri = $uri;
		CMS::$site = CMS::$defsite;

		if (isset(CMS::$sites[CMS::$defsite]['page_main'])) CMS::$page_main = CMS::$sites[CMS::$defsite]['page_main'];

		$_defdata = false;
		if (isset(CMS::$sites[CMS::$defsite])) $_defdata = CMS::$sites[CMS::$defsite];

		if (isset(CMS::$sites)) foreach(CMS::$sites as $site => $data) {
			$_host = isset($data['host'])?trim($data['host']):'';
			$_prefix = isset($data['prefix'])?trim($data['prefix']):'';
			if ($_host!=''||$_prefix!='') {
				$_bhost = false;
				$_bprefix = false;
				$_uri = $uri;
				if ($_prefix!='') {
					if ($m = Core_Regexps::match_with_results('{^/'.$_prefix.'/(.*)$}',$uri)) {
						$_uri = '/'.$m[1];
						$_bprefix = true;
					}
					else continue;
				}
				if ($_host!='') {
					if ($env->request->host==$_host) $_bhost = true;
					else if ($_host[0]=='{') {
						if (Core_Regexps::match($_host,$env->request->host)) $_bhost = true;
						else continue;
					}
					else continue;
				}
				if ($_bprefix||$_bhost) {
					CMS::$site = $site;
					if ($_bprefix) CMS::$site_prefix = '/'.$_prefix;
					$uri = $_uri;
					$env->request->uri($uri);
					$_defdata = $data;
					break;
				}
			}
		}

		if ($_defdata) {
			if (isset($_defdata['page_main'])) CMS::$page_main = $_defdata['page_main'];
			if (isset($_defdata['page_404'])) CMS::$page_404 = $_defdata['page_404'];
			if (isset($_defdata['layout'])) CMS::$force_layout = $_defdata['layout'];
		}

		if (CMS::$db) {
			$head = CMS::vars()->get('head');
			if (isset($head['meta.title'])) $env->meta->title($head['meta.title']);
			if (isset($head['meta.description'])) $env->meta->description($head['meta.description']);
			if (isset($head['meta.keywords'])) $env->meta->keywords($head['meta.keywords']);
		}



		$curi = $uri;
		if ($m = Core_Regexps::match_with_results('/^([^\?]+)\?/',$curi)) $curi = $m[1];


		//foreach(CMS::$plugins_before_dispatch as $class => $method) {
		//	$r = new ReflectionMethod($class,$method);
		//	$r->invoke(NULL);
		//}

		$use_layout = false;

		// Просмотр всех мапперов зарегистрированных в системе
		foreach(CMS::$mappers as $name => $mapper) {
			// Если срабатывает маппер
			if ($route = $mapper->route($env->request)) {

				CMS::$current_mapper = $mapper;
				CMS::$current_component_name = $name;
				
				if ($route instanceof Net_HTTP_Response) return $route;

				// Имя подключаемого модуля
				$controller_module_name = 'Component.'.$name.'.Controller';

				// Имя контроллера по умолчанию
				$controller_name = Core_Strings::replace($controller_module_name,'.','_');

				// Имя действитя по умолчанию
				$action_name = 'index';

				$do_load_controllers = true;

				if ($route===true) $route = array(
					'controller' => $controller_name,
					'action' => 'index',
				);

				if (is_array($route)) {
					$_route = WebKit_Controller::Route();
					$_route->merge($route);
					$route = $_route;
				}

				if (!isset($route['action'])) $route['action'] = 'index';

				// Если маппер вернул нестандартное имя контроллера
				if (isset($route['controller'])) $controller_name = $route['controller'];

				// Если маппер вернул нестандартное имя действия
				if (isset($route['action'])) $action_name = $route['action'];

				// Если маппер не велел загружать модуль с конроллером (загрузит сам)
				if (isset($route['no_load'])) $do_load_controllers = false;

				// Загружаем модуль с контроллером
				if ($do_load_controllers) {
					if (strpos($controller_name,'.')===false&&strpos($controller_name,'_')===false) $controller_name = 'Component.'.$name.'.'.$controller_name;
					if (strpos($controller_name,'.')!==false) $controller_module_name = $controller_name;
					Core::autoload($controller_module_name);
				}
				// Получаем экземпляр контроллера
				CMS::$current_controller_name = $controller_name;
				//$controller = Core_Types::reflection_for($controller_name)->newInstance($env, $response);
				$controller = Core::make($controller_name, $env, $response);

				if ($use_layout) {
					if (!property_exists($controller,'layout')) $controller->use_layout($use_layout);
					else if (!$controller->layout) $controller->use_layout($use_layout);
				}

				if (!CMS::$print_version&&is_string(CMS::$force_layout)) $controller->use_layout(CMS::$force_layout);

				CMS::$current_controller = $controller;

				// Запускаем контроллер с переданными аргументами
				$rc = $controller->dispatch($route);
				return $rc;
			}
		}

		// Индексный контролер
		if ($curi=='/') {
			$_layout = is_string(CMS::$force_layout)? CMS::$force_layout : 'work';
			return $this->run_index_controller(CMS::$page_main,$_layout);
		}


		// Индексный контролер админской части
		if (isset(CMS::$restricted_realms[CMS::$admin_realm])) {
			$_data = CMS::$restricted_realms[CMS::$admin_realm];
			$_uri = isset($_data['index'])? $_data['index'] : CMS::admin_path();
			$_layout = isset($_data['layout'])? $_data['layout'] : 'admin';
			$_page = isset($_data['page_main'])? $_data['page_main'] : 'admin_main';
			if (CMS::is_admin_request($env->request)) {
				if (!isset(WS::env()->not_found)) 
					WS::env()->not_found = Core::object();
				WS::env()->not_found->layout = $_layout;
				WS::env()->not_found->static_file = '404.html';
			}
			if ($_uri==$curi) return $this->run_admin_index_controller($_page,$_layout);
		}


		if (md5($uri)=='b0b94791138ef54aeb161e403329f827') die('cms');
		return Net_HTTP::not_found();
		//throw new WebKit_Controller_NoRouteException($uri);
	}
///     </body>
///   </method>

///   <method name="run_index_controller" returns="">
///     <args>
///       <arg name="view" type="string" />
///       <arg name="layout" type="string" default="work" />
///     </args>
///     <body>
	protected function run_index_controller($view,$layout='work') {
		Core::load(CMS::$index_controller);
		$controller = Core_Types::reflection_for(CMS::$index_controller)->newInstance($this->env, $this->response);
		return $controller->dispatch(WebKit_Controller::Route()->merge(array($view,$layout,'action'=>'index')));
	}
///     </body>
///   </method>

///   <method name="run_admin_index_controller" returns="">
///     <args>
///       <arg name="view" type="string" />
///       <arg name="layout" type="string" default="admin" />
///     </args>
///     <body>
	protected function run_admin_index_controller($view,$layout='admin') {
		Core::load(CMS::$admin_index_controller);
		$controller = Core_Types::reflection_for(CMS::$admin_index_controller)->newInstance($this->env, $this->response);
		return $controller->dispatch(WebKit_Controller::Route()->merge(array($view,$layout,'action'=>'index')));
	}
///     </body>
///   </method>
/// </protocol>

}
/// </class>

/// </module>
