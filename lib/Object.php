<?php
/**
 * Набор утилит для работы с объектами
 *
 * @package Object
 */


/**
 * Класс модуля
 * 
 * @package Object 
 */
class Object implements Core_ModuleInterface {

/**
 * Версия модуля
 */
  const VERSION = '0.2.2';

/**
 * Создает объект класса Object_AttrList
 * 
 * @return  Object_AttrList
 */
public function AttrList() {
	return new Object_AttrList();
}

/**
 * Создает объект класса Object_Listener
 * 
 * @param string $type имя класса или интерфейса
 * @return Object_Listener
 */
  static public function Listener($type = null) {
  	return new Object_Listener($type);
  }

/**
 * Создает объект класса Object_Factory
 * @param string $prefix префис класса
 *
 * @return  Object_Factory
 */
  static public function Factory($prefix = '') {
  	return new Object_Factory($prefix);
  }


/**
 * Создает объект класса Object_Aggregator
 *
 * @return Object_Aggregator
 */
  static public function Aggregator() {
   return new Object_Aggregator();
   }

/**
 * Создает объект класса Object_Wrapper
 * @param object $object Исходный объект
 * @param array  $attrs  Массив расширения
 * @return Object_Wrapper
 */
  static public function Wrapper($object, array $attrs = array()) {
  	return new Object_Wrapper($object, $attrs);
  }

/**
 * Создает объект класс Object_Filter
 * @param mixed $value значение, по которому происходит фильтрация
 * @param string $field имя свойства, которое нужно проверять
 */
  static public function Filter($value, $field = 'group') {
  	return new Object_Filter($value, $field);
  }

}


/**
 * Интерфейс, который должен реализовывать класс имеющий список атрибутов Object_AttrList
 * @package Object 
 */
interface Object_AttrListInterface {

/**
 * Возвращает список атрибутов Object_AttrList
 *
 * В зависимости от параметра $flavor могут возвращаться разные наборы атрибутов
 * @param  mixed $flavor 
 * @return Object_AttrList список атрибутов
 */
  public function __attrs($flavor = null);

}


/** 
 * Базовый класс для классов 
 * 
 * - {@link Object_ObjectAttribute}
 * - {@link Object_CollectionAttribute}
 * - {@link Object_ValueAttribute}
 * 
 *
 * @package Object 
 * 
 */
abstract class Object_Attribute {
	/**
	 * @var string Название атрибута
	 */
	public $name;
  
	/**
	 * Создание атрибута.
	 * 
	 * Опции устанавливается как открытые свойства класса.
	 * 
	 * @param string $name Название атрибута.
	 * @param array $options Дополнительные опции.
	 */
	public function __construct($name, array $options = array()) {
		foreach ($options as $k => $v) $this->$k = $v;
		$this->name = $name;
	}

	/**
	 * Выполняет проверку, является ли коллекция экземпляром Object_ObjectAttribute
	 * 
	 * @return boolean
	 */
	public function is_object() { return $this instanceof Object_ObjectAttribute; }

	/**
	 * Выполняет проверку, является ли коллекция экземпляром Object_ValueAttribute
	 * 
	 * @return boolean
	 */
	public function is_value() { return $this instanceof Object_ValueAttribute; }

	/**
	 * Выполняет проверку, является ли коллекция экземпляром Object_CollectionAttribute
	 * 
	 * @return boolean
	 */
	public function is_collection() { return $this instanceof Object_CollectionAttribute; }
}


/**
 * Обертка вокруг абстрактного класса Object_Attribute
 * 
 * @see Object_Attribute
 * @package Object 
 */
class Object_ObjectAttribute extends Object_Attribute {}

/**
 * Обертка вокруг абстрактного класса Object_Attribute
 * 
 * @see Object_Attribute
 * @package Object 
 */
class Object_CollectionAttribute extends Object_Attribute {}

/**
 * Обертка вокруг абстрактного класса Object_Attribute
 * 
 * @see Object_Attribute
 * @package Object 
 */
