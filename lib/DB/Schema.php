<?php
/**
 * @package DB\Schema
 */


Core::load('DB', 'DB.ORM.Mappers', 'Events');

class DB_Schema implements Core_ModuleInterface {
  const VERSION = '0.1.0';
  
  static public function table(DB_Connection $connection) {
    return new DB_Schema_Table($connection);
  }


//TODO: оформить коментарии


/** $schema -- массив или лубой другой итерируемый объект
 *  Ключи являются именами таблиц.
 *  Значения -- массивы , которые могут содержать следующие ключи:
 *   - 'description': описание таблицы, пока не поддерживается
 *   - 'mysql_engine', 'mysql_engine': параметры специфичные для MySQL
 *   - 'columns': массив массив, каждый из которых описывает колонку.
 *       Колонка:
 *     - 'description': описание
 *     - 'name': имя колонки,
 *     - 'mysql_definition', 'pgsql_definition' ... : строка определени колонки, если задано, то все последющие опции игнорируются
 *     - 'type': Тип: 'char', 'varchar', 'text', 'blob', 'int', 'timestamp', 'datetime', 'date', 'time'
 *       'float', 'numeric', or 'serial'. Используйте 'serial' для auto incrementing
 *         колонок, для MySQL это аналогично 'INT auto_increment'.
 *     - 'mysql_type', 'pgsql_type', 'sqlite_type', etc.: тип специфичный для конкретной БД.
 *     - 'size': Размер: 'tiny', 'small', 'medium', 'normal',
 *       'big'. Аналогично TINYINT TINYTEXT и т.д. в MySQL
 *     - 'not null': По умолчанию false
 *     - 'default': Значение по умолчанию для колонки
 *     - 'default_quote': помещать ли значение по умоляанию в кавычки. По умолчанию, если
 *        default строка, то true.
 *     - 'length': Длина для таких типов как 'char', 'varchar' or 'text'
 *     - 'unsigned': По умолчанию false
 *     - 'precision', 'scale': Для типа 'numeric'
 *     Обязательные параметры name и type
 *  - 'indexes' массив массивов описывающие индексы.
 *    Каждый индекс может содержать
 *     - 'name': имя индекса
 *     - 'type': тип: null, primary key, unique, fulltext
 *     - 'columns': массив колонок входящих в индекс,
 *        каждая может быть строкой с именем колонки или массивом, первый элемент которого имя, а второй длина

DB_Schema::process(
  array(
    'test2' => array(
      'mysql_engine' => 'MyISAM',
      'columns' => array(
        array('name' => 'id', 'type' => 'serial'),
        array('name' => 'title', 'type' => 'varchar', 'length' => 255, 'default' => '', 'not null' => true),
        array('name' => 'body', 'type' => 'text', 'size' => 'big', 'default' => '', 'not null' => true),
      ),
      'indexes' => array(
        array('type' => 'primary key', 'columns' => array('id')),
        array('name' => 'title', 'columns' => array('title'))
      )
    )
  ),
  $db
);
*/
  static public function process($schema, $connection = null, $force_update = false, $cache = null) {
    if (is_null($connection)) Core::load('WS');
    $rc = true;
    foreach ($schema as $table_name => $table_data) {
      $conn = !is_null($connection) ? $connection : WS::env()->orm->session->connection_for($table_name);
      if (!$conn) return false;
      $table = self::table($conn)->for_table($table_name)->for_data($table_data);
      if ($cache) {
        $key = 'schema:' . $table_name;
        $value = md5(serialize($table_data));
        if ($cache->get($key) != $value) {
          $rc = $table->execute($force_update) && $rc;
          $cache->set($key, $value, 0);
        }
      }
      else {
        $rc = $table->execute($force_update) && $rc;
      }
    }
    return $rc;
  }

  static public function process_cache($schema, $cache = true, $connection = null, $force_update = false ) {
    if ($cache === true) $cache = WS::env()->cache;
    return DB_Schema::process($schema, $connection, $force_update, $cache);
  }

