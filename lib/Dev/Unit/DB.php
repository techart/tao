<?php
/**
 * Dev.Unit.DB
 *
 * @package Dev\Unit\DB
 * @version 0.1.2
 */
Core::load('DB', 'Dev.Unit', 'Proc', 'IO.FS');

/**
 * @package Dev\Unit\DB
 */
class Dev_Unit_DB implements Core_ModuleInterface
{
	const VERSION = '0.1.2';

	static protected $options = array(
		'dsn' => 'mysql://www:www@mysql.rd1.techart.intranet/test',
		'db_script' => 'mysql -u%s -p%s -h%s %s'
	);

	/**
	 * @param array $options
	 *
	 * @return mixed
	 */
	static public function options(array $options = array())
	{
		if (count($options)) {
			Core_Arrays::update(self::$options, $options);
		}
		return self::$options;
	}

	/**
	 * @param string $name
	 * @param        $value
	 *
	 * @return mixed
	 */
	static public function option($name, $value = null)
	{
		$prev = isset(self::$options[$name]) ? self::$options[$name] : null;
		if ($value !== null) {
			self::options(array($name => $value));
		}
		return $prev;
	}

}

/**
 * @package Dev\Unit\DB
 */
class Dev_Unit_DB_TestCase extends Dev_Unit_TestCase
{
	protected $connection;
	protected $data = array();

	/**
	 */
	protected function before_setup()
	{
		$this->create_connection();
		$this->create_sql();
		$this->load_data();
	}

	/**
	 */
	protected function after_teardown()
	{
		$this->drop_sql();
		$this->drop_connection();
	}

	/**
	 * @param string $dsn
	 */
	protected function create_connection($dsn = '')
	{
		$dsn = $dsn ? $dsn : Dev_Unit_DB::option('dsn');
		$this->connection = DB::Connection($dsn);
	}

	/**
	 */
	protected function drop_connection()
	{
		$this->connection->disconnect();
		$this->connection = null;
	}

	/**
	 * @param string $path
	 */
	protected function create_sql($path = null)
	{
		$path = $path ? $path : $this->default_path_for('create.sql');
		$this->sql($path);
	}

	/**
	 * @param string $path
	 */
	protected function load_data($path = '')
	{
		$path = $path ? $path : $this->default_path_for('data.php');
		$this->data = include $path;
		foreach ($this->data as $table => $rows)
			$this->insert_rows($table, $rows);
	}

	/**
	 * @param string $path
	 */
	protected function drop_sql($path = '')
	{
		$path = $path ? $path : $this->default_path_for('drop.sql');
		$this->sql($path);
	}

	/**
	 * @param string $name
	 *
	 * @return string
	 */
	private function default_path_for($name)
	{
		return implode(array(
				'.',
				'test',
				'data',
				str_replace('.', DIRECTORY_SEPARATOR,
					preg_replace('{^[^.]+\.}', '', Core_Types::module_name_for($this))
				),
				$name
			), DIRECTORY_SEPARATOR
		);
	}

	/**
	 * @param type  $tables
	 * @param array $data
	 */
	private function insert_rows($table, $rows)
	{
		foreach ($rows as $row) {
			$columns = array();
			$values = array();
			foreach ($row as $column => $value) {
				$columns[] = "`$column`";
				$values[] = "'$value'";
			}
			$sql = "INSERT INTO `$table` (" . implode(',', $columns) . ") VALUES (" .
				implode(',', $values) . ")";
			$this->connection->execute($sql);
		}
	}

	/**
	 * @param string $path
	 */
	protected function sql($path)
	{
		$p = Proc::Pipe($this->build_command(), 'wb');
		foreach (IO_FS::File($path) as $line)
			$p->write($line);
		$p->close();
		if ($p->exit_status != 0) {
			throw new Core_Exception('Erro while running db_script');
		}
	}

	/**
	 */
	protected function build_command()
	{
		return vsprintf(Dev_Unit_DB::option('db_script'), array(
				$this->connection->dsn->user,
				$this->connection->dsn->password,
				$this->connection->dsn->host,
				$this->connection->dsn->database,
				$this->connection->dsn->scheme,
				$this->connection->dsn->port,
			)
		);
	}

	/**
	 * @param string $property
	 *
	 * @return mixed
	 */
	public function __get($property)
	{
		if (key_exists($property, $this->data)) {
			return $this->data[$property];
		} else {
			return parent::__get($property);
		}
	}

	/**
	 * @param string $property
	 * @param        $value
	 *
	 * @return mixed
	 */
	public function __set($property, $value)
	{
		if (key_exists($property, $this->data)) {
			throw new Core_ReadOnlyPropertyException($property);
		} else {
			return parent::__set($property);
		}
	}

	/**
	 * @param string $property
	 *
	 * @return boolean
	 */
	public function __isset($property)
	{
		if (key_exists($property, $this->data)) {
			return true;
		} else {
			return parent::__set($property);
		}
	}

	/**
	 * @param string $property
	 */
	public function __unset($property)
	{
		if (key_exists($property, $this->data)) {
			throw new Core_ReadOnlyPropertyException($property);
		} else {
			return parent::__set($property);
		}
	}

}

