<?php
/**
 * DB.ORM
 * 
 * Объектно-ориентированный интерфейс к реляционной базе данных
 * 
 * @package DB\ORM
 * @version 0.2.8
 */
Core::load('DB.ORM.SQL', 'DB', 'Data.Pagination', 'Validation', 'Object', 'Events');

/**
 * Модуль DB.ORM
 * 
 * @package DB\ORM
 */
class DB_ORM implements Core_ModuleInterface, Core_ConfigurableModuleInterface {
  const VERSION = '0.2.8';

  static protected $options = array('associate_tables_delimiter' => '_has_' );


/**
 */
  static public function initialize(array $options = array()) {
	self::options($options);
  }

/**
 * @param array $options
 * @return mixed
 */
  static public function options(array $options = array()) {
	if (count($options)) Core_Arrays::update(self::$options, $options);
	return self::$options;
  }

/**
 * @param string $name
 * @param  $value
 */
  static public function option($name, $value = null) {
	$prev = isset(self::$options[$name]) ? self::$options[$name] : null;
	if ($value !== null) self::options(array($name => $value));
	return $prev;
  }



/**
 * @return DB_ORM_MapperSet
 */
  static public function MapperSet(DB_ORM_Mapper $parent = null) {
	return new DB_ORM_MapperSet($parent);
  }


}


/**
 * Базовый класс исключений
 * 
 * @package DB\ORM
 */
class DB_ORM_Exception extends Core_Exception {}


/**
 * @package DB\ORM
 */
interface DB_ORM_ImmutableMapperInterface {


/**
 */
  public function immutable();

}


/**
 * Базовый класс маппера
 * 
 * <p>Базовый класс DB.ORM.Mapper определяет набор основных свойств, присущих всем мапперам.</p>
 * <p>Он определяет три основные операции: pmap (property map) и cmap (call map), позволяющие реализовывать
 * динамическое вычисление значений свойств и и результата вызовов методов объектов, а также операцию spawn(),
 * порождающую дочерний маппер.</p>
 * <p>Операция pmap, определяемая реализацией защищенного метода pmap, отвечает за вычисление значений динамических
 * свойств маппера. При обращении к свойству  $mapper->property вычисление организуется следующим образом:</p>
 * <ol><li>Если определен метод pmap_{property}, возвращается результат выполнения этого метода;</li>
 * <li>Если определен метод map_{property},  возвращается результат выполнения этого метода;</li>
 * <li>Если определено значение поля $this->{property}, возвращается значение этого свойства;</li>
 * <li>Возвращается значение свойства property родительского объекта, или null, если родительский объект не
 * определен.</li>
 * </ol><p>Таким образом, можно динамически переопределять свойства объекта, при этом свойства наследуются дочерними
 * элементами дерева от родительских.</p>
 * <p>Наследование свойств может быть очень полезным. Например, если мы сохраним объект класса DB.Connection в поле
 * корневого элемента дерева, то каждый маппер сможет получить значение этого поля как $this->connection. Таким
 * образом, каждый маппер может обратиться к базе данных, не задумываясь о своей родительской цепочке
 * мапперов.</p>
 * <p>Предположим теперь, что у нас есть две базы и два соединения. В этом случае, мы можем убрать свойство
 * connection из корневого элемента и сделать два дочерних маппера, создав свойство connection у каждого из них.
 * Для дочерних мапперов ничего не изменится -- они будут по-прежнему обращаться к свойству $this->connection.</p>
 * <p>Корневой элемент дерева мапперов, не имеющий родителей, доступен в любом маппере как свойство session.
 * Это название не случайно, так как корневой элемент может хранить общую информацию, специфичную для текущего
 * сеанса работы с базой, предоставляя ее дочерним мапперам. В частности, таким образом может храниться
 * информация о текущем авторизованном пользователе приложения.</p>
 * <p>Также всегда доступно свойство parent, возвращающее ссылку на родительский объект маппера.</p>
 * <p>Операция cmap, определяемая реализацией защищенного метода cmap, отвечает за диспетчеризацию вызовов методов
 * маппера. При обращении к методу маппера $mapper->method() вычисление организуется следующим образом:</p>
 * <ol><li>Если определен метод cmap_{method}, возвращается результат выполнения этого метода;</li>
 * <li>Если определен метод map_{method},  возвращается результат выполнения этого метода;</li>
 * <li>Генерируется исключение Core.MissingMethodException, то есть родительскому мапперу метод не
 * делегируется.</li>
 * </ol><p>И, наконец, операция spawn, реализуемая одноименным публичным методом, позволяет мапперу порождать дочерние
 * мапперы того же типа, что и родительский маппер. То есть, если у нас есть маппер $m класса StoryMapper, вызов
 * $m->spawn() вернет маппер класса StoryMapper, поле parent которого будет указывать на маппер $m.</p>
 * <p>Объекты класса поддерживают следующие обязательные свойства:</p>
 * parentссылка на родительский маппер;
 * sessionссылка на корневой маппер дерева.
 * 
 * @abstract
 * @package DB\ORM
 */
abstract class DB_ORM_Mapper
  implements Core_PropertyAccessInterface,
			 Core_CallInterface {

  protected $parent;
  protected $maps = array();


/**
 * Конструктор
 * 
 * @param  $parent
 */
  public function __construct($parent = null) {  if ($parent) $this->child_of($parent); }



/**
 * Порождает дочерний маппер
 * 
 * @return DB_ORM_Mapper
 */
  public function spawn() { return Core::make(Core_Types::class_name_for($this), $this, true); }

  public function clear() {
	$class = get_class($this);
	return $this->parent instanceof $class ? $this->parent->clear() : $this;
  }

  public function shift($class) {
	$p = Core::make($class, $this->parent);
	$this->parent = $p;
	return $this;
  }

/**
 * @param string $path
 * @return mixed
 */
  public function downto($path) {
	$r = $this;
	foreach (explode('/', $path) as $s) {
	  switch (true) {
		case $s && is_numeric($s):
		  $r = $r[$s];
		  break;
		case $s && isset($r->$s):
		  $r = $r->$s;
		  break;
		default:
		  return null;
	  }
	}
	return $r;
  }


  public function map($name, $callback)
  {
	if (Core_Types::is_callable($callback)) {
	  if ($callback instanceof Closure && method_exists($callback, 'bindTo')) {
		$callback = $callback->bindTo($m = $this->spawn(), $m);
	  }
	  $this->maps[$name] = $callback;
	}
	return $this;
  }



	/**
	 * Определяет, реализует ли данный маппер отображение метода $method
	 * 
	 * Если отображение поддерживается, возвращается имя метода, 
	 * выполняющего отображение, в противном случае возвращается false.
	 * 
	 * @param string $method имя метода
	 * 
	 * @return string|boolean
	 */
	public function can_cmap($method) {
		$default = $this->__can_map($method, true);
		return method_exists($this,    "cmap_$method") ? "cmap_$method" :
			(method_exists($this, "map_$method")  ? "map_$method"  :
				( ($this->parent && !$default) ? $this->parent->can_cmap($method) :  $default));
	}

	/**
	 * Определяет, реализует ли данный маппер отображение свойства $property
	 * 
	 * Если отображение поддерживается, возвращается имя метода, 
	 * выполняющего отображение, в противном случае возвращается false.
	 * 
	 * @param string $property имя свойства
	 * 
	 * @return string|boolean
	 */
	public function can_pmap($property) {
		$default = $this->__can_map($property, false);
		return method_exists($this,    "pmap_$property") ? "pmap_$property" :
			(method_exists($this, "map_$property")  ? "map_$property"  : 
				( ($this->parent && !$default) ? $this->parent->can_pmap($property) : $default));
	}

/**
 * @param string $name
 * @param boolean $is_call
 * @return string
 */
  protected function __can_map($name, $is_call = false)
  {
	return isset($this->maps[$name]);
  }

/**
 * @param string $name
 * @param null|array $args
 * @return mixed
 */
	protected function __map($name, $args = null) { return null; }

/**
 * Выполняет отображение свойства маппера
 * 
 * @param string $name
 * @return mixed
 */
  protected function pmap($name, $method) {
	if (is_string($method) && method_exists($this, $method))
	  return  $this->$method();
	if ($r = $this->__map($name))
	  return $r;
	if ($this->parent)
	  return $this->parent->pmap($name, $method);
  }

/**
 * Выполняет отображение вызова метода маппера
 * 
 * @param string $name
 * @param string $method
 * @param array $args
 * @return mixed
 */
  protected function cmap($name, $method, array $args) {
	if (is_string($method) && method_exists($this, $method))
	  return call_user_func_array(array($this, $method), $args);
	if ($r = $this->__map($name, $args))
	  return $r;
	if ($this->parent)
	  return $this->parent->cmap($name, $method, $args);

  }



/**
 * возвращает значение свойства
 * 
 * @param string $property
 * @return mixed
 */
  public function __get($property) {
	switch ($property) {
	  case 'parent':
		return $this->$property;
	  case 'db':
	  case 'session':
		return $this instanceof DB_ORM_ConnectionMapperInterface ? $this : $this->parent->$property;
	  case '__root':
		return isset($this->parent) ? $this->parent->__get("__root") : $this;
	  default:
		if ($m  = $this->can_pmap($property))
		  return $this->pmap($property, $m);
		else
		  return (isset($this->$property)) ?
			$this->$property :
			(isset($this->parent) ? $this->parent->$property : null);
	}
  }

/**
 * устанавливает значение свойства
 * 
 * @param string $property
 * @param  $value
 */
  public function __set($property, $value) {
	switch ($property) {
	  case 'parent':
		$this->child_of($value);
		return $this;
	  case 'db':
	  case 'session':
	  case '__root':
		throw new Core_ReadOnlyPropertyException($property);
	  default:
		throw $this->can_pmap($property) ?
		  new Core_ReadOnlyPropertyException($property) :
		  new Core_MissingPropertyException($property);
	}
  }

/**
 * @param string $property
 * @return boolean
 */
  public function __isset($property) {
	switch ($property) {
	  case 'parent':
	  case 'db':
	  case 'session':
	  case '__root':
		return true;
	  default:
		return (boolean) $this->can_pmap($property);
	}
  }

/**
 * Удаляет свойство
 * 
 * @param string $property
 */
	/**
	 * Удаляет свойства
	 * 
	 * Поддерживается только удаление свойства parent
	 * 
	 * @throws Core_UndestroyablePropertyException При попытке удаления любого 
	 * другого свойства, в том числе динамического.
	 * 
	 *  @throws Core_MissingPropertyException При попытке удалить несуществующее свойство.
	 */
	public function __unset($property) {
		switch ($property) {
			case 'parent':
				unset($this->parent);
				break;
			case 'db':
			case 'session':
			case '__root':
				throw new Core_UndestroyablePropertyException($property);
			default:
				throw $this->can_pmap($property) ?
					new Core_UndestroyablePropertyException($property) :
					new Core_MissingPropertyException($property);
		}
	}



/**
 * Выполняет диспетчеризацию динамических вызовов
 * 
 * @param string $method
 * @param array $args
 * @return mixed
 */
  public function __call($method, $args) {
	if (isset($this->maps[$method])) {
	  return Core::invoke($this->maps[$method], $args);
	}
	if ($m = $this->can_cmap($method))
	  return $this->cmap($method, $m, $args);
	else {
	  if ($this->parent)
		return method_exists($this->parent, $method) ?
		  call_user_func_array(array($this->parent, $method), $args) : $this->parent->__call($method, $args);
	  else
		throw new Core_MissingMethodException($method);
	}
  }



  protected function created_from_mapperset($mapperset, $name) {
	return $this;
  }

/**
 * Устанавливает значение ссылки на родителький маппер
 * 
 * @param DB_ORM_Mapper $parent
 * @return DB_ORM_Mapper
 */
  private function child_of(DB_ORM_Mapper $parent) {
	$this->parent = $parent;
	return $this;
  }

}


