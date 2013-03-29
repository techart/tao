<?php
/// <module name="Dev.Source.CreateXML" maintainer="svistunov@techart.ru" version="0.3.0">
Core::load('CLI.Application', 'IO.FS', 'Dev.Source');

/// <class name="Dev.Source.Dump" stereotype="module">
///   <implements interface="Core.ModuleInterface" />
class Dev_Source_Dump implements Core_ModuleInterface, CLI_RunInterface {
///   <constants>
  const VERSION = '0.3.0';
///   </constants>

///   <protocol name="performing">

///   <method name="main" scope="class">
///     <args>
///       <arg name="argv" type="array" />
///     </args>
///     <body>
  static public function main(array $argv) { Core::with(new Dev_Source_Dump_Application())->main($argv); }
///     </body>
///   </method>

///   </protocol>
}
/// </class>

/// <class name="Dev.Source.Dump.Application" extends="CLI.Application.Base">
class Dev_Source_Dump_Application extends CLI_Application_Base {

///   <protocol name="performing">

///   <method name="run" returns="int">
///     <args>
///       <arg name="argv" type="array" />
///     </args>
///     <body>
  public function run(array $argv) {
    Core::with($this->config->output ?
      IO_FS::File($this->config->output)->open('w+') :
      IO::stdout())->write(Dev_Source::Library($argv)->xml->SaveXML());
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
      brief('Dev.Source.Dump '.Dev_Source_Dump::VERSION.': TAO module document dump utility')->
      string_option('output', '-o', '--output', 'Output file');

    $this->config->output = null;
  }
///     </body>
///   </method>

///   </protocol>
}
/// </class>

/// </module>
