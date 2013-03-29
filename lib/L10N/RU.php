<?php
/// <module name="L10N.RU" version="0.2.0" maintainer="timokhin@techart.ru">
///   <brief>Содержит описание месяцов и дней недели для RU локали</brief>
Core::load('L10N');

/// <class name="L10N.RU" stereotype="module">
///   <implements interface="Core.ModuleInterface" />
class L10N_RU implements Core_ModuleInterface {
///   <constants>
  const VERSION = '0.2.0';
  const PREPOSITIONAL = 101;
///   </constants>
}
/// </class>


/// <class name="L10N.RU.Locale" extends="L10N.Locale">
class L10N_RU_Locale extends L10N_Locale {

///   <protocol name="creating">

///   <method name="__construct">
///     <brief>Контруктор</brief>
///     <body>
  public function __construct() {
    return parent::__construct(array(
      'months'   => array(
        L10N::FULL          => array(1 => 'январь', 'февраль', 'март', 'апрель', 'май', 'июнь',  'июль', 'август', 'сентябрь', 'октябрь', 'ноябрь', 'декабрь'),
        L10N::INFLECTED     => array(1 => 'января', 'февраля', 'марта', 'апреля', 'мая', 'июня', 'июля', 'августа', 'сентября', 'октября', 'ноября', 'декабря'),
        L10N::ABBREVIATED   => array(1 => 'янв', 'фев', 'мар', 'апр', 'май', 'июн', 'июл', 'авг', 'сен', 'окт', 'ноя', 'дек'),
        L10N_RU::PREPOSITIONAL => array(1 => 'январе', 'феврале', 'марте', 'апреле', 'мае', 'июне', 'июле', 'августе', 'сентябре', 'октябре', 'ноябре', 'декабре')),
      'weekdays' => array(
        L10N::FULL        => array('воскресенье', 'понедельник', 'вторник', 'среда', 'четверг', 'пятница', 'суббота', 'воскресенье'),
        L10N::ABBREVIATED => array('вск', 'пнд', 'втр', 'срд', 'чтв', 'птн', 'сбт', 'вск'))));
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="querying">

///   <method name="plural_for">
///     <brief>Возвращает одну из трех форм слова в зависимости от числа</brief>
///     <args>
///       <arg name="count" type="int" brief="Число, в зависимости от которого выбирается нужная форма слова" />
///       <arg name="str1" type="string" brief="первая форма слова, например - 'строка' (1 строка)" />
///       <arg name="str2" type="string" brief="вторая форма слова, например - 'строки' (2 строки)" />
///       <arg name="str3" type="string" brief="третья форма слова, например - 'строк' (10 строк)" />
///     </args>
///     <body>
  public function plural_for($count, $str1, $str2, $str3) {
    $remainder100 = $count % 100;
    $remainder10 = $count % 10;
    switch (true) {
      case ($remainder100 >= 11 && $remainder100 <= 14 || $remainder10 == 0 || $remainder10 >= 5) :
        return $str3;
      case ($remainder10 == 1) :
        return $str1;
      default :
        return $str2;
    }
  }
///     </body>
///   </method>

///   </protocol>

}
/// </class>

/// </module>
