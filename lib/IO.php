<?php
/**
 * IO
 * 
 * Базовый модуль ввода/вывода
 * 
 * <p>Базовый модуль иерархии модулей IO, имеющих отношение к вводу/выводу.</p>
 * <p>Модуль определяет набор стандартных потоков ввода/вывода (stdin, stdout, stderr),
 * а также базовый класс исключений модулей IO.*. Реальная функциональность реализована в
 * других модулях IO.*.</p>
 * <p>В процессе загрузки модуль неявно подгружает модуль IO.Stream, таким образом, нет
 * необходимости подгружать IO.Stream явно.</p>
 * 
 * @package IO
 * @version 0.2.2
 */

/**
 * Класс модуля
 * 
 * <p>Класс модуля выполняет подгрузку модуля IO.Stream и определяет набор статических методов,
 * позволяющих получить доступ к стандартным потокам ввода/вывода.</p>
 * 
 * @package IO
 */


class IO implements Core_ModuleInterface {

  const VERSION = '0.2.2';

  static protected $stdin;
  static protected $stdout;
  static protected $stderr;


/**
 * Выполняет инициализацию модуля
 * 
 */
  static public function initialize() { Core::load('IO.Stream'); }



/**
 * Возвращает объект класса IO.Stream.ResourceStream, соответствующий stdin.
 * 
 * @return IO_Stream_ResourceStream
 */
  static public function stdin($stdin = null) {
    if ($stdin instanceof IO_Stream_AbstractStream)
      self::$stdin = $stdin;
    return self::$stdin ? self::$stdin : self::$stdin = IO_Stream::ResourceStream(STDIN);
  }

/**
 * Возвращает объект класса IO.Stream.ResourceStream, соответствующий stdout.
 * 
 * @return IO_Stream_ResourceStream
 */
  static public function stdout($stdout = null) {
    if ($stdout instanceof IO_Stream_AbstractStream)
      self::$stdout = $stdout;
    return self::$stdout ? self::$stdout : self::$stdout = IO_Stream::ResourceStream(STDOUT);
  }

/**
 * Возвращает объект класса IO.Stream.ResourceStream, соответствующий stderr.
 * 
 * @return IO_Stream_ResourceStream
 */
  static public function stderr($stderr = null) {
    if ($stderr instanceof IO_Stream_AbstractStream)
      self::$stderr = $stderr;
    return self::$stderr ? self::$stderr : self::$stderr = IO_Stream::ResourceStream(STDERR);
  }

}


/**
 * Базовый класс исключений иерархии модулей IO
 * 
 * @package IO
 */
class IO_Exception extends Core_Exception {}

