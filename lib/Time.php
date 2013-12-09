<?php
/**
 * Работа с датами
 * 
 * В PHP нет стандартного способа для работы с датами. Присутствующий в PHP класс DateTime
 * получил значительную часть необходимой функциональности только в версии 5.3, а на момент
 * начала разработки фреймворка плохо подходил для реальной работы. Поэтому было принято
 * решение реализовать собственный класс для представления моментов времени и использовать
 * его во всех модулях библиотеки. В дальнейшем, возможно, произойдет слияние этого класса
 * со стандартным DateTime.
 * 
 * Момент времени, представляемый экземпляром класса Time.DateTime, который наследуется от
 * стандартного класса DateTime. Такой подход имеет свои плюсы и минусы, в дальнейшем,
 * возможно, это решение будет пересмотрено.
 * 
 * @todo надо бы сделать методы которые не меняют состояния класса типа как в Ruby method и method!
 * 
 * @author Timokhin <timokhin@techart.ru>
 * 
 * @package Time
 */

/** 
 * Класс модуля
 * 
 * Реализует набор фабричных методов для создания объектов класса Time.DateTime, 
 * и несколько вспомогательных методов.
 * 
 * @link http://www.php.net/manual/ru/datetime.formats.php
 * 
 * @version 0.3.0
 * 
 * @package Time
 */
class Time implements Core_ModuleInterface
{
	/** Имя модуля */
	const MODULE  = 'Time';
	
	/** Версия модуля */
	const VERSION = '0.3.0';

	/** @todo to date format */
	/** Y-m-d H:i:s */
	const FMT_DEFAULT  = 'Y-m-d H:i:s';
	
	/** d.m.Y H:i:s */
	const FMT_DMYHMS   = 'd.m.Y H:i:s';
	
	/** d.m.Y H:i */
	const FMT_DMYHM    = 'd.m.Y H:i';
	
	/** d.m.Y */
	const FMT_DMY      = 'd.m.Y';
	
	/** Y-m-d */
	const FMT_YMD      = 'Y-m-d';
	
	/** m.d.Y */
	const FMT_MDY      = 'm.d.Y';
	
	/** H:i:s */
	const FMT_HMS      = 'H:i:s';
	
	/** H:i */
	const FMT_HM       = 'H:i';
	
	/** D, d M Y H:i:s O */
	const FMT_RFC1123  = 'D, d M Y H:i:s O';
	
	/** Y-m-d\TH:i:sO */
	const FMT_ISO_8601 = "Y-m-d\TH:i:sO";


	/**
	* Регулярные выражения для автоматического определения формата даты
	*/
	protected static $format_patters = array(
		'{^\d+\.\d+\.\d+$}' => 'd.m.Y',
		'{^\d+\.\d+\.\d+([\s-]+)\d+:\d+$}' => 'd.m.Y${1}H:i',
		'{^\d+\.\d+\.\d+([\s-]+)\d+:\d+:\d+$}' => 'd.m.Y${1}H:i:s',
		'!^\d{1,4}-\d{1,2}-\d{1,2}([\s-]+)\d+:\d+:\d+$!' => 'Y-m-d H:i:s',
		'!^\d{1,4}-\d{1,2}-\d{1,2}$!' => 'Y-m-d',
		'!^\d{1,4}-\d{1,2}-\d{1,2}([\s-]+)\d+:\d+$!' => 'Y-m-d H:i',
		'!^\d{1,2}/\d{1,2}/\d{1,4}$!' => 'm/d/Y',
		'!^\d{1,2}/\d{1,2}/\d{1,4}([\s-]+)\d+:\d+:\d+$!' => 'm/d/Y H:i:s',
		'!^\d{1,2}/\d{1,2}/\d{1,4}([\s-]+)\d+:\d+$!' => 'm/d/Y H:i',
	);

	/**
	* Добавление автоопределяемого формата
	*/
	public static function add_format_pattern($regexp, $format)
	{
		self::$format_patters[$regexp] = $format;
	}

	/**
	* Автоматическое определение формата даты
	*/
	public static function detect_format($string)
	{
		foreach (self::$format_patters as $pattern => $replace) {
			$format = preg_replace($pattern, $replace, $string);
			if ($format != $string) {
				return $format;
			}
		}
		return '';
	}


