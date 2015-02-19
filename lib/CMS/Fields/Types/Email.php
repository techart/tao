<?php

/**
 * @package CMS\Fields\Types\Email
 */
class CMS_Fields_Types_Email extends CMS_Fields_AbstractField implements Core_ModuleInterface
{

	const VERSION = '0.0.0';

	public function view_value($value, $name, $data)
	{
		$v = trim(parent::view_value($value, $name, $data));
		if ($v != '') {
			$v = "<a href=\"mailto:$v\">$v</a>";
		}
		return $v;
	}

	protected function preprocess($template, $name, $data)
	{
		parent::preprocess($template, $name, $data);
		$params = $template->parms;
		if (!isset($params['tagparms']['style'])) {
			$template->update_parm('tagparms', array('style' => 'width:300px'));
		}
		return $this;
	}

}

class CMS_Fields_Types_Email_ValueContainer extends CMS_Fields_ValueContainer
{

	public function render()
	{
		$v = $this->value();
		if ($v != '') {
			$v = "<a href=\"mailto:$v\">$v</a>";
		}
		return $v;
	}

}
