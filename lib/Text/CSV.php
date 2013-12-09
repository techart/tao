<?php
/**
 * Text.CSV
 * 
 * Модуль предоставляет классы для работы с CVS
 * 
 * @package Text\CSV
 * @version 0.1.0
 */

/**
 * @package Text\CSV
 */
class Text_CSV implements Core_ModuleInterface {
  const VERSION = '0.1.0';


/**
 * Фабричный метод, возвращает объект класса Text.CVS.Reader
 * 
 * @param string $path
 * @return Text_CVS_Reader
 */
  public static function Reader($path = null) {
    return new Text_CSV_Reader($path);
  }

}

/**
 * Итератор для чтения csv файлов
 * 
 * @package Text\CSV
 */
class Text_CSV_Reader implements Iterator, Core_PropertyAccessInterface {

  protected $file = null;
  
  protected $current;
  protected $row_count = -1;
  
  protected $delimeter = ',';
  protected $enclosure = '"';
  

/**
 * Конструктор
 * 
 * @param string $path
 */
  public function __construct($path = null) {
    if ($path !== null)
      $this->file = fopen($path, 'r');
  }
  
/**
 * Устанавливает csv файл
 * 
 * @param string $path
 */
  public function from_file($path) {
    $this->file = fopen($path, 'r');
  }

/**
 * Считывает csv из потока
 * 
 * @param  $file
 */
  public function from_stream($file) {
    $this->file = $file;
  }

/**
 * Деструктор
 * 
 */
  public function __destruct() {
    if (is_resource($this->file)) fclose($this->file);
  }


/**
 * Сбрасывает итератор в начало
 * 
 * @return mixed
 */
  public function rewind() {
    rewind($this->file);
    $this->next();
  }

/**
 * Возвращает текущий элемент итератора
 * 
 * @return mixed
 */
  public function current() {
    return $this->current;
  }

/**
 * Возвращает ключ текущего элемента
 * 
 * @return mixed
 */
  public function key() {
    return $this->row_count;
  }

/**
 * Возвращает следующий элемент
 * 
 */
  public function next() {
    $this->current = fgetcsv($this->file, 0, $this->delimeter, $this->enclosure);
    if($this->current !== false)
      $this->row_count++;
    else fclose($this->file);
    return $this->current;
  }

/**
 * Проверяет валидность текущего элемента
 * 
 * @return boolean
 */
  public function valid() {
    return $this->current !== false;
  }



/**
 * Доступ на чтение к свойствам объекта
 * 
 * @param string $property
 * @return mixed
 */
  public function __get($property) {
    switch ($property) {
      case 'delimeter': case 'enclosure':
        return $this->$property;
      default:
        throw new Core_MissingPropertyException($property);
    }
  }

/**
 * Доступ на запись к свойствам объекта
 * 
 * @param string $property
 * @param  $value
 * @return mixed
 */
  public function __set($property, $value) {
    switch ($property) {
      case 'delimeter': case 'enclosure':
      	{$this->$property  = (string) $value; return $this;}
      default:
        throw new Core_MissingPropertyException($property);
    }
  }

/**
 * Проверяет установлено ли свойство
 * 
 * @param string $property
 * @return boolean
 */
  public function __isset($property) {
    switch ($property) {
      case 'delimeter': case 'enclosure':
        return isset($this->$property);
      default:
        throw new Core_MissingPropertyException($property);
    }
  }
  
/**
 * Очищает свойство объекта
 * 
 * @param string $property
 */
  public function __unset($property) {
    switch ($property) {
      case 'delimeter': case 'enclosure':
        throw new Core_ReadOnlyPropertyException($property);
      default:
        throw new Core_MissingPropertyException($property);
    }
  }



/**
 * Возвращает всё содержимое файла ввиде массива
 * 
 * @return array
 */
  public function load() {
    rewind($this->file);
    $res = array();
    foreach ($this as $k => $v)
      $res[$k] = $v;
    return $res;
  }


}