	/**
	 * Создает объект класса Time_DateTime
	 * 
	 * Момент времени может быть задан различными способами:
	 * - в виде числа -- в этом случае число является значением UNIX timestamp;
	 * - в виде строки -- в этом случае делается попытка разбора строки и создания
	 * соответствущего объекта;
	 * - в виде объекта класса Time.DateTime -- в этом случае метод просто возвращает этот объект.
	 * 
	 * Парсинг строки выполняется с помощью метода Time.DateTime::parse() без указания 
	 * формата, что, в свою очередь, приводит к вызову встроенной функции {@link http://php.ru/manual/function.strtotime.html strtotime()}.
	 * 
	 * @see Time_DateTime::parse()
	 * 
	 * @params integer|string|object $timestamp Момент времени
	 * 
	 * @return Time_DateTime
	 */
	static public function DateTime($timestamp = null)
	{
		if (is_null($timestamp)) return new Time_DateTime();
		switch (true) {
			case $timestamp instanceof Time_DateTime:
				return $timestamp;
			case !is_object($timestamp) && (string) (int) $timestamp === (string) $timestamp:
				$date = new Time_DateTime("@$timestamp");
				$date->setTimezone(new DateTimeZone(date_default_timezone_get()));
				return $date;
			default:
				return Time_DateTime::parse((string) $timestamp);
		}
	}

	/**
	 * Создает объект класса Time_DateTime, соответствующий текущей дате
	 * 
	 * @return Time_DateTime
	 */
	static public function now()
	{
		return new Time_DateTime();
	}

	/**
	 * Возвращает количество секунд между двумя датами
	 * 
	 * @params Time_DateTime $from первая дата
	 * @params Time_DateTime $to вторая дата
	 * 
	 * @return integer
	 */
	static public function seconds_between(Time_DateTime $from, Time_DateTime $to)
	{
		return abs($from->timestamp - $to->timestamp);
	}

	/**
	 * Создает объект класса Time_DateTime по набору параметров
	 * 
	 * Набор параметров описывает момент времени. Псевдоним для  Time.DateTime::compose()
	 * 
	 * @see Time_DateTime::compose()
	 * 
	 * @params integer $year год
	 * @params integer $month месяц по умолчанию 1
	 * @params integer $day день по умолчанию 1
	 * @params integer $hour час по умолчанию 0
	 * @params integer $minute минуты по умолчанию 0
	 * @params integer $second секунды по умолчанию 0
	 * 
	 * @return Time_DateTime|false
	 */
	static public function compose($year, $month = 1, $day = 1, $hour = 0, $minute = 0, $second = 0)
	{
		return Time_DateTime::compose($year, $month, $day, $hour, $minute, $second);
	}

	/**
	 * Создает объект класса Time_DateTime на основании строкового представления даты
	 * 
	 * Псевдоним для метода Time_DateTime::parse().
	 * @see Time_DateTime::parse()
	 * 
	 * @params string $string строка, представляющая дату
	 * @params string $format строка формата по умолчанию пустая строка
	 * 
	 * @return Time_DateTime
	 */
	static public function parse($string, $format = '')
	{
		return Time_DateTime::parse($string, $format);
	}
}


/** 
 * Объектное представление дат
 * 
 * На данный момент информация о дате хранится в виде UNIX timestamp. В дальнейшем возможно 
 * изменение внутреннего формата хранения. Рекомендуется использовать фабричный метод модуля
 * Time::DateTime() для создания объектов класса.
 * 
 * @see Time::DateTime()
 * 
 * @package Time
 */
class Time_DateTime extends DateTime implements Core_PropertyAccessInterface, Core_EqualityInterface
{
	/**
	 * Переустанавливает текущее значение времени объекта DateTime в новое значение.
	 * 
	 * @link http://www.php.net/manual/ru/datetime.settime.php
	 * 
	 * @params integer $hour Час нового времени.
	 * @params integer $minute Минуты нового времени.
	 * @params integer $second Секунды нового времени.
	 * 
	 * @return self
	 */
	public function setTime()
	{
		$args = func_get_args();
		parent::setTime((int)$args[0], (int)$args[1], (int)$args[2]);
		return $this;
	}

