<?php

class CMS_Vars2_Type_Gallery extends CMS_Var implements Core_ModuleInterface
{
	public function type_title() {
		return '%LANG{en}Image gallery%LANG{ru}Галерея';
	}
	
	public function fields() {
		return array(
			'gallery' => array(
				'type' => 'gallery',
				'tab' => 'default',
				'multiple' => true,
			),
		);
	}
	
	public function render() {
		return $this->field('gallery')->render();
	}
	
	public function get()
	{
		return $this->field('gallery');
	}

}