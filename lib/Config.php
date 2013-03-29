<?php
/// <module name="Config" maintainer="timokhin@techart.ru" version="1.1.0">
///   <brief>Модуль построения конфигурационных настроек</brief>
Core::load('Data');

/// <class name="Config" stereotype="module">
///   <implements interface="Core.ModuleInterface" />
///   <depends supplier="Config.Tree" stereotype="creates" />
class Config implements Core_ModuleInterface {
  const MODULE = 'Config';
  const VERSION = '1.1.0';

///   <protocol name="building">

///   <method name="Tree" scope="class" stereotype="factory">
///     <brief>Фабричный метод, возвращающий объект класса Config.Tree</brief>
///     <body>
  static public function Tree() { return new Config_Tree(); }
///     </body>
///   </method>

///   </protocol>
}
/// </class>


/// <class name="Config.Exception" extends="Core.Exception" stereotype="exception">
///   <brief>Класс исключения</brief>
class Config_Exception extends Core_Exception {}
/// </class>


/// <class name="Config.AbstractParser" stereotype="abstract">
///   <brief>Абстрактный класс, предназначенный для парсинга конфигурационных настроек</brief>
abstract class Config_AbstractParser {
///   <protocol name="parsing">

///   <method name="parse" returns="Config.Tree" stereotype="abstract">
///     <brief>Парсит дерево конфигурационных настроек</brief>
///     <args>
///       <arg name="default" type="Config.Tree" default="null" brief="значения по умолчанию" />
///     </args>
///     <body>
  abstract public function parse(Config_Tree $defaults = null);
///     </body>
///   </method>

///   </protocol>
}
/// </class>


/// <class name="Config.Tree" extends="Data.Tree">
///   <brief>Класс представляющий дерево конфигурационных настроек</brief>
class Config_Tree extends Data_Tree {}
/// </class>

/// </module>
