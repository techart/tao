<?php
/// <module name="Fn" version="0.2.0" maintainer="timokhin@techart.ru">
///   <brief>Модуль предоставляет набор классов для различных модификаций стандартного итератора</brief>
/// <class name="Fn" stereotype="module">
///   <implements interface="Core.ModuleInterface" />
class Fn implements Core_ModuleInterface {
///   <constants>
  const VERSION = '0.2.0';
///   </constants>

///   <protocol name="building">

///   <method name="map" returns="Fn.Mapper" scope="class">
///     <brief>Фабричный метод, возвращает объект класса Fn.Mapper</brief>
///     <args>
///       <arg name="f" type="array" brief="callback-массив" />
///       <arg name="source" brief="итератор источник" />
///     </args>
///     <body>
  static public function map(array $f, $source) { return new Fn_Mapper($f, $source); }
///     </body>
///   </method>

///   <method name="filter" returns="Fn.Filter" scope="class">
///     <brief>Фабричный метод, возвращает объект класса Fn.Filter</brief>
///     <args>
///       <arg name="f" type="array" brief="callback-массив" />
///       <arg name="source" brief="итератор источник" />
///     </args>
///     <body>
  static public function filter(array $f, $source) { return new Fn_Filter($f, $source); }
///     </body>
///   </method>

///   <method name="singular" returns="Iterator">
///     <brief>Фабричный метод, возвращает объект класса Fn.Singular</brief>
///     <args>
///       <arg name="object" brief="объект" />
///     </args>
///     <body>
  static public function singular($object, $method = null) {
    return new Fn_Singular($object, $method);
  }
///     </body>
///   </method>

///   <method name="join" returns="Fn.Joiner">
///     <brief>Фабричный метод, возвращает объект класса Fn.Joiner</brief>
///     <body>
  static public function join() {
    $args = func_get_args();
    return new Fn_Joiner(count($args) > 1 ? $args : $args[0]);
  }
///     </body>
///   </method>

///   <method name="generate" returns="Fn.Generator" scope="class">
///     <brief>Фабричный метод, возвращает объект класса Fn.Generator</brief>
///     <args>
///       <arg name="f" type="array" brief="callback-массив" />
///     </args>
///     <body>
  static public function generate(array $f) { return new Fn_Generator($f); }
///     </body>
///   </method>

///   </protocol>
}
/// </class>

/// <class name="Fn.AbstractTransformer" stereotype="abstract">
///   <brief>Абстрактный класс для трансформации итератора-источника</brief>
abstract class Fn_AbstractTransformer implements Iterator {

  protected $source;
///   <protocol name="creating">

///   <method name="__construct">
///     <brief>Конструктор</brief>
///     <args>
///       <arg name="source" brief="источник-итератор" />
///     </args>
///     <body>
  public function __construct($source) {
    $this->source =
      ($source instanceof Iterator) ? $source : (
      ($source instanceof IteratorAggregate) ? $source->getIterator() :
      Core::with(new ArrayObject((array) $source))->getIterator());
  }
///     </body>
///   </method>

///   </protocol>
}
/// </class>

/// <class name="Fn.AbstractMapper" extends="Fn.AbstractTransformer" stereotype="abstract">
///   <brief>Абстактный класс преобразования итератора</brief>
///     <details>
///       Унаследовавшись от этого класса и опеределив метод map поличум итератор, элементами корого будут
///       элементы итератора-источника преобразованные с помощью метода map
///     </details>
abstract class Fn_AbstractMapper extends Fn_AbstractTransformer {

