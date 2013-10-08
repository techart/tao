<?php

class CMS_Fields_Types_FieldSet extends CMS_Fields_AbstractField implements Core_ModuleInterface {
	const VERSION = '0.0.0';

	public function sqltypes($name, $data) {
		if (!isset($data['fields'])) return false;
		$out = array();
		foreach($data['fields'] as $field => $fdata) {
			$type = CMS_Fields::type($fdata);
			if (isset($fdata['sqltype'])) {
				$out[$field] = $fdata['sqltype'];
			}

			else if ($sqltype = $type->sqltype($field,$fdata)) $out[$field] = $sqltype;

			else if (isset($fdata['sqltypes'])) {
				foreach($fdata['sqltypes'] as $_name => $sqltype) $out[$_name] = $sqltype;
			}

			else if ($sqltypes = $type->sqltypes($field,$fdata)) {
				foreach($sqltypes as $_name => $sqltype) $out[$_name] = $sqltype;
			}
		}

		if (count($out)>0) return $out;
		return false;
	}

	public function form_fields($form, $name, $data) {
		if (!isset($data['fields'])) return $form;
		foreach($data['fields'] as $field => $fdata) {
			$fdata = array_merge($data, $fdata);
			$type = CMS_Fields::type($fdata);
			$type->form_fields($form,$field,$fdata);
		}
		return $form;
	}

	public function assign_from_object($form,$object, $name, $data) {
		if (!isset($data['fields'])) return $this;
		foreach($data['fields'] as $field => $fdata) {
			$fdata = array_merge($data, $fdata);
			$type = CMS_Fields::type($fdata);
			$type->assign_from_object($form, $object, $field, $fdata);
		}

	}

	public function assign_to_object($form, $object, $name, $data) {
		if (!isset($data['fields'])) return $this;
		foreach($data['fields'] as $field => $fdata) {
			$fdata = array_merge($data, $fdata);
			$type = CMS_Fields::type($fdata);
			$type->assign_to_object($form, $object, $field, $fdata);
		}
	}

	public function form_validator($form, $name, $data) {
		if (!isset($data['fields'])) return $form;
		foreach($data['fields'] as $field => $fdata) {
			$fdata = array_merge($data, $fdata);
			$type = CMS_Fields::type($fdata);
			$type->form_validator($form,$field,$fdata);
		}
		return $form;
	}

	public function search_subfield($name, $data, $field) {
		if (isset($data['fields'][$field])) {
			return $data['fields'][$field];
		}
		return parent::search_subfield($name, $data, $field);
	}

	public function process_inserted($name, $data, $item) {
		foreach($data['fields'] as $field => $fdata) {
			$fdata = array_merge($data, $fdata);
			$type = CMS_Fields::type($fdata);
			$type->process_inserted($field, $fdata, $item);
		}
		return parent::process_inserted($name, $data,$item);
	}

	protected function stdunset($data) {
		$res = parent::stdunset($data);
		return $this->punset($res, 'fields');
	}


}
