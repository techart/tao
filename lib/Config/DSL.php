<?php
/**
 * Config.DSL
 * 
 * Модуль построения конфигурационных настроек с помощью DSL
 * 
 * @package Config\DSL
 * @version 0.2.1
 */
Core::load('DSL');

/**
 * @package Config\DSL
 */
class Config_DSL implements Core_ModuleInterface {
  const VERSION = '0.2.1';

/**
 * @return Config_DSL_Builder
 */
  static public function Builder($object = null) { return new Config_DSL_Builder(null, $object); }

/**
 * Сокращение для self::Builder()->load($file)->object
 * 
 * @param string $file
 * @return Config_DSL_Object
 */
  static public function load($file) { return self::Builder()->load($file)->object; }

}


/**
 * DSL.Builder для построения конфигурационных настроек
 * 
 * @package Config\DSL
 */
class Config_DSL_Builder extends DSL_Builder {


/**
 * Конструктор
 * 
 * @param Config_DSL_Builder $parent
 * @param stdClass $object
 */
  public function __construct($parent = null, $object = null) {
    parent::__construct($parent, is_object($object) ? $object : (is_array($object) ? Core::object($object) : Core::object()));
  }



/**
 * Загружает конфигурационные настроеки из файла
 * 
 * @param string $file
 * @return Config_DSL_Builder
 */
  public function load($file) {
    if (!is_file($file)) return $this;
    $config = $this->object;
    ob_start();
    $result = include($file);
    ob_end_clean();
    if (is_array($result) || is_object($result)) {
      $this->object = $result;
    }
    return $this;
  }

/**
 * Порождает новый Config.DSL.Builder с текущим объектом ввиде предка
 * 
 * @param string $name
 * @return Config_DSL_Builder
 */
  public function begin($name) {
    return new Config_DSL_Builder($this,
      isset($this->object->$name) ?
        $this->object->$name : ($this->object->$name = new stdClass()));
  }

/**
 * Порождает DSL.Builder с текущим объектом ввиде предка
 * 
 * @param string $name
 * @return Config_DSL_Builder
 */
  public function with($name) {
    return new Config_DSL_SimpleBuilder($this, $this->object->$name);
  }



/**
 * Доступ на чтение к свойствам объекта
 * 
 * @param string $property
 * @return mixed
 */
  public function __get($property) {
    $value = parent::__get($property);
    if (!is_null($value)) {
      return $value;
    }
    if (strpos($property, 'begin_') === 0) {
      $name = substr($property, 6);
    } else {
      $name = $property;
    }
    return $this->begin($name);
  }

}

/**
 * @package Config\DSL
 */
class Config_DSL_SimpleBuilder extends DSL_Builder {

/**
 * Делегирует вызов конфигурируемому объекту
 * 
 * @param string $method
 * @param array $args
 * @return mixed
 */
  public function __call($method, $args) {
    call_user_func_array(array($this->object, $method), $args);
    return $this;
  }

}



