<?php
/**
 * DB.Adapter.PostgreSQL
 * 
 * PostgreSQL адаптер
 * 
 * @package DB\Adapter\PostgreSQL
 * @version 0.2.0
 */

Core::load('DB.Adapter.PDO', 'Time');

/**
 * @package DB\Adapter\PostgreSQL
 */
class DB_Adapter_PostgreSQL implements Core_ModuleInterface {
  const VERSION = '0.2.0';

}

/**
 * Класс подключения к БД
 * 
 * @package DB\Adapter\PostgreSQL
 */
class DB_Adapter_PostgreSQL_Connection extends DB_Adapter_PDO_Connection {


/**
 * Подготавливает SQL-запрос к выполнению
 * 
 * @param string $sql
 * @return DB_Adapter_PostgreSQL_Cursor
 */
  public function prepare($sql) {
    try {
      return new DB_Adapter_PostgreSQL_Cursor($this->pdo->prepare($sql));
    } catch (PDOException $e) {
      throw new DB_ConnectionException($e->getMessage());
    }
  }

/**
 * Преобразует значение в пригодный вид для вставки в sql запрос
 * 
 * @param  $value
 * @return mixed
 */
  public function cast_parameter($value) {
    switch (true) {
      case ($value instanceof Time_DateTime) :
        return $value->format(Time::FMT_DEFAULT);
      case (Core_Types::is_array($value)) : {
        $str = '{';
        foreach ($value as $e) $str .= ($str == '{' ? '' : ',').'"'.
          ( $this->is_castable_parameter($e) ? $this->cast_parameter($e) : $e).'"';
        return $str.'}';
      }
      default:
        return $value;
    }
  }

/**
 * Проверяет требуется ли преобразовывать значение
 * 
 * @param  $value
 * @return boolean
 */
  public function is_castable_parameter($value) {
   return ($value instanceof Time_DateTime);
  }

  public function explain($sql, $binds) {
    return new Core_NotImplementedException();
    //return Core:: Object($this->prepare("EXPLAIN $sql")->bind($binds)->fetch());
  }
  
  public function get_schema() {
    return false;
  }

  public function escape_identifier($str) {
    return function_exists('pg_escape_identifier') ? pg_escape_identifier($str) : "\"$str\"";
  }

}


/**
 * @package DB\Adapter\PostgreSQL
 */
class DB_Adapter_PostgreSQL_Cursor extends DB_Adapter_PDO_Cursor {

/**
 * Преобразует значение полученное из БД в нужный формат, для работы с ним в php
 * 
 * @param DB_ColumnMeta $metadata
 * @param  $value
 * @return mixed
 */
  public function cast_column(DB_ColumnMeta $metadata, $value) {
    switch (true) {
      case $metadata->type == 'date':
        return is_null($value) ? null : Time::parse($value, TIME::FMT_YMD);
      case $metadata->type == 'timestamp':
        return is_null($value) ? null : Time::parse($value);
      case Core_Strings::starts_with($metadata->type, '_') : {
        $arr = array();
        foreach (explode(',',Core_Strings::substr($value, 1, strlen($value)-2)) as $element)
          $arr[] = $this->cast_column(new DB_ColumnMeta(
            $metadata->name,
            Core_Strings::substr($metadata->type, 1),
            $metadata->length, $metadata->precision), ltrim(rtrim($element, '"'),'"'));
        return $arr;
      }
      default:
        return $value;
    }
  }

}

