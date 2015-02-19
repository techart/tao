<?php
/**
 * @package CMS\Fields\Types\Content
 */

Core::load('CMS.Fields.Types.ImageList', 'Text.Process', 'CMS.Fields.Types.Textarea');

class CMS_Fields_Types_Content_ValueContainer extends CMS_Fields_Types_Textarea_ValueContainer
{

	protected function is_htmlpurifier()
	{
		return !isset($this->data['htmlpurifier']) || $this->data['htmlpurifier'];
	}

	protected function process_format($code, $value, $format)
	{
		if (empty($format['output'])) {
			return $value;
		}
		if (Core_Types::is_callable($format['output'])) {
			return Core::invoke($format['output'], array($value));
		}
		if (is_string($format['output']) || is_array($format['output'])) {
			Core::load('Text.Process');
			return Text_Process::process($value, $format['output']);
		}
		return $value;
	}

	public function value()
	{
		$value = (string)parent::value();
		if (empty($value)) {
			return '';
		}
		if ($this->forced_lang) {
			$value = CMS::lang($value, $this->forced_lang);
		} else {
			$value = CMS::lang($value);
		}
		$formats = $this->type->get_formats($this->name, $this->data);
		$fvalues = $this->type->format_split($value);
		if (!empty($fvalues)) {
			$value = '';
			foreach ($fvalues as $code => $fv) {
				if (in_array($code, array_keys($formats))) {
					$value .= $this->process_format($code, $fv, $formats[$code]);
				}
			}
		}
		return $value;
	}

	public function nl2br($value)
	{
		return $value;
	}
}

class CMS_Fields_Types_Content extends CMS_Fields_Types_ImageList implements Core_ModuleInterface
{
	const VERSION = '0.1.0';

	//TODO: рефакторинг
	protected $formats_cache = array();
	protected $default_switch_style = 'tabs';

	public function enable_multilang()
	{
		return true;
	}

	public function form_fields($form, $name, $data)
	{
		foreach ($this->get_formats($name, $data) as $code => $format) {
			if (!empty($format['__langs_name'])) {
				foreach ($format['__langs_name'] as $lang => $lname)
					$format['__type']->form_fields($form, $lname, $format['__data']);
			} else {
				$format['__type']->form_fields($form, $format['__name'], $format['__data']);
			}
		}
		if ($langs = $this->data_langs($data)) {
			foreach ($this->data_langs($data) as $lang => $ldata)
				$form->input($this->name_lang($name . '_format', $lang));
		} else {
			$form->input($name . '_format');
		}
		return parent::form_fields($form, $name, $data);
	}

	public function assign_from_object($form, $object, $name, $data)
	{
		$value = is_object($object) ? $object[$name] : $object;
		if ($langs = $this->data_langs($data)) {
			$lvalues = CMS::lang()->lang_split($value);
			foreach ($langs as $lang => $ldata) {
				$current_format_name = $this->name_lang($name . '_format', $lang);
				$fvalues = $this->format_split($lvalues[$lang]);
				foreach ($fvalues as $code => $fvalue) {
					$fname = $this->get_format_name($name, $code);
					$flname = $this->name_lang($fname, $lang);
					$form[$flname] = $fvalue;
					$form[$current_format_name] = $code;
				}
				if (empty($form[$current_format_name]) && !empty($current_format_name)) {
					$form[$current_format_name] = reset(array_keys($this->get_formats($name, $data)));
				}
			}
		} else {
			$fvalues = $this->format_split($value);
			$current_format_name = $name . '_format';
			if (!empty($fvalues)) {
				foreach ($fvalues as $code => $fval) {
					$form[$this->get_format_name($name, $code)] = $fval;
					$form[$current_format_name] = $code;
				}
			}
			$form[$name] = $value;
		}
		if (empty($form[$current_format_name]) && !empty($current_format_name)) {
			$form[$current_format_name] = reset(array_keys($this->get_formats($name, $data)));
		}

		//return parent::assign_from_object($form,$object,$name,$data);
	}

	public function format_split($s)
	{
		$formats = array();
		if ($m = Core_Regexps::match_with_results('/^(.*?)%FORMAT\{([a-z]+)\}(.*)%ENDFORMAT$/ism', $s)) {
			$formats[$m[2]] = $m[3];
		}
		return !empty($formats) ? $formats : array('html' => $s);
	}

	public function clear_value($value, $format = 'html')
	{
		if ($format == 'html') {
			return Text_Process::process($value, 'htmlpurifier');
		}
		return $value;
	}

