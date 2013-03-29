<?php
/// <module name="Dev.Unit.Run.Text" version="0.3.0" maintainer="timokhin@techart.ru">

Core::load('Dev.Unit', 'IO.Stream', 'Time', 'CLI.Application');

/// <class name="Dev.Unit.Run.Text">
///   <implements interface="Core.ModuleInterface" />
///   <implements interface="CLI.RunInterface" />
///   <depends supplier="Dev.Unit.Run.Text.TestRunner" stereotype="creates" />
class Dev_Unit_Run_Text implements Core_ModuleInterface, CLI_RunInterface {

///   <constants>
  const MODULE  = 'Dev.Unit.Text';
  const VERSION = '0.3.0';


  const DATE_FORMAT = '%m-%d-%Y %H:%M';

  const DELIMITER1 = "----------------------------------------------------------------------";
  const DELIMITER2 = "======================================================================";
///   </constants>

///   <protocol name="building">

///   <method name="TestRunner" returns="Dev.Unit.Text.Run.TestRunner" scope="class">
///     <args>
///       <arg name="stream" type="IO.Stream.AbstractStream" />
///     </args>
///     <body>
  static public function TestRunner(IO_Stream_AbstractStream $stream = null) {
    return new Dev_Unit_Run_Text_TestRunner($stream);
  }
///     </body>
///   </method>

///   <method name="main" returns="int" scope="class">
///     <args>
///       <arg name="argv" type="array" />
///     </args>
///     <body>
  static public function main(array $argv) { return Core::with(new Dev_Unit_Run_Text_Application())->main($argv); }
///     </body>
///   </method>

///   </protocol>
}
/// </class>


/// <class name="Dev.Unit.Run.Text.Listener" extends="Dev.Unit.TestResultListener">
class Dev_Unit_Run_Text_TestResultListener extends Dev_Unit_TestResultListener {

  protected $stream;
  protected $current_name = '';
  protected $test_no      = 1;

///   <protocol name="creating">

///   <method name="__construct">
///     <args>
///       <arg name="stream" type="IO.Stream.AbstractStream" />
///     </args>
///     <body>
  public function __construct(IO_Stream_AbstractStream $stream) { $this->stream = $stream; }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="performing">

///   <method name="start_test" returns="Dev.Unit.Text.TestResult">
///     <args>
///       <arg name="test" type="Dev.Unit.TextCase" />
///     </args>
///     <body>
  public function on_start_test(Dev_Unit_TestResult $result, Dev_Unit_TestCase $test) {
    $name = Core_Types::virtual_class_name_for($test);
    return $this->format(
      '%3d. %-39s %-20s ',
      $this->test_no++,
      ($name != $this->current_name) ? ($this->current_name = $name) : '',
      $test->method);
  }
///     </body>
///   </method>


///   <method name="add_success" returns="Dev.Unit.Text.TestResult">
///     <args>
///       <arg name="test" type="Dev.Unit.TestCase" />
///     </args>
///     <body>
  public function on_add_success(Dev_Unit_TestResult $result, Dev_Unit_TestCase $test) {
    return $this->format("ok\n");
  }
///     </body>
///   </method>

///   <method name="add_error" returns="Dev.Unit.Text.TestResult">
///     <args>
///       <arg name="test" type="Dev.Unit.TestCase" />
///       <arg name="error" type="stdClass" />
///     </args>
///     <body>
  public function on_add_error(Dev_Unit_TestResult $result, Dev_Unit_TestCase $test, Dev_Unit_Error $error) {
    return $this->format("ERROR\n");
  }
///     </body>
///   </method>

///   <method name="on_add_failure" returns="Dev.Unit.Text.TestResult">
///     <args>
///       <arg name="test" type="Dev.Unit.TestCase" />
///       <arg name="failure" type="stdClass" />
///     </args>
///     <body>
  public function on_add_failure(Dev_Unit_TestResult $result, Dev_Unit_TestCase $test, Dev_Unit_Failure $failure) {
    return $this->format("FAIL\n");
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="supporting">

///   <method name="format" returns="Dev.Unit.Text.TestResultListener" access="private">
///     <body>
  private function format() {
    $args = func_get_args();
    $format = array_shift($args);
    $this->stream->write(vsprintf($format, $args));
    return $this;
  }
///     </body>
///   </method>

///   </protocol>

}
/// </class>


/// <class name="Dev.Unit.Run.Text.TestRunner">
///   <depends supplier="Dev.Unit.Text.TestResult" stereotype="uses" />
class Dev_Unit_Run_Text_TestRunner {

  protected $stream;

///   <protocol name="creating">

///   <method name="__construct">
///     <args>
///       <arg name="stream" type="IO.Stream.AbstractStream" />
///     </args>
///     <body>
  public function __construct(IO_Stream_AbstractStream $stream = null) {
    $this->stream = $stream ? $stream : IO::stdout();
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="performing">

///   <method name="run" returns="Dev.Unit.Run.Text.TestResult">
///     <args>
///       <arg name="Dev.Unit.RunInterface" />
///     </args>
///     <body>
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
///     </body>
///   </method>

///   </protocol>

///   <protocol name="supporting">

///   <method name="print_errors_for" returns="Dev.Unit.Run.Text.TestRunner" access="protected">
///     <args>
///       <arg name="result" type="Dev.Unit.TestResult" />
///     </args>
///     <body>
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
///     </body>
///   </method>


///   </protocol>
}
/// </class>


/// <class name="Dev.Unit.Run.Text.Application" extends="CLI.Application.Base">
class Dev_Unit_Run_Text_Application extends CLI_Application_Base {

///   <protocol name="performing">

///   <method name="run" returns="int">
///     <args>
///       <arg name="argv" type="array" />
///     </args>
///    <body>
  public function run(array $argv) {
    Dev_Unit_Run_Text::TestRunner()->run(
      Dev_Unit::TestLoader()->
        prefix($this->config->noprefix ? '' : $this->config->prefix)->
        from($argv)->
        suite);
    return 0;
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="supporting">

///   <method name="setup" returns="Dev.Unit.Test.Application" access="protected">
///     <body>
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
///     </body>
///   </method>

///   </protocol>
}
/// </class>

/// </module>
