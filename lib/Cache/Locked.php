<?php

Core::load('Cache');

class Cache_Locked implements Core_ModuleInterface
{

	public static function Client($backend)
	{
		return new Cache_Locked_Client($backend);
	}

}

class Cache_Locked_Client implements Core_CallInterface
{
	protected $backend;

	protected $locked_timeout = 10;
	protected $wait_delta = 30000;

	public function __construct($backend)
	{
		$this->backend = is_object($backend) ? $backend : Cache::connect((string)$backend);
	}

	protected function lock_key($key)
	{
		return "lock:$key";
	}

	public function set_lock($key)
	{
		// var_dump('set_lock!!', $key);
		$this->backend->set($this->lock_key($key), getmypid(), $this->locked_timeout);
		return $this;
	}

	public function get_lock($key)
	{
		return $this->backend->get($this->lock_key($key));
	}

	public function is_lock($key)
	{
		return $this->backend->has($this->lock_key($key));
	}

	public function remove_lock($key)
	{
		if ($this->is_lock($key)) {
			// var_dump('remove_lock!!', $key);
			$this->backend->delete($this->lock_key($key));
		}
		return $this;
	}

	public function get($key, $default = null)
	{
		$lock = $this->get_lock($key);
		$value = $this->backend->get($key, $default);
		if (!$lock && $value === $default) {
			$this->set_lock($key);
		}
		if ($lock) {
			$value = $this->wait($key, $lock, $value, $default);
		}
		return $value;
	}

	public function set($key, $value, $timeout = null)
	{
		$rc = $this->backend->set($key, $value, $timeout);
		$this->remove_lock($key);
		return $rc;
	}

	protected function disable_array_cache()
	{
		if (method_exists($this->backend, 'use_array_cache')) {
			$this->backend->use_array_cache(false);
		}
	}

	protected function enable_array_cache()
	{
		if (method_exists($this->backend, 'use_array_cache')) {
			$this->backend->use_array_cache(true);
		}
	}

	protected function wait($key, $lock, $value, $default = null)
	{
		if ($lock != getmypid() && $value === $default) {
			$start = time();
			$i = 0;
			$this->disable_array_cache();
			while ((time() - $start) < $this->locked_timeout) {
				$i++;
				// var_dump("$i: wait: {$this->is_lock($key)}");
				usleep($this->wait_delta);
				if (!$this->is_lock($key)) {
					$this->enable_array_cache();
					return $this->backend->get($key);
				}
			}
			$this->enable_array_cache();
			return $this->backend->get($key);
		}
		return $value;
	}

	public function __call($method, $args)
	{
		return call_user_func_array(array($this->backend, $method), $args);
	}
}