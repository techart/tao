<?php
/**
 * Proc
 *
 * Модуль для с процессами и pipe
 *
 * @package Proc
 * @version 0.2.0
 */

Core::load('IO');

/**
 * @package Proc
 */
class Proc
	implements Core_ModuleInterface
{

	const VERSION = '0.2.0';

	/**
	 * Фабричный метод, возвращает объект класса Proc.Process
	 *
	 * @param string $command
	 *
	 * @return Proc_Process
	 */
	static public function Process($command)
	{
		return new Proc_Process($command);
	}

	/**
	 * Фабричный метод, возвращает объект класса Proc.Process
	 *
	 * @param string $command
	 * @param string $mode
	 *
	 * @return Proc_Pipe
	 */
	static public function Pipe($command, $mode = IO_Stream::DEFAULT_OPEN_MODE)
	{
		return new Proc_Pipe($command, $mode);
	}

	/**
	 * Выполняет команду
	 *
	 * @param string $command
	 *
	 * @return int
	 */
	static public function exec($command)
	{
		$rc = 0;
		$lines = null;
		exec($command, $lines, $rc);
		return $rc;
	}

	/**
	 * Проверяет существует ли процесс
	 *
	 * @param int $pid
	 *
	 * @return boolean
	 */
	static public function process_exists($pid)
	{
		return posix_kill($pid, 0);
	}

}

/**
 * Класс искключения
 *
 * @package Proc
 */
class Proc_Exception extends Core_Exception
{
}

/**
 * Класс для работы с pipe
 *
 * @package Proc
 */
class Proc_Pipe extends IO_Stream_ResourceStream
{

	protected $exit_status = 0;

	/**
	 * Конструктор
	 *
	 * @param string $command
	 * @param string $mode
	 */
	public function __construct($command, $mode = IO_Stream::DEFAULT_OPEN_MODE)
	{
		if (!$this->id = @popen($command, $mode)) {
			throw new Proc_Exception("Unable to open pipe: $command");
		}
	}

	/**
	 * Закрывате поток
	 *
	 */
	public function close()
	{
		$this->exit_status = @pclose($this->id);
		$this->id = null;
		return $this;
	}

	/**
	 * Деструктор
	 *
	 */
	public function __destruct()
	{
		if ($this->id) {
			$this->close();
		}
	}

	/**
	 * Доступ на чтение к свойствам объекта
	 *
	 * @param string $property
	 *
	 * @return mixed
	 */
	public function __get($property)
	{
		switch ($property) {
			case 'exit_status':
				return $this->$property;
			default:
				return parent::__get($property);
		}
	}

	/**
	 * Доступ на запись к свойствам объекта
	 *
	 * @param string $property
	 * @param        $value
	 *
	 * @return mixed
	 */
	public function __set($property, $value)
	{
		switch ($property) {
			case 'exit_status':
				throw new Core_ReadOnlyPropertyException($property);
			default:
				return parent::__set($property, $value);
		}
	}

	/**
	 * Проверяет установленно ил свойство
	 *
	 * @return boolean
	 */
	public function __isset($property)
	{
		switch ($property) {
			case 'exit_status':
				return true;
			default:
				return parent::__isset($property);
		}
	}

}

/**
 * Класс для работы с процессами
 *
 * @package Proc
 */
class Proc_Process implements Core_PropertyAccessInterface
{
	protected $id;

	private $run_options = array();

	protected $command;
	protected $working_dir;
	protected $environment;

	protected $pid;

	protected $input;
	protected $output;
	protected $error;

	/**
	 * Конструктор
	 *
	 * @param string $command
	 */
	public function __construct($command)
	{
		$this->command = $command;
	}

	/**
	 * Устанавливает рабочий каталог
	 *
	 * @param string $path
	 *
	 * @return Proc_Process
	 */
	public function working_dir($path)
	{
		$this->working_dir = $path;
		return $this;
	}

	/**
	 * Добавляет/устанавливает переменный окружения
	 *
	 * @param array $env
	 *
	 * @return Proc_Process
	 */
	public function environment(array $env)
	{
		if (!Core_Types::is_array($this->environment)) {
			$this->environment = array();
		}
		foreach ($env as $k => $v)
			$this->environment[$k] = (string)$v;
		return $this;
	}

