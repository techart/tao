<?php
/// <module name="DB" version="0.2.3" maintainer="timokhin@techart.ru">
///   <brief>Модуль предоставляет набор классов для работы с БД</brief>
Core::load('Object', 'DB.Adapter');

/// <class name="DB" stereotype="module">
///   <implements interface="Core.ModuleInterface" />
///   <depends supplier="DB.Connection" stereotype="creates" />
class DB implements Core_ConfigurableModuleInterface {

///   <constants>
  const VERSION = '0.2.3';
  const PLACEHOLDER_REGEXP = ':([a-zA-Z_][a-zA-Z_0-9]*)';
///   </constants>


  static protected $options = array(
    'error_handling_mode' => PDO::ERRMODE_EXCEPTION,
    'collection_class'    => 'ArrayObject',
    'charset'             => 'UTF8',
    'row_class_field'     => '__class' );

///   <protocol name="creating">

///   <method name="initialize" scope="class">
///     <brief>Инициализация</brief>
///     <args>
///       <arg name="options" type="array" default="array()" brief="массив опций" />
///     </args>
///     <body>
  static public function initialize(array $options = array()) { self::options($options); }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="building">

///   <method name="Connection" returns="DB.Connection" stereotype="factory">
///     <brief>Фабричный метод, возвращает объект класса DB.Connection</brief>
///     <args>
///       <arg name="dsn" type="string" brief="строка DSN, определяющая параметры доступа к базе" />
///     </args>
///     <body>
  static public function Connection($dsn) { return new DB_Connection($dsn); }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="configuring">

///   <method name="options" returns="mixed" scope="class">
///     <brief>Устанавливает опции модуля</brief>
///     <args>
///       <arg name="options" type="array" default="array()" brief="массив опций" />
///     </args>
///     <body>
  static public function options(array $options = array()) {
    if (count($options)) Core_Arrays::update(self::$options, $options);
    return self::$options;
  }
///     </body>
///   </method>

///   <method name="option" returns="mixed">
///     <brief>Устанавливает/возвращает опцию модуля</brief>
///     <args>
///       <arg name="name" type="string" brief="имя опции" />
///       <arg name="value" default="null" brief="значение" />
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


/// <class name="DB.Exception" extends="Core.Exception" stereotype="exception">
///   <brief>Класс исключения</brief>
class DB_Exception extends Core_Exception {}
/// </class>


/// <class name="DB.ConnectionException" extends="DB.Exception" stereotype="exception">
///   <brief>Класс исключения для неудачного подключения</brief>
class DB_ConnectionException extends DB_Exception {}
/// </class>


/// <class name="DB.CursorException" extends="DB.Exception" stereotype="exception">
///   <brief>Класс исключения для курсора</brief>
class DB_CursorException extends DB_Exception {
  protected $sql;

  public function __construct($message, $sql = '') {
    $this->sql = $sql;
    $m = empty($this->sql) ? $message : $message . ' in query : ' . $sql;
    parent::__construct($m);
  }

}
/// </class>


/// <class name="DB.DSN">
///     <brief>Класс представляющий собой dsn строку подключения к БД ввиде объекта</brief>
///     <details>
///       Строка имеет вид: type://username:password@host:port/database/scheme.
///       Например mysql://app:app@localhost/test
///       В этой строке обязательными являются type, host и database
///     </details>
///   <implements interface="Core.PropertyAccessInterface" />
///   <implements interface="Core.StringifyInterface" />
///   <depends supplier="DB.ConnectionException" stereotype="throws" />
class DB_DSN implements Core_PropertyAccessInterface, Core_StringifyInterface {
  // UNUSED:
  const FORMAT = '{^([^:/]+)://(?:(?:([^:@]+)(?::([^@]+))?@)?([^:/]+)?(?::(\d+))?/)?([^/]+)(/[^/]+)?$}';

