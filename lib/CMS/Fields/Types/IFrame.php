<?php
/**
 * @package CMS\Fields\Types\IFrame
 */

class CMS_Fields_Types_IFrame extends CMS_Fields_AbstractField implements Core_ModuleInterface
{
	public function form_fields($form, $name, $data)
	{
		return $form->input($name);
	}
}
