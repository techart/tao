<?php
/**
 * @package CMS\Helper
 */



class CMS_Helper implements Core_ModuleInterface {
	const MODULE  = 'CMS.Helper';
	const VERSION = '0.0.0';

	static $pn_url;
	static $pn_current;

	public function draw($view,$content) {
		$_view = CMS::layout_view();
		CMS::layout_view($view);
		$content = CMS::process_insertions($content);
		CMS::layout_view($_view);
		return $content;
	}

	public function page_navigator($view,$pagenum,$numpages,$url) {
		$app = CMS::app_path('views/helpers');
		$lib = CMS::tao_view('helpers');
		$templates = array();
		if (CMS::admin()) {
			$templates[] = "{$app}/page-navigator-admin.phtml";
			$templates[] = "{$lib}/page-navigator-admin.phtml";
		}

		else {
			$templates[] = "{$app}/page-navigator-site.phtml";
			$templates[] = "{$app}/page-navigator.phtml";
			
		}
		$templates[] = "{$lib}/page-navigator.phtml";
		foreach($templates as $template) {
			if (IO_FS::exists($template)) break;
		}

		self::$pn_url = $url;
		self::$pn_current = $pagenum;

		return $view->partial($template,array(
			'tpl' => $url,
			'page' => $pagenum,
			'numpages' => $numpages,
		));
	}

	protected function url($page) {
		if (is_callable(self::$pn_url)) {
			return call_user_func(self::$pn_url,$page);
		}
		$s = preg_replace('{\%$}',$page,self::$pn_url);
		$s = preg_replace('{\%([^0-9a-z])}i',"$page\\1",$s);
		return $s;
	}

	public function pn_link($view,$i,$t=false) {
		if (!$t) $t = $i;
		if ($i==self::$pn_current) return "<b class=\"page-navigator-current\">$t</b>";
		return '<a href="'. ($this->url($i)).'"'.($i==self::$pn_current?' class="page-navigator-current current"':'') .">$t</a>";
	}

}

