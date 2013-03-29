<?php
/// <module name="Digest" version="0.2.0" maintainer="timokhin@techart.ru">
///   <brief>Объектная обертка над php функциями md5, sha1, crypt </brief>

/// <class name="Digest">
///   <implements interface="Core.ModuleInterface" />
class Digest implements Core_ModuleInterface {
///   <constants>
  const MODULE  = 'Digest';
  const VERSION = '0.2.0';
///   </constants>

///   <protocol name="performing">

///   <method name="crypt">
///     <brief>Выполняет одноименную php функцию</brief>
///     <args>
///       <arg name="string" type="string" brief="строка" />
///       <arg name="salt" type="salt" default="null" brief="последовательность, на которой основывается шифрование" />
///     </args>
///     <body>
  static public function crypt($string, $salt = null) { return crypt($string, $salt); }
///     </body>
///   </method>

///   </protocol>
}
/// </class>

/// <class name="Digest.MD5">
///   <brief>MD5 шифрование</brief>
class Digest_MD5 {
///   <protocol name="calculating">

///   <method name="digest" returns="int" scope="class">
///     <brief>MD5 кодирование, возвращается бинарная строка из 16 символов.</brief>
///     <args>
///       <arg name="string" type="string" brief="строка кодирования" />
///     </args>
///     <body>
  static public function digest($string) { return md5($string, true); }
///     </body>
///   </method>

///   <method name="hexdigest" returns="int" scope="class">
///     <brief>MD5 кодирование, возвращается 32-значное шестнадцатеричное число</brief>
///     <args>
///       <arg name="string" type="string" brief="строка кодирования" />
///     </args>
///     <body>
  static public function hexdigest($string) { return md5($string, false); }
///     </body>
///   </method>

///   </protocol>
}
/// </class>

/// <class name="Digest.SHA1">
///   <brief>SHA1 кодирование</brief>
class Digest_SHA1 {
///   <protocol name="calculating">

///   <method name="digest" returns="int" scope="class">
///     <brief>SHA1 кодирование, возвращается бинарная строка из 16 символов</brief>
///     <args>
///       <arg name="string" type="string" brief="строка кодирования" />
///     </args>
///     <body>
  static public function digest($string) { return sha1($string, true); }
///     </body>
///   </method>

///   <method name="hexdigest" returns="string" scope="class">
///     <brief>SHA1 кодирование, 40-разрядное шестнадцатиричное число</brief>
///     <args>
///       <arg name="string" type="string" brief="строка кодирования" />
///     </args>
///     <body>
  static public function hexdigest($string) { return sha1($string, false); }
///     </body>
///   </method>

///   </protocol>
}
/// </class>

/// </module>
