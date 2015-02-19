<?php

/**
 * @package CMS\Vars2\Type\Image
 */
class CMS_Vars2_Type_Image extends CMS_Var implements Core_ModuleInterface
{

	public function type_title()
	{
		return '%LANG{en}Image%LANG{ru}Изображение';
	}

	public function fields()
	{
		return array(
			'image' => array(
				'type' => 'image',
				'tab' => 'default',
			),
		);
	}

	public function get()
	{
		return $this->field('image');
	}

	public function preview()
	{
		CMS::layout_view()->use_scripts('/tao/scripts/admin/vars/image.js');
		Core::load('CMS.Images');
		$url = CMS_Images::modified_image('./' . $this['image'], '150x150');
		$id = 'container-' . md5($this['image']);
		return "<span class='var-image-preview' data-image='{$url}' data-container='{$id}'>[Image]</span>";
	}

}