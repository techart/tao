<?php

Core::load('CMS.Controller.Fields');

class CMS_Controller_Table extends CMS_Controller_Fields implements Core_ModuleInterface {
	
	const MODULE  = 'CMS.Controller.Table';
	const VERSION = '0.0.0';

	////////////////////////////////////////////////////////////////////////
	// Источник данных

	// Имя DB.SQL объекта (устарело, не рекомендуется к использованию)
	protected $entity		= false;

	// Имя таблицы в БД (устарело, не рекомендуется к использованию)
	protected $dbtable		= false;

	// Модуль схемы
	protected $schema_module	= false;

	// Имя основного ORM-маппера
	protected $orm_name		= false;

	protected $storage_name;

	// Имя ORM-субмаппера для просмотра списка
	protected $orm_for_select	= false;

	// Мнемокод контроллера/экшна (для генерации евентов)
	protected $mnemocode = 'cms.table';
	protected $mnemocode_prefix = 'cms.table';

	protected $add_in_list	= false;

	protected $title_add_in_list	= 'Быстрое добавление записи';

	protected function mnemocode($name = null) {
		if (is_null($name)) {
			$name = $this->action;
		}
		$m = get_class($this);
		$m = str_replace('Component_','',$m);
		$m = strtolower($m);
		$m = preg_replace('{[^a-z0-9]+}','.',$m);
		$m = trim($m,'.');
		$m = "{$this->mnemocode_prefix}.{$m}.{$name}";
		return trim($m, '.');
	}

	public function assign($options = array())
	{
		foreach ($options as $k => $v) {
			$this->$k = $v;
		}
		return $this;
	}

	// Возвращает основной ORM-маппер
	protected function orm_mapper() {

		if (!$this->orm_name) {
			return false;
		}

		$name = $this->orm_name;

		if (is_string($name)) {
			return CMS::orm()->$name;
		}

		return false;
	}

	protected function storage() {
		$name = $this->storage_name;
		return Storage::manager()->$name();
	}

	public function orm_name() {
		return $this->orm_name;
	}

	public function name() {
		return $this->storage_name ? $this->storage_name : $this->orm_name();
	}

	public function schema_fields() {
		if (!$this->schema_module) {
			return false;
		}
		Core::load($this->schema_module);
		$class = str_replace('.','_',$this->schema_module);
		return call_user_func(array($class,'fields'));
	}

	public function schema_tabs() {
		if (!$this->schema_module) {
			return false;
		}
		Core::load($this->schema_module);
		$class = str_replace('.','_',$this->schema_module);
		if (method_exists($class,'tabs')) {
			return call_user_func(array($class,'tabs'));
		}
		return false;
	}

	// Возвращает ORM-маппер для выборки строк
	protected function orm_mapper_for_select($parms=array()) {
		$mapper = $this->orm_mapper();
		if (!$mapper) return false;
		if ($sub = $this->orm_for_select) $mapper = $mapper->downto($sub);
		$mapper = $this->orm_mapper_for_parms($mapper,$parms);
		if ($mapper->auto_add()) {
			$mapper = $mapper->auto_add_mapper_created();
		}
		return $mapper;
	}

	// Возвращает ORM-маппер для подсчета строк
	protected function orm_mapper_for_count($parms=array()) {
		$parms['__mapper_for_count'] = true;
		return $this->orm_mapper_for_select($parms);
	}

	// Применяет к ORM-мапперу параметры и фильтры
	protected function orm_mapper_for_parms($mapper,$parms=array()) {
		if (!$mapper) return false;

		if (isset($parms['__mapper_for_count'])&&$parms['__mapper_for_count']) {
			if (isset($parms['limit'])) unset($parms['limit']);
			if (isset($parms['offset'])) unset($parms['offset']);
		}

		if(isset($parms['limit'])) {
			$limit = (int)$parms['limit'];
			unset($parms['limit']);

			if (isset($parms['offset'])) {
				$offset = (int)$parms['offset'];
				unset($parms['offset']);
			}

			else {
				$offset = 0;
			}

			$mapper = $mapper->spawn()->range($limit,$offset);
		}

		unset($parms['__mapper_for_count']);
		
		foreach($parms as $key => $value) {
			if (!in_array($key,$this->exclude_filters)) {
				$mapper = $mapper->$key($value);
			}
		}
		return $mapper;
	}

	protected $object = false;

