<?php
/// <module name="XML.Feed" version="0.1.1" maintainer="svistunov@techart.ru">
///   <brief>Чтение RSS-лент различных форматов</brief>
///   <details>
///     <p>Модуль предоставляет простейший объектный интерфейс к RSS-лентам различных форматов.</p>
///     <p>Лента представляется в виде объекта класса XML.Feed.Feed, отдельные записи — в виде
///        объектов класса XML.Feed.Entry. </p>
///     <p>Доступ к основным атрибутам ленты и записи осуществляется через набор стандартных свойств
///        соответствующих объектов, на данный момент это title, description и link, в дальнейшем
///        их число может быть расширено.</p>
///     <p>Доступ к остальным атрибутам выполняется динамически с помощью
///        Core.PropertyAccessInterface, при этом выполняется автоматическое преобразования в
///        camelCase для имен атрибутов.</p>
///     <p>Объект класса XML.Feed.Feed предоставляет доступ к отдельным записям ленты с
///        использованием двух интерфейсов: Core.IndexedAccessInterface и IteratorAggregate.
///        Индексированный доступ позволяет получить доступ к записи по ее числовому индексу в
///        ленте, итератор — пройти по всем записям ленты.</p>
///     <p>Разбор лент выполняется объектами класса XML.Feed.Parser, определение формата ленты
///        производится автоматически. Вместо явного создания объекта парсера можно использовать
///        процедурный интерфейс, предоставляемый классом модуля, в этом случае используется
///        создаваемый автоматически экземпляр парсера.</p>
///      <p>Пример использования:</p>
///      <code>
///      $feed = XML_Feed::fetch('http://www.techart.ru/rss/news/');
///
///      printf("Protocol: %s", $feed->protocol);
///
///      foreach ($feed as $entry) printf("%s\n%s", $entry->link, $entry->title);
///
///      $entry = $feed[0];
///      printf("First entry:\nurl: %s\ntitle: %s", $entry->link, $entry->title);
///      </code>
///   </details>
Core::load('XML','Net.Agents.HTTP');

/// <class name="XML.Feed" stereotype="module">
///   <brief>Модуль XML.Feed</brief>
///   <details>
///     <p>Реализует процедурный интерфейс модуля. Интерфейс позволяет получить объектное
///        представление ленты тремя способами:</p>
///     <dl>
///       <dt>XML_Feed::parse($xml)</dt><dd>выполняет разбор строки, содержащей данные ленты;</dd>
///       <dt>XML_Feed::fetch($url, $agent)</dt><dd>загружает ленту по указанному адресу;</dd>
///       <dt>XML_Feed::load($path)</dt><dd>загружает ленту из файла.</dd>
///     </dl>
///   </details>
///   <implements interface="Core.ModuleInterface" />
///   <depends supplier="XML.Feed.Parser" stereotype="creates" />
class XML_Feed implements Core_ModuleInterface {

///   <constants>
  const VERSION = '0.1.1';
///   </constants>

  static private $parser;

///   <protocol name="creating">

///   <method name="initialize" scope="class">
///     <brief>Выполняет инициализацию модуля</brief>
///     <details>
///       <p>Метод создает экземпляр парсера по умолчанию для использования процедурным
///         интерфейсом.</p>
///     </details>
///     <body>
  static public function initialize() { self::$parser = new XML_Feed_Parser(); }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="building">

///   <method name="parse" returns="XML.Feed" scope="class">
///     <brief>Выполняет разбор ленты, переданной в виде строки</brief>
///     <args>
///       <arg name="xml" type="string" brief="текст документа" />
///     </args>
///     <body>
  static public function parse($xml) { return self::$parser->parse($xml); }
///     </body>
///   </method>

///   <method name="fetch" returns="XML.Feed" scope="class">
///     <brief>Выкачивает ленту с указанного адреса и выполняет разбор</brief>
///     <args>
///       <arg name="url" type="string" brief="адрес ленты" />
///       <arg name="agent" type="Net.HTTP.AgentInterface" brief="HTTP-агент" />
///     </args>
///     <details>
///       <p>Как правило, можно не передавать HTTP-агент, в этом случае будет использован
///          HTTP-агент по умолчанию.</p>
///     </details>
///     <body>
  static public function fetch($url, Net_HTTP_AgentInterface $agent = null) {
    return self::$parser->fetch($url, $agent);
  }
///     </body>
///   </method>

///   <method name="load" returns="XML.Feed" scope="class">
///     <args>
///       <arg name="path" type="string" brief="Путь к файлу" />
///     </args>
///     <details>
///       <p>Загружает ленту из файла и выполняет ее разбор.</p>
///     </details>
///     <body>
  static public function load($path) { return self::$parser->load($path); }
///     </body>
///   </method>

///   </protocol>
}
/// </class>


