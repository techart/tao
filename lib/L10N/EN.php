<?php
/// <module name="L10N.EN" version="0.2.0" maintainer="timokhin@techart.ru">
///   <brief>Содержит описание месяцов и дней недели для EN локали</brief>
Core::load('L10N');

/// <class name="L10N.RU" stereotype="module">
///   <implements interface="Core.ModuleInterface" />
class L10N_EN implements Core_ModuleInterface {
///   <constants>
  const VERSION = '0.2.0';
///   </constants>
}
/// </class>


/// <class name="L10N.EN.Locale" extends="L10N.Locale">
class L10N_EN_Locale extends L10N_Locale {

///   <protocol name="creating">

///   <method name="__construct">
///     <brief>Контруктор</brief>
///     <body>
  public function __construct() {
    return parent::__construct(array(
      'months'   => array(
        L10N::FULL          => array(1 => 'january', 'february', 'march', 'april', 'may', 'june',  'july', 'august', 'september', 'october', 'november', 'december'),
        L10N::ABBREVIATED   => array(1 => 'jan', 'feb', 'mar', 'apr', 'may', 'jun', 'jul', 'aug', 'sep', 'oct', 'nov', 'dec')),
      'weekdays' => array(
        L10N::FULL        => array('sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'),
        L10N::ABBREVIATED => array('sun', 'mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun'))));
  }
///     </body>
///   </method>

///   </protocol>
}
/// </class>

/// </module>
