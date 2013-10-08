<?php

Core::load('Log', 'CMS.Controller.Table', 'Tree');

class CMS_Controller_SlickTable extends CMS_Controller_Table
{

	public static function initialize()
	{
		// Log::logger()->to_firephp();
	}

	protected $style = "max-height:528px;";
	// protected $style = "";

	protected $expanded_to_level = -1;

	protected $last = array();

	protected $offset = 0;

	protected $limit = 100;

	protected $chunks = 5;

	protected $chunks_break = 2;

	protected $use_chuncks = true;

	protected $tree_fields = array();

	protected $tail_ids = array();

	protected $sort_data = array('column' => null, 'dir' => 1);

	protected function tree_fields() {
		return $this->slick_fields();
	}

	protected function slick_fields()
	{
		return !empty($this->tree_fields) ? $this->tree_fields : $this->list_fields();
	}

	protected function options()
	{
		return array('limit' => $this->limit);
	}

	protected function default_action($action = 'default', $args = array())
	{
		return 'tree';
	}

	protected function action_columns()
	{
		return json_encode($this->tree_fields());
	}

	protected function action_options()
	{
		return json_encode($this->options());
	}

	// protected function action_slick()
	// {
	// 	return $this->render_slick();
	// }

	protected function set_sort($column, $dir = 1)
	{
		$this->sort_data['column'] = $column;
		$this->sort_data['dir'] = $dir == 1 ? 'ASC' : "DESC";
		return $this;
	}

	public function index($action,$args)
	{
		// Log::logger()->debug($action);
		$res = parent::index($action,$args);
		return $res;
	}

	protected function render_slick()
	{
		$fform = $this->create_filters_form();
		$filters_form_fields = $this->prepare_filter_forms();
		$t = $this->render('slick', array(
			'c' => $this,
			'title' => $this->title_list(),
			'count' => $this->total($this->orm_mapper()),
			'message_norows' => $this->message_norows(),
			'can_add' => $this->access_add(),
			'add_url' => $this->action_url('add',$this->page),
			'add_button_caption' => $this->button_add(),
			'filters_form' => $fform,
			'filters_form_fields' => $filters_form_fields,
			'style' => $this->style,
		));
		$this->preprocess_render($t);
		return $t;
	}

	protected function app_js_file()
	{
		return CMS::stdfile_url('scripts/SlickGrid/slick.app.js');
	}

	protected function preprocess_render($t)
	{
		$t->use_styles(
			CMS::stdfile_url('styles/admin/table.css'),
			CMS::stdfile_url('styles/SlickGrid/slick.grid.css'),
			CMS::stdfile_url('styles/SlickGrid/slick.css'),
			CMS::stdfile_url('styles/SlickGrid/controls/slick.pager.css')
			//CMS::stdfile_url('styles/SlickGrid/css/smoothness/jquery-ui-1.8.16.custom.css')
		);
		$t->use_scripts(
				CMS::stdfile_url('scripts/tao.js'),
				CMS::stdfile_url('scripts/jquery/ui.js'),
				CMS::stdfile_url('scripts/jquery/event.drag.js'),
				CMS::stdfile_url('scripts/jquery/event.drop.js'),
				CMS::stdfile_url('scripts/SlickGrid/slick.core.js'),
				CMS::stdfile_url('scripts/SlickGrid/slick.formatters.js'),
				CMS::stdfile_url('scripts/SlickGrid/slick.editors.js'),
				CMS::stdfile_url('scripts/SlickGrid/slick.grid.js'),
				CMS::stdfile_url('scripts/SlickGrid/slick.dataview.js'),
				CMS::stdfile_url('scripts/SlickGrid/controls/slick.remotepager.js'),
				// CMS::stdfile_url('scripts/SlickGrid/slick.remotestore.js'),
				// CMS::stdfile_url('scripts/SlickGrid/slick.table.js'),
				// CMS::stdfile_url('scripts/SlickGrid/slick.tree.class.js'),
				CMS::stdfile_url('scripts/SlickGrid/plugins/slick.rowmovemanager.js'),
				CMS::stdfile_url('scripts/SlickGrid/plugins/slick.rowselectionmodel.js'),
				$this->app_js_file()
			);
		return $t;
	}

	protected function action_tree()
	{
		if ($this->env->request->is_xhr()) {
			$mapper = $this->tree_mapper();
			$info = $this->get_tree_data($mapper);

			$s = microtime(true);
			$info = $this->cut_result($info);
			$rows = $this->fetch_result_by($info);
			$res = array();
			foreach ($rows as $k => $row) {
				$this->on_row($row);
				$attrs = $this->tree_data_row_extra_fields($row->attrs, $row);
				$res[$k] = $attrs;
			}
			Log::logger()->debug('cut_result+fetch_time:', microtime(true) - $s);
			$total = $this->total($mapper);
			return json_encode(array('total' => $total, 'count' => count($res), 'stories' => $res));
		} else {
			return $this->render_slick();
		}
	}

