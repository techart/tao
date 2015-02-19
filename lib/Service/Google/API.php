<?php
/**
 * @package Service\Google\API
 */

Core::load('Cache');
Core::load('IO');

Core::load('Service.Google.API.Analytics');

class Service_Google_API implements Core_ConfigurableModuleInterface
{

	const VERSION = '0.1.1';

	static protected $cache;
	static protected $stdin;
	static protected $options = array('lib_path' => '../vendor/bitgandtter/google-api/', 'cache_path' => 'fs://../cache/google_api');

	public static function initialize(array $options = array())
	{
		self::options($options);
		if (!class_exists('Google_Client') && set_include_path(get_include_path() . PATH_SEPARATOR . self::option('lib_path') . 'src/')) {
			if (!@include_once('Google_Client.php')) {
				throw new Service_Google_API_ClientLibraryModuleNotFoundException('src/Google_Client.php', self::option('lib_path'));
			}
		}
		self::$cache = Cache::connect(self::option('cache_path'), 0);
		self::$stdin = IO::stdin();
	}

	public static function options(array $options = array())
	{
		Core_Arrays::deep_merge_update_inplace(self::$options, $options);
		return self::$options;
	}

	public static function option($name, $value = null)
	{
		if (is_null($value)) {
			return self::$options[$name];
		}
		return self::$options[$name] = $value;
	}

	public static function Analytics($console_app = true)
	{
		if (!@include_once('contrib/Google_AnalyticsService.php')) {
			throw new Service_Google_API_ClientLibraryModuleNotFoundException('src/contrib/Google_AnalyticsService.php', self::option('lib_path'));
		}

		if ($console_app === true) {
			$service = new Service_Google_API_Analytics(self::$cache, self::$stdin);
		} else {
			$service = new Service_Google_API_Analytics(self::$cache, null);
		}
		return $service;
	}

}

abstract class Service_Google_API_AbstractService
{

	protected $cache;
	protected $stdin;
	protected $current_client = null;
	protected $clients = array();

	protected $client_class_name = 'Service_Google_API_AbstractClient';
	protected $cache_subfolder = 'base';

	public function __construct($cache, $stdin)
	{
		$this->cache = $cache;
		$this->stdin = $stdin;
	}

	public function add_client($name, $auth_inf, $write_to_cache = false)
	{
		$this->clients[$name] = $this->make_client($name, $auth_inf);
		$this->current_client = & $this->clients[$name];
		$this->current_client->authorizate($this->stdin);
		$token = $this->current_client->get_token();
		if ($write_to_cache) {
			$this->write_to_cache($name, $token);
		}
		return $this->current_client;
	}

	public function use_client($name, $auth_inf, $token = null)
	{
		$this->clients[$name] = $this->make_client($name, $auth_inf);
		$this->current_client = & $this->clients[$name];

		if ($token == null) {
			if ($this->cache->has($this->cache_subfolder . ":" . $name)) {
				$token = $this->cache->get($this->cache_subfolder . ":" . $name);
			} else {
				$result = $this->current_client->authorizate($this->stdin);
				if (is_bool($result) !== true) {
					$token = $this->current_client->get_token();
					$this->write_to_cache($name, $token);

					$this->clients[$name] = $this->make_client($name, $auth_inf);
					$this->current_client = & $this->clients[$name];
				} else {
					die;
				}
			}
		}
		$this->current_client->prepare($token);
		return $this->current_client;
	}

	public function make_client($name, $auth_inf)
	{
		// creating new class $client_class_name
		$classname = $this->client_class_name;
		return new $classname($name, $auth_inf, $this);
	}

	public function write_to_cache($name, $token)
	{
		$this->cache->set($this->cache_subfolder . ":" . $name, $token);
	}

	public function get_client($name = null)
	{
		$client = null;
		if ($name === null) {
			$client = $this->current_client;
		} else {
			if (isset($this->clients[$name])) {
				$client = $this->clients[$name];
			}
		}
		return $client;
	}

	public function get_client_names()
	{
		return array_keys($this->clients);
	}

}

abstract class Service_Google_API_AbstractClient
{

	protected $access_token;
	protected $client;
	protected $name;
	protected $service;

	protected $handler;

	protected $scope;

	public function __construct($name, $auth_inf, $handler)
	{
		$this->name = $name;
		$this->client = new Google_Client();
		$this->client->setClientId($auth_inf['id']);
		$this->client->setClientSecret($auth_inf['secret']);
		$this->client->setRedirectUri($auth_inf['redirect_uri']);
		$this->client->setDeveloperKey($auth_inf['developer_key']);
		$this->init_scope();

		$this->client->setScopes(array(
				$this->scope
			)
		);

		$this->handler = $handler;
	}

	abstract protected function init_scope();

	public function get_name()
	{
		return $this->name;
	}

	public function authorizate($stdin)
	{
		$auth_code = null;
		if (!is_null($stdin)) {
			$authUrl = $this->client->createAuthUrl();

			print "Please visit:\n$authUrl\n\n";
			print "Please enter the auth code:\n";
			$auth_code = $stdin->read_line();

			//$_GET['code'] = $auth_code;
		}
		$this->access_token = $this->client->authenticate($auth_code);
		return $this->access_token;
	}

	public function prepare($token)
	{
		$this->client->setAccessToken($token);
		$this->client->setUseObjects(true);

		$this->service = new Google_AnalyticsService($this->client);

		$this->refresh_access_token();

		if (!$this->client->getAccessToken()) {
			$authUrl = $this->client->createAuthUrl();
			print "<a class='login' href='$authUrl'>Connect Me!</a>";
		}
	}

	public function refresh_access_token($force = false)
	{
		if ($this->client->getAuth()->isAccessTokenExpired() || $force) {
			$this->client->getAuth()->refreshToken($this->client->getAuth()->token['refresh_token']);
		}
	}

	/**
	 * @deprecated Вместо использования этого метода рекомендуется вызывать метод refresh_access_token(). Обновленный токен будет доступен через метод get_token().
	 */
	public function get_refreshed_access_token($refreshToken)
	{
		Google_Client::$auth->refreshToken($refreshToken);
		return json_encode(Google_Client::$auth->token);
	}

	public function set_token($token, $write_to_cache = false)
	{
		$this->client->setAccessToken($token);
		if ($write_to_cache) {
			$this->handler->write_to_cache($this->name, $this->get_token());
		}
	}

	public function get_token()
	{
		return $this->client->getAccessToken();
	}

	public function native()
	{
		return $this->service;
	}

	public function __get($property)
	{
		switch ($property) {
			case 'access_token':
			case 'client':
			case 'name':
			case 'service':
				return $this->$property;
			default:
				throw new Core_MissingPropertyException($property);
		}
	}

	public function __set($property, $value)
	{
		throw new Core_ReadOnlyObjectException($this);
	}

}

class Service_Google_API_ClientLibraryModuleNotFoundException extends Core_Exception
{
	protected $arg_name;
	protected $arg_value;

	public function __construct($name, $value)
	{
		$this->arg_name = (string)$name;
		$this->arg_value = $value;
		parent::__construct("Missing client library module: '$this->arg_name'. Path to library: '$this->arg_value'.\n");
	}
}

class Service_Google_API_TokenNotFoundException extends Core_Exception
{
	protected $arg_name;

	public function __construct($name)
	{
		$this->arg_name = (string)$name;
		parent::__construct("Access token not found for project: '$this->arg_name'.\n");
	}
}