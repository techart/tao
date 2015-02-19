<?php
/**
 * JSON
 *
 * @package JSON
 * @version 0.2.2
 */

Core::load('Time', 'Object');

/**
 * @package JSON
 */
class JSON implements Core_ModuleInterface
{

	const VERSION = '0.2.2';

	protected static $options = array('pretty_print' => false);

	public static function initialize($options = array())
	{
		self::$options = array_merge(self::$options, $options);
	}

	public static function options()
	{
		return self::$options;
	}

	public static function option($name)
	{
		return self::$options[$name];
	}

	/**
	 * @return JSON_Converter
	 */
	static public function Converter()
	{
		return new JSON_Converter();
	}

}

/**
 * @package JSON
 */
class JSON_AttributeConverter
{

	/**
	 * @param  $object
	 *
	 * @return boolean
	 */
	public function can_encode($object)
	{
		return $this->can('encode', $object);
	}

	/**
	 * @param  $object
	 *
	 * @return boolean
	 */
	public function can_decode($object)
	{
		return $this->can('decode', $object);
	}

	/**
	 * @param string $operation
	 * @param        $object
	 *
	 * @return boolean
	 */
	public function can($operation, $object)
	{
		return method_exists($this, $m = $operation . '_' . strtolower(Core_Types::real_class_name_for($object))) ? $m : false;
	}

}

/**
 * @package JSON
 */
class JSON_Converter
{

	protected $converters = array();

	/**
	 */
	public function __construct()
	{
		$this->setup();
	}

	/**
	 */
	protected function setup()
	{
	}

	/**
	 * @return JSON_Converter
	 */
	public function using(JSON_AttributeConverter $converter)
	{
		$this->converters[] = $converter;
		return $this;
	}

	/**
	 * @param Object_AttrListInterface $object
	 * @param                          $flavor
	 * @param                          $columns
	 *
	 * @return string
	 */
	public function from(Object_AttrListInterface $object, $flavor = null, $columns = array())
	{
		return $this->encode_object($object, $flavor, $columns);
	}

	/**
	 * @param  $items
	 * @param  $flavor
	 * @param  $columns
	 *
	 * @return string
	 */
	public function from_collection($items, $flavor = null, $columns = array())
	{
		$r = array();
		foreach ($items as $item)
			if ($item instanceof Object_AttrListInterface) {
				$r[] = $this->encode_object($item, $flavor, $columns);
			} elseif (is_array($item)) {
				$r[] = $this->encode_scalar($item, null);
			}
		return $r;
	}

	/**
	 * @param Object_AttrListInterface $object
	 * @param string                   $json
	 * @param                          $flavor
	 * @param                          $columns
	 *
	 * @return object
	 */
	public function to(Object_AttrListInterface $object, $json, $flavor = null, $columns = array())
	{
		return $this->decode_object(is_string($json) ? json_decode($json) : $json, $object, $flavor, $columns);
	}

	public static function pretty_print($json)
	{
		$result = '';
		$level = 0;
		$prev_char = '';
		$in_quotes = false;
		$ends_line_level = null;
		$json_length = strlen($json);

		for ($i = 0; $i < $json_length; $i++) {
			$char = $json[$i];
			$new_line_level = null;
			$post = "";
			if ($ends_line_level !== null) {
				$new_line_level = $ends_line_level;
				$ends_line_level = null;
			}
			if ($char === '"' && $prev_char != '\\') {
				$in_quotes = !$in_quotes;
			} else {
				if (!$in_quotes) {
					switch ($char) {
						case '}':
						case ']':
							$level--;
							$ends_line_level = null;
							$new_line_level = $level;
							break;

						case '{':
						case '[':
							$level++;
						case ',':
							$ends_line_level = $level;
							break;

						case ':':
							$post = " ";
							break;

						case " ":
						case "\t":
						case "\n":
						case "\r":
							$char = "";
							$ends_line_level = $new_line_level;
							$new_line_level = null;
							break;
					}
				}
			}
			if ($new_line_level !== null) {
				$result .= "\n" . str_repeat("\t", $new_line_level);
			}
			$result .= $char . $post;
			$prev_char = $char;
		}
		return $result;
	}

