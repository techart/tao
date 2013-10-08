<?php

class CMS_Fields_Types_Textarea extends CMS_Fields_AbstractField implements Core_ModuleInterface {

	const VERSION = '0.0.0';

	public function enable_multilang() {
		return true;
	}

	public function preprocess($template, $name, $data) {
		parent::preprocess($template, $name, $data);
		$parms = $template->parms;
		if (empty($parms['tagparms']['style'])) {
			$template->update_parm('tagparms', array('style' => 'width: 300px;height:100px;'));
		}
		return $template;
	}

}
