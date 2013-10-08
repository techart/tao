<?php

Core::load('Templates.HTML.Maps');

class CMS_Fields_Types_MapCoords extends CMS_Fields_AbstractField implements Core_ModuleInterface {

	const VERSION = '0.0.1';
	
	protected $schema = array();

	protected $default_options = array(
		'properties' => array(
			'zoom' => 3,
			'type' => 'roads',
			'style' => 'height: 180px; width: 180px; border: 1px solid black;',
		),
		'controls' => array(
			'scale' => 'small'
		),
	);

	public function view_value($value,$name,$data) {
		if (isset($data['sqltypes'])) {
			$fields_names = array_slice((array)$data['sqltypes'], 0, 3);
			switch (count($this->check_schema($data))) {
				case 0:
					$val_string = "";
					$view_value = parent::view_value($value,$name,$data);
					break;
				case 1:
					$val_string = $value[$fields_names[0]];
					$view_value = $this->floatize_string($val_string);
					break;
				case 2:
					$view_value = null;
					if (!is_null($value[$fields_names[0]])) {
						$val_string = $value[$fields_names[0]].';'.$value[$fields_names[1]];
						$view_value = $this->floatize_string($val_string);
					}
					break;
				case 3:
					$view_value = null;
					if (!is_null($value[$fields_names[0]])) {
						$val_string = $value[$fields_names[0]].';'.$value[$fields_names[1]].';'.$value[$fields_names[2]];
						$view_value = $this->floatize_string($val_string);
					}
					break;
			}
		}
		else {
			$view_value = parent::view_value($value,$name,$data);
		}
		return $view_value;
	}	

	public function check_schema($data) {
		if (isset($data['schema'])) {
			$func = create_function('$el', 'return $el["name"];');
			$this->schema = array_map($func, $data['schema']['columns']);
		}
		else {
			if (isset($data['sqltypes'])) {
				$this->schema = array_keys(array_slice((array)$data['sqltypes'], 0, 3));
				
			}
			else {
				$this->schema = array();
			}
		}
		return $this->schema;
	}

	public function use_zoom_check($data) {
		$use_zoom = false;
		$fields_count = count($this->check_schema($data));
		if (in_array($fields_count, array(0,1,3))) {
			$use_zoom = true;
		}
		return $use_zoom;
	}

	protected function stdunset($data) {
		$res = parent::stdunset($data);
		return $this->punset($res, 'sqltype', 'sqltypes', 'schema', 'format', 'service', 'options');
	}

	public function parse_field($value) {
		$coords = null;
		if (is_array($value)) {
			$value = array_values(array_slice($value,0,3));
			foreach ($value as $k => $field) {
				if ($field===null) {
					$value[$k] = $field;
				}
				else {
					if (is_string($field)) {
						if ($k==2) {
							$value[$k] = (int)$field;
						}
						else {
							$value[$k] = floatval($this->floatize_string($field));
						}
					}
				}
			}
			if (!is_null($value[0])) {
				$coords = array('lat' => $value[0], 'lng' => $value[1], 'zoom' => $value[2]);
			}
		}
		else {
			if (preg_match('/(.*);(.*);(.*)/',$value,$m)) {
				if ($m[1]!='') {
					$coords = array('lat' => floatval($this->floatize_string($m[1])), 'lng' => floatval($this->floatize_string($m[2])), 'zoom' => (int)$m[3]);
				}
			}
		}
		return $coords;
	}

	public function floatize_string($float_string){
		$locale_info = localeconv();
		$float_string = str_replace($locale_info["mon_thousands_sep"] , "", $float_string);
		$float_string = str_replace($locale_info["mon_decimal_point"] , ".", $float_string);
		return $float_string;
	}

