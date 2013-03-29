<?php
/// <module name="WS.Middleware.Template" version="0.2.0" maintainer="timokhin@techart.ru">

/// <class name="WS.Middleware.Template" stereotype="module">
class WS_Middleware_Template implements Core_ModuleInterface {

///   <constants>
  const VERSION = '0.2.1';
///   </constants>

///   <protocol name="building">

  static public function Service(WS_ServiceInterface $application) {
    return new WS_Middleware_Template_Service($application);
  }

///   </protocol>
}
/// </class>


/// <class name="WS.Middleware.Template.Service" extends="WS.MiddlewareService">
class WS_Middleware_Template_Service extends WS_MiddlewareService {

///   <protocol name="processing">

///   <method name="run" returns="Net.HTTP.Response">
///     <args>
///       <arg name="env" type="WS.Environment" />
///     </args>
///     <body>
  public function run(WS_Environment $env) {
    $result = $this->application->run($env);
    if ($result->body instanceof Core_StringifyInterface) $result->body($result->body->as_string());
    return $result;
  }
///     </body>
///   </method>

///   </protocol>

}
/// </class>

/// </module>
