<?php

/**
 * @package CMS\Vars2\Type\Array
 */
class CMS_Vars2_Type_Array extends CMS_Var implements Core_ModuleInterface
{
	const VERSION = '0.0.0';

	public function type_title()
	{
		return '%LANG{en}Array%LANG{ru}Массив';
	}

	public function fields()
	{
		return array(
			'array' => array(
				'type' => 'array',
				'tab' => 'default',
				'style' => 'width:100%;height:400px'
			),
		);
	}

	public function get()
	{
		return $this['array'];
	}

	public function preview()
	{
		$v = $this->get();
		if (!is_array($v)) {
			return '';
		}
		$c = count($v);
		return "Array({$c})";
	}

}