<?php
/**
 * Search.Sphinx
 *
 * Модуль предоставляющий интерфейс для доступа к полнотекстовому поисковому движку Sphinx
 *
 * За болеее подробной информацией обращайтесь к документации Sphinx
 *
 * @package Search\Sphinx
 * @version 0.2.2
 */

Core::load('Object');

/**
 * @package Search\Sphinx
 */
class Search_Sphinx implements Core_ModuleInterface
{

	const VERSION = '0.2.2';

	const DEFAULT_RANGE = 20;

	/**
	 * Фабричный метод, возвращает объект класса Search.Sphinx.Client
	 *
	 * @param string $dsn
	 * @param int    $mode
	 *
	 * @return Search_Sphinx_Client
	 */
	static public function Client($dsn = 'sphinx://localhost:3312', $mode = SPH_MATCH_EXTENDED)
	{
		return new Search_Sphinx_Client($dsn, $mode);
	}

	/**
	 * Фабричный метод. возвращает объект класса Search.Sphinx.Query
	 *
	 * @param Search_Sphinx_Client $client
	 * @param string               $expression
	 *
	 * @return Search_Sphinx_Query
	 */
	static public function Query(Search_Sphinx_Client $client, $expression)
	{
		return new Search_Sphinx_Query($client, $expression);
	}

}

/**
 * Интерфейс для перевода результата в требуемый формат
 *
 * @package Search\Sphinx
 */
interface Search_Sphinx_ResolverInterface
{

	/**
	 * Возвращает набор объектов по входному результату поиска Sphinx
	 *
	 * @param array $matches
	 *
	 * @return mixed
	 */
	public function load(array $matches);
}

/**
 * Клиент для обращения к Sphinx
 *
 * @package Search\Sphinx
 */
class Search_Sphinx_Client implements Core_CallInterface
{

	private $client;

	/**
	 * Конструктор
	 *
	 * @param string $dsn
	 * @param int    $mode
	 */
	public function __construct($dsn = 'sphinx://localhost:3312', $mode = SPH_MATCH_EXTENDED)
	{
		if (!($m = Core_Regexps::match_with_results('{^sphinx://([a-zA-Z./]+)(:?:(\d+))?}', (string)$dsn))) {
			throw new Search_Sphinx_Exception("Bad DSN $dsn");
		}

		$this->client = new SphinxClient();

		$this->catch_errors(
			($this->client->SetServer($m[1], (int)Core::if_not($m[3], 3312)) !== false) &&
			($this->client->setMatchMode($mode) !== false) &&
			($this->client->setArrayResult(true) !== false)
		);
	}

	/**
	 * Сбрасывает настройки
	 *
	 * @return Search_Sphinx_Client
	 */
	public function reset()
	{
		return $this->catch_errors(
			($this->client->ResetFilters() !== false) &&
			($this->client->ResetGroupBy() !== false) &&
			($this->client->SetLimits(0, Search_Sphinx::DEFAULT_RANGE) !== false) &&
			($this->client->SetSortMode(SPH_SORT_RELEVANCE) !== false)
		);
	}

	/**
	 * задает интервал поиска
	 *
	 * @param int $limit
	 * @param int $offset
	 */
	public function range($limit, $offset = 0)
	{
		return $this->catch_errors(
			$this->client->SetLimits($offset, $limit) !== false
		);
	}

	/**
	 * Группирует элементы поиска
	 *
	 * @param string $attribute
	 * @param int    $func
	 * @param string $group_sort
	 *
	 * @return Search_Sphinx_Client
	 */
	public function group_by($attribute, $func = SPH_GROUPBY_ATTR, $group_sort = '@group desc')
	{
		return $this->catch_errors($this->client->SetGroupBy($attribute, $func, $group_sort) !== false);
	}

	/**
	 * Устанавливает фильтр поиска
	 *
	 * @param string  $attribute
	 * @param         $value
	 * @param boolean $exclude
	 *
	 * @return Search_Sphinx_Client
	 */
	public function filter($attribute, $values, $exclude = false)
	{
		return $this->catch_errors($this->client->SetFilter($attribute, (array)$values, $exclude) !== false);
	}

