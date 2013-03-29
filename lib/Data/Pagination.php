<?php
/// <module name="Data.Pagination" version="0.2.0" maintainer="timokhin@techart.ru">
///   <brief>Позволяет разбивать данные постранично</brief>
/// <class name="Data.Pagination" stereotype="module">
///   <implements interface="Core.ModuleInterface" />
class Data_Pagination implements Core_ModuleInterface {

///   <constants>
  const VERSION = '0.2.1';

  const DEFAULT_ITEMS_PER_PAGE = 10;
  const DEFAULT_PADDING = 2;
///   </constants>

///   <protocol name="building">

///   <method name="Pager" returns="Data.Pagination.Pager" scope="class" stereotype="factory">
///     <brief>Фабричный метод, возвращает объект класаа Data.Pagination.Pager</brief>
///     <args>
///       <arg name="num_of_items" type="int" brief="количество элементов"/>
///       <arg name="current_page" type="int" default="1" brief="текущая страница" />
///       <arg name="items_per_page" type="int" default="self::DEFAULT_ITEMS_PER_PAGE" brief="количество элементов на страницу" />
///     </args>
///     <body>
  static public function Pager($num_of_items, $current_page = 1, $items_per_page = self::DEFAULT_ITEMS_PER_PAGE) {
    return new Data_Pagination_Pager($num_of_items, $current_page, $items_per_page);
  }
///     </body>
///   </method>

///   </protocol>
}
/// </class>