	protected function new_object() {
		if ($this->storage_name) return $this->storage()->make_entity();
		if ($mapper = $this->orm_mapper()) return $mapper->make_entity();
		if ($tbl = $this->dbtable) return clone DB_SQL::db()->$tbl->prototype;
		return $this->entity_reflection()->newInstance();
	}

	protected function count_all($parms) {
		if ($this->storage_name) {
			if (isset($parms['limit'])) unset($parms['limit']);
			if (isset($parms['offset'])) unset($parms['offset']);
			$q = $this->storage()->create_query()->filter($parms);
			return $this->storage()->count($q);
		}
		if ($mapper = $this->orm_mapper_for_count($parms)) {
			return $mapper->count();
		}
		if ($tbl = $this->dbtable) {
			return DB_SQL::db()->$tbl->count();
		}
		return $this->object->count_all($parms);
	}

	protected function select_all($parms) {
		if ($this->storage_name) {
			$q = $this->storage()->create_query()->filter($parms);
			return $this->storage()->select($q);
		}
		if ($mapper = $this->orm_mapper_for_select($parms)) {
			return $mapper->select();
		}
		if ($tbl = $this->dbtable) {
			return DB_SQL::db()->$tbl->select();
		}
		return $this->object->select_all($parms);
	}

	protected function load($id) {
		if ($this->storage_name) {
			return $this->storage()->find($id);
		}
		if ($mapper = $this->orm_mapper()) {
			return $mapper[$id];
		}
		if ($tbl = $this->dbtable) return DB_SQL::db()->$tbl->find($id);
		$item = $this->object->load($id);
		return $item;
	}

	protected function delete($item) {
		if ($this->storage_name) {
			return $this->storage()->delete($item);
		}
		if ($mapper = $this->orm_mapper()) {
			return $mapper->delete($item);
		}
		$id = $this->item_id($item);
		if ($tbl = $this->dbtable) return DB_SQL::db()->$tbl->delete($id);
		return $this->object->delete($id);
	}

	protected function insert($item) {
		if ($this->storage_name) {
			return $this->storage()->insert($item);
		}
		if ($mapper = $this->orm_mapper()) {
			return $mapper->insert($item);
		}
		if ($tbl = $this->dbtable) return DB_SQL::db()->$tbl->insert($item);
		return $item->insert();
	}

	protected function update($item) {
		if ($this->storage_name) {
			return $this->storage()->update($item);
		}
		if ($mapper = $this->orm_mapper()) {
			return $mapper->update($item);
		}
		if ($tbl = $this->dbtable) return DB_SQL::db()->$tbl->update($item);
		return $item->update();
	}

	protected function item_key($item) {
		return $item->key();
	}

	public function item_id($item) {
		return $item->id();
	}

	public function item_homedir($item,$private=false) {
		return $item->homedir($private);
	}

	public function item_cachedir($item,$private=false) {
		return $item->cache_dir_path($private);
	}


	protected $entity_reflection = false;

	protected function entity_reflection() {
		if ($this->entity_reflection) return $this->entity_reflection;
		$this->entity_reflection = Core_Types::reflection_for(Core_Strings::replace($this->entity,'.','_'));
		return $this->entity_reflection;
	}

	////////////////////////////////////////////////////////////////////////
	// Фильтрация данных

	// Список имен фильтров
	protected $filters = array();
	protected function filters() {
		return array_unique(array_merge($this->filters, array_keys($this->filters_form())));
	}

	// Список фильтров, не передаваемых в ORM
	protected $exclude_filters = array();
	protected function exclude_filters() {
		return $this->exclude_filters;
	}

	// Форма фильтра на списке (набор полей)
	protected $filters_form	= array();
	protected function filters_form() {
		return $this->search_fields($this->form_fields('list'), $this->filters_form, 'in_filters');
	}

	// Фильтры-кнопки на списке
	protected $filters_buttons	= array();
	protected function filters_buttons() {
		return $this->filters_buttons;
	}

	// Список параметров, обязательно передаваемых в качестве фильтров
	protected $force_filters= array();
	protected function force_filters() {
		return $this->force_filters;
	}

