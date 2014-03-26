<?php
/**
 * @package CMS\Fields\Types\DateStr
 */


Core::load('Time');

class CMS_Fields_Types_DateStr extends CMS_Fields_AbstractField implements Core_ModuleInterface
{

	const VERSION = '0.0.0';

	static function format_date($value, $data)
	{
		if(!isset($data['valid1970']) && $value == 0) return '';
		if(isset($data['format'])) $format = $data['format'];
		if(empty($format)) {
			$format = 'd.m.Y';
			if(isset($data['with_time']) && $data['with_time']) $format = 'd.m.Y - H:i';
			if(isset($data['with_seconds']) && $data['with_seconds']) $format = 'd.m.Y - H:i:s';
		}
		return CMS::date($format, $value);
	}

	public function view_value($value, $name, $data)
	{
		$value = $value instanceof Time_DateTime ? $value->ts : parent::view_value($value, $name, $data);
		return self::format_date($value, $data);
	}

	public function assign_from_object($form, $object, $name, $data)
	{
		$value = is_object($object) ? $object[$name] : $object;
		$value = $this->view_value($value, $name, $data);
		$form[$name] = $value;
	}

	public function assign_to_object($form, $object, $name, $data)
	{
		$object->$name = CMS::s2date($form[$name]);
	}

	public function sqltype()
	{
		return 'int';
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

class CMS_Fields_Types_DateStr_ValueContainer extends CMS_Fields_ValueContainer
{

	public function render()
	{
		$data = $this->data;
		$value = parent::value();
		return CMS_Fields_Types_DateStr::format_date($value, $data);
	}
}
