<?php
/**
 * @package CMS\Fields\Types\Checkbox
 */


class CMS_Fields_Types_Checkbox extends CMS_Fields_AbstractField implements Core_ModuleInterface {

	const VERSION = '0.0.0';

	public function form_fields($form,$name,$data) {
		return $form->checkbox($name);
	}
	
	public function sqltype()
	{
		return 'tinyint(4)';
	}

}

class CMS_Fields_Types_Checkbox_ValueContainer extends CMS_Fields_ValueContainer {

	public function render($values = array()) {
		if (empty($values)) {
			$values = array(0 => 'Нет' , 1 => 'Да');
		}
		$v = parent::render();
		return $values[$v];
	}

}
