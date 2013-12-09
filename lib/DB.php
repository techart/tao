<?php
/**
 * Набор классов для работы с БД
 * 
 * @author Timokhin <timokhin@techart.ru>
 * 
 * @version 0.2.3
 * 
 * @package DB
 */
Core::load('Object', 'DB.Adapter');

/**
 * Класс модуля
 * 
 * @package DB
 */
class DB implements Core_ConfigurableModuleInterface {

	/** 
	 * Версия модуля
	 */
	const VERSION = '0.2.3';
	
	const PLACEHOLDER_REGEXP = ':([a-zA-Z_][a-zA-Z_0-9]*)';

	/**
	 * @var array Набор опций
	 */
	static protected $options = array(
		'error_handling_mode' => PDO::ERRMODE_EXCEPTION,
		'collection_class'    => 'ArrayObject',
		'charset'             => 'UTF8',
		'row_class_field'     => '__class',
		'time_zone' => false,
	);

	/**
	 * Инициализация
	 * 
	 * Устанавливает набор опций
	 * 
	 * @param array $options Набор опций
	 */
	static public function initialize(array $options = array())
	{
		self::options($options);
	}

	/**
	 * Фабричный метод, возвращает объект класса DB.Connection
	 * 
	 * @param string $dsn строка DSN, определяющая параметры доступа к базе
	 * 
	 * @return DB_Connection
	 */
	static public function Connection($dsn)
	{
		return new DB_Connection($dsn);
	}

	/**
	 * Установка и получение опций модуля
	 * 
	 * @param array $options массив опций по умолчанию array()
	 * 
	 * @return self::$options
	 */
	static public function options(array $options = array())
	{
		if (count($options)) {
			Core_Arrays::update(self::$options, $options);
		}
		return self::$options;
	}

	/**
	 * Установка и получение значения опции.
	 * 
	 * Возвращается предыдущее значение опции.
	 * 
	 * @param string $name Имя опции
	 * @param mixed $value Значение опции
	 * 
	 * @return mixed
	 */
	static public function option($name, $value = null)
	{
		$prev = isset(self::$options[$name]) ? 
			self::$options[$name] : 
			null;
			
		if ($value !== null) {
			self::options(array($name => $value));
		}
		return $prev;
	}
}


/**
 * Класс исключения
 * 
 * @package DB
 */
class DB_Exception extends Core_Exception
{
}


/**
 * Класс исключения для неудачного подключения
 * 
 * @package DB
 */
class DB_ConnectionException extends DB_Exception
{
}


/**
 * Класс исключения для курсора
 * 
 * @package DB
 */
class DB_CursorException extends DB_Exception
{
	/**
	 * @var string Запрос, который вызвал исключение
	 */
	protected $sql;

	/**
	 * Конструктор
	 * 
	 * @param string $message Сообщение об исключительной ситуации
	 * @param string $sql Запрос, который вызвал исключение по умолчанию пустая строка
	 */
	public function __construct($message, $sql = '')
	{
		$this->sql = $sql;
		$m = empty($this->sql) ? 
			$message : 
			$message . ' in query : ' . $sql;
			
		parent::__construct($m);
	}
}


/**
 * Класс объекта DSN строки подключения к БД
 * 
 * Строка имеет вид: type://username:password@host:port/database/scheme.
 * Например mysql://app:app@localhost/test
 * В этой строке обязательными являются type, host и database
 * 
 * @package DB
 */
class DB_DSN implements Core_PropertyAccessInterface, Core_StringifyInterface 
{
	/** @deprecated вместо регулярных выражений используется parse_url */
	const FORMAT = '{^([^:/]+)://(?:(?:([^:@]+)(?::([^@]+))?@)?([^:/]+)?(?::(\d+))?/)?([^/]+)(/[^/]+)?$}';

	/** @var string тип БД */
	protected $type     = '';
	
	/** @var string пользователь БД */
	protected $user     = '';
	
	/** @var string Пароль БД */
	protected $password = '';
	
	/** @var string Сервер БД */
	protected $host     = '';
	
	/** @var integer Порт БД */
	protected $port     = '';
	
	/** @var string Имя БД */
	protected $database = '';
	
	/** @var string Схема БД */
	protected $scheme   = '';
  
