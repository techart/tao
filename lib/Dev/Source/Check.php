<?php
/// <module name="Dev.Source.Check" maintainer="svistunov@techart.ru" version="0.3.0">
Core::load('Object', 'CLI.Application', 'Dev.Source');

/// <class name="Dev.Source.Check" stereotype="module">
///   <implements interface="Core.ModuleInterface" />
///   <implements interface="CLI.RunInterface" />
class Dev_Source_Check implements Core_ModuleInterface, CLI_RunInterface {
///   <constants>
  const VERSION = '0.3.0';
///   </constants>

///   <protocol name="performing">
///   <method name="main" scope="class">
///     <args>
///       <arg name="argv" type="array" />
///     </args>
///     <body>
  static public function main(array $argv) { Core::with(new Dev_Source_Check_Application())->main($argv); }
///     </body>
///   </method>
///   </protocol>

}
/// </class>

/// <interface name="Dev.Source.Check.Checker" >
interface Dev_Source_Check_Checker {

///   <protocol name="perfoming">

///   <method name="run" >
///     <args>
///       <arg name="module" type="Dev.Source.Module" />
///       <arg name="result" type="Dev.Source.Check.Result"/>
///     </args>
///     <body>
  public function run(Dev_Source_Module $module, Dev_Source_Check_Result $result);
///     </body>
///   </method>

///   </protocol>

}
/// </interface>

/// <class name="Dev.Source.Check.GroupChecker" extends="Dev.Source.Check.Checker">
///   <implements interface="Dev.Source.Check.Checker" />
class Dev_Source_Check_GroupChecker implements Dev_Source_Check_Checker {

  protected $checkers;

///   <protocol name="creating">
///   <method name="__construct">
///     <body>
  public function __construct() {
    $arg = Core::normalize_args(func_get_args());
    $this->checkers = array();
    if ($arg != null)
      foreach ($arg as $k => $v)
        $this->add_checker($v);
  }
///     </body>
///   </method>
///   </protocol>

///   <protocol name="perfoming">

///   <method name="run" returns="Dev.Source.Check.GroupCheker">
///     <args>
///       <arg name="module" type="Dev.Source.Module" />
///       <arg name="result" type="Dev.Source.Check.Result"/>
///     </args>
///     <body>
  public function run(Dev_Source_Module $module, Dev_Source_Check_Result $result) {
    foreach ($this->checkers as $checker)
      $checker->run($module, $result);
    return $this;
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="supporting">

///   <method name="add_checker" returns="Dev.Source.Check.GroupChecker">
///     <args>
///       <arg name="checker" type="Dev.Source.Check.Checker" />
///     </args>
///     <body>
  public function add_checker(Dev_Source_Check_Checker $checker) {
    $this->checkers[] = $checker;
    return $this;
  }
///     </body>
///   </method>

///   </protocol>

}
/// </class>

/// <class name="Dev.Source.Check.Result" extends="Data.Object">
///   <implements interface="IteratorAggregate" />
class Dev_Source_Check_Result extends Object_Struct implements IteratorAggregate {

