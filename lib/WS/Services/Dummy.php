<?php
/**
 * @package WS\Services\Dummy
 */


class WS_Services_Dummy implements Core_ModuleInterface{
  const VERSION = '0.0.1';
  
  static public function Service() {
    return new WS_Services_Dummy_Service($application);
  }
}

class WS_Services_Dummy_Service implements WS_ServiceInterface {
  
  public function __construct() {}

  public function run(WS_Environment $env) {
    return false;
  }

}