	public function assign_to_object($form, $object, $name, $data)
	{
		$value = '';
		$formats = $this->get_formats($name, $data);
		if ($langs = $this->data_langs($data)) {
			foreach ($this->data_langs($data) as $lang => $ldata) {
				$format_lang_name = $this->name_lang($name . '_format', $lang);
				if (isset($form[$format_lang_name]) && ($fcode = $form[$format_lang_name]) &&
					in_array($fcode, array_keys($formats))
				) {
					$flname = $this->name_lang($this->get_format_name($name, $fcode), $lang);
					$fvalue = $form[$flname];
					$fvalue = $this->clear_value($fvalue, $fcode);
					if (!empty($fvalue)) {
						$value .= "%LANG{{$lang}}" . "%FORMAT{{$fcode}}" . $fvalue . '%ENDFORMAT';
					}
				}
			}
		} else {
			$format_name = $name . '_format';
			if (isset($form[$format_name]) && ($fcode = $form[$format_name]) &&
				in_array($fcode, array_keys($formats))
			) {
				$fname = $this->get_format_name($name, $fcode);
				$fvalue = $form[$fname];
				$fvalue = $this->clear_value($fvalue, $fcode);
				$value .= "%FORMAT{{$fcode}}" . $fvalue . '%ENDFORMAT';
			}
		}
		$object[$name] = $value;
	}

	protected function preprocess($template, $name, $data)
	{
		return parent::preprocess($template, $name, $data);
	}

	protected function layout_preprocess($l, $name, $data)
	{
		$formats = $this->get_formats($name, $data);
		foreach ($formats as $code => $format) {
			if (!empty($format['__langs_name'])) {
				foreach ($format['__langs_name'] as $lang => $lname)
					$format['__type']->layout_preprocess($l, $lname, $format['__data']);
			} else {
				$format['__type']->layout_preprocess($l, $format['__name'], $format['__data']);
			}
		}
		$l->update_parm('formats', $formats);
		$l->use_scripts(CMS::stdfile_url('scripts/fields/content.js'));
		$l->use_styles(CMS::stdfile_url('styles/fields/content.css'));

		$id = $this->url_class();
		$code = <<<JS
$(function() {
$(".{$id}.field-{$name}").each(function() {TAO.fields.content($(this));});
});
JS;
		$l->append_to('js', $code);
		$l->with('url_class', $id);

		return parent::layout_preprocess($l, $name, $data);
	}

	protected function stdunset($data)
	{
		$res = parent::stdunset($data);
		return $this->punset($res, 'formats', 'switch style', 'extra_formats', 'teaser split');
	}

	public function get_switch_style($name, $data)
	{
		return isset($data['switch style']) ? $data['switch style'] : $this->default_switch_style;
	}

	protected function get_format_name($name, $code)
	{
		return "{$name}_$code";
	}

	public function get_formats($name, $data)
	{
		$key = md5(serialize($name) . serialize(isset($data['formats']) ? $data['formats'] : null));
		if (isset($this->formats_cache[$key])) {
			return $this->formats_cache[$name];
		}
		$formats = isset($data['formats']) ? $data['formats'] : $this->default_formats($name, $data);
		$formats = array_merge($formats, isset($data['extra_formats']) ? $data['extra_formats'] : array());
		foreach ($formats as $code => $format) {
			$fdata = $formats[$code]['__data'] = CMS_Fields::validate_parms(isset($format['widget']) ? $format['widget'] : 'textarea');
			$formats[$code]['__data']['tagparms'] = array_merge(isset($formats[$code]['__data']['tagparms']) && is_array($formats[$code]['__data']['tagparms'])
					? $formats[$code]['__data']['tagparms']
					: array(),
				$this->tagparms($name, $data)
			);
			if ($code == 'html' && method_exists(CMS::$current_controller, 'field_action_url') && isset($data['__item'])) {
				$formats[$code]['__data']['imagelist'] = CMS::$current_controller->field_action_url($name, 'imagelist', $data['__item']);
			}
			$fname = $formats[$code]['__name'] = $this->get_format_name($name, $code);
			$ftype = $formats[$code]['__type'] = CMS_Fields::type($fdata);
			if ($langs = $this->data_langs($data)) {
				foreach ($langs as $lang => $ldata) {
					$formats[$code]['__langs_name'][$lang] = $this->name_lang($fname, $lang);
				}
			}
		}
		return $this->formats_cache[$name] = $formats;
	}

	protected function default_formats($name, $data)
	{
		return array(
			'html' => array('name' => 'HTML', 'widget' => 'html', 'output' => ''),
			'wiki' => array('name' => 'Wiki', 'widget' => 'wiki', 'output' => 'wiki'),
			'txt' => array('name' => 'Текст', 'widget' => 'textarea', 'output' => '')
		);
	}

}