class Object_ValueAttribute extends Object_Attribute {}

/**
 * Класс для формирования списка атрибутов
 *
 * Используется например в модуле JSON для преобразования данных
 * 
 * @package Object 
 */
class Object_AttrList implements IteratorAggregate {

/**
 * @var array Массив атрибутов
 */
	protected $attrs = array();
/**
 * @var Object_AttrList Родитель
 */
	protected $parent;

/**
 * Установка родителя.
 * 
 * @param Object_AttrList $parent
 * 
 * @throws Core_InvalidArgumentValueException Если в качестве параметра используется вызывающий объект
 * 
 * @return self
 */
	public function extend(Object_AttrList $parent) {
		if ($this === $parent) {
			throw new Core_InvalidArgumentValueException('parent','this');
		}
		$this->parent = $parent;
		return $this;
	}

/**
 * Добавляет атрибут типа типа Object_ObjectAttribute.
 * 
 * @param string $name Имя атрибута
 * @param string $type Тип данных (имя класса)
 * @param array $options Содержимое коллекции.
 * 
 * @return self
 */
	public function object($name, $type, array $options = array()) {
		foreach ((array) $name as $n)
			$this->attribute(
				new Object_ObjectAttribute(
					$n,
					array_merge($options, array('type' => $type))));
		return $this;
	}

/**
 * Добавляет атрибут типа Object_CollectionAttribute.
 * 
 * Описание типов данных для параметра $item можно посмотреть {@link http://php.net/manual/en/function.settype.php здесь}.
 * 
 * @param string|array $name Имя коллекции или массив имен
 * @param string $items Тип данных в коллекции (имя класса, 'datetime', 'boolean', ... )
 * @param array $options Дополнительные опции.
 * 
 * @throws Core_InvalidArgumentTypeException Если $name не указанного типа.
 * 
 * @return self
 */
	public function collection($name, $items = null, array $options = array()) {
		if ( ! is_string($name) && ! is_array($name) )
		{
			throw new Core_InvalidArgumentTypeException('name', $name);
		}
		
		foreach ((array) $name as $n)
			$this->attribute(
				new Object_CollectionAttribute(
					$n,
					array_merge($options, array('items' => $items))));
		return $this;
	}

/**
 * Создает объект типа Object_ValueAttribute.
 * 
 * @param string $name Имя атрибута
 * @param (string|array) $options 
 * Если $options является строкой - то это тип значения,
 * если $options является массивом - то это опции атрибута.
 * 
 * @return self
 */
	public function value($name, $options = array()) {
		foreach ((array) $name as $n)
			$this->attribute(
				new Object_ValueAttribute(
				$n, is_string($options) ? array('type' => $options) : (array) $options));
		return $this;
	}

/**
 * Добавляет в текущую коллекцию объект типа Object_Attribute
 * 
 * @see Object_Attribute
 * 
 * Ключ - имя атрибута (задается как параметр $name при создании объекта)
 * 
 * @param Object_Attribute $attr
 * 
 * @return self
 */
	protected function attribute(Object_Attribute $attr) {
		$this->attrs[$attr->name] = $attr;
		return $this;
	}

/**
 * Делает объект пригодным для использования через итератор.
 * Если есть родительский объект, то добавляет его как итератор к текущему.
 * 
 * @return AppendIterator
 */
	public function getIterator() {
		$iterator = new AppendIterator();
		if (isset($this->parent)) 
			$iterator->append($this->parent->getIterator());
			
		$iterator->append(new ArrayIterator($this->attrs));
		return $iterator;
	}

}


/**
 * Объект исключения Object_Const
 * 
 * @package Object
 * @deprecated 
 */
class Object_BadConstException extends Core_Exception {

/**
 * @var mixed Значение константы
 */
	protected $value;

/**
 * Конструктор
 * 
 * @param mixed $value значение константы
 */
	public function __construct($value) {
		parent::__construct("Bad constant value: $value");
	}
}

