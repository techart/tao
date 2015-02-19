<?php
class XML_Reader extends XMLReader implements IteratorAggregate, Core_ModuleInterface
{
	/**
	 * 
	 */
	public function getIterator()
	{
		return $this->elements();
	}

	/**
	 * 
	 */
	public function elements($tag_name = '*')
	{
		return Core::make('XML.Reader.Element.Iterator', $this, $tag_name);
	}
}

/**
 * 
 */
class XML_Reader_Iterator implements Iterator, Core_ModuleInterface
{
	protected $reader;
	private $index;
	private $last_read;
	private $element_stack;

	public function __construct(XMLReader $reader)
	{
		$this->reader = $reader;
	}

	public function next_by_type($node_type)
	{
		if (is_null(self::valid())) {
			self::rewind();
		}

		while(self::valid()) {
			if ($this->reader->nodeType == $node_type) {
				break;
			}
			self::next();
		}

		return self::valid() ? self::current() : null;
	}

	public function next_element()
	{
		return self::next_by_type(XMLReader::ELEMENT);
	}

	public function next_element_by_name($tag_name = '*')
	{
		while(self::next_element()) {
			if ($tag_name == '*' || $this->reader->name == $tag_name) {
				break;
			}
			self::next();
		}
	}

	public function rewind()
	{
		if ($this->reader->nodeType == XMLReader::NONE) {
			self::next();
		} elseif (is_null($this->last_read)) {
			$this->last_read = true;
		}

		$this->index = 0;
	}

	public function next()
	{
		if (($this->last_read = $this->reader->read())
			&& $this->reader->nodeType == XMLReader::ELEMENT
		) {
			$depth = $this->reader->depth;
			$this->element_stack[$depth] = self::current();
			if (count($this->element_stack) != $depth + 1) {
				$this->element_stack = array_slice($this->element_stack, 0, $depth + 1);
			}
		}
		$this->index++;
	}

	public function current()
	{
		Core::load('XML.Reader.Node');
		return XML_Reader_Node::factory($this->reader);
	}

	public function key()
	{
		return $this->index;
	}

	public function valid()
	{
		return $this->last_read;
	}
}