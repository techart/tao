<?php
/**
 * CMS.WikiParser
 * 
 * @package Text\Parser\Wiki
 * @version 0.0.0
 */

Core::load('Text.Process');

/**
 * @package Text\Parser\Wiki
 */

class Text_Parser_Wiki implements Core_ModuleInterface, Text_Process_ProcessInterface 
{
	static $template_link			= '<a href="%s">%s</a>';
	static $template_external_link		= '<a href="%s" target="_blank">%s</a>';
	static $template_wiki_link 		= '<a href="/%s">%s</a>';
	static $template_h1			= '<h1>%s</h1>';
	static $template_h2			= '<h2>%s</h2>';
	static $template_h3			= '<h3>%s</h3>';
	static $template_h4			= '<h4>%s</h4>';
	static $template_h5			= '<h5>%s</h5>';
	static $template_table_caption		= '<caption>%s</caption>';
	static $template_b			= '<b>%s</b>';
	static $template_i			= '<i>%s</i>';
	
	static $tag_p_start			= '<p>';
	static $tag_p_end			= '</p>';
	
	static $tag_dl_start			= '<dl class="wiki">';
	static $tag_dl_end 			= '</dl>';
	
	static $tag_dt_start			= '<dt>';
	static $tag_dt_end			= '</dt>';
			
	static $tag_dd_start			= '<dd>';
	static $tag_dd_end			= '</dd>';

	static $tag_pre_start	= '<pre class="wiki">';
	static $tag_pre_end	= '</pre>';

	static $show_url_length = 100;
	static $url_callback = false;
	
	protected $current = false;
	protected $html = '';
	protected $lines = array();
	protected $cursor = 0;
	protected $ul = 0;
	protected $ol = 0;
	
	
	protected $highlights = array(
		'php' => 'Text.Highlight.PHP',
	);
	
	
	public function configure($config) {
	  
	}
	
