<?php
/**
 * Dev.Unit.Run.Text
 * 
 * @package Dev\Unit\Run\Text
 * @version 0.3.0
 */

Core::load('Dev.Unit', 'IO.Stream', 'Time', 'CLI.Application');

/**
 * @package Dev\Unit\Run\Text
 */
class Dev_Unit_Run_Text implements Core_ModuleInterface, CLI_RunInterface {

  const MODULE  = 'Dev.Unit.Text';
  const VERSION = '0.3.0';


  const DATE_FORMAT = '%m-%d-%Y %H:%M';

  const DELIMITER1 = "----------------------------------------------------------------------";
  const DELIMITER2 = "======================================================================";


/**
 * @param IO_Stream_AbstractStream $stream
 * @return Dev_Unit_Text_Run_TestRunner
 */
  static public function TestRunner(IO_Stream_AbstractStream $stream = null) {
    return new Dev_Unit_Run_Text_TestRunner($stream);
  }

/**
 * @param array $argv
 * @return int
 */
  static public function main(array $argv) { return Core::with(new Dev_Unit_Run_Text_Application())->main($argv); }

}


/**
 * @package Dev\Unit\Run\Text
 */
class Dev_Unit_Run_Text_TestResultListener extends Dev_Unit_TestResultListener {

  protected $stream;
  protected $current_name = '';
  protected $test_no      = 1;


/**
 * @param IO_Stream_AbstractStream $stream
 */
  public function __construct(IO_Stream_AbstractStream $stream) { $this->stream = $stream; }



/**
 * @param Dev_Unit_TextCase $test
 * @return Dev_Unit_Text_TestResult
 */
  public function on_start_test(Dev_Unit_TestResult $result, Dev_Unit_TestCase $test) {
    $name = Core_Types::virtual_class_name_for($test);
    return $this->format(
      '%3d. %-39s %-20s ',
      $this->test_no++,
      ($name != $this->current_name) ? ($this->current_name = $name) : '',
      $test->method);
  }


/**
 * @param Dev_Unit_TestCase $test
 * @return Dev_Unit_Text_TestResult
 */
  public function on_add_success(Dev_Unit_TestResult $result, Dev_Unit_TestCase $test) {
    return $this->format("ok\n");
  }

/**
 * @param Dev_Unit_TestCase $test
 * @param stdClass $error
 * @return Dev_Unit_Text_TestResult
 */
  public function on_add_error(Dev_Unit_TestResult $result, Dev_Unit_TestCase $test, Dev_Unit_Error $error) {
    return $this->format("ERROR\n");
  }

/**
 * @param Dev_Unit_TestCase $test
 * @param stdClass $failure
 * @return Dev_Unit_Text_TestResult
 */
  public function on_add_failure(Dev_Unit_TestResult $result, Dev_Unit_TestCase $test, Dev_Unit_Failure $failure) {
    return $this->format("FAIL\n");
  }



/**
 * @return Dev_Unit_Text_TestResultListener
 */
  private function format() {
    $args = func_get_args();
    $format = array_shift($args);
    $this->stream->write(vsprintf($format, $args));
    return $this;
  }


}


/**
 * @package Dev\Unit\Run\Text
 */
class Dev_Unit_Run_Text_TestRunner {

  protected $stream;


/**
 * @param IO_Stream_AbstractStream $stream
 */
  public function __construct(IO_Stream_AbstractStream $stream = null) {
    $this->stream = $stream ? $stream : IO::stdout();
  }



/**
 * @param  $Dev.Unit.RunInterface
 * @return Dev_Unit_Run_Text_TestResult
 */
  public function run(Dev_Unit_RunInterface $test) {

    $result = Dev_Unit::TestResult()->
      listener(new Dev_Unit_Run_Text_TestResultListener($this->stream));


    $started_at = Time::now();

    $this->stream->
      format("Started at %s\n", $started_at->format(Dev_Unit_Run_Text::DATE_FORMAT))->
      write(Dev_Unit_Run_Text::DELIMITER1."\n");

    $test->run($result);
    $finished_at = Time::now();

    $this->print_errors_for($result);

    $this->stream->
      format(
        "%s\nFinished at %s (%ds). Tests: %d\n",
        Dev_Unit_Run_Text::DELIMITER2,
        $finished_at->format(Dev_Unit_Run_Text::DATE_FORMAT),
        Time::seconds_between($finished_at, $started_at),
        $result->tests_ran);

      if ($result->was_successful()) {
      $this->stream->write("OK\n\n");
    } else {
      $this->stream->write('FAILED (');
      if ($result->num_of_failures)
        $this->stream->format('failures=%d', $result->num_of_failures);
      if ($result->num_of_errors)
        $this->stream->format(($result->num_of_failures ? ', ' : '').'errors=%d', $result->num_of_errors);
      $this->stream->write(")\n\n");
    }

    return $result;
  }



/**
 * @param Dev_Unit_TestResult $result
 * @return Dev_Unit_Run_Text_TestRunner
 */
  protected function print_errors_for(Dev_Unit_TestResult $result) {
    foreach (array('failures', 'errors') as $flavour) {
      if (Core::with_attr($result, "num_of_$flavour") > 0) {
        $this->stream->format("%s\n%s\n", Dev_Unit_Run_Text::DELIMITER1, Core_Strings::upcase($flavour));
        foreach ($result->$flavour as $item)
          $this->stream->format(
            "%s (%d)\n  %s\n",
            $item->file,
            $item->line,
            $item->message);
      }
    }

    return $this;
  }


}


/**
 * @package Dev\Unit\Run\Text
 */
class Dev_Unit_Run_Text_Application extends CLI_Application_Base {


/**
 * @param array $argv
 * @return int
 */
  public function run(array $argv) {
    Dev_Unit_Run_Text::TestRunner()->run(
      Dev_Unit::TestLoader()->
        prefix($this->config->noprefix ? '' : $this->config->prefix)->
        from($argv)->
        suite);
    return 0;
  }



/**
 * @return Dev_Unit_Test_Application
 */
  protected function setup() {
    $this->options->
      brief('Dev.Unit.Run.Text'.Dev_Unit_Run_Text::VERSION."\n".
            'Specify test class or test module names as arguments')->
      string_option('prefix', '-p', '--prefix', 'Test class prefix')->
      boolean_option('noprefix', '-n', '--no-prefix', 'Don\'t use class prefix');

    $this->log->dispatcher->
      to_stream(IO::stderr())->
        where('module', '=', Core_Types::module_name_for($this));

    $this->config->prefix = 'Test.';
    $this->config->noprefix = false;
  }

}

