<?php
/**
 * Log.FirePHP
 * 
 * @package Log\FirePHP
 * @version 0.1.0
 */
Core::load('Log', 'Events');

/**
 * @package Log\FirePHP
 */
class Log_FirePHP implements Core_ModuleInterface {
  const VERSION = '0.1.0';
//


/**
 * @return Log_FirePHP_Handler
 */
  static public function Handler() { return new Log_FirePHP_Handler(); }

}

/**
 * @package Log\FirePHP
 */
class Log_FirePHP_Handler extends Log_Handler {

  public function init()
  {
    $self = $this;
    Events::add_listener('ws.response', function($resp) use ($self) {
      $self->dump($resp);
    });
  }

  static protected $types = array(
    Log_Level::DEBUG => 'LOG',
    Log_Level::INFO => 'INFO',
    Log_Level::CRITICAL => 'ERROR',
    Log_Level::ERROR => 'ERROR',
    Log_Level::WARNING => 'WARN'
  );
  static protected $max_len = 5000;
  static protected $start_number = 1;

  protected $messages = array();


/**
 * @param int $value
 */
  static public function max_len($value = null) {
    if ($value === null) return self::$max_len;
    self::$max_len = (int) $value;
  }



/**
 */
  public function close() { $this->messages = array();}

/**
 * @param  $message
 * @return Log_FirePHP_Handler
 */
  public function emit($message) {
    $this->messages[] = $message;
    return $this;
  }

/**
 * @param Net_HTTP_Response $response
 * @return Net_HTTP_Response
 */
  public function dump(Net_HTTP_Response $response) {
    $response->
      header('X-Wf-Protocol-1', 'http://meta.wildfirehq.org/Protocol/JsonStream/0.2')->
      header('X-Wf-1-Plugin-1', 'http://meta.firephp.org/Wildfire/Plugin/FirePHP/Library-FirePHPCore/0.3')->
      header('X-Wf-1-Structure-1', 'http://meta.firephp.org/Wildfire/Structure/FirePHP/FirebugConsole/0.1');
    $response->headers($this->convert_to_headers());
    return $response;
  }



/**
 * @return array
 */
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

/**
 * @param array $names
 * @param array $headers
 * @param int $number
 * @param string $json
 * @param int $len
 */
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

/**
 * @param int $number
 * @return string
 */
  private function build_name(&$number) {
    $res =  'X-Wf-1-1-1-'.$number;//implode('-', str_split($number));
    $number += 1;
    return $res;
  }

}

