<?php
/**
 * Data.Pagination
 *
 * Позволяет разбивать данные постранично
 *
 * @package Data\Pagination
 * @version 0.2.0
 */

/**
 * @package Data\Pagination
 */
class Data_Pagination implements Core_ModuleInterface
{

	const VERSION = '0.2.1';

	const DEFAULT_ITEMS_PER_PAGE = 10;
	const DEFAULT_PADDING = 2;

	/**
	 * Фабричный метод, возвращает объект класаа Data.Pagination.Pager
	 *
	 * @param int $num_of_items
	 * @param int $current_page
	 * @param int $items_per_page
	 *
	 * @return Data_Pagination_Pager
	 */
	static public function Pager($num_of_items, $current_page = 1, $items_per_page = self::DEFAULT_ITEMS_PER_PAGE)
	{
		return new Data_Pagination_Pager($num_of_items, $current_page, $items_per_page);
	}

}

/**
 * Разбивает данный постранично
 *
 * @package Data\Pagination
 */
class Data_Pagination_Pager
	implements Core_PropertyAccessInterface,
	Core_IndexedAccessInterface,
	Core_CountInterface
{

	protected $num_of_items = 0;
	protected $items_per_page = 10;
	protected $num_of_pages = 1;

	protected $current_page = 1;
	protected $pages;

	/**
	 * Конструктор
	 *
	 * @param int $num_of_items
	 * @param int $current_page
	 * @param int $items_per_page
	 */
	public function __construct($num_of_items, $current_page = 1, $items_per_page = self::ITEMS_PER_PAGE)
	{
		$this->pages = new ArrayObject();
		$this->num_of_items = $num_of_items;
		$this->items_per_page = $items_per_page;

		$this->num_of_pages = $this->num_of_items == 0 ? 1 :
			(int)($this->num_of_items / $this->items_per_page) +
			(($this->num_of_items % $this->items_per_page) ? 1 : 0);

		$this->current_page = ((int)$current_page > 0 && (int)$current_page <= $this->num_of_pages) ?
			(int)$current_page : 1;
	}

	/**
	 * Индексный доступ к свойствам объекта
	 *
	 * @param int $index
	 *
	 * @return Data_Pagination_Page
	 */
	public function offsetGet($index)
	{
		$index = (int)$index;
		if (isset($this->pages[$index])) {
			return $this->pages[$index];
		}
		if ($index > 0 && $index <= $this->num_of_pages) {
			return $this->pages[$index] = new Data_Pagination_Page($this, $index);
		} else {
			return null;
		}
	}

	/**
	 * Проверяет существует ли страница с номером $index
	 *
	 * @param int $index
	 *
	 * @return boolean
	 */
	public function offsetExists($index)
	{
		$index = (int)$index;
		return $index > 0 && $index <= $this->num_of_pages;
	}

	/**
	 * Выбрасывает исключение
	 *
	 * @param int $index
	 * @param     $value
	 */
	public function offsetSet($index, $value)
	{
		throw $this->offsetExists($index) ?
			new Core_ReadOnlyIndexedPropertyException($index) :
			new Core_MissingIndexedPropertyException($index);
	}

	/**
	 * Выбрасывает исключение
	 *
	 * @param int $index
	 */
	public function offsetUnset($index)
	{
		throw $this->offsetExists($index) ?
			new Core_ReadOnlyIndexedPropertyException($index) :
			new Core_MissingIndexedPropertyException($index);
	}

	/**
	 * Возвращает первую страницу
	 *
	 * @return Data_Pagination_Page
	 */
	public function first()
	{
		return $this[1];
	}

	/**
	 * Возвращает текущую страницу
	 *
	 * @return Data_Pagination_Page
	 */
	public function current()
	{
		return $this[$this->current_page];
	}

	/**
	 * Возвращает последнюю страницу
	 *
	 * @return Data_Pagination_Page
	 */
	public function last()
	{
		return $this[$this->num_of_pages];
	}

	/**
	 * Доступ на чтение к свойствам объекта
	 *
	 * @param string $property
	 */
	public function __get($property)
	{
		switch ($property) {
			case 'num_of_items':
			case 'num_of_pages':
			case 'items_per_page':
				return $this->$property;
			case 'length':
				return count($this->pages);
			case 'last':
			case 'first':
			case 'current':
				return $this->$property();
			default:
				throw new Core_MissingPropertyException($property);
		}
	}

	/**
	 * Выбрасывает исключение
	 *
	 * @param string $property
	 * @param        $value
	 */
	public function __set($property, $value)
	{
		throw $this->__isset($property) ?
			new Core_ReadOnlyPropertyException($property) :
			new Core_MissingPropertyException($property);
	}

	/**
	 * Проверяет установленно ли свойство с именем $property
	 *
	 * @param string $property
	 *
	 * @return boolean
	 */
	public function __isset($property)
	{
		switch ($property) {
			case 'num_of_items':
			case 'items_per_page':
			case 'num_of_pages':
			case 'length':
			case 'last':
			case 'first':
			case 'current':
				return true;
			default:
				return false;
		}
	}

	/**
	 * Выбрасывает исключение
	 *
	 * @param string $property
	 */
	public function __unset($property)
	{
		throw $this->__isset($property) ?
			new Core_UndestroyablePropertyException($property) :
			new Core_MissingPropertyException($property);
	}

	/**
	 */
	public function count()
	{
		return count($this->pages);
	}

}

