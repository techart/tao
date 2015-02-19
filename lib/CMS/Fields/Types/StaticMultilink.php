<?php
/**
 * @package CMS\Fields\Types\StaticMultilink
 */

Core::load('CMS.Fields.Types.Multilink');

class CMS_Fields_Types_StaticMultilink extends CMS_Fields_Types_Multilink implements Core_ModuleInterface
{
	const VERSION = '0.0.0';

	protected function assign_item_from_object($form, $object, $name, $parms, $key)
	{
		$delimiter = $parms['delimiter'] ? $parms['delimiter'] : ',';
		$selected_items = explode($delimiter, $object->$name);
		$k = str_replace($name, '', $key);
		$form[$key] = in_array($k, $selected_items);
	}

	public function assign_to_object($form, $object, $name, $data)
	{
		$object->$name = '';
		
		$values = array_keys(CMS::items_for_select($data['items']));
		$quoted_values = array_map(array(Core_Regexps, 'quote'), $values);
		$string_values = implode('|', $quoted_values);
		$pcre = "!^{$name}({$string_values})$!";
		
		$delimiter = isset($data['delimiter']) ? $data['delimiter'] : ',';
		foreach ($form->fields as $n => $f) {
			if (preg_match($pcre, $n, $m)) {
				if ($f->value) {
					$object->$name .= $delimiter . $m[1];
				}
			}
		}

		$object->$name = trim($object->$name, $delimiter);
	}
}

class CMS_Fields_Types_StaticMultilink_ValueContainer extends CMS_Fields_ValueContainer implements Core_ModuleInterface
{
	public function render()
	{
		if (!$this->type->access($this->name, $this->data, 'container_render', $this->item, $this)) {
			return '';
		}

		$value  = '';
		$values = $this->as_array();
		$items  = CMS::items_for_select($this->data['items']);

		foreach($values as $v) {
			$value .= ($value ? ', ' : '') . $items[$v];
		}

		return $value;
	}

	public function as_array()
	{
		$delimiter = $this->data['delimiter'] ? $this->data['delimiter'] : ',';
		return explode($delimiter, $this->value());
	}

	public function set($value)
	{
		if (is_array($value)) {
			$delimiter = $this->data['delimiter'] ? $this->data['delimiter'] : ',';
			$value = implode($delimiter, $value);
		}

		return parent::set($value);
	}
}