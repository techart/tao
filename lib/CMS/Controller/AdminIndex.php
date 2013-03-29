<?php
/// <module name="CMS.Controller.AdminIndex" maintainer="gusev@techart.ru" version="0.0.0">

/// <class name="CMS.Controller.AdminIndex" extends="CMS.Controller.Base" stereotype="module">
///   <implements interface="Core.ModuleInterface" />

class CMS_Controller_AdminIndex extends CMS_Controller_Base implements Core_ModuleInterface { 
	
///   <constants>
	const MODULE = 'CMS.Controller.AdminIndex'; 
	const VERSION = '0.0.0'; 
///   </constants>
	
///   <protocol name="creating">

///   <method name="setup" returns="CMS.Controller.Index">
///     <body>
	public function setup() { 
		$this->auth_realm = CMS::$admin_realm;
		return parent::setup()->use_views_from('../app/views'); 
	} 
///     </body>
///   </method>
	
///   </protocol>	
	
///   <protocol name="processing">

///   <method name="index" returns="WebKit.Views.TemplateView">
///     <args>
///       <arg name="view" type="string" />
///       <arg name="layout" type="string" />
///     </args>
///     <body>
	public function index($view,$layout) {
		$this->use_layout($layout); 
		return $this->render($view); 
	} 
///     </body>
///   </method>
	

///   </protocol>
} 
/// </class>

/// </module>

