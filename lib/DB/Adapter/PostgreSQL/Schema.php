<?php

/**
 * Класс схемы БД
 *
 * @package DB\Adapter\MySQL
 */
class DB_Adapter_PostgreSQL_Schema implements DB_Adapter_SchemaInterface, Core_ModuleInterface
{

	protected static $type_map = array(
		'varchar:normal' => 'CHARACTER VARYING',
		'char:normal' => 'CHAR',

		'text:tiny' => 'TEXT',
		'text:small' => 'TEXT',
		'text:medium' => 'TEXT',
		'text:big' => 'TEXT',
		'text:normal' => 'TEXT',

		'serial:tiny' => 'SERIAL',
		'serial:small' => 'SERIAL',
		'serial:medium' => 'SERIAL',
		'serial:big' => 'BIGSERIAL',
		'serial:normal' => 'SERIAL',

		'int:tiny' => 'SMALLINT',
		'int:small' => 'SMALLINT',
		'int:medium' => 'INTEGER',
		'int:big' => 'BIGINT',
		'int:normal' => 'INTEGER',

		'float:tiny' => 'REAL',
		'float:small' => 'REAL',
		'float:medium' => 'REAL',
		'float:big' => 'DOUBLE PRECISION',
		'float:normal' => 'DOUBLE PRECISION',

		'numeric:normal' => 'DECIMAL',

		'blob:big' => 'BYTEA',
		'blob:normal' => 'BYTEA',

		'timestamp:normal' => 'TIMESTAMP WITHOUT TIME ZONE',
		'datetime:normal' => 'TIMESTAMP WITHOUT TIME ZONE',
		'date:normal' => 'DATE',
		'time:normal' => 'TIME'
	);

	protected $sql;

	public function __construct()
	{
		$this->sql = new DB_Adapter_PostgreSQL_Schema_SQL();
	}


	public function column_def_length($column)
	{
		if (in_array(Core_Strings::downcase($column['pgsql_type']), array('character varying', 'char'))
			&& isset($column['length'])
		) {
			return '(' . $column['length'] . ')';
		} elseif (isset($column['precision']) && isset($column['scale'])) {
			return '(' . $column['precision'] . ', ' . $column['scale'] . ')';
		}
		return '';
	}

	public function column_def_unsigned($column)
	{
		if (!empty($column['unsigned'])) {
			return " CHECK (\"{$column['name']}\" >= 0)";
		}
		return '';
	}

	public function column_def_null($column)
	{
		if (isset($column['not null'])) {
			return $column['not null'] ? ' NOT NULL' : ' NULL';
		}
		return '';
	}

	public function column_def_default($column)
	{
		if (!$this->is_serial_column($column) && array_key_exists('default', $column)) {
			if (is_string($column['default']) && $column['default_quote'] !== false) {
				$column['default'] = "'" . $column['default'] . "'";
			} elseif (!isset($column['default'])) {
				$column['default'] = 'NULL';
			}
			if (Core_Strings::starts_with(trim($column['default'], "'"), '0000-00-00')) {
				$column['default'] = 'NULL';
			}
			return ' DEFAULT ' . $column['default'];
		}

		if (!$this->is_serial_column($column) && empty($column['not null']) && !isset($column['default'])) {
			return ' DEFAULT NULL';
		}
		return '';
	}

	/**
	 * Определение столбцов
	 *
	 * @params array $column
	 *
	 * @return string
	 */
	public function column_definition($column)
	{
		$column = $this->map_column($column);
		if (isset($column['pgsql_definition'])) {
			return $column['pgsql_definition'];
		}
		$sql = $this->get_name($column) . $column['pgsql_type'];
		$sql .= $this->column_def_length($column);
		$sql .= $this->column_def_unsigned($column);
		$sql .= $this->column_def_null($column);
		$sql .= $this->column_def_default($column);
		if (!empty($column['constraint'])) {
			$sql .= ' ' . $column['constraint'];
		}
		return $sql;
	}

