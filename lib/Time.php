<?php
/// <module name="Time" version="0.2.2" maintainer="timokhin@techart.ru">
///   <brief>Работа с датами</brief>
///   <details>
///     <p>В PHP нет стандартного способа для работы с датами. Присутствующий в PHP класс DateTime
///        получил значительную часть необходимой функциональности только в версии 5.3, а на момент
///        начала разработки фреймворка плохо подходил для реальной работы. Поэтому было принято
///        решение реализовать собственный класс для представления моментов времени и использовать
///        его во всех модулях библиотеки. В дальнейшем, возможно, произойдет слияние этого класса
///        со стандартным DateTime.</p>
///     <p>Момент времени, представляемый экземпляром класса Time.DateTime, хранится внутри
///        экземпляра в виде UNIX timestamp. Такой подход имеет свои плюсы и минусы, в дальнейшем,
///        возможно, это решение будет пересмотрено.</p>
///   </details>

//TODO: надо бы сделать методы которые не меняют состояния класса
//      типа как в Ruby method и method!

/// <class name="Time" stereotype="module">
///   <brief>Класс модуля</brief>
///   <implements interface="Core.ModuleInterface" />
///   <depends supplier="Time.DateTime" stereotype="creates" />
///   <details>
///     <p>Реализует набор фабричных методов для создания объектов класса Time.DateTime, и несколько
///        вспомогательных методов.</p>
///     <p>Модуль определяет набор констант форматирования дат, а именно:</p>
///     <dl>
///       <dt>FMT_DEFAULT</dt>
///       <dd>'Y-i-d m:i:s'</dd>
///       <dt>FMT_DMYHMS</dt>
///       <dd>'d.i.Y m:i:s'</dd>
///       <dt>FMT_DMYHM</dt>
///       <dd>'d.i.Y m:i'</dd>
///       <dt>FMT_DMY</dt>
///       <dd>'d.i.Y'</dd>
///       <dt>FMT_YMD</dt>
///       <dd>'Y-i-d'</dd>
///       <dt>FMT_MDY</dt>
///       <dd>'i.d.Y'</dd>
///       <dt>FMT_HMS</dt>
///       <dd>'Y-i-dTm:i:s'</dd>
///       <dt>FMT_HM</dt>
///       <dd>'Y-i-dTm:i:s'</dd>
///       <dt>FMT_RFC1123</dt>
///       <dd>'Y-i-dTm:i:s'</dd>
///       <dt>FMT_ISO_8601</dt>
///       <dd>'Y-i-dTm:i:s'</dd>
///     </dl>
///   </details>
class Time implements Core_ModuleInterface {

///   <constants>
  const MODULE  = 'Time';
  const VERSION = '0.3.0';

