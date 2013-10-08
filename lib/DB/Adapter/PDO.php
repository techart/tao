<?php
/**
 * PDO адаптер
 * 
 * @author Timokhin <timokhin@techart.ru>
 * 
 * @package DB\Adapter\PDO
 */
Core::load('DB', 'WS');

/**
 * Класс модуля
 * 
 * @version 0.2.0
 * 
 * @package DB\Adapter\PDO
 */
class DB_Adapter_PDO implements Core_ModuleInterface
{
	/** 
	 * Версия модуля
	 */
	const VERSION = '0.2.0';
	
	/**
	 * Возвращает пулл соединений с БД
	 * 
	 * @todo сделать что-нибудь с WS
	 * 
	 * @return array;
	 */
	public static function connections_pool()
	{
		return WS::env()->pdo_connections ? WS::env()->pdo_connections : array();
	}
}

/**
 * Класс подключения к БД
 * 
 * @package DB\Adapter\PDO
 */
abstract class DB_Adapter_PDO_Connection implements DB_Adapter_ConnectionInterface
{
	/**
	 * @var PDO объект PDO
	 */
	protected $pdo;

	/**
	 * Конструктор
	 * 
	 * @params DB_DSN $dsn объект строки подключения к БД
	 * 
	 * @throws DB_ConnectionException Если не может подключиться к БД
	 */
	public function __construct(DB_DSN $dsn) {
		try {
			$connections = DB_Adapter_PDO::connections_pool();
			if (isset($connections[$dsn->pdo_string])) {
				$this->pdo = $connections[$dsn->pdo_string];
			} else {
				$this->pdo = new PDO($dsn->pdo_string, $dsn->user, $dsn->password);
			}
		} catch (PDOException $e) {
			throw new DB_ConnectionException($e->getMessage());
		}
	}

	/**
	 * Устанавливает атрибут
	 * 
	 * @params integer $id идентификатор
	 * @params mixed $value значение
	 * 
	 * @link http://php.ru/manual/pdo.setattribute.html
	 * 
	 * @return boolean
	 */
	public function set_attribute($id, $value)
	{
		return $this->pdo->setAttribute($id, $value);
	}

	/**
	 * Возвращает атрибут
	 * 
	 * @params integer $id идентификатор
	 * 
	 * @link http://php.ru/manual/pdo.getattribute.html
	 * 
	 * @return mixed
	 */
	public function get_attribute($id)
	{
		return $this->pdo->getAttribute($id);
	}

	/**
	 * Открывает транзакцию
	 * 
	 * @link http://php.ru/manual/pdo.begintransaction.html
	 * 
	 * @throws DB_ConnectionException Если драйвер не поддерживает транзакции.
	 * 
	 */ 
	public function transaction()
	{
		try {
			$this->pdo->beginTransaction();
		} catch (PDOException $e) {
			throw new DB_ConnectionException($e->getMessage);
		}
	}

	/**
	 * Фиксирует транзакцию
	 * 
	 * @link http://php.ru/manual/pdo.commit.html
	 * 
	 * @throws DB_ConnectionException  
	 */
	public function commit()
	{
		try {
			$this->pdo->commit();
		} catch (PDOException $e) {
			throw new DB_ConnectionException($e->getMessage);
		}
	}

	/**
	 * Откатывает транзакцию
	 * 
	 * @link http://php.ru/manual/pdo.rollback.html
	 * 
	 * @throws DB_ConnectionException Если нет активной транзакции
	 */
	public function rollback() 
	{
		try {
			$this->pdo->rollback();
		} catch (PDOException $e) {
			throw new DB_ConnectionException($e->getMessage);
		}
	}

	/**
	 * Возвращает последний вставленный идентификатор
	 * 
	 * @link http://php.ru/manual/pdo.lastinsertid.html
	 * 
	 * @throws DB_ConnectionException 
	 * 
	 * @return string
	 */
	public function last_insert_id()
	{
		try {
			return $this->pdo->lastInsertId();
		} catch (PDOException $e) {
			throw new DB_ConnectionException($e->getMessage);
		}
	}

