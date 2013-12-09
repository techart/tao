<?php
/**
 * @package CMS\Fields\Types\Subheader
 */


class CMS_Fields_Types_Subheader extends CMS_Fields_AbstractField implements Core_ModuleInterface {

	const VERSION = '0.0.0';

	public function form_fields() {}
	public function assign_from_object() {}
	public function assign_to_object() {}
	
	public function draw_in_layout($name,$data,$layout=false) {
		$data['__caption'] = $data['caption'];
		$data['caption'] = '';
		parent::draw_in_layout($name,$data,$layout);
	}

}
