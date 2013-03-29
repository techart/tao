<?php

class CMS_Fields_Types_Multilink extends CMS_Fields_AbstractField implements Core_ModuleInterface {
	const VERSION = '0.0.0';
	
	public function assign_from_object($form, $object, $name, $parms) {
		$items = isset($parms['__items']) ? $parms['__items'] : CMS::items_for_select($parms['items']);
		foreach($items as $key => $data) {
			if (!($data instanceof DB_ORM_Entity) &&Core_Types::is_iterable($data))
				foreach($data as $key => $value)
					$this->add_to_form($name, $key, $form, $object);
			else 
				$this->add_to_form($name, $key, $form, $object);
		}
	}
	
	protected function add_to_form($name, $key, $form, $object) {
		$n = $name.$key;
		$form->checkbox($name.$key);
		$form[$n] = $object[$n];
	}
	
	public function assign_to_object($form,$object,$name,$data) {
		foreach ($form->fields as $n => $f) {
			if (preg_match("!^{$name}[\d]+!", $n, $m)) {
				$object[$n] = $f->value;
			}
		}
	}
	
	protected function preprocess($template, $name, $data) {
		$t = parent::preprocess($template, $name, $data);
		return $t->with('items', isset($data['__items']) ? $data['__items'] : CMS::items_for_select($data['items']));
	}

	public function copy_value($from, $to, $name, $data) {
		if ($from instanceof DB_ORM_Entity && $to instanceof DB_ORM_Entity) {
			foreach ($from->attrs as $k => $v) {
				if (preg_match("!^{$name}[\d]+!", $k, $m)) {
					$to[$k] = $v;
				}
			}
		}
		return parent::copy_value($from, $to, $name, $data);
	}
	
	
}
