<?php
/// <module name="Service.Google.Chart" maintainer="svistunov@techart.ru" version="0.1.0">
///   <brief>
///     Модуль представляет интерфейс для сервиса Google Chart.
///     Для лучшего понимания происходящего следует ознакомиться с <a href="http://code.google.com/intl/en/apis/chart/">документацией Google Chart</a>.
///   </brief>
///   <details>
///     <p>Для создания чартов в модуле есть фабричные методы, название которых соответствует типу
///        чарта. На вход подается обязательный параметр – размер чарта (строка формата
///        '[width]x[height]'). У всех чартов есть метод save_as('куда/сохранить'), который скачивает
///        результат и сохраняет по указанному пути; и метод as_url(), который возвращает сформированный
///        url. Этот url например можно передать в качестве значения аттрибута src тега img.</p>
///     <p>Передавать данные для построения чарта можно следующими способами. В метод data можно передать
///        либо массив значений, либо просто последовательность значений. Для добавления ещё одних данных
///        нужно соответственно ещё раз вызвать метод data или аналогичный. Следующий способ –
///        метод data_from, куда можно передать хэшь или массив (вообщем всё по чему можно сделать foreach).
///        Это удобно при работе с объектами бизнес логики или результатами выборки из БД. Последний
///        способ специфичен для чартов linexy и scatter. Это метод dataXY, на вход которому подается либо
///        ассоциативный массив либо два массива. В этом случае значения понимаются как пара (x,y), т.е.
///        однозначно определяет точку на графике. Все остальные методы предназначены для задания
///        дополнительных параметров таких как цвет, оси, подписи и т.д.</p>
///     <p>Для более подробной информации обращайтесь к описанию класса Graph и Axis, а так же к <a href="http://code.google.com/intl/en/apis/chart/">документации Google Chart</a>.</p>
///     <p>В данный момент не реализованна заливка участов графика и фоновая заливка. Также нет поддержки чартов QR codes - типа</p>
///     <p>Несколько примеров использования:</p>
///     <code><![CDATA[
///     Service_Google_Chart::linexy('300x200')->dataXY(array(-10 => 10, 20 => 20, 30 => 30,40 => 40,50 => 50,60 => 60,70 => 10, 80 => 10, 90 => 10,100 => 10, 110 => 10,120 => 74,130 => 10,140 => 10,150 => 10,160 =>0))->
///      text()->
///        axis('x')->
///          auto_range(20)->
///        end->
///        axis('y')->
///          auto_range(20)->
///        end->
///        marker('c', 'FF0000', 0, -1, 5)->
///        marker('r', '00FF00', 0, 0.2, .7)->
///        grid(20, 20)->
///        title('Title')->
///        save_as('linxy.png');
///
///    Service_Google_Chart::pie('300x120')->data(10,40,50)->labels('10','40','50')->
///      orientation(.7)->
///      save_as('pie.png');
///   // $c - DB_Connection
///   $data = $c->prepare(<<<SQL
///   SELECT id, count
///   FROM test_table
///   SQL
///   )->execute()->fetch_all();
///
///   Service_Google_Chart::pie('700x300')->
///    title('Использование data_from')->
///    data_from($data,  'count')->
///    save_as('data_from.png');
///     ]]></code>
///   </details>