/**
 * Коллекция опций.
 * 
 * Коллекция значений различных опций, определяющих поведение маппера.
 * 
 * @package DB\ORM
 */
class DB_ORM_MappingOptions
  implements Core_PropertyAccessInterface,
			 Core_IndexedAccessInterface {

  protected $parent;
  protected $options = array();


/**
 * Конструктор
 * 
 * @param DB_ORM_Mapper $mapper
 */
	/**
	 * Конструктор
	 * 
	 * @param DB_ORM_MappingOptions $mapper.
	 *
	 * @throws Core_InvalidArgumentValueException Если в качестве параметра используется вызывающий объект
	 */
	public function __construct($parent = null) {
		if ($parent === $this)
		{
			throw new Core_InvalidArgumentValueException('parent','this');
		}
		
		$this->parent = ($parent instanceof DB_ORM_MappingOptions) ? $parent : null;
		//$parent = isset($mapper->parent) ? $mapper->parent->options : null;
		//$this->parent = ($parent instanceof DB_ORM_MappingOptions) ? $parent : null;
	}



/**
 * Обработка вызовов методов установки значений опций
 * 
 * @param string $method
 * @param array $args
 * @return mixed
 */
	public function __call($method, $args) {
		switch ($method) {
			case 'classname':
				return $this->option($method, (string) $args[0]);
			case 'column':
				$this->array_option('columns', (string) $args[0]);
				if (isset($args[1])) $this->array_option('defaults', $args[1], $args[0]);
				return $this;
			case 'validator':
				return $this->use_validator($args[0]);
			case 'table':
				return $this->option($method, explode(' ', (string) $args[0]));
			case 'columns':
			case 'exclude':
			case 'only':
			case 'key':
				foreach ((array) $args[0] as $v) $this->array_option($method, (string) $v);
				return $this;
			case 'defaults':
				foreach ((array) $args[0] as $k => $v) $this->array_option($method, $v, $k);
				return $this;
			case 'lookup_by':
			case 'search_by':
				return $this->option($method, (string) $args[0]);
			case 'explicit_key':
				return $this->option($method, (boolean) $args[0]);
			case 'calculate':
				foreach ((array) $args[0] as $k => $v) {
					$alias = (string) $k;
					if (is_int($k)) {
						switch(true) {
							case Core_Strings::contains($v, ' '):
								$v = str_replace(' as ', ' ', $v);
								$parts = explode(' ', $v);
								$alias = array_pop($parts);
								$v = implode(' ', $parts);
								break;
							case preg_match('{[0-9a-zA-Z_]+\.([0-9a-zA-Z_]+)}', $v, $m):
								$alias = $m[1];
								break;
							default:
								$alias = $v;
						}
					}
					$this->array_option($method, $v, $alias);
				}
				return $this;
			case 'having':
			case 'where':
				return $this->array_option($method, (string) $args[0]);
			case 'order_by':
			case 'group_by':
				return $this->option($method, (string) $args[0]);
			case 'join':
				return $this->array_option('join', array((string) $args[0], (string) $args[1], (array) $args[2]));
			case 'range':
				return $this->option('range', array((int) $args[0], isset($args[1]) ? (int) $args[1] : 0));
			case 'index':
				return $this->option('index', (string) $args[0]);
			default:
				return $this->option($method, $args[0]);
		}
	}


	/**
	 * Возвращает значение опции
	 * 
	 * Помимо опций, перечисленных в описании метода __call, 
	 * достпны еще две опции, значения которыъ вычисляются на основании значений других опций:
	 * - result список всех колонок результирующего набора
	 * - aliased_table имя исходной таблицы вместе с ее псевдонимом
	 * 
	 * @param string $index имя опции
	 * 
	 * @return mixed
	 */
	public function offsetGet($index) {
		$parent = $this->parent;
		switch ($index) {
			case 'table':
			case 'classname':
			case 'validator':
			case 'order_by':
			case 'group_by':
			case 'key':
			case 'explicit_key':
			case 'range':
			case 'index':
			case 'lookup_by':
			case 'search_by':
			case 'only':
				return $this->has_option($index) ?
					$this->options[$index] :
					($parent ? $parent[$index] : null);
			case 'aliased_table':
				return implode(' ', $this['table']);
			case 'table_prefix':
				return isset($this->options['table']) ?
					(isset($this->options['table'][1]) ? $this->options['table'][1] : $this->options['table'][0]) :
					($parent ? $parent[$index] : null);
			case 'defaults':
			case 'columns':
			case 'join':
			case 'where':
			case 'having':
			case 'calculate':
			case 'exclude':
				return $parent ?
					array_merge($parent[$index],
						$this->has_option($index) ? $this->options[$index] : array()) :
					($this->has_option($index) ? $this->options[$index] : array());
			case 'result':
				$r = array();
				$t = $this['table'];

				foreach ($this['columns'] as $c)
				  $r[$c] = (isset($t[1]) ? $t[1] : $t[0]).'.'.$c;

				foreach ($this['calculate'] as $k => $v) $r[$k] = $v;

				if ($only = $this['only']) $r = array_intersect_key($r, array_flip($only));

				foreach ($this['exclude'] as $c) unset($r[$c]);

				return $r;
			default:
				$value = $this->has_option($index) ? $this->options[$index] : null;
				if (is_array($value)) return array_merge($parent ? (array) $parent[$index] : array(), $value);
				if (is_null($value)) return $parent ? $parent[$index] : null;
				return $value;
		}
	}

	/**
	 * Запрещает установку каких-либо опций через интерфейс индексированного доступа
	 * 
	 * Для установки свойств опций необходимо использовать соответствующие методы
	 * 
	 * @param string $index имя опции
	 * @param string $value значение опции
	 * 
	 * @throws Core_ReadOnlyIndexedPropertyException
	 */
	public function offsetSet($index, $value) {
		switch ($index) {
			case 'table':
			case 'classname':
			case 'validator':
			case 'table':
			case 'columns':
			case 'defaults':
			case 'order_by':
			case 'group_by':
			case 'key':
			case 'explicit_key':
			case 'range':
			case 'index':
			case 'join':
			case 'where':
			case 'having':
			case 'calculate':
			case 'exclude':
			case 'aliased_table':
			case 'table_prefix':
			case 'result':
			case 'lookup_by':
			case 'search_by':
			case 'only':
				throw new Core_ReadOnlyIndexedPropertyException($index);
			default:
				return $this->__call($index, array($value));
		}
	}

	/**
	 * Проверяет факт установки значения опции
	 * 
	 * В проверке участвуют также опции родительских объектов
	 * 
	 * @param string $index имя опции
	 * 
	 * @return boolean
	 */
	public function offsetExists($index) {
		switch ($index) {
			case 'table':
			case 'classname':
			case 'validator':
			case 'columns':
			case 'defaults':
			case 'order_by':
			case 'group_by':
			case 'key':
			case 'explicit_key':
			case 'range':
			case 'index':
			case 'join':
			case 'where':
			case 'having':
			case 'calculate':
			case 'exclude':
			case 'lookup_by':
			case 'search_by':
			case 'only':
				return $this->has_option($index) || ($this->parent && isset($this->parent[$index]));
			case 'aliased_table':
			case 'table_prefix':
				return isset($this['table']);
			case 'result':
				return isset($this['columns']) || isset($this['calculate']);
			default:
				return $this->has_option($index) || ($this->parent && $this->parent->has_option($index));
		}
	}

	/**
	 * Удаляет значение опции
	 * 
	 * Позволяет удалить значение любой опции, кроме:
	 * - aliased_table;
	 * - table_prefix;
	 * - result. 
	 * Удаляются только опции, принадлежащие данному объекту, соответствующие 
	 * опции родительского объекта не учитываются.
	 * 
	 * @param string $index имя опции
	 * 
	 * @throws Core_UndestroyableIndexedPropertyException Если удаляются не разрещенные опции.
	 */
	public function offsetUnset($index) {
		switch ($index) {
			case 'table':
			case 'classname':
			case 'validator':
			case 'columns':
			case 'defaults':
			case 'order_by':
			case 'group_by':
			case 'key':
			case 'explicit_key':
			case 'range':
			case 'index':
			case 'join':
			case 'where':
			case 'having':
			case 'calculate':
			case 'exclude':
			case 'lookup_by':
			case 'search_by':
			case 'only':
				unset($this->options[$index]);
				break;
			case 'aliased_table':
			case 'table_prefix':
			case 'result':
				throw new Core_UndestroyableIndexedPropertyException($index);
			default:
				unset($this->options[$index]);
		}
		return $this;
	}

	/**
	 * Возвращает признак наличия опции в данном объекте.
	 * 
	 * Этот вызов аналогичен __isset, но работает только с массивом опций данного объекта
	 * 
	 * @param mixed $name имя опции - может быть любым значением, которое подходит для индекса массива.
	 * 
	 * @return boolean
	 */
	protected function has_option($name) { return array_key_exists($name, $this->options); }

	/**
	 * Устанавливает значение опции
	 * 
	 * @param string $name имя опции
	 * @param mixed $value значение опции
	 * 
	 * @return self
	 */
	protected function option($name, $value) {
		$this->options[$name] = $value;
		return $this;
	}

	/**
	 * Уставливает значение для опции, допускающей набор значений
	 * 
	 * Если индекс опции не указан, очередное значение просто добавляется в массив значений опции
	 * @link http://php.ru/manual/function.array-search.html array_search
	 * 
	 * @param string $name имя опции
	 * @param mixed $value значение опции
	 * @param integer $idx индекс опции по умолчанию null
	 * 
	 * @return self
	 */
	protected function array_option($name, $value, $idx = null) {
		if (isset($this->options[$name]) && is_array($this->options[$name]) && array_search($value, $this->options[$name], true) !== FALSE)
			return $this;
		is_null($idx) ?
		$this->options[$name][]     = $value :
		$this->options[$name][$idx] = $value;
		return $this;
	}

	/**
	 * Устанавливает значение опции validator с проверкой типа аргумента
	 * 
	 * @param Validation_Validator $validator
	 */
	private function use_validator(Validation_Validator $validator) { return $this->option('validator', $validator); }



/**
 * Возвращает родительский объект опций
 * 
 * @return DB_ORM_MapperOptions
 */
	/**
	 * Возвращает родительский объект опций
	 * 
	 * @deprecated
	 * 
	 * @return DB_ORM_MappingOptions|null
	 */
	public function get_parent_() {
		return isset($this->parent) ? $this->parent : null;
	}


	/**
	 * Возвращает значение свойства объекта
	 * 
	 * Поддерживаются следующие свойства:
	 * - options возвращает массив значений опций;
	 * - parent возвращает родительскую коллекцию опций.
	 * 
	 * Важно помнить, что родительским объектом является коллекция опций, а не маппер!
	 * 
	 * @param string $property имя свойства
	 * 
	 * @throws Core_MissingPropertyException Если $property имеет любое неподдерживаемое свойство.
	 * 
	 * @return array
	 */
	public function __get($property) {
		switch ($property) {
			case 'options':
			case 'parent':
				return $this->$property;
			default:
				throw new Core_MissingPropertyException($property);
		}
	}

	/**
	 * Запрещает изменение значений свойств объекта.
	 * 
	 * Значение свойства parent не может быть изменено после создания объекта, 
	 * для изменения значений опций необходимо использовать соответствующие методы.
	 * 
	 * @param string $property имя свойства
	 * @param mixed $value значение свойства
	 * 
	 * @throws Core_UndestroyablePropertyException Если $property равно:
	 * - parent
	 * - options
	 * 
	 * @throws Core_MissingPropertyException Если $property имеет любое другое значение.
	 */
	public function __set($property, $value) {
		switch ($property) {
		case 'parent':
		case 'options':
			throw new Core_ReadOnlyPropertyException($property);
		default:
			throw new Core_MissingPropertyException($property);
		}
	}

	/**
	 * Проверяет, установлено ли свойство объекта
	 * 
	 * Возвращает true только в том случае, если установлены свойства 
	 * - options;
	 * - parent.
	 * и параметр $property имеет одно из этих значений.
	 * 
	 * Для всех других значений $property вощзвращается false.
	 * 
	 * @param string $property имя свойства
	 * 
	 * @return boolean
	 */
	public function __isset($property) {
		switch ($property) {
			case 'options':
			case 'parent':
				return isset($this->$property);
			default:
				return false;
		}
	}

	/**
	 * Запрещает удаление свойств объекта.
	 * 
	 * @param string $property имя свойства
	 * 
	 * @throws Core_UndestroyablePropertyException Если $property равно:
	 * - parent
	 * - options
	 * 
	 * @throws Core_MissingPropertyException Если $property имеет любое другое значение.
	 */
	public function __unset($property) {
		switch ($property) {
			case 'parent':
			case 'options':
				throw new Core_UndestroyablePropertyException($property);
			default:
				throw new Core_MissingPropertyException($property);
		}
	}
}