	protected function tree_data_row_extra_fields($row,$entity)
	{
		return $row;
	}

	protected function cut_result($rows)
	{
		$offset = 0;
		$keys = array_keys($rows);
		$values = array_values($rows);
		if (!empty($this->last)) {
			$id = (int) $this->last['id'];
			$find = array_search($id, $keys);
			if ($find !== FALSE) {
				$offset = $find + 1;
			}
		}
		return array_slice($rows, $offset, $this->limit, true);
	}

	protected function fetch_result_by($info)
	{
		$rows = $this->orm_mapper()->spawn()->in('id', array_keys($info))->select($key = 'id')->getArrayCopy();
		$res = array();
		foreach ($info as $id => $inf) {
			$row = $rows[$id];
			$row['depth'] = $inf['depth'];
			$row['_collapsed'] = $inf['_collapsed'];
			$res[$id] = $row;
		}
		if (count($res) > 0) {
			$keys = range($this->offset, $this->offset + count($res) - 1);
			$res = array_combine($keys, array_values($res));
		}
		return $res;
	}

	protected function get_tree_data($mapper)
	{
		$this->last_element();
		$rows = $this->get_root_rows($mapper);

		$s = microtime(true);
		$this->load_childs($rows);
		Log::logger()->debug('load_childs_time:', microtime(true) - $s);

		$s = microtime(true);
		$tree = Tree::create_tree($rows);
		Log::logger()->debug('create_tree_time:', microtime(true) - $s);

		$res = array();
		$depth = 0;
		$s = microtime(true);
		foreach ($tree as $i => $row) {
			$this->add_item($res, $row, $depth);
		}
		Log::logger()->debug('add_items_time:', microtime(true) - $s);

		return $res;
	}

	protected function add_item(&$res, $row, $depth = 0)
	{
		$row['depth'] = $depth;
		if ($this->expanded_to_level != -1 &&  $this->expanded_to_level <= $depth) {
			$row['_collapsed'] = true;
		}
		$attrs = $row;
		unset($attrs['childs']);
		if (!in_array($row['id'], $this->tail_ids) || $row['id'] == $this->last['id']) {
			$res[$row['id']] = $attrs;
		}
		if (isset($row['childs'])) {
			$this->childs_items($res, $row['childs'], $depth);
		}
	}

	protected function childs_items(&$res, $childs, $depth = 0)
	{
		$depth++;
		foreach ($childs as $row) {
			$this->add_item($res, $row, $depth);
		}
	}

	protected function last_element()
	{
		$data = json_decode($this->env->request->content, true);
		if (isset($data['last']['id']) && $data['last']['id']) {
			$this->last = $data['last'];
			$this->tail_ids[] = $data['last']['id'];
			$this->load_tail($this->last);
		}
	}

	//FIXME: can lags
	protected function load_childs(&$rows)
	{
		if (!$this->use_chuncks) {
			list($rows, $tmp) = $this->load_childs_by($rows, $rows);
			return $rows;
		}

		$input_rows = $rows;
		Log::logger()->debug(count($rows));

		$id = 0;
		if (!empty($this->last)) {
		 	$id = (int) $this->last['id'];
		}
		$count = 0;
		$break = false;
		$start_counting = false;

		if ($id > 0 && isset($rows[$id])) {
			$start_counting = true;
			$count = 1;
		}

		foreach (array_chunk($input_rows, $this->chunks, true) as $ci => $chunk) {
			Log::logger()->debug('chunk', $ci);
			$s = microtime(true);
			list($rows, $childs)  = $this->load_childs_by($rows, $chunk);
			Log::logger()->debug('load_childs_by_time', microtime(true) - $s);
			Log::logger()->debug('childs_count', count($childs));
			//MAGIC:
			if ($break) {
				break;
			}
			if ($id > 0) {
				if ($start_counting) {
					$count += count($childs) + 1;
				} else {
					$find = array_search($id, array_keys($childs));
					if ($find !== FALSE) {
						// $count += count($childs) - $find;
						$start_counting = true;
					}
					$count += 1;
				}
				if ($count > $this->limit) {
					$break = true;
				}
			} else if (count($ids) >= $this->limit * $this->chunks_break) {
				$break = true;
			}
			// if ($break) {
			// 	break;
			// }
		}
		
		Log::logger()->debug('rows_count', count($rows));
		return $rows;
	}

