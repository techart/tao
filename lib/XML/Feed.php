<?php
/**
 * XML.Feed
 *
 * Чтение RSS-лент различных форматов
 *
 * <p>Модуль предоставляет простейший объектный интерфейс к RSS-лентам различных форматов.</p>
 * <p>Лента представляется в виде объекта класса XML.Feed.Feed, отдельные записи — в виде
 * объектов класса XML.Feed.Entry. </p>
 * <p>Доступ к основным атрибутам ленты и записи осуществляется через набор стандартных свойств
 * соответствующих объектов, на данный момент это title, description и link, в дальнейшем
 * их число может быть расширено.</p>
 * <p>Доступ к остальным атрибутам выполняется динамически с помощью
 * Core.PropertyAccessInterface, при этом выполняется автоматическое преобразования в
 * camelCase для имен атрибутов.</p>
 * <p>Объект класса XML.Feed.Feed предоставляет доступ к отдельным записям ленты с
 * использованием двух интерфейсов: Core.IndexedAccessInterface и IteratorAggregate.
 * Индексированный доступ позволяет получить доступ к записи по ее числовому индексу в
 * ленте, итератор — пройти по всем записям ленты.</p>
 * <p>Разбор лент выполняется объектами класса XML.Feed.Parser, определение формата ленты
 * производится автоматически. Вместо явного создания объекта парсера можно использовать
 * процедурный интерфейс, предоставляемый классом модуля, в этом случае используется
 * создаваемый автоматически экземпляр парсера.</p>
 * <p>Пример использования:</p>
 * <code>
 * $feed = XML_Feed::fetch('http://www.techart.ru/rss/news/');
 *
 * printf("Protocol: %s", $feed->protocol);
 *
 * foreach ($feed as $entry) printf("%s\n%s", $entry->link, $entry->title);
 *
 * $entry = $feed[0];
 * printf("First entry:\nurl: %s\ntitle: %s", $entry->link, $entry->title);
 * </code>
 *
 * @package XML\Feed
 * @version 0.1.1
 */
Core::load('XML', 'Net.Agents.HTTP');

/**
 * Модуль XML.Feed
 *
 * <p>Реализует процедурный интерфейс модуля. Интерфейс позволяет получить объектное
 * представление ленты тремя способами:</p>
 * XML_Feed::parse($xml)выполняет разбор строки, содержащей данные ленты;
 * XML_Feed::fetch($url, $agent)загружает ленту по указанному адресу;
 * XML_Feed::load($path)загружает ленту из файла.
 *
 * @package XML\Feed
 */
class XML_Feed implements Core_ModuleInterface
{

	const VERSION = '0.1.1';

	static private $parser;

	/**
	 * Выполняет инициализацию модуля
	 *
	 */
	static public function initialize()
	{
		self::$parser = new XML_Feed_Parser();
	}

	/**
	 * Выполняет разбор ленты, переданной в виде строки
	 *
	 * @param string $xml
	 *
	 * @return XML_Feed
	 */
	static public function parse($xml)
	{
		return self::$parser->parse($xml);
	}

	/**
	 * Выкачивает ленту с указанного адреса и выполняет разбор
	 *
	 * @param string                  $url
	 * @param Net_HTTP_AgentInterface $agent
	 *
	 * @return XML_Feed
	 */
	static public function fetch($url, Net_HTTP_AgentInterface $agent = null)
	{
		return self::$parser->fetch($url, $agent);
	}

	/**
	 * @param string $path
	 *
	 * @return XML_Feed
	 */
	static public function load($path)
	{
		return self::$parser->load($path);
	}

}

/**
 * Выполняет разбор ленты, формирует ее объектное представление.
 *
 * @package XML\Feed
 */
class XML_Feed_Parser
{

	/**
	 * Загружает документ ленты с указанного адреса и выполняет разбор
	 *
	 * @param string                  $url
	 * @param Net_HTTP_AgentInterface $agent
	 *
	 * @return XML_Feed_Feed
	 */
	public function fetch($url, Net_HTTP_AgentInterface $agent = null)
	{
		if (!isset($agent)) {
			$agent = Net_HTTP::Agent();
		}

		$r = $agent->send(Net_HTTP::Request($url));

		if ($r->status->is_success) {
			return $this->parse($r->body, $url);
		}

		throw new XML_Feed_BadURLException($url);
	}

	/**
	 * Загружает документ из файла и выполняет разбор
	 *
	 * @param string $path
	 *
	 * @return XML_Feed_Feed
	 */
	public function load($path)
	{
		if ($xml = file_get_contents($path)) {
			return $this->parse($xml, $path);
		}

		throw new XML_Feed_BadFileException($path);
	}

