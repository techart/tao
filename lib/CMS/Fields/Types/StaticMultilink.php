<?php
Core::load('CMS.Fields.Types.Multilink');

class CMS_Fields_Types_StaticMultilink extends CMS_Fields_Types_Multilink implements Core_ModuleInterface {
	const VERSION = '0.0.0';
	
	protected function add_to_form($name, $key, $form, $object) {
		$selected_items = explode(',', $object->$name);
		$n = $name.$key;
		$form->checkbox($name.$key);
		$form[$n] = in_array($key, $selected_items);
	}
	
	public function assign_to_object($form,$object,$name,$data) {
		$object->$name = '';
		foreach ($form->fields as $n => $f) {
			if (preg_match("!^{$name}([\d]+)!", $n, $m)) {
				if($f->value) {
					$object->$name .= ',' . $m[1];
				}
			}
		}
		$object->$name = trim($object->$name, ',');
	}
}
