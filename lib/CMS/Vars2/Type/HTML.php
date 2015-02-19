<?php
/**
 * @package CMS\Vars2\Type\HTML
 */

Core::load('CMS.Vars2.Type.String');

class CMS_Vars2_Type_HTML extends CMS_Vars2_Type_String implements Core_ModuleInterface
{

	public function type_title()
	{
		return '%LANG{en}HTML text%LANG{ru}Текст HTML';
	}

	public function fields()
	{
		return array(
			'value' => array(
				'type' => 'html',
				'multilang' => true,
				'tab' => 'default',
				'style' => 'width:95%;height:400px;'
			),
		);
	}

}