  protected $type     = '';
  protected $user     = '';
  protected $password = '';
  protected $host     = '';
  protected $port     = '';
  protected $database = '';
  protected $scheme   = '';
  protected $parms    = array();

///   <protocol name="creating">

///   <method name="__construct" access="protected">
///     <brief>Конструктор</brief>
///     <args>
///       <arg name="parms" type="array" brief="Массив элементов DSN" />
///     </args>
///     <body>
  protected function __construct(array $parms) {
    foreach ($parms as $k => $v)
      if (isset($this->$k)) $this->$k = $v;
  }
///     </body>
///   </method>

///   <method name="parse" returns="DB.DSN" scope="class">
///     <brief>Парсит строку подключения</brief>
///     <args>
///       <arg name="string" type="string" brief="Строка DSN" />
///     </args>
///     <body>
  static public function parse($string) {
    $p = parse_url($string);
    $scheme = '';
    if (isset($p['path'])) {
      $parts = explode('/', trim($p['path'], '/'));
      if (isset($parts[1])) {
        $scheme = $parts[1];
        $p['path'] = $parts[0];
      }
    }
    if ($p) {
      return new DB_DSN(array(
        'type'     => $p['scheme'],
        'user'     => $p['user'],
        'password' => $p['pass'],
        'host'     => $p['host'],
        'port'     => $p['port'],
        'database' => trim($p['path'], '/'),
        'scheme'   => $scheme,
        'parms'    => $p['query']));
    }
    else
      throw new DB_ConnectionException("Bad DSN: $string");
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="converting">

///   <method name="as_pdo_string" returns="string" access="protected">
///     <brief>Возвращает строку ввиде пригодном для PDO</brief>
///     <body>
  protected function as_pdo_string() {
    return Core_Strings::format("%s:host=%s;dbname=%s", $this->type, $this->host, $this->database).
           ($this->port ? ";port={$this->port}" : '').($this->scheme ? ";scheme={$this->scheme}" : '');
  }
///     </body>
///   </method>

///   <method name="as_string" returns="string">
///     <brief>Возвращает строку подключения</brief>
///     <body>
  public function as_string() {
    return
      ("$this->type://").
      ($this->user ? $this->user.($this->password ? ":$this->password" : '').'@' : '').
      ($this->host ? $this->host.($this->port ?     ":$this->port"     : '').'/' : '').
      ($this->database).(count($this->parms) > 0 ? '?'.http_build_query($this->parms) : '');
  }
///     </body>
///   </method>

///   <method name="__toString" returns="string">
///     <brief>Возвращает строку подключения</brief>
///     <body>
  public function __toString() { return $this->as_string(); }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="accessing" interface="Core.PropertyAccessInterface">

///   <method name="__get" returns="mixed">
///     <brief>Доступ на чтение к свойствам объекта</brief>
///     <details>
///       <dl>
///         <dt>type</dt><dd>тип БД, mysql pgsql</dd>
///         <dt>user</dt><dd>имя пользователя</dd>
///         <dt>password</dt><dd>пароль</dd>
///         <dt>host</dt><dd>хост</dd>
///         <dt>port</dt><dd>порт</dd>
///         <dt>database</dt>база данных<dd></dd>
///         <dt>scheme</dt><dd>схема</dd>
///       </dl>
///     </details>
///     <args>
///       <arg name="property" type="string" brief="имя свойства" />
///     </args>
///     <body>
  public function __get($property) {
    switch ($property) {
      case 'type':
      case 'user':
      case 'password':
      case 'host':
      case 'port':
      case 'database':
      case 'scheme':
      case 'parms':
        return $this->$property;
      case 'pdo_string':
        return $this->as_pdo_string();
      default:
        throw new Core_MissingPropertyException($property);
    }
  }
///     </body>
///   </method>

///   <method name="__set" returns="mixed">
///     <brief>Доступ на запись к свойствам объекта</brief>
///     <details>
///       Установить можно все свойства, кроме pdo_string
///     </details>
///     <args>
///       <arg name="property" type="string" brief="Имя свойства" />
///       <arg name="value"    type="string" brief="Значение свойства" />
///     </args>
///     <body>
  public function __set($property, $value) {
    switch ($property) {
      case 'type':
      case 'user':
      case 'password':
      case 'host':
      case 'port':
      case 'database':
      case 'scheme':
        $this->$property = (string) $value;
        return $this;
      case 'parms':
        $this->$property = (array) $value;
        return $this;
      case 'pdo_string':
        throw new Core_ReadOnlyPropertyException($property);
      default:
        throw new Core_MissingPropertyException($property);
    }
  }
///     </body>
///   </method>

///   <method name="__isset" returns="boolean">
///     <brief>Проверяет установленно ли свойство</brief>
///     <args>
///       <arg name="property" type="string" brief="Имя свойства" />
///     </args>
///     <body>
  public function __isset($property) {
    switch ($property) {
      case 'type':
      case 'user':
      case 'password':
      case 'host':
      case 'port':
      case 'database':
      case 'pdo_string':
      case 'scheme':
      case 'parms':
        return true;
      default:
        return false;
    }
  }
///     </body>
///   </method>

///   <method name="__unset">
///     <brief>Очищает свойство</brief>
///     <args>
///       <arg name="property" type="string" brief="Имя свойства" />
///     </args>
///     <body>
  public function __unset($property) {
    switch ($property) {
      case 'type':
      case 'user':
      case 'password':
      case 'host':
      case 'port':
      case 'database':
      case 'pdo_string':
      case 'scheme':
      case 'parms':
        throw new Core_UndestroyablePropertyException($property);
      default:
        throw new Core_MissingPropertyException($property);
    }
  }
///     </body>
///   </method>

///   </protocol>
}
/// </class>

/// <interface name="DB.EventListener">
///   <brief>Интерфейс для слушателя событий БД</brief>
interface DB_EventListener {}
/// </interface>

/// <interface name="DB.QueryExecutionListener" extends="DB.EventListener">
///   <brief>Интерфейст для слушателя выполняемых команд</brief>
interface DB_QueryExecutionListener extends DB_EventListener {
///   <protocol name="observing">

///   <method name="on_execute">
///     <brief>Вызывается при выполнении команды</brief>
///     <args>
///       <arg name="cursor" type="DB.Cursor" brief="курсор" />
///     </args>
///     <body>
  public function on_execute(DB_Cursor $cursor);
///     </body>
///   </method>

///   </protocol>
}
/// </interface>

/// <class name="DB.Connection">
///   <brief>Класс подключения к БД</brief>
///   <implements interface="Core.PropertyAccessInterface" />
///   <depends supplier="DB.ConnectionException" stereotype="throws" />
///   <depends supplier="DB.Cursor" stereotype="creates" />
class DB_Connection implements Core_PropertyAccessInterface {
  protected $dsn;
  protected $adapter;

