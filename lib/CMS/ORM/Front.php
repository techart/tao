<?php
/**
 * @package CMS\ORM\Front
 */


Core::load('CMS.ORM');

class CMS_ORM_Front implements Core_ModuleInterface {
	const VERSION = '0.0.0';

	public static function fields($fields=array())
	{
		$out = array(
			'id' => array(
				'sqltype' => 'serial',
				'in_form' => false,
				'in_list' => false,
				'init_value' => 0,
			),
			'_created' => array(
				'sqltype' => 'tinyint index',
				'in_form' => false,
				'in_list' => false,
				'init_value' => 0,
			),
			'_created_time' => array(
				'sqltype' => 'datetime index',
				'in_form' => false,
				'in_list' => false,
				'init_value' => date('Y-m-d H:m:s'),
			),
			'user_id' => array(
				'sqltype' => 'int index',
				'in_form' => false,
				'in_list' => false,
				'init_value' => (int)WS::env()->auth->user->id,
			),
			'isactive' => array(
				'sqltype' => 'tinyint index',
				'type' => 'checkbox',
				'in_form' => true,
				'in_list' => false,
				'init_value' => 0,
			),
		);
		foreach($fields as $field => $data) {
			if (!isset($out[$field])) {
				$out[$field] = $data;
			} else {
				foreach($data as $key => $value) {
					$out[$field][$key] = $value;
				}
			}
		}
		return $out;
	}
}

class CMS_ORM_FrontMapper extends CMS_ORM_Mapper {

	protected function map_published_or_admin() {
		if ($this->user_is_admin()) {
			return $this;
		}
		if ($this->user_is_editor()) {
			return $this->where('isactive=1 OR user_id=:user_id',WS::env()->auth->user->id);
		}
		return $this->where('isactive=1');
	}

	public  function group_admins() {
		return 'admin';
	}

	public  function group_editors() {
		return 'admin';
	}

	public  function user_is_admin() {
		if (CMS::admin()) return true;
		if (!WS::env()->auth->user) {
			return false;
		}
		if (!WS::env()->auth->user) {
			return false;
		}
		$ga = $this->group_admins();
		if ($ga&&WS::env()->auth->user->check_access($ga)) {
			return true;
		}
		return false;
	}

	public  function user_is_editor($item=false) {
		if ($this->user_is_admin()) {
			return true;
		}
		if (!WS::env()->auth->user) {
			return false;
		}
		$ge = $this->group_editors();
		if ($ge&&WS::env()->auth->user->check_access($ge)) {
			return true;
		}
		return false;
	}

	public function user_can_view($item) {
		if (!$item->isactive) {
			return $this->user_can_edit($item);
		}
		return true;
	}

	public function user_can_edit($item) {
		if ($this->user_is_admin()) {
			return true;
		}
		if ($this->user_is_editor()&&WS::env()->auth->user->id==$item->user_id) {
			return true;
		}
		return false;
	}

	public function user_can_delete($item) {
		return $this->user_can_edit($item);
	}

}


class CMS_ORM_FrontEntity extends CMS_ORM_Entity {

	public function setup1() {
		parent::setup();
		$this->_created = 0;
		$this->_created_time = date('Y-m-d H:m:s');
		$this->isactive = 0;
		$this->user_id = (int)WS::env()->auth->user->id;
		if ($mapper = $this->mapper) {
			foreach($mapper->fields() as $field => $data) {
				$def = '';
				if (isset($data['default'])) {
					$def = $data['default'];
				}
				$this->{$field} = $def;
			}
		}
		return $this;
	}

	public function author_name() {
		return CMS::objects()->users->get_name($this->user_id);
	}

	public function author_url() {
		return CMS::objects()->users->get_url($this->user_id);
	}

	public function author_avatar($size='100x100') {
		return CMS::objects()->users->get_avatar($this->user_id,$size);
	}

}
