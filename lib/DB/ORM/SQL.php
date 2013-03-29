<?php
/// <module name="DB.ORM.SQL" version="0.2.0" maintainer="timokhin@techart.ru">
/// <brief>Простейший DSL для генерации текстов SQL-запросов.</brief>
/// <details>
///   <p>Модуль предоставляет набор классов, позволяющих с помощью вызовов методов построиь
///      отдельные фрагменты SQL-запросов и затем сформировать полный текст запроса. Использование
///      DSL вместо явного формирования текста запроса позволяет получить единообразный стиль
///      записи запросов и уменьшить количество потенциальных ошибок при их формировании.</p>
///   <p></p>
/// </details>

/// <class name="DB.ORM.SQL" stereotype="module">
///   <brief>Класс модуля</brief>
///   <implements interface="Core.ModuleInterface" />
///   <depends supplier="DB.ORM.SQL.Select" stereotype="creates" />
///   <depends supplier="DB.ORM.SQL.Update" stereotype="creates" />
///   <depends supplier="DB.ORM.SQL.Insert" stereotype="creates" />
///   <depends supplier="DB.ORM.SQL.Delete" stereotype="creates" />
class DB_ORM_SQL implements Core_ModuleInterface {

///   <constants>
  const VERSION = '0.2.0';
///   </constants>

///   <protocol name="building">

///   <method name="Select" scope="class" returns="DB.ORM.SQL.Select">
///     <brief>Создает объект класса DB.ORM.SQL.Select для генерации SQL SELECT</brief>
///     <body>
  static public function Select() {
    return new DB_ORM_SQL_Select(Core::normalize_args(func_get_args()));
  }
///     </body>
///   </method>

///   <method name="Update" scope="class" returns="DB.ORM.SQL.Update">
///     <brief>Создает объект класса DB.ORM.SQL.Update для генерации SQL UPDATE</brief>
///     <args>
///       <arg name="table" type="string" brief="имя таблицы" />
///     </args>
///     <body>
  static public function Update($table) { return new DB_ORM_SQL_Update($table); }
///     </body>
///   </method>

///   <method name="Insert" scope="class" returns="DB.ORM.SQL.Insert">
///     <brief>Создает объект класса DB.ORM.SQL.Insert для генерации SQL INSERT</brief>
///     <body>
  static public function Insert() {
    return new DB_ORM_SQL_Insert(Core::normalize_args(func_get_args()));
  }
///     </body>
///   </method>

///   <method name="Delete" scope="class" returns="DB.ORM.SQL.Delete">
///     <brief>Создает объект класса DB.ORM.SQL.Delete для генерации SQL DELETE</brief>
///     <args>
///       <arg name="table" type="string" brief="иия таблицы" />
///     </args>
///     <body>
  static public function Delete($table) { return new DB_ORM_SQL_Delete($table); }
///     </body>
///   </method>

///   </protocol>
}
/// </class>


