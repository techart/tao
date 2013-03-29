<?php
/// <module name="Dev.Source.Doc" maintainer="svistunov@techart.ru" version="0.1.0">
Core::load('XML','CLI.Application', 'Proc', 'Dev.Source', 'Object');
/// <class name="Dev.Source.Doc" stereotype="module">
///   <implements interface="Core.ModuleInterface" />
///   <implements interface="CLI.RunInterface" />
class Dev_Source_Doc implements Core_ModuleInterface, CLI_RunInterface {
///   <constants>
  const VERSION = '0.1.0';
///   </constants>

///   <protocol name="performing">
///   <method name="main">
///     <args>
///       <arg name="argv" type="array" />
///     </args>
///     <body>
  static public function main(array $argv) { Core::with(new Dev_Source_Doc_Application())->main($argv); }
///     </body>
///   </method>
///   </protocol>
}
/// </class>

/// <class name="Dev.Source.Doc.LibraryDirGenerator">
class Dev_Source_Doc_LibraryDirGenerator {
  protected $path_to_library;
  protected $path_to_html;
  protected $toc;

///   <protocol name="creating">
///   <method name="__construct">
///     <args>
///       <arg name="path_to_library" type="string" />
///       <arg name="path_to_html" type="string" />
///     </args>
///     <body>
  public function __construct($path_to_library, $path_to_html) {
    $this->path_to_library = rtrim((string) $path_to_library, '/');
    $this->path_to_html = rtrim((string) $path_to_html, '/');

    $this->toc = array();
  }
///     </body>
///   </method>
///   </protocol>

///   <protocol name="supporting">

///   <method name="generate">
///     <body>
  public function generate() {
    foreach (new Dev_Source_LibraryDirIterator($this->path_to_library) as $module_name => $module) {
      if (Core_Strings::contains($module_name, '.')) {
        $file_place = $this->path_to_html."/".Core_Regexps::replace('{/\w+$}', '', Core_Strings::replace($module_name, '.', '/'));
        if (!IO_FS::exists($file_place))
          IO_FS::mkdir($file_place , 0775, true);
      }
      try {
        $module_generator = new Dev_Source_Doc_ModuleGenerator(
          $module, $this->path_to_html."/".Core_Strings::replace($module_name, '.', '/'));
        $module_generator->write();
        $module_generator->write_diagram();
        $this->add_to_toc($module_generator);
      } catch (Dev_Source_InvalidSourceException$e) {
      }
    }
    $this->write_css();
    $this->write_toc();
    $this->write_index();
  }
///     </body>
///   </method>

///   <method name="write_toc" access="protected">
///     <body>
  protected function write_toc() {
    $toc_dom = XML::Builder();

    $modules_dom = $toc_dom->begin_html()->begin_head()->title('Library Documentation')->
      link(array('rel'=> 'stylesheet', 'type'=> 'text/css', 'href' => 'style.css'))->end->
      begin_body()->begin_ul(array('class' => 'modules'));
    ksort($this->toc);

    foreach ($this->toc as $module_name => $module) {
      $module_dom = $modules_dom->begin_li()->a(array($module_name,
        'href' => $module['href'], 'target' => 'content'));
      if ($module['interfaces'] != null) {
        $interfaces_dom = $module_dom->begin_ul(array('class' => 'interfaces'));
          ksort($module['interfaces']);
        foreach ($module['interfaces'] as $interface_name => $interface_ref)
          $interfaces_dom->begin_li()->a(array($interface_name, 'href' => $interface_ref, 'target' => 'content'));
      }
      $classes_dom = $module_dom->begin_ul(array('class' => 'classes'));
        ksort($module['classes']);
      foreach ($module['classes'] as $class_name => $class_ref)
        $classes_dom->begin_li()->a(array($class_name, 'href' => $class_ref, 'target' => 'content'));
    }

    IO_FS::File($this->path_to_html."/toc.html")->open('w+')->
      write($toc_dom->document->saveHTML())->close();
  }
///     </body>
///   </method>

///   <method name="add_to_toc" access="protected">
///     <args>
///       <arg name="module_generator" type="Dev.Source.Doc.ModuleGenerator" />
///     </args>
///     <body>
  protected function add_to_toc(Dev_Source_Doc_ModuleGenerator $module_generator) {
    $this->toc[$module_generator->module->name] = array('href' => $module_generator->ref,
      'classes' => $module_generator->classes, 'interfaces' => $module_generator->interfaces);
  }
///     </body>
///   </method>

///   <method name="write_css" access="protected">
///     <body>
  protected function write_css() {
    IO_FS::File($this->path_to_html."/style.css")->
      open('w+')->write(self::css())->close();
  }
///     </body>
///   </method>

///   <method name="write_index">
///     <body>
  protected function write_index() {
    IO_FS::File($this->path_to_html."/index.html")->
      open('w+')->write(self::index_html())->close();
  }
///     </body>
///   </method>

/// <ignore>
  public static function css() {
    return <<<CSS
    --body { color: red;}
CSS;

  }

