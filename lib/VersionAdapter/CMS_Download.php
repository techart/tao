<?php
Core::load('Net.HTTP');

class CMS_Download implements Core_ModuleInterface {
  const VERSION = '0.0.0';
  
  static public function download($file, $cache = false) {
    return Net_HTTP::Download($file, $cache);
  }
}