  protected $errors;

///   <protocol name="creating">

///   <method name="__construct">
///     <body>
  public function __construct() { $this->errors = Core::hash(); }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="supporting">

///   <method name="set_errors">
///     <body>
  protected function set_errors() { throw new Core_ReadOnlyPropertyException('errors'); }
///     </body>
///   </method>

///   <method name="is_ok" returns="boolean">
///     <body>
  public function is_ok() { return (boolean) count($this->errors); }
///     </body>
///   </method>

///   <method name="add_error">
///     <args>
///       <arg name="checker" type="Dev.Source.Check.Checker" />
///       <arg name="module" type="Dev.Source.Module" />
///       <arg name="error" type="string" />
///     </args>
///     <body>
  public function add_error(Dev_Source_Check_Checker $checker, Dev_Source_Module $module, $error) {
    $this->errors[] =
      (object) array(
        'checker' => $checker,
        'module' => $module,
        'error' => $error);
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="iterating" interface="IteratorAggregate">

///   <method name="getIterator" returns="Iterator">
///     <body>
  public function getIterator() {
    return $this->errors->getIterator();
  }
///     </body>
///   </method>

///   </protocol>

}
/// </class>

/// <class name="Dev.Source.Check.Runner">
class Dev_Source_Check_Runner {

///   <protocol name="supporting">

///   <method name="run">
///     <args>
///       <arg name="modules" type="Dev.Source.LibraryIteratorInterface" />
///       <arg name="checker" type="Dev.Source.Check.Checker" />
///       <arg name="result" type="Dev.Source.Check.Result" default="null" />
///     </args>
///     <body>
  public function run(Dev_Source_LibraryIteratorInterface $modules, Dev_Source_Check_Checker $checker, Dev_Source_Check_Result $result = null) {
    $result = Core::if_null($result, new Dev_Source_Check_Result());
    foreach ($modules as $module_name => $module) {
      $checker->run($module, $result);
    }
  }
///     </body>
///   </method>

///   </protocol>
}
/// </class>

/// <class name="Dev.Source.Check.NoTabChecker" extends="Dev.Source.Check.Checker">
///   <implements interface="Dev.Source.Check.Checker" />
class Dev_Source_Check_NoTabChecker implements Dev_Source_Check_Checker {

///   <protocol name="perfoming">

///   <method name="run">
///     <args>
///       <arg name="module" type="Dev.Source.Module" />
///       <arg name="result" type="Dev.Source.Check.Result" default="null" />
///     </args>
///     <body>
  public function run(Dev_Source_Module $module, Dev_Source_Check_Result $result) {
    foreach ($module->file as $line_number => $line) {
      if (Core_Strings::contains($line, "\t"))
        $result->add_error($this, $module, "Tab on line $line_number");
    }
    $module->file->close();
  }
///     </body>
///   </method>

///   </protocol>
}
/// </class>

/// <class name="Dev.Source.Check.NoEndCharsChecker" extends="Dev.Source.Check.Checker">
///   <implements interface="Dev.Source.Check.Checker" />
class Dev_Source_Check_NoEndCharsChecker implements Dev_Source_Check_Checker {

///   <protocol name="perfoming">

///   <method name="run">
///     <args>
///       <arg name="module" type="Dev.Source.Module" />
///       <arg name="result" type="Dev.Source.Check.Result" />
///     </args>
///     <body>
  public function run(Dev_Source_Module $module, Dev_Source_Check_Result $result) {
    $s = $module->file->open('r')->text();
    $s->seek(-2, SEEK_END);
    $last_line = $s->read_line();
    if ($last_line != '?>') $result->add_error($this, $module, "Error End file");
    $s->close();
  }
///     </body>
///   </method>

///   </protocol>
}
/// </class>

/// <class name="Dev.Source.Check.VersionChecker" extends="Dev.Source.Check.Checker">
///   <implements interface="Dev.Source.Check.Checker" />
class Dev_Source_Check_VersionChecker implements Dev_Source_Check_Checker {

///   <protocol name="perfoming">

///   <method name="run">
///     <args>
///       <arg name="module" type="Dev.Source.Module" />
///       <arg name="result" type="Dev.Source.Check.Result" />
///     </args>
///     <body>
  public function run(Dev_Source_Module $module, Dev_Source_Check_Result $result) {
    foreach ($module->file as $line_number => $line) {
      $m1 = Core_Regexps::match_with_results('{^/'.'//\s+<module.*version=["\'](\d\.\d\.\d)["\']}', $line);
      if ($m1) $comment_version = $m1[1];
      $m2 = Core_Regexps::match_with_results('{.*const.*(?:VERSION|version)\s*=\s*["\'](\d\.\d\.\d)["\']}', $line);
      if ($m2) $code_version = $m2[1];
    }
    if ($comment_version!=null && $code_version != $comment_version) $result->add_error($this, $module, "Version in comment and in code not equal");
    $module->file->close();
  }
///     </body>
///   </method>

///   </protocol>
}
/// </class>

/// <class name="Dev.Source.Check.NamesChecker" extends="Dev.Source.Check.Checker">
///   <implements interface="Dev.Source.Check.Checker" />
class Dev_Source_Check_NamesChecker implements Dev_Source_Check_Checker {

///   <protocol name="perfoming">

///   <method name="run">
///     <args>
///       <arg name="module" type="Dev.Source.Module" />
///       <arg name="result" type="Dev.Source.Check.Result"/>
///     </args>
///     <body>
  public function run(Dev_Source_Module $module, Dev_Source_Check_Result $result) {
    $comment_name = false;
    $code_name = false;
    foreach ($module->file as $line_number => $line) {
      if (!$comment_name) {
        $m1 = Core_Regexps::match_with_results('{/'.'//\s+<(class|interface|method).*name="([^"\']+)"}', $line);
        if ($m1) {
          $comment_name=$m1[2];
          $code_name=false;
        }
      }
      if (!$code_name && $comment_name) {
        $m2 = Core_Regexps::match_with_results( '{^[a-zA-Z\s]*(class|interface|function)\s+([a-zA-Z_0-9]+)}', $line);
        if ($m2) {
          $code_name = $m2[2];
          if ($m2[1] != 'function') $code_name = Core_Strings::replace($code_name, '_', '.');
          if ($code_name != $comment_name)
            $result->add_error($this, $module, "names no equal {$code_name}, {$comment_name} in line {$line_number}");
          $comment_name = false;
        }
      }
    }
    $module->file->close();
  }
///     </body>
///   </method>

///   </protocol>
}
/// </class>

/// <class name="Dev.Source.Check.ValidXMLCommentChecker" extends="Dev.Source.Check.Checker">
///   <implements interface="Dev.Source.Check.Checker" />
class Dev_Source_Check_ValidXMLCommentChecker implements Dev_Source_Check_Checker {
  protected $result;
  protected $module;