	/**
	 * Квотит параметр
	 * 
	 * @link http://php.ru/manual/pdo.quote.html
	 * 
	 * @params string $value параметр
	 * 
	 * @return string
	 */
	public function quote($value)
	{
		return $this->pdo->quote($value);
	}

	/**
	 * Вызывается в DB.Connection после соединения
	 */
	public function after_connect()
	{
	}
}

/**
 * Класс курсора БД
 * 
 * @package DB\Adapter\PDO
 */
abstract class DB_Adapter_PDO_Cursor implements DB_Adapter_CursorInterface
{
	/**
	 * @var PDO объект PDO
	 */
	protected  $pdo;

	/**
	 * Конструктор
	 * 
	 * Устанавливается режим PDO::FETCH_ASSOC (см. {@link http://php.ru/manual/pdostatement.setfetchmode.html} и 
	 * {@link http://php.ru/manual/pdostatement.fetch.html fetch_style})
	 * 
	 * @params PDO $pdo PDO объект
	 */
	public function __construct($pdo)
	{
		$this->pdo = $pdo;
		$this->pdo->setFetchMode(PDO::FETCH_ASSOC);
	}

	/**
	 * Возвращает очередную строку результата
	 * 
	 * @throws DB_CursorException 
	 */
	public function fetch()
	{
		try {
			return $this->pdo->fetch();
		} catch (PDOException $e) {
			throw new DB_CursorException($e->getMessage());
		}
	}

	/**
	 * Возвращает все строки результата
	 * 
	 * @throw new DB_CursorException
	 * 
	 * @return array
	 */
	public function fetch_all()
	{
		$result = array();
		try {
			while ($row = $this->pdo->fetch()) {
				$result[] = $row;
			}
			return $result;
		} catch (PDOException $e) {
			throw new DB_CursorException($e->getMessage());
		}
	}

	/**
	 * Закрывает курсор
	 * 
	 * @throws DB_CursorException 
	 * 
	 * @return boolean
	 */
	public function close()
	{
		try {
			return $this->pdo->closeCursor();
		} catch (PDOException $e) {
			throw new DB_CursorException($e->getMessage());
		}
	}

	/**
	 * Определение типа
	 * 
	 * @link http://php.ru/manual/pdo.constants.html
	 * 
	 * @params mixed $v параметр, тип которого надо определить
	 * 
	 * @return integer
	 */
	protected function get_param_type($v)
	{
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
  
	/**
	 * Выполняет запрос 
	 * 
	 * @params array $binds массив параметров
	 * 
	 * @throws DB_CursorException 
	 * 
	 * @return boolean
	 */
	public function execute(array $binds)
	{
		try {
			foreach (array_values($binds) as $n => $v) {
				$this->pdo->bindValue($n+1, $v, $this->get_param_type($v));
			}
			return $this->pdo->execute();
		} catch (PDOException $e) {
			throw new DB_CursorException($e->getMessage());
		}
	}

	/**
	 * Возвращает количество строк в результате
	 * 
	 * @return integer
	 */
	public function get_num_of_rows()
	{
		return $this->pdo->rowCount();
	}

	/**
	 * Возвращает количество колонок
	 * 
	 * @return integer
	 */
	public function get_num_of_columns()
	{
		return $this->pdo->columnCount();
	}

	/**
	 * Возвращает мета данные строки результата
	 */
	public function get_row_metadata()
	{
		$metadata = new ArrayObject();
		for ($i = 0; $i < $this->pdo->columnCount(); $i++) {
			$v = $this->pdo->getColumnMeta($i);
			$metadata[$v['name']] = new DB_ColumnMeta(
				$v['name'], 
				isset($v['native_type']) ? $v['native_type'] : null, 
				$v['len'], 
				$v['precision']
			);
		}
		return $metadata;
	}
}
