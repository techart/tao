<?php
/**
 * MIME.Decode
 * 
 * Модуль для Base64 и Quoted-Printable  декодирования
 * 
 * @package MIME\Decode
 * @version 0.2.0
 */
Core::load('MIME');

/**
 * @package MIME\Decode
 */
class MIME_Decode implements Core_ModuleInterface {

  const MODULE  = 'MIME.Decode';
  const VERSION = '0.2.0';
  const BASE64_CHUNK_SIZE = 32768;


/**
 * Возвращает декодировщик соответствующий $encoding
 * 
 * @param string $encoding
 * @param IO_Stream_AbstractStream $stream
 * @return IO_Stream_AbstractDecoder
 */
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

/**
 * Фабричный метод, возвращает объект класса MIME.Decode.Base64Decoder
 * 
 * @param IO_Stream_AbstractStream $stream
 * @return MIME_Decode_Base64Decoder
 */
  static public function Base64Decoder(IO_Stream_AbstractStream $stream = null) {
    return new MIME_Decode_Base64Decoder($stream);
  }

/**
 * Фабричный метод, возвращает объект класса MIME.Decode.QuoterPrintableDecoder
 * 
 * @param IO_Stream_AbstractStream $stream
 * @return MIME_Decode_QuoterPrintableDecoder
 */
  static public function QuotedPrintableDecoder(IO_Stream_AbstractStream $stream = null) {
    return new MIME_Decode_QuotedPrintableDecoder($stream);
  }

}


/**
 * Абстрактный класс декодировщика
 * 
 * @abstract
 * @package MIME\Decode
 */
abstract class MIME_Decode_AbstractDecoder implements Iterator {

  protected $stream;

  protected $boundary;

  protected $count = 0;
  protected $current;

  protected $is_last_part = false;


/**
 * Конструктор
 * 
 * @param IO_Stream_AbstractStream $input
 */
  public function __construct(IO_Stream_AbstractStream $stream = null) {
    if ($stream) $this->from_stream($stream);
  }



/**
 * Устанавливает границу
 * 
 * @return MIME_Decode_AbstractDecoder
 */
  public function with_boundary($boundary) {
    $this->boundary = (string) $boundary;
    return $this;
  }

/**
 * Устанавливает входной поток
 * 
 * @param IO_Stream_AbstractStream $stream
 * @return MIME_Decode_AbstractDecoder
 */
  public function from_stream(IO_Stream_AbstractStream $stream) {
    $this->stream = $stream;
    return $this;
  }



/**
 * Проверяет евляется ли текущая часть письма последней
 * 
 * @return boolean
 */
  public function is_last_part() { return $this->is_last_part; }



/**
 * сбрасывает итератор на начало
 * 
 */
  public function rewind() {
    $this->is_last_part = false;
    $this->count = 0;
    $this->next();
  }

/**
 * Возвращает текущий элемент итератора
 * 
 * @return string
 */
  public function current() {
    return $this->current;
  }

/**
 * Проверяет валидность текущего элемента итератора
 * 
 * @return boolean
 */
  public function valid() { return $this->current !== null; }

/**
 * Возвращает ключ текущего элемента итератора
 * 
 * @return int
 */
  public function key() { return $this->count; }



/**
 * Устанавливает выходной поток
 * 
 * @param IO_Stream_AbstractStream $stream
 * @return MIME_Decode_AbstractDecoder
 */
  public function to_stream(IO_Stream_AbstractStream $stream) {
    foreach ($this as $chunk) $stream->write($chunk);
    return $this;
  }

/**
 * Устанавливает выходной поток во временный фаил
 * 
 * @return IO_Stream_TemporaryStream
 */
  public function to_temporary_stream() {
    $this->to_stream($stream = IO_Stream::TemporaryStream());
    return $stream;
  }

/**
 * Возвращает весь разкодированный текст
 * 
 * @return string
 */
//TODO: если есть to_string чтобы не добавить Core_StringifyInterface
  public function to_string() {
    $result = '';
    foreach ($this as $chunk) $result .= $chunk;
    return $result;
  }



/**
 * Проверяет является ли $lien границей письма
 * 
 * @param string $line
 * @return boolean
 */
  protected function is_boundary($line) {
    return ($this->boundary && Core_Regexps::match("{^--{$this->boundary}(?:--)?\n\r?$}", $line));
  }

/**
 * Считывает строку из входного потока
 * 
 * @return string
 */
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

}


/**
 * Base64 декодировщик
 * 
 * @package MIME\Decode
 */
class MIME_Decode_Base64Decoder extends MIME_Decode_AbstractDecoder {

  protected $buffer = '';


/**
 * Возвращает следующий элемент итератора
 * 
 */
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

}


/**
 * QuotedPrintable декодировщик
 * 
 * @package MIME\Decode
 */
class MIME_Decode_QuotedPrintableDecoder extends MIME_Decode_AbstractDecoder {


/**
 * Возвращает следующий элемент итератора
 * 
 */
  public function next() {
    $this->current = ($line = $this->read_line()) === null ?
      null : MIME::decode_qp($line);
    $this->count++;
  }

}


/**
 * EightBit декодировщик
 * 
 * @package MIME\Decode
 */
class MIME_Decode_EightBitDecoder extends MIME_Decode_AbstractDecoder {


/**
 * Возвращает следующий элемент итератора
 * 
 */
  public function next() {
    if (($this->current = $this->read_line()) !== null) $this->count++;
  }

}

