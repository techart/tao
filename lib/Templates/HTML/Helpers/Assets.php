<?php
/**
 * WebKit.Helpers.Helpers.Assets
 *
 * @package Templates\HTML\Assets
 * @version 1.0.2
 */
Core::load('Templates.HTML', 'Templates.HTML.Helpers.Tags');

/**
 * @package Templates\HTML\Helpers\Assets
 */
class Templates_HTML_Helpers_Assets
	implements Core_ModuleInterface,
	Templates_HelperInterface
{

	const VERSION = '1.0.2';

	/**
	 */
	static public function initialize()
	{
		Templates_HTML::use_helper('tags', 'Templates.HTML.Helpers.Tags');
		Templates_HTML::use_helper('assets', 'Templates.HTML.Helpers.Assets');
	}

	/**
	 * @param string $url
	 * @param array  $attributes
	 *
	 * @return string
	 */
	public function image_tag($t, $url, array $attributes = array())
	{
		return $t->tags->tag('img',
			Core_Arrays::merge($attributes, array(
					'src' => $this->image_path_for($url))
			)
		);
	}

	/**
	 * @param string $type
	 * @param string $url
	 * @param array  $options
	 *
	 * @return string
	 */
	public function auto_discovery_link_tag($t, $type, $url, array $options = array())
	{
		return $t->tags->tag('link', array(
				'rel' => $options['rel'] ? $options['rel'] : 'alternate',
				'type' => $options['type'] ? $options['type'] : "application/$type+xml",
				'title' => $options['title'] ? $options['title'] : Core_Strings::upcase($type),
				'href' => $url)
		) . "\n";
	}

	/**
	 * @return string
	 */
	public function stylesheet_link_tag($t)
	{
		$result = '';
		foreach (($args = Core_Types::is_array(func_get_arg(1)) ? func_get_arg(1) : array_slice(func_get_args(), 1)) as $src)
			$result .= $t->tags->tag('link', array(
						'rel' => 'stylesheet',
						'type' => 'text/css',
						'media' => 'screen',
						'href' => $this->stylesheet_path_for($t, $src))
				) . "\n";
		return $result;
	}

	/**
	 * @return string
	 */
	public function javascript_include_tag($t)
	{
		$result = '';

		foreach (($args = Core_Types::is_array(func_get_arg(1)) ? func_get_arg(1) : array_slice(func_get_args(), 1)) as $src)
			$result .= $t->tags->content_tag('script', '', array(
						'type' => 'text/javascript',
						'src' => $this->javascript_path_for($t, $src))
				) . "\n";

		return $result;
	}

	/**
	 * @param string $src
	 *
	 * @return string
	 */
	public function image_path_for($t, $src)
	{
		return $this->compute_public_path($src, 'images', '.png');
	}

	/**
	 * @param string $src
	 *
	 * @return string
	 */
	public function javascript_path_for($t, $src)
	{
		return $this->compute_public_path($src, 'scripts', '.js', true);
	}

	/**
	 * @param string $src
	 *
	 * @return string
	 */
	public function stylesheet_path_for($t, $src)
	{
		return $this->compute_public_path($src, 'styles', '.css', true);
	}

	/**
	 * @param     $asset
	 * @param     $dir
	 * @param     $extension
	 * @param int $timestamp
	 *
	 * @return string
	 */
	protected function compute_public_path($asset, $dir, $extension, $timestamp = false)
	{
		if ($asset[0] == '/' || strstr($asset, "://")) {
			return $asset;
		}
		$asset .= (preg_match('{(?:gif|png|jpg|js|css)$}', $asset) ? '' : $extension);
		return "/$dir/$asset" . ($timestamp ? '?' . IO_FS::Stat("$dir/$asset")->mtime->timestamp : '');
	}

}

