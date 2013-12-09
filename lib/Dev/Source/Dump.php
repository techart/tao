<?php
/**
 * Dev.Source.CreateXML
 * 
 * @package Dev\Source\Dump
 * @version 0.3.0
 */
Core::load('CLI.Application', 'IO.FS', 'Dev.Source');

/**
 * @package Dev\Source\Dump
 */
class Dev_Source_Dump implements Core_ModuleInterface, CLI_RunInterface {
  const VERSION = '0.3.0';


/**
 * @param array $argv
 */
  static public function main(array $argv) { Core::with(new Dev_Source_Dump_Application())->main($argv); }

}

/**
 * @package Dev\Source\Dump
 */
class Dev_Source_Dump_Application extends CLI_Application_Base {


/**
 * @param array $argv
 * @return int
 */
  public function run(array $argv) {
    Core::with($this->config->output ?
      IO_FS::File($this->config->output)->open('w+') :
      IO::stdout())->write(Dev_Source::Library($argv)->xml->SaveXML());
    return 0;
  }



/**
 */
  protected function setup() {
    $this->options->
      brief('Dev.Source.Dump '.Dev_Source_Dump::VERSION.': TAO module document dump utility')->
      string_option('output', '-o', '--output', 'Output file');

    $this->config->output = null;
  }

}