	/**
	 * Переустанавливает текущее значение даты объекта DateTime в новое значение.
	 * 
	 * @link http://www.php.net/manual/ru/datetime.setdate.php
	 * 
	 * @params integer $year Год новой даты.
	 * @params integer $month Месяц новой даты.
	 * @params integer $day День новой даты.
	 * 
	 * @return self
	 */
	public function setDate()
	{
		$args = func_get_args();
		parent::setDate((int)$args[0], (int)$args[1], (int)$args[2]);
		return $this;
	}

	/**
	 * Создает объект класса Time_DateTime по набору параметров
	 * 
	 * Набор параметров описывает момент времени. Перед созданием 
	 * объекта проверяет корректность даты по григорианскому календарю {@link http://php.ru/manual/function.checkdate.html}
	 * 
	 * @params integer $year год
	 * @params integer $month месяц по умолчанию 1
	 * @params integer $day день по умолчанию 1
	 * @params integer $hour час по умолчанию 0
	 * @params integer $minute минуты по умолчанию 0
	 * @params integer $second секунды по умолчанию 0
	 * 
	 * @return Time_DateTime|false
	 */
	static public function compose($year, $month = 1, $day = 1, $hour = 0, $minute = 0, $second = 0)
	{
		if (!checkdate($month, $day, $year)) {
			return false;
		}
		$date = new Time_DateTime();
		$date->setDate($year, $month, $day);
		$date->setTime($hour, $minute, $second);
		return $date;
	}
	
	/**
	 * Создает объект класса Time_DateTime
	 * 
	 * Строка будет разобрана с учетом текущих настроек LC_TIME.
	 * 
	 * @link http://php.ru/manual/function.strptime.html
	 * @link http://php.ru/manual/function.strftime.html
	 * @link http://php.ru/manual/function.setlocale.html
	 * 
	 * @params string $string Строка для разбора и получения параметров для создания объекта
	 * @params string $format Формат строки $string 
	 * 
	 * @return null|Time_DateTime
	 */
	static public function parse_clib($string, $format)
	{
		$tm = strptime($string, $format);
		if (!$tm) {
			return null;
		}
		return self::compose(
			$tm['tm_year'] + 1900, $tm['tm_mon'] + 1, ($tm['tm_mday']) ? $tm['tm_mday'] : 1 ,
			$tm['tm_hour'], $tm['tm_min'], $tm['tm_sec']
		);
	}


	/**
	 * Создает объект класса Time.DateTime на основании строкового представления даты
	 * 
	 * Если параметр $clib имеет значение не $null или в строке формата находится символ %, 
	 * то строка разбирается функцией parse_clib (см. {@link http://php.ru/manual/function.strptime.html})
	 * Если параметр $clib имеет значение null, то строка разбирается функцией 
	 * DateTime::createFromFormat (см. {@link http://www.php.net/manual/ru/datetime.createfromformat.php})
	 * 
	 * @params string $string Строка для разбора
	 * @params string $format Строка формата по умолчанию пустая строка
	 * @params mixed $clib Флаг, показывающий, как разбирать строку по умолчанию null
	 * 
	 * @return null|Time_DateTime
	 */
	static public function parse($string, $format = '', $clib = null)
	{
		$rc = null;

		if (!$string) {
			return null;
		}

		if (empty($format)) {
			$format = Time::detect_format($string);
		}

		if ($format) {
			if (is_null($clib) && Core_Strings::contains($format, '%')) {
				$clib = true;
			}	
			if ($clib == true) {
				$rc = self::parse_clib($string, $format);
			} else {
				$rc = self::createFromFormat($format, $string);
			}
		} else {
			if ($res = self::strtotime($string)) {
				return $res;
			} else {
				$rc = self::parse_clib($string, '');
			}
		}
		if ($rc && !checkdate($rc->month, $rc->day, $rc->year)) {
			return null;
		}
		
		return $rc;
	}

	/**
	 * Обертка над strtotime
	 * @param  string $string Строка даты
	 * @return self|null         
	 */
	static public function strtotime($string)
	{
		$timestamp = strtotime($string);
		if ($timestamp > 0) {
			return Time::DateTime($timestamp);
		}
		return null;
	}