	/**
	 * @var array Параметры запроса
	 */
	protected $parms    = array();

	/**
	 * Конструктор
	 * 
	 * @params array $parms Массив элементов DSN
	 */
	protected function __construct(array $parms)
	{
		foreach ($parms as $k => $v) {
			if (isset($this->$k)) {
				$this->$k = $v;
			}
		}
	}

	/**
	 * Парсер строки подключения
	 * 
	 * Эта функция создает объект класса после успешного разбора строки DSN.
	 * Подробности разбора строки можно найти в 
	 * {@link http://php.ru/manual/function.parse-url.html описании parse_url}
	 * Обязательно должны присутствовать параметры type, host и database.
	 *
	 * Если указана схема, но не указана база, то считается что имя базы = имя схемы
	 * 
	 * @param string $string Строка DSN
	 * 
	 * @throws DB_ConnectionException Если не можем разобрать строку.
	 * 
	 * @return DB_DSN
	 */
	static public function parse($string) 
	{
		$p = parse_url($string);
		$scheme = '';

		if (isset($p['path'])) {
			$parts = explode('/', trim($p['path'], '/ '));
			if (isset($parts[1])) {
				$scheme = $parts[1];
				$p['path'] = $parts[0];
			}
		}
		
		$query = array();
		if (isset($p['query'])) {
			parse_str($p['query'], $query);
		}
		
		$parms = array(
			'type'     => $p['scheme'],
			'user'     => $p['user'],
			'password' => $p['pass'],
			'host'     => $p['host'],
			'port'     => isset($p['port']) ? $p['port'] : '',
			'database' => trim($p['path'], '/ '),
			'scheme'   => $scheme,
			'parms'    => $query
		);

		if (isset($parms['type']) && 
			isset($parms['host']) && 
			isset($parms['database']) && 
			!empty($parms['database'])
		) {
			return new DB_DSN($parms);
		} else {
			throw new DB_ConnectionException("Bad DSN: $string");
		}
	}

	/**
	 * Возвращает строку в виде пригодном для PDO
	 * 
	 * @return string
	 */
	protected function as_pdo_string() 
	{
		return 
			Core_Strings::format("%s:host=%s;dbname=%s", $this->type, $this->host, $this->database).
			($this->port ? ";port={$this->port}" : '').($this->scheme ? ";scheme={$this->scheme}" : '');
	}

	/**
	 * Возвращает строку подключения.
	 * 
	 * @return string
	 */
	public function as_string()
	{
		return
			("$this->type://").
			($this->user ? $this->user.($this->password ? ":$this->password" : '').'@' : '').
			$this->host.($this->port ? ":$this->port" : '').'/'.$this->database.
			($this->scheme ? "/$this->scheme" : '').
			(count($this->parms) > 0 ? '?' . http_build_query($this->parms) : '');
	}

	/**
	 * Возвращает строку подключения.
	 * 
	 * @return string
	 */
	public function __toString()
	{
		return $this->as_string();
	}

