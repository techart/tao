<?php
/// <module name="Mail.Message" maintainer="timokhin@techart.ru" version="0.2.5">
///   <brief>Объектное представление почтового сообщения</brief>
///   <details>
///   <p>Модуль определяет классы, соответствующие таким элементам почтового сообщения, как
///   поле заголовка, заголовок, часть сообщения и само сообщение.</p>
///   </details>
Core::load('Object', 'IO.FS', 'Mail');

/// <class name="Mail.Message">
///   <implements interface="Core.ModuleInterface" />
///   <depends supplier="Mail.Message.Field" stereotype="creates" />
///   <depends supplier="Mail.Message.Head"  stereotype="creates" />
///   <depends supplier="Mail.Message.Part"  stereotype="creates" />
///   <depends supplier="Mail.Message.Message" stereotype="creates" />
class Mail_Message implements Core_ModuleInterface {

///   <constants>
  const VERSION = '0.2.5';
///   </constants>

///   <protocol name="building">

///   <method name="Field" returns="Mail.Message.Field" scope="class" stereotype="factory">
///     <brief>фабричный метод, возвращает объект класса Mail.Message.Field</brief>
///     <args>
///       <arg name="name" type="string" brief="имя поля" />
///       <arg name="body" type="string" brief="содержимое поля" />
///     </args>
///     <body>
  static public function Field($name, $body) {
    return new Mail_Message_Field($name, $body);
   }
///     </body>
///   </method>

///   <method name="Head" returns="Mail.Message.Head" scope="class" stereotype="factory">
///     <brief>фабричный метод, возвращает объект класса Mail.Message.Head</brief>
///     <body>
  static public function Head() { return new Mail_Message_Head(); }
///     </body>
///   </method>

///   <method name="Part" returns="Mail.Message.Part" scope="class" stereotype="factory">
///     <brief>фабричный метод, возвращает объект класса Mail.Message.Part</brief>
///     <body>
  static public function Part() { return new Mail_Message_Part(); }
///     </body>
///   </method>

///   <method name="Message" returns="Mail.Message.Message" scope="class" stereotype="factory">
///     <brief>фабричный метод, возвращает объект класса Mail.Message.Message</brief>
///     <args>
///       <arg name="nested" type="boolean" default="false" />
///     </args>
///     <body>
  static public function Message($nested = false) { return new Mail_Message_Message($nested); }
///     </body>
///   </method>

///   </protocol>
}
/// </class>


/// <class name="Mail.Message.Exception" extends="Mail.Exception">
///     <brief>Класс исключения</brief>
class Mail_Message_Exception extends Mail_Exception {}
/// </class>


