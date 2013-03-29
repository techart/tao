<?php

class Text_Process implements Core_ModuleInterface /*Core_ConfigurableModuleInterface*/ {
  const VERSION = '0.1.0';
  
  static protected $process = array();
  
  static public function initialize($config) {
    self::add_process(array(
      'translit' => 'Text.Parser.Translit',
      'bb' => 'Text.Parser.BB',
      'parms' => 'Text.Parser.Parms',
      'php' => 'Text.Highlight.PHP',
      'wiki' => 'Text.Parser.Wiki',
      'plaintext' => 'Text.Filter.PlainText',
      'html' => 'Text.Filter.HTML',
      'typographer' => 'Text.Processor.Typographer'
    ));
    if (isset($config['process'])) self::add_process($config['process']);
  }
  
  static public function add_process($name, $class = null) {
    if (is_null($class)) {
      foreach ($name as $n => $c)
        self::$process[$n] = $c;
    }
    else
      self::$process[$name] = $class;
  }
  
  static public function process($source, $process = array()) {
    $args = func_get_args();
    if (count($args) > 2) $process = array_slice($args, 1);
    if (empty($process)) return $source;
    $process = (array) $process;
    foreach ($process as $name => $config) {
      if (is_int($name) && is_string($config)) {
        $name = $config;
        $config = array();
      }
      else if (is_int($name) && is_array($config)) {
        $source = self::process($source, $config);
      }
      if (!isset(self::$process[$name])) continue;
      if (!is_string(self::$process[$name])) {
        $source = Core::invoke(self::$process[$name], array($source));
      } else {
        $p = Core::make(self::$process[$name]);
        if (!Core_Types::is_subclass_of('Text.Process.ProcessInterface', $p)) continue;
        $p->configure($config);
        $source = $p->process($source);
      }
    }
    return $source;
  }
  
  
}

interface Text_Process_ProcessInterface {
  public function process($source);
  public function configure($config);
}

interface Text_Process_UnparseInterface {
  public function unparse($input);
}
