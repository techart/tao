<?php
class CMS_Controller_FSPages extends CMS_Controller implements Core_ModuleInterface
{
	protected $result = null;
	
	protected function auth_realm()
	{
		$realm = false;
		if (Core_Regexps::match('{^/admin/}',$_SERVER['REQUEST_URI'])) {
			$realm = 'admin';
		}
		Events::call('cms.fspages.realm',$realm);
		return $realm;
	}
	
	public function index($path)
	{
		$view = $this->render($path)->as_string();
		if (!is_null($this->result)) {
			return $this->result;
		}
		return $view;
	}
	
	public function result($result)
	{
		if ($result===false) {
			$result = $this->page_not_found();
		}
		$this->result = $result;
	}
	
	public function not_found()
	{
		$this->result(false);
	}
	
	public function redirect($url)
	{
		$this->result = $this->redirect_to($url);
	}
	
	public function moved_permanently($url)
	{
		$this->result = $this->moved_permanently_to($url);
	}
}
