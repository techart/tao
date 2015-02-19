<?php
/**
 * WS.Auth.Session
 *
 * @package WS\Auth\Session
 * @version 0.2.0
 */
Core::load('WS.Auth', 'Forms');

/**
 * @package WS\Auth\Session
 */
class WS_Auth_Session implements Core_ModuleInterface
{
	const VERSION = '0.2.0';

	/**
	 * @param WS_ServiceInterface         $application
	 * @param WS_Auth_AuthModuleInterface $auth_module
	 * @param string                      $auth_url
	 *
	 * @return WS_Auth_Session_Service
	 */
	static public function Service(WS_ServiceInterface $application, WS_Auth_AuthFindModuleInterface $auth_module, $auth_url = '/auth/?url={url}')
	{
		return new WS_Auth_Session_Service($application, $auth_module, $auth_url);
	}

}

/**
 * @package WS\Auth\Session
 */
class WS_Auth_Session_Service extends WS_Auth_Service
{

	protected $auth_url;

	/**
	 * @param WS_ServiceInterface         $application
	 * @param WS_Auth_AuthModuleInterface $auth_module
	 * @param string                      $auth_url
	 */
	public function __construct(WS_ServiceInterface $application, WS_Auth_AuthFindModuleInterface $auth_module, $auth_url = '/auth/?url={url}')
	{
		parent::__construct($application, $auth_module);
		$this->auth_url = $auth_url;
	}

	/**
	 * @param WS_Environment $env
	 */
	protected function set_user(WS_Environment $env)
	{
		if (isset($env->request->session['user_id'])) {
			$env->auth->user = $this->auth_module->find_user($env->request->session['user_id']);
		}
	}

	/**
	 * @param WS_Environment $env
	 *
	 * @return mixed
	 */
	public function run(WS_Environment $env)
	{
		$env->auth = Core::object(array(
				'user' => null,
				'module' => $this->auth_module)
		);

		$this->set_user($env);

		$uri = $env->request->url;

		try {
			$response = $this->application->run($env);
		} catch (WS_Auth_UnauthenticatedException $e) {
			$response = Net_HTTP::Response(Net_HTTP::UNAUTHORIZED);
		} catch (WS_Auth_ForbiddenException $e) {
			$response = Net_HTTP::Response(Net_HTTP::FORBIDDEN);
		}

		if ($response->status->code == Net_HTTP::UNAUTHORIZED) {
			return Net_HTTP::redirect_to(Core_Strings::replace($this->auth_url, '{url}', $uri));
		} else {
			return $response;
		}
	}

}

/**
 * @abstract
 * @package WS\Auth\Session
 */
abstract class WS_Auth_Session_AuthResource
{

	protected $env;
	protected $form;

	/**
	 * @param WS_Environment $env
	 */
	public function __construct(WS_Environment $env)
	{
		$this->env = $env;
		$this->form = Forms::Form('auth')->
			method(Net_HTTP::POST)->
			begin_fields->
			input('login')->
			password('password')->
			end_fields;
	}

	/**
	 * @return mixed
	 */
	public function index()
	{
		return $this->html($this->env->auth->user ? 'authenticated' : 'login')->
			with(array('env' => $this->env, 'form' => $this->form));
	}

	/**
	 */
	public function create()
	{
		if ($this->form->process($this->env->request) &&
			($user = $this->env->auth->module->authenticate($this->form['login'], $this->form['password']))
		) {
			$this->env->auth->user = $user;
			$this->env->request->session['user_id'] = $user->id;
			return Net_HTTP::redirect_to(Core::if_not_set($this->env->request, 'url', '/'));
		}
		return $this->html('login')->
			with(array('env' => $this->env, 'form' => $this->form));
	}

	/**
	 * @return Net_HTTP_Response
	 */
	public function delete()
	{
		$this->env->auth->user = null;
		unset($this->env->request->session['user_id']);
		return Net_HTTP::redirect_to(Core::if_not_set($this->env->request, 'url', '/'));
	}

	/**
	 * @return Templates_HTML_Template
	 */
	abstract protected function html($template);

}

