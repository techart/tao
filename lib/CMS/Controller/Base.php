<?php
/// <module name="CMS.Controller.Base" maintainer="gusev@techart.ru" version="0.0.0">
Core::load('WebKit.Controller');
/// <class name="CMS.Controller.Base" stereotype="module" extends="WebKit.Controller.AbstractController">
///   <implements interface="Core.ModuleInterface" />
class CMS_Controller_Base extends WebKit_Controller_AbstractController implements Core_ModuleInterface {
	
///   <constants>
	const MODULE  = 'CMS.Controller.Base';
	const VERSION = '0.0.0';
///   </constants>

	static $db;

	protected $auth_realm = false;
	protected $auth_realm_title = false;


	static function db() {
		return CMS::orm();
	}

	protected function auth_realm() {
		if ($this->auth_realm) return $this->auth_realm;
		return CMS::$default_auth_realm;
	}

	protected function auth_realm_title() {
		if ($this->auth_realm_title) return $this->auth_realm_title;
		return $this->auth_realm();
	}


///   <protocol name="creating">

///   <method name="setup" returns="CMS.Controller.Base">
///     <body>
	public function setup() {
		parent::setup();

		if ($this->auth_realm()) {
			$this->before_filter('restricted_authenticate_filter');
		}
		
		$this->before_filter('setup_filter');
		
		if (CMS::$default_last_modified) $this->response['Last-Modified'] = gmdate("D, d M Y H:i:s T");
		
		return $this;
	}
///     </body>
///   </method>
	

///   </protocol>	
	

///   <protocol name="filter">

///   <method name="restricted_authenticate_filter">
///     <body>
	protected function restricted_authenticate_filter() {

		$res = CMS_Handlers_RealmAuth::access($this->auth_realm(), array($this, 'extra_auth'));

		if ($res) {
			if ($res['empty']) return;
			if (isset($res['data']['layout'])) {
				$this->use_layout($res['data']['layout']);
			}
			$this->after_auth($res['auth_parms']);
		}
		else {
			$this->_noauth();
		}	
	}
///     </body>
///   </method>

///   <method name="extra_auth">
///     <body>
	public function extra_auth($login,$password,$realm) {
		if (is_callable(CMS::$extra_auth)) return call_user_func(CMS::$extra_auth,$login,$password,$realm);
		return false;
	}
///     </body>
///   </method>

///   <method name="after_auth">
///     <body>
	protected function after_auth($parms) {
		if (is_callable(CMS::$after_auth)) return call_user_func(CMS::$after_auth,$parms);
	}
///     </body>
///   </method>


///   <method name="setup_filter">
///     <body>
	protected function setup_filter() {
	}
///     </body>
///   </method>
	
	
///   </protocol>
	
///   <protocol name="supporting">

///   <method name="_noauth">
///     <body>
	protected function _noauth() {
		$title = $this->auth_realm_title();
		throw new WS_Auth_UnauthenticatedException($title);
	}
///     </body>
///   </method>
	

///   </protocol>

	protected function render_view($template, array $parms = array(),$layout = '') {
		$t = parent::render_view($template,$parms,$layout);
		CMS::layout_view($t);
		return $t;
	}

	
	
}
/// </class>


/// </module>