/**
 * Класс представляющий собой страницу
 *
 * @package Data\Pagination
 */
class Data_Pagination_Page implements Core_PropertyAccessInterface, Core_EqualityInterface
{
	protected $pager;
	protected $number;

	/**
	 * Конструктор
	 *
	 * @param Data_Pagination_Pager $pager
	 * @param int                   $number
	 */
	public function __construct(Data_Pagination_Pager $pager, $number)
	{
		$this->pager = $pager;
		$this->number = $number;
	}

	/**
	 * Возвращает сдвиг оносительно первого элемента
	 *
	 * @return number
	 */
	public function offset()
	{
		return $this->pager->items_per_page * ($this->number - 1);
	}

	/**
	 * Возвращает номер первого элемента на странице
	 *
	 * @return number
	 */
	public function first_item()
	{
		return $this->offset() + 1;
	}

	/**
	 * Возвращает индекс последнего элемента на странице
	 *
	 * @return number
	 */
	public function last_item()
	{
		return min($this->pager->items_per_page * $this->number, $this->pager->num_of_items);
	}

	/**
	 * Возвращает следующую страницу
	 *
	 * @return Data_Pagination_Page
	 */
	public function next()
	{
		return $this->is_last() ? $this : $this->pager[$this->number + 1];
	}

	/**
	 * Возвращает предыдущую страницу
	 *
	 * @return Data_Pagination_Page
	 */
	public function previous()
	{
		return $this->is_first() ? $this : $this->pager[$this->number - 1];
	}

	/**
	 * Проверяет является ли данная страница первой
	 *
	 * @return boolean
	 */
	public function is_first()
	{
		return $this->number == $this->pager->first->number;
	}

	/**
	 * Проверяет является ли данная страница последней
	 *
	 * @return boolean
	 */
	function is_last()
	{
		return $this->number == $this->pager->last->number;
	}

	/**
	 * Возвращает окно с заданным отступом ввиде объекта Data.Pagination.Window
	 *
	 * @param int $padding
	 *
	 * @return Data_Pagination_Window
	 */
	public function window($padding = Data_Pagination::DEFAULT_PADDING)
	{
		return new Data_Pagination_Window($this, $padding);
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
			case 'pager':
				return $this->pager;
			case 'first_item':
			case 'last_item':
			case 'offset':
			case 'previous':
			case 'next':
			case 'is_first':
			case 'is_last':
				return $this->$property();
			case 'number':
				return $this->number;
			default:
				throw new Core_MissingPropertyException($property);
		}
	}

	/**
	 * Выбрасывает исключение
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
	 * Проверяет установлено ли свойство с именем $property
	 *
	 * @param string $property
	 *
	 * @return boolean
	 */
	public function __isset($property)
	{
		switch ($property) {
			case 'pager':
			case 'first_item':
			case 'last_item':
			case 'offset':
			case 'previous':
			case 'next':
			case 'number':
			case 'is_first':
			case 'is_last':
				return true;
			default:
				return false;
		}
	}

	/**
	 * Выбрасывает исключение
	 *
	 * @param string $property
	 */
	public function __unset($property)
	{
		throw new Core_ReadOnlyObjectException($this);
	}

	/**
	 * @param Data_Pagination_Page $to
	 *
	 * @return boolean
	 */
	public function equals($to)
	{
		return (
			$to instanceof self &&
			$this->pager === $to->pager &&
			$this->number == $to->number &&
			$this->first_item == $to->first_item &&
			$this->last_item == $to->last_item);
	}

}

