<?php
/**
 * MySQL адаптер
 * 
 * @author Timokhin <timokhin@techart.ru>
 * 
 * @package DB\Adapter\MySQL
 */

Core::load('DB.Adapter.PDO', 'Time');

/**
 * Класс модуля
 * 
 * @version 0.2.0
 * 
 * @package DB\Adapter\MySQL
 */
class DB_Adapter_MySQL implements Core_ModuleInterface
{
	/** 
	 * Версия модуля
	 */
	const VERSION = '0.2.0';
}

/**
 * Класс подключения к БД
 * 
 * @package DB\Adapter\MySQL
 */
class DB_Adapter_MySQL_Connection extends DB_Adapter_PDO_Connection
{
	/**
	 * @var DB_Adapter_MySQL_Schema Схема 
	 */
	protected $schema;

	/**
	 * Подготавливает SQL-запрос к выполнению
	 * 
	 * @params string $sql SQL-запрос
	 * 
	 * @throws DB_ConnectionException Если сервер базы данных не может успешно подготовить утверждение
	 * 
	 * @return DB_Adapter_MySQL_Cursor
	 */
	public function prepare($sql)
	{
		try {
			return new DB_Adapter_MySQL_Cursor($this->pdo->prepare($sql));
		} catch (PDOException $e) {
			throw new DB_ConnectionException($e->getMessage());
		}
	}

	/**
	 * Преобразует значение в пригодный вид для вставки в sql запрос
	 * 
	 * @params mixed $value значение
	 * 
	 * @return mixed
	 */
	public function cast_parameter($value)
	{
		if ($value instanceof Time_DateTime) {
			return $value->format(Time::FMT_DEFAULT);
		} else {
			return $value;
		}
	}

	/**
	 * Проверяет требуется ли преобразовывать значение
	 * 
	 * @params mixed $value значение
	 * 
	 * @return boolean
	 */
	public function is_castable_parameter($value)
	{
		return ($value instanceof Time_DateTime);
	}

	/**
	 * Вызывается в DB.Connection после соединения
	 */
	public function after_connect()
	{
		$this->pdo->exec('SET NAMES '.DB::option('charset'));
	}

	/**
	 * Выполняет EXPLAIN для анализа запроса.
	 * 
	 * @params string $sql sql-запрос
	 * @params array $binds массив параметров
	 * 
	 * @return array массив строк
	 */
	public function explain($sql, $binds)
	{
		$c = $this->prepare("EXPLAIN ($sql)");
		$c->execute($binds);
		
		$result = array();
		foreach ($c->fetch_all() as $v) {
			$result[] = Core::object($v);
		}
		return $result;
	}

	/**
	 * Получает схему
	 * 
	 * @return DB_Adapter_MySQL_Schema
	 */
	public function get_schema()
	{
		return isset($this->schema) ? 
			$this->schema : 
			$this->schema = new DB_Adapter_MySQL_Schema();
	}

	/**
	 * Заключает параметр в обратные кавычки
	 * 
	 * @params string $str
	 * 
	 * @return string
	 */
	public function escape_identifier($str)
	{
		return "`$str`";
	}
}


/**
 * Класс курсора БД
 * 
 * @package DB\Adapter\MySQL
 */
class DB_Adapter_MySQL_Cursor extends DB_Adapter_PDO_Cursor
{
	/**
	 * Преобразует значение
	 * 
	 * Преобразует значение полученное из БД в нужный формат, для работы с ним в php
	 * 
	 * @params DB_ColumnMeta $metadata мета-данные колонки
	 * @params mixed $value значение
	 * 
	 * @return mixed
	 */
	public function cast_column(DB_ColumnMeta $metadata, $value)
	{
		switch ($metadata->type) {
			case 'datetime':
			case 'timestamp':
			case 'time':
			case 'date':
				return is_null($value) ? null : Time::DateTime($value);
			case 'boolean':
				return $value ? true : false;
			case 'longlong':
			case 'int24':
			case 'integer':
			case 'long':
			case 'tiny':
			case 'short':
				return is_null($value) ? null : (int) $value;
			case 'float':
			case 'double':
				return is_null($value) ? null : (float) $value;
			default:
				return $value;
		}
	}
}

/**
 * Класс схемы БД
 * 
 * @package DB\Adapter\MySQL
 */
class DB_Adapter_MySQL_Schema implements DB_Adapter_SchemaInterface 
{

