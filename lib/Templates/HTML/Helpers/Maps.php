<?php
/**
 * Templates.HTML.Maps
 *
 * @package Templates\HTML\Helpers\Maps
 * @version 1.0.0
 */
Core::load('Templates.HTML');

/**
 * @package Templates\HTML\Helpers\Maps
 */
class Templates_HTML_Helpers_Maps implements Core_ModuleInterface, Templates_HelperInterface
{

	const VERSION = '1.0.0';

	/**
	 */
	static public function initialize()
	{
		Templates_HTML::use_helper('maps', 'Templates.HTML.Helpers.Maps');
	}

	/**
	 * @param string $service
	 * @param string $id
	 * @param array  $options
	 *
	 * @return string
	 */
	public function map($t, $service, $map_id, $name, $options = array(), $path_to_template = 'helpers/maps')
	{

		if (!is_array($options)) {
			$options = (array)$options;
		}

		$json_string = json_encode($options);

		$lang = 'ru';
		if ($options['properties']['lang'] != null) {
			$lang = $options['properties']['lang'];
		}

		if ($service == null) {
			$service = 'google';
		}

		switch ($service) {
			case 'google':
				$t->use_script('https://maps.google.com/maps/api/js?sensor=false&language=' . $lang, array('type' => 'lib', 'weight' => -21));
				$t->use_script('http://google-maps-utility-library-v3.googlecode.com/svn/trunk/infobubble/src/infobubble-compiled.js', array('type' => 'lib', 'weight' => -20));
				break;
			case 'yandex':
				$t->use_script('http://api-maps.yandex.ru/2.0/?load=package.full&lang=' . $lang, array('type' => 'lib', 'weight' => -21));
				break;
			default:
				return;
		}
		$t->use_script('maps.js', array('type' => 'lib', 'weight' => -19));
		return $t->partial($path_to_template, array(
				'json_string' => $json_string,
				'map' => $service . '_maps',
				'name' => $name,
				'map_id' => $map_id)
		);
	}

}

