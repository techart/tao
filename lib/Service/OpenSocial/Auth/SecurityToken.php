<?php
/**
 * Service.OpenSocial.Auth.SecurityToken
 * 
 * @package Service\OpenSocial\Auth\SecurityToken
 * @version 0.1.0
 */

/**
 * @package Service\OpenSocial\Auth\SecurityToken
 */
class Service_OpenSocial_Auth_SecurityToken implements Service_OpenSocial_ModuleInterface {


/**
 * @param string $token_name
 * @param string $token_value
 * @return Service_OpenSocial_Auth_SecurityToken_Adapter
 */
  static public function Adapter($token_name, $token_value) {
    return new Service_OpenSocial_Auth_SecurityToken_Adapter($token_name, $token_value);
  }

}


/**
 * @package Service\OpenSocial\Auth\SecurityToken
 */
class Service_OpenSocial_Auth_SecurityToken_Adapter extends Service_OpenSocial_AuthAdapter {


/**
 */
  public function __construct($token_name, $token_value) {
    parent::__construct(array('st_name' => $token_name, 'st_value' => $token_value));
  }



/**
 * @param array $options
 * @param Service_OpenSocial_Container $container
 */
  public function authorize_request(Net_HTTP_Request $request, Service_OpenSocial_Container $container) {
    return $request->query_parameters(array($this->options['st_name'] => $this->options['st_value']));
  }

}

