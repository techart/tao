<?php
/**
 * L10N.RU
 * 
 * Содержит описание месяцов и дней недели для RU локали
 * 
 * @package L10N\RU
 * @version 0.2.0
 */
Core::load('L10N');

/**
 * @package L10N\RU
 */
class L10N_RU implements Core_ModuleInterface {
  const VERSION = '0.2.0';
  const PREPOSITIONAL = 101;
}


/**
 * @package L10N\RU
 */
class L10N_RU_Locale extends L10N_Locale {


/**
 * Контруктор
 * 
 */
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



/**
 * Возвращает одну из трех форм слова в зависимости от числа
 * 
 * @param int $count
 * @param string $str1
 * @param string $str2
 * @param string $str3
 */
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


}