/**
 * Объектное представление константы
 * 
 * @package Object
 * @deprecated
 */
abstract class Object_Const
  implements Core_StringifyInterface, Core_EqualityInterface {

/**
 * @var mixed Значение константы
 */
	protected $value;

/**
 * Конструктор
 * @param mixed $value Значение константы
 */
	protected function __construct($value) {
		$this->value = $value;
	}

/**
 * Возвращает объект по значению
 * @param  mixed $value значение
 * @return object
 */
	abstract static function object($value);

/**
 * Строковое представление константы
 * @return string
 */
	public function as_string() {
		return (string) $this->value;
	}

/**
 * Строковое представление константы
 * 
 * @see self::as_string()
 * @return string
 */
	public function __toString() {
		return $this->as_string();
	}

/**
 * Сравнение двух констант
 * @param  mixed $to
 * @return boolean
 */
	public function equals($to) {
		return ($to instanceof $this) && ($this instanceof $to) && $to->value = $this->value;
	}


/**
 * Доступ к свойствам
 * @param string $property
 * @return mixed
 */
	public function __get($property) {
		switch (true) {
			case $property == 'value':                       return $this->value;
			case property_exists($this, $property):          return $this->$property;
			case method_exists($this, $m = "get_$property"): return $this->$m();
			default:
				throw new Core_MissingPropertyException($property);
		}
	}

/**
 * Доступ на запись запрещен
 * @param string $property
 * @param mixed $value
 * 
 * @throws Core_ReadOnlyObjectException
 */
	public function __set($property, $value) {
		throw new Core_ReadOnlyObjectException($this);
	}

/**
 * Проверяет установлено ил свойство
 * @param  string  $property
 * @return boolean
 */
	public function __isset($property) {
		return $property == 'value' || isset($this->$property) || method_exists($this, "get_$property");
	}

/**
 * Удаление свойства запрещено
 * @param string $propety
 * 
 * @throws Core_ReadOnlyObjectException
 */
	public function __unset($propety) {
		throw new Core_ReadOnlyObjectException($this);
	}

/**
 * Значение константы
 * @return mixed
 */
	public function value() {
		return $this->value;
	}

/**
 * Возвращает объект соответствующий заданному классу
 * @param  string  $class       имя класса
 * @param  mixed  $value       значение
 * @param  integer $cardinality верхний предел значения
 * 
 * @throws Object_BadConstException
 * 
 * @return mixed               
 */
	static protected function object_for($class, $value, $cardinality = 0) {
		switch (true) {
			case $value instanceof $class:
				return $value;
			case is_string($value) && method_exists($class, $m = strtoupper((string) $value)):
				return  call_user_func(array($class, $m));
			case is_int($value) && ($value >= 0) && $value < $cardinality:
				return new $class($value);
			default:
				throw new Object_BadConstException($value);
		}
	}

}

/**
 * Класс представляет собой структуру с расширенными возможностями
 *
 * @package Object
 */
