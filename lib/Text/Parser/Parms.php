<?php
/// <module name="CMS.Parser" maintainer="gusev@techart.ru" version="0.0.0">

Core::load('Text.Process');

/// <class name="CMS.Parser" stereotype="module">
///   <implements interface="Core.ModuleInterface" />

class Text_Parser_Parms implements Core_ModuleInterface, Text_Process_ProcessInterface, Text_Process_UnparseInterface { 
	const VERSION = '0.0.0'; 
	
	protected $lines; 
	protected $cursor; 
	protected $out; 
	protected $error_found; 
	protected $error_message; 

///   <protocol name="processing">


///   <method name="unparse" returns="string">
///     <args>
///       <arg name="source" type="iterable" />
///     </args>
///     <body>
	public function unparse($src) {
		return $this->unparse_array($src,'');
	}
///     </body>
///   </method>

  public function configure($config) {}

  public function process($s) {return $this->parse($s);}

///   <method name="parse" returns="array">
///     <args>
///       <arg name="source" type="string" />
///     </args>
///     <body>
	public function parse($src) { 
		$this->lines = ''; 
		$m = explode("\n",$src); 
		
		foreach($m as $line) { 
			$line = trim($line); 
			if ($line!='') { 
				if ($line[0]!='#') $this->lines[] = $line; 
			} 
		} 
		
		$this->cursor = 0; 
		$this->out = array(); 
		$this->error_found = false; 
		while (!$this->eof()) { 
			$rc = $this->parse_statement($this->out); 
			if ($rc=='}') return 'Обнаружен неожиданный символ "{"'; 
			if ($this->error_found) return $this->error_message; 
		} 
		
		return $this->out; 
	} 
///     </body>
///   </method>



///   </protocol>	
	
///   <protocol name="supporting">



///   <method name="unparse_array" returns="string">
///     <body>
	protected function unparse_array($src,$prefix) {
		if (Core_Types::is_iterable($src)) {
			$out = '';
			foreach($src as $key => $value) {
				if (Core_Types::is_iterable($value)) {
					$value = $this->unparse_array($value,"\t$prefix");
					$out .= "$prefix$key = {\n$value$prefix}\n";
				}

				else {
					$value = (string)$value;
					$out .= "$prefix$key = $value\n";
				}
			}
			return $out;
		}

		else return (string)$src;
	}
///     </body>
///   </method>



///   <method name="eof" returns="boolean">
///     <body>
	protected function eof() { 
		return !isset($this->lines[$this->cursor]); 
	} 
///     </body>
///   </method>
	
///   <method name="get_line" returns="string">
///     <body>
	protected function get_line() { 
		$line = $this->lines[$this->cursor]; 
		$this->cursor++; return $line; 
	} 
///     </body>
///   </method>
	
///   <method name="error">
///     <args>
///       <arg name="message" type="string" />
///     </args>
///     <body>
	protected function error($message) { 
		$this->error_found = true; 
		$this->error_message = $message; 
	} 
///     </body>
///   </method>
	
///   <method name="parse_compound">
///     <args>
///       <arg name="dest" type="array" />
///     </args>
///     <body>
	protected function parse_compound(&$out) { 
		$end = false; 
		while (!$end) { 
			$rc = $this->parse_statement($out); 
			if ($this->error_found) return; 
			if ($rc=='}') return; 
			if ($this->eof()) { 
				$this->error('Не закончен блок'); 
				return; 
			} 
		} 
	} 
///     </body>
///   </method>
	
///   <method name="parse_statement">
///     <args>
///       <arg name="dest" type="array" />
///     </args>
///     <body>
	protected function parse_statement(&$out) { 
		if ($this->error_found) return; 
		$line = $this->get_line(); 
		
		if ($line=='}') return '}'; 
		
		else if (preg_match('/^([^=]+)=\s*{$/',$line,$m)) { 
			$key = trim($m[1]); 
			$out[$key] = array(); 
			$this->parse_compound($out[$key]); 
		} 
		
		else if (preg_match('/^([^=]+)=(.+)$/',$line,$m)) { 
			$key = trim($m[1]); 
			$val = trim($m[2]); 
			$out[$key] = $val; 
		} 
		
		else { 
			$key = $line; 
			$val = ''; 
			$out[$key] = $val; 
		} 
		
	} 
///     </body>
///   </method>
	
///   </protocol>	
	
} 
/// </class>

/// </module>