	// Создание формы фильтра
	protected function create_filters_form() {
		$fields = $this->filters_form();
		$form = Forms::Form('filtersform')->action($this->action_url('filter',1));
		foreach($fields as $name => $parms) {
			$item = false;
			if (isset($this->request[$name])) {
				$item = new ArrayObject(array($name => $this->request[$name]));
				$parms['__item'] = $item;
			}
			$type = CMS_Fields::type($parms);
			$type->form_fields($form,$name,$parms);
			if ($item) {
				//$form[$name] = $_GET[$name];
				$type->assign_from_object($form, $item, $name, $parms);
			}
		}
		return $form;
	}

	protected function filter_object() {
		$form = $this->create_filters_form();
		$form->process($this->env->request);
		$o = $this->new_object();
		$attrs = new ArrayObject();
		foreach($this->filters_form() as $name => $data) {
			$type = CMS_Fields::type($data);
			if (isset($form[$name]) && $form[$name]!='' && !is_null($form[$name])) {
				$type->assign_to_object($form,$o,$name,$data);
				$attrs[$name] = $o[$name];
			}
		}
		return array($o, $attrs);
	}

	// Обработчик фильтра
	protected function action_filter() {
		list($o, $attrs) = $this->filter_object();
		$values = array();
		foreach($attrs as $name => $value) {
			$value = trim($value);
			if ($value!='') $values[$name] = $value;
		}
		$url = $this->action_url('list','1',$values);
		return $this->redirect_to($url);
	}

	////////////////////////////////////////////////////////////////////////
	// Доступ

	protected $auth_realm = 'admin';

	protected function auth_realm() {
		if ($this->auth_realm=='admin')
			return CMS::$admin_realm;
		return parent::auth_realm();
	}

	protected function access_denied() {
		return $this->render('denied');
	}

	protected function access($action, $item = null) {
		$rc = Events::call('cms.table.access', $action, $item, $this);
		if (!is_null($rc)) return $rc;
		$rc = Events::call('cms.table.access.' . $action, $item, $this);
		if (!is_null($rc)) return $rc;
		return true;
	}

	protected function check_access() {
		return $this->access('list');
	}

	protected function check_item_access($item) {
		return true;
	}

	////////////////////////////////////////////////////////////////////////
	// Управление шаблонами

	protected $templates_dir = false;

	protected function templates_dir() {
		return $this->templates_dir;
	}

	public function redefined_template($template) {
		if (!Core_Regexps::match('{\.phtml$}',$template)) $template .= '.phtml';
		if ($template[0]=='.'||$template[0]=='/') return $template;

		$dir = $this->templates_dir();
		if ($dir && is_file("$dir/$template")) return "$dir/$template";

		$dir = CMS::current_component_dir('views/admin');
		if (is_file("$dir/$template")) return "$dir/$template";
		
		return false;
	}

	public function template($template) {
		if (!Core_Regexps::match('{\.phtml$}',$template)) $template .= '.phtml';
		if ($template[0]=='.'||$template[0]=='/') return $template;

		if ($t = $this->redefined_template($template)) return $t;

		$tpl = CMS::views_path('admin/table2/'.$template);
		
		if (is_file($tpl)) return $tpl;
		if ($this->view_exists($template)) return $this->view_path_for($template);
		
		return $tpl;
	}

	protected function render($template,$parms=array()) {
		if (is_numeric($template)) return parent::render($template);
		$parms['template'] = $template;
		$template = $this->template($template);
		return parent::render($template,$parms);
	}

	////////////////////////////////////////////////////////////////////////
	// Events

	protected function on_before_action() {}
	protected function on_before_list() {}
	protected function on_after_change() {}
	protected function on_after_change_item() {}
	protected function on_after_update_item() {}
	protected function on_after_mass_update_item() {}
	protected function on_after_insert_item() {}
	protected function on_after_copy_item($from,$to) {}
	protected function on_after_delete_item() {}
	protected function on_before_change_item() {}
	protected function on_before_update_item() {}
	protected function on_before_mass_update_item() {}
	protected function on_before_insert_item() {}
	protected function on_before_delete_item() {}
	protected function on_before_action_edit() {}
	protected function on_before_action_add() {}
	protected function on_before_action_copy() {}
	protected function on_row($row) {}

	////////////////////////////////////////////////////////////////////////
	// Index

	public function setup_config() {
		$tabs = $this->get_from_component('tabs');
		if (!empty($tabs)) $this->form_tabs = array_merge( (array) $this->form_tabs, $tabs);
		$table = $this->get_from_component('table');
		if ($table)
			foreach ($table as $name => $value) {
				if (property_exists($this, $name)) $this->$name = $value;
			}
		return parent::setup_config();
	}