class Object_Struct
  implements Core_PropertyAccessInterface,
             Core_CallInterface,
             Core_EqualityInterface {


/**
 * Доступ на чтение к свойствам объекта.
 * 
 * Если существует метод get_$property, где $property - имя свойства, 
 * то возвращается результат этого метода, 
 * иначе возвращается значение обычного свойства объекта, если оно существует.
 * 
 * @throws Core_MissingPropertyException если свойство не существует.
 * 
 * @param string $property Свойство объекта
 * 
 * @return mixed Значение свойства
 */
	public function __get($property) {
		if (method_exists($this, $method = "get_{$property}"))
			return $this->$method();
		elseif (property_exists($this, $property))
			return $this->$property;
		else
			throw new Core_MissingPropertyException($property);
	}


/**
 * Доступ на запись к свойствам объекта.
 * 
 * Если существует метод set_$property, где $property - имя свойства, 
 * то значение устанавливается с помощью этого метода,, 
 * иначе устанавливается значение обычного свойства объекта, если оно существует.
 * 
 * @throws Core_MissingPropertyException если свойство не существует.
 * 
 * @param string $property Свойство объекта
 * @param mixed $value Значение свойства
 * 
 * @return self
 */
  public function __set($property, $value) {
    if (method_exists($this, $method = "set_{$property}"))
        return $this->$method($value);
    elseif (property_exists($this, $property))
      {$this->$property = $value; return $this;}
    else
      throw new Core_MissingPropertyException($property);
  }


/**
 * Проверяется существует ли свойство с именем $property
 * 
 * @param string Свойство объекта
 * 
 * @return boolean
 */
  public function __isset($property) {
    return (method_exists($this, $method =  "get_{$property}") && (bool) $this->$method() ) ||
           (property_exists($this, $property) && isset($this->$property));
  }


/**
 * Установка в значение null свойства объекта.
 * 
 * Если существует метод set_$property, где $property - имя свойства, 
 * то вызывается этот метод с параметром для установки null, 
 * иначе устанавливается значение обычного свойства объекта в null, если оно существует.
 * 
 * @throws Core_MissingPropertyException если свойство не существует.
 * 
 * @param string $property Свойство объекта
 * 
 * @return self
 */
  public function __unset($property) {

    switch (true) {
      case method_exists($this, $m = "set_{$property}"):
        call_user_func(array($this, $m), null);
        break;
      case property_exists($this, $property):
        $this->$property = null;
        break;
      default:
        throw new Core_MissingPropertyException($property);
    }
    return $this;
  }


/**
 * Устанавливает свойство объекта с помощью вызова метода с именем свойства
 * 
 * @param string $method имя метода-свойства
 * @param array $args аргументы вызова - В функцию __set передается только $args[0]
 * 
 * @return self
 */
  public function __call($method, $args) {
    $this->__set($method, $args[0]);
    return $this;
  }


/** 
 * Возвращает массив имен всех свойств объекта
 * 
 * @return array $result
 */
  private function get_properties() {
    $result = array();
    foreach (Core::with(new ReflectionObject($this))->getProperties() as $v)
      //if (($name = $v->getName()) != '_frozen') 
      $result[] = $v->getName();
    return $result;
  }


/**
 * Сравнивает два объекта Object_Struct
 * 
 * @param Object_Struct $with Объект, с которым сравнивается текущий.
 * 
 * @return boolean
 */
  public function equals($with) {
    if (!($with instanceof Object_Struct) ||
        !Core::equals($p = $this->get_properties(), $with->get_properties()))
      return false;

    foreach ($p as $v) if (!Core::equals($this->$v, $with->$v)) return false;

    return true;
  }

}


/**
 * Базовый абстрактный класс для Object_Aggregator и Object_Listener
 * 
 * @package Object
 */
