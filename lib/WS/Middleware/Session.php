<?php
/// <module name="WS.Session" version="0.2.0" maintainer="timokhin@techart.ru">
Core::load('Net.HTTP.Session', 'WS');

/// <class name="WS.Session" stereotype="module">
///   <implements interface="Core.ModulteInterface" />
class WS_Middleware_Session implements Core_ModuleInterface {
///   <constants>
  const VERSION = '0.2.1';
///   </constants>

///   <protocol name="building">

///   <method name="Service" returns="WS.Session.Service" scope="class">
///     <body>
  static public function Service(WS_ServiceInterface $application) { return new WS_Middleware_Session_Service($application); }
///     </body>
///   </method>

///   </protocol>
}
/// </class>

/// <class name="WS.Session.Service" extends="WS.MiddlewareService">
class WS_Middleware_Session extends WS_MiddlewareService {

///   <protocol name="performing">

///   <method name="run" returns="mixed">
///     <args>
///       <arg name="env" type="WS.Environment" />
///     </args>
///     <body>
  public function run(WS_Environment $env) {
    $error = null;
    $session = Net_HTTP_Session::Store();

    $env->request->session($session);

    $env->flash = Net_HTTP_Session::Flash(
      (isset($session['flash']) && is_array($session['flash'])) ? $session['flash'] : array());

    try {
      $result = $this->application->run($env);
    } catch(Exception $e) {
      $error = $e;
    }

    $session['flash'] = $env->flash->later;
    $session->commit();

    if ($error) throw $error;
    else        return $result;
  }
///     </body>
///   </method>

///   </protocol>
}
/// </class>

/// </module>
