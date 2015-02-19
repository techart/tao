<?php
/**
 * Service.Google.Chart
 *
 * Модуль представляет интерфейс для сервиса Google Chart.
 * Для лучшего понимания происходящего следует ознакомиться с документацией Google Chart.
 *
 * <p>Для создания чартов в модуле есть фабричные методы, название которых соответствует типу
 * чарта. На вход подается обязательный параметр – размер чарта (строка формата
 * '[width]x[height]'). У всех чартов есть метод save_as('куда/сохранить'), который скачивает
 * результат и сохраняет по указанному пути; и метод as_url(), который возвращает сформированный
 * url. Этот url например можно передать в качестве значения аттрибута src тега img.</p>
 * <p>Передавать данные для построения чарта можно следующими способами. В метод data можно передать
 * либо массив значений, либо просто последовательность значений. Для добавления ещё одних данных
 * нужно соответственно ещё раз вызвать метод data или аналогичный. Следующий способ –
 * метод data_from, куда можно передать хэшь или массив (вообщем всё по чему можно сделать foreach).
 * Это удобно при работе с объектами бизнес логики или результатами выборки из БД. Последний
 * способ специфичен для чартов linexy и scatter. Это метод dataXY, на вход которому подается либо
 * ассоциативный массив либо два массива. В этом случае значения понимаются как пара (x,y), т.е.
 * однозначно определяет точку на графике. Все остальные методы предназначены для задания
 * дополнительных параметров таких как цвет, оси, подписи и т.д.</p>
 * <p>Для более подробной информации обращайтесь к описанию класса Graph и Axis, а так же к документации Google Chart.</p>
 * <p>В данный момент не реализованна заливка участов графика и фоновая заливка. Также нет поддержки чартов QR codes - типа</p>
 * <p>Несколько примеров использования:</p>
 * <code>
 * Service_Google_Chart::linexy('300x200')->dataXY(array(-10 => 10, 20 => 20, 30 => 30,40 => 40,50 => 50,60 => 60,70 => 10, 80 => 10, 90 => 10,100 => 10, 110 => 10,120 => 74,130 => 10,140 => 10,150 => 10,160 =>0))->
 * text()->
 * axis('x')->
 * auto_range(20)->
 * end->
 * axis('y')->
 * auto_range(20)->
 * end->
 * marker('c', 'FF0000', 0, -1, 5)->
 * marker('r', '00FF00', 0, 0.2, .7)->
 * grid(20, 20)->
 * title('Title')->
 * save_as('linxy.png');
 *
 * Service_Google_Chart::pie('300x120')->data(10,40,50)->labels('10','40','50')->
 * orientation(.7)->
 * save_as('pie.png');
 * // $c - DB_Connection
 * $data = $c->prepare(execute()->fetch_all();
 *
 * Service_Google_Chart::pie('700x300')->
 * title('Использование data_from')->
 * data_from($data,  'count')->
 * save_as('data_from.png');
 * </code>
 *
 * @package Service\Google\Chart
 * @version 0.1.0
 */

Core::load('Net.Agents.HTTP', 'IO.FS');

/**
 * @package Service\Google\Chart
 */
class Service_Google_Chart implements Core_ModuleInterface
{

	const VERSION = '0.1.0';
	const DEFAULT_SIZE = '200x125';
	const SERVER_URL = 'http://chart.apis.google.com/chart?';

	/**
	 * @param string $type
	 * @param string $size
	 *
	 * @return Service_Google_Chart
	 */
	static public function Graph($type, $size)
	{
		return new Service_Google_Chart_Graph($type, $size);
	}

	/**
	 * @param string $size
	 *
	 * @return Service_Google_Chart
	 */
	static public function line($size)
	{
		return self::Graph('lc', $size);
	}

	/**
	 * @param string $size
	 *
	 * @return Service_Google_Chart
	 */
	static public function linexy($size)
	{
		return self::Graph('lxy', $size);
	}

	/**
	 * @param string $size
	 *
	 * @return Service_Google_Chart
	 */
	static public function sparkline($size)
	{
		return self::Graph('ls', $size);
	}

	/**
	 * @param string $size
	 *
	 * @return Service_Google_Chart
	 */
	static public function bar_horizontal_group($size)
	{
		return self::Graph('bhg', $size);
	}

