<?php
/**
 * @package CMS\Text\Render
 */


class CMS_Text_Render implements Core_ModuleInterface {
	const MODULE  = 'CMS.Text.Render';
	const VERSION = '0.0.0';

	protected $out;
	protected $p;
	protected $ps;
	
	protected $replaces = array();
	
	protected $anchor = 0;
	public $anchors = array();
	
	static function run($src) {
		$renderer = new CMS_Text_Render();
		return $renderer->render($src);
	}
	
	static function renderer() {
		return new CMS_Text_Render();
	}

	public function parm($needle,$replacement) {
		$this->replaces[$needle] = $replacement;
		return $this;
	}

	public function result() {
		return $this->out;
	}

	public function anchors() {
		return $this->anchors;
	}

	protected function h($n,$s) {
		$this->endp();
		$s = trim($s);
		if ($s=='') return;
		$a = '';
		if ($s[strlen($s)-1]=='*') {
			$s = substr($s,0,strlen($s)-1);
			$this->anchor++;
			$this->anchors['a'.$this->anchor] = $s;
			$a = '<a name="a'.$this->anchor.'"></a>';
		}
		$this->out .= "\n$a<h$n>$s</h$n>";
	}
	
	protected function addp($s) {
		if ($this->p==false) {
			$this->p = 'p';
			$this->ps = '';
		}
		if ($this->p=='p') {
			$this->ps .= " ".$s;
		}
		else if ($this->p=='ul') {
			$s = trim($s);
			$this->out .= "\n<li>$s</li>";
		}
		else {
			if ($this->p=='php') {
				$s = htmlspecialchars($s);
				$s = str_replace('[?=','&lt;?=',$s);
				$s = str_replace("\t",'        ',$s);
				$s = preg_replace('/(class|const|var|private|protected|public|function|if|for|while|foreach|extends|implements|interface|abstract)\s+/ ','<b>\\1 </b>',$s);
				$s = str_replace(' ','&nbsp;',$s);
				$s = preg_replace('{//(.+)$}','<span class="comment">//\\1</span>',$s);
				$s = preg_replace('{\'([^\']+)\'}','<span class="str">\'\\1\'</span>',$s);
				$s = preg_replace('{(\$[a-z][a-z0-9_]+)}i','<span class="var">\\1</span>',$s);
			}
			$this->out .= "\n<p>$s</p>";
		}
	}
	
	protected function startp($t) {
		$this->endp();
		if ($t=='ul') $this->out .= "\n<ul>";
		else $this->out .= "\n<div class=\"$t\">";
		$this->p = $t;
	}
	
	protected function endp() {
		if ($this->p=='p') {
			$s = trim($this->ps);
			if ($s!='') $this->out .= "\n<p>$s</p>";
			$this->ps = '';
		}	
		else if ($this->p=='ul') {
			$this->out .= "\n</ul>";
		}
		else if (is_string($this->p)) {
			$this->out .= "\n</div>";
		}
		$this->p = false;
	}
	
	
	protected function process_line($line) {
		$oline = $line;
		$line = trim($line);
		if ($line=='') {
			if ($this->p=='p') $this->endp();
			else if (is_string($this->p)) $this->out .= "\n<p>&nbsp;</p>";
		}
		else if ($line=='--') {
			$this->endp();
		}	
		else if ($line=='-*') {
			$this->startp('ul');
		}	
		else if ($line=='-code') {
			$this->startp('code');
		}	
		else if ($line=='-php') {
			$this->startp('php');
		}	
		else if (preg_match('/^----(.+)$/',$line,$m)) $this->h(3,$m[1]);
		else if (preg_match('/^---(.+)$/',$line,$m)) $this->h(2,$m[1]);
		else if (preg_match('/^--(.+)$/',$line,$m)) $this->h(1,$m[1]);
		else {
			$this->addp($oline);
		}	
	}
		
	public function render($content) {
		$this->out = '';
		$this->p = false;
		$lines = explode("\n",$content);
		foreach($lines as $line) {
			$this->process_line($line);
		}
		$this->endp();
		
		foreach($this->replaces as $needle => $replacement) $this->out = str_replace("%%{{$needle}}",$replacement, $this->out);
		
		return $this;
	}


}
