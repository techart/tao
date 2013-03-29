<?php
/// <module name="Mail.Transport.Sendmail" version="0.2.1" maintainer="timokhin@techart.ru">
///   <brief>Модуль для отправки сообщения с помощью стандартной утилиты sendmail</brief>

Core::load('Mail.Transport');

/// <class name="Mail.Transport.Sendmail" stereotype="module">
///   <implements interface="Core.ConfigurableModuleInterface" />
///   <depends supplier="Mail.Transport.Sendmail.Sender" stereotype="creates" />
class Mail_Transport_Sendmail implements Core_ConfigurableModuleInterface {

///   <constants>
  const MODULE  = 'Mail.Transport.Sendmail';
  const VERSION = '0.2.1';
///   </constants>

  static protected $options = array(
    'binary' => 'sendmail',
    'flags'  => '-t -i');

///   <protocol name="creating">

///   <method name="initialize" scope="class">
///     <brief>Инициализация модуля</brief>
///     <args>
///       <arg name="options" type="array" default="array()" brief="массив опций" />
///     </args>
///     <body>
  static public function initialize(array $options = array()) { self::options($options); }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="configuring">

///   <method name="options" returns="mixed" scope="class">
///     <brief>Устанавливает опции из массива $options</brief>
///     <args>
///       <arg name="options" type="array" default="array()" brief="массив опций" />
///     </args>
///     <body>
  static public function options(array $options = array()) {
    if (count($options)) Core_Arrays::update(self::$options, $options);
    return self::$options;
  }
///     </body>
///   </method>

///   <method name="option" returns="mixed">
///     <brief>Устанавливает опцию модуля</brief>
///     <args>
///       <arg name="name" type="string" brief="имя опции" />
///       <arg name="value" default="null" brief="значение опции" />
///     </args>
///     <body>
  static public function option($name, $value = null) {
    $prev = isset(self::$options[$name]) ? self::$options[$name] : null;
    if ($value !== null) self::options(array($name => $value));
    return $prev;
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="building">

///   <method name="Sender" returns="Mail.Transport.Sendmail.Sender" scope="class">
///     <brief>Фабричный метод, возвращает объект класса Mail.Transport.Sendmail.Sender</brief>
///     <args>
///       <arg name="options" type="array" default="array()" brief="массив настроек" />
///     </args>
///     <body>
  static public function Sender(array $options = array()) { return new Mail_Transport_Sendmail_Sender($options); }
///     </body>
///   </method>

///   </protocol>
}
/// </class>


/// <class name="Mail.Transport.Sendmail.Sender" extends="Mail.Transport.AbstractSender">
///   <depends supplier="Proc.Pipe" stereotype="uses" />
///   <depends supplier="Mail.Serialize.Encoder" stereotype="uses" />
class Mail_Transport_Sendmail_Sender extends Mail_Transport_AbstractSender {

  protected $options;

///   <protocol name="creating">

///   <method name="__construct">
///     <brief>Конструктор</brief>
///     <args>
///       <arg name="options" type="array" default="array()" brief="массив опций" />
///     </args>
///     <body>
  public function __construct(array $options = array()) {
    $this->options = Mail_Transport_Sendmail::options();
    Core_Arrays::update($this->options, $options);
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="processing">

///   <method name="send" returns="boolean">
///     <brief>Отправляет сообщение</brief>
///     <args>
///       <arg name="message" type="Mail.Message.Message" brief="сообщение" />
///     </args>
///     <body>
  public function send(Mail_Message_Message $message) {
    $pipe = Proc::Pipe($this->sendmail_command(), 'wb');

    Mail_Serialize::Encoder()->
     to_stream($pipe)->
     encode($message);

    return $pipe->close()->exit_status ? false : true;
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="supporting">

///   <method name="sendmail_command" returns="string" scope="class">
///     <brief>Возвращает команду для вызова sendmail</brief>
///     <body>
  protected function sendmail_command() {
    return $this->options['binary'].' '.$this->options['flags'];
  }
///     </body>
///   </method>

///   </protocol>
}
/// </class>

/// </module>