/**
 * SQL-маппер
 * 
 * Объекты этого класса обеспечивают связь между объектами бизнес-логики и 
 * таблицами реляционной базы, реализуя в сильно упрощенном виде паттерн data mapper.
 * 
 * @package DB\ORM
 */
class DB_ORM_SQLMapper extends DB_ORM_Mapper
  implements IteratorAggregate,
			 Core_IndexedAccessInterface,
			 Core_CountInterface,
			 DB_ORM_ImmutableMapperInterface,
			 Core_StringifyInterface {
	/**
	 * @var DB_ORM_MappingOptions Коллекция опций
	 */
	protected $options;

	/**
	 * @var boolean Признак неизменяемого состояния объекта 
	 */
	protected $is_immutable = false;

	/**
	 * @var array Внутренний кеш маппера
	 */
	protected $cache = array();
	
	/**
	 * @var array список значений параметров отображаемых объектов
	 */
	protected $binds = array();

	/**
	 * Конструктор
	 * 
	 * @param $parent DB_ORM_SQLMapper родительский маппер
	 * @param $spawn boolean показывает способ, которым создавался объект: 
	 * напрямую или через метод spawn(). Обычно этот параметр устанавливается в 
	 * значение true в методе spawn() класса DB_ORM_Mapper, а по умолчанию имеет 
	 * значение false. См. описание функции setup()
	 * 
	 * @see DB_ORM_SQLMapper::setup()
	 * 
	 */
  public function __construct($parent = null , $spawn = false) {
	parent::__construct($parent);

	$parent_options = $parent instanceof DB_ORM_SQLMapper ? $parent->options : null;
	$this->options = new DB_ORM_MappingOptions($parent_options);
	if (!$spawn) {
	  $this->configure();
	}
	//if (!($this->parent && ($this instanceof $this->parent))) $this->setup();
	$this->as_array(false);
  }

  protected function configure()
  {
	$this->before_setup();
	$this->setup();
	$this->after_setup();
  }

  protected function before_setup()
  {
	return $this;
  }

  protected function after_setup()
  {
	return $this;
  }

	/**
	 * Внутренний метод инициализации
	 * 
	 * Метод предназначен для настройки необходимых опций при создании экземпляра объекта.
	 * 
	 * Внимание! Если класс маппера совпадает с классом родительского маппера, метод вызван не будет, так как 
	 * значения соответствующих опций и так будут наследоваться от родительского маппера. В противном случае 
	 * выполнение операции spawn приводило бы к дублированию соответствующих опций.
	 */
	protected function setup() {return $this;}

  public function __name() {
	if ($this->option('__name'))
	  return $this->option('__name');
	return self::table_from($this);
  }



/**
 * @return DB_ORM_SQLMapper
 */
  public function mode($mode) {
	$this->option('mode', (string) $mode);
	// $this->mode = (string) $mode;
	return $this;
  }

  public function distinct($field = null) {
	$this->mode('DISTINCT');
	if (!is_null($field))
	  $this->option('distinct_field', $field);
	return $this;
  }

	/**
	 * Получение или установка опции.
	 * 
	 * Если значение опции не задано, то метод пытается получить значение свойства.
	 * Если задано, то устанавливает значение свойства.
	 * 
	 * @param string $name имя опции
	 * @param mixed $value значение опции.
	 * 
	 * @return self|mixed 
	 */
	public function option($name, $value = null) {
		if (is_null($value)) {
		  return $this->options[$name];
		}
		$this->options[$name] = $value;
		return $this;
	}

  public function array_option($name, $value, $idx = null) {
	$this->options->array_option($name, $value, $idx);
	return $this;
  }

  public function options($values = array()) {
	foreach ($values as $k => $v) $this->option($k, $v);
	return $this;
  }

	/**
	 * Переводит объект в неизменяемое состояние
	 * 
	 * @return self
	 */
	public function immutable() {
		$this->is_immutable = true;
		return $this;
	}

/**
 * Загружает все записи таблицы в кеш маппера
 * 
 * @return DB_ORM_SQLMapper
 */
  public function preload() {
	$keys = $this->options['key'];
	$composite = count($keys) > 1;

	foreach ($this->select() as $item) {
	  if ($composite) {
		$ids = array();
		foreach ($keys as $v) $ids[] = $item[$v];
		$id = implode(',', $ids);
	  } else $id = $item[$keys[0]];
	  $this->cache($id, $item);
	}
	return $this;
  }



/**
 * @param DB_ORM_SQLMapper|string $mapper
 */
  public function associate_table_for($mapper) {
	$tables = array($this->options['table'][0], self::table_from($mapper));
	sort($tables);
	return  implode(DB_ORM::option('associate_tables_delimiter'), $tables);
  }

/**
 * @param DB_ORM_SQLMapper|string $mapper
 */
  static public function table_from($mapper) {
	return ($mapper instanceof DB_ORM_SQLMapper) ? $mapper->options['table'][0] : (string) $mapper;
  }

/**
 * @param DB_ORM_Entity|int $entity
 */
  static public function id_from($entity) {
	return ($entity instanceof DB_ORM_Entity) ? $entity['id'] : (int) $entity;
  }




/**
 * @return string
 */
  public function as_string() {
	return $this->sql()->select($this->mode)->as_string();
  }

/**
 * @return string
 */
  public function __toString() {
	return $this->as_string();
  }



/**
 * @param DB_ORM_SQLMapper|string $mapper
 * @param DB_ORM_Entity|int|string $entity
 */
  public function map_associated_with($mapper, $entity = null) {
	$this_table = $this->options['table'][0];
	$mapper_table = self::table_from($mapper);
	$assoc_table = $this->associate_table_for($mapper);
	$res = $this->join('inner', $assoc_table,
	  sprintf('%1$s.id = %2$s.%1$s_id', $this_table, $assoc_table));
	if (!is_null($entity))
	  $res = $res->where(sprintf('%1$s.%2$s_id = :%2$s_id', $assoc_table, $mapper_table), self::id_from($entity));
	return $res;
  }

//TODO: название поля id брать из опций маппера

/**
 * @param DB_ORM_SQLMapper|string $mapper
 * @param DB_ORM_Entity|int|string $entity
 */
  public function map_dissociated_with($mapper, $entity) {
	return $this->where(sprintf('id NOT IN (SELECT %1$s.%2$s_id FROM %1$s WHERE %1$s.%3$s_id = :%3$s_id)',
	  $this->associate_table_for($mapper), $this->options['table'][0], self::table_from($mapper)), self::id_from($entity));
  }

/**
 * @param DB_ORM_SQLMapper|string $mapper
 * @param DB_ORM_Entity|int|string $this_entities
 * @param DB_ORM_Entity|int|string $mapper_entities
 */
  public function associate_with($mapper, $this_entities, $mapper_entities) {
	$res = true;
	foreach ((array) $this_entities as $this_entity)
	  foreach((array) $mapper_entities as $mapper_entity)
		$res = $res && $this->connection->execute(
		  sprintf('INSERT INTO %1$s (%2$s_id, %3$s_id) VALUES(:%2$s_id, :%3$s_id)',
			$this->associate_table_for($mapper), self::table_from($mapper), $this->options['table'][0]),
		  array(self::id_from($mapper_entity), self::id_from($this_entity)));
	return $res;
  }

/**
 * @param DB_ORM_SQLMapper|string $mapper
 * @param DB_ORM_Entity|int|string $this_entities
 * @param DB_ORM_Entity|int|string $mapper_entities
 */
  public function dissociate_with($mapper, $this_entities, $mapper_entities) {
	$res = true;
	foreach ((array) $this_entities as $this_entity)
	  foreach((array) $mapper_entities as $mapper_entity)
		$res = $res && $this->connection->execute(
		  sprintf('DELETE FROM %1$s WHERE %2$s_id = :%2$s_id AND %3$s_id = :%3$s_id)',
			$this->associate_table_for($mapper), self::table_from($mapper), $this->options['table'][0]),
		  array(self::id_from($mapper_entity), self::id_from($this_entity)));
	return $res;
  }

/**
 * Создает объект сущности класса, имя которого указано в опции classname
 * 
 * @return mixed
 */
  public function make_entity() {
	$args = func_get_args();
	switch (count($args)) {
	  case 0: $args = array(array(), $this->clear()); break;
	  default: $args[] = $this->clear() ;break;
	}
	$entity = isset($this->options['classname']) ?
	  Core::amake($this->options['classname'], $args) :
	  Core::make(DB::option('collection_class'));

	$array_access = ($entity instanceof ArrayAccess);

	foreach ($this->options['defaults'] as $k => $v)
	  if ($array_access && !isset($entity[$k]))
		$entity[$k] = $v;
	  else if (!isset($entity->$k))
		$entity->$k = $v;

	//$entity->mapper = $this->clear();
	
	$entity->after_make();

	return $entity;
  }

  public function not_in($column, $values) {
	return $this->in($column, $values, true);
  }

  public function in($column, $values, $not = false) {
	if (empty($values)) return $this->where('1 = 0');
	$values = array_values($values);
	$ph = array();
	$not = $not ? 'NOT' : '';
	$placeholder = ":{$column}_{$not}_in_";
	$placeholder = preg_replace('{[^a-z0-9_:]}i', '_', $placeholder);
	foreach ($values as $k => $v) {
	  $ph[] =  $placeholder . $k;
	}
	return $this->where("$column $not IN (" . implode(',', $ph) . ")", $values);
  }

  public function as_array($v = true) {
	$this->option('as_array', $v);
	return $this;
  }

/**
 * Создает курсор для SELECT-запроса маппера и выполняет запрос
 * 
 * @return DB_Cursor
 */
  public function query($execute = true) {
	$c = $this->make_cursor($this->sql()->select($this->mode), !$this->option('as_array'))->
	  bind($this->__get('binds'));
	$this->mode = '';
	return $execute ? $c->execute() : $c;
  }

/**
 * Выполняет SELECT-запрос и выбирает все строки результирующего набора
 * 
 * @return mixed
 */
  public function select($key = null) {return $this->query()->fetch_all(null,  $key);}

/**
 * Выполняет SELECT-запрос и выбирает первую строку результирующего выражения
 * 
 * @return mixed
 */
  public function select_first() { return $this->query()->fetch(); }

/**
 * Выполняет SQL-запрос с дополнительным WHERE-условием и выбирает все строки результирующего набора
 * 
 * @return mixed
 */
  public function select_for() {
	$args = func_get_args();
	list($m, $this->mode) = array($this->mode, '');
	return $this->spawn()->
	  where(array_shift($args), Core::normalize_args($args))->
	  mode($m)->
	  select();
  }

/**
 * Выполняет SELECT-запрос c дополнительным WHERE-условием и выбирает первую строку результата
 * 
 * @return mixed
 */
  public function select_first_for() {
	$args = func_get_args();
	list($m, $this->mode) = array($this->mode, '');
	return $this->spawn()->
	  where(array_shift($args), Core::normalize_args($args))->
	  mode($m)->
	  select_first();
  }

/**
 * @return int
 */
  public function stat($just_count = false) {
	list($m, $this->mode) = array($this->mode, '');
	return Core::with_index($this->make_cursor($this->sql()->stat($just_count, $m), false)->
	  bind($this->__get('binds'))->
		execute()->
		fetch(), 'count');
  }

/**
 * @param boolean $fetch_first
 * @return mixed
 */
  public function stat_all($fetch_first = false) {
	list($m, $this->mode) = array($this->mode, '');
	$r = $this->make_cursor($this->sql()->stat(false, $m), false)->
	  bind($this->__get('binds'))->
	  execute();
	return ($fetch_first ? $r->fetch() : $r->fetch_all());
  }

/**
 * @param args $args
 * @return DB_ORM_SQLMapper
 */
  public function filter($args) {
	switch (true) {
	  case is_string($args): return $this->where($args);
	  default:
		return $this->spawn()->apply_filter($args);
	}
  }

/**
 * Выполняет поиск объекта в таблице по значению первичного ключа
 * 
 * @return mixed
 */
  public function find() {
	$binds = array();
	$values = Core::normalize_args(func_get_args());

	foreach ($this->options['key'] as $idx => $key)
	  $binds[$key] = isset($values[$key]) ? $values[$key] : (isset($values[$idx]) ? $values[$idx] : 0);

	if (($entity = $this->make_cursor($this->sql()->find())->
	  bind(array_merge($this->__get('binds'), $binds))->
	  execute()->
	  fetch()) && method_exists($entity, 'after_find')) $entity->after_find();

	return $entity;
  }

  public function inspect()
  {
	Core::load('DB.Schema');
	return DB_Schema::Table($this->connection)->for_table($this->options['table'][0])->inspect();
  }

/**
 * @param  $value
 * @return mixed
 */
  public function lookup($value) {
	if (($entity = isset($this->options['lookup_by']) ?
	  $this->spawn()->where($this->options['table_prefix'].'.'.$this->options['lookup_by'].'=:__val', $value)->select_first() :
	  null) && method_exists($entity, 'after_find')) $entity->after_find();

	return $entity;
  }

  public function search($value) {
	if ($this->can_cmap('search')) return $this->__call('search', func_get_args());
	$value = str_replace('%', '\%', $value);
	return isset($this->options['search_by']) ?
	  $this->spawn()->where($this->options['table_prefix'].'.'.$this->options['search_by'].' LIKE :__val', "$value%") :
	  $this;
  }

/**
 * Обновляет информацию об объекте бизнес-логики в таблице базе данных
 * 
 * @param mixed $e
 * @return boolean
 */
  public function update($e, $columns = array(), $key_values = array()) {
	list($m, $this->mode) = array($this->mode, '');
	//TODO: refactor
	if (!empty($key_values)) {
	  foreach ($this->options['key'] as $k)
		if (isset($key_values[$k]))
		  $e['key_' . $k] = $key_values[$k];
	}
	$rc =
	  $this->validate($e) && $this->access('update', $e) &&
	  $this->callback($e, 'before_save') && $this->callback($e, 'before_update') &&
	  ($this->make_cursor($this->sql()->update($m, $columns, $key_values))->
		bind($e)->
		execute()->is_successful) &&
	  $this->callback($e, 'after_update') && $this->callback($e, 'after_save');
	Events::call('orm.mapper.change', $rc, $action = 'update', $this, $e);
	Events::call('orm.mapper.update', $rc, $this, $e);
	Events::call("orm.mapper.{$this->__name()}.update", $rc, $this, $e);
	return $rc;
  }

/**
 * Изменяет значение отдельных колонок в таблице для набора записей
 * 
 * @param array $values
 * @param array $columns
 * @return boolean
 */
  public function update_all(array $values, array $calc = array()) {
	list($m, $this->mode) = array($this->mode, '');
	$query = $this->sql()->update_all($m);
	if (!empty($values)) $query->set(array_keys($values));
	if (!empty($calc)) $query->set($calc);
	$rc = $this->make_cursor($query)->
	  bind(array_merge($this->__get('binds'), $values))->
	  execute()->is_successful;
	Events::call('orm.mapper.change', $rc, $action = 'update_all', $this, $e = null);
	Events::call('orm.mapper.update_all', $rc, $this, $e = null);
	Events::call("orm.mapper.{$this->__name()}.update_all", $rc, $this, $e = null);
	return $rc;
  }

/**
 * Добавляет информацию об объекте бизнес логики в таблицу базы данных
 * 
 * @param mixed $e
 * @param string $mode
 * @return int
 */
  public function insert($e) {
	list($m, $this->mode) = array($this->mode, '');
	$rc =
	  $this->validate($e) && $this->access('insert', $e) &&
	  $this->callback($e, 'before_save') && $this->callback($e, 'before_insert') &&
	  $this->make_cursor($this->sql()->insert($m))->
		bind($e)->
		  execute();
	if ($rc) {
	  $id = (int) $this->connection->last_insert_id();
	  if ($id && (count($this->options['key']) == 1) && !$this->options['explicit_key'])
		$e[$this->options['key'][0]] = $id;
	}
	$rc =
	  $rc && $this->callback($e, 'after_insert') && $this->callback($e, 'after_save');
	Events::call('orm.mapper.change', $rc, $action = 'insert', $this, $e);
	Events::call('orm.mapper.insert', $rc, $this, $e);
	Events::call("orm.mapper.{$this->__name()}.insert", $rc, $this, $e);
	return $rc;
  }

/**
 * Удаляет информацию об объекте бизнес-логики из таблицы базы данных
 * 
 * @param mixed $e
 * @return boolean
 */
  public function delete($e) {
	list($m, $this->mode) = array($this->mode, '');
	$rc =
	  $this->access('delete', $e) &&
	  $this->callback($e, 'before_delete') &&
	  $this->make_cursor($this->sql()->delete($m))->
		bind($e)->
		execute()->is_successful &&
	  $this->callback($e, 'after_delete');
	Events::call('orm.mapper.change', $rc, $action = 'delete', $this, $e);
	Events::call('orm.mapper.delete', $rc, $this, $e);
	Events::call("orm.mapper.{$this->__name()}.delete", $rc, $this, $e);
	return $rc;
  }


  public function save($e) {
	$id = null;
	if (method_exists($e, 'id')) {
	  $id = $e->id();
	} else {
	  $key = $this->options['key'];
	  if (is_array($key)) {
		$key = reset($key);
		$id = $e[$key];
	  } else {
		$id = $e['id'];
	  }
	}
	if (!empty($id)) return $this->update($e);
	else return $this->insert($e);
  } 

/**
 * Удаляет набор записей из таблицы
 * 
 * @return boolean
 */
  public function delete_all() {
	list($mode, $this->mode) = array($this->mode, '');
	$rc = $this->make_cursor($this->sql()->delete_all($mode))->
	  bind($this->__get('binds'))->
	  execute()->is_successful;
	Events::call('orm.mapper.change', $rc, $action = 'delete_all', $this, $e = null);
	Events::call('orm.mapper.delete_all', $rc, $this, $e = null);
	 Events::call("orm.mapper.{$this->__name()}.delete_all", $rc, $this, $e = null);
  }



/**
 * @return int
 */
  public function count() { return $this->stat(true); }




/**
 * @param string $method
 * @param array $args
 * @return mixed
 */
  protected function cmap($name, $method, array $args = array()) {
	return is_string($method) ? call_user_func_array(array($this->spawn(), $method), $args) : parent::cmap($name, $method, $args);//$this->__map($name, $args);
  }

/**
 * @param string $method
 * @return mixed
 */
  protected function pmap($name, $method) {
	return is_string($method) ? call_user_func_array(array($this->spawn(), $method), array()) : parent::pmap($name, $method);//$this->__map($name);
  }




/**
 * Порождает дочерний маппер с заданным where-условием
 * 
 * @return DB_ORM_SQLStatement
 */
  public function spawn_for() {
	$args = func_get_args();
	return $this->spawn()->where(array_shift($args), Core::normalize_args($args));
  }



/**
 * Порождает дочерний маппер, c диапазоном записей, соответствущим диапазону объекта-пейджера
 * 
 * @param Data_Pagination $pager
 * @return DB_ORM_SQLMapper
 */
  public function paginate_with(Data_Pagination_Pager $pager) {
	return $this->spawn()->range($pager->items_per_page, $pager->current->offset);
  }



/**
 * Возвращает итератор по записям маппера
 * 
 * @return Iterator
 */
  public function getIterator() { return $this->query(false)->getIterator(); }



/**
 * @param  $args
 * @return DB_ORM_SQLMapper
 */
  protected function apply_filter($args) { return $this; }

/**
 * @param  $entity
 * @return boolean
 */
  public function validate($entity) {
	return isset($this->options['validator']) ?
	  Core::with_index($this->options, 'validator')->validate($entity) :
	  true;
  }

/**
 * @param  $action
 * @param  $entity
 * @return boolean
 */
  public function access($action, $entity = null) {
	$table = self::table_from($this);
	$rc = Events::call('orm.access', $this, $table, $action, $entity);
	if (!is_null($rc)) return $rc;
	$rc = Events::call('orm.access.' . $table, $this, $action, $entity);
	if (!is_null($rc)) return $rc;
	return true;
  }

/**
 * @param  $entity
 * @param string $name
 * @return mixed
 */
  protected function callback($entity, $name) {
	return is_object($entity) && method_exists($entity, $name) ?
	  $entity->$name() : true;
  }

/**
 * @param string $sql
 * @return DB_Cursor
 */
  protected function make_cursor($sql, $typed = true) {
	$cursor = $this->connection->prepare($sql);
	if ($typed) $cursor->as_object($this->make_entity());
	return $cursor;
  }

	/**
	 * Возвращает объект-генератор SQL-выражений, соотаетствующий опциям маппера.
	 * 
	 * @return DB_ORM_SQLBuilder
	 */
	public function sql() { return new DB_ORM_SQLBuilder($this->options); }

	/**
	 * Кеширует бизнес-объект во внутреннем кеше маппера
	 * 
	 * @param integer $index индекс объекта в кеше
	 * @param object $object кешируемый объект
	 * 
	 * @return self
	 */
	protected function cache($index, $object) {
		$this->cache[$index] = $object;
		return $this;
	}

/**
 * Формирует общий список значений параметров с учетом значений параметров родительских мапперов
 * 
 * @param string $expr
 * @param mixed $parms
 * @return DB_ORM_SQLMapper
 */
  protected function collect_binds($expr, $parms) {
	$adapter = $this->connection->adapter;

	if ($adapter->is_castable_parameter($parms)) $parms = $adapter->cast_parameter($parms);

	if ($match = Core_Regexps::match_all('{(?::([a-zA-Z_0-9]+))}', $expr)) {
	  foreach ($match[1] as $no => $name) {
		if (Core_Types::is_array($parms) || $parms instanceof ArrayAccess)
		  $this->binds[$name] = $adapter->cast_parameter(isset($parms[$name]) ? $parms[$name] : $parms[$no]);
		elseif (is_object($parms))
		  $this->binds[$name] = $adapter->cast_parameter($parms->$name);
		else
		  $this->binds[$name] = $adapter->cast_parameter($parms);
	  }
	}

	return $this;
  }



/**
 * Устанавливает имя класса отображаемых объектов бизнес-логики
 * 
 * @param string $name
 * @return DB_ORM_SQLMapper
 */

/**
 * Устанавливает используемый валидатор
 * 
 * @param Validation_Validator $validator
 * @return DB_ORM_SQLMapper
 */

/**
 * Устанавливает имя таблицы, с которой работает маппер
 * 
 * @param string $tablename
 * @return DB_ORM_SQLMapper
 */

/**
 * Определяет список столбцов таблицы, с которой работает маппер.
 * 
 * @return DB_ORM_SQLMapper
 */

/**
 * Устанавливает список вычисляемых полей результирующего набор
 * 
 * @param array $columns
 * @return DB_ORM_SQLMapper
 */

/**
 * Устанавливает список полей, которые должны быть исключены из результирующего набора
 * 
 * @return DB_ORM_SQLMapper
 */

/**
 * Устанавливает набор полей, используемых в качестве первичного ключа.
 * 
 * @return DB_ORM_SQLMapper
 */

/**
 * Устанавливает необходимость явного задания значения для одиночного первичного ключа
 * 
 * @param boolean $explicit
 * @return DB_ORM_SQLMapper
 */

/**
 * Описывает SQL-выражение join, используемое при выборке
 * 
 * @param string $type
 * @param string $table
 * @param string $condition
 * @return DB_ORM_SQLMapper
 */

/**
 * Устанавливает WHERE-условие выборки
 * 
 * @param string|array $expr
 * @param  $parms
 * @return DB_ORM_SQLMapper
 */

/**
 * Устанавливает HAVING-условие выборки
 * 
 * @param string|array $expr
 * @param  $parms
 * @return DB_ORM_SQLMapper
 */

/**
 * Устанавливает SQL-выражение ORDER BY
 * 
 * @return DB_ORM_SQLMapper
 */

/**
 * Устанавливает SQL-выражение GROUP BY
 * 
 * @return DB_ORM_SQLMapper
 */

/**
 * Устанавливает диапазон выборки результирующего набора
 * 
 * @param int $limit
 * @param int $offset
 * @return DB_ORM_SQLMapper
 */

/**
 * Устанавливает явное имя индекса, используемого при выборке
 * 
 * @param string $name
 * @return DB_ORM_SQLMapper
 */



	/**
	 * Выполняет диспетчеризацию вызово динамических методов.
	 * 
	 * Метод обрабатывает вызовы методов установки опций маппера. 
	 * С деталями можно ознакомиться в описаниях соответствующих методов.
	 * 
	 * @param string $method имя метода
	 * @param mixed $args аргументы метода
	 * 
	 * @return self
	 */
	public function __call($method, $args) {
		switch ($method) {
			case 'classname':
			case 'column':
			case 'validator':
			case 'table':
			case 'calculate':
	  case 'explicit_key':
	  case 'lookup_by':
	  case 'search_by':
	  case 'index':
	  case 'defaults':
		$this->options->__call($method, $args);
		return $this;
			case 'order_by':
			case 'group_by':
			case 'range':
				if ($this->is_immutable) {
					return $this->spawn()->__call($method, $args);
				} else {
					$this->options->__call($method, $args);
					return $this;
				}
			case 'key':
			case 'columns':
		$this->options->$method(Core::normalize_args($args));
		return $this;
			case 'only':
			case 'exclude':
				if ($this->is_immutable) {
					return $this->spawn()->__call($method, $args);
				} else {
					$this->options->$method(Core::normalize_args($args));
					return $this;
				}
			case 'having':
			case 'where':
				if ($this->is_immutable) return $this->spawn()->__call($method, $args);

				$expr = isset($args[0]) ? $args[0] : null;
				$parms = isset($args[1]) ? $args[1] : null;

				if (is_array($expr)) $expr = '('.implode(') AND (', $expr).')';
				if ($parms !== null) $this->collect_binds($expr, $parms);

				$this->options->$method($expr);
				return $this;
			case 'join':
				if ($this->is_immutable) return $this->spawn()->__call($method, $args);
				$type  = array_shift($args);
				$table = array_shift($args);
				$this->options->$method($type, $table, Core::normalize_args($args));
				return $this;
			default:
				return parent::__call($method, $args);
		}
	}

	/**
	 * Загружает отображаемый объект и сохраняет его в кэше маппера
	 * 
	 * Интерфейс индексируемого доступа предназначен загрузки объектов с использованием кэширования.
	 * 
	 * Иначе говоря, вызов $mapper[$id] эквивалентен вызову $mapper->find($id), за исключением того, что в первом 
	 * случае загруженный объект будет сохранен в кэше маппера. Поэтому, повторное обращение $mapper[$id] при 
	 * том же значении id вернет соответствующий объект без обращения к базе.
	 * 
	 * Этот мезанизм может быть использован для реализации загрузки объекта в случае связи "один ко многим". 
	 * Например, пусть у нас есть статьи и рубрики, причем каждая статья принадлежит рубрике, что определяется 
	 * полем category_id в таблице статей. Предположим, что метод DB::db() возвращает нам корневой элемент нашего 
	 * дерева мапперов. В этом случае в объекте предметной области класса Story  мы можем реализовать следующий 
	 * метод:
	 * 
	 * <code>
	 * class Story {
	 * 		function get_category() {
	 *			return DB::db()->categories[$this['category_id']];
	 *		}
	 * </code>
	 * 
	 * @param string|integer $index значение первичного ключа записи, соответствующей объекту
	 * 
	 * return mixed
	 */
	public function offsetGet($index) {
	if (!isset($this->cache[$index]) && (($e = $this->find($index)) || ($e = $this->lookup($index)))) {
	  $this->cache[$index] = $e;
	}
	return isset($this->cache[$index])?$this->cache[$index]:null;
  }

	/**
	 * Запрещает явную запись объектов в кэш.
	 * 
	 * Объект может быть помещен в кэш только при выполнении операции find, инициированной доступом к 
	 * индексированному свойству. Однако он может быть явно удален из кэша с помощью операции unset.
	 * 
	 * @param string|integer $index
	 * @param mixed $value значение свойства
	 * 
	 * @throws Core_ReadOnlyIndexedPropertyException Если объект находится в кеше
	 * @throws Core_MissingIndexedPropertyException Если объект отсутствует в кеше
	 */
	public function offsetSet($index, $value) {
		throw isset($this->cache[$index]) ?
			new Core_ReadOnlyIndexedPropertyException($index) :
			new Core_MissingIndexedPropertyException($index);
	}

	/**
	 * Проверяет наличие объекта в кэше маппера
	 * 
	 * @param string|integer значение первичного ключа записи, соответствующей объекту
	 * 
	 * @return boolean
	 */
	public function offsetExists($index) { return isset($this->cache[$index]); }

	/**
	 * Удаляет объект из кэша маппера
	 * 
	 * Повторное обращение по индексу, соответствующему удаленному объекту, 
	 * приведет к новому обращению к базе данных.
	 * 
	 * @param string|integer значение первичного ключа записи, соответствующей объекту
	 */
	public function offsetUnset($index) { unset($this->cache[$index]);  }



/**
 * Возвращает значение свойства объекта
 * 
 * @param string $property
 * @return mixed
 */
	/**
	 * Возвращает значение свойства объекта
	 * 
	 * @todo Дописать коммент для возвращаемых свойств.
	 * 
	 * Поддерживаются следующие свойства:
	 * - mode ;
	 * - connection ;
	 * - schema ;
	 * - options объект класса DB.ORM.MapperOptions, содержащий значения опций маппера;
	 * - cache ArrayObject кэш отображаемых объектов;
	 * - binds array список значений параметров отображаемых объектов (учитывая родителя);
	 * 
	 * Для остальных значений вызывается parent::__get($property)
	 * 
	 * @param string $property имя свойства
	 * 
	 * @return mixed
	 */
	public function __get($property) {
		switch ($property) {
			case 'mode':
				return $this->option($property);
			case 'connection':
				return $this->session->connection_for($this->options['table'][0]);
			case 'schema':
				return $this->connection->get_schema();
			case 'options':
				return $this->$property;
			case 'cache':
				return new ArrayObject($this->cache);
			case 'binds':
				return array_merge((array)$this->parent->__get('binds'), $this->binds);
			default:
				return parent::__get($property);
		}
	}

	/**
	 * Изменение свойств объекта.
	 * 
	 * Для свойства mode если у текущего объекта существует родитель и у родителя 
	 * есть метод option, то выполняется установка свойства не только текущего объекта, 
	 * но и родителя.
	 * 
	 * @throws Core_ReadOnlyIndexedPropertyException При попытке установки свойств:
	 * - options,
	 * - cache,
	 * - binds,
	 * - connection, 
	 * - schema.
	 * 
	 * Для всех остальных свойств вызывается parent::__set($property, $value)
	 * 
	 * @param string $property имя свойства
	 * @param mixed $value значение свойства
	 * 
	 * @return self
	 */
	public function __set($property, $value) {
		switch ($property) {
			case 'mode':
				$this->option($property, $value);
				if ($this->parent && method_exists($this->parent, 'option')) 
					$this->parent->option($property, $value);
				return $this;
			case 'options':
			case 'cache':
			case 'binds':
			case 'connection':
			case 'schema':
				throw new Core_ReadOnlyIndexedPropertyException($property);
			default:
				return parent::__set($property, $value);
		}
	}

	/**
	 * Проверяет установку свойства объекта
	 * 
	 * Для значений connection и schema пробует получить их значения через 
	 * __get и возвращает булев результат выполнения.
	 * 
	 * Для options, cache, binds возвращает true.
	 * 
	 * Для всех остальных значений выполняет проверку в родителе.
	 * 
	 * @param string $property имя свойства
	 * 
	 * @return boolean
	 */
	public function __isset($property) {
		switch ($property) {
			case 'connection': case 'schema':
				return (bool) $this->__get($property);
			case 'options':
			case 'cache':
			case 'binds':
				return true;
			default:
				return parent::__isset($property);
		}
	}

	/**
	 * Запрещает удаление свойства объекта
	 * 
	 * @param string $property имя свойства
	 * 
	 * @throws Core_UndestroyablePropertyException Если $property имеет одно из значений:
	 * - options
	 * - cache
	 * - binds
	 * - connection
	 * - schema
	 * 
	 * Для всех остальных значений вызывает parent::__unset($property)
	 */
	public function __unset($property) {
		switch ($property) {
			case 'options':
			case 'cache':
			case 'binds':
			case 'connection':
			case 'schema':
				throw new Core_UndestroyablePropertyException($property);
			default:
				return parent::__unset($property);
		}
	}
}


