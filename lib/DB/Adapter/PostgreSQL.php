<?php
/// <module name="DB.Adapter.PostgreSQL" version="0.2.0" maintainer="svistunov@techart.ru">
///   <brief>PostgreSQL адаптер</brief>

Core::load('DB.Adapter.PDO', 'Time');

/// <class name="DB.Adapter.PostgreSQL" stereotype="module">
class DB_Adapter_PostgreSQL implements Core_ModuleInterface {
///   <constants>
  const VERSION = '0.2.0';
///   </constants>

}
/// </class>

/// <class name="DB.Adapter.PostgreSQL.Connection" extends="DB.Adapter.PDO.Connection">
///   <brief>Класс подключения к БД</brief>
class DB_Adapter_PostgreSQL_Connection extends DB_Adapter_PDO_Connection {

///   <protocol name="processing">

///   <method name="prepare" returns="DB.Adapter.PostgreSQL.Cursor">
///     <brief>Подготавливает SQL-запрос к выполнению</brief>
///     <args>
///       <arg name="sql" type="string" brief="sql-запрос" />
///     </args>
///     <body>
  public function prepare($sql) {
    try {
      return new DB_Adapter_PostgreSQL_Cursor($this->pdo->prepare($sql));
    } catch (PDOException $e) {
      throw new DB_ConnectionException($e->getMessage());
    }
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
///     </body>
///   </method>

///   <method name="is_castable_parameter" returns="boolean">
///     <brief>Проверяет требуется ли преобразовывать значение</brief>
///     <args>
///       <arg name="value" brief="значение" />
///     </args>
///     <body>
  public function is_castable_parameter($value) {
   return ($value instanceof Time_DateTime);
  }
///     </body>
///   </method>

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

///   </protocol>
}
/// </class>


/// <class name="DB.Adapter.PostgreSQL.Cursor">
class DB_Adapter_PostgreSQL_Cursor extends DB_Adapter_PDO_Cursor {
///   <protocol name="processing">

///   <method name="cast_column" returns="mixed">
///     <brief>Преобразует значение полученное из БД в нужный формат, для работы с ним в php</brief>
///     <args>
///       <arg name="metadata" type="DB.ColumnMeta" brief="мета-данный колонки" />
///       <arg name="value" brief="значение" />
///     </args>
///     <body>
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
///     </body>
///   </method>

///   </protocol>
}
/// </class>

/// </module>