Core::load('Net.Agents.HTTP', 'IO.FS');
/// <class name="Service.Google.Chart" stereotype="module">
class Service_Google_Chart implements Core_ModuleInterface {

///   <constants>
  const VERSION = '0.1.0';
  const DEFAULT_SIZE = '200x125';
  const SERVER_URL = 'http://chart.apis.google.com/chart?';
///   </constants>

///   <protocol name="building">

///   <method name="Graph" returns="Service.Google.Chart" scope="class">
///     <args>
///       <arg name="type" type="string" />
///       <arg name="size" type="string" />
///     </args>
///     <body>
  static public function Graph($type, $size) { return new Service_Google_Chart_Graph($type, $size); }
///     </body>
///   </method>

///   <method name="line" returns="Service.Google.Chart" scope="class">
///     <args>
///       <arg name="size" type="string" />
///     </args>
///     <body>
  static public function line($size) { return self::Graph('lc', $size); }
///     </body>
///   </method>

///   <method name="linexy" returns="Service.Google.Chart" scope="class">
///     <args>
///       <arg name="size" type="string" />
///     </args>
///     <body>
  static public function linexy($size) { return self::Graph('lxy', $size); }
///     </body>
///   </method>

///   <method name="sparkline" returns="Service.Google.Chart" scope="class">
///     <args>
///       <arg name="size" type="string" />
///     </args>
///     <body>
  static public function sparkline($size) { return self::Graph('ls', $size); }
///     </body>
///   </method>

///   <method name="bar_horizontal_group" returns="Service.Google.Chart" scope="class">
///     <args>
///       <arg name="size" type="string" />
///     </args>
///     <body>
  static public function bar_horizontal_group($size) { return self::Graph('bhg', $size); }
///     </body>
///   </method>

///   <method name="bar_horizontal_stacked" returns="Service.Google.Chart" scope="class">
///     <args>
///       <arg name="size" type="string" />
///     </args>
///     <body>
  static public function bar_horizontal_stacked($size) { return self::Graph('bhs', $size); }
///     </body>
///   </method>

///   <method name="bar_vertical_group" returns="Service.Google.Chart" scope="class">
///     <args>
///       <arg name="size" type="string" />
///     </args>
///     <body>
  static public function bar_vertical_group($size) { return self::Graph('bvg', $size); }
///     </body>
///   </method>

///   <method name="bar_vertical_stacked" returns="Service.Google.Chart" scope="class">
///     <args>
///       <arg name="size" type="string" />
///     </args>
///     <body>
  static public function bar_vertical_stacked($size) { return self::Graph('bvs', $size); }
///     </body>
///   </method>

///   <method name="pie" returns="Service.Google.Chart" scope="class">
///     <args>
///       <arg name="size" type="string" />
///     </args>
///     <body>
  static public function pie($size) { return self::Graph('p', $size); }
///     </body>
///   </method>

///   <method name="pie_3d" returns="Service.Google.Chart" scope="class">
///     <args>
///       <arg name="size" type="string" />
///     </args>
///     <body>
  static public function pie_3d($size) { return self::Graph('p3', $size); }
///     </body>
///   </method>

///   <method name="pie_concentric" returns="Service.Google.Chart" scope="class">
///     <args>
///       <arg name="size" type="string" />
///     </args>
///     <body>
  static public function pie_concentric($size) { return self::Graph('pc', $size); }
///     </body>
///   </method>

///   <method name="venn" returns="Service.Google.Chart" scope="class">
///     <args>
///       <arg name="size" type="string" />
///     </args>
///     <body>
  static public function venn($size) { return self::Graph('v', $size); }
///     </body>
///   </method>

///   <method name="scatter" returns="Service.Google.Chart" scope="class">
///     <args>
///       <arg name="size" type="string" />
///     </args>
///     <body>
  static public function scatter($size) { return self::Graph('s', $size); }
///     </body>
///   </method>

///   <method name="radar" returns="Service.Google.Chart" scope="class">
///     <args>
///       <arg name="size" type="string" />
///     </args>
///     <body>
  static public function radar($size) { return self::Graph('r', $size); }
///     </body>
///   </method>

///   <method name="map" returns="Service.Google.Chart" scope="class">
///     <args>
///       <arg name="size" type="string" />
///     </args>
///     <body>
  static public function map($size) { return self::Graph('t', $size); }
///     </body>
///   </method>

///   <method name="meter" returns="Service.Google.Chart" scope="class">
///     <args>
///       <arg name="size" type="string" />
///     </args>
///     <body>
  static public function meter($size) { return self::Graph('gom', $size); }
///     </body>
///   </method>

///   </protocol>
}
/// </class>


/// <class name="Service.Google.Chart.Exception" extends="Core.Exception">
class Service_Google_Chart_Exception extends Core_Exception {}
/// </class>


/// <class name="Service.Google.Chart.UnsupportedMethodException">
class Service_Google_Chart_UnsupportedMethodException extends Service_Google_Chart_Exception {

  protected $type;
  protected $option;

///   <protocol name="creating">

///   <method name="__construct">
///     <args>
///       <arg name="type" type="string" />
///       <arg name="option" type="string" />
///     </args>
///     <body>
  public function __construct($type, $option) {
    $this->type = (string) $type;
    $this->option = (string) $option;
    parent::__construct("Unsupported option '{$this->option}' for type {$this->type}");
  }
///     </body>
///   </method>

///   </protocol>
}
/// </class>


/// <class name="Service.Google.Chart.Encoder" stereotype="abstract">
///   <implements interface="Core.PropertyAccessInterface" />
abstract class Service_Google_Chart_DataEncoder implements Core_PropertyAccessInterface {

  static protected $simple_chars   = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
  static protected $extended_chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789-.';

