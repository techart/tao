<?php
/// <module name="WS.Middleware.PDF" version="0.0.1" maintainer="svistunov@techart.ru">
Core::load('WS');

/// <class name="WS.Middleware.PDF" stereotype="module">
///   <brief>Класс модуля</brief>
///   <implements interface="Core.ModuleInterface" />
class WS_Middleware_PDF implements Core_ModuleInterface {

///   <constants>
  const VERSION = '0.0.1';
///   </constants>

///   <protocol name="building">

///   <method name="Service" returns="WS.Middleware.PDF.Service" scope="class">
///     <brief>Создает объект класса WS.Middleware.PDF.Service</brief>
///     <args>
///       <arg name="application" type="WS.ServiceInterface" brief="объект приложения" />
///     </args>
///     <body>
  static public function Service(WS_ServiceInterface $application, $title = null, $clear_url = true, $patterns = null, $options = array()) {
    return new WS_Middleware_PDF_Service($application, $title, $clear_url, $patterns, $options);
  }
///     </body>
///   </method>

///   </protocol>
}
/// </class>


/// <class name="WS.Middleware.PDF.Service" extends="WS.MiddlewareService">
///   <brief>Конфигурационный сервис</brief>
class WS_Middleware_PDF_Service extends WS_MiddlewareService {

  protected $patterns = array(
    '{^/pdf/}' => '/',
    //'{\.pdf$}' => ''
  );
  
  protected $clear_url = true;
  
  protected $mpdf_dir = '../vendor/mpdf/mpdf.php';
  
  protected $options = array();
  
  protected $title = 'output.pdf';
  
///   <protocol name="creating">

///   <method name="__construct">
///     <brief>Конструктор</brief>
///     <args>
///       <arg name="application" type="WS.ServiceInterface" brief="объект приложения" />
///     </args>
///     <body>
  public function __construct(WS_ServiceInterface $application, $title = null, $clear_url = true, $patterns = null, $options = array()) {
    parent::__construct($application);
    if (is_array($patterns)) $this->patterns = $patterns;
    if (!empty($title)) $this->title = $title;
    $this->clear_url = $clear_url;
    $this->options = $options;
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="performing">

///   <method name="run" returns="mixed">
///     <brief>Выполняет обработку запроса</brief>
///     <args>
///       <arg name="env" type="WS.Environment" brief="объект окружения" />
///     </args>
///     <body>
  public function run(WS_Environment $env) {
    $m = false;
    foreach ($this->patterns as $p => $r) {
      $pattern_match = preg_match($p, $env->request->urn);
      if ($pattern_match) {
        $m = true;
        if ($this->clear_url)
          $env->request->uri(preg_replace($p, $r, $env->request->urn));
      }
    }
    $env->pdf = (object) array('active' => $m);
    $r = $this->application->run($env);
    if ($env->pdf->active) {
      $response = Net_HTTP::merge_response($r, $env->$response);
      if (!$response->status->is_success) return $r;
      if (isset($env->config) && isset($env->config->pdf))
        foreach ($env->config->pdf as $option => $v)
          if (isset($this->$option)) $this->$option = $v;
      if (!class_exists('mPDF')) include($this->mpdf_dir);
      $mpdf = new mPDF();
      foreach ($this->options as $name => $v)
        $mpdf->$name = $v;
      if (is_callable($env->pdf->callback)) {
        $env->response = $response;
        return call_user_func($env->pdf->callback, $mpdf, $env);
      }
      else {
        $mpdf->WriteHTML((string) $response->body);
        $mpdf->Output(!empty($env->pdf->title) ? $env->pdf->title : $this->title, 'D');
      }
    }
    else
      return $r;
  }
///     </body>
///   </method>

///   </protocol>
}
/// </class>

/// </module>