	/**
	 * Обертка над DateTime::createFromFormat
	 * 
	 * @link http://www.php.net/manual/ru/datetime.createfromformat.php
	 * 
	 * @params string $format Строка формата
	 * @params string $string Строка, представляющая дату(время)
	 * 
	 * @return Time_DateTime|false
	 */
	static public function createFromFormat($format, $string)
	{
		if (method_exists('DateTime', 'createFromFormat')) {
			if ($rc = parent::createFromFormat($format, $string)) {
				return self::compose($rc->format('Y'), $rc->format('m'), $rc->format('d'), $rc->format('H'), $rc->format('i'), $rc->format('s'));
			}
		} else {
			return self::strtotime($string);
		}
	}

	/**
	 * Выполняет проверку на равенство
	 * 
	 * Псевдоним для is_equal_to() 
	 * Проверяет на равенство дату, представляемую объектом, с датой, представляемой другим 
	 * объектом класса Time.DateTime.
	 * 
	 * @params Time_DateTime $to дата
	 * 
	 * @return boolean
	 */
	public function equals($to)
	{
		return ($to instanceof Time_DateTime) && $this->is_equal_to($to);
	}

	/**
	 * Проверяет дату объекта на нахождение в заданном интервале
	 * 
	 * @params Time_DateTime $from Начало интервала
	 * @params Time_DateTime $to Конец интервала
	 * 
	 * @return boolean
	 */
	public function between(Time_DateTime $from, Time_DateTime $to)
	{
		return $this->not_earlier_than($from) && $this->not_later_than($to);
	}

	/**
	 * Проверяет, предшествует ли дата объекта заданной дате
	 * 
	 * @params Time_DateTime $time
	 * 
	 * @return boolean
	 */
	public function earlier_than(Time_DateTime $time)
	{
		return $this < $time;
	}

	/**
	 * Проверяет, что дата объекта не предшествует заданной дате
	 * 
	 * @params Time_DateTime $time
	 * 
	 * @return boolean
	 */
	public function not_earlier_than(Time_DateTime $time)
	{
		return $this >= $time;
	}

	/**
	 * Проверяет, следует ли дата объекта за указанной датой
	 * 
	 * @params Time_DateTime $time
	 * 
	 * @return boolean
	 */
	public function later_than(Time_DateTime $time)
	{
		return $this > $time;
	}

	/**
	 * Проверяет, что дата объекта не следует за указанной датой
	 * 
	 * @params Time_DateTime $time
	 * 
	 * @return boolean
	 */
	public function not_later_than(Time_DateTime $time)
	{
		return $this <= $time;
	}

	/**
	 * Выполняет проверку дат на равенство с точностью до дня
	 * 
	 * В отличие от метода equals(), сравниваются только календарные даты и игнорируется 
	 * составляющая собственно времени (часы, минуты, секунды).
	 * 
	 * @params Time_DateTime $time
	 * 
	 * @return boolean
	 */
	public function same_date_as(Time_DateTime $time)
	{
		return $this->format('Y-m-d') == $time->format('Y-m-d');
	}

	/**
	 * Проверяет на равенство две даты
	 * 
	 * @params Time_DateTime $time
	 * 
	 * @return boolean
	 */
	public function is_equal_to(Time_DateTime $time) 
	{
		return $this == $time;
	}

	/**
	 * Возвращает смещение временной зоны
	 * 
	 * @return integer
	 */
	public function time_zone_offset()
	{
		return $this->getOffset();
	}

	/**
	 * Возвращает Timestamp
	 * 
	 * @return integer
	 */
	public function getTimestamp()
	{
		if (method_exists('DateTime', 'getTimestamp')) {
			return parent::getTimestamp();
		}
		return mktime($this->hour, $this->minute, $this->second, $this->month, $this->day, $this->year);
	}

	/**
	 * Устанавливает Timestamp
	 * 
	 * @params integer $unixtimestamp Метка времени Unix представляющая дату.
	 * 
	 * @return self
	 */
	public function setTimestamp($unixtimestamp )
	{
		if (method_exists('DateTime', 'setTimestamp')) {
			parent::setTimestamp($unixtimestamp);
			return $this;
		}
		$date = getdate((int) $unixtimestamp);
		if (!empty($date)) {
			$this->setDate($date['year'], $date['mon'], $date['mday']);
			$this->setTime($date['hours'], $date['minutes'], $date['seconds']);
		}
        return $this;
	}


