<?php
/// <module name="Search.Sphinx" version="0.2.2" maintainer="svistunov@techart.ru">
///  <brief>Модуль предоставляющий интерфейс для доступа к полнотекстовому поисковому движку Sphinx</brief>
///  <details>
///   За болеее подробной информацией обращайтесь к <a href="http://www.sphinxsearch.com/docs/">документации Sphinx</a>
///  </details>

Core::load('Object');

/// <class name="Search.Sphinx" stereotype="module">
class Search_Sphinx implements Core_ModuleInterface {

///   <constants>
  const VERSION = '0.2.2';
///   </constants>

  const DEFAULT_RANGE = 20;

///   <protocol name="building">

///   <method name="Client" scope="class" returns="Search.Sphinx.Client">
///     <brief>Фабричный метод, возвращает объект класса Search.Sphinx.Client</brief>
///     <args>
///       <arg name="dsn" type="string" brief="строка подключения" />
///       <arg name="mode" type="int" default="SPH_MATCH_EXTENDED" brief="метод Sphinx" />
///     </args>
///     <body>
  static public function Client($dsn = 'sphinx://localhost:3312', $mode = SPH_MATCH_EXTENDED) {
    return new Search_Sphinx_Client($dsn, $mode);
  }
///     </body>
///   </method>

///   <method name="Query" scope="class" returns="Search.Sphinx.Query">
///     <brief>Фабричный метод. возвращает объект класса Search.Sphinx.Query</brief>
///     <args>
///       <arg name="client" type="Search.Sphinx.Client" brief="клиент" />
///       <arg name="expression" type="string" brief="выражение поиска" />
///     </args>
///     <body>
  static public function Query(Search_Sphinx_Client $client, $expression) {
    return new Search_Sphinx_Query($client, $expression);
  }
///     </body>
///   </method>

///   </protocol>
}
/// </class>


/// <interface name="Search.Sphinx.ResolverInterface">
///   <brief>Интерфейс для перевода результата в требуемый формат</brief>
interface Search_Sphinx_ResolverInterface {

///   <protocol name="performing">

///   <method name="load" returns="mixed">
///     <brief>Возвращает набор объектов по входному результату поиска Sphinx</brief>
///     <args>
///       <arg name="matches" type="array" brief="результату поиска Sphinx" />
///     </args>
///     <body>
  public function load(array $matches);
///     </body>
///   </method>
///   </protocol>
}
/// </interface>


/// <class name="Search.Sphinx.Client">
///   <brief>Клиент для обращения к Sphinx</brief>
///   <implements interface="Core.CallInterface" />
class Search_Sphinx_Client implements Core_CallInterface {