	public function setup() {
		return parent::setup()->render_defaults('id','page','args','action','mnemocode');
	}


	protected $id = 0;
	protected $page = 1;
	protected $action = 'list';
	protected $sort = false;
	protected $sort_direction = false;
	protected $args = array();


	protected function default_action($action, $args) {
		return 'list';
	}

	public function index($action,$args) {
		$rc = Events::call($this->mnemocode('start_action'), $action, $args, $this);
		if (!empty($rc)) {
			return $rc;
		}

		if ($action == 'default' || $action == 'index')
			$action = $this->default_action($action, $args);
		
		if (!$this->check_access()) {
			return $this->access_denied();
		}

		if (strpos($action,'-')!==false) {
			$args = "$action/$args";
			$action = $this->default_action($action, $args);
		}

		$this->action = $action == 'default'? $this->action : $action;
		$this->mnemocode = $this->mnemocode($action);
		if (is_int($args)) $this->page = $args;
		else {
			foreach(explode('/',$args) as $arg) {
				$arg = trim($arg);
				if ($m = Core_Regexps::match_with_results('{^([^-]+)-(.+)$}',$arg)) {
					$this->args[trim($m[1])] = trim($m[2]);
				} else {
					$this->args[] = $arg;
				}
			}
		}

		if (isset($this->args['id'])) $this->id = $this->args['id'];
		if (isset($this->args['page'])) $this->page = $this->args['page'];

		if (isset($this->args['sortdesc'])) {
			$this->sort = $this->args['sortdesc'];
			$this->sort_direction = 'desc';
		}
		else if (isset($this->args['sort'])) {
			$this->sort = $this->args['sort'];
			$this->sort_direction = 'asc';
		}

		if (isset($this->args['field'])) {
			$field = $this->args['field'];
			$r = $this->on_before_field_action($this->action);
			if (is_string($r)||is_object($r)) return $r;
			if ($r===false) return $this->page_not_found();
			return $this->field_action($field,$this->action);
		}

		$r = $this->on_before_action($this->action);
		if (is_string($r)||is_object($r)) return $r;
		if ($r===false) return $this->page_not_found();
		$method = "action_{$this->action}";

		$rc = Events::call($this->mnemocode('before_execute_action'), $method, $action, $args, $this);
		if (!empty($rc)) {
			return $rc;
		}

		if (!empty($method)) {
			return $this->$method();
		} else {
			return $this->page_not_found();
		}
	}


	////////////////////////////////////////////////////////////////////////
	// Urls

	protected function args_for_urls() {
		$out = array();
		foreach($this->args as $k => $v) {
			if (!is_int($k)&&$k!='page'&&$k!='id') {
				$out[] = $k;
			}
		}
		return $out;
	}
	
	protected function args_string() {
		$out = '';
		$args = $this->args_for_urls();
		foreach($args as $arg) {
			if (isset($this->args[$arg])) {
				$value = trim($this->args[$arg]);
				if ($value!='') {
					$out .= "$arg-$value/";
				}
			}
		}
		return $out;
	}

	public function action_url($action,$p=false,$args=false,$extra=false) {
		$url = $this->urls->admin_url();
		if (is_object($p)) {
			$url .= "$action/";
			$url .= "page-$this->page/";
			$url .= "id-".$this->item_id($p)."/";
		}

		else if ($p) {
			if ($action!='list'||$p!=1) $url .= "$action/page-$p/";
		}

		else {
			$url .= "$action/";
		}

		$_sort = $this->sort;
		$_direction = $this->sort_direction;

		if (is_array($extra)) {
			if (isset($extra['sort'])) $_sort = $extra['sort'];
			if (isset($extra['sort_direction'])) $_direction = $extra['sort_direction'];
		}

		if ($_sort) {
			$sort = $_direction=='desc'?'sortdesc':'sort';
			$url .= "$sort-$_sort/";
		}
		
		$url .= $this->args_string();

		$qs = $this->args_to_query_string($args);
		return $url.$qs;
	}

