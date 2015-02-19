<?php
/**
 * Cache.Backend.APC
 *
 * APC (Alternative PHP Cache) кэширование
 *
 * @package Cache\Backend\APC
 * @version 0.2.0
 */

/**
 * @package Cache\Backend\APC
 */
class Cache_Backend_APC implements Core_ModuleInterface
{

	const VERSION = '0.2.0';
	const DEFAULT_TIMEOUT = 60;

	/**
	 * Инициализация модуля
	 *
	 */
	static public function initialize()
	{
		if (!extension_loaded('apc')) {
			throw new Cache_Exception('No apc php-module');
		}
	}

	/**
	 * Информация о кэше
	 *
	 * @param string $cache_type
	 * @param bool   $limited
	 *
	 * @return array
	 */
	static public function cache_info($cache_type = '', $limited = false)
	{
		return apc_cache_info($cache_type, $limited);
	}

	/**
	 * Очищает пользовательский/системный кэш
	 *
	 * @param string $cache_type
	 *
	 * @return bool
	 */
	static public function clear_cache($cache_type = null)
	{
		return apc_clear_cache($cache_type);
	}

	/**
	 * Сохраняет файл в двоичном кэше минуя все фильтры
	 *
	 * @param string $file_name
	 *
	 * @return bool
	 */
	static public function compile_file($file_name)
	{
		return apc_compile_file($file_name);
	}

	/**
	 * Сохраняет каталог в двоичном кэше
	 *
	 * @param string $dir_name
	 * @param bool   $recursively
	 *
	 * @return bool
	 */
	static public function compile_dir($dir_name, $recursively = true)
	{
		$compiled = true;

		if ($recursively) {
			foreach (glob($dir_name . DIRECTORY_SEPARATOR . '*', GLOB_ONLYDIR) as $dir)
				$compiled = $compiled && self::compile_dir($dir, $recursively);
		}

		foreach (glob($dir_name . DIRECTORY_SEPARATOR . '*.php') as $file)
			$compiled = $compiled && apc_compile_file($file);

		return $compiled;
	}

	/**
	 * Задает набор констант
	 *
	 * @param string $key
	 * @param array  $constants
	 *
	 * @return array
	 */
	static public function define_constants($key, array $constants, $case_sensitive = true)
	{
		return apc_define_constants($key, $constants, $case_sensitive);
	}

	/**
	 * Загружает набор констант из кэша
	 *
	 * @param string $key
	 * @param bool   $case_sensitive
	 *
	 * @return array
	 */
	static public function load_constants($key, $case_sensitive = true)
	{
		return apc_load_constants($key, $case_sensitive);
	}

	/**
	 * Информация о кэше
	 *
	 * @param bool $limited
	 *
	 * @return array
	 */
	static public function sma_info($limited = false)
	{
		return apc_sma_info($limited);
	}

	/**
	 * Фабричный метод, возвращает объект класса Cache.Backend.APC.Backend
	 *
	 * @param string $dsn
	 * @param int    $timeout
	 *
	 * @return Cache_Backend_APC_Backend
	 */
	public function Backend($dsn, $timeout = Cache_APC::DEFAULT_TIMEOUT)
	{
		return new Cache_Backend_APC_Backend($dsn, $timeout);
	}

}

/**
 * Класс реализующий APC кэширование
 *
 * @package Cache\Backend\APC
 */
class Cache_Backend_APC_Backend extends Cache_Backend
{

	/**
	 * Конструктор
	 *
	 * @param string $dsn
	 * @param int    $timeout
	 */
	public function __construct($dsn, $timeout = Cache_Backend_APC::DEFAULT_TIMEOUT)
	{
		if (!Core_Regexps::match('{^apc://.*}', $dsn)) {
			throw new Cache_BadDSNException($dsn);
		}
		$this->timeout = $timeout;
	}

	public function get_all_keys()
	{
		$info = apc_cache_info();
		$list = array();
		foreach ($info['cache_list'] as $cl) {
			$list[] = $cl['key'];
		}
		return $list;
	}

	/**
	 * Возвращает значение по ключу, если значение не установлено возвращает $default
	 *
	 * @param string $key
	 * @param        $default
	 *
	 * @return mixed
	 */
	public function get($key, $default = null)
	{
		$v = apc_fetch($key);
		$res = $v === false ? $default : unserialize($v);
		Events::call('cache.get', $key, $default, $res);
		return $res;
	}

	/**
	 * Устанавливает значение по ключу
	 *
	 * @param string $key
	 * @param        $value
	 * @param int    $timeout
	 *
	 * @return boolean
	 */
	public function set($key, $value, $timeout = null)
	{
		$timeout = Core::if_null($timeout, $this->timeout);
		Events::call('cache.set', $key, $value, $timeout);
		return apc_store($key, serialize($value), $timeout);
	}

	/**
	 * Удалят значение из кэша
	 *
	 * @param string $key
	 *
	 * @return boolean
	 */
	public function delete($key)
	{
		Events::call('cache.delete', $key);
		return apc_delete($key);
	}

	/**
	 * Проверяет есть ли занчение с ключом $key в кэше
	 *
	 * @param string $key
	 *
	 * @return boolean
	 */
	public function has($key)
	{
		Events::call('cache.has', $key);
		return (boolean)apc_fetch($key);
	}

	/**
	 * Инвалидирует кэш
	 *
	 */
	public function flush()
	{
		Events::call('cache.flush');
		Events::call('cache.delete', $s = '*');
		$rc = apc_clear_cache('user');
		$rc = apc_clear_cache('opcode') && $rc;
		return $rc;
	}

}