	/**
	 * @param string $size
	 *
	 * @return Service_Google_Chart
	 */
	static public function bar_horizontal_stacked($size)
	{
		return self::Graph('bhs', $size);
	}

	/**
	 * @param string $size
	 *
	 * @return Service_Google_Chart
	 */
	static public function bar_vertical_group($size)
	{
		return self::Graph('bvg', $size);
	}

	/**
	 * @param string $size
	 *
	 * @return Service_Google_Chart
	 */
	static public function bar_vertical_stacked($size)
	{
		return self::Graph('bvs', $size);
	}

	/**
	 * @param string $size
	 *
	 * @return Service_Google_Chart
	 */
	static public function pie($size)
	{
		return self::Graph('p', $size);
	}

	/**
	 * @param string $size
	 *
	 * @return Service_Google_Chart
	 */
	static public function pie_3d($size)
	{
		return self::Graph('p3', $size);
	}

	/**
	 * @param string $size
	 *
	 * @return Service_Google_Chart
	 */
	static public function pie_concentric($size)
	{
		return self::Graph('pc', $size);
	}

	/**
	 * @param string $size
	 *
	 * @return Service_Google_Chart
	 */
	static public function venn($size)
	{
		return self::Graph('v', $size);
	}

	/**
	 * @param string $size
	 *
	 * @return Service_Google_Chart
	 */
	static public function scatter($size)
	{
		return self::Graph('s', $size);
	}

	/**
	 * @param string $size
	 *
	 * @return Service_Google_Chart
	 */
	static public function radar($size)
	{
		return self::Graph('r', $size);
	}

	/**
	 * @param string $size
	 *
	 * @return Service_Google_Chart
	 */
	static public function map($size)
	{
		return self::Graph('t', $size);
	}

	/**
	 * @param string $size
	 *
	 * @return Service_Google_Chart
	 */
	static public function meter($size)
	{
		return self::Graph('gom', $size);
	}

}

/**
 * @package Service\Google\Chart
 */
class Service_Google_Chart_Exception extends Core_Exception
{
}

/**
 * @package Service\Google\Chart
 */
class Service_Google_Chart_UnsupportedMethodException extends Service_Google_Chart_Exception
{

	protected $type;
	protected $option;

	/**
	 * @param string $type
	 * @param string $option
	 */
	public function __construct($type, $option)
	{
		$this->type = (string)$type;
		$this->option = (string)$option;
		parent::__construct("Unsupported option '{$this->option}' for type {$this->type}");
	}

}

/**
 * @abstract
 * @package Service\Google\Chart
 */
abstract class Service_Google_Chart_DataEncoder implements Core_PropertyAccessInterface
{

	static protected $simple_chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
	static protected $extended_chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789-.';

	protected $prefix;
	protected $separator;

	/**
	 * @param array   $data
	 * @param numeric $min
	 * @param numeric $max
	 *
	 * @return string
	 */
	abstract public function encode_series(array $data, $min, $max);

	/**
	 * @param array  $data
	 * @param object $range
	 *
	 * @return string
	 */
	public function encode_all(array $data, $range)
	{
		$r = array();
		foreach ($data as $set) {
			if (Core_Types::is_array($set[0]) && Core_Types::is_array($set[1])) {
				$r[] = $this->encode_series($set[0], $range->left, $range->right);
				$r[] = $this->encode_series($set[1], $range->bottom, $range->top);
			} else {
				$r[] = $this->encode_series($set, $range->bottom, $range->top);
			}
		}
		return $this->prefix . implode($this->separator, $r);
	}

	/**
	 * @param string $property
	 *
	 * @return mixed
	 */
	public function __get($property)
	{
		switch ($property) {
			case 'prefix':
			case 'separator':
				return $this->$property;
			default:
				throw new Core_MissingPropertyException($property);
		}
	}

	/**
	 * @param string $property
	 * @param        $value
	 *
	 * @return mixed
	 */
	public function __set($property, $value)
	{
		throw new Core_ReadOnlyObjectException($this);
	}

	/**
	 * @param string $property
	 *
	 * @return boolean
	 */
	public function __isset($property)
	{
		switch ($property) {
			case 'prefix':
			case 'separator':
				return isset($this->$property);
			default:
				return false;
		}
	}