/// <class name="Data.Pagination.Pager">
///     <brief>Разбивает данный постранично</brief>
///   <implements interface="Core.PropertyAccessInterface" />
///   <implements interface="Core.IndexedAccessInterface" />
///   <implements interface="Core.CountInterface" />
class Data_Pagination_Pager
  implements Core_PropertyAccessInterface,
             Core_IndexedAccessInterface,
             Core_CountInterface {

  protected $num_of_items   = 0;
  protected $items_per_page = 10;
  protected $num_of_pages   = 1;

  protected $current_page   = 1;
  protected $pages;

///   <protocol name="creating">

///   <method name="__construct">
///     <brief>Конструктор</brief>
///     <args>
///       <arg name="num_of_items" type="int" brief="количество элементов" />
///       <arg name="current_page" type="int" default="1" brief="текущая страница" />
///       <arg name="items_per_page" type="int" default="self::DEFAULT_ITEMS_PER_PAGE" brief="количество элементов на странице" />
///     </args>
///     <body>
  public function __construct($num_of_items, $current_page = 1, $items_per_page = self::ITEMS_PER_PAGE) {
    $this->pages = new ArrayObject();
    $this->num_of_items   = $num_of_items;
    $this->items_per_page = $items_per_page;

    $this->num_of_pages   = $this->num_of_items == 0 ? 1 :
      (int)($this->num_of_items / $this->items_per_page) +
      (($this->num_of_items % $this->items_per_page) ? 1 : 0);

    $this->current_page = ((int) $current_page > 0 && (int) $current_page <= $this->num_of_pages) ?
      (int) $current_page : 1;
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="indexing" interface="Core.IndexedAccessInterface">

///   <method name="offsetGet" returns="Data.Pagination.Page">
///     <brief>Индексный доступ к свойствам объекта</brief>
///     <details>
///       По номеру, начиная с единицы, возвращает страницу ввиде объекта класса Data.Pagination.Page
///     </details>
///     <args>
///       <arg name="index" type="int" brief="номер страницы" />
///     </args>
///     <body>
  public function offsetGet($index) {
    $index = (int) $index;
    if (isset($this->pages[$index])) return $this->pages[$index];
    if ($index > 0 && $index <= $this->num_of_pages)
      return $this->pages[$index] = new Data_Pagination_Page($this, $index);
    else
      return null;
  }
///     </body>
///   </method>

///   <method name="offsetExists" returns="boolean">
///     <brief>Проверяет существует ли страница с номером $index</brief>
///     <args>
///       <arg name="index" type="int" brief="номер страницы" />
///     </args>
///     <body>
  public function offsetExists($index) {
    $index = (int) $index;
    return $index > 0 && $index <= $this->num_of_pages;
  }
///     </body>
///   </method>

///   <method name="offsetSet">
///     <brief>Выбрасывает исключение</brief>
///     <details>
///       Установка страниц снаружи запрещена
///     </details>
///     <args>
///       <arg name="index" type="int" brief="номер страницы" />
///       <arg name="value" brief="значение" />
///     </args>
///     <body>
  public function offsetSet($index, $value) {
    throw $this->offsetExists($index) ?
      new Core_ReadOnlyIndexedPropertyException($index) :
      new Core_MissingIndexedPropertyException($index);
  }
///     </body>
///   </method>

///   <method name="offsetUnset">
///     <brief>Выбрасывает исключение</brief>
///     <details>
///       Внешне удалить страницу нельзя
///     </details>
///     <args>
///       <arg name="index" type="int" brief="номер страницы" />
///     </args>
///     <body>
  public function offsetUnset($index) {
    throw $this->offsetExists($index) ?
      new Core_ReadOnlyIndexedPropertyException($index) :
      new Core_MissingIndexedPropertyException($index);
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="quering">

///   <method name="first" returns="Data.Pagination.Page">
///     <brief>Возвращает первую страницу</brief>
///     <body>
  public function first() { return $this[1]; }
///     </body>
///   </method>

///   <method name="current" returns="Data.Pagination.Page">
///     <brief>Возвращает текущую страницу</brief>
///     <body>
  public function current() {
    return $this[$this->current_page];
  }
///     </body>
///   </method>

///   <method name="last" returns="Data.Pagination.Page">
///     <brief>Возвращает последнюю страницу</brief>
///     <body>
  public function last()  { return $this[$this->num_of_pages]; }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="accessing" interface="Core.PropertyAccessInterface">

///   <method name="__get">
///     <brief>Доступ на чтение к свойствам объекта</brief>
///     <details>
///     <dl>
///       <dt>num_of_items</dt><dd>Количество элементов</dd>
///       <dt>num_of_pages</dt><dd>Количество страниц</dd>
///       <dt>items_per_page</dt><dd>Количество элементов на странице</dd>
///       <dt>length</dt><dd>Количество страниц</dd>
///       <dt>last</dt><dd>Последняя страница</dd>
///       <dt>first</dt><dd>Первая страница</dd>
///       <dt>current</dt><dd>Текущая страница</dd>
///     </dl>
///     </details>
///     <args>
///       <arg name="property" type="string" brief="имя свойства" />
///     </args>
///     <body>
  public function __get($property) {
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
///     </body>
///   </method>

///   <method name="__set">
///     <brief>Выбрасывает исключение</brief>
///     <details>
///       Доступ на запись свойств объекта запрещен
///     </details>
///     <args>
///       <arg name="property" type="string" brief="имя свойства" />
///       <arg name="value" brief="значение" />
///     </args>
///     <body>
  public function __set($property, $value) {
    throw $this->__isset($property) ?
      new Core_ReadOnlyPropertyException($property) :
      new Core_MissingPropertyException($property);
  }
///     </body>
///   </method>

///   <method name="__isset" returns="boolean" >
///     <brief>Проверяет установленно ли свойство с именем $property</brief>
///     <args>
///       <arg name="property" type="string" brief="имя свойства" />
///     </args>
///     <body>
  public function __isset($property) {
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
///     </body>
///   </method>

///   <method name="__unset" >
///     <brief>Выбрасывает исключение</brief>
///     <details>
///       Очистка свойств запрещена
///     </details>
///     <args>
///       <arg name="property" type="string" />
///     </args>
///     <body>
  public function __unset($property) {
    throw $this->__isset($property) ?
      new Core_UndestroyablePropertyException($property) :
      new Core_MissingPropertyException($property);
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="count" interface="Core.CoutnInterface">

///   <method name="count">
///     <body>
  public function count() {
    return count($this->pages);
  }
///     </body>
///   </method>

///   </protocol>
}
/// </class>


/// <class name="Data.Pagination.Page">
///   <brief>Класс представляющий собой страницу</brief>
///   <implements interface="Core.PropertyAccessInterface" />
///   <implements interface="Core.EqualityInterface" />
class Data_Pagination_Page implements Core_PropertyAccessInterface, Core_EqualityInterface {
  protected $pager;
  protected $number;

///   <protocol name="creating">

///   <method name="__construct">
///     <brief>Конструктор</brief>
///     <args>
///       <arg name="pager" type="Data.Pagination.Pager" brief="ссылка на объект, содержащий страницы" />
///       <arg name="number" type="int" brief="номер страницы" />
///     </args>
///     <body>
  public function __construct(Data_Pagination_Pager $pager, $number) {
    $this->pager  = $pager;
    $this->number = $number;
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="quering">

///   <method name="offset" returns="number">
///     <brief>Возвращает сдвиг оносительно первого элемента</brief>
///     <body>
  public function offset() { return $this->pager->items_per_page * ($this->number - 1); }
///     </body>
///   </method>

///   <method name="first_item" returns="number">
///     <brief>Возвращает номер первого элемента на странице</brief>
///     <body>
  public function first_item() { return $this->offset() + 1; }
///     </body>
///   </method>

///   <method name="last_item" returns="number">
///     <brief>Возвращает индекс последнего элемента на странице</brief>
///     <body>
  public function last_item() {
    return min($this->pager->items_per_page*$this->number, $this->pager->num_of_items);
  }
///     </body>
///   </method>

///   <method name="next" returns="Data.Pagination.Page">
///     <brief>Возвращает следующую страницу</brief>
///     <body>
  public function next() { return $this->is_last() ? $this : $this->pager[$this->number + 1]; }
///     </body>
///   </method>

///   <method name="previous" returns="Data.Pagination.Page">
///     <brief>Возвращает предыдущую страницу</brief>
///     <body>
  public function previous() { return $this->is_first() ? $this : $this->pager[$this->number - 1]; }
///     </body>
///   </method>

///   <method name="is_first" returns="boolean">
///     <brief>Проверяет является ли данная страница первой</brief>
///     <body>
  public function is_first() { return $this->number == $this->pager->first->number; }
///     </body>
///   </method>

///   <method name="is_last" returns="boolean">
///     <brief>Проверяет является ли данная страница последней</brief>
///     <body>
  function is_last() { return $this->number == $this->pager->last->number; }
///     </body>
///   </method>

///   <method name="window" returns="Data.Pagination.Window" >
///     <brief>Возвращает окно с заданным отступом ввиде объекта Data.Pagination.Window</brief>
///     <args>
///       <arg name="padding" type="int" default="2" />
///     </args>
///     <body>
  public function window($padding = Data_Pagination::DEFAULT_PADDING) {
    return new Data_Pagination_Window($this, $padding);
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="accessing" interface="Core.PropertyAccessInterface">

///   <method name="__get" returns="mixed">
///     <brief>Доступ на чтение к свойствам объекта</brief>
///     <details>
///       <dl>
///         <dt>pager</dt><dd>ссылка на объект, содержащий страницы</dd>
///         <dt>first_item</dt><dd>первый элемент данной страницы</dd>
///         <dt>last_item</dt><dd>последний элемент данной страницы</dd>
///         <dt>offset</dt><dd>смещение относительно первого элемента</dd>
///         <dt>previous</dt><dd>предыдущая страница</dd>
///         <dt>next</dt><dd>следующая страница</dd>
///         <dt>number</dt><dd>номер страницы</dd>
///       </dl>
///     </details>
///     <args>
///       <arg name="property" type="string" brief="имя свойства" />
///     </args>
///     <body>
  public function __get($property) {
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
///     </body>
///   </method>

///   <method name="__set" returns="mixed">
///     <brief>Выбрасывает исключение</brief>
///     <details>
///       Свойства доступны только для чтения
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
///     <brief>Проверяет установлено ли свойство с именем $property</brief>
///     <args>
///       <arg name="property" type="string" brief="имя свойства" />
///     </args>
///     <body>
  public function __isset($property) {
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
///     </body>
///   </method>

///   <method name="__unset" >
///     <brief>Выбрасывает исключение</brief>
///     <details>
///       Свойства доступны только для чтения
///     </details>
///     <args>
///       <arg name="property" type="string" brief="имя свойства" />
///     </args>
///     <body>
  public function __unset($property) { throw new Core_ReadOnlyObjectException($this); }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="quering">
///   <method name="equals" returns="boolean">
///     <args>
///       <arg name="to" type="Data.Pagination.Page" />
///     </args>
///     <body>
  public function equals($to) {
    return (
      $to instanceof self &&
      $this->pager === $to->pager &&
      $this->number == $to->number &&
      $this->first_item == $to->first_item &&
      $this->last_item == $to->last_item);
  }
///     </body>
///   </method>

///</protocol>
}
/// </class>


/// <class name="Data.Pagination.Window">
///   <brief>Класс представлющий собой окно, содержащее несколько страниц</brief>
///   <details>
///     Например для пятой страницы окно с отступом 2 будет содержать 3,4,5,6,7 страницы
///   </details>
///   <implements interface="Core.PropertyAccessInterface" />
///   <implements interface="Core.CountInterface" />
class Data_Pagination_Window
  implements Core_PropertyAccessInterface, Core_CountInterface {

  protected $page;
  protected $padding;

  protected $first;
  protected $pages;
  protected $last;

///   <protocol name="creating">

///   <method name="__construct">
///     <brief>Конструктор</brief>
///     <args>
///       <arg name="page" type="Data.Pagination.Page" brief="страница" />
///       <arg name="padding" type="int" default="2" brief="отступ" />
///     </args>
///     <body>
  public function __construct(Data_Pagination_Page $page, $padding = Data_Pagination::DEFAULT_PADDING) {
    $this->page    = $page;
    $this->set_padding($padding);
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="accessing" interface="Core.PropertyAccessInterface">

///   <method name="__get" returns="mixed">
///     <brief>Доступ к свойтсвам объекта на чтение</brief>
///     <details>
///       <dl>
///         <dt>first</dt><dd>первая страница текущего окна</dd>
///         <dt>last</dt><dd>последняя страница текущего окна</dd>
///         <dt>page</dt><dd>родительска я страница</dd>
///         <dt>padding</dt><dd>отступ</dd>
///         <dt>pager</dt><dd>объект содержащий страницы</dd>
///         <dt>pages</dt><dd>все страницы текущего окна ввиде ArrayObject</dd>
///       </dl>
///     </details>
///     <args>
///       <arg name="property" type="string" brief="имя свойства" />
///     </args>
///     <body>
  public function __get($property) {
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
///     </body>
///   </method>

///   <method name="__set" returns="mixed">
///     <brief>Выбрасывает исключение</brief>
///     <details>
///       Доступ на запись к свойствам объекта запрещен
///     </details>
///     <args>
///       <arg name="property" type="string" brief="имя свойства" />
///       <arg name="value" brief="значение" />
///     </args>
///     <body>
  public function __set($property, $value) {
    switch ($property) {
      case 'first':
      case 'last':
      case 'page':
      case 'pages':
      case 'pager':
        throw new Core_ReadOnlyPropertyException($property);
      case 'padding':
        $this->set_padding((int) $value);
        return $this;
      default:
        throw new Core_MissingPropertyException($property);
    }
  }
///     </body>
///   </method>

///   <method name="__isset" returns="boolean">
///     <brief>Проверяет установлено ли свойство объекта с именем $property</brief>
///     <args>
///       <arg name="property" type="string" brief="имя свойства" />
///     </args>
///     <body>
  public function __isset($property) {
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
///     </body>
///   </method>

///   <method name="__unset">
///     <brief>Выбрасывает исключение</brief>
///     <details>
///       Очистка свойства запрещена
///     </details>
///     <args>
///       <arg name="property" type="string" />
///     </args>
///     <body>
  public function __unset($property) {
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
///     </body>
///   </method>

///   </protocol>

///   <protocol name="supporting">

///   <method name="set_padding" returns="Data.Pagination.Window">
///     <brief>Устанавливает отступ и просчитывает все остальный параметры</brief>
///     <args>
///       <arg name="padding" type="int" brief="отступ" />
///     </args>
///     <body>
  protected function set_padding($padding) {
    $this->padding = $padding;
    $this->pages = null;

    $this->first = isset($this->pager[$this->page->number - $this->padding]) ?
      $this->pager[$this->page->number - $this->padding] : $this->pager->first;
    $this->last  = isset($this->pager[$this->page->number + $padding]) ?
      $this->pager[$this->page->number + $padding] : $this->pager->last;

    return $this;
  }
///     </body>
///   </method>

///   <method name="get_pages" returns="ArrayObject" access="protected">
///     <brief>Возвращает ArrayObject все страниц текущего окна</brief>
///     <body>
  protected function get_pages() {
    if (!$this->pages) {
      for ($this->pages = new ArrayObject(), $i = $this->first->number; $i <= $this->last->number; $i++)
        $this->pages[$i] = $this->pager[$i];
    }
    return $this->pages;
  }
///   </body>
///   </method>

///   </protocol>

///   <protocol name="count" interface="Core.CountInterface">

///   <method name="count">
///     <body>
  public function count() {
    return count($this->__get('pages'));
  }
///     </body>
///   </method>

///   </protocol>
}
/// </class>

/// </module>