	public function index_definition($table, $index)
	{
		if (!isset($index['name'])) {
			return '';
		}
		$name = $index['name'];
		if (strtolower($index['type']) == 'primary key') {
			$name = $table . '_pkey';
		}
		$type = $this->process_index_type($index);
		$columns = $this->process_index_cols($index);
		if ($type == 'INDEX') {
			return "QUERY:" . $this->add_index($table, $index);
		}
		return
			"CONSTRAINT \"{$name}\" ".
			$type .
			"({$columns})" .
			(isset($index['addition']) ? $index['addition'] : '');
	}

	public function add_column($column)
	{
		return 'ADD COLUMN ' . $this->column_definition($column);
	}

	public function remove_column($column)
	{
		return 'DROP COLUMN ' . $this->get_name($column);
	}

	public function update_column($column)
	{
		$column = $this->map_column($column);
		$name = $this->get_name($column);
		$type = $column['pgsql_type'];
		$len = $this->column_def_length($column);
		$uns = $this->column_def_unsigned($column);
		$null = $this->column_def_null($column);
		$default = $this->column_def_default($column);
		$res = array("ALTER COLUMN {$name} TYPE {$type} {$len}");
		if ($len && isset($column['length'])) {
			$res[0] .= " USING substr({$name}, 1, {$column['length']})";
		}
		if ($default) {
			$res[] = "ALTER {$name} SET $default";
		} else {
			$res[] = "ALTER {$name} DROP DEFAULT";
		}
		if (trim($null) == 'NOT NULL') {
			$res[] = "ALTER {$name} SET NOT NULL";
		} else {
			$res[] = "ALTER {$name} DROP NOT NULL";
		}
		if ($uns) {
			$res[] = "ADD CONSTRAINT {$name} $uns";
		}
		return $res;
	}

	public function remove_index($table, $index)
	{
		return "DROP INDEX \"{$index['name']}\" ON \"$table\"";
	}

	public function add_index($table, $index)
	{
		$type = $this->process_index_type($index);
		$name = $index['name'];
		$columns = $this->process_index_cols($index);
		if ($index['type'] == 'fulltext') {
			return "CREATE INDEX {$name} ON \"{$table}\" USING gin(to_tsvector({$columns})";
		} else if ($type == 'INDEX') {
			$type = '';
		}
		if (trim($type) == 'PRIMARY KEY') {
			return "ALTER TABLE  \"{$table}\" ADD PRIMARY KEY ({$columns})";
		}
		return "CREATE {$type} INDEX {$name} ON \"{$table}\" ({$columns})";
	}

	public function alter_table($table, $actions)
	{
		$actions_sql = implode(', ', $actions);
		return "ALTER TABLE \"{$table['name']}\" $actions_sql";
	}

	public function create_table($table, $defs)
	{
		$defs = array_filter($defs);
		$sql = sprintf("CREATE TABLE \"%s\" (%s)", $table['name'], implode(', ', $defs));
		return $sql;
	}

	protected function get_name($column)
	{
		return '"' . $column['name'] . '" ';
	}

	public function map_column($column)
	{
		$column['pgsql_type'] = $this->map_type($column);
		return $column;
	}

	public function is_serial_column($column)
	{
		return $column['type'] == 'serial' || $column['type'] == 'bigserial';
	}

	public function map_type($column, $for_inspect = false)
	{
		if (!empty($column['pgsql_type'])) {
			return $column['pgsq_type'];
		}
		if ($for_inspect && $this->is_serial_column($column)) {
			if ($column['type'] == 'bigserial') {
				$column['size'] = 'big';
			}
			$column['type'] = 'int';
		}
		if (isset($column['precision'])) {
			return 'NUMERIC';
		}
		$key = Core_Strings::contains(':', $column['type']) ? $column['type'] :
			Core_Strings::downcase($column['type']) . ':' . (isset($column['size']) ? $column['size'] : 'normal');
		$type = self::$type_map[$key];
		return $type;
	}