	protected function args_to_query_string($args=false) {
		$qs = '';

		if (Core_Types::is_iterable($args))
			foreach($args as $arg => $value)
				$qs .= ($qs==''? '?':'&')."$arg=$value";
		else if (is_string($args))
			$qs = ($args!=''?'?':'').$args;
		else foreach($this->filters() as $filter)	{
			if (isset($_GET[$filter]))
				$qs .= ($qs==''? '?':'&')."$filter=".$_GET[$filter];
		}

		return $qs;
	}

	public function list_url($page) {
		return $this->action_url('list',$page);
	}

	protected function on_field_item_access($item) {
		return (!$this->check_item_access($item))||(!$this->access_edit($item));
	}

	protected function fields_for_action() {
		return array($this->form_fields('edit'), $this->filters_form());
	}

	public function field_action_url($field, $action, $item = false, $args = false) {
		$url = parent::field_action_url($field, $action, $item , $args);
		$qs = $this->args_to_query_string($args);
		return $url.$qs;
	}


	////////////////////////////////////////////////////////////////////////
	// List

	protected $list_style = 'table';
	protected function list_style() {
		return $this->list_style;
	}

	protected $list_fields = array();
	protected function list_fields() {
		$fields = $this->schema_fields();
		if (!$fields) {
			if ($mapper = $this->orm_mapper()) {
				$fields = $mapper->schema_fields();
				if (!is_array($fields)||count($fields)==0) {
					$fields = false;
				}
			}
		}
		if ($fields) {
			$fields = $this->search_fields($fields,array(),'in_list','weight_in_list','caption_in_list');
			return Core_Arrays::merge($this->list_fields, $fields);
		} else {
			return $this->list_fields;
		}
	}
	
	protected $per_page = 20;
	protected function per_page() {
		return $this->per_page;
	}

	protected $title_list = 'lang:_common:ta_list';
	protected function title_list() {
		return $this->title_list;
	}

	protected $norows = 'lang:_common:ta_norows';
	protected function message_norows() {
		return $this->norows;
	}

	protected $button_list = 'lang:_common:ta_button_list';
	protected function button_list() {
		return $this->button_list;
	}

	protected $submit_massupdate	= 'lang:_common:ta_submit_mass_edit';
	protected function submit_massupdate() {
		return $this->submit_massupdate;
	}


	protected $del_confirm	= 'lang:_common:ta_del_confirm';
	protected $copy_confirm = false;

	public function row_actions() {
		return array(
			'copy' => array(
				'confirm' => $this->copy_confirm,
			),
			'edit' => true,
			'delete' => array(
				'confirm' => $this->del_confirm,
			),
		);
	}

	protected $can_copy = false;
	protected function row_can_copy($row) {
		return $this->can_copy;
	}

	protected $can_edit = true;
	protected function row_can_edit($row) {
		return $this->access_edit($row);
	}

	protected $can_delete = true;
	protected function row_can_delete($row) {
		return $this->access_delete($row);
	}

	protected $can_massupdate = true;
	protected function access_massupdate() {
		return $this->can_massupdate && $this->access('massupdate');
	}

	public function row_action_enabled($row,$action) {
		switch($action) {
			case 'copy':
				$r = $this->row_can_copy($row);
				return $r;
			case 'edit':
				return $this->row_can_edit($row);
			case 'delete':
				return $this->row_can_delete($row);
		}

		return true;
	}


	protected $count = 0;

	public function sort_param($field,$direction) {
		$fields = $this->list_fields();
		if (!isset($fields[$field])) return false;
		$data = $fields[$field];
		if (!isset($data['order_by'])) return false;
		if (is_string($data['order_by'])) return $data['order_by'].($direction=='desc'?' desc':'');
		return 'id';
	}

	public function sort_url($field,$direction) {
		if (!$this->sort_param($field,$direction)) return false;
		return $this->action_url('list',1,false,array('sort' => $field,'sort_direction' => $direction));
	}


	protected function render_list($parms) {
		return $this->render('list',$parms);
	}

	protected function prepare_filter() {
		$filter = array();
		foreach($this->filters() as $fn) if (isset($this->request[$fn])) $filter[$fn] = $this->request[$fn];
		foreach($this->force_filters() as $fk => $fv) $filter[$fk] = $fv;
		return $filter;
	}

	protected $numpages = 1;
	protected $rows;

