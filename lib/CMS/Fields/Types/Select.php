<?php

class CMS_Fields_Types_Select extends CMS_Fields_AbstractField implements Core_ModuleInterface {

	const VERSION = '0.0.0';
	
	
	public function view_value($value,$name,$data) {
		$value = parent::view_value($value,$name,$data);
		if (isset($data['items'])) {
			$items = CMS::items_for_select($data['items']);
			if (isset($items[$value])) $value = $items[$value];
		}
		return $value;
	}

	public function form_fields($form,$name,$data) {
		$items = $this->get_items($name, $data);
		if ($langs = $this->data_langs($data)) {
			foreach($langs as $lang => $ldata) {
				$form->select($this->name_lang($name,$lang), $items);
			}
			return $form;
		}
		else {
			return $form->select($name, $items);
		}
	}
	

	protected function get_items($name, $data) {
		$items = array();
		if (isset($data['__items'])) {
			$items = $data['__items'];
		} else {
			$items = CMS::items_for_select($data['items']);
		}
		return $items;
	}

	protected function preprocess($template, $name, $data) {
		$t = parent::preprocess($template, $name, $data);
		$items = $this->get_items($name, $data);
		return $t->with('items', $items);
	}
}

class CMS_Fields_Types_Select_ValueContainer extends CMS_Fields_ValueContainer {

	public function render() {
		return $this->type->view_value($this->value(), $this->name, $this->data);
	}

}