	public function map_column_default($column)
	{
		if (Core_Strings::starts_with(trim($column['default'], "'"), '0000-00-00')) {
			return null;
		}
		return isset($column['default']) ? $column['default'] : null;
	}

	public function index_exists_query($data, $index)
	{
		$table = $data['name'];
		$name = $index['name'];
		if (isset($index['type']) && $index['type'] == 'primary key') {
			return $this->sql->primary_key_exists($table);
		}
		return $this->sql->index_exists($table, $name);
	}

	protected function process_index_type($index)
	{
		$key = '';
		$type = isset($index['type']) ? $index['type'] : '';
		switch ($type) {
			case 'primary key':
				$key = 'PRIMARY KEY ';
				break;
			case 'unique':
				$key = 'UNIQUE ';
				break;
			default:
				$key = 'INDEX';
				break;
		}
		return $key;
	}

	protected function process_index_cols($index)
	{
		$res = array();
		foreach ($index['columns'] as $c) {
			if (is_array($c)) {
				$res[] = '"' . $c[0] . '"';
			} else {
				$res[] = '"' . $c . '"';
			}
		}
		return implode(', ', $res);
	}

	static public function get_columns_type_map()
	{
		return self::$type_map;
	}

	static public function get_flip_columns_type_map()
	{
		return array_flip(self::$type_map);
	}

	static public function flip_column($type)
	{
		$map = self::get_flip_columns_type_map();
		$type = Core_Strings::upcase($type);
		$res = array(null, null);
		if (isset($map[$type])) {
			$res = explode(':', $map[$type]);
		}
		return $res;
	}

	protected function inspect_columns($mapper)
	{
		$columns = array();
		foreach ($mapper->as_array() as $r) {
			$col = array();
			$col['name'] = $r['column_name'];
			if (!empty($r['check_clause']) && preg_match("!{$col['name']}\s>=?\s0!i", $r['check_clause'])) {
				$col['unsigned'] = true;
			}
			if (isset($columns[$col['name']])) {
				$columns[$col['name']] = array_merge($columns[$col['name']], $col);
				continue;
			}
			$col['type'] = $r['data_type'];
			list($col['type'], $col['size']) = self::flip_column($col['type']);
			$col['not null'] = ($r['is_nullable'] == 'YES' ? false : true);
			$default = $r['column_default'];
			if (!is_null($default)) {
				if (Core_Strings::starts_with($default, 'nextval')) {
					$col['type'] = 'serial';
				} else {
					$col['default'] = preg_replace('!::.*!i', '', $default);
				}
			}
			if (!empty($r['character_maximum_length'])) {
				$col['length'] = $r['character_maximum_length'];
			}
			if (Core_Strings::downcase($r['data_type']) == 'numeric') {
				$col['type'] = 'float';
				$col['size'] = 'normal';
				$col['precision'] = $r['numeric_precision'];
				$col['scale'] = $r['numeric_scale'];
			}
			$columns[$col['name']] = $col;
		}
		return $columns;
	}

	protected function inspect_indexes($table_name, $dsn)
	{
		$connection = WS::env()->orm->session->connection_for($table_name);
		$sql = $this->sql->inspect_indexes($table_name);
		$indexes = array();
		foreach ($connection->prepare($sql) as $r) {
			$name = $r['index_name'];
			$columns = (array) $r['index_keys'];
			$type = 'index';
			if ($r['is_primary']) {
				$type = 'primary key';
			} else if ($r['is_unique']) {
				$type = 'UNIQUE';
			}
			//TODO: fulltext
			if (!isset($indexes[$name])) {
				$indexes[$name] = array('columns' => $columns, 'type' => $type, 'name' => $name);
			}
		}
		return $indexes;
	}

