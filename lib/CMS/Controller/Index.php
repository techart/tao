<?php
/// <module name="CMS.Controller.Index" maintainer="gusev@techart.ru" version="0.0.0">

/// <class name="CMS.Controller.Index" extends="CMS.Controller.Base" stereotype="module">
///   <implements interface="Core.ModuleInterface" />
class CMS_Controller_Index extends CMS_Controller_Base implements Core_ModuleInterface {

///   <constants>
	const MODULE = 'CMS.Controller.Index'; 
	const VERSION = '0.0.0'; 
///   </constants>

///   <protocol name="creating">

///   <method name="setup" returns="CMS.Controller.Index">
///     <body>
	public function setup() {
		return parent::setup()->use_views_from(CMS::app_path('views'));
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

///   <method name="error404" returns="WebKit.Views.TemplateView">
///     <args>
///       <arg name="view" type="string" />
///       <arg name="layout" type="string" />
///     </args>
///     <body>
	public function error404($layout) {
		$this->use_layout($layout);
		return $this->page_not_found();
	}
///     </body>
///   </method>

///   </protocol>
}
/// </class>

/// </module>


