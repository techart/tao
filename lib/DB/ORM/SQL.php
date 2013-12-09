<?php
/**
 * DB.ORM.SQL
 * 
 * Простейший DSL для генерации текстов SQL-запросов.
 * 
 * <p>Модуль предоставляет набор классов, позволяющих с помощью вызовов методов построиь
 * отдельные фрагменты SQL-запросов и затем сформировать полный текст запроса. Использование
 * DSL вместо явного формирования текста запроса позволяет получить единообразный стиль
 * записи запросов и уменьшить количество потенциальных ошибок при их формировании.</p>
 * <p>
 * 
 * @package DB\ORM\SQL
 * @version 0.2.0
 */

/**
 * Класс модуля
 * 
 * @package DB\ORM\SQL
 */
class DB_ORM_SQL implements Core_ModuleInterface {

  const VERSION = '0.2.0';


/**
 * Создает объект класса DB.ORM.SQL.Select для генерации SQL SELECT
 * 
 * @return DB_ORM_SQL_Select
 */
  static public function Select() {
    return new DB_ORM_SQL_Select(Core::normalize_args(func_get_args()));
  }

/**
 * Создает объект класса DB.ORM.SQL.Update для генерации SQL UPDATE
 * 
 * @param string $table
 * @return DB_ORM_SQL_Update
 */
  static public function Update($table) { return new DB_ORM_SQL_Update($table); }

/**
 * Создает объект класса DB.ORM.SQL.Insert для генерации SQL INSERT
 * 
 * @return DB_ORM_SQL_Insert
 */
  static public function Insert() {
    return new DB_ORM_SQL_Insert(Core::normalize_args(func_get_args()));
  }

/**
 * Создает объект класса DB.ORM.SQL.Delete для генерации SQL DELETE
 * 
 * @param string $table
 * @return DB_ORM_SQL_Delete
 */
  static public function Delete($table) { return new DB_ORM_SQL_Delete($table); }

}


/**
 * Абстрактный класс объекта формирования SQL-выражения
 * 
 * @abstract
 * @package DB\ORM\SQL
 */
abstract class DB_ORM_SQL_Statement
  implements Core_StringifyInterface {

  protected $parts = array();
  protected $adapter = null;


/**
 * @param string $mode
 * @return DB_ORM_SQL_Insert
 */
  public function mode($mode) {
    if ($mode) $this->parts['mode'] = (string) $mode;
    return $this;
  }



/**
 * @return string
 */
  public function __toString() { return $this->as_string(); }



/**
 * @param string $name
 * @param  $initial
 * @return DB_ORM_SQL_Statement
 */
  protected function make_part($name, $initial = array()) {
    if (!isset($this->parts[$name])) $this->parts[$name] = $initial;
    return $this;
  }

/**
 * @param string $name
 * @param array $args
 * @return DB_ORM_SQL_Statement
 */
  protected function update_part($name, array $args) {
    $this->make_part($name);
    foreach (Core::normalize_args($args) as $v) $this->parts[$name][] = (string) $v;
    return $this;
  }

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

}


/**
 * @abstract
 * @package DB\ORM\SQL
 */
abstract class DB_ORM_SQL_Selection extends DB_ORM_SQL_Statement {


/**
 * @return DB_ORM_SQL_SelectStatement
 */
  public function where() {
    $args = func_get_args();
    return $this->update_part('where', $args);
  }

}


/**
 * @package DB\ORM\SQL
 */
class DB_ORM_SQL_Update extends DB_ORM_SQL_Selection {


/**
 * @param string $table
 */
  public function __construct($table) {
    $this->parts['table']   = $table;
    $this->parts['columns'] = array();
  }



/**
 * @return DB_ORM_SQL_Update
 */
  public function set() {
    $this->parts['columns'] = array();
    foreach (Core::normalize_args(func_get_args()) as $k => $column)
      $this->parts['columns'][$k] = (string) $column;
    return $this;
  }



/**
 * @return string
 */
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

}


/**
 * @package DB\ORM\SQL
 */
class DB_ORM_SQL_Select extends DB_ORM_SQL_Selection {


/**
 */
    public function __construct(array $columns = array()) {
      if (count($columns))
      $this->columns($columns);
    }



/**
 * @return DB_ORM_SQL_SelectStatement
 */
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

/**
 * @return DB_ORM_SQL_SelectStatement
 */
  public function from() {
    $this->make_part('from');

    foreach (Core::normalize_args(func_get_args()) as $k => $v)
      if (is_int($k)) $this->parts['from'][] = (string) $v;
      else            $this->parts['from'][$k] = (string) $v;

    return $this;
  }

/**
 * @return DB_ORM_SQL_SelectStatement
 */
  public function order_by() {
    $args = func_get_args();
    return $this->update_part('order_by', $args);
  }

/**
 * @return DB_ORM_SQL_SelectStatement
 */
  public function having() {
    $args = func_get_args();
    return $this->update_part('having', $args);
  }

/**
 * @return DB_ORM_SQL_SelectStatement
 */
  public function group_by() {
    $args = func_get_args();
    return $this->update_part('group_by', $args);
  }

/**
 * @param string $type
 * @param string $table
 * @return DB_ORM_SelectStatement
 */
  public function join($type, $table) {
    $this->make_part('joins');
    $this->parts['joins'][] = array($type, $table, Core::normalize_args(array_splice(func_get_args(), 2)));
    return $this;
  }

/**
 * @param int $limit
 * @param int $offset
 * @return DB_ORM_SQL_SelectStatement
 */
  public function range($limit, $offset = 0) {
    $this->parts['range'] = array($limit, $offset);
    return $this;
  }

  public function index($name) {
    $this->parts['index'] = (string) $name;
    return $this;
  }



/**
 * @return string
 */
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

}


/**
 * @package DB\ORM\SQL
 */
class DB_ORM_SQL_Delete extends DB_ORM_SQL_Selection {


/**
 * @param string $table
 */
  public function __construct($table) {
    $this->parts['table'] = $table;
  }



/**
 * @return string
 */
  public function as_string() {
    if (isset($this->parts['where'])) $where = implode(') AND (', $this->parts['where']);
    return 'DELETE '.(isset($this->parts['mode']) ? $this->parts['mode'] : '').
           ' FROM '.$this->escape($this->parts['table'])."\n".
           ($where ? "WHERE ($where)" : '')."\n";
  }

}


/**
 * @package DB\ORM\SQL
 */
class DB_ORM_SQL_Insert extends DB_ORM_SQL_Statement {


/**
 */
  public function __construct(array $columns) {
    $this->parts['columns'] = array();
    foreach ($columns as $v) $this->parts['columns'][] = (string) $v;
  }



/**
 * @param string $table
 * @return DB_ORM_SQL_Insert
 */
  public function into($table) {
    $this->parts['table'] = (string) $table;
    return $this;
  }



/**
 * @return string
 */
  public function as_string() {
    $columns = implode(', ', $this->parts['columns']);
    $values  = '';
    foreach ($this->parts['columns'] as $v) $values .= ($values ? ', ' : '').":$v";
    return 'INSERT '.(isset($this->parts['mode']) ? $this->parts['mode'] : '').
           ' INTO '.$this->escape($this->parts['table'])." ($columns)\nVALUES($values)";
  }

}

