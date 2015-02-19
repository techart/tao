<?php
/**
 * Cache.Backend.APCU
 *
 * APCU (Alternative PHP Cache) кэширование
 *
 * @package Cache\Backend\APCU
 * @version 0.2.0
 */

/**
 * @package Cache\Backend\APCU
 */
class Cache_Backend_APCU implements Core_ModuleInterface
{

	/**
	 * Инициализация модуля
	 *
	 */
	static public function initialize()
	{
		if (!extension_loaded('apcu')) {
			throw new Cache_Exception('No apcu php-module');
		}
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
		return apcu_clear_cache($cache_type);
	}

	/**
	 * Фабричный метод, возвращает объект класса Cache.Backend.APC.Backend
	 *
	 * @param string $dsn
	 * @param int    $timeout
	 *
	 * @return Cache_Backend_APC_Backend
	 */
	public function Backend($dsn, $timeout = Cache_APCU::DEFAULT_TIMEOUT)
	{
		return new Cache_Backend_APCU_Backend($dsn, $timeout);
	}

}

/**
 * Класс реализующий APC кэширование
 *
 * @package Cache\Backend\APC
 */
class Cache_Backend_APCU_Backend extends Cache_Backend
{

	/**
	 * Конструктор
	 *
	 * @param string $dsn
	 * @param int    $timeout
	 */
	public function __construct($dsn, $timeout = Cache_Backend_APCU::DEFAULT_TIMEOUT)
	{
		if (!Core_Regexps::match('{^apcu://.*}', $dsn)) {
			throw new Cache_BadDSNException($dsn);
		}
		$this->timeout = $timeout;
	}

	public function get_all_keys()
	{
		$info = apcu_cache_info();
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
		$v = apcu_fetch($key);
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
		return apcu_store($key, serialize($value), $timeout);
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
		return apcu_delete($key);
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
		return (boolean)apcu_fetch($key);
	}

	/**
	 * Инвалидирует кэш
	 *
	 */
	public function flush()
	{
		Events::call('cache.flush');
		Events::call('cache.delete', $s = '*');
		$rc = apcu_clear_cache('user');
		$rc = apcu_clear_cache('opcode') && $rc;
		return $rc;
	}

}