  //TODO: to date format
  const FMT_DEFAULT  = 'Y-m-d H:i:s';
  const FMT_DMYHMS   = 'd.m.Y H:i:s';
  const FMT_DMYHM    = 'd.m.Y H:i';
  const FMT_DMY      = 'd.m.Y';
  const FMT_YMD      = 'Y-m-d';
  const FMT_MDY      = 'm.d.Y';
  const FMT_HMS      = 'H:i:s';
  const FMT_HM       = 'H:i';
  const FMT_RFC1123  = 'D, d M Y H:i:s O';
  const FMT_ISO_8601 = "Y-m-d\TH:i:sO";
///   </constants>

///   <protocol name="building">

///   <method name="DateTime" returns="Time.DateTime" scope="class">
///     <brief>Создает объект класса Time.DateTime</brief>
///     <args>
///       <arg name="timestamp" brief="момент времени" />
///     </args>
///     <details>
///       <p>Момент времени может быть задан различными способами:</p>
///       <ul>
///         <li>в виде числа -- в этом случае число является значением UNIX timestamp;</li>
///         <li>в виде строки -- в этом случае делается попытка разбора строки и создания
///             соответствущего объекта;</li>
///         <li>в виде объекта класса Time.DateTime -- в этом случае метод просто возвращает этот
///             объект.</li>
///       </ul>
///       <p>Парсинг строки выполняется с помощью метода Time.DateTime::parse() без указания
///          формата, что, в свою очередь, приводит к вызову встроенной функции strtotime().</p>
///     </details>
///     <body>
  static public function DateTime($timestamp) {
    switch (true) {
      case (string) (int) $timestamp === (string) $timestamp:
        $date = new Time_DateTime("@$timestamp");
        $date->setTimezone(new DateTimeZone(date_default_timezone_get()));
        return $date;
      case $timestamp instanceof Time_DateTime:
          return $timestamp;
      default:
        return Time_DateTime::parse((string) $timestamp);
    }
  }
///     </body>
///   </method>

///   <method name="now" returns="Time.DateTime" scope="class">
///     <brief>Создает объект класса Time.DateTime, соответствующий текущей дате</brief>
///     <body>
  static public function now() { return new Time_DateTime();}
///     </body>
///   </method>

///   <method name="seconds_between" returns="int" scope="class">
///     <brief>Возвращает количество секунд между двумя датами</brief>
///     <args>
///       <arg name="from" type="Time.DateTime" brief="первая дата" />
///       <arg name="to"   type="Time.DateTime" brief="вторая дата" />
///     </args>
///     <body>
  static public function seconds_between(Time_DateTime $from, Time_DateTime $to) {
    return abs($from->timestamp - $to->timestamp);
  }
///     </body>
///   </method>

///   <method name="compose" returns="Time.DateTime" scope="class">
///     <brief>Создает объект класса Time.DateTime по набору параметров, описывающий момент времени</brief>
///     <args>
///       <arg name="year"   type="int" brief="год" />
///       <arg name="month"  type="int" default="1" brief="месяц" />
///       <arg name="day"    type="int" default="1" brief="день" />
///       <arg name="hour"   type="int" default="0" brief="час" />
///       <arg name="minute" type="int" default="0" brief="минуты" />
///       <arg name="second" type="int" default="0" brief="секунды" />
///     </args>
///     <details>
///       <p>Псевдоним для  Time.DateTime::compose().</p>
///     </details>
///     <body>
  static public function compose($year, $month = 1, $day = 1, $hour = 0, $minute = 0, $second = 0) {
    return Time_DateTime::compose($year, $month, $day, $hour, $minute, $second);
  }
///     </body>
///   </method>

///   <method name="parse" returns="Time.DateTime" scope="class">
///     <brief>Создает объект класса Time.DateTime на основании строкового представления даты</brief>
///     <args>
///       <arg name="string" type="string" brief="строка" />
///       <arg name="format" type="string" default="Time::FMT_DEFAULT" brief="строка формата" />
///     </args>
///     <details>
///       <p>Псевдоним для метода Time.DateTime::parse().</p>
///     </details>
///     <body>
  static public function parse($string, $format = '') {
    return Time_DateTime::parse($string, $format);
  }
///     </body>
///   </method>

///   </protocol>
}
/// </class>


/// <class name="Time.DateTime">
///   <brief>Объектное представление дат</brief>
///   <implements interface="Core.PropertyAccessInterface" />
///   <details>
///     <p>На данный момент информация о дате хранится в виде UNIX timestamp. В дальнейшем возможно
///        изменение внутреннего формата хранения. Рекомендуется использовать фабричный метод модуля
///        Time::DateTime() для создания объектов класса.</p>
///     <p>Свойства:</p>
///     <dl>
///       <dt>timestamp</dt><dd>UNIX timestamp</dd>
///       <dt>year</dt><dd>год</dd>
///       <dt>month</dt><dd>месяц</dd>
///       <dt>day</dt><dd>день</dd>
///       <dt>hour</dt><dd>час</dd>
///       <dt>minute</dt><dd>минута</dd>
///       <dt>second</dt><dd>секунда</dd>
///       <dt>wday</dt><dd>порядковый номер дня недели</dd>
///       <dt>yday</dt><dd>порядковый номер дня в году</dd>
///     </dl>
///   </details>
class Time_DateTime extends DateTime implements Core_PropertyAccessInterface, Core_EqualityInterface {

  public function setTime() {
    $args = func_get_args();
    parent::setTime($args[0], $args[1], $args[2]);
    return $this;
  }

  public function setDate() {
    $args = func_get_args();
    parent::setDate($args[0], $args[1], $args[2]);
    return $this;
  }

///   <method name="compose" scope="class" returns="Time.DateTime">
///     <brief>Создает объект класса Time.DateTime по набору параметров, описывающий момент времени</brief>
///     <args>
///       <arg name="year"   type="int" brief="год" />
///       <arg name="month"  type="int" default="1" brief="месяц" />
///       <arg name="day"    type="int" default="1" brief="день" />
///       <arg name="hour"   type="int" default="0" brief="час" />
///       <arg name="minute" type="int" default="0" brief="минуты" />
///       <arg name="second" type="int" default="0" brief="секунды" />
///     </args>
///     <body>
  static public function compose($year, $month = 1, $day = 1, $hour = 0, $minute = 0, $second = 0) {
    if (!checkdate($month, $day, $year)) return false;
    $date = new Time_DateTime();
    $date->setDate($year, $month, $day);
    $date->setTime($hour, $minute, $second);
    return $date;
  }
///     </body>
///   </method>