	public function decimals_to_degrees($decimal) {
		$val = "";
		$sign = '';
		if ($decimal<0) {
			$decimal *= -1;
			$sign = '-';
		}

		$deg = floor($decimal);
		$min_val = ($decimal-$deg)*60;
		$min = floor($min_val);
		$sec = round(($min_val-$min)*60);

		$val .= $sign.$deg."° ";
		if ($min > 0) {
			$val .= $min."' ";
		}
		if ($sec > 0) {
			$val .= $sec."''";
		}
		return $val;
	}

	public function degrees_to_decimals($degree) {
		$degree = $this->floatize_string($degree);
		if ($degree=='') {
			$decimal = null;
		}
		else {
			if (is_numeric($degree)) {
				$decimal = floatval($degree);
			}
			else {
				$pattern = "/^(?:(-?\d+)°)*\s*(?:(\d+)')*\s*(?:(\d+)'')*$/";
				if (preg_match($pattern,trim($degree),$m)) {
			
					$deg = (int)$m[1];
					$min = (int)$m[2];
					$sec = (int)$m[3];

					$sign = 1;
					if ($deg<0) {
						$deg *= -1;
						$sign = -1;
					}

					$decimal = $sign*($deg + $min/60 + $sec/3600);
		
				}
				else {
					$decimal = 0;
				}
			}
		}
		return $decimal;
	}

	protected function js_injection($template, $field_name, $type, $url_class = false, $method_name = null) {
		$selector = '.field-'.$field_name;
		if ($url_class) {
			$selector = '.'.$url_class.$selector;
			$template->with('url_class', $url_class);
		}
		
		$call = $method_name ? "$type.$method_name" : $type;
		$code = "; $(function() { $('$selector').each(
					function() {
						TAO.fields.$call($(this)); 
						}
		)});";
		$template->append_to('js', $code);
	}
	
	protected function layout_preprocess($l, $name, $data) {
		$id = $this->url_class();
		$this->js_injection($l, $name, $data['type'], $id);

		$l->use_scripts(CMS::stdfile_url('scripts/fields/map-coords.js'));
		$l->use_styles(CMS::stdfile_url('styles/fields/map-coords.css'));
		return parent::layout_preprocess($l, $name, $data);
	}

	public function form_fields($form,$name,$data) {
		if ($langs = $this->data_langs($data)) {
			foreach($langs as $lang => $ldata) {
				$form->input($this->name_lang($name,$lang));
				$form->input($this->name_lang($name.'_add',$lang));
			}
		}
		else {
			$form->input($name);
			$form->input($name.'_add');
		}

		if ($this->use_zoom_check($data)) {
			$form->input($name.'_zoom');
		}
		return $form;
	}

	public function assign_from_object($form,$object,$name,$data) {
		switch (count($this->check_schema($data))) {
			case 0:
				$value = is_object($object) ? $object[$name] : $object;
				break;
			case 1:
				$value = is_object($object) ? $object[$this->schema[0]] : $object;
				break;
			case 2:
				$value = is_object($object) ? array($object[$this->schema[0]], $object[$this->schema[1]]) : $object;
				break;
			case 3:
				$value = is_object($object) ? array($object[$this->schema[0]], $object[$this->schema[1]], $object[$this->schema[2]]) : $object;
				break;
		}

		$coords = $this->parse_field($value);
		if (!is_null($coords)) {
			$form[$name] = $coords['lat'];
			$form[$name.'_add'] = $coords['lng'];
			if ($this->use_zoom_check($data)) {
				$form[$name.'_zoom'] = $coords['zoom'];
			}
			if (isset($data['format'])) {
				switch ($data['format']) {
					case 'degrees':
						$form[$name] = $this->decimals_to_degrees(floatval($this->floatize_string($form[$name])));
						$form[$name.'_add'] = $this->decimals_to_degrees(floatval($this->floatize_string($form[$name.'_add'])));
					break;
				}
			}
		}
	}

	public function assign_to_object($form,$object,$name,$data) {
		$lat = $form[$name];
		$lng = $form[$name.'_add'];
		if ($lat!=''&&$lng!='') {
			$lat = $this->degrees_to_decimals($lat);
			$lng = $this->degrees_to_decimals($lng);

			if ($this->use_zoom_check($data)) {
				$zoom = (int)$form[$name.'_zoom'];
			}

			switch (count($this->check_schema($data))) {
				case 0:
					$object[$name] = ($lat.';'.$lng.';'.$zoom);
					break;
				case 1:
					$object[$this->schema[0]] = ($lat.';'.$lng.';'.$zoom);
					break;
				case 2:
					$object[$this->schema[0]] = $lat;
					$object[$this->schema[1]] = $lng;
					break;
				case 3:
					$object[$this->schema[0]] = $lat;
					$object[$this->schema[1]] = $lng;
					$object[$this->schema[2]] = $zoom;
					break;
			}
		}
	}

	protected function preprocess($template, $name, $data) {
		$t = parent::preprocess($template, $name, $data);
		$options = Core_Arrays::deep_merge_update($this->default_options, (array)$data['options']);
		$format = 'decimal';
		if (isset($data['format'])) {
			$format = $data['format'];
		}
		
		switch (count($this->check_schema($data))) {
			case 0:
				$values = $this->parse_field($data['__item']->$name);
				if ($values['zoom']) {
					$options['properties']['zoom'] = $values['zoom'];
				}
				break;
			case 1:
				$field_name = $this->schema[0];
				$values = $this->parse_field($data['__item']->$field_name);
				if ($values['zoom']) {
					$options['properties']['zoom'] = $values['zoom'];
				}
				break;
			case 3:
				$zoom_name = $this->schema[2];
				$zoom = $data['__item']->$zoom_name;
				if (!is_null($zoom)) {
					$options['properties']['zoom'] = $zoom;
				}
				break;
		}

		$use_zoom = $this->use_zoom_check($data);
		$show_zoom = $use_zoom&&isset($data['show_zoom']);

		return $t->with(array(
			'service' => $data['service'],
			'options' => $options,
			'format' => $format,
			'use_zoom' => $use_zoom,
			'show_zoom' => $show_zoom
		));
	}
}