	/**
	 * Доступ на чтение
	 * 
	 * - type тип БД, mysql pgsql;
	 * - user имя пользователя;
	 * - password пароль;
	 * - host хост;
	 * - port порт;
	 * - database база данных;
	 * - scheme схема;
	 * - parms массив параметров запроса;
	 * - pdo_string строка в виде пригодном для PDO подключения.
	 * 
	 * @throws Core_MissingPropertyException Любое другое значение
	 * 
	 * @params string имя свойства
	 * 
	 * @return string|array
	 */
	public function __get($property)
	{
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

	/**
	 * Доступ на запись
	 * 
	 * Установить можно все свойства, кроме pdo_string
	 * Для установки значения свойства parms должен передаваться массив.
	 * 
	 * @param string $property Имя свойства
	 * @param string|array $value Значение свойства
	 * 
	 * @throws Core_ReadOnlyPropertyException При попытке установить pdo_string
	 * @throws Core_MissingPropertyException При попытке установить несуществующее свойство
	 * 
	 * @return self
	 */
	public function __set($property, $value)
	{
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

	/**
	 * Проверяет установленно ли свойство
	 * 
	 * @param string $property Имя свойства
	 * 
	 * @return @boolean
	 */
	public function __isset($property) 
	{
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

	/**
	 * Очищает свойство
	 * 
	 * @params string $property Имя свойства
	 * 
	 * @throws Core_UndestroyablePropertyException Очистка свойств запрещена.
	 * @throws Core_MissingPropertyException При попытке очистить несуществующее свойство
	 */
	public function __unset($property) 
	{
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
}

/**
 * Интерфейс для слушателя событий БД
 * 
 * @package DB
 */
interface DB_EventListener
{
}

/**
 * Интерфейс для слушателя выполняемых команд
 * 
 * @package DB
 */
interface DB_QueryExecutionListener extends DB_EventListener
{
	/**
	 * Вызывается при выполнении команды
	 * 
	 * @param DB_Cursor $cursor курсор
	 */
	public function on_execute(DB_Cursor $cursor);
}

/**
 * Класс подключения к БД
 * 
 * @package DB
 */
class DB_Connection implements Core_PropertyAccessInterface 
{
	/** @var string Строка DSN */
	protected $dsn;
	
	/** @var DB_Adapter Адаптер */
	protected $adapter;

	/** @var Object_Listener Слушатель */
	protected $listeners;
	
	/** 
	 * Конструктор
	 * 
	 * @params string $dsn Строка DSN
	 */
	public function __construct($dsn) 
	{
		$this->dsn = DB_DSN::parse($dsn);
		$this->listeners = Object::Listener('DB.EventListener');
	}

	/**
	 * Отсоединение от БД
	 * 
	 * @return self
	 */
	public function disconnect()
	{
		$this->adapter = null;
		return $this;
	}

	/** 
	 * Соединение с БД
	 * 
	 * @return self
	 */
	public function connect() 
	{
		if (empty($this->adapter)) {
			$this->adapter = DB_Adapter::instantiate($this->dsn);
			$this->adapter->set_attribute(PDO::ATTR_ERRMODE, DB::option('error_handling_mode'));
			$this->adapter->after_connect();
		}
		return $this;
	}

	/**
	 * Регистрирует слушателя событий
	 * 
	 * @params DB_EventListener $listener слушатель
	 * 
	 * @return self
	 */
	public function listener(DB_EventListener $listener)
	{
		$this->listeners->append($listener);
		return $this;
	}

	/**
	 * Создает транзакцию
	 * 
	 * @return self
	 */
	public function transaction()
	{
		$this->__get('adapter')->transaction();
		return $this;
	}

	/**
	 * Коммит транзакции
	 * 
	 * @return self
	 */
	public function commit()
	{
		$this->__get('adapter')->commit();
		return $this;
	}

	/**
	 * Откат транзакции
	 * 
	 * @return self
	 */
	public function rollback()
	{
		$this->__get('adapter')->rollback();
		return $this;
	}

	/**
	 * Получение схемы
	 *
	 * @return DB_Adapter_SchemaInterface схема
	 */
	public function get_schema()
	{
		return $this->adapter->get_schema();
	}

	/**
	 * Подготавливает sql запрос
	 * 
	 * @params string $sql SQL-запрос
	 * 
	 * @return DB_Cursor
	 */
	public function prepare($sql)
	{
		return new DB_Cursor($this, $sql);
	}

	/**
	 * Выполняет sql-запрос
	 * 
	 * Возвращает количество строк в результате
	 * 
	 * @params string $sql SQL-запрос
	 * @params array $parms Параметры SQL-запроса по умолчанию array()
	 * 
	 * @return integer 
	 */
	public function execute($sql, $parms = array())
	{
		return $this->prepare($sql)->bind($parms)->execute()->num_of_rows;
	}

	/**
	 * Выполняет sql-запрос
	 * 
	 * Возвращает курсор для получения результатов
	 * 
	 * @params string $sql SQL-запрос
	 * @params array $parms Параметры SQL-запроса по умолчанию array()
	 * 
	 * @return DB_Cursor 
	 */
	public function query($sql, $parms = array())
	{
		return $this->prepare($sql)->bind($parms)->execute();
	}

	/**
	 * Возвращает номер последнего вставленного идентификатора
	 * 
	 * @return integer
	 */
	public function last_insert_id()
	{
		return $this->__get('adapter')->last_insert_id();
	}

	/**
	 * Квотит параметр
	 * 
	 * @params string $value Параметр
	 * 
	 * @return string
	 */
	public function quote($value)
	{
		return $this->__get('adapter')->quote((string) $value);
	}

	/**
	 * Доступ на чтение
	 * - adapter
	 * - dsn
	 * - listeners
	 * 
	 * @params string $property Имя свойства
	 * 
	 * @throws Core_MissingPropertyException Если любое другое свойство
	 * 
	 * @return object
	 */
	public function __get($property) 
	{
		switch ($property) {
			case 'adapter':
				$this->connect();
			case 'dsn':
			case 'listeners':
				return $this->$property;
			default:
				throw new Core_MissingPropertyException($property);
		}
	}

	/**
	 * Доступ на запись
	 * 
	 * @params string $property Имя свойства
	 * @params mixed $value Значение свойства
	 * 
	 * @throws Core_ReadOnlyObjectException Всегда - доступ только для чтения
	 */
	public function __set($property, $value)
	{
		throw new Core_ReadOnlyObjectException($this);
	}

	/**
	 * Проверяет установленно ли свойство
	 * 
	 * @params string $property Имя свойства
	 * 
	 * @return boolean
	 */
	public function __isset($property)
	{
		switch ($property) {
			case 'adapter':
			case 'dsn':
			case 'listeners':
				return isset($this->$property);
			default:
				return false;
		}
	}

	/**
	 * Очищает свойтсво
	 * 
	 * @params string $property Имя свойства
	 * 
	 * @throws Core_ReadOnlyObjectException Всегда - доступ только для чтения
	 */
	public function __unset($property)
	{
		throw new Core_ReadOnlyObjectException($this);
	}
}


/**
 * Класс - Итератор по записям курсора
 * 
 * @package DB
 */
class DB_CursorIterator implements Iterator {
	
	/** @var DB_Cursor Курсор */
	protected $cursor;

	/**
	 * Конструктор
	 * 
	 * @params DB_Cursor $cursor Курсор
	 */
	public function __construct(DB_Cursor $cursor)
	{
		$this->cursor = $cursor;
	}

	/**
	 * Возвращает текущий элемент - строку результата
	 * 
	 * @return mixed
	 */
	public function current()
	{
		return $this->cursor->row;
	}

	/**
	 * Возвращает ключ текущего элемента - номер строки
	 * 
	 * @return mixed
	 */
	public function key()
	{
		return $this->cursor->num_of_fetched - 1;
	}

	/**
	 * Возвращает следующий элемент - строку результата
	 * 
	 * @return mixed
	 */
	public function next()
	{
		$this->cursor->fetch();
	}

	/**
	 * Сбрасывает итератор в начало
	 * 
	 * @return mixed
	 */
	public function rewind()
	{
		$this->cursor->close();
		$this->cursor->execute();
		$this->cursor->fetch();
	}

	/**
	 * Проверяет является ли текущий элемент валидным
	 * 
	 * @return boolean
	 */
	public function valid()
	{
		return $this->cursor->row ? true : false;
	}
}

/**
 * Мета-данные колонки БД
 * 
 * @package DB
 */
class DB_ColumnMeta extends stdClass
{
	/**
	 * Конструктор
	 * 
	 * @params string $name имя колонки
	 * @params string $type тип колонки
	 * @params integer $length длина колонки
	 * @params integer $precision точность
	 */
	public function __construct($name, $type, $length, $precision)
	{
		$this->name      = strtolower($name);
		$this->type      = strtolower($type);
		$this->length    = (int) $length;
		$this->precision = (int) $precision;
	}
}

/**
 * Курсор
 * 
 * @package DB
 */
class DB_Cursor implements Core_PropertyAccessInterface, IteratorAggregate
{
	/** @var DB_Connection Объект подключения к базе данных */
	protected $connection;

	/** @var DB_Adapter Адаптер */
	protected $adapter;

	/** @var array {@link http://php.ru/manual/pdostatement.getcolumnmeta.html} */
	protected $metadata;
	
	/** @var mixed строка результата, возвращенная адаптером */
	protected $row;
	
	/** @var integer номер строки результата */
	protected $row_no = 0;
	
	/** @var string SQL-запрос */
	protected $sql    = '';
	
	/** @var array Набор значений для вставки в sql запрос */
	protected $binds  = array();
	
	/** @var object Прототип объекта, используемый в качестве объектного представления строки из таблицы */
	protected $prototype;
	
	/** @var boolean Признак успешного выполнения запроса */
	protected $is_successful = true;
	
	/** @var integer Время выполнения запроса */
	protected $execution_time;
	
	/** @var boolean признак игнорирования колонки type */
	protected $ignore_type = false;

	/**
	 * Конструктор
	 * 
	 * @params DB_Connection $connection Объект подключения к базе данных
	 * @params string $sql SQL-запрос
	 */
	public function __construct(DB_Connection $connection, $sql)
	{
		$this->connection  = $connection;
		$this->sql         = (string) $sql;
	}

	/**
	 * Устанавливает прототип для возвращаемого результата
	 * 
	 * @params object|string $prototype Прототип объекта, 
	 * используемого для хранения объектов записей, или имя класса такого объекта
	 * @params boolean $ignore_type игнорировать ли колонку type
	 * 
	 * @throws Core_BadArgumentTypeException Если параметр $prototype имеет недопустимый тип
	 * 
	 * @return self
	 */
	public function as_object($prototype, $ignore_type = false)
	{
		$this->ignore_type = (boolean) $ignore_type;
		
		if (Core_Types::is_string($prototype)) {
			$this->prototype = Core_Types::reflection_for($prototype)->newInstance();
		} elseif (Core_Types::is_object($prototype)) {
			$this->prototype = $prototype;
		} else {
			throw new Core_BadArgumentTypeException('prototype');
		}
		
		return $this;
	}

	/**
	 * Связывает параметры в sql-запросе с переданными в метод
	 * 
	 * @uses DB::PLACEHOLDER_REGEXP
	 * 
	 * @return self
	 */
	public function bind()
	{
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
							$this->binds[] = $adapter->cast_parameter(
								isset($values[$name]) ? 
									$values[$name] : 
									$values[$no]
							);
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

	/**
	 * Проверяет есть связанные параметры
	 * 
	 * @return boolean
	 */
	protected function has_binds()
	{
		return count($this->binds) > 0;
	}

	/**
	 * Выполняет EXPLAIN для анализа запроса
	 * 
	 * @uses DB::PLACEHOLDER_REGEXP
	 * 
	 * @return array|null
	 */
	public function explain()
	{
		if (Core_Strings::starts_with(Core_Strings::trim($this->sql), 'SELECT')) {
			return 
				$this->connection->adapter->explain(
					Core_Regexps::replace('{'.DB::PLACEHOLDER_REGEXP.'}', '?', $this->sql),
					$this->binds
				);
		} else {
			return null;
		}
	}

	/**
	 * Возвращает сформированный запрос SQL
	 * 
	 * @uses DB::PLACEHOLDER_REGEXP
	 * 
	 * @return string
	 */
	public function pure_sql()
	{
		$sql = $this->sql;
		if ($match = Core_Regexps::match_all('{(?:'.DB::PLACEHOLDER_REGEXP.')}', $sql)) {
			foreach ($match[1] as $no => $name) {
				$sql = str_replace(":$name", "'" . $this->binds[$no] . "'", $sql);
			}
		}
		return $sql;
	}
  

	/**
	 * Выполняет запрос
	 * 
	 * @uses DB::PLACEHOLDER_REGEXP
	 * 
	 * @return self
	 * @throws DB_CursorException Ошибка при выполнении запроса
	 */
	public function execute()
	{
		$this->close();
		$this->adapter = $this
						->connection
						->adapter
						->prepare(Core_Regexps::replace('{'.DB::PLACEHOLDER_REGEXP.'}', '?', $this->sql));

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

	/**
	 * Возвращает очередную строку результата
	 * 
	 * @throws DB_CursorException Если адаптер не установлен.
	 * 
	 * @return mixed
	 */
	public function fetch() 
	{
		if ($this->adapter) {
			if ($this->row = $this->adapter->fetch()) {
				$this->row = $this->make_row_instance($this->row);
				$this->row_no++;
			}
			return $this->row ? $this->row : null;
		} else {
			throw new DB_CursorException('Unable to fetch before query execution');
		}
	}

	/**
	 * Возвращает все строки результата
	 * 
	 * @params string|array|ArrayAccess $prototype Прототип объекта записи или имя класса такого объекта 
	 * @params mixed $key  свойство объекта $row
	 * 
	 * @return mixed
	 * @throws Core_InvalidArgumentTypeException Не верный $prototype
	 */
	public function fetch_all($prototype = null, $key = null) 
	{
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
		while ($row = $this->fetch()) {
			if (is_null($key) || !isset($row[$key])) {
				$result[] = $row;
			} else {
				$result[$row[$key]] = $row;
			}
		}
		return $result;
	}

	/**
	 * Возвращает текущую строку результата
	 * 
	 * @return mixed
	 */
	public function fetch_value()
	{
		if ($this->adapter && $row = $this->adapter->fetch()) {
			$this->close();
			return current($row);
		} else {
			return null;
		}
	}

	/**
	 * Возвращает все строки результата, используя прототип
	 * 
	 * @params string|array|ArrayAccess Прототип объекта записи или имя класса такого объекта 
	 * 
	 * @return mixed
	 */
	public function fetch_all_as($prototype)
	{
		return $this->fetch_all($prototype);
	}

	/**
	 * Закрывает курсор
	 */
	public function close()
	{
		if ($this->adapter) {
			return $this->adapter->close();
		}
	}

	/**
	 * Доступ на чтение
	 * 
	 * - metadata мета-данные;
	 * - is_successful флаг успешного выполнения запроса;
	 * - sql sql-запрос;
	 * - row строка результата;
	 * - binds массив связанных параметров;
	 * - execution_time время выполнения запроса;
	 * - num_of_rows количество строк в результате;
	 * - num_of_fetched количество выбранных из результата строк;
	 * - num_of_columns количество колонок;
	 * - connection объект подключения к базе данных.
	 * 
	 * @params string $property Имя свойства
	 * 
	 * @throws Core_MissingPropertyException Если любое другое значение
	 * 
	 * @return mixed
	 */
	public function __get($property)
	{
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

	/**
	 * Доступ на запись
	 * 
	 * @params string $property Имя свойства
	 * @params string $property Значение свойства
	 * 
	 * @throws Core_ReadOnlyObjectException Всегда - доступ только на чтение
	 */
	public function __set($property, $value)
	{
		throw new Core_ReadOnlyObjectException($this);
	}

	/**
	 * Проверяет установленно ли свойство объекта
	 * 
	 * Для значений 
	 * - metadata,
	 * - is_successful,
	 * - sql,
	 * - row,
	 * - connection,
	 * - binds,
	 * - execution_time
	 * возвращает результат isset($property)
	 * 
	 * Для значений 
	 * - num_of_rows,
	 * - num_of_fetched,
	 * - num_of_columns
	 * возвращает true
	 * 
	 * Для всех остальных значений false
	 * 
	 * @params string $property Имя свойства
	 * 
	 * @return boolean
	 */
	public function __isset($property)
	{
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

	/**
	 * Очищает свойство объекта
	 * 
	 * @params string $property Имя свойства
	 * 
	 * @throws Core_ReadOnlyObjectException Всегда - доступ только на чтение
	 */
	public function __unset($property)
	{
		throw new Core_ReadOnlyObjectException($this);
	}

	/**
	 * Возвращает итератор по строкам результата
	 * 
	 * @return DB_CursorIterator
	 */
	public function getIterator()
	{
		return new DB_CursorIterator($this);
	}

	/**
	 * Возвращает объект строки результата
	 * 
	 * Обрабатывает строку результата и возвращает построенный на её основе объект
	 * 
	 * @params array $row массив, соответствующий записи
	 * 
	 * @return object
	 */
	protected function make_row_instance(array $row)
	{
		if ($this->prototype) {
			$class_field = DB::option('row_class_field');
			$prototype = 
			(
				isset($row[$class_field]) &&
				(!$this->ignore_type) &&
				Core_Types::is_subclass_of($this->prototype, $type = $row[$class_field])
			) ?
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
			foreach ($row as $k => &$v) {
				$v = $this->adapter->cast_column($this->metadata[$k], $v);
			}
			return $row;
		}
	}
}