	/**
	 * @param string $property
	 */
	public function __unset($property)
	{
		throw new Core_ReadOnlyObjectException($this);
	}

}

/**
 * @package Service\Google\Chart
 */
class Service_Google_Chart_SimpleEncoder extends Service_Google_Chart_DataEncoder
{

	/**
	 * @param string $pref
	 * @param string $separetor
	 */
	public function __construct($pref = 's:', $separator = ',')
	{
		$this->prefix = (string)$pref;
		$this->separator = (string)$separator;
	}

	/**
	 * @param array   $series
	 * @param numeric $min
	 * @param numeric $max
	 *
	 * @return string
	 */
	public function encode_series(array $series, $min, $max)
	{
		$res = '';
		$max_value = 61;
		foreach ($series as $v)
			$res .= ($v === null ? '_' : self::$simple_chars[(int)(($v - $min) * $max_value / ($max - $min))]);
		return $res;
	}

}

/**
 * @package Service\Google\Chart
 */
class Service_Google_Chart_TextEncoder extends Service_Google_Chart_DataEncoder
{

	/**
	 * @param string $pref
	 * @param string $separetor
	 */
	public function __construct($pref = 't:', $separator = '|')
	{
		$this->prefix = (string)$pref;
		$this->separator = (string)$separator;
	}

	/**
	 * @param array   $series
	 * @param numeric $min
	 * @param numeric $max
	 *
	 * @return string
	 */
	public function encode_series(array $series, $min, $max)
	{
		$res = '';
		$max_value = 100;
		foreach ($series as $v)
			$res .= ($res > '' ? ',' : '') . ($v === null ? '-1' : round(($v - $min) * $max_value / ($max - $min), 1));
		return $res;
	}

}

/**
 * @package Service\Google\Chart
 */
class Service_Google_Chart_ExtendedEncoder extends Service_Google_Chart_DataEncoder
{

	/**
	 * @param string $pref
	 * @param string $separetor
	 */
	public function __construct($pref = 'e:', $separator = ',')
	{
		$this->prefix = (string)$pref;
		$this->separator = (string)$separator;
	}

	/**
	 * @param array   $series
	 * @param numeric $min
	 * @param numeric $max
	 *
	 * @return string
	 */
	public function encode_series(array $series, $min, $max)
	{
		$res = '';
		$max_value = 4095;
		$size_enc = strlen(self::$extended_chars);
		foreach ($series as $v) {
			if ($v == null) {
				$res .= '__';
			} else {
				$f = (int)floor((($v - $min) * $max_value / ($max - $min)) / $size_enc);
				$s = (($v - $min) * $max_value / ($max - $min)) % $size_enc;
				$res .= self::$extended_chars[$f] . self::$extended_chars[$s];
			}
		}
		return $res;
	}

}

/**
 * @package Service\Google\Chart
 */
class Service_Google_Chart_Graph implements Core_StringifyInterface, Core_PropertyAccessInterface, Core_IndexedAccessInterface
{

	protected $type;
	protected $data = array();
	protected $opts = array();
	protected $range;
	protected $encoder;

	/**
	 * @param string $type
	 * @param string $size
	 */
	public function __construct($type, $size)
	{
		$this->type = $type;
		$this->reset()->simple()->size($size);
	}

	/**
	 * @param  $index
	 *
	 * @return mixed
	 */
	public function offsetGet($index)
	{
		return $this->opts[$index];
	}

	/**
	 * @param  $index
	 * @param  $value
	 *
	 * @return mixed
	 */
	public function offsetSet($index, $value)
	{
		return $this->option($index, $value);
	}

	/**
	 * @param  $index
	 *
	 * @return boolean
	 */
	public function offsetExists($index)
	{
		return isset($this->opts[$index]);
	}

	/**
	 * @param  $index
	 */
	public function offsetUnset($index)
	{
		unset($this->opts[$index]);
		return $this;
	}

	/**
	 * @param string $property
	 *
	 * @return mixed
	 */
	public function __get($property)
	{
		switch ($property) {
			case 'type':
			case 'data':
			case 'opts':
			case 'range':
			case 'encoder':
				return $this->$property;
			case 'request':
				return Net_HTTP::Request(Service_Google_Chart::SERVER_URL)->parameters($this->as_array());
			default:
				throw new Core_MissingPropertyException($property);
		}
	}

