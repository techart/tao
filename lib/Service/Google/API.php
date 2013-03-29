<?php

Core::load('Cache');
Core::load('IO');

Core::load('Service.Google.API.Analytics');

class Service_Google_API implements Core_ConfigurableModuleInterface {

	const VERSION = '0.1.1';

	static protected $cache;
	static protected $stdin;
	static protected $options = array('lib_path' => '../extern/google-api-php-client/', 'cache_path' => 'fs://../cache/google_api');

	static function initialize(array $options = array()) {
		self::options($options);
		if (set_include_path(get_include_path() . PATH_SEPARATOR . self::option('lib_path').'src/')) {
			if (! @include_once('Google_Client.php')) {
				throw new Service_Google_API_ClientLibraryModuleNotFoundException('src/Google_Client.php', self::option('lib_path'));
			}
		}
		self::$cache = Cache::connect(self::option('cache_path'),0);
		self::$stdin = IO::stdin();
	}

	static function options(array $options = array()) {
		Core_Arrays::deep_merge_update_inplace(self::$options, $options);
		return self::$options;
	}

	static function option($name, $value = null) {
		if (is_null($value)) {
			return self::$options[$name];
		}
		return self::$options[$name] = $value;
	}

	static function Analytics() {
		if (! @include_once('contrib/Google_AnalyticsService.php')) {
			throw new Service_Google_API_ClientLibraryModuleNotFoundException('src/contrib/Google_AnalyticsService.php', self::option('lib_path'));
		}
		return new Service_Google_API_Analytics(&self::$cache, &self::$stdin);
	}

}

abstract class Service_Google_API_AbstractService {

	protected $cache;
	protected $stdin;
	protected $current_client = null;
	protected $clients = array();

	protected $client_class_name = 'Service_Google_API_AbstractClient';
	protected $cache_subfolder = 'base';

	function __construct($cache, $stdin) {
		$this->cache = $cache;
		$this->stdin = $stdin;
	}

	function add_client($name, $auth_inf, $write_to_cache = false) {
		// creating new class $client_class_name
		$this->clients[$name] = new $this->client_class_name($name, $auth_inf, $this);
		$this->current_client = &$this->clients[$name];
		$this->current_client->authorizate($this->stdin);
		$token = $this->current_client->get_token();
		if ($write_to_cache) {
			$this->write_to_cache($name, $token);
		}
		return $this->current_client;
	}

	function use_client($name, $auth_inf, $token = null) {
		// creating new class $client_class_name
		$this->clients[$name] = new $this->client_class_name($name, $auth_inf, $this);
		$this->current_client = &$this->clients[$name];

		if ($token == null) {
			if ($this->cache->has($this->cache_subfolder.":".$name)) {
				$token = $this->cache->get($this->cache_subfolder.":".$name);
			}
			else {
				throw new Service_Google_API_TokenNotFoundException($name);
			}
		}
		$this->current_client->prepare($token);
		return $this->current_client;
	}

	function write_to_cache($name, $token) {
		$this->cache->set($this->cache_subfolder.":".$name, $token);
	}

	function get_client($name = null) {
		$client = null;
		if ($name === null) {
			$client = $this->current_client;
		}
		else {
			if (isset($this->clients[$name])) {
				$client = $this->clients[$name];
			}
		}
		return $client;
	}

	function get_client_names() {
		return array_keys($this->clients);
	}

}

abstract class Service_Google_API_AbstractClient {

	protected $access_token;
	protected $client;
	protected $name;
	protected $service;

	protected $handler;

	protected $scope = null;

	function __construct($name, $auth_inf, $handler) {
		$this->name = $name;
		$this->client = new Google_Client();
		$this->client->setClientId($auth_inf['id']);
		$this->client->setClientSecret($auth_inf['secret']);
		$this->client->setRedirectUri($auth_inf['redirect_uri']);
		$this->client->setDeveloperKey($auth_inf['developer_key']);

		$this->client->setScopes(array(
			$this->scope
		));

		$this->handler = $handler;
	}

	function get_name() {
		return $this->name;
	}

	function authorizate($stdin) {
		$authUrl = $this->client->createAuthUrl();

		print "Please visit:\n$authUrl\n\n";
		print "Please enter the auth code:\n";
		$auth_code = $stdin->read_line();

 		$_GET['code'] = $auth_code;
 		$this->access_token = $this->client->authenticate();
	}

	function prepare($token) {
		$this->client->setAccessToken($token);
		$this->client->setUseObjects(true);

		$this->service = new Google_AnalyticsService($this->client);

        $this->refresh_access_token();

		if (!$this->client->getAccessToken()) {
			$authUrl = $this->client->createAuthUrl();
			print "<a class='login' href='$authUrl'>Connect Me!</a>";
		}
	}

	function refresh_access_token($force = false) {
		if ($this->client->getAuth()->isAccessTokenExpired()||$force) {
			$this->client->getAuth()->refreshToken($this->client->getAuth()->token['refresh_token']);
		}
	}

	/**
	 * @deprecated Вместо использования этого метода рекомендуется вызывать метод refresh_access_token(). Обновленный токен будет доступен через метод get_token().
	 */
	function get_refreshed_access_token($refreshToken) {
		Google_Client::$auth->refreshToken($refreshToken);
		return json_encode(Google_Client::$auth->token);
	}

	function set_token($token, $write_to_cache = false) {
		$this->client->setAccessToken($token);
		if ($write_to_cache) {
			$this->handler->write_to_cache($this->name, $this->get_token());
		}
	}

	function get_token() {
		return $this->client->getAccessToken();
	}

	function native() {
		return $this->service;
	}

	function __get($property) {
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

	function __set($property, $value) {
		throw new Core_ReadOnlyObjectException($this);
	}

}

class Service_Google_API_ClientLibraryModuleNotFoundException extends Core_Exception {
  protected $arg_name;
  protected $arg_value;

  public function __construct($name, $value) {
    $this->arg_name = (string) $name;
    $this->arg_value = $value;
    parent::__construct("Missing client library module: '$this->arg_name'. Path to library: '$this->arg_value'.\n");
  }
}

class Service_Google_API_TokenNotFoundException extends Core_Exception {
  protected $arg_name;

  public function __construct($name) {
    $this->arg_name = (string) $name;
    parent::__construct("Access token not found for project: '$this->arg_name'.\n");
  }
}