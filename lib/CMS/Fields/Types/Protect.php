<?php

/**
 * @package CMS\Fields\Types\Protect
 */
class CMS_Fields_Types_Protect extends CMS_Fields_AbstractField implements Core_ModuleInterface
{

	const VERSION = '0.0.0';

	static function initialize()
	{
		Validation::use_tests(array('validate_digital_protect' => 'CMS_Fields_Types_Protect_Test'));
	}

	static function field_code($name)
	{
		return "f_{$name}_code";
	}

	public function form_fields($form, $name, $data)
	{
		$form->input(self::field_code($name))->input($name);
	}

	public function form_validator($form, $name, $data)
	{
		$message = CMS::lang()->_common->captcha_error;
		if (isset($data['error_message'])) {
			$message = $data['error_message'];
		}
		if (isset($data['validate_error_message'])) {
			$message = $data['validate_error_message'];
		}
		$this->validator($form)->validate_digital_protect($name, $message);
	}

	public function layout($layout = false, $data = array())
	{
		if (isset($data['hidden']) && $data['hidden']) {
			return parent::layout('hidden', $data);
		}
		return parent::layout($layout, $data);
	}

	public function assign_from_object($form, $object, $name, $data)
	{
	}

	public function assign_to_object($form, $object, $name, $data)
	{
	}

	public function code($name)
	{
		$form = $this->view->helpers['forms']->form;
		$code = trim($form[self::field_code($name)]);
		if ($code == '') {
			$code = md5(time() . rand(11111, 99999));
			$form[self::field_code($name)] = $code;
		}
		return $code;
	}

	public function caption_content($name, $data)
	{
		$code = $this->code($name);
		return "<img src='/digital-protect/$code' class='digital-protect digital-protect-$name'>";
	}

	public function validator_tagparms($name, $data, &$tagparms)
	{
		$rc = false;
		if (isset($data['ajax']) && $data['ajax']) {
			$rc = true;
			$code = $this->code($name);
			$url = "/check-digital-protect/$name/$code";
			$tagparms['data-validate-ajax'] = $url;
			$class = isset($tagparms['class']) ? $tagparms['class'] : '';
			$class = trim("$class validable");
			$tagparms['class'] = $class;
			if (isset($data['error_message'])) {
				$tagparms['data-error-message'] = $data['error_message'];
			}
		}
		return $rc;
	}

	public function digit_expr($d)
	{
		$exprs = array(
			'0' => array(0, 'Math.sin(0)', '8-8'),
			'1' => array(1, '6*0+1'),
			'2' => array(2, '6/3', '4-2'),
			'3' => array(3, '6/2', 'Math.floor(Math.log(24.346))'),
			'4' => array(4, '2*2', '2+2'),
			'5' => array(5, '30/6', '2+3', '(6+2)/2+1'),
			'6' => array(6, 'Math.floor(Math.cos(1.3)*26)'),
			'7' => array(7, 'Math.floor(Math.sin(2.1)*8)+1'),
			'8' => array(8, '2*2*2', '64/8'),
			'9' => array(9, '3*3', '3+2*3', '27/3'),
		);
		if (isset($exprs[$d])) {
			$exprs = $exprs[$d];
			if (count($exprs) > 0) {
				$p = rand(0, count($exprs));
				if (isset($exprs[$p])) {
					return $exprs[$p];
				}
			}
		}
		return "'$d'";
	}

}

class CMS_Fields_Types_Protect_Test extends Validation_AbstractTest
{

	protected $attribute;
	protected $message;

	public function __construct($attribute, $message)
	{
		$this->attribute = $attribute;
		$this->message = $message;
	}

	public function test($object, Validation_Errors $errors, $array_access = false)
	{
		Core::load('CMS.Protect');
		$value = trim($this->value_of_attribute($object, $this->attribute, $array_access));
		$code = $this->value_of_attribute($object, CMS_Fields_Types_Protect::field_code($this->attribute), $array_access);
		$key = CMS_Protect::key($code);

		if ($value == '' || $value != $key) {
			$errors->reject_value($this->attribute, $this->message);
		}
		return false;
	}

}
