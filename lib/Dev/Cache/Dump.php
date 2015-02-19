<?php
/**
 * Dev.Cache.Dump
 *
 * CLI приложение для вывода дампа закешированного занчения по ключу
 *
 * @package Dev\Cache\Dump
 * @version 0.3.0
 */
Core::load('CLI.Application', 'Cache');

/**
 * @package Dev\Cache\Dump
 */
class Dev_Cache_Dump implements Core_ModuleInterface, CLI_RunInterface
{
	const VERSION = '0.3.0';

	/**
	 * Фабричный метод, возвращает объект приложения
	 *
	 * @param array $argv
	 */
	static public function main(array $argv)
	{
		Core::with(new Dev_Cache_Dump_Application())->main($argv);
	}

}

/**
 * Класс CLI приложения
 *
 * @package Dev\Cache\Dump
 */
class Dev_Cache_Dump_Application extends CLI_Application_Base
{

	/**
	 * Запускает приложение
	 *
	 * @param array $argv
	 *
	 * @return int
	 */
	public function run(array $argv)
	{
		$cache = Cache::connect($this->config->dsn);

		if ($this->config->modules != null) {
			foreach (Core_Strings::split_by(',', $this->config->modules) as $v)
				Core::load($v);
		}

		foreach ($argv as $v) {
			IO::stdout()->write_line($v)->
				write_line(var_export($cache[$v], true));
		}
		return 0;
	}

	/**
	 * Устанавливает параметры CLI приложения
	 *
	 */
	protected function setup()
	{
		$this->options->
			brief('Dev.Cache.Dump ' . Dev_Cache_Dump::VERSION . ': TAO Cache dump utility')->
			string_option('dsn', '-f', '--dsn', 'Cache backend DSN')->
			string_option('modules', '-m', '--preload', 'Preload');

		$this->config->dsn = 'memcache://localhost/11211';
	}

}

