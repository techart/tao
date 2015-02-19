<?php
/**
 * Dev.Source.Check
 *
 * @package Dev\Source\Check
 * @version 0.3.0
 */
Core::load('Object', 'CLI.Application', 'Dev.Source');

/**
 * @package Dev\Source\Check
 */
class Dev_Source_Check implements Core_ModuleInterface, CLI_RunInterface
{
	const VERSION = '0.3.0';

	/**
	 * @param array $argv
	 */
	static public function main(array $argv)
	{
		Core::with(new Dev_Source_Check_Application())->main($argv);
	}

}

/**
 * @package Dev\Source\Check
 */
interface Dev_Source_Check_Checker
{

	/**
	 * @param Dev_Source_Module       $module
	 * @param Dev_Source_Check_Result $result
	 */
	public function run(Dev_Source_Module $module, Dev_Source_Check_Result $result);

}

/**
 * @package Dev\Source\Check
 */
class Dev_Source_Check_GroupChecker implements Dev_Source_Check_Checker
{

	protected $checkers;

	/**
	 */
	public function __construct()
	{
		$arg = Core::normalize_args(func_get_args());
		$this->checkers = array();
		if ($arg != null) {
			foreach ($arg as $k => $v)
				$this->add_checker($v);
		}
	}

	/**
	 * @param Dev_Source_Module       $module
	 * @param Dev_Source_Check_Result $result
	 *
	 * @return Dev_Source_Check_GroupCheker
	 */
	public function run(Dev_Source_Module $module, Dev_Source_Check_Result $result)
	{
		foreach ($this->checkers as $checker)
			$checker->run($module, $result);
		return $this;
	}

	/**
	 * @param Dev_Source_Check_Checker $checker
	 *
	 * @return Dev_Source_Check_GroupChecker
	 */
	public function add_checker(Dev_Source_Check_Checker $checker)
	{
		$this->checkers[] = $checker;
		return $this;
	}

}

/**
 * @package Dev\Source\Check
 */
class Dev_Source_Check_Result extends Object_Struct implements IteratorAggregate
{

	protected $errors;

	/**
	 */
	public function __construct()
	{
		$this->errors = Core::hash();
	}

	/**
	 */
	protected function set_errors()
	{
		throw new Core_ReadOnlyPropertyException('errors');
	}

	/**
	 * @return boolean
	 */
	public function is_ok()
	{
		return (boolean)count($this->errors);
	}

	/**
	 * @param Dev_Source_Check_Checker $checker
	 * @param Dev_Source_Module        $module
	 * @param string                   $error
	 */
	public function add_error(Dev_Source_Check_Checker $checker, Dev_Source_Module $module, $error)
	{
		$this->errors[] =
			(object)array(
				'checker' => $checker,
				'module' => $module,
				'error' => $error);
	}

	/**
	 * @return Iterator
	 */
	public function getIterator()
	{
		return $this->errors->getIterator();
	}

}

/**
 * @package Dev\Source\Check
 */
class Dev_Source_Check_Runner
{

	/**
	 * @param Dev_Source_LibraryIteratorInterface $modules
	 * @param Dev_Source_Check_Checker            $checker
	 * @param Dev_Source_Check_Result             $result
	 */
	public function run(Dev_Source_LibraryIteratorInterface $modules, Dev_Source_Check_Checker $checker, Dev_Source_Check_Result $result = null)
	{
		$result = Core::if_null($result, new Dev_Source_Check_Result());
		foreach ($modules as $module_name => $module) {
			$checker->run($module, $result);
		}
	}

}

/**
 * @package Dev\Source\Check
 */
class Dev_Source_Check_NoTabChecker implements Dev_Source_Check_Checker
{

	/**
	 * @param Dev_Source_Module       $module
	 * @param Dev_Source_Check_Result $result
	 */
	public function run(Dev_Source_Module $module, Dev_Source_Check_Result $result)
	{
		foreach ($module->file as $line_number => $line) {
			if (Core_Strings::contains($line, "\t")) {
				$result->add_error($this, $module, "Tab on line $line_number");
			}
		}
		$module->file->close();
	}

}

/**
 * @package Dev\Source\Check
 */
class Dev_Source_Check_NoEndCharsChecker implements Dev_Source_Check_Checker
{

