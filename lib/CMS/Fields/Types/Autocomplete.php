<?php

/**
 * @package CMS\Fields\Types\Autocomplete
 */
class CMS_Fields_Types_Autocomplete extends CMS_Fields_AbstractField implements Core_ModuleInterface
{
	const VERSION = '0.0.0';

	protected $empty = array('id' => 0, 'title' => '---');
	protected $js_file = '';

	public function __construct()
	{
		$this->js_file = CMS::stdfile_url('scripts/fields/autocomplete.js');
	}

	public function view_value($value, $name, $data)
	{
		$value = parent::view_value($value, $name, $data);
		if (isset($data['load'])) {
			$obj = Core::invoke($data['load'], array($value));
			$value = (string)$obj;
		}
		return $value;
	}

	protected function stdunset($data)
	{
		$data = parent::stdunset($data);
		return $this->punset($data, 'search', 'load', 'empty', 'js_file');
	}

	protected function get_empty($data)
	{
		if (isset($data['empty'])) {
			if ($data['empty'] == false) {
				return false;
			}
			if (is_string($data['empty'])) {
				return array('id' => 0, 'title' => '---');
			}
			return $data['empty'];
		}
		return $this->empty;
	}

	protected function get_item_text($name, $data)
	{
		$value = $data['__item'][$name];
		$empty = $this->get_empty($data);
		if ($empty && $empty['id'] == $value) {
			return $empty['title'];
		}
		return Core::invoke($data['load'], array($data['__item'][$name]));
	}

	protected function get_js_file($data)
	{
		return isset($data['js_file']) ? (string)$data['js_file'] : $this->js_file;
	}

	protected function layout_preprocess($l, $name, $data)
	{
		$js_file = $this->get_js_file($data);
		$l->use_scripts(CMS::stdfile_url('scripts/jquery/ui.js'));
		$l->use_scripts($js_file);
		$l->use_style(CMS::stdfile_url('styles/jquery/ui.css'));
		return parent::layout_preprocess($l, $name, $data);
	}

	protected function preprocess($t, $name, $data)
	{
		$data['tagparms']['data-text'] = $this->get_item_text($name, $data);
		$data['tagparms']['data-href'] = $this->controller($name, $data)->field_action_url($name, 'items', $data['__item']);
		$data['tagparms']['data-type'] = 'autocomplete';
		$data['tagparms']['data-disable'] = (int)$js_file == $this->js_file;

		$t->append_to('js',
			"$( function() {
				$('input[data-type=autocomplete]:not(.field-process)').filter('[data-disable!=1]').each(function(input) {
					TAO.fields.autocomplete(this);
				});
			});"
		);

		parent::preprocess($t, $name, $data);
	}

	public function action($name, $data, $action, $item = false, $fields = array())
	{
		$args = func_get_args();
		$res = Net_HTTP::Response();
		$res->content_type('application/json');
		$query = WS::env()->request['query'];
		$ents = CMS::items_for_select(Core::invoke($data['search'], array($query)));
		$values = array();
		$empty = $this->get_empty($data);
		if ($empty && empty($query)) {
			$values[] = $empty;
		}
		foreach ($ents as $k => $e) {
			$values[] = array('id' => (int)$k, 'title' => (string)$e);
		}
		$res->body(json_encode(array('data' => $values)));
		return $res;
	}

}
