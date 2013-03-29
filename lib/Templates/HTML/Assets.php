<?php
/// <module name="WebKit.Helpers.Assets" version="1.0.2" maintainer="timokhin@techart.ru">
Core::load('Templates.HTML', 'Templates.HTML.Tags');

/// <class name="WebKit.Helpers.Assets" stereotype="module">
///   <implements interface="Core.ModuleInterface" />
///   <implements interface="WebKit.Views.HelperInterface" />
class Templates_HTML_Assets
  implements Core_ModuleInterface,
  Templates_HelperInterface {

///   <constants>
  const VERSION = '1.0.2';
///   </constants>

///   <method name="initialize" scope="class">
///     <body>
  static public function initialize() {
    Templates_HTML::use_helper('tags', 'Templates.HTML.Tags');
    Templates_HTML::use_helper('assets', 'Templates.HTML.Assets');
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="generating">


///   <method name="image_tag" returns="string">
///     <args>
///       <arg name="url" type="string" />
///       <arg name="attributes" type="array" default="array()" />
///     </args>
///     <body>
  public function image_tag($t, $url, array $attributes = array()) {
    return $t->tags->tag('img',
      Core_Arrays::merge($attributes, array(
        'src' => $this->image_path_for($url))));
  }
///     </body>
///   </method>

///   <method name="auto_discovery_link_tag" returns="string">
///     <args>
///       <arg name="type" type="string" />
///       <arg name="url" type="string" />
///       <arg name="options" type="array" default="array()" />
///     </args>
///     <body>
  public function auto_discovery_link_tag($t, $type, $url, array $options = array()) {
    return $t->tags->tag('link', array(
      'rel'   => $options['rel']   ? $options['rel']   : 'alternate',
      'type'  => $options['type']  ? $options['type']  : "application/$type+xml",
      'title' => $options['title'] ? $options['title'] : Core_Strings::upcase($type),
      'href'  => $url ))."\n";
  }
///     </body>
///   </method>

///   <method name="stylesheet_link_tag" returns="string" varargs="true">
///     <body>
  public function stylesheet_link_tag($t) {
    $result = '';
    foreach (($args = Core_Types::is_array(func_get_arg(1)) ? func_get_arg(1) : array_slice(func_get_args(), 1)) as $src)
      $result .= $t->tags->tag('link', array(
        'rel'   => 'stylesheet',
        'type'  => 'text/css',
        'media' => 'screen',
        'href'  => $this->stylesheet_path_for($t, $src)))."\n";
    return $result;
  }
///     </body>
///   </method>


///   <method name="javascript_include_tag" returns="string" varargs="true">
///     <body>
  public function javascript_include_tag($t) {
    $result = '';

    foreach (($args = Core_Types::is_array(func_get_arg(1)) ? func_get_arg(1) : array_slice(func_get_args(), 1)) as $src)
      $result .= $t->tags->content_tag('script', '', array(
        'type' => 'text/javascript',
        'src'  => $this->javascript_path_for($t, $src)))."\n";

    return $result;
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="quiering">

///   <method name="image_path_for" returns="string">
///     <args>
///       <arg name="src" type="string" />
///     </args>
///     <body>
  public function image_path_for($t, $src) {
    return $this->compute_public_path($src, 'images', '.png');
  }
///     </body>
///   </method>

///   <method name="javascript_path_for" returns="string">
///     <args>
///       <arg name="src" type="string" />
///     </args>
///     <body>
  public function javascript_path_for($t, $src) {
    return $this->compute_public_path($src, 'scripts', '.js', true);
  }
///     </body>
///   </method>

///   <method name="stylesheet_path_for" returns="string">
///     <args>
///       <arg name="src" type="string" />
///     </args>
///     <body>
  public function stylesheet_path_for($t, $src) {
    return $this->compute_public_path($src, 'styles', '.css', true);
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="supporting">

///   <method name="compute_public_path" returns="string" access="protected">
///     <args>
///       <arg name="asset" />
///       <arg name="dir" />
///       <arg name="extension" />
///       <arg name="timestamp" type="int" default="false" />
///     </args>
///     <body>
  protected function compute_public_path($asset, $dir, $extension, $timestamp = false) {
    if ($asset[0] == '/' || strstr($asset, "://")) return $asset;
    $asset .= (preg_match('{(?:gif|png|jpg|js|css)$}', $asset) ? '' : $extension);
    return "/$dir/$asset".($timestamp ? '?'.IO_FS::Stat("$dir/$asset")->mtime->timestamp : '');
  }
///   </body>
///   </method>

///   </protocol>
}
///   </class>

/// </module>
