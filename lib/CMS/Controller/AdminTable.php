<?php

Core::load('CMS.Controller.AdminTableBase');


class CMS_Controller_AdminTable extends CMS_Controller_AdminTableBase implements Core_ModuleInterface {
	
	const MODULE  = 'CMS.Controller.AdminTable';
	const VERSION = '0.0.0';
	
	
	public function setup() {
		if ($this->auth_realm=='admin') $this->auth_realm = CMS::$admin_realm;
		return parent::setup()->native_views(CMS::app_path("components/".CMS::$current_component_name."/views"));
	}
	
	protected function check_access() {
		if (CMS::$globals['full']) return true;
		$acc = trim($this->access);
		if ($acc!=''&&!CMS::check_globals_or($acc)) {
			return false;
		}
		return true;
	}
	
	public function items_for_select($s) {
	
		if (is_string($s) && ($m = Core_Regexps::match_with_results('/^:(.+)$/',$s))) {
			$method = trim($m[1]);
			if ($m = Core_Regexps::match_with_results('/^(.+)\((.*)\)$/',$method)) {
				$method = trim($m[1]);
				$parms = explode(',',trim($m[2]));
				foreach($parms as $k=>$v) {
					$v = trim($v);
					$parms[$k] = $v;
				}
				return call_user_func_array(array($this,$method),$parms);
			}
			return $this->$method();
		}
		
		return CMS::items_for_select($s);
		
	}

	protected function get_var_value($name) {
		$site = false;
		if ($m = Core_Regexps::match_with_results('{^(.+)/([^/]+)$}',$name)) {
			$name = trim($m[1]);
			$site = trim($m[2]);
			if ($site=='*') $site = CMS_Admin::site();
		}
		return CMS::vars()->get($name,$site);
	}	

	protected function parse_parms($in) {
		return CMS::parse_parms($in);
	}

	protected function use_standart_views() {
		$this->use_views_from(CMS::view('admin/table'));
	}
	
	public function standart_template($tpl) {
		return CMS::view("admin/table/$tpl.phtml");
	}
	
	protected function page_navigator($page_num,$num_pages,$tpl) {
		return CMS::page_navigator($page_num,$num_pages,$tpl);
	}
	
	protected function mkdirs($dir) {
		return CMS::mkdirs($dir);
	}	

	protected function chmod_file($name) {
		CMS::chmod_file($name);
	}

	protected function file_url($path) {
		return CMS::file_url($path);
	}	
	
	protected function field_template_path($name) {

		$f = "$name.phtml";
		if (file_exists($f)) return $f;

		$f = CMS::app_view("admin/table/fields/$name.phtml");
		if (file_exists($f)) return $f;

		$f = CMS::view("admin/table/fields/$name.phtml");
		if (file_exists($f)) return $f;
		
		return CMS::view('admin/table/fields/input.phtml');
	}
	
}

