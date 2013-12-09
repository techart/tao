<?php
/**
 * CMS.Controller.Index
 * 
 * @package CMS\Controller\Index
 * @version 0.0.0
 */

/**
 * @package CMS\Controller\Index
 */
class CMS_Controller_Index extends CMS_Controller_Base implements Core_ModuleInterface {

	const MODULE = 'CMS.Controller.Index'; 
	const VERSION = '0.0.0'; 


/**
 * @return CMS_Controller_Index
 */
	public function setup() {
		return parent::setup()->use_views_from(CMS::app_path('views'));
	}




/**
 * @param string $view
 * @param string $layout
 * @return WebKit_Views_TemplateView
 */
	public function index($view,$layout) {
		$this->use_layout($layout);
		return $this->render($view);
	}

/**
 * @param string $view
 * @param string $layout
 * @return WebKit_Views_TemplateView
 */
	public function error404($layout) {
		$this->use_layout($layout);
		return $this->page_not_found();
	}

}



