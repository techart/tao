<?php

/**
 * @package CMS\Fields\Types\Parms
 */
class CMS_Fields_Types_Parms extends CMS_Fields_AbstractField implements Core_ModuleInterface
{

	const MODULE = 'CMS.Fields.Types.Parms';
	const VERSION = '0.0.0';

	static function initialize()
	{
		Validation::use_tests(array('validate_parms' => 'CMS_Fields_Types_Parms_Test'));
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
		parent::assign_to_object($form, $object, $name, $data);
		if (isset($data['parse_to'])) {
			$parsed = CMS::parse_parms($form[$name]);
			if (is_array($parsed)) {
				$object[$data['parse_to']] = CMS::parse_parms($form[$name]);
			}
		}
	}

	public function form_validator($form, $name, $data)
	{
		$this->validator($form)->validate_parms($name);
	}

	public function copy_value($from, $to, $name, $data)
	{
		if (isset($data['parse_to'])) {
			$key = $data['parse_to'];
			$to[$key] = $from[$key];
		}
		return parent::copy_value($from, $to, $name, $data);
	}

}

class CMS_Fields_Types_Parms_Test extends Validation_AbstractTest
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