	protected static $type_map = array(
		'varchar:normal'  => 'VARCHAR',
		'char:normal'     => 'CHAR',

		'text:tiny'       => 'TINYTEXT',
		'text:small'      => 'TINYTEXT',
		'text:medium'     => 'MEDIUMTEXT',
		'text:big'        => 'LONGTEXT',
		'text:normal'     => 'TEXT',

		'serial:tiny'     => 'TINYINT',
		'serial:small'    => 'SMALLINT',
		'serial:medium'   => 'MEDIUMINT',
		'serial:big'      => 'BIGINT',
		'serial:normal'   => 'INT',

		'int:tiny'        => 'TINYINT',
		'int:small'       => 'SMALLINT',
		'int:medium'      => 'MEDIUMINT',
		'int:big'         => 'BIGINT',
		'int:normal'      => 'INT',

		'float:tiny'      => 'FLOAT',
		'float:small'     => 'FLOAT',
		'float:medium'    => 'FLOAT',
		'float:big'       => 'DOUBLE',
		'float:normal'    => 'FLOAT',

		'numeric:normal'  => 'DECIMAL',

		'blob:big'        => 'LONGBLOB',
		'blob:normal'     => 'BLOB',

		'timestamp:normal' => 'TIMESTAMP',
		'datetime:normal' => 'DATETIME',
		'date:normal' => 'DATE' 
	);

	/**
	 * Определение столбцов
	 * 
	 * @params array $column
	 * 
	 * @return string
	 */
  public function column_definition($column) {
    $column = $this->map_column($column);
    if (isset($column['mysql_definition'])) return $column['mysql_definition'];
    
    $sql = $this->get_name($column) . $column['mysql_type'];
    
    if (in_array(Core_Strings::downcase($column['mysql_type']), array('varchar', 'char', 'tinytex', 'mediumtext', 'longtext', 'text')) && isset($column['length']))
      $sql .= '(' . $column['length'] . ')';
    elseif (isset($column['precision']) && isset($column['scale'])) {
      $sql .= '(' . $column['precision'] . ', ' . $column['scale'] . ')';
    }
    
    if (!empty($column['unsigned'])) {
      $sql .= ' unsigned';
    }
    
    if (isset($column['not null'])) {
      if ($column['not null'])
        $sql .= ' NOT NULL';
      else
        $sql .= ' NULL';
    }

    if (!empty($column['auto_increment'])) {
      $sql .= ' AUTO_INCREMENT';
    }

    if (array_key_exists('default', $column)) {
      if (is_string($column['default']) && $column['default_quote'] !== false)
        $column['default'] = "'" . $column['default'] . "'";
      elseif (!isset($column['default']))
        $column['default'] = 'NULL';
      $sql .= ' DEFAULT ' . $column['default'];
    }

    if (empty($column['not null']) && !isset($column['default']))
      $sql .= ' DEFAULT NULL';

    if (!empty($column['constraint']))
      $sql .=  ' ' . $column['constraint'];

    if (!empty($column['description']))
      $sql .= ' COMMENT ' . $column['description'];

    return $sql;
  }
  
  public function index_definition($table, $index) {
    return $this->process_index_type($index) . " `{$index['name']}` " .  '(' . $this->process_index_cols($index) . ')'.
      (isset($index['addition']) ? $index['addition'] : '');
  }
  
  public function add_column($column) {
    return 'ADD ' . $this->column_definition($column);
  }
  
  public function remove_column($column) {
    return 'DROP ' . $this->get_name($column);
  }
  
  public function update_column($column) {
    return 'MODIFY ' . $this->column_definition($column);
  }
  
  public function remove_index($table, $index) {
    return isset($index['type']) && $index['type'] == 'primary key' ?
      'ALTER TABLE `' . $table . '` DROP PRIMARY KEY' :
      "DROP INDEX `{$index['name']}` ON `$table`";
  }
  
  public function add_index($table, $index) {
    return isset($index['type']) && $index['type'] == 'primary key' ?
      "ALTER TABLE `$table` ADD PRIMARY KEY (" . $this->process_index_cols($index) . ')'
      : "CREATE " . $this->process_index_type($index) . " `{$index['name']}` ON `$table` " . '(' .
        $this->process_index_cols($index) . ')' . (isset($index['addition']) ? $index['addition'] : '');
  }
  
  public function alter_table($table, $actions) {
    $actions_sql = implode(', ', $actions);
    return "ALTER TABLE `{$table['name']}` $actions_sql";
  }
  
  public function create_table($table, $defs) {
    Core_Arrays::expand($table, array('mysql_engine' => 'InnoDB', 'mysql_character_set' => 'utf8'));
    $sql = sprintf("CREATE TABLE `%s` (%s)", $table['name'], implode(', ', $defs));
    $sql .= 'ENGINE = ' . $table['mysql_engine'] . ' DEFAULT CHARACTER SET ' . $table['mysql_character_set'];
    if (!empty($table['collation'])) {
      $sql .= ' COLLATE ' . $table['collation'];
    }
    return $sql;
  }
  
  
  protected function get_name($column) {
    return "`" . $column['name'] . "` ";
  }
  
  public function map_column($column) {
    $column['mysql_type'] = $this->map_type($column);
    if ($column['type'] == 'serial') {
      $column['auto_increment'] = true;
      //$column['constraint'] = empty($column['constraint']) ? 'PRIMARY KEY' : $column['constraint'] . ' PRIMARY KEY';
    }
    return $column;
  }
  
