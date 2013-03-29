<?php
/// <module name="DSL" version="0.2.0" maintainer="timokhin@techart.ru">
/// <brief>Базовые классы, предназначенные для построения DSL</brief>

/// <class name="DSL" stereotype="module">
///   <implements interface="Core.ModuleInterface" />
class DSL implements Core_ModuleInterface {
///   <constants>
  const VERSION = '0.2.0';
///   </constants>
}
/// </class>


/// <class name="DSL.Builder">
///   <brief>Базовый класс фабрик иерахически связанных объектов</brief>
///   <implements interface="Core.PropertyAccessInterface" />
///   <details>
///     <p>Класс предназначен для использования в качестве базового при
///        построении систем иерархически связанных объектов.</p>
///   </details>
class DSL_Builder implements Core_PropertyAccessInterface {

  protected $parent;
  protected $object;

///   <protocol name="creating">

///   <method name="__construct">
///     <brief>Конструктор</brief>
///     <args>
///       <arg name="parent" default="null" brief="родительский объект" />
///       <arg name="object" type="object" default="null" brief="текущий конфигурируемый объект" />
///     </args>
///     <body>
  public function __construct($parent, $object) {
    $this->parent = $parent;
    $this->object = $object;
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="accessing">

///   <method name="__get" returns="mixed">
///     <brief>Возвращает значение свойства</brief>
///     <args>
///       <arg name="property" type="string" brief="имя свойства" />
///     </args>
///     <body>
  public function __get($property) {
    switch ($property) {
      case 'end':
        return $this->parent ? $this->parent : $this->object;
      case 'object':
        return $this->$property;
      default:
        return $this->object->$property;
    }
  }
///     </body>
///   </method>

///   <method name="__set" returns="DSL.Builder">
///     <brief>Устанавливает значение свойства</brief>
///     <args>
///       <arg name="property" type="string" brief="имя свойства" />
///       <arg name="value" brief="значение" />
///     </args>
///     <body>
  public function __set($property, $value) { throw new Core_ReadOnlyObjectException($this); }
///     </body>
///   </method>

///   <method name="__isset" returns="boolean">
///    <brief>Проверяет установку значения свойства</brief>
///     <args>
///       <arg name="property" type="string" brief="имя свойства" />
///     </args>
///     <body>
  public function __isset($property) {
    switch ($property) {
      case 'object':
        return isset($this->$property);
      default:
        return false;
    }
  }
///     </body>
///   </method>

///   <method name="__unset">
///     <brief>Удаляет свойство</brief>
///     <args>
///       <arg name="property" type="string" brief="имя свойства" />
///     </args>
///     <body>
  public function __unset($property) { throw new Core_ReadOnlyObjectException($this); }
///   </body>
///   </method>

///   </protocol>

///   <protocol name="calling" interface="Core.CallInterface">

///   <method name="__call" returns="mixed">
///     <brief>Делегирует вызов конфигурируемому объекту</brief>
///     <args>
///       <arg name="method" type="string" brief="имя метода" />
///       <arg name="args"   type="array"  brief="значения аргументов" />
///     </args>
///     <body>
  public function __call($method, $args) {
    method_exists($this->object, $method) ?
      call_user_func_array(array($this->object, $method), $args) :
      $this->object->$method = $args[0];

    return $this;
  }
///     </body>
///   </method>

///   </protocol>
}
/// </class>

/// </module>
