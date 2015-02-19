<?php

/**
 * @package CMS\Vars2\Type\Dir
 */
class CMS_Vars2_Type_Dir extends CMS_Var implements Core_ModuleInterface
{
	const VERSION = '0.0.0';

	public $vars = array();

	public function type_title()
	{
		return '%LANG{en}Dir%LANG{ru}Блок настроек';
	}

	public function fields()
	{
		return array();
	}

	public function get()
	{
		return array();
	}

	public function is_dir()
	{
		return true;
	}

}