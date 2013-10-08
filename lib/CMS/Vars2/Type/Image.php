<?php

class CMS_Vars2_Type_Image extends CMS_Var implements Core_ModuleInterface {
	const VERSION = '0.0.0';

	public function type_title() {
		return '%LANG{en}Image%LANG{ru}Изображение';
	}

	public function fields() {
		return array(
			'image' => array(
				'type' => 'image',
				'tab' => 'default',
			),
		);
	}

	public function get() {
		return $this->field('image');
	}

}