  protected $current;

///   <protocol name="processing">

///   <method name="map" returns="array">
///     <brief>Абстактный метод для преобразования элеметнов итератора-источника</brief>
///     <details>
///       Возвращаться должен массив из двух элементов, первый - ключ, второй - значение
///     </details>
///     <args>
///       <arg name="key" brief="ключ элемента источника" />
///       <arg name="value" brief="значение элемента источника" />
///     </args>
///     <body>
  abstract protected function map($key, $value);
///     </body>
///   </method>

///   </protocol>

///   <protocol name="iterating">

///   <method name="current" returns="mixed">
///     <brief>Возвращает текущий элемент итератора</brief>
///     <body>
  public function current() { return $this->current[1]; }
///     </body>
///   </method>

///   <method name="key" returns="mixed">
///     <brief>Возвращает ключ текущего элемента итератора</brief>
///     <body>
  public function key() { return $this->current[0]; }
///     </body>
///   </method>

///   <method name="next">
///     <brief>Возвращает следующий элемент итератора</brief>
///     <body>
  public function next() {
    $this->source->next();
    $this->fetch_next();
  }
///     </body>
///   </method>

///   <method name="rewind" returns="mixed">
///     <brief>Сбрасывает итератор в начало</brief>
///     <body>
  public function rewind() {
    $this->source->rewind();
    $this->fetch_next();
  }
///     </body>
///   </method>

///   <method name="valid" returns="boolean">
///     <brief>Проверяет валидность текущего элемента</brief>
///     <body>
  public function valid() { return $this->source->valid(); }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="supporting">

///   <method name="fetch_next" access="private">
///     <brief>Выдает следующий элемент</brief>
///     <body>
  private function fetch_next() {
    if ($this->source->valid())
      $this->current = (array) $this->map($this->source->key(), $this->source->current());
  }
///     </body>
///   </method>

///   </protocol>
}
/// </class>

/// <class name="Fn.Mapper" extends="Fn.AbstractMapper">
///   <brief>Преобразователь итератора</brief>
///     <details>
///       Для преобразования элементов использует callback-массив
///     </details>
class Fn_Mapper extends Fn_AbstractMapper {

  protected $f;

///   <protocol name="creating">

///   <method name="__construct">
///     <brief>Конструктор</brief>
///     <args>
///       <arg name="f" type="array" brief="callback-массив" />
///       <arg name="source" brief="итератор-источник" />
///     </args>
///     <body>
  public function __construct(array $f, $source) {
    parent::__construct($source);
    $this->f = $f;
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="processing">

///   <method name="map" returns="array" access="protected">
///     <brief>Преобразует элеметы итератора-источника</brief>
///     <args>
///       <arg name="key" brief="ключ элемента источника" />
///       <arg name="value" brief="значение элемента источника" />
///     </args>
///     <body>
  protected function map($key, $value) { return call_user_func_array($this->f, array($key, $value)); }
///     </body>
///   </method>



///   </protocol>
}
/// </class>

/// <class name="Fn.AbstractFilter" extends="Fn.AbstractTransformer" stereotype="abstract">
///   <brief>Абстрактный класс для фильтрации итератора-источника</brief>
///   <details>
///     Унаследовавшись от этого класса и опеределив метод filter поличум итератор, элементами корого будут
///     только те элементы итератора-источника для которых filter возвращает true
///   </details>
abstract class Fn_AbstractFilter extends Fn_AbstractTransformer {

///   <protocol name="filtering">

///   <method name="filter" returns="boolean" stereotype="abstract">
///     <brief>Фильтрует элементы итератора-источника</brief>
///     <args>
///       <arg name="key" brief="ключ элемента источника" />
///       <arg name="value" brief="значение элемента источника" />
///     </args>
///     <body>
  abstract protected function filter($key, $value);
///     </body>
///   </method>

///   </protocol>

///   <protocol name="iterating" interface="Iterator">

///   <method name="current" returns="mixed">
///     <brief>Возвращает текущий элемент итератора</brief>
///     <body>
  public function current() { return $this->source->current();  }
///     </body>
///   </method>

///   <method name="key" returns="mixed">
///     <brief>Возвращает ключ текущего элемента итератора</brief>
///     <body>
  public function key() { return $this->source->key(); }
///     </body>
///   </method>

///   <method name="rewind">
///     <brief>Сбрасывает итератор в начало</brief>
///     <body>
  public function rewind() {
    $this->source->rewind();
    $this->skip();
  }
///     </body>
///   </method>

///   <method name="valid" returns="boolean">
///     <brief>Проверяет валидность текущего элемента</brief>
///     <body>
  public function valid() { return $this->source->valid(); }
///     </body>
///   </method>

///   <method name="next">
///     <brief>Возвращает следующий элемент итератора</brief>
///     <body>
  public function next() {
    $this->source->next();
    $this->skip();
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="supporting">

///   <method name="skip" access="protected">
///     <brief>Пропускает ненужные элементы</brief>
///     <body>
  protected function skip() {
    while ($this->source->valid() && !$this->filter($this->source->key(), $this->source->current()))
      $this->source->next();
  }
///     </body>
///   </method>

///   </protocol>
}
/// </class>

/// <class name="Fn.Filter" extends="Fn.AbstractFilter">
///   <brief>Филтер итератора</brief>
///   <details>
///     Для фильтрации элементов использует callback-массив
///   </details>
class Fn_Filter extends Fn_AbstractFilter {