class DB_ORM_SQLBuilder {

  protected $options;


/**
 * Конструктор
 * 
 * @param DB_ORM_MappingOptions $options
 */
  public function __construct(DB_ORM_MappingOptions $options) { $this->for_options($options); }



/**
 * Устанавливает опции генерации SQL-запросов
 * 
 * @param DB_ORM_MappingOptions $options
 * @return DB_ORM_SQLBuilder
 */
  public function for_options(DB_ORM_MappingOptions $options) {
	$this->options = $options;
	return $this;
  }



/**
 * Генерирует SQL-выражение SELECT, выбирающее вся строки
 * 
 * @return DB_ORM_SQL_Select
 */
  public function select($mode = '') {
	$sql = $this->select_with_options(
	  $this->options['result'],
	  array('join', 'where', 'group_by', 'having', 'order_by', 'index'), $mode);

	return ($r = $this->options['range']) ? $sql->range($r[0], $r[1]) : $sql;
  }

/**
 * Генерирует SQL-выражение SELECT, выбирающее одну строку по первичному ключу
 * 
 * @return DB_ORM_SQL_Select
 */
  public function find() {
	$table  = $this->options['table'];
	$prefix = isset($table[1]) ? $table[1] : $table[0];

	$keys   = array();
	foreach ($this->options['key'] as $v) $keys[] = "$prefix.$v = :$v";

	return $this->select_with_options(
	  $this->options['result'],
	  array('join', 'where', 'group_by', 'having', 'order_by', 'index'))->
		where($keys)->range(1);
  }

/**
 * Генерирует SQL-выражение SELECT, подсчитывающее вычисляемыю статистику
 * 
 * @param boolean $just_count
 * @return DB_ORM_SQL_SelectStatement
 */
  public function stat($just_count = false, $mode = '') {
	$what = '*';
	if (strtolower($mode) == 'distinct' && $this->options['distinct_field']) {
	  $what = $mode . ' ' . $this->options['distinct_field'];
	  $mode = '';
	}

	return $just_count ?
	  $this->select_with_options(
		"COUNT($what) count",
		array('join', 'where', 'group_by', 'having', 'index'),
		$mode) :
	  $this->select_with_options(
		Core::if_not_set($this->options, 'calculate', "COUNT($what) count"),
		array('join', 'where', 'group_by', 'having', 'order_by', 'index'),
		$mode);
  }

/**
 * Генерирует SQL-выражение INSERT
 * 
 * @param string $mode
 * @return DB_ORM_SQL_Insert
 */
  public function insert($mode = '') {
	$o = $this->options;

	$auto = (count($o['key']) == 1 && !$o['explicit_key']) ? $o['key'][0] : false;

	$cols = array();
	foreach ($o['columns'] as $v) if (!($auto && $v == $auto)) $cols[] = $v;
	return DB_ORM_SQL::Insert($cols)->mode($mode)->into($o['table'][0]);
  }

/**
 * Генерирует SQL-выражение DELETE для удаления одной строки по первичному ключу
 * 
 * @return DB_ORM_SQL_Delete
 */
  public function delete($mode = '') {
	$keys = array();
	foreach ($this->options['key'] as $v) $keys[] = "$v = :$v";

	return DB_ORM_SQL::Delete($this->options['table'][0])->where($keys)->mode($mode);
  }

/**
 * Генерирует SQL-выражение DELETE для удаления всех строк
 * 
 * @return DB_ORM_SQL_Delete
 */
  public function delete_all($mode = '') {
	$sql = DB_ORM_SQL::Delete($this->options['table'][0])->mode($mode);
	return isset($this->options['where']) ? $sql->where($this->options['where']) : $sql;
  }

/**
 * Генерирует SQL-выражение UPDATE для обновления одной записи по первичному ключу
 * 
 * @return DB_ORM_SQL_Update
 */
  public function update($mode = '', $columns = array(), $key_values = array()) {
	$keys = array();
	foreach ($this->options['key'] as $v) $keys[] =  "$v =  :" . (isset($key_values[$v]) ? 'key_' : '') . $v;

	return DB_ORM_SQL::Update($this->options['aliased_table'])->
	  mode($mode)->
	  set(count($columns) ? array_intersect($this->options['columns'], $columns) : $this->options['columns'])->
	  where($keys);
  }

/**
 * Генерирует SQL-выражение UPDATE для обновления всех записей
 * 
 * @return DB_ORM_SQL_Update
 */
  public function update_all($mode = '') {
	return DB_ORM_SQL::Update($this->options['aliased_table'])->
	  mode($mode)->
	  where($this->options['where']);
  }



/**
 * Формирует SQL-выражение SELECT с указанными опциями
 * 
 * @param  $what
 * @param array $options
 * @return DB_ORM_SQL_Statement
 */
  protected function select_with_options($what, array $options = array(), $mode = '') {
	$sql = DB_ORM_SQL::Select($what)->mode($mode)->from($this->options['aliased_table']);

	foreach ($options as $opt) {
	  switch ($opt) {
		case 'join':
		  foreach ($this->options['join'] as $j) $sql->join($j[0], $j[1], $j[2]);
		  break;
		case 'where':
		case 'group_by':
		case 'having':
		case 'order_by':
		case 'index':
		  if (isset($this->options[$opt])) $sql->$opt($this->options[$opt]);
		  break;
	  }
	}
	return $sql;
  }

}