	/**
	 * @param array|stdObject $data
	 *
	 * @return object
	 */
	public function encode($data)
	{
		$json = json_encode($data);
		return JSON::option('pretty_print') ? $this->pretty_print($json) : $json;
	}

	/**
	 * @param Object_AttrListInterface $object
	 * @param                          $flavor
	 *
	 * @return object
	 */
	protected function encode_object(Object_AttrListInterface $object, $flavor = null, $columns = array())
	{
		if (method_exists($object, 'encode')) {
			$r = $object->encode($flavor);
			if (!is_null($r)) {
				return $r;
			}
		}
		$r = new stdClass();
		foreach ($object->__attrs($flavor) as $attr) {
			if (count($columns) > 0 && !in_array($attr->name, $columns)) {
				continue;
			}

			switch (true) {
				case $attr->is_object():
					$r->{$attr->name} = $object->{$attr->name} ? $this->encode_object($object->{$attr->name}, $flavor) : null;
					break;
				case $attr->is_collection():
					$r->{$attr->name} = $this->encode_collection($object, $attr, $flavor);
					break;
				case $attr->is_value():
					$r->{$attr->name} = $this->encode_value($object, $attr);
					break;
			}
		}
		return $r;
	}

	/**
	 * @param Object_AttrListInterface $object
	 * @param Object_Attribute         $attr
	 * @param                          $flavor
	 * @param                          $columns
	 *
	 * @return array
	 */
	protected function encode_collection(Object_AttrListInterface $object, Object_Attribute $attr, $flavor = null, $columns = array())
	{
		$items = array();

		foreach ($object->{$attr->name} as $item)
			if (is_object($item) && $item instanceof Object_AttrListInterface) {
				$items[] = $this->encode_object($item, $flavor, $columns);
			} else {
				$items[] = $this->encode_scalar($item, $attr);
			}

		return $items;
	}

	/**
	 * @param Object_AttrListInterface $object
	 * @param Object_Attribute         $attr
	 *
	 * @return mixed
	 */
	protected function encode_value(Object_AttrListInterface $object, Object_Attribute $attr)
	{
		$value = $object->{$attr->name};
		if (method_exists($object, 'before_encode_value')) {
			$value = $object->before_encode_value($attr, $value);
		}
		if (isset($attr->type)) {
			switch ($attr->type) {
				case 'string':
					return (string)$value;
				case 'int':
					return (int)$value;
				case 'float':
					return (float)$value;
				case 'boolean':
					return (boolean)$value;
				case 'datetime':
					return $this->encode_datetime($value, $attr);
				default:
					foreach ($this->converters as $c)
						if ($m = $c->can_encode($attr->type)) {
							return $c->$m($value, $attr);
						}
					return serialize($value);
			}
		} else {
			return $this->encode_scalar($value, $attrs);
		}
	}

	/**
	 * @param mixed            $value
	 * @param Object_Attribute $attrs
	 */
	protected function encode_scalar($value, $attr)
	{
		switch (true) {
			case is_string($value):
			case is_numeric($value):
			case is_bool($value):
				return $value;
			case $value instanceof Time_DateTime:
				return $this->encode_datetime($value);
			case is_object($value):
				foreach ($this->converters as $c)
					if ($m = $c->can_encode($value)) {
						return $c->$m($value, $attr);
					}
				return serialize($value);
			default:
				return $value;
		}
	}

	/**
	 * @param  $value
	 *
	 * @return Time_DateTime
	 */
	protected function encode_datetime($value, $attr = null)
	{
		return ($value = Time::DateTime($value)) ? $value->format(isset($attr->format) ? $attr->format : Time::FMT_ISO_8601) : null;
	}