  protected $f;

///   <protocol name="creating">

///   <method name="__construct">
///     <brief>Конструктор</brief>
///     <args>
///       <arg name="f" type="array" brief="callback-массив"  />
///       <arg name="source" brief="итератор-источник" />
///     </args>
///     <body>
  public function __construct(array $f, $source) {
    parent::__construct($source);
    $this->f = $f;
  }
///     </body>
///   </method>

///   <method name="filter" returns="boolean" access="protected">
///     <brief>Фильтрует элементы итератора-источника</brief>
///     <args>
///       <arg name="k" brief="ключ элемента источника" />
///       <arg name="v" brief="значение элемента источника" />
///     </args>
///     <body>
  protected function filter($k, $v) { return (boolean) call_user_func_array($this->f, array($k, $v)); }
///     </body>
///   </method>

///   </protocol>
}
/// </class>

/// <class name="Fn.Joiner" extends="Fn.AbstractTransformer">
///   <brief>Объединяет несколько итераторов в один</brief>
class Fn_Joiner extends Fn_AbstractTransformer {

  protected $current;

///   <protocol name="iterating" interface="Iterator">

///   <method name="rewind">
///     <brief>Сбрасывает итератор в начало</brief>
///     <body>
  public function rewind() {
    $this->source->rewind();
    $this->current = $this->next_part();
    $this->current->rewind();
  }
///     </body>
///   </method>

///   <method name="next">
///     <brief>Возвращает следующий элемент итератора</brief>
///     <body>
  public function next() {
    $this->current->next();
    if (!$this->current->valid()) {
      $this->source->next();
      if ($this->current = $this->next_part()) $this->current->rewind();
    }
  }
///     </body>
///   </method>

///   <method name="current" returns="mixed">
///     <brief>Возвращает текущий элемент итератора</brief>
///     <body>
  public function current() { return $this->current->current(); }
///     </body>
///   </method>

///   <method name="key" returns="mixed">
///     <brief>Возвращает ключ текущего элемента итератора</brief>
///     <body>
  public function key() { return $this->current->key(); }
///     </body>
///   </method>

///   <method name="valid" returns="boolean">
///     <brief>Проверяет валидность текущего элемента</brief>
///     <body>
  public function valid() {
    return $this->current && $this->current->valid();
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="supporting">

///   <method name="next_part" access="private" returns="mixed">
///     <brief>Переходит к следущему итератору</brief>
///     <body>
  private function next_part() {
    $part = $this->source->valid() ? $this->source->current() : null;
    return ($part) ?
    ($part instanceof Iterator) ? $part : (($part instanceof IteratorAggregate) ?
    $part->getIterator() : Core::with(new ArrayObject((array) $part))->getIterator()) : null;
  }
///     </body>
///   </method>

///   </protocol>
}
/// </class>

/// <class name="Fn.AbstractGenerator">
///   <brief>Абстрактный класс для генерации элементов итератора</brief>
///   <implements interface="Iterator" />
abstract class Fn_AbstractGenerator implements Iterator {

