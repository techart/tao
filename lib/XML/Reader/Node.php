<?
Core::load('XML.Reader.Iterator');

class XML_Reader_Node implements Core_ModuleInterface
{
	public $name;

	protected $reader;
	protected $type;
	
	protected $attributes;
	protected $child_elements;
	protected $child_nodes;
	protected $to_simple_xml;
	protected $to_array;

	public static function factory(XMLReader $reader)
	{
		switch ($reader->nodeType) {
			case XMLREADER::ELEMENT:   $module = 'XML.Reader.Element'; break;
			case XMLREADER::ATTRIBUTE: $module = 'XML.Reader.Attribute'; break;
			default:                   $module = 'XML.Reader.Node'; break;
		}

		return Core::amake($module, func_get_args());
	}

	public function __construct(XMLReader $reader)
	{
		$this->reader = $reader;
		$this->type   = $this->reader->nodeType;
		$this->name   = $this->reader->name;
	}

	public function __get($prop)
	{
		switch ($prop) {
			case 'attributes':
			case 'child_elements':
			case 'child_nodes':
			case 'xml':
			case 'inner_xml':
			case 'outer_xml':
				return $this->$prop();

			case 'string':
			case 'simple_xml':
			case 'dom_node':
				$method = 'as_'. $prop;
				return $this->$method();
		}

		if (isset($this->reader->$prop)) {
			return $this->reader->$prop;
		}

		throw new Core_MissingPropertyException($prop);
	}

	public function __set($prop, $value)
	{
		if (isset($this->$prop)) {
			throw new Core_ReadOnlyPropertyException($prop);
		}

		$this->reader->$prop = $value;
	}

	public function __call($method, $args)
	{
		if (method_exists($this->reader, $method)) {
			return call_user_func_array(array($this->reader, $method), $args);
		}

		throw new Core_MissingMethodException($method);
	}

	public function __toString()
	{
		return $this->as_string();
	}

	public function as_string()
	{
		if (method_exists($this->reader, 'readString')) {
            return $this->reader->readString();
        }

        if (XMLReader::NONE == $this->reader->nodeType) {
            return '';
        }

        if (($node = $this->reader->expand()) === false) {
            throw new BadMethodCallException('Unable to expand node.');
        }

        return $node->textContent;
	}

	public function as_simple_xml()
	{
		if (is_null($this->to_simple_xml)) {
			$this->to_simple_xml = new SimpleXMLElement($this->readOuterXML());
		}

		return $this->to_simple_xml;
	}

	public function as_dom_node()
	{
		if (($node = $this->reader->expand()) === false) {
			throw new BadMethodCallException('unable to expand node');
			
		}

		$dom      = new DomDocument;
		$dom_node = $dom->importNode($node, true);
		$dom->appendChild($dom_node);

		return $dom_node;
	}

	
	public function xml()
	{
		return $this->inner_xml();
	}

	public function inner_xml()
	{
		if (method_exists($this->reader, 'readInnerXML') && 1 != 1) {
			return $this->reader->readInnerXML();
		}

		if (XMLReader::NONE == $this->reader->nodeType) {
			return '';
		}

		$xml  = '';
		$node = $this->as_dom_node();
		foreach($node->childNodes as $child) {
			$xml .= $child->ownerDocument->saveXML($child);
		}

		return $xml;
	}

	public function outer_xml()
	{
		if (method_exists($this->reader, 'readOuterXML')) {
			return $this->reader->readOuterXML();
		}

		if (XMLReader::NONE == $this->reader->nodeType) {
			return '';
		}
		
		$node = $this->as_dom_node();
		return $node->ownerDocument->saveXML($node);
	}

	public function attribute($attr_name, $default = null)
	{
		$value = $this->reader->getAttribute($attr_name);
		return $value ? $value : $default;
	}

	public function attributes()
	{
		if (is_null($this->attributes)) {
			$this->attributes = Core::make('XML.Reader.Attribute.Iterator', $this->reader);
		}

		return $this->attributes;
	}

	public function child_elements()
	{
		if (is_null($this->child_elements)) {
			$this->child_elements = Core::make('XML.Reader.Childs.Element.Iterator', $this->reader);
		}

		return $this->child_elements;
	}

	public function child_nodes()
	{
		if (is_null($this->child_nodes)) {
			$this->child_nodes = Core::make('XML.Reader.Childs.Node.Iterator', $this->reader);
		}

		return $this->child_nodes;
	}
}

/**
 * 
 */
class XML_Reader_Node_Iterator extends XML_Reader_Iterator {}

/**
 * 
 */
class XML_Reader_Childs_Node_Iterator extends XML_Reader_Iterator
{
	protected $stop_depth;

	public function __construct(XMLReader $reader)
	{
		parent::__construct($reader);
		$this->stop_depth = $this->reader->depth;
	}

	public function rewind()
	{
		parent::next();
		parent::rewind();
	}

	public function valid()
	{
		return parent::valid() && $this->reader->depth > $this->stop_depth;
	}
}