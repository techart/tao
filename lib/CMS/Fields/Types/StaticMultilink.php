<?php
Core::load('CMS.Fields.Types.Multilink');

class CMS_Fields_Types_StaticMultilink extends CMS_Fields_Types_Multilink implements Core_ModuleInterface {
	const VERSION = '0.0.0';
	
	protected function assign_item_from_object($form, $object, $name, $parms, $key) {
		$selected_items = explode(',', $object->$name);
		$k = str_replace($name, '', $key);
		$form[$key] = in_array($k, $selected_items);
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
