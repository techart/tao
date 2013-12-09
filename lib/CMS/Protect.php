<?php
/**
 * CMS.Protect
 * 
 * @package CMS\Protect
 * @version 0.0.0
 */

Core::load('Validation.Commons'); 
Core::load('Net.HTTP.Session');


/**
 * @package CMS\Protect
 */
class CMS_Protect implements Core_ModuleInterface { 
	
	const MODULE = 'CMS.Protect'; 
	const VERSION = '0.0.0'; 
	const FONT = "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"; 
	
	static $foreground = false; 
	static $background = false;
	static $noise = 0; 
	static $lines = 0; 
	static $width = 100; 
	static $height = 50; 
	static $owidth = 100; 
	static $oheight = 50; 
	

/**
 * @param array $config
 */
	static function initialize($config=array()) { 
		foreach($config as $key => $value) self::$$key = $value; 
	} 
	

	



/**
 * @param string $page
 */
	static function key($page='') {
		$page = trim($page, '/');
		$session = Net_HTTP_Session::Store();
		$name = 'digital-protect/keystring'.$page;
		$key = isset($session[$name])? trim($session[$name]) : '';
		if ($key=='') {
			$key = self::generate_key();
			$session[$name] = $key;
		}
		return $key; 
	} 


/**
 */
	static function generate_key() {
		$keylength = self::key_length();
		$key = '';
		for ($i=0; $i<$keylength; $i++) $key .= mt_rand(0,9);
		return $key;
	}


/**
 */
	static function key_length() {
		$k = isset($_GET['length'])? (int)$_GET['length'] : 0;
		if ($k<1) $k = 5;
		return $k;
	}


/**
 */
	static function check($name,$page) {
		$key = self::key($page);
		$name = trim($name);
		$value = trim($_POST[$name]);
		if ($value!='') {
			if ($value==$key) {
				print 'ok';
				return;
			}
		}
		print 'error';
	}


/**
 * @param string $field
 * @param string $page
 */
	public function jsprotect($field,$page='') { 
		$keystring = self::key($page);
		ob_start();
		if (IO_FS::exists('../app/views/jsprotect.phtml')) include('../app/views/jsprotect.phtml');
		else include(CMS::view('jsprotect.phtml'));
		$content = ob_get_clean();
		return $content;
	}
	
/**
 * @param string $page
 */
	public function draw($page='') { 
		$foreground_color = self::$foreground; 
		$background_color = self::$background;
		if ($foreground_color===false) $foreground_color = array(0,0,0); else $foreground_color = self::convert_color($foreground_color); 
		if ($background_color===false) $background_color = array(200,200,200); else $background_color = self::convert_color($background_color);

		if (isset($_GET['bg']) && $m = Core_Regexps::match_with_results('/^([0-9a-f]{2})([0-9a-f]{2})([0-9a-f]{2})$/i',$_GET['bg'])) {
			$background_color = array(hexdec($m[1]),hexdec($m[2]),hexdec($m[3])); 
		} 
		
		if (isset($_GET['fg']) && $m = Core_Regexps::match_with_results('/^([0-9a-f]{2})([0-9a-f]{2})([0-9a-f]{2})$/i',$_GET['fg'])) {
			$foreground_color = array(hexdec($m[1]),hexdec($m[2]),hexdec($m[3])); 
		} 
		
		while (true) { 
			$keystring = self::key($page);
			$keylength = self::key_length();
			$font = imagecreatefromstring(base64_decode(self::FONT));
			imagealphablending($font, true); 
			$fontfile_width = imagesx($font); 
			$fontfile_height = imagesy($font)-1; 
			
			$font_metrics = array(); 
			$symbol = 0; 
			$reading_symbol = false; 
			for ($i = 0; $i<$fontfile_width && $symbol<10; $i++) { 
				$transparent = (imagecolorat($font, $i, 0) >> 24) == 127; 
				if (!$reading_symbol && !$transparent) { 
					$font_metrics[$symbol] = array('start'=>$i); 
					$reading_symbol=true; continue; 
				} 
				
				if ($reading_symbol && $transparent) { 
					$font_metrics[$symbol]['end']=$i; 
					$reading_symbol=false; $symbol++; continue; 
				} 
			} 
			
			$img = imagecreatetruecolor(self::originalWidth(), self::originalHeight()); 
			imagealphablending($img, true); 
			$white = imagecolorallocate($img, 255, 255, 255); 
			$black = imagecolorallocate($img, 0, 0, 0); 
			imagefilledrectangle($img, 0, 0, self::originalWidth()-1, self::originalHeight()-1, $white); 
			$x = 1; 
			for($i=0; $i<$keylength; $i++) { 
				$m = $font_metrics[$keystring{$i}]; 
				$y = mt_rand(-5, 5)+(self::originalHeight()-$fontfile_height)/2+2; 
				$shift = 2; 
				imagecopy($img,$font,$x-$shift,$y,$m['start'],1,$m['end']-$m['start'],$fontfile_height); 
				$x += $m['end']-$m['start']-$shift; 
			} 
			
			if ($x<self::originalWidth()-10) break; 
		} 

		$center = $x/2;
		$img2 = imageCreateTrueColor(self::originalWidth(), self::originalHeight()); 
		$rand1 = mt_rand(750000,1200000)/10000000; 
		$rand2 = mt_rand(750000,1200000)/10000000; 
		$rand3 = mt_rand(750000,1200000)/10000000; 
		$rand4 = mt_rand(750000,1200000)/10000000; 
		$rand5 = mt_rand(0,3141592)/500000; 
		$rand6 = mt_rand(0,3141592)/500000; 
		$rand7 = mt_rand(0,3141592)/500000; 
		$rand8 = mt_rand(0,3141592)/500000; 
		$rand9 = mt_rand(330,420)/110; 
		$rand10 = mt_rand(330,450)/110; 
		

		for ($x=0; $x<self::originalWidth(); $x++) { 
			for ($y=0; $y<self::originalHeight(); $y++) { 
				$sx = $x+(sin($x*$rand1+$rand5)+sin($y*$rand3+$rand6))*$rand9-self::originalWidth()/2+$center+1; 
				$sy = $y+(sin($x*$rand2+$rand7)+sin($y*$rand4+$rand8))*$rand10; 
				
				if ($sx<0 || $sy<0 || $sx>=self::originalWidth()-1 || $sy>=self::originalHeight()-1) { 
					$color = 255; $color_x = 255; $color_y = 255; $color_xy = 255; 
				} 
				
				else { 
					$color = imagecolorat($img, $sx, $sy) & 0xFF; 
					$color_x = imagecolorat($img, $sx+1, $sy) & 0xFF; 
					$color_y = imagecolorat($img, $sx, $sy+1) & 0xFF; 
					$color_xy = imagecolorat($img, $sx+1, $sy+1) & 0xFF; 
				} 
				
				if ($color==0 && $color_x==0 && $color_y==0 && $color_xy==0) { 
					$newred = $foreground_color[0]; 
					$newgreen = $foreground_color[1]; 
					$newblue = $foreground_color[2]; 
				} 
				
				else if ($color==255 && $color_x==255 && $color_y==255 && $color_xy==255) { 
					$newred = $background_color[0]; 
					$newgreen = $background_color[1]; 
					$newblue = $background_color[2]; 
				} 
				
				else { 
					$frsx = $sx-floor($sx); 
					$frsy = $sy-floor($sy); 
					$frsx1 = 1-$frsx; 
					$frsy1 = 1-$frsy; 
					$newcolor = ( $color*$frsx1*$frsy1+ $color_x*$frsx*$frsy1+ $color_y*$frsx1*$frsy+ $color_xy*$frsx*$frsy ); 
					if ($newcolor>255) $newcolor=255; 
					$newcolor = $newcolor/255; 
					$newcolor0 = 1-$newcolor; 
					
					$newred = $newcolor0*$foreground_color[0]+$newcolor*$background_color[0]; 
					$newgreen = $newcolor0*$foreground_color[1]+$newcolor*$background_color[1]; 
					$newblue = $newcolor0*$foreground_color[2]+$newcolor*$background_color[2]; 
				
				} 
				
				imagesetpixel($img2, $x, $y, imagecolorallocate($img2, $newred, $newgreen, $newblue)); 
			} 
		}
		for($i=0;$i<self::$lines;$i++) {
			$n = mt_rand(0,100);
			if ($n<50) {
				$y1 = mt_rand(0,self::originalHeight()/2);
				$y2 = mt_rand(self::originalHeight()/2,self::originalHeight());
			}
			else {
				$y1 = mt_rand(self::originalHeight()/2,self::originalHeight());
				$y2 = mt_rand(0,self::originalHeight()/2);
			}
			$c = $foreground_color;
			imageline($img2, 0, $y1, self::originalWidth()-1,$y2,imagecolorallocate($img2, $c[0], $c[1], $c[2])); 
		}
		
		for($i=0;$i<self::$noise;$i++) {
			$x = mt_rand(0,self::originalWidth());
			$y = mt_rand(0,self::originalHeight());
			$n = mt_rand(-30,30);
			$c = $foreground_color;
			$c[0] += $n; if ($c[0]>255) $c[0] = 255; if ($c[0]<0) $c[0] = 0; 
			$c[1] += $n; if ($c[1]>255) $c[1] = 255; if ($c[1]<0) $c[1] = 0; 
			$c[2] += $n; if ($c[2]>255) $c[2] = 255; if ($c[2]<0) $c[2] = 0; 
			imagesetpixel($img2, $x, $y, imagecolorallocate($img2, $c[0], $c[1], $c[2])); 
		}

		if (self::getWidth()!=self::originalWidth()||self::getHeight()!=self::originalHeight()) {
			$img = imageCreateTrueColor(self::getWidth(), self::getHeight());; 
			imagecopyresampled($img,$img2,0,0,0,0,self::getWidth(),self::getHeight(),self::originalWidth(),self::originalHeight());
			$img2 = $img;
		}
		

		if (function_exists("imagejpeg")) {
			header("Content-Type: image/jpeg"); 
			imagejpeg($img2, null, 90); 
		} 
		
		else if (function_exists("imagegif")) { 
			header("Content-Type: image/gif"); 
			imagegif($img2); 
		} 
		
		else if (function_exists("imagepng")) { 
			header("Content-Type: image/x-png"); 
			imagepng($img2); 
		} 
	} 

	




/**
 * @return int
 */
	public function getWidth() { 
		return self::$width; 
	} 
	
/**
 * @return int
 */
	public function getHeight() { 
		return self::$height; 
	} 
	
/**
 * @return int
 */
	static function originalWidth() {
		return self::$owidth; 
	}
	
/**
 * @return int
 */
	static function originalHeight() {
		return self::$oheight; 
	}			
	
/**
 * @param string $color
 * @return array
 */
	static function hex_color($color) {
		$color = trim($color);
		if ($color=='') $color = '000000';
		else if (strlen($color)==3) $color = $color[0].'0'.$color[1].'0'.$color[2].'0';
		else if (strlen($color)!=6) $color = $color[0].'0'.$color[0].'0'.$color[0].'0';
		$color = strtoupper($color);
		$color = array(hexdec(substr($color,0,2)),hexdec(substr($color,2,2)),hexdec(substr($color,4,2)));
		return $color;
	}
	
/**
 * @param string $color
 * @return array
 */
	static function convert_color($color) {
		if (is_string($color)) {
			$color = trim($color);
			if ($m = Core_Regexps::match_with_results('{^#([0-9a-f]+)$}i',$color)) return self::hex_color($m[1]);
		
			switch(strtolower($color)) {
				case 'random':
					return array(mt_rand(0,255),mt_rand(0,255),mt_rand(0,255));
					break;
				case 'lightrandom':
					return array(mt_rand(180,255),mt_rand(180,255),mt_rand(180,255));
					break;
				case 'darkrandom':
					return array(mt_rand(0,120),mt_rand(0,120),mt_rand(0,120));
					break;
			}
		}
		return $color;	
	}
	
	

} 


			
class CMS_Protect_ValidationTest extends Validation_AttributeTest { 

	protected $page; 
			
	public function __construct($attribute,$message,$page='') { 
		$this->page = $page; 
		parent::__construct($attribute,$message); 
	} 
	
	protected function do_test($value) { 
		return true; 
	} 

} 

