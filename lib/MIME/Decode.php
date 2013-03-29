<?php
/// <module name="MIME.Decode" version="0.2.0" maintainer="timokhin@techart.ru">
///   <brief>Модуль для Base64 и Quoted-Printable  декодирования</brief>
Core::load('MIME');

/// <class name="MIME.Decode" stereotype="module">
///   <implements interface="Core.ModuleInterface" />
///   <depends supplier="MIME" stereotype="uses" />
///   <depends supplier="MIME.Decode.Base64Decoder" stereotype="creates" />
///   <depends supplier="MIME.Decode.QuotedPrintableDecoder" stereotype="creates" />
///   <depends supplier="MIME.Decode.EightBitDecoder" stereotype="creates" />
class MIME_Decode implements Core_ModuleInterface {

///   <constants>
  const MODULE  = 'MIME.Decode';
  const VERSION = '0.2.0';
  const BASE64_CHUNK_SIZE = 32768;
///   </constants>

///   <protocol name="building">

///   <method name="decoder" returns="IO.Stream.AbstractDecoder" scope="class">
///     <brief>Возвращает декодировщик соответствующий $encoding</brief>
///     <args>
///       <arg name="encoding" type="string" brief="тип кодирования" />
///       <arg name="stream"   type="IO.Stream.AbstractStream" default="null" brief="поток" />
///     </args>
///     <body>
  static public function decoder($encoding, IO_Stream_AbstractStream $stream = null) {
    switch ($encoding) {
      case MIME::ENCODING_B64:
        return new MIME_Decode_Base64Decoder($stream);
      case MIME::ENCODING_QP:
        return new MIME_Decode_QuotedPrintableDecoder($stream);
      case MIME::ENCODING_8BIT:
      default:
        return new MIME_Decode_EightBitDecoder($stream);
    }
  }
///     </body>
///   </method>

///   <method name="Base64Decoder" returns="MIME.Decode.Base64Decoder" scope="class">
///     <brief>Фабричный метод, возвращает объект класса MIME.Decode.Base64Decoder</brief>
///     <args>
///       <arg name="stream"   type="IO.Stream.AbstractStream" default="null" brief="входной поток" />
///     </args>
///     <body>
  static public function Base64Decoder(IO_Stream_AbstractStream $stream = null) {
    return new MIME_Decode_Base64Decoder($stream);
  }
///     </body>
///   </method>

///   <method name="QuotedPrintableDecoder" returns="MIME.Decode.QuoterPrintableDecoder" scope="class">
///     <brief>Фабричный метод, возвращает объект класса MIME.Decode.QuoterPrintableDecoder</brief>
///     <args>
///       <arg name="stream"   type="IO.Stream.AbstractStream" default="null" brief="входной поток" />
///     </args>
///     <body>
  static public function QuotedPrintableDecoder(IO_Stream_AbstractStream $stream = null) {
    return new MIME_Decode_QuotedPrintableDecoder($stream);
  }
///     </body>
///   </method>

///   </protocol>
}
/// </class>


/// <class name="MIME.Decode.AbstractDecoder" stereotype="abstract">
///   <brief>Абстрактный класс декодировщика</brief>
///   <depends supplier="IO.Stream.AbstractStream" stereotype="uses" />
abstract class MIME_Decode_AbstractDecoder implements Iterator {

  protected $stream;

  protected $boundary;

  protected $count = 0;
  protected $current;