	/**
	 * @param string $property
	 * @param        $value
	 *
	 * @return mixed
	 */
	public function __set($property, $value)
	{
		switch ($property) {
			case 'type':
			case 'data':
			case 'opts':
			case 'range':
			case 'encoder':
			case 'request':
				throw new Core_ReadOnlyObjectException($this);
			default:
				throw new Core_MissingPropertyException($property);
		}
	}

	/**
	 * @param string $property
	 *
	 * @return boolean
	 */
	public function __isset($property)
	{
		return isset($this->$property) || $property == 'request';
	}

	/**
	 * @param string $property
	 */
	public function __unset($property)
	{
		return $this->__set($property, null);
	}

	/**
	 * @return Service_Google_Chart_Graph
	 */
	public function simple()
	{
		$this->encoder = new Service_Google_Chart_SimpleEncoder();
		return $this;
	}

	/**
	 * @return Service_Google_Chart_Graph
	 */
	public function text()
	{
		$this->encoder = new Service_Google_Chart_TextEncoder();
		return $this;
	}

	/**
	 * @return Service_Google_Chart_Graph
	 */
	public function extended()
	{
		$this->encoder = new Service_Google_Chart_ExtendedEncoder();
		return $this;
	}

	/**
	 * @param Service_Google_Chart_DataEncoder $encoder
	 *
	 * @return Service_Google_Chart_Graph
	 */
	public function encoder(Service_Google_Chart_DataEncoder $encoder)
	{
		$this->encoder = $encoder;
		return $this;
	}

	/**
	 * @return Service_Google_Chart_Graph
	 */
	public function reset()
	{
		$this->data = array();
		$this->range = Core::object(array(
				'left' => null, 'right' => null, 'top' => null, 'bottom' => null)
		);
		return $this;
	}

	/**
	 * @return Service_Google_Chart_Graph
	 */
	public function data()
	{
		$items = Core::normalize_args(func_get_args());
		$this->correct_range($items)->data[] = $items;
		return $this;
	}

	/**
	 * @param         $source
	 * @param string  $field
	 * @param boolean $as_property
	 *
	 * @return Service_Google_Chart_Graph
	 */
	public function data_from($source, $field = null, $as_property = false)
	{
		return $this->data($this->from_iterator($source, $field, $as_property));
	}

	/**
	 * @param array $data_x
	 * @param array $data_y
	 *
	 * @return Service_Google_Chart_Graph
	 */
	public function data_xy(array $data_x, array $data_y = null)
	{
		if ($data_y == null) {
			$k = array_keys($data_x);
			$v = array_values($data_x);
		} else {
			$k = $data_x;
			$v = $data_y;
		}
		$this->
			restrict_to('lxy,s', 'dataXY')->
			correct_range($k, false)->
			correct_range($v)->
			data[] = array($k, $v);
		return $this;
	}

	/**
	 * @param         $data
	 * @param string  $field_y
	 * @param string  $field_x
	 * @param boolean $as_property
	 *
	 * @return Service_Google_Chart_Graph
	 */
	public function data_xy_from($data, $field_y = null, $field_x = null, $as_property = false)
	{
		$keys = array();
		$values = array();

		foreach ($data as $k => $v) {
			$keys[] = $field_x === null ? $k : ($as_property ? $v->$field_x : $v[$field_x]);
			$values[] = $field_y === null ? $v : ($as_property ? $v->$field_y : $v[$field_y]);
		}

		return $this->data_xy($keys, $values);
	}

	/**
	 * @param string $size
	 *
	 * @return Service_Google_Chart_Graph
	 */
	public function size($size)
	{
		return $this->option('chs', (string)$size);
	}

	/**
	 * @param string $title
	 *
	 * @return Service_Google_Chart_Graph
	 */
	public function title($title)
	{
		return $this->except_for('t', 'title')->option('chtt', $this->str_to_validurl($title));
	}

	/**
	 * @return Service_Google_Chart_Graph
	 */
	public function legend()
	{
		$args = func_get_args();
		return $this->option('chdl', Core::normalize_args($args));
	}

	public function legend_from($source, $field = null, $as_property = false)
	{
		return $this->legend($this->from_iterator($source, $field, $as_property));
	}

