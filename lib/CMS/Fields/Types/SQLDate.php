<?php
/**
 * @package CMS\Fields\Types\SQLDate
 */


Core::load('CMS.Fields.Types.SQLDateStr');

class CMS_Fields_Types_SQLDate extends CMS_Fields_Types_SQLDateStr {

	const VERSION = '0.0.0';

	public function form_fields($form,$name,$data) {
		$form->input($name.'_dd');
		$form->input($name.'_mm');
		$form->input($name.'_yy');
		return $form;
	}

	public function assign_from_object($form,$object,$name,$data) {
		$date = Time::DateTime($object->$name);
		$form[$name.'_dd'] = $date->day;
		$form[$name.'_mm'] = $date->month;
		$form[$name.'_yy'] = $date->year;
	}

	public function assign_to_object($form,$object,$name,$data) {
		$d = (int)$form[$name.'_dd']; //$d = str_pad($d, 2, "0", STR_PAD_LEFT);
		$m = (int)$form[$name.'_mm']; //$m = str_pad($m, 2, "0", STR_PAD_LEFT);
		$y = (int)$form[$name.'_yy']; //$y = str_pad($y, 4, "0", STR_PAD_LEFT);
		$object[$name] = Time::DateTime("$y-$m-$d");
	}

}