  public function map_type($column) {
    if (!empty($column['mysql_type'])) return $column['mysql_type'];
    $key = Core_Strings::contains(':', $column['type']) ? $column['type'] :
      Core_Strings::downcase($column['type']).':' . (isset($column['size']) ? $column['size'] : 'normal');
    return self::$type_map[$key];
  }
  
  public function index_exists_query($data, $index) {
    $name = (isset($index['type']) && $index['type'] == 'primary key') ? 'PRIMARY' : $index['name'];
    $table = $data['name'];
    return 'SHOW INDEX FROM ' . $table . " WHERE key_name = '$name'";
  }
  
  protected function process_index_type($index) {
    $key = '';
    $type = isset($index['type']) ? $index['type'] : '';
    switch($type) {
      case 'primary key':
        $key = 'PRIMARY KEY ';
        break;
      case 'unique':
        $key = 'UNIQUE INDEX '; 
        break;
      case 'fulltext':
        $key = 'FULLTEXT INDEX ';
        break;
      default:
        $key = 'INDEX ';
        break;
    }
    return $key;
  }
  
  protected function process_index_cols($index) {
    $res = array();
    foreach ($index['columns'] as $c) {
      if (is_array($c))
        $res[] = '`' . $c[0] . '`(' . $c[1] . ')';
      else
        $res[] = '`' . $c . '`';
    }
    return implode(', ', $res);
  }
  
  static public function get_columns_type_map() {
    return self::$type_map;
  }

  static public function get_flip_columns_type_map() {
    return array_flip(self::$type_map);
  }

  static public function flip_column($type) {
    $map = self::get_flip_columns_type_map();
    $type = Core_Strings::upcase($type);
    $res = array(null, null);
    if (isset($map[$type])) {
        $res = explode(':', $map[$type]);
    }
    return $res;
  }

  protected function inspect_columns($mapper) {
    $columns = array();
    foreach ($mapper as $r) {
      $col = array();
      $numeric = !is_null($r['numeric_scale']);
      $col['type'] = $r['column_type'];
      $col['name'] = $r['column_name'];
      $col['description'] = is_int($r['column_comment']) ? '' : $r['column_comment'];

      if (preg_match('@([a-z]+)(?:\((\d+)(?:,(\d+))?\))?\s*(unsigned)?@', $col['type'], $matches)) {
        list($col['type'], $col['size']) = self::flip_column($matches[1]);
        if (isset($matches[2])) {
          if ($col['type'] == 'numeric' || $col['type'] == 'float' || $col['type'] == 'double') {
            $col['precision'] = $matches[2];
            $col['scale'] = $matches[3];
          }
          else if (!$numeric) {
            $col['length'] = $matches[2];
          }
        }
        if (isset($matches[4])) {
          $col['unsigned'] = true;
        }
      }

      if ($col['type'] == 'int' && isset($r['extra']) && $r['extra'] == 'auto_increment') {
        $col['type'] = 'serial';
      }

      $col['not null'] = ($r['is_nullable'] == 'YES' ? false : true);

      if (!is_null($r['column_default'])) {
        if ($numeric) {
          //FIXME: float defaults
          $col['default'] = intval($r['column_default']);
        } else {
          $col['default'] = $r['column_default'];
        }
      }
      $columns[$col['name']] = $col;
    }
    return $columns;
  }

  protected function inspect_indexes($mapper) {
    $indexes = array();
    foreach ($mapper as $r ) {
      $name = $r['index_name'];

      if (isset($r['sub_part']) && !is_null($r['sub_part'])) {
        $col = array($r['column_name'], intval($r['sub_part']));
      } else {
        $col = $r['column_name'];
      }
      if ($r['index_name'] == 'PRIMARY') {
        $type = 'primary key';
      } else if ($r['non_unique'] == 0) {
        $type = 'unique';
      } else if ($r['index_type'] == 'FULLTEXT') {
        $type = 'fulltext';
      } else {
        $type = 'index';
      }
      if (!isset($indexes[$name]))
        $indexes[$name] = array('columns' => array($col), 'type' => $type, 'name' => $name);
      else
        $indexes[$name]['columns'][] = $col;
    }
    return $indexes;
  }

  public function inspect($info_mapper, $table_name, $dsn) {
    $table = array();
    $columns = $info_mapper->columns->columns('table_name', 'column_type', 'column_name', 'column_default',
      'extra', 'is_nullable', 'numeric_scale', 'column_comment, ordinal_position');

    $table['columns'] = $this->inspect_columns($columns->for_schema($dsn->database)->for_table($table_name)->order_by('ordinal_position'));
    $table['indexes'] = $this->inspect_indexes($info_mapper->statistics->for_table($table_name)->for_schema($dsn->database)->order_by('index_name, seq_in_index'));

    return $table;
  }
}
/// </module>
