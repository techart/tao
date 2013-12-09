<?php
/**
 * Service.OpenSocial.Containers.GFC
 * 
 * @package Service\OpenSocial\Containers\GFC
 * @version 0.1.0
 */

class Service_OpenSocial_Containers_GFC implements Service_OpenSocial_ModuleInterface {


/**
 * @return Service_OpenSocial_Container
 */
  static public function container() {
    return Service_OpenSocial::Container(array(
      'name'          => 'Google',
      'rest_endpoint' => 'http://www.google.com/friendconnect/api',
      'rpc_endpoint'  => 'http://www.google.com/friendconnect/api/rpc'));
  }

/**
 * @return Service_OpenSocial_Container
 */
  static public function sandbox() {
    return Service_OpenSocial::Container(array(
      'name'          => 'Google',
      'rest_endpoint' => 'http://www.google.com/friendconnect/api',
      'rpc_endpoint'  => 'http://www.google.com/friendconnect/api/rpc'));
  }

}