abstract class Object_AbstractDelegator
	implements IteratorAggregate, Core_CallInterface, Core_IndexedAccessInterface {

/**
 * @var array массив зарегистрированных объектов
 */
	protected $delegates   = array();
/**
 * @var array массив зарегистрированных классов
 */
	protected $classes = array();
/**
 * @var integer Текущий индекс
 */
	protected $last_index = 0;

/**
 * Конструктор
 * @param array $delegates массив объектов
 */
	public function __construct(array $delegates = array()) {
		foreach ($delegates as $d) $this->append($d);
	}

/**
 * Добавляет объект
 * @param  object $object
 * @param  mixed $index
 * @return self
 */
	protected function append_object($object, $index = null) {
		$index = $this->compose_index($index);
		$this->delegates[$index] = $object;
		return $this;
	}

/**
 * Формирует индекс
 * @param null|int|string $index
 * @return string|int
 */
	protected function compose_index($index) {
		return is_null($index) ? $this->last_index++ : (is_numeric($index) ? $index : (string) $index);
	}

	

/**
 * Добавление объектов или имен классов.
 * 
 * @param string|object $instance 
 * @param null|int|string $index
 * 
 * @return self
 */
	public function append($instance, $index = null) {
		$index = $this->compose_index($index);
		switch (true) {
			case (is_string($instance)):
				$this->classes[$index] = $instance;
				break;
			case (is_object($instance)):
				$this->append_object($instance, $index);
				break;
			default:
				throw new Core_InvalidArgumentValueException('instance','Must be string or object');
		}
		return $this;
	}

/**
 * Удаление делегированных строк или объектов.
 * 
 * @param int|string $index Корректный индекс массива
 * 
 * @return self
 */
	public function remove($index) {
		if (isset($this->delegates[$index])) {
			unset($this->delegates[$index]);
      $this->last_index--;
    }
		if (isset($this->classes[$index])) {
			unset($this->classes[$index]);
      $this->last_index--;
    }
		return $this;
	}


/**
 * Возвращает итератор
 *
 * Проходит по всем имеющимся классам и создает объекты
 * @return ArrayIterator
 */
	public function getIterator() {
		foreach ($this->classes as $index => $class)
			$this->append_object(Core::make($class), $index);
			
		$this->classes = array();
		return new ArrayIterator($this->delegates);
	}


/**
 * Доступ к объектам
 * @param null|int|string $index
 * @return object|null
 */
	public function offsetGet($index) {
		switch (true) {
			case isset($this->delegates[$index]):
				return $this->delegates[$index];
			case isset($this->classes[$index]):
				$this->append_object(Core::make($this->classes[$index]), $index);
				unset($this->classes[$index]);
				return $this->delegates[$index];
		}
		return null;
	}

/**
 * Аналог append 
 * @param  null|int|string $index
 * @param  string|object $value
 * @return self
 */
	public function offsetSet($index, $value) {
		$this->append($value, $index);
		return $this;
	}

/**
 * Определяет есть ли объект или класс по заданному индексу
 * @param null|int|string $index 
 * @return boolean
 */
	public function offsetExists($index) { 
		return isset($this->delegates[$index]) || isset($this->classes[$index]); 
	}

/**
 * Аналог remove
 * @param  null|int|string $index
 * @return self
 */
	public function offsetUnset($index) {
		return $this->remove($index);
	}


}


/**
 * Делегирует вызов списку объектов
 *
 * Позволяет уведомлять объекты-слушатели о произошедших событиях.
 * 
 * @package Object
 */
class Object_Listener extends Object_AbstractDelegator {

/**
 * @var string Тип делегируемого объекта
 */
	protected $type;

/**
 * Конструктор
 * @param string|null $type Тип делегируемого объекта
 * @param array $listeners список "слушателей"
 */
	public function __construct($type = null, array $listeners = array()) {
		if ($type) $this->type = Core_Types::real_class_name_for($type);
		parent::__construct($listeners);
	}


/**
 * Добавление делегируемого объекта
 * 
 * Если при создании объекта был указан параметр $type,
 * то должны добавляться только объекты этого типа. То есть 
 * параметр $listener должен быть объектом типа $this->type.
 * 
 * @param object $listener 
 * @param null|int|string $index 
 * 
 * @see Object_AbstractDelegator::append()
 * 
 * @throws Core_InvalidArgumentTypeException  Если установлено свойство $this->type 
 * и $listener не является объектом этого типа.
 */
	public function append($listener, $index = null) {
		if (!$this->type || ($listener instanceof $this->type))
			return parent::append($listener, $index);
		else
			throw new Core_InvalidArgumentTypeException('listener', $listener);
	}


/**
 * Вызов метода у всех зарегистрированных "слушателей"
 * 
 * @param  string $method Имя метода
 * @param  array $args   аргументы
 * @return self
 */
	public function __call($method, $args) {
	  
		foreach ($this as $k => $v)
			if (method_exists($this->delegates[$k], $method))
				call_user_func_array(array($this->delegates[$k], $method), $args);
		return $this;
	}
}


/**
 * Агрегатор.
 *
 * Перекидывает вызов метода на первый найденный зарегистрированный объект.
 * Есть возможность задания $fallback
 *
 * @package Object
 */
