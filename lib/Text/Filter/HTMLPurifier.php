<?php
/**
 * @package Text\Filter\HTMLPurifier
 */

Core::load('Text.Process');

class Text_Filter_HTMLPurifier implements Core_ModuleInterface, Text_Process_ProcessInterface
{

	private $remove_empty = true;
	private $empty_tags = array('p');
	private $clear = array('&#13;', '<p><br/></p>');

	public function configure($config)
	{
		if (isset($config['remove_empty'])) {
			$this->remove_empty = $config['remove_empty'];
		}
		if (isset($config['empty_tags'])) {
			$this->remove_empty = $config['empty_tags'];
		}
		if (isset($config['clear'])) {
			$this->clear = $config['clear'];
		}
	}

	public function process($string)
	{
		$trims = trim($string);
		if (empty($trims)) {
			return $string;
		}
		try {
			/* $value = '<?xml version="1.0" encoding="utf-8"?>' . trim(str_replace("\n", ' ', $string)); */
			$value = '<?xml version="1.0" encoding="utf-8"?>' . trim(preg_replace('!(?>\r\n|\n|\r|\f)(?=[^}]*?(?:{|$))!', ' ', $string));
			$value = preg_replace('{\s*</}', '</', $value);
			$doc = new DOMDocument();
			@$doc->loadHTML($value);
			$xpath = new DOMXPath($doc);
			if ($this->remove_empty) {
				foreach ($this->empty_tags as $tag) {
					foreach ($xpath->query("//{$tag}[not(node())]") as $node) {
						$node->parentNode->removeChild($node);
					}
				}
			}
			$inner = '';
			foreach ($doc->getElementsByTagName('body')->item(0)->childNodes as $child) {
				$inner .= $doc->saveHTML($child);
			}
			$result = $inner;
			foreach ($this->clear as $str) {
				$result = trim(str_replace($str, '', $result));
			}
			return $result;
		} catch (Exception $e) {
			return $string;
		}
	}

}