	public function process($s) { return $this->parse($s);}
	

/**
 * @param string $source
 * @return string
 */
	public function parse($source,$config=array()) {
		foreach($config as $k=>$v) {
			self::$$k = $v;
		}
		$this->current = false;
		$this->html = '';
		$this->cursor = 0;
		$this->lines = explode("\n",$source);
		$this->ul = 0;
		$this->ol = 0;

		$ccc = 0;
		while (!$this->eof()) {
			$this->parse_one();
		}
		$this->close_block();
		return $this->html;
	}
	
	


/**
 */
	protected function open_block($type) {
		if ($this->current==$type) return;
		$this->close_block();
		if ($type=='p') $this->html .= self::$tag_p_start;
	}
	
/**
 */
	protected function close_block() {
		if (!$this->current) return;
		if ($this->current=='p') $this->html .= self::$tag_p_end;
		$this->current = false;
	}
	
/**
 * @return boolean
 */
	protected function eof() {
		return $this->cursor>=sizeof($this->lines);
	}
	
/**
 * @return string
 */
	protected function get() {
		return $this->lines[$this->cursor++];
	}
	
/**
 */
	protected function unget() {
		$this->cursor--;
	}
	

/**
 * @return string
 */
	public function parse_link_callback($m) {
		$s = trim($m[1]);
		if ($m = Core_Regexps::match_with_results('{^(.+)\|(.+)$}',$s)) {
			$url = trim($m[1]);
			$txt = trim($m[2]);
		}
		else {
			$url = $s;
			$txt = $s;
			if (mb_strlen($txt)>self::$show_url_length+3) $txt = mb_substr($txt,0,self::$show_url_length).'...';
		}
		if (Core_Regexps::match('{^http://}',$url)) return sprintf(self::$template_external_link,$url,$txt);
		if (Core_Regexps::match('{/}',$url)) return sprintf(self::$template_link,$url,$txt);
		$wurl = false;
		if (self::$url_callback) {
			$wurl = call_user_func(self::$url_callback,$url);
		}
		if (!$wurl) {
			$wurl = CMS::objects()->wiki->make_url($url);
		}
		if ($wurl) return sprintf(self::$template_link,$wurl,$txt);
		return sprintf(self::$template_wiki_link,ucfirst($url),$txt);
	}

/**
 */
	protected function parse_simple_line($line) {
		$line = Core_Regexps::replace('{\'\'\'(.+?)\'\'\'}','<b>\\1</b>',$line);
		$line = Core_Regexps::replace('{\'\'(.+?)\'\'}','<i>\\1</i>',$line);
		$line = Core_Regexps::replace_using_callback('{\[\[(.+?)\]\]}',array($this,'parse_link_callback'),$line);
		if ($m = Core_Regexps::match_with_results('{^([\s\*\#]*)(http://[^\s+]+)\s*}',$line)) {
			$url = $m[2];
			$urltext = $url;
			$urltext = Core_Regexps::replace('{^http://}','',$urltext);
			$urltext = Core_Regexps::replace('{/.*$}','',$urltext);
			$line = $m[1].'[<a href="'.$url.'" target="_blank">'.$urltext.'</a>]';
		}
		$line = str_ireplace('[b]','<b>',$line);
		$line = str_ireplace('[/b]','</b>',$line);
		$line = str_ireplace('[q]','<blockquote>',$line);
		$line = str_ireplace('[/q]','</blockquote>',$line);
		return $line;
	}


/**
 */
	protected function parse_one() {
			$line = $this->get();
			$tline = rtrim($line);
			
			$line = $this->parse_simple_line($line);
			
			if ($line=='') $this->close_block();

			else if ($m = Core_Regexps::match_with_results('{^<source\s+lang="(.+)">}i',$tline)) {
				$this->close_block();
				$lang = strtolower(trim($m[1]));
				if (isset($this->highlights[$lang])) {
					$module = $this->highlights[$lang];
					Core::load($module);
					$class = str_replace('.','_',$module);
					$highlighter = new $class;
					$this->parse_source($highlighter);
				}
			}

			else if (strtolower($tline)=='</source>') {
				
			}
			
			else if ($m = Core_Regexps::match_with_results('{^\{\|}',$tline)) {
				$this->close_block();
				$this->unget();
				$this->html .= self::parse_table();
			}
			else if ($m = Core_Regexps::match_with_results('{^;(.+):(.+)}',$tline)) {
				$this->close_block();
				$this->unget();
				$this->html .= self::$tag_dl_start;
				$this->parse_dl();
				$this->html .= self::$tag_dl_end;
			}
			
			else if ($m = Core_Regexps::match_with_results('{^======(.+)======$}',$tline)) {
				$this->close_block();
				$this->html .= sprintf(self::$template_h5,trim($m[1]));
			}
			
			else if ($m = Core_Regexps::match_with_results('{^====(.+)====$}',$tline)) {
				$this->close_block();
				$this->html .= sprintf(self::$template_h4,trim($m[1]));
			}
			
			else if ($m = Core_Regexps::match_with_results('{^===(.+)===$}',$tline)) {
				$this->close_block();
				$this->html .= sprintf(self::$template_h3,trim($m[1]));
			}
			
			else if ($m = Core_Regexps::match_with_results('{^==(.+)==$}',$tline)) {
				$this->close_block();
				$this->html .= sprintf(self::$template_h2,trim($m[1]));
			}
			
			else if ($m = Core_Regexps::match_with_results('{^=(.+)=$}',$tline)) {
				$this->close_block();
				$this->html .= sprintf(self::$template_h1,trim($m[1]));
			}
			
			else if ($tline=='----') {
				$this->close_block();
				$this->html .= "\n<hr />\n";
			}
			
			else if (strlen($line)>0&&$line[0]=='*') {
				$this->close_block();
				$this->html .= "\n<ul>";
				$this->unget();
				$this->parse_list('*');
				$this->html .= "\n</ul>";
			}

			else if (strlen($line)>0&&$line[0]=='#') {
				$this->close_block();
				$this->html .= "\n<ol>";
				$this->unget();
				$this->parse_list('#');
				$this->html .= "\n</ol>";
			}

			else if ($line[0]==' '||$line[0]=="\t") {
				$this->close_block();
				$this->html .= self::$tag_pre_start;
				$this->unget();
				$this->parse_pre();
				$this->html .= self::$tag_pre_end;
			}
			
			else {
				$this->open_block('p');
				$this->html .= "$line";
			}
	}
	
/**
 * @return string
 */
	protected function parse_table() {
		$line = '';
		while (!$this->eof()&&!($m = Core_Regexps::match_with_results('{^\{\|(.*)}',$line))) $line = trim($this->get());
		if ($this->eof()) return '';
		$parms = trim($m[1]); if ($parms!='') $parms = " $parms";
		$out .= "<table$parms>";
		
		$line = trim($this->get());
		if ($m = Core_Regexps::match_with_results('{^\|\+(.+)}',$line)) {
			$out .= sprintf(self::$template_table_caption,trim($m[1]));
		}
		else $this->unget();
		
		while ($tr = $this->parse_tr()) {
			$out .= $tr;
		}
		$out .= '</table>';
		return $out;
	}
	
/**
 * @return string
 */
	protected function parse_tr() {
		$out = '';
		while (!$this->eof()) {
			$line = trim($this->get());
			if ($line=='|}'||$line==''||($line[0]!='|'&&$line[0]!='!')) {
				if ($out=='') return false;
				else {
					$this->unget();
					return "<tr>$out</tr>";
				}
			}
			else if ($line=='|-') {
					return "<tr>$out</tr>";
			}
			else if ($line=='|') {
					$out .= '<td>'.$this->parse_table().'</td>';
			}
			else if ($m = Core_Regexps::match_with_results('{^\|([^\|]+)\|$}',$line)) {
					$parms = trim($m[1]);
					$out .= '<td '.$parms .'>'.$this->parse_table().'</td>';
			}
			else {
				$tdt = ($line[0]=='|')?'td':'th';
				$line = substr($line,1);
				$tds = explode('||',$line);
				foreach($tds as $td) {
					$parms = '';
					if ($m = Core_Regexps::match_with_results('{^(.+?)\|(.+)$}',$td)) {
						$parms = ' '.trim($m[1]);
						$td = trim($m[2]);
					}
					$out .= "<$tdt$parms>$td</$tdt>";
				}
			}
		}	
		
	}
	
/**
 */
	protected function parse_pre() {
		while (!$this->eof()) {
			$line = rtrim($this->get());
			if ($line!=''&&($line[0]==' '||$line[0]=="\t")) {
				$this->html .= $line."\n";
			}
			else {
				$this->unget();
				return;
			}
		}
	}		

/**
 */
	protected function parse_source($hl) {
		$source = '';
		while (!$this->eof()) {
			$line = rtrim($this->get());
			if (strtolower($line)=='</source>') break;
			else $source .= $line."\n";
		}
		$code = $hl->run($source);
		$this->html .= self::$tag_pre_start;
		$this->html .= $code;
		$this->html .= self::$tag_pre_end;
	}		
	
	
/**
 */
	protected function parse_dl() {
		while (!$this->eof()) {
			$line = $this->get();
			if ($m = Core_Regexps::match_with_results('{^;(.+?):(.+)$}',$line)) {
				$this->html .= self::$tag_dt_start;
				$this->html .= trim($m[1]);
				$this->html .= self::$tag_dt_end;
				$this->html .= self::$tag_dd_start;
				$this->html .= trim($m[2]);
				$this->html .= self::$tag_dd_end;
			}
			else {
				$this->unget();
				return;
			}
		}
	}		
	
/**
 */
	protected function parse_list($prefix) {
		$lp = strlen($prefix);
		while (!$this->eof()) {
			$line = $this->get();
			if (substr($line,0,$lp)==$prefix) {
				$line = substr($line,$lp);
				if (strlen($line)>0&&$line[0]=='*') {
					$this->html .= "\n<ul>";
					$this->unget();
					$this->parse_list($prefix.'*');
					$this->html .= "\n</ul>";
				}
				
				else if (strlen($line)>0&&$line[0]=='#') {
					$this->html .= "\n<ol>";
					$this->unget();
					$this->parse_list($prefix.'#');
					$this->html .= "\n</ol>";
				}
				
				else {
					$this->html .= '<li>';
					$line = $this->parse_simple_line($line);
					$this->html .= rtrim($line);
					$this->html .= "</li>\n";
				}

			}
			else {
				$this->unget();
				return;
			}
		}
	}
	
	
	
} 



