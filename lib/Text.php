<?php
/// <module name="Text" version="0.2.0" maintainer="timokhin@techart.ru">
///   <brief>Модуль для работы с текстом</brief>
/// <class name="Text" stereotype="module">
///   <depends supplier="Text.Tokenizer" stereotype="creates" />
///   <depends supplier="Text.Builder"    stereotype="creates" />
///   <implements interface="Core.ModuleInterface" />
class Text implements Core_ModuleInterface {

///   <constants>
  const VERSION = '0.2.0';

  const DEFAULT_TOKEN_DELIMITER = "\n";
  const DEFAULT_JOIN_DELIMITER  = ' ';
///   </constants>

///   <protocol name="building">

///   <method name="Tokenizer" returns="Text.Tokenizer" scope="class">
///     <brief>Фабричный метод, возвращает объект классаText.Tokenizer </brief>
///     <body>
  static public function Tokenizer($source, $delimiter = Text::DEFAULT_TOKEN_DELIMITER) {
    return new Text_Tokenizer($source, $delimiter);
  }
///     </body>
///   </method>

///   <method name="Builder" returns="Text.Builder" scope="class">
///     <brief>Фабричный метод, возвращает объект класса Text.Builder</brief>
///     <body>
  static public function Builder() { return new Text_Builder(); }
///     </body>
///   </method>

///   </protocol>
}
/// </class>


/// <class name="Text.Tokenizer">
///   <brief>Класс предоставляет итератор по тексту, разбитому по разделителю</brief>
///   <implements interface="Iterator" />
class Text_Tokenizer implements Iterator {

  protected $delimiter;
  protected $source;
  protected $current;
  protected $length;
  protected $offset  = 0;
  protected $index   = 0;

///   <protocol name="creating">

///   <method name="__construct">
///     <brief>Конструктор</brief>
///     <args>
///       <arg name="source"    type="string" brief="источник-текст" />
///       <arg name="delimiter" type="string" default="Text::DEFAULT_TOKEN_DELIMITER" brief="разделитель" />
///     </args>
///     <body>
  public function __construct($source, $delimiter = Text::DEFAULT_TOKEN_DELIMITER) {
    $this->source    = $source;
    $this->length    = strlen($source);
    $this->delimiter = $delimiter;
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="iterating" interface="Iterator">

///   <method name="rewind">
///     <brief>Сбрасывает итератор на начало</brief>
///     <body>
  public function rewind() {
    $this->offset = 0;
    $this->current = null;
    $this->index = 0;
    $this->skip();
  }
///     </body>
///   </method>

///   <method name="current" returns="string">
///     <brief>Возвращает текущий элемент итератора</brief>
///     <body>
  public function current() { return $this->current; }
///     </body>
///   </method>

///   <method name="key" returns="int">
///     <brief>Возвращает ключ текущего элемента итератора</brief>
///     <body>
  public function key() { return $this->index; }
///     </body>
///   </method>

///   <method name="next">
///     <brief>Возвращает следующий элемент итератора</brief>
///     <body>
  public function next() { $this->skip()->index++; }
///     </body>
///   </method>

///   <method name="valid" returns="boolean">
///     <brief>Проверяет валидность текущего элемента итератора</brief>
///     <body>
  public function valid() { return $this->current !== null; }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="supporting">

///   <method name="skip" access="protected" returns="Text.StringTokenizer">
///     <brief>Устанавливает текущий элемент итератора</brief>
///     <body>
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
///     </body>
///   </method>

///   </protocol>
}
/// </class>


/// <class name="Text.Builder">
//TODO: надо доку писать
///   <implements interface="Core.StringifyInterface" />
class Text_Builder implements Core_StringifyInterface {

  const TEXT       = 0;
  const END        = 1;
  const DELIMITER  = 2;
  const MARGIN     = 3;
  const STARTED    = 4;

