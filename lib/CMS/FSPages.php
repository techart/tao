<?php

/**
 * @package CMS\FSPages
 */
class CMS_FSPages implements Core_ModuleInterface
{
	static $disabled = false;
	static $with_htm_extension = false;

	static function initialize($config = array())
	{
		if (self::$disabled) {
			return;
		}
		foreach ($config as $key => $value)
			self::$$key = $value;
		CMS::add_component('CMSFSPages', new CMS_FSPages_Router());
	}
}

class CMS_FSPages_Router extends CMS_Router
{
	public function route($request)
	{
		$uri = trim(strtolower($this->clean_url($request->uri)));
		if (CMS_FSPages::$with_htm_extension) {
			$uri = preg_replace('{\.htm$}','/',$uri);
		}
		$path = '';
		if ($uri == '/') {
			$path = '/';
		} elseif ($m = Core_Regexps::match_with_results('{^/(.+)/$}', $uri)) {
			foreach (explode('/', $m[1]) as $chunk) {
				if (Core_Regexps::match('{[a-z0-9_-]+}', $chunk)) {
					$path .= "/{$chunk}";
				}
			}
		}
		if ($path != '') {
			$dirs = array(
				CMS::$taopath . '/views/pages',
				CMS::app_path('views/pages'),
			);
			/**
			 * @event cms.fspages.dirs
			 * @arg $dirs Список каталогов
			 * Событие генерируется механизмом статических страниц (CMS.FSPages) для уточнения списка каталогов, в которых ищутся шаблоны. При необходимости в список можно добавить свой каталог.
			 */
			Events::call('cms.fspages.dirs', $dirs);
			if (count($dirs) > 0) {
				for ($i = count($dirs) - 1; $i >= 0; $i--) {
					$dir = $dirs[$i];
					$page = false;
					$page_path = "{$dir}{$path}/index.phtml";
					if (IO_FS::exists($page_path)) {
						$page = $page_path;
					} else {
						$page_path = "{$dir}/{$path}.phtml";
						if (IO_FS::exists($page_path)) {
							$page = $page_path;
						}
					}
					if ($page) {
						return array(
							'controller' => 'CMS.Controller.FSPages',
							'action' => 'index',
							$page,
						);
					}
				}
			}
		}
		return false;
	}
}

