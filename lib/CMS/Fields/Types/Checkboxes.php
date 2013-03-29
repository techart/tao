<?php

class CMS_Fields_Types_Checkboxes extends CMS_Fields_AbstractField implements Core_ModuleInterface {
	const VERSION = '0.0.0';

	public function assign_from_object($form, $object, $name, $parms) {
		$items = isset($parms['__items']) ? $parms['__items'] : CMS::items_for_select($parms['items']);
		$iobjects = array();
		foreach ($items as $id => $title) $iobjects[] = (object) array('id' => $id, 'title' => $title);
		$form->object_multi_select($name, $iobjects);
		$form[$name] = $object->$name;
	}
	
	public function assign_to_object($form,$object,$name,$data) {
		$object->$name = $form[$name];
	}
	
	protected function preprocess($template, $name, $data) {
		$t = parent::preprocess($template, $name, $data);
		return $t->with('items', isset($data['__items']) ? $data['__items'] : CMS::items_for_select($data['items']));
	}

	public function copy_value($from, $to, $name, $data) {
		$to->$name = $from->$name;
		return $this;
	}
	
	
}