<?php
class CMS_Controller_AdminStd extends CMS_Controller implements Core_ModuleInterface {
	
	const MODULE = 'CMS.Controller.AdminStd';
	const VERSION = '0.0.0'; 

	protected $auth_realm = 'admin';

	public function flush_caches() {
		WS::env()->cache->flush();
		return $this->redirect_to(CMS::admin_path());
	} 


} 