	/**
	 * @return Service_Google_Chart_Graph
	 */
	public function colors()
	{
		$args = func_get_args();
		return $this->option('chco', Core::normalize_args($args));
	}

	/**
	 * @param numeric $value
	 *
	 * @return Service_Google_Chart_Graph
	 */
	public function bottom($value)
	{
		$this->range->bottom = $value;
		return $this;
	}

	/**
	 * @param numeric $value
	 *
	 * @return Service_Google_Chart_Graph
	 */
	public function top($value)
	{
		$this->range->top = $value;
		return $this;
	}

	/**
	 * @param numeric $value
	 *
	 * @return Service_Google_Chart_Graph
	 */
	public function left($value)
	{
		$this->range->left = $value;
		return $this;
	}

	/**
	 * @param numeric $value
	 *
	 * @return Service_Google_Chart_Graph
	 */
	public function right($value)
	{
		$this->range->right = $value;
		return $this;
	}

	/**
	 * @param string $type
	 * @param int    $idx
	 *
	 * @return Service_Google_Chart_Axis
	 */
	public function axis($type, $idx = null)
	{
		$this->restrict_to('bhs,bvs,bhg,bvg,r,rs,s,lc,ls,lxy', 'axis');
		return new Service_Google_Chart_Axis(
			$this,
			$type,
			($idx === null) ?
				((isset($this->opts['chxt']) && is_array($this->opts['chxt'])) ?
					count($this->opts['chxt']) : 0) : $idx);
	}

	/**
	 * @return Service_Google_Chart_Axis
	 */
	public function labels()
	{
		$args = func_get_args();
		return $this->restrict_to('p,p3,pc,gom', 'labels')->option('chl', Core::normalize_args($args));
	}

	public function labels_from($source, $field = null, $as_property = false)
	{
		return $this->labels($this->from_iterator($source, $field, $as_property));
	}

	/**
	 * @return Service_Google_Chart_Axis
	 */
	public function marker()
	{
		$args = Core::normalize_args(func_get_args());
		return $this->restrict_to('bhs,bvs,bhg,bvg,r,rs,s,lc,ls,lxy', 'marker')->option('chm', $args,
			(isset($this->opts['chm']) && Core_Types::is_array($this->opts['chm']) ?
				count($this->opts['chm']) : 0)
		);
	}

	/**
	 * @return Service_Google_Chart_Axis
	 */
	public function grid()
	{
		$args = Core::normalize_args(func_get_args());
		return $this->restrict_to('bhs,bvs,bhg,bvg,r,rs,s,lc,ls,lxy', 'grid')->option('chg', $args);
	}

	/**
	 * @return Service_Google_Chart_Axis
	 */
	public function spacing()
	{
		$args = Core::normalize_args(func_get_args());
		return $this->restrict_to('bhs,bhg,bvs,bvg', 'spacing')->option('chbh', $args);
	}

	/**
	 * @param string $geo_area
	 *
	 * @return Service_Google_Chart_Axis
	 */
	public function area($geo_area = 'world')
	{
		return $this->restrict_to('t', 'area')->option('chtm', $geo_area);
	}

	/**
	 * @return Service_Google_Chart_Axis
	 */
	public function countries()
	{
		$args = Core::normalize_args(func_get_args());
		return $this->restrict_to('t', 'counttries')->option('chld', $args);
	}

	/**
	 * @return Service_Google_Chart_Axis
	 */
	public function zero_line()
	{
		$args = Core::normalize_args(func_get_args());
		return $this->restrict_to('bhs,bhg,bvs,bvg', 'zero_line')->option('chp', $args);
	}

	/**
	 * @param int $radians
	 *
	 * @return Service_Google_Chart_Axis
	 */
	public function orientation($radians)
	{
		return $this->restrict_to('p,p3,pc', 'orientation')->option('chp', $radians);
	}

	/**
	 * @return Service_Google_Chart_Axis
	 */
	public function sizes()
	{
		$args = Core::normalize_args(func_get_args());
		return $this->restrict_to('s', 'sizes')->option('_ss', $args);
	}

