<?php
/// <module name="Search.Sphinx.ORM" maintainer="svistunov@techart.ru" version="0.2.1">
///   <brief>Модуль служит для связи Sphinx и SB.ORM</brief>
Core::load('DB.ORM', 'Search.Sphinx');

/// <class name="Search.Sphinx.ORM" stereotype="module">
///   <implements interface="Core.ModuleInterface" />
class Search_Sphinx_ORM implements Core_ModuleInterface {

///   <constants>
  const VERSION = '0.2.1';
///   </constants>

///   <protocol name="building">

///   <method name="Resolver" returns="Search.Sphinx.ORM.Resolver" scope="class" stereotype="factory">
///     <brief>Фабричный метод, возвращает объект класса Search.Sphinx.ORM.Resolver</brief>
///     <body>
  static public function Resolver() {  return new Search_Sphinx_ORM_Resolver(); }
///     </body>
///   </method>

///   </protocol>
}
/// </class>


/// <class name="Search.Sphinx.ORM.Exception" extends="Core.Exception">
///   <brief>Класс исключения</brief>
class Search_Sphinx_ORM_Exception extends Core_Exception {}
/// </class>


/// <class name="Search.Sphinx.ORM.Resolver" extends="Search.Sphinx.Resolver">
///   <brief>Ресолвер связывающий результат поиска и DB.ORM </brief>
class Search_Sphinx_ORM_Resolver implements Search_Sphinx_ResolverInterface {

  protected $dimension = 0;
  protected $mappers = array();

///   <protocol name="creating">

///   <method name="__construct">
///     <brief>Конструктор</brief>
///     <body>
  public function __construct() { $this->setup(); }
///     </body>
///   </method>

///   <method name="setup" access="protected">
///     <body>
  protected function setup() {  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="configuring">

///   <method name="mappers" returns="Search.Sphinx.ORM.Resolver">
///     <brief>Устанавливает набор мапперов</brief>
///     <body>
  public function mappers() {
    foreach (Core::normalize_args(func_get_args()) as $k => $mapper)
      if ($mapper instanceof DB_ORM_Mapper) $this->mappers[$k] = $mapper;
    return $this;
  }
///     </body>
///   </method>

  public function dimension($dimension) {
    $this->dimension = $dimension;
    return $this;
  }

///   </protocol>

///   <protocol name="loading">

///   <method name="load" returns="Data.Hash">
///     <brief>Возвращает сущности соответствующие установленным мапперам</brief>
///     <args>
///       <arg name="result_set" type="Search.Sphinx.ResultSet" />
///     </args>
///     <body>
  public function load(array $matches) {

    $parts = array();
    $num_of_mappers = Core::if_not($this->dimension, count($this->mappers));

    foreach ($matches as $match) {
      $class_id = (int) Core::if_not_set($match['attrs'], '_class', 0);
      if (isset($this->mappers[$class_id])) $parts[$class_id][] = ($match['id'] - $class_id)/$num_of_mappers;
    }

    $result = array();

    foreach ($parts as $class_id => $ids) {
      $id_field = $this->mappers[$class_id]->options['key'][0];

      foreach (
        $this->mappers[$class_id]->spawn()->
          where($this->mappers[$class_id]->options['table_prefix'].'.'.$id_field.' IN ('.implode(',', $ids).')') as $v)
        $result[$class_id + $v[$id_field]*$num_of_mappers] = $v;
    }

    return $result;
  }
///     </body>
///   </method>

///   </protocol>
}
/// </class>

/// </module>
