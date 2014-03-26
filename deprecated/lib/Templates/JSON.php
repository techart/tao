<?php
/**
 * Templates.JSON
 * 
 * JSON шаблоны
 * 
 * @package Templates\JSON
 * @version 0.2.1
 */

Core::load('Templates', 'Object');

/**
 * @package Templates\JSON
 */
class Templates_JSON implements Core_ModuleInterface {
  const VERSION = '0.2.1';

  static protected $helpers;


/**
 * Инициализирует модуль
 * 
 */
  static public function initialize() {
    self::$helpers = Object::Aggregator()->fallback_to(Templates::helpers());
  }



/**
 * Регестрирует хелпер
 * 
 */
  static public function use_helpers() {
    $args = Core::normalize_args(func_get_args());
    foreach ($args as $k => $v)
      if ($v instanceof Templates_HelperInterface) self::$helpers->append($v, is_numeric($k) ? null : (string) $k);
  }



/**
 * Возвращает делегатор хелперов
 * 
 * @return Object_Aggregator
 */
  static public function helpers() { return self::$helpers; }



/**
 * Фабричный метод, возвращает объект класса Templates.JSON.Template
 * 
 * @param string $name
 * @return Templates_JSON_Template
 */
  static public function Template($name) {  return new Templates_JSON_Template($name);  }


}

/**
 * JSON-шаблон
 * 
 * @package Templates\JSON
 */
class Templates_JSON_Template extends Templates_Template {


/**
 * Вовзращает конечный результат
 * 
 * @return string
 */
  public function render() { return $this->load($this->path); }



/**
 * Возвращает делегатор хелперов
 * 
 * @return Object_Aggregator
 */
  protected function get_helpers() {
    return Core::if_null($this->helpers, Templates_JSON::helpers());
  }

/**
 * Вовзращает путь до шаблона
 * 
 * @return string
 */
  protected function get_path() { return parent::get_path().'.pjson'; }

/**
 * Инклюдит шаблон, создавая необходимые переменные
 * 
 * @param  $__path
 * @return Templates_JSON_Template
 */
  protected function load($__path) {
    foreach ($this->parms as $__k => $__v) $$__k = $__v;
    $parms = $this->parms;
    $json = array();
    if (IO_FS::exists($__path)) {
      ob_start();
      include($__path);
      return Core::if_not(ob_get_clean(), json_encode((array) $json));
    }
    else
      throw new Templates_MissingTemplateException($__path);
  }

}

