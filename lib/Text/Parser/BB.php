<?php

Core::load('Text.Process');

class Text_Parser_BB implements Core_ModuleInterface, Text_process_processInterface { 
	
	const VERSION = '0.1.0'; 
	
	static $lclass = false; 
	static $smiles_dir = "image/smiles";

	static $tag_b	 = true;
	static $tag_i	 = true;
	static $tag_url	 = false;
	
	static $codes = array();
	static $reflections = array();
	
	static function add($class,$method) {
		$code = mb_strtolower($method);
		self::$codes[$code] = array($class,$method);
	}

	static function process_codes_bb($m) {
		$code = mb_strtolower(trim($m[1]));
		$parms = trim($m[2]);
		$callback = self::$codes[$code];
		list($class,$method) = $callback;
		if (!isset(self::$reflections[$code])) self::$reflections[$code] = new ReflectionMethod($class,$method);
		return self::$reflections[$code]->invokeArgs(NULL,array($parms));				
	}
	
	static function process_codes($s) {
		foreach(self::$codes as $code => $class) {
			$s = preg_replace_callback('{\[('.$code.')\s+([^\]]+)\]}i',array('Text_Parser_BB','process_codes_bb'),$s);
		}
		return $s;
	}
	
	static function initialize($config=array()) { 
		self::$lclass = Core::make('Text.Parser.BB.Line'); 
		foreach($config as $key => $value) self::$$key = $value; 
		CMS::register_insertions('Text_Parser_BB', 'SMILES');
		self::add('CMS_Insertions','YOUTUBE'); 
		self::add('CMS_Insertions','RUTUBE'); 
	} 
	
	static function SMILES($textarea_id) { 
		ob_start(); 
		include("../app/views/insertions/smiles.phtml"); 
		return ob_get_clean(); 
	} 
	
	public function configure($config) {}
	
	public function process($s) { return $this->transform($s); }
	
	static function transform($src,$config = false) {
		if (Core_Types::is_iterable($config)) foreach($config as $key => $value) self::$$key = $value;
		
		$out = ''; 
		$src = strip_tags($src); 
		$src = str_replace("[quote]","\n[quote]\n",$src); 
		$src = str_replace("[/quote]","\n[/quote]\n",$src); 
		$lines = explode("\n",$src); 
		
		foreach($lines as $line) { 
			$line = trim($line); 
			if ($line!='') $out .= self::$lclass->line($line); 
		} 
		
		return self::process_codes($out); 
	} 
} 

class Text_Parser_BB_Line { 
	
	protected function tag_b($m) { 
		return '<b>'.$m[2].'</b>'; 
	} 
	
	protected function tag_i($m) { 
		return '<i>'.$m[2].'</i>'; 
	} 
	
	protected function tag_url($m) { 
		
		$href = trim($m[1]); 
		$text = trim($m[2]); 
		
		if ($m = Core_Regexps::match_with_results('{^=(.+)}',$href)) { 
			$href = trim($m[1]); 
			return "<a href=\"$href\" target=\"_blank\">$text</a>"; 
		} 
		
		return $text; 
	} 
	
	protected function tag_quote($m) { 
		$name = trim($m[1]); 
		$text = trim($m[2]); 
	} 
	
	protected function tagre($tag) { 
		return '{\['.$tag.'([^\]]*)\]([^\[\]]+)\[/'.$tag.'\]}'; 
	} 
	
	static function tag_smile($m) { 
		if (IO_FS::exists($file = Text_Parser_BB::$smiles_dir."/".$m[1].'.gif')) { 
			$is = getimagesize($file); 
			return '<img src="/'.Text_Parser_BB::$smiles_dir.'/'.$m[1].'.gif" '.$is[3].' />'; 
		} 
		
		else { 
			return ''; 
		} 
	} 
	
	public function line($src) { 
		$src = trim($src); 
		if (Text_Parser_BB::$tag_b) $src = preg_replace_callback($this->tagre('b'),array($this,'tag_b'),$src); 
		if (Text_Parser_BB::$tag_i) $src = preg_replace_callback($this->tagre('i'),array($this,'tag_i'),$src);
		if (Text_Parser_BB::$tag_url) $src = preg_replace_callback($this->tagre('url'),array($this,'tag_url'),$src); 
		$src = str_replace('[quote]','<blockquote>',$src); 
		$src = preg_replace('{\[quote\s+([^\]]+)\]}','<p class="quotename">\\1:</p><blockquote>',$src); 
		$src = str_replace('[/quote]','</blockquote>',$src); 
		$src = preg_replace_callback('{:sm(\d+):}', array($this, 'tag_smile'), $src); return "<p>$src</p>";
	} 
} 