	/**
	 * @return Service_Google_Chart_Axis
	 */
	public function line_style($thickness, $line_segment, $blank_segment, $data_index = null)
	{
		return $this->restrict_to('lc,ls,lxy', 'line_style')->option('chls', "$thickness,$line_segment,$blank_segment",
			($data_index == null) ?
				((isset($this->opts['chls']) && Core_Types::is_array($this->opts['chls'])) ?
					count($this->opts['chls']) : 0)
				: $data_index
		);
	}

	/**
	 * @return array
	 */
	public function as_array()
	{
		$parms = array();

		foreach ($this->opts as $opt => $val) {
			switch ($opt) {
				case 'chs': // size
				case 'chtt': // title
				case 'chtm': // geo area for map
					$parms[$opt] = $val;
					break;
				case 'chld': // map countries
					$parms[$opt] = implode('', $val);
					break;
				case 'chp': //bar zero_lenes or pie orientatio
					$parms[$opt] = Core_Types::is_array($val) ? implode(',', array_map(array($this, 'str_to_validurl'), $val)) :
						$val;
					break;
				case 'chdl': // legend
				case 'chl': // pie chart labels or meter label or QR text encode
				case 'chls': // line style
					$parms[$opt] = implode('|', array_map(array($this, 'str_to_validurl'), $val));
					break;
				case 'chco': // colors
				case 'chxt': // axes types
				case 'chg': // grid lines
				case 'chbh': // bar spacing
					$parms[$opt] = implode(',', $val);
					break;
				case 'chm': // markers
					$m = array();
					foreach ($val as $v)
						$m[] = implode(',', $v);
					$parms[$opt] = implode('|', $m);
					break;
				case 'chxtc': // axes ticks
					$t = array();
					foreach ($val as $k => $v)
						$t[] = "$k,$v";
					$parms[$opt] = implode('|', $t);
					break;
				case 'chxl': // axes labels
					$t = array();
					foreach ($val as $k => $v)
						$t[] = "$k:|" . implode('|', array_map(array($this, 'str_to_validurl'), $v));
					$parms[$opt] = implode('|', $t);
					break;
				case 'chxp': // axes labels positions
				case 'chxs': // axes styles
				case 'chxr': // axes ranges
					$t = array();
					foreach ($val as $k => $v)
						$t[] = "$k," . implode(',', array_map(array($this, 'str_to_validurl'), $v));
					$parms[$opt] = implode('|', $t);
					break;
				case '_ss': // scatter sizes
					$scatter_sizes = $this->encoder->encode_series($val, 0, 100);
					break;
			}
		}

		if ($this->match_types('p,p3,pc,bhs,bvs,bhg,bvg,map,t,gom')) {
			$this->range->bottom = 0;
		}
		if ($this->match_types('gom')) {
			$this->range->top = 100;
		}

		$parms['cht'] = $this->type;
		$parms['chd'] = $this->encoder->encode_all($this->data, $this->range) .
			($scatter_sizes ? $this->encoder->separator . $scatter_sizes : '');

		return $parms;
	}

	/**
	 * @return string
	 */
	public function as_url()
	{
		return $this->request->url;
	}

	/**
	 * @param string $str
	 */
	protected function str_to_validurl($str)
	{
		return Core_Strings::replace(Core_Strings::replace($str, ' ', '+'), "\n", '|');
	}

	/**
	 * @param         $source
	 * @param string  $field
	 * @param boolean $as_property
	 *
	 * @return array
	 */
	protected function from_iterator($source, $field = null, $as_property = false)
	{
		$items = array();
		foreach ($source as $v)
			$items[] = ($field ? ($as_property ? $v->$field : $v[$field]) : $v);
		return $items;
	}

	/**
	 * @param array   $items
	 * @param boolean $is_y
	 *
	 * @return Service_Google_Chart_Base
	 */
	protected function correct_range(array $items, $is_y = true)
	{
		if ($is_y) {
			foreach ($items as $v) {
				if ($this->range->top === null || $this->range->top < $v) {
					$this->range->top = $v;
				}
				if ($this->range->bottom === null || $this->range->bottom > $v) {
					$this->range->bottom = $v;
				}
			}
		} else {
			foreach ($items as $v) {
				if ($this->range->left === null || $this->range->left > $v) {
					$this->range->left = $v;
				}
				if ($this->range->right === null || $this->range->right < $v) {
					$this->range->right = $v;
				}
			}
		}

		return $this;
	}