	/**
	 * Устанавливает Timestamp
	 * 
	 * Параметры добавляются к соответствующим текущим значениям объекта
	 * 
	 * @params integer $seconds
	 * @params integer $minutes
	 * @params integer $hours
	 * @params integer $days
	 * @params integer $months
	 * @params integer $years
	 * 
	 * @return self
	 */
	public function add_by_timestap($seconds, $minutes = 0, $hours = 0, $days = 0, $months = 0, $years = 0)
	{
		$ts = mktime($this->hour + $hours, $this->minute + $minutes,
			$this->second + $seconds, $this->month + $months, $this->day + $days, $this->year + $years);
		return $this->setTimestamp($ts);
	}

	/**
	 * Смещает дату вперед на определенный интервал
	 * 
	 * @params integer|DateInterval|string $seconds
	 * @params integer $minutes
	 * @params integer $hours
	 * @params integer $days
	 * @params integer $months
	 * @params integer $years
	 * 
	 * @return self
	 */
	public function add($seconds, $minutes = 0, $hours = 0, $days = 0, $months = 0, $years = 0)
	{
		if (!class_exists('DateInterval')) {
			return $this->add_by_timestap($seconds, $minutes, $hours, $days, $months, $years);
		}
		if ($seconds instanceof DateInterval) {
			$interval = $seconds;
		} elseif (is_string($seconds)) {
			$interval = new DateInterval($seconds);
		} else {
			$interval = new DateInterval("P{$years}Y{$months}M{$days}DT{$hours}H{$minutes}M{$seconds}S");
		}
		parent::add($interval);
		return $this;
	}

	/**
	 * Смещает дату вперед на заданное число секунд
	 * 
	 * @params integer $interval 
	 * 
	 * @return self
	 */
	public function add_seconds($interval)
	{
		return $this->add($interval);
	}

	/**
	 * Смещает дату вперед на заданное число минут
	 * 
	 * @params integer $interval 
	 * 
	 * @return self
	 */
	public function add_minutes($interval)
	{
		return $this->add(0, $interval);
	}

	/**
	 * Смещает дату вперед на заданное число часов
	 * 
	 * @params integer $interval 
	 * 
	 * @return self
	 */
	public function add_hours($interval)
	{
		return $this->add(0, 0, $interval);
	}

	/**
	 * Смещает дату вперед на заданное число дней
	 * 
	 * @params integer $interval 
	 * 
	 * @return self
	 */
	public function add_days($interval)
	{
		return $this->add(0, 0, 0, $interval);
	}

	/**
	 * Смещает дату вперед на заданное число месяцев
	 * 
	 * @params integer $interval 
	 * 
	 * @return self
	 */
	public function add_months($interval)
	{
		return $this->add(0, 0, 0, 0, $interval);
	}

	/**
	 * Смещает дату вперед на заданное число лет
	 * 
	 * @params integer $interval 
	 * 
	 * @return self
	 */
	public function add_years($interval)
	{
		return $this->add(0, 0, 0, 0, 0, $interval);
	}

	/**
	 * Смещает дату назад на определенный интервал
	 * 
	 * @params integer|DateInterval|string $seconds
	 * @params integer $minutes
	 * @params integer $hours
	 * @params integer $days
	 * @params integer $months
	 * @params integer $years
	 * 
	 * @return self
	 */
	public function sub($seconds, $minutes = 0, $hours = 0, $days = 0, $months = 0, $years = 0)
	{
		if (!class_exists('DateInterval')) {
			return $this->add_by_timestap(-$seconds, -$minutes, -$hours, -$days, -$months, -$years);
		}
		if ($seconds instanceof DateInterval) {
			$interval = $seconds;
		} elseif (is_string($seconds)) {
			$interval = new DateInterval($seconds);
		} else {
			$interval = new DateInterval("P{$years}Y{$months}M{$days}DT{$hours}H{$minutes}M{$seconds}S");
		}
		parent::sub($interval);
		return $this;
	}

	/**
	 * Смещает дату назад на заданное количество секунд
	 * 
	 * @params integer $interval
	 * 
	 * @return self
	 */
	public function sub_seconds($interval)
	{
		return $this->sub($interval);
	}