	/**
	 * Выполняет разбор документа ленты
	 *
	 * @param string $xml
	 *
	 * @return XML_Feed_Feed
	 */
	public function parse($xml, $path = null)
	{
		$document = XML::load(str_replace('xmlns=', 'ns=', $xml));
		if (!($document instanceof DOMDocument)) {
			throw new XML_Feed_BadFileException($path);
		}
		return new XML_Feed_Feed($document, $this->detect_protocol_for($document));
	}

	/**
	 * Определяет формат ленты
	 *
	 * @param DOMDocument $document
	 *
	 * @return XML_Feed_Protocol
	 */
	protected function detect_protocol_for(DOMDocument $document)
	{
		$tag = $document->documentElement;
		$version = $tag->getAttribute('version');

		switch ($tag->tagName) {
			case 'rss':
				return new XML_Feed_RSS($document, 'rss ' . ($version ? $version : '2.0'));
			case 'rdf:RDF':
				return new XML_Feed_RSS($document, 'rss 1.0');
			case 'feed':
				return new XML_Feed_Atom($document, 'atom ' . ($version ? $version : '1.0'));
			default:
				throw new XML_Feed_UnsupportedProtocolException($tag->tagName);
		}
	}

}

/**
 * Базовый класс исключений модуля
 *
 * @package XML\Feed
 */
class XML_Feed_Exception extends Core_Exception
{
}

/**
 * Ошибка: некорректный URL документа или отсутствующий документ
 *
 * <p>Поддерживаемые свойства:</p>
 * urlURL документа
 *
 * @package XML\Feed
 */
class XML_Feed_BadURLException extends XML_Feed_Exception
{

	protected $url;

	/**
	 * Конструктор
	 *
	 * @param string $url
	 */
	public function __construct($url)
	{
		$this->url = $url;
		parent::__construct("Can't fetch feed from $url");
	}

}

/**
 * Ошибка: некорректный путь к файлу или содержимое файла документа
 *
 * @package XML\Feed
 */
class XML_Feed_BadFileException extends XML_Feed_Exception
{

	protected $path;

	/**
	 * Конструктор
	 *
	 * @param string $path
	 */
	public function __construct($path)
	{
		$this->path = $path;
		parent::__construct("Can't load feed from $path");
	}

}

/**
 * Ошибка: неподдерживаемый протокол
 *
 * @package XML\Feed
 */
class XML_Feed_UnsupportedProtocolException extends XML_Feed_Exception
{

	protected $tag;

	/**
	 * @param string $tag
	 */
	public function __construct($tag)
	{
		$this->tag = $tag;
		parent::__construct("Unsupported protocol tag: $tag");
	}

}

/**
 * Абстрактный класс протокола документа
 *
 * <p>Предназначен для использования в качестве базового класса для реализаций поддержки RSS и
 * Atom различных версий.</p>
 * <p>Объекты класса обеспечивают трансляцию специфичных элементов документа в единый набор
 * свойств, независимых от формата, а также преобразования имен свойств в camelCase
 * формат.</p>
 *
 * @abstract
 * @package XML\Feed
 */
abstract class XML_Feed_Protocol implements Core_PropertyAccessInterface
{

	protected $xpath;
	protected $version;

	/**
	 * Конструктор
	 *
	 * @param DOMDocument $document
	 * @param string      $protocol
	 */
	public function __construct(DOMDocument $document, $version)
	{
		$this->xpath = new DOMXPath($document);
		$this->version = $version;
	}

	/**
	 * Возвращает значение стандартного свойства link
	 *
	 * @abstract
	 *
	 * @param DOMElement $element
	 *
	 * @return string
	 */
	abstract public function link_for(DOMNode $element);

	/**
	 * Возвращает значение стандартного свойства title
	 *
	 * @abstract
	 *
	 * @param DOMNode $element
	 *
	 * @return string
	 */
	abstract public function title_for(DOMNode $element);

	/**
	 * Возврашает дату публикации
	 *
	 * @abstract
	 *
	 * @param DOMNode $element
	 *
	 * @return Time_DateTime
	 */
	abstract public function published_at_for(DOMNode $element);

	/**
	 * Возвращает значение стандартного свойства description
	 *
	 * @abstract
	 *
	 * @param DOMNode $element
	 *
	 * @return string
	 */
	abstract public function description_for(DOMNode $element);

	/**
	 * Возвращает список записей ленты
	 *
	 * @abstract
	 * @return DOMNodeList
	 */
	abstract public function get_entries();

