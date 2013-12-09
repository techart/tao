<?php
/**
 * @package CMS\Controller\FSPages
 */

class CMS_Controller_FSPages extends CMS_Controller implements Core_ModuleInterface
{
	protected $result = null;
	
	protected function auth_realm()
	{
		$realm = false;
		if (Core_Regexps::match('{^/admin/}',$_SERVER['REQUEST_URI'])) {
			$realm = 'admin';
		}
		/**
		@event cms.fspages.realm
		@arg $realm имя области
		Отдельные статические страницы (CMS.FSPages) могут исполняться в рамках обособленной (чаще всего - закрытой паролем) области.
		По умолчанию страницы, чьи адреса начинаются с '''/admin/''' исполняются в области '''admin''', остальные - без указания области (в области по умолчанию).
		Однако, это можно исправить в обработчике данного события. Проверьте REQUEST_URI и установите нужный realm.
		*/
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
