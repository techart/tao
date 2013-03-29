<?php
/// <module name="Text.Stem.Ru" version="0.2.0" maintainer="timokhin@techart.ru">
///   <brief>Поиск основ слов русского языка</brief>
/// <class name="Text.Stem.RU" stereotype="module">
class Text_Stem_RU implements Core_ModuleInterface {
///   <constants>
  const VERSION = '0.2.0';
///   </constants>

///   <protocol name="building">

///   <method name="Stemmer" returns="Text.Stem.RU.Stemmer" scope="class">
///     <brief>Фабричный метод, возвращает объек класса Text.Stem.RU.Stemmer</brief>
///     <body>
  static public  function Stemmer()  { return new Text_Stem_RU_Stemmer; }
///     </body>
///   </method>

///   </protocol>
}
/// </class>

/// <class name="Text.Stem.RU.Stemmer">
///     <brief>Класс осeществляющий поиск основ слов</brief>
class Text_Stem_RU_Stemmer {

///   <constants>
  const VOWEL = '/аеиоуыэюя/';
  const PERFECTIVEGROUND = '/((ив|ивши|ившись|ыв|ывши|ывшись)|((?<=[ая])(в|вши|вшись)))$/';
  const REFLEXIVE = '/(с[яь])$/';
  const ADJECTIVE = '/(ее|ие|ые|ое|ими|ыми|ей|ий|ый|ой|ем|им|ым|ом|его|ого|ему|ому|их|ых|ую|юю|ая|яя|ою|ею)$/';
  const PARTICIPLE = '/((ивш|ывш|ующ)|((?<=[ая])(ем|нн|вш|ющ|щ)))$/';
  const VERB = '/((ила|ыла|ена|ейте|уйте|ите|или|ыли|ей|уй|ил|ыл|им|ым|ен|ило|ыло|ено|ят|ует|уют|ит|ыт|ены|ить|ыть|ишь|ую|ю)|((?<=[ая])(ла|на|ете|йте|ли|й|л|ем|н|ло|но|ет|ют|ны|ть|ешь|нно)))$/';
  const NOUN = '/(а|ев|ов|ие|ье|е|иями|ями|ами|еи|ии|и|ией|ей|ой|ий|й|иям|ям|ием|ем|ам|ом|о|у|ах|иях|ях|ы|ь|ию|ью|ю|ия|ья|я)$/';
  const RVRE = '/^(.*?[аеиоуыэюя])(.*)$/';
  const DERIVATIONAL = '/[^аеиоуыэюя][аеиоуыэюя]+[^аеиоуыэюя]+[аеиоуыэюя].*(?<=о)сть?$/';
///   </constants>

  protected $cache = array();
  protected $use_cache = true;

///   <protocol name="configuring">

///   <method name="use_cache" returns="Text.Stem.RU.Stemmer">
///     <brief>Включает или выключает кеширование</brief>
///     <args>
///       <arg name="use_cache" type="boolean" brief="булевый флаг" />
///     </args>
///     <body>
  public function use_cache($use_cache) {
    $this->use_cache = (boolean) $use_cache;
    return $this;
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="processing">

///   <method name="stem">
///     <brief>Возвращает основу слова</brief>
//TODO: зачем два одинаковыч метода stem и stem_word
///     <args>
///       <arg name="word" type="string" brief="слово" />
///     </args>
///     <body>
  public function stem($words) { return $this->stem_word($words); }
///     </body>
///   </method>

///   <method name="clear_cache" returns="Text.Stem.RU.Stemmer">
///     <brief>Очищает кэш</brief>
///     <body>
  public function clear_cache() {
    $this->cache = array();
    return $this;
  }
///     </body>
///   </method>

///   <method name="stem_word" returns="string">
///     <brief>Возвращает основу слова</brief>
///     <args>
///       <arg name="word" type="string" brief="слово" />
///     </args>
///     <body>
  public function stem_word($word) {
    $word = strtr(mb_strtolower($word), array('ё' => 'е'));

    if ($this->use_cache && isset($this->cache[$word])) return $this->cache[$word];

    list($str, $start, $rv) = Core_Regexps::match_with_results(self::RVRE, $word);
    if (!$rv) return $word;

    // step 1
    if (!Core_Regexps::replace_ref(self::PERFECTIVEGROUND, '', $rv)) {
      $rv = preg_replace(self::REFLEXIVE, '', $rv);
      if (Core_Regexps::replace_ref(self::ADJECTIVE, '', $rv))
        $rv = preg_replace(self::PARTICIPLE, '', $rv);
      else
        if (!Core_Regexps::replace_ref(self::VERB, '', $rv))
          $rv = preg_replace(self::NOUN, '', $rv);
    }

    // step 2
    $rv = preg_replace('{и$}', '', $rv);

    // step 3
    if (preg_match(self::DERIVATIONAL, $rv))
      $rv = preg_replace('{ость?$}', '', $rv);

    // step 4
    if (!Core_Regexps::replace_ref('{ь$}', '', $rv))
      $rv = preg_replace(array('{ейше?}', '{нн$}'), array('', 'н'), $rv);

    return $this->use_cache ?
      $this->cache[$word] = $start.$rv :
      $start.$rv;
  }
///     </body>
///   </method>

///   </protocol>
}
/// </class>

/// </module>