class DB_ORM_MapperSet extends DB_ORM_Mapper {

  private $cache   = array();
  private $mappers = array();


/**
 * @param DB_ORM_Mapper $parent
 */
  public function __construct(DB_ORM_Mapper $parent = null) {
	parent::__construct($parent);
	$this->setup();
  }

/**
 * @return DB_ORM_MapperSet
 */
  protected function setup() { return $this; }



/**
 * @param array $mappers
 * @return DB_ORM_MapperSet
 */
  public function submappers(array $mappers, $prefix = '') {
	foreach ($mappers as $k => $v)
	  $this->mappers[is_numeric($k) ? strtolower($v) : $k] = "$prefix$v";
	return $this;
  }

/**
 * @param array $mappers
 * @return DB_ORM_MapperSet
 */
// TODO: deprecated
  public function with_submappers(array $mappers, $prefix = '') {
	return $this->submappers($mappers, $prefix);
  }

/**
 * @param string $mapper
 * @param string $module
 * @return DB_ORM_MapperSet
 */
  public function submapper($mapper, $module) {
	$this->mappers[(string) $mapper] = (string) $module;
	return $this;
  }

/**
 * @param string $mapper
 * @param string $module
 * @return DB_ORM_MapperSet
 */
// TODO: deprecated
  public function with_submapper($mapper, $module) {
	return $this->submapper($mapper, $module);
  }



/**
 * Реализация операции pmap: отображение свойства маппера
 * 
 * @param string $name
 * @param string $method
 * @return mixed
 */
  protected function pmap($name, $method) {
	if (!isset($this->cache[$name]))
	  $this->cache[$name] = parent::pmap($name, $method);
	return $this->cache[$name];
  }

/**
 * @param string $name
 * @param boolean $is_call
 * @return boolean
 */
	public function __can_map($name, $is_call = false) { return isset($this->mappers[$name]); }

/**
 * @param string $name
 * @param  $args
 * @return DB_ORM_Mapper
 */
	public function __map($name, $args = null) {
	  $r = $this->load($this->mappers[$name], $name);
	  if (method_exists($this, 'created_from_mapperset'))
		$r->created_from_mapperset($this, $name);
	  return $r;
	}



/**
 * @param string $module
 * @return DB_ORM_MapperSet
 */
  protected function load_mappers_from($module) {
	Core::load($module);
	return call_user_func(array(Core_Types::real_class_name_for($module), 'mappers'), $this);
  }

/**
 * @param string $mapper
 * @return DB_ORM_Mapper
 */
  protected function load($mapper, $name) {
	if (preg_match('{^(?:(.+)\.)?([a-zA-Z0-9]+|\*)$}', $mapper, $m)) {
	  if ($m[2] == '*') {
		if ($m[1]) Core::load($m[1]);
		return call_user_func(array(Core_Types::real_class_name_for($m[1]), 'mappers'), $this);
	  }
	  $m = Core::make($mapper, $this);
	  $m->option('__name', $name);
	  return $m;
	}
  }

}