  static public function parse_clib($string, $format) {
    $tm = strptime($string, $format);
    if (!$tm) return null;
    return self::compose(
      $tm['tm_year'] + 1900, $tm['tm_mon'] + 1, ($tm['tm_mday']) ? $tm['tm_mday'] : 1 ,
      $tm['tm_hour'], $tm['tm_min'], $tm['tm_sec']);
  }


///   <method name="parse" returns="Time.DateTime" scope="class">
///     <brief>Создает объект класса Time.DateTime на основании строкового представления даты</brief>
///     <args>
///       <arg name="string" type="string" brief="строка" />
///       <arg name="format" type="string" default="Time::FMT_DEFAULT" brief="строка формата" />
///     </args>
///     <details>
///       <p>В том случае, если указана строка формата, для разбора строки используется встроенная
///       функция strptime(). Если строка формата не указана, используется встроенная функция
///       strtotime(), которая, вообще говоря, более интеллектуальна и может быть использована в
///       случаях, когда необходимо автоопределение формата.</p>
///       <p>Метод возвращает null в случае, если разбор строки завершился неудачей.</p>
///     </details>
///     <body>
  static public function parse($string, $format = '', $clib = null) {
    $rc = null;
    if (!$string) return null;
    if ($format) {
      if (is_null($clib)) {
        if (Core_Strings::contains($format, '%')) {
          $rc = self::parse_clib($string, $format);
        } else {
          $rc = Time_DateTime::createFromFormat($format, $string);
        }
      } else {
        if ($clib) $rc = self::parse_clib($string, $format);
        else $rc = Time_DateTime::createFromFormat($format, $string);
      }
    } else {
      try {
        $rc = new Time_DateTime($string);
      } catch(Exception $e) {
        $rc = self::parse_clib($string, $format);
      }
    }
    if ($rc instanceof DateTime) $rc = Time::DateTime($rc->getTimestamp());
    if ($rc && !checkdate($rc->month, $rc->day, $rc->year)) return null;
    return $rc;
  }
///     </body>
///   </method>

///  </protocol>

///   <protocol name="match" interface="Core.EqualityInterface">

///   <method name="equals" returns="boolean">
///     <brief>Выполняет проверку на равенство</brief>
///     <args>
///       <arg name="to" brief="дата" />
///     </args>
///     <details>
///       <p>Псевдоним для is_equal()</p>
///       <p>Проверяет на равенство дату, представляемую объетом, с датой, представляемой другим
///          объектом класса Time.DateTime.</p>
///     </details>
///     <body>
  public function equals($to) { return ($to instanceof Time_DateTime) && $this->is_equal_to($to); }
///       </body>
///     </method>

///   </protocol>

///   <protocol name="comparing">

///   <method name="between">
///     <args>
///       <arg name="from" type="Time.DateTime" />
///       <arg name="to" type="Time.DateTime" />
///     </args>
///     <body>
  public function between(Time_DateTime $from, Time_DateTime $to) {
    return $this->not_earlier_than($from) && $this->not_later_than($to);
  }
///     </body>
///   </method>

///   <method name="earlier_than" returns="boolean">
///     <brief>Проверяет, предшествует ли дата объекта заданной дате</brief>
///     <args>
///       <arg name="time" type="Time.DateTime" brief="дата" />
///     </args>
///     <body>
  public function earlier_than(Time_DateTime $time) { return $this < $time; }
///     </body>
///   </method>

///   <method name="not_earlier_than" returns="boolean">
///     <brief>Проверяет, что дата объекта не предшествует заданной дате</brief>
///     <args>
///       <arg name="time" type="Time.DateTime" brief="дата" />
///     </args>
///     <body>
  public function not_earlier_than(Time_DateTime $time) { return $this >= $time; }
///     </body>
///   </method>

///   <method name="later_than" returns="boolean">
///     <brief>Проверяет, следует ли дата объекта за указанной датой</brief>
///     <args>
///       <arg name="time" type="Time.DateTime"  brief="дата"/>
///     </args>
///     <body>
  public function later_than(Time_DateTime $time)  { return $this > $time; }
///     </body>
///   </method>

///   <method name="not_later_than" returns="boolean">
///     <brief>Проверяет, что дата объекта не следует за указанной датой</brief>
///     <args>
///       <arg name="time" type="Time.DateTime" brief="дата" />
///     </args>
///     <body>
  public function not_later_than(Time_DateTime $time) { return $this <= $time; }
///     </body>
///   </method>

///   <method name="same_date_as" returns="boolean">
///     <brief>Выполняет проверку дат на равенство с точностью до дня</brief>
///     <args>
///       <arg name="time" type="Time.DateTime" brief="дата" />
///     </args>
///     <details>
///       <p>В отличие от методa equals(), сравниваются только календарные даты и игнорируется
///          составляющая собственно времени (часы, минуты, секунды).</p>
///     </details>
///     <body>
  public function same_date_as(Time_DateTime $time) {
    return $this->format('Y-m-d') == $time->format('Y-m-d');
  }
///     </body>
///   </method>

///   <method name="is_equal_to" returns="boolean">
///     <brief>Проверяет на равенство две даты</brief>
///     <args>
///       <arg name="time" type="Time.DateTime" brief="дата" />
///     </args>
///     <body>
// TODO: изменение имени метода: is_equal() -> is_equal_to()
  public function is_equal_to(Time_DateTime $time) {  return $this == $time; }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="calculating">

///   <method name="time_zone_offset" returns="int">
///     <brief>Возвращает смещение временной зоны</brief>
///     <body>
  public function time_zone_offset() {
    return $this->getOffset();
  }
///     </body>
///   </method>