	/**
	 * Возврашает элемент, соответствующий ленте
	 *
	 * @abstract
	 * @return DOMElement
	 */
	abstract public function get_feed();

	/**
	 * Возвращает значение произвольного свойства с преобразованием типов и имен
	 *
	 * @param string     $query
	 * @param DOMElement $element
	 * @param int        $index
	 *
	 * @return string
	 */
	abstract public function get($query, DOMElement $element, $index = 0);

	/**
	 * Выполняет XPath-запрос
	 *
	 * @param string     $query
	 * @param DOMElement $element
	 * @param int        $index
	 *
	 * @return mixed
	 */
	protected function xpath($query, DOMElement $element = null, $index = 0)
	{
		$r = $element ? $this->xpath->query($query, $element) : $this->xpath->query($query);
		return $index === null ? $r : $r->item($index);
	}

	/**
	 * Выполняет проверку наличия дочерних узлов у заданного элемента
	 *
	 * @param  $element
	 *
	 * @return boolean
	 */
	protected function has_child_nodes_for($element)
	{
		return ($element instanceof DOMElement) && $this->xpath('./*', $element);
	}

	/**
	 * Возвращает значение свойства
	 *
	 * @param string $property
	 *
	 * @return mixed
	 */
	public function __get($property)
	{
		switch ($property) {
			case 'version':
				return $this->$property;
			default:
				throw new Core_MissingPropertyException($property);
		}
	}

	/**
	 * Устанавливает значение свойства
	 *
	 * @param string $property
	 * @param        $value
	 *
	 * @return XML_Feed_Protocol
	 */
	public function __set($property, $value)
	{
		throw new Core_ReadOnlyObjectException($this);
	}

	/**
	 * Проверяет установку значения свойства
	 *
	 * @param string $property
	 *
	 * @return boolean
	 */
	public function __isset($property)
	{
		switch ($property) {
			case 'version':
				return isset($this->version);
			default:
				return false;
		}
	}

	/**
	 * Удаляет значение свойства
	 *
	 * @param string $property
	 */
	public function __unset($property)
	{
		throw new Core_ReadOnlyObjectException($this);
	}

}

/**
 * Адаптер для протокола RSS
 *
 * @package XML\Feed
 */
class XML_Feed_RSS extends XML_Feed_Protocol
{

	/**
	 * Возвращает значение стандартного свойства link
	 *
	 * @param DOMNode $element
	 *
	 * @return string
	 */
	public function link_for(DOMNode $element)
	{
		return $this->xpath('./link', $element)->nodeValue;
	}

	/**
	 * Возвращает значение стандартного свойства title
	 *
	 * @param DOMNode $element
	 *
	 * @return string
	 */
	public function title_for(DOMNode $element)
	{
		return $this->xpath('./title', $element)->nodeValue;
	}

	/**
	 * Возврашает дату публикации
	 *
	 * @abstract
	 *
	 * @param DOMNode $element
	 *
	 * @return Time_DateTime
	 */
	public function published_at_for(DOMNode $element)
	{
		return $this->get($this->version == 'rss 1.0' ? './dc:date' : './pubDate', $element);
	}

	/**
	 * Возвращает значение стандартного свойства description
	 *
	 * @param DOMNode $element
	 *
	 * @return string
	 */
	public function description_for(DOMNode $element)
	{
		return $this->xpath('./description', $element)->nodeValue;
	}

	/**
	 * Возвращает список элементов записей ленты
	 *
	 * @return DOMNodeList
	 */
	public function get_entries()
	{
		return $this->xpath->query('//item');
	}

	/**
	 * Возвращает элемент, соответствующий ленте
	 *
	 * @return DOMNodeList
	 */
	public function get_feed()
	{
		return $this->xpath->query('//channel')->item(0);
	}

	/**
	 * Возвращает значение произвольного свойства с преобразованием типов и имен
	 *
	 * @param string     $query
	 * @param DOMElement $element
	 * @param int        $index
	 *
	 * @return mixed
	 */
	public function get($query, DOMElement $element, $index = 0)
	{
		return ($this->has_child_nodes_for($e = $this->xpath($query, $element, $index))) ?
			$e : ((($r = $e->nodeValue) && preg_match('{date$}i', $query)) ? Time::DateTime($r) : $r);
	}

}

/**
 * Адаптер для протокола Atom
 *
 * @package XML\Feed
 */
class XML_Feed_Atom extends XML_Feed_Protocol
{

