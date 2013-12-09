<?php
/**
 * @package CMS\Fields\Types\Hidden
 */


class CMS_Fields_Types_Hidden extends CMS_Fields_AbstractField implements Core_ModuleInterface {

	const VERSION = '0.0.0';

	public function render_in_layout($name,$data) {
		return $this->render($name,$data);
	}



}
