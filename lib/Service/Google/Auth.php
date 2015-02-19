<?php
/**
 * Service.Google.Auth
 *
 * @package Service\Google\Auth
 * @version 0.1.0
 */
Core::load('Net.Agents.HTTP');

/**
 * @package Service\Google\Auth
 */
class Service_Google_Auth implements Core_ModuleInterface
{
	const VERSION = '0.1.0';

	const AUTHSUB_REQUEST_URI = 'https://www.google.com/accounts/AuthSubRequest';
	const AUTHSUB_SESSION_TOKEN_URI = 'https://www.google.com/accounts/AuthSubSessionToken';
	const AUTHSUB_TOKEN_INFO_URI = 'https://www.google.com/accounts/AuthSubTokenInfo';
	const AUTHSUB_REVOKE_TOKEN_URI = 'https://www.google.com/accounts/AuthSubRevokeToken';

	const CLIENTLOGIN_URI = 'https://www.google.com/accounts/ClientLogin';
	const DEFAULT_SOURCE = 'Techart-TAO';

	/**
	 */
	static public function ClientLogin()
	{
		return new Service_Google_Auth_ClientLogin();
	}

}

/**
 * @package Service\Google\Auth
 */
class Service_Google_Auth_Exception extends Core_Exception
{
}

/**
 * @package Service\Google\Auth
 */
class Service_Google_Auth_ClientLogin implements Core_PropertyAccessInterface
{

	protected $token;
	protected $error;

	protected $agent;

	protected $parameters = array(
		'Email' => '',
		'Passwd' => '',
		'service' => 'xapi',
		'accountType' => 'HOSTED_OR_GOOGLE',
		'source' => Service_Google_Auth::DEFAULT_SOURCE
	);

	private $method_to_parameters = array(
		'email' => 'Email',
		'password' => 'Passwd',
		'service' => 'service',
		'account_type' => 'accountType',
		'source' => 'source'
	);

	/**
	 */
	public function __construct(array $parameters = array())
	{
		if ($parameters) {
			$this->parameters($parameters);
		}
		$this->agent = Net_HTTP::Agent();
	}

	/**
	 */
	public function parameter($name, $value)
	{
		$this->parameters(array($name => $value));
		return $this;
	}

	/**
	 */
	public function parameters(array $parameters)
	{
		Core_Arrays::update($this->parameters, $parameters);
		return $this;
	}

	/**
	 * @param Net_HTTP_AgentInterface $agent
	 */
	public function agent(Net_HTTP_AgentInterface $agent)
	{
		$this->agent = $agent;
		return $this;
	}

	/**
	 * @param string $method
	 * @param array  $args
	 */
	public function __call($method, $args)
	{
		if (isset($this->method_to_parameters[$method])) {
			$this->parameters(array($this->method_to_parameters[$method] => $args[0]));
			return $this;
		} else {
			throw new Core_MissingMethodException($method);
		}
	}

	/**
	 * @param string           $email
	 * @param string           $password
	 * @param string           $service
	 * @param string           $source
	 * @param HOSTED_OR_GOOGLE $account_type
	 *
	 * @return boolean
	 */
	public function login($email = null, $password = null)
	{
		foreach (array('email' => $email, 'password' => $password) as $k => $v)
			if ($v) {
				$this->$k($v);
			}
		$request = Net_HTTP::Request(Service_Google_Auth::CLIENTLOGIN_URI)->
			method(Net_HTTP::POST)->
			parameters($this->parameters);

		$response = $this->agent->send($request);

		if ($response->status->code !== 200) {
			$this->error = $this->get_value($response, 'Error');
		} else {
			$this->token = $this->get_value($response, 'Auth');
		}
		return $this;
	}

	/**
	 * @param Net_HTTP_Response $res
	 * @param string            $name
	 *
	 * @return string|null
	 */
	protected function get_value(Net_HTTP_Response $res, $name)
	{
		return
			($m = Core_Regexps::match_with_results('{' . $name . '=([^\n]*)}i', $res->body)) ?
				$m[1] :
				null;
	}

	/**
	 * @param string $property
	 *
	 * @return mixed
	 */
	public function __get($property)
	{
		switch ($property) {
			case 'error':
			case 'token' :
				return $this->$property;
			default :
				throw new Core_MissingPropertyException($property);
		}
	}

	/**
	 * @param string $property
	 * @param        $value
	 *
	 * @return mixed
	 */
	public function __set($property, $value)
	{
		throw new Core_ReadOnlyObjectException($this);
	}

	/**
	 * @param string $property
	 *
	 * @return boolean
	 */
	public function __isset($property)
	{
		switch ($property) {
			case 'error':
			case 'token' :
				return isset($this->$property);
			default :
				throw new Core_MissingPropertyException($property);
		}
	}

	/**
	 * @param string $property
	 */
	public function __unset($property)
	{
		throw new Core_ReadOnlyObjectException($this);
	}

}