class Object_Aggregator extends Object_AbstractDelegator {

/**
 * @var array callback методы
 */
	private $methods;
/**
 * $fallback
 */
	private $fallback;

/**
 * ONLY FOR UNIT TEST
 *
 * @internal
 */
	protected function get_private_property_methods()  {
		return $this->methods;
	}
  
/**
 * ONLY FOR UNIT TEST
 * 
 * @internal
 */
	protected function get_private_property_fallback()  {
		return $this->fallback;
	}

/**
 * Устанавливает $fallback
 * 
 * @param  Object_Aggregator $fallback
 * 
 * @throws Core_InvalidArgumentValueException Если в качестве fallback передается сам $this
 * 
 * @return self                      
 */
	public function fallback_to(Object_Aggregator $fallback) {
		if ($this === $fallback) {
			throw new Core_InvalidArgumentValueException('fallback','this');
		}
		$this->fallback = $fallback;
		return $this;
	}

/**
 * Обнуляет цепочку $fallback
 * 
 * @return self                      
 */
	public function clear_fallback() {
		$this->fallback = null;
		return $this;
	}


/**
 * Перенаправляет вызов метода.
 * 
 * Ищет вызванный метод в массиве $methods.
 * Если не находит его в этом массиве, то ищет метод в массиве классов
 * $delegates. Если находит его там, то копирует его в массив $methods.
 * Если не находит, то пробует найти его в цепочке калбэков.
 *
 * @param method string Имя метода
 * @param  array массив аргументов 
 * 
 * @throws Core_MissingMethodException Если нигде не может найти запрошенный метод.
 */
	public function __call($method, $args) {
		if (!isset($this->methods[$method])) {
			foreach ($this as $k => $d) {
				if (method_exists($d, $method)) {
					$this->methods[$method] = array($d, $method);
					break;
				}
			}
		}
		
		switch (true) {
			case isset($this->methods[$method]):
				return call_user_func_array($this->methods[$method], (array) $args);
			case $this->fallback:
				return $this->fallback->__call($method, $args);
			default:
				throw new Core_MissingMethodException($method);
		}
	}


/**
 * Возвращает зарегистрированный объект по индексу 
 * 
 * Возвращает либо объект по запрошенному индексу из родительского класса, 
 * либо, если родитель вернул null, то элемент массива $fallback 
 * с запрошенным индексом.  
 *
 * @param null|int|string $index
 * 
 * @throws Core_MissingIndexedPropertyException Если запрошенный элемент отсутствует.
 * 
 * @return object
 */
	public function offsetGet($index) {
		$from_parent  = parent::offsetGet($index);
		if (!empty($from_parent)) return $from_parent;
		if ($this->fallback instanceof self)
			return $this->fallback[$index];
		throw new Core_MissingIndexedPropertyException($index);
	}

/**
 * Добавляет объект по индексу
 * @param  null|int|string $index
 * @param  string|object $value
 * @return parent        
 */
  public function offsetSet($index, $value) {
    return parent::offsetSet($index, $value);
  }

/**
 * Проверяет существование объекта по индексу
 * @param  null|int|string $index
 * @return boolean       
 */
  public function offsetExists($index) {
    return parent::offsetExists($index) || (($this->fallback instanceof self) && isset($this->fallback[$index])); }

/**
 * Пытается удалить объект по индексу
 * @param  null|int|string $index
 * @return void
 */
  public function offsetUnset($index) {
    parent::offsetUnset($index);
    if (isset($this->fallbak[$index]))
        unset($this->fallbak[$index]);
  }


}


/**
 * Фабрика объектов
 * @package Object
 */
