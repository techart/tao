<?php
/**
 * DB.Adapter.PostgreSQL
 *
 * PostgreSQL адаптер
 *
 * @package DB\Adapter\PostgreSQL
 * @version 0.2.0
 */

Core::load('DB.Adapter.PDO', 'Time', 'DB.Adapter.PostgreSQL.Schema');

/**
 * @package DB\Adapter\PostgreSQL
 */
class DB_Adapter_PostgreSQL implements Core_ModuleInterface
{
	const VERSION = '0.2.0';

}

/**
 * Класс подключения к БД
 *
 * @package DB\Adapter\PostgreSQL
 */
class DB_Adapter_PostgreSQL_Connection extends DB_Adapter_PDO_Connection
{
	/**
	 * @var DB_Adapter_MySQL_Schema Схема
	 */
	protected $schema;

	/**
	 * Подготавливает SQL-запрос к выполнению
	 *
	 * @param string $sql
	 *
	 * @return DB_Adapter_PostgreSQL_Cursor
	 */
	public function prepare($sql)
	{
		$sql = $this->convert_quote($sql);
		$sql = $this->convert_date_format($sql);
		$sql = $this->convert_from_unixtime($sql);
		$sql = $this->convert_if($sql);
		try {
			return new DB_Adapter_PostgreSQL_Cursor($this->pdo->prepare($sql));
		} catch (PDOException $e) {
			throw new DB_ConnectionException($e->getMessage());
		}
	}

	public function convert_from_unixtime($sql)
	{
		$sql = preg_replace_callback('!FROM_UNIXTIME\(([^,]+),([^)]+)\)!i', function($matches) {
			$format = $matches[2];
			$format = str_replace('%m', 'MM', $format);
			$format = str_replace('%d', 'DD', $format);
			$format = str_replace('%y', 'YYYY', $format);
			$format = str_replace('%u', 'W', $format);
			return "to_char(date(to_timestamp({$matches[1]})), {$format})";
		}, $sql);
		return $sql;
	}

	public function convert_quote($sql)
	{
		return str_replace('`', '"', $sql);
	}

	public function convert_if($sql)
	{
		$sql = preg_replace_callback('!if\(([^,]+),([^,]+),([^)]+)\)!i', function($matches) {
			return "(CASE WHEN CAST({$matches[1]} as boolean) THEN {$matches[2]} ELSE {$matches[3]} END)";
		}, $sql);
		return $sql;
	}

	public function convert_date_format($sql)
	{
		$sql = preg_replace_callback('!DATE_FORMAT\(([^,]+),([^)]+)\)!i', function($matches) {
			$format = $matches[2];
			$format = str_replace('%m', 'MM', $format);
			$format = str_replace('%d', 'DD', $format);
			$format = str_replace('%y', 'YYYY', $format);
			return "to_char({$matches[1]}, {$format})";
		}, $sql);
		return $sql;
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
			case ($value instanceof Time_DateTime) :
			{
				return $value->format(Time::FMT_DEFAULT);
			}
			case (Core_Types::is_array($value)) :
			{
				$str = '{';
				foreach ($value as $e)
					$str .= ($str == '{' ? '' : ',') . '"' .
						($this->is_castable_parameter($e) ? $this->cast_parameter($e) : $e) . '"';
				return $str . '}';
			}
			case (!is_object($value) && (string)(int)$value === (string)$value):
			{
				return (int) $value;
			}
			default:
			{
				return $value;
			}
		}
	}

	public function last_insert_id($table = null, $key = null)
	{
		try {
			if (empty($key)) {
				$key = 'id';
			} else if (is_array($key)) {
				$key = reset($key);
			}
			$seq = "{$table}_{$key}_seq";
			return $this->pdo->lastInsertId($seq);
		} catch (PDOException $e) {
			throw new DB_ConnectionException($e->getMessage());
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
		return ($value instanceof Time_DateTime);
	}

	public function explain($sql, $binds)
	{
		return new Core_NotImplementedException();
		//return Core:: Object($this->prepare("EXPLAIN $sql")->bind($binds)->fetch());
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
			$this->schema = new DB_Adapter_PostgreSQL_Schema();
	}

	public function escape_identifier($str)
	{
		return "\"$str\"";// function_exists('pg_escape_identifier') ? pg_escape_identifier($str) : "\"$str\"";
	}

}

/**
 * @package DB\Adapter\PostgreSQL
 */
class DB_Adapter_PostgreSQL_Cursor extends DB_Adapter_PDO_Cursor
{

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
		switch (true) {
			case $metadata->type == 'date':
			{
				return is_null($value) ? null : Time::parse($value);
			}
			case $metadata->type == 'timestamp':
			{
				return is_null($value) ? null : Time::parse($value);
			}
			case Core_Strings::starts_with($metadata->type, '_') :
			{
				$arr = array();
				foreach (explode(',', Core_Strings::substr($value, 1, strlen($value) - 2)) as $element)
					$arr[] = $this->cast_column(new DB_ColumnMeta(
							$metadata->name,
							Core_Strings::substr($metadata->type, 1),
							$metadata->length, $metadata->precision), ltrim(rtrim($element, '"'), '"')
					);
				return $arr;
			}
			case Core_Strings::starts_with($metadata->type, 'int'):
			{
				return (int) $value;
			}
			case in_array($metadata->type, array('varchar', 'text')):
			{
				return (string) $value;
			}
			case in_array($metadata->type, array('numeric', 'double', 'float')):
			{
				return (float) $value;
			}
			default:
			{
				return $value;
			}
		}
	}

}