<?php
/**
 * MIME.Encode
 * 
 * Модуль для Base64 Quoted-Printable кодирования
 * 
 * @package MIME\Encode
 * @version 0.2.0
 */
Core::load('MIME');

/**
 * @package MIME\Encode
 */
class MIME_Encode implements Core_ModuleInterface {

  const MODULE  = 'MIME.Encode';
  const VERSION = '0.2.0';
  const BASE64_CHUNK_SIZE = 57;


/**
 * Возвращает кодировщик соответствующий $encoding
 * 
 * @param string $encoding
 * @param IO_Stream_AbstractStream $stream
 * @return IO_Stream_AbstractEncoder
 */
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

/**
 * Фабричный метод, возвращает объект класса MIME.Encode.Base64Encoder
 * 
 * @param IO_Stream_AbstractStream $stream
 * @return MIME_Encode_Base64Encoder
 */
  static public function Base64Encoder(IO_Stream_AbstractStream $stream = null) {
    return new MIME_Encode_Base64Encoder($stream);
  }

/**
 * Фабричный метод, возвращает объект класса MIME.Encode.QuotePrintableEncoder
 * 
 * @param IO_Stream_AbstractStream $stream
 * @return MIME_Encode_QuotePrintableEncoder
 */
  static public function QuotedPrintableEncoder(IO_Stream_AbstractStream $stream = null) {
    return new MIME_Encode_QuotedPrintableEncoder($stream);
  }

/**
 * Фабричный метод, возвращает объект класса MIME.Encode.EightBitEncoder
 * 
 * @param IO_Stream_AbstractStream $stream
 * @return MIME_Encode_EightBitEncoder
 */
  static public function EightBitEncoder(IO_Stream_AbstractStream $stream = null) {
   return new MIME_Encode_EightBitEncoder($stream);
  }

}

/**
 * Абстрактный класс кодировщика
 * 
 * @abstract
 * @package MIME\Encode
 */
abstract class MIME_Encode_AbstractEncoder implements Iterator {

  protected $stream;
  protected $current;
  protected $count = 0;


/**
 * Конструктор
 * 
 * @param IO_Stream_AbstractStream $input
 */
  public function __construct(IO_Stream_AbstractStream $stream = null) {
    if ($stream) $this->from_stream($stream);
  }



/**
 * Устанавливает входной поток
 * 
 * @param IO_Stream_AbstractStream $stream
 * @return MIME_Encode_AbstractEncoder
 */
  public function from_stream(IO_Stream_AbstractStream $stream) {
    $this->stream = $stream;
    return $this;
  }



/**
 * Возвращает текущий элемент итератора
 * 
 */
  public function current() { return $this->current; }

/**
 * Возвращает ключ текущего элемента итератора
 * 
 * @return int
 */
  public function key() { return $this->count; }

/**
 * Проверяет валидность текущего элемента итератора
 * 
 * @return boolean
 */
  public function valid() { return $this->current === null ? false : true; }



/**
 * Устанавливает выходной поток
 * 
 * @param IO_Stream_AbstractStream $stream
 * @return MIME_Encode_AbstractEncoder
 */
  public function to_stream(IO_Stream_AbstractStream $stream) {
    foreach ($this as $line) $stream->write($line);
    return $this;
  }

/**
 * Возвращает весь закодированный текст
 * 
 * @return string
 */
//TODO: если есть to_string чтобы не добавить Core_StringifyInterface
  public function to_string() {
    $result = '';
    foreach ($this as $line) $result .= $line;
    return $result;
  }

/**
 * Кодирует текст
 * 
 * @param string $text
 */
  abstract public function encode($text);

}


/**
 * Base64 кодировщик
 * 
 * @package MIME\Encode
 */
class MIME_Encode_Base64Encoder extends MIME_Encode_AbstractEncoder {

  protected $cache;
  protected $data;
  protected $pos;


/**
 * сбрасывает итератор на начало
 * 
 */
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

/**
 * Возвращает следующий элемент итератора
 * 
 */
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




/**
 * Кодирует текст
 * 
 * @param string $text
 */
  public function encode($text) {
    return MIME::encode_b64($text);
  }


}


/**
 * QuotedPrintable кодировщик
 * 
 * @package MIME\Encode
 */
class MIME_Encode_QuotedPrintableEncoder extends MIME_Encode_AbstractEncoder {

  protected $current;
  protected $count = 0;
  protected $length = MIME::LINE_LENGTH;


/**
 * Сбрасывает итератор в начало
 * 
 */
  public function rewind() {
    $this->count = 0;
    $this->current = MIME::encode_qp($this->stream->read_line(), $this->length);
  }

/**
 * Возвращает следующий элемент итератора
 * 
 */
  public function next() {
    if ($this->stream->eof())
      $this->current = null;
    else {
      $this->current = MIME::encode_qp($this->stream->read_line(), $this->length);
      $this->count++;
    }
  }



/**
 * Кодирует текст
 * 
 * @param string $text
 */
  public function encode($text) {
    return MIME::encode_qp($text, $this->length);
  }



/**
 * Устанавливает длину строки кодирования
 * 
 * @param int $value
 * @return MIME_Encode_QuotedPrintableEncoder
 */
  public function line_length($value) {
    $this->length = (int) $value;
    return $this;
  }


}

/**
 * EightBit кодировщик
 * 
 * @package MIME\Encode
 */
class MIME_Encode_EightBitEncoder extends MIME_Encode_AbstractEncoder {


/**
 * Сбрасывает итератор на начало
 * 
 */
  public function rewind() {
    $this->count = 0;
    $this->current = $this->stream->read_line();
  }

/**
 * Возвращает следующий элемент итератора
 * 
 */
  public function next() {
    if (($this->current = $this->stream->read_line()) !== null) $this->count++;
  }



/**
 * Кодирует текст
 * 
 * @param string $text
 */
  public function encode($text) {
    return $text;
  }


}