	protected function get_rows() {
		if (empty($this->rows)) {
			$filter = $this->prepare_filter();
			$per_page = $this->per_page();
			$this->count = $this->count_all($filter);
			$this->numpages = CMS::calc_pages($this->count,$per_page,$this->page);

			$filter['offset'] = ($this->page-1)*$per_page;
			$filter['limit'] = $per_page;

			if ($this->sort) {
				$order_by = $this->sort_param($this->sort,$this->sort_direction);
				if ($order_by) $filter['order_by'] = $order_by;
			}

			$rows = $this->select_all($filter);
			foreach($rows as $row) {
				$this->on_row($row);
				Events::call($this->mnemocode().'.on_row',$row);
			}
			$this->rows = $rows;
		}

		return $this->rows;
	}

	protected $massupdate_fields = array();

	protected function massupdate_form($rows) {
		if (count($rows)==0) return false;
		$fields = $this->list_fields();
		if ($fields) {
			foreach($this->list_fields() as $field => $parms) {
				if (isset($parms['edit'])) {
					$data = $parms['edit'];
					if ($data===true) $data = array('type' => 'input');
					if (is_string($data)) $data = array('type' => $data);
					$this->massupdate_fields[$field] = $data;
				}
			}
		}
		if (count($this->massupdate_fields)==0) return false;
		$form = Forms::Form('massupdate')->action($this->action_url('list',$this->page))->input('ids');

		$ids = array();
		foreach($rows as $row) {
			$id = $this->item_id($row);
			$ids[$id] = $id;
			foreach($this->massupdate_fields as $field=>$data) {
				$name = "$field$id";
				$type = CMS_Fields::type($data);
				if ($type->is_upload()) $type = CMS_Fields::type('input');
				$type->form_fields($form,$name,$data);
				$type->assign_from_object($form,$row->$field,$name,$data);
			}
		}
		$form['ids'] = implode(',',$ids);

		return $form;

	}

	protected function prepare_filter_forms() {
		$filters_form_fields = $this->filters_form();
		list($filter_item, $attrs) = $this->filter_object();
		foreach ($filters_form_fields as $k => $f) {
			$f['__item'] = $filter_item;
			$filters_form_fields[$k] = $f;
		}
		return $filters_form_fields;
	}

	protected function action_list() {

		$fform = $this->create_filters_form();

		$this->on_before_list();

		$rows = $this->get_rows();

		$form = false;
		if ($this->access_massupdate()) {
			$form = $this->massupdate_form($rows);
			if ($form) {
				if ($this->env->request->method_code==Net_HTTP::POST) {
					if ($form->process($this->env->request)) {
						foreach(explode(',',$form['ids']) as $id) {
							$id = trim($id);
							if ($id!='') {
								$item = $this->load($id);
								if ($this->access_edit($item)) {
									foreach($this->massupdate_fields as $field => $data) {
										$name = "$field$id";
										$type = CMS_Fields::type($data);
										$obj = new ArrayObject;
										$type->assign_to_object($form,$obj,$name,$data);
										$item->$field = $obj[$name];
									}
									$this->update($item);
								}
							}
						}
						Events::call('admin.change');
					}
					return $this->redirect_to($this->action_url('list',$this->page));
				}
			}
		}

		$page_navigator = false;
		if ($this->numpages>1) $page_navigator = CMS::page_navigator($this->page,$this->numpages,array($this,'list_url'));

		$filters_buttons = array();
		foreach($this->filters_buttons() as $caption => $url) {
			$url = trim($url);
			if ($caption[0]!='*') {
				if ($m = Core_Regexps::match_with_results('{^(\w+)=(.+)$}',$url)) {
					$name = trim($m[1]);
					$value = trim($m[2]);
					if (isset($_GET[$name])&&$_GET[$name]==$value) $caption = "*$caption";
				}
				if ($m = Core_Regexps::match_with_results('{\{(.+)\}}',$url)) {
					$cond = trim($m[1]);
					if ($cond!=''&&$cond[0]=='!') {
						$cond = substr($cond,1);
						if (!isset($_GET[$cond])) $caption = "*$caption";
					}
				}
			}

			$url = preg_replace('{\{.+\}}','',$url);

			if ($url==''||($url[0]!='/'&&!Core_Regexps::match('{^http:}',$url)))
				$url = $this->action_url('list',1,$url);
			$filters_buttons[$caption] = $url;
		}

		$filters_form_fields = $this->prepare_filter_forms();

		if ($this->add_in_list && $this->access_add()) {
			$this->create_form($this->action_url('add',$this->page), 'add');
			$item = $this->new_object();
			$this->item_to_form($item);
		}

		return $this->render_list(array(
			'title' => $this->title_list(),
			'form' => $this->add_in_list ?  $this->form : null,
			'form_fields' => $this->add_in_list ? $this->filtered_form_fields : null,
			'submit_text' => $this->submit_add(),
			'count' => $this->count,
			'rows' => $rows,
			'list_fields' => $this->list_fields(),
			'list_style' => $this->list_style(),
			'message_norows' => $this->message_norows(),
			'can_add' => $this->access_add(),
			'add_url' => $this->action_url('add',$this->page),
			'add_button_caption' => $this->button_add(),
			'massupdate_form' => $form,
			'massupdate_fields' => $this->massupdate_fields,
			'massupdate_submit_text' => $this->submit_massupdate(),
			'page_navigator' => $page_navigator,
			'filters_buttons' => $filters_buttons,
			'filters_form' => $fform,
			'filters_form_fields' => $filters_form_fields,
			'sort' => $this->sort,
			'sort_direction' => $this->sort_direction,
		));
	}