	/**
	 * @param string $json
	 *
	 * @return object
	 */
	public function decode($json)
	{
		return json_decode($json);
	}

	/**
	 * @param object                   $json
	 * @param Object_AttrListInterface $object
	 * @param                          $flavor
	 * @param                          $columns
	 *
	 * @return object
	 */
	public function decode_object($json, Object_AttrListInterface $object, $flavor = null, $columns = array())
	{
		if (method_exists($object, 'decode')) {
			$r = $object->decode($json, $flavor);
			if (!is_null($r)) {
				return $r;
			}
		}
		foreach ($object->__attrs($flavor) as $attr) {
			if (count($columns) > 0 && !in_array($attr->name, $columns)) {
				continue;
			}

			if (isset($json->{$attr->name})) {
				switch (true) {
					case $attr->is_object() && is_object($json->{$attr->name}):
						$object->{$attr->name} = $this->decode_object($json->{$attr->name}, Core::make($attr->type));
						break;
					case $attr->is_collection() && is_array($json->{$attr->name}):
						$this->decode_collection($json, $object, $attr, $flavor);
						break;
					case $attr->is_value():
						$this->decode_value($json, $object, $attr);
						break;
				}
			}
		}
		return $object;
	}

	/**
	 * @param array                     $json
	 * @param Object_AttrsListInterface $object
	 * @param Object_Attribute          $attr
	 * @param                           $flavor
	 * @param                           $columns
	 *
	 * @return object
	 */
	protected function decode_collection($json, Object_AttrListInterface $object, Object_Attribute $attr, $flavor = null, $columns = array())
	{
		$operation = isset($attr->operation) ? $attr->operation : 'append';
		foreach ($json->{$attr->name} as $v) {
			if (Core_Types::is_subclass_of('Object_AttrListInterface', $attr->items)) {
				$item = $this->decode_object($v, Core::make($attr->items));
			} else {
				$item = $this->decode_scalar($v, $attr->items);
			}

			if (is_string($operation) && method_exists($object->{$attr->name}, $operation)) {
				$object->{$attr->name}->$operation($item);
			} else {
				if (is_array($operation)) {
					call_user_func($operation, $item);
				}
			}
		}
		return $object;
	}

	/**
	 * @param mixed  $value
	 * @param string $type
	 */
	protected function decode_scalar($value, $type)
	{
		if (!$type) {
			return $value;
		}
		switch ($type) {
			case 'datetime' :
				$value = $this->decode_datetime($value);
				break;
			default:
				settype($value, $type);
				break;
		}
		return $value;
	}

	/**
	 * @param                           $json
	 * @param Object_AttrsListInterface $object
	 * @param Object_Attribute          $attr
	 *
	 * @return object
	 */
	protected function decode_value($json, Object_AttrListInterface $object, Object_Attribute $attr)
	{
		$value = $json->{$attr->name};
		if (method_exists($object, 'before_decode_value')) {
			$value = $object->before_decode_value($attr, $value);
		}
		if (isset($attr->type)) {
			switch ($attr->type) {
				case 'string':
				case 'int':
				case 'float':
				case 'boolean':
				case 'datetime':
					$object->{$attr->name} = $this->decode_scalar($value, $attr->type);
					break;
				default:
					foreach ($this->converters as $c)
						if ($m = $c->can_decode($attr->type)) {
							break;
						}

					if ($m) {
						$object->{$attr->name} = $c->$m($value, $attr);
					} else {
						if (is_string($value) &&
							is_object($restored = unserialize($value)) &&
							($restored instanceof $attr->type)
						) {
							$object->{$attr->name} = $restored;
						}
					}
			}
		} else {
			$object->{$attr->name} = $value;
		}

		return $object;
	}

	/**
	 * @param  $value
	 *
	 * @return Time_DateTime
	 */
	protected function decode_datetime($value)
	{
		return Time::DateTime($value);
	}

}