	/**
	 * Смещает дату назад на заданное количество минут
	 * 
	 * @params integer $interval
	 * 
	 * @return self
	 */
	public function sub_minutes($interval)
	{
		return $this->sub(0, $interval);
	}
	/**
	 * Смещает дату назад на заданное количество часов
	 * 
	 * @params integer $interval
	 * 
	 * @return self
	 */
	public function sub_hours($interval)
	{
		return $this->sub(0, 0, $interval);
	}

	/**
	 * Смещает дату назад на заданное количество дней
	 * 
	 * @params integer $interval
	 * 
	 * @return self
	 */
	public function sub_days($interval)
	{
		return $this->sub(0, 0, 0, $interval);
	}

	/**
	 * Смещает дату назад на заданное количество месяцев
	 * 
	 * @params integer $interval
	 * 
	 * @return self
	 */
	public function sub_months($interval)
	{
		return $this->sub(0, 0, 0, 0, $interval);
	}

	/**
	 * Смещает дату назад на заданное количество лет
	 * 
	 * @params integer $interval
	 * 
	 * @return self
	 */
	public function sub_years($interval)
	{
		return $this->sub(0, 0, 0, 0, 0, $interval);
	}

	/**
	 * Возвращает значение свойства
	 * 
	 * @params string $property имя свойства
	 * 
	 * @throws Core_MissingPropertyException Несуществующее свойство
	 * 
	 * @return integer
	 */
	public function __get($property)
	{
		switch ($property) {
			case 'timestamp': case 'ts': return $this->getTimestamp();
			case 'year':      return (int) $this->format('Y');
			case 'month':     return (int) $this->format('n');
			case 'day':       return (int) $this->format('j');
			case 'hour':      return (int) $this->format('G');
			case 'minute':    return (int) $this->format('i');
			case 'second':    return (int) $this->format('s');
			case 'wday':      $w =(int) $this->format('w'); return $w == 0 ? 7 : $w;
			case 'yday':      return (int) $this->format('j');
			default:
				throw new Core_MissingPropertyException($property);
		}
	}

	/**
	 * Устанавливает значение свойства
	 * 
	 * На данный момент только значение свойства timestamp может быть установлено извне.
	 * 
	 * @params string $property имя свойства
	 * @params string $value значение
	 * 
	 * @throws Core_ReadOnlyPropertyException Если свойство существует и это не timestamp
	 * @throws Core_MissingPropertyException Если свойство не существует
	 * 
	 * @return self
	 */
	public function __set($property, $value)
	{
		switch ($property) {
			case 'timestamp':
				$this->setTimestamp((int) $value);
				return $this;
			case 'year':
			case 'month':
			case 'day':
			case 'hour':
			case 'minute':
			case 'second':
			case 'wday':
			case 'yday':
				throw new Core_ReadOnlyPropertyException($property);
			default:
				throw new Core_MissingPropertyException($property);
		}
	}

	/**
	 * Проверяет установку значения свойства
	 * 
	 * @params string $property имя свойства
	 * 
	 * @return boolean
	 */
	public function __isset($property)
	{
		switch ($property) {
			case 'timestamp':
			case 'year':
			case 'month':
			case 'day':
			case 'hour':
			case 'minute':
			case 'second':
			case 'wday':
			case 'yday':
				return true;
			default:
				return false;
		}
	}

	/**
	 * Удаляет свойство
	 * 
	 * @params string $property имя свойства
	 * 
	 * @throws Core_UndestroyablePropertyException для существующих свойств
	 * @throws Core_MissingPropertyException для не существующих свойств
	 */
	public function __unset($property)
	{
		switch ($property) {
			case 'timestamp':
			case 'year':
			case 'month':
			case 'day':
			case 'hour':
			case 'minute':
			case 'second':
			case 'wday':
			case 'yday':
				throw new Core_UndestroyablePropertyException($property);
			default:
				throw new Core_MissingPropertyException($property);
		}
	}
	
	/**
	 * Возвращает строку формата для parse_clib
	 * 
	 * @params string $format Строка формата по умолчанию Time::FMT_DEFAULT
	 * 
	 * @return string
	 */
	public function format_clib($format = Time::FMT_DEFAULT)
	{
		return strftime($format, $this->ts);
	}