	////////////////////////////////////////////////////////////////////////
	// Form

	protected $filtered_form_fields = array();

	protected function form_fields($action = 'edit') {
		$fields = $this->schema_fields();
		if (!$fields) {
			if ($mapper = $this->orm_mapper()) {
				$fields = $mapper->schema_fields();
				if (!is_array($fields)||count($fields)==0) {
					$fields = false;
				}
			}
		}
		if ($fields) {
			return $this->search_fields($fields,array(),'in_form','weight_in_form','caption_in_form', true);
		}
		return parent::form_fields($action);
	}

	protected $form_tabs = array();
	protected function form_tabs($action='edit',$item=false) {
		if ($tabs = $this->schema_tabs()) {
			return $tabs;
		}
		if ($mapper = $this->orm_mapper()) {
			if ($tabs = $mapper->schema_tabs($action)) {
				return $tabs;
			}
		}
		return $this->form_tabs;
	}

	protected function access_tab($tab, $data, $action, $item = false) {
		$er = Events::call('cms.table.tabs', $tab, $data, $action, $item, $this);
		if (!is_null($er)) return $er;
		return true;
	}

	protected function get_form_tabs($action,$item=false) {
		$tabs = $this->form_tabs($action,$item);
		$out = array();
		if (!Core_Types::is_iterable($tabs)) return $out;
		$weight = 0;
		$delta = 0.0001;
		foreach($tabs as $tab => $data) {
			if (!$this->access_tab($tab, $data, $action, $item)) continue;
			if (is_string($data)) $data = array('caption'=>$data);
			$valid = true;
			if (isset($data['edit_only'])&&$data['edit_only']&&$action!='edit') $valid = false;
			if (isset($data['add_only'])&&$data['add_only']&&$action!='add') $valid = false;
			if (!isset($data['weight'])) {
				$weight += $delta;
				$data['weight'] = $weight;
			}
			if ($valid) $out[$tab] = $data;
		}
		uasort($out, array($this, 'sort_by_weight'));
		return $out;
	}	


	////////////////////////////////////////////////////////////////////////
	// Edit

	protected function with_save_button() {
		return false;
	}

	protected function save_button_text() {
		return 'lang:_common:ta_save_button';
	}

	protected $title_edit	= 'lang:_common:ta_title_edit';
	protected function title_edit($item) {
		return $this->title_edit;
	}

	protected $submit_edit		= 'lang:_common:ta_submit_edit';
	protected function submit_edit($item) {
		return $this->submit_edit;
	}

	protected function render_edit($parms) {
		return $this->render('edit',$parms);
	}

	protected function access_edit($item) {
		return $this->can_edit && $this->access('edit', $item);
	}

	protected function redirect_after_edit() {
		return $this->action_url('list',$this->page);
	}

	protected $edit_item = false;

