<?php
/**
 * WS.Middleware.Template
 * 
 * @package WS\Middleware\Template
 * @version 0.2.0
 */

/**
 * @package WS\Middleware\Template
 */
class WS_Middleware_Template implements Core_ModuleInterface {

  const VERSION = '0.2.1';


  static public function Service(WS_ServiceInterface $application) {
    return new WS_Middleware_Template_Service($application);
  }

}


/**
 * @package WS\Middleware\Template
 */
class WS_Middleware_Template_Service extends WS_MiddlewareService {


/**
 * @param WS_Environment $env
 * @return Net_HTTP_Response
 */
  public function run(WS_Environment $env) {
    $result = $this->application->run($env);
    if ($result->body instanceof Core_StringifyInterface) $result->body($result->body->as_string());
    return $result;
  }


}

