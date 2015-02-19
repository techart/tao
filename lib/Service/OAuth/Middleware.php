<?php
/**
 * Service.OAuth.Middleware
 *
 * @package Service\OAuth\Middleware
 * @version 0.1.0
 */
Core::load('Service.OAuth', 'WS');

/**
 * @package Service\OAuth\Middleware
 */
class Service_OAuth_Middleware implements Core_ConfigurableModuleInterface
{
	const VERSION = '0.1.0';

	static protected $options = array(
		'prefix' => '/oauth/',
		'callback' => 'callback/',
		'return_url_name' => 'url');

	/**
	 * @param array $options
	 */
	static public function initialize(array $options = array())
	{
		self::options($options);
	}

	/**
	 * @param array $options
	 *
	 * @return mixed
	 */
	static public function options(array $options = array())
	{
		if (count($options)) {
			Core_Arrays::update(self::$options, $options);
		}
		return self::$options;
	}

	/**
	 * Устанавливает опцию
	 *
	 * @param string $name
	 * @param        $value
	 *
	 * @return mixed
	 */
	static public function option($name, $value = null)
	{
		$prev = null;
		if (array_key_exists($name, self::$options)) {
			$prev = self::$options[$name];
			if ($value !== null) {
				self::options(array($name => $value));
			}
		}
		return $prev;
	}

	/**
	 * Создает объект класса Service.OAuth.Middleware
	 *
	 * @param WS_ServiceInterface $application
	 *
	 * @return Service_OAuth_Middleware
	 */
	static public function Service(WS_ServiceInterface $application)
	{
		return new Service_OAuth_Middleware_Service($application);
	}

}

/**
 * @package Service\OAuth\Middleware
 */
class Service_OAuth_Middleware_Service extends WS_MiddlewareService
{
	protected $config = array();
	protected $args = array();

	/**
	 * Конструктор
	 *
	 * @param WS_ServiceInterface $application
	 */
	public function __construct(WS_ServiceInterface $application)
	{
		$args = func_get_args();
		parent::__construct(array_shift($args));
		$this->args = Core::normalize_args($args);
	}

	protected function config()
	{
		if ($this->args[0] instanceof Core_InvokeInterface) {
			$this->args = $this->args[0]->invoke();
		}
		return $this->config = array_merge($this->config, $this->args);
	}

	/**
	 * Выполняет обработку запроса
	 *
	 * @param WS_Environment $env
	 *
	 * @return mixed
	 */
	public function run(WS_Environment $env)
	{
		$this->config();
		if (!isset($env->oauth)) {
			$env->oauth(new stdClass());
		}
		foreach ($this->config as $name => $c) {
			$env->oauth->$name = (object)$c;
			if ($c['client']->is_logged_in() && !empty($c['is_login_callback']) && $c['is_login_callback'] instanceof Core_InvokeInterface) {
				$c['is_login_callback']->invoke(array($name, $env));
			}
			$url = sprintf('%s%s/', Service_OAuth_Middleware::option('prefix'), $name);
			switch ($env->request->path) {
				case $url:
					return $this->login_redirect($env, $c, $url);
				case $url . Service_OAuth_Middleware::option('callback'):
					return $this->login_confirm($env, $c);
			}
		}
		return $this->application->run($env);
	}

	/**
	 * @param WS_Enviroment $env
	 * @param array         $c
	 * @param string        $url
	 */
	protected function login_redirect($env, $c, $url)
	{
		$host = $env->request->scheme . '://' . $env->request->host;
		$env->request->session()->set('query', $env->request->query);
		switch (true) {
			case (!$c['client']->is_logged_in()):
				return $c['client']->login_3legged_redirect(
					$host . $url . Service_OAuth_Middleware::option('callback') . '?' . $env->request->query
				);
			case isset($env->request[Service_OAuth_Middleware::option('return_url_name')]):
				return $this->redirect($env);
			default:
				return $this->application->run($env);
		}
	}

	/**
	 * @param WS_Enviroment $env
	 * @param array         $c
	 */
	protected function login_confirm($env, $c)
	{
		$env->request->query($env->request->session()->get('query'));
		$env->request->session()->remove('query');
		if ($c['client']->login_3legged_confirm($env->request)) {
			$this->load_user_data($env, $c);
			if (isset($env->request[Service_OAuth_Middleware::option('return_url_name')])) {
				return $this->redirect($env);
			}
		}
		return $this->application->run($env);
	}

	protected function load_user_data($env, $c)
	{
		$request = $env->request;
		if (isset($c['api_me_url'])) {
			$res = $c['client']->send(Net_HTTP::Request($c['api_me_url']));
			$data = json_decode($res->body);
			$data = isset($data->response) ? $data->response : $data;
			if (empty($data->id) && !empty($data->uid)) {
				$data->id = $data->uid;
			}
			if (empty($data->id) && !empty($data->user_id)) {
				$data->id = $data->user_id;
			}
			$env->oauth->$c['name']->user = $data;
		} else {
			$id = isset($request['user_id']) ? $request['user_id'] : $c['client']->token['user_id'];
			$name = isset($request['screen_name']) ? $request['screen_name'] : $c['client']->token['screen_name'];
			$env->oauth->$c['name']->user = (object)array('id' => $id, 'name' => $name);
		}
		$c['client']->flush_store();
		return $this;
	}

	/**
	 * @param WS_Enviroment $env
	 */
	protected function redirect($env)
	{
		return Net_HTTP::redirect_to($env->request[Service_OAuth_Middleware::option('return_url_name')] .
			'?' . $env->request->query
		);
	}

}



