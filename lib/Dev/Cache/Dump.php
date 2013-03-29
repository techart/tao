<?php
/// <module name="Dev.Cache.Dump" maintainer="svistunov@techart.ru" version="0.3.0">
///   <brief>CLI приложение для вывода дампа закешированного занчения по ключу</brief>
Core::load('CLI.Application', 'Cache');

/// <class name="Dev.Cache.Dump" stereotype="module">
///   <implements interface="Core.ModuleInterface" />
class Dev_Cache_Dump implements Core_ModuleInterface, CLI_RunInterface {
///   <constants>
  const VERSION = '0.3.0';
///   </constants>

///   <protocol name="performing">

///   <method name="main" scope="class">
///     <brief>Фабричный метод, возвращает объект приложения</brief>
///     <args>
///       <arg name="argv" type="array" brief="массив входным значений" />
///     </args>
///     <body>
  static public function main(array $argv) { Core::with(new Dev_Cache_Dump_Application())->main($argv); }
///     </body>
///   </method>

///   </protocol>
}
/// </class>

/// <class name="Dev.Cache.Dump.Application" extends="CLI.Application.Base">
///   <brief>Класс CLI приложения</brief>
class Dev_Cache_Dump_Application extends CLI_Application_Base {

///   <protocol name="performing">

///   <method name="run" returns="int">
///     <brief>Запускает приложение</brief>
///     <args>
///       <arg name="argv" type="array" brief="массив входным значений" />
///     </args>
///     <body>
  public function run(array $argv) {
    $cache = Cache::connect($this->config->dsn);

    if ($this->config->modules != null)
    foreach (Core_Strings::split_by(',', $this->config->modules) as $v)
      Core::load($v);

    foreach ($argv as $v) {
      IO::stdout()->write_line($v)->
        write_line(var_export($cache[$v], true));
    }
    return 0;
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="supporting">

///   <method name="setup" access="protected">
///   <brief>Устанавливает параметры CLI приложения</brief>
///     <body>
  protected function setup() {
    $this->options->
      brief('Dev.Cache.Dump '.Dev_Cache_Dump::VERSION.': TAO Cache dump utility')->
      string_option('dsn',      '-f', '--dsn',     'Cache backend DSN')->
      string_option('modules' , '-m', '--preload', 'Preload');

    $this->config->dsn = 'memcache://localhost/11211';
  }
///     </body>
///   </method>

///   </protocol>
}
/// </class>

/// </module>
