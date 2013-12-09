<?php
/**
 * @package WS\Middleware\Dummy
 */


class WS_Middleware_Dummy implements Core_ModuleInterface{
  const VERSION = '0.0.1';
  
  static public function Service(WS_ServiceInterface $application) {
    return new WS_Middleware_Dummy_Service($application);
  }
}

class WS_Middleware_Dummy_Service extends WS_MiddlewareService {

  public function run(WS_Environment $env) {
    return $this->application->run($env);
  }

}

