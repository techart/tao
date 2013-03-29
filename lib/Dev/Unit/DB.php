<?php
/// <module name="Dev.Unit.DB" maintainer="svistunov@techart.ru" version="0.1.2">
Core::load('DB', 'Dev.Unit', 'Proc', 'IO.FS');

/// <class name="Dev.Unit.DB" stereotype="module">
///   <implements interface="Core.ModuleInterface" />
class Dev_Unit_DB implements Core_ModuleInterface {
///   <constants>
  const VERSION = '0.1.2';
///   </constants>

  static protected $options = array(
    'dsn' => 'mysql://www:www@mysql.rd1.techart.intranet/test',
    'db_script' => 'mysql -u%s -p%s -h%s %s'
  );

///   <protocol name="configuring">

///   <method name="options" returns="mixed" scope="class">
///     <args>
///       <arg name="options" type="array" default="array()" />
///     </args>
///     <body>
  static public function options(array $options = array()) {
    if (count($options)) Core_Arrays::update(self::$options, $options);
    return self::$options;
  }
///     </body>
///   </method>

///   <method name="option" returns="mixed">
///     <args>
///       <arg name="name" type="string" />
///       <arg name="value" default="null" />;
///     </args>
///     <body>
  static public function option($name, $value = null) {
    $prev = isset(self::$options[$name]) ? self::$options[$name] : null;
    if ($value !== null) self::options(array($name => $value));
    return $prev;
  }
///     </body>
///   </method>

///   </protocol>
}
/// </class>

/// <class name="Dev.Unit.DB.TestCase" exteds="Dev.Unit.TestCase">
class Dev_Unit_DB_TestCase extends Dev_Unit_TestCase {
  protected $connection;
  protected $data = array();

///   <protocol name="testing">

///   <method name="before_setup" access="protected">
///     <body>
  protected function before_setup() {
    $this->create_connection();
    $this->create_sql();
    $this->load_data();
  }
///     </body>
///   </method>

///   <method name="after_teardown" access="protected">
///     <body>
  protected function after_teardown() {
    $this->drop_sql();
    $this->drop_connection();
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="performing">

///   <method name="create_connection">
///     <args>
///       <arg name="dsn" type="string" default="''" />
///     </args>
///     <body>
  protected function create_connection($dsn = '') {
    $dsn = $dsn ? $dsn : Dev_Unit_DB::option('dsn');
    $this->connection = DB::Connection($dsn);
  }
///     </body>
///   </method>

///   <method name="drop_connection">
///     <body>
  protected function drop_connection() {
    $this->connection->disconnect();
    $this->connection = null;
  }
///     </body>
///   </method>

///   <method name="create_sql">
///     <args>
///       <arg name="path" type="string" default="''" />
///     </args>
///     <body>
  protected function create_sql($path = null) {
    $path = $path ? $path : $this->default_path_for('create.sql');
    $this->sql($path);
  }
///     </body>
///   </method>

///   <method name="load_data">
///     <args>
///       <arg name="path" type="string" default="''" />
///     </args>
///     <body>
  protected function load_data($path = '') {
    $path = $path ? $path : $this->default_path_for('data.php');
    $this->data = include $path;
    foreach ($this->data as $table => $rows)
      $this->insert_rows($table, $rows);
  }
///     </body>
///   </method>

///   <method name="drop_sql">
///     <args>
///       <arg name="path" type="string" default="''" />
///     </args>
///     <body>
  protected function drop_sql($path = '') {
    $path = $path ? $path : $this->default_path_for('drop.sql');
    $this->sql($path);
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="name">

///   <method name="default_path_for" returns="string">
///     <args>
///       <arg name="name" type="string" />
///     </args>
///     <body>
  private function default_path_for($name) {
    return implode(array(
      '.',
      'test',
      'data',
      str_replace('.', DIRECTORY_SEPARATOR,
          preg_replace('{^[^.]+\.}', '', Core_Types::module_name_for($this))),
      $name
    ), DIRECTORY_SEPARATOR);
  }
///     </body>
///   </method>

///   <method name="insert_rows">
///     <args>
///       <arg name="tables" type="type" />
///       <arg name="data" type="array" />
///     </args>
///     <body>
  private function insert_rows($table,$rows) {
      foreach ($rows as $row) {
        $columns = array();
        $values = array();
        foreach ($row as $column => $value) {
          $columns[] = "`$column`";
          $values[] = "'$value'";
        }
        $sql = "INSERT INTO `$table` (".implode(',', $columns).") VALUES (".
          implode(',', $values).")";
        $this->connection->execute($sql);
      }
  }
///     </body>
///   </method>

///   <method name="sql">
///     <args>
///       <arg name="path" type="string" />
///     </args>
///     <body>
  protected function sql($path) {
    $p = Proc::Pipe($this->build_command(), 'wb');
    foreach (IO_FS::File($path) as $line)
      $p->write($line);
    $p->close();
    if ($p->exit_status != 0) throw new Core_Exception('Erro while running db_script');
  }
///     </body>
///   </method>

///   <method name="build_command">
///     <body>
  protected function build_command() {
    return vsprintf(Dev_Unit_DB::option('db_script'), array(
      $this->connection->dsn->user,
      $this->connection->dsn->password,
      $this->connection->dsn->host,
      $this->connection->dsn->database,
      $this->connection->dsn->scheme,
      $this->connection->dsn->port,
    ));
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="accessing">

///   <method name="__get" returns="mixed">
///     <args>
///       <arg name="property" type="string" />
///     </args>
///     <body>
  public function __get($property) {
    if (key_exists($property, $this->data))
      return $this->data[$property];
    else
      return parent::__get($property);
  }
///     </body>
///   </method>

///   <method name="__set" returns="mixed">
///     <args>
///       <arg name="property" type="string" />
///       <arg name="value" />
///     </args>
///     <body>
  public function __set($property, $value) {
    if (key_exists($property, $this->data))
      throw new Core_ReadOnlyPropertyException($property);
    else
      return parent::__set($property);
  }
///     </body>
///   </method>

///   <method name="__isset" returns="boolean">
///     <args>
///       <arg name="property" type="string" />
///     </args>
///     <body>
  public function __isset($property) {
    if (key_exists($property, $this->data))
      return true;
    else
      return parent::__set($property);
  }
///     </body>
///   </method>

///   <method name="__unset">
///     <args>
///       <arg name="property" type="string" />
///     </args>
///     <body>
  public function __unset($property) {
    if (key_exists($property, $this->data))
      throw new Core_ReadOnlyPropertyException($property);
    else
      return parent::__set($property);
  }
///     </body>
///   </method>

///   </protocol>

}
/// </class>

/// </module>