  protected $is_last_part = false;

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

///   <method name="with_boundary" returns="MIME.Decode.AbstractDecoder">
///     <brief>Устанавливает границу</brief>
///     <body>
  public function with_boundary($boundary) {
    $this->boundary = (string) $boundary;
    return $this;
  }
///     </body>
///   </method>

///   <method name="from_stream" returns="MIME.Decode.AbstractDecoder">
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

///   <protocol name="quering">

///   <method name="is_last_part" returns="boolean">
///     <brief>Проверяет евляется ли текущая часть письма последней</brief>
///     <body>
  public function is_last_part() { return $this->is_last_part; }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="iterating">

///   <method name="rewind">
///     <brief>сбрасывает итератор на начало</brief>
///     <body>
  public function rewind() {
    $this->is_last_part = false;
    $this->count = 0;
    $this->next();
  }
///     </body>
///   </method>

///   <method name="current" returns="string">
///     <brief>Возвращает текущий элемент итератора</brief>
///     <body>
  public function current() {
    return $this->current;
  }
///     </body>
///   </method>

///   <method name="valid" returns="boolean">
///     <brief>Проверяет валидность текущего элемента итератора</brief>
///     <body>
  public function valid() { return $this->current !== null; }
///     </body>
///   </method>

///   <method name="key" returns="int">
///     <brief>Возвращает ключ текущего элемента итератора</brief>
///     <body>
  public function key() { return $this->count; }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="processing">

///   <method name="to_stream" returns="MIME.Decode.AbstractDecoder">
///     <brief>Устанавливает выходной поток</brief>
///     <args>
///       <arg name="stream" type="IO.Stream.AbstractStream" brief="поток" />
///     </args>
///     <body>
  public function to_stream(IO_Stream_AbstractStream $stream) {
    foreach ($this as $chunk) $stream->write($chunk);
    return $this;
  }
///     </body>
///   </method>

///   <method name="to_temporary_stream" returns="IO.Stream.TemporaryStream">
///     <brief>Устанавливает выходной поток во временный фаил</brief>
///     <body>
  public function to_temporary_stream() {
    $this->to_stream($stream = IO_Stream::TemporaryStream());
    return $stream;
  }
///     </body>
///   </method>

///   <method name="to_string" returns="string">
///     <brief>Возвращает весь разкодированный текст</brief>
//TODO: если есть to_string чтобы не добавить Core_StringifyInterface
///     <body>
  public function to_string() {
    $result = '';
    foreach ($this as $chunk) $result .= $chunk;
    return $result;
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="supporting">

///   <method name="is_boundary" returns="boolean" access="protected">
///     <brief>Проверяет является ли $lien границей письма</brief>
///     <args>
///       <arg name="line" type="string" brief="строка" />
///     </args>
///     <body>
  protected function is_boundary($line) {
    return ($this->boundary && Core_Regexps::match("{^--{$this->boundary}(?:--)?\n\r?$}", $line));
  }
///     </body>
///   </method>

///   <method name="read_line" returns="string" access="protected">
///     <brief>Считывает строку из входного потока</brief>
///     <body>
  protected function read_line() {
    if ($this->stream->eof()) {
      $this->is_last_part = true;
      return null;
    }

    $line = $this->stream->read_line();

    if ($this->is_boundary($line)) {
      if (Core_Regexps::match("{{$this->boundary}--\n\r?}", $line)) $this->is_last_part = true;
      return null;
    }
   return $line;
  }
///     </body>
///   </method>

///   </protocol>
}
/// </class>


/// <class name="MIME.Decode.Base64Decoder" extends="MIME.Decode.AbstractDecoder">
///   <brief>Base64 декодировщик</brief>
class MIME_Decode_Base64Decoder extends MIME_Decode_AbstractDecoder {

  protected $buffer = '';

///   <protocol name="iterating">

///   <method name="next">
///     <brief>Возвращает следующий элемент итератора</brief>
///     <body>
  public function next() {
    while (($line = $this->read_line()) !== null) {
      $this->buffer .= Core_Regexps::replace('{[^a-zA-Z0-9+/]}', '', $line);
      if (strlen($this->buffer) > MIME_Decode::BASE64_CHUNK_SIZE) break;
    }

    if ($this->buffer == '')
      $this->current = null;
    else {
      if (strlen($this->buffer) > MIME_Decode::BASE64_CHUNK_SIZE) {
        $len_4xN = strlen($this->buffer) & ~3;
        $this->current = base64_decode(substr($this->buffer, 0, $len_4xN));
        $this->buffer = substr($this->buffer, $len_4xN);
      } else {
        $this->buffer .= '===';
        $len_4xN = strlen($this->buffer) & ~3;
        $this->current = base64_decode(substr($this->buffer, 0, $len_4xN));
        $this->buffer = '';
      }
      $this->count++;
    }
  }
///     </body>
///   </method>

///   </protocol>
}
/// </class>


/// <class name="MIME.Decode.QuotedPrintableDecoder" extends="MIME.Decode.AbstractDecoder">
///   <brief>QuotedPrintable декодировщик</brief>
class MIME_Decode_QuotedPrintableDecoder extends MIME_Decode_AbstractDecoder {

///   <protocol name="iterating">

///   <method name="next">
///     <brief>Возвращает следующий элемент итератора</brief>
///     <body>
  public function next() {
    $this->current = ($line = $this->read_line()) === null ?
      null : MIME::decode_qp($line);
    $this->count++;
  }
///     </body>
///   </method>

///   </protocol>
}
/// </class>


/// <class name="MIME.Decode.EightBitDecoder" extends="MIME.Decode.AbstractDecoder">
///   <brief>EightBit декодировщик</brief>
class MIME_Decode_EightBitDecoder extends MIME_Decode_AbstractDecoder {

///   <protocol name="iterating">

///   <method name="next">
///     <brief>Возвращает следующий элемент итератора</brief>
///     <body>
  public function next() {
    if (($this->current = $this->read_line()) !== null) $this->count++;
  }
///     </body>
///   </method>

///   </protocol>
}
/// </class>

/// </module>
