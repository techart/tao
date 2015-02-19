<?php
/**
 * WS.Session
 *
 * @package WS\Middleware\Session
 * @version 0.2.0
 */
Core::load('Net.HTTP.Session', 'WS');

/**
 * @package WS\Middleware\Session
 */
class WS_Middleware_Session implements Core_ModuleInterface
{
	const VERSION = '0.2.1';

	/**
	 * @return WS_Session_Service
	 */
	static public function Service(WS_ServiceInterface $application)
	{
		return new WS_Middleware_Session_Service($application);
	}

}

/**
 * @package WS\Middleware\Session
 */
class WS_Middleware_Session_Service extends WS_MiddlewareService
{

	/**
	 * @param WS_Environment $env
	 *
	 * @return mixed
	 */
	public function run(WS_Environment $env)
	{
		$error = null;
		if (Core::is_cli() && !$env->request->session()) {
			return $this->application->run($env);
		}
		$session = $env->request->session();
		$env->flash = Net_HTTP_Session::Flash($session);

		try {
			$result = $this->application->run($env);
		} catch (Exception $e) {
			$error = $e;
		}
		$value = $env->flash->later;
		if ($value || $env->flash->is_init()) {
			$session['flash'] = $value;
		}

		if ($error) {
			throw $error;
		} else {
			return $result;
		}
	}

}

