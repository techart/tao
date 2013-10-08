<?php
/**
 * Набор классов для валидации объектов
 * 
 * @author Svistunov Sergey <svistunov@techart.ru>
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
		'convert'         => true,
		'server_charset'  => 'CP1251',
		'client_charset'  => 'UTF-8'
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
		if (! extension_loaded('sqlsrv')) {
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
	 * @param string $name Имя опции
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
				$error .= $e[2]."\n";
			}
		}

		parent::__construct("MSSQL Error: ".((string) $error));
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
class DB_Adapter_MSSQL_Connection implements DB_Adapter_ConnectionInterface {

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
					'UID'      => $dsn->user,
					'PWD'      => $dsn->password,
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
	 * @param mixed $value
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

///   <method name="commit">
///     <brief>Фиксирует транзакцию</brief>
///     <body>
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
///     </body>
///   </method>

///   <method name="rollback">
///     <brief>Откатывает транзакцию</brief>
///     <body>
	public function rollback()
	{
		if (sqlsrv_rollback($this->conn) === false)
		throw new DB_Adapter_MSSQL_Exception();
	}
///     </body>
///   </method>

///   <method name="last_insert_id" returns="int">
///     <brief>Возвращает последний вставленный идентификатор</brief>
///     <body>
  public function last_insert_id() {
    if (($stmt = sqlsrv_query($this->conn, 'SELECT SCOPE_IDENTITY() as last_id')) !== false) {
      $r = sqlsrv_fetch_array($stmt);
      sqlsrv_free_stmt($stmt);
      return $r['last_id'];
    }
  return null;
  }
///     </body>
///   </method>

///   <method name="quote" returns="string">
///     <brief>Квотит параметр</brief>
///     <args>
///       <arg name="value" brief="параметр" />
///     </args>
///     <body>
  public function quote($value) { return $value; }
///     </body>
///   </method>

///   <method name="prepare" returns="DB.Adapter.MSSQL.Cursor">
///     <brief>Подготавливает SQL-запрос к выполнению</brief>
///     <args>
///       <arg name="sql" type="string" brief="sql-запрос" />
///     </args>
///     <body>
  public function prepare($sql) {
    return new DB_Adapter_MSSQL_Cursor($this->connection, $sql);
  }
///     </body>
///   </method>

///   <method name="cast_parameter" returns="mixed">
///     <brief>Преобразует значение в пригодный вид для вставки в sql запрос</brief>
///     <args>
///       <arg name="value" brief="значение" />
///     </args>
///     <body>
  public function cast_parameter($value) {
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
///     </body>
///   </method>

///   <method name="is_castable_parameter" returns="boolean">
///     <brief>Проверяет требуется ли преобразовывать значение</brief>
///     <args>
///       <arg name="value" brief="значение" />
///     </args>
///     <body>
  public function is_castable_parameter($value) {
   return $value instanceof Time_DateTime ||
          (DB_Adapter_MSSQL::option('convert') && is_string($value));
  }
///     </body>
///   </method>

///   <method name="explain" >
///     <args>
///       <arg name="sql" type="string" />
///       <arg name="binds" />
///     </args>
///     <body>
  public function explain($sql, $binds) { throw new Core_NotImplementedException(); }
///     </body>
///   </method>

///   <method name="after_connect">
///     <brief>Вызывается в DB.Connection после соединения</brief>
///     <body>
  public function after_connect() {}
///     </body>
///   </method>

  public function get_schema() {
    return false;
  }

  public function escape_identifier($str) {
    return "$str";
  }

///   </protocol>
}
/// </class>


/// <class name="DB.Adapter.MSSQL.Cursor">
class DB_Adapter_MSSQL_Cursor implements DB_Adapter_CursorInterface {
  private $conn;
  private $sql;
  private $stmt;

///   <protocol name="creating">

///   <method name="__construct">
///     <brief>Конструктор</brief>
///     <args>
///       <arg name="connection" type="DB.Adapter.MSSQL.Connection" />
///       <arg name="sql" type="string" />
///     </args>
///     <body>
  public function __construct($connection, $sql) {
    $this->connection = $connection;
    $this->sql = $sql;
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="processing">

///   <method name="execute">
///     <brief>Выполняет запрос</brief>
///     <args>
///       <arg name="binds" type="array" brief="массив параметров" />
///     </args>
///     <body>
  public function execute(array $binds) {
    if (($this->stmt = sqlsrv_query($this->connection, $this->sql, $binds)) === false)
      throw new DB_Adapter_MSSQL_Exception();
    return $this;
  }
///     </body>
///   </method>

///   <method name="fetch" returns="mixed">
///     <brief>Возвращает очередную строку результата</brief>
///     <body>
  public function fetch() {
    return sqlsrv_fetch_array($this->stmt, SQLSRV_FETCH_ASSOC);
  }
///     </body>
///   </method>

///   <method name="fetch_all" returns="mixed">
///     <brief>Возвращает все строки результата</brief>
///     <body>
  public function fetch_all() {
    $rows = array();
    while ($row = $this->fetch()) $rows[] = $row;
    return $rows;
  }
///     </body>
///   </method>

///   <method name="close">
///     <brief>Закрывает курсор</brief>
///     <body>
  public function close() { sqlsrv_free_stmt($this->stmt); }
///     </body>
///   </method>

///   <method name="get_num_of_rows" returns="int">
///     <brief>Возвращает количество строк в результате</brief>
///     <body>
  public function get_num_of_rows() {return sqlsrv_num_rows($this->stmt);}
///     </body>
///   </method>

///   <method name="get_num_of_columns" returns="int">
///     <brief>Возвращает количетсво колонок</brief>
///     <body>
  public function get_num_of_columns() { return sqlsrv_num_fields($this->stmt); }
///     </body>
///   </method>

///   <method name="get_row_metadata" returns="DB.ColumnMeta">
///     <brief>Возвращает мета данные строки результата</brief>
///     <body>
  public function get_row_metadata() {
    $metadata = new ArrayObject();
    foreach (sqlsrv_field_metadata($this->stmt) as $column_meta)
      $metadata[$column_meta['Name']] = new DB_ColumnMeta(
        $column_meta['Name'], $column_meta['Type'], $column_meta['Size'], $column_meta['Precision']);
    return $metadata;
  }
///     </body>
///   </method>

///   <method name="cast_column" returns="mixed">
///     <brief>Преобразует значение полученное из БД в нужный формат, для работы с ним в php</brief>
///     <args>
///       <arg name="metadata" type="DB.ColumnMeta" brief="мета-данный колонки" />
///       <arg name="value" brief="значение" />
///     </args>
///     <body>
  public function cast_column(DB_ColumnMeta $metadata, $value) {
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
        return (int) $value;
      case 6:
      case 7:
        return (float) $value;
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
///     </body>
///   </method>

///   </protocol>
}
/// </class>

/// </module>
