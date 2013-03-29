<?php
/// <module name="WebKit.Helpers.Tags" version="1.0.0" maintainer="timokhin@techart.ru">
Core::load('Templates.HTML');

/// <class name="WebKit.Helpers.Tags" stereotype="module">
///   <implements interface="Core.ModuleInterface" />
///   <implements interface="WebKit.Views.HelperInterface" />
class Templates_HTML_Tags implements Core_ModuleInterface, Templates_HelperInterface {

///   <constants>
  const VERSION = '1.0.0';
///   </constants>

///   <protocol name="generating">

///   <method name="tag" returns="string">
///     <args>
///       <arg name="name" type="string" />
///       <arg name="attributes" type="array" default="array()" />
///       <arg name="close" type="boolean" default="true" />
///     </args>
///     <body>
  public function tag($t, $name, array $attributes = array(), $close = true) {
    $tag = '<'.((string) $name);

    foreach ($attributes as $k => $v)
      if (!is_array($v)) $tag .= ($v === true ? " $k " : ( $v === false ? '' :  " $k=\"".htmlspecialchars($v).'"'));
    return $tag .= (boolean) $close ? ' />' : '>';
  }
///     </body>
///   </method>

///   <method name="content_tag" returns="string">
///     <args>
///       <arg name="name" type="string" />
///       <arg name="content" type="string" />
///       <arg name="close" type="boolean" default="true" />
///     </args>
///     <body>
  public function content_tag($t, $name, $content, array $attributes = array()) {
    $tag = '<'.((string) $name);
    foreach ($attributes as $k => $v)
      if (!is_array($v))
        $tag .= ($v === true ? " $k " : ( $v === false ? '' :  " $k=\"".htmlspecialchars($v).'"'));

    return $tag .= '>'.((string) $content).'</'.((string) $name.'>');
  }
///     </body>
///   </method>

///   <method name="cdata_section" returns="string">
///     <args>
///       <arg name="content" type="string" />
///     </args>
///     <body>
  public function cdata_section($t, $content) {
    return '<![CDATA['.((string) $content).']'.']>';
  }
///     </body>
///   </method>

///   </protocol>
}
/// </class>

/// </module>
