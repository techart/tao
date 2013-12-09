<?php
/**
 * Mail
 * 
 * Модуль для работы с почтовыми письмами
 * 
 * В модуль входят классы для формирования, кодирования и отправки сообщений
 * 
 * @package Mail
 * @version 0.2.0
 */
/**
 * @package Mail
 */
class Mail implements Core_ModuleInterface {
  const MODULE  = 'Mail';
  const VERSION = '0.2.0';


/**
 * Инициализация
 * 
 */
  static public function initialize() {
    Core::load('Mail.Message', 'Mail.Serialize', 'Mail.Transport');
  }



/**
 * Фабричный метод, возвращает объект класса Mail.Message.Message
 * 
 * @return Mail_Message_Message
 */
  static public function Message() { return new Mail_Message_Message(); }

/**
 * Фабричный метод, возвращает объект класса Mail.Message.Part
 * 
 * @return Mail_Message_Part
 */
  static public function Part() { return new Mail_Message_Part(); }

/**
 * Фабричный метод, возвращает объект класса Mail.Message.Serializer
 * 
 * @param IO_Strem_AbstractStream $strem
 * @return Mail_Message_Serializer
 */
  static public function Encoder(IO_Stream_AbstractStream $stream = null) {
    return new Mail_Serialize_Encoder($stream);
  }

}

/**
 * Класс исключения
 * 
 * @package Mail
 */
class Mail_Exception extends Core_Exception {}

