<?php
/**
 * CLI
 *
 * Поддержка CLI-приложений
 *
 * <p>Иерархия модулей CLI реализует простейший фреймворк для написания приложений с
 * интерфейсом командной строки. Основная идея фреймворка заключается в том, чтобы не писать
 * отдельный сценарий командной строки для запуска PHP-кода, а вместо этого реализовать
 * единый универсальный сценарий запуска, принимающий в качестве параметра имя модуля,
 * реализующего специальный интерфейс.</p>
 * <p>В качестве сценария запуска используется стандартный сценарий tao-run, находящийся в
 * каталоге bin. В качестве первого параметра запуска ему передается имя модуля в
 * стандартной нотации (через точку), все остальные параметры предназначены для обработки
 * вызываемым модулем. При этом сценарий tao-run обеспечивает загрузку ядра, и необходимых
 * модулей CLI, что упрощает разработку утилит командной строки и обеспечивает
 * единообразность их вызова, в частности, с помощью стандартных переменных окружения.</p>
 * <p>Вызываемый модуль должен реализовывать интерфейс CLI.RunInterface, содержащий
 * единственный статический метод main().</p>
 *
 * @package CLI
 * @version 0.2.1
 */

/**
 * @package CLI
 */
class CLI implements Core_ModuleInterface
{

	const MODULE = 'CLI';
	const VERSION = '0.2.1';

	/**
	 * Запускает CLI-приложение
	 *
	 * @param array $argv
	 */
	static public function run_module(array $argv)
	{
		Core::load($argv[0]);
		if (Core_Types::reflection_for($module = Core_Types::real_class_name_for($argv[0]))->implementsInterface('CLI_RunInterface')) {
			return call_user_func(array($module, 'main'), $argv);
		} else {
			throw new CLI_NotRunnableModuleException($argv[0]);
		}
	}

}

/**
 * Базовый класс исключений модуля
 *
 * @package CLI
 */
class CLI_Exception extends Core_Exception
{
}

/**
 * Исключение: модуль не является запускаемым
 *
 * @package CLI
 */
class CLI_NotRunnableModuleException extends CLI_Exception
{

	protected $module;

	/**
	 * Конструктор
	 *
	 * @param string $module
	 */
	public function __construct($module)
	{
		$this->module = $module;
		parent::__construct("Not runnable module: $this->module");
	}

}

/**
 * Интерфейс запускаемых модулей
 *
 * <p>Для того, чтобы модуль стал запускаемым, он должен реализовать статический метод
 * main().</p>
 * <p>В качестве аргумента метод получает массив параметров командной строки, включающей
 * в себя в качестве первого элемента собственно имя модуля.</p>
 *
 * @package CLI
 */
interface CLI_RunInterface
{

	/**
	 * Точка входа
	 *
	 * @param array $argv
	 */
	static public function main(array $argv);
}



