<?php

class CMS_Fields_Types_ExtendedMultilang extends CMS_Fields_AbstractField implements Core_ModuleInterface
{
	public function enable_multilang()
	{
		return true;
	}

	public function schema($name, &$data)
	{
		$data['multilang'] = true;
		$langs = $this->data_langs($data);
		if (!$langs) {
			return parent::schema($name, $data);
		}
		$data['sqltypes']  = array();
		foreach($langs as $lang => $ldata) {
			$data['sqltypes']["{$name}_{$lang}"] = $data['sqltype'];
		}
	}

	public function assign_from_object($form, $object, $name, $data)
	{
		if (!$this->access($name, $data, 'assign_from_object', $object, $form)) {
			return;
		}

		$data['multilang'] = true;
		$langs = $this->data_langs($data);
		if (!$langs) {
			return parent::assign_from_object($form, $object, $name, $data);
		}

		foreach($langs as $lang => $ldata) {
			$_name = $this->name_lang($name, $lang);
			$form[$_name] = $object["{$name}_{$lang}"];
		}
	}

	public function assign_to_object($form, $object, $name, $data)
	{
		if (!$this->access($name, $data, 'assign_to_object', $object, $form)) {
			return;
		}

		$data['multilang'] = true;
		$langs = $this->data_langs($data);
		if (!$langs) {
			return parent::assign_from_object($form, $object, $name, $data);
		}

		foreach($langs as $lang => $ldata) {
			$_name = $this->name_lang($name, $lang);
			$object["{$name}_{$lang}"] = $form[$_name];
		}
	}

	public function form_fields($form, $name, $data)
	{
		$data['multilang'] = true;
		return parent::form_fields($form, $name, $data);
	}

	public function render($name, $data, $template = 'template', $parms = array())
	{
		$data['multilang'] = true;
		return parent::render($name, $data, $template, $parms);
	}

	public function render_in_layout($name, $data, $layout = false, $template = 'template', $parms = array())
	{
		$data['multilang'] = true;
		return parent::render_in_layout($name, $data, $layout, $template, $parms);
	}
}

class App_Fields_ExtendedMultilang_ValueContainer extends CMS_Fields_ValueContainer implements Core_ModuleInterface
{
	public function set($value)
	{
		$this->data['multilang'] = true;
		$langs = $this->type->data_langs($this->data);

		if (!$langs) {
			return parent::set($value);
		}

		foreach($langs as $lang => $ldata) {
			$this->item->{"{$this->name}_{$lang}"} = $value[$lang];
		}

		return $this;
	}

	public function value()
	{
		$this->data['multilang'] = true;
		$langs = $this->type->data_langs($this->data);

		if (!$langs) {
			return parent::value();
		}

		$value = array();
		foreach($langs as $lang => $ldata) {
			$value[$lang] = $this->item->{"{$this->name}_{$lang}"};
		}

		return $value;
	}

	public function render()
	{
		if (!$this->type->access($this->name, $this->data, 'container_render', $this->item, $this)) {
			return '';
		}

		$values = $this->value();
		if (!is_array($values)) {
			return parent::render();
		}

		return $values[CMS::site_lang()];
	}
}