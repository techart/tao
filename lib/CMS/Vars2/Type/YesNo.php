<?php

/**
 * @package CMS\Vars2\Type\YesNo
 */
class CMS_Vars2_Type_YesNo extends CMS_Var implements Core_ModuleInterface
{
	public function type_title()
	{
		return '%LANG{en}Yes/No selection%LANG{ru}Выбор Да/Нет';
	}
	
	public function fields()
	{
		return array(
			'value' => array(
				'type' => 'select',
				'tab' => 'default',
				'items' => array(
					0 => CMS::lang('%LANG{en}No%LANG{ru}Нет'),
					1 => CMS::lang('%LANG{en}Yes%LANG{ru}Да'),
				),
			),
		);
	}
	
	public function get()
	{
		return (bool)$this['value'];
	}
	
	public function preview()
	{
		return ((bool)$this['value'])? CMS::lang('%LANG{en}Yes%LANG{ru}Да') : CMS::lang('%LANG{en}No%LANG{ru}Нет');
	}

}