<?php

class Templates_HTML_Assets_Preprocess_SCSS_Server extends scss_server implements Core_ModuleInterface
{

	protected $current_dir;

	public function __construct($dir, $cacheDir=null, $scss=null)
	{
		parent::__construct($dir, $cacheDir, $scss);
		$scss->addImportPath(array($this, 'findPath'));
	}

	public function findPath($path)
	{
		$paths = array();
		$paths[] = $this->current_dir . '/' . $path;
		$paths[] = $this->current_dir . '/_' . $path . '.scss';
		foreach ($paths as $p) {
			if (file_exists($p)) {
				return $p;
			}
		}
		return null;
	}

	public function compile($in, $out)
	{
		$this->current_dir = rtrim(dirname($in), '/');
		return parent::compile($in, $out);
	}

	public function needsCompile($in, $out)
	{
		return parent::needsCompile($in, $out);
	}
}