	/**
	 * @param Dev_Source_Module       $module
	 * @param Dev_Source_Check_Result $result
	 */
	public function run(Dev_Source_Module $module, Dev_Source_Check_Result $result)
	{
		$s = $module->file->open('r')->text();
		$s->seek(-2, SEEK_END);
		$last_line = $s->read_line();
		if ($last_line != '?>') {
			$result->add_error($this, $module, "Error End file");
		}
		$s->close();
	}

}

/**
 * @package Dev\Source\Check
 */
class Dev_Source_Check_VersionChecker implements Dev_Source_Check_Checker
{

	/**
	 * @param Dev_Source_Module       $module
	 * @param Dev_Source_Check_Result $result
	 */
	public function run(Dev_Source_Module $module, Dev_Source_Check_Result $result)
	{
		foreach ($module->file as $line_number => $line) {
			$m1 = Core_Regexps::match_with_results('{^/' . '//\s+<module.*version=["\'](\d\.\d\.\d)["\']}', $line);
			if ($m1) {
				$comment_version = $m1[1];
			}
			$m2 = Core_Regexps::match_with_results('{.*const.*(?:VERSION|version)\s*=\s*["\'](\d\.\d\.\d)["\']}', $line);
			if ($m2) {
				$code_version = $m2[1];
			}
		}
		if ($comment_version != null && $code_version != $comment_version) {
			$result->add_error($this, $module, "Version in comment and in code not equal");
		}
		$module->file->close();
	}

}

/**
 * @package Dev\Source\Check
 */
class Dev_Source_Check_NamesChecker implements Dev_Source_Check_Checker
{

	/**
	 * @param Dev_Source_Module       $module
	 * @param Dev_Source_Check_Result $result
	 */
	public function run(Dev_Source_Module $module, Dev_Source_Check_Result $result)
	{
		$comment_name = false;
		$code_name = false;
		foreach ($module->file as $line_number => $line) {
			if (!$comment_name) {
				$m1 = Core_Regexps::match_with_results('{/' . '//\s+<(class|interface|method).*name="([^"\']+)"}', $line);
				if ($m1) {
					$comment_name = $m1[2];
					$code_name = false;
				}
			}
			if (!$code_name && $comment_name) {
				$m2 = Core_Regexps::match_with_results('{^[a-zA-Z\s]*(class|interface|function)\s+([a-zA-Z_0-9]+)}', $line);
				if ($m2) {
					$code_name = $m2[2];
					if ($m2[1] != 'function') {
						$code_name = Core_Strings::replace($code_name, '_', '.');
					}
					if ($code_name != $comment_name) {
						$result->add_error($this, $module, "names no equal {$code_name}, {$comment_name} in line {$line_number}");
					}
					$comment_name = false;
				}
			}
		}
		$module->file->close();
	}

}

/**
 * @package Dev\Source\Check
 */
class Dev_Source_Check_ValidXMLCommentChecker implements Dev_Source_Check_Checker
{
	protected $result;
	protected $module;

	public function error_handler($errno, $errstr, $errfile, $errline)
	{
		$this->result->add_error($this, $this->module, Core_Regexps::replace('/DOMDocument::\s*[a-zA-Z()]+\s*:/', '', $errstr));
	}

	/**
	 * @param Dev_Source_Module       $module
	 * @param Dev_Source_Check_Result $result
	 */
	public function run(Dev_Source_Module $module, Dev_Source_Check_Result $result)
	{
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

}

/**
 * @package Dev\Source\Check
 */
class Dev_Source_Check_Application extends CLI_Application_Base
{

	/**
	 * @param Dev_Source_Check_Result $result
	 */
	public function output($result)
	{
		foreach ($result as $error_struct)
			printf("%s:%s: %s\n", Core_Types::class_name_for($error_struct->checker, true),
				$error_struct->module->name, $error_struct->error
			);
	}

	/**
	 * @param array $argv
	 *
	 * @return int
	 */
	public function run(array $argv)
	{
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
				Dev_Source::Library($argv), $checker, $result
		);
		$this->output($result);
		return 0;
	}

	/**
	 */
	protected function setup()
	{
		$this->options->
			brief('Dev.Source.Check ' . Dev_Source_Check::VERSION . ' TAO code checker')->
			string_option('library', '-l', '--library', 'Path to library');
	}

}