	public function inspect_columns_mapper($info_mapper, $table_name, $dsn)
	{
		return $info_mapper->columns->spawn()->only('data_type', 'column_name', 'column_default',
			'character_maximum_length', 'is_nullable', 'numeric_scale', 'numeric_precision', 'numeric_precision_radix',
			 'ordinal_position', 'datetime_precision', 'constraint_name', 'check_clause')
		->calculate('cu.constraint_name')
		->calculate('cc.check_clause')
		->join('left', 'information_schema.constraint_column_usage as cu', 'cu.column_name=columns.column_name and cu.table_name=columns.table_name')
		->join('left', 'information_schema.check_constraints as cc', 'cc.constraint_name=cu.constraint_name and cc.constraint_catalog=cu.table_catalog')
		->where('information_schema.columns.table_catalog = :scheme', $dsn->database)
		->where('information_schema.columns.table_name = :table', $table_name)
		->order_by('ordinal_position')
		;
	}

	public function inspect($info_mapper, $table_name, $dsn)
	{
		$table = array();
		
		$table['columns'] = $this->inspect_columns($this->inspect_columns_mapper($info_mapper, $table_name, $dsn));
		$table['indexes'] = $this->inspect_indexes($table_name, $dsn);

		return $table;
	}
}


class DB_Adapter_PostgreSQL_Schema_SQL
{
	public function inspect_indexes($table_name)
	{
		return
<<<SQL
SELECT
  ns.nspname               AS schema_name,
  idx.indrelid :: REGCLASS AS table_name,
  i.relname                AS index_name,
  idx.indisunique          AS is_unique,
  idx.indisprimary         AS is_primary,
  am.amname                AS index_type,
  idx.indkey,
       ARRAY(
           SELECT pg_get_indexdef(idx.indexrelid, k + 1, TRUE)
           FROM
             generate_subscripts(idx.indkey, 1) AS k
           ORDER BY k
       ) AS index_keys,
  (idx.indexprs IS NOT NULL) OR (idx.indkey :: int[] @> array[0]) AS is_functional,
  idx.indpred IS NOT NULL AS is_partial
FROM pg_index AS idx
  JOIN pg_class AS i
    ON i.oid = idx.indexrelid
  JOIN pg_am AS am
    ON i.relam = am.oid
  JOIN pg_namespace AS NS ON i.relnamespace = NS.OID
WHERE NOT nspname LIKE 'pg%' AND idx.indrelid = '{$table_name}' :: REGCLASS;
SQL;
	}

	public function index_exists($table, $name)
	{
		return
<<<SQL
 select
    t.relname as table_name,
    i.relname as index_name,
    a.attname as column_name
from
    pg_class t,
    pg_class i,
    pg_index ix,
    pg_attribute a
where
    t.oid = ix.indrelid
    and i.oid = ix.indexrelid
    and a.attrelid = t.oid
    and a.attnum = ANY(ix.indkey)
    and t.relkind = 'r'
    and t.relname = '{$table}'
    and i.relname = '{$name}'
order by
    t.relname,
    i.relname;
SQL;
	}

	public function primary_key_exists($table)
	{
		return
<<<SQL
SELECT  t.table_catalog, 
         t.table_schema, 
         t.table_name, 
         kcu.constraint_name, 
         kcu.column_name, 
         kcu.ordinal_position 
FROM    INFORMATION_SCHEMA.TABLES t 
         LEFT JOIN INFORMATION_SCHEMA.TABLE_CONSTRAINTS tc 
                 ON tc.table_catalog = t.table_catalog 
                 AND tc.table_schema = t.table_schema 
                 AND tc.table_name = t.table_name 
                 AND tc.constraint_type = 'PRIMARY KEY' 
         LEFT JOIN INFORMATION_SCHEMA.KEY_COLUMN_USAGE kcu 
                 ON kcu.table_catalog = tc.table_catalog 
                 AND kcu.table_schema = tc.table_schema 
                 AND kcu.table_name = tc.table_name 
                 AND kcu.constraint_name = tc.constraint_name 
WHERE   t.table_schema NOT IN ('pg_catalog', 'information_schema') AND t.table_name ='{$table}' 
ORDER BY t.table_catalog, 
         t.table_schema, 
         t.table_name, 
         kcu.constraint_name, 
         kcu.ordinal_position;
SQL;
	}
}