<?php
/**
 * WS.Auth.OpenSocial
 * 
 * @package WS\Auth\OpenSocial
 * @version 0.2.0
 */
Core::load('WS.Auth.Session');

/**
 * @package WS\Auth\OpenSocial
 */
class WS_Auth_OpenSocial implements Core_ModuleInterface {
  const VERSION = '0.2.0';


/**
 * @param WS_ServiceInterface $application
 * @param WS_Auth_AuthModuleInterface $auth_module
 * @param string $auth_url
 * @return WS_Auth_OpenSocial_Service
 */
  static public function Service(WS_ServiceInterface $application, WS_Auth_OpenSocial_AuthModuleInterface $auth_module, $auth_url = '/auth/?url={url}') {
    return new WS_Auth_OpenSocial_Service($application, $auth_module, $auth_url);
  }

}

/**
 * @package WS\Auth\OpenSocial
 */
class WS_Auth_OpenSocial_Service extends WS_Auth_Session_Service {


/**
 * @param WS_ServiceInterface $application
 * @param WS_Auth_OpenSocial_AuthModuleInterface $auth_module
 * @param string $auth_url
 */
  public function __construct(WS_ServiceInterface $application, WS_Auth_OpenSocial_AuthModuleInterface $auth_module, $auth_url = '/auth/?url={url}') {
    parent::__construct($application, $auth_module, $auth_url);
  }



/**
 * @param WS_Environment $env
 */
  protected function set_user(WS_Environment $env) {
    parent::set_user($env);
    if (!$env->auth->user && isset($env->request->session['remote_user_id']))
      $env->auth->user = $this->auth_module->find_remote_user($env->request->session['remote_user_id']);
  }

}

/**
 * @abstract
 * @package WS\Auth\OpenSocial
 */
abstract class WS_Auth_OpenSocial_AuthResource extends WS_Auth_Session_AuthResource {

/**
 */
  public function save_user($user) {
    $this->env->auth->user = $user;
    $this->env->request->session['remote_user_id'] = $user->remote_id;
  }

/**
 */
  public function authenticate() {
    if ($user = $this->env->auth->module->authenticate_remote_user($this->env)) {
      $this->save_user($user);
      return Net_HTTP::redirect_to(Core::if_not_set($this->env->request, 'url', '/'));
    }
    return $this->index();
  }

}

/**
 * @package WS\Auth\OpenSocial
 */
interface WS_Auth_OpenSocial_AuthModuleInterface extends WS_Auth_AuthModuleInterface {


/**
 * Аутентифицирует удаленного пользователя
 * 
 * @param WS_Environment $env
 */
  public function authenticate_remote_user(WS_Environment $env);

/**
 * По идентификатору возвращает пользователя
 * 
 * @param int $id
 */
  public function find_remote_user($id);

}