	/**
	 * Преобразует дату в строку заданного формата
	 * 
	 * @params string $format Строка формата по умолчанию Time::FMT_DEFAULT
	 * @params null|boolean $clib Флаг, показывающий как форматировать строку
	 * 
	 * @return string
	 */
	public function format($format = Time::FMT_DEFAULT, $clib = null)
	{
		if (is_null($clib) && Core_Strings::contains($format, '%')) {
			$clib = true;
		}
		
		if ($clib) {
			return $this->format_clib($format);
		} else {
			return parent::format($format);
		}
	}

	/**
	 * Возвращает дату в виде строки в соответствии с локалью и падежом
	 * @param  string  $format  формат
	 * @param  string  $locale  локаль
	 * @param  integer $variant падеж
	 * @return string           
	 *
	 * @see  L10N
	 */
	public function format_l10n($format, $locale = 'ru', $variant = 0)
	{
		Core::load('L10N');
		L10N::locale($locale);
		return L10N::strftime($format, $this, $variant);
	}

	/**
	 * Преобразует дату в строку в формате RFC1123
	 * 
	 * Результат выполнения метода не зависит от выбранной локали.
	 * 
	 * @return string
	 */
	public function as_rfc1123()
	{
		return $this->format(self::RFC1123);
	}

	/**
	 * Возвращает строковое представление объекта
	 * 
	 * При формировании строкового представления используется формат по умолчанию.
	 * 
	 * @return string
	 */
	public function as_string()
	{
		return $this->format();
	}

	/**
	 * Возвращает строковое представление объекта
	 * 
	 * Псевдоним для as_string().
	 * 
	 * @return string
	 */
	public function __toString()
	{
		return $this->format();
	}

	/**
	 * Переводит дату из строкового представления в timestamp
	 * 
	 * В качестве параметра принимаются значения вида: "d.m.y", "d.m.y - G:i", "d.m.y - G:i:s". 
	 * Если передана некорректная строка, то будет возвращен ноль.
	 * 
	 * @params string $in дата
	 * 
	 * @return integer
	 */
	static public function s2date($in)
	{
		$date = Time_DateTime::parse($in, '!d.m.Y');
		if (!$date) {
			$date = Time_DateTime::parse($in, 'd.m.Y - G:i');
		}
		if (!$date) {
			$date = Time_DateTime::parse($in, 'd.m.Y - G:i:s');
		}
		return $date ? $date->ts : 0;
	}
	
	static public function datetime2timestamp($time, $format = '') {
		$res = self::s2date($time);
		if ($res > 0) {
			return $res;
		} else {
			$date = Time::parse($time, $format);
			return $date ? $date->ts : 0;
		}
	}
	
	/**
	 * Переводит дату из строкового представления в формат SQL DATE
	 * 
	 * В качестве параметра принимаются значения вида: "d.m.y" или "d.m.Y - G:i" или "d.m.Y - G:i:s". 
	 * Если передана некорректная строка, то будет возвращено "0000-00-00".
	 * 
	 * @params string $in
	 * 
	 * @return string
	 */
	static public function s2sqldate($in)
	{
		$in = trim($in);
		$date = Time::DateTime($in);
		
		return ($date) ? str_replace(' 00:00:00', '', $date->as_string()) : '0000-00-00';
		/*
		$res = '0000-00-00';
		if ($date) {
			$res = str_replace(' 00:00:00', '', $date->as_string());
		}
		return $res;
		*/
	}

	/**
	 * Форматирует SQL DATE/DATETIME в соответствии с переданным форматом
	 * 
	 * В формате допустимы только dmyYHGis
	 * 
	 * @params string $format Формат
	 * @params string|integer $time Дата/время
	 * 
	 * @return string|null
	 */
	static public function sqldateformat($format,$time)
	{
		$date = Time::DateTime($time);
		return ($date) ? $date->format($format) : null;
	}
	
	/**
	 * Суммирует дату/время с секундами
	 * 
	 * @params string $datetime
	 * @params integer $sec
	 * 
	 * @return string|false
	 */
	static function datetime_add($datetime,$sec)
	{
		$date = Time::DateTime($datetime);
		if (!$date) {
			return false;
		}
		$date->add($sec);
		return $date->as_string();
	}

}