	/**
	 * Устанавливает фильтер поиска
	 *
	 * @param string  $attribute
	 * @param int     $min
	 * @param int     $max
	 * @param boolean $exclude
	 *
	 * @return Search_Sphinx_Client
	 */
	public function where($attribute, $min, $max, $exclude = false)
	{
		return $this->catch_errors($this->client->SetFilterRange($attribute, $min, $max, $exclude) !== false);
	}

	/**
	 * Задает интервал поиска
	 *
	 * @param int $min
	 * @param int $max
	 *
	 * @return Search_Sphinx_Client
	 */
	public function between($min, $max)
	{
		return $this->catch_errors($this->client->SetIDRange($min, $max) !== false);
	}

	/**
	 * Устанавливает сортировку результата
	 *
	 * @param string $mode
	 * @param string $expr
	 *
	 * @return Search_Sphinx_Client
	 */
	public function sort_by($mode, $expr = '')
	{
		return $this->catch_errors($this->client->SetSortMode($mode, $expr) !== false);
	}

	/**
	 * Выполняет запрос поиска и возвращает объек Search.Sphinx.Results с результатами
	 *
	 * @param string                          $expression
	 * @param string                          $index
	 * @param Search_Sphinx_ResolverInterface $resolver
	 *
	 * @return Search_Sphinx_Results
	 */
	public function query($expression, $index = '*', Search_Sphinx_ResolverInterface $resolver = null)
	{

		$r = $this->client->Query($expression, $index);

		return $r ?
			($resolver ?
				new Search_Sphinx_Results($r, $resolver) :
				new Search_Sphinx_Results($r)) :
			new Search_Sphinx_Results(array(
					'matches' => array(),
					'total' => 0,
					'total_found' => 0,
					'words' => array(),
					'warning' => $this->client->GetLastWarning(),
					'error' => $this->client->GetLastError())
			);
	}

	/**
	 * Возвращает объект Search.Sphinx.Query для задания условий поиска
	 *
	 * @param string $expression
	 *
	 * @return Search_Sphinx_Query
	 */
//TODO: from sss: может поменять названия методов select и query местами,
//                а то не логично, что select возвращает Query объект
	public function select($expression)
	{
		return new Search_Sphinx_Query($this, $expression);
	}

	/**
	 * Выбрасывает исключение в случае ошибки
	 *
	 * @param boolean $rc
	 *
	 * @return Search_Sphinx_Client
	 */
	private function catch_errors($rc)
	{
		if (!$rc) {
			throw new Search_Sphinx_Exception($this->client->GetLastError());
		}
		return $this;
	}

	/**
	 * Перенаправляет вызовы в стандартный client с учетом CameCase
	 *
	 * @param string $method
	 * @param array  $args
	 */
	public function __call($method, $args)
	{
		return call_user_func_array(array($this->client, Core_Strings::to_camel_case($method)), $args);
	}

}

/**
 * Обертка над результатом поиска Sphinx
 *
 * @package Search\Sphinx
 */
