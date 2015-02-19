<?php
/**
 * Service.OpemSocial.Formats.JSON
 *
 * @package Service\OpenSocial\Formats\JSON
 */

/**
 * @package Service\OpenSocial\Formats\JSON
 */
class Service_OpenSocial_Formats_JSON implements Service_OpenSocial_ModuleInterface
{

	/**
	 * @return Service_OpenSocial_JSON_Format
	 */
	static public function Format()
	{
		return new Service_OpenSocial_Formats_JSON_Format();
	}

}

/**
 * @package Service\OpenSocial\Formats\JSON
 */
class Service_OpenSocial_Formats_JSON_Format extends Service_OpenSocial_Format
{

	/**
	 * @param string $name
	 * @param string $content_type
	 */
	public function __construct()
	{
		parent::__construct('json', 'application/json');
	}

	/**
	 * @abstract
	 *
	 * @param  $object
	 *
	 * @return string
	 */
	public function encode($object)
	{
		return json_encode($object);
	}

	/**
	 * @abstract
	 *
	 * @param string $string
	 *
	 * @return object
	 */
	public function decode($string)
	{
		return json_decode($string);
	}

}