  protected $prefix;
  protected $separator;

///   <protocol name="processing">

///   <method name="encode_series" returns="string">
///     <args>
///       <arg name="data" type="array" />
///       <arg name="min" type="numeric" />
///       <arg name="max" type="numeric" />
///     </args>
///     <body>
  abstract public function encode_series(array $data, $min, $max);
///     </body>
///   </method>

///   <method name="encode_all" returns="string">
///     <args>
///       <arg name="data" type="array" />
///       <arg name="range" type="object" />
///     </args>
///     <body>
  public function encode_all(array $data, $range) {
    $r = array();
    foreach ($data as $set) {
      if (Core_Types::is_array($set[0]) && Core_Types::is_array($set[1])) {
        $r[] = $this->encode_series($set[0], $range->left, $range->right);
        $r[] = $this->encode_series($set[1], $range->bottom, $range->top);
      }
      else
        $r[] = $this->encode_series($set, $range->bottom, $range->top);
    }
    return $this->prefix.implode($this->separator, $r);
  }
///     </body>
///   </method>
///   </protocol>

///   <protocol name="accessing">

///   <method name="__get" returns="mixed">
///     <args>
///       <arg name="property" type="string" />
///     </args>
///     <body>
  public function __get($property) {
    switch ($property) {
      case 'prefix': case 'separator': return $this->$property;
      default: throw new Core_MissingPropertyException($property);
    }
  }
///     </body>
///   </method>

///   <method name="__set" returns="mixed">
///     <args>
///       <arg name="property" type="string" />
///       <arg name="value" />
///     </args>
///     <body>
  public function __set($property, $value) {
    throw new Core_ReadOnlyObjectException($this);
  }
///     </body>
///   </method>

///   <method name="__isset" returns="boolean">
///     <args>
///       <arg name="property" type="string" />
///     </args>
///     <body>
  public function __isset($property) {
    switch ($property) {
      case 'prefix': case 'separator': return isset($this->$property);
      default: return false;
    }
  }
///     </body>
///   </method>

///   <method name="__unset">
///     <args>
///       <arg name="property" type="string" />
///     </args>
///     <body>
  public function __unset($property) {
        throw new Core_ReadOnlyObjectException($this);
  }
///     </body>
///   </method>

///   </protocol>

}
/// </class>

/// <class name="Service.Google.Chart.SimpleEncoder" extends="Service.Google.Chart.DataEncoder">
class Service_Google_Chart_SimpleEncoder extends Service_Google_Chart_DataEncoder {

///   <protocol name="creating">

///   <method name="__construct">
///     <args>
///       <arg name="pref" type="string" default="'s:'" />
///       <arg name="separetor" type="string" default="','" />
///     </args>
///     <body>
  public function __construct($pref = 's:', $separator = ',') {
    $this->prefix = (string) $pref;
    $this->separator = (string) $separator;
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="processing">

///   <method name="encode_series" returns="string">
///     <args>
///       <arg name="series" type="array" />
///       <arg name="min" type="numeric" />
///       <arg name="max" type="numeric" />
///     </args>
///     <body>
  public function encode_series(array $series, $min, $max) {
    $res = '';
    $max_value = 61;
    foreach ($series as $v)
      $res .= ($v === NULL ? '_' : self::$simple_chars[(int)(($v-$min)*$max_value/($max-$min))]);
    return $res;
  }
///     </body>
///   </method>

///   </protocol>
}
/// </class>

/// <class name="Service.Google.Chart.TextEncoder" extends="Service.Google.Chart.DataEncoder">
class Service_Google_Chart_TextEncoder extends Service_Google_Chart_DataEncoder {

///   <protocol name="creating">

///   <method name="__construct">
///     <args>
///       <arg name="pref" type="string" default="'t:'" />
///       <arg name="separetor" type="string" default="'|'" />
///     </args>
///     <body>
  public function __construct($pref = 't:', $separator = '|') {
    $this->prefix = (string) $pref;
    $this->separator = (string) $separator;
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="processing">

///   <method name="encode_series" returns="string">
///     <args>
///       <arg name="series" type="array" />
///       <arg name="min" type="numeric" />
///       <arg name="max" type="numeric" />
///     </args>
///     <body>
  public function encode_series(array $series, $min, $max) {
    $res = '';
    $max_value = 100;
    foreach ($series as $v)
      $res .= ($res > '' ? ',' : '').($v === NULL ? '-1' : round(($v-$min)*$max_value/($max-$min), 1));
    return $res;
  }
///     </body>
///   </method>

///   </protocol>
}
/// </class>

/// <class name="Service.Google.Chart.ExtendedEncoder" extends="Service.Google.Chart.DataEncoder">
class Service_Google_Chart_ExtendedEncoder extends Service_Google_Chart_DataEncoder {

///   <protocol name="creating">

///   <method name="__construct">
///     <args>
///       <arg name="pref" type="string" default="'e:'" />
///       <arg name="separetor" type="string" default="','" />
///     </args>
///     <body>
  public function __construct($pref = 'e:', $separator = ',') {
    $this->prefix = (string) $pref;
    $this->separator = (string) $separator;
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="processing">

///   <method name="encode_series" returns="string">
///     <args>
///       <arg name="series" type="array" />
///       <arg name="min" type="numeric" />
///       <arg name="max" type="numeric" />
///     </args>
///     <body>
  public function encode_series(array $series, $min, $max) {
    $res = '';
    $max_value = 4095;
    $size_enc = strlen(self::$extended_chars);
    foreach ($series as $v) {
      if ($v == NULL) $res.='__';
      else {
        $f = (int) floor((($v-$min)*$max_value/($max-$min))/$size_enc);
        $s = (($v-$min)*$max_value/($max-$min)) % $size_enc;
        $res .= self::$extended_chars[$f].self::$extended_chars[$s];
      }
    }
    return $res;
  }
///     </body>
///   </method>

///   </protocol>
}
/// </class>


/// <class name="Service.Google.Chart.Graph">
///   <implements interface="Core.StringifyInterface" />
///   <implements interface="Core.PropertyAccessInterface" />
///   <implements interface="Core.IndexedAccessInterface" />
class Service_Google_Chart_Graph implements Core_StringifyInterface, Core_PropertyAccessInterface, Core_IndexedAccessInterface {