	/**
	 * Устанавливает входной поток
	 *
	 * @param boolean|string $input
	 *
	 * @return Proc_Process
	 */
	public function input($input = true)
	{
		return $this->define_redirection($input, 0, 'r');
	}

	/**
	 * Устанавливает выходной поток
	 *
	 * @param boolean|string $output
	 * @param string         $mode
	 *
	 * @return Proc_Process
	 */
	public function output($output = true, $mode = 'w')
	{
		return $this->define_redirection($output, 1, $mode);
	}

	/**
	 * Устанавливает поток ошибок
	 *
	 * @param boolean|string $error
	 *
	 * @return Proc_Process
	 */
	public function error($error = true)
	{
		return $this->define_redirection($error, 2, 'w');
	}

	/**
	 * Закрывает входной поток
	 *
	 * @return Proc_Process
	 */
	public function finish_input()
	{
		if ($this->input) {
			$this->input->close();
		}
		return $this;
	}

	/**
	 * Запускает процесс
	 *
	 * @return Proc_Process
	 */
	public function run()
	{
		$pipes = array();

		if ($this->id = proc_open($this->command, $this->run_options, $pipes, $this->working_dir, $this->environment)) {

			if (isset($pipes[0])) {
				$this->input = IO_Stream::ResourceStream($pipes[0]);
			}
			if (isset($pipes[1])) {
				$this->output = IO_Stream::ResourceStream($pipes[1]);
			}
			if (isset($pipes[2])) {
				$this->error = IO_Stream::ResourceStream($pipes[2]);
			}

			$this->run_options = null;
		}

		return $this;
	}

	/**
	 * Закрывает процесс и все открытые потоки
	 *
	 * @return int
	 */
	public function close()
	{
		if (!$this->is_started()) {
			return null;
		}

		foreach (array('input', 'output', 'error') as $pipe)
			if (isset($this->$pipe)) {
				$this->$pipe->close();
			}

		proc_close($this->id);

		$this->id = null;
	}

	/**
	 * Проверяет запущен ли процесс
	 *
	 * @return boolean
	 */
	public function is_started()
	{
		return $this->id ? true : false;
	}

	/**
	 * Возвращает статус процесса
	 *
	 * @return Data_Struct
	 */
	public function get_status()
	{
		return ($data = proc_get_status($this->id)) ?
			(object)$data :
			null;
	}

	/**
	 * Доступ к свойствам объекта на чтение
	 *
	 * @param string $property
	 *
	 * @return mixed
	 */
	public function __get($property)
	{
		switch ($property) {
			case 'id':
			case 'input':
			case 'output':
			case 'error':
			case 'command':
			case 'environment':
			case 'working_dir':
				return $this->$property;
			default:
				throw new Core_MissingPropertyException($property);
		}
	}

	/**
	 * Доступ к свойствам объекта на запись
	 *
	 * @param string $property
	 * @param        $value
	 *
	 * @return mixed
	 */
	public function __set($property, $value)
	{
		switch ($property) {
			case 'id':
			case 'input':
			case 'output':
			case 'error':
			case 'command':
			case 'environment':
			case 'working_dir':
				throw new Core_ReadOnlyPropertyException($property);
			default:
				throw new Core_MissingPropertyException($property);
		}
	}

	/**
	 * Проверяет установленно ли свойство объекта
	 *
	 * @param string $property
	 *
	 * @return boolean
	 */
	public function __isset($property)
	{
		switch ($property) {
			case 'id':
			case 'input':
			case 'output':
			case 'error':
			case 'command':
			case 'environment':
			case 'working_dir':
				return true;
			default:
				return false;
		}
	}

	/**
	 * Очищает свойство объекта
	 *
	 * @param string $property
	 */
	public function __unset($property)
	{
		throw $this->__isset($property) ?
			new Core_UndestroyablePropertyException($property) :
			new Core_MissingPropertyException($property);
	}

	/**
	 * Направляет потоки
	 *
	 * @return Proc_Process
	 */
	private function define_redirection($source, $idx, $mode)
	{
		if ($source === true) {
			$this->run_options[$idx] = array('pipe', $mode);
		} else {
			$this->run_options[$idx] = array('file', (string)$source, $mode);
		}
		return $this;
	}

}