  public static function index_html() {
    return <<<HTML
<!DOCTYPE html
     PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
     "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">

<html xmlns="http://www.w3.org/1999/xhtml">
<head>
  <title>TAO Documentation</title>
  <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
</head>

<frameset cols="20%,*">
  <frame src="toc.html" title="Navigation" name="toc" />
  <frame  src="Core.html" name="content">
</frameset>

</html>

HTML;
  }
/// </ignore>

///   </protocol>

}
/// </class>

/// <class name="Dev.Source.Doc.ModuleGenerator" extends="Dtat.Object">
class Dev_Source_Doc_ModuleGenerator  extends Object_Struct {
  protected $path;
  protected $module;
  protected $ref;
  protected $classes;
  protected $interfaces;
  static $listeners;

///   <protocol name="creating">
///   <method name="__construct">
///     <args>
///       <arg name="module" type="Dev.Source.Module" />
///       <arg name="path" type="path" />
///     </args>
///     <body>
  public function __construct(Dev_Source_Module $module, $path) {
    $this->module = $module;
    $this->path = $path;
    $this->ref = Core_Strings::replace($this->module->name, '.', '/').".html";
  }
///     </body>
///   </method>
///   </protocol>

///   <protocol name="supporting">

///   <method name="listener" returns="Dev.Source.Doc.ModuleGenerator">
///     <args>
///       <arg name="listener" type="Dev.Source.Doc.ModuleGeneratorListener" />
///     </args>
///     <body>
  static function listener(Dev_Source_Doc_ModuleGeneratorListener $listener) {
    if (!isset(self::$listeners)) self::$listeners = Object::Listener();
    self::$listeners->append($listener);
  }
///     </body>
///   </method>

///   <method name="get_interfaces" access="protected" returns="mixed">
///     <body>
  protected function get_interfaces() {
    if ($this->interfaces != null) return $this->interfaces;
    foreach ($this->module->xml->getElementsByTagName('interface') as $k => $v) {
      $name = $v->getAttribute('name');
      $this->interfaces[$name] = $this->ref.'#i-'.Core_Strings::replace($name, '.', '-');
    }
    return $this->interfaces;
  }
///     </body>
///   </method>

///   <method name="get_classes" returns="mixed" access="protected">
///     <body>
  protected function get_classes() {
    if ($this->classes != null) return $this->classes;
    $this->classes = array();
    foreach ($this->module->xml->getElementsByTagName('class') as $k => $v) {
      $name = $v->getAttribute('name');
      $this->classes[$name] = $this->ref.'#c-'.Core_Strings::replace($name, '.', '-');
    }
    return $this->classes;
  }
///     </body>
///   </method>

///   <method name="write" returns="Dev.Source.Doc.Generator">
///     <body>
  public function write() {
    $xslt = new XSLTProcessor();
    $xslt->registerPHPFunctions();
    $xslt->importStylesheet(DOMDocument::loadXML(Dev_Source_Doc_ModuleGenerator::xslt()));
    $stream = IO_FS::File($this->path.".html")->open('w+');
    $stream->write($xslt->transformToXML($this->module->xml));
    $stream->close();
    if (isset(self::$listeners)) self::$listeners->on_write($this);
    return $this;
  }
///     </body>
///   </method>

///   <method name="write_diagram">
///     <body>
  public function write_diagram() {
    exec("bin/tao-source-diagram -o{$this->path}.png -Tpng {$this->module->name}");
  }
///     </body>
///   </method>

///   <method name="generate_imagemap" returns="string">
///     <body>
  static public function generate_imagemap($module_name) {
    exec("bin/tao-source-diagram -Tcmap {$module_name}", $ouput);
    $res = '';
    foreach ($ouput as $v)
      $res .= $v."\n";
    return $res;
  }
///     </body>
///   </method>

/// <ignore>
  static function xslt() {
    return <<<XSL

<xsl:stylesheet version="1.0"
  xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
   xmlns:str="http://exslt.org/strings"
   extension-element-prefixes="str"
   xmlns:php="http://php.net/xsl">

  <xsl:output method="html"/>

  <xsl:template match="/">
    <xsl:apply-templates select="module" />
  </xsl:template>

  <xsl:template match="/module">
    <xsl:variable name="module_name" select="@name" />

<html>
  <head>
    <title><xsl:value-of select="\$module_name" /></title>
    <META HTTP-EQUIV="Content-Type" CONTENT="text/html; charset=UTF-8"/>
    <link rel="stylesheet" type="text/css" >
      <xsl:attribute name="href">
        <xsl:for-each select="str:split(string(\$module_name), '.')" >
          <xsl:if test="not(position()=last())">
            <xsl:text>../</xsl:text>
          </xsl:if>
          <xsl:if test="position()=last()">
            <xsl:text>style.css</xsl:text>
          </xsl:if>
        </xsl:for-each>
      </xsl:attribute>
    </link>
  </head>
  <body>
    <div class="module">
      <h1><xsl:value-of select="\$module_name" /></h1>
      <xsl:if test="brief"><p class="brief"><xsl:value-of select="string(brief)" /></p></xsl:if>
      <xsl:if test="details">
      <div class="details">
        <p><xsl:value-of select="details" disable-output-escaping="yes" /></p>
      </div>
      </xsl:if>

      <ul class="interfaces">
        <xsl:for-each select="interface">
        <xsl:sort data-type="text" select="@name" />
          <li>
            <a><xsl:attribute name="href"><xsl:value-of select="concat('#i-', translate(@name, '.' , '-'))" /></xsl:attribute>
              <xsl:value-of select="@name" /></a>
          </li>
        </xsl:for-each>
      </ul>
      <ul class="classes">

        <xsl:for-each select="class">
        <xsl:sort data-type="text" select="@name" />
          <li>
            <a><xsl:attribute name="href"><xsl:value-of select="concat('#c-', translate(@name, '.' , '-'))" /></xsl:attribute>
              <xsl:value-of select="@name" /></a>
          </li>
        </xsl:for-each>

      </ul>

      <xsl:variable name="map_name" select="concat('map-', \$module_name)" />
      <map>
        <xsl:attribute name="name"><xsl:value-of select="\$map_name" /></xsl:attribute>
        <xsl:value-of disable-output-escaping="yes" select="php:functionString('Dev_Source_Doc_ModuleGenerator::generate_imagemap', \$module_name)" />
      </map>

      <img border="0">
        <xsl:attribute name="src" ><xsl:value-of select="concat(str:split(\$module_name, '.')[last()], '.png')" /></xsl:attribute>
        <xsl:attribute name="usemap"><xsl:value-of select="concat('#', \$map_name)" /></xsl:attribute>
      </img>

      <xsl:apply-templates select="interface" >
      <xsl:sort data-type="text" select="@name" />
        <xsl:with-param name="module_name" select="\$module_name" />
      </xsl:apply-templates>

      <xsl:apply-templates select="class" >
      <xsl:sort data-type="text" select="@name" />
        <xsl:with-param name="module_name" select="\$module_name" />
      </xsl:apply-templates>

    </div>
  </body>
  </html>
  </xsl:template>

  <xsl:template match="class">
    <xsl:param name="module_name" />
    <xsl:call-template name="class">
      <xsl:with-param name="module_name" select="\$module_name" />
      <xsl:with-param name="type">class</xsl:with-param>
    </xsl:call-template>
  </xsl:template>

  <xsl:template match="interface">
    <xsl:param name="module_name" />
    <xsl:call-template name="class">
      <xsl:with-param name="module_name" select="\$module_name" />
      <xsl:with-param name="type">interface</xsl:with-param>
    </xsl:call-template>
  </xsl:template>

  <xsl:template name="class">
    <xsl:param name="module_name" />
    <xsl:param name="type" />
    <xsl:variable name="class_name" select="@name" />
    <xsl:variable name="translate_class_name" select="translate(\$class_name, '.' , '-')" />

      <div>
      <xsl:attribute name="class"><xsl:value-of select="\$type" /></xsl:attribute>
      <xsl:attribute name="id"><xsl:value-of select="concat(substring(\$type,1,1), '-', \$translate_class_name)" /></xsl:attribute>
      <h2><xsl:value-of select="\$class_name" /></h2>
      <xsl:if test="brief"><p class="brief"><xsl:value-of select="string(brief)" /></p></xsl:if>
      <xsl:if test="details">
      <div class="details">
        <p><xsl:value-of select="details" disable-output-escaping="yes" /></p>
      </div>
      </xsl:if>

      <ul class="protocols">
        <xsl:for-each select="protocol">
          <li>
            <a>
              <xsl:attribute name="href">
               <xsl:value-of select="concat('#p-', \$translate_class_name, '-', translate(@name, '.', '-'))" />
              </xsl:attribute>
              <xsl:value-of select="@name" />
              <ul class="methods">
              <xsl:for-each select="method">
                 <li>
                   <a>
                     <xsl:attribute name="href">
                       <xsl:value-of select="concat('#m-', \$translate_class_name, '-', translate(@name, '.', '-'))" />
                     </xsl:attribute>
                   <xsl:call-template name="method_name" /><!--
                --></a>
                 </li>
              </xsl:for-each>
              </ul>
            </a>
          </li>
        </xsl:for-each>
      </ul>
      <xsl:if test="depends">
      <ul class="dependencies">
        <xsl:apply-templates select="depends" >
          <xsl:with-param name="module_name" select="\$module_name" />
        </xsl:apply-templates>
      </ul>
      </xsl:if>

      <xsl:apply-templates select="protocol" >
        <xsl:with-param name="class_name" select="\$class_name" />
      </xsl:apply-templates>
    </div>
  </xsl:template>

  <xsl:template match="depends">
    <xsl:param name="module_name" />
    <xsl:variable name="supplier" select="@supplier" />
    <li>
      <xsl:value-of select="@stereotype" />
      <a>
      <xsl:attribute name="href">

          <xsl:call-template name="href">
            <xsl:with-param name="module_name" select="\$module_name" />
            <xsl:with-param name="supplier" select="\$supplier" />
          </xsl:call-template>

      </xsl:attribute>
      <xsl:value-of select="\$supplier" />
      </a>
    </li>
  </xsl:template>

  <xsl:template name="href">
    <xsl:param name="module_name" />
    <xsl:param name="supplier" />

    <xsl:if test="starts-with(\$supplier, \$module_name)">
      <xsl:value-of select="concat('#c-', translate(\$supplier, '.', '-'))" />
    </xsl:if>

    <xsl:if test="not(starts-with(\$supplier, \$module_name))">
    <xsl:for-each select="str:split(string(\$module_name), '.')" >
            <xsl:if test="not(position()=last())"><xsl:text>../</xsl:text></xsl:if>
          </xsl:for-each>
          <xsl:for-each select="str:split(string(\$supplier), '.')" >
            <xsl:choose>
              <xsl:when test="position()=last()">
                <xsl:value-of select="concat('#c-', translate(\$supplier, '.', '-'))" />
              </xsl:when>
              <xsl:when test="position()= (last()-1)">
                <xsl:value-of select="concat(string(.), '.html')" />
              </xsl:when>
              <xsl:otherwise>
                <xsl:value-of select="concat(string(.), '/')" />
              </xsl:otherwise>
            </xsl:choose>
          </xsl:for-each>
     </xsl:if>
  </xsl:template>

  <xsl:template match="protocol">
    <xsl:param name="class_name" />
    <xsl:variable name="protocol_name" select="@name" />
    <div class="protocol">
    <xsl:attribute name="id">
      <xsl:value-of select="concat('p-', translate(\$class_name, '.', '-'), '-', \$protocol_name)" />
    </xsl:attribute>
    <h3><xsl:value-of select="\$protocol_name" /></h3>

    <xsl:apply-templates select="method">
      <xsl:with-param name="class_name" select="\$class_name" />
    </xsl:apply-templates>

    </div>
  </xsl:template>

  <xsl:template match="method">
    <xsl:param name="class_name" />
    <xsl:variable name="method_name" select="@name" />
    <div class="method">
      <xsl:attribute name="id">
        <xsl:value-of select="concat('m-', translate(\$class_name, '.', '-'), '-', \$method_name)" />
      </xsl:attribute>
      <h4><xsl:call-template name="method_name" /></h4>

      <xsl:if test="brief"><p class="brief"><xsl:value-of select="string(brief)" /></p></xsl:if>

      <xsl:if test="args">
        <dl class="args">
        <xsl:apply-templates select="args//arg" />
        </dl>
      </xsl:if>

      <xsl:if test="details">
      <div class="details">
        <p><xsl:value-of select="string(details)" /></p>
      </div>
      </xsl:if>

      <div class="source">
        <pre><xsl:value-of select="string(body)" /></pre>
      </div>

    </div>
  </xsl:template>

  <xsl:template match="arg">
    <xsl:variable name="arg_name" select="@name" />
    <dt><xsl:value-of select="\$arg_name" /></dt>
    <xsl:if test="@type"><dd class="type"><xsl:value-of select="@type" /></dd></xsl:if>
    <xsl:if test="@default"><dd class="default"><xsl:value-of select="@default" /></dd></xsl:if>
    <xsl:if test="@info"><dd class="info"><xsl:value-of select="@info" /></dd></xsl:if>
  </xsl:template>

  <xsl:template name="method_name">
     <xsl:value-of select="@name" />(<!--
  --><xsl:for-each select="args/arg"><!--
    --><xsl:value-of select="@name" /><!--
      --><xsl:if test="position() &lt; last()"><!--
        --><xsl:text>, </xsl:text><!--
      --></xsl:if><!--
   --></xsl:for-each>)<!--
--></xsl:template>


</xsl:stylesheet>


XSL;

  }
/// </ignore>

///   </protocol>

}
/// </class>

