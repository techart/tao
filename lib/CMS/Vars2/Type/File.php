<?php

/**
 * @package CMS\Vars2\Type\File
 */
class CMS_Vars2_Type_File extends CMS_Var implements Core_ModuleInterface
{

	public function type_title()
	{
		return '%LANG{en}File%LANG{ru}Файл';
	}

	public function fields()
	{
		return array(
			'file' => array(
				'type' => 'upload',
				'tab' => 'default',
			),
		);
	}

	public function get()
	{
		return $this->field('file');
	}

	public function preview()
	{
		$caption = CMS::lang()->_common->ta_download;
		$url = $this->field('file')->url();
		return "<a href='$url'>$caption</a>";
	}

}