  private $client;

///   <protocol name="creating">

///   <method name="__construct">
///     <brief>Конструктор</brief>
///     <args>
///       <arg name="dsn" type="string" default="sphinx://localhost:3312" brief="строка подключения" />
///       <arg name="mode" type="int" default="SPH_MATCH_EXTENDED" brief="тип поиска - константа Sphinx" />
///     </args>
///     <body>
  public function __construct($dsn = 'sphinx://localhost:3312', $mode = SPH_MATCH_EXTENDED) {
    if (!($m = Core_Regexps::match_with_results('{^sphinx://([a-zA-Z./]+)(:?:(\d+))?}', (string) $dsn)))
    throw new Search_Sphinx_Exception("Bad DSN $dsn");

    $this->client = new SphinxClient();

    $this->catch_errors(
    ($this->client->SetServer($m[1], (int) Core::if_not($m[3], 3312)) !== false) &&
    ($this->client->setMatchMode($mode) !== false) &&
    ($this->client->setArrayResult(true) !== false));
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="performing">

///   <method name="reset" returns="Search.Sphinx.Client">
///     <brief>Сбрасывает настройки</brief>
///     <body>
  public function reset() {
    return $this->catch_errors(
      ($this->client->ResetFilters() !== false) &&
      ($this->client->ResetGroupBy() !== false) &&
      ($this->client->SetLimits(0, Search_Sphinx::DEFAULT_RANGE) !== false) &&
      ($this->client->SetSortMode(SPH_SORT_RELEVANCE) !== false));
  }
///     </body>
///   </method>

///   <method name="range">
///     <brief>задает интервал поиска</brief>
///     <args>
///       <arg name="limit" type="int" brief="количество возвращаемых элементам" />
///       <arg name="offset" type="int" default="0" brief="смещение" />
///     </args>
///     <body>
  public function range($limit, $offset = 0) {
    return $this->catch_errors(
    $this->client->SetLimits($offset, $limit) !== false);
  }
///     </body>
///   </method>

///   <method name="group_by" returns="Search.Sphinx.Client">
///     <brief>Группирует элементы поиска</brief>
///     <args>
///       <arg name="attribute" type="string" brief="атрибут" />
///       <arg name="func" type="int" default="SPH_GROUPBY_ATTR" brief="функция группировки" />
///       <arg name="group_sort" type="string" default="'@group desc'" brief="сортировка в группе" />
///     </args>
///     <body>
  public function group_by($attribute, $func = SPH_GROUPBY_ATTR, $group_sort = '@group desc') {
    return $this->catch_errors($this->client->SetGroupBy($attribute, $func, $group_sort) !== false);
  }
///     </body>
///   </method>

///   <method name="filter" returns="Search.Sphinx.Client">
///     <brief>Устанавливает фильтр поиска</brief>
///     <args>
///       <arg name="attribute" type="string" brief="атрибут" />
///       <arg name="value" brief="значение" />
///       <arg name="exclude" type="boolean" default="false" brief="инвертирует поиск" />
///     </args>
///     <body>
  public function filter($attribute, $values, $exclude = false) {
    return $this->catch_errors($this->client->SetFilter($attribute, (array) $values, $exclude) !== false);
  }
///     </body>
///   </method>

///   <method name="where" returns="Search.Sphinx.Client">
///     <brief>Устанавливает фильтер поиска</brief>
///     <args>
///       <arg name="attribute" type="string" brief="атрибут" />
///       <arg name="min" type="int" brief="минимальное значение" />
///       <arg name="max" type="int" brief="максимальное значение" />
///       <arg name="exclude" type="boolean" default="false" brief="инвертирует поиск" />
///     </args>
///     <body>
  public function where($attribute, $min, $max, $exclude = false) {
    return $this->catch_errors($this->client->SetFilterRange($attribute, $min, $max, $exclude) !== false);
  }
///     </body>
///   </method>

///   <method name="between" returns="Search.Sphinx.Client">
///     <brief>Задает интервал поиска</brief>
///     <args>
///       <arg name="min" type="int" brief="минимальное значение" />
///       <arg name="max" type="int" brief="максимальное значение" />
///     </args>
///     <body>
  public function between($min, $max) {
    return $this->catch_errors($this->client->SetIDRange($min, $max) !== false);
  }
///     </body>
///   </method>

///   <method name="between" returns="Search.Sphinx.Client">
///     <brief>Устанавливает сортировку результата</brief>
///     <args>
///       <arg name="mode" type="string" brief="тип сортировки" />
///       <arg name="expr" type="string" brief="выражение для сортировке" />
///     </args>
///     <body>
  public function sort_by($mode, $expr = '') {
    return $this->catch_errors($this->client->SetSortMode($mode, $expr) !== false);
  }
///     </body>
///   </method>

///   <method name="query" returns="Search.Sphinx.Results">
///     <brief>Выполняет запрос поиска и возвращает объек Search.Sphinx.Results с результатами</brief>
///     <args>
///       <arg name="expression" type="string" brief="выражение поиска" />
///       <arg name="index" type="string" default="'*'" brief="Sphinx индекс" />
///       <arg name="resolver" type="Search.Sphinx.ResolverInterface" default="null" brief="ресолвер" />
///     </args>
///     <body>
  public function query($expression, $index = '*', Search_Sphinx_ResolverInterface $resolver = null) {

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
///     </body>
///   </method>

///   <method name="select" returns="Search.Sphinx.Query">
//TODO: from sss: может поменять названия методов select и query местами,
//                а то не логично, что select возвращает Query объект
///     <brief>Возвращает объект Search.Sphinx.Query для задания условий поиска</brief>
///     <args>
///       <arg name="expression" type="string" brief="выражение поиска" />
///     </args>
///     <body>
  public function select($expression) { return new Search_Sphinx_Query($this, $expression); }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="supporting">

///   <method name="catch_errors" returns="Search.Sphinx.Client">
///     <brief>Выбрасывает исключение в случае ошибки</brief>
///     <args>
///       <arg name="rc" type="boolean" brief="ошибка" />
///     </args>
///     <body>
  private function catch_errors($rc) {
    if (!$rc) throw new Search_Sphinx_Exception($this->client->GetLastError());
    return $this;
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="calling">

///   <method name="__call">
///     <brief>Перенаправляет вызовы в стандартный client с учетом CameCase</brief>
///     <args>
///       <arg name="method" type="string" />
///       <arg name="args" type="array" />
///     </args>
///     <body>
  public function __call($method, $args) {
    return call_user_func_array(array($this->client, Core_Strings::to_camel_case($method)), $args);
  }
///     </body>
////  </method>

///   </protocol>
}
/// </class>


/// <class name="Search.Sphinx.Results">
///   <brief>Обертка над результатом поиска Sphinx</brief>
class Search_Sphinx_Results
  implements Core_PropertyAccessInterface,
             IteratorAggregate,
             Core_CountInterface,
             Core_IndexedAccessInterface  {

  protected $documents = array();

  protected $execution_time;
  protected $total;
  protected $total_found;
  protected $error;
  protected $warning;

///   <protocol name="creating">

///   <method name="__construct">
///     <brief>Конструктор</brief>
///     <args>
///       <arg name="result" type="array" returns="массив результата поиска Sphinx" />
///       <arg name="resolver" type="Search.Sphinx.Resolver" default="null" brief="ресолвер" />
///     </args>
///     <body>
  public function __construct(array $results, Search_Sphinx_ResolverInterface $resolver = null) {
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
///     </body>
///   </method>

///   </protocol>

///   <protocol name="iterating">

///   <method name="getIterator">
///     <brief>Возвращает итератор</brief>
///     <body>
  public function getIterator() { return new ArrayIterator($this->documents); }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="counting" interface="Core.CountInterface">

///   <method name="count" returns="int">
///     <brief>Возвращает количество найденых документов</brief>
///     <body>
  public function count() { return count($this->documents); }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="performing">

///   <method name="document" returns="Search.Sphinx.Results">
///     <brief>Добавляет новый документ к результату</brief>
///     <args>
///       <arg name="id" type="int" brief="идентификатор" />
///       <arg name="attrs" brief="атрибут" />
///       <arg name="weight" brief="вес" />
///       <arg name="entity" default="null" brief="сущность" />
///     </args>
///     <body>
  public function append($id, $attrs, $weight, $entity = null) {
    $attrs = array('sid' => $id, 'attrs' => $attrs, 'weight' => $weight);
    $this->documents[] = $entity ? Object::Wrapper($entity, $attrs) : Core::object($attrs);
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="indexing" interface="Core.IndexedPropertyAccessInterface">

///   <method name="offsetGet" returns="mixed">
///     <brief>Возвращает документ</brief>
///     <args>
///       <arg name="index" type="int" brief="индентификатор документа" />
///     </args>
///     <body>
  public function offsetGet($index) { return Core::if_not_set($this->documents, $index, null); }
///     </body>
///   </method>

///   <method name="offsetSet" returns="mixed">
///     <brief>Выкидывает исключение</brief>
///     <args>
///       <arg name="index" brief="индентификатор документа" />
///       <arg name="value" brief="значение" />
///     </args>
///     <body>
  public function offsetSet($index, $value) {  throw new Core_ReadOnlyObjectException($this); }
///     </body>
///   </method>

///   <method name="offsetExists" returns="boolean">
///     <brief>Проверяет есть ли документ с таким идентификатором</brief>
///     <args>
///       <arg name="index" brief="идентификатор" />
///     </args>
///     <body>
  public function offsetExists($index) { return isset($this->documents[$index]); }
///     </body>
///   </method>

///   <method name="offsetUnset">
///     <brief>Выбрасывает исключение</brief>
///     <args>
///       <arg name="index" brief="индентификатор документа" />
///     </args>
///     <body>
  public function offsetUnset($index) { throw new Core_ReadOnlyObjectException($this); }
///     </body>
///   </method>

///   </protocol>


///   <protocol name="accessing">

///   <method name="__get" returns="mixed">
///     <brief>Доступ на чтение к свойствам объекта</brief>
///     <details>
///     <dl>
///       <dt>execution_time</dt><dd>время выполнения поиска</dd>
///       <dt>total</dt><dd> количество документов соответствующих запросу </dd>
///       <dt>total_found</dt><dd> количество найденных документов</dd>
///       <dt>error</dt><dd>сообщение об ошибке</dd>
///       <dt>warning</dt><dd>предупреждение</dd>
///       <dt>entities</dt><dd>набор сущностей</dd>
///     </dl>
///     </details>
///     <args>
///       <arg name="property" type="string" brief="имя свойства" />
///     </args>
///     <body>
  public function __get($property) {
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
///     </body>
///   </method>

///   <method name="__set" returns="mixed">
///     <brief>Доступ на запись к свойствам объекта</brief>
///     <details>
///       Выбрасывает исключение
///     </details>
///     <args>
///       <arg name="property" type="string" brief="имя свойства" />
///       <arg name="value" brief="значение" />
///     </args>
///     <body>
  public function __set($property, $value) { throw new Core_ReadOnlyObjectException($this); }
///     </body>
///   </method>

///   <method name="__isset" returns="boolean">
///     <brief>Проверяет установленно ли свойтсво</brief>
///     <args>
///       <arg name="property" type="string" brief="имя свойства" />
///     </args>
///     <body>
  public function __isset($property) {
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
///     </body>
///   </method>

///   <method name="__unset">
///     <brief>Очищает свойство объекта</brief>
///     <details>
///       Выбрасывает исключение
///     </details>
///     <args>
///       <arg name="property" type="string" brief="имя свойства" />
///     </args>
///     <body>
  public function __unset($property) { throw new Core_ReadOnlyObjectException($this); }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="supporting">

///   <method name="get_entities" returns="ArrayObject">
///     <body>
  protected function get_entities() {
    $r = new ArrayObject();
    foreach ($this->documents as $k => $d) $r[$k] = $d->__object;
    return $r;
  }
///     </body>
///   </method>

///   </protocol>

}
/// </class>


/// <class name="Search.Sphinx.Query">
///   <brief>Класс содержащий настройки поиска, и позволяющий их задавать</brief>
///   <implements interface="IteratorAggregate" />
///   <implements interface="Core.PropertyAccessInterface" />
class Search_Sphinx_Query implements IteratorAggregate, Core_PropertyAccessInterface {

  protected $options;

  protected $expression;
  protected $client;

  protected $resolver;

///   <protocol name="creating">

///   <method name="__construct">
///     <brief>Конструктор</brief>
///     <args>
///       <arg name="client" type="Search.Sphinx.Client" brief="клиент" />
///       <arg name="expression" type="string" brief="выражение поиска" />
///     </args>
///     <body>
  public function __construct(Search_Sphinx_Client $client, $expression) {
    $this->client = $client;
    $this->expression = (string) $expression;
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="accessing">

///   <method name="__get" returns="mixed">
///     <args>
///       <arg name="property" type="string" />
///     </args>
///     <body>
  public function __get($property) {
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
///     </body>
///   </method>

///   <method name="__set" returns="mixed">
///     <args>
///       <arg name="property" type="string" />
///       <arg name="value" />
///     </args>
///     <body>
  public function __set($property, $value) {
    throw new Core_ReadOnlyObjectException($this);
  }
///     </body>
///   </method>

///   <method name="__isset" returns="boolean">
///     <args>
///       <arg name="property" type="string" />
///     </args>
///     <body>
  public function __isset($property) {
      switch ($property) {
      case 'options':
      case 'client':
      case 'expression':
      case 'resolver':
        return (boolean) $this->$property;
      default:
        return false;
    }
  }
///     </body>
///   </method>

///   <method name="__unset">
///     <args>
///       <arg name="property" type="string" />
///     </args>
///     <body>
  public function __unset($property) {
    throw new Core_ReadOnlyObjectException($this);
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="iterating" interface="Core.IteratorAggregate">

///   <method name="getIterator">
///     <brief>Возвращает итератор по результату поиска</brief>
///     <body>
  public function getIterator() { return $this->execute()->getIterator(); }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="configuring">

///   <method name="using" returns="Search.Sphinx.Query">
///     <brief>Устанавливает индексы поиска</brief>
///     <args>
///       <arg name="indexes" type="string" brief="индексы" />
///     </args>
///     <body>
  public function using($indexes) {
    $this->options['indexes'] = (string) $indexes;
    return $this;
  }
///     </body>
///   </method>

///   <method name="range" returns="Search.Sphinx.Query">
///     <brief>задает интервал поиска</brief>
///     <args>
///       <arg name="limit" type="int" brief="количество возвращаемых элементам" />
///       <arg name="offset" type="int" default="0" brief="смещение" />
///     </args>
///     <body>
  public function range($limit, $offset = 0) {
    $this->options['range'] = array($limit, $offset);
    return $this;
  }
///     </body>
///   </method>

///   <method name="group_by" returns="Search.Sphinx.Query">
///     <brief>Группирует элементы поиска</brief>
///     <args>
///       <arg name="attribute" type="string" brief="атрибут" />
///       <arg name="func" type="int" default="SPH_GROUPBY_ATTR" brief="функция группировки" />
///       <arg name="group_sort" type="string" default="'@group desc'" brief="сортировка в группе" />
///     </args>
///     <body>
  public function group_by($attribute, $func = SPH_GROUPBY_ATTR, $group_sort = '@group desc') {
    $this->options['group_by'][] = array($attribute, $func, $group_sort);
    return $this;
  }
///     </body>
///   </method>

///   <method name="filter" returns="Search.Sphinx.Client">
///     <brief>Устанавливает фильтр поиска</brief>
///     <args>
///       <arg name="attribute" type="string" brief="атрибут" />
///       <arg name="value" brief="значение" />
///       <arg name="exclude" type="boolean" default="false" brief="инвертирует поиск" />
///     </args>
///     <body>
  public function filter($attribute, $values, $exclude = false) {
    $this->options['filter'][] = array($attribute, $values, $exclude);
    return $this;
  }
///     </body>
///   </method>

///   <method name="where" returns="Search.Sphinx.Client">
///     <brief>Устанавливает фильтер поиска</brief>
///     <args>
///       <arg name="attribute" type="string" brief="атрибут" />
///       <arg name="min" type="int" brief="минимальное значение" />
///       <arg name="max" type="int" brief="максимальное значение" />
///       <arg name="exclude" type="boolean" default="false" brief="инвертирует поиск" />
///     </args>
///     <body>
  public function where($attribute, $min, $max, $exclude = false) {
    $this->options['where'][] = array($attribute, $min, $max, $exclude);
    return $this;
  }
///     </body>
///   </method>

///   <method name="between" returns="Search.Sphinx.Client">
///     <brief>Задает интервал поиска</brief>
///     <args>
///       <arg name="min" type="int" brief="минимальное значение" />
///       <arg name="max" type="int" brief="максимальное значение" />
///     </args>
///     <body>
  public function between($min, $max) {
    $this->options['between'] = array($min, $max);
    return $this;
  }
///     </body>
///   </method>


///   <method name="sort_by" returns="Search.Sphinx.Client">
///     <brief>Устанавливает сортировку результата</brief>
///     <args>
///       <arg name="mode" type="string" brief="тип сортировки" />
///       <arg name="expr" type="string" brief="выражение для сортировке" />
///     </args>
///     <body>
  public function sort_by($mode, $expr = '') {
    $this->options['sort_by'] = array($mode, $expr);
    return $this;
  }
///     </body>
///   </method>

///   <method name="resolve_with" returns="Search.Sphinx.Query">
///     <brief>Устанавливает ресолвер</brief>
///     <args>
///       <arg name="resolver" type="Search.Sphinx.ResolverInterface" brief="ресолвер" />
///     </args>
///     <body>
  public function resolve_with(Search_Sphinx_ResolverInterface $resolver) {
    $this->resolver = $resolver;
    return $this;
  }
///     </body>
///   </method>

///   <method name="without_resolver" returns="Search.Sphinx.Query">
///     <brief>Обнуляет ресолвер</brief>
///     <body>
  public function without_resolver() {
    $this->resolver = null;
    return $this;
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="performing">

///   <method name="execute" returns="Search.Sphinx.Results">
///     <brief>Выполняет поиск</brief>
//TODO: Здесь наверное тоже стоит обернуть все в $this->client->catch_errors
///     <body>
  public function execute() {

    $this->client->reset();

    if (isset($this->options['range']))
      $this->client->range($this->options['range'][0], $this->options['range'][1]);

    if (isset($this->options['where']))
      foreach ($this->options['where'] as $cond)
        $this->client->where($cond[0], $cond[1], $cond[2], $cond[3]);

    if (isset($this->options['filter']))
      foreach ($this->options['filter'] as $cond)
        $this->client->filter($cond[0], $cond[1], $cond[2]);

    if (isset($this->options['group_by']))
      foreach ($this->options['group_by'] as $expr)
        $this->client->group_by($expr[0], $expr[1], $expr[2]);

    if (isset($this->options['between']))
      $this->client->between($this->options['between'][0], $this->options['between'][1]);

    if (isset($this->options['sort_by']))
      $this->client->sort_by($this->options['sort_by'][0], $this->options['sort_by'][1]);

    return $this->resolver ?
      $this->client->query($this->expression, Core::if_not_set($this->options, 'indexes', '*'), $this->resolver) :
      $this->client->query($this->expression, Core::if_not_set($this->options, 'indexes', '*'));
  }
///     </body>
///   </method>

///   </protocol>
}
/// </class>

/// </module>
