<?php

Core::load('Text.Highlight');


class Text_Highlight_PHP extends Text_Highlight implements Core_ModuleInterface { 

	const VERSION = '0.0.0';

	protected $keywords = array('public','protected','private','function','for','foreach','static','self','parent','extends','implements','if','else','elseif','return','break','continue','class');
	protected $do_keywords = true;
	protected $do_numbers = true;
	protected $do_strings1 = true;
	protected $do_strings2 = true;
	protected $do_comments_sharp = true;
	protected $do_comments_slashes = true;
	protected $do_comments_block = true;
	
	protected function lex() {
		$c = parent::lex();
		if ($c=='$') {
			$id = $this->lex_identifier();
			return $this->tag_id_start . '$' . $id . $this->tag_id_end;
		}
		else return $c;
	}
	
	
	
} 