/// <class name="Mail.Message.Field" extends="Object.Struct">
///   <brief>Поле заголовка почтового сообщения</brief>
///   <details>
///     <p>Поле сообщения включает в себя имя поля, значение поля и набор дополнительных
///     атрибутов.</p>
///   </details>
///   <implements interface="Core.IndexedAccessInterface" />
///   <implements interface="Core.StringifyInterface" />
///   <implements interface="Core.EqualityInterface" />
class Mail_Message_Field
  extends    Object_Struct
  implements Core_IndexedAccessInterface,
             Core_StringifyInterface,
             Core_EqualityInterface {

  static protected $acronyms = array(
    'mime' => 'MIME', 'ldap' => 'LDAP', 'soap' => 'SOAP', 'swe'  => 'SWE',
    'bcc'  => 'BCC',  'cc'   => 'CC',   'id' => 'ID');

  const EMAIL_REGEXP = '#(?:[a-zA-Z0-9_\.\-\+])+\@(?:(?:[a-zA-Z0-9\-])+\.)+(?:[a-zA-Z0-9]{2,4})#';
  const EMAIL_NAME_REGEXP = "{(?:(.+)\s?)?(<?(?:[a-zA-Z0-9_\.\-\+])+\@(?:(?:[a-zA-Z0-9\-])+\.)+(?:[a-zA-Z0-9]{2,4})>?)$}Ui";
  const ATTR_REGEXP = '{;\s*\b([a-zA-Z0-9_\.\-]+)\s*\=\s*(?:(?:"([^"]*)")|(?:\'([^\']*)\')|([^;\s]*))}i';

  protected $name;
  protected $value;
  protected $attrs = array();

///   <protocol name="creating">

///   <method name="__construct">
///     <brief>Конструктор</brief>
///     <args>
///       <arg name="name" type="string" brief="имя поля" />
///       <arg name="body" type="string" brief="содержимое поля" />
///       <arg name="attrs" type="array" brief="аттрибуты поля" />
///     </args>
///     <body>
  public function __construct($name, $body, $attrs = array()) {
    $this->name  = $this->canonicalize($name);
    $this->set_body($body);
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="quering">

///   <method name="matches" returns="boolean">
///     <brief>Проверяет соответствие имени поля указанному имени</brief>
///     <args>
///       <arg name="name" type="string" brief="имя" />
///     </args>
///     <body>
  public function matches($name) {
    return Core_Strings::downcase($this->name) ==
           Core_Strings::downcase(Core_Strings::trim($name));
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="indexing">

///   <method name="offsetGet" returns="string">
///     <brief>Возвращает значение атрибута поля</brief>
///     <args>
///       <arg name="index" type="string" brief="имя атрибута" />
///     </args>
///     <body>
  public function offsetGet($index) {
    return isset($this->attrs[$index]) ? $this->attrs[$index] : null;
  }
///     </body>
///   </method>

///   <method name="offsetSet" returns="string">
///     <brief>Устанавливает значение атрибута поля</brief>
///     <args>
///       <arg name="index" type="string" brief="имя атрибута" />
///       <arg name="value" type="string" brief="значение атрибута" />
///     </args>
///     <body>
  public function offsetSet($index, $value) {
    $this->attrs[(string) $index] = $value;
    return $this;
  }
///     </body>
///   </method>

///   <method name="offsetExists" returns="boolean">
///     <brief>Проверяет, установлен ли атрибут поля</brief>
///     <args>
///       <arg name="index" type="string" brief="имя атрибута" />
///     </args>
///     <body>
  public function offsetExists($index) {
    return isset($this->attrs[$index]);
  }
///     </body>
///   </method>

///   <method name="offsetUnset">
///     <brief>Удаляет атрибут</brief>
///     <args>
///       <arg name="index" type="string" brief="имя атрибута поля" />
///     </args>
///     <body>
  public function offsetUnset($index) {
    unset($this->attrs[$index]);
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="stringifying" interface="Core.StringifyInterface">

///     <method name="as_string" returns="string">
///     <brief>Возвращает поле ввиде закодированной строки</brief>
///       <body>
  public function as_string() { return $this->encode(); }
///       </body>
///     </method>

///   <method name="__toString" returns="string">
///     <brief>Возвращает поле ввиде строки</brief>
///     <body>
  public function __toString() { return $this->as_string(); }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="encoding">

///   <method name="encode" returns="string">
///     <brief>Кодирует поле</brief>
///     <body>
//TODO: iconv_mime_encode не верно кодирует длинные строки
  public function encode() {
    $body = $this->name.': '.$this->encode_value($this->value, false).';';
    foreach ($this->attrs as $index => $value) {
      $attr = $this->encode_attr($index, $value);
      $delim = (($this->line_length($body) + strlen($attr) + 1) >= MIME::LINE_LENGTH) ?
          "\n " : ' ';
      $body .= $delim.$attr;
    }
    return substr($body, 0, strlen($body) - 1);
  }
///     </body>
///   </method>

///   <method name="encode_attr" access="protected" returns="string" >
///     <brief>Кодирует аттрибут поля</brief>
///     <args>
///       <arg name="index" type="string" />
///     </args>
///     <body>
  protected function encode_attr($index, $value) {
    $value = $this->encode_value($value);
    switch (true) {
      case $index == 'boundary': case strpos($value, ' '):
        return "$index=\"$value\";";
      default:
        return "$index=$value;";
    }
  }
///     </body>
///   </method>

///   <method name="encode_value" access="protected" returns="string" >
///     <brief>Кодирует значение поля или значение аттрибута</brief>
///     <args>
///       <arg name="value" type="string" />
///       <arg name="quote" type="boolean" default="true" />
///     </args>
///     <body>
  protected function encode_value($value, $quote = true) {
    if ($this->is_address_line($value))
      return $this->encode_email($value);
    else
      return $this->encode_mime($value, $quote);
  }
///     </body>
///   </method>

///   <method name="encode_email" access="protected" returns="string">
///     <brief>Кодирует строку email адресов</brief>
///     <description>
///       Преобразует строку вида "Серж &lt;svistunov@techart.ru&gt;, Max &lt;timokhin@techart.ru&gt;"
///       в строрку вида "=?UTF-8?B?0KHQtdGA0LY=?= &lt;svistunov@techart.ru&gt;,
///         Max &lt;timokhin@techart.ru&gt;"
///     </description>
///     <args>
///       <arg name="value" type="string" brief="строка для кодирования" />
///     </args>
///     <body>
  protected function encode_email($value) {
    $result = array();
    foreach (explode(',', $value) as $k => $v)
      if (preg_match(self::EMAIL_NAME_REGEXP, $v, $m))
        $result[] = ($m[1] ? $this->encode_mime(trim($m[1]), false) : '').' '.$m[2];
      else
        return $this->encode_mime($value, false);
    return implode(','.MIME::LINE_END.' ', $result);
  }
///     </body>
///   </method>

///   <method name="encode_mime">
///     <brief>Обертка над iconv_mime_encode</brief>
///     <args>
///       <arg name="value" type="string" />
///       <arg name="quote" type="boolean" default="false" />
///     </args>
///     <body>
  protected function encode_mime($value, $quote = true) {
    $q = $quote ? '"' : '';
    return !MIME::is_printable($value) ? $q.preg_replace('{^: }', '', iconv_mime_encode(
      '',
      $value,
      array(
        'scheme' => 'B',
        'input-charset' => 'UTF-8',
        'output-charset' => 'UTF-8',
        "line-break-chars" => MIME::LINE_END
      )
    )).$q : $value /*MIME::split($value)*/;
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="supporting">

///   <method name="line_length" access="private" returns="int">
///     <brief>Возвращает последней строки в тексте</brief>
///     <args>
///       <arg name="txt" type="string" />
///     </args>
///     <body>
  private function line_length($txt) {
    return strlen(end(explode("\n", $txt)));
  }
///     </body>
///   </method>

///   <method name="is_address_line" access="protected" returns="boolean">
///     <brief>Проверяет, является ли строка tmail адресом</brief>
///     <args>
///       <arg name="line" type="string" />
///     </args>
///     <body>
  protected function is_address_line($line) {
    return preg_match(self::EMAIL_REGEXP, $line);
  }
///     </body>
///   </method>

///   <method name="set_name" access="protected">
///     <brief>Установка свойства name извне запрещена</brief>
///     <args>
///       <arg name="name" type="string" brief="имя" />
///     </args>
///     <body>
  protected function set_name($name) {
    throw new Core_ReadOnlyPropertyException('name');
  }
///     </body>
///   </method>

///   <method name="set_body" returns="string" access="protected">
///     <brief>Устанавливает содержимое поля</brief>
///     <details>
///       Если в качестве $body передан массив, то первый элемент массива воспринемается как содержимое поля,
///       а остальные элементы массива - как атрибуты поля
///     </details>
///     <args>
///       <arg name="body" type="string|array|mixed" brief="содержимое письма" />
///     </args>
///     <body>
  protected function set_body($body) {
    $this->attrs = array();
    if (is_array($body)) {
      foreach ($body as $k => $v)
        switch (true) {
          case is_string($k):
            $this[$k] = $v;
            break;
          case is_int($k):
            $this->value = (string) $v;
        }
    } else
      $this->from_string((string) $body);
    return $this;
  }
///     </body>
///   </method>

///   <method name="from_string" returns="Mail.Message.Field">
///     <brief>Производит разбор строки, извлекая аттрибуты</brief>
///     <args>
///       <arg name="body" type="string" />
///     </args>
///     <body>
  public function from_string($body) {
    if (preg_match_all(self::ATTR_REGEXP, $body, $m, PREG_SET_ORDER)) {
      foreach ($m as $res) {
        $v = $res[2] ? $res[2] : ($res[3] ? $res[3] : ($res[4] ? $res[4] : null));
        if (isset($res[1]) && $v)
          $this[$res[1]] = $v;
      }
      $this->value = trim(substr($body, 0, strpos($body, ';')));
    } else
    $this->value = $body;
    return $this;
  }
///     </body>
///   </method>

///   <method name="get_body" returns="string" access="protected">
///     <body>
  protected function get_body() {
    return $this->encode();
  }
///     </body>
///   </method>

///   <method name="set_value" returns="string" access="protected">
///     <brief>Устанавливает значение поля</brief>
///     <args>
///       <arg name="value" type="string" />
///     </args>
///     <body>
  protected function set_value($value) {;
    $this->value = (string) $value;
    return $this;
  }
///     </body>
///   </method>

///   <method name="canonicalize" returns="string">
///     <brief>Приводит имя к виду соответствующему почтовому стандарту</brief>
///     <args>
///       <arg name="name" type="string" />
///     </args>
///     <body>
  protected function canonicalize($name) {
    $parts = Core_Arrays::map(
      'return strtolower($x);',
      Core_Strings::split_by('-', trim($name)));

    foreach ($parts as &$part)
      $part = isset(self::$acronyms[$part]) ?
        self::$acronyms[$part] :
        (preg_match('{[aeiouyAEIOUY]}', $part) ? ucfirst($part) : strtoupper($part));

    return Core_Arrays::join_with('-', $parts);
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="quering">
///   <method name="equals" returns="boolean">
///     <args>
///       <arg name="to" />
///     </args>
///     <body>
  public function equals($to) {
    return $to instanceof self &&
      $this->value == $to->value &&
      $this->name == $to->name &&
      Core::equals($this->attrs, $to->attrs);
  }
///     </body>
///   </method>
///   </protocol>
}
/// </class>

/// <class name="Mail.Message.Head">
///     <brief>Заголовок сообщения</brief>
///   <implements interface="Core.IndexedAccessInterface" />
///   <implements interface="IteratorAggregate" />
///   <implements interface="Core.EqualityInterface" />
class Mail_Message_Head
  implements Core_IndexedAccessInterface,
             IteratorAggregate,
             Core_EqualityInterface  {

  protected $fields;

///   <protocol name="creating">

///   <method name="from_string" returns="Mail.Message.Head" scope="class">
///     <brief>Парсит и декодирует поля заголовка из строки</brief>
///     <args>
///       <arg name="data" type="string" brief="строка с полями заголовка" />
///     </args>
///     <body>
  static public function from_string($data) {
    $head = new Mail_Message_Head();
    foreach (MIME::decode_headers($data) as $k => $f)
      foreach ((array) $f as $v)
        $head->field($k, $v);
    return $head;
  }
///     </body>
///   </method>

///   <method name="__construct">
///     <brief>Конструктор</brief>
///     <body>
  public function __construct() {
    $this->fields = new ArrayObject();
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="iterating" interface="IteratorAggregate">

///   <method name="getIterator" returns="Iterator">
///     <brief>Возвращает итератор по полям заголовка</brief>
///     <body>
  public function getIterator() { return $this->fields->getIterator(); }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="building">

///   <method name="field" returns="Mail.Message.Head">
///     <brief>Добавляет к заголовку новое поле</brief>
///     <args>
///       <arg name="name" type="string" brief="имя поля" />
///       <arg name="value" type="string" brief="значение поля" />
///     </args>
///     <body>
  public function field($name, $value) {
    $this->fields[] = new Mail_Message_Field($name, $value);
    return $this;
  }
///     </body>

///   <method name="fields" returns="Mail.Message.Head">
///     <brief>Добавляет к заголовку поля из массива $values</brief>
///     <args>
///       <arg name="values" brief="массив полей" />
///     </args>
///     <body>
  public function fields($values) {
    foreach ($values as $name => $value) $this->field($name, $value);
    return $this;
  }
///     </body>
///   </method>

///   </method>

///   </protocol>

///   <protocol name="indexing">

///   <method name="offsetGet" returns="string">
///     <brief>Возвращает поле заголовка</brief>
///     <args>
///       <arg name="index" type="string" brief="имя заголовка" />
///     </args>
///     <body>
  public function offsetGet($index) {
    return is_int($index) ? $this->fields[$index] : $this->get($index);
  }
///     </body>
///   </method>

///   <method name="offsetSet" returns="string">
///     <brief>Устанавливает или добаляет поле к заголовку</brief>
///     <args>
///       <arg name="index" type="string" />
///       <arg name="value" type="string" />
///     </args>
///     <body>
  public function offsetSet($index, $value) {
    if (is_int($index))
      throw isset($this[$index]) ?
        new Core_ReadOnlyIndexedPropertyException($index) :
        new Core_MissingIndexedPropertyException($index);
    else
     if ($this->offsetExists($index)) {
       $this[$index]->body = $value;
     } else
       $this->field($index, $value);
     return $this;
  }
///     </body>
///   </method>

///   <method name="offsetExists" returns="boolean">
///     <brief>Проверяет установелнно ли поле с именем $index</brief>
///     <args>
///       <arg name="index" type="string" brief="имя поля" />
///     </args>
///     <body>
  public function offsetExists($index) {
    return is_int($index) ?
      isset($this->fields[$index]) :
      ($this->get($index) ? true : false);
  }
///     </body>
///   </method>

///   <method name="offsetUnset">
///     <brief>Выбрасывает исключение Core.NotImplementedException</brief>
///     <args>
///       <arg name="index" type="string" brief="имя поля" />
///     </args>
///     <body>
  public function offsetUnset($index) { throw new Core_NotImplementedException(); }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="quering">

///   <method name="get" returns="Mail.Message.Field">
///     <brief>Проверяет установелнно ли поле с именем $name</brief>
///     <args>
///       <arg name="name" type="string" brief="имя поля" />
///     </args>
///     <body>
  public function get($name) {
    foreach ($this->fields as $field)
      if ($field->matches((string) $name)) return $field;
    return null;
  }
///     </body>
///   </method>

///   <method name="get_all" returns="ArrayObject">
///     <brief>Возвращает ArrayObject всех полей заголовка с именем $name</brief>
///     <args>
///       <arg name="name" type="string" brief="имя поля" />
///     </args>
///     <body>
  public function get_all($name) {
    $result = new ArrayObject();
    foreach ($this->fields as $field)
      if ($field->matches((string) $name)) $result[] = $field;
    return $result;
  }
///     </body>
///   </method>

///   <method name="count_for" returns="int">
///     <brief>Возвращает количество полей с именем $name</brief>
///     <args>
///       <arg name="name" type="string" brief="name" />
///     </args>
///     <body>
  public function count_for($name) {
    $count = 0;
    foreach ($this->fields as $field)
      $count += $field->matches((string) $name) ? 1 : 0;
    return $count;
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="encoding">

///   <method name="encode" returns="string">
///     <brief>Кодирует заголовок</brief>
///     <body>
  public function encode() {
    $encoded = '';
    foreach ($this->fields as $field) $encoded .= $field->encode().MIME::LINE_END;
    return $encoded;
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="quering">
///   <method name="equals" returns="boolean">
///     <args>
///       <arg name="to" />
///     </args>
///     <body>
  public function equals($to) {
    $r =  $to instanceof self;
    $ar1 = $this->getIterator()->getArrayCopy();
    $ar2 = $to->getIterator()->getArrayCopy();
    $r = $r && (count($ar1) == count($ar2));
    foreach ($ar1 as  $v) {
      $r = $r && (Core::equals($v, $to->get($v->name)));
    }
    return $r;
  }
///     </body>
///   </method>
///</protocol>
}
/// </class>
/// <composition>
///   <source class="Mail.Message.Head" role="head" multiplicity="1" />
///   <target class="Mail.Message.Field" role="field" multiplicity="N" />
/// </composition>

/// <class name="Mail.Message.Part">
///     <brief>Часть почтового сообщения</brief>
///   <implements interface="Core.PropertyAccessInterface" />
///   <implements interface="Core.CallInterface" />
///   <implements interface="Core.EqualityInterface" />
///   <depends supplier="IO.FS.File" stereotype="uses" />
///   <depends supplier="IO.Stream.AbstractStream" stereotype="uses" />
class Mail_Message_Part
  implements Core_PropertyAccessInterface,
             Core_StringifyInterface,
             Core_CallInterface,
             Core_EqualityInterface {

  protected $head;
  protected $body;

///   <protocol name="creating">

///   <method name="__construct">
///     <brief>Конструктор</brief>
///     <body>
  public function __construct() {
    $this->head = Mail_Message::Head();
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="building">

///   <method name="head" returns="Mail.Message.Part">
///     <brief>Устанавливает заголовок сообщения</brief>
///     <args>
///       <arg name="head" type="Mail.Message.Head" brief="заголовок" />
///     </args>
///     <body>
  public function head(Mail_Message_Head $head) {
    $this->head = $head;
    return $this;
  }
///     </body>
///   </method>

///   <method name="field" returns="Mail.Message.Part">
///     <brief>Добавляет новое поля к заголовку письма</brief>
///     <args>
///       <arg name="name" type="string" brierf="имя поля" />
///       <arg name="value" type="string" brief="содержимое поля" />
///     </args>
///     <body>
  public function field($name, $value) {
    $this->head[$name] = $value;
    return $this;
  }
///     </body>
///   </method>

///   <method name="headers" returns="Mail.Message.Part">
///     <brief>Добавляет несколько полей заголовка из массива $headers</brief>
///     <args>
///       <arg name="headers" type="array" brief="массив заголовков письма" />
///     </args>
///     <body>
  public function headers(array $headers) {
    foreach ($headers as $k => $v) $this->head[$k] = $v;
    return $this;
  }
///     </body>
///   </method>

///   <method name="file" returns="Mail.Message.Part">
///     <brief>Заполняет письмо из файла, т.е. получаетя attach к письму</brief>
///     <args>
///       <arg name="file" brief="путь к файлу или IO.FS.File" />
///       <arg name="name" type="string"  brief="имя файла, заменяет оригинальное" />
///     </args>
///     <body>
  public function file($file, $name = '') {
    if (!($file instanceof IO_FS_File))
      $file = IO_FS::File((string) $file);

    $this->head['Content-Type'] = array($file->content_type, 'name' => ($name ? $name : $file->name));
    $this->head['Content-Transfer-Encoding'] = $file->mime_type->encoding;
    $this->head['Content-Disposition'] = 'attachment';

    $this->body = $file;

    return $this;
  }
///     </body>
///   </method>

///   <method name="body" returns="Mail.Message.Part">
///     <brief>Устанавливает содержимае письма</brief>
///     <args>
///       <arg name="body" brief="содержимое письма" />
///     </args>
///     <body>
  public function body($body) {
    $this->body = $body;
    return $this;
  }
///     </body>
///   </method>

///   <method name="stream" returns="Mail.Message.Part">
///     <brief>Заполняет письмо из потока</brief>
///     <args>
///       <arg name="stream" type="IO.Stream.AbstractStream" brief="поток" />
///       <arg name="content_type" default="null" />
///     </args>
///     <body>
  public function stream(IO_Stream_AbstractStream $stream, $content_type = null) {
    if ($content_type) {
      $this->head['Content-Type'] = $content_type;
      $this->head['Content-Transfer-Encoding'] = MIME::type($this->head['Content-Type']->value)->encoding;
    }
    $this->body = $stream;
    return $this;
  }
///     </body>
///   </method>

///   <method name="text" returns="Mail.Message.Part">
///     <brief>Заполняет письмо ввиде простого текста</brief>
///     <args>
///       <arg name="text" type="string" brief="содержимое письма" />
///       <arg name="content_type" default="null" />
///     </args>
///     <body>
  public function text($text, $content_type = null) {
    $this->head['Content-Type'] = $content_type ?
      $content_type :
      array('text/plain', 'charset' => MIME::default_charset());

    $this->head['Content-Transfer-Encoding'] =
      MIME::type($this->head['Content-Type']->value)->encoding;

    $this->body = (string) $text;

    return $this;
  }
///     </body>
///   </method>

///   <method name="html" returns="Mail.Message.Part">
///     <brief>Заполняет письмо ввиде html</brief>
///     <args>
///       <arg name="text" type="string" brief="содержимое письма ввиде html" />
///     </args>
///     <body>
  public function html($text) {
    return $this->text(
      $text,
      array('text/html', 'charset' => MIME::default_charset()));
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="accessing">

///   <method name="__get" returns="mixed">
///     <brief>Доступ на чтение к совйствам объекта</brief>
///     <details>
///       <dl>
///         <dt>head</dt><dd>Заголовок письма</dd>
///         <dt>body</dt><dd>Содержимое письма</dd>
///       </dl>
///     </details>
///     <args>
///       <arg name="property" type="string" brief="имя свойтсва" />
///     </args>
///     <body>
  public function __get($property) {
    switch ($property) {
      case 'head':
      case 'body':
        return $this->$property;
      default:
        throw new Core_MissingPropertyException($property);
    }
  }
///     </body>
///   </method>

///   <method name="__set" returns="mixed">
///     <brief>Доступ на запись к свойствам объекта</brief>
///     <details>
///       Установить можно только содержимое письма body
///     </details>
///     <args>
///       <arg name="property" type="string" brief="имя свойства" />
///       <arg name="value" brief="значение свойтсва" />
///     </args>
///     <body>
  public function __set($property, $value) {
      switch ($property) {
        case 'body':
          $this->body($value);
          return $this;
        case 'head':
          throw new Core_ReadOnlyPropertyException($property);
        default:
          throw new Core_MissingPropertyException($property);
      }
  }
///     </body>
///   </method>

///   <method name="__isset" returns="boolean">
///     <brief>Проверяет установлено ли свойство</brief>
///     <args>
///       <arg name="property" type="string" brief="имя свойства" />
///     </args>
///     <body>
  public function __isset($property) {
    switch ($property) {
      case 'body':
      case 'head':
        return true;
      default:
        return false;
    }
  }
///     </body>
///   </method>

///   <method name="__unset">
///     <brief>Выкидывает исключение Core.NotImplementedException</brief>
///     <args>
///       <arg name="property" type="string" />
///     </args>
///     <body>
  public function __unset($property) { throw new Core_NotImplementedException(); }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="stringify" interface="Core.StringifyInterface">

///   <method name="as_string" returns="string">
///     <brief>Возвращает закодированное письмо ввиде строки</brief>
///     <body>
  public function as_string() { return Mail_Serialize::Encoder()->encode($this); }
///     </body>
///   </method>

///   <method name="__toString" returns="string">
///     <brief>Возвращает закодированное письмо ввиде строки</brief>
///     <body>
  public function __toString() { return $this->as_string(); }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="calling" interface="Core.CallProtocol">

///   <method name="__call" returns="Mail.Message.Part">
///     <brief>С помощью вызова метода можно установить/добавить поле к заголовку письма</brief>
///     <args>
///       <arg name="method" type="string" />
///       <arg name="args" />
///     </args>
///     <body>
  public function __call($method, $args) {
    $this->head[$this->field_name_for_method($method)] = $args[0];
    return $this;
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="supporting">

///   <method name="field_name_for_method" returns="string" access="protected">
///     <args>
///       <arg name="method" type="string" brief="" />
///     </args>
///     <body>
  protected function field_name_for_method($method) {
    return Core_Strings::replace($method, '_', '-');
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="quering">
///   <method name="equals" returns="boolean">
///     <args>
///       <arg name="to" />
///     </args>
///     <body>
  public function equals($to) {
    $r =  $to instanceof self &&
      Core::equals($this->head, $to->head);

    $this_body = ($this->body instanceof IO_Stream_AbstractStream ||
                  $this->body instanceof IO_FS_File) ?
      $this->body->load() :
      $this->body;

    $to_body = ($to->body instanceof IO_Stream_AbstractStream ||
                  $to->body instanceof IO_FS_File) ?
      $to->body->load() :
      $to->body;

    return $r && Core::equals($this_body, $to_body);
  }
///     </body>
///   </method>
///</protocol>
}
/// </class>
/// <aggregation>
///   <source class="Mail.Message.Part" role="part" multiplicity="1" />
///   <target class="Mail.Message.Head" role="head" multiplicity="N" />
/// </aggregation>


/// <class name="Mail.Message.Message" extends="Mail.Message.Part">
///     <brief>Почтовое письмо</brief>
///   <depends supplier="Mail.Message.Exception" stereotype="throws" />
class Mail_Message_Message
  extends Mail_Message_Part
  implements IteratorAggregate {

  protected $preamble = '';
  protected $epilogue = '';

///   <protocol name="creating">

///   <method name="__construct">
///     <brief>конструктор</brief>
///     <args>
///       <arg name="nested" type="boolean" default="false" />
///     </args>
///     <body>
  public function __construct($nested = false) {
    parent::__construct();
    if (!$nested) {
      $this->head['MIME-Version'] = '1.0';
      $this->date(Time::now());
    }
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="building">

///   <method name="multipart" returns="Mail.Message.Message">
///     <brief>Устанавливает заголовок письма в multipart с указанным типом и границей</brief>
///     <args>
///       <arg name="type"     type="string" default="mixed" brief="тип" />
///       <arg name="boundary" type="string" default="null" brief="граница" />
///     </args>
///     <body>
  public function multipart($type = 'mixed', $boundary = null) {
    $this->body = new ArrayObject();

    $this->head['Content-Type'] = array(
     'Multipart/'.ucfirst(strtolower($type)),
     'boundary' => ($boundary ? $boundary : MIME::boundary()));

    return $this;
  }
///     </body>
///   </method>

///   <method name="multipart_mixed" returns="Mail.Message.Message">
///     <brief>Устанавливает заголовок письма в multipart/mixed</brief>
///     <args>
///       <arg name="boundary" type="string" default="null" brief="граница" />
///     </args>
///     <body>
  public function multipart_mixed($boundary = null) { return $this->multipart('mixed', $boundary); }
///     </body>
///   </method>

///   <method name="multipart_alternative" returns="Mail.Message.Message">
///     <brief>Устанавливает заголовок письма в multipart/alternative</brief>
///     <args>
///       <arg name="boundary" type="string" default="null" brief="граница" />
///     </args>
///     <body>
  public function multipart_alternative($boundary = null) { return $this->multipart('alternative', $boundary); }
///     </body>
///   </method>

///   <method name="multipart_related" returns="Mail.Message.Message">
///     <brief>Устанавливает заголовок письма в multipart/related</brief>
///     <args>
///       <arg name="boundary" type="string" default="null" brief="граница" />
///     </args>
///     <body>
  public function multipart_related($boundary = null) { return $this->multipart('related', $boundary); }
///     </body>
///   </method>

///   <method name="date" returns="Mail.Message.Message">
///     <brief>Устанавливает дату в заголовке</brief>
///     <args>
///       <arg name="date" brief="дата" />
///     </args>
///     <body>
  public function date($date) {
    $this->head['Date'] = ($date instanceof Time_DateTime) ? $date->as_rfc1123() : (string) $date;
    return $this;
  }
///     </body>
///   </method>

///   <method name="part" returns="Mail.Message.Message">
///     <brief>Добавляет к письму часть</brief>
///     <args>
///       <arg name="part" type="Mail.Message.Part" brief="часть" />
///     </args>
///     <body>
  public function part(Mail_Message_Part $part) {
    if (!$this->body instanceof ArrayObject) $this->body = new ArrayObject();

    if ($this->is_multipart())
      $this->body->append($part);
    else
      throw new Mail_Message_Exception('Not multipart message');
    return $this;
  }
///     </body>
///   </method>

///   <method name="text_part" returns="Mail.Message.Message">
///     <brief>Добавляет к письму текстовую часть</brief>
///     <args>
///       <arg name="text"         type="string" brief="текст письма" />
///       <arg name="content_type" default="null" />
///     </args>
///     <body>
  public function text_part($text, $content_type = null) {
    return $this->part(Mail_Message::Part()->text($text, $content_type));
  }
///     </body>
///   </method>

///   <method name="html_part" returns="Mail.Message.Message">
///     <brief>Добавляте к письму html-часть</brief>
///     <args>
///       <arg name="text" type="string" brief="html письма" />
///     </args>
///     <body>
  public function html_part($text) {
    return $this->part(Mail_Message::Part()->html($text));
  }
///     </body>
///   </method>

///   <method name="file_part" returns="Mail.Message.Message">
///     <brief>Добавляет к письму attach фаил</brief>
///     <args>
///       <arg name="file" brief="путь к файли или IO.FS.File" />
///       <arg name="name" type="string" default="''" brief="имя файла, заменяет оригинальное" />
///     </args>
///     <body>
  public function file_part($file, $name = '') {
    return $this->part(Mail_Message::Part()->file($file, $name));
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="changing">

///   <method name="preamble" returns="Mail.Message.Message">
///     <brief>Добавляет к письму преабулу</brief>
///     <args>
///       <arg name="text" type="string" brief="текст преамбулы" />
///     </args>
///     <body>
  public function preamble($text) {
    $this->preamble = (string) $text;
    return $this;
  }
///     </body>
///   </method>

///   <method name="epilogue" returns="Mail.Message.Message">
///     <brief>Добавляет к письму эпилог</brief>
///     <args>
///       <arg name="text" type="string" brief="текст эпилога" />
///     </args>
///     <body>
  public function epilogue($text) {
    $this->epilogue = (string) $text;
    return $this;
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="iterating" interface="IteratorAggregate">

///   <method name="getIterator" returns="Iterator">
///     <brief>Возвращает итератор по вложенным частям письма</brief>
///     <body>
  public function getIterator() {
    return $this->is_multipart() ?
      $this->body->getIterator() :
      new ArrayIterator($this->body);
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="quering">

///   <method name="is_multipart" returns="boolean">
///     <brief>Проверяет имеет ли письмо вложения</brief>
///     <body>
  public function is_multipart() {
    return Core_Strings::starts_with(
      Core_Strings::downcase($this->head['Content-Type']->value), 'multipart'); }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="accessing" interface="Core.PropertyAccessInterface">

///   <method name="__get" returns="mixed">
///     <brief>Доступ на чтение к свойствам объекта</brief>
///     <details>
///       <dl>
///         <dt>preamble</dt><dd>Преамбула письма</dd>
///         <dt>epilogue</dt><dd>Эпилог письма</dd>
///       </dl>
///     </details>
///     <args>
///       <arg name="property" type="string" brief="имя свойства" />
///     </args>
///     <body>
  public function __get($property) {
    switch ($property) {
      case 'preamble':
      case 'epilogue':
        return $this->$property;
      default:
        return parent::__get($property);
    }
  }
///     </body>
///   </method>

///   <method name="__set" returns="mixed">
///     <brief>Доступ на запись к свойствам объекта</brief>
///     <details>
///       <dl>
///         <dt>preamble</dt><dd>Преамбула письма</dd>
///         <dt>epilogue</dt><dd>Эпилог письма</dd>
///       </dl>
///     </details>
///     <args>
///       <arg name="property" type="string" brief="имя свойства" />
///       <arg name="value" brief="значение" />
///     </args>
///     <body>
  public function __set($property, $value) {
    switch ($property) {
      case 'preamble':
      case 'epilogue':
        $this->$property = (string) $value;
        break;
      default:
        return parent::__set($property, $value);
    }
    return $this;
  }
///     </body>
///   </method>

///   <method name="__isset" returns="boolean">
///     <brief>Проверяет установленно ли свойство объекта</brief>
///     <args>
///       <arg name="property" type="string" brief="имя свойтсва" />
///     </args>
///     <body>
  public function __isset($property) {
    switch ($property) {
      case 'preamble':
      case 'epilogue':
        return true;
      default:
        return parent::__isset($property);
    }
  }
///     </body>
///   </method>

///   <method name="__unset">
///     <brief>Выбрасывает исключение Core.UndestroyablePropertyException</brief>
///     <args>
///       <arg name="property" type="string" />
///     </args>
///     <body>
  public function __unset($property) {
    switch ($property) {
      case 'preamble':
      case 'epilogue':
        throw new Core_UndestroyablePropertyException($property);
      default:
        parent::__unset($property);
    }
  }
///     </body>
///   </method>

///   </protocol>
}
/// </class>
/// <aggregation>
///   <source class="Mail.Message.Message" role="message" multiplicity="1" />
///   <target class="Mail.Message.Part"    role="part"    multiplicity="0..N" />
/// </aggregation>

/// </module>
