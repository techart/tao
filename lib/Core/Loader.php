<?php
/**
 * @package Core\Loader
 */


class Core_Loader implements Core_ModuleInterface {
  const VERSION = '0.1.0';
  
  static public function extended() {
    Core::load('Core.Loader.Extended');
    return Core_Loader_Extended::loader();
  }
}