/**
 * Класс представлющий собой окно, содержащее несколько страниц
 *
 * Например для пятой страницы окно с отступом 2 будет содержать 3,4,5,6,7 страницы
 *
 * @package Data\Pagination
 */
class Data_Pagination_Window
	implements Core_PropertyAccessInterface, Core_CountInterface
{

	protected $page;
	protected $padding;

	protected $first;
	protected $pages;
	protected $last;

	/**
	 * Конструктор
	 *
	 * @param Data_Pagination_Page $page
	 * @param int                  $padding
	 */
	public function __construct(Data_Pagination_Page $page, $padding = Data_Pagination::DEFAULT_PADDING)
	{
		$this->page = $page;
		$this->set_padding($padding);
	}

	/**
	 * Доступ к свойтсвам объекта на чтение
	 *
	 * @param string $property
	 *
	 * @return mixed
	 */
	public function __get($property)
	{
		switch ($property) {
			case 'first':
			case 'last':
			case 'page':
			case 'padding':
				return $this->$property;
			case 'pager':
				return $this->page->pager;
			case 'pages':
				return $this->get_pages();
			default:
				throw new Core_MissingPropertyException($property);
		}
	}

	/**
	 * Выбрасывает исключение
	 *
	 * @param string $property
	 * @param        $value
	 *
	 * @return mixed
	 */
	public function __set($property, $value)
	{
		switch ($property) {
			case 'first':
			case 'last':
			case 'page':
			case 'pages':
			case 'pager':
				throw new Core_ReadOnlyPropertyException($property);
			case 'padding':
				$this->set_padding((int)$value);
				return $this;
			default:
				throw new Core_MissingPropertyException($property);
		}
	}

	/**
	 * Проверяет установлено ли свойство объекта с именем $property
	 *
	 * @param string $property
	 *
	 * @return boolean
	 */
	public function __isset($property)
	{
		switch ($property) {
			case 'first':
			case 'last':
			case 'page':
			case 'padding':
				return isset($this->$property);
			case 'pages':
			case 'pager':
				return true;
			default:
				return false;
		}
	}

	/**
	 * Выбрасывает исключение
	 *
	 * @param string $property
	 */
	public function __unset($property)
	{
		switch ($property) {
			case 'first':
			case 'last':
			case 'page':
			case 'padding':
			case 'pages':
			case 'pager':
				throw new Core_UndestroyablePropertyException($property);
			default:
				throw new Core_MissingPropertyException($property);
		}
	}

	/**
	 * Устанавливает отступ и просчитывает все остальный параметры
	 *
	 * @param int $padding
	 *
	 * @return Data_Pagination_Window
	 */
	protected function set_padding($padding)
	{
		$this->padding = $padding;
		$this->pages = null;

		$this->first = isset($this->pager[$this->page->number - $this->padding]) ?
			$this->pager[$this->page->number - $this->padding] : $this->pager->first;
		$this->last = isset($this->pager[$this->page->number + $padding]) ?
			$this->pager[$this->page->number + $padding] : $this->pager->last;

		return $this;
	}

	/**
	 * Возвращает ArrayObject все страниц текущего окна
	 *
	 * @return ArrayObject
	 */
	protected function get_pages()
	{
		if (!$this->pages) {
			for ($this->pages = new ArrayObject(), $i = $this->first->number; $i <= $this->last->number; $i++)
				$this->pages[$i] = $this->pager[$i];
		}
		return $this->pages;
	}

	/**
	 */
	public function count()
	{
		return count($this->__get('pages'));
	}

}

