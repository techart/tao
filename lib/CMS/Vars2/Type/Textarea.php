<?php
/**
 * @package CMS\Vars2\Type\Textarea
 */

Core::load('CMS.Vars2.Type.String');

class CMS_Vars2_Type_Textarea extends CMS_Vars2_Type_String implements Core_ModuleInterface
{

	public function type_title()
	{
		return '%LANG{en}Textarea%LANG{ru}Текст';
	}

	public function fields()
	{
		return array(
			'value' => array(
				'type' => 'textarea',
				'multilang' => true,
				'tab' => 'default',
				'style' => 'width:100%;height:400px;'
			),
		);
	}

}