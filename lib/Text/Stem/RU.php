<?php
/**
 * Text.Stem.Ru
 *
 * Поиск основ слов русского языка
 *
 * @package Text\Stem\RU
 * @version 0.2.0
 */

/**
 * @package Text\Stem\RU
 */
class Text_Stem_RU implements Core_ModuleInterface
{
	const VERSION = '0.2.0';

	/**
	 * Фабричный метод, возвращает объек класса Text.Stem.RU.Stemmer
	 *
	 * @return Text_Stem_RU_Stemmer
	 */
	static public function Stemmer()
	{
		return new Text_Stem_RU_Stemmer;
	}

}

/**
 * Класс осeществляющий поиск основ слов
 *
 * @package Text\Stem\RU
 */
class Text_Stem_RU_Stemmer
{

	const VOWEL = '/аеиоуыэюя/';
	const PERFECTIVEGROUND = '/((ив|ивши|ившись|ыв|ывши|ывшись)|((?<=[ая])(в|вши|вшись)))$/';
	const REFLEXIVE = '/(с[яь])$/';
	const ADJECTIVE = '/(ее|ие|ые|ое|ими|ыми|ей|ий|ый|ой|ем|им|ым|ом|его|ого|ему|ому|их|ых|ую|юю|ая|яя|ою|ею)$/';
	const PARTICIPLE = '/((ивш|ывш|ующ)|((?<=[ая])(ем|нн|вш|ющ|щ)))$/';
	const VERB = '/((ила|ыла|ена|ейте|уйте|ите|или|ыли|ей|уй|ил|ыл|им|ым|ен|ило|ыло|ено|ят|ует|уют|ит|ыт|ены|ить|ыть|ишь|ую|ю)|((?<=[ая])(ла|на|ете|йте|ли|й|л|ем|н|ло|но|ет|ют|ны|ть|ешь|нно)))$/';
	const NOUN = '/(а|ев|ов|ие|ье|е|иями|ями|ами|еи|ии|и|ией|ей|ой|ий|й|иям|ям|ием|ем|ам|ом|о|у|ах|иях|ях|ы|ь|ию|ью|ю|ия|ья|я)$/';
	const RVRE = '/^(.*?[аеиоуыэюя])(.*)$/';
	const DERIVATIONAL = '/[^аеиоуыэюя][аеиоуыэюя]+[^аеиоуыэюя]+[аеиоуыэюя].*(?<=о)сть?$/';

	protected $cache = array();
	protected $use_cache = true;

	/**
	 * Включает или выключает кеширование
	 *
	 * @param boolean $use_cache
	 *
	 * @return Text_Stem_RU_Stemmer
	 */
	public function use_cache($use_cache)
	{
		$this->use_cache = (boolean)$use_cache;
		return $this;
	}



	/**
	 * Возвращает основу слова
	 *
	 * @param string $word
	 */
//TODO: зачем два одинаковыч метода stem и stem_word
	public function stem($words)
	{
		return $this->stem_word($words);
	}

	/**
	 * Очищает кэш
	 *
	 * @return Text_Stem_RU_Stemmer
	 */
	public function clear_cache()
	{
		$this->cache = array();
		return $this;
	}

	/**
	 * Возвращает основу слова
	 *
	 * @param string $word
	 *
	 * @return string
	 */
	public function stem_word($word)
	{
		$word = strtr(mb_strtolower($word), array('ё' => 'е'));

		if ($this->use_cache && isset($this->cache[$word])) {
			return $this->cache[$word];
		}

		list($str, $start, $rv) = Core_Regexps::match_with_results(self::RVRE, $word);
		if (!$rv) {
			return $word;
		}

		// step 1
		if (!Core_Regexps::replace_ref(self::PERFECTIVEGROUND, '', $rv)) {
			$rv = preg_replace(self::REFLEXIVE, '', $rv);
			if (Core_Regexps::replace_ref(self::ADJECTIVE, '', $rv)) {
				$rv = preg_replace(self::PARTICIPLE, '', $rv);
			} else {
				if (!Core_Regexps::replace_ref(self::VERB, '', $rv)) {
					$rv = preg_replace(self::NOUN, '', $rv);
				}
			}
		}

		// step 2
		$rv = preg_replace('{и$}', '', $rv);

		// step 3
		if (preg_match(self::DERIVATIONAL, $rv)) {
			$rv = preg_replace('{ость?$}', '', $rv);
		}

		// step 4
		if (!Core_Regexps::replace_ref('{ь$}', '', $rv)) {
			$rv = preg_replace(array('{ейше?}', '{нн$}'), array('', 'н'), $rv);
		}

		return $this->use_cache ?
			$this->cache[$word] = $start . $rv :
			$start . $rv;
	}

}

