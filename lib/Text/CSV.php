<?php
/// <module name="Text.CSV" maintainer="svistunov@techart.ru" version="0.1.0">
///   <brief>Модуль предоставляет классы для работы с CVS</brief>

/// <class name="Text.CSV" stereotype="module">
///   <implements interface="Core.ModuleInterface" />
class Text_CSV implements Core_ModuleInterface {
///   <constants>
  const VERSION = '0.1.0';
///   </constants>

///   <protocol name="building">

///   <method name="Reader" returns="Text.CVS.Reader">
///     <brief>Фабричный метод, возвращает объект класса Text.CVS.Reader </brief>
///     <args>
///       <arg name="path" type="string" default="null" brief="путь к файлу" />
///     </args>
///     <body>
  public static function Reader($path = null) {
    return new Text_CSV_Reader($path);
  }
///     </body>
///   </method>

///   </protocol>
}
/// </class>

/// <class name="Text.CSV.Reader">
///   <brief>Итератор для чтения csv файлов</brief>
///   <implements interface="Iterator" />
///   <implements interface="Core.PropertyAccessInterface" />
class Text_CSV_Reader implements Iterator, Core_PropertyAccessInterface {

  protected $file = null;
  
  protected $current;
  protected $row_count = -1;
  
  protected $delimeter = ',';
  protected $enclosure = '"';
  
///   <protocol name="creating">

///   <method name="__construct">
///     <brief>Конструктор</brief>
///     <args>
///       <arg name="path" type="string" default="null" brief="путь к файлу" />
///     </args>
///     <body>
  public function __construct($path = null) {
    if ($path !== null)
      $this->file = fopen($path, 'r');
  }
///     </body>
///   </method>
  
///   <method name="from_file">
///     <brief>Устанавливает csv файл</brief>
///     <args>
///       <arg name="path" type="string" brief="путь к файлу" />
///     </args>
///     <body>
  public function from_file($path) {
    $this->file = fopen($path, 'r');
  }
///     </body>
///   </method>

///   <method name="from_stream">
///     <brief>Считывает csv из потока</brief>
///     <args>
///       <arg name="file" brief="дескриптор файла/потока"/>
///     </args>
///     <body>
  public function from_stream($file) {
    $this->file = $file;
  }
///     </body>
///   </method>

///   <method name="__destruct">
///     <brief>Деструктор</brief>
///     <body>
  public function __destruct() {
    if (is_resource($this->file)) fclose($this->file);
  }
///     </body>
///   </method>
///   </protocol>

///   <protocol name="iterating">

///   <method name="rewind" returns="mixed">
///     <brief>Сбрасывает итератор в начало</brief>
///     <body>
  public function rewind() {
    rewind($this->file);
    $this->next();
  }
///     </body>
///   </method>

///   <method name="current" returns="mixed">
///     <brief>Возвращает текущий элемент итератора</brief>
///     <body> 
  public function current() {
    return $this->current;
  }
///     </body>
///   </method>

///   <method name="key" returns="mixed">
///     <brief>Возвращает ключ текущего элемента</brief>
///     <body> 
  public function key() {
    return $this->row_count;
  }
///     </body>
///   </method>

///   <method name="next">
///     <brief>Возвращает следующий элемент</brief>
///     <body>
  public function next() {
    $this->current = fgetcsv($this->file, 0, $this->delimeter, $this->enclosure);
    if($this->current !== false)
      $this->row_count++;
    else fclose($this->file);
    return $this->current;
  }
///     </body>
///   </method>

///   <method name="valid" returns="boolean">
///     <brief>Проверяет валидность текущего элемента</brief>
///     <body> 
  public function valid() {
    return $this->current !== false;
  }
///     </body>
///   </method>

/// </protocol>

///   <protocol name="accessing">

///   <method name="__get" returns="mixed">
///     <brief>Доступ на чтение к свойствам объекта</brief>
///     <details>
///       <dl>
///         <dt>delimeter</dt><dd>разделитель, по умолчанию ','</dd>
///         <dt>enclosure</dt><dd>ограничитель, по умолчанию '"'</dd>
///       </dl>
///     </details>
///     <args>
///       <arg name="property" type="string" brief="имя свойства" />
///     </args>
///     <body>
  public function __get($property) {
    switch ($property) {
      case 'delimeter': case 'enclosure':
        return $this->$property;
      default:
        throw new Core_MissingPropertyException($property);
    }
  }
///     </body>
///   </method>

///   <method name="__set" returns="mixed">
///     <brief>Доступ на запись к свойствам объекта</brief>
///     <args>
///       <arg name="property" type="string" brief="имя свойства" />
///       <arg name="value" brief="значение" />
///     </args>
///     <body>
  public function __set($property, $value) {
    switch ($property) {
      case 'delimeter': case 'enclosure':
      	{$this->$property  = (string) $value; return $this;}
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
      case 'delimeter': case 'enclosure':
        return isset($this->$property);
      default:
        throw new Core_MissingPropertyException($property);
    }
  }
///     </body>
///   </method>
  
///   <method name="__unset">
///     <brief>Очищает свойство объекта</brief>
///     <details>
///      Выбрасывает исключение
///     </details>
///     <args>
///       <arg name="property" type="string" brief="имя свойства" />
///     </args>
///     <body>
  public function __unset($property) {
    switch ($property) {
      case 'delimeter': case 'enclosure':
        throw new Core_ReadOnlyPropertyException($property);
      default:
        throw new Core_MissingPropertyException($property);
    }
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="supporting">

///   <method name="load" returns="array">
///     <brief>Возвращает всё содержимое файла ввиде массива</brief>
///     <body>
  public function load() {
    rewind($this->file);
    $res = array();
    foreach ($this as $k => $v)
      $res[$k] = $v;
    return $res;
  }
///     </body>
///   </method>

///   </protocol>

}
/// </class>

/// </module>
