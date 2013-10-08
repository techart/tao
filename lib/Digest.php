<?php
/**
 * Объектная обертка над php функциями md5, sha1, crypt, а так же работа с паролями.
 *
 * Классы Digest.MD5 и Digest.SHA1 являются оберткой над стандартными php функциями.
 *
 * Классы Digest_юPasswordCryptEncoder и Digest.PasswordMD5Encoder предназначены для кодирования и проверки паролей.
 *
 * @author Svistunov <svistunov@techart.ru>
 * 
 * @package Digest
 */



/**
 * Класс модуля
 *
 * <code>
 * $input = 'test';
 * var_dump($input, $hash = Digest::password($input), Digest::is_valid($input, $hash));
 * </code>
 * 
 * Настройки:
 * 
 * + password_encoder_class -- какой класс использовать для кодирования, по умолчанию Digest.PasswordMD5TwiceEncoder
 * + password_encoder_callback -- возможность указать callback для кодирования паролей, переопределит password_encoder_class
 * + password_salt -- соль, по умолчанию пустая строка
 * 
 * Параметры можно задать как для Core::configure модуля Digest так и в конфиге в секции security
 * 
 * Можно реализовать свой кодировщик и указать его в опции.
 * 
 * Также в Digest есть Digest.PasswordCryptEncoder, который желательно использовать на проектах для которых важна защита паролей.
 * Эта реализация использует функцию crypt.
 * По умолчанию соль пустая, поэтому она будет генериться при каждом запросе для использования алгоритма blowfish.
 * Для использования другого алгоритма или постоянной соли можно задать значение явно. Например
 * <code>
 *   ->begin_security
 *     ->password_salt('$2a$10$mIg2qF3gIpO5k6N5Zb91yg$')
 *     ->password_encoder_class('Digest.PasswordCryptEncoder')
 *   ->end
 * </code>
 * Настройки можно задавать и через Core::configure.
 *
 * @link http://php.net/manual/en/function.crypt.php
 * 
 * @package Digest
 */
class Digest implements Core_ModuleInterface
{

	/** Имя модуля */
	const MODULE	= 'Digest';
	/** Версия модуля */
	const VERSION = '0.3.0';


	/**
	 * Текущий кодировщик паролей
	 * @var Digest_PasswordEncoderInterface
	 */
	private static $encoder = null;

	/**
	 * Конфиг
	 * @var
	 */
	private static $config = null;

	/**
	 * Опции модуля
	 * @var array
	 */
	private static $options = array(
		'password_encoder_class' => 'Digest.PasswordMD5TwiceEncoder',//'Digest.PasswordCryptEncoder',
		'password_encoder_callback' => null,
		'password_salt' => ''
	);

	/**
	 * Инициализация модуля, установка опций
	 * @param  array  $options массив опций
	 */
	public static function initialize($options = array())
	{
		$config = (array) self::config()->security;
		self::options($config);
		self::options($options);
	}

	/**
	 * Доступ и установка конфига
	 * @todo : refactoring
	 */
	public function config($config = null)
	{
		if (!is_null($config)) self::$config = $config;
		if (is_null(self::$config)) {
			Core::load('WS');
			self::$config = WS::env()->config;
		}
		return self::$config;
	}

	/**
	 * Установка опций модуля
	 * @param  array  $options массив опций
	 */
	public static function options($options = array())
	{
		self::$options = array_merge(self::$options, $options);
	}

	/**
	 * Установка или чтение опции модуля
	 * @param  string $name  название опции
	 * @param  mixed $value значение опции
	 * @return mixed        значение опции
	 */
	public static function option($name, $value = null)
	{
		if (is_null($value)) {
			return self::$options[$name];
		} else {
			$old = self::$options[$name];
			self::$options[$name] = $value;
			return $old;
		}
	}

	/**
	 * Выполняет одноименную php функцию
	 * @param  string $string строка
	 * @param  string $salt   соль
	 * @return string         шифрованная строка
	 */
	public static function crypt($string, $salt = null)
	{
		return crypt($string, $salt);
	}

	/**
	 * Кодирует пароль
	 *
	 * Используется класс кодировщика установленный в опции password_encoder_class
	 * @param  string $str  пароль
	 * @param  string $salt соль
	 * @return string       закодированная строка
	 */
	public static function password($str, $salt = null)
	{
		return self::PasswordEncoder()->encode($str, $salt);
	}

