<?php

Core::load('CMS.Controller.Table');

class CMS_Controller_TreeTable extends CMS_Controller_Table implements Core_ModuleInterface {
	const VERSION = '0.0.0';
	
	protected $state_expanded = array();
	protected $state_expanded_name;
	protected $state_prefix = 'ext-';
	protected $tree_fields = array();
	protected $enable_checkcolumn = false;
	
	
	public function setup() {
		$this->setup_settings();
		$this->load_state();
		return parent::setup();
	}
	
	protected function setup_settings() {
		$this->state_expanded_name = !empty($this->state_expanded_name) ? $this->state_expanded_name :  str_replace('.', '_', $this->name) . '_expanded';
		Templates_HTML::add_scripts_settings(array('tree' => array('expanded_state_name' => $this->state_expanded_name)));
		Templates_HTML::add_scripts_settings(array('tree' => array(
			'edit_icon' => '/files/_assets/images/edit.gif',
			'delete_icon' => '/files/_assets/images/del.gif',
			'fields' => $this->tree_fields()
		)));
	}
	
	protected function tree_fields() {
		return !empty($this->tree_fields) ? $this->tree_fields : $this->list_fields();
	}
	
	protected function load_state() {
		$name = $this->state_prefix . $this->state_expanded_name;
		$this->state_expanded = (array) $this->decode_value(WS::env()->request[$name]);
	}
	
	public function app_js_file() {
		return CMS::stdfile_url('scripts/admin-tree-app.js');
	}
	
	protected function default_action($action, $args) {
		return 'tree';
	}
	
	protected function action_tree() {
		return $this->render_tree();
	}
	
	protected function action_json_tree() {
		if ($this->env->request->is_xhr()) {
			return $this->tree_json();
		}
	}
	
	protected function action_update_tree() {
		if ($this->env->request->is_xhr()) {
			return $this->tree_update();
		}
	}
	
	protected function render_tree() {
		$t = $this->render('tree', array(
			'c' => $this,
			'title' => $this->title_list(),
			'count' => $this->get_tree_count(),
			'message_norows' => $this->message_norows(),
			'can_add' => $this->access_add(),
			'add_url' => $this->action_url('add',$this->page),
			'add_button_caption' => $this->button_add(),
		));
		if ($this->enable_checkcolumn)
			$t->use_scripts(CMS::stdfile_url('scripts/extjs/CheckColumn.js'))
				->use_styles(CMS::stdfile_url('styles/extjs/CheckHeader.css'));
		return $t;
	}
	
	protected function tree_update() {
		//TODO: one update query
		$data = json_decode($this->env->request->content, true);
		$return = array();
		if (isset($data['id'])) $data = array($data);
		try {
			foreach ($data as $row) {
				if (!empty($row['id'])) {
					$entity = $this->load($row['id']);
					if ($entity->id()) {
						if (isset($row['index'])) {
							$row['ord'] = $row['index'];
							unset($row['index']);
						}
						if (isset($row['parentId'])) {
							$row['parent_id'] = $row['parentId'];
							unset($row['parentId']);
						}
						foreach ($row as $k => $v)
							$entity[$k] = $v;
						$entity->update();
						$return[] = $this->tree_data_row($entity);
					}
				}
			}
			return Net_HTTP::Response()->
				content_type('application/json')->
				body(json_encode(array('success' => true, 'data' => count($return) == 1 ? reset($return) : $return)));
		} catch (Exception $e) {
			return Net_HTTP::Response()->
				content_type('application/json')->
				body(json_encode(array('success' => false, 'message' => $e->getMessage())));
		}
	}
	
	protected function tree_json() {
		$res = Net_HTTP::Response()->content_type('application/json');
		$data = $this->tree_data(WS::env()->request['node'], json_decode(WS::env()->request['sort'], true));
		return $res->body(json_encode($data));
	}
	
	protected function tree_data($root, $sort) {
		$root = $root == 'root' ? 0 : (int) $root;
		$rows = $this->get_tree_rows($root, $sort);
		$data = array();
		foreach ($rows as $r) {
			$data[] = $this->tree_data_row($r);
		}
		return $data;
	}

	protected function get_tree_count($entity = 0) {
		if ($this->storage_name) {
			$q = $this->storage_query($entity);
			return $this->storage()->count($q);
		}
		return $this->tree_mapper($entity)->count();
	}

	protected function storage_query($entity = 0) {
		$pid =  is_object($entity) ? $entity->id() : $entity;
		$q = $this->storage()->create_query()->order_by('ord')->order_by('id');
		return $pid == 0 ? $q->eq_or_none('parent_id', $pid) : $q->eq('parent_id', $pid);
	}

