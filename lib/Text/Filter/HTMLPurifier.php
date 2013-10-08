<?php

Core::load('Text.Process');

class Text_Filter_HTMLPurifier implements Core_ModuleInterface, Text_Process_ProcessInterface {

	private $remove_empty = true;
	private $empty_tags = array('p');

	public function configure($config) {
		if (isset($config['remove_empty'])) {
			$this->remove_empty = $config['remove_empty'];
		}
		if (isset($config['empty_tags'])) {
			$this->remove_empty = $config['empty_tags'];
		}
	}

	public function process($string) {
		$trims = trim($string);
		if (empty($trims)) {
			return $string;
		}
		try {
			$value = '<?xml version="1.0" encoding="utf-8"?>' . trim(str_replace("\n", ' ', $string));
			$doc = new DOMDocument();
			@$doc->loadHTML($value);
			$xpath = new DOMXPath($doc);
			if ($this->remove_empty) {
				foreach ($this->empty_tags as $tag) {
					foreach( $xpath->query("//{$tag}[not(node())]") as $node) {
						$node->parentNode->removeChild($node);
					}
				}
			}
			$inner = '';
			foreach ($doc->getElementsByTagName('body')->item(0)->childNodes as $child) {
			    $inner .= $doc->saveXML($child);
			}
			return trim(str_replace('&#13;', '', $inner));
		} catch (Exception $e) {
			return $string;
		}
	}

}