  protected $type;
  protected $data = array();
  protected $opts = array();
  protected $range;
  protected $encoder;

///   <protocol name="creating">

///   <method name="__construct">
///     <args>
///       <arg name="type" type="string" />
///       <arg name="size" type="string" />
///     </args>
///     <body>
  public function __construct($type, $size) {
    $this->type = $type;
    $this->reset()->simple()->size($size);
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="indexing" interface="Core.IndexedPropertyAccessInterface">

///   <method name="offsetGet" returns="mixed">
///     <args>
///       <arg name="index" />
///     </args>
///     <body>
  public function offsetGet($index) {
    return $this->opts[$index];
  }
///     </body>
///   </method>

///   <method name="offsetSet" returns="mixed">
///     <args>
///       <arg name="index" />
///       <arg name="value" />
///     </args>
///     <body>
  public function offsetSet($index, $value) {
    return $this->option($index, $value);
  }
///     </body>
///   </method>

///   <method name="offsetExists" returns="boolean">
///     <args>
///       <arg name="index" />
///     </args>
///     <body>
  public function offsetExists($index) {
    return isset($this->opts[$index]);
  }
///     </body>
///   </method>

///   <method name="offsetUnset">
///     <args>
///       <arg name="index" />
///     </args>
///     <body>
  public function offsetUnset($index) {
    unset($this->opts[$index]);
    return $this;
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="accessing">

///   <method name="__get" returns="mixed">
///     <args>
///       <arg name="property" type="string" />
///     </args>
///     <body>
  public function __get($property) {
    switch($property) {
      case 'type':
      case 'data':
      case 'opts':
      case 'range':
      case 'encoder':
        return $this->$property;
      case 'request':
        return Net_HTTP::Request(Service_Google_Chart::SERVER_URL)->parameters($this->as_array());
      default:
        throw new Core_MissingPropertyException($property);
    }
  }
///     </body>
///   </method>

///   <method name="__set" returns="mixed">
///     <args>
///       <arg name="property" type="string" />
///       <arg name="value" />
///     </args>
///     <body>
  public function __set($property, $value) {
      switch($property) {
      case 'type':
      case 'data':
      case 'opts':
      case 'range':
      case 'encoder':
      case 'request':
        throw new Core_ReadOnlyObjectException($this);
      default:
        throw new Core_MissingPropertyException($property);
    }
  }
///     </body>
///   </method>

///   <method name="__isset" returns="boolean">
///     <args>
///       <arg name="property" type="string" />
///     </args>
///     <body>
  public function __isset($property) {
    return isset($this->$property) || $property == 'request';
  }
///     </body>
///   </method>

///   <method name="__unset">
///     <args>
///       <arg name="property" type="string" />
///     </args>
///     <body>
  public function __unset($property) {
    return $this->__set($property, null);
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="configuring">

///   <method name="simple" returns="Service.Google.Chart.Graph">
///     <body>
  public function simple() {
    $this->encoder = new Service_Google_Chart_SimpleEncoder();
    return $this;
  }
///     </body>
///   </method>

///   <method name="text" returns="Service.Google.Chart.Graph">
///     <body>
  public function text() {
    $this->encoder = new Service_Google_Chart_TextEncoder();
    return $this;
  }
///     </body>
///   </method>

///   <method name="extended" returns="Service.Google.Chart.Graph">
///     <body>
  public function extended() {
    $this->encoder = new Service_Google_Chart_ExtendedEncoder();
    return $this;
  }
///     </body>
///   </method>

///   <method name="encoder" returns="Service.Google.Chart.Graph">
///     <args>
///       <arg name="encoder" type="Service.Google.Chart.DataEncoder" />
///     </args>
///     <body>
  public function encoder(Service_Google_Chart_DataEncoder $encoder) {
    $this->encoder = $encoder;
    return $this;
  }
///     </body>
///   </method>

///   <method name="reset" returns="Service.Google.Chart.Graph">
///     <body>
  public function reset() {
    $this->data = array();
    $this->range = Core::object(array(
      'left' => null, 'right' => null, 'top' => null, 'bottom' => null));
    return $this;
  }
///     </body>
///   </method>

///   <method name="data" returns="Service.Google.Chart.Graph" varargs="true">
///     <body>
  public function data() {
    $items = Core::normalize_args(func_get_args());
    $this->correct_range($items)->data[] = $items;
    return $this;
  }
///     </body>
///   </method>

///   <method name="data_from" returns="Service.Google.Chart.Graph">
///     <args>
///       <arg name="source" />
///       <arg name="field" type="string" default="null" />
///       <arg name="as_property" type="boolean" default="false" />
///     </args>
///     <body>
  public function data_from($source, $field = null, $as_property = false) {
    return $this->data($this->from_iterator($source, $field, $as_property));
  }
///     </body>
///   </method>

///   <method name="data_xy" returns="Service.Google.Chart.Graph" varargs="true">
///     <args>
///       <arg name="data_x" type="array" />
///       <arg name="data_y" type="array" default="null" />
///     </args>
///     <body>
  public function data_xy(array $data_x, array $data_y = null) {
    if ($data_y == null) {
      $k = array_keys($data_x);
      $v = array_values($data_x);
    } else {
      $k = $data_x;
      $v = $data_y;
    }
    $this->
      restrict_to('lxy,s', 'dataXY')->
      correct_range($k, false)->
      correct_range($v)->
        data[] = array($k, $v);
    return $this;
  }
///     </body>
///   </method>

///   <method name="data_xy_from" returns="Service.Google.Chart.Graph">
///     <args>
///       <arg name="data" />
///       <arg name="field_y" type="string" default="null" />
///       <arg name="field_x" type="string" default="null" />
///       <arg name="as_property" type="boolean" default="false" />
///     </args>
///     <body>
  public function data_xy_from($data, $field_y = null, $field_x = null, $as_property = false) {
    $keys   = array();
    $values = array();

    foreach ($data as $k => $v) {
      $keys[]   = $field_x === null ? $k : ($as_property ? $v->$field_x : $v[$field_x]);
      $values[] = $field_y === null ? $v : ($as_property ? $v->$field_y : $v[$field_y]);
    }

    return  $this->data_xy($keys, $values);
  }
///     </body>
///   </method>

///   <method name="size" returns="Service.Google.Chart.Graph">
///     <args>
///       <arg name="size" type="string" />
///     </args>
///     <body>
  public function size($size) { return $this->option('chs', (string) $size); }
///     </body>
///   </method>

///   <method name="title" returns="Service.Google.Chart.Graph">
///     <args>
///       <arg name="title" type="string" />
///     </args>
///     <body>
  public function title($title) { return $this->except_for('t', 'title')->option('chtt', $this->str_to_validurl($title)); }
///     </body>
///   </method>

///   <method name="legend" returns="Service.Google.Chart.Graph" varargs="true">
///     <body>
  public function legend() {
    $args = func_get_args();
    return $this->option('chdl', Core::normalize_args($args));
  }
///     </body>
///   </method>

  public function legend_from($source, $field = null, $as_property = false) {
    return $this->legend($this->from_iterator($source, $field, $as_property));
  }

///   <method name="colors" returns="Service.Google.Chart.Graph" varargs="true">
///     <body>
  public function colors() {
    $args = func_get_args();
    return $this->option('chco', Core::normalize_args($args));
  }
///     </body>
///   </method>

///   <method name="bottom" returns="Service.Google.Chart.Graph">
///     <args>
///       <arg name="value" type="numeric" />
///     </args>
///     <body>
  public function bottom($value) {
    $this->range->bottom = $value;
    return $this;
  }
///     </body>
///   </method>

///   <method name="top" returns="Service.Google.Chart.Graph">
///     <args>
///       <arg name="value" type="numeric" />
///     </args>
///     <body>
  public function top($value) {
    $this->range->top = $value;
    return $this;
  }
///     </body>
///   </method>

///   <method name="left" returns="Service.Google.Chart.Graph">
///     <args>
///       <arg name="value" type="numeric" />
///     </args>
///     <body>
  public function left($value) {
    $this->range->left = $value;
    return $this;
  }
///     </body>
///   </method>

///   <method name="right" returns="Service.Google.Chart.Graph">
///     <args>
///       <arg name="value" type="numeric" />
///     </args>
///     <body>
  public function right($value) {
    $this->range->right = $value;
    return $this;
  }
///     </body>
///   </method>

///   <method name="axis" returns="Service.Google.Chart.Axis">
///     <args>
///       <arg name="type" type="string" />
///       <arg name="idx" type="int" default="null" />
///     </args>
///     <body>
  public function axis($type, $idx = null) {
    $this->restrict_to('bhs,bvs,bhg,bvg,r,rs,s,lc,ls,lxy', 'axis');
    return new Service_Google_Chart_Axis(
      $this,
      $type,
      ($idx === null) ?
        ((isset($this->opts['chxt']) && is_array($this->opts['chxt'])) ?
          count($this->opts['chxt']) : 0) : $idx);
  }
///     </body>
///   </method>

///   <method name="labels" returns="Service.Google.Chart.Axis" varargs="true">
///     <body>
  public function labels() {
    $args = func_get_args();
    return $this->restrict_to('p,p3,pc,gom', 'labels')->option('chl', Core::normalize_args($args));
  }
///     </body>
///   </method>

  public function labels_from($source, $field = null, $as_property = false) {
    return $this->labels($this->from_iterator($source, $field, $as_property));
  }

///   <method name="marker" returns="Service.Google.Chart.Axis" varargs="true">
///     <body>
  public function marker() {
    $args = Core::normalize_args(func_get_args());
    return $this->restrict_to('bhs,bvs,bhg,bvg,r,rs,s,lc,ls,lxy', 'marker')->option('chm', $args,
      (isset($this->opts['chm']) && Core_Types::is_array($this->opts['chm']) ?
        count($this->opts['chm']) : 0));
  }
///     </body>
///   </method>

///   <method name="grid" returns="Service.Google.Chart.Axis" varargs="true">
///     <body>
  public function grid() {
    $args = Core::normalize_args(func_get_args());
    return $this->restrict_to('bhs,bvs,bhg,bvg,r,rs,s,lc,ls,lxy', 'grid')->option('chg', $args);
  }
///     </body>
///   </method>

///   <method name="spacing" returns="Service.Google.Chart.Axis" varargs="true">
///     <body>
  public function spacing() {
    $args = Core::normalize_args(func_get_args());
    return $this->restrict_to('bhs,bhg,bvs,bvg', 'spacing')->option('chbh', $args);
  }
///     </body>
///   </method>

///   <method name="area" returns="Service.Google.Chart.Axis">
///     <args>
///       <arg name="geo_area" type="string" default="'world'" />
///     </args>
///     <body>
  public function area($geo_area = 'world') {
    return $this->restrict_to('t', 'area')->option('chtm', $geo_area);
  }
///     </body>
///   </method>

///   <method name="countries" returns="Service.Google.Chart.Axis" varargs="true">
///     <body>
  public function countries() {
    $args = Core::normalize_args(func_get_args());
    return $this->restrict_to('t', 'counttries')->option('chld', $args);
  }
///     </body>
///   </method>

///   <method name="zero_line" returns="Service.Google.Chart.Axis" varargs="true">
///     <body>
  public function zero_line() {
    $args = Core::normalize_args(func_get_args());
    return $this->restrict_to('bhs,bhg,bvs,bvg', 'zero_line')->option('chp', $args);
  }
///     </body>
///   </method>

///   <method name="orientation" returns="Service.Google.Chart.Axis">
///     <args>
///       <arg name="radians" type="int" />
///     </args>
///     <body>
  public function orientation($radians) {
    return $this->restrict_to('p,p3,pc', 'orientation')->option('chp', $radians);
  }
///     </body>
///   </method>

///   <method name="sizes" returns="Service.Google.Chart.Axis" varargs="true">
///     <body>
  public function sizes() {
    $args = Core::normalize_args(func_get_args());
    return $this->restrict_to('s', 'sizes')->option('_ss', $args);
  }
///     </body>
///   </method>

///   <method name="line_style" returns="Service.Google.Chart.Axis">
///     <body>
  public function line_style($thickness,  $line_segment, $blank_segment, $data_index = null) {
    return $this->restrict_to('lc,ls,lxy', 'line_style')->option('chls', "$thickness,$line_segment,$blank_segment",
      ($data_index == null) ?
      ((isset($this->opts['chls']) && Core_Types::is_array($this->opts['chls'])) ?
      count($this->opts['chls']) : 0)
      : $data_index);
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="performing">

///   <method name="as_array" returns="array">
///     <body>
  public function as_array() {
    $parms = array();

    foreach ($this->opts as $opt => $val) {
      switch ($opt) {
        case 'chs':   // size
        case 'chtt':  // title
        case 'chtm':  // geo area for map
          $parms[$opt] = $val;
          break;
        case 'chld':  // map countries
          $parms[$opt] = implode('', $val);
          break;
        case 'chp':   //bar zero_lenes or pie orientatio
          $parms[$opt] = Core_Types::is_array($val) ? implode(',', array_map(array($this, 'str_to_validurl'), $val)) :
            $val;
          break;
        case 'chdl':  // legend
        case 'chl':   // pie chart labels or meter label or QR text encode
        case 'chls':  // line style
          $parms[$opt] = implode('|', array_map(array($this, 'str_to_validurl'), $val));
          break;
        case 'chco':  // colors
        case 'chxt':  // axes types
        case 'chg':   // grid lines
        case 'chbh':  // bar spacing
          $parms[$opt] = implode(',', $val);
          break;
        case 'chm':  // markers
          $m = array();
          foreach ($val as $v) $m[] = implode(',', $v);
          $parms[$opt] = implode('|', $m);
          break;
        case 'chxtc': // axes ticks
          $t = array();
          foreach ($val as $k => $v) $t[] = "$k,$v";
          $parms[$opt] = implode('|', $t);
          break;
        case 'chxl':  // axes labels
          $t = array();
          foreach ($val as $k => $v) $t[] = "$k:|".implode('|', array_map(array($this, 'str_to_validurl'), $v));
          $parms[$opt] = implode('|', $t);
          break;
        case 'chxp':  // axes labels positions
        case 'chxs':  // axes styles
        case 'chxr':  // axes ranges
          $t = array();
          foreach ($val as $k => $v) $t[] = "$k,".implode(',', array_map(array($this, 'str_to_validurl'), $v));
          $parms[$opt] = implode('|', $t);
          break;
        case '_ss':   // scatter sizes
          $scatter_sizes = $this->encoder->encode_series($val, 0, 100);
          break;
      }
    }

    if ($this->match_types('p,p3,pc,bhs,bvs,bhg,bvg,map,t,gom'))
      $this->range->bottom = 0;
    if ($this->match_types('gom')) $this->range->top = 100;

    $parms['cht'] = $this->type;
    $parms['chd'] = $this->encoder->encode_all($this->data, $this->range).
      ($scatter_sizes ? $this->encoder->separator.$scatter_sizes : '');

    return $parms;
  }
///     </body>
///   </method>

///   <method name="as_url" returns="string">
///     <body>
  public function as_url() {
    return $this->request->url;
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="supporting">

///   <method name="str_to_validurl" access="protected">
///     <args>
///       <arg name="str" type="string" />
///     </args>
///     <body>
  protected function str_to_validurl($str) {
    return Core_Strings::replace(Core_Strings::replace($str, ' ', '+'), "\n", '|');
  }
///     </body>
///   </method>

///   <method name="from_iterator" access="protected" returns="array">
///       <args>
///         <arg name="source" />
///         <arg name="field" type="string" default="null" />
///         <arg name="as_property" type="boolean" default="null" />
///       </args>
///       <body>
  protected function from_iterator($source, $field = null, $as_property = false) {
    $items = array();
    foreach ($source as $v)
      $items[] = ($field ? ($as_property ? $v->$field : $v[$field]) : $v);
    return $items;
  }
///       </body>
///     </method>

///   <method name="correct_range" returns="Service.Google.Chart.Base">
///     <args>
///       <arg name="items" type="array" />
///       <arg name="is_y" type="boolean" default="true" />
///     </args>
///     <body>
  protected function correct_range(array $items, $is_y = true) {
    if ($is_y)
      foreach ($items as $v) {
        if ($this->range->top    === null || $this->range->top    < $v) $this->range->top = $v;
        if ($this->range->bottom === null || $this->range->bottom > $v) $this->range->bottom = $v;
      }
    else
      foreach ($items as $v) {
        if ($this->range->left   === null || $this->range->left   > $v) $this->range->left = $v;
        if ($this->range->right  === null || $this->range->right  < $v) $this->range->right = $v;
      }

    return $this;
  }
///     </body>
///   </method>

///   <method name="get_range" returns="object">
///     <body>
  public function get_range() { return $this->range; }
///     </body>
///   </method>

///   <method name="option" returns="Service.Google.Chart.Graph" access="protected">
///     <args>
///       <arg name="name" type="string" />
///       <arg name="value" />
///       <arg name="idx" type="int" default="null" />
///     </args>
///     <body>
  public function option($name, $value, $idx = null) {
    if ($idx === null) $this->opts[$name] = $value;
    else               $this->opts[$name][(int)$idx] = $value;
    return $this;
  }
///     </body>
///   </method>

///   <method name="restrict_to" returns="Service.Google.Chart.Graph" access="protected">
///     <args>
///       <arg name="type" type="string" />
///       <arg name="option" type="string" />
///     </args>
///     <body>
  protected function restrict_to($types, $option) {
    if (!$this->match_types($types))
      throw new Service_Google_Chart_UnsupportedMethodException($this->type, $option);
    return $this;
  }
///     </body>
///   </method>

///   <method name="except_for" returns="Service.Google.Chart.Graph" access="protected">
///     <args>
///       <arg name="type" type="string" />
///       <arg name="option" type="string" />
///     </args>
///     <body>
  protected function except_for($types, $option) {
    if ($this->match_types($types))
      throw new Service_Google_Chart_UnsupportedMethodException($this->type, $option);
    return $this;
  }
///     </body>
///   </method>

///   <method name="math_types">
///     <args>
///       <arg name="types" type="" />
///     </args>
///     <body>
  protected function match_types($types) {
    return Core_Regexps::match("!(^{$this->type})|({$this->type}\$)|([,]{$this->type}[,])!i", $types);
  }
///     </body>
///   </method>

///   <method name="save_as" returns="boolean">
///     <args>
///       <arg name="path" type="string" />
///     </args>
///     <body>
  public function save_as($path) {
    return IO_FS::File($path)->update(Net_HTTP::Agent()->send($this->request->method(Net_HTTP::POST))->body);
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="stringifying">

///   <method name="as_string" returns="string">
///     <body>
  public function as_string() {
    return $this->as_url();
  }
///     </body>
///   </method>

///   <method name="__toString" returns="string">
///     <body>
  public function __toString() {
    return $this->as_string();
  }
///     </body>
///   </method>

///   </protocol>

}
/// </class>


/// <class name="Service.Google.Chart.Axis">
class Service_Google_Chart_Axis {
  protected $graph;
  protected $idx;
  protected $type;

///   <protocol name="creating">

///   <method name="__construct">
///     <args>
///       <arg name="graph" type="Service.Google.Chart.Graph" />
///       <arg name="type" type="string" />
///       <arg name="idx" type="int" />
///     </args>
///     <body>
  public function __construct(Service_Google_Chart_Graph $graph, $type, $idx) {
    $this->idx   = $idx;
    $this->graph = $graph;
    $this->type = $type;
    $this->option('chxt', $type);
  }
///     </body>
///   </method>

///   </protocol>


///   <protocol name="perfoming">
///   <method name="tick" returns="Service.Google.Chart.Axis">
///     <args>
///       <arg name="width" type="int" />
///     </args>
///     <body>
  public function tick($width) { return $this->option('chxtc', (int) $width); }
///     </body>
///   </method>

///   <method name="plabels" returns="Service.Google.Chart.Axis" varargs="true">
///     <body>
  public function plabels() {
    $args = func_get_args();
    $args = Core::normalize_args($args);
    return $this->labels(array_values($args))->positions(array_keys($args));
  }
///     </body>
///   </method>

///   <method name="labels" returns="Service.Google.Chart.Axis" varargs="true">
///     <body>
  public function labels() {
    $args = func_get_args();
    return $this->option('chxl', Core::normalize_args($args));
  }
///     </body>
///   </method>

///   <method name="positions"  returns="Service.Google.Chart.Axis" varargs="true">
///     <body>
  public function positions() {
    $args = func_get_args();
    return $this->option('chxp', Core::normalize_args($args));
  }
///     </body>
///   </method>

///   <method name="style" returns="Service.Google.Chart.Axis" varargs="true">
///     <body>
  public function style() {
    $args = func_get_args();
    return $this->option('chxs', Core::normalize_args($args));
  }
///     </body>
///   </method>

///   <method name="range" returns="Service.Google.Chart.Axis" varargs="true">
///     <body>
  public function range() {
    $args = func_get_args();
    return $this->option('chxr', Core::normalize_args($args));
  }
///     </body>
///   </method>

///   <method name="auto_range" returns="Service.Google.Chart.Axis">
///     <args>
///       <arg name="step" type="int" default="0" />
///     </args>
///     <body>
  public function auto_range($step = 0) {
    $r = $this->graph->get_range();
    switch ($this->type) {
      case 'x': case 't': return $this->range($r->left, $r->right, $step);
      case 'y': case 'r': return $this->range($r->bottom, $r->top, $step);
    }
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="supporting">

///   <method name="__get" returns="Service.Google.Chart.Graph|null">
///     <args>
///       <arg name="property" type="string" />
///     </args>
///     <body>
  public function __get($property) { return $property == 'end' ? $this->graph : null; }
///     </body>
///   </method>

///   <method name="option" returns="Service.Google.Chart.Axis" access="protected">
///     <args>
///       <arg name="name" type="string" />
///       <arg name="value" />
///     </args>
///     <body>
  protected function option($name, $value) {
    $this->graph->option($name, $value, $this->idx);
    return $this;
  }
///     </body>
///   </method>

///   </protocol>
}
/// </class>

/// </module>