/// <interface name="Dev.Source.Doc.ModuleGeneratorListener">
interface Dev_Source_Doc_ModuleGeneratorListener {
///   <protocol name="observing">

///   <method name="on_write">
///     <args>
///       <arg name="module_generator" type="Dev.Source.Doc.ModuleGenerator" />
///     </args>
///     <body>
  public function on_write(Dev_Source_Doc_ModuleGenerator $module_generator);
///     </body>
///   </method>

///   </protocol>
}
/// </interface>

/// <class name="Dev.Source.Doc.ApplicationListener">
///   <implements interface="Dev.Source.Doc.ModuleGeneratorListener" />
class Dev_Source_Doc_ApplicationListener implements Dev_Source_Doc_ModuleGeneratorListener {
  protected $stream;
///   <protocol name="creating">

///   <method name="__construct">
///     <args>
///       <arg name="stream" type="IO.Stream.AbstractStream" />
///     </args>
///     <body>
  public function __construct(IO_Stream_AbstractStream $stream = null) {
    $this->stream = Core::if_null($stream, IO::stderr());
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="observing">

///   <method name="on_write">
///     <args>
///       <arg name="module_generator" type="Dev.Source.Doc.ModuleGenerator" />
///     </args>
///     <body>
  public function on_write(Dev_Source_Doc_ModuleGenerator $module_generator) {
    $this->stream->write($module_generator->module->name."\n");
  }
///     </body>
///   </method>

///   </protocol>
}
/// </class>

/// <class name="Dev.Source.Doc.Application" extends="CLI.Application.Base">
class Dev_Source_Doc_Application extends CLI_Application_Base {
///   <protocol name="performing">

///   <method name="run" returns="int">
///     <args>
///       <arg name="argv" type="array" />
///     </args>
///     <body>
  public function run(array $argv) {
    if ($this->config->visible)
      Dev_Source_Doc_ModuleGenerator::listener(new Dev_Source_Doc_ApplicationListener());
    if (empty($this->config->module)) {
      $library_generator = new Dev_Source_Doc_LibraryDirGenerator(
        $this->config->library, $this->config->output);
      $library_generator->generate();
    }
    else {
      $module_generator = new Dev_Source_Doc_ModuleGenerator(
        new Dev_Source_Module($this->config->module) , $this->config->output);
      $module_generator->write_diagram();
      $module_generator->write();
    }
    return 0;
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="supporting">

///   <method name="setup" access="protected">
///     <body>
  protected function setup() {
    $this->options->
      brief('Dev.Source.Doc '.Dev_Source_Doc::VERSION.': TAO documentation generator')->
      string_option('library',  '-l', '--library', 'Path to library')->
      string_option('module',   '-m', '--module',  'Module name')->
      boolean_option('visible', '-v', '--visible', 'Visible output process (wtf?')->
      string_option('output',   '-o', '--output',  'Path to output');

    $this->config->library = './lib';
    $this->config->visible  = false;
  }
///     </body>
///   </method>

///   </protocol>
}
/// </class>

/// </module>
