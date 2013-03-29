<?php

class CMS_Fields_Types_SQLDateStr extends CMS_Fields_AbstractField implements Core_ModuleInterface {

	const VERSION = '0.0.0';


	public function view_value($value,$name,$data) {
		$value = parent::view_value($value,$name,$data);
		if (!isset($data['valid1970'])&&CMS::date('Ymd',$value)=='19700101') return '';
		$format = $this->get_format($name, $data);
		$value = CMS::date($format,$value);
		return $value;
	}

	public function get_format($name, $data) {
		if (isset($data['format'])) $format = $data['format'];
		if (empty($format)) {
			$format = 'd.m.Y';
			if (isset($data['with_time'])&&$data['with_time']) $format = 'd.m.Y - H:i';
			if (isset($data['with_seconds'])&&$data['with_seconds']) $format = 'd.m.Y - H:i:s';
		}
		return $format;
	}

	public function assign_from_object($form,$object,$name,$data) {
		$value = (string)(is_object($object)?$object[$name]:$object);
		$value = $this->view_value($value,$name,$data);
		$form[$name] = $value;
	}


	public function assign_to_object($form,$object,$name,$data) {
		$object->$name = Time::parse(str_replace('- ', '', $form[$name]));
	}
	
	public function sqltype() {
		return 'datetime';
	}

}


class CMS_Fields_Types_SQLDateStr_ValueContainer extends CMS_Fields_ValueContainer {

	public function render() {
		$data = $this->data;
		$value = parent::value();
		if (!isset($data['valid1970'])&&CMS::date('Ymd',$value)=='19700101') return '';
		if (isset($data['format'])) $format = $data['format'];
		if (empty($format)) {
			$format = 'd.m.Y';
			if (isset($data['with_time'])&&$data['with_time']) $format = 'd.m.Y - H:i';
			if (isset($data['with_seconds'])&&$data['with_seconds']) $format = 'd.m.Y - H:i:s';
		}
		$value = CMS::date($format,$value);
		return $value;
	}

}
