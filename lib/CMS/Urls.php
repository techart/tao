<?php

class CMS_Urls implements Core_ModuleInterface
{

	public static function instance()
	{
		return new self();
	}

	protected $map;

	public function __construct()
	{
		$this->map = new ArrayObject();
	}

	public function map($name, $mapper)
	{
		$this->map[$name] = $mapper;
		return $this;
	}

	public function __get($property)
	{
		if (isset($this->map[$property])) {
			return $this->map[$property];
		} else {
			throw new Core_MissingPropertyException($property);
		}
	}

	public function __set($property, $mapper)
	{
		return $this->map($property, $mapper);
	}

	public function __isset($property)
	{
		return isset($this->map[$property]);
	}

	public function __unset($property)
	{
		unset($this->map[$property]);
	}

}