<?php
/// <module name="L10N" version="0.2.1" maintainer="timokhin@techart.ru">
///   <brief>Модуль предназначен для локализации дат на русский и английский языки</brief>
Core::load('Time');

/// <class name="L10N" stereotype="module">
class L10N implements Core_ModuleInterface {

///   <constants>
  const VERSION = '0.2.1';
///   </constants>

  const FULL          = 0;
  const ABBREVIATED   = 1;
  const INFLECTED     = 2;

  static protected $locale = null;

///   <protocol name="creating">

///   <method name="lang" returns="L10N.LocaleInterface" scope="class">
///     <brief>Устанавливает локаль</brief>
///     <details>
///       Локаль может быть 'ru' или 'en'.
///       Подгружается соответствующий модуль.
///     </details>
///     <args>
///       <arg name="lang" type="string" default="null" brief="локаль" />
///     </args>
///     <body>
  static public function locale($lang = null) {
    if ($lang !== null) {
      Core::load($module = 'L10N.'.strtoupper($lang));
      self::$locale = Core::make("$module.Locale");
    }
    return self::$locale;
  }
///     </body>
///   </method>

///   <method name="strfrime" returns="string" scope="class">
///     <brief>Возвращает отформатированную строку, подставляя дату в нужном формате</brief>
///     <args>
///       <arg name="format" type="string" brief="строка форматирования" />
///       <arg name="date" type="Time.DateTime|int|string" brief="дата" />
///       <arg name="variant" type="int" brief="вариант" />
///     </args>
///     <body>
  static public function strftime($format, $date, $variant = L10N::FULL) {
    return self::$locale->strftime($format, $date, $variant);
  }
///     </body>
///   </method>

///   <method name="month_name" returns="string" scope="class">
///     <brief>Вовращает имя месяца в соответствии с установленной локалью</brief>
///     <args>
///       <arg name="month" type="int" brief="месяц" />
///       <arg name="variant" type="int" brief="вариант" />
///     </args>
///     <body>
    static public function month_name($month, $variant = L10N::FULL) {
      return self::$locale->month_name($month, $variant);
    }
///     </body>
///   </method>

///   <method name="weekday_name" returns="string" scope="class">
///     <brief>Вовращает день нелдели в соответствии с установленной локалью</brief>
///     <args>
///       <arg name="wday" type="int" brief="день недели" />
///       <arg name="variant" type="int" brief="вариант" />
///     </args>
///     <body>
    static public function weekday_name($wday, $variant = L10N::FULL) {
      return self::$locale->weekday_name($wday, $variant);
    }
///     </body>
///   </method>

///   </protocol>
}
/// </class>


/// <class name="L10N.Locale" stereotype="abstract">
///   <brief>Абстрактный класс локали</brief>
abstract class L10N_Locale {

  protected $data;

///   <protocol name="creating">

///   <method name="__construct">
///     <brief>Конструктор</brief>
///     <args>
///       <arg name="definition" type="array" brief="массив с описанием различных вариантов соответвующий локали" />
///     </args>
///     <body>
  public function __construct($definition) {
    $this->data = $definition;
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="quering">

///   <method name="strfrime" returns="string">
///     <brief>Возвращает отформатированную строку, подставляя дату в нужном формате</brief>
///     <details>
///       Строка форматирования может содержать следующие конструкции:
///       <dl>
///         <dt>%a</dt><dd>день недели в варианте L10N::ABBREVIATED (в сокращенном виде)</dd>
///         <dt>%A</dt><dd>день недели в варианте L10N::FULL (полное название)</dd>
///         <dt>%b</dt><dd>день месяца в варианте L10N::ABBREVIATED </dd>
///         <dt>%d(\s)*%B</dt><dd>день ввиде ввиде двухзначного числа (01 02 ...) и месяц в варианте L10N::INFLECTED (янворя фувроля ...) </dd>
///         <dt>%e(\s)*%B</dt><dd>день и месяц в варианте L10N::INFLECTED </dd>
///         <dt>%B</dt><dd>месяц в варианте указанном в атрибуте $variant</dd>
///       </dl>
///       Варианты могут быть FULL, ABBREVIATED, INFLECTED. Так же спецефичный варианты могут быть определены в модуле локали.
///       Например в L10N.RU дополнительно поределен вариант PREPOSITIONAL
///     </details>
///     <args>
///       <arg name="format" type="string" brief="строка форматирования" />
///       <arg name="date" type="Time.DateTime|int|string" brief="дата" />
///       <arg name="variant" type="int" brief="вариант" />
///     </args>
///     <body>
  public function strftime($format, $date, $variant = L10N::FULL) {
    if (!$date = Time::DateTime($date))
      throw new Core_InvalidArgumentTypeException('date', $date);

    return strftime(
            preg_replace(
              array('/%a/', '/%A/', '/%b/', '/%d(\s)*%B/', '/%e(\s)*%B/', '/%B/'),
              array(
                $this->variant(L10N::ABBREVIATED, 'weekdays', $date->wday),
                $this->variant(L10N::FULL, 'weekdays', $date->wday),
                $this->variant(L10N::ABBREVIATED, 'months', $date->month),
                sprintf('%02d\1%s', $date->day, $this->variant(L10N::INFLECTED, 'months', $date->month)),
                sprintf('%d\1%s', $date->day, $this->variant(L10N::INFLECTED, 'months', $date->month)),
                $this->variant($variant, 'months', $date->month)),
              $format),
            $date->timestamp);
  }
///     </body>
///   </method>

///   <method name="month_name" returns="string">
///     <brief>Вовращает имя месяца</brief>
///     <args>
///       <arg name="month" type="int" brief="месяц" />
///       <arg name="variant" type="int" brief="вариант" />
///     </args>
///     <body>
    public function month_name($month, $variant = L10N::FULL) {
      return $this->variant($variant, 'months', (int)$month);
    }
///     </body>
///   </method>


///   <method name="weekday_name" returns="string">
///     <brief>Вовращает день нелдели</brief>
///     <args>
///       <arg name="wday" type="int" brief="день недели" />
///       <arg name="variant" type="int" brief="вариант" />
///     </args>
///     <body>
    public function weekday_name($wday, $variant = L10N::FULL) {
      return $this->variant($variant, 'weekdays', (int) $wday);
    }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="supporting">

///   <method name="variant" returns="string" access="protected">
///     <brief>Вовращает название месяца или дня недели</brief>
///     <body>
  protected function variant($variant, $from, $index) {
    return $this->data[$from][isset($this->data[$from][$variant]) ? $variant : L10N::FULL][$index];
  }
///     </body>
///   </method>


///   </protocol>
}
/// </class>

/// </module>
