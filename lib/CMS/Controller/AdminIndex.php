<?php
/**
 * CMS.Controller.AdminIndex
 * 
 * @package CMS\Controller\AdminIndex
 * @version 0.0.0
 */

/**
 * @package CMS\Controller\AdminIndex
 */

class CMS_Controller_AdminIndex extends CMS_Controller_Base implements Core_ModuleInterface { 
	
	const MODULE = 'CMS.Controller.AdminIndex'; 
	const VERSION = '0.0.0'; 
	

/**
 * @return CMS_Controller_Index
 */
	public function setup() { 
		$this->auth_realm = CMS::$admin_realm;
		return parent::setup()->use_views_from('../app/views'); 
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
	

} 


