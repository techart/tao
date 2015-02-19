<?php
/**
 * @package CMS\Controller\FrontTable
 */

Core::load('CMS.Controller.Table');

class CMS_Controller_FrontTable extends CMS_Controller_Table implements Core_ModuleInterface
{
	const VERSION = '0.0.0';

	protected $auth_realm = false;
	protected $orm_for_select = 'published_or_admin';

	protected function list_style()
	{
		return 'lent';
	}

	protected function mnemocode($action = 'list')
	{
		$code = strtolower(CMS::$current_component_name);
		return "$code.$action";
	}

	protected function in_embedded_admin()
	{
		return true;
	}

	protected function templates_dir()
	{
		return CMS::current_component_dir('views');
	}

	protected function action_edit()
	{
		if ($this->in_embedded_admin()) {
			CMS::$globals['is_embedded_admin'] = true;
			$this->use_layout('admin');
		}
		return parent::action_edit();
	}

	public function action_view($item = false)
	{
		if (!$item) {
			$item = $this->load($this->id);
		}
		if (!$item) {
			return $this->page_not_found();
		}
		$mapper = $this->orm_mapper();
		if (!$mapper->user_can_view($item)) {
			return $this->access_denied();
		}
		return $this->render('view', array(
				'item' => $item,
				'mapper' => $mapper,
				'can_edit' => $mapper->user_can_edit($item),
				'can_delete' => $mapper->user_can_delete($item),
				'list_url' => $this->action_url('list', $this->page),
				'edit_url' => $this->action_url('edit', $item),
				'delete_url' => $this->action_url('delete', $item),
			)
		);
	}

	protected function redirect_after_edit($item)
	{
		return $this->action_url('view', $item);
	}

	protected function access_add()
	{
		$m = $this->orm_mapper();
		return $this->orm_mapper()->user_is_editor();
	}

	protected function access_edit($item)
	{
		return $this->orm_mapper()->user_can_edit($item);
	}

}
