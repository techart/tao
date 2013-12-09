<?php
/**
 * WS.Auth.Basic
 * 
 * @package WS\Auth\Basic
 * @version 0.2.0
 */

Core::load('WS.Auth', 'Digest');

/**
 * @package WS\Auth\Basic
 */
class WS_Auth_Basic implements Core_ModuleInterface {

  const VERSION = '0.2.1';


/**
 * @param WS_ServiceInterface $application
 * @param WS_Auth_AuthModuleInterface $auth_module
 * @return WS_Auth_Basic_Service
 */
  static public function Service(WS_ServiceInterface $application, WS_Auth_AuthModuleInterface $auth_module, $options = array()) {
    return new WS_Auth_Basic_Service($application, $auth_module, $options);
  }

}


/**
 * @package WS\Auth\Basic
 */
class WS_Auth_Basic_Service extends WS_Auth_Service {
  protected $options = array('env_name' => 'auth');

  public function __construct(WS_ServiceInterface $application, WS_Auth_AuthModuleInterface $auth_module, $options = array()) {
    parent::__construct($application, $auth_module);
    $this->options = array_merge($this->options, $options);
  }


/**
 * @param WS_Environment $env
 * @return mixed
 */
  public function run(WS_Environment $env) {
    $name = $this->options['env_name'];
    if (!isset($env->$name))
      $env->$name = Core::object(array(
        'user'   => null,
        'module' => $this->auth_module));

    if (isset($env->request->headers['Authorization'])) {
      list($login, $password) = $this->parse_credentials($env->request->headers['Authorization']);
      $env->$name->user = $this->auth_module->authenticate($login, $password);
    }

    try {
      $response = $this->application->run($env);
    } catch (WS_Auth_UnauthenticatedException $e) {
      $response = Net_HTTP::Response(Net_HTTP::UNAUTHORIZED)->
                    header('WWW-Authenticate', 'Basic realm="'.$e->realm.'"');
    } catch (WS_Auth_ForbiddenException $e) {
      $response = Net_HTTP::Response(Net_HTTP::FORBIDDEN);
    }

    return $response;
  }



/**
 * @param string $string
 * @return array
 */
  protected function parse_credentials($string) {
    return ($m = Core_Regexps::match_with_results(
      '{(.+):(.+)}',
      Core_Strings::decode64(
        Core_Strings::replace($string, 'Basic ', '')))) ?
          array($m[1], $m[2]) :
          array(null, null);
  }

}

