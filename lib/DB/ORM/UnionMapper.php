<?php
/**
 * Класс реализующий маппер union запроса
 */
class DB_ORM_UnionMapper extends DB_ORM_SQLMapper implements Core_ModuleInterface
{
	protected $entity_class = '';

	/**
	 * Добавляет маппер в union
	 * 
	 * @param $mapper DB_ORM_SQLMapper маппер
	 * @param $key mixed любое значение допустимое для ключа массива.
	 * 		  В дальнейшем по этому ключу можно будет заменить маппер.
	 */
	public function union($mapper, $key = null)
	{
		if (is_array($mapper)) {
			foreach($mapper as $key => $map) {
				$this->union($map, is_int($key) ? null : $key);
			}
		} else {
			$mapper = $this->prepare_union_mapper($mapper);
			$this->options->union($mapper, $key);
		}

		return $this;
	}

	/**
	 * 
	 */
	public function sql()
	{
		$table_from = $this->make_union_sql() .' '. $this->options['table'][0];
		$this->options->aliased_table($table_from);

		return parent::sql();
	}

	/**
	 * Делегирование не найденых методов union маппера.
	 * В основном используется для map_ методов union мапперов
	 */
	public function __call($method, $args)
	{
		try {
			return parent::__call($method, $args);
		} catch(Core_MissingMethodException $e) {}

		foreach($this->options['union'] as $key => $mapper) {
			$mapper = call_user_func_array(array($mapper, $method), $args);
			$this->union($mapper, $key);
		}


		return $this;
	}

	/**
	 * 
	 */
	protected function setup()
	{
		if (empty($this->entity_class)) {
			$this->entity_class = DB::option('collection_class');
		}

		return $this
			->classname($this->entity_class)
			->table('union_result')
			->union_setup();
	}

	/**
	 * 
	 */
	protected function union_setup()
	{
		return $this;
	}

	/**
	 * 
	 */
	protected function prepare_union_mapper($mapper)
	{
		$root_mapper = $mapper->clear();
		$root_mapper->copy_options_from($mapper, array('union', 'order_by', 'range'));
		$root_mapper->binds = $mapper->__get('binds');

		unset(
			$root_mapper->options['order_by'],
			$root_mapper->options['range']
		);

		return $root_mapper;
	}

	/**
	 * 
	 */
	protected function make_union_sql()
	{
		$queries   = '';
		$mappers = $this->options['union'];

		$index = 0;
		foreach($mappers as $mapper) {
			$mapper_binds = $mapper->__get('binds');
			$mapper_query = $mapper->as_string();

			foreach($mapper_binds as $bind_name => $bind_value) {
				$mapper_query = Core_Regexps::replace(
					'{\:'. Core_Regexps::quote($bind_name) .'\b}',
					':'. ($bind_name = sprintf('union%d_%s', $index, $bind_name)),
					$mapper_query
				);

				$this->binds[$bind_name] = $bind_value;
			}

			$queries[] = $mapper_query;
			$index++;
		}

		return '(('. implode(') UNION (', $queries) .'))';
	}

	public function update() 
	{
		throw new Core_MissingMethodException('update');
	}
	
	public function update_all() 
	{
		throw new Core_MissingMethodException('update_all');
		
	}
	
	public function insert() 
	{
		throw new Core_MissingMethodException('insert');
	}
	
	public function delete() 
	{
		throw new Core_MissingMethodException('delete');
	}

	public function delete_all() 
	{
		throw new Core_MissingMethodException('delete_all');
	}
}