/// <class name="DB.ORM.SQL.Statement" stereotype="abstract">
///   <brief>Абстрактный класс объекта формирования SQL-выражения</brief>
///   <implements interface="Core.StringifyInterface" />
abstract class DB_ORM_SQL_Statement
  implements Core_StringifyInterface {

  protected $parts = array();
  protected $adapter = null;

///   <protocol name="configuring">

///   <method name="mode" returns="DB.ORM.SQL.Insert">
///     <args>
///       <arg name="mode" type="string" />
///     </args>
///     <body>
  public function mode($mode) {
    if ($mode) $this->parts['mode'] = (string) $mode;
    return $this;
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="stringifying" interface="Core.StringifyProtocol">

///   <method name="__toString" returns="string">
///     <body>
  public function __toString() { return $this->as_string(); }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="supporting">

///   <method name="make_part" returns="DB.ORM.SQL.Statement">
///     <args>
///       <arg name="name" type="string" />
///       <arg name="initial" default="array" />
///     </args>
///     <body>
  protected function make_part($name, $initial = array()) {
    if (!isset($this->parts[$name])) $this->parts[$name] = $initial;
    return $this;
  }
///     </body>
///   </method>

///   <method name="update_part" returns="DB.ORM.SQL.Statement">
///     <args>
///       <arg name="name" type="string" />
///       <arg name="args" type="array" />
///     </args>
///     <body>
  protected function update_part($name, array $args) {
    $this->make_part($name);
    foreach (Core::normalize_args($args) as $v) $this->parts[$name][] = (string) $v;
    return $this;
  }
///     </body>
///   </method>

  public function set_adapter(DB_Adapter_ConnectionInterface $adapter) {
    $this->adapter = $adapter;
    return $this;
  }

  public function get_adapter() {
    if ($this->adapter)
      return $this->adapter;
    Core::load('WS');
    if (WS::env()->orm)
      return WS::env()->orm->connection->adapter;
    if (WS::env()->db)
      return WS::env()->db->adapter;
    return null;
  }

  public function escape($str) {
    if (preg_match('!\s+!', $str)) return $str;
    if (preg_match('!\.+!', $str)) return $str;
    $adapter = $this->get_adapter();
    if ($adapter) {
      $str = str_replace(array('"', "'", '`'), '', $str);
      return $adapter->escape_identifier($str);
    }
    return $str;
  }

///   </protocol>
}
/// </class>


/// <class name="DB.ORM.SQL.Selection" extends="DB.ORM.SQL.Statement" stereotype="abstract">
abstract class DB_ORM_SQL_Selection extends DB_ORM_SQL_Statement {

///   <protocol name="configuring">

///   <method name="where" returns="DB.ORM.SQL.SelectStatement">
///     <body>
  public function where() {
    $args = func_get_args();
    return $this->update_part('where', $args);
  }
///     </body>
///   </method>

///   </protocol>
}
/// </class>


/// <class name="DB.ORM.SQL.Update" extends="DB.ORM.SQL.Selection">
class DB_ORM_SQL_Update extends DB_ORM_SQL_Selection {

///   <protocol name="creating">

///   <method name="__construct">
///     <args>
///       <arg name="table" type="string" />
///     </args>
///     <body>
  public function __construct($table) {
    $this->parts['table']   = $table;
    $this->parts['columns'] = array();
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="configuring">

///   <method name="set" returns="DB.ORM.SQL.Update">
///     <body>
  public function set() {
    $this->parts['columns'] = array();
    foreach (Core::normalize_args(func_get_args()) as $k => $column)
      $this->parts['columns'][$k] = (string) $column;
    return $this;
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="stringifying" interface="Core.StringifyInterface">

///   <method name="as_string" returns="string">
///     <body>
  public function as_string() {
    $set = '';
    foreach ($this->parts['columns'] as $k => $v)
      $set .= ($set ? ', ' : ''). (is_string($k) ? "$k = $v" : "$v = :$v");
    $where = '';
    if (isset($this->parts['where'])) $where = implode(') AND (', $this->parts['where']);

    return 'UPDATE '.(isset($this->parts['mode']) ? $this->parts['mode'] : '').' '. $this->escape($this->parts['table'])."\n".
           "SET $set\n".
           ($where ? "WHERE ($where)" : '')."\n";
  }
///     </body>
///   </method>

///   </protocol>
}
/// </class>


/// <class name="DB.ORM.SQL.Select" extends="DB.ORM.SQL.Selection">
class DB_ORM_SQL_Select extends DB_ORM_SQL_Selection {

///   <protocol name="creating">

///   <method name="__construct">
///     <body>
    public function __construct(array $columns = array()) {
      if (count($columns))
      $this->columns($columns);
    }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="configuring">

///   <method name="columns" returns="DB.ORM.SQL.SelectStatement">
///     <body>
  public function columns() {
    $this->make_part('columns');

    foreach (Core::normalize_args(func_get_args()) as $k => $v) {
      if (is_array($v))
        foreach ($v as $col) $this->parts['columns'][$col] = "$k.$col";
      else {
        if (is_int($k) && !preg_match('!distinct!i', $v)) $this->parts['columns'][preg_match('{[0-9a-zA-Z_]+\.([0-9a-zA-Z_]+)}', $v, $m) ? $m[1] : $v] = $v;
        else            $this->parts['columns'][$k] = $v;
      }
    }

    return $this;
  }
///     </body>
///   </method>

///   <method name="from" returns="DB.ORM.SQL.SelectStatement">
///     <body>
  public function from() {
    $this->make_part('from');

    foreach (Core::normalize_args(func_get_args()) as $k => $v)
      if (is_int($k)) $this->parts['from'][] = (string) $v;
      else            $this->parts['from'][$k] = (string) $v;

    return $this;
  }
///     </body>
///   </method>

///   <method name="order_by" returns="DB.ORM.SQL.SelectStatement">
///     <body>
  public function order_by() {
    $args = func_get_args();
    return $this->update_part('order_by', $args);
  }
///     </body>
///   </method>

///   <method name="having" returns="DB.ORM.SQL.SelectStatement">
///     <body>
  public function having() {
    $args = func_get_args();
    return $this->update_part('having', $args);
  }
///     </body>
///   </method>

///   <method name="group_by" returns="DB.ORM.SQL.SelectStatement">
///     <body>
  public function group_by() {
    $args = func_get_args();
    return $this->update_part('group_by', $args);
  }
///     </body>
///   </method>

///   <method name="join" returns="DB.ORM.SelectStatement">
///     <args>
///       <arg name="type" type="string" />
///       <arg name="table" type="string" />
///     </args>
///     <body>
  public function join($type, $table) {
    $this->make_part('joins');
    $this->parts['joins'][] = array($type, $table, Core::normalize_args(array_splice(func_get_args(), 2)));
    return $this;
  }
///     </body>
///   </method>

///   <method name="range" returns="DB.ORM.SQL.SelectStatement">
///     <args>
///       <arg name="limit" type="int" />
///       <arg name="offset" type="int" default="0" />
///     </args>
///     <body>
  public function range($limit, $offset = 0) {
    $this->parts['range'] = array($limit, $offset);
    return $this;
  }
///     </body>
///   </method>

  public function index($name) {
    $this->parts['index'] = (string) $name;
    return $this;
  }

///   </protocol>

///   <protocol name="stringifying" interface="Core.StringifyInterface">

///   <method name="as_string" returns="string">
///     <body>
  public function as_string() {
    $columns = '*';
    if (isset($this->parts['columns'])) {
      $columns = '';
      foreach ($this->parts['columns'] as $k => $v)
        $columns .= ($columns ? ', ' : '').($v == $k ? $v : "$v $k");
    }

    $from = '';
    foreach ($this->parts['from'] as $k => $v) $from .= ($from ? ', ' : ''). $this->escape($v).(is_int($k) ? '' : " $k");

    $where = '';
    if (isset($this->parts['where'])) $where = implode(') AND (', $this->parts['where']);

    $group_by = '';
    if (isset($this->parts['group_by'])) $group_by = implode(', ', $this->parts['group_by']);

    $order_by = '';
    if (isset($this->parts['order_by'])) $order_by = implode(', ', $this->parts['order_by']);

    $having = '';
    if (isset($this->parts['having'])) $having = implode(') AND (', $this->parts['having']);

    $range = '';
    if (isset($this->parts['range'])) {
      $range = 'LIMIT '.$this->parts['range'][0];
      if ($this->parts['range'][1] > 0) $range .=' OFFSET '.$this->parts['range'][1];
    }

    $index = '';
    if (isset($this->parts['index'])) $index = 'USE INDEX ('.$this->parts['index'].')';

    $joins = '';
    if (isset($this->parts['joins'])) {
      foreach ($this->parts['joins'] as $join)
        $joins .= strtoupper($join[0]).' JOIN '.$this->escape($join[1]).' ON ('.implode(') AND (', $join[2]).') ';
    }
    return 'SELECT '.(isset($this->parts['mode']) ? $this->parts['mode'] : '')." $columns\n".
           "FROM $from $index $joins".
           ($where    ? "\nWHERE ($where)" : '').
           ($group_by ? "\nGROUP BY $group_by" : '').
           ($having   ? "\nHAVING ($having)" : '').
           ($order_by ? "\nORDER BY $order_by" : '').
           ($range    ? "\n$range" : '')."\n";
  }
///     </body>
///   </method>

///   </protocol>
}
/// </class>


/// <class name="DB.ORM.SQL.Delete" extends="DB.ORM.SQL.Selection">
class DB_ORM_SQL_Delete extends DB_ORM_SQL_Selection {

///   <protocol name="creating">

///   <method name="__construct">
///     <args>
///       <arg name="table" type="string" />
///     </args>
///     <body>
  public function __construct($table) {
    $this->parts['table'] = $table;
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="stringifying" interface="Core.StringifyInterface">

///   <method name="as_string" returns="string">
///     <body>
  public function as_string() {
    if (isset($this->parts['where'])) $where = implode(') AND (', $this->parts['where']);
    return 'DELETE '.(isset($this->parts['mode']) ? $this->parts['mode'] : '').
           ' FROM '.$this->escape($this->parts['table'])."\n".
           ($where ? "WHERE ($where)" : '')."\n";
  }
///     </body>
///   </method>

///   </protocol>
}
/// </class>


/// <class name="DB.ORM.SQL.Insert" extends="DB.ORM.SQL.Statement">
class DB_ORM_SQL_Insert extends DB_ORM_SQL_Statement {

///   <protocol name="creating">

///   <method name="__construct">
///     <body>
  public function __construct(array $columns) {
    $this->parts['columns'] = array();
    foreach ($columns as $v) $this->parts['columns'][] = (string) $v;
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="configuring">

///   <method name="into" returns="DB.ORM.SQL.Insert">
///     <args>
///       <arg name="table" type="string" />
///     </args>
///     <body>
  public function into($table) {
    $this->parts['table'] = (string) $table;
    return $this;
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="stringifying" interface="Core.StringifyInterface">

///   <method name="as_string" returns="string">
///     <body>
  public function as_string() {
    $columns = implode(', ', $this->parts['columns']);
    $values  = '';
    foreach ($this->parts['columns'] as $v) $values .= ($values ? ', ' : '').":$v";
    return 'INSERT '.(isset($this->parts['mode']) ? $this->parts['mode'] : '').
           ' INTO '.$this->escape($this->parts['table'])." ($columns)\nVALUES($values)";
  }
///     </body>
///   </method>

///   </protocol>
}
/// </class>

/// </module>
