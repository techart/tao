<?php
/// <module name="IO" version="0.2.2" maintainer="timokhin@techart.ru">
///   <brief>Базовый модуль ввода/вывода</brief>
///   <details>
///     <p>Базовый модуль иерархии модулей IO, имеющих отношение к вводу/выводу.</p>
///     <p>Модуль определяет набор стандартных потоков ввода/вывода (stdin, stdout, stderr),
///        а также базовый класс исключений модулей IO.*. Реальная функциональность реализована в
///        других модулях IO.*.</p>
///     <p>В процессе загрузки модуль неявно подгружает модуль IO.Stream, таким образом, нет
///        необходимости подгружать IO.Stream явно.</p>
///   </details>

/// <class name="IO" stereotype="module">
///   <brief>Класс модуля</brief>
///   <implements interface="Core.ModuleInterface" />

///   <depends supplier="IO.Stream.ResourceStream" stereotype="creates" />

///   <details>
///     <p>Класс модуля выполняет подгрузку модуля IO.Stream и определяет набор статических методов,
///        позволяющих получить доступ к стандартным потокам ввода/вывода.</p>
///   </details>
class IO implements Core_ModuleInterface {

///   <constants>
  const VERSION = '0.2.2';
///   </constants>

  static protected $stdin;
  static protected $stdout;
  static protected $stderr;

///   <protocol name="creating">

///   <method name="initialize" scope="class">
///     <brief>Выполняет инициализацию модуля</brief>
///     <details>
///       <p>Метод подгружает модуль IO.Stream.</p>
///     </details>
///     <body>
  static public function initialize() { Core::load('IO.Stream'); }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="building">

///   <method name="stdin" returns="IO.Stream.ResourceStream" scope="class">
///     <brief>Возвращает объект класса IO.Stream.ResourceStream, соответствующий stdin.</brief>
///     <body>
  static public function stdin($stdin = null) {
    if ($stdin instanceof IO_Stream_AbstractStream)
      self::$stdin = $stdin;
    return self::$stdin ? self::$stdin : self::$stdin = IO_Stream::ResourceStream(STDIN);
  }
///     </body>
///   </method>

///   <method name="stdout" returns="IO.Stream.ResourceStream" scope="class">
///     <brief>Возвращает объект класса IO.Stream.ResourceStream, соответствующий stdout.</brief>
///     <body>
  static public function stdout($stdout = null) {
    if ($stdout instanceof IO_Stream_AbstractStream)
      self::$stdout = $stdout;
    return self::$stdout ? self::$stdout : self::$stdout = IO_Stream::ResourceStream(STDOUT);
  }
///     </body>
///   </method>

///   <method name="stderr" returns="IO.Stream.ResourceStream" scope="class">
///     <brief>Возвращает объект класса IO.Stream.ResourceStream, соответствующий stderr.</brief>
///     <body>
  static public function stderr($stderr = null) {
    if ($stderr instanceof IO_Stream_AbstractStream)
      self::$stderr = $stderr;
    return self::$stderr ? self::$stderr : self::$stderr = IO_Stream::ResourceStream(STDERR);
  }
///     </body>
///   </method>

///   </protocol>
}
/// </class>


/// <class name="IO.Exception" extends="Core.Exception" stereotype="exception">
///   <brief>Базовый класс исключений иерархии модулей IO</brief>
class IO_Exception extends Core_Exception {}
/// </class>

/// </module>
