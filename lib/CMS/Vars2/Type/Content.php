<?php
/**
 * @package CMS\Vars2\Type\Content
 */


class CMS_Vars2_Type_Content extends CMS_Var implements Core_ModuleInterface
{
	public function type_title() {
		return '%LANG{en}Content block%LANG{ru}Контентная область';
	}
	
	public function fields() {
		return array(
			'value' => array(
				'type' => 'content',
				'multilang' => true,
				'tab' => 'default',
				'style' => 'width:100%;'
			),
			'attaches' => array(
				'type' => 'attaches',
				'tab' => 'default',
			),
		);
	}

	public function render() {
		return $this->field('value')->render();
	}

}