/// <class name="XML.Feed.Parser">
///   <brief>Выполняет разбор ленты, формирует ее объектное представление.</brief>
///   <depends supplier="XML.Feed.Feed" stereotype="creates" />
///   <depends supplier="XML.Feed.Protocol" stereotype="creates" />
///   <depends supplier="XML.Feed.BadURLException" stereotype="throws" />
///   <depends supplier="XML.Feed.BadFileException" stereotype="throws" />
///   <depends supplier="XML.Feed.UnsupportedProtocolException" stereotype="throws" />
///   <depends supploer="Net.HTTP.Agent" stereotype="uses" />
class XML_Feed_Parser {

///   <protocol name="performing">

///   <method name="fetch" returns="XML.Feed.Feed">
///     <brief>Загружает документ ленты с указанного адреса и выполняет разбор</brief>
///     <args>
///       <arg name="url" type="string" brief="URL ленты" />
///       <arg name="agent" type="Net.HTTP.AgentInterface" default="null" brief="HTTP-агент" />
///     </args>
///     <details>
///       <p>В случае, если агент не передается, используется агент по умолчанию. Если скачивание
///         ленты завершилось неудачно, выбрасывается исключение XML.Feed.BadURLException.</p>
///     </details>
///     <body>
  public function fetch($url, Net_HTTP_AgentInterface $agent = null) {
    if (!isset($agent)) $agent = Net_HTTP::Agent();

    $r = $agent->send(Net_HTTP::Request($url));

    if ($r->status->is_success) return $this->parse($r->body, $url);

    throw new XML_Feed_BadURLException($url);
  }
///     </body>
///   </method>

///   <method name="load" returns="XML.Feed.Feed">
///     <brief>Загружает документ из файла и выполняет разбор</brief>
///     <args>
///       <arg name="path" type="string" brief="Путь к файлу" />
///     </args>
///     <details>
///       <p>В случае, если в процессе чтения файла возникли ошибки, выбрасывается исключение
///          XML.Feed.BadFileException.</p>
///     </details>
///     <body>
  public function load($path) {
    if ($xml = file_get_contents($path)) return $this->parse($xml, $path);

    throw new XML_Feed_BadFileException($path);
  }
///     </body>
///   </method>

///   <method name="parse" returns="XML.Feed.Feed">
///     <brief>Выполняет разбор документа ленты</brief>
///     <args>
///       <arg name="xml" type="string" brief="текст документа" />
///     </args>
///     <body>
  public function parse($xml, $path = null) {
    $document = XML::load(str_replace('xmlns=', 'ns=', $xml));
	if (!($document instanceof DOMDocument)) throw new XML_Feed_BadFileException($path);
    return new XML_Feed_Feed($document, $this->detect_protocol_for($document));
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="supporing">

///   <method name="detect_protocol_for" returns="XML.Feed.Protocol" access="protected">
///     <brief>Определяет формат ленты</brief>
///     <args>
///       <arg name="document" type="DOMDocument" />
///     </args>
///     <details>
///       <p>В случае, если протокол не может быть определен, выбрасывается исключение
///          XML.Feed.UnsupportedProtocolException.</p>
///     </details>
///     <body>
  protected function detect_protocol_for(DOMDocument $document) {
    $tag = $document->documentElement;
    $version = $tag->getAttribute('version');

    switch ($tag->tagName) {
      case 'rss':
        return new XML_Feed_RSS($document, 'rss '.($version ? $version : '2.0'));
      case 'rdf:RDF':
        return new XML_Feed_RSS($document, 'rss 1.0');
      case 'feed':
        return new XML_Feed_Atom($document, 'atom '.($version ? $version : '1.0'));
      default:
        throw new XML_Feed_UnsupportedProtocolException($tag->tagName);
    }
  }
///     </body>
///   </method>

///   </protocol>
}
/// </class>


/// <class name="XML.Feed.Exception" extends="Core.Exception" stereotype="exception">
///   <brief>Базовый класс исключений модуля</brief>
class XML_Feed_Exception extends Core_Exception {}
/// </class>


/// <class name="XML.Feed.BadURLException" extends="XML.Feed.Exception" stereotype="exception">
///   <brief>Ошибка: некорректный URL документа или отсутствующий документ</brief>
///   <details>
///     <p>Поддерживаемые свойства:</p>
///     <dl>
///       <dt>url</dt><dd>URL документа</dd>
///     </dl>
///   </details>
class XML_Feed_BadURLException extends XML_Feed_Exception {

