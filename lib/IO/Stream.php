<?php
/// <module name="IO.Stream" version="0.2.3" maintainer="timokhin@techart.ru">
///   <brief>Работа с потоками ввода/вывода</brief>
///   <details>
///     <p>Модуль обеспечивает минимальную объектную абстракцию потоков ввода/вывода, при этом
///        поток представляется в виде итерируемого объекта.</p>
///   </details>
Core::load('IO');

/// <class name="IO.Stream" stereotype="module">
///   <brief>Класс модуля</brief>
///   <implements interface="Core.ModuleInterface" />
///   <depends supplier="IO.Stream.ResourceStream" stereotype="creates" />
///   <depends supplier="IO.Stream.TemporaryStream" stereotype="creates" />
///   <depends supplier="IO.Stream.NamedResourceStream" stereotype="creates" />
///   <depends supplier="IO.Stream.Iterator" stereotype="creates" />
///   <details>
///     <p>Определяет набор фабричных методов для создания экземпляров классов модуля.</p>
///     <p>Модуль также определяет следующие константы:</p>
///     <dl>
///       <dt>DEFAULT_OPEN_MODE</dt>
///       <dd>режим открытия потока по умолчанию;</dd>
///       <dt>DEFAULT_CHUNK_SIZE</dt>
///       <dd>размер буфера чтения бинарного потока по умолчанию;</dd>
///       <dt>DEFAULT_LINE_LENGTH</dt>
///       <dd>максимальная длина строки текстового потока по умолчанию.</dd>
///     </dl>
///   </details>
class IO_Stream implements Core_ModuleInterface {

///   <constants>
  const VERSION = '0.2.3';

  const DEFAULT_OPEN_MODE   = 'rb';

  const DEFAULT_CHUNK_SIZE  = 8192;
  const DEFAULT_LINE_LENGTH = 1024;
///   </constants>

///   <protocol name="building">

///   <method name="ResourceStream" returns="IO.Stream.ResourceStream" scope="class">
///     <brief>Создает объект класса IO.Stream.ResourceStream</brief>
///     <args>
///       <arg name="id" type="int" brief="идентификатор ресурса" />
///     </args>
///     <body>
  static public function ResourceStream($id) { return new IO_Stream_ResourceStream($id); }
///     </body>
///   </method>

///   <method name="TemporaryStream" returns="IO.Stream.TemporaryStream" scope="class">
///     <brief>Создает объект класса IO.Stream.TemporaryStream</brief>
///     <body>
  static public function TemporaryStream() { return new IO_Stream_TemporaryStream(); }
///     </body>
///   </method>

///   <method name="NamedResourceStream" returns="IO.Stream.ResourceStream" scope="class">
///     <brief>Создает объект класса IO.Stream.NamedResourceStream</brief>
///     <args>
///       <arg name="uri" type="string" brief="URI ресурса" />
///       <arg name="mode" type="string" default="IO_Stream::DEFAULT_OPEN_MODE" brief="режим открытия потока" />
///     </args>
///     <body>
  static public function NamedResourceStream($uri, $mode = IO_Stream::DEFAULT_OPEN_MODE) {
    return new IO_Stream_NamedResourceStream($uri, $mode);
  }
///     </body>
///   </method>

///   </protocol>
}
/// </class>

/// <class name="IO.Stream.Exception" extends="IO.Exception" stereotype="exception">
///   <brief>Класс исключения</brief>
class IO_Stream_Exception extends IO_Exception {}
/// </class>


/// <class name="IO.Stream.AbstractStream" stereotype="abstract">
///   <brief>Базовый класс потока</brief>
///   <implements interface="IteratorAggregate" />
///   <details>
///     <p>Определяет интерфейс класса потока, предназначен для использования в качестве базового
///        класс при реализации специфичных классов потоков.</p>
///   </details>
abstract class IO_Stream_AbstractStream implements IteratorAggregate {