  protected $listeners;
  protected $test = false;

///   <protocol name="creating">

///   <method name="__construct">
///     <brief>Конструктор</brief>
///     <args>
///       <arg name="dsn" type="string" brief="Строка DSN" />
///     </args>
///     <body>
  public function __construct($dsn) {
    $this->dsn = DB_DSN::parse($dsn);
    $this->listeners = Object::Listener('DB.EventListener');
  }
///     </body>
///   </method>

///   <method name="disconnect">
///     <brief>Отсоединяется</brief>
///     <body>
  public function disconnect() {
    $this->adapter = null;
    return $this;
  }
///     </body>
///   </method>

///   <method name="connect">
///     <brief>Коннектиться к БД</brief>
///     <body>
  public function connect() {
    if (empty($this->adapter)) {
      $this->adapter = DB_Adapter::instantiate($this->dsn);
      $this->adapter->set_attribute(PDO::ATTR_ERRMODE, DB::option('error_handling_mode'));
      $this->adapter->after_connect();
    }
    return $this;
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="configuring">

///   <method name="listener" returns="DB.Connection">
///     <brief>Регистрирует слушателя событий</brief>
///     <args>
///       <arg name="listener" type="DB.EventListener" brief="слушатель" />
///     </args>
///     <body>
  public function listener(DB_EventListener $listener) {
    $this->listeners->append($listener);
    return $this;
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="performing">

///   <method name="transaction" returns="DB.Connection">
///     <brief>Создает транзакцию</brief>
///     <body>
  public function transaction() {
    $this->__get('adapter')->transaction();
    return $this;
  }
///     </body>
///   </method>

///   <method name="commit" returns="DB.Connection">
///     <brief>Коммитит изменения транзакции</brief>
///     <body>
  public function commit() {
    $this->__get('adapter')->commit();
    return $this;
  }
///     </body>
///   </method>

///   <method name="rollback" returns="DB.Connection">
///     <brief>Откатывает изменения транзакции</brief>
///     <body>
  public function rollback() {
    $this->__get('adapter')->rollback();
    return $this;
  }
///     </body>
///   </method>

  public function get_schema() {
    return $this->adapter->get_schema();
  }

///   <method name="prepare" returns="DB.Cursor">
///     <brief>Подготавливает sql запрос</brief>
///     <args>
///       <arg name="sql" brief="SQL-запрос" />
///     </args>
///     <body>
  public function prepare($sql) { return new DB_Cursor($this, $sql); }
///     </body>
///   </method>

///   <method name="execute" returns="int">
///     <brief>Выполняет sql-запрос</brief>
///     <args>
///       <arg name="sql" brief="SQL-запрос" />
///       <arg name="parms" default="array()" brief="Параметры SQL-запроса" />
///     </args>
///     <body>
  public function execute($sql, $parms = array()) {
    return $this->prepare($sql)->bind($parms)->execute()->num_of_rows;
  }
///     </body>
///   </method>

///   <method name="query" returns="DB.Cursor">
///   <brief>Выполняет SQL-запрос, возвращая курсор для получения результатов</brief>
///     <args>
///       <arg name="sql" brief="SQL-запрос" />
///       <arg name="parms" default="array()" brief="Параметры SQL-запроса" />
///     </args>
///     <body>
  public function query($sql, $parms = array()) {
    return $this->prepare($sql)->bind($parms)->execute();
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="quering">

///   <method name="last_insert_id" returns="int">
///     <brief>Возвращает номер последнего вставленного идентификатора</brief>
///     <body>
  public function last_insert_id() { return $this->__get('adapter')->last_insert_id(); }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="computing">

///   <method name="quote" returns="string">
///     <brief>Квотит параметр</brief>
///     <args>
///       <arg name="value" brief="Параметр" />
///     </args>
///     <body>
  public function quote($value) { return $this->__get('adapter')->quote((string) $value); }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="accessing" interface="Core.PropertyAccessInterface">

///   <method name="__get" returns="mixed">
///     <brief>Доступ на чтение к свойствам объекта</brief>
///     <details>
///       <dl>
///         <dt>adapter</dt><dd>Адаптер</dd>
///         <dt>dsn</dt><dd>DB.DSN</dd>
///         <dt>listeners</dt><dd>Делигатор слушателей</dd>
///       </dl>
///     </details>
///     <args>
///       <arg name="property" type="string" brief="Имя свойства" />
///     </args>
///     <body>
  public function __get($property) {
    switch ($property) {
      case 'adapter':
        $this->connect();
        return $this->$property;
      case 'dsn':
      case 'listeners':
        return $this->$property;
      default:
        throw new Core_MissingPropertyException($property);
    }
  }
///     </body>
///   </method>

///   <method name="__set" returns="mixed">
///     <brief>Доступ на запись к свойствам объекта</brief>
///     <details>
///       Выбрасывает исключение, доступ только для чтения
///     </details>
///     <args>
///       <arg name="property" type="string" brief="Имя свойства" />
///       <arg name="value"    type="string" brief="Значение свойства" />
///     </args>
///     <body>
  public function __set($property, $value) {throw new Core_ReadOnlyObjectException($this); }
///     </body>
///   </method>

///   <method name="__isset" returns="boolean">
///     <brief>Проверяет установленно ли свойство</brief>
///     <args>
///       <arg name="property" type="string" brief="Имя свойства" />
///     </args>
///     <body>
  public function __isset($property) {
    switch ($property) {
      case 'adapter':
      case 'dsn':
      case 'listeners':
        return isset($this->$property);
      default:
        return false;
    }
  }
///     </body>
///   </method>

///   <method name="__unset">
///     <brief>Очищает свойтсво</brief>
///     <args>
///       <arg name="property" type="string" brief="Имя свойства" />
///     </args>
///     <body>
  public function __unset($property) { throw new Core_ReadOnlyObjectException($this); }
///     </body>
///   </method>

///   </protocol>
}
/// </class>


/// <class name="DB.CursorIterator">
///   <brief>Итератор по записям курсора</brief>
///   <implements interface="Iterator" />
class DB_CursorIterator implements Iterator {
  protected $cursor;

///   <protocol name="creating">

///   <method name="__construct">
///     <brief>Конструктор</brief>
///     <args>
///       <arg name="cursor" type="DB.Cursor" brief="Курсор" />
///     </args>
///     <body>
  public function __construct(DB_Cursor $cursor) {
    $this->cursor = $cursor;
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="iterating" interface="Iterator">

///   <method name="current" returns="mixed">
///     <brief>Возвращает текущий элемент - строку результата</brief>
///     <body>
  public function current() { return $this->cursor->row; }
///     </body>
///   </method>

///   <method name="key" returns="mixed">
///     <brief>Возвращает ключ текущего элемента - номер строки</brief>
///     <body>
  public function key() { return $this->cursor->num_of_fetched - 1; }
///     </body>
///   </method>

///   <method name="next">
///     <brief>Вовзращает следующий элемент - строку результата</brief>
///     <body>
  public function next() { $this->cursor->fetch(); }
///     </body>
///   </method>

///   <method name="rewind">
///     <brief>Сбрасывает итератор в начало</brief>
///     <body>
  public function rewind() {
    $this->cursor->close();
    $this->cursor->execute();
    $this->cursor->fetch();
  }
///     </body>
///   </method>

///   <method name="valid" returns="boolean">
///     <brief>Проверяет является ли текущий элемент валидным</brief>
///     <body>
  public function valid() { return $this->cursor->row ? true : false; }
///     </body>
///   </method>

/// </protocol>
}
/// </class>
/// <composition>
///   <source class="DB.Connection" role="connection" multiplicity="1" />
///   <target class="DB.DSN" role="dsn" multiplicity="1" />
/// </composition>
/// <composition>
///   <source class="DB.Connection" role="connection" multiplicity="1" />
///   <target class="DB.Driver.AbstractDriver" role="driver" multiplicity="1" />
/// </composition>


/// <class name="DB.ColumnMeta" extends="stdClass">
///   <brief>Мета-данные колонки БД</brief>
class DB_ColumnMeta extends stdClass  {
///   <protocol name="creating">

///   <method name="__construct">
///     <brief>Конструктор</brief>
///     <args>
///       <arg name="name" type="string" brief="имя колонки" />
///       <arg name="type" type="string" brief="тип колонки" />
///       <arg name="length" type="int" brief="длина колонки" />
///       <arg name="precision" type="int" brief="точность" />
///     </args>
///     <body>
  public function __construct($name, $type, $length, $precision) {
    $this->name      = strtolower($name);
    $this->type      = strtolower($type);
    $this->length    = (int) $length;
    $this->precision = (int) $precision;
  }
///     </body>
///   </method>

///   </protocol>
}
/// </class>


/// <class name="DB.Cursor">
///   <brief>Курсор</brief>
///   <implements interface="Core.PropertyAccessInterface" />
///   <implements interface="IteratorAggregate" />
///   <depends supplier="DB.CursorIterator"  stereotype="creates" />
///   <depends supplier="DB.CursorException" stereotype="throws" />
///   <depends supplier="DB.RowMeta"         stereotype="creates" />
class DB_Cursor
  implements Core_PropertyAccessInterface,
             IteratorAggregate {

  protected $connection;

  protected $adapter;

  protected $metadata;
  protected $row;
  protected $row_no = 0;
  protected $sql    = '';
  protected $binds  = array();
  protected $prototype;
  protected $is_successful = true;
  protected $execution_time;
  protected $ignore_type = false;

///   <protocol name="creating">

///   <method name="__construct">
///     <brief>Конструктор</brief>
///     <args>
///       <arg name="connection" type="DB.Connection" brief="Объект подключения к базе данных" />
///       <arg name="sql" brief="SQL-запрос" />
///     </args>
///     <body>
  public function __construct(DB_Connection $connection, $sql) {
    $this->connection  = $connection;
    $this->sql         = (string) $sql;
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="configuring">

///   <method name="as_object" returns="DB.Cursor">
///     <brief>Устанавливает прототип для возвращаемого результата</brief>
///     <args>
///       <arg name="prototype" brief="Прототип объекта, используемого для хранения объектов записей, или имя класса такого объекта" />
///       <arg name="ignore_type" type="boolean" default="false" brief="игнорировать ли колонку type" />
///     </args>
///     <body>
  public function as_object($prototype, $ignore_type = false) {
    $this->ignore_type = (boolean) $ignore_type;
    if (Core_Types::is_string($prototype))
      $this->prototype = Core_Types::reflection_for($prototype)->newInstance();
    elseif (Core_Types::is_object($prototype))
      $this->prototype = $prototype;
    else
      throw new Core_BadArgumentTypeException('prototype');

    return $this;
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="binding">

///   <method name="bind" returns="DB.Cursor" varargs="true">
///     <brief>Связывает параметры в sql-запросе с переданными в метод</brief>
///     <body>
  public function bind() {
    $values  = count($args = func_get_args()) > 1 ? $args : $args[0];
    $adapter = $this->connection->adapter;
    $this->binds = array();
    if ($match = Core_Regexps::match_all('{(?:'.DB::PLACEHOLDER_REGEXP.')}', $this->sql)) {
      if ($adapter->is_castable_parameter($values)) {
        $this->binds[] = $adapter->cast_parameter($values);
      } else {
        foreach ($match[1] as $no => $name) {
          switch (true) {
            case is_array($values) || $values instanceof ArrayAccess:
              $this->binds[] = $adapter->cast_parameter(isset($values[$name]) ? $values[$name] : $values[$no]);
              break;
            case is_object($values):
              $this->binds[] = $adapter->cast_parameter($values->$name);
              break;
            default:
              $this->binds[] = $adapter->cast_parameter($values);
              break;
          }
        }
      }
    }
    return $this;
  }
///     </body>
///   </method>

///   <method name="has_binds" returns="boolean" access="protected">
///     <brief>Проверяет есть связанные параметры</brief>
///     <body>
  protected function has_binds() { return count($this->binds) > 0; }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="performing">

///   <method name="explain" returns="DB.Cursor">
///     <brief>Выполняет EXPLAIN для анализа запроса</brief>
///     <body>
  public function explain() {
    if (Core_Strings::starts_with(Core_Strings::trim($this->sql), 'SELECT'))
      return $this->connection->adapter->explain(
        Core_Regexps::replace('{'.DB::PLACEHOLDER_REGEXP.'}', '?', $this->sql),
        $this->binds);
    else
      return null;
  }
///     </body>
///   </method>

  public function pure_sql() {
    $sql = $this->sql;
    if ($match = Core_Regexps::match_all('{(?:'.DB::PLACEHOLDER_REGEXP.')}', $sql)) {
      foreach ($match[1] as $no => $name) {
        $sql = str_replace(":$name", "'" . $this->binds[$no] . "'", $sql);
      }
    }
    return $sql;
  }
  

///   <method name="execute" returns="DB.Cursor">
///     <brief>Выполняет запрос</brief>
///     <body>
  public function execute() {
    $this->close();
    $this->adapter = $this->connection->adapter->prepare(Core_Regexps::replace('{'.DB::PLACEHOLDER_REGEXP.'}', '?', $this->sql));

    $this->execution_time = 0;
    $time = microtime();

    try {
      $this->is_successful = $this->adapter->execute($this->binds);
    } catch (DB_CursorException $e) {
      throw new DB_CursorException($e->getMessage(), $this->pure_sql());
    }
    if ($this->is_successful) {
      $this->execution_time = microtime() - $time;
      $this->metadata = $this->adapter->get_row_metadata();
    }
    $this->connection->listeners->on_execute($this);

    return $this;
  }
///     </body>
///   </method>

///   <method name="fetch" returns="mixed">
///     <brief>Возвращает очередную строку результата</brief>
///     <body>
  public function fetch() {
    if ($this->adapter) {
      if ($this->row = $this->adapter->fetch()) {
        $this->row = $this->make_row_instance($this->row);
        $this->row_no++;
      }
      return $this->row ? $this->row : null;
    } else
      throw new DB_CursorException('Unable to fetch before query execution');
  }
///     </body>
///   </method>

///   <method name="fetch_all" returns="mixed">
///     <brief>Возвращает все строки результата</brief>
///     <args>
///       <arg name="prototype" default="null" brief="Прототип объекта, используемого для хранения объектов записей, или имя класса такого объекта" />
///     </args>
///     <body>
  public function fetch_all($prototype = null, $key = null) {
    switch (true) {
      case $prototype == null:
        $result = Core::make(DB::option('collection_class'));
        break;
      case Core_Types::is_string($prototype):
        $result = Core_Types::reflection_for($prototype)->newInstance();
        break;
      case Core_Types::is_array($prototype):
        $result = $prototype;
        break;
      case $prototype instanceof ArrayAccess:
        $result = clone($prototype);
        break;
      default:
        throw new Core_InvalidArgumentTypeException('prototype', $prototype);
    }
    while ($row = $this->fetch())
      if (is_null($key))
        $result[] = $row;
      else
        $result[$row->$key] = $row;
    return $result;
  }
///     </body>
///   </method>

///   <method name="fetch_value" returns="mixed">
///     <body>
  public function fetch_value() {
    if ($this->adapter && $row = $this->adapter->fetch()) {
      $this->close();
      return current($row);
    } else
      return null;
  }
///     </body>
///   </method>

///   <method  name="fetch_all_as" returns="mixed">
///     <brief>Возвращает все строки результата, используя прототип</brief>
///     <args>
///       <arg name="prototype" brief="Прототип объекта, используемого для хранения объектов записей, или имя класса такого объекта" />
///     </args>
///     <body>
  public function fetch_all_as($prototype) { return $this->fetch_all($prototype); }
///     </body>
///   </method>

///   <method name="close" returns="boolean">
///     <brief>Закрывает курсор</brief>
///     <body>
  public function close() { if ($this->adapter) return $this->adapter->close(); }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="accessing" interface="Core.PropertyAccessInterface">

///   <method name="__get" returns="mixed">
///     <brief>Доступ на чтение к свойствам объекта</brief>
///     <details>
///       <dl>
///         <dt>metadata</dt><dd>мета-данные</dd>
///         <dt>is_successful</dt><dd>флаг успешного выполнения запроса</dd>
///         <dt>sql</dt><dd>sql-команда</dd>
///         <dt>row</dt><dd>строка результата</dd>
///         <dt>binds</dt><dd>массив связанных параметров</dd>
///         <dt>execution_time</dt><dd>время выполнения запроса</dd>
///         <dt>num_of_rows</dt><dd>количество строк в результате</dd>
///         <dt>num_of_fetched</dt><dd>количество выбранных из результата строк</dd>
///         <dt>num_of_columns</dt><dd> количество колонок</dd>
///       </dl>
///     </details>
///     <args>
///       <arg name="property" type="string" brief="Имя свойства" />
///     </args>
///     <body>
  public function __get($property) {
    switch ($property) {
      case 'metadata':
      case 'is_successful':
      case 'sql':
      case 'row':
      case 'binds':
      case 'execution_time':
        return $this->$property;
      case 'num_of_rows':
        return $this->adapter ? $this->adapter->get_num_of_rows() : 0;
      case 'num_of_fetched':
        return $this->row_no;
      case 'num_of_columns':
        return $this->adapter ? $this->adapter->get_num_of_columns() : 0;
      case 'connection':
        return $this->connection;
      default:
        throw new Core_MissingPropertyException($property);
      }
  }
///     </body>
///   </method>

///   <method name="__set" returns="mixed">
///     <brief>Доступ на запись к свойствам объекта</brief>
///     <details>
///       Выбрасывает исключение, доступ только для чтения
///     </details>
///     <args>
///       <arg name="property" type="string" brief="Имя свойства" />
///       <arg name="value"    type="string" brief="Значение свойства" />
///     </args>
///     <body>
  public function __set($property, $value) { throw new Core_ReadOnlyObjectException($this); }
///     </body>
///   </method>

///   <method name="__isset" returns="boolean">
///     <brief>Проверяет установленно ли свойство объекта</brief>
///     <args>
///       <arg name="property" type="string" brief="Имя свойства" />
///     </args>
///     <body>
  public function __isset($property) {
    switch ($property) {
      case 'metadata':
      case 'is_successful':
      case 'sql':
      case 'row':
      case 'connection':
      case 'binds':
      case 'execution_time':
        return isset($this->$property);
      case 'num_of_rows':
      case 'num_of_fetched':
      case 'num_of_columns':
        return true;
      default:
        return false;
    }
  }
///     </body>
///   </method>

///   <method name="__unset">
///     <brief>Очищает свойство объекта</brief>
///     <args>
///       <arg name="property" type="string" brief="Имя свойства" />
///     </args>
///     <body>
  public function __unset($property) { throw new Core_ReadOnlyObjectException($this); }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="iterating" interface="IteratorAggregate">
///     <brief>Возвращает итератор по строкам результата</brief>

///     <method name="getIterator" returns="DB.CursorIterator">
///       <body>
  public function getIterator() { return new DB_CursorIterator($this); }
///       </body>
///     </method>

///   </protocol>

///   <protocol name="supporting">

///   <method name="make_row_instance" returns="mixed" access="protected">
///     <brief>Обробатывает строку результата и возвращает постоенный на её основе объект</brief>
///     <args>
///       <arg name="row" type="array" brief="массив, соответствующий записи" />
///     </args>
///     <body>
  protected function make_row_instance(array $row) {
    if ($this->prototype) {
      $class_field = DB::option('row_class_field');
      $prototype = (
        isset($row[$class_field]) &&
        (!$this->ignore_type) &&
        Core_Types::is_subclass_of($this->prototype, $type = $row[$class_field])) ?
          new $type() :
          $this->prototype;

      $array_access = ($prototype instanceof ArrayAccess);
      $result = clone $prototype;

      foreach ($row as $k => $v)  {
        $v = $this->adapter->cast_column($this->metadata[$k], $v);
        $array_access ? $result[$k] = $v : $result->$k = $v;
      }
      return $result;
    } else {
      foreach ($row as $k => &$v)
        $v = $this->adapter->cast_column($this->metadata[$k], $v);

      return $row;
    }
  }
///     </body>
///   </method>

///   </protocol>
}
/// </class>
/// <aggregation>
///   <source class="DB.Cursor" role="cursor"  />
///   <target class="DB.Connection" role="connection"  />
/// </aggregation>

/// </module>