  protected $url;

///   <protocol name="creating">

///   <method name="__construct">
///     <brief>Конструктор</brief>
///     <args>
///       <arg name="url" type="string" brief="адрес" />
///     </args>
///     <body>
  public function __construct($url) {
    $this->url = $url;
    parent::__construct("Can't fetch feed from $url");
  }
///     </body>
///   </method>

///   </protocol>
}
/// </class>


/// <class name="XML.Feed.BadFileException" extends="XML.Feed.Exception" stereotype="exception">
///   <brief>Ошибка: некорректный путь к файлу или содержимое файла документа</brief>
class XML_Feed_BadFileException extends XML_Feed_Exception {

  protected $path;

///   <protocol name="creating">

///   <method name="__construct">
///     <brief>Конструктор</brief>
///     <args>
///       <arg name="path" type="string" brief="путь к файлу" />
///     </args>
///     <details>
///       <p>Поддерживаемые свойства:</p>
///       <dl>
///         <dt>path</dt><dd>путь к файлу</dd>
///       </dl>
///     </details>
///     <body>
  public function __construct($path) {
    $this->path = $path;
    parent::__construct("Can't load feed from $path");
  }
///     </body>
///   </method>

///   </protocol>
}
/// </class>


/// <class name="XML.Feed.UnsupportedProtocolException" extends="XML.Feed.Exception" stereotype="exception">
///   <brief>Ошибка: неподдерживаемый протокол</brief>
class XML_Feed_UnsupportedProtocolException extends XML_Feed_Exception {

  protected $tag;

///   <protocol name="creating">

///   <method name="__construct">
///     <args>
///       <arg name="tag" type="string" brief="корневой тег документа" />
///     </args>
///     <details>
///       <p>Поддерживаемые свойства:</p>
///       <dl>
///         <dt>tag</dt><dd>корневой тег документа, для которого не удалось установить формат</dd>
///       </dl>
///     </details>
///     <body>
  public function __construct($tag) {
    $this->tag = $tag;
    parent::__construct("Unsupported protocol tag: $tag");
  }
///     </body>
///   </method>

///   </protocol>
}
/// </class>


/// <class name="XML.Feed.Protocol" stereotype="abstract">
///   <brief>Абстрактный класс протокола документа</brief>
///   <details>
///     <p>Предназначен для использования в качестве базового класса для реализаций поддержки RSS и
///        Atom различных версий.</p>
///     <p>Объекты класса обеспечивают трансляцию специфичных элементов документа в единый набор
///        свойств, независимых от формата, а также преобразования имен свойств в camelCase
///        формат.</p>
///   </details>
abstract class XML_Feed_Protocol implements Core_PropertyAccessInterface {

