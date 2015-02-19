<?php
/**
 * Набор классов для валидации объектов
 *
 * @author  Svistunov Sergey <svistunov@techart.ru>
 *
 * @version 0.2.1
 * @deprecated
 *
 * @package DB\Adapter\MSSQL
 */

Core::load('Time');

/**
 * Класс модуля
 *
 * @package DB\Adapter\MSSQL
 */
class DB_Adapter_MSSQL implements Core_ConfigurableModuleInterface
{
	/**
	 * Версия модуля
	 */
	const VERSION = '0.2.1';

	/**
	 * @var array Набор опций
	 */
	static protected $options = array(
		'convert' => true,
		'server_charset' => 'CP1251',
		'client_charset' => 'UTF-8'
	);

	/**
	 * Инициализация
	 *
	 * @param array $options Массив опций по умолчанию array()
	 *
	 * @throws Core_Exception Если отсутствует Microsoft SQL Server Driver for PHP.
	 */
	static public function initialize(array $options = array())
	{
		if (!extension_loaded('sqlsrv')) {
			throw new Core_Exception('Sqlsrv is not supported');
		}
		self::options($options);
	}

	/**
	 * Изменение и получение опций.
	 *
	 * Изменяются уже существующие опции. Новые опции не добавляются.
	 *
	 * @param array $options Массив опций по умолчанию array()
	 *
	 * @return array self::$options
	 */
	static public function options(array $options = array())
	{
		if (count($options)) {
			Core_Arrays::update(self::$options, $options);
		}
		return self::$options;
	}

	/**
	 * Установка и получение значения опции.
	 *
	 * Возвращается предыдущее значение опции.
	 *
	 * @param string $name  Имя опции
	 * @param string $value Значение опции по умолчанию null
	 *
	 * @return null|string
	 */
	static public function option($name, $value = null)
	{
		$prev = isset(self::$options[$name]) ?
			self::$options[$name] :
			null;

		if ($value !== null) {
			self::options(array($name => $value));
		}

		return $prev;
	}
}

/**
 * Класс исключения
 *
 * @package DB\Adapter\MSSQL
 */
class DB_Adapter_MSSQL_Exception extends DB_Exception
{
	/**
	 * Конструктор
	 *
	 * Если параметр $error не передан, то сообщение будет состоять из
	 * всех ошибок и предупреждений, полученных в ходе последней операции.
	 *
	 * @param string $error Сообщение об ошибке по умолчанию null
	 */
	public function __construct($error = null)
	{
		if (is_null($error)) {
			foreach (sqlsrv_errors() as $e) {
				$error .= $e[2] . "\n";
			}
		}

		parent::__construct("MSSQL Error: " . ((string)$error));
	}
}

/**
 * Класс исключения
 *
 * @package DB\Adapter\MSSQL
 */
class DB_Adapter_MSSQL_ConnectionException extends DB_Adapter_MSSQL_Exception
{
}

/**
 * Класс подключения к БД
 *
 * @package DB\Adapter\MSSQL
 */
class DB_Adapter_MSSQL_Connection implements DB_Adapter_ConnectionInterface
{

	/**
	 * @var resource Ресурс соединения с БД
	 */
	protected $connection;

	/**
	 * @var array Атрибуты
	 */
	protected $attrs = array();

	/**
	 * Конструктор
	 *
	 * @param DB_DSN $dsn Объект строки параметров подключения к БД
	 *
	 * @throws DB_Adapter_MSSQL_ConnectionException Если подключение не удалось
	 */
	public function __construct(DB_DSN $dsn)
	{
		try {
			$this->connection = sqlsrv_connect(
				$dsn->host,
				array(
					'UID' => $dsn->user,
					'PWD' => $dsn->password,
					'Database' => $dsn->database
				) + (count($dsn->parms) > 0 ? $dsn->parms : array())
			);
		} catch (Exception $e) {
			throw new DB_Adapter_MSSQL_ConnectionException($e->getMessage());
		}

		if (!$this->connection) {
			throw new DB_Adapter_MSSQL_ConnectionException();
		}
	}

	/**
	 * Устанавливает атрибут
	 *
	 * @param integer $id идентификатор
	 * @param mixed   $value
	 *
	 * @throws Core_NotImplementedException Всегда
	 */
	public function set_attribute($id, $value)
	{
		throw new Core_NotImplementedException();
	}

	/**
	 * Возвращает атрибут
	 *
	 * @param integer $id идентификатор
	 *
	 * @throws Core_NotImplementedException Всегда
	 *
	 * @return mixed
	 */
	public function get_attribute($id)
	{
		throw new Core_NotImplementedException();
	}

	/**
	 * Открывает транзакцию
	 *
	 * @throws DB_Adapter_MSSQL_Exception Если транзакция не удалась.
	 */
	public function transaction()
	{
		if (sqlsrv_begin_transaction($this->connection) === false) {
			throw new DB_Adapter_MSSQL_Exception();
		}
	}

	/**
	 * Фиксирует транзакцию
	 *
	 */
	/**
	 * Фиксирует транзакцию
	 *
	 * @throws DB_Adapter_MSSQL_Exception Если транзакция не удалась.
	 */
	public function commit()
	{
		if (sqlsrv_commit($this->connection) === false) {
			throw new DB_Adapter_MSSQL_Exception();
		}
	}

