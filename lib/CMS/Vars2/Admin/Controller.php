<?php

Core::load('CMS.Controller.Table');

class CMS_Vars2_Admin_Controller extends CMS_Controller_Table {
	const VERSION = '0.0.0';
	
	protected $title_list	= 'Настройки';
	protected $title_edit	= 'Настройки: редактирование';
	protected $title_add	= 'Настройки: добавление';

	protected $button_add	= 'Добавить раздел';
	
	protected  function list_style() {
		return 'vars';
	}

	protected function filters() {
		return array('chapter');
	}

	protected function access_add()
	{
		return $this->is_admin();
	}

	protected function access_edit($item)
	{
		return $this->user_access($item->_access);
	}
	
	public function user_access($access)
	{
		$access = trim($access);
		if ($access!='') {
			$user = WS::env()->admin_auth->user;
			return $user->check_access($access);
		}
		return true;
	}

	protected function title_add() {
		if (isset($_GET['chapter'])) {
			return 'Создание новой настройки';
		}
		return 'Создание нового раздела';
	}

	protected function title_edit($var) {
		if ($var->is_dir()) {
			return 'Редактирование параметров раздела';
		}
		return 'Редактирование настройки';
	}

	public function templates_dir() {
		return CMS::views_path('admin/vars2');
	}

	protected function new_object() {
		return CMS::vars()->entity(isset($_GET['chapter'])?'string':'dir');
	}

	protected function count_all() {
		return 1;
	}

	protected function load($id) {
		if ($id===0) {
			return false;
		}
		$var = CMS::vars()->get_var($id);
		return $var;
	}

	protected function update($var) {
		CMS::vars()->save_var($var);
	}

	protected function insert($var) {
		CMS::vars()->save_var($var);
	}

	protected function delete($var) {
		CMS::vars()->delete($var);
	}
	
	protected function action_list()
	{
		if (isset($_GET['chapter'])) {
			unset($_GET['chapter']);
		}
		return parent::action_list();
	}
	
	protected function select_all() {
		$vars = CMS::vars()->get_all_vars();
		$def = CMS::vars()->entity('dir',array(
			'_noedit' => true,
			'_nodelete' => true,
			'_title' => 'Основные настройки',
		));
		$out = array('.' => $def);
		foreach($vars as $var) {
			if ($var->is_dir()) {
				$out[$var->id()] = $var;
			} else {
				$out['.']->vars[$var->id()] = $var;
			}
		}
		return $out;
	}

	protected function mnemocode() {
		return "cms.vars.admin.{$this->action}";
	}

	public function is_admin() {
		return WS::env()->admin_auth->user->check_access('full');
	}

	protected function chapter() {
		return isset($_GET['chapter'])? $_GET['chapter'] : false;
	}

	protected function form_tabs($action) {
		if ($action=='add') {
			if (isset($_GET['chapter'])) {
				$tabs = array('config' => CMS::lang('%LANG{en}New var%LANG{ru}Новая настройка'));
			} else {
				$tabs = array('config' => CMS::lang('%LANG{en}New dir%LANG{ru}Новый раздел'));
			}
		} else {
			$var = $this->edit_item;
			$tabs = $var->tabs($var);
			if ($this->is_admin()) {
				$tabs['config'] = CMS::lang('%LANG{en}Admin%LANG{ru}Администирование');
			}
			if ($var->is_dir()) unset($tabs['default']);
		}
		return $tabs;
	}

	protected function form_fields($action) {
		$fields = array();
		if ($action=='list') {
		} elseif ($action=='add') {
				$fields = array();
				if ($this->is_admin()) {
					$fields['_name'] = array(
						'type' => 'input',
						'style' => 'width: 200px;',
						'tab' => 'config',
						'caption' => CMS::lang('%LANG{en}Name%LANG{ru}Мнемокод'),
					);
					if (isset($_GET['chapter'])) {
						$pchapter = trim($_GET['chapter']);
						if ($pchapter!='') {
							$pchapter .= '.';
						}
						$fields['_name']['obligatory_prefix'] = $pchapter;
						$fields['_type'] = array(
							'type' => 'select',
							'items' => CMS::vars()->types_list(),
							'caption' => CMS::lang('%LANG{en}Type%LANG{ru}Тип'),
							'tab' => 'config',
						);
					}
				}
		} else {
			$var = $this->edit_item;
			if (!$var->is_dir()) {
				$fields = array(
					'vname' => array(
						'type' => 'subheader',
						'tab' => 'default',
						'caption' => $var->id().': '.$var->title(),
					),
				);
				$fields += $var->fields($var);

			}
		}
		if ($this->is_admin()) {
			$fields['_title'] = array(
				'type' => 'input',
				'style' => 'width: 100%;',
				'tab' => 'config',
				'caption' => CMS::lang('%LANG{en}Title%LANG{ru}Название'),
			);
			$fields['_access'] = array(
				'type' => 'input',
				'style' => 'width: 200px;',
				'tab' => 'config',
				'caption' => CMS::lang('%LANG{en}Access%LANG{ru}Доступ'),
			);
			$fields['_delsubheader'] = array(
				'type' => 'subheader',
				'style' => 'width: 200px;',
				'tab' => 'config',
				'caption' => CMS::lang('&nbsp;'),
			);
			static $ev = false;
			if (!$ev) {
				Events::add_listener('cms.fields.admin.mainform._delsubheader.after',function($parms) {
					$var = $parms['item'];
					$url = WS::env()->urls->adminvars->delete_url($var->name());
					if ($url=='#') {
						return;
					}
					$title = 'Удалить';
					$confirm = 'Вы уверены?';
					print "<a href='{$url}' onClick='return confirm(\"{$confirm}\")' class='icon-button'><em style='background-image: url(/tao/images/del.png);'>{$title}</em></a>";
				});
				$ev = true;
			}
		}
		return $fields;
	}
	
	protected function process_form($item)
	{
		$errors = parent::process_form($item);
		if (isset($this->form['_name'])&&sizeof($errors)==0) {
			$name = trim($this->form['_name']);
			if (Core_Regexps::match('{^[a-z0-9_-]+$}i',$name)) {
				return array();
			}
			if (Core_Regexps::match('{^[a-z0-9_-]+\.[a-z0-9_-]+$}i',$name)) {
				return array();
			}
			return array('_name' => CMS::lang('%LANG{en}Invalid name!%LANG{ru}Некорректный мнемокод!'));
		}
		return $errors;
	}
	
	protected function redirect_after_add($var) {
		if ($var->is_dir()) {
			return WS::env()->urls->adminvars->list_url();
		}
		return WS::env()->urls->adminvars->edit_url($var->name());
	}

}

