<?php
/**
 * Text
 * 
 * Модуль для работы с текстом
 * 
 * @package Text
 * @version 0.2.0
 */
/**
 * @package Text
 */
class Text implements Core_ModuleInterface {

  const VERSION = '0.2.0';

  const DEFAULT_TOKEN_DELIMITER = "\n";
  const DEFAULT_JOIN_DELIMITER  = ' ';


/**
 * Фабричный метод, возвращает объект классаText.Tokenizer
 * 
 * @return Text_Tokenizer
 */
  static public function Tokenizer($source, $delimiter = Text::DEFAULT_TOKEN_DELIMITER) {
    return new Text_Tokenizer($source, $delimiter);
  }

/**
 * Фабричный метод, возвращает объект класса Text.Builder
 * 
 * @return Text_Builder
 */
  static public function Builder() { return new Text_Builder(); }

  static public function process($source, $process = array())
  {
    Core::load('Text.Process');
    return Text_Process::process($source, $process);
  }

}


/**
 * Класс предоставляет итератор по тексту, разбитому по разделителю
 * 
 * @package Text
 */
class Text_Tokenizer implements Iterator {

  protected $delimiter;
  protected $source;
  protected $current;
  protected $length;
  protected $offset  = 0;
  protected $index   = 0;


/**
 * Конструктор
 * 
 * @param string $source
 * @param string $delimiter
 */
  public function __construct($source, $delimiter = Text::DEFAULT_TOKEN_DELIMITER) {
    $this->source    = $source;
    $this->length    = strlen($source);
    $this->delimiter = $delimiter;
  }



/**
 * Сбрасывает итератор на начало
 * 
 */
  public function rewind() {
    $this->offset = 0;
    $this->current = null;
    $this->index = 0;
    $this->skip();
  }

/**
 * Возвращает текущий элемент итератора
 * 
 * @return string
 */
  public function current() { return $this->current; }

/**
 * Возвращает ключ текущего элемента итератора
 * 
 * @return int
 */
  public function key() { return $this->index; }

/**
 * Возвращает следующий элемент итератора
 * 
 */
  public function next() { $this->skip()->index++; }

/**
 * Проверяет валидность текущего элемента итератора
 * 
 * @return boolean
 */
  public function valid() { return $this->current !== null; }



/**
 * Устанавливает текущий элемент итератора
 * 
 * @return Text_StringTokenizer
 */
  protected function skip() {
    if ($this->offset >= $this->length) {
      $this->current = null;
    } elseif (($pos = strpos($this->source, $this->delimiter, $this->offset)) !== false) {
      $this->current = substr($this->source, $this->offset, $pos - $this->offset);
      $this->offset = $pos + strlen($this->delimiter);
    } else {
      $this->current = substr($this->source, $this->offset);
      $this->offset  = $this->length;
    }
    return $this;
  }

}


/**
 * @package Text
 */
//TODO: надо доку писать
class Text_Builder implements Core_StringifyInterface {

  const TEXT       = 0;
  const END        = 1;
  const DELIMITER  = 2;
  const MARGIN     = 3;
  const STARTED    = 4;

  protected $contexts = array();
  protected $current  = -1;


/**
 */
  public function __construct() { $this->context('', '', 0, ''); }





/**
 * @param string $begin
 * @param string $end
 * @param string $margin
 * @return Text_Builder
 */
  public function inside($begin, $end, $margin) { return $this->context($begin, $end, $margin); }

/**
 * @param int $margin
 * @return Text_Builder
 */
  public function begin($margin = 0) { return $this->context('', '', $margin); }

/**
 * @param string $delimiter
 * @return Text_Builder
 */
  public function with_delimiter($delimiter) {
    $this->contexts[$this->current][self::DELIMITER] = (string) $delimiter;
    return $this;
  }

/**
 * @param string $delimiter
 * @param int $margin
 * @return Text_Builder
 */
  public function begin_with_delimiter($delimiter, $margin = 0) {
    return $this->begin($margin)->with_delimiter($delimiter);
  }

/**
 * @param int $margin
 * @return Text_Builder
 */
  public function with_margin($margin) {
    $this->contexts[$current][self::MARGIN] = (int) $margin;
    return $this;
  }

/**
 * @return Text_Builder
 */
  public function end() {
    if ($this->current > 0) {
      $this->contexts[$this->current - 1][self::TEXT] .=
        $this->contexts[$this->current][self::TEXT].$this->contexts[$this->current][self::END];

      unset($this->contexts[$this->current]);
      $this->current--;
    }
    return $this;
  }

/**
 * @param string $text
 * @return Text_Builder
 */
  public function t($text) {
    $current =& $this->contexts[$this->current];

    $current[self::TEXT] .=
      str_replace(
        "\n",
        "\n".str_repeat(' ',
          $current[self::MARGIN]),
          ($current[self::STARTED] ? $current[self::DELIMITER] : '').$text);

    $current[self::STARTED] = true;
    return $this;
  }

/**
 * @param string $text
 * @return Text_Builder
 */
  public function text($text) { return $this->t($text); }

/**
 * @param string $text
 * @return Text_Builder
 */
  public function l($text) { return $this->t($text)->nl(); }

/**
 * @param string $text
 * @return Text_Builder
 */
  public function line($text) { return $this->l($text); }

/**
 * @return Text_Builder
 */
  public function nl() {
    $this->contexts[$this->current][self::TEXT] .= "\n";
    return $this;
  }



/**
 * @return string
 */
  public function as_string() { return $this->contexts[0][0]; }

/**
 * @return string
 */
  public function __toString() { return $this->as_string(); }



/**
 * @param string $begin
 * @param string $end
 * @param int $margin
 * @param string $delimiter
 * @return Text_Builder
 */
  protected function context($begin = '', $end = '', $margin = 0, $delimiter = '') {
    $margin = ($this->current + 1 > 0 ? $this->contexts[$this->current][self::MARGIN] : 0) + $margin;

    $this->contexts[$this->current + 1] = array(

      self::TEXT =>
        ($margin > 0 &&
        $this->current + 1 > 0 &&
        substr($this->contexts[$this->current][self::TEXT], -1) == "\n" ?
          str_repeat(' ', $margin) : '').$begin,
      self::END       => $end,
      self::DELIMITER => $delimiter,
      self::MARGIN    => $margin,
      self::STARTED   => false );
    $this->current++;
    return $this;
  }

/**
 * @param string $token
 * @return Text_Builder
 */
  public function __get($token) {
    switch ($token) {
      case 'end':
      case 'nl':
        return $this->$token();
      default:
        return $this;
    }
  }

}