  protected $binary = false;

///   <protocol name="processing">

///   <method name="read" returns="string">
///     <brief>Читает данные из потока</brief>
///     <args>
///       <arg name="length" type="int" default="null" brief="длина читаемого блока" />
///     </args>
///     <details>
///       <p>В зависимости от типа потока (текстовый/бинарный) вызывает методы read_line() или
///          read_chunk() соответстветственно.</p>
///     </details>
///     <body>
  public function read($length = null) {
    return $this->binary ?
      $this->read_chunk($length) :
      $this->read_line($length);
  }
///     </body>
///   </method>

///   <method name="read_chunk" returns="string" stereotype="abstract">
///     <brief>Читает блок данных из бинарного потока.</brief>
///     <args>
///       <arg name="length" type="int" default="null" brief="длина читаемого блока" />
///     </args>
///     <details>
///       <p>Читаемый блок ограничен указанной длиной блока или концом потока.</p>
///       <p>Возвращает прочитанный блок, или null, если достигнут конец потока.</p>
///     </details>
///     <body>
  abstract public function read_chunk($length = null);
///     </body>
///   </method>

///   <method name="read_line" returns="string" stereotype="abstract">
///     <brief>Читает строку из текстового потока</brief>
///     <args>
///       <arg name="length" type="int" default="null" brief="максимальная длина строки" />
///     </args>
///     <details>
///       <p>Читаемый блок ограничен максимальной длиной строки, символом конца строки или концом
///          файла.</p>
///       <p>Возвращает прочитанную строку или null , если достигнут конец потока.</p>
///     </details>
///     <body>
  abstract public function read_line($length = null);
///     </body>
///   </method>

///   <method name="write" returns="IO.Stream.AbstractStream" stereotype="abstract">
///     <brief>Записывает данные в поток</brief>
///     <args>
///       <arg name="data" type="string" brief="данные" />
///     </args>
///     <body>
  abstract public function write($data);
///     </body>
///   </method>

///   <method name="format" returns="IO.Stream.AbstractStream">
///     <brief>Записывает данные в поток, используя форматирование в стиле printf</brief>
///     <details>
///       <p>В качестве первого аргумента передается строка формата, остальные аргументы --
///          параметры форматирования.</p>
///     </details>
///     <body>
  public function format() {
    $args = func_get_args();
    return $this->write(vsprintf(array_shift($args), $args));
  }
///     </body>
///   </method>

///   <method name="close">
///     <brief>Закрывает поток</brief>
///     <body>
  public function close() {}
///     </body>
///   </method>

///   <method name="rewind">
///     <brief>Устанвливает позицию в начало</brief>
///     <body>
  public function rewind() {
    return $this;
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="configuring">

///   <method name="binary" returns="IO.Stream.Stream">
///     <brief>Переводит поток в бинарный режим</brief>
///     <args>
///       <arg name="is_binary" type="boolean" default="true" brief="флаг перевода в бинарный режим" />
///     </args>
///     <body>
  public function binary($is_binary = true) {
    $this->binary = $is_binary;
    return $this;
  }
///     </body>
///   </method>

///   <method name="text" returns="IO.Stream.Stream">
///     <brief>Переводит поток в текстовый режим</brief>
///     <args>
///       <arg name="is_text" type="boolean" default="true" brief="флаг перевода в текстовый режим" />
///     </args>
///     <body>
  public function text($is_text = true) {
    $this->binary = !$is_text;
    return $this;
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="quering">

///   <method name="eof" returns="boolean" stereotype="abstract">
///     <brief>Проверяет, достигнут ли конец потока</brief>
///     <body>
  abstract public function eof();
///     </body>
///   </method>

///   </protocol>
}
/// </class>


/// <class name="IO.Stream.ResourceStream" extends="IO.Stream.AbstractStream">
///     <brief>Поток, связанный с ресурсом</brief>
///   <implements interface="Core.PropertyAccessInterface" />
///   <depends supplier="IO.Stream.Exception" stereotype="throws" />
///   <depends supplier="IO.Stream.Iterator" stereotype="creates" />
///   <details>
///     <p>Представляет поток, связанный с неким ресурсом ввода/вывода по его идентификатору.</p>
///     <p>Свойства:</p>
///     <dl>
///       <dt>id</dt>
///       <dd>идентификатор ресурса (только чтение).</dd>
///     </dl>
///   </details>
// TODO: id = null по умолчанию
class IO_Stream_ResourceStream
  extends    IO_Stream_AbstractStream
  implements Core_PropertyAccessInterface {

  protected $id  = false;

///   <protocol name="creating">

///   <method name="__construct">
///     <brief>Конструктор</brief>
///     <args>
///       <arg name="id" type="int" brief="идентификатор ресурса" />
///     </args>
///     <body>
  public function __construct($id) { $this->id = $id; }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="performing">

///   <method name="write" returns="IO.Stream.ResourceStream">
///     <brief>Записывает данные в поток</brief>
///     <args>
///       <arg name="data" brief="данные" />
///     </args>
///     <body>
  public function write($data) {
    fwrite($this->id, (string) $data);
    return $this;
  }
///     </body>
///   </method>

///   <method name="write_line" returns="IO.Stream.ResourceStream">
///     <brief>Записывает в поток строку, добавляя в конец символ перевода строки</brief>
///     <args>
///       <arg name="data" brief="данные" />
///     </args>
///     <body>
  public function write_line($data) {
    $this->write($data."\n");
    return $this;
  }
///     </body>
///   </method>

///   <method name="line" returns="Io.Stream.ResourceStream">
///     <args>
///       <arg name="data" brief="данные" />
///     </args>
///     <details>
///       <p>Псевдоним для метода write_line().</p>
///     </details>
///     <body>
  public function line($data) { return $this->write_line($date); }
///     </body>
///   </method>

///   <method name="read_chunk" returns="string">
///     <brief>Читает данные из бинарного потока</brief>
///     <args>
///       <arg name="length" type="int" default="null" brief="длина читаемых данным" />
///     </args>
///     <body>
  public function read_chunk($length = null) {
    return fread($this->id, $length ? (int) $length : IO_Stream::DEFAULT_CHUNK_SIZE);
  }
///     </body>
///   </method>

///   <method name="read_line" returns="string">
///     <brief>Читает строку из текстового потока</brief>
///     <args>
///       <arg name="length" type="int" default="null" brief="длина строки" />
///     </args>
///     <body>
  public function read_line($length = null) {
    return (!$this->id || $this->eof()) ?
      null :
      fgets($this->id, $length ? (int) $length : IO_Stream::DEFAULT_LINE_LENGTH);
  }
///     </body>
///   </method>

///   <method name="eof" returns="boolean">
///     <brief>Определяет, достигнут ли конец потока</brief>
///     <body>
  public function eof() { return !$this->id || @feof($this->id); }
///     </body>
///   </method>

///   <method name="close">
///     <brief>Закрывает поток</brief>
///     <body>
  public function close() {
    if ($this->id) {
      if (@fclose($this->id))
        $this->id = null;
      else
        throw new IO_Stream_Exception("Unable to close named resource: $this->id");
    }
  }
///     </body>
///   </method>

///   <method name="rewind">
///     <brief>Устанвливает позицию в начало</brief>
///     <body>
  public function rewind() {
    @rewind($this->id);
    return $this;
  }
///     </body>
///   </method>

///   <method name="load" returns="string">
///     <brief>Возвращает все содержимое потока в виде строки</brief>
///     <details>
///       <p>Метод представляет собой обертку над встроенной функцией stream_get_contents().</p>
///     </details>
///     <body>
  public function load() { $this->rewind(); return stream_get_contents($this->id); }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="accessing" interface="Core.PropertyAccessInterface">

///   <method name="__get" returns="mixed">
///     <brief>Возвращает значение свойства</brief>
///     <args>
///       <arg name="property" type="string" brief="имя свойтсва" />
///     </args>
///     <body>
  public function __get($property) {
    switch ($property) {
      case 'id':
      case 'binary':
        return $this->$property;
      default:
        throw new Core_MissingPropertyException($property);
    }
  }
///     </body>
///   </method>

///   <method name="__set">
///     <brief>Устанавливает значение свойства</brief>
///     <args>
///       <arg name="property" type="string" brief="имя свойства" />
///       <arg name="value" brief="значение" />
///     </args>
///     <body>
  public function __set($property, $value) {
    switch ($property) {
      case 'id':
      case 'binary':
        throw new Core_ReadOnlyPropertyException($property);
      default:
        throw Core_MissingPropertyException($property);
    }
  }
///     </body>
///   </method>

///   <method name="__isset" returns="boolean">
///     <brief>Проверяет установку значения свойства</brief>
///     <args>
///       <arg name="property" type="string" brief="имя свойства" />
///     </args>
///     <body>
  public function __isset($property) {
    switch ($property) {
      case 'id':
      case 'binary':
        return true;
      default:
        return false;
    }
  }
///     </body>
///   </method>

///   <method name="__unset">
///     <brief>Удаляет свойство</brief>
///     <args>
///       <arg name="property" type="string" breif="имя свойства" />
///     </args>
///     <body>
  public function __unset($property) {
    switch ($property) {
      case 'id':
      case 'binary':
        throw new Core_ReadOnlyPropertyException($property);
      default:
        throw new Core_MissingPropertyException($property);
    }
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="iterating" interface="IteratorAggregate">

///   <method name="getIterator">
///     <brief>Создает итератор потока класса IO.Stream.Iterator</brief>
///     <details>
///       <p>Наличие этого метода позволяет использовать объект потока как итератор.</p>
///     </details>
///     <body>
  public function getIterator() { return new IO_Stream_Iterator($this); }
///     </body>
///   </method>

///   </protocol>
}
/// </class>


/// <class name="IO.Stream.TemporaryStream" extends="IO.Stream.ResourceStream">
///     <brief>Поток для временных файлов</brief>
class IO_Stream_TemporaryStream extends IO_Stream_ResourceStream {

///   <protocol name="creating">

///   <method name="__construct">
///     <brief>Конструктор</brief>
///     <details>
///       <p>Создает временный поток. Для данных потока открывается временный файл, для создания
///          файла используется встроенная функция tmpfile().</p>
///     </details>
///     <body>
  public function __construct() { parent::__construct(tmpfile()); }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="destroying">

///   <method name="__destruct">
///     <brief>Декструктор</brief>
///     <details>
///       <p>Автоматически закрывает поток при удалении объекта.</p>
///     </details>
///     <body>
  public function __destruct() { $this->close(); }
///     </body>
///   </method>

///   </protocol>
}
/// </class>


/// <class name="IO.Stream.NamedResourceStream" extends="IO.Stream.ResourceStream">
///   <brief>Поток для именованных ресурсов</brief>
class IO_Stream_NamedResourceStream extends IO_Stream_ResourceStream {

///   <protocol name="creating">

///   <method name="__construct">
///     <brief>Конструктор</brief>
///     <args>
///       <arg name="uri"  type="string" brief="URI ресурса" />
///       <arg name="mode" type="string" default="IO_Stream::DEFAULT_OPEN_MODE" brief="способ открытия ресурса" />
///     </args>
///     <details>
///       <p>Открывает именованный ресурс по его URI (например, локальному пути для файловых
///          ресурсов.</p>
///     </details>
///     <body>
  public function __construct($uri, $mode = IO_Stream::DEFAULT_OPEN_MODE) {
    if (!$this->id = @fopen($uri, $mode))
        throw new IO_Stream_Exception("Unable to open named resource: $uri");
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="destroying">

///   <method name="__destruct">
///     <brief>Деструктор</brief>
///     <details>
///       <p>Автоматически закрывает поток при удалении объекта.</p>
///     </details>
///     <body>
  public function __destruct() { $this->close(); }
///     </body>
///   </method>

///   </protocol>
}
/// </class>

/// <interface name="IO.Stream.SeekInterface">
///   <brief>Интерфейс позиционирования в потоке</brief>
///   <details>
///     <p>Интерфейс должен быть реализован классами потоков, допускающих позиционирование,
///        например, файловыми потоками.</p>
///     <p>Интерфейс определяет набор констант, задающих тип смещения:</p>
///     <dl>
///       <dt>SEEK_SET</dt>
///       <dd>абсолютное позицинировние;</dd>
///       <dt>SEEK_CUR</dt>
///       <dd>позиционирование относительно текущего положения;</dd>
///       <dt>SEEK_END</dt>
///       <dd>позиционирование относительно конца файла.</dd>
///     </dl>
///   </details>
interface IO_Stream_SeekInterface {

///   <constants>
  const SEEK_SET = 0;
  const SEEK_CUR = 1;
  const SEEK_END = 2;
///   </constants>

///  <protocol name="performing">

///   <method name="seek" returns="number">
///     <brief>Устанавливает текущую позицию в потоке</brief>
///     <args>
///       <arg name="offset" type="int" brief="смещение" />
///       <arg name="whence" type="int" brief="позиция от которой делается смещение" />
///     </args>
///     <body>
  public function seek($offset, $whence);
///     </body>
///   </method>

///   <method name="tell" returns="number">
///     <brief>Возвращает текущую позицию в потоке</brief>
///     <body>
  public function tell();
///     </body>
///   </method>

///   </protocol>
}
/// </interface>


/// <class name="IO.Stream.Iterator">
///   <brief>Итератор потока</brief>
///   <details>
///     <p>Позволяет использовать объект потока в качестве итератора, например, внутри цикла
///        foreach. В большинстве случаев объекты этого класса используются неявно через интерфейс
///        IteratorAggregate потока.</p>
///     <p>Чтение данных зависит от типа потока: для текстовых оно производится построчно, для
///        бинарных -- порциями размера, соответствующего размеру буфера чтения. Ключи итератора
///        соответствуют порядковому номеру операции чтения, начиная с 1.</p>
///   </details>
///   <implements interface="Iterator" />
class IO_Stream_Iterator implements Iterator {

  private $stream;

  private $data        = null;
  private $data_count  = 0;

///   <protocol name="creating">

///   <method name="__construct">
///     <brief>Конструктор</brief>
///     <args>
///       <arg name="stream" type="IO.Stream.IOStream" brief="поток" />
///     </args>
///     <body>
  public function __construct(IO_Stream_AbstractStream $stream) { $this->stream = $stream; }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="iterating">

///   <method name="current" returns="string">
///     <brief>Возвращает очередной элемент</brief>
///     <body>
  public function current() { return $this->data === null ? $this->read() : $this->data; }
///     </body>
///   </method>

///   <method name="key" returns="int">
///     <brief>Возвращает ключ для очередного элемента</brief>
///     <body>
  public function key() { return $this->data_count; }
///     </body>
///   </method>

///   <method name="next">
///     <brief>Возвращает следующий элемент.</brief>
///     <body>
  public function next() { $this->read(); }
///     </body>
///   </method>

///   <method name="rewind">
///     <brief>Сбрасывает итератор</brief>
///     <details>
///       <p>Сброс итератора приводит только к сбросу счетчика элементов.</p>
///     </details>
///     <body>
  public function rewind() { $this->data_count = 0; $this->stream->rewind();}
///     </body>
///   </method>

///   <method name="valid" returns="boolean">
///     <brief>Проверяет доступность элементов итератора</brief>
///     <body>
  public function valid() {
    return $this->data_count==0 ? !$this->stream->eof() : !$this->data===false; }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="supporting">

///   <method name="read" returns="string" access="private">
///     <brief>Выполняет чтение очередной порции данных из потока</brief>
///     <body>
  private function read() {
    if (($this->data = $this->stream->read()) !== false)
      $this->data_count++;
    return $this->data;
  }
///     </body>
///   </method>

///   </protocol>
}
/// </class>

/// </module>
