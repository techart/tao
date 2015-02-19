<?php
/**
 * @package Text\Filter\HTML
 */

Core::load('Text.Process');

class Text_Filter_HTML implements Core_ModuleInterface, Text_Process_ProcessInterface
{
	const VERSION = '0.1.0';

	protected $allowed_tags = array('a', 'em', 'strong', 'cite', 'code', 'ul', 'ol', 'li', 'dl', 'dt', 'dd', 'br');

	public function configure($config)
	{
		if (isset($config['tags'])) {
			$this->allowed_tags = $config['tags'];
		}
	}

	public function process($string)
	{
		// Remove NUL characters (ignored by some browsers)
		$string = str_replace(chr(0), '', $string);
		// Remove Netscape 4 JS entities
		$string = preg_replace('%&\s*\{[^}]*(\}\s*;?|$)%', '', $string);

		// Defuse all HTML entities
		$string = str_replace('&', '&amp;', $string);
		// Change back only well-formed entities in our whitelist
		// Decimal numeric entities
		$string = preg_replace('/&amp;#([0-9]+;)/', '&#\1', $string);
		// Hexadecimal numeric entities
		$string = preg_replace('/&amp;#[Xx]0*((?:[0-9A-Fa-f]{2})+;)/', '&#x\1', $string);
		// Named entities
		$string = preg_replace('/&amp;([A-Za-z][A-Za-z0-9]*;)/', '&\1', $string);

		$allowed_html = '';
		foreach ($this->allowed_tags as $t)
			$allowed_html .= "<$t>";
		return strip_tags($string, $allowed_html); //FIXME: strip_tags -- лажа
	}

}
