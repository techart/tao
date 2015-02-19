<?php
/**
 * @package Text\Processor\Typographer
 */

Core::load('Text.Process');

class Text_Processor_Typographer implements Core_ModuleInterface, Text_Process_ProcessInterface
{
	const VERSION = '0.1.0';
	private $tag_exp = '{(</?[-a-zA-Z0-9:]+\b(?>[^"\'>]+|"[^"]*"|\'[^\']*\')*>)}s';

	public function configure($c)
	{
	}

	public function process($text)
	{
		$parts = preg_split($this->tag_exp, $text, null, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE);
		foreach($parts as $key => $part) {
			if(!$this->has_tag($part)) {
				$parts[$key] = $this->process_text($part);
			}
		}
		return implode($parts);
	}

	public function prepare_rule($rule)
	{
		return "/$rule/iu";
	}

	protected function process_text($text)
	{
		$rules = array(
			'\(c\)' => '&copy;',
			' - ' => ' &mdash; ',
			'(\d)-(\d)' => '$1&ndash;$2',
			'  ' => ' ',
			'""' => '"',
			'«' => '&laquo;',
			'»' => '&raquo;',
			'&laquo;\s*&laquo;' => '&laquo;',
			'"([a-zA-Zа-яА-Я0-9])' => '&laquo;$1',
			'"' => '&raquo;',
			'([a-zA-Zа-яА-Я])\.([a-zA-Zа-яА-Я])\.([a-zA-Zа-яА-Я])' => '$1. $2. $3',
			'([a-zA-Zа-яА-Я])\.([a-zA-Zа-яА-Я])\.' => '$1. $2.',
			'(№|§)(\S)' => '$1 $2',
			'\s(г|пос|д|ул|пр|пер|гл)\.(\S)' => ' $1. $2',
			'\s(г-н|г-жа)(\S)' => ' $1. $2',
			'(\d)(кг|г|гр|мг|л|мл|км\/ч|кВ)([\s.,;)])' => '$1 $2$3',
			'(\s|^)(' . implode('|', $this->wrappable_words()) . ')(\s+)' => '$1$2&nbsp;',
			'(\s+)(-|–|—|&ndash;|&mdash;)' => '&nbsp;$2',
			'(\d{1,2}([а-я]+)?)(\s)((' . implode('|', $this->months_exps()) . ')[а-яА-Я]+)' => '$1&nbsp;$4',
			'((' . implode('|', $this->months_exps()) . ')[а-яА-Я]+)(\s)(\d{4})' => '$1&nbsp;$4',
			'(\d{2,4})(\s)(год[а-я]{1,2})' => '$1&nbsp;$3',
			'([А-Я][а-я]+)(\s)(([А-Я]\.)(\s+)?([А-Я]\.))' => '$1&nbsp;$4$6',
		);
		return preg_replace($this->prepare_rules(array_keys($rules)), $rules, $text);
	}

	protected function prepositions()
	{
		return array('в', 'без', 'до', 'из', 'к', 'на', 'по', 'о', 'от', 'перед', 'при', 'через', 'с',
			'у', 'и', 'нет', 'за', 'над', 'для', 'об', 'под', 'про', 'не');
	}

	protected function personal_pronouns()
	{
		return array('я', 'меня', 'мне', 'меня', 'мной', 'мною', 'мы', 'нас', 'нам', 'нами', 'ты', 'тебя', 'тебе',
			'тобой', 'тобою', 'вы', 'вас', 'вам', 'вами', 'он', 'его', 'него', 'ему', 'нему', 'им', 'ним', 'она',
			'её', 'неё', 'ей', 'ней', 'ею', 'нею', 'оно', 'они', 'их', 'них', 'ими', 'ними');
	}

	protected function months_exps()
	{
		return array('январ', 'феврал', 'март', 'апрел', 'май', 'мая', 'маю', 'мае', 'июн', 'июл', 'август',
			'сентябр', 'октябр', 'ноябр', 'декабр');
	}

	protected function wrappable_words()
	{
		return array_merge($this->personal_pronouns(), $this->prepositions());
	}

	private function has_tag($text)
	{
		return preg_match($this->tag_exp, $text);
	}

	private function prepare_rules($rules)
	{
		return array_map(array($this, 'prepare_rule'), $rules);
	}

}