	protected function action_edit() {
		$item = $this->load($this->id);
		if (!$item) return $this->page_not_found();

		$this->edit_item = $item;

		if (!$this->access_edit($item)) {
			return $this->access_denied($item);
		}

		$o = $this->on_before_action_edit($item);
		if (is_object($o)||is_string($o)) return $o;

		$this->create_form($this->action_url('edit',$item),'edit');
		$this->item_to_form($item);

		$errors = array();
		if ($this->env->request->method_name=='post') {
			$errors = $this->process_form($item);
			if (sizeof($errors)==0) {
				$this->form_to_item($item);
				if (count($this->upload_fields)>0) $this->process_uploads($item);
				$this->process_inserted_item($item);
				$this->on_before_change_item($item);
				$this->on_before_update_item($item);
				$this->update($item);
				$this->on_after_change('edit');
				$this->on_after_change_item($item);
				$this->on_after_update_item($item);
				Events::call('admin.change',$item);
				if (isset($_POST['__save_and_stay'])&&$_POST['__save_and_stay']!='0') {
					return $this->redirect_to($_POST['__save_and_stay']);
				}
				return $this->redirect_to($this->redirect_after_edit($item));
			}
		}


		return $this->render_edit(array(
			'title' => $this->title_edit($item),
			'form' => $this->form,
			'form_fields' => $this->filtered_form_fields,
			'submit_text' => $this->submit_edit($item),
			'with_save_button' => $this->with_save_button($item),
			'save_button_text' => $this->save_button_text($item),
			'item' => $item,
			'item_id' => $this->id,
			'list_url' => $this->action_url('list',$this->page),
			'list_button_caption' => $this->button_list(),
			'form_tabs' => $this->get_form_tabs('edit',$item),
			'errors' => $errors,
		));

	}


	////////////////////////////////////////////////////////////////////////
	// Add


	protected $title_add = 'lang:_common:ta_title_add';
	protected function title_add() {
		return $this->title_add;
	}

	protected $submit_add = 'lang:_common:ta_submit_add';
	protected function submit_add() {
		return $this->submit_add;
	}

	protected $button_add = 'lang:_common:ta_button_add';
	protected function button_add() {
		return $this->button_add;
	}


	protected function render_add($parms) {
		return $this->render('add',$parms);
	}

	protected $can_add = true;
	protected function access_add() {
		return $this->can_add && $this->access('add');
	}

	protected function redirect_after_add($item) {
		return $this->action_url('list',$this->page);
	}

	protected function action_add() {
		if (!$this->access_add()) {
			return $this->access_denied();
		}
		$this->on_before_action_add();
		$this->create_form($this->action_url('add',$this->page),'add');
		$item = $this->new_object();

		if (method_exists($item,'auto_add')&&$item->auto_add()) {
			$this->on_before_change_item($item);
			$this->on_before_insert_item($item);
			$this->insert($item);
			$this->orm_mapper_for_select()->auto_add_delete_old();
			return $this->redirect_to($this->action_url('edit',$item));
		}

		$this->item_to_form($item);

		if ($this->env->request->method_name=='post') {
			$errors = $this->process_form($item);
			if (sizeof($errors)==0) {
				$this->form_to_item($item);
				$this->on_before_change_item($item);
				$this->on_before_insert_item($item);
				$this->insert($item);
				if (count($this->upload_fields)>0) $this->process_uploads($item);
				$this->process_inserted_item($item);
				$this->update($item);
				$this->on_after_change('add');
				$this->on_after_change_item($item);
				$this->on_after_insert_item($item);
				Events::call('admin.change',$item);
				return $this->redirect_to($this->redirect_after_add($item));
			}
		}


		return $this->render_add(array(
			'title' => $this->title_add(),
			'form' => $this->form,
			'form_fields' => $this->filtered_form_fields,
			'submit_text' => $this->submit_add(),
			'item' => $item,
			'item_id' => 0,
			'list_url' => $this->action_url('list',$this->page),
			'list_button_caption' => $this->button_list(),
			'form_tabs' => $this->get_form_tabs('add',$item),
			'errors' => $errors,
		));


	}


	////////////////////////////////////////////////////////////////////////
	// Delete

	protected function redirect_after_delete($item) {
		return $this->action_url('list',$this->page);
	}

	protected function access_delete($item) {
		return $this->can_delete && $this->access('delete', $item);
	}

	protected function action_delete() {
		$item = $this->load($this->id);
		if (!$item) return $this->page_not_found();
		if (!$this->access_delete($item)) return $this->page_not_found();
		$redirect = $this->redirect_after_delete($item);
		$this->on_before_delete_item($item);
		$this->delete($item);
		$this->on_after_delete_item($item);
		$this->on_after_change('delete');
		Events::call('admin.change',$item);
		return $this->redirect_to($redirect);
	}


}

