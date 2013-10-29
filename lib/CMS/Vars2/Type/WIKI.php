<?php
Core::load('CMS.Vars2.Type.String');

class CMS_Vars2_Type_WIKI extends CMS_Vars2_Type_String implements Core_ModuleInterface
{
	public function type_title()
	{
		return '%LANG{en}WIKI text%LANG{ru}Текст с WIKI-разметкой';
	}
	
	public function fields()
	{
		return array(
			'value' => array(
				'type' => 'wiki',
				'multilang' => true,
				'tab' => 'default',
				'style' => 'width:100%;height:400px;'
			),
		);
	}
	
	public function get()
	{
		$value = parent::get();
		return CMS::parse_wiki($value);
	}
}