  protected $current;
  private   $count;

///   <protocol name="generating">

///   <method name="generate" returns="array" stereotype="abstract">
///     <brief>Генерирует следующий элемент итератора</brief>
///     <args>
///       <arg name="count" type="int" brief="текущее количество элемнтов" />
///     </args>
///     <body>
  abstract protected function generate($count);
///     </body>
///   </method>

///   </protocol>

///   <protocol name="iterating" interface="Iterator">

///   <method name="current" returns="mixed">
///     <brief>Возвращает текущий элемент итератора</brief>
///     <body>
  public function current() { return $this->current[1]; }
///     </body>
///   </method>

///   <method name="key" returns="mixed">
///     <brief>Возвращает ключ текущего элемента итератора</brief>
///     <body>
  public function key() { return $this->current[0]; }
///     </body>
///   </method>

///   <method name="next">
///     <brief>Возвращает следующий элемент итератора</brief>
///     <body>
  public function next() {
    if ($this->current = $this->generate($this->count)) $this->count++;
  }
///     </body>
///   </method>

///   <method name="rewind">
///     <brief>Сбрасывает итератор в начало</brief>
///     <body>
  public function rewind() {
    $this->count = 0;
    $this->current = null;
    $this->next();
  }
///     </body>
///   </method>

///   <method name="valid" returns="boolean">
///     <brief>Проверяет валидность текущего элемента</brief>
///     <body>
  public function valid() { return $this->current !== null; }
///     </body>
///   </method>

///   </protocol>
}
/// </class>

/// <class name="Fn.Generator" extends="Fn.AbstractGenerator">
///   <brief>генерирует элементы итератора</brief>
class Fn_Generator extends Fn_AbstractGenerator {
  protected $f;

///   <protocol name="creating">

///   <method name="__construct">
///     <brief>=Конструктор</brief>
///     <args>
///       <arg name="f" type="array" brief="callback-массив" />
///     </args>
///     <body>
  public function __construct(array $f) {
    $this->f = $f;
  }
///     </body>
///   </method>

///   <method name="generate" returns="mixed" access="protected">
///     <args>
///       <arg name="count" type="int" brief="текущее количество элементов" />
///     </args>
///     <body>
  protected function generate($count) { return call_user_func_array($this->f, array($count)); }
///     </body>
///   </method>

///   </protocol>
}
/// </class>

/// <class name="Fn.Singular">
///   <brief>Делает итератор из одного элемента, возвращаетмого методом переданного объекта</brief>
///   <implements interface="Iterator" />
class Fn_Singular implements Iterator {

  protected $object;
  protected $method;
  protected $ready = false;

///   <protocol name="creating">

///   <method name="__construct">
///     <brief>Конструктор</brief>
///     <args>
///       <arg name="object" type="object" brief="объект" />
///       <arg name="method" type="string" brief="метод" />
///     </args>
///     <body>
  public function __construct($object, $method = null) {
    $this->object = $object;
    $this->method = $method;
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="iterating" interface="Iterator">

///   <method name="current" returns="mixed">
///     <brief>Возвращает текущий элемент итератора</brief>
///     <body>
  public function current() {
    $method = $this->method;
    return $this->method ? $this->object->$method() : $this->object;
  }
///     </body>
///   </method>

///   <method name="key" returns="int">
///     <brief>Возвращает ключ текущего элемента итератора</brief>
///     <body>
  public function key() { return 0; }
///     </body>
///   </method>

///   <method name="rewind">
///     <brief>Сбрасывает итератор в начало</brief>
///     <body>
  public function rewind() { $this->ready = false; }
///     </body>
///   </method>

///   <method name="valid" returns="boolean">
///     <brief>Проверяет валидность текущего элемента</brief>
///     <body>
  public function valid() { return !$this->ready; }
///     </body>
///   </method>

///   <method name="next">
///     <brief>Возвращает следующий элемент итератора</brief>
///     <body>
  public function next() { $this->ready = true; }
///     </body>
///   </method>

///   </protocol>
}
/// </class>

/// </module>