interface DB_ORM_ConnectionMapperInterface {
  public function connect($connection);
}

abstract class DB_ORM_ConnectionMapper extends DB_ORM_MapperSet implements DB_ORM_ConnectionMapperInterface {

  const DEFAULT_CONNECTION_NAME = 'default';
  
  protected $connections;
  protected $active_connection = self::DEFAULT_CONNECTION_NAME;
  protected $connections_by_tables = array();
  
  protected function pmap_connection() {
	return $this->connections[$this->active_connection];
  }
  
  public function connection_for($table = null) {
	if (!empty($this->connections_by_tables[$table]))
	  return $this->connections[$this->connections_by_tables[$table]];
	return $this->pmap_connection();
  }
  
  public function connection($name = 'default') {
	return $this->connections[$name];
  }
  
  public function activate_connection($name) {
	$this->active_connection = $name;
	return $this;
  }
  
  public function reset_connection() {
	$this->activate_connection = self::DEFAULT_CONNECTION_NAME;
	return $this;
  }


  public function tables(array $tables) {
	$this->connections_by_tables = array_merge($this->connections_by_tables, $tables);
	return $this;
  }
  
  public function table($table, $connaction_name) {
	$this->connections_by_tables[$table] = $connaction_name;
	return $this;
  }

/**
 * Устанавливает объект подключения к базе данных или создает на основании DSN
 * 
 * @param string|DB_Connection $connection
 * @param string $name
 * @return DB_ORM_ConnectionMapper
 */
  public function connect($connection, $name = self::DEFAULT_CONNECTION_NAME) {
	$this->connections[$name] = ($connection instanceof DB_Connection) ?
	  $connection :
	  DB::Connection((string) $connection);
	return $this;
  }

