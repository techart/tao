<?php
/**
 * @package CMS\Fields\Types\Multilink
 */


class CMS_Fields_Types_Multilink extends CMS_Fields_AbstractField implements Core_ModuleInterface {
	const VERSION = '0.0.0';

	protected function get_items_keys($name, $parms)
	{
		$keys = isset($parms['__items_keys']) ? $parms['__items_keys'] : array();
		if (!empty($keys)) {
			return $keys;
		}
		$items = isset($parms['__items']) ? $parms['__items'] : CMS::items_for_select($parms['items']);
		foreach($items as $key => $data) {
			if (!($data instanceof DB_ORM_Entity) && Core_Types::is_iterable($data)) {
				foreach($data as $key => $value) {
					$keys[] = $this->checkbox_name($name, $key);
				}
			} else {
				$keys[] = $this->checkbox_name($name, $key);
			}
		}
		return $keys;
	}

	public function form_fields($form,$name,$data) {
		foreach ($this->get_items_keys($name, $data) as $key) {
			$this->add_form_fields($form, $name, $data, $key);
		}	
	}

	protected function add_form_fields($form, $name, $data, $key)
	{
		$form->checkbox($key);
		return $this;
	}
	
	public function assign_from_object($form, $object, $name, $parms) {
		foreach ($this->get_items_keys($name, $parms) as $key) {
			$this->assign_item_from_object($form, $object, $name, $parms, $key);
		}
	}

	protected function assign_item_from_object($form, $object, $name, $parms, $key)
	{
		$form[$key] = $object[$key];
		return $this;
	}

	protected function checkbox_name($name, $key)
	{
		return $name.$key;
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
