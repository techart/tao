<?php
/**
 * WS.Adapters
 * 
 * @package WS\Adapters
 * @version 0.2.0
 */

/**
 * @package WS\Adapters
 */
class WS_Adapters implements Core_ModuleInterface {

  const VERSION = '0.2.0';


/**
 * @return WS_Adapters_Apache_Adapter
 */
  static public function apache() {
    Core::load('WS.Adapters.Apache');
    return new WS_Adapters_Apache_Adapter();
  }

}

