<?php
/// <module name="MIME.Encode" version="0.2.0" maintainer="timokhin@techart.ru">
///   <brief>Модуль для Base64 Quoted-Printable кодирования</brief>
Core::load('MIME');

/// <class name="MIME.Encode" stereotype="module">
///   <implements interface="Core.ModuleInterface" />
///   <depends supplier="MIME.Encode.Base64Encoder" stereotype="creates" />
///   <depends supplier="MIME.Encode.QuotedPrintableEncoder" stereotype="creates" />
///   <depends supplier="MIME.Encode.EightBitEncoder" stereotype="creates" />
///   <depends supplier="MIME" stereotype="uses" />
class MIME_Encode implements Core_ModuleInterface {

///   <constants>
  const MODULE  = 'MIME.Encode';
  const VERSION = '0.2.0';
  const BASE64_CHUNK_SIZE = 57;
///   </constants>

///   <protocol name="building">

///   <method name="encoder" returns="IO.Stream.AbstractEncoder" scope="class">
///     <brief>Возвращает кодировщик соответствующий $encoding</brief>
///     <args>
///       <arg name="encoding" type="string" brief="тип кодирования" />
///       <arg name="stream" type="IO.Stream.AbstractStream" default="null" brief="поток" />
///     </args>
///     <body>
  static public function encoder($encoding, IO_Stream_AbstractStream $stream = null) {
    switch ($encoding) {
      case MIME::ENCODING_B64:
        return new MIME_Encode_Base64Encoder($stream);
      case MIME::ENCODING_QP:
        return new MIME_Encode_QuotedPrintableEncoder($stream);
      case MIME::ENCODING_8BIT:
      default:
        return new MIME_Encode_EightBitEncoder($stream);
    }
  }
///     </body>
///   </method>

///   <method name="Base64Encoder" returns="MIME.Encode.Base64Encoder" scope="class">
///     <brief>Фабричный метод, возвращает объект класса MIME.Encode.Base64Encoder</brief>
///     <args>
///       <arg name="stream" type="IO.Stream.AbstractStream" default="null" brief="входной поток" />
///     </args>
///     <body>
  static public function Base64Encoder(IO_Stream_AbstractStream $stream = null) {
    return new MIME_Encode_Base64Encoder($stream);
  }
///     </body>
///   </method>

///   <method name="QuotedPrintableEncoder" returns="MIME.Encode.QuotePrintableEncoder" scope="class">
///     <brief>Фабричный метод, возвращает объект класса MIME.Encode.QuotePrintableEncoder</brief>
///     <args>
///       <arg name="stream" type="IO.Stream.AbstractStream" default="null" brief="входной поток" />
///     </args>
///     <body>
  static public function QuotedPrintableEncoder(IO_Stream_AbstractStream $stream = null) {
    return new MIME_Encode_QuotedPrintableEncoder($stream);
  }
///     </body>
///   </method>

///   <method name="EightBitEncoder" returns="MIME.Encode.EightBitEncoder" scope="class">
///     <brief>Фабричный метод, возвращает объект класса MIME.Encode.EightBitEncoder</brief>
///     <args>
///       <arg name="stream" type="IO.Stream.AbstractStream" default="null" brief="входной поток" />
///     </args>
///     <body>
  static public function EightBitEncoder(IO_Stream_AbstractStream $stream = null) {
   return new MIME_Encode_EightBitEncoder($stream);
  }
///     </body>
///   </method>

///   </protocol>
}
/// </class>

/// <class name="MIME.Encode.AbstractEncoder" stereotype="abstract">
///   <brief>Абстрактный класс кодировщика</brief>
///   <implements interface="Iterator" />
///   <depends supplier="IO.Stream.AbstractStream" stereotype="uses" />
abstract class MIME_Encode_AbstractEncoder implements Iterator {

