<?php
/// <module name="DB.Adapter" version="0.2.0" maintainer="timokhin@techart.ru">
///   <brief>Модуль определяет набор интерфейсов для адаптеров БД, также подгружает конкретный адаптер</brief>
/// <class name="DB.Adapter" stereotype="module">
///   <implements interface="Core.ModuleInterface" />
class DB_Adapter implements Core_ConfigurableModuleInterface {

///   <constants>
  const VERSION = '0.2.0';
///   </constants>

  static protected $options = array(
    'adapters' => array(
      'mysql' => 'MySQL', 'pgsql' => 'PostgreSQL', 'mssql' => 'MSSQL')
      );
    
  
  static public function initialize(array $options = array()) {
    return self::options($options);
  }
  
  static public function options(array $options = array()) {
    return self::$options = array_merge(self::$options, $options);
  }
  
  static public function option($name, $value = null) {
    if (is_null($value)) return self::$options[$name];
    return self::$options[$name] = $value;
  }

///   <protocol name="building">

///   <method name="instantiate" returns="DB.Adapter.ConnectionInterface">
///     <brief>Возвращает объект адаптера соответствующего DSN</brief>
///     <args>
///       <arg name="dsn" type="DB.DSN" brief="параметра доступа к БД" />
///     </args>
///     <body>
  static public function instantiate(DB_DSN $dsn) {
    $adapters = self::option('adapters');
    if (isset($adapters[$dsn->type])) {
      $module = $adapters[$dsn->type];
      $module = Core_Strings::contains('.', $module) ? $module : 'DB.Adapter.' . $module;
      Core::load($module);
      return Core::make($module . '.Connection', $dsn);
    } else
    throw new DB_Exception("Missing adapter for type $module");
  }
///     </body>
///   </method>

///   </protocol>
}
/// </class>


/// <interface name="DB.Adapter.ConnectionInterface">
///   <brief>Интерфейс для класса соединения с БД</brief>
interface DB_Adapter_ConnectionInterface {

///   <protocol name="creating">

///   <method name="__construct">
///     <brief>Конструктор</brief>
///     <args>
///       <arg name="dsn" type="DB.DSN" brief="параметра доступа к БД" />
///     </args>
///     <body>
  public function __construct(DB_DSN $dsn);
///     </body>
///   </method>

///   </protocol>

///   <protocol name="processing">

///   <method name="cast_parameter" returns="mixed">
///     <brief>Преобразует значение в пригодный вид для вставки в sql запрос</brief>
///     <args>
///       <arg name="value" brief="значение" />
///     </args>
///     <body>
  public function cast_parameter($value);
///     </body>
///   </method>

///   <method name="is_castable_parameter" returns="boolean">
///     <brief>Проверяет требуется ли преобразовывать значение</brief>
///     <args>
///       <arg name="value" brief="значение" />
///     </args>
///     <body>
  public function is_castable_parameter($value);
///     </body>
///   </method>

///   <method name="set_attribute" returns="boolean">
///     <brief>Устанавливает атрибут</brief>
///     <args>
///       <arg name="id" brief="идентификатор"  />
///       <arg name="value" brief="значение" />
///     </args>
///     <body>
  public function set_attribute($id, $value);
///     </body>
///   </method>

///   <method name="get_attribute" returns="mixed">
///     <brief>Возвращает атрибут</brief>
///     <args>
///       <arg name="id" type="int" brief="идентификатор" />
///     </args>
///     <body>
  public function get_attribute($id);
///     </body>
///   </method>

///   <method name="prepare" returns="DB.Adapter.CursorInterface">
///     <brief>Подготавливает SQL-запрос к выполнению</brief>
///     <args>
///       <arg name="sql" type="string" brief="sql-запрос" />
///     </args>
///     <body>
  public function prepare($sql);
///     </body>
///   </method>

///   <method name="transaction">
///     <brief>Открывает транзакцию</brief>
///     <body>
  public function transaction();
///     </body>
///   </method>

///   <method name="commit">
///     <brief>Фиксирует транзакцию</brief>
///     <body>
  public function commit();
///     </body>
///   </method>

///   <method name="rollback">
///     <brief>Откатывает транзакцию</brief>
///     <body>
  public function rollback();
///     </body>
///   </method>

///   <method name="last_insert_id" returns="int">
///     <brief>Возвращает последний вставленный идентификатор</brief>
///     <body>
  public function last_insert_id();
///     </body>
///   </method>

///   <method name="quote">
///     <brief>Квотит параметр</brief>
///     <args>
///       <arg name="value" brief="параметр" />
///     </args>
///     <body>
  public function quote($value);
///     </body>
///   </method>

///   <method name="after_connect">
///     <brief>Вызывается в DB.Connection после соединения</brief>
///     <body>
  public function after_connect();
///     </body>
///   </method>

///   <method name="explain">
///     <brief>Выполняет EXPLAIN для анализа запроса</brief>
///     <args>
///       <arg name="sql" type="string" brief="sql-запрос" />
///       <arg name="binds" type="array" brief="массив параметров" />
///     </args>
///     <body>
  public function explain($sql, $binds);
///     </body>
///   </method>

  public function get_schema();

  public function escape_identifier($str);

///   </protocol>
}
/// </interface>

/// <interface name="DB.Adapter.CursorInterface">
///   <brief>Интерфейс курсора БД</brief>
interface DB_Adapter_CursorInterface {

///   <protocol name="processing">

///   <method name="cast_column" returns="mixed">
///     <brief>Преобразует значение полученное из БД в нужный формат, для работы с ним в php</brief>
///     <args>
///       <arg name="metadata" type="DB.ColumnMeta" brief="мета-данный колонки" />
///       <arg name="value" brief="значение" />
///     </args>
///     <body>
  public function cast_column(DB_ColumnMeta $metadata, $value);
///     </body>
///   </method>

///   <method name="fetch" returns="mixed">
///     <brief>Возвращает очередную строку результата</brief>
///     <body>
  public function fetch();
///     </body>
///   </method>

///   <method name="close">
///     <brief>Закрывает курсор</brief>
///     <body>
  public function close();
///     </body>
///   </method>

///   <method name="execute">
///     <brief>Выполняет запрос</brief>
///     <args>
///       <arg name="binds" type="array" brief="массив параметров" />
///     </args>
///     <body>
  public function execute(array $binds);
///     </body>
///   </method>

///   <method name="get_num_of_rows" returns="int">
///     <brief>Возвращает количество строк в результате</brief>
///     <body>
  public function get_num_of_rows();
///     </body>
///   </method>

///   <method name="get_num_of_columns" returns="int">
///     <brief>Возвращает количетсво колонок</brief>
///     <body>
  public function get_num_of_columns();
///     </body>
///   </method>

///   <method name="get_row_metadata" returns="DB.ColumnMeta">
///     <brief>Возвращает мета данные строки результата</brief>
///     <body>
  public function get_row_metadata();
///     </body>
///   </method>

///   </protocol>
}
/// </interface>

interface DB_Adapter_SchemaInterface {
  public function column_definition($column);
  
  public function index_definition($table, $index);
  
  public function add_column($column);
  
  public function remove_column($column);
  
  public function update_column($column);
  
  public function remove_index($table, $index);
  
  public function add_index($table, $index);
  
  public function alter_table($table, $actions);
  
  public function create_table($table, $defs);
  
  public function map_column($column);

  public function inspect($info_mapper, $table_name, $dsn);
}

/// </module>
