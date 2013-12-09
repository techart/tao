<?php
/**
 * WebKit.Helpers.Tags
 * 
 * @package Templates\HTML\Tags
 * @version 1.0.0
 */
Core::load('Templates.HTML');

/**
 * @package Templates\HTML\Tags
 */
class Templates_HTML_Tags implements Core_ModuleInterface, Templates_HelperInterface {

  const VERSION = '1.0.0';


/**
 * @param string $name
 * @param array $attributes
 * @param boolean $close
 * @return string
 */
  public function tag($t, $name, array $attributes = array(), $close = true) {
    $tag = '<'.((string) $name);

    foreach ($attributes as $k => $v)
      if (!is_array($v)) $tag .= ($v === true ? " $k " : ( $v === false ? '' :  " $k=\"".htmlspecialchars($v).'"'));
    return $tag .= (boolean) $close ? ' />' : '>';
  }

/**
 * @param string $name
 * @param string $content
 * @param boolean $close
 * @return string
 */
  public function content_tag($t, $name, $content, array $attributes = array()) {
    $tag = '<'.((string) $name);
    foreach ($attributes as $k => $v)
      if (!is_array($v))
        $tag .= ($v === true ? " $k " : ( $v === false ? '' :  " $k=\"".htmlspecialchars($v).'"'));

    return $tag .= '>'.((string) $content).'</'.((string) $name.'>');
  }

/**
 * @param string $content
 * @return string
 */
  public function cdata_section($t, $content) {
    return '<![CDATA['.((string) $content).']'.']>';
  }

}