  public function getTimestamp() {
    if (method_exists('DateTime', 'getTimestamp'))
      return parent::getTimestamp();
    return mktime($this->hour, $this->minute, $this->second, $this->month, $this->day, $this->year);
  }

  public function setTimestamp($ts) {
    if (method_exists('DateTime', 'setTimestamp'))
      return parent::setTimestamp($ts);
    return Time::DateTime($ts);
  }


///   </protocol>

///   <protocol name="changing">

  public function add_by_timestap($seconds, $minutes = 0, $hours = 0, $days = 0, $months = 0, $years = 0) {
    $ts = mktime($this->hour + $hours, $this->minute + $minutes,
        $this->second + $seconds, $this->month + $months, $this->day + $days, $this->year + $years);
    return Time::DateTime($ts);
  }

///   <method name="add" returns="Time.DateTime">
///     <brief>Смещает дату вперед на определенный интервал</brief>
///     <args>
///       <arg name="seconds" type="int" brief="секунды" />
///       <arg name="minutes" type="int" default="0" brief="минуты" />
///       <arg name="hours"   type="int" default="0" brief="часы" />
///       <arg name="days"    type="int" default="0" brief="дни" />
///       <arg name="months"  type="int" default="0" brief="месяцы" />
///       <arg name="years"   type="int" default="0" brief="годы" />
///     </args>
///     <body>
  public function add($seconds, $minutes = 0, $hours = 0, $days = 0, $months = 0, $years = 0) {
    if (!class_exists('DateInterval'))
      return $this->add_by_timestap($seconds, $minutes, $hours, $days, $months, $years);
    if ($seconds instanceof DateInterval)
      return parent::add($seconds);
    if (is_string($seconds))
      $interval = new DateInterval($seconds);
    else
      $interval = new DateInterval("P{$years}Y{$months}M{$days}DT{$hours}H{$minutes}M{$seconds}S");
    parent::add($interval);
    return $this;
  }
///     </body>
///   </method>

///   <method name="add_seconds" returns="Time.DateTime">
///     <brief>Смещает дату вперед на заданное число секунд</brief>
///     <args>
///       <arg name="interval" type="int" brief="интервал" />
///     </args>
///     <body>
  public function add_seconds($interval) { return $this->add($interval); }
///     </body>
///   </method>

///   <method name="add_minutes" returns="Time.DateTime">
///     <brief>Смещает дату вперед на заданное число минут</brief>
///     <args>
///       <arg name="interval" type="int" brief="интервал" />
///     </args>
///     <body>
  public function add_minutes($interval) {  return $this->add(0, $interval);  }
///     </body>
///   </method>

///   <method name="add_hours" returns="Time.DateTime">
///     <brief>Смещает дату вперед на заданное число часов</brief>
///     <args>
///       <arg name="interval" type="int" brief="интервал" />
///     </args>
///     <body>
  public function add_hours($interval) { return $this->add(0, 0, $interval); }
///     </body>
///   </method>

///   <method name="add_days" returns="Time.DateTime">
///     <brief>Смещает дату вперед на заданное число дней</brief>
///     <args>
///       <arg name="interval" type="int" brief="интервал" />
///     </args>
///     <body>
  public function add_days($interval) { return $this->add(0, 0, 0, $interval); }
///     </body>
///   </method>

///   <method name="add_months" returns="Time.DateTime">
///     <brief>Смещает дату вперед на заданное число месяцев</brief>
///     <args>
///       <arg name="interval" type="int" brief="интервал" />
///     </args>
///     <body>
  public function add_months($interval) { return $this->add(0, 0, 0, 0, $interval); }
///     </body>
///   </method>

///   <method name="add_years" returns="Time.DateTime">
///     <brief>Смещает дату вперед на заданное число лет</brief>
///     <args>
///       <arg name="interval" type="int" brief="интервал" />
///     </args>
///     <body>
  public function add_years($interval) { return $this->add(0, 0, 0, 0, 0, $interval); }
///     </body>
///   </method>

///   <method name="sub" returns="Time.DateTime">
///     <brief>Смещает дату назад на определенный интервал</brief>
///     <args>
///       <arg name="seconds" type="int"             brief="секунды" />
///       <arg name="minutes" type="int" default="0" brief="минуты" />
///       <arg name="hours"   type="int" default="0" brief="часы" />
///       <arg name="days"    type="int" default="0" brief="дни" />
///       <arg name="months"  type="int" default="0" brief="месяцы" />
///       <arg name="years"   type="int" default="0" brief="годы" />
///     </args>
///     <body>
  public function sub($seconds, $minutes = 0, $hours = 0, $days = 0, $months = 0, $years = 0) {
    if (!class_exists('DateInterval'))
      return $this->add_by_timestap(-$seconds, -$minutes, -$hours, -$days, -$months, -$years);
    if ($seconds instanceof DateInterval)
      return parent::sub($seconds);
    if (is_string($seconds))
      $interval = new DateInterval($seconds);
    else
      $interval = new DateInterval("P{$years}Y{$months}M{$days}DT{$hours}H{$minutes}M{$seconds}S");
    parent::sub($interval);
    return $this;
  }
///     </body>
///   </method>

///   <method name="sub_seconds" returns="Time.DateTime">
///     <brief>Смещает дату назад на заданное количество секунд</brief>
///     <args>
///       <arg name="interval" type="int" brief="интервал" />
///     </args>
///     <body>
  public function sub_seconds($interval)  { return $this->sub($interval); }
///     </body>
///   </method>

///   <method name="sub_minutes" returns="Time.DateTime">
///     <brief>Смещает дату назад на заданное количество минут</brief>
///     <args>
///       <arg name="interval" type="int" brief="интервал" />
///     </args>
///     <body>
  public function sub_minutes($interval) { return $this->sub(0, $interval); }
///     </body>
///   </method>

///   <method name="sub_hours" returns="Time.DateTime">
///     <brief>Смещает дату назад на заданное количество часов</brief>
///     <args>
///       <arg name="interval" type="int" brief="интервал" />
///     </args>
///     <body>
  public function sub_hours($interval) { return $this->sub(0, 0, $interval); }
///     </body>
///   </method>

///   <method name="sub_days" returns="Time.DateTime">
///     <brief>Смещает дату назад на заданное количество дней</brief>
///     <args>
///       <arg name="interval" type="int" brief="интервал" />
///     </args>
///     <body>
  public function sub_days($interval) { return $this->sub(0, 0, 0, $interval); }
///     </body>
///   </method>

///   <method name="sub_months" returns="Time.DateTime">
///     <brief>Смещает дату назад на заданное количество месяцев</brief>
///     <args>
///       <arg name="interval" type="int" brief="месяцы" />
///     </args>
///     <body>
  public function sub_months($interval) { return $this->sub(0, 0, 0, 0, $interval); }
///     </body>
///   </method>

///   <method name="sub_years" returns="Time.DateTime">
///     <brief>Смещает дату назад на заданное количество лет</brief>
///     <args>
///       <arg name="interval" type="int" brief="интервал" />
///     </args>
///     <body>
  public function sub_years($interval) { return $this->sub(0, 0, 0, 0, 0, $interval); }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="accessing" interface="Core.PropertyAccessInterface">

///   <method name="__get" returns="mixed">
///     <brief>Возвращает значение свойства</brief>
///     <args>
///       <arg name="property" type="string" brief="имя свойства" />
///     </args>
///     <body>
  public function __get($property) {
    switch ($property) {
      case 'timestamp': case 'ts': return $this->getTimestamp();
      case 'year':      return (int) $this->format('Y');
      case 'month':     return (int) $this->format('n');
      case 'day':       return (int) $this->format('j');
      case 'hour':      return (int) $this->format('G');
      case 'minute':    return (int) $this->format('i');
      case 'second':    return (int) $this->format('s');
      case 'wday':      $w =(int) $this->format('w'); return $w == 0 ? 7 : $w;
      case 'yday':      return (int) $this->format('j');
    default:
      throw new Core_MissingPropertyException($property);
    }
  }
///     </body>
///   </method>

///   <method name="__set" returns="Time.DateTime">
///     <brief>Устанавливает значение свойства</brief>
///     <args>
///       <arg name="property" type="string" brief="имя свойства" />
///       <arg name="value"    type="string" brief="значение" />
///     </args>
///     <details>
///       <p>На данный момент только значение свойства timestamp может быть установлено извне.</p>
///     </details>
///     <body>
  public function __set($property, $value) {
    switch ($property) {
      case 'timestamp':
        $this->setTimestamp((int) $value);
        return $this;
      case 'year':
      case 'month':
      case 'day':
      case 'hour':
      case 'minute':
      case 'second':
      case 'wday':
      case 'yday':
        throw new Core_ReadOnlyPropertyException($property);
      default:
        throw new Core_MissingPropertyException($property);
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
      case 'timestamp':
      case 'year':
      case 'month':
      case 'day':
      case 'hour':
      case 'minute':
      case 'second':
      case 'wday':
      case 'yday':
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
///       <arg name="property" type="string" brief="имя свойства" />
///     </args>
///     <body>
  public function __unset($property) {
    switch ($property) {
      case 'timestamp':
      case 'year':
      case 'month':
      case 'day':
      case 'hour':
      case 'minute':
      case 'second':
      case 'wday':
      case 'yday':
        throw new Core_UndestroyablePropertyException($property);
      default:
        throw new Core_MissingPropertyException($property);
    }
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="formatting">


  public function format_clib($format = Time::FMT_DEFAULT) {
    return strftime($format, $this->ts);
  }

///   <method name="format">
///     <brief>Преобразует дату в строку заданного формата</brief>
///     <args>
///       <arg name="as_string" type="string" default="Time::FMT_DEFAULT" brief="строка формата" />
///     </args>
///     <details>
///       <p>Для преобразования в строку используется встроенная функция strftime().</p>
///       <p>При использовании этого метода необходимо помнить, что отдельные элементы строки
///          форматирования генерируют представление, зависящее от текущей локали. Поэтому в ряде
///          случаев, например, при генерации представления дат для поддержки различных сетевых
///          протоколов, имеет смысл использовать специализированные методы, работаюищие в
///          соответствии c тем или иным RFC.</p>
///     </details>
///     <body>
  public function format($format = Time::FMT_DEFAULT, $clib = null) {
    if (is_null($clib)) {
      if (Core_Strings::contains($format, '%'))
        return $this->format_clib($format);
      return parent::format($format);
    } else {
      if ($clib)
        return $this->format_clib($format);
      else
        return parent::format($format);
    }
  }

  public function format_l10n($format, $locale = 'ru', $variant = 0) {
    Core::load('L10N');
    L10N::locale($locale);
    return L10N::strftime($format, $this, $variant);
  }
///     </body>
///   </method>

///   <method name="as_rfc1123" returns="string">
///     <brief>Преобразует дату в строку в формате RFC1123</brief>
///     <details>
///       <p>Результат выполнения метода не зависит от выбранной локали.</p>
///     </details>
///     <body>
  public function as_rfc1123() {
    return $this->format(self::RFC1123);
  }
///     </body>
///   </method>


///   </protocol>

///   <protocol name="stringyfing" interface="Core.StringifyInterface">

///   <method name="as_string" returns="string">
///     <brief>Возвращает строковое представление объекта</brief>
///     <details>
///       <p>При формировании строкового представления используется формат по умолчанию.</p>
///     </details>
///     <body>
  public function as_string() { return $this->format(); }
///     </body>
///   </method>

///   <method name="__toString" returns="string">
///     <brief>Возвращает строковое представление объекта</brief>
///     <details>
///       <p>Псевдоним для as_string().</p>
///     </details>
///     <body>
  public function __toString() { return $this->format(); }
///     </body>
///   </method>

///   </protocol>
}
/// </class>

/// </module>