class Search_Sphinx_Results
	implements Core_PropertyAccessInterface,
	IteratorAggregate,
	Core_CountInterface,
	Core_IndexedAccessInterface
{

	protected $documents = array();

	protected $execution_time;
	protected $total;
	protected $total_found;
	protected $error;
	protected $warning;

	/**
	 * Конструктор
	 *
	 * @param array                  $result
	 * @param Search_Sphinx_Resolver $resolver
	 */
	public function __construct(array $results, Search_Sphinx_ResolverInterface $resolver = null)
	{
		foreach ($results as $k => $v) {
			switch ($k) {
				case 'execution_time':
				case 'total':
				case 'total_found':
				case 'error':
				case 'warning':
					$this->$k = $v;
			}
		}

		if (isset($results['matches'])) {
			$entities = $resolver ? $resolver->load($results['matches']) : null;
			foreach ($results['matches'] as $k => $v) {
				$this->append($v['id'], $v['attrs'], $v['weight'], $entities ? $entities[$v['id']] : null);
			}
		}
	}

	/**
	 * Возвращает итератор
	 *
	 */
	public function getIterator()
	{
		return new ArrayIterator($this->documents);
	}

	/**
	 * Возвращает количество найденых документов
	 *
	 * @return int
	 */
	public function count()
	{
		return count($this->documents);
	}

	/**
	 * Добавляет новый документ к результату
	 *
	 * @param int $id
	 * @param     $attrs
	 * @param     $weight
	 * @param     $entity
	 *
	 * @return Search_Sphinx_Results
	 */
	public function append($id, $attrs, $weight, $entity = null)
	{
		$attrs = array('sid' => $id, 'attrs' => $attrs, 'weight' => $weight);
		$this->documents[] = $entity ? Object::Wrapper($entity, $attrs) : Core::object($attrs);
	}

	/**
	 * Возвращает документ
	 *
	 * @param int $index
	 *
	 * @return mixed
	 */
	public function offsetGet($index)
	{
		return Core::if_not_set($this->documents, $index, null);
	}

	/**
	 * Выкидывает исключение
	 *
	 * @param  $index
	 * @param  $value
	 *
	 * @return mixed
	 */
	public function offsetSet($index, $value)
	{
		throw new Core_ReadOnlyObjectException($this);
	}

	/**
	 * Проверяет есть ли документ с таким идентификатором
	 *
	 * @param  $index
	 *
	 * @return boolean
	 */
	public function offsetExists($index)
	{
		return isset($this->documents[$index]);
	}

	/**
	 * Выбрасывает исключение
	 *
	 * @param  $index
	 */
	public function offsetUnset($index)
	{
		throw new Core_ReadOnlyObjectException($this);
	}

	/**
	 * Доступ на чтение к свойствам объекта
	 *
	 * @param string $property
	 *
	 * @return mixed
	 */
	public function __get($property)
	{
		switch ($property) {
			case 'execution_time':
			case 'total':
			case 'total_found':
			case 'error':
			case 'warning':
				return $this->$property;
			case 'entities':
				return $this->get_entities();
			default:
				throw new Core_MissingPropertyException($property);
		}
	}

	/**
	 * Доступ на запись к свойствам объекта
	 *
	 * @param string $property
	 * @param        $value
	 *
	 * @return mixed
	 */
	public function __set($property, $value)
	{
		throw new Core_ReadOnlyObjectException($this);
	}

	/**
	 * Проверяет установленно ли свойтсво
	 *
	 * @param string $property
	 *
	 * @return boolean
	 */
	public function __isset($property)
	{
		switch ($property) {
			case 'execution_time':
			case 'total':
			case 'total_found':
			case 'error':
			case 'warning':
				return isset($this->$property);
			case 'entities':
				return true;
			default:
				return false;
		}
	}

	/**
	 * Очищает свойство объекта
	 *
	 * @param string $property
	 */
	public function __unset($property)
	{
		throw new Core_ReadOnlyObjectException($this);
	}

	/**
	 * @return ArrayObject
	 */
	protected function get_entities()
	{
		$r = new ArrayObject();
		foreach ($this->documents as $k => $d)
			$r[$k] = $d->__object;
		return $r;
	}

}

/**
 * Класс содержащий настройки поиска, и позволяющий их задавать
 *
 * @package Search\Sphinx
 */
class Search_Sphinx_Query implements IteratorAggregate, Core_PropertyAccessInterface
{

	protected $options;

	protected $expression;
	protected $client;

	protected $resolver;

	/**
	 * Конструктор
	 *
	 * @param Search_Sphinx_Client $client
	 * @param string               $expression
	 */
	public function __construct(Search_Sphinx_Client $client, $expression)
	{
		$this->client = $client;
		$this->expression = (string)$expression;
	}

	/**
	 * @param string $property
	 *
	 * @return mixed
	 */
	public function __get($property)
	{
		switch ($property) {
			case 'options':
			case 'client':
			case 'expression':
			case 'resolver':
				return $this->$property;
			default:
				throw new Core_MissingPropertyException($property);
		}
	}

	/**
	 * @param string $property
	 * @param        $value
	 *
	 * @return mixed
	 */
	public function __set($property, $value)
	{
		throw new Core_ReadOnlyObjectException($this);
	}

	/**
	 * @param string $property
	 *
	 * @return boolean
	 */
	public function __isset($property)
	{
		switch ($property) {
			case 'options':
			case 'client':
			case 'expression':
			case 'resolver':
				return (boolean)$this->$property;
			default:
				return false;
		}
	}

	/**
	 * @param string $property
	 */
	public function __unset($property)
	{
		throw new Core_ReadOnlyObjectException($this);
	}