class Object_Factory implements Core_CallInterface {

/**
 * @var array массив зарегистрированных классов
 */
	private $map = array();

/**
 * @var string префикс для названий классов
 */
	private $prefix;

/**
 * Конструктор
 * @param string $prefix префикс для названий классов
 */
	public function __construct($prefix = '') {
		$this->prefix = $prefix;
	}

/**
 * Заполняет массив $map значениями.
 * 
 * Если параметр $name массив, то параметр $type необязательный.
 * Если он передан, то используется как префикс для значений ключей массива $name
 * Если не передан, то в качестве префикса для значений ключей используется свойство 
 * $this->prefix (устанавливается параметром конструктора).
 * 
 * Если параметр $name является строкой, то параметр $type обязателен.
 * В этом случае $name используется как ключ массива, а $type как значение.
 * В качестве префикса используется $this->prefix.
 * Если параметр $type отсутствует или переданно пустое значение, то 
 * бросается исключение Core_InvalidArgumentTypeException
 * 
 * Если параметр $name имеет другой тип, то 
 * бросается исключение Core_InvalidArgumentTypeException.
 * 
 * @param array|string $name
 * @param mixed $type По умолчанию null
 * 
 * @throws Core_InvalidArgumentTypeException
 * 
 * @return self
 */
	public function map($name, $type = null) {
		switch (true) {
			case is_array($name):
				$prefix = ($type === null ? $this->prefix : (string) $type);
				foreach ($name as $k => $v) $this->map[$k] = "$prefix$v";
				break;
			case is_string($name):
				if ($type)
					$this->map[$name] = "{$this->prefix}$type";
			else
				throw new Core_InvalidArgumentTypeException('type', $type);
				break;
			default:
				throw new Core_InvalidArgumentTypeException('name', $name);
		}
		return $this;
	}


/**
 * Заполняет массив $map значениями, используя функцию $this->map
 * 
 * @param array $maps
 * @param string $prefix
 * 
 * @return self
 */
	public function map_list(array $maps, $prefix = '') {
		foreach ($maps as $k => $v) $this->map($k, "$prefix$v");
		return $this;
	}


/**
 * Создает объект класса map[$name] c параметрами конструктора $args.
 * 
 * @uses self::$map
 * 
 * @param mixed $name Должен быть ключом массива, установленного ранее 
 * через $this->map() или $this->map_list(). Значением ключа должен быть 
 * либо объект либо имя класса 
 * 
 * @see Core::reflection_for()
 * @see Core::amake()
 * 
 * @param array $args Параметры, передаваемые конструктору класса
 * 
 * @throws Core_InvalidArgumentValueException Если ключ $name отсутствует в массиве $map
 * 
 * @return object
 */
	public function new_instance_of($name, $args = array()) {
		if (isset($this->map[$name]))
			return Core::amake($this->map[$name], $args);
		else
			throw new Core_InvalidArgumentValueException('name', $name);
	}

/**
 * Псевдоним new_instance_of
 *
 * @param  string $method
 * @param  array $args
 * @see  self::new_instance_of($name, $args = array())
 */
	public function __call($method, $args) { return $this->new_instance_of($method, $args); }

}


/**
 * Враппер над объектом
 * @package Object
 */
