<?php
/// <module name="Service.OpemSocial.Formats.JSON">

/// <class name="Service.OpenSocial.Formats.JSON" stereotype="module">
///   <implements interface="Service.OpenSocial.ModuleInterface" />
///   <depends supplier="Service.OpenSocial.Formats.JSON.Format" stereotype="creates" />
class Service_OpenSocial_Formats_JSON implements Service_OpenSocial_ModuleInterface {

///   <protocol name="building">

///   <method name="Format" returns="Service.OpenSocial.JSON.Format" scope="class">
///     <body>
  static public function Format() { return new Service_OpenSocial_Formats_JSON_Format(); }
///     </body>
///   </method>

///   </protocol>
}
/// </class>

/// <class name="Service.OpenSocial.Formats.JSON.Format" extends="Service.OpenSocial.Format">
class Service_OpenSocial_Formats_JSON_Format extends Service_OpenSocial_Format {

///   <protocol name="creating">

///   <method name="__construct">
///     <args>
///       <arg name="name" type="string" />
///       <arg name="content_type" type="string" />
///     </args>
///     <body>
  public function __construct() { parent::__construct('json', 'application/json'); }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="processing">

///   <method name="encode" returns="string" stereotype="abstract">
///     <args>
///       <arg name="object" />
///     </args>
///     <body>
  public function encode($object) { return json_encode($object); }
///     </body>
///   </method>

///   <method name="decode" returns="object" stereotype="abstract">
///     <args>
///       <arg name="string" type="string" />
///     </args>
///     <body>
  public function decode($string) { return json_decode($string); }
///     </body>
///   </method>

///   </protocol>
}
/// </class>

/// </module>
