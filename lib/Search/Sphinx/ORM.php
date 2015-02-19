<?php
/**
 * Search.Sphinx.ORM
 *
 * Модуль служит для связи Sphinx и SB.ORM
 *
 * @package Search\Sphinx\ORM
 * @version 0.2.1
 */
Core::load('DB.ORM', 'Search.Sphinx');

/**
 * @package Search\Sphinx\ORM
 */
class Search_Sphinx_ORM implements Core_ModuleInterface
{

	const VERSION = '0.2.1';

	/**
	 * Фабричный метод, возвращает объект класса Search.Sphinx.ORM.Resolver
	 *
	 * @return Search_Sphinx_ORM_Resolver
	 */
	static public function Resolver()
	{
		return new Search_Sphinx_ORM_Resolver();
	}

}

/**
 * Класс исключения
 *
 * @package Search\Sphinx\ORM
 */
class Search_Sphinx_ORM_Exception extends Core_Exception
{
}

/**
 * Ресолвер связывающий результат поиска и DB.ORM
 *
 * @package Search\Sphinx\ORM
 */
class Search_Sphinx_ORM_Resolver implements Search_Sphinx_ResolverInterface
{

	protected $dimension = 0;
	protected $mappers = array();

	/**
	 * Конструктор
	 *
	 */
	public function __construct()
	{
		$this->setup();
	}

	/**
	 */
	protected function setup()
	{
	}

	/**
	 * Устанавливает набор мапперов
	 *
	 * @return Search_Sphinx_ORM_Resolver
	 */
	public function mappers()
	{
		foreach (Core::normalize_args(func_get_args()) as $k => $mapper)
			if ($mapper instanceof DB_ORM_Mapper) {
				$this->mappers[$k] = $mapper;
			}
		return $this;
	}

	public function dimension($dimension)
	{
		$this->dimension = $dimension;
		return $this;
	}

	/**
	 * Возвращает сущности соответствующие установленным мапперам
	 *
	 * @param Search_Sphinx_ResultSet $result_set
	 *
	 * @return Data_Hash
	 */
	public function load(array $matches)
	{

		$parts = array();
		$num_of_mappers = Core::if_not($this->dimension, count($this->mappers));

		foreach ($matches as $match) {
			$class_id = (int)Core::if_not_set($match['attrs'], '_class', 0);
			if (isset($this->mappers[$class_id])) {
				$parts[$class_id][] = ($match['id'] - $class_id) / $num_of_mappers;
			}
		}

		$result = array();

		foreach ($parts as $class_id => $ids) {
			$id_field = $this->mappers[$class_id]->options['key'][0];

			foreach (
				$this->mappers[$class_id]->spawn()->
					where($this->mappers[$class_id]->options['table_prefix'] . '.' . $id_field . ' IN (' . implode(',', $ids) . ')') as $v)
				$result[$class_id + $v[$id_field] * $num_of_mappers] = $v;
		}

		return $result;
	}

}

