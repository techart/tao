<?php
/**
 * L10N
 *
 * Модуль предназначен для локализации дат на русский и английский языки
 *
 * @package L10N
 * @version 0.2.1
 */
Core::load('Time');

/**
 * @package L10N
 */
class L10N implements Core_ModuleInterface
{

	const VERSION = '0.2.1';

	const FULL = 0;
	const ABBREVIATED = 1;
	const INFLECTED = 2;

	static protected $locale = null;

	/**
	 * Устанавливает локаль
	 *
	 * @param string $lang
	 *
	 * @return L10N_LocaleInterface
	 */
	static public function locale($lang = null)
	{
		if ($lang !== null) {
			Core::load($module = 'L10N.' . strtoupper($lang));
			self::$locale = Core::make("$module.Locale");
		}
		return self::$locale;
	}

	/**
	 * Возвращает отформатированную строку, подставляя дату в нужном формате
	 *
	 * @param string                   $format
	 * @param Time_DateTime|int|string $date
	 * @param int                      $variant
	 *
	 * @return string
	 */
	static public function strftime($format, $date, $variant = L10N::FULL)
	{
		return self::$locale->strftime($format, $date, $variant);
	}

	/**
	 * Вовращает имя месяца в соответствии с установленной локалью
	 *
	 * @param int $month
	 * @param int $variant
	 *
	 * @return string
	 */
	static public function month_name($month, $variant = L10N::FULL)
	{
		return self::$locale->month_name($month, $variant);
	}

	/**
	 * Вовращает день нелдели в соответствии с установленной локалью
	 *
	 * @param int $wday
	 * @param int $variant
	 *
	 * @return string
	 */
	static public function weekday_name($wday, $variant = L10N::FULL)
	{
		return self::$locale->weekday_name($wday, $variant);
	}

}

/**
 * Абстрактный класс локали
 *
 * @abstract
 * @package L10N
 */
abstract class L10N_Locale
{

	protected $data;

	/**
	 * Конструктор
	 *
	 * @param array $definition
	 */
	public function __construct($definition)
	{
		$this->data = $definition;
	}

	/**
	 * Возвращает отформатированную строку, подставляя дату в нужном формате
	 *
	 * @param string                   $format
	 * @param Time_DateTime|int|string $date
	 * @param int                      $variant
	 *
	 * @return string
	 */
	public function strftime($format, $date, $variant = L10N::FULL)
	{
		if (!$date = Time::DateTime($date)) {
			throw new Core_InvalidArgumentTypeException('date', $date);
		}

		return strftime(
			preg_replace(
				array('/%a/', '/%A/', '/%b/', '/%d([^%]*)%B/', '/%e([^%]*)%B/', '/%B/'),
				array(
					$this->variant(L10N::ABBREVIATED, 'weekdays', $date->wday),
					$this->variant(L10N::FULL, 'weekdays', $date->wday),
					$this->variant(L10N::ABBREVIATED, 'months', $date->month),
					sprintf('%02d\1%s', $date->day, $this->variant(L10N::INFLECTED, 'months', $date->month)),
					sprintf('%d\1%s', $date->day, $this->variant(L10N::INFLECTED, 'months', $date->month)),
					$this->variant($variant, 'months', $date->month)),
				$format
			),
			$date->timestamp
		);
	}

	/**
	 * Вовращает имя месяца
	 *
	 * @param int $month
	 * @param int $variant
	 *
	 * @return string
	 */
	public function month_name($month, $variant = L10N::FULL)
	{
		return $this->variant($variant, 'months', (int)$month);
	}

	/**
	 * Вовращает день нелдели
	 *
	 * @param int $wday
	 * @param int $variant
	 *
	 * @return string
	 */
	public function weekday_name($wday, $variant = L10N::FULL)
	{
		return $this->variant($variant, 'weekdays', (int)$wday);
	}

	/**
	 * Вовращает название месяца или дня недели
	 *
	 * @return string
	 */
	protected function variant($variant, $from, $index)
	{
		return $this->data[$from][isset($this->data[$from][$variant]) ? $variant : L10N::FULL][$index];
	}

}

