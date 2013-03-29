<?php
/// <module name="WS.Adapters.Apache" version="0.2.0" maintainer="timokhin@techart.ru">

Core::load('WS', 'Net.HTTP');

/// <class name="WS.Adapters.Apache" stereotype="module">
///   <implements interface="Core.ModuleInterface" />
///   <depends supplier="WS.Adapters.Apache.Adapter" stereotype="creates" />
class WS_Adapters_Apache implements Core_ModuleInterface {
///   <constants>
  const VERSION = '0.2.1';
///   </constants>
///   <protocol name="supporting" >
///   <method name="Adapter" scope="class" returns="WS.Adapters.Apache.Adapter">
///     <body>
  static public function Adapter() { return new WS_Adapters_Apache_Adapter(); }
///     </body>
///   </method>
///   </protocol>
}
/// </class>

/// <class name="WS.Adapters.Apache.Adapter">
///   <implements interface="WS.AdapterInterface" />
///   <depends supplier="Net.HTTP.Request" stereotype="creates" />
///   <depends supplier="Net.HTTP.Response" stereotype="uses" />
class WS_Adapters_Apache_Adapter implements WS_AdapterInterface {

///   <protocol name="building">

///   <method name="make_request" returns="Net.HTTP.Request">
///     <body>
  public function make_request() {
    Core_Arrays::deep_merge_update_inplace($_POST, array_filter($this->current_uploads()));

    return Net_HTTP::Request(
      ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') ? 'https://' : 'http://').
       $_SERVER['HTTP_HOST'].
       $_SERVER['REQUEST_URI'], array('REMOTE_ADDR' => $_SERVER['REMOTE_ADDR']))->
    method((isset($_POST['_method']) && $_POST['_method']) ? $_POST['_method'] : $_SERVER['REQUEST_METHOD'])->
    headers(apache_request_headers());
  }
///     </body>
///   </method>

///   <method name="process_response">
///     <args>
///       <arg name="response" type="Net.HTTP.Response" />
///     </args>
///     <body>
  public function process_response(Net_HTTP_Response $response) {
    //FIXME: $response->protocol
    
    ob_start();
    

    $body = $response->body;

    if (Core_Types::is_iterable($body))
      foreach ($body as $line) print($line);
    else
      print $body instanceof Core_StringifyInterface ?
        $body->as_string() : (string) $body;
        
    $response->body = ob_get_contents();
    ob_end_clean();
    Events::call('ws.response',$response);

    if ((int) $response->status->code != 200) header('HTTP/1.0 '.$response->status);
    foreach ($response->headers->as_array(true) as $v) header($v);
    print $response->body;
      
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="supporting">

///   <method name="current_uploads" access="protected">
///     <body>
  protected function current_uploads() {
    $files = array();
    foreach ($_FILES as $name => $file) {
      if (is_array($file['error'])) {
        $files[$name] = array_shift($file);
        foreach ($file as $v ) {
          $files[$name] = Core_Arrays::deep_merge_append($files[$name], $v);
        }
      }
      else {
        $files[$name] = array_values($file);
      }
    }
    $this->create_objects($files);
    return $files;
  }
///     </body>
///   </method>

///   <method name="create_objects" access="protected">
///     <args>
///       <arg name="nfiles" type="array" brief="массив файлов" />
///     </args>
///     <body>
  protected function create_objects(array &$files) {
    foreach ($files as $name => &$file) {
      if (is_array($file))
        if (count($file) == 5 && is_string($file[0]) && is_string($file[1]) && is_string($file[2]) && is_int($file[3]) && is_int($file[4]))
          if ($file[3] === UPLOAD_ERR_OK) {
            $file = Net_HTTP::Upload($file[2], $file[0], array('name' => $file[0], 'type' => $file[1], 'tmp_name' => $file[2], 'error' => $file[3], 'size' => $file[4]));
          }
          else $file = null;
        else
          $this->create_objects($file);
    }
  }
///     </body>
///   </method>

///   </protocol>

}
/// </class>

/// </module>
