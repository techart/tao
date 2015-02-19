<?php

/**
 * @package CMS\Fields\Types\Array
 */
class CMS_Fields_Types_Array extends CMS_Fields_AbstractField implements Core_ModuleInterface
{

	const VERSION = '0.0.0';

	static function initialize()
	{
		Validation::use_tests(array('validate_array' => 'CMS_Fields_Types_Array_Test'));
	}

	public function field_src_name($name, $data)
	{
		return "{$name}_src";
	}

	public function form_fields($form, $name, $data)
	{
		$src = $this->field_src_name($name, $data);
		$form->input($src);
		return $form;
	}

	public function sqltypes($name, $data)
	{
		$src = $this->field_src_name($name, $data);
		return array(
			$name => 'text',
			$src => 'text',
		);
	}

	public function init_value($name, $data, $item)
	{
		parent::init_value($name, $data, $item);
		$src = $this->field_src_name($name, $data);
		$item[$src] = '';
		return $item;
	}

	public function serialized($name, $data)
	{
		return array($name);
	}

	public function preprocess($template, $name, $data)
	{
		parent::preprocess($template, $name, $data);
		$parms = $template->parms;
		if (empty($parms['tagparms']['style'])) {
			$template->update_parm('tagparms', array('style' => 'width: 100%;height:100px;'));
		}
		if (empty($parms['tagparms']['class'])) {
			$template->update_parm('tagparms', array('class' => 'use-tab-key'));
		}
		return $template;
	}

	public function assign_to_object($form, $object, $name, $data)
	{
		$src_name = $this->field_src_name($name, $data);
		$src = $form[$src_name];
		$parsed = CMS::parse_parms($src);
		if (is_array($parsed)) {
			$object[$name] = $parsed;
		}
		$object[$src_name] = $form[$src_name];
		return $this;
	}

	public function assign_from_object($form, $object, $name, $data)
	{
		$src = $this->field_src_name($name, $data);
		$form[$src] = $object[$src];
		return $this;
	}

	public function form_validator($form, $name, $data)
	{
		$src = $this->field_src_name($name, $data);
		return $this->validator($form)->validate_array($src);
	}

	public function copy_value($from, $to, $name, $data)
	{
		$src = $this->field_src_name($name, $data);
		$to[$name] = $from[$name];
		$to[$src] = $from[$src];
		return $this;
	}

}

/**
 * Class CMS_Fields_Types_Array_ValueContainer
 *
 * @property CMS_Fields_Types_Array $type
 */
class CMS_Fields_Types_Array_ValueContainer extends CMS_Fields_ValueContainer implements Core_ModuleInterface
{
	/**
	 * Устанавливает значение поля из массива
	 *
	 * @param array|Traversable $value
	 *
	 * @return $this
	 */
	public function set($value)
	{
		$this->item->{$this->name} = $value;
		$this->item->{$this->type->field_src_name($this->name, $this->data)} = CMS::unparse_parms($value);
		return $this;
	}
}

class CMS_Fields_Types_Array_Test extends Validation_AbstractTest
{

	protected $attribute;

	public function __construct($attribute)
	{
		$this->attribute = $attribute;
	}

	public function test($object, Validation_Errors $errors, $array_access = false)
	{
		$value = trim($this->value_of_attribute($object, $this->attribute, $array_access));
		$parsed = CMS::parse_parms($value);
		if (is_string($parsed)) {
			$errors->reject_value($this->attribute, $parsed);
		}
	}

}
