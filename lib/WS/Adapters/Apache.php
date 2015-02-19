<?php
/**
 * WS.Adapters.Apache
 *
 * @package WS\Adapters\Apache
 * @version 0.2.0
 */

Core::load('WS', 'Net.HTTP');

/**
 * @package WS\Adapters\Apache
 */
class WS_Adapters_Apache implements Core_ModuleInterface
{
	const VERSION = '0.2.1';

	/**
	 * @return WS_Adapters_Apache_Adapter
	 */
	static public function Adapter()
	{
		return new WS_Adapters_Apache_Adapter();
	}
}

/**
 * @package WS\Adapters\Apache
 */
class WS_Adapters_Apache_Adapter extends WS_AdapterAbstract
{

	protected function headers()
	{
		return apache_request_headers();
	}


	public function validate()
	{
		return function_exists('apache_request_headers');
	}

}

