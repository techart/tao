<?php
/**
 * Mail.Transport.PHP
 * 
 * Модуль для отправки сообщения с помощью стандартных средств php
 * 
 * @package Mail\Transport\PHP
 * @version 0.2.0
 */
Core::load('Mail.Transport');

/**
 * @package Mail\Transport\PHP
 */
class Mail_Transport_PHP implements Core_ModuleInterface {

  const VERSION = '0.2.0';
  const EXPORTS = 'Sender';


/**
 * Фабричный метод, возвращает объект класса Mail.Transport.PHP.Sender
 * 
 * @return Mail_Transport_PHP_Sender
 */
  static public function Sender() { return new Mail_Transport_PHP_Sender(); }

}

/**
 * Отправляет сообщение с помощью функции mail
 * 
 * @package Mail\Transport\PHP
 */
class Mail_Transport_PHP_Sender extends Mail_Transport_AbstractSender {

/**
 * Отправляет сообщение
 * 
 * @param Mail_Message_Message $message
 * @return boolean
 */
  public function send(Mail_Message_Message $message) {
    $encoder = Mail_Serialize::Encoder();
    return mail(
      preg_replace('{^To:\s*}', '', $message->head['To']->encode()),
      preg_replace('{^Subject:\s*}', '', $message->head['Subject']->encode()),
      $encoder->to_string()->encode_body($message),
      $encoder->to_string()->encode_head(
        $message,
        array('To' => false, 'Subject' => false)));
  }

}

