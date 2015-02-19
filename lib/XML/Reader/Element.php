<?php
Core::load('XML.Reader.Node', 'XML.Reader.Iterator');

class XML_Reader_Element extends XML_Reader_Node implements IteratorAggregate, Core_ModuleInterface
{
	protected $attributes;

	public function __construct(XMLReader $reader)
	{
		if ($reader->nodeType != XMLReader::ELEMENT) {
			throw new Core_InvalidArgumentValueException('reader', $reader->nodeType);
		}

		parent::__construct($reader);
		$this->attributes = new ArrayObject(parent::attributes()->to_array());
	}

	public function getIterator()
	{
		return $this->child_elements();
	}
}

class XML_Reader_Element_Iterator extends XML_Reader_Iterator
{
	protected $tag_name;
	private $index;

	public function __construct(XMLReader $reader, $tag_name = '*')
	{
		parent::__construct($reader);
		$this->tag_name = $tag_name;
	}

	public function rewind()
	{
		parent::rewind();
		parent::next_element_by_name($this->tag_name);
		$this->did_rewind = true;
        $this->index     = 0;
	}

	public function current()
	{
		$this->did_rewind || self::rewind();

		return parent::current();
	}

	public function key()
	{
		return $this->index;
	}

	public function next()
	{
		if (parent::valid()) {
            $this->index++;
        }
        parent::next();
        parent::next_element_by_name($this->tag_name);
	}
}

class XML_Reader_Childs_Element_Iterator extends XML_Reader_Element_Iterator
{
	protected $stop_depth;
	protected $descend_tree;

	public function __construct(XMLReader $reader, $tag_name = '*', $descend_tree = false)
	{
		parent::__construct($reader, $tag_name);
		$this->stop_depth   = $this->reader->depth;
		$this->descend_tree = $descend_tree;
	}

	public function rewind()
	{
		parent::rewind();
		parent::next();
	}

	public function next()
	{
		do {
			parent::next();
			if ($this->descend_tree || $this->reader->depth == $this->stop_depth + 1) {
				break;
			}
		} while($this->valid());
	}

	public function valid()
	{
		return parent::valid() && $this->reader->depth > $this->stop_depth;
	}
}