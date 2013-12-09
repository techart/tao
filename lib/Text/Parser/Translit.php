<?php
/**
 * @package Text\Parser\Translit
 */


Core::load('Text.Process');

class Text_Parser_Translit implements Core_ModuleInterface, Text_Process_ProcessInterface { 
	const VERSION = '0.1.0'; 
	
	static $letters = array(
		'А' => 'A',
		'Б' => 'B',
		'В' => 'V',
		'Г' => 'G',
		'Д' => 'D',
		'Е' => 'E',
		'Ё' => 'Yo',
		'Ж' => 'Zh',
		'З' => 'Z',
		'И' => 'I',
		'Й' => 'I',
		'К' => 'K',
		'Л' => 'L',
		'М' => 'M',
		'Н' => 'N',
		'О' => 'O',
		'П' => 'P',
		'Р' => 'R',
		'С' => 'S',
		'Т' => 'T',
		'У' => 'U',
		'Ф' => 'F',
		'Х' => 'H',
		'Ц' => 'Ts',
		'Ч' => 'Ch',
		'Ш' => 'Sh',
		'Щ' => 'Sch',
		'Ъ' => '',
		'Ы' => 'Y',
		'Ь' => '',
		'Э' => 'E',
		'Ю' => 'Yu',
		'Я' => 'Ya',
	);
	
	function configure($config) {}
	
	function process($s) {return self::run($s);}
	
	static function run($s) {
		foreach(self::$letters as $rus => $lat) {
			$s = str_replace($rus,$lat,$s);  
			$s = str_replace(mb_strtolower($rus),mb_strtolower($lat),$s);  
		}
		return $s;
	} 
}

