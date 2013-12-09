<?php
/**
 * L10N.EN
 * 
 * Содержит описание месяцов и дней недели для EN локали
 * 
 * @package L10N\EN
 * @version 0.2.0
 */
Core::load('L10N');

/**
 * @package L10N\EN
 */
class L10N_EN implements Core_ModuleInterface {
  const VERSION = '0.2.0';
}


/**
 * @package L10N\EN
 */
class L10N_EN_Locale extends L10N_Locale {


/**
 * Контруктор
 * 
 */
  public function __construct() {
    return parent::__construct(array(
      'months'   => array(
        L10N::FULL          => array(1 => 'january', 'february', 'march', 'april', 'may', 'june',  'july', 'august', 'september', 'october', 'november', 'december'),
        L10N::ABBREVIATED   => array(1 => 'jan', 'feb', 'mar', 'apr', 'may', 'jun', 'jul', 'aug', 'sep', 'oct', 'nov', 'dec')),
      'weekdays' => array(
        L10N::FULL        => array('sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'),
        L10N::ABBREVIATED => array('sun', 'mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun'))));
  }

}

