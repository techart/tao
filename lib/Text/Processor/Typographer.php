<?php

Core::load('Text.Process');

class Text_Processor_Typographer implements Core_ModuleInterface, Text_Process_ProcessInterface {
	const VERSION = '0.1.0';

	private $tag_exp = '{(</?[-a-zA-Z0-9:]+\b(?>[^"\'>]+|"[^"]*"|\'[^\']*\')*>)}s';

	public function configure($c) { }

	public function process($text) {
		$parts = preg_split($this->tag_exp, $text, null, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE);
		foreach($parts as $key => $part) {
			if(!$this->has_tag($part))
				$parts[$key] = $this->process_text($part);
		}
		return implode($parts);
	}

	private function prepare_rules($rules) {
		return array_map(array($this, 'prepare_rule'), $rules);
	}

	private function has_tag($text) {
		return preg_match($this->tag_exp, $text);
	}

	protected function process_text($text) {
		$rules = array(
			'\(c\)' => '&copy;',
			' - ' => ' &mdash; ',
			'(\d)-(\d)' => '$1&ndash;$2',
			'  ' => ' ',
			'""' => '"',
			'&laquo;\s*&laquo;' => '&laquo;',
			'"([a-zA-Zа-яА-Я0-9])' => '&laquo;$1',
			'"' => '&raquo;',
			'([a-zA-Zа-яА-Я])\.([a-zA-Zа-яА-Я])\.([a-zA-Zа-яА-Я])' => '$1. $2. $3',
			'([a-zA-Zа-яА-Я])\.([a-zA-Zа-яА-Я])\.' => '$1. $2.',
			'(№|§)(\S)' => '$1 $2',
			'\s(г|пос|д|ул|пр|пер|гл)\.(\S)' => ' $1. $2',
			'\s(г-н|г-жа)(\S)' => ' $1. $2',
			'(\d)(кг|г|гр|мг|л|мл|км\/ч|кВ)([\s.,;)])' => '$1 $2$3'
		);
		return preg_replace($this->prepare_rules(array_keys($rules)), $rules, $text);
	}

	public function prepare_rule($rule) {
		return "/$rule/u";
	}
}
