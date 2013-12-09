<?php
/**
 * @package CMS\Vars2\Admin\Mapper
 */


class CMS_Vars2_Admin_Mapper extends CMS_Mapper implements Core_ModuleInterface {
	const VERSION = '0.0.0';

	protected $controllers = array(
		'CMS.Vars2.Admin.Controller' => array(
			'path' => '{admin:vars}',
			'table-admin' => true,
		),
	);

	public function list_url() {
		return CMS::admin_path("vars");
	}

	public function edit_url($name) {
		if (trim($name)=='') {
			return '#';
		}
		return CMS::admin_path("vars/edit/id-{$name}/");
	}

	public function add_var_url($name) {
		return CMS::admin_path("vars/add/")."?chapter={$name}";
	}

	public function delete_url($name) {
		if (trim($name)=='') {
			return '#';
		}
		return CMS::admin_path("vars/delete/id-{$name}/");
	}

}
