<?php
/**
 * CLI.GetOpt
 *
 * @package CLI\GetOpt
 * @version 0.3.0
 */

/**
 * @package CLI\GetOpt
 */
class CLI_GetOpt implements Core_ModuleInterface
{

	const VERSION = '0.3.0';
	const USAGE_FORMAT = "%6s%s %-20s  %s\n";

	const STRING = 0;
	const BOOL = 1;
	const INT = 2;
	const FLOAT = 3;

	/**
	 * @return CLI_GetOpt_Parser
	 */
	static public function Parser()
	{
		return new CLI_GetOpt_Parser();
	}

}

/**
 * @package CLI\GetOpt
 */
class CLI_GetOpt_Exception extends Core_Exception
{
}

/**
 * @package CLI\GetOpt
 */
class CLI_GetOpt_UnknownOptionException extends CLI_GetOpt_Exception
{
	protected $name;

	/**
	 * @param string $name
	 */
	public function __construct($name)
	{
		$this->name = $name;
		parent::__construct("Unknown option: $name");
	}

}

/**
 * @package CLI\GetOpt
 */
class CLI_GetOpt_Parser implements IteratorAggregate
{

	public $script;
	public $brief = '';

	protected $options;

	/**
	 * @param string $name
	 * @param string $short
	 * @param string $long
	 * @param string $comment
	 *
	 * @return CLI_GetOpt_Parser
	 */
	public function string_option($name, $short, $long, $comment)
	{
		return $this->option(CLI_GetOpt::STRING, $name, $short, $long, $comment);
	}

	/**
	 * @param string $name
	 * @param string $short
	 * @param string $long
	 * @param string $comment
	 *
	 * @return CLI_GetOpt_Parser
	 */
	public function int_option($name, $short, $long, $comment)
	{
		return $this->option(CLI_GetOpt::INT, $name, $short, $long, $comment);
	}

	/**
	 * @param string $name
	 * @param string $short
	 * @param string $long
	 * @param string $comment
	 *
	 * @return CLI_GetOpt_Parser
	 */
	public function float_option($name, $short, $long, $comment)
	{
		return $this->option(CLI_GetOpt::FLOAT, $name, $short, $long, $comment);
	}

	/**
	 * @param string  $name
	 * @param string  $short
	 * @param string  $long
	 * @param string  $comment
	 * @param boolean $value
	 *
	 * @return CLI_GetOpt_Parser
	 */
	public function boolean_option($name, $short, $long, $comment, $value = true)
	{
		return $this->option(CLI_GetOpt::BOOL, $name, $short, $long, $comment, (boolean)$value);
	}

	/**
	 * @param string $text
	 *
	 * @return CLI_GetOpt_Parser
	 */
	public function brief($text)
	{
		$this->brief = $text;
		return $this;
	}

	/**
	 * @return object
	 */
	public function parse(array &$argv, $config = null)
	{
		if ($config === null) {
			$config = Core::object();
		}

		$this->script = array_shift($argv);

		while (count($argv) > 0) {
			$arg = $argv[0];
			if ($parsed = $this->parse_option($arg)) {
				if ($option = $this->lookup_option($parsed[0])) {
					$this->set_option($config, $option, $parsed[1]);
				} else {
					throw new CLI_GetOpt_UnknownOptionException($parsed[0]);
				}
				array_shift($argv);
			} else {
				break;
			}
		}

		return $config;
	}

	/**
	 * @return string
	 */
	public function usage_text()
	{
		$text = "{$this->brief}\n";
		foreach ($this as $o)
			$text .= sprintf(CLI_GetOpt::USAGE_FORMAT,
				$o->short, $o->short ? ',' : '', $o->long, $o->comment
			);
		return $text;
	}

	/**
	 * @param string $name
	 * @param string $short
	 * @param string $long
	 * @param string $comment
	 * @param        $value
	 *
	 * @return CLI_GetOpt_Parser
	 */
	private function option($type, $name, $short, $long, $comment, $value = null)
	{
		$o = Core::object();
		$o->name = $name;
		$o->short = $short;
		$o->long = $long;
		$o->type = $type;
		$o->comment = $comment;
		$o->value = $value;
		$this->options[] = $o;
		return $this;
	}

	/**
	 * @param string $arg
	 *
	 * @return array|false
	 */
	protected function parse_option($arg)
	{
		switch (true) {
			case $m = Core_Regexps::match_with_results('{^(--[a-zA-Z][a-zA-Z0-9-]*)(?:=(.*))?$}', $arg):
				return isset($m[2]) ? array($m[1], $m[2]) : array($m[1], null);
			case $m = Core_Regexps::match_with_results('{^(-[a-zA-Z0-9])(.*)}', $arg):
				return isset($m[2]) ? array($m[1], $m[2]) : array($m[1], null);
			default:
				return false;
		}
	}

	/**
	 * @param string $name
	 *
	 * @return object
	 */
	protected function lookup_option($name)
	{
		foreach ($this->options as $o)
			if ($o->short == $name || $o->long == $name) {
				return $o;
			}
	}

	/**
	 * @return CLI_GetOpt_Parser
	 */
	protected function set_option($config, $option, $value)
	{
		$path = explode('.', $option->name);
		$attr = array_pop($path);
		if ($value == '' || is_null($value)) {
			$value = $option->value;
		}
		$c = $config;
		foreach ($path as $p)
			$c = isset($c->$p) ? $c->$p : $c->$p = Core::object();
		switch ($option->type) {
			case CLI_GetOpt::STRING:
				$c->$attr = (string)$value;
				break;
			case CLI_GetOpt::BOOL:
				$c->$attr = (boolean)$value;
				break;
			case CLI_GetOpt::INT:
				$c->$attr = (int)$value;
				break;
			case CLI_GetOpt::FLOAT:
				$c->$attr = (float)$value;
				break;
		}
		return $this;
	}

	/**
	 * @return ArrayIterator
	 */
	public function getIterator()
	{
		return new ArrayIterator($this->options);
	}

}