  static public function process_file($file, $cache = true, $connection = null, $force_update = false) {
    if (is_file($file)) {
      $schema = include $file;
      if ($cache === true) $cache = WS::env()->cache;
      return DB_Schema::process($schema,$connection, $force_update, $cache);
    }
    return false;
  }
  
}

class DB_Schema_ExecutionListener implements DB_QueryExecutionListener {
  protected $query = array();
  protected $skip = false;
  
  public function on_execute(DB_Cursor $cursor) {
    if (!$this->skip && $cursor->is_successful) {
      $this->query[] = $cursor->pure_sql();
    }
    $this->skip = false;
  }

  public function skip($v = true) {
    $this->skip = $v;
    return $this;
  }
  
  public function query() {
    $q = $this->query;
    $this->reset();
    return $q;
  }

  public function reset() {
    $this->query = array();
    return $this;
  }

}

class DB_Schema_Table {

  protected $db;
  protected $data;
  protected $dsn;
  protected $information;
  protected $db_schema;
  
  public function __construct(DB_Connection $connection) {
    $this->db = $connection;
    $this->db->listeners->append(new DB_Schema_ExecutionListener(), 'schema_query');
    $this->dsn = $connection->dsn;
    $this->information = DB_ORM_Mappers::set()->connect($connection)->information;
    $this->db_schema = $this->db->adapter->get_schema();
    $this->clear();
  }
  
  public function log_query() {
    return $this->db->listeners['schema_query']->query();
  }
  
  public function for_table($name) {
    $this->data['name'] = $name;
    return $this;
  }
  
  public function for_data(array $data) {
    $this->data = array_merge_recursive($this->data, $data);
    return $this;
  }
  
  public function exists() {
    return $this->mapper('tables')->count();
  }
  
  public function column_exists($name) {
    return $this->mapper('columns')->for_name($name)->count();
  }
  
  public function get_columns() {
    return $this->mapper('columns')->select();
  }
  
  public function get_column($name) {
    return $this->mapper('columns')->for_name($name)->select();
  }
  
  public function set_column($column) {
    $this->data['columns'][$column['name']] = $column;
    return $this;
  }
  
  public function is_column_change($column) {
    $type = strtolower($this->db_schema->map_type($column));
    $nullable = isset($column['not null']) ? !$column['not null'] : null;
    $mapper = $this->mapper('columns')->
      for_name($column['name'])->
      for_type($type)->
      for_default(isset($column['default']) ? $column['default'] : null)->
      for_nullable($nullable)->
      for_length(isset($column['length']) ? $column['length'] : null);
    return $mapper->count() < 1;
  }
  
  public function remove_column($column) {
    $column = is_string($column) ? array('name' => $column) : $column;
    $name = $column['name'];
    if(isset($this->data['columns'][$name]))
      unset($this->data['columns'][$name]);
    else
      $this->data['remove_columns'][$name] = $column;
    return $this;
  }

  public function index_exists($index) {
    $this->db->listeners['schema_query']->skip();
    $sql = $this->db_schema->index_exists_query($this->data, $index);
    return $this->db->execute($sql);
  }
  
  public function add_index($index) {
    $this->data['indexes'][] = $index;
    return $this;
  }
  
  public function remove_index($index) {
    $index = is_string($index) ? array('name' => $index) : $index;
    if (isset($this->data['indexes'][$index['name']]))
      unset($this->data['indexes'][$index['name']]);
    else
      $this->data['remove_indexes'][$index['name']] = $index;
    return $this;
  }
  
  //TODO: rename table
  public function rename($new_name) {
    return $this;
  }
  
  protected function data_canonicalize() {
    $this->canonicalize_name('columns');
    $this->canonicalize_name('indexes');
    $this->canonicalize_name('remove_columns');
    $this->canonicalize_name('remove_indexes');
  }
  
  //TODO remove_columns indexes remove_indexes
  protected function canonicalize_columns($key) {
  
  }
  
