<?php

class CMS_Vars2_Type_String extends CMS_Var implements Core_ModuleInterface {
	const VERSION = '0.0.0';

	public function type_title() {
		return '%LANG{en}String%LANG{ru}Строка';
	}

	public function fields() {
		return array(
			'value' => array(
				'type' => 'input',
				'multilang' => true,
				'tab' => 'default',
				'style' => 'width:100%;'
			),
		);
	}

	public function get() {
		return CMS::lang($this['value']);
	}

}