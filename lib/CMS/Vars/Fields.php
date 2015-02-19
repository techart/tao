<?php
/**
 * @package CMS\Vars\Fields
 */

Core::load('CMS.Fields');

class CMS_Vars_Fields implements Core_ModuleInterface
{
	const VERSION = '0.0.0';

	static public function initialize()
	{
		CMS::field_type('varcode', 'CMS.Vars.Fields.Code');
	}
}

class CMS_Vars_Fields_Code extends CMS_Fields_AbstractField
{

	protected function preprocess($template, $name, $data)
	{
		$item = $this->get_item($name, $data);
		if ($item) {
			$prefix = $item->code ? str_replace($item->code, '', $item->full_code) : CMS::vars()->db()->find($this->request('parent_id'))->code . '.';
			// var_dump();
			$template->with('prefix', $prefix);
		}
		$template->use_script(CMS::stdfile_url('scripts/fields/varcode.js'));
		$template->use_style(CMS::stdfile_url('styles/fields/varcode.css'));
		return parent::preprocess($template, $name, $data);
	}

	public function action_prefix($name, $data, $item = false, $fields = array())
	{
		$id = (int)WS::env()->request['id'];
		$item = CMS::vars()->db()->find($id);
		if ($item) {
			return $item->full_code . '.';
		}
		return '';
	}

}