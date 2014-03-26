<?php
/**
 * Templates.Text
 * 
 * Текстовые шаблоны
 * 
 * @package Templates\Text
 * @version 0.1.0
 */

Core::load('Templates', 'Text', 'Object');

/**
 * @package Templates\Text
 */
class Templates_Text implements Core_ModuleInterface {
  const VERSION = '0.2.0';

  static protected $helpers;


/**
 * Инициализация
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
 * Фабричный методот, возвращающий объект класса Templates.Text.Template
 * 
 * @param string $name
 * @return Templates_Text_Template
 */
  static public function Template($name) { return new Templates_Text_Template($name); }

}


/**
 * Текстовый шаблон
 * 
 * @package Templates\Text
 */
class Templates_Text_Template extends Templates_Template {

  protected $text;


/**
 * Конструктор
 * 
 */
  public function __construct($name) { $this->text = Text::Builder(); parent::__construct($name); }



/**
 * Возвращает конечный результат
 * 
 * @return string
 */
  public function render() {
    ob_start();
    $this->load($this->path);
    return Core::if_not(ob_get_clean(), $this->text->as_string());
  }



/**
 * Возвращает делегатор хелперов
 * 
 * @return Object_Aggregator
 */
  protected function get_helpers() {
    return Core::if_null($this->helpers, Templates_Text::helpers());
  }

/**
 * Возвращает путь к шаблону
 * 
 * @return string
 */
  protected function get_path() { return parent::get_path().'.ptxt'; }

/**
 * Инклюдит шаблон, создавая необходимые переменные
 * 
 * @param string $__path
 * @return Templates_Text_Template
 */
  protected function load($__path) {
    foreach ($this->parms as $__k => $__v) $$__k = $__v;
    $parms = $this->parms;
    $text = $this->text;
    if (IO_FS::exists($__path)) {
      include($__path);
      return $this;
    }
    else
      throw new Templates_MissingTemplateException($__path);
  }

}