	/**
	 * Проверяет правильность пароля
	 * @param  string  $str  пароль
	 * @param  string  $hash хеш
	 * @return boolean       
	 */
	public static function is_valid($str, $hash)
	{
		return self::PasswordEncoder()->is_valid($str, $hash);
	}

	/**
	 * Создает и сохраняет экземпляр класса кодировщика паролей
	 *
	 * Используется класс из опции password_encoder_class.
	 * В конструктор передается соль из опции password_salt
	 */
	public static function PasswordEncoder()
	{
		if (!is_null(self::$encoder)) return self::$encoder;
		$args = Core::normalize_args(func_get_args());
		$salt = self::option('password_salt');
		if (!empty($salt) && (!isset($args[0]) || empty($args[0]))) {
			$args[0] = $salt;
		}
		$class = self::option('password_encoder_class');
		if (!is_null(self::option('password_encoder_callback'))) {
			$class = 'Digest.PasswordCallbackEncoder';
			$salt = isset($args[0]) ? $args[0] : null;
			$args[0] = self::option('password_encoder_callback');
			$args[1] = $salt;
		}
		return self::$encoder = Core::amake($class, $args);
	}

}

/**
 * MD5 шифрование
 * @package Digest
 */
class Digest_MD5
{

/**
 * MD5 кодирование, возвращается бинарная строка из 16 символов
 * @param  string $string строка кодирования
 * @return string         закодированная строка
 */
	public static function digest($string) {
	 return md5($string, true);
	}

/**
 * MD5 кодирование, возвращается 32-значное шестнадцатеричное число
 * @param  string $string строка кодирования
 * @return string         закодированная строка
 */
	public static function hexdigest($string) {
		return md5($string, false);
	}
}

/**
 * SHA1 кодирование
 * @package Digest
 */
class Digest_SHA1 {

/**
 * SHA1 кодирование, возвращается бинарная строка из 16 символов
 * @param  string $string строка кодирования
 * @return string         закодированная строка
 */
	public static function digest($string)
	{
		return sha1($string, true);
	}

/**
 * SHA1 кодирование, 40-разрядное шестнадцатиричное число
 * @param  string $string строка кодирования
 * @return string         закодированная строка
 */
	public static function hexdigest($string)
	{
		return sha1($string, false);
	}
}

/**
 * Интерфейс кодировщика паролей
 * @package Digest
 */
interface Digest_PasswordEncoderInterface
{
	/**
	 * Кодирует строку пароля
	 * @param  string $str  пароль
	 * @param  string $salt соль
	 * @return string       закодированная строка
	 */
	public function encode($str, $salt = null);

	/**
	 * Проверяет соответствие пароля
	 * @param  string  $str  пароль
	 * @param  string  $hash хеш
	 * @param  string  $salt соль
	 * @return boolean      
	 */
	public function is_valid($str, $hash, $salt = null);

}

/**
 * Кодировщик паролей.
 *
 * Использует функцию сrypt
 *
 * @link http://php.net/manual/en/function.crypt.php
 * 
 * @package Digest
 */
class Digest_PasswordCryptEncoder implements Digest_PasswordEncoderInterface
{

	/**
	 * Соль
	 * @var string
	 */
	protected $salt;

	/**
	 * Вес
	 * @var integer
	 */
	protected $cost;

	/**
	 * Символы из которых генериться соль
	 * @var string
	 */
	private $chars = "./ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789";


	/**
	 * Конструктор
	 * @param string  $salt соль
	 * @param integer $cost вес
	 */
	public function __construct($salt = null, $cost = 10)
	{
		$this->salt = $salt;
		$this->cost = $cost;
		$this->generate_salt();
	}

	/**
	 * Устанавливает соль
	 * @param string $salt
	 */
	public function set_salt($salt)
	{
		$this->salt = $salt;
		$this->generate_salt();
		return $this;
	}

	/**
	 * Текущее значение соли
	 * @return string 
	 */
	public function get_salt() {
		return $this->salt;
	}

	/**
	 * Устанавливает вес
	 * @param integer $cost 
	 */
	public function set_cost($cost)
	{
		$this->cost = $cost;
		return $this;
	}

	/**
	 * Текущее значение веса
	 * @return integer 
	 */
	public function get_cost()
	{
		return $this->cost;
	}