  protected function canonicalize_name($key) {
    $values = &$this->data[$key];
    foreach ($values as $k => &$v) {
      switch(true) {
        case is_string($k):
          $v['name'] = $k;
          break;
        case is_int($k) && !empty($v['name']):
          break;
          $values[$key][$v['name']] = $v;
          unset($values[$k]);
          break;
      }
    }
  }
  
  public function execute($force_update = false) {
    $this->data_canonicalize();
    Events::call('db.schema.execute', $this->data);
    Events::call('db.schema.execute.' . $this->data['name'], $this->data);
    $this->data_canonicalize();
    $this->db->listeners['schema_query']->reset();
    if ($this->exists())
      $rc = $this->update($force_update);
    else
      $rc = $this->create();
    $this->clear();
    return $rc;
  }
  
  public function clear() {
    $this->data['columns'] = array();
    $this->data['remove_columns'] = array();
    $this->data['indexes']  = array();
    $this->data['remove_indexes'] = array();
    return $this;
  }
  
  public function update($force_update = false) {
    //TODO: update table info
    $actions = $this->update_columns_actions($force_update);
    $rc = $this->alter_action($actions);
    $rc = $this->indexes_action($force_update) && $rc;
    return $rc;
  }
  
  public function create() {
    $column_defs= $this->create_columns_definition();
    $index_defs = $this->create_indexes_definition();
   return  $this->create_action($column_defs, $index_defs);
  }
  
  protected function alter_action($actions) {
    if (empty($actions)) return true;
    return $this->db->execute($this->db_schema->alter_table($this->data, $actions));
  }
  
  //TODO: force update
  
  //TODO: FOREIGN KEY
  protected function indexes_action($force_update = false) {
    $rc = true;
    foreach ($this->data['remove_indexes'] as $index)
      if ($this->index_exists($index))
        $rc = $this->db->execute($this->db_schema->remove_index($this->data['name'], $index)) && $rc;
    foreach ($this->data['indexes'] as $index) {
      switch(true) {
        case $force_update && $this->index_exists($index):
          $rc = $this->db->execute($this->db_schema->remove_index($this->data['name'], $index)) && $rc;
          $rc = $this->db->execute($this->db_schema->add_index($this->data['name'], $index)) && $rc;
          break;
        case !$this->index_exists($index):
          $rc = $this->db->execute($this->db_schema->add_index($this->data['name'], $index)) && $rc;
          break;
      }
    }
    return $rc;
  }
  
  protected function create_action($column_defs, $index_defs) {
    $sql = $this->db_schema->create_table($this->data, array_merge($column_defs, $index_defs));
    return $this->db->execute($sql);
  }
  
  protected function update_columns_actions($force_update = false) {
    $actions = array();
    foreach ($this->data['remove_columns'] as $name => $column)
      if ($this->column_exists($column['name']))
        $actions[] = $this->db_schema->remove_column($column);
    foreach ($this->data['columns'] as $name => $column) {
      if ($this->column_exists($column['name'])) {
        if ($force_update || $this->is_column_change($column))
          $actions[] = $this->db_schema->update_column($column);
      }
      else
        $actions[] = $this->db_schema->add_column($column);
    }
    return $actions;
  }
  
  protected function create_columns_definition() {
    $defs = array();
    foreach ($this->data['columns'] as $name => $column) 
      $defs[] = $this->db_schema->column_definition($column);
    return $defs;
  }
  
  protected function create_indexes_definition() {
    $defs = array();
    foreach ($this->data['indexes'] as $index)
      $defs[] = $this->db_schema->index_definition($this->data['name'], $index);
    return $defs;
  }
  
  public function mapper($name) {
    $this->db->listeners['schema_query']->skip();
    return $this->information->$name->for_table($this->data['name'])->for_schema($this->dsn->database);
  }

  public function inspect() {
    return $this->db_schema->inspect($this->information, $this->data['name'], $this->dsn);
  }
}