  protected $xpath;
  protected $version ;

///   <protocol name="creating">

///   <method name="__construct">
///     <brief>Конструктор</brief>
///     <args>
///       <arg name="document" type="DOMDocument" brief="исходный документ" />
///       <arg name="protocol" type="string" brief="протокол документа" />
///     </args>
///     <body>
  public function __construct(DOMDocument $document, $version) {
    $this->xpath = new DOMXPath($document);
    $this->version = $version;
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="quering">

///   <method name="link_for" returns="string" stereotype="abstract">
///     <brief>Возвращает значение стандартного свойства link</brief>
///     <args>
///       <arg name="element" type="DOMElement" brief="элемент документа" />
///     </args>
///     <body>
  abstract public function link_for(DOMNode $element);
///     </body>
///   </method>

///   <method name="title_for" returns="string" stereotype="abstract">
///     <brief>Возвращает значение стандартного свойства title</brief>
///     <args>
///       <arg name="element" type="DOMNode" brief="элемент документа" />
///     </args>
///     <body>
  abstract public function title_for(DOMNode $element);
///     </body>
///   </method>

///   <method name="published_at_for" returns="Time.DateTime" stereotype="abstract">
///     <brief>Возврашает дату публикации</brief>
///     <args>
///       <arg name="element" type="DOMNode" brief="элемент документа" />
///     </args>
///     <body>
  abstract public function published_at_for(DOMNode $element);
///     </body>
///   </method>

///   <method name="description_for" returns="string" stereotype="abstract">
///     <brief>Возвращает значение стандартного свойства description</brief>
///     <args>
///       <arg name="element" type="DOMNode" brief="элемент документа" />
///     </args>
///     <body>
  abstract public function description_for(DOMNode $element);
///     </body>
///   </method>

///   <method name="get_entries" returns="DOMNodeList" stereotype="abstract">
///     <brief>Возвращает список записей ленты</brief>
///     <body>
  abstract public function get_entries();
///     </body>
///   </method>

///   <method name="get_feed" returns="DOMElement" stereotype="abstract">
///     <brief>Возврашает элемент, соответствующий ленте</brief>
///     <body>
  abstract public function get_feed();
///     </body>
///   </method>

///   <method name="get" returns="string">
///     <brief>Возвращает значение произвольного свойства с преобразованием типов и имен</brief>
///     <args>
///       <arg name="query"   type="string" />
///       <arg name="element" type="DOMElement" />
///       <arg name="index"   type="int" default="0" />
///     </args>
///     <details>
///       <p>На данный момент преобразование типов заключается в конвертации строковых значений,
///          представляющих даты, в объекты типа Time.DateTime.</p>
///     </details>
///     <body>
  abstract public function get($query, DOMElement $element, $index = 0);
///     </body>
///   </method>

///   </protocol>

///   <protocol name="supporting">

///   <method name="xpath" returns="mixed" access="protected">
///     <brief>Выполняет XPath-запрос</brief>
///     <args>
///       <arg name="query"   type="string"                    brief="запрос" />
///       <arg name="element" type="DOMElement" default="null" brief="элемент документа" />
///       <arg name="index"   type="int"        default="0"    brief="числовой индекс результата" />
///     </args>
///     <body>
  protected function xpath($query, DOMElement $element = null, $index = 0) {
    $r = $element ? $this->xpath->query($query, $element) : $this->xpath->query($query);
    return $index === null ? $r : $r->item($index);
  }
///     </body>
///   </method>

///   <method name="has_child_nodes_for" returns="boolean" access="protected">
///     <brief>Выполняет проверку наличия дочерних узлов у заданного элемента</brief>
///     <args>
///       <arg name="element" brief="элемент документа" />
///     </args>
///     <body>
  protected function has_child_nodes_for($element) {
    return ($element instanceof DOMElement) && $this->xpath('./*', $element);
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="accessing" interface="Core.PropertyAccessInterface">

///   <method name="__get" returns="mixed">
///     <brief>Возвращает значение свойства</brief>
///     <args>
///       <arg name="property" type="string" brief="имя свойства" />
///     </args>
///     <body>
  public function __get($property) {
    switch ($property) {
      case 'version':
        return $this->$property;
      default:
        throw new Core_MissingPropertyException($property);
    }
  }
///     </body>
///   </method>

///   <method name="__set" returns="XML.Feed.Protocol">
///     <brief>Устанавливает значение свойства</brief>
///     <args>
///       <arg name="property" type="string" brief="имя свойства" />
///       <arg name="value" brief="значение свойства" />
///     </args>
///     <details>
///       <p>Свойства объекта доступны только на чтение.</p>
///     </details>
///     <body>
  public function __set($property, $value) { throw new Core_ReadOnlyObjectException($this); }
///     </body>
///   </method>

///   <method name="__isset" returns="boolean">
///     <brief>Проверяет установку значения свойства</brief>
///     <args>
///       <arg name="property" type="string" brief="имя свойства" />
///     </args>
///     <body>
  public function __isset($property) {
    switch ($property) {
      case 'version':
        return isset($this->version);
      default:
        return false;
    }
  }
///     </body>
///   </method>

///   <method name="__unset">
///     <brief>Удаляет значение свойства</brief>
///     <args>
///       <arg name="property" type="string" />
///     </args>
///     <details>
///       <p>Свойства объекта доступны только на чтение</p>
///     </details>
///     <body>
  public function __unset($property) { throw new Core_ReadOnlyObjectException($this); }
///     </body>
///   </method>

///   </protocol>
}
/// </class>


/// <class name="XML.Feed.RSS" extends="XML.Feed.Protocol">
///   <brief>Адаптер для протокола RSS</brief>
class XML_Feed_RSS extends XML_Feed_Protocol {

///   <protocol name="quering">

///   <method name="link_for" returns="string">
///     <brief>Возвращает значение стандартного свойства link</brief>
///     <args>
///       <arg name="element" type="DOMNode" brief="элемент документа" />
///     </args>
///     <body>
  public function link_for(DOMNode $element) { return $this->xpath('./link', $element)->nodeValue; }
///     </body>
///   </method>

///   <method name="title_for" returns="string">
///     <brief>Возвращает значение стандартного свойства title</brief>
///     <args>
///       <arg name="element" type="DOMNode" brief="элемент документа" />
///     </args>
///     <body>
  public function title_for(DOMNode $element) { return $this->xpath('./title', $element)->nodeValue; }
///     </body>
///   </method>

///   <method name="published_at_for" returns="Time.DateTime" stereotype="abstract">
///     <brief>Возврашает дату публикации</brief>
///     <args>
///       <arg name="element" type="DOMNode" brief="элемент документа" />
///     </args>
///     <body>
  public function published_at_for(DOMNode $element) {
    return $this->get($this->version == 'rss 1.0' ? './dc:date' : './pubDate', $element);
  }
///     </body>
///   </method>

///   <method name="description_for" returns="string">
///     <brief>Возвращает значение стандартного свойства description</brief>
///     <args>
///       <arg name="element" type="DOMNode" brief="элемент документа" />
///     </args>
///     <body>
  public function description_for(DOMNode $element) { return $this->xpath('./description', $element)->nodeValue; }
///     </body>
///   </method>

///   <method name="get_entries" returns="DOMNodeList">
///     <brief>Возвращает список элементов записей ленты</brief>
///     <body>
  public function get_entries() { return $this->xpath->query('//item'); }
///     </body>
///   </method>

///   <method name="get_feed" returns="DOMNodeList">
///     <brief>Возвращает элемент, соответствующий ленте</brief>
///     <body>
  public function get_feed() { return $this->xpath->query('//channel')->item(0); }
///     </body>
///   </method>

///   <method name="get" returns="mixed">
///     <brief>Возвращает значение произвольного свойства с преобразованием типов и имен</brief>
///     <args>
///       <arg name="query"   type="string"     brief="запрашиваемое свойство" />
///       <arg name="element" type="DOMElement" brief="исходный элемент" />
///       <arg name="index"   type="int" default="0" brief="индекс элемента" />
///     </args>
///     <body>
  public function get($query, DOMElement $element, $index = 0) {
    return ($this->has_child_nodes_for($e = $this->xpath($query, $element, $index))) ?
             $e : ((($r = $e->nodeValue) && preg_match('{date$}i', $query)) ? Time::DateTime($r) : $r);
  }
///     </body>
///   </method>

///   </protocol>
}
/// </class>


/// <class name="XML.Feed.Atom" extends="XML.Feed.Protocol">
///   <brief>Адаптер для протокола Atom</brief>
class XML_Feed_Atom extends XML_Feed_Protocol {

///   <protocol name="quering">

///   <method name="link_for" returns="string" stereotype="abstract">
///     <brief>Возвращает значение стандартного свойства link</brief>
///     <args>
///       <arg name="element" type="DOMNode" brief="элемент документа" />
///     </args>
///     <body>
  public function link_for(DOMNode $element) {
    $e = $this->xpath("./link[rel='alternate']", $element);
    if (!$e) $e = $this->xpath('./link', $element);
    return $this->xpath('/*')->getAttribute('xml:base').
           (Core_Strings::starts_with($h = $e->getAttribute('href'), '/') ? substr($h, 1) : $h);
  }
///     </body>
///   </method>


///   <method name="title_for" returns="string">
///     <brief>Возвращает значение стандартного свойства title</brief>
///     <args>
///       <arg name="element" type="DOMNode" brief="элемент документа" />
///     </args>
///     <body>
  public function title_for(DOMNode $element) {
    return $this->node_value_for($this->xpath('./title', $element));
  }
///     </body>
///   </method>

///   <method name="description_for" returns="string">
///     <brief>Возвращает значение стандартного свойства description</brief>
///     <args>
///       <arg name="element" type="DOMNode" brief="элемент документа" />
///     </args>
///     <body>
  public function description_for(DOMNode $element) {
    foreach (array('content', 'subtitle', 'info') as $name)
      if ($n = $this->xpath($name, $element)) return $this->node_value_for($n);
  }
///     </body>
///   </method>

///   <method name="published_at_for" returns="Time.DateTime" stereotype="abstract">
///     <brief>Возврашает дату публикации</brief>
///     <args>
///       <arg name="element" type="DOMNode" brief="элемент документа" />
///     </args>
///     <body>
  public function published_at_for(DOMNode $element) {
    return
      $this->version == 'atom 0.3' ?
        ($r = $this->get('./created', $element) ? $r : $this->get('./modified', $element)) :
        ($r = $this->get('./published', $element) ? $r : $this->get('./updated', $element));
  }
///     </body>
///   </method>

///   <method name="get_entries" returns="DOMNodeList">
///     <brief>Возвращает список элементов записей ленты</brief>
///     <body>
  public function get_entries() { return $this->xpath('//entry', null, null); }
///     </body>
///   </method>

///   <method name="get_feed" returns="DOMNode">
///     <brief>Возвращает элемент, соответствующий ленте</brief>
///     <body>
  public function get_feed() { return $this->xpath('/*'); }
///     </body>
///   </method>

///   <method name="get" returns="mixed">
///     <brief>Возвращает значение произвольного свойства с преобразованием типов и имен</brief>
///     <args>
///       <arg name="query"   type="string"     brief="запрашиваемое свойство" />
///       <arg name="element" type="DOMElement" brief="исходный элемент" />
///       <arg name="index"   type="int" default="0" brief="индекс элемента" />
///     </args>
///     <body>
  public function get($query, DOMElement $element, $index = 0) {
    if ($this->has_child_nodes_for($e = $this->xpath($query, $element, $index))) return $e;

    return (($r = $this->node_value_for($e)) && Core_Strings::ends_with($query, 'ed')) ?
      Time::DateTime($r) : $r;
  }
///     </body>
///   </method>

///   <method name="node_value_for" returns="mixed" access="protected">
///     <brief>Возвращает значение узла элемента</brief>
///     <args>
///       <arg name="element" brief="элемент документа" />
///     </args>
///     <body>
  protected function node_value_for($element) {
    if (!($element instanceof DOMElement)) return '';
    if ($element->getAttribute('type') == 'xhtml' ||
        $element->getAttribute('type') == 'application/xhtml+xml') {
      $div = $this->xpath('./div', $element);
      $div->removeAttribute('ns');
      return $element->ownerDocument->saveXML($div);
    }
    return $element->nodeValue;
  }
///     </body>
///   </method>

///   </protocol>
}
/// </class>


/// <class name="XML.Feed.Element">
///   <brief>Базовый класс элемента RSS-ленты</brief>
///   <implements interface="Core.PropertyAccessInterface" />
///   <implements interface="Core.EqualityInterface" />
///   <details>
///     <p>Объекты класса содержат ссылку на соответствующий элемент DOM и ссылку на объект
///        адаптера протокола, который позволяет правильным образом интерпретировать содержимое
///        элемента.</p>
///     <p>Поддерживаются следующие стандартные свойства:</p>
///     <dl>
///       <dt>link</dt><dd>ссылка</dd>
//        <dt>title</dt><dd>заголовок</dd>
///       <dt>description</dt><dd>описание</dd>
///       <dt>published_at</dt><dd>дата публикации</dd>
///     </dl>
///   </details>
class XML_Feed_Element implements Core_PropertyAccessInterface, Core_EqualityInterface {

  protected $element;
  protected $protocol;

///   <protocol name="creating">

///   <method name="__construct">
///     <brief>Конструктор</brief>
///     <args>
///       <arg name="element" type="DOMElement" brief="элемент DOM" />
///       <arg name="protocol" type="XML.Feed.Protocol" brief="используемый адаптер протокола" />
///     </args>
///     <body>
  public function __construct(DOMElement $element, XML_Feed_Protocol $protocol) {
    $this->element  = $element;
    $this->protocol = $protocol;
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="accessing" interface="Core.PropertyAccessInterface">

///   <method name="__get" returns="mixed">
///     <brief>Возвращает значение свойства</brief>
///     <args>
///       <arg name="property" type="string" brief="имя свойства" />
///     </args>
///     <body>
  public function __get($property) {
    switch ($property) {
      case 'link':
      case 'title':
      case 'description':
      case 'published_at':
        $m = $property.'_for';
        return $this->protocol->$m($this->element);
      default:
        return property_exists($this, $property) ?
          $this->$property :
          $this->get(Core_Strings::to_camel_case($property, true));
    }
  }
///     </body>
///   </method>

///   <method name="__set" returns="mixed">
///     <brief>Устанавливает значение свойства</brief>
///     <args>
///       <arg name="property" type="string" brief="имя свойства" />
///       <arg name="value" brief="значение свойства" />
///     </args>
///     <details>
///       <p>Свойства объекта доступны только на чтение</p>
///     </details>
///     <body>
  public function __set($property, $value) { throw new Core_ReadOnlyObjectException($this); }
///     </body>
///   </method>

///   <method name="__isset" returns="boolean">
///     <brief>Проверяет установку значения свойства</brief>
///     <args>
///       <arg name="property" type="string" brief="имя свойства" />
///     </args>
///     <body>
  public function __isset($property) {
    switch ($property) {
      case 'link':
      case 'title':
      case 'description':
      case 'published_at':
        $m = $property.'_for';
        return $this->protocol->$m($this->element) !== null;
      default:
        return isset($this->$property) ||
               $this->get(Core_Strings::to_camel_case($property, true)) !== null;
    }
  }
///     </body>
///   </method>

///   <method name="__unset">
///     <args>
///       <arg name="property" type="string" brief="имя свойства" />
///     </args>
///     <body>
  public function __unset($property) { throw new Core_ReadOnlyObjectException($this); }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="quering">

///   <method name="get" returns="mixed">
///     <brief>Возвращает значение произвольного свойства или xpath-запроса</brief>
///     <args>
///       <arg name="query" type="string" brief="xpath-запрос" />
///       <arg name="index" type="int" default="0" brief="индекс свойства" />
///     </args>
///     <body>
  public function get($query, $index = 0) {
    return ($e = $this->protocol->get($query, $this->element, $index)) instanceof DOMElement ?
             new XML_Feed_Element($e, $this->protocol) : $e;
  }
///     </body>
///   </method>

///   <method name="equals" returns="boolean">
///     <brief>Выполняет проверку на равенство другому элементу</brief>
///     <args>
///       <arg name="top" brief="сравниваемый объект" />
///     </args>
///     <body>
  public function equals($to) {
    return ($to instanceof self) && $this->element->isSameNode($to->element);
  }
///     </body>
///   </method>

///   </protocol>
}
/// </class>
/// <aggregation>
///   <source class="XML.Feed.Element" role="element" multiplicity="1" />
///   <target class="XML.Feed.Protocol" role="protocol" multiplicity="1" />
/// </aggregation>

/// <class name="XML.Feed.Feed" extends="XML.Feed.Element">
///   <brief>Объектное представление ленты</brief>
///   <implements interface="Core.IndexedAccessInterface" />
///   <implements interface="IteratorAggregate" />
///   <implements interface="Core.CountInterface" />
///   <depends supplier="XML.Feed.Iterator" stereotype="creates" />
class XML_Feed_Feed extends XML_Feed_Element
  implements Core_IndexedAccessInterface, IteratorAggregate, Core_CountInterface {

  protected $document;
  protected $entries;

///   <protocol name="creating">

///   <method name="__construct">
///     <brief>Конструктор</brief>
///     <args>
///       <arg name="document" class="DOMDocument" brief="XML-документ" />
///       <arg name="protocol" type="XML.Feed.Protocol" brief="протокол документа" />
///     </args>
///     <body>
  public function __construct(DOMDocument $document, XML_Feed_Protocol $protocol) {
    parent::__construct($protocol->get_feed(), $protocol);
    $this->entries  = $this->protocol->get_entries();
    $this->document = $document;
  }
///     </body>
///   </method>

///   </protocol>


///   <protocol name="counting" interface="Core.CountInterface">

///   <method name="count" returns="int">
///     <brief>Возвращает количество записей в ленте</brief>
///     <body>
  public function count() { return $this->entries->length; }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="indexing" interface="Core.IndexedAccesInterface">

///   <method name="offsetGet" returns="XML.Feed.Entry">
///     <brief>Возвращает запись ленты по ее числовому индексу</brief>
///     <args>
///       <arg name="index" type="int" brief="числовой индекс" />
///     </args>
///     <body>
  public function offsetGet($index) {
    return ($e = $this->entries->item($index)) ? new XML_Feed_Entry($e, $this->protocol) : null;
  }
///     </body>
///   </method>

///   <method name="offsetSet">
///     <brief>Запрещает явную установку записи ленты</brief>
///     <args>
///       <arg name="index" type="int" brief="числовой индекс" />
///       <arg name="value" />
///     </args>
///     <body>
  public function offsetSet($index, $value) { throw new Core_ReadOnlyObjectException($this); }
///     </body>
///   </method>

///   <method name="offsetExists" returns="boolean">
///     <brief>Проверяет существование записи ленты с данным числовым индексом</brief>
///     <args>
///       <arg name="index" type="int" brief="числовой индекс" />
///     </args>
///     <body>
  public function offsetExists($index) { return $index > 0 && $index < $this->entries->length; }
///     </body>
///   </method>

///   <method name="offsetUnset">
///     <brief>Запрещает удаление записи ленты по ее числовому индексы</brief>
///     <args>
///       <arg name="index" type="int" brief="числовой индекс" />
///     </args>
///     <body>
  public function offsetUnset($index) { throw new Core_ReadOnlyObjectException($this); }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="iterating" interface="IteratorAggregate">

///   <method name="getIterator" returns="XML.Feed.Iterator">
////    <brief>Возвращает итератор по записям ленты</brief>
///     <body>
  public function getIterator() { return new XML_Feed_Iterator($this); }
///     </body>
///   </method>

///   </protocol>
}
/// </class>


/// <class name="XML.Feed.Entry" extends="XML.Feed.Element">
///   <brief>Объектное представление записи ленты</brief>
///   <details>
///     <p>Тут пока ничего нет :|</p>
///   </details>
class XML_Feed_Entry extends XML_Feed_Element {}
/// </class>


/// <class name="XML.Feed.Iterator">
///   <implements interface="Iterator" />
class XML_Feed_Iterator implements Iterator {

   protected $index = -1;
   protected $current;
   protected $feed;

///   <protocol name="creating">

///   <method name="__construct">
///     <args>
///       <arg name="feed" type="XML.Feed.Feed" brief="собственно лента" />
///     </args>
///     <body>
   public function __construct(XML_Feed_Feed $feed) { $this->feed = $feed; }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="iterating">

///   <method name="rewind">
///     <brief>Сбрасывает итератор</brief>
///     <body>
   public function rewind() {
     $this->index = -1;
     $this->current = $this->next();
   }
///     </body>
///   </method>

///   <method name="current" returns="XML.Feed.Entry">
///     <brief>Возвращает текущую запись</brief>
///     <body>
   public function current() { return $this->current; }
///     </body>
///   </method>

///   <method name="key" returns="int">
///     <brief>Возвращает индекс текущей записи</brief>
///     <body>
   public function key() { return $this->index; }
///     </body>
///   </method>

///   <method name="next" returns="XML.Feed.Entry">
///     <brief>Переходит к следующей записи</brief>
///     <body>
   public function next() {
     $this->index++;
     return $this->current = $this->feed[$this->index];
   }
///     </body>
///   </method>

///   <method name="valid" returns="boolean">
///     <brief>Проверяет нахождение внутри диапазона записей</brief>
///     <body>
   public function valid() { return $this->index >= 0 && $this->index < count($this->feed); }
///     </body>
///   </method>

///   </protocol>
}
/// </class>

/// </module>
