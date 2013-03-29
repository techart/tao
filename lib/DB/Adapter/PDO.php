<?php
/// <module name="DB.Adapter.PDO" version="0.2.0" maintainer="timokhin@techart.ru">
///   <brief>Адаптер PDO</brief>
Core::load('DB', 'WS');

/// <class name="DB.Adapter.PDO" stereotype="abstract">
///   <implements interface="Core.ModuleInterface" />
class DB_Adapter_PDO implements Core_ModuleInterface {
///   <constants>
  const VERSION = '0.2.0';
///   </constants>
}
/// </class>

/// <class name="DB.Adapter.PDO.Connection" stereotype="abstract">
///   <brief>Класс подключения к БД</brief>
///   <implements interface="DB.Adapter.ConnectionInterface" />
abstract class DB_Adapter_PDO_Connection
  implements DB_Adapter_ConnectionInterface {

  protected $pdo;

///   <protocol name="creating">

///   <method name="__construct">
///     <brief>Конструктор</brief>
///     <args>
///       <arg name="dsn" type="DB.DSN" brief="параметра доступа к БД" />
///     </args>
///     <body>
  public function __construct(DB_DSN $dsn) {
    try {
      $connections = WS::env()->pdo_connections ? WS::env()->pdo_connections : array();
      if (isset($connections[$dsn->pdo_string]))
        $this->pdo = $connections[$dsn->pdo_string];
      else {
        $this->pdo = new PDO($dsn->pdo_string, $dsn->user, $dsn->password);
      }
    } catch (PDOException $e) {
      throw new DB_ConnectionException($e->getMessage());
    }
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="processing">

///   <method name="set_attribute" returns="boolean">
///     <brief>Устанавливает атрибут</brief>
///     <args>
///       <arg name="id" brief="идентификатор"  />
///       <arg name="value" brief="значение" />
///     </args>
///     <body>
  public function set_attribute($id, $value) { return $this->pdo->setAttribute($id, $value); }
///     </body>
///   </method>

///   <method name="get_attribute" returns="mixed">
///     <brief>Возвращает атрибут</brief>
///     <args>
///       <arg name="id" type="int" brief="идентификатор" />
///     </args>
///     <body>
  public function get_attribute($id) { return $this->pdo->getAttribute($id); }
///     </body>
///   </method>

///   <method name="transaction">
///     <brief>Открывает транзакцию</brief>
///     <body>
  public function transaction() {
    try {
      $this->pdo->beginTransaction();
    } catch (PDOException $e) {
      throw new DB_ConnectionException($e->getMessage);
    }
  }
///     </body>
///   </method>

///   <method name="commit">
///     <brief>Фиксирует транзакцию</brief>
///     <body>
  public function commit() {
    try {
      $this->pdo->commit();
    } catch (PDOException $e) {
      throw new DB_ConnectionException($e->getMessage);
    }
  }
///     </body>
///   </method>

///   <method name="rollback">
///     <brief>Откатывает транзакцию</brief>
///     <body>
  public function rollback() {
    try {
      $this->pdo->rollback();
    } catch (PDOException $e) {
      throw new DB_ConnectionException($e->getMessage);
    }
  }
///     </body>
///   </method>

///   <method name="last_insert_id" returns="int">
///     <brief>Возвращает последний вставленный идентификатор</brief>
///     <body>
  public function last_insert_id() {
    try {
      return $this->pdo->lastInsertId();
    } catch (PDOException $e) {
      throw new DB_ConnectionException($e->getMessage);
    }
  }
///     </body>
///   </method>

///   <method name="quote" returns="string">
///     <brief>Квотит параметр</brief>
///     <args>
///       <arg name="value" brief="параметр" />
///     </args>
///     <body>
  public function quote($value) { return $this->pdo->quote($value); }
///     </body>
///   </method>

///   <method name="after_connect">
///     <brief>Вызывается в DB.Connection после соединения</brief>
///     <body>
  public function after_connect() {}
///     </body>
///   </method>

///   </protocol>
}
/// </class>


/// <class name="DB.Adapter.PDO.Cursor" stereotype="abstract">
///   <brief>Класс курсора БД</brief>
///   <implements interface="DB.Adapter.Cursor.Interface" />
abstract class DB_Adapter_PDO_Cursor
  implements DB_Adapter_CursorInterface {

  protected  $pdo;

///   <protocol name="creating">

///   <method name="__construct">
///     <brief>Конструктор</brief>
///     <args>
///       <arg name="pdo" brief="PDO объект" />
///     </args>
///     <body>
  public function __construct($pdo) {
    $this->pdo = $pdo;
    $this->pdo->setFetchMode(PDO::FETCH_ASSOC);
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="processing">

///   <method name="fetch" returns="mixed">
///     <brief>Возвращает очередную строку результата</brief>
///     <body>
  public function fetch() {
    try {
      return $this->pdo->fetch();
    } catch (PDOException $e) {
      throw new DB_CursorException($e->getMessage());
    }
  }
///     </body>
///   </method>

///   <method name="fetch_all" returns="mixed">
///     <brief>Возвращает все строки результата</brief>
///     <body>
  public function fetch_all() {
    try {
      while ($row = $this->pdo->fetch()) $result[] = $row;
      return $result;
    } catch (PDOException $e) {
      throw new DB_CursorException($e->getMessage());
    }
  }
///     </body>
///   </method>

///   <method name="close">
///     <brief>Закрывает курсор</brief>
///     <body>
  public function close() {
    try {
      return $this->pdo->closeCursor();
    } catch (PDOException $e) {
      throw new DB_CursorException($e->getMessage());
    }
  }
///     </body>
///   </method>

  protected function get_param_type($v) {
    switch(gettype($v)) {
      case 'resource':
        return PDO::PARAM_LOB;
      case 'integer':
        return PDO::PARAM_INT;
      case 'boolean':
        return PDO::PARAM_BOOL;
      case 'null':
        return PDO::PARAM_NULL;
      default:
        return PDO::PARAM_STR;
    }
  }
  

///   <method name="execute">
///     <brief>Выполняет запрос</brief>
///     <args>
///       <arg name="binds" type="array" brief="массив параметров" />
///     </args>
///     <body>
  public function execute(array $binds) {
    try {
      foreach (array_values($binds) as $n => $v)
        $this->pdo->bindValue($n+1, $v, $this->get_param_type($v));
      return $this->pdo->execute();
    } catch (PDOException $e) {
      throw new DB_CursorException($e->getMessage());
    }
  }
///     </body>
///   </method>

///   <method name="get_num_of_rows" returns="int">
///     <brief>Возвращает количество строк в результате</brief>
///     <body>
  public function get_num_of_rows() { return $this->pdo->rowCount(); }
///     </body>
///   </method>

///   <method name="get_num_of_columns" returns="int">
///     <brief>Возвращает количетсво колонок</brief>
///     <body>
  public function get_num_of_columns() { return $this->pdo->columnCount(); }
///     </body>
///   </method>

///   <method name="get_row_metadata" returns="DB.ColumnMeta">
///     <brief>Возвращает мета данные строки результата</brief>
///     <body>
  public function get_row_metadata() {
    $metadata = new ArrayObject();
    for ($i = 0; $i < $this->pdo->columnCount(); $i++) {
      $v = $this->pdo->getColumnMeta($i);
      $metadata[$v['name']] = new DB_ColumnMeta(
        $v['name'], isset($v['native_type'])?$v['native_type']:null, $v['len'], $v['precision']);
    }
    return $metadata;
  }
///     </body>
///   </method>

///   </protocol>
}
/// </class>

/// </module>
