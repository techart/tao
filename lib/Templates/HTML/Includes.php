<?php

class Templates_HTML_Includes implements Core_ConfigurableModuleInterface {

  const VERSION = '0.1.0';
  
  static protected $options = array(
    'join_dir' => '/%files%/_joins',
 );

  static public function initialize(array $options = array()) {
    foreach(self::$options as $k => $v) self::$options[$k] = str_replace('%files%',Core::option('files_name'),$v);
    self::options($options);
  }
  
  static public function options(array $options = array()) {
    if (count($options)) Core_Arrays::update(self::$options, $options);
    return self::$options;
  }
  
  static public function option($name, $value = null) {
    $prev = isset(self::$options[$name]) ? self::$options[$name] : null;
    if ($value !== null) self::options(array($name => $value));
    return $prev;
  }
  

  static public function agregator() {
    $args = func_get_args();
    return Core::amake('Templates.HTML.Includes.Agregator', $args);
  }

}


interface Templates_HTML_Includes_AgregatorInterface extends IteratorAggregate {
/* public function __construct()*/
    public function files_list();
    public function join($v = true);
    public function exclude_join($file);
    public function add($file, $weight = 0, $type = 'app', $join = true);
    public function remove($file);
}


/// <class name="Templates.HTML.Includes.Agregator">
class Templates_HTML_Includes_Agregator implements Core_IndexedAccessInterface, Templates_HTML_Includes_AgregatorInterface {

  protected $files;
  protected $type;
  protected $do_join = false;
  protected $exclude_join = array();
  protected $preprocessors = array();
  protected $postprocessors = array();
  static protected $sort_types = array(
      'app', 'lib'
  );

  public function __construct($files = array(), $type = 'js') {
    $this->files = new ArrayObject($files);
    $this->type = $type;
    Core::load('Templates.HTML.Preprocess');
  }
  
  public function add_preprocessor($name, Templates_HTML_Preprocess_PreprocessorInterface $p) {
    $this->preprocessors[$name] = $p;
    return $this;
  }

  public function add_postprocessor($name, Templates_HTML_Postprocess_PostprocessorInterface $p) {
    $this->postprocessors[$name] = $p;
    return $this;
  }

  public function remove_postprocessor($name) {
    unset($this->postprocessors[$name]);
    return $this;
  }
  
  public function get_postprocessor($name) {
    return $this->postprocessors[$name];
  }
  
  public function remove_preprocessor($name) {
    unset($this->preprocessors[$name]);
    return $this;
  }
  
  public function get_preprocessor($name) {
    return $this->preprocessors[$name];
  }
  
  public function preprocess($file) {
    Events::call('templates.preprocess', $file);
    foreach ($this->preprocessors as $p)
      $file = $p->preprocess($file);
    return $file;
  }

  public function postprocess(&$path, &$content) {
    Events::call('templates.join_file', $path, $content);
    foreach($this->postprocessors as $p)
      $p->postprocess($path, $content);
    return $this;
  }

  public function add($file, $weight = 0, $type = 'app', $join = true, $immutable = false) {
   $file = (string) $file;
   $el = isset($this->files[$file])? $this->files[$file] : null;
   if (!empty($el) && $el['immutable']) return $this;
   
    $this->files[$file] = new ArrayObject(array(
        'weight' => $weight,
        'type' => $type,
        'join' => in_array($file, $this->exclude_join) ? false : $join,
        'immutable' => $immutable
    ));
    return $this;
  }

  public function join($v = true) {
    $this->do_join = $v;
    return $this;
  }

  public function exclude_join($file) {
    if (isset($this[$file]))
        $this[$file]['join'] = false;
    else
        $this->exclude_join[] = $file;
  }

  public function remove($file) {
    unset($this->files[$file]);
    return $this;
  }

  public function sort($a, $b) {
    switch (true) {
      case $a['type'] == 'app' && $b['type'] == 'lib':
          return 1;
      case $a['type'] == 'lib' && $b['type'] == 'app':
          return -1;
      case $a['type'] == $b['type']:
          if ($a['weight'] == $b['weight'])
              return 0;
          return $a['weight'] < $b['weight'] ? -1 : 1;
    }
  }