	/**
	 * Возвращает значение стандартного свойства link
	 *
	 * @abstract
	 *
	 * @param DOMNode $element
	 *
	 * @return string
	 */
	public function link_for(DOMNode $element)
	{
		$e = $this->xpath("./link[rel='alternate']", $element);
		if (!$e) {
			$e = $this->xpath('./link', $element);
		}
		return $this->xpath('/*')->getAttribute('xml:base') .
		(Core_Strings::starts_with($h = $e->getAttribute('href'), '/') ? substr($h, 1) : $h);
	}

	/**
	 * Возвращает значение стандартного свойства title
	 *
	 * @param DOMNode $element
	 *
	 * @return string
	 */
	public function title_for(DOMNode $element)
	{
		return $this->node_value_for($this->xpath('./title', $element));
	}

	/**
	 * Возвращает значение стандартного свойства description
	 *
	 * @param DOMNode $element
	 *
	 * @return string
	 */
	public function description_for(DOMNode $element)
	{
		foreach (array('content', 'subtitle', 'info') as $name)
			if ($n = $this->xpath($name, $element)) {
				return $this->node_value_for($n);
			}
	}

	/**
	 * Возврашает дату публикации
	 *
	 * @abstract
	 *
	 * @param DOMNode $element
	 *
	 * @return Time_DateTime
	 */
	public function published_at_for(DOMNode $element)
	{
		return
			$this->version == 'atom 0.3' ?
				($r = $this->get('./created', $element) ? $r : $this->get('./modified', $element)) :
				($r = $this->get('./published', $element) ? $r : $this->get('./updated', $element));
	}

	/**
	 * Возвращает список элементов записей ленты
	 *
	 * @return DOMNodeList
	 */
	public function get_entries()
	{
		return $this->xpath('//entry', null, null);
	}

	/**
	 * Возвращает элемент, соответствующий ленте
	 *
	 * @return DOMNode
	 */
	public function get_feed()
	{
		return $this->xpath('/*');
	}

	/**
	 * Возвращает значение произвольного свойства с преобразованием типов и имен
	 *
	 * @param string     $query
	 * @param DOMElement $element
	 * @param int        $index
	 *
	 * @return mixed
	 */
	public function get($query, DOMElement $element, $index = 0)
	{
		if ($this->has_child_nodes_for($e = $this->xpath($query, $element, $index))) {
			return $e;
		}

		return (($r = $this->node_value_for($e)) && Core_Strings::ends_with($query, 'ed')) ?
			Time::DateTime($r) : $r;
	}

	/**
	 * Возвращает значение узла элемента
	 *
	 * @param  $element
	 *
	 * @return mixed
	 */
	protected function node_value_for($element)
	{
		if (!($element instanceof DOMElement)) {
			return '';
		}
		if ($element->getAttribute('type') == 'xhtml' ||
			$element->getAttribute('type') == 'application/xhtml+xml'
		) {
			$div = $this->xpath('./div', $element);
			$div->removeAttribute('ns');
			return $element->ownerDocument->saveXML($div);
		}
		return $element->nodeValue;
	}

}

/**
 * Базовый класс элемента RSS-ленты
 *
 * <p>Объекты класса содержат ссылку на соответствующий элемент DOM и ссылку на объект
 * адаптера протокола, который позволяет правильным образом интерпретировать содержимое
 * элемента.</p>
 * <p>Поддерживаются следующие стандартные свойства:</p>
 * linkссылка
 *
 * //        titleзаголовок
 * descriptionописание
 * published_atдата публикации
 *
 * @package XML\Feed
 */
//        <dt>title</dt><dd>заголовок</dd>
class XML_Feed_Element implements Core_PropertyAccessInterface, Core_EqualityInterface
{

	protected $element;
	protected $protocol;

	/**
	 * Конструктор
	 *
	 * @param DOMElement        $element
	 * @param XML_Feed_Protocol $protocol
	 */
	public function __construct(DOMElement $element, XML_Feed_Protocol $protocol)
	{
		$this->element = $element;
		$this->protocol = $protocol;
	}

	/**
	 * Возвращает значение свойства
	 *
	 * @param string $property
	 *
	 * @return mixed
	 */
	public function __get($property)
	{
		switch ($property) {
			case 'link':
			case 'title':
			case 'description':
			case 'published_at':
				$m = $property . '_for';
				return $this->protocol->$m($this->element);
			default:
				return property_exists($this, $property) ?
					$this->$property :
					$this->get(Core_Strings::to_camel_case($property, true));
		}
	}

	/**
	 * Устанавливает значение свойства
	 *
	 * @param string $property
	 * @param        $value
	 *
	 * @return mixed
	 */
	public function __set($property, $value)
	{
		throw new Core_ReadOnlyObjectException($this);
	}