	/**
	 * Откатывает транзакцию
	 *
	 */
	public function rollback()
	{
		if (sqlsrv_rollback($this->conn) === false) {
			throw new DB_Adapter_MSSQL_Exception();
		}
	}

	/**
	 * Возвращает последний вставленный идентификатор
	 *
	 * @return int
	 */
	public function last_insert_id()
	{
		if (($stmt = sqlsrv_query($this->conn, 'SELECT SCOPE_IDENTITY() as last_id')) !== false) {
			$r = sqlsrv_fetch_array($stmt);
			sqlsrv_free_stmt($stmt);
			return $r['last_id'];
		}
		return null;
	}

	/**
	 * Квотит параметр
	 *
	 * @param  $value
	 *
	 * @return string
	 */
	public function quote($value)
	{
		return $value;
	}

	/**
	 * Подготавливает SQL-запрос к выполнению
	 *
	 * @param string $sql
	 *
	 * @return DB_Adapter_MSSQL_Cursor
	 */
	public function prepare($sql)
	{
		return new DB_Adapter_MSSQL_Cursor($this->connection, $sql);
	}

	/**
	 * Преобразует значение в пригодный вид для вставки в sql запрос
	 *
	 * @param  $value
	 *
	 * @return mixed
	 */
	public function cast_parameter($value)
	{
		switch (true) {
			case $value instanceof Time_DateTime:
				return $value->format(Time::FMT_DEFAULT);
			case is_string($value):
				return DB_Adapter_MSSQL::option('convert') ?
					iconv(DB_Adapter_MSSQL::option('client_charset'), DB_Adapter_MSSQL::option('server_charset'), $value) :
					$value;
			default:
				return $value;
		}
	}

	/**
	 * Проверяет требуется ли преобразовывать значение
	 *
	 * @param  $value
	 *
	 * @return boolean
	 */
	public function is_castable_parameter($value)
	{
		return $value instanceof Time_DateTime ||
		(DB_Adapter_MSSQL::option('convert') && is_string($value));
	}

	/**
	 * @param string $sql
	 * @param        $binds
	 */
	public function explain($sql, $binds)
	{
		throw new Core_NotImplementedException();
	}

	/**
	 * Вызывается в DB.Connection после соединения
	 *
	 */
	public function after_connect()
	{
	}

	public function get_schema()
	{
		return false;
	}

	public function escape_identifier($str)
	{
		return "$str";
	}

}

class DB_Adapter_MSSQL_Cursor implements DB_Adapter_CursorInterface
{
	private $conn;
	private $sql;
	private $stmt;

	/**
	 * Конструктор
	 *
	 * @param DB_Adapter_MSSQL_Connection $connection
	 * @param string                      $sql
	 */
	public function __construct($connection, $sql)
	{
		$this->connection = $connection;
		$this->sql = $sql;
	}

	/**
	 * Выполняет запрос
	 *
	 * @param array $binds
	 */
	public function execute(array $binds)
	{
		if (($this->stmt = sqlsrv_query($this->connection, $this->sql, $binds)) === false) {
			throw new DB_Adapter_MSSQL_Exception();
		}
		return $this;
	}

	/**
	 * Возвращает очередную строку результата
	 *
	 * @return mixed
	 */
	public function fetch()
	{
		return sqlsrv_fetch_array($this->stmt, SQLSRV_FETCH_ASSOC);
	}

	/**
	 * Возвращает все строки результата
	 *
	 * @return mixed
	 */
	public function fetch_all()
	{
		$rows = array();
		while ($row = $this->fetch())
			$rows[] = $row;
		return $rows;
	}

	/**
	 * Закрывает курсор
	 *
	 */
	public function close()
	{
		sqlsrv_free_stmt($this->stmt);
	}

	/**
	 * Возвращает количество строк в результате
	 *
	 * @return int
	 */
	public function get_num_of_rows()
	{
		return sqlsrv_num_rows($this->stmt);
	}

	/**
	 * Возвращает количетсво колонок
	 *
	 * @return int
	 */
	public function get_num_of_columns()
	{
		return sqlsrv_num_fields($this->stmt);
	}

	/**
	 * Возвращает мета данные строки результата
	 *
	 * @return DB_ColumnMeta
	 */
	public function get_row_metadata()
	{
		$metadata = new ArrayObject();
		foreach (sqlsrv_field_metadata($this->stmt) as $column_meta)
			$metadata[$column_meta['Name']] = new DB_ColumnMeta(
				$column_meta['Name'], $column_meta['Type'], $column_meta['Size'], $column_meta['Precision']);
		return $metadata;
	}

	/**
	 * Преобразует значение полученное из БД в нужный формат, для работы с ним в php
	 *
	 * @param DB_ColumnMeta $metadata
	 * @param               $value
	 *
	 * @return mixed
	 */
	public function cast_column(DB_ColumnMeta $metadata, $value)
	{
		switch ($metadata->type) {
			case 91:
			case 93:
			case -2:
				return is_null($value) ? null : Time::DateTime($value);
			case -5:
			case 4:
			case 5:
			case -6:
			case 2:
				return (int)$value;
			case 6:
			case 7:
				return (float)$value;
			case 1:
			case -8:
			case -10:
			case -9:
			case -1:
			case 12:
				return (DB_Adapter_MSSQL::option('convert')) ?
					iconv(DB_Adapter_MSSQL::option('server_charset'), DB_Adapter_MSSQL::option('client_charset'), $value) :
					$value;
			default:
				return $value;
		}
	}

}

