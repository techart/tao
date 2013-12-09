<?php
/**
 * WS.Auth
 * 
 * @package WS\Auth
 * @version 0.2.0
 */

Core::load('WS');

/**
 * @package WS\Auth
 */
class WS_Auth implements Core_ModuleInterface {

  const VERSION = '0.2.0';


/**
 * @param string $realm
 */
  static public function unauthenticated($realm = 'Restricted area') {
    throw new WS_Auth_UnauthenticatedException($realm);
  }

/**
 */
  static public function forbidden() { throw new WS_Auth_ForbiddenException(); }

}


/**
 * @package WS\Auth
 */
class WS_Auth_Exception extends Core_Exception {}


/**
 * @package WS\Auth
 */
class WS_Auth_UnauthenticatedException extends WS_Auth_Exception {

  protected $realm;


/**
 * @param string $realm
 */
  public function __construct($realm = 'Resticted area') { $this->realm = $realm; }

}


/**
 * @package WS\Auth
 */
class WS_Auth_ForbiddenException extends WS_Auth_Exception {}


/**
 * @package WS\Auth
 */
interface WS_Auth_AuthModuleInterface {


/**
 * @param string $login
 * @param string $password
 * @return mixed
 */
  public function authenticate($login, $password);

}

/**
 * @package WS\Auth
 */
interface WS_Auth_FindUserInterface {

/**
 * @param int $id
 * @return mixed
 */
  public function find_user($id);

}

/**
 * @package WS\Auth
 */
interface WS_Auth_AuthFindModuleInterface extends WS_Auth_AuthModuleInterface, WS_Auth_FindUserInterface {

}


/**
 * @abstract
 * @package WS\Auth
 */
abstract class WS_Auth_Service extends WS_MiddlewareService {

  protected $auth_module;


/**
 * @param WS_ServiceInterface $application
 * @param WS_Auth_AuthModuleInterface $auth_module
 */
  public function __construct(WS_ServiceInterface $application, WS_Auth_AuthModuleInterface $auth_module) {
    parent::__construct($application);
    $this->auth_module = $auth_module;
  }

}

