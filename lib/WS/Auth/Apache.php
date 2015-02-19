<?php
/**
 * WS.Auth.Apache
 *
 * @package WS\Auth\Apache
 * @version 0.2.0
 */

Core::load('WS.Auth');

/**
 * @package WS\Auth\Apache
 */
class WS_Auth_Apache implements Core_ModuleInterface
{

	/**
	 * @param WS_ServiceInterface         $application
	 * @param WS_Auth_AuthModuleInterface $auth_module
	 *
	 * @return WS_Auth_Basic_Service
	 */
	public static function Service(WS_ServiceInterface $application, WS_Auth_AuthModuleInterface $auth_module)
	{
		return new WS_Auth_Basic_Service($application, $auth_module);
	}

	public static function ORMAuthModule($orm_name = null)
	{
		return new WS_Auth_Apache_ORMAuthModule($orm_name);
	}

	public static function RemoteAuthModule($url = null, $wrapper = null, $cache_key = null)
	{
		return new WS_Auth_Apache_RemoteAuthModule($url, $wrapper, $cache_key);
	}
}

class WS_Auth_Apache_Service extends WS_Auth_Service
{
	protected $request;

	/**
	 * Выполняет обработку запроса
	 *
	 * @param WS_Environment $env
	 *
	 * @return mixed
	 */
	public function run(WS_Environment $env)
	{
		$this->request = $env->request;

		if ($this->request->server('AUTH_TYPE')) {
			if (!$this->auth()) {
				return Net_HTTP::Response(Net_HTTP::FORBIDDEN);
			}
		}
		return $this->application->run($env);
	}

	protected function auth()
	{
		$enable = Core::if_null(Config::all()->apache_auth->enable, false);
		$enable_admin = Core::if_null(Config::all()->apache_auth->enable_admin, false);
		if (!$enable) {
			return true;
		}
		if (!empty(WS::env()->auth->user)) {
			return true;
		}
		$user = null;
		$login = $this->request->server('PHP_AUTH_USER');
		if (!$login) {
			$login = $this->request->server('REMOTE_USER');
		}
		if ($login) {
			$user = $this->auth_module->authenticate($login, null);
			if (!$user->isactive) {
				return false;
			}
		}
		if (!$user && !Core::is_cli()) {
			return false;
		} else {
			Events::call('ws.auth.apache.user',$user);
			WS::env()->auth->user = $user;
			WS::env()->auth->module = $this->auth_module;
			if ($enable_admin) {
				$admin_user = clone $user;
				$admin_user->password = $this->request->server('PHP_AUTH_PW');
				WS::env()->admin_auth->user = $admin_user;
			}
			$this->reset_default_auth();
			return true;
		}
	}

	protected function reset_default_auth()
	{
		CMS::$default_auth_realm = false;
	}
}

class WS_Auth_Apache_ORMAuthModule implements WS_Auth_AuthModuleInterface
{
	protected $orm_name;

	public function __construct($orm_name = null)
	{
		$this->orm_name = $orm_name ? $orm_name : 'users';
	}

	public function set_orm_name($name)
	{
		$this->orm_name = (string)$name;
		return $this;
	}

	public function get_orm_name()
	{
		return $this->orm_name;
	}

	/**
	 * @param string $login
	 * @param string $password
	 *
	 * @return mixed
	 */
	public function authenticate($login, $password)
	{
		$orm_name = $this->orm_name;
		$row = WS::env()->orm->{$orm_name}->login($login)->as_array()->only('id')->select_first();
		return WS::env()->orm->{$orm_name}->load($row['id']);
	}

}

class WS_Auth_Apache_RemoteAuthModule implements WS_Auth_AuthModuleInterface
{
	protected $url;
	protected $cache_key;
	protected $wrapper;
	protected static $users;

	public function __construct($url = null, $wrapper = null, $cache_key = null)
	{
		$this->url = $url ? $url : 'http://api.office.techart.ru/users.json';
		$this->wrapper = $wrapper ? $wrapper : 'WS.Auth.Apache.RemoteUserWrapper';
		$this->cache_key = $cache_key ? $cache_key : 'office:users';
	}

	public function get_url()
	{
		return $this->url;
	}

	public function set_url($url)
	{
		$this->url = $url;
		return $this;
	}

	public function get_cache_key()
	{
		return $this->cache_key;
	}

	public function set_cache_key($cache_key)
	{
		$this->cache_key = $cache_key;
		return $this;
	}

	public function get_wrapper()
	{
		return $this->wrapper;
	}

	public function set_wrapper($wrapper)
	{
		$this->wrapper = $wrapper;
		return $this;
	}

	public static function users()
	{
		return static::$users;
	}

	/**
	 * @param string $login
	 * @param string $password
	 *
	 * @return mixed
	 */
	public function authenticate($login, $password)
	{
		return $this->get_user_by_login($login);
	}

	public function get_user_by_login($login)
	{
		$users = $this->get_users();
		foreach ($users as $user) {
			if ($user->login == $login) {
				return $this->make_user($user);
			}
		}
		return null;
	}

	public function make_user($data)
	{
		return Core::make($this->wrapper, $data);
	}

	protected function get_users($active = true)
	{
		$users = array();
		foreach ($this->get_all_users() as $user) {
			if (!$active || $user->isactive) {
				$users[$user->id] = $user;
			}
		}
		return $users;
	}

	protected function get_all_users()
	{
		if (static::$users) {
			return static::$users;
		}
		static::$users = WS::env()->cache->get($this->cache_key);
		if (!static::$users) {
			static::$users = array();
			foreach ($this->fetch_users() as $user) {
				static::$users[$user->id] = $user;
			}
			WS::env()->cache->set($this->cache_key, static::$users);
		}
		return static::$users;
	}

	protected function fetch_users()
	{
		$users = array();
		try {
			$agent = Net_HTTP::Agent();
			$res = $agent->send(Net_HTTP::Request($this->url));
			$users = json_decode($res->body);
		} catch (Exception $e) {

		}
		return $users;
	}

}

class WS_Auth_Apache_RemoteUserWrapper extends stdClass
{
	public $groups = array();

	public function __construct($data = false)
	{
		$this->assign($data);
	}

	public function assign($data)
	{
		if (!empty($data)) {
			foreach ($data as $name => $value) {
				$name = trim($name);
				$this->{$name} = $value;
				if ($value) {
					if ($m = Core_Regexps::match_with_results('{^group_(.+)$}', $name)) {
						$group = $m[1];
						$this->groups[$group] = $group;
					}
				}
			}
		}
		return $this;
	}

	public function id()
	{
		return (int)$this->id;
	}
	
	public function check_access($access)
	{
		if (!$this->isactive) {
			return false;
		}
		if ($this->isadmin) {
			return true;
		}
		$group = trim($access);
		return isset($this->groups[$group]);
	}

}