class CMS_Fields_Types_MapCoords_ValueContainer extends CMS_Fields_ValueContainer {

	protected $formats = array(
		'degrees_ru' => array(
			'lat' => array(	'с.ш', 'ю.ш' ),
			'lng' => array(	'в.д', 'з.д' ),
		), 
		'degrees_en' => array(
			'lat' => array(	'N', 'S' ),
			'lng' => array(	'E', 'W' ),
		), 
		'degrees' => array(
			'lat' => array(	'', '' ),
			'lng' => array(	'', '' ),
		), 
	);

	public function __construct($name,$data,$item,$type) {
		$this->name = $name;
		$this->data = $data;
		$this->item = $item;
		$this->type = $type;
		$this->schema = $this->type->check_schema($this->data);
	}

	public function value($part = null, $format = null) {
		switch (count($this->schema)) {
			case 0:
				$value = $this->item->{$this->name};
				break;
			case 1:
				$value = $this->item->{$this->schema[0]};
				break;
			case 2:
			case 3:
				$value = array($this->item->{$this->schema[0]}, $this->item->{$this->schema[1]});
				break;
			default: 
				$value = null;
				break;
		}
		if ($value = $this->type->parse_field($value)) {
			switch ($part) {
				case 'lat':
				case 'lng':
					$value = $this->format($value[$part], $part, $format);
					break;
				case 'latlng':
					$value['lat'] = $this->format($value['lat'], 'lat', $format);
					$value['lng'] = $this->format($value['lng'], 'lng', $format);
					break;
				default:
					$value['lat'] = $this->format($value['lat'], 'lat', $format);
					$value['lng'] = $this->format($value['lng'], 'lng', $format);
					$value = $value['lat']."; ".$value['lng'];
					break;
			}
		}
		return $value;
	}