class Object_Wrapper
  implements Core_PropertyAccessInterface, Core_CallInterface {

/**
 * Расширяемый объект
 */
  protected $object;

/**
 * массив атрибутов, с помощью которых и происходит расширение
 * @var array
 */
  protected $attrs = array();

	/**
   * Конструктор 
	 * @param object $object
	 * @param array $attrs
	 * 
	 * @throw Core_InvalidArgumentValueException Если параметры не соответствуют указанным типам
	 */
  public function __construct($object, array $attrs) {
	  if (!(is_object($object))) {
		  throw new Core_InvalidArgumentValueException('object','Must be object');
	  }
    $this->object = $object;
    $this->attrs  = $attrs;
  }


/** 
 * Доступ на чтение.
 * 
 * Может быть либо именем свойства, либо ключом массива, 
 * либо специальным значением: 
 * -	'__object' - вернет объект, переданный в конструкторе,
 * -	'__attrs' - вернет массив, переданный в конструкторе.
 *
 * @param string $property Имя свойства
 * 
 * @return mixed
 */
	public function __get($property) {
		switch ($property) {
			case '__object':
				return $this->object;
			case '__attrs':
				return $this->attrs;
			default:
				return array_key_exists($property, $this->attrs) ?
					$this->attrs[$property] : 
					(
						(property_exists($this->object, $property)) ?
							$this->object->$property : 
							null
					);
		}
	}


	/**
	 * Доступ на запись.
 	 *
	 * Сначала обращение идет к массиву расширения, затем к самому объекту
	 * 
	 * @param string $property Имя свойства
	 * @param mixed $value
	 * 
	 * @throws Core_ReadOnlyObjectException Если $property имеет значение '__object' или '__attrs'
	 * 
	 * @return self
	 */
	public function __set($property, $value) {
		if ($property == '__object' || $property == '__attrs')
		{
			throw new Core_ReadOnlyObjectException($this);
		}

		if (array_key_exists($property, $this->attrs))
			$this->attrs[$property] = $value;
		else
			$this->object->$property = $value;
		return $this;
	}


/**
 * Проверяет установленно ли свойство объекта
 * 
 * @param string $property Имя свойства
 * 
 * @return boolean
 */
	public function __isset($property) {
		return isset($this->attrs[$property]) || isset($this->object->$property);
	}


/**
 * Удаление свойства.
 * 
 * @param string $property Имя свойства
 * 
 * @throws Core_ReadOnlyObjectException Если $property имеет значение '__object' или '__attrs'
 * 
 * @return self
 */
	public function __unset($property) {
		if ($property == '__object' || $property == '__attrs')
		{
			throw new Core_ReadOnlyObjectException($this);
		}
		if (array_key_exists($property, $this->attrs))
			unset($this->attrs[$property]);
		else if (property_exists($this->object,$property))
			unset($this->object->$property);
//		else
//			throw new Core_MissingPropertyException($property);
		return $this;
	}

	/**
	 * Вызов метода
     *
     * Если в расширении есть callback, то используем его, иначе пробрасываем вызов в искомый объект
     *
     * @param  string $method
     * @param  array $args
	 */
	public function __call($method, $args) {
		if (Core_Types::is_callable($c = $this->__get($method))) return call_user_func_array($c, $args);
		return call_user_func_array(array($this->object, $method), $args);
	}


}


/**
 * Фильтр
 *
 * <code>
 * $a = array(array('key1' => 1, 'key2' => 2), array('key1' => 11, 'key2' => 12));
 * var_dump(array_filter($a, Object::Filter(1, 'key1')));
 * // в результате останется только первый элемент массива (object) array('key1' => 1, 'key2' => 2)
 * </code>
 * 
 * @package Object
 */
class Object_Filter {

/**
 * @var string название совйства
 */
  protected $field;

/**
 * @var mixed значение свойства
 */
  protected $value;

/**
 * Конструктор
 *  
 * @throws Core_InvalidArgumentValueException Если вторым параметром передан null
 * 
 * @param mixed $value Значение, по которому происходит фильтрация.
 * @param string $field Название значения фильтрации.
 */
	public function __construct($value, $field = 'group') {
		if (is_null($field))
		{
		  throw new Core_InvalidArgumentValueException('field','null');
		}
		  
		$this->field = $field;
		$this->value = $value;
	}

	/**
	 * Проверка установленных в конструкторе значений.
	 * 
	 * 
	 * 
	 * Если $e скаляр, то:
	 * - возвращается true, если $e == $this->value
	 * - иначе false.
	 * 
	 * Если $e массив или объект возвращает true, если существует ключ $e[$this->field] 
	 * и $e[$this->field] == $this->value  
	 * Если нет или если такого ключа - то false.
	 *
	 * @param string|array|object $e .
	 * 
	 * @return boolean
	 */
	public function filter($e) {
		if (is_scalar($e)) {
			return ($e == $this->value);
		}
		else {
			return (isset($e[$this->field]) && $e[$this->field] == $this->value);
		}
	}
}
