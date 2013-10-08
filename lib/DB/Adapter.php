<?php
/**
 * Модуль определяет набор интерфейсов для адаптеров БД, также подгружает конкретный адаптер
 * 
 * @author Timokhin <timokhin@techart.ru>
 * 
 * @package DB\Adapter
 */
 

/** 
 * Класс модуля
 * 
 * @version 0.2.0
 * 
 * @package DB\Adapter
 */
class DB_Adapter implements Core_ConfigurableModuleInterface
{

	/** 
	 * Версия модуля
	 */
	const VERSION = '0.2.0';

	/**
	 * @var array Опции модуля
	 */
	static protected $options = array(
		'adapters' => array(
			'mysql' => 'MySQL', 'pgsql' => 'PostgreSQL', 'mssql' => 'MSSQL'
		)
	);
    
	/**
	 * Инициализация
	 * 
	 * @params array $options Опции модуля
	 */
	static public function initialize(array $options = array())
	{
		return self::options($options);
	}
	
	/**
	 * Установка и получение опций модуля
	 * 
	 * Если ключ уже есть в массиве, то его значение переопределяется,
	 * если еще нет - то элемент добавляется в массив параметров.
	 * 
	 * Возвращается значение опции модуля после установки.
	 * 
	 * @params array options Опции модуля
	 * 
	 * @return array
	 */
	static public function options(array $options = array())
	{
		return self::$options = array_merge(self::$options, $options);
	}
  
	/**
	 * Установка и получение значения опции
	 * 
	 * Если ключ уже есть в массиве, то его значение переопределяется,
	 * если еще нет - то элемент добавляется в массив параметров.
	 * 
	 * Возвращается значение опции модуля после установки.
	 * 
	 * @params string|integer $name имя опции
	 * @params mixed $value значение опции по умочанию null
	 * 
	 * @return array
	 */
	static public function option($name, $value = null)
	{
		if (is_null($value)) return self::$options[$name];
		return self::$options[$name] = $value;
	}

	/**
	 * Возвращает объект адаптера соответствующего DSN
	 * 
	 * @params DB_DSN $dsn объект строки DSN
	 * 
	 * @throws DB_Exception Если отсутствует параметр type в строке DSN
	 * 
	 * @return object
	 */
	static public function instantiate(DB_DSN $dsn)
	{
		$adapters = self::option('adapters');
		if (isset($adapters[$dsn->type])) {
			$module = $adapters[$dsn->type];
			$module = Core_Strings::contains('.', $module) ? $module : 'DB.Adapter.' . $module;
			Core::load($module);
			return Core::make($module . '.Connection', $dsn);
		} else {
			throw new DB_Exception("Missing adapter for type $module");
		}
	}
}


/**
 * Интерфейс для класса соединения с БД
 * 
 * @package DB\Adapter
 */
interface DB_Adapter_ConnectionInterface
{

	/**
	 * Конструктор
	 * 
	 * @params DB_DSN $dsn Объект строки параметров доступа к БД
	 */
	public function __construct(DB_DSN $dsn);

	/**
	 * Преобразует значение в пригодный вид для вставки в sql запрос
	 * 
	 * @params mixed $value значение для преобразования
	 */
	public function cast_parameter($value);

	/**
	 * Проверяет требуется ли преобразовывать значение
	 * 
	 * @params mixed $value проверяемое значение
	 */
	public function is_castable_parameter($value);

	/**
	 * Устанавливает атрибут
	 * 
	 * @params integer $id идентификатор
	 * @params mixed $value значение
	 */
	public function set_attribute($id, $value);

	/**
	 * Возвращает атрибут
	 * 
	 * @params integer $id идентификатор
	 * 
	 * @return mixed
	 */
	public function get_attribute($id);

	/**
	 * Подготавливает SQL-запрос к выполнению
	 * 
	 * @params string $sql sql-запрос
	 */
	public function prepare($sql);

	/**
	 * Открывает транзакцию
	 */
	public function transaction();

	/**
	 * Фиксирует транзакцию
	 */
	public function commit();

	/**
	 * Откатывает транзакцию
	 */
	public function rollback();

	/**
	 * Возвращает последний вставленный идентификатор
	 * 
	 * @return integer
	 */
	public function last_insert_id();

	/**
	 * Квотит параметр
	 * 
	 * @params string $value параметр
	 */
	public function quote($value);

	/**
	 * Вызывается в DB.Connection после соединения
	 */
	public function after_connect();

	/**
	 * Выполняет EXPLAIN для анализа запроса
	 * 
	 * @params string $sql sql-запрос
	 * @params array $binds массив параметров
	 */
	public function explain($sql, $binds);

	/**
	 * Получить схему БД
	 */
	public function get_schema();

	/**
	 * Заключает параметр в обратные кавычки
	 */
	public function escape_identifier($str);

}

/**
 * Интерфейс курсора БД
 * 
 * @package DB\Adapter
 */
interface DB_Adapter_CursorInterface
{

	/**
	 * Преобразует значение полученное из БД в нужный формат, для работы с ним в php
	 * 
	 * @params DB_ColumnMeta $metadata мета-данные колонки
	 * @params mixed $value значение для преобразования
	 */
	public function cast_column(DB_ColumnMeta $metadata, $value);

	/**
	 * Возвращает очередную строку результата
	 */
	public function fetch();

	/**
	 * Закрывает курсор
	 */
	public function close();

	/**
	 * Выполняет запрос
	 * 
	 * @params array $binds массив параметров
	 */
	public function execute(array $binds);

	/**
	 * Возвращает количество строк в результате
	 * 
	 * @return integer
	 */
	public function get_num_of_rows();

	/**
	 * Возвращает количество колонок
	 * 
	 * @return integer
	 */
	public function get_num_of_columns();

	/**
	 * Возвращает мета данные строки результата
	 */
	public function get_row_metadata();
}

interface DB_Adapter_SchemaInterface 
{
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
