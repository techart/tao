<?php
/// <module name="DB.SQL" version="1.0.2" maintainer="timokhin@techart.ru">
Core::load('Data.Pagination', 'DB');

/// <class name="DB.SQL" stereotype="module">
///   <implements interface="Core.ModuleInterface" />
///   <depends supplier="DB.SQL.Table" stereotype="creates" />
///   <depends supplier="DB.SQL.View" stereotype="creates" />
///   <depends supplier="DB.SQL.SelectStatement" stereotype="creates" />
///   <depends supplier="DB.SQL.FindStatement" stereotype="creates" />
///   <depends supplier="DB.SQL.CountStatement" stereotype="creates" />
///   <depends supplier="DB.SQL.UpdateStatement" stereotype="creates" />
///   <depends supplier="DB.SQL.InsertStatement" stereotype="creates" />
///   <depends supplier="DB.SQL.DeleteStatement" stereotype="creates" />
///   <depends supplier="DB.SQL.Pager" stereotype="creates" />
///   <depends supplier="DB.SQL.Database" stereotype="uses" />
class DB_SQL implements Core_ModuleInterface {

///   <constants>
  const MODULE  = 'DB.SQL';
  const VERSION = '1.0.2';
///   </constants>

///   <protocol name="connecting">

///   <method name="connect" returns="DB.SQL.Database" scope="class">
///     <args>
///       <arg name="dsn" type="string" />
///     </args>
///     <body>
  static public function connect($dsn) {
    return self::db()->connect(DB::Connection($dsn));
  }
///     </body>
///   </method>

///   <method name="db" returns="DB.SQL.Database" scope="class">
///     <body>
  static public function db() {
    return DB_SQL_Database::instance();
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="building">

///   <method name="Table" returns="DB.SQL.Table" scope="class">
///     <args>
///       <arg name="name"     type="string" />
///       <arg name="columns" type="array" default="array()" />
///     </args>
///     <body>
  static public function Table($name, array $columns = array()) {
    return new DB_SQL_Table($name, $columns);
  }
///     </body>
///   </method>

///   <method name="View" returns="DB.SQL.View" scope="class">
///     <args>
///       <arg name="parent" default="null" />
///     </args>
///     <body>
  static public function View($parent = null) {
    return new DB_SQL_View($parent);
  }
///     </body>
///   </method>

///   <method name="Select" returns="DB.SQL.SelectStatement" scope="class" varargs="true">
///     <body>
  static public function Select() {
    return new DB_SQL_SelectStatement($columns = func_get_args());
  }
///     </body>
///   </method>

///   <method name="Find" returns="DB.SQL.FindStatement" scope="class" varargs="true">
///     <body>
  static public function Find() {
    return new DB_SQL_FindStatement($columns = func_get_args());
  }
///     </body>
///   </method>

///   <method name="Count" returns="DB.SQL.CountStatement" scope="class">
///     <args>
///       <arg name="column" type="string" default="'COUNT(*)'" />
///     </args>
///     <body>
  static public function Count($column = 'COUNT(*)') {
    return new DB_SQL_CountStatement($column);
  }
///     </body>
///   </method>

///   <method name="Update" returns="DB.SQL.UpdateSttement" scope="class">
///     <args>
///       <arg name="table" default="null" />
///     </args>
///     <body>
  static public function Update($table = null) {
    return new DB_SQL_UpdateStatement($table);
  }
///     </body>
///   </method>

///   <method name="Delete" returns="DB.SQL.DeleteStatement" scope="class">
///     <args>
///       <arg name="table" default="null" />
///     </args>
///     <body>
  static public function Delete($table = null) {
    return new DB_SQL_DeleteStatement($table);
  }
///     </body>
///   </method>

///   <method name="Insert" returns="DB.SQL.InsertStatement" scope="class" varargs="true">
///     <body>
  static public function Insert() {
    return new DB_SQL_InsertStatement($args = func_get_args());
  }
///     </body>
///   </method>

///   <method name="Pager" returns="DB.SQL.Pager" scope="class">
///     <args>
///       <arg name="num_of_items" type="int" />
///       <arg name="current_page" type="int" />
///       <arg name="items_per_page" type="int" default="Data.Pagination::DEFAULT_ITEMS_PER_PAGE" />
///     </args>
///     <body>
  static public function Pager($num_of_items, $current_page, $items_per_page = Data_Pagination::DEFAULT_ITEMS_PER_PAGE) {
    return new DB_SQL_Pager($num_of_items, $current_page, $items_per_page);
  }
///     </body>
///   </method>

///   </protocol>
}
/// </class>


/// <class name="DB.SQL.Exception" extends="DB.Exception" stereotype="exception">
class DB_SQL_Exception extends DB_Exception {}
/// </class>


/// <class name="DB.SQL.StatementNotFoundException" extends="DB.SQL.Exception" stereotype="exception">
class DB_SQL_StatementNotFoundException extends DB_SQL_Exception {
  protected $name;

/// <protocol name="creating">

///  <method name="__construct">
///   <args>
///     <arg name="name" type="string" />
///   </args>
///   <body>
  public function __construct($name) {
    $this->name = (string) $name;
    parent::__construct("Statement not found: $this->name");
  }
///   </body>
///   </method>

///   </protocol>
}
/// </class>


/// <class name="DB.SQL.IncompatiblePrototypeException" extends="DB.SQL.Exception" stereotype="exception">
class DB_SQL_IncompatiblePrototypeException extends DB_SQL_Exception {
  protected $prototype_type;

///   <protocol name="creating">

///   <method name="__construct">
///     <args>
///       <arg name="prototype" />
///     </args>
///     <body>
  public function __construct($prototype) {
    $this->prototype_type = Core_Types::virtual_class_name_for($prototype);
    parent::__construct("Incompatible prototype type: $this->prototype_type");
  }
///     </body>
///   </method>

///   </protocol>
}
/// </class>


/// <class name="DB.SQL.Database">
///   <implements interface="Core.PropertyAccessInterface" />
///   <depends supplier="DB.SQL.Entity" stereotype="uses" />
class DB_SQL_Database implements Core_PropertyAccessInterface {

  static protected $instance;

  protected $connection;
  protected $tables;
  protected $views;