  protected $stream;
  protected $current;
  protected $count = 0;

///   <protocol name="creating">

///   <method name="__construct">
///     <brief>Конструктор</brief>
///     <args>
///       <arg name="input" type="IO_Stream_AbstractStream" brief="входной поток" />
///     </args>
///     <body>
  public function __construct(IO_Stream_AbstractStream $stream = null) {
    if ($stream) $this->from_stream($stream);
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="configuring">

///   <method name="from_stream" returns="MIME.Encode.AbstractEncoder">
///     <brief>Устанавливает входной поток</brief>
///     <args>
///       <arg name="stream" type="IO.Stream.AbstractStream" brief="поток" />
///     </args>
///     <body>
  public function from_stream(IO_Stream_AbstractStream $stream) {
    $this->stream = $stream;
    return $this;
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="iterating">

///   <method name="current">
///     <brief>Возвращает текущий элемент итератора</brief>
///     <body>
  public function current() { return $this->current; }
///     </body>
///   </method>

///   <method name="key" returns="int">
///     <brief>Возвращает ключ текущего элемента итератора</brief>
///     <body>
  public function key() { return $this->count; }
///     </body>
///   </method>

///     <method name="valid" returns="boolean">
///     <brief>Проверяет валидность текущего элемента итератора</brief>
///       <body>
  public function valid() { return $this->current === null ? false : true; }
///       </body>
///     </method>

///   </protocol>

///   <protocol name="processing">

///   <method name="to_stream" returns="MIME.Encode.AbstractEncoder">
///     <brief>Устанавливает выходной поток</brief>
///     <args>
///       <arg name="stream" type="IO.Stream.AbstractStream" brief="поток" />
///     </args>
///     <body>
  public function to_stream(IO_Stream_AbstractStream $stream) {
    foreach ($this as $line) $stream->write($line);
    return $this;
  }
///     </body>
///   </method>

///   <method name="to_string" returns="string">
///     <brief>Возвращает весь закодированный текст</brief>
//TODO: если есть to_string чтобы не добавить Core_StringifyInterface
///     <body>
  public function to_string() {
    $result = '';
    foreach ($this as $line) $result .= $line;
    return $result;
  }
///     </body>
///   </method>

///   <method name="encode">
///     <brief>Кодирует текст</brief>
///     <args>
///       <arg name="text" type="string" brief="текст" />
///     </args>
///     <body>
  abstract public function encode($text);
///     </body>
///   </method>

///   </protocol>
}
/// </class>


/// <class name="MIME.Encode.Base64Encoder" extends="MIME.Encode.AbstractEncoder">
///   <brief>Base64 кодировщик</brief>
class MIME_Encode_Base64Encoder extends MIME_Encode_AbstractEncoder {

  protected $cache;
  protected $data;
  protected $pos;

///   <protocol name="iterating">

///   <method name="rewind">
///     <brief>сбрасывает итератор на начало</brief>
///     <body>
  public function rewind() {
    $this->count   = 0;
    $this->cache   = '';
    $this->pos     = 0;

    if (($this->data = $this->stream->read_chunk()) != null) {
      $this->current = base64_encode(
        substr($this->data, 0, MIME_Encode::BASE64_CHUNK_SIZE)).MIME::LINE_END;
      $this->pos = MIME_Encode::BASE64_CHUNK_SIZE;
    }
  }
///     </body>
///   </method>

///   <method name="next">
///     <brief>Возвращает следующий элемент итератора</brief>
///     <body>
  public function next() {
    while (true) {
      if ($this->data == '' && $this->cache == '') {
        $this->current = null;
        break;
      }

      if ($this->pos + MIME_Encode::BASE64_CHUNK_SIZE < strlen($this->data)) {
        $this->current = base64_encode(
          substr($this->data, $this->pos, MIME_Encode::BASE64_CHUNK_SIZE))."\n";
        $this->pos += MIME_Encode::BASE64_CHUNK_SIZE;
        $this->count++;
        break;
      } else {
        $this->cache = substr($this->data, $this->pos);

        if (($chunk = $this->stream->read_chunk()) != '') {
          $this->pos = 0;
          $this->data = $this->cache.$chunk;
        } else {
          $this->current = base64_encode($this->cache)."\n";
          $this->data  = '';
          $this->cache = '';
          $this->pos   = 0;
          $this->count++;
          break;
        }
      }
    }
  }
///       </body>
///     </method>


///     </protocol>

///   <protocol name="processing">

///   <method name="encode">
///     <brief>Кодирует текст</brief>
///     <args>
///       <arg name="text" type="string" brief="текст" />
///     </args>
///     <body>
  public function encode($text) {
    return MIME::encode_b64($text);
  }
///     </body>
///   </method>

///   </protocol>

}
/// </class>


/// <class name="MIME.Encode.QuotedPrintableEncoder" extends="MIME.Encode.AbstractEncoder">
///   <brief>QuotedPrintable кодировщик</brief>
class MIME_Encode_QuotedPrintableEncoder extends MIME_Encode_AbstractEncoder {

  protected $current;
  protected $count = 0;
  protected $length = MIME::LINE_LENGTH;

///   <protocol name="iterating">

///   <method name="rewind">
///     <brief>Сбрасывает итератор в начало</brief>
///     <body>
  public function rewind() {
    $this->count = 0;
    $this->current = MIME::encode_qp($this->stream->read_line(), $this->length);
  }
///     </body>
///   </method>

///   <method name="next">
///     <brief>Возвращает следующий элемент итератора</brief>
///     <body>
  public function next() {
    if ($this->stream->eof())
      $this->current = null;
    else {
      $this->current = MIME::encode_qp($this->stream->read_line(), $this->length);
      $this->count++;
    }
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="processing">

///   <method name="encode">
///     <brief>Кодирует текст</brief>
///     <args>
///       <arg name="text" type="string" brief="текст" />
///     </args>
///     <body>
  public function encode($text) {
    return MIME::encode_qp($text, $this->length);
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="supporting">

///   <method name="line_length" returns="MIME.Encode.QuotedPrintableEncoder">
///     <brief>Устанавливает длину строки кодирования</brief>
///     <args>
///       <arg name="value" type="int" brief="длина" />
///     </args>
///     <body>
  public function line_length($value) {
    $this->length = (int) $value;
    return $this;
  }
///     </body>
///   </method>

///   </protocol>

}
/// </class>

/// <class name="MIME.Encode.EightBitEncoder" extends="MIME.Encode.AbstractEncoder">
///   <brief>EightBit кодировщик</brief>
class MIME_Encode_EightBitEncoder extends MIME_Encode_AbstractEncoder {

///   <protocol name="iterating">

///   <method name="rewind">
///     <brief>Сбрасывает итератор на начало</brief>
///     <body>
  public function rewind() {
    $this->count = 0;
    $this->current = $this->stream->read_line();
  }
///     </body>
///   </method>

///   <method name="next">
///     <brief>Возвращает следующий элемент итератора</brief>
///     <body>
  public function next() {
    if (($this->current = $this->stream->read_line()) !== null) $this->count++;
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="processing">

///   <method name="encode">
///     <brief>Кодирует текст</brief>
///     <args>
///       <arg name="text" type="string" brief="текст" />
///     </args>
///     <body>
  public function encode($text) {
    return $text;
  }
///     </body>
///   </method>

///   </protocol>

}
/// </class>

/// </module>