	/**
	 * Проверяет установку значения свойства
	 *
	 * @param string $property
	 *
	 * @return boolean
	 */
	public function __isset($property)
	{
		switch ($property) {
			case 'link':
			case 'title':
			case 'description':
			case 'published_at':
				$m = $property . '_for';
				return $this->protocol->$m($this->element) !== null;
			default:
				return isset($this->$property) ||
				$this->get(Core_Strings::to_camel_case($property, true)) !== null;
		}
	}

	/**
	 * @param string $property
	 */
	public function __unset($property)
	{
		throw new Core_ReadOnlyObjectException($this);
	}

	/**
	 * Возвращает значение произвольного свойства или xpath-запроса
	 *
	 * @param string $query
	 * @param int    $index
	 *
	 * @return mixed
	 */
	public function get($query, $index = 0)
	{
		return ($e = $this->protocol->get($query, $this->element, $index)) instanceof DOMElement ?
			new XML_Feed_Element($e, $this->protocol) : $e;
	}

	/**
	 * Выполняет проверку на равенство другому элементу
	 *
	 * @param  $top
	 *
	 * @return boolean
	 */
	public function equals($to)
	{
		return ($to instanceof self) && $this->element->isSameNode($to->element);
	}

}

/**
 * Объектное представление ленты
 *
 * @package XML\Feed
 */
class XML_Feed_Feed extends XML_Feed_Element
	implements Core_IndexedAccessInterface, IteratorAggregate, Core_CountInterface
{

	protected $document;
	protected $entries;

	/**
	 * Конструктор
	 *
	 * @param                   $document
	 * @param XML_Feed_Protocol $protocol
	 */
	public function __construct(DOMDocument $document, XML_Feed_Protocol $protocol)
	{
		parent::__construct($protocol->get_feed(), $protocol);
		$this->entries = $this->protocol->get_entries();
		$this->document = $document;
	}

	/**
	 * Возвращает количество записей в ленте
	 *
	 * @return int
	 */
	public function count()
	{
		return $this->entries->length;
	}

	/**
	 * Возвращает запись ленты по ее числовому индексу
	 *
	 * @param int $index
	 *
	 * @return XML_Feed_Entry
	 */
	public function offsetGet($index)
	{
		return ($e = $this->entries->item($index)) ? new XML_Feed_Entry($e, $this->protocol) : null;
	}

	/**
	 * Запрещает явную установку записи ленты
	 *
	 * @param int $index
	 * @param     $value
	 */
	public function offsetSet($index, $value)
	{
		throw new Core_ReadOnlyObjectException($this);
	}

	/**
	 * Проверяет существование записи ленты с данным числовым индексом
	 *
	 * @param int $index
	 *
	 * @return boolean
	 */
	public function offsetExists($index)
	{
		return $index > 0 && $index < $this->entries->length;
	}

	/**
	 * Запрещает удаление записи ленты по ее числовому индексы
	 *
	 * @param int $index
	 */
	public function offsetUnset($index)
	{
		throw new Core_ReadOnlyObjectException($this);
	}

	/**
	 * Возвращает итератор по записям ленты
	 *
	 * @return XML_Feed_Iterator
	 */
	public function getIterator()
	{
		return new XML_Feed_Iterator($this);
	}

}

/**
 * Объектное представление записи ленты
 *
 * <p>Тут пока ничего нет :|</p>
 *
 * @package XML\Feed
 */
class XML_Feed_Entry extends XML_Feed_Element
{
}

/**
 * @package XML\Feed
 */
class XML_Feed_Iterator implements Iterator
{

	protected $index = -1;
	protected $current;
	protected $feed;

	/**
	 * @param XML_Feed_Feed $feed
	 */
	public function __construct(XML_Feed_Feed $feed)
	{
		$this->feed = $feed;
	}

	/**
	 * Сбрасывает итератор
	 *
	 */
	public function rewind()
	{
		$this->index = -1;
		$this->current = $this->next();
	}

	/**
	 * Возвращает текущую запись
	 *
	 * @return XML_Feed_Entry
	 */
	public function current()
	{
		return $this->current;
	}

	/**
	 * Возвращает индекс текущей записи
	 *
	 * @return int
	 */
	public function key()
	{
		return $this->index;
	}

	/**
	 * Переходит к следующей записи
	 *
	 * @return XML_Feed_Entry
	 */
	public function next()
	{
		$this->index++;
		return $this->current = $this->feed[$this->index];
	}

	/**
	 * Проверяет нахождение внутри диапазона записей
	 *
	 * @return boolean
	 */
	public function valid()
	{
		return $this->index >= 0 && $this->index < count($this->feed);
	}

}

