<?php

/**
 * WS.Adapters.FPM
 *
 * @package WS\Adapters\FPM
 */

Core::load('WS', 'Net.HTTP');

/**
 * @package WS\Adapters\FPM
 */
class WS_Adapters_FPM implements Core_ModuleInterface
{
	/**
	 * @return WS_Adapters_FPM_Adapter
	 */
	static public function Adapter()
	{
		return new WS_Adapters_FPM_Adapter();
	}
}

/**
 * @package WS\Adapters\FPM
 */
class WS_Adapters_FPM_Adapter extends WS_AdapterAbstract
{


	protected function headers()
	{
		$headers = array();
		foreach($_SERVER as $key => $value) {
			if (substr($key, 0, 5) != 'HTTP_') {
				continue;
			}
			$header = str_replace(' ', '-', ucwords(str_replace('_', ' ', strtolower(substr($key, 5)))));
			$headers[$header] = $value;
		}
		return $headers;
	}
	
}

