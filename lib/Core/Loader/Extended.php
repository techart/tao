<?php
/**
 * @package Core\Loader\Extended
 */


class Core_Loader_Extended implements Core_ModuleInterface {
  const VERSION = '0.1.0';
  
  static public function loader() {
    return new Core_Loader_Extended_Loader();
  }
}

class Core_Loader_Extended_Loader extends Core_ModuleLoader {

  protected $replace_parms = array();
  
  public function file_path_for($module, $first = false) {
    $file = null;
    foreach ($this->paths as $name => $roots) {
      if (!is_array($roots)) {
        $roots = array($roots);
      }
      $drop_prefix = $this->extract_prefix_from($name);
      $parms = $this->parse_params_from($name);
      foreach ($roots as $root) {
        $module_template = $this->create_module_template_from($name);
        if (preg_match($module_template, $module, $module_match)) {
          $prefix = $this->set_parms_to($drop_prefix, $parms, $module_match);
          $root = $this->set_parms_to($root, $parms, $module_match);
          $file = $this->compose_file_path_for($module, $root, $prefix);
          $file = $this->process_not_necessarily_part($file);
          if (!is_file($file) && !$first) {
            continue;
          } else {
            return $file;
          }
        }
      }
    }
    if (!is_null($file)) {
      return $file;
    }
    if (Core::option('spl_autoload')) return false;
    else throw new Core_ModuleNotFoundException($module);
  }
  
  protected function process_not_necessarily_part($file) {
    $orig_file = $file;
    if (preg_match_all('{\([^)]*\)\?}', $file, $matches)) {
      $files = array($file);
      foreach ($matches as $result_match) {
        foreach ($result_match as $m) {
          foreach ($files as $k => $f) {
            $files[] = str_replace($m, '', $f);
            $files[] = str_replace($m, str_replace(array('(', ')', '?'),'', $m), $f);
            unset($files[$k]);
          }
        }
      }
    } else {
      $files = array($file);
    }
    foreach ($files as $fk => $fv) {
       if (preg_match_all('{\(([^)|]+)\|([^)|]+)\)}', $fv, $matches)) {
          $files[$fk] = str_replace($matches[0], $matches[2], $fv);
          $files[] = str_replace($matches[0], $matches[1], $fv);
       }
    }
    foreach (array_reverse($files) as $f) 
      if ( ($path = realpath($f)) && is_file($path) ) {
        $file = $path;
        break;
      }
    if ($orig_file == $file) $file = $f;
    return $file;
  }
  
  protected function extract_prefix_from(&$name) {
    $res = false;
    $num = 0;
    while ($name[0] == '-') {
      $num++;
      $name = substr($name, 1);
    }
    if ($num > 0) {
      $parts = explode('.', $name);
      $res = implode('.', array_slice($parts, 0, $num));
    }
    return $res;
  }
  
  protected function parse_params_from(&$name) {
    $name = preg_replace_callback('/{([a-z][a-zA-Z0-9_]*)(?::([^}]+))?}/', array($this, 'replace_callback') , $name);
    $params = $this->replace_parms;
    $this->replace_parms = array();
    return $params;
  }
  
  protected function create_module_template_from($name) {
    return $name == '*' ? '{.+}' : '{^'.$name.'}';
  }
  
  protected function set_parms_to($root, $parms, $values) {
    foreach ($parms as $i => $n) {
      $root = str_replace("{{$n}}", $values[$i+1], $root);
    }
    return $root;
  }
  
  protected function compose_file_path_for($module, $root, $drop_prefix) {
    $module =  $this->drop_prefix_for($module, $drop_prefix);
    return $root . '/' . str_replace('.', '/', $module) . '.php';
  }
  
  protected function drop_prefix_for($module, $prefix) {
    return !empty($prefix) ? preg_replace("{^$prefix.}", '', $module) : $module;
  }
  
  protected function replace_callback($match) {
    $name = $match[1];
    $this->replace_parms[] = $name;
    $template = !empty($match[2]) ? $match[2] : '([^./]+)';
    return $template;
  }
}
