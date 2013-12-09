<?php
/**
 * @package CMS\Fields\Types\SQLDateStr
 */


class CMS_Fields_Types_SQLDateStr extends CMS_Fields_AbstractField implements Core_ModuleInterface {

	const VERSION = '0.0.0';


	public function view_value($value,$name,$data) {
		$value = parent::view_value($value,$name,$data);
		if (!isset($data['valid1970'])&&CMS::date('Ymd',$value)=='19700101') return '';
		$format = $this->get_format($name, $data);
		$value = CMS::date($format,$value);
		return $value;
	}

	public function get_format($name, $data) {
		if (isset($data['format'])) $format = $data['format'];
		if (empty($format)) {
			$format = 'd.m.Y';
			if (isset($data['with_time'])&&$data['with_time']) $format = 'd.m.Y - H:i';
			if (isset($data['with_seconds'])&&$data['with_seconds']) $format = 'd.m.Y - H:i:s';
		}
		return $format;
	}

	public function assign_from_object($form, $object, $name, $data) {
		if (isset($object[$name])) {
			if (is_object($object[$name])) {
				$value = $object[$name]->format($this->get_format($name, $data)); 
			} else{
				$value = $this->view_value((string) $object[$name], $name, $data);
			}
		} else {
			$value = $this->view_value((string) $object, $name, $data);
		}
		$form[$name] = $value;
	}


	public function assign_to_object($form,$object,$name,$data) {
		$object->$name = Time::parse($form[$name]);
	}
	
	public function sqltype() {
		return 'datetime';
	}

	protected function layout_preprocess($l, $name, $data)
	{
		if(isset($data['datepicker']) && $data['datepicker']) {
			$l->use_scripts(CMS::stdfile_url('scripts/jquery/ui.js'));
			$l->use_scripts(CMS::stdfile_url('scripts/fields/datepicker.js'));
			$this->use_lang_file($l, $data);
			$l->use_styles(CMS::stdfile_url('styles/jquery/ui.css'));
			$l->use_styles(CMS::stdfile_url('styles/jquery/datepicker.css'));
		}

		return parent::layout_preprocess($l, $name, $data);
	}

	protected function preprocess($t, $name, $data)
	{
		if(isset($data['datepicker']) && $data['datepicker']) {
			$data['tagparms']['class'] = "datepick dp-applied";
			$lang = $this->get_lang($data);

			$t->append_to('js',
				"$(function() {
					$('.datepick').each(function() {
						TAO.fields.datepicker($(this), '$lang');
					});
				});"
			);
		}

		return parent::preprocess($t, $name, $data);
	}


	protected function use_lang_file($l, $data)
	{
		$lang_file = $this->get_lang_file($data);
		if($lang_file)
			$l->use_scripts($lang_file);
	}

	protected function get_lang_file($data)
	{
		$lang_file = false;

		$lang_file = $data['lang_file'];
		$lang = $this->get_lang($data);
		$path = "jquery/lang/$lang.js";

		if($data['lang_file'])
			$lang_file = $data['lang_file'];
		elseif(IO_FS::exists('scripts/' . $path))
			$lang_file = $path;
		elseif(IO_FS::exists(CMS::stdfile('scripts/' . $path)))
			$lang_file = CMS::stdfile_url('scripts/' . $path);

		return $lang_file;
	}

	protected function get_lang($data)
	{
		return $data['lang'] ? $data['lang'] : CMS::site_lang();
	}
}


class CMS_Fields_Types_SQLDateStr_ValueContainer extends CMS_Fields_ValueContainer {

	public function render() {
		$data = $this->data;
		$value = parent::value();
		if (!isset($data['valid1970'])&&CMS::date('Ymd',$value)=='19700101') return '';
		if (isset($data['format'])) $format = $data['format'];
		if (empty($format)) {
			$format = 'd.m.Y';
			if (isset($data['with_time'])&&$data['with_time']) $format = 'd.m.Y - H:i';
			if (isset($data['with_seconds'])&&$data['with_seconds']) $format = 'd.m.Y - H:i:s';
		}
		$value = CMS::date($format,$value);
		return $value;
	}

}