	protected function extract_parent_ids($ids)
	{
		$res = $this->get_tree_rows($this->orm_mapper()->spawn()->only('id', 'parent_id')->in('parent_id', $ids));
		return $res;
	}

	protected function load_childs_by($rows, $input)
	{
		$child_count = 0;
		$result_child = $child = $this->extract_parent_ids(array_keys($input));
		$i = 0;
		while(count($child) > 0) {
			$child = $this->extract_parent_ids(array_keys($child));
			$result_child += $child;
			$i++;
		}
		Log::logger()->debug('depth', $i);
		$rows += $result_child;
		return array($rows, $result_child);
	}

	//FIXME: can lags
	protected function load_tail($el)
	{
		$pid = $el['parent_id'];
		while($pid) {
			$el = $this->orm_mapper()->spawn()->as_array()->where('id = :pid', $pid)->select_first();
			if (!$el) {
				break;
			}
			$this->tail_ids[] = $el['id'];
			$pid = $el['parent_id'];
		}
	}

	protected function get_tree_rows($mapper)
	{
		$parms = $this->prepare_filter();
		$mapper = $this->orm_mapper_for_parms($mapper, $parms);
		$mapper = $mapper->spawn()->as_array();
		if (!empty($this->sort_data['column'])) {
			$mapper = $mapper->order_by($this->sort_data['column'] . ' ' . $this->sort_data['dir']);
		}
		else {
			$mapper = $mapper->order_by('IFNULL(ord,0)' . ($mapper->options['order_by'] ? ',' . $mapper->options['order_by'] : ''));
		}
		return $mapper->select($key = 'id')->getArrayCopy();
	}

	protected function get_root_rows($mapper)
	{
		$mapper = $mapper->spawn()->where("parent_id = 0 OR parent_id IS NULL")->only('id');
		$ids = array_keys($this->get_tree_rows($mapper));
		$start_id = reset($ids);
		if (!empty($this->last) && !empty($this->tail_ids)) {
			$start_id = end($this->tail_ids);
		}
		if ($start_id) {
			$start_index = array_search($start_id, $ids);
			if ($start_index !== false) {
				$ids = array_slice($ids, $start_index, $this->limit + 1, true);
			}	
		}
		return $this->get_tree_rows($this->orm_mapper()->spawn()->only('id', 'parent_id')->in('id', $ids));
	}

	protected function total($mapper)
	{
		$parms = $this->prepare_filter();
		$mapper = $this->orm_mapper_for_parms($this->orm_mapper(), $parms);
		return $mapper->count();
	}

	protected function tree_mapper()
	{
		$this->pager();
		$mapper = $this->orm_mapper()->spawn();
		return $mapper;
	}

	protected function pager()
	{
		$per_page = $this->per_page();
		$pager = array();
		if (isset($this->request['offset'])) {
			$pager['offset'] = $this->request['offset'];
		} else {
			$this->page = (int) isset($this->request['page']) ? $this->request['page'] : 1;
			$pager['offset'] = ($this->page-1)*$per_page;
		}
		if (isset($this->request['count'])) {
			$pager['limit'] = $this->request['count'];
		} else {
			$pager['limit'] = $per_page;
		}
		if (isset($this->request['sortcol'])) {
			$this->set_sort($this->request['sortcol'], $this->request['sortdir']);
		}
		$this->offset = $pager['offset'];
		$this->limit = $pager['limit'];
		return $pager;
	}

	protected function action_update()
	{
		if ($this->env->request->is_xhr()) {
			return $this->tree_update();
		}
	}

	protected function save_reorder($parent_id)
	{
		$this->orm_mapper()->spawn()->where('ord = 0 OR ord = NULL')->update_all(array(), array('ord' => 'id'));
	}

	protected function tree_update()
	{
		$data = json_decode($this->env->request->content, true);
		if (isset($data['id'])) $data = array($data);
		try {
			foreach ($data as $row) {
				if (!empty($row['id'])) {
					$entity = $this->load($row['id']);
					if ($entity->id()) {
						foreach ($row as $k => $v) {
							$entity[$k] = $v;
						}
						// Log::logger()->debug('item', $entity->attrs);
						$entity->update();
					}
				}
				$this->save_reorder($parent_id);
			}
			return Net_HTTP::Response()->
				content_type('application/json')->
				body(json_encode(array('success' => true, 'message' => 'OK')))
				;
		} catch (Exception $e) {
			return Net_HTTP::Response()->
				content_type('application/json')->
				body(json_encode(array('success' => false, 'message' => $e->getMessage() /* 'Error' */)));
		}
	}

}