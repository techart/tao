<?php
/**
 * WS.Middleware.Status
 *
 * @package WS\Middleware\Status
 * @version 0.2.0
 */

Core::load('Templates', 'WS', 'Events');

/**
 * @package WS\Middleware\Status
 */
class WS_Middleware_Status implements Core_ModuleInterface
{

	const VERSION = '0.2.1';

	/**
	 * @param WS_ServiceInterface $application
	 * @param array               $map
	 * @param string              $default_template
	 *
	 * @return WS_Middleware_Status_Service
	 */
	public function Service(WS_ServiceInterface $application, array $map, $default_template = 'status', $disabled = false)
	{
		return new WS_Middleware_Status_Service($application, $map, $default_template);
	}

}

/**
 * @package WS\Middleware\Status
 */
class WS_Middleware_Status_Service extends WS_MiddlewareService
{

	protected $map;
	protected $disabled = false;
	protected $default_template;

	/**
	 * @param WS_ServiceInterface $application
	 * @param array               $map
	 * @param string              $default_template
	 */
	public function __construct(WS_ServiceInterface $application, array $map, $default_template = 'status', $disabled = false)
	{
		parent::__construct($application);
		$this->default_template = $default_template;
		$this->map = $map;
		$this->disabled = $disabled;
	}

	/**
	 * @return WS_Middleware_Status_Service
	 */
	public function disable()
	{
		$this->disabled = true;
		return $this;
	}

	/**
	 * @return WS_Middleware_Status_Service
	 */
	public function enable()
	{
		$this->disabled = false;
		return $this;
	}

	/**
	 * @param WS_Environment $env
	 *
	 * @return Net_HTTP_Response
	 */
	public function run(WS_Environment $env)
	{
		//if ($this->disabled) return $this->application->run($env);

		$error = null;

		try {
			$body = $this->application->run($env);
			$response = Net_HTTP::merge_response($body, $env->response);
		} catch (Exception $e) {
			$error = $e;
			if ($this->disabled) {
				throw $e;
			}
			$response = Net_HTTP::Response(Net_HTTP::INTERNAL_SERVER_ERROR);
		}
		if (!$response->body && ($template = $this->find_template_name_for($response->status))) {
			if (isset($env->not_found->static_file)) {
				$response->body(IO_FS::File($env->not_found->static_file));
			} else {
				$layout = isset($env->not_found->layout) ? $env->not_found->layout : 'work';
				$view = Templates::HTML($template);
				if ($layout) {
					$view->within_layout($layout);
				}
				$view->root->with(array(
						'env' => $env,
						'response' => $response,
						'error' => $error)
				);
				if ($view->exists()) {
					$response->body($view);
				} else {
					if (IO_FS::exists($static_name = $template . '.html')) {
						$response->body(IO_FS::File($static_name));
					}
				}
			}
		}
		Events::call('ws.status', $response);
		return $response;
	}

	/**
	 * @param int $status
	 *
	 * @return Tempalates_HTML_Template
	 */
	protected function find_template_name_for(Net_HTTP_Status $status)
	{
		foreach ($this->map as $k => $v) {
			if (is_string($v) && $status->code == $k) {
				return $v;
			}
			if (is_numeric($v) && $status->code == $v) {
				return $this->default_template;
			}
		}
	}

}