  protected $contexts = array();
  protected $current  = -1;

///   <protocol name="creating">

///   <method name="__construct">
///     <body>
  public function __construct() { $this->context('', '', 0, ''); }
///     </body>
///   </method>

///   </protocol>



///   <protocol name="performing">

///   <method name="inside" returns="Text.Builder">
///     <args>
///       <arg name="begin"  type="string" />
///       <arg name="end"    type="string" />
///       <arg name="margin" type="string" />
///     </args>
///     <body>
  public function inside($begin, $end, $margin) { return $this->context($begin, $end, $margin); }
///     </body>
///   </method>

///   <method name="begin" returns="Text.Builder">
///     <args>
///       <arg name="margin" type="int" default="0" />
///     </args>
///     <body>
  public function begin($margin = 0) { return $this->context('', '', $margin); }
///     </body>
///   </method>

///   <method name="with_delimiter" returns="Text.Builder">
///     <args>
///       <arg name="delimiter" type="string" />
///     </args>
///     <body>
  public function with_delimiter($delimiter) {
    $this->contexts[$this->current][self::DELIMITER] = (string) $delimiter;
    return $this;
  }
///     </body>
///   </method>

///   <method name="begin_with_delimiter" returns="Text.Builder">
///     <args>
///       <arg name="delimiter" type="string" />
///       <arg name="margin"    type="int" default="0" />
///     </args>
///     <body>
  public function begin_with_delimiter($delimiter, $margin = 0) {
    return $this->begin($margin)->with_delimiter($delimiter);
  }
///     </body>
///   </method>

///   <method name="with_margin" returns="Text.Builder">
///     <args>
///       <arg name="margin" type="int" />
///     </args>
///     <body>
  public function with_margin($margin) {
    $this->contexts[$current][self::MARGIN] = (int) $margin;
    return $this;
  }
///     </body>
///   </method>

///   <method name="end" returns="Text.Builder">
///     <body>
  public function end() {
    if ($this->current > 0) {
      $this->contexts[$this->current - 1][self::TEXT] .=
        $this->contexts[$this->current][self::TEXT].$this->contexts[$this->current][self::END];

      unset($this->contexts[$this->current]);
      $this->current--;
    }
    return $this;
  }
///     </body>
///   </method>

///   <method name="t" returns="Text.Builder">
///     <args>
///       <arg name="text" type="string" />
///     </args>
///     <body>
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
///     </body>
///   </method>

///   <method name="text" returns="Text.Builder">
///     <args>
///       <arg name="text" type="string" />
///     </args>
///     <body>
  public function text($text) { return $this->t($text); }
///     </body>
///   </method>

///   <method name="l" returns="Text.Builder">
///     <args>
///       <arg name="text" type="string" />
///     </args>
///     <body>
  public function l($text) { return $this->t($text)->nl(); }
///     </body>
///   </method>

///   <method name="line" returns="Text.Builder">
///     <args>
///       <arg name="text" type="string" />
///     </args>
///     <body>
  public function line($text) { return $this->l($text); }
///     </body>
///   </method>

///   <method name="nl" returns="Text.Builder">
///     <body>
  public function nl() {
    $this->contexts[$this->current][self::TEXT] .= "\n";
    return $this;
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="stringifying" interface="Core.StringifyInterface">

///   <method name="as_string" returns="string">
///     <body>
  public function as_string() { return $this->contexts[0][0]; }
///     </body>
///   </method>

///   <method name="__toString" returns="string">
///     <body>
  public function __toString() { return $this->as_string(); }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="supporting">

///   <method name="context" returns="Text.Builder">
///     <args>
///       <arg name="begin"     type="string" default="''" />
///       <arg name="end"       type="string" default="''" />
///       <arg name="margin"    type="int"    default="0"  />
///       <arg name="delimiter" type="string" default="''" />
///     </args>
///     <body>
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
///     </body>
///   </method>

///   <method name="__get" returns="Text.Builder">
///     <args>
///       <arg name="token" type="string" />
///     </args>
///     <body>
  public function __get($token) {
    switch ($token) {
      case 'end':
      case 'nl':
        return $this->$token();
      default:
        return $this;
    }
  }
///     </body>
///   </method>

///   </protocol>
}
/// </class>

/// </module>
