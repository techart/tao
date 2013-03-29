<?php
/// <module name="Mail.Transport" version="0.2..0" maintainer="timokhin@techart.ru">
///   <brief>Модуль предоставляет классы для отправки сообщений</brief>

Core::load('Mail', 'Proc');

/// <class name="Mail.Transport" stereotype="module">
///   <implements interface="Core.ModuleInterface" />
///   <depends supplier="Mail.Transport.Sendmail" stereotype="loads" />
///   <depends supplier="Mail.Transport.Sendmail.Sender" stereotype="creates" />
class Mail_Transport implements Core_ModuleInterface {

///   <constants>
  const MODULE  = 'Mail.Transport';
  const VERSION = '0.2.0';
///   </constants>

///   <protocol name="building">

///   <method name="sendmail" returns="Mail.Transport.Sendmail.Sender">
///     <brief>Фабричный метод, возвращает объект класса Mail.Transport.Sendmail.Sender</brief>
///     <args>
///       <arg name="options" type="array" default="array()" brief="массив настроек" />
///     </args>
///     <body>
  static public function sendmail(array $options = array()) {
    Core::load('Mail.Transport.Sendmail');
    return new Mail_Transport_Sendmail_Sender($options);
  }
///     </body>
///   </method>

///   <method name="php" returns="Mail.Transport.PHP.Sender">
///     <brief>Фабричный метод, возвращает объект класса Mail.Transport.PHP.Sender</brief>
///     <body>
  static public function php() {
    Core::load('Mail.Transport.PHP');
    return new Mail_Transport_PHP_Sender();
  }
///     </body>
///   </method>

///   </protocol>
}
/// </class>


/// <class name="Mail.Transport.Exception" extends="Mail.Exception">
///   <brief>Класс исключения</brief>
class Mail_Transport_Exception extends Mail_Exception {}
/// </class>


/// <class name="Mail.Transport.AbstractSender" stereotype="abstract">
///   <brief>Абстрактный класс для отправки сообщения</brief>
//TODO: А почему не интерфейс?
abstract class Mail_Transport_AbstractSender {

///   <protocol name="processing">

///   <method name="send" returns="boolean">
///     <brief>Отправляет сообщение</brief>
///     <args>
///       <arg name="msg" type="Mail.Message.Message" brief="сообщение" />
///     </args>
///     <body>
  abstract public function send(Mail_Message_Message $msg);
///     </body>
///   </method>

///   </protocol>
}
/// </class>

/// </module>
