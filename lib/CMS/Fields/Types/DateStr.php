<?php

class CMS_Fields_Types_DateStr extends CMS_Fields_AbstractField implements Core_ModuleInterface {

	const VERSION = '0.0.0';

	static function format_date($value,$data) {
		if (!isset($data['valid1970'])&&$value==0) return '';
		if (isset($data['format'])) $format = $data['format'];
		if (empty($format)) {
			$format = 'd.m.Y';
			if (isset($data['with_time'])&&$data['with_time']) $format = 'd.m.Y - H:i';
			if (isset($data['with_seconds'])&&$data['with_seconds']) $format = 'd.m.Y - H:i:s';
		}
		return CMS::date($format,$value);
	}

	public function view_value($value,$name,$data) {
		$value = parent::view_value($value,$name,$data);
		return self::format_date($value,$data);
	}

	public function assign_from_object($form,$object,$name,$data) {
		$value = is_object($object)?$object[$name]:$object;
		$value = $this->view_value($value,$name,$data);
		$form[$name] = $value;
	}

	public function assign_to_object($form,$object,$name,$data) {
		$object->$name = CMS::s2date($form[$name]);
	}
	
	public function sqltype() {
		return 'int';
	}
}

class CMS_Fields_Types_DateStr_ValueContainer extends CMS_Fields_ValueContainer {

	public function render() {
		$data = $this->data;
		$value = parent::value();
		return CMS_Fields_Types_DateStr::format_date($value,$data);
	}
}
