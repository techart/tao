<?php
/**
 * WS.Middleware.PDF
 * 
 * @package WS\Middleware\PDF
 * @version 0.0.1
 */
Core::load('WS');

/**
 * Класс модуля
 * 
 * @package WS\Middleware\PDF
 */
class WS_Middleware_PDF implements Core_ModuleInterface {

  const VERSION = '0.0.1';


/**
 * Создает объект класса WS.Middleware.PDF.Service
 * 
 * @param WS_ServiceInterface $application
 * @return WS_Middleware_PDF_Service
 */
  static public function Service(WS_ServiceInterface $application, $title = null, $clear_url = true, $patterns = null, $options = array()) {
    return new WS_Middleware_PDF_Service($application, $title, $clear_url, $patterns, $options);
  }

}


/**
 * Конфигурационный сервис
 * 
 * @package WS\Middleware\PDF
 */
class WS_Middleware_PDF_Service extends WS_MiddlewareService {

  protected $patterns = array(
    '{^/pdf/}' => '/',
    //'{\.pdf$}' => ''
  );
  
  protected $clear_url = true;
  
  protected $mpdf_dir = '../vendor/mpdf/mpdf.php';
  
  protected $options = array();
  
  protected $title = 'output.pdf';
  

/**
 * Конструктор
 * 
 * @param WS_ServiceInterface $application
 */
  public function __construct(WS_ServiceInterface $application, $title = null, $clear_url = true, $patterns = null, $options = array()) {
    parent::__construct($application);
    if (is_array($patterns)) $this->patterns = $patterns;
    if (!empty($title)) $this->title = $title;
    $this->clear_url = $clear_url;
    $this->options = $options;
  }



/**
 * Выполняет обработку запроса
 * 
 * @param WS_Environment $env
 * @return mixed
 */
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

}

