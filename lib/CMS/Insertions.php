<?php
/**
 * CMS.Insertions
 * 
 * @package CMS\Insertions
 * @version 0.0.0
 * 
 */

class CMS_Insertions extends CMS_Insertions_Base implements Core_ModuleInterface {
	const MODULE  = 'CMS.Insertions';
	const VERSION = '0.0.0';
	
	
	static function initialize() {
		//CMS::register_insertions( 'CMS_Insertions','IMGR','IMGL','IMG','PAGE','CALL','IMAGES','NAVIGATION','VAR:_VAR','YOUTUBE','RUTUBE', 'MAPS');
	}
	
	static function file_list($src) {
		$ar = explode(',',$src);
		$files = array();
		$except = array();
		$fsrc = CMS::env()->files;
		if (!Core_Types::is_iterable($fsrc)) $fsrc = array();
		foreach($ar as $f) {
			if (preg_match('/^all(.*)/',$f,$m)) {
				foreach($fsrc as $fid => $file) {
					$files[$fid] = self::file($fid);
				}
			}
			else if (preg_match('/^-(.+)$/',$f,$m)) {
				$id = self::file_id($m[1]);
				if ($id) $except[] = $id;
			}
			else if (preg_match('/(\d+)-(\d+)$/',$f,$m)) {
				for ($i=(int)$m[1] ; $i<=$m[2] ; $i++)
					if (isset($fsrc[$i])) $files[$i] = $fsrc[$i];
			}
			else {
				if (isset($fsrc[$f])) $files[$f] = $fsrc[$f];
				else {
					foreach($fsrc as $key => $file) {
						if ($file['alias']==$f) {
							$files[$key] = $file;
						}	
					}	
				}
			}	
		}
		
		foreach($except as $e) unset($files[$e]);
		return $files;
	}
	
	static function file_id($id) {
		if (!isset(CMS::env()->files)) return false;
		if (!Core_Types::is_iterable(CMS::env()->files)) return false;
		if (isset(CMS::env()->files[$id])) return $id;
		foreach(CMS::env()->files as $key => $file) if ($file['alias']==$id) return $key;
		return false;
	}
	
	static function file($id) {
		if (!isset(CMS::env()->files)) return false;
		if (!Core_Types::is_iterable(CMS::env()->files)) return false;
		if (isset(CMS::env()->files[$id])) return CMS::env()->files[$id];
		foreach(CMS::env()->files as $file) if ($file['alias']==$id) return $file;
		return false;
	}
	
	static function IMGL($parms) {
		return self::render('imgl',array('files' => self::file_list($parms)));
	}
	
	static function IMGR($parms) {
		return self::render('imgr',array('files' => self::file_list($parms)));
	}
	
	static function IMAGES($parms) {
		$list = $parms;
		$tpl = 'images';
		if (preg_match('/^([^:]+):(.+)/',$list,$m)) {
			$tpl = $m[1];
			$list = $m[2];
		}
		return self::render($tpl,array('files' => self::file_list($list)));
	}
	
	static function IMG($parms) {
		$file = self::file($parms);
		if ($file) {
			$rc = self::render('img',array('file' => $file));
			return $rc;
		}	
		return "%IMG{{$parms}}";
	}
	
	static function PAGE($parms) {
		$parms = trim($parms);
		if ($parms!='') return CMS::$env->cms->page->$parms;
		return "%PAGE{}";
	}
	
	static function CALL($parms) {
	
		if (preg_match('{(.+)::(.+)\((.+)\)}',$parms,$m)) {
			$component = $m[1];
			$method = new ReflectionMethod('Component_'.$m[1],$m[2]);
			$args = explode(',',$m[3]);
			return $method->invokeArgs(NULL,$args);
		}
	
		return "%CALL{{$parms}}";
	}
	
	
	static function NAVIGATION($parms) {
		$parms = trim($parms);
		if ($parms=='') return CMS::$navigation->draw();
		if (Core_Regexps::match('/^[0-9a-z_]+$/i',$parms)) {
			return CMS::$navigation->linkset_by_id($parms)->draw();
		}
		if ($m = Core_Regexps::match_with_results('/^([^:]+):([^:]+)$/',$parms)) {
			return CMS::$navigation->linkset_by_id($m[2])->draw($m[1]);
		}
		if ($m = Core_Regexps::match_with_results('/^([^:]+):([^:]+):([^:]+)$/',$parms)) {
			return CMS::$navigation[$m[2]]->linkset_by_id($m[3])->draw($m[1]);
		}
		return "%NAVIGATION{{$parms}}";
	}
	
	static function YOUTUBE($parms) {
		$code = trim($parms);
		return self::render('youtube',array('code' => $code));
	}		
	
	static function RUTUBE($parms) {
		$code = trim($parms);
		return self::render('rutube',array('code' => $code));
	}		
	
	static function _VAR($parms) {
		$name = trim($parms);
		$res = CMS::vars()->get($parms);
		if ($res instanceof CMS_FILE_PATH_URL) {
			$d = filemtime($res->path());
			$res = $res->url()."?$d";
		}
		return CMS::process_insertions($res);
	}	

	static function MAPS($parms=false) { 

		if (!CMS::layout_view()) return '';

		Core::load('Templates.HTML.Helpers.Maps');
		$parms = explode(';', $parms);
		$type = trim($parms[0]);
		$map_id = trim($parms[1]);
		$name = trim($parms[2]);
		$options = CMS::vars()->get($name);
		return CMS::layout_view()->maps->map($type, $map_id, $name, $options);
	}
}


class CMS_Insertions_Base {
	
	
	static function tpl($tpl) {
		$t = CMS::app_view("insertions/$tpl.phtml");
		if (IO_FS::exists($t)) return $t;
		return CMS::view("insertions/$tpl.phtml");
	}
	
	static function render($tpl,$parms=array()) {
		return CMS::render_in_page(self::tpl($tpl),$parms);
	}
}