	/**
	 * Кодирует строку пароля
	 * @param  string $str  пароль
	 * @param  string $salt соль
	 * @return string       закодированная строка
	 */
	public function encode($str, $salt = null)
	{
		$salt = is_null($salt) ? $this->salt : $salt;
		return crypt($str, $salt);
	}

	/**
	 * Проверяет соответствие пароля
	 * @param  string  $str  пароль
	 * @param  string  $hash хеш
	 * @param  string  $salt соль
	 * @return boolean      
	 */
	public function is_valid($str, $hash, $salt = null)
	{
		return $hash == crypt($str, $hash);
	}


	/**
	 * Генерит соль для метода blowfish, если это возможно
	 * 
	 * @todo Optimize
	 */
	protected function generate_salt()
	{
		if (!is_null($this->salt)) return $this->salt;
		if (CRYPT_BLOWFISH) { // generate blowfish salt
			$salt = '$2a$' . sprintf('%02d', $this->cost) . '$';
			$len = strlen($this->chars) - 1;
			for ($i = 0; $i < 22; $i++) {
				$salt .= substr($this->chars, mt_rand(0, $len), 1);
			}
			$this->salt = $salt;
		}
		return $this->salt;
	}

}

/**
 * Кодировщик паролей.
 *
 * Использует функцию md5
 * @package Digest
 */
class Digest_PasswordMD5Encoder implements Digest_PasswordEncoderInterface
{
	/**
	 * Соль
	 * @var string
	 */
	protected $salt;

	/**
	 * Конструктор
	 * @param string $salt соль
	 */
	public function __construct($salt = null)
	{
		$this->salt = $salt;
	}

	/**
	 * Устанавливает соль
	 * @param string $salt
	 */
	public function set_salt($salt)
	{
		$this->salt = $salt;
		return $this;
	}

	/**
	 * Текущее значение соли
	 * @return string
	 */
	public function get_salt() {
		return $this->salt;
	}

	/**
	 * Кодирует строку пароля
	 * @param  string $str  пароль
	 * @param  string $salt соль
	 * @return string       закодированная строка
	 */
	public function encode($str, $salt = null)
	{
		$salt = is_null($salt) ? $this->salt : $salt;
		return md5($str . $salt);
	}

	/**
	 * Проверяет соответствие пароля
	 * @param  string  $str  пароль
	 * @param  string  $hash хеш
	 * @param  string  $salt соль
	 * @return boolean      
	 */
	public function is_valid($str, $hash, $salt = null)
	{
		return $hash == $this->encode($str, $salt);
	}
}

/**
 * Кодировщик паролей.
 *
 * Использует callback
 * @package Digest
 */
class Digest_PasswordCallbackEncoder implements Digest_PasswordEncoderInterface
{
	/**
	 * Соль
	 * @var string
	 */
	protected $salt;

	/**
	 * Соль
	 * @var callback
	 */
	protected $callback;

	/**
	 * Конструктор
	 * @param string $salt соль
	 */
	public function __construct($callback, $salt = null)
	{
		$this->callback = $callback;
		$this->salt = $salt;
	}

	/**
	 * Кодирует строку пароля
	 * @param  string $str  пароль
	 * @param  string $salt соль
	 * @return string       закодированная строка
	 */
	public function encode($str, $salt = null)
	{
		$salt = is_null($salt) ? $this->salt : $salt;
		return Core::invoke($this->callback, array($str, $salt));
	}

	/**
	 * Проверяет соответствие пароля
	 * @param  string  $str  пароль
	 * @param  string  $hash хеш
	 * @param  string  $salt соль
	 * @return boolean      
	 */
	public function is_valid($str, $hash, $salt = null)
	{
		return $hash == $this->encode($str, $salt);
	}

}

/**
 * Кодировщик паролей.
 *
 * Использует md5 дважды (с солью по середине)
 * @package Digest
 */
class Digest_PasswordMD5TwiceEncoder extends Digest_PasswordMD5Encoder
{
	/**
	 * Кодирует строку пароля
	 * @param  string $str  пароль
	 * @param  string $salt соль
	 * @return string       закодированная строка
	 */
	public function encode($str, $salt = null)
	{
		$res = parent::encode($str, '');
		$res = parent::encode($res, is_null($salt) ? $this->salt : $salt);
		return $res;
	}
}