  public function error_handler($errno, $errstr, $errfile, $errline) {
    $this->result->add_error($this, $this->module, Core_Regexps::replace('/DOMDocument::\s*[a-zA-Z()]+\s*:/', '', $errstr));
  }

///   <protocol name="perfoming">

///   <method name="run">
///     <args>
///       <arg name="module" type="Dev.Source.Module" />
///       <arg name="result" type="Dev.Source.Check.Result" />
///     </args>
///     <body>
  public function run(Dev_Source_Module $module, Dev_Source_Check_Result $result) {
    $this->result = $result;
    $this->module = $module;
    try {
      $xml = Dev_Source::Library($module->name)->xml;
      set_error_handler(array($this, 'error_handler'), E_WARNING);
      $xml->relaxNGValidate('etc/tao-doc.rng');
      restore_error_handler();
    } catch (Dev_Source_InvalidSourceException $e) {
      foreach ($e->errors as $error)
        $result->add_error($this, $module, Core_Strings::format("%s: %d : %s", $module->name, $error->line, $error->message));
    }
  }
///     </body>
///   </method>

///   </protocol>
}
/// </class>

/// <class name="Dev.Source.Check.Application" extends="CLI.Application.Base">
class Dev_Source_Check_Application extends CLI_Application_Base {

///   <protocol name="supporting">

///   <method name="output">
///     <args>
///       <arg name="result" type="Dev.Source.Check.Result" />
///     </args>
///     <body>
  public function output($result) {
    foreach ($result as $error_struct)
      printf("%s:%s: %s\n", Core_Types::class_name_for($error_struct->checker, true),
        $error_struct->module->name, $error_struct->error);
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="performing">

///   <method name="run" returns="int">
///     <args>
///       <arg name="argv" type="array" />
///     </args>
///     <body>
  public function run(array $argv) {
    $runner = new Dev_Source_Check_Runner();
    $checker = new Dev_Source_Check_GroupChecker(
      new Dev_Source_Check_NamesChecker(),
      new Dev_Source_Check_NoEndCharsChecker(),
      new Dev_Source_Check_NoTabChecker(),
      new Dev_Source_Check_VersionChecker(),
      new Dev_Source_Check_ValidXMLCommentChecker());
    $result = new Dev_Source_Check_Result();

    $runner->run(isset($this->config->library) ?
      Dev_Source::LibraryDirIterator($this->config->library) :
      Dev_Source::Library($argv) , $checker, $result);
    $this->output($result);
    return 0;
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="supporting">

///   <method name="setup" access="protected">
///     <body>
  protected function setup() {
    $this->options->
      brief('Dev.Source.Check '.Dev_Source_Check::VERSION.' TAO code checker')->
      string_option('library', '-l', '--library', 'Path to library');
  }
///     </body>
///   </method>

///   </protocol>

}
/// </class>

/// </module>
