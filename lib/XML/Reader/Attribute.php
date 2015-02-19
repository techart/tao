<?php
Core::load('XML.Reader.Node');

class XML_Reader_Attribute extends XML_Reader_Node
{
	protected $value;

	public function __construct(XMLReader $reader)
	{
		parent::__construct($reader);

		$this->name  = $this->reader->name;
		$this->value = $this->reader->value;
	}

	public function __toString()
	{
		return $this->value();
	}

	public function value()
	{
		return $this->value;
	}
}


class XML_Reader_Attribute_Iterator implements Iterator, Countable, ArrayAccess
{
	protected $reader;
	protected $valid;
	protected $to_array;

	public function __construct(XMLReader $reader)
	{
		$this->reader = $reader;
	}

	public function count()
	{
		return $this->reader->attributeCount;
	}

	public function current()
	{
		return XML_Reader_Node::factory($this->reader);
	}

	public function key()
	{
		return $this->reader->name;
	}

	public function next()
	{
		$this->valid = $this->reader->moveToNextAttribute();
		if (!$this->valid) {
			$this->reader->moveToElement();
		}
	}

	public function valid()
	{
		return $this->valid;
	}

	public function rewind()
	{
		$this->valid = $this->reader->moveToFirstAttribute();
	}

	public function to_array()
	{
		if (is_null($this->to_array)) {
			$this->to_array = iterator_to_array($this);
		}

		return $this->to_array;
	}

	public function offsetExists($offset)
	{
		return $this->to_array() && isset($this->to_array[$offset]);
	}

	public function offsetGet($offset)
	{
		return $this->offsetExists($offset) ? $this->to_array[$offset] : null;
	}

	public function offsetSet($offset, $value)
	{
		throw new Core_ReadOnlyPropertyException($value);
	}

	public function offsetUnset($offset)
	{
		throw new Core_ReadOnlyPropertyException($offset);
	}

	public function __toArray()
	{
		return $this->to_array();
	}
}