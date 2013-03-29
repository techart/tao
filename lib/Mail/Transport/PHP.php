<?php
/// <module name="Mail.Transport.PHP" version="0.2.0" maintainer="timokhin@techart.ru">
///   <brief>Модуль для отправки сообщения с помощью стандартных средств php</brief>
Core::load('Mail.Transport');

/// <class name="Mail.Transport.PHP" stereotype="module">
class Mail_Transport_PHP implements Core_ModuleInterface {

  const VERSION = '0.2.0';
  const EXPORTS = 'Sender';

///   <protocol name="building">

///   <method name="Sender" returns="Mail.Transport.PHP.Sender" scope="class">
///     <brief>Фабричный метод, возвращает объект класса Mail.Transport.PHP.Sender</brief>
///     <body>
  static public function Sender() { return new Mail_Transport_PHP_Sender(); }
///     </body>
///   </method>

///   </protocol>
}
/// </class>

/// <class name="Mail.Transport.PHP.Sender" extends="Mail.Transport.AbstractSender">
///   <brief>Отправляет сообщение с помощью функции mail</brief>
class Mail_Transport_PHP_Sender extends Mail_Transport_AbstractSender {
///   <protocol name="processing">

///   <method name="send" returns="boolean">
///     <brief>Отправляет сообщение</brief>
///     <args>
///       <arg name="message" type="Mail.Message.Message" brief="сообщение" />
///     </args>
///     <body>
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
///     </body>
///   </method>

///   </protocol>
}
/// </class>

/// </module>
