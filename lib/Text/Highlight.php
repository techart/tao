<?php
/**
 * @package Text\Highlight
 */


Core::load('Text.Process');

class Text_Highlight implements Core_ModuleInterface, Text_Process_ProcessInterface { 

	const VERSION = '0.0.0'; 
	
	protected $tag_keyword_start 	= '<span class="hl-keyword">';
	protected $tag_keyword_end 		= '</span>';

	protected $tag_number_start 	= '<span class="hl-number">';
	protected $tag_number_end 		= '</span>';

	protected $tag_string_start 	= '<span class="hl-string">';
	protected $tag_string_end 		= '</span>';
	
	protected $tag_id_start 		= '<span class="hl-id">';
	protected $tag_id_end 			= '</span>';
	
	protected $tag_comment_start	= '<span class="hl-comment">';
	protected $tag_comment_end 		= '</span>';
	
	protected $keywords = array();
	protected $do_keywords = false;
	protected $do_numbers = false;
	protected $do_strings1 = false;
	protected $do_strings2 = false;
	protected $do_comments_sharp = false;
	protected $do_comments_slashes = false;
	protected $do_comments_block = false;
	
	protected $src = '';
	protected $out = '';
	protected $pos = 0;
	protected $kwhash = array();
	
	protected function add($s) {
		$this->out .= $s;
	}
	
	protected function eof() {
		return $this->pos>=strlen($this->src);
	}
	
	protected function get() {
		if ($this->eof()) return '';
		$c = substr($this->src,$this->pos,1);
		$this->pos++;
		return $c;
	}

	protected function unget($n=1) {
		if ($this->pos>=$n) $this->pos -= $n;
	}
	
	protected function init($s=false) {
		if ($s) $this->src = $s;
		foreach($this->keywords as $kw) $this->kwhash[$kw] = true;
		$this->pos = 0;
	}
	
	protected function keywords($s) {
		$s = " $s ";
		foreach($this->keywords as $keyword) {
			$re = "/([^a-z0-9_])($keyword)([^a-z0-9_])/i";
			$s = Core_Regexps::replace($re,'\\1__kwstart__\\2__kwend__\\3',$s);
		}
		$s = substr($s,1,strlen($s)-2);
		$s = str_replace('__kwstart__',$this->tag_keyword_start,$s);
		$s = str_replace('__kwend__',$this->tag_keyword_end,$s);
		return $s;
	}
	
	public function run1($s) {
		$s = $this->keywords($s);
		return $s;
	} 

	protected function is_letter($c) {
		if (strlen($c)!=1) return false;
		$o = ord($c);
		return ($o>=ord('a')&&$o<=ord('z'))||($o>=ord('A')&&$o<=ord('Z'));
	}

	protected function is_digit($c) {
		if (strlen($c)!=1) return false;
		$o = ord($c);
		return $o>=ord('0')&&$o<=ord('9');
	}
	
	protected function lex_identifier($c=false) {
		if ($c===false) $c = $this->get();
		$w = '';
		while($this->is_letter($c)||$this->is_digit($c)||$c=='_') {
			$w .= $c;
			$c = $this->get();
		}
		if ($c!='') $this->unget();
		return $w;
	}

	protected function lex_number($c=false) {
		if ($c===false) $c = $this->get();
		$w = '';
		while($this->is_digit($c)||$c=='.') {
			$w .= $c;
			$c = $this->get();
		}
		if ($c!='') $this->unget();
		return $w;
	}
	
	protected function lex_seq($end) {
		$rc = '';
		while(!$this->eof()) {
			$c = $this->get();
			if ($c=='\\') {
				$c .= $this->get();
			}
			if ($c==$end) return $rc.$end;
			$rc .= $c;
		}
		return $rc;
	}

	protected function lex_for_eol() {
		$rc = '';
		while(!$this->eof()) {
			$c = $this->get();
			if (ord($c)==10||ord($c)==13) {
				$this->unget();
				return $rc;
			}
			$rc .= $c;
		}
		return $rc;
	}
	
	protected function lex_comment_block() {
		$rc = '';
		while(!$this->eof()) {
			$c = $this->get();
			if ($c=='*'&&!$this->eof()) {
				$c = $this->get();
				if ($c=='/') return $rc . '*/';
				else $rc .= '*' . $c;
			}
			else $rc .= $c;
		}
		return $rc;
	}
	
	protected function lex() {
		if ($this->eof()) return '';
		$c = $this->get();
		if ($this->is_letter($c)||$c=='_') {
			if ($this->do_keywords) {
				$w = $this->lex_identifier($c);
				if ($this->kwhash[$w]) return $this->tag_keyword_start . $w . $this->tag_keyword_end; 
				return $w;
			}
			else return $c;
		}
		
		else if ($this->is_digit($c)) {
			if ($this->do_numbers) {
				$w = $this->lex_number($c);
				return $this->tag_number_start . $w . $this->tag_number_end; 
			}
			else return $c;
		} 

		else if ($c=="'"&&$this->do_strings1) {
			return $this->tag_string_start . "'" . htmlspecialchars($this->lex_seq("'"))  . $this->tag_string_end;			
		}

		else if ($c=='"'&&$this->do_strings2) {
			return $this->tag_string_start . '"' . htmlspecialchars($this->lex_seq('"'))  . $this->tag_string_end;			
		}
		
		else if ($c=='#'&&$this->do_comments_sharp) {
			return $this->tag_comment_start . '#' . htmlspecialchars($this->lex_for_eol())  . $this->tag_comment_end;			
		}
		
		else if ($c=='/'&&!$this->eof()) {
			$c = $this->get();
			if ($c=='/'&&$this->do_comments_slashes) {
				return $this->tag_comment_start . '//' . htmlspecialchars($this->lex_for_eol())  . $this->tag_comment_end;			
			}
			
			if ($c=='*'&&$this->do_comments_block) {
				return $this->tag_comment_start . '/*' . htmlspecialchars($this->lex_comment_block())  . $this->tag_comment_end;			
			}
			
			else {
				$this->unget();
				return '/';
			}
		}
		
		else return $c;	
	}
	
	public function run($s) {
		$this->init($s);
		while (!$this->eof()) {
			$lex = $this->lex();
			if ($lex=='<') $lex = '&lt;';
			if ($lex=='>') $lex = '&gt;';
			$this->add($lex);
		}
		return $this->out;
	} 
	
	public function process($s) { return $this->run($s);}
	
	public function configure($config) {}
} 