	protected function format($value, $part, $format) {
		if (array_key_exists($format, $this->formats)) {
			if ($value >= 0) {
				$value = $this->type->decimals_to_degrees($value);
				$value = $value.' '.$this->formats[$format][$part][0];
			}
			else {
				$value = -$value;
				$value = $this->type->decimals_to_degrees($value);
				$value = $value.' '.$this->formats[$format][$part][1];
			}
		}
		return $value;
	}

	public function set($value) {
		$buffer = $this->value('latlng');
		if (is_array($value)) {
			if (isset($value['lat'])) {
				$buffer['lat'] = $this->type->degrees_to_decimals($value['lat']);
			}
			if (isset($value['lng'])) {
				$buffer['lng'] = $this->type->degrees_to_decimals($value['lng']);
			}
		}
		switch (count($this->schema)) {
			case 0:
				$this->item->{$this->name} = $buffer['lat'].';'.$buffer['lng'].';0';
				break;

			case 1:
				$this->item->{$this->schema[0]} = $buffer['lat'].';'.$buffer['lng'].';0';
				break;

			case 2:
			case 3:
				$this->item->{$this->schema[0]} = $buffer['lat'];
				$this->item->{$this->schema[1]} = $buffer['lng'];
				break;
		}
		return $this;
	}

	public function render($format = null, $template = '{lat}, {lng}') {
		switch ($format) {
			case 'degrees_en':
			case 'degrees_ru':
			case 'degrees':
			case 'decimal':
				$value = $this->value('latlng');
				if ($value) {
					if ($value['lat']>0) {
						$lat_suff = ' '.$this->formats[$format]['lat'][0];
					}
					else {
						$value['lat'] = -$value['lat'];
						$lat_suff = ' '.$this->formats[$format]['lat'][1];
					}
					if ($value['lng']>0) {
						$lng_suff = ' '.$this->formats[$format]['lng'][0];
					}
					else {
						$value['lng'] = -$value['lng'];
						$lng_suff = ' '.$this->formats[$format]['lng'][1];
					}
					if ($format=='decimal') {
						$lat = (string)$value['lat'];
						$lng = (string)$value['lng'];
					}
					else {
						$lat_degree = $this->type->decimals_to_degrees($value['lat']);
						$lng_degree = $this->type->decimals_to_degrees($value['lng']);
						$lat = $lat_degree.$lat_suff;
						$lng = $lng_degree.$lng_suff;
					}
					$value = $template;
					$value = preg_replace('/{lat}/', $lat, $value);
					$value = preg_replace('/{lng}/', $lng, $value);
					if ($value===null) {
						$value = $lat.', '.$lng;
					}
				}
				break;
			default:
				$value = $this->value();
				break;
		}
		if (is_null($value)) return '';
		if (!is_string($value)) return print_r($value,true);
		return $value;
	}

	public function value_to_url($format = 'degrees') {
		$view_value = $this->render($format);
		$url_value = $this->value('latlng');
		if ($url_value) {
			if ($url_value['lat']>0) {
				$lat_suff = 'N';
			}
			else {
				$url_value['lat'] = -$url_value['lat'];
				$lat_suff = 'S';
			}
			if ($url_value['lng']>0) {
				$lng_suff = 'E';
			}
			else {
				$url_value['lng'] = -$url_value['lng'];
				$lng_suff = 'W';
			}

			$lat = $this->type->floatize_string($url_value['lat']);
			$lng = $this->type->floatize_string($url_value['lng']);

			$link = $this->template();
			return $link->link_to('http://toolserver.org/~geohack/geohack.php?language=ru&params='.$lat.'_'.$lat_suff.'_'.$lng.'_'.$lng_suff.'_type:landmark_region:RU_scale:4000', $view_value);
		}
		return $view_value;
	}
}