	protected function get_tree_rows($root, $sort = null) {
		if ($this->storage_name) {
			$q = $this->storage_query($root);
			return $this->storage()->select($q);
		}
		$mapper = $this->tree_mapper($root);
		return $mapper->order_by('ord' . ($mapper->options['order_by'] ? ','.$mapper->options['order_by'] : ''))->select();
	}
	
	//TODO: into Mapper
	protected function tree_mapper($root = 0) {
		$filter = $this->prepare_filter();
		$mapper = $this->orm_mapper_for_select($filter)->spawn();
		if ($root instanceof DB_ORM_EntityInterface) $root = $root->id();
		return $root == 0 ? $mapper->where('parent_id = :root OR parent_id IS NULL', $root) :
			$mapper->where('parent_id = :root', $root);
	}
	
	//TODO: into Entity
	protected function tree_data_row($entity) {
		$this->on_row($entity);
		$row = array();
		$row['title'] = $entity->title;
		$row['id'] = $entity->id;
		$row['ord'] = $entity->ord;
		$row['parent_id'] = $entity->parent_id;
		$row['children_count'] = $this->get_tree_count($entity);
		$row['children'] = $this->tree_data_row_children($entity, $row);
		$row['leaf'] = $this->tree_data_row_is_leaf($entity, $row);
		$row['expanded'] = $this->tree_data_row_is_expanded($entity, $row);
		$row['edit'] = $this->action_url('edit',$entity);
		$row['delete'] = $this->action_url('delete',$entity);
		return $this->tree_data_row_extra_fields($entity, $row);
	}
	
	protected function tree_data_row_extra_fields($entity, $row) {
		foreach ($this->tree_fields() as $name => $f)
			$row[$name] = $entity->$name;
		return $row;
	}
	
	protected function tree_data_row_children($entity, $row) {
		if (!$row['children_count']) return array();
		if ($this->tree_data_row_is_expanded($entity, $row)) {
			$res = array();
			foreach ($this->get_tree_rows($entity) as $ch) {
				$res[] = $this->tree_data_row($ch);
			}
			return $res;
		}
		return null;
	}
	
	protected function tree_data_row_is_leaf($entity, $row) {
		return false;
	}
	
	protected function tree_data_row_is_expanded($entity, $row) {
		return $row['children_count'] && ($entity->parent_id == 0 || in_array($entity->id(), $this->state_expanded));
	}
	
	
	public static function decode_value($value) {
		// a -> Array
		// n -> Number
		// d -> Date
		// b -> Boolean
		// s -> String
		// o -> Object
		// e -> Empty (null)

		$regexp = "/^(a|n|d|b|s|o|e)\:(.*)$/";
		preg_match($regexp, rawurldecode($value), $matches);
	
		if(empty($matches) || empty($matches[1])) {
		    return; // non state
		}
		$type = $matches[1];
		$value = $matches[2];
		switch ($type) {
		    case 'e':
		        return null;
		    case 'n':
		        return (float) $value;
		    case 'd':
		        return Time::DateTime($value);
		    case 'b':
		        return ($value == '1');
		    case 'a':
		        $all = array();
		        if ($value != '') {
		        	$split = explode('^', $value);
		        	foreach ($split as $val) {
		        		$all[] = self::decode_value($val);
		        	}
		        }
		        return $all;
		   case 'o':
		        $all = array();
		        if ($value != '') {
		        	$split = explode('^', $value);
		        	if (!empty($split)) {
		        		foreach ($split as $val) {
		        			$keyVal = explode('=', $val);
		        			$all[$keyVal[0]] = self::decode_value($keyVal[1]);
		        		}
		        	}
		        }
		        return $all;
		   default:
		        return $value;
		}
	}


	public static function encode_value($value) {
		$enc = '';
		if (is_null($value)) {
				return 'e:1';
		} else if(is_numeric($value)) {
				$enc = 'n:' . $value;
		} else if( is_bool($value)) {
				$enc = 'b:' . ($value ? '1' : '0');
		} else if($value instanceof Time_DateTime) {
				$enc = 'd:' . $value->format('%D, %d %M %Y %H:%M:%S %e');
		} else if(Core_Types::is_iterable($value)) {
				$is_array = true;
				foreach (array_keys($value) as $key) $is_array = is_numeric($key) && $is_array;
				if ($is_array) {
					$flat = array();
					foreach ($value as $i => $v) {
						  $flat[] = self::encode_value($v);
					}
					$enc = 'a:' . implode('^', $flat);
				}
				else {
					$flat = array();
					foreach ($value as $k => $v) {
						$flat[] = $k . '=' . self::encode_value($v);
					}
				$enc = 'o:' . implode('^', $flat);
				}
		} else {
				$enc = 's:' . $value;
		}
		return rawurlencode($enc);
	}
}
