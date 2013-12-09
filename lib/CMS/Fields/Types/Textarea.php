<?php
/**
 * @package CMS\Fields\Types\Textarea
 */


class CMS_Fields_Types_Textarea extends CMS_Fields_AbstractField implements Core_ModuleInterface {

	const VERSION = '0.0.0';

	public function enable_multilang() {
		return true;
	}

	public function form_fields($form,$name,$data) {
		$rc = parent::form_fields($form,$name,$data);
		if (isset($data['value'])) {
			$form[$name] = $data['value'];
		}
		return $rc;
	}

	protected function stdunset($data) {
		$res = parent::stdunset($data);
		return $this->punset($res, 'value');
	}
	
	public function preprocess($template, $name, $data) {
		$template->use_scripts(CMS::stdfile_url('scripts/jquery/tabby.js'));
		parent::preprocess($template, $name, $data);
		$parms = $template->parms;
		$template->update_parm('tagparms', array('class' => 'use-tab-key'));
		if (empty($parms['tagparms']['style'])) {
			$template->update_parm('tagparms', array('style' => 'width: 300px;height:100px;'));
		}
		return $template;
	}

}
