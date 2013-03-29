<?php
/// <module name="WS.Middleware.Status" version="0.2.0" maintainer="timokhin@techart.ru">

Core::load('Templates', 'WS', 'Events');

/// <class name="WS.Middleware.Status" stereotype="module">
///   <implements interface="Core.ModuleInterface" />
class WS_Middleware_Status implements Core_ModuleInterface {

///   <constants>
  const VERSION = '0.2.1';
///   </constants>

///   <protocol name="building">

///   <method name="Service" returns="WS.Middleware.Status.Service" scope="class">
///     <args>
///       <arg name="application" type="WS.ServiceInterface" />
///       <arg name="map" type="array" />
///       <arg name="default_template" type="string" default="'http/status'" />
///     </args>
///     <body>
  public function Service(WS_ServiceInterface  $application, array $map, $default_template = 'status', $disabled = false) {
    return new WS_Middleware_Status_Service($application, $map, $default_template);
  }
///     </body>
///   </method>

///   </protocol>

}
/// </class>


/// <class name="WS.Middleware.Status.Service" extends="WS.MiddlewareService">
class WS_Middleware_Status_Service extends WS_MiddlewareService {

  protected $map;
  protected $disabled = false;
  protected $default_template;

///   <protocol name="creating">

///   <method name="__construct">
///     <args>
///       <arg name="application" type="WS.ServiceInterface" />
///       <arg name="map" type="array" />
///       <arg name="default_template" type="string" default="'http/status'" />
///     </args>
///     <body>
  public function __construct(WS_ServiceInterface $application, array $map, $default_template = 'status', $disabled = false) {
    parent::__construct($application);
    $this->default_template = $default_template;
    $this->map = $map;
    $this->disabled = $disabled;
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="configuring">

///   <method name="disable" returns="WS.Middleware.Status.Service">
///     <body>
  public function disable() {
    $this->disabled = true;
    return $this;
  }
///     </body>
///   </method>

///   <method name="enable" returns="WS.Middleware.Status.Service">
///     <body>
  public function enable() {
    $this->disabled = false;
    return $this;
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="processing">

///   <method name="run" returns="Net.HTTP.Response">
///     <args>
///       <arg name="env" type="WS.Environment" />
///     </args>
///     <body>
  public function run(WS_Environment $env) {
    //if ($this->disabled) return $this->application->run($env);

    $error = null;

    try {
      $body = $this->application->run($env);
      $response = Net_HTTP::merge_response($body, $env->response);
    } catch (Exception $e) {
      $error = $e;
      if ($this->disabled) throw $e;
      $response = Net_HTTP::Response(Net_HTTP::INTERNAL_SERVER_ERROR);
    }
    if (!$response->body  && ($template = $this->find_template_name_for($response->status))) {
      if (isset($env->not_found->static_file)) {
        $response->body(IO_FS::File($env->not_found->static_file));
      }
      else {
        $layout = isset($env->not_found->layout) ? $env->not_found->layout : 'work';
        $view = Templates::HTML($template);
        if ($layout)
          $view->within_layout($layout);
        $view->root->with(array(
            'env'      => $env,
            'response' => $response,
            'error'    => $error ));
        if ($view->exists())
          $response->body($view);
        else if (IO_FS::exists($static_name = $template . '.html'))
          $response->body(IO_FS::File($static_name));
      }
    }
    Events::call('ws.status', $response);
    return $response;
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="supporting">

///   <method name="find_template_for" returns="Tempalates.HTML.Template">
///     <args>
///       <arg name="status" type="int" />
///     </args>
///     <body>
  protected function find_template_name_for(Net_HTTP_Status $status) {
    foreach ($this->map as $k => $v) {
      if (is_string($v)  && $status->code == $k) return $v;
      if (is_numeric($v) && $status->code == $v) return $this->default_template;
    }
  }
///     </body>
///   </method>

///   </protocol>
}
/// </class>

/// </module>
