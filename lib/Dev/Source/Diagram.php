<?php
/**
 * Dev.Source.Diagram
 *
 * @package Dev\Source\Diagram
 * @version 0.4.0
 */
Core::load('CLI.Application', 'IO.FS', 'Dev.Source', 'Text', 'Proc');

/**
 * @package Dev\Source\Diagram
 */
class Dev_Source_Diagram implements Core_ModuleInterface, CLI_RunInterface
{
	const VERSION = '0.4.0';

	/**
	 * @param array $argv
	 */
	static public function main(array $argv)
	{
		Core::with(new Dev_Source_Diagram_Application())->main($argv);
	}

}

/**
 * @package Dev\Source\Diagram
 */
class Dev_Diagram_Exception extends Core_Exception
{
}

class Dev_Source_Diagram_XSL
{
	static public function class_diagram()
	{
		return <<<XSL
<xsl:stylesheet xmlns:xsl="http://www.w3.org/1999/XSL/Transform" version="1.0">

  <xsl:output method="text" indent="no" />

  <xsl:strip-space elements="*" />

  <xsl:template match="/library">
digraph library {

  overlap="scale";
  rankdir="BT";

  edge [ arrowsize="1", fontname="Arial", fontsize="9" ];

  node [ shape="plaintext", fontname="Arial", fontsize="9" ];

    <xsl:apply-templates select="module/interface" />
    <xsl:apply-templates select="module/class" />
    <xsl:apply-templates select="module/dependency" />
    <xsl:apply-templates select="module/composition" />
    <xsl:apply-templates select="module/aggregation" />
    <xsl:apply-templates select="module/association" />

    <xsl:apply-templates select="module" />
}
  </xsl:template>

  <xsl:template match="interface">

    <xsl:variable name="iname" select="@name" />
    <xsl:variable name="inode" select="translate(\$iname, '.', '_')" />
    <xsl:variable name="pname" select="@extends" />

    <xsl:value-of select="\$inode" /> <![CDATA[[label=<
    <table border="0" cellborder="1" cellpadding="4" cellspacing="0" bgcolor="white">
      <tr>
        <td href="]]>
          <xsl:value-of select="concat('#i-', translate(\$iname, '.', '-'))" />
        <![CDATA[" tooltip="]]>
          <xsl:value-of select="\$iname" />
        <![CDATA[" align="left" width="250" bgcolor="#98FB98"><font point-size="10">&lt;&lt;]]><xsl:value-of select="\$iname" /><![CDATA[&gt;&gt;</font></td>
      </tr>]]>
    <xsl:if test="\$pname">
      <![CDATA[<tr>
        <td align="left"><font point-size="10" color="#003C30">^</font> ]]><xsl:value-of select="\$pname" /><![CDATA[</td>
      </tr>]]>
    </xsl:if>
    <xsl:apply-templates select="protocol" >
      <xsl:with-param name="class_name" select="\$iname" />
    </xsl:apply-templates>
    <![CDATA[</table>]]>>];

    <xsl:if test="//interface[@name=\$pname]">
      <xsl:value-of select="\$inode" /> -> <xsl:value-of select="translate(\$pname, '.', '_')" /> [arrowhead="empty",arrowsize="1.5"];
    </xsl:if>

  </xsl:template>

  <xsl:template match="class">

    <xsl:variable name="cname" select="@name" />
    <xsl:variable name="cnode" select="translate(\$cname, '.', '_')" />
    <xsl:variable name="pname" select="@extends" />

    <xsl:value-of select="\$cnode" /> <![CDATA[[label=<
    <table border="0" cellborder="1" cellpadding="4" cellspacing="0" bgcolor="white">
      <tr>
        <td href="]]> <xsl:value-of select="concat('#c-', translate(\$cname, '.', '-'))" />
        <![CDATA[" tooltip="]]>
          <xsl:value-of select="\$cname" />
        <![CDATA[" align="left" width="250" bgcolor="]]>
        <xsl:choose><xsl:when test="@stereotype='abstract'">#DDDDDD</xsl:when><xsl:when test="@stereotype='module'">#FEC44F</xsl:when><xsl:when test="@stereotype='exception'">#FCBBA1</xsl:when><xsl:otherwise>#CAE1FF</xsl:otherwise></xsl:choose><![CDATA["><font point-size="10">]]><xsl:value-of select="\$cname" /><![CDATA[</font></td>
      </tr>]]>
    <xsl:if test="\$pname">
      <![CDATA[<tr>
        <td align="left"><font point-size="10" color="#003C30">^</font> ]]><xsl:value-of select="\$pname" /><![CDATA[</td>
      </tr>]]>
    </xsl:if>

    <xsl:if test="implements">
      <![CDATA[<tr>
        <td align="left" balign="left"><font color="#073E23">]]><xsl:for-each select="implements"><![CDATA[&lt;&lt;]]><xsl:value-of select="@interface" /><![CDATA[&gt;&gt;<br />]]></xsl:for-each><![CDATA[</font></td>
      </tr>]]>
    </xsl:if>

    <xsl:apply-templates select="protocol" >
      <xsl:with-param name="class_name" select="\$cname" />
    </xsl:apply-templates>
    <![CDATA[</table>]]>>];

    <xsl:if test="//class[@name=\$pname]">
      <xsl:value-of select="\$cnode" /> -> <xsl:value-of select="translate(\$pname, '.', '_')" /> [arrowhead="empty",arrowsize="1.5"];
    </xsl:if>

    <xsl:apply-templates select="implements" />

    <xsl:apply-templates select="depends" />

  </xsl:template>

  <xsl:template match="protocol">
    <xsl:param name="class_name" />
    <![CDATA[<tr>
      <td href="]]>
        <xsl:value-of select="concat('#p-', translate(\$class_name, '.', '-'), '-', @name)" />
      <![CDATA[" tooltip="]]>
        <xsl:value-of select="@name" />
      <![CDATA[" align="left" balign="left"><font point-size="10">]]><xsl:value-of select="@name" /><![CDATA[</font><br />]]><xsl:apply-templates select="method" /><![CDATA[</td>
    </tr>]]>
  </xsl:template>

  <xsl:template match="method"><!--
    --><![CDATA[<font point-size="10" color="#003C30">]]><xsl:choose><xsl:when test="@access='private'">- </xsl:when><xsl:when test="@access='protected'"># </xsl:when><xsl:otherwise>+ </xsl:otherwise></xsl:choose><![CDATA[</font>]]><xsl:value-of select="@name" />(<xsl:for-each select="args/arg"><!--
      --><xsl:value-of select="@name" /><!--
        --><xsl:if test="position() &lt; last()"><xsl:text>,</xsl:text></xsl:if><!--
  --></xsl:for-each><![CDATA[)<br />]]></xsl:template>

  <xsl:template match="implements">
    <xsl:variable name="interface" select="@interface" />
    <xsl:if test="//interface[@name=\$interface]">
      <xsl:value-of select="translate(../@name, '.', '_')" /> -> <xsl:value-of select="translate(\$interface, '.', '_')" /> [
      arrowhead="empty",color="#008B45",style="dashed"];
    </xsl:if>

  </xsl:template>

  <xsl:template match="composition">
    <xsl:variable name="source" select="source/@class" />
    <xsl:variable name="target"  select="target/@class" />
    <xsl:if test="//class[@name=\$source] and //class[@name=\$target]">
      <xsl:value-of select="translate(\$source, '.', '_')" /> -> <xsl:value-of select="translate(\$target, '.', '_')" /> [headlabel="<xsl:value-of select="concat(source/@role, ' ', source/@multiplicity)" />",taillabel="<xsl:value-of select="concat(target/@role, ' ', target/@multiplicity)" />",arrowhead="none",arrowtail="diamond",label="<xsl:value-of select="@name" />",color="#08306B",fontcolor="#08306B"];
    </xsl:if>
  </xsl:template>

  <xsl:template match="aggregation">
    <xsl:variable name="source" select="source/@class" />
    <xsl:variable name="target"  select="target/@class" />
    <xsl:if test="//class[@name=\$source] and //class[@name=\$target]">
      <xsl:value-of select="translate(\$source, '.', '_')" /> -> <xsl:value-of select="translate(\$target, '.', '_')" /> [headlabel="<xsl:value-of select="concat(source/@role, ' ', source/@multiplicity)" />",taillabel="<xsl:value-of select="concat(target/@role, ' ', target/@multiplicity)" />",arrowhead="none",arrowtail="odiamond",label="<xsl:value-of select="@name" />",color="#08306B",fontcolor="#08306B"];
    </xsl:if>
  </xsl:template>

  <xsl:template match="dependency">
    <xsl:variable name="client" select="@client" />
    <xsl:variable name="supplier" select="@supplier" />
    <xsl:if test="//class[@name=\$client] and //class[@name=\$supplier]">
      <xsl:value-of select="translate(\$client, '.', '_')" /> -> <xsl:value-of select="translate(\$supplier, '.', '_')" /> [
      arrowhead="open",color="blue",style="dashed",fontcolor="blue",label="&lt;&lt;<xsl:value-of select="@stereotype" />&gt;&gt;"];
    </xsl:if>
  </xsl:template>



  <xsl:template match="depends">
    <xsl:variable name="client" select="../@name" />
    <xsl:variable name="supplier" select="@supplier" />
    <xsl:if test="//class[@name=\$client] and //class[@name=\$supplier]">
      <xsl:value-of select="translate(\$client, '.', '_')" /> -> <xsl:value-of select="translate(\$supplier, '.', '_')" /> [
      arrowhead="open",color="blue",style="dashed",fontcolor="blue",label="&lt;&lt;<xsl:value-of select="@stereotype" />&gt;&gt;"];
    </xsl:if>
  </xsl:template>

  <xsl:template match="module">
  subgraph cluster_<xsl:value-of  select="translate(@name, '.', '_')" /> {
    pencolor="#FE9929";
    label="<xsl:value-of select="concat(@name, ' ', @version)" />";
    <xsl:for-each select="class|interface">
      <xsl:value-of select="translate(@name, '.', '_')" />;
    </xsl:for-each>
  };
  </xsl:template>

</xsl:stylesheet>
XSL;
	}
}

/**
 * @package Dev\Source\Diagram
 */
class Dev_Source_Diagram_Application extends CLI_Application_Base
{

	/**
	 * @param array $argv
	 *
	 * @return int
	 */
	public function run(array $argv)
	{
		try {
			$xslt = new XSLTProcessor();
			$xslt->importStylesheet(
				DOMDocument::loadXML(Dev_Source_Diagram_XSL::class_diagram())
			);

			$result = $xslt->transformToXML(Dev_Source::Library($argv)->xml);

		} catch (Dev_Source_InvalidSourceException $e) {
			$this->dump_errors($e);
			return -1;
		}

		$this->config->dump ?
			IO::stdout()->write($result) : $this->output($result);

		return 0;
	}

	/**
	 */
	protected function setup()
	{
		$this->options->
			brief('Dev.Source.Diagram ' . Dev_Source_Diagram::VERSION . ': TAO module visualization utility')->
			string_option('application', '-a', '--application', 'Visualizer application (graphviz)')->
			string_option('format', '-T', '--format', 'Output format')->
			boolean_option('dump', '-d', '--dump', 'No output conversion')->
			string_option('output', '-o', '--output', 'Output file');

		$this->config->application = 'dot';
		$this->config->format = 'png';
		$this->config->output = null;
		$this->config->dump = false;

	}

	/**
	 */
	protected function output($result)
	{
		switch ($application = $this->config->application) {
			case 'dot':
			case 'neato':
			case 'fdp':
			case 'circo':
			case 'twopi':
				Proc::Pipe(
					Core_Strings::format('%s -T%s %s',
						$application,
						$this->config->format,
						$this->config->output ? ' -o ' . $this->config->output : ''
					), 'w'
				)->
					write($result)->
					close();
				break;
			default:
				throw new Dev_Diagram_Exception(
					Core_Strings::format('Unknown application (%s)', $application));
		}
	}

	/**
	 * @param Dev_Source_InvalidSourceException $exception
	 */
	protected function dump_errors(Dev_Source_InvalidSourceException $exception)
	{
		$stderr = IO::stderr();

		$stderr->write("{$exception->message}\n\n");

		foreach (Text::Tokenizer($exception->source) as $no => $line)
			$stderr->format("%05d: %s\n", $no + 1, $line);
		$stderr->write("\n");

		foreach ($exception->errors as $error)
			$stderr->format("%d : %s", $error->line, $error->message);
	}

}

