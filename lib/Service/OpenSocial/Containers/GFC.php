<?php
/// <module name="Service.OpenSocial.Containers.GFC" version="0.1.0" maintainer="svistunov@techart.ru">

/// <class name="Service.OpenSocial.Containers.GFC>
///   <implements interface="Service.OpenSocial.ModuleInterface" />
class Service_OpenSocial_Containers_GFC implements Service_OpenSocial_ModuleInterface {

///   <protocol name="building">

///   <method name="container" returns="Service.OpenSocial.Container" scope="class">
///     <body>
  static public function container() {
    return Service_OpenSocial::Container(array(
      'name'          => 'Google',
      'rest_endpoint' => 'http://www.google.com/friendconnect/api',
      'rpc_endpoint'  => 'http://www.google.com/friendconnect/api/rpc'));
  }
///     </body>
///   </method>

///   <method name="sandbox" returns="Service.OpenSocial.Container" scope="class">
///     <body>
  static public function sandbox() {
    return Service_OpenSocial::Container(array(
      'name'          => 'Google',
      'rest_endpoint' => 'http://www.google.com/friendconnect/api',
      'rpc_endpoint'  => 'http://www.google.com/friendconnect/api/rpc'));
  }
///     </body>
///   </method>

///   </protocol>
}
/// </class>

/// </module>