  protected function files_hash($files) {
    $s = '';
    foreach($files as $file) {
        $mt = @filemtime('.' . $file);
        if ($mt) {
          $s .= $file;
          $s .= $mt;
        }
    }
    return md5($s);
  }

  protected function joins_dir($file = '') {
    $dir = Templates_HTML_Includes::option('join_dir');
    if (!is_dir('.' . $dir))
      IO_FS::mkdir('.' . $dir, IO_FS::option('dir_mod'), true);
    return "$dir/$file";
  }
  
  protected function file_path($file) {
    $file = $this->preprocess($file);
    $method = $this->type . '_path';
    $path = ltrim(Templates_HTML::$method($file), '/');
    return Templates_HTML::is_url($path) ? $path : '/' . $path;
  }

  protected function join_files($files) {
    if (empty($files)) return null;
    $filename = $this->joins_dir($this->files_hash($files).".{$this->type}");
    $filepath = '.' . $filename;
    if (!is_file($filepath)) {
        $out = '';
        foreach($files as $file) {
            $path = '.' . $file;
            $out .= "\n\n";
            $out .= file_exists($path) ?  file_get_contents($path) : '';
            if ($this->type == 'js')
              $out .= ';';
        }
        $this->postprocess($filepath , $out);
        file_put_contents($filepath, $out);
        IO_FS::chmod($filepath, IO_FS::option('file_mod'));
    }
    return $filename;
  }
  
  protected function auto_weigth() {
    $types_weight = array();
    $delta = 0.0000001;
    foreach ($this->files as $f) {
      if ($f['weight'] == 0) {
        if (!isset($types_weight[$f['type']])) $types_weight[$f['type']] = 0;
        $types_weight[$f['type']] += $delta;
        $f['weight'] = $types_weight[$f['type']];
      }
    }
  }


  public function files_list() {
    $this->auto_weigth();
    $this->files->uasort(array($this, 'sort'));
    $to_join = array();
    $as_is = array();
    $i = 0;
    $join_index = 0;
    foreach ($this->files as $file => $data) {
      $file = $this->file_path($file);
      if ($data['join'] && $this->do_join && !Templates_HTML::is_url($file)) {
          $join_index = $join_index == 0 ? $i : $join_index;
          $to_join[$i] = $file;
      }
      else $as_is[$i] = $file;
      $i++;
    }
    if (!empty($to_join))
        $as_is[$join_index] = $this->join_files($to_join);
    ksort($as_is);
    return $as_is;
  }

  public function getIterator() {
      return new ArrayIterator($this->files_list());
  }

///   <protocol name="indexing" interface="Core.IndexedPropertyAccessInterface">

///   <method name="offsetGet" returns="mixed">
///     <args>
///       <arg name="index" type="string" brief="имя блока" />
///     </args>
///     <body>
  public function offsetGet($index) {
   return $this->files[$index];
  }
///     </body>
///   </method>

///   <method name="offsetSet" returns="mixed">
///     <args>
///       <arg name="index" type="string" brief="имя блока" />
///       <arg name="value" brief="значение" />
///     </args>
///     <body>
  public function offsetSet($index, $value) {
    return $this->add($index,
      empty($value['weight']) ? 0 : $value['weight'],
      empty($value['type']) ? 'app' : $value['type'],
      empty($value['join']) ? true : $value['join'],
      !isset($value['immutable']) ? false : $value['immutable']);
  }
///     </body>
///   </method>

///   <method name="offsetExists" returns="boolean">
///     <args>
///       <arg name="index" type="string" brief="имя блока" />
///     </args>
///     <body>
  public function offsetExists($index) { return isset($this->files[$index]); }
///     </body>
///   </method>

///   <method name="offsetUnset">
///     <args>
///       <arg name="index" type="string" brief="имя блока" />
///     </args>
///     <body>
  public function offsetUnset($index) { unset($this->files[$index]); }
///     </body>
///   </method>

///   </protocol>

}
/// </class>
