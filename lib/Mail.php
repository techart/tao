<?php
/// <module name="Mail" version="0.2.0" maintainer="timokhin@techart.ru">
///   <brief>Модуль для работы с почтовыми письмами</brief>
///     <details>
///       В модуль входят классы для формирования, кодирования и отправки сообщений
///     </details>
/// <class name="Mail" stereotype="module">
///   <implements interface="Core.ModuleInterface" />
class Mail implements Core_ModuleInterface {
///   <constants>
  const MODULE  = 'Mail';
  const VERSION = '0.2.0';
///   </constants>

///   <protocol name="creating">

///   <method name="initialize" scope="class">
///     <brief>Инициализация</brief>
///     <details>
///       Подгружаются модули Mail.Message, Mail.Serialize, Mail.Transport
///     </details>
///     <body>
  static public function initialize() {
    Core::load('Mail.Message', 'Mail.Serialize', 'Mail.Transport');
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="building">

///   <method name="Message" returns="Mail.Message.Message" scope="class">
///     <brief>Фабричный метод, возвращает объект класса Mail.Message.Message</brief>
///     <body>
  static public function Message() { return new Mail_Message_Message(); }
///     </body>
///   </method>

///   <method name="Part" returns="Mail.Message.Part" scope="class">
///     <brief>Фабричный метод, возвращает объект класса Mail.Message.Part</brief>
///     <body>
  static public function Part() { return new Mail_Message_Part(); }
///     </body>
///   </method>

///   <method name="Encoder" returns="Mail.Message.Serializer" scope="class">
///     <brief>Фабричный метод, возвращает объект класса Mail.Message.Serializer</brief>
///     <args>
///       <arg name="strem" type="IO.Strem.AbstractStream" default="brief" brief="поток" />
///     </args>
///     <body>
  static public function Encoder(IO_Stream_AbstractStream $stream = null) {
    return new Mail_Serialize_Encoder($stream);
  }
///     </body>
///   </method>

///   </protocol>
}
/// </class>

/// <class name="Mail.Exception" extends="Core.Exception">
///     <brief>Класс исключения</brief>
class Mail_Exception extends Core_Exception {}
/// </class>

/// </module>
