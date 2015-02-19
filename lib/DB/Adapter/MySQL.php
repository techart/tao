<?php
/**
 * MySQL адаптер
 *
 * @author  Timokhin <timokhin@techart.ru>
 *
 * @package DB\Adapter\MySQL
 */

Core::load('DB.Adapter.PDO', 'Time', 'DB.Adapter.MySQL.Schema');

/**
 * Класс модуля
 *
 * @version 0.2.0
 *
 * @package DB\Adapter\MySQL
 */
class DB_Adapter_MySQL implements Core_ModuleInterface
{
	/**
	 * Версия модуля
	 */
	const VERSION = '0.2.0';
}

/**
 * Класс подключения к БД
 *
 * @package DB\Adapter\MySQL
 */
class DB_Adapter_MySQL_Connection extends DB_Adapter_PDO_Connection
{
	/**
	 * @var DB_Adapter_MySQL_Schema Схема
	 */
	protected $schema;

	/**
	 * Подготавливает SQL-запрос к выполнению
	 *
	 * @params string $sql SQL-запрос
	 *
	 * @throws DB_ConnectionException Если сервер базы данных не может успешно подготовить утверждение
	 *
	 * @return DB_Adapter_MySQL_Cursor
	 */
	public function prepare($sql)
	{
		try {
			return new DB_Adapter_MySQL_Cursor($this->pdo->prepare($sql));
		} catch (PDOException $e) {
			throw new DB_ConnectionException($e->getMessage());
		}
	}

	/**
	 * Преобразует значение в пригодный вид для вставки в sql запрос
	 *
	 * @params mixed $value значение
	 *
	 * @return mixed
	 */
	public function cast_parameter($value)
	{
		if ($value instanceof Time_DateTime) {
			return $value->format(Time::FMT_DEFAULT);
		} else {
			return $value;
		}
	}

	/**
	 * Проверяет требуется ли преобразовывать значение
	 *
	 * @params mixed $value значение
	 *
	 * @return boolean
	 */
	public function is_castable_parameter($value)
	{
		return ($value instanceof Time_DateTime);
	}

	/**
	 * Вызывается в DB.Connection после соединения
	 */
	public function after_connect()
	{
		$this->pdo->exec('SET NAMES ' . DB::option('charset'));
		$time_zone = DB::option('time_zone');
		if ($time_zone !== false) {
			$this->pdo->exec("SET time_zone = {$time_zone}");
		}
	}

	/**
	 * Выполняет EXPLAIN для анализа запроса.
	 *
	 * @params string $sql sql-запрос
	 * @params array $binds массив параметров
	 *
	 * @return array массив строк
	 */
	public function explain($sql, $binds)
	{
		$c = $this->prepare("EXPLAIN ($sql)");
		$c->execute($binds);

		$result = array();
		foreach ($c->fetch_all() as $v) {
			$result[] = Core::object($v);
		}
		return $result;
	}

	/**
	 * Получает схему
	 *
	 * @return DB_Adapter_MySQL_Schema
	 */
	public function get_schema()
	{
		return isset($this->schema) ?
			$this->schema :
			$this->schema = new DB_Adapter_MySQL_Schema();
	}

	/**
	 * Заключает параметр в обратные кавычки
	 *
	 * @params string $str
	 *
	 * @return string
	 */
	public function escape_identifier($str)
	{
		return "`$str`";
	}
}

/**
 * Класс курсора БД
 *
 * @package DB\Adapter\MySQL
 */
class DB_Adapter_MySQL_Cursor extends DB_Adapter_PDO_Cursor
{
	/**
	 * Преобразует значение
	 *
	 * Преобразует значение полученное из БД в нужный формат, для работы с ним в php
	 *
	 * @params DB_ColumnMeta $metadata мета-данные колонки
	 * @params mixed $value значение
	 *
	 * @return mixed
	 */
	public function cast_column(DB_ColumnMeta $metadata, $value)
	{
		switch ($metadata->type) {
			case 'datetime':
			case 'timestamp':
			case 'date':
				return is_null($value) ? null : Time_DateTime::parse($value);
			case 'time':
				return is_null($value) ? null : Time_DateTime::parse($value, TIME::FMT_HMS);
			case 'boolean':
				return $value ? true : false;
			case 'longlong':
			case 'int24':
			case 'integer':
			case 'long':
			case 'tiny':
			case 'short':
				return is_null($value) ? null : (int)$value;
			case 'float':
			case 'double':
				return is_null($value) ? null : (float)$value;
			default:
				return $value;
		}
	}
}