<?php
/**
 * @package CMS\Fields\Helper
 */


Core::load('CMS.Fields');

class CMS_Fields_Helper implements Core_ModuleInterface, Templates_HelperInterface {

	const MODULE = 'CMS.Fields.Helper';
	const VERSION = '0.0.0'; 

	public function draw($view,$fields,$layout=false) {
		if (!is_array($fields)) return '';
		if (count($fields)<1) return '';

		$out = '';

		foreach($fields as $name => $data) {
			$out .= $this->field_in_layout($view,$name,$data,$layout);
		}

		return $out;
	}

	public function field($view,$name,$data) {
		$type = CMS_Fields::type($data);
		$type->view = $view;
		return $type->render($name,$data);
	}

	public function field_in_layout($view,$name,$data,$layout=false) {
		$type = CMS_Fields::type($data);
		$type->view = $view;
		return $type->render_in_layout($name,$data,$layout);
	}

} 