  protected $class_mappers = array();

///   <protocol name="creating">

///   <method name="instance" returns="DB.SQL.Database" scope="class">
///     <body>
  static public function instance() {
    return self::$instance ? self::$instance : self::$instance = new DB_SQL_Database();
  }
///     </body>
///   </method>

///   <method name="__construct" access="protected">
///     <body>
  protected function __construct() {
    $this->tables = new ArrayObject();
    $this->views  = new ArrayObject();
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="connecting">

///   <method name="connect" returns="DB.SQL.Database">
///     <args>
///       <arg name="connection" type="DB.Connection" />
///     </args>
///     <body>
  public function connect(DB_Connection $connection) {
    $this->connection = $connection;
    return $this;
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="building">

///   <method name="table" returns="DB.SQL.Database">
///     <args>
///       <arg name="table" type="DB.SQL.Table" />
///     </args>
///     <body>
  public function table(DB_SQL_Table $table) {
    $this->tables[$table->name] = $table;
    $this->views[$table->name]  = $table->view;
    return $this;
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="computing">

///   <method name="quote" returns="string" varargs="true">
///     <body>
  public function quote() {
    $args = func_get_args();
    if (count($args) == 1) {
      return $this->connection->quote($args[0]);
    } else {
      $sql = (string) Core_Arrays::shift($args);
      foreach ($args as $arg)
        $sql = Core_Regexps::replace('{\?}', $this->connection->quote((string) $arg), $sql, 1);
      return $sql;
    }
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="performing">

///   <method name="store">
///     <args>
///       <arg name="entity" type="DB.SQL.Entity" />
///     </args>
///     <body>
  public function store(DB_SQL_Entity $entity) {
    return ($mapper = $this->view_for($entity)) ?
      ($entity->id ? $mapper->update($entity) : $mapper->insert($entity)) : null;
  }
///     </body>
///   </method>

///   <method name="load" returns="DB.SQL.Entity">
///     <args>
///       <arg name="class_name" type="string" />
///       <arg name="id" />
///     </args>
///     <body>
  public function load($class_name, $id) {
    $mapper = $this->view_for($class_name);
    return ($mapper = $this->view_for($class_name)) ? $mapper->find($id) : null;
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="quering">

///   <method name="view_for" returns="DB.SQL.View">
///     <args>
///       <arg name="object" />
///     </args>
///     <body>
  public function view_for($object) {
    $class_name = Core_Types::virtual_class_name_for($object);
    return $class_name && isset($this->class_mappers[$class_name]) ?
      $this->class_mappers[$class_name] : null;
  }
///     </body>
///   </method>

///   <method name="view_for_table" returns="DB.SQL.View">
///     <args>
///       <arg name="table_name" type="string" />
///     </args>
///     <body>
  public function view_for_table($table_name) {
    return $this->views[$table_name];
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="accessing" interface="Core.PropertyAccessInterface">

///   <method name="__get" returns="mixed">
///     <args>
///       <arg name="property" type="string" />
///     </args>
///     <body>
  public function __get($property) {
    switch ($property) {
      case 'connection':
      case 'tables':
      case 'views':
        return $this->$property;
      default:
        if (isset($this->views[$property]))
          return $this->views[$property];
        else
          throw new Core_MissingPropertyException($property);
    }
  }
///     </body>
///   </method>

///   <method name="__set" returns="mixed" stereotype="accessor">
///     <args>
///       <arg name="property" type="string" />
///       <arg name="value" />
///     </args>
///     <body>
  public function __set($property, $value) {
    switch ($property) {
      case 'connection':
      case 'tables':
      case 'views':
        throw new Core_ReadOnlyPropertyException($name);
      default:
        if (isset($this->views[$property]))
          throw new Core_ReadOnlyPropertyException($name);
        else
          throw new Core_MissingPropertyException($name);
    }
  }
///     </body>
///   </method>

///   <method name="__isset" returns="boolean">
///     <args>
///       <arg name="property" type="string" />
///     </args>
///     <body>
  public function __isset($property) {
    switch ($property) {
      case 'connection':
      case 'tables':
      case 'views':
        return true;
      default:
        return isset($this->views[$property]);
    }
  }
///     </body>
///   </method>

///   <method name="__unset">
///     <args>
///       <arg name="property" type="string" />
///     </args>
///     <body>
  public function __unset($property) {
    throw $this->__isset($property) ?
      new Core_UndestroyablePropertyException($property) :
      new Core_MissingPropertyException($property);
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="supporting">

///   <method name="map_class" returns="DB.SQL.Database">
///     <body>
  public function map_class($class_name, DB_SQL_View $view) {
    $this->class_mappers[Core_Types::virtual_class_name_for($class_name)] = $view;
    return $this;
  }
///     </body>
///   </method>

///   </protocol>
}
/// </class>

/// <aggregation>
///   <source class="DB.SQL.Database" role="database" multiplicity="1" />
///   <target class="DB.Connection" role="connection" multiplicity="1" />
/// </aggregation>

/// <aggregation>
///   <source class="DB.SQL.Database" role="database" multiplicity="1" />
///   <target class="DB.SQL.Table" role="table" multiplicity="N" />
/// </aggregation>

/// <aggregation>
///   <source class="DB.SQL.Database" role="database" multiplicity="1" />
///   <target class="DB.SQL.View" role="view" multiplicity="N" />
/// </aggregation>

/// <class name="DB.SQL.Table">
///   <implements interface="Core.PropertyAccessInterface" />
///   <implements interface="Core.CallInterface" />
class DB_SQL_Table
  implements Core_PropertyAccessInterface,
             Core_CallInterface {

  protected $name;
  protected $columns = array();
  protected $view;
  protected $serial;

///   <protocol name="creating">

///   <method name="__construct">
///     <args>
///       <arg name="name" type="string" />
///       <arg name="columns" type="array" default="array()" />
///     </args>
///     <body>
  public function __construct($name, array $columns = array()) {
    $this->name    = $name;
    
    $entity = new DB_SQL_Entity();
    $entity->set_dbtable($this);
    $this->view = DB_SQL::View()->
      for_table($this)->
      maps_to($entity);

    $this->columns($columns);
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="building">

///   <method name="serial" returns="DB.SQL.Table">
///     <args>
///       <arg name="name" type="string" default="'id'" />
///     </args>
///     <body>
  public function serial($name = 'id') {
    $this->serial = (string) $name;
    return $this;
  }
///     </body>
///   </method>

///   <method name="columns" returns="DB.SQL.Table" varargs="true">
///     <body>
  public function columns() {
    foreach (Core_Arrays::flatten(func_get_args()) as $arg)
      $this->columns[] = (string) $arg;
    return $this;
  }
///     </body>
///   </method>

///   <method name="default_view" returns="DB.SQL.Table">
///     <args>
///       <arg name="view" type="DB.SQL.View" />
///     </args>
///     <body>
  public function default_view(DB_SQL_View $view) {
    $this->view = $view->for_table($this);
    return $this;
  }
///     </body>
///   </method>

///   <method name="default_sql" returns="DB.SQL.Table" varargs="true">
///     <body>
  public function default_sql() {
    $this->view->default_sql(func_get_args());
    return $this;
  }
///     </body>
///   </method>


///   <method name="view" returns="DB.SQL.Table">
///     <args>
///       <arg name="name" type="string" />
///       <arg name="view" type="DB.SQL.View" />
///     </args>
///     <body>
  public function view($name, DB_SQL_View $view) {
    $this->view->view($name, $view);
    return $this;
  }
///     </body>
///   </method>

///   <method name="sql" returns="DB.SQL.Table">
///     <args>
///       <arg name="name" type="string" />
///       <arg name="sql"  type="DB.SQL.Statement" />
///     </args>
///     <body>
  public function sql($name, DB_SQL_Statement $sql) {
    $this->view->sql($name, $sql);
    return $this;
  }
///     </body>
///   </method>

///   <method name="isql" returns="DB.SQL.Table">
///     <args>
///       <arg name="name" type="string" />
///       <arg name="sql"  type="DB.SQL.Statement" />
///     </args>
///     <body>
  public function isql($name, DB_SQL_Statement $sql) {
    $this->view->isql($name, $sql);
    return $this;
  }
///     </body>
///   </method>


///   <method name="nsql" returns="DB.SQL.Table">
///     <args>
///       <arg name="name" type="string" />
///       <arg name="sql"  type="DB.SQL.Statement" />
///     </args>
///     <body>
  public function nsql($name, DB_SQL_Statement $sql) {
    $this->view->nsql($name, $sql);
    return $this;
  }
///     </body>
///   </method>

///   <method name="maps_to" returns="DB.SQL.Table">
///     <args>
///       <arg name="prototype" />
///     </args>
///     <body>
  public function maps_to($prototype) {
    $this->view->maps_to($prototype);
    return $this;
  }
///     </body>
///   </method>

///   <method name="order_by" returns="DB.SQL.Table" varargs="true">
///     <body>
  public function order_by() {
    $this->view->order_by($args = func_get_args());
    return $this;
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="quering">

///   <method name="column_exists" returns="boolean">
///     <args>
///       <arg name="name" type="string" />
///     </args>
///     <body>
  public function column_exists($name) {
    return Core_Arrays::contains($this->columns, (string) $name);
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="accessing" interface="Core.PropertyAccessInterface">

///   <method name="__get" returns="mixed">
///     <args>
///       <arg name="property" type="string" />
///     </args>
///     <body>
  public function __get($property) {
    switch ($property) {
      case 'name':
      case 'database':
      case 'columns':
      case 'view':
      case 'serial':
        return $this->$property;
      default:
        return $this->view->$property;
    }
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
    switch ($property) {
      case 'name':
      case 'database':
      case 'columns':
      case 'view':
        throw new Core_ReadOnlyPropertyException($property);
      default:
        $this->view->$property = $value;
    }
    return $this;
  }
///     </body>
///   </method>

///   <method name="__isset" returns="boolean">
///     <args>
///       <arg name="property" type="string" />
///     </args>
///     <body>
  public function __isset($property) {
    switch ($property) {
      case 'name':
      case 'database':
      case 'columns':
      case 'view':
        return true;
      default:
        return isset($this->view->$property);
    }
  }
///     </body>
///   </method>

///   <method name="__unset">
///     <args>
///       <arg name="property" type="string" />
///     </args>
///     <body>
  public function __unset($property) {
    throw $this->__isset($property) ?
      new Core_UndestroyablePropertyException($property) :
      new Core_MissingPropertyException($property);
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="calling" interface="Core.CallerInterface">

///   <method name="__call" returns="mixed">
///     <args>
///       <arg name="method" type="string" />
///       <arg name="args" type="array" />
///     </args>
///     <body>
  public function __call($method, $args) {
    return $this->view->__call($method, $args);
  }
///     </body>
///   </method>

///   </protocol>
}
/// </class>
/// <composition>
///   <source class="DB.SQL.Table" role="table" multiplicity="1" />
///   <target class="DB.SQL.View"  role="view" multiplicity="1" />
/// </composition>


/// <class name="DB.SQL.View">
///   <implements interface="Core.PropertyAccess" />
///   <implements interface="Core.CallInterface" />
///   <depends supplier="DB.SQL.StatementNotFoundException" stereotype="throws" />
class DB_SQL_View
  implements Core_PropertyAccessInterface,
             Core_CallInterface {

  protected $table;
  protected $parent;

  protected $prototype;

  protected $views;
  protected $statements;

  protected $columns  = array();
  protected $from     = array();
  protected $where    = array();
  protected $having   = array();
  protected $joins    = array();
  protected $group_by = array();
  protected $order_by = array();

///   <protocol name="creating">

///   <method name="__construct">
///     <args>
///       <arg name="parent" default="null" />
///     </args>
///     <body>
  public function __construct($parent = null) {
    $this->views  = new ArrayObject();
    $this->parent = $parent;
    $this->setup();
  }
///     </body>
///   </method>

///   <method name="setup" returns="DB.SQL.View" access="protected">
///     <body>
  protected function setup() { return $this; }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="building">

///   <method name="tie_to" returns="DB.SQL.View">
///     <args>
///       <arg name="view" type="DB.SQL.View" />
///     </args>
///     <body>
  public function tie_to(DB_SQL_View $view) {
    $this->parent = $view;
    return $this;
  }
///     </body>
///   </method>

///   <method name="default_sql" returns="DB.SQL.Table" varargs="true">
///     <body>
  public function default_sql() {
    $args = Core_Arrays::flatten(func_get_args());
    foreach ((count($args) > 0 ? $args : array('find', 'select', 'count', 'insert', 'update', 'delete')) as $name) {
        switch ($name) {
          case 'find':
            $this->isql('find',   DB_SQL::Find()->where('id = :id'));
            break;
          case 'select':
            $this->isql('select', DB_SQL::Select());
            break;
          case 'count':
            $this->isql('count',  DB_SQL::Count());
            break;
          case 'insert':
            $this->isql('insert', DB_SQL::Insert());
          case 'update':
            $this->isql('update', DB_SQL::Update()->where('id = :id'));
            break;
          case 'delete':
            $this->isql('delete', DB_SQL::Delete()->where('id = :id'));
            break;
        }
    }
    return $this;
  }
///     </body>
///   </method>

///   <method name="for_table" returns="DB.SQL.View">
///     <args>
///       <arg name="table" type="DB.SQL.Table" />
///     </args>
///     <body>
  public function for_table(DB_SQL_Table $table) {
    $this->table = $table;
    $this->from[] = $table->name;
    return $this;
  }
///     </body>
///   </method>

///   <method name="maps_to" returns="DB.SQL.View">
///     <args>
///       <arg name="prototype" />
///     </args>
///     <body>
  public function maps_to($prototype) {
    if (!$class_name = Core_Types::real_class_name_for($prototype))
      throw new Core_InvalidArgumentTypeException('prototype', $prototype);

    if (Core_Types::is_object($prototype))
      $this->prototype = $prototype;
    else
      $this->prototype = new $class_name();

    if (Core_Types::is_subclass_of('DB.SQL.Entity', $this->prototype))
      DB_SQL::db()->map_class(Core_Types::real_class_name_for($this->prototype), $this);

    return $this;
  }
///     </body>
///   </method>

///   <method name="sql" returns="DB.SQL.View">
///     <args>
///       <arg name="name" type="string" />
///       <arg name="statement" type="DB.SQL.Statement" />
///     </args>
///     <body>
  public function sql($name, DB_SQL_Statement $statement) {
    return $this->isql($name, $statement);
  }
///     </body>
///   </method>

///   <method name="isql" returns="DB.SQL.View">
///     <args>
///       <arg name="name" type="string" />
///       <arg name="statement" type="DB.SQL.Statement" />
///     </args>
///     <body>
  public function isql($name, DB_SQL_Statement $statement) {
    $this->statements[$name] = $statement->tie_to($this);
    return $this;
  }
///     </body>
///   </method>

///   <method name="nsql" returns="DB.SQL.View">
///     <args>
///       <arg name="name" type="string" />
///       <arg name="statement" type="DB.SQL.Statement" />
///     </args>
///     <body>
  public function nsql($name, DB_SQL_Statement $statement) {
    $this->statements[$name] = $statement;
    return $this;
  }
///     </body>
///   </method>

///   <method name="view" returns="DB.SQL.View">
///     <args>
///       <arg name="name" type="string" />
///       <arg name="view" type="DB.SQL.View" />
///     </args>
///     <body>
  public function view($name, DB_SQL_View $view) {
    $this->views[$name] = $view->tie_to($this);
    return $this;
  }
///     </body>
///   </method>

///   </protocol>


///   <protocol name="performing">

///   <method name="save" returns="mixed">
///     <args>
///       <arg name="object" type="DB.SQL.Entity" />
///     </args>
///     <body>
  public function save(DB_SQL_Entity $object) {
    return $object->id ? $this->update($object) : $this->insert($object);
  }
///     </body>
///   </method>

///   </protocol>


///   <protocol name="accessing" interface="Core.PropertyAccessInterface">

///   <method name="__get" returns="mixed">
///     <args>
///       <arg name="property" type="string" />
///     </args>
///     <body>
  public function __get($property) {
    switch ($property) {
      case 'table':
      case 'prototype':
        return $this->$property ? $this->$property: $this->parent->__get($property);
      case 'parent':
        return $this->parent;
      case 'columns':
        return count($this->columns) ?
          $this->columns :
          ($this->table ? $this->table->columns : $this->parent->__get('columns'));
      case 'from':
      case 'joins':
      case 'where':
      case 'having':
      case 'order_by':
      case 'group_by':
        return Core_Arrays::merge($this->parent ? $this->parent->__get($property) : array(), $this->$property);
      default:
        if (isset($this->views[$property]))
          return $this->views[$property];
        else
          return $this->find_statement($property);
    }
  }
///     </body>
///   </method>

///   <method name="__set">
///     <args>
///       <arg name="property" type="string" />
///       <arg name="value" />
///     </args>
///     <body>
  public function __set($property, $value) {
    if (isset($this->$property))
      throw new Core_ReadOnlyPropertyException($property);
    else
      throw new Core_MissingPropertyException($property);
  }
///     </body>
///   </method>

///   <method name="__isset" returns="boolean">
///     <args>
///       <arg name="property" type="string" />
///     </args>
///     <body>
  public function __isset($property) {
    return isset($this->$property);
  }
///     </body>
///   </method>

///   <method name="__unset">
///     <args>
///       <arg name="property" type="string" />
///     </args>
///     <body>
  public function __unset($property) {
    throw isset($this->property) ?
      new Core_UndestroyablePropertyException($property) :
      new Core_MissingPropertyException($property);
  }
///     </body>
///   </method>

///   </protocol>


///   <protocol name="calling" interface="Core.CallInterface">

///   <method name="__call" returns="mixed">
///     <args>
///       <arg name="method" type="string" />
///       <arg name="args" type="array" />
///     </args>
///     <body>
  public function __call($method, $args) {
    switch ($method) {
      case 'columns':
        $this->set_columns($args);
        break;
      case 'from':
      case 'where':
      case 'having':
      case 'order_by':
      case 'group_by':
        foreach (Core_Arrays::flatten($args) as $arg) {
          $target   =& $this->$method;
          $target[] = $arg;
        }
        break;
      case 'left_join':
      case 'right_join':
      case 'inner_join':
      case 'outer_join':
        $this->joins[] = array(
          Core_Strings::upcase(Core_Strings::replace($method, '_join', '')),
          Core_Arrays::shift($args),
          $args );
        break;
      default:
        return Core_Types::reflection_for($sql = $this->find_statement($method))->
          getMethod('run')->
          invokeArgs($sql, $args);
    }
    return $this;
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="supporting">

///   <method name="set_columns" access="protected">
///     <args>
///       <arg name="columns" type="array" />
///     </args>
///     <body>
  protected function set_columns(array $columns) {
    $prefix = '';
    foreach (Core_Arrays::flatten($columns) as $column) {
      if ($column[strlen($column) - 1] == ':')
       $prefix = ($column == ':' ? '' : rtrim($column, ':').'.');
      else
        $this->columns[] = "$prefix$column";
    }
    return $this;
  }
///     </body>
///   </method>

///   <method name="find_statement" access="protected" returns="DB.SQL.Statement">
///     <args>
///       <arg name="name" type="string" />
///     </args>
///     <body>
  protected function find_statement($name) {
    if (isset($this->statements[$name])) return clone $this->statements[$name];
    if ($this->parent && $sql = $this->parent->find_statement($name)) {
      $clone = clone $sql;
      return $clone->tie_to($this);
    } else
      throw new DB_SQL_StatementNotFoundException($name);
  }
///     </body>
///   </method>

///   </protocol>
}
/// </class>

/// <aggregation>
///   <source class="DB.SQL.View" role="view" multiplicity="1" />
///   <target class="DB.SQL.Statement" role="statement" multiplicity="N" />
/// </aggregation>

/// <aggregation>
///   <source class="DB.SQL.View" role="child" multiplicity="1" />
///   <target class="DB.SQL.View" role="parent" multiplicity="1" />
/// </aggregation>

/// <class name="DB.SQL.Statement" stereotype="abstract">
abstract class DB_SQL_Statement {

  protected $view;

///   <protocol name="creating">

///   <method name="__construct">
///     <body>
  public function __construct() {}
///     </body>
///   </method>

///   </protocol>

///   <protocol name="building">

///   <method name="tie_to" returns="DB.SQL.Statement">
///     <args>
///       <arg name="view" type="DB.SQL.View" />
///     </args>
///     <body>
  public function tie_to(DB_SQL_View $view) {
    $this->view = $view;
    return $this;
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="performing">

///   <method name="run" returns="mixed">
///     <body>
  abstract public function run();
///     </body>
///   </method>

///   </protocol>

///   <protocol name="accessing" interface="Core.PropertyAccessInterface">

///   <method name="__get" returns="mixed">
///     <args>
///       <arg name="property" type="string" />
///     </args>
///     <body>
  public function __get($property) {
    switch ($name) {
      case 'view': return $this->view;
      default:
        throw new Core_MissingPropertyException($property);
    }
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
    switch ($name) {
      case 'view':
        throw new Core_ReadOnlyPropertyException($property);
      default:
        throw new Core_MissingPropertyException($property);
    }
  }
///     </body>
///   </method>

///   <method name="__isset" returns="boolean">
///     <args>
///       <arg name="property" type="string" />
///     </args>
///     <body>
  public function __isset($property) {
    switch ($property) {
      case 'view':
        return true;
      default:
        return false;
    }
  }
///     </body>
///   </method>

///   <method name="__unset">
///     <args>
///       <arg name="property" type="string" />
///     </args>
///     <body>
  public function __unset($property) {
    throw $this->__isset($property) ?
      new Core_UndestroyablePropertyException($property) :
      new Core_MissingPropertyException($property);
  }
///     </body>
///   </method>

///   </protocol>
}
/// </class>


/// <class name="DB.SQL.AbstractSelectStatement" extends="DB.SQL.Statement">
abstract class DB_SQL_AbstractSelectStatement extends DB_SQL_Statement  {

  protected $columns  = array();
  protected $from     = array();
  protected $where    = array();
  protected $having   = array();
  protected $order    = array();
  protected $joins    = array();
  protected $group_by = array();
  protected $order_by = array();
  protected $no_order = false;
  protected $offset   = 0;
  protected $limit    = 0;


///   <protocol name="building">

///   <method name="limit" returns="DB.SQL.AbstractSelectStatement">
///     <args>
///       <arg name="limit" type="int" />
///       <arg name="offset" type="int" />
///     </args>
///     <body>
  public function limit($limit, $offset = false) {
    $this->limit = (int) $limit;
    if ($offset !== false) $this->offset = (int) $offset;
    return $this;
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="converting">

///   <method name="as_string" returns="string">
///     <body>
  public function as_string() { return $this->make_sql(); }
///     </body>
///   </method>

///   <method name="__toString" returns="string">
///     <body>
  public function __toString() { return $this->as_string(); }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="calling" interface="Core.CallInterface">

///   <method name="__call" returns="mixed">
///     <args>
///       <arg name="method" type="string" />
///       <arg name="args" type="array" />
///     </args>
///     <body>
  public function __call($method, $args) {
    switch ($method) {
      case 'columns':
        $this->set_columns($args);
        break;
      case 'offset':
        $this->$method = $args[0];
        break;
      case 'from':
      case 'where':
      case 'having':
      case 'order_by':
      case 'group_by':
        foreach (Core_Arrays::flatten($args) as $arg) {
          $target   =& $this->$method;
          $target[] = $arg;
        }
        break;
      case 'left_join':
      case 'right_join':
      case 'inner_join':
      case 'outer_join':
        $this->joins[] = array(
          Core_Strings::upcase(Core_Strings::replace($method, '_join', '')),
          Core_Arrays::shift($args),
          $args );
        break;
    }
    return $this;
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="supporting">

///   <method name="set_columns" access="protected">
///     <args>
///       <arg name="columns" type="array" />
///     </args>
///     <body>
  protected function set_columns(array $columns) {
    $prefix = '';
    foreach (Core_Arrays::flatten($columns) as $column) {
      if ($column[strlen($column) - 1] == ':')
       $prefix = ($column == ':' ? '' : rtrim($column, ':').'.');
      else
        $this->columns[] = "$prefix$column";
    }
    return $this;
  }
///     </body>
///   </method>

///   <method name="make_sql" returns="string" access="protected">
///     <body>
  protected function make_sql() {
    $sql = 'SELECT '.
      Core_Arrays::join_with(', ',
        count($this->columns) ? $this->columns : $this->view->__get('columns'))."\n";

    $sql .= 'FROM '.
      Core_Arrays::join_with(', ',
        Core_Arrays::merge(
          $this->view ? $this->view->__get('from') : array(), $this->from))."\n";

    foreach ($joins = Core_Arrays::merge(
               $this->view ? $this->view->__get('joins') : array() , $this->joins) as $join) {
      $sql .= $join[0].' JOIN '.$join[1].' ON ('.Core_Arrays::join_with(') AND (', $join[2]).")\n";
    }

    if (count($where = Core_Arrays::merge(
        $this->view ? $this->view->__get('where') : array(), $this->where)))
      $sql .= 'WHERE ('.Core_Arrays::join_with(') AND (', $where).")\n";

    if (count($group_by = Core_Arrays::merge($this->view ? $this->view->__get('group_by') : array(), $this->group_by)))
      $sql .= 'GROUP BY '.Core_Arrays::join_with(', ', $group_by)."\n";

    if (count($having = Core_Arrays::merge($this->view ? $this->view->__get('having') : array(), $this->having)))
      $sql .= 'HAVING ('.Core_Arrays::join_with(') AND (', $having).")\n";

    if (!$this->no_order && count($order_by = count($this->order_by) ? $this->order_by : $this->view->__get('order_by')))
      $sql .= 'ORDER BY '.Core_Arrays::join_with(', ', $order_by)."\n";

//      if (!$this->no_order && count($order_by = Core_Arrays::merge($this->view ? $this->view->__get('order_by') : array(), $this->order_by)))
//      $sql .= 'ORDER BY '.Core_Arrays::join_with(', ', $order_by)."\n";

    if ($this->limit)  $sql .= 'LIMIT '.(int)$this->limit."\n";
    if ($this->offset) $sql .= 'OFFSET '.(int)$this->offset."\n";

    return $sql;
  }
///     </body>
///   </method>

///   </protocol>
}
/// </class>


/// <class name="DB.SQL.SelectStatement" extends="DB.SQL.AbstractSelectStatement">
class DB_SQL_SelectStatement extends DB_SQL_AbstractSelectStatement {

  protected $prototype = null;

///   <protocol name="creating">

///   <method name="__construct">
///     <args>
///       <arg name="columns" type="array" default="array()" />
///     </args>
///     <body>
  public function __construct(array $columns = array()) {
    parent::__construct();
    $this->set_columns($columns);
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="building">

///   <method name="prototype" returns="DB.SQL.SelectStatement">
///     <args>
///       <arg name="prototype" />
///     </args>
///     <body>
  public function prototype($prototype) {
    $this->prototype = $prototype;
    return $this;
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="performing">

///   <method name="run" returns="ArrayObject" varargs="true">
///     <body>
  public function run() {
    return DB_SQL::db()->connection->
      prepare($this->make_sql())->
      bind(count($args = func_get_args()) > 1 ? $args : $args[0])->
      execute()->
      as_object(
        $this->prototype ?
          $this->prototype :
          ($this->view->prototype ? $this->view->prototype : new ArrayObject()))->
      fetch_all();
  }
///     </body>
///   </method>

///   </protocol>
}
/// </class>


/// <class name="DB.SQL.FindStatement" extends="DB.SQL.AbstractSelectStatement">
class DB_SQL_FindStatement extends DB_SQL_AbstractSelectStatement {

///   <protocol name="creating">

///   <method name="__construct">
///     <args>
///       <arg name="columns" type="array" />
///     </args>
///     <body>
  public function __construct(array $columns = array()) {
    parent::__construct();
    $this->set_columns($columns);
    $this->limit = 1;
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="performing">

///   <method name="run" returns="mixed">
///     <body>
  public function run() {
    return DB_SQL::db()->connection->
      prepare($this->make_sql())->
      bind(count($args = func_get_args()) > 1 ? $args : $args[0])->
      execute()->
      as_object($this->view->prototype)->
      fetch();
  }
///     </body>
///   </method>

///   </protocol>
}
/// </class>


/// <class name="DB.SQL.CountStatement" extends="DB.SQL.AbstractSelectStatement">
class DB_SQL_CountStatement extends DB_SQL_AbstractSelectStatement {

///   <protocol name="creating">

///   <method name="__construct">
///     <args>
///       <arg name="column" type="string" />
///     </args>
///     <body>
  public function __construct($column) {
    parent::__construct();
    $this->set_columns(array($column));
    $this->order_by = array();
    $this->limit = 1;
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="performing">

///   <method name="run" returns="mixed" varargs="true">
///     <body>
  public function run() {
    $result = DB_SQL::db()->connection->
      prepare($this->make_sql())->
      bind(count($args = func_get_args()) > 1 ? $args : $args[0])->
      execute()->
      fetch();
    reset($result);
    return $result ? current($result) : null;
  }
///     </body>
///   </method>

///   </protocol>
}
/// </class>


/// <class name="DB.SQL.UpdateStatement" extends="DB.SQL.Statement">
class DB_SQL_UpdateStatement extends DB_SQL_Statement {

  protected $table;

  protected $where = array();
  protected $set   = array();

///   <protocol name="creating">

///   <method name="__construct">
///     <args>
///       <arg name="table" type="string" default="null" />
///     </args>
///     <body>
  public function __construct($table = null) {
    if ($table) $this->table = (string) $table;
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="building">

///   <method name="set" returns="DB.SQL.UpdateStatement" varargs="true">
///     <body>
  public function set() {
    foreach (Core_Arrays::flatten($args = func_get_args()) as $arg)
      $this->set[] = (string) $arg;
    return $this;
  }
///     </body>
///   </method>

///   <method name="where" returns="DB.SQL.UpdateStatement" varargs="true">
///     <body>
  public function where() {
    foreach (Core_Arrays::flatten($args = func_get_args()) as $arg) {
      $this->where[] = (string) $arg;
    }
    return $this;
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="performing">

///   <method name="run" returns="mixed" varargs="true">
///     <body>
  public function run() {
    $args = func_get_args();
    $arg  =& $args[0];
    $serial = $this->view ? $this->view->table->serial : false;

    $sql = 'UPDATE '.($this->table ? $this->table : $this->view->table->name)."\n";

    if (count($this->set) > 0) {
      $sql .= 'SET '.Core_Arrays::join_with(', ', $this->set)."\n";
    } else {
      $set = array();
      if ($arg instanceof DB_SQL_Entity ||
          $arg instanceof Iterator ||
          Core_Types::is_array($arg)) {
        foreach(($arg instanceof DB_SQL_Entity ? $arg->attributes : $arg) as $attr => $value) {
          if (($attr == $serial) ||
            ($this->view && !$this->view->table->column_exists($attr))) continue;

          $set[] = "$attr = :$attr";
        }

      }
      $sql .= 'SET '.Core_Arrays::join_with(', ', $set)."\n";
    }
    if (count($where = Core_Arrays::merge($this->view ? $this->view->__get('where') : array(), $this->where)))
      $sql .= 'WHERE ('.Core_Arrays::join_with(') AND (', $where).')';

    if ($arg instanceof DB_SQL_Entity ? ($arg->before_save() && $arg->before_update()) : true) {
      $rc = DB_SQL::db()->connection->
        prepare($sql)->
        bind(count($args) > 1 ? $args : $arg)->
        execute();
      if ($arg instanceof DB_SQL_Entity && $rc) {
        if ($arg->after_update()) $arg->after_save();
      }
    }

    return $rc;
  }
///     </body>
///   </method>

///   </protocol>
}
/// </class>


/// <class name="DB.SQL.InsertStatement" extends="DB.SQL.Statement">
class DB_SQL_InsertStatement extends DB_SQL_Statement {

  protected $columns = array();
  protected $table;

///   <protocol name="creating">

///   <method name="__construct">
///     <args>
///       <arg name="columns" type="array" default="array()" />
///     </args>
///     <body>
  public function __construct($columns = array()) {
    foreach ($columns as $column) $this->columns[] = (string) $column;
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="building">

///   <method name="into" returns="DB.SQL.InsertStatement">
///     <args>
///       <arg name="table" type="string" />
///     </args>
///     <body>
  public function into($table) {
    $this->table = (string) $table;
    return $this;
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="performing">

///   <method name="run" returns="mixed" varargs="true">
///     <body>
  public function run() {
    $args = func_get_args();
    $arg  =& $args[0];
    $serial = $this->view ? $this->view->table->serial : null;

    $sql = 'INSERT INTO '.($this->table ? $this->table : $this->view->table->name);

    if (count($this->columns) > 0) {
      $columns = $this->columns;
    } elseif ($arg instanceof DB_SQL_Entity) {
      if ($this->view->table->column_exists('type')) $columns = array('type');
      foreach ($arg->attributes as $attr => $value) {
        if ($attr == $serial || ($this->view && !$this->view->table->column_exists($attr))) continue;
        $columns[] =   $attr;
      }
    } elseif ($arg instanceof Iterator || $arg instanceof IteratorAggregate || Core_Types::is_array($arg)) {
      foreach ($arg as $attr => $value) {
        if ($attr == $serial || ($this->view && !$this->view->table->column_exists($attr))) continue;
        $columns[] =   $attr;
      }
    }

    $sql .= ' ('.Core_Arrays::join_with(', ', $columns).")\n";
    $sql .= 'VALUES ('.Core_Arrays::join_with(', ', Core_Arrays::map('return ":$x";', $columns)).")\n";

    if ($arg instanceof DB_SQL_Entity ? ($arg->before_save() && $arg->before_insert()) : true) {
      $rc = DB_SQL::db()->connection->
        prepare($sql)->
        bind(count($args) > 1 ? $args : $arg)->
        execute()->is_successful;

      if ($rc) {
        if ($arg instanceof DB_SQL_Entity) {
          if ($this->view && $serial && ($arg instanceof DB_SQL_Entity))
            $arg[$serial] = DB_SQL::db()->connection->last_insert_id();

          if ($arg->after_insert()) $arg->after_save();
        }
      }
    }
    return $rc;
  }
///     </body>
///   </method>

///   </protocol>
}
/// </class>


/// <class name="DB.SQL.DeleteStatement" extends="DB.SQL.Statement">
class DB_SQL_DeleteStatement extends DB_SQL_Statement {

  protected $table;
  protected $where = array();

///   <protocol name="creating">

///   <method name="__construct">
///     <args>
///       <arg name="table" type="string" default="null" />
///     </args>
///     <body>
  public function __construct($table = null) {
    if ($table) $this->table = (string) $table;
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="building">

///   <method name="where" returns="DB.SQL.UpdateStatement" varargs="true">
///     <body>
  public function where() {
    foreach (Core_Arrays::flatten($args = func_get_args()) as $arg)
      $this->where[] = (string) $arg;

    return $this;
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="performing">

///   <method name="run" returns="mixed" varargs="true">
///     <body>
  public function run() {
    $binds = count($args = func_get_args()) > 1 ? $args : $args[0];
    $run_callbacks =
        ($binds instanceof DB_SQL_Entity) &&
         $this->view &&
        ($binds instanceof $this->view->prototype);

    $sql = 'DELETE FROM '.($this->table ? $this->table : $this->view->table->name)."\n";

    if (count($where = Core_Arrays::merge($this->view ? $this->view->__get('where') : array(), $this->where)))
      $sql .= 'WHERE ('.Core_Arrays::join_with(') AND (', $where).')';

    if ($run_callbacks ? $binds->before_delete() : true) {
      $rc = DB_SQL::db()->connection->
        prepare($sql)->
        bind($binds)->
        execute();
    }

    if ($run_callbacks && $rc) $binds->after_delete();

    return $rc;
  }
///     </body>
///   </method>

///   </protocol>
}
/// </class>


/// <class name="DB.SQL.Entity">
///   <implements interface="Core.PropertyAccessInterface" />
///   <implements interface="Core.IndexedAccessInterface" />
class DB_SQL_Entity
  implements Core_PropertyAccessInterface,
             Core_IndexedAccessInterface,
             Core_CloneInterface,
             Core_CallInterface  {

  protected $attributes;
  protected $table;

///   <protocol name="creating">

///   <method name="__construct">
///     <args>
///       <arg name="attributes" type="array" default="array()" />
///     </args>
///     <body>
  public function __construct(array $attributes = array()) {
    $this->attributes = new ArrayObject();
    $this->setup_defaults();
    $this->assign($attributes);
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="configuring">

///   <method name="setup_defaults" access="protected">
///     <body>
  protected function setup_defaults() {}
///     </body>
///   </method>

///   </protocol>

	public function set_dbtable($table) {
		$this->table;
	}
	
	public function get_dbtable() {
		return $this->table;
	}

	public function id() {
		$key = $this->key();
		return $this->$key;
	}
	
	public function key() {
		if ($tbl = $this->get_dbtable()) $tbl->serial;
		return 'id';
	}
	
	protected function cache_dir_name() {
			return '_cache';
	}

	public function cache_dir_path($p=false) {
		$path = $this->homedir($p);
		$path .= '/'.$this->cache_dir_name();
		return $path;
	}

	protected function mnemocode() {
		if ($tbl = $this->get_dbtable()) {
			return $tbl->name;
		}
		return strtolower(get_class($this));
	}

	protected function homedir_location($private=false) {
		return ($private?'../':'').'files/' . $this->mnemocode();
	}

	public function homedir($p=false) {
		if ($this->id()==0) return false;
		$private = false;
		$path = false;
		if ($p===true) $private = true;
		if (is_string($p)) $path = $p;

		$dir = $this->homedir_location($private);
		$id = $this->id();
		$did = (int)floor($id/500);
		$s1 = str_pad((string)$id, 4,'0',STR_PAD_LEFT);
		$s2 = str_pad((string)$did,4,'0',STR_PAD_LEFT);
		$dir = "$dir/$s2/$s1";
		if ($path) $dir .= "/$path";
		return $dir;
	}

///   <protocol name="indexing" interface="Core.IndexedAccessInterface">

///   <method name="offsetGet" returns="mixed">
///     <args>
///       <arg name="index" type="string" />
///     </args>
///     <body>
  public function offsetGet($index) {
    if (method_exists($this, $name = "row_get_$index"))
      return $this->$name();
    else
      return $this->attributes[(string) $index];
  }
///     </body>
///   </method>

///   <method name="offsetSet" returns="mixed">
///     <args>
///       <arg name="index" type="string" />
///       <arg name="value" />
///     </args>
///     <body>
  public function offsetSet($index, $value) {
    if (method_exists($this, $name = "row_set_$index"))
      $this->$name($value);
    else
      $this->attributes[(string) $index] = $value;
    return $this;
  }
///     </body>
///   </method>

///   <method name="offsetExists" returns="boolean">
///     <args>
///       <arg name="index" type="string" />
///     </args>
///     <body>
  public function offsetExists($index) {
    return (isset($this->attributes[$index]) ||
            method_exists($this, "row_get_$index"));
  }
///     </body>
///   </method>

///   <method name="offsetUnset" returns="boolean">
///     <args>
///       <arg name="index" type="string" />
///     </args>
///     <body>
  public function offsetUnset($index) { throw new Core_ReadOnlyObjectException($this); }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="calling" interface="Core.CallInterface">

///   <method name="__call" returns="mixed">
///     <args>
///       <arg name="method" type="string" />
///       <arg name="args"   type="array" />
///     </args>
///     <body>
  public function __call($method, $args) {
    $this->__set($method, $args[0]);
    return $this;
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="cloning" interface="Core.CloneInterface">

///   <method name="__clone">
///     <body>
  public function __clone() { $this->attributes = clone $this->attributes; }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="accessing" interface="Core.PropertyAccessInterface">

///   <method name="__get" returns="mixed">
///     <args>
///       <arg name="property" type="string" />
///     </args>
///     <body>
  public function __get($property) {
    switch ($property) {
      case 'attributes':
        return $this->attributes;
      default:
        if (method_exists($this, $method = "get_$property"))
          return $this->$method();
        else
          return isset($this->attributes[$property]) ? $this->attributes[$property] : null;
    }
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
    switch ($property) {
      case 'attributes':
      case 'type':
        throw new Core_ReadOnlyPropertyException($property);
      default:
        if (method_exists($this, $method = "set_$property"))
          $this->$method($value);
        else
          $this[$property] = $value;
        return $this;
    }
  }
///     </body>
///   </method>

///   <method name="__isset" returns="boolean">
///     <args>
///       <arg name="property" type="string" />
///     </args>
///     <body>
  public function __isset($property) {
    switch ($property) {
      case 'attributes':
      case 'type':
        return true;
      default:
        return method_exists($this, $method = "set_$property") ||
               isset($this[$property]);
    }
  }
///     </body>
///   </method>

///   <method name="__unset">
///     <args>
///       <arg name="property" type="string" />
///     </args>
///     <body>
  public function __unset($property) {
    throw $this->__isset($property) ?
      new Core_UndestroyablePropertyException($property) :
      new Core_MissingPropertyException($property);
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="handling">

///   <method name="before_delete" returns="boolean" stereotype="handler">
///     <body>
  public function before_delete() { return true; }
///     </body>
///   </method>

///   <method name="after_delete" returns="boolean" stereotype="handler">
///     <body>
  public function after_delete()  { return true; }
///     </body>
///   </method>

///   <method name="before_insert" returns="boolean" stereotype="handler">
///     <body>
  public function before_insert() { return true; }
///     </body>
///   </method>

///   <method name="after_insert" returns="boolean" stereotype="handler">
///     <body>
  public function after_insert() { return true; }
///     </body>
///   </method>

///   <method name="before_update" returns="boolean" stereotype="handler">
///     <body>
  public function before_update() { return true; }
///     </body>
///   </method>

///   <method name="after_update" returns="boolean" stereotype="handler">
///     <body>
  public function after_update() { return true; }
///     </body>
///   </method>

///   <method name="after_save" returns="boolean" stereotype="handler">
///     <body>
  public function after_save() { return true; }
///     </body>
///   </method>

///   <method name="before_save" returns="boolean" stereotype="handler">
///     <body>
  public function before_save() { return true; }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="supporting">

///   <method name="assign" access="protected">
///     <body>
  protected function assign(array $attributes) {
    foreach ($attributes as $k => $v) $this->__set($k, $v);
  }
///     </body>
///   </method>

///   <method name="row_set_type" returns="string">
///     <body>
  protected function row_set_type($value) { return Core_Types::real_class_name_for($this); }
///     </body>
///   </method>

///   <method name="row_get_type" returns="string">
///     <body>
    protected function row_get_type() { return Core_Types::real_class_name_for($this); }
///     </body>
///   </method>

///   </protocol>
}
/// </class>


/// <class name="DB.SQL.Pager" extends="Data.Pagination.Pager">
///   <depends supplier="DB.SQL.AbstractSelectStatement" stereotype="uses" />
class DB_SQL_Pager extends Data_Pagination_Pager {

///   <protocol name="performing">

///   <method name="apply_limits" returns="DB.SQL.AbstractSelectStatement">
///     <args>
///       <arg name="sql" type="DB.SQL.AbstractSelectStatement" />
///     </args>
///     <body>
  public function apply_limits(DB_SQL_AbstractSelectStatement $sql) {
    return $sql->
      offset($this->current->offset)->
      limit($this->items_per_page);
  }
///     </body>
///   </method>

///   </protocol>
}
/// </class>

/// </module>
