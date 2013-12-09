<?php
/**
 * @package CMS\Fields\Types\Input
 */


class CMS_Fields_Types_Input extends CMS_Fields_AbstractField implements Core_ModuleInterface {

	const VERSION = '0.0.0';

	public function enable_multilang() {
		return true;
	}

	public function tagparms($name, $data) {
		$r = parent::tagparms($name, $data);
		if (isset($data['type'])) $r['type'] = $data['type'];
		if ($r['type']=='input') $r['type'] = 'text';
		return $r;
	}


}