	/**
	 * @return object
	 */
	public function get_range()
	{
		return $this->range;
	}

	/**
	 * @param string $name
	 * @param        $value
	 * @param int    $idx
	 *
	 * @return Service_Google_Chart_Graph
	 */
	public function option($name, $value, $idx = null)
	{
		if ($idx === null) {
			$this->opts[$name] = $value;
		} else {
			$this->opts[$name][(int)$idx] = $value;
		}
		return $this;
	}

	/**
	 * @param string $type
	 * @param string $option
	 *
	 * @return Service_Google_Chart_Graph
	 */
	protected function restrict_to($types, $option)
	{
		if (!$this->match_types($types)) {
			throw new Service_Google_Chart_UnsupportedMethodException($this->type, $option);
		}
		return $this;
	}

	/**
	 * @param string $type
	 * @param string $option
	 *
	 * @return Service_Google_Chart_Graph
	 */
	protected function except_for($types, $option)
	{
		if ($this->match_types($types)) {
			throw new Service_Google_Chart_UnsupportedMethodException($this->type, $option);
		}
		return $this;
	}

	/**
	 * @param  $types
	 */
	protected function match_types($types)
	{
		return Core_Regexps::match("!(^{$this->type})|({$this->type}\$)|([,]{$this->type}[,])!i", $types);
	}

	/**
	 * @param string $path
	 *
	 * @return boolean
	 */
	public function save_as($path)
	{
		return IO_FS::File($path)->update(Net_HTTP::Agent()->send($this->request->method(Net_HTTP::POST))->body);
	}

	/**
	 * @return string
	 */
	public function as_string()
	{
		return $this->as_url();
	}

	/**
	 * @return string
	 */
	public function __toString()
	{
		return $this->as_string();
	}

}

/**
 * @package Service\Google\Chart
 */
class Service_Google_Chart_Axis
{
	protected $graph;
	protected $idx;
	protected $type;

	/**
	 * @param Service_Google_Chart_Graph $graph
	 * @param string                     $type
	 * @param int                        $idx
	 */
	public function __construct(Service_Google_Chart_Graph $graph, $type, $idx)
	{
		$this->idx = $idx;
		$this->graph = $graph;
		$this->type = $type;
		$this->option('chxt', $type);
	}

	/**
	 * @param int $width
	 *
	 * @return Service_Google_Chart_Axis
	 */
	public function tick($width)
	{
		return $this->option('chxtc', (int)$width);
	}

	/**
	 * @return Service_Google_Chart_Axis
	 */
	public function plabels()
	{
		$args = func_get_args();
		$args = Core::normalize_args($args);
		return $this->labels(array_values($args))->positions(array_keys($args));
	}

	/**
	 * @return Service_Google_Chart_Axis
	 */
	public function labels()
	{
		$args = func_get_args();
		return $this->option('chxl', Core::normalize_args($args));
	}

	/**
	 * @return Service_Google_Chart_Axis
	 */
	public function positions()
	{
		$args = func_get_args();
		return $this->option('chxp', Core::normalize_args($args));
	}

	/**
	 * @return Service_Google_Chart_Axis
	 */
	public function style()
	{
		$args = func_get_args();
		return $this->option('chxs', Core::normalize_args($args));
	}

	/**
	 * @return Service_Google_Chart_Axis
	 */
	public function range()
	{
		$args = func_get_args();
		return $this->option('chxr', Core::normalize_args($args));
	}

	/**
	 * @param int $step
	 *
	 * @return Service_Google_Chart_Axis
	 */
	public function auto_range($step = 0)
	{
		$r = $this->graph->get_range();
		switch ($this->type) {
			case 'x':
			case 't':
				return $this->range($r->left, $r->right, $step);
			case 'y':
			case 'r':
				return $this->range($r->bottom, $r->top, $step);
		}
	}

	/**
	 * @param string $property
	 *
	 * @return Service_Google_Chart_Graph|null
	 */
	public function __get($property)
	{
		return $property == 'end' ? $this->graph : null;
	}

	/**
	 * @param string $name
	 * @param        $value
	 *
	 * @return Service_Google_Chart_Axis
	 */
	protected function option($name, $value)
	{
		$this->graph->option($name, $value, $this->idx);
		return $this;
	}

}