	/**
	 * Возвращает итератор по результату поиска
	 *
	 */
	public function getIterator()
	{
		return $this->execute()->getIterator();
	}

	/**
	 * Устанавливает индексы поиска
	 *
	 * @param string $indexes
	 *
	 * @return Search_Sphinx_Query
	 */
	public function using($indexes)
	{
		$this->options['indexes'] = (string)$indexes;
		return $this;
	}

	/**
	 * задает интервал поиска
	 *
	 * @param int $limit
	 * @param int $offset
	 *
	 * @return Search_Sphinx_Query
	 */
	public function range($limit, $offset = 0)
	{
		$this->options['range'] = array($limit, $offset);
		return $this;
	}

	/**
	 * Группирует элементы поиска
	 *
	 * @param string $attribute
	 * @param int    $func
	 * @param string $group_sort
	 *
	 * @return Search_Sphinx_Query
	 */
	public function group_by($attribute, $func = SPH_GROUPBY_ATTR, $group_sort = '@group desc')
	{
		$this->options['group_by'][] = array($attribute, $func, $group_sort);
		return $this;
	}

	/**
	 * Устанавливает фильтр поиска
	 *
	 * @param string  $attribute
	 * @param         $value
	 * @param boolean $exclude
	 *
	 * @return Search_Sphinx_Client
	 */
	public function filter($attribute, $values, $exclude = false)
	{
		$this->options['filter'][] = array($attribute, $values, $exclude);
		return $this;
	}

	/**
	 * Устанавливает фильтер поиска
	 *
	 * @param string  $attribute
	 * @param int     $min
	 * @param int     $max
	 * @param boolean $exclude
	 *
	 * @return Search_Sphinx_Client
	 */
	public function where($attribute, $min, $max, $exclude = false)
	{
		$this->options['where'][] = array($attribute, $min, $max, $exclude);
		return $this;
	}

	/**
	 * Задает интервал поиска
	 *
	 * @param int $min
	 * @param int $max
	 *
	 * @return Search_Sphinx_Client
	 */
	public function between($min, $max)
	{
		$this->options['between'] = array($min, $max);
		return $this;
	}

	/**
	 * Устанавливает сортировку результата
	 *
	 * @param string $mode
	 * @param string $expr
	 *
	 * @return Search_Sphinx_Client
	 */
	public function sort_by($mode, $expr = '')
	{
		$this->options['sort_by'] = array($mode, $expr);
		return $this;
	}

	/**
	 * Устанавливает ресолвер
	 *
	 * @param Search_Sphinx_ResolverInterface $resolver
	 *
	 * @return Search_Sphinx_Query
	 */
	public function resolve_with(Search_Sphinx_ResolverInterface $resolver)
	{
		$this->resolver = $resolver;
		return $this;
	}

	/**
	 * Обнуляет ресолвер
	 *
	 * @return Search_Sphinx_Query
	 */
	public function without_resolver()
	{
		$this->resolver = null;
		return $this;
	}



	/**
	 * Выполняет поиск
	 *
	 * @return Search_Sphinx_Results
	 */
//TODO: Здесь наверное тоже стоит обернуть все в $this->client->catch_errors
	public function execute()
	{

		$this->client->reset();

		if (isset($this->options['range'])) {
			$this->client->range($this->options['range'][0], $this->options['range'][1]);
		}

		if (isset($this->options['where'])) {
			foreach ($this->options['where'] as $cond)
				$this->client->where($cond[0], $cond[1], $cond[2], $cond[3]);
		}

		if (isset($this->options['filter'])) {
			foreach ($this->options['filter'] as $cond)
				$this->client->filter($cond[0], $cond[1], $cond[2]);
		}

		if (isset($this->options['group_by'])) {
			foreach ($this->options['group_by'] as $expr)
				$this->client->group_by($expr[0], $expr[1], $expr[2]);
		}

		if (isset($this->options['between'])) {
			$this->client->between($this->options['between'][0], $this->options['between'][1]);
		}

		if (isset($this->options['sort_by'])) {
			$this->client->sort_by($this->options['sort_by'][0], $this->options['sort_by'][1]);
		}

		return $this->resolver ?
			$this->client->query($this->expression, Core::if_not_set($this->options, 'indexes', '*'), $this->resolver) :
			$this->client->query($this->expression, Core::if_not_set($this->options, 'indexes', '*'));
	}

}