  public function __sleep() {
	return array('active_connection', 'connections_by_tables');
  }

  public function __wakeup() {
	foreach (WS::env()->db as $name => $conn) {
	  $this->connections[$name] = $conn;
	}
	return $this;
  }

}


interface DB_ORM_EntityInterface extends Core_PropertyAccessInterface, Core_IndexedAccessInterface {}


interface DB_ORM_AttrEntityInterface extends DB_ORM_EntityInterface, Object_AttrListInterface {}


abstract class DB_ORM_Entity
  extends Events_Observer
  implements DB_ORM_EntityInterface/*, Core_CallInterface, IteratorAggregate*/, Core_StringifyInterface {

  protected $attrs = array();
  protected $mapper = null;
  protected $enable_dispatch = false;


/**
 * @param array $attrs
 */
  public function __construct(array $attrs = array(), $mapper = null) {
	parent::__construct();
	if ($mapper)
	  $this->mapper = $mapper;
	$this->setup()->assign($attrs); }



/**
 * @return DB_ORM_Entity
 */
  protected function setup() { $this->dispatch('setup'); return $this; }

/**
 * @param array $values
 * @return DB_ORM_Entity
 */
  protected function defaults(array $values) {
	foreach ($values as $k => $v) $this[$k] = $v;
	return $this;
  }



/**
 * @return DB_ORM_Entity
 */
  public function assign(array $attrs) {
	foreach ($attrs as $k => $v) $this->__set($k, $v);
	return $this;
  }

  public function assign_attrs(array $attrs) {
	foreach ($attrs as $k => $v) $this->set($k, $v);
	return $this;
  }
  
  public function id() {
	if ($mapper = $this->get_mapper()) {
	  $key = $this->key();
	  return $this->$key;
	}
	return $this['id'];
  }

  public function is_phantom()
  {
	$id = $this->id();
	return empty($id);
  }
  
  public function key() {
	if ($mapper = $this->get_mapper()) {
	  $key = $mapper->options['key'];
	  if (Core_Types::is_iterable($key))
		return current($key);
	}
	return 'id';
  }
  
  protected function cache_dir_name() {
	  return '_cache';
  }

  public function cache_dir_path($p=false) {
	$path = $this->homedir($p);
	$path .= '/'.$this->cache_dir_name();
	return $path;
  }

  protected function mnemocode() {
	if ($this->mapper) {
	  if (isset($this->mapper->options['table'][0]))
		return $this->mapper->options['table'][0];
	}
	return strtolower(get_class($this));
  }

  protected function homedir_location($private=false) {
	return ($private?'../':'') . Core::option('files_name') . '/' . $this->mnemocode();
  }

  public function homedir($p=false) {
	if ($this->id()==0) return false;
	$private = false;
	$path = false;
	if ($p===true) $private = true;
	if (is_string($p)) $path = $p;

	$dir = $this->homedir_location($private);
	$id = $this->id();
	$did = (int)floor($id/500);
	$s1 = str_pad((string)$id, 4,'0',STR_PAD_LEFT);
	$s2 = str_pad((string)$did,4,'0',STR_PAD_LEFT);
	$dir = "$dir/$s2/$s1";
	if ($path) $dir .= "/$path";
	return $dir;
  }
  
  public function set_mapper($mapper) {
	if ($mapper instanceof DB_ORM_SQLMapper) {
	  $class = Core_Types::real_class_name_for($mapper->options['classname']);
	  if ($this instanceof $class)
		$this->mapper = $mapper;
	}
	return $this;
  }
  
  public function get_mapper() {return $this->mapper ? $this->mapper->spawn() : null;}

  public function after_insert() {return $this->dispatch_res('after_insert', true);}
  public function after_update() {return $this->dispatch_res('after_update', true);}
  public function after_delete() {return $this->dispatch_res('after_delete', true);}
  public function after_save() {return $this->dispatch_res('after_save', true);}

  public function before_insert() {return $this->dispatch_res('before_insert', true);}
  public function before_update() {return $this->dispatch_res('before_update', true);}
  public function before_delete() {return $this->dispatch_res('before_delete', true);}
  public function before_save() {return $this->dispatch_res('before_save', true);}

  public function after_find() {return $this->dispatch_res('after_find', true);}
  public function after_make() {return $this->dispatch_res('after_make', true);}
  
  
  public function update() {
	$args = func_get_args();
	array_unshift($args, $this);
	return Core::invoke(array($this->get_mapper(), 'update'), $args);
  }
  
  public function insert() {
	$args = func_get_args();
	array_unshift($args, $this);
	return Core::invoke(array($this->get_mapper(), 'insert'), $args);
  }
  
  public function delete() {
	$args = func_get_args();
	array_unshift($args, $this);
	return Core::invoke(array($this->get_mapper(), 'delete'), $args);
  }

  public function save() {
	$args = func_get_args();
	array_unshift($args, $this);
	return Core::invoke(array($this->get_mapper(), 'save'), $args);
  }
  
  public function get($name, $method = null) {
	$default = isset($this->attrs[(string) $name]) ? $this->attrs[(string) $name] : null;
	return $method ? $this->dispatch_res($method, $default) : $default;
  }
  
  public function set($name, $value, $method = null) {
	$this->attrs[(string) $name] =  $value;
	if ($method) { 
		$this->dispatch($method, null, $value);
	}
	return $this;
  }


/**
 * @param string $index
 * @return mixed
 */
  public function offsetGet($index) {
	switch (true) {
	  case method_exists($this, $name = "row_get_$index"):
		return $this->$name();
	  case $index === '__class':
		return Core_Types::real_class_name_for($this);
	  default:
		return $this->get($index, $name);
	}
  }

/**
 * @param string $index
 * @param  $value
 * @return mixed
 */
  public function offsetSet($index, $value) {
	switch (true) {
	  case method_exists($this, $name = "row_set_$index"):
		$this->$name($value);
		break;
	  case $index === '__class':
		break;
	  default:
		return $this->set($index, $value, $name);
	}
	return $this;
  }

/**
 * @param string $index
 * @return boolean
 */
  public function offsetExists($index) {
	return (array_key_exists($index    , $this->attrs) ||
			method_exists($this, "row_get_$index"));
  }

/**
 * @param string $index
 * @return boolean
 */
  public function offsetUnset($index) { throw new Core_ReadOnlyObjectException($this); }



/**
 * @param string $property
 * @return mixed
 */
  public function __get($property) {

	switch ($property) {
	  case 'attrs':
	  case 'attributes':
		return $this->attrs;
	  default:
		if (method_exists($this, $method = "get_$property"))
		  return $this->$method();
		else
		  return $this->get($property, $method);
	}
  }

/**
 * @param string $property
 * @param  $value
 * @return mixed
 */
  public function __set($property, $value) {
	switch ($property) {
	  case 'attrs':
		$this->attrs = $value;
		return $this;
	  case 'attributes':
		throw new Core_ReadOnlyPropertyException($property);
	  default:
		if (method_exists($this, $method = "set_$property"))
		  {$this->$method($value); return $this;}
		else
		  return $this->set($property, $value, $method);
	}
  }

/**
 * @param string $property
 * @return boolean
 */
  public function __isset($property) {
	switch ($property) {
	  case 'attrs':
	  case 'attributes':
		return true;
	  default:
		return method_exists($this, "get_$property") || isset($this[$property]);
	}
  }

/**
 * @param string $property
 */
  public function __unset($property) {
	switch ($property) {
	  case 'attrs':
	  case 'attributes':
		throw new Core_UndestroyablePropertyException($property);
	  default:
		throw new Core_MissingPropertyException($property);
	}
  }



/**
 * @param string $method
 * @return mixed
 */
  public function  __call($method, $args) {
	switch (count($args)) {
	  case 1: return $this->dispatch_res($method, null, null, $args[0]);
	  case 2: return $this->dispatch_res($method, null, null, $args[0], $args[1]);
	  default: return $this->dispatch_res($method, null, null);
	}
  }



/**
 * @return Iterator
 */
  //public function getIterator() {
	//return new ArrayIterator($this->attrs);
  //}

  public function as_string() {
	$res = '';
	foreach(array('title', 'name', 'id') as $name)
	  if (!empty($this[$name])) {
		$res = $this[$name];
		break;
	  }
	return (string) $res;
  }
  
  public function __toString() {
	return $this->as_string();
  }

  public function __sleep() {
	if ($this->mapper && $name = $this->mapper->option('__name')) {
	  $this->attrs['__mapper_name'] = $name;
	}
	return array('attrs', 'enable_dispatch', 'dispatcher');
  }

  public function __wakeup() {
	if (isset($this->attrs['__mapper_name'])) {
	  $name = $this->attrs['__mapper_name'];
	  $this->set_mapper(WS::env()->orm->$name);
	}
	// $this->after_find();
  }

}

