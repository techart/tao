<?php
/// <module name="Log.FirePHP" maintainer="svistunov@techart.ru" version="0.1.0">
Core::load('Log', 'Events');

/// <class name="Log.FirePHP" stereotype="module">
///   <implements interface="Core.ModuleInterface" />
class Log_FirePHP implements Core_ModuleInterface {
///   <constants>
  const VERSION = '0.1.0';
///   </constants>
//

///   <protocol name="building">

///   <method name="handler" scope="class" returns="Log.FirePHP.Handler">
///     <body>
  static public function Handler() { return new Log_FirePHP_Handler(); }
///     </body>
///   </method>

///   </protocol>
}
/// </class>

/// <class name="Log.FirePHP.Handler" extends="Log.Handler">
class Log_FirePHP_Handler extends Log_Handler {

  public function init()
  {
    $self = $this;
    Events::add_listener('ws.response', function($resp) use ($self) {
      $self->dump($resp);
    });
  }

/// <constants>
  static protected $types = array(
    Log_Level::DEBUG => 'LOG',
    Log_Level::INFO => 'INFO',
    Log_Level::CRITICAL => 'ERROR',
    Log_Level::ERROR => 'ERROR',
    Log_Level::WARNING => 'WARN'
  );
  static protected $max_len = 5000;
  static protected $start_number = 1;
/// </constsnts>

  protected $messages = array();

///   <protocol name="configure">

///   <method name="">
///     <args>
///       <arg name="value" type="int" default="null" />
///     </args>
///     <body>
  static public function max_len($value = null) {
    if ($value === null) return self::$max_len;
    self::$max_len = (int) $value;
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="performing">

///   <method name="close">
///     <body>
  public function close() { $this->messages = array();}
///     </body>
///   </method>

///   <method name="emit" returns="Log.FirePHP.Handler">
///     <args>
///       <arg name="message" />
///     </args>
///     <body>
  public function emit($message) {
    $this->messages[] = $message;
    return $this;
  }
///     </body>
///   </method>

///   <method name="dump" returns="Net.HTTP.Response">
///     <args>
///       <arg name="response" type="Net.HTTP.Response" />
///     </args>
///     <body>
  public function dump(Net_HTTP_Response $response) {
    $response->
      header('X-Wf-Protocol-1', 'http://meta.wildfirehq.org/Protocol/JsonStream/0.2')->
      header('X-Wf-1-Plugin-1', 'http://meta.firephp.org/Wildfire/Plugin/FirePHP/Library-FirePHPCore/0.3')->
      header('X-Wf-1-Structure-1', 'http://meta.firephp.org/Wildfire/Structure/FirePHP/FirebugConsole/0.1');
    $response->headers($this->convert_to_headers());
    return $response;
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="supporting">

///   <method name="convert_to_headers" returns="array" access="protected">
///     <body>
  protected function convert_to_headers() {
    $headers = array();
    $names = array();
    $number = self::$start_number;
    foreach ($this->messages as $m) {
      $json = json_encode(array(
        array('Type' => self::$types[$m->level], 'File' => 'FirePHP', 'Line' => 0),
        $m->body
      ));
      $len = strlen($json);
      // if ($len >= self::$max_len)
      //   $this->chunked($names, $headers, $number, $json, $len);
      // else {
        $names[] = $this->build_name($number);
        $headers[] = sprintf('%s|%s|', $len, $json);
      // }
    }
    return count($names) > 0 ? array_combine($names, $headers) : array();
  }
///     </body>
///   </method>

///   <method name="chunked" access="private">
///     <args>
///       <arg name="names" type="array" />
///       <arg name="headers" type="array" />
///       <arg name="number" type="int" />
///       <arg name="json" type="string" />
///       <arg name="len" type="int" />
///     </args>
///     <body>
  private function chunked(&$names, &$headers, &$number, &$json, $len) {
    $chunks = str_split($json, self::$max_len);
    foreach ($chunks as $ind => $chunk) {
      $names[] = $this->build_name($number);
      switch ($ind) {
        case 0:
          $headers[] = sprintf('%s|%s|\\', $len, $chunk);
          break;
        case (count($chunks) - 1):
          $headers[] = sprintf('|%s|', $chunk);
          break;
        default:
          $headers[] = sprintf('|%s|\\', $chunk);
      }
    }
  }
///     </body>
///   </method>

///   <method name="build_name" returns="string" access="private">
///     <args>
///       <arg name="number" type="int" />
///     </args>
///     <body>
  private function build_name(&$number) {
    $res =  'X-Wf-1-1-1-'.$number;//implode('-', str_split($number));
    $number += 1;
    return $res;
  }
///     </body>
///   </method>

///   </protocol>
}
/// </class>

/// </module>
