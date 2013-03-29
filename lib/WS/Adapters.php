<?php
/// <module name="WS.Adapters" version="0.2.0" maintainer="timokhin@techart.ru">

/// <class name="WS.Adapters" stereotype="module">
///   <implements interface="Core.ModuleInterface" />
class WS_Adapters implements Core_ModuleInterface {

///   <constants>
  const VERSION = '0.2.0';
///   </constants>

///   <protocol name="building">

///   <method name="apache" scope="class" returns="WS.Adapters.Apache.Adapter">
///     <body>
  static public function apache() {
    Core::load('WS.Adapters.Apache');
    return new WS_Adapters_Apache_Adapter();
  }
///     </body>
///   </method>

///   </protocol>
}
/// </class>

/// </module>
