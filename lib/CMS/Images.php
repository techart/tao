<?php

class CMS_Images implements Core_ModuleInterface
{

	const MODULE = 'CMS.Images';
	const VERSION = '0.0.0';

	static $class = false;
	static $gisz = array();
	static $jpeg_quality = 80;
	static $png_quality = 5;
	static $png_filters = PNG_NO_FILTER;

	static $default_watermark_image = './image/watermark.png';
	static $default_watermark_parms = array('mode' => 5, 'opacity' => 1);
	static $cache_dir = 'files/cache/images';

	/**
	 * @param array $config
	 *
	 */
	static function initialize($config = array())
	{
		foreach ($config as $key => $value)
			self::$$key = $value;
	}

	static function Image($file = false)
	{
		$class = 'CMS.Images.GD';
		if (self::$class) {
			$class = self::$class;
		}
		//else if (class_exists('Imagick')) $class = 'CMS.Image.Imagick';
		$im = Core::make($class);
		if ($file) {
			$im->load($file);
		}
		return $im;
	}

	static function get_image_size($filename)
	{
		if (isset(self::$gisz[$filename])) {
			return self::$gisz[$filename];
		}
		$sz = @getImageSize($filename);
		self::$gisz[$filename] = $sz;
		return $sz;
	}

	static function size($filename)
	{
		$sz = self::get_image_size($filename);
		return array($sz[0], $sz[1]);
	}

	static function string2sizes($src)
	{
		$w = false;
		$h = false;
		$src = preg_replace('{\s+}', '', $src);
		$src = trim($src);
		if ($src != '') {
			if ($m = Core_Regexps::match_with_results('{(\d+)x(\d+)}i', $src)) {
				$w = (int)$m[1];
				$h = (int)$m[2];
			} else {
				if ($m = Core_Regexps::match_with_results('{(\d+)x}i', $src)) {
					$w = (int)$m[1];
				}
			}
			if ($m = Core_Regexps::match_with_results('{x(\d+)}i', $src)) {
				$h = (int)$m[1];
			}
		}
		return array($w, $h);
	}

	static function parse_modifiers($s)
	{
		$out = array();
		if (empty($s)) {
			return $out;
		}
		if (is_array($s)) {
			return $s;
		}
		foreach (explode(';', (string)$s) as $mod) {
			if ($m = self::parse_modifier($mod)) {
				$out[] = $m;
			}
		}
		return $out;
	}

	static function parse_modifier($s)
	{
		$s = preg_replace('{\s+}', '', $s);
		if ($s != '') {
			if ($s == 'grayscale') {
				return array('action' => 'grayscale');
			}
			if ($m = self::parse_sizes($s)) {
				$w = $m[0] > 0 ? $m[0] : 100000;
				$h = $m[1] > 0 ? $m[1] : 100000;
				return array('action' => 'fit', 'width' => $w, 'height' => $h);
			}
			if ($m = Core_Regexps::match_with_results('{^(fit|resize|margins|crop)\(([^\(\)]+)\)$}i', $s)) {
				$action = $m[1];
				$color = '#ffffff';
				$args = $m[2];
				if ($m = Core_Regexps::match_with_results('{^(.+)(#[a-f0-9]+)$}i', $args)) {
					$args = $m[1];
					$color = $m[2];
				}
				$sz = self::parse_sizes($args);
				if ($sz) {
					$w = $sz[0];
					$h = $sz[1];
					if ($action == 'fit') {
						$w = $w == 0 ? 100000 : $w;
						$h = $h == 0 ? 100000 : $h;
					}
					if ($w > 0 && $h > 0) {
						return array('action' => $action, 'width' => $w, 'height' => $h, 'color' => $color);
					}
				}
			}
			if (preg_match('{^([a-z_]+)(\(([^)]*)\))?$}i', $s, $m)) {
				$method = $m[1];
				$args_str = $m[3];
				$args = array_filter(explode(',', $args_str));
				return array_merge($args, array('action' => $method));
			}
		}
		return false;
	}

	static function parse_sizes($s)
	{
		$s = preg_replace('{^\s+}', '', $s);
		if ($m = Core_Regexps::match_with_results('{^(\d+)x(\d+)$}i', $s)) {
			return array($m[1], $m[2]);
		}
		if ($m = Core_Regexps::match_with_results('{^(\d+)x$}i', $s)) {
			return array($m[1], 0);
		}
		if ($m = Core_Regexps::match_with_results('{^x(\d+)$}i', $s)) {
			return array(0, $m[1]);
		}
		return false;
	}

	static function modified_image($path, $mods)
	{
		if (is_string($mods)) {
			$mods = self::parse_modifiers($mods);
		}
		$ext = false;
		$name = false;
		if ($m = Core_Regexps::match_with_results('{(.+)\.(jpe?g|gif|png|bmp)$}i', $path)) {
			$name = $m[1];
			$ext = strtolower($m[2]);
		}
		if (!$ext || !IO_FS::exists($path)) {
			$path = '../tao/files/images/admin.gif';
			$ext = 'gif';
			$name = 'error';
		}

		$name = preg_replace('{^\.\./}', 'up/', $name);
		$name = ltrim($name, '.');
		$name = ltrim($name, '/');

		$stat = IO_FS::Stat($path);
		$mtime = $stat->mtime->timestamp;
		$name .= "-{$stat->size}-{$mtime}-";
		$name .= md5(serialize($mods));

		$cache = self::$cache_dir . "/{$name}.{$ext}";
		if (IO_FS::exists('./' . $cache)) {
			return '/' . $cache;
		}
		$dir = preg_replace('{/([^/]+)$}', '', $cache);
		if (!IO_FS::exists($dir)) {
			IO_FS::mkdir($dir);
		}
		$image = self::Image($path);
		$image->modify($mods);
		$image->save('./' . $cache);
		CMS::chmod_file('./' . $cache);
		return '/' . $cache;
	}
}

class CMS_Images_Image
{

	protected $ih = false;
	protected $loaded_format = false;

	public function __construct()
	{
	}

	public function load($file)
	{
	}

	public function resize($w, $h, $fit = true)
	{
		return $this;
	}

	public function image($format = 'jpg')
	{
		return (string)$this->ih;
	}

	public function out($format = 'jpg')
	{
		header("Content-type: image/$format");
		print $this->image($format);
	}

	public function save($file, $format = 'jpg')
	{
		IO_FS::File($file)->update($this->image($format));
		return $this;
	}

	protected function get_file_format($file)
	{
		$ext = $this->get_file_format_exif($file);
		if ($ext) {
			return $ext;
		}

		if ($m = Core_Regexps::match_with_results('{\.(jpg|gif|png|jpeg|bmp)$}i', $file)) {
			$ext = strtolower($m[1]);
			if ($ext == 'jpeg') {
				$ext = 'jpg';
			}
		}

		return $ext;
	}

	protected function get_file_format_exif($file)
	{
		if (function_exists('exif_imagetype')) {
			$type = exif_imagetype($file);
			switch ($type) {
				case IMAGETYPE_GIF: return 'gif';
				case IMAGETYPE_JPEG: return 'jpg';
				case IMAGETYPE_PNG: return 'png';
				case IMAGETYPE_BMP: return 'bmp';
			}
		}

		return false;
	}

	protected function validate_size(&$w, &$h)
	{

		if ($w == 0 && $h == 0) {
			$w = 1;
			$h = 1;
			return;
		}

		if ($h == 0) {
			$d = $this->width() / $w;
			$h = (int)ceil($this->height() / $d);
			return;
		}

		if ($w == 0) {
			$d = $this->height() / $h;
			$w = (int)ceil($this->width() / $d);
			return;
		}
	}

	protected function csstring2array($parms)
	{
		$parms_tmp = explode(';', $parms);
		$parms = array();

		foreach ($parms_tmp as $pitem) {
			if (preg_match('{^([^:]+)\:(.+)$}', $pitem, $m)) {
				$parms[trim($m[1])] = trim($m[2]);
			}
		}

		return $parms;
	}

	protected function get_position_value($value, $_p, $p)
	{
		$res = 0;
		if (preg_match('{(\-?\d*)(.*)}', $value, $m)) {
			$m[1] = trim($m[1]);
			switch (trim($m[2])) {
				case 'center':
					$res = floor(($_p - $p) / 2);
					break;
				case '%':
					$res = $_p * $m[1] / 100;
					break;
				default:
				case 'px':
					$res = $m[1];
					break;
			}

		}

		return $res;
	}

	protected function watermark_parms(&$parms, $_w, $_h, $w, $h)
	{
		$nl = 0;
		$nt = 0;
		$opacity = 1.0;

		if (is_string($parms)) {
			if (preg_match('{^([1-9])$}', $parms, $m)) {
				$parms = array('mode' => $m[1], 'opacity' => '0.2');
			} else {
				$parms = $this->csstring2array($parms);
			}
		}

		if (is_array($parms)) {
			if (preg_match('{^[1-9]$}', $parms['mode'])) {
				if ($parms['mode'] <= 3) {
					$nt = $_h - $h;
				}
				if ($parms['mode'] >= 4 && $parms['mode'] <= 6) {
					$nt = (int)(($_h - $h) / 2);
				}
				if (!($parms['mode'] % 3)) {
					$nl = $_w - $w;
				}
				if (!(($parms['mode'] + 1) % 3)) {
					$nl = (int)(($_w - $w) / 2);
				}
			} else {

				if ($parms['left']) {
					$nl = $this->get_position_value($parms['left'], $_w, $w);
				} elseif (isset($parms['right'])) {
					$nl = $_w - $w - $this->get_position_value($parms['right'], $_w, $w);
				}
				if ($parms['top']) {
					$nt = $this->get_position_value($parms['top'], $_h, $h);
				} elseif (isset($parms['bottom'])) {
					$nt = $_h - $h - $this->get_position_value($parms['bottom'], $_h, $h);
				}
			}
			if (isset($parms['opacity'])) {
				$opacity = (float)$parms['opacity'];
			} else {
				$opacity = 0.2;
			}
		}

		return array(round($nl), round($nt), $opacity);
	}

	public function modify($mods = array())
	{
		if (!$this->ih) {
			return $this;
		}
		foreach ($mods as $name => $mod) {
			if (isset($mod['action'])) {
				$action = $mod['action'];

				if ($action == 'fit') {
					$this->fit($mod['width'], $mod['height']);
				} else {
					if ($action == 'resize') {
						$this->resize($mod['width'], $mod['height']);
					} else {
						if ($action == 'crop') {
							$this->crop($mod['width'], $mod['height']);
						} else {
							if ($action == 'margins') {
								$this->fit_with_margins($mod['width'], $mod['height'], $mod['color']);
							} else {
								if ($action == 'grayscale') {
									$this->grayscale();
								} else {
									if ($action == 'watermark') {
										$wimage = $mod['image'];
										$wparms = $mod['parms'];
										$this->watermark($wimage, $wparms);
									}
								}
							}
						}
					}
				}
			} else {
				if (is_array($mod)) {
					call_user_func_array(array($this, $name), $mod);
				}
			}
		}
		return $this;
	}

	public function margins()
	{
		if (method_exists($this, 'fit_with_margins')) {
			$args = func_get_args();
			return call_user_func_array(array($this, 'fit_with_margins'), $args);
		}
		return $this;
	}

	protected function is_stretch($w, $h)
	{
		$_w = $this->width();
		$_h = $this->height();
		if (($w >= $_w || 0 == $w) && ($h >= $_h || 0 == $h)) {
			return true;
		}
		return false;
	}

}

class CMS_Images_GD extends CMS_Images_Image
{

	public function load($file)
	{

		$ext = $this->get_file_format($file);
		if (!$ext) {
			return false;
		}
		$this->loaded_format = $ext;

		switch ($ext) {
			case 'jpg':
				$this->ih = @imagecreatefromjpeg($file);
				break;
			case 'gif':
				$this->ih = @imagecreatefromgif($file);
				break;
			case 'png':
				$this->ih = @imagecreatefrompng($file);
				imagealphablending($this->ih, false);
				imagesavealpha($this->ih, true);
				break;
			case 'bmp':
				$this->ih = @imagecreatefromwbmp($file);
				break;
		}

		return $this;
	}

	public function turn_right()
	{
		$this->ih = imagerotate($this->ih, -90, 0);
		return $this;
	}

	public function turn_left()
	{
		$this->ih = imagerotate($this->ih, 90, 0);
		return $this;
	}

	public function turn_over()
	{
		$this->ih = imagerotate($this->ih, 180, 0);
		return $this;
	}

	public function save($file, $format = false)
	{
		if (!$format) {
			$format = $this->loaded_format;
		}

		if (!$this->ih) {
			return $this;
		}

		switch ($format) {
			case 'gif':
				imagegif($this->ih, $file);
				break;
			case 'bmp':
				imagewbmp($this->ih, $file);
				break;
			case 'png':
				imagepng($this->ih, $file, CMS_Images::$png_quality, CMS_Images::$png_filters);
				break;
			default:
				imagejpeg($this->ih, $file, CMS_Images::$jpeg_quality);

		}
		IO_FS::File($file)->set_permission();
		return $this;
	}

	public function out($format = false)
	{
		if (!$format) {
			$format = $this->loaded_format;
		}

		switch ($format) {
			case 'gif':
				header("Content-type: image/gif");
				imagegif($this->ih);
				break;
			case 'bmp':
				header("Content-type: image/bmp");
				imagewbmp($this->ih);
				break;
			case 'png':
				header("Content-type: image/png");
				imagepng($this->ih, null, CMS_Images::$png_quality, CMS_Images::$png_filters);
				break;
			default:
				header("Content-type: image/jpg");
				imagejpeg($this->ih, null, CMS_Images::$jpeg_quality);

		}

		return $this;
	}

	public function width()
	{
		return imagesx($this->ih);
	}

	public function height()
	{
		return imagesy($this->ih);
	}

	public function resize($w, $h, $fit = false)
	{
		if ($fit) {
			return $this->fit($w, $h);
		}
		$_w = $this->width();
		$_h = $this->height();
		if ($w == $_w && $h == $_h) {
			return $this;
		}

		$new = imagecreatetruecolor($w, $h);
		imagecolortransparent($new, imagecolortransparent($this->ih));
		if ($this->loaded_format == 'png') {
			imagealphablending($new, false);
			imagesavealpha($new, true);
		}
		imagecopyresampled($new, $this->ih, 0, 0, 0, 0, $w, $h, $_w, $_h);
		imagedestroy($this->ih);
		$this->ih = $new;

		return $this;
	}

	public function fit($w, $h)
	{
		if ($this->is_stretch($w, $h)) {
			return $this;
		}
		$_w = $this->width();
		$_h = $this->height();

		$nw = $_w;
		$nh = $_h;

		if ($nw > $w && $w > 0) {
			$d = $nw / $w;
			$nw = $w;
			$nh = (int)ceil($nh / $d);
		}

		if ($nh > $h && $h > 0) {
			$d = $nh / $h;
			$nh = $h;
			$nw = (int)ceil($nw / $d);
		}

		$new = imagecreatetruecolor($nw, $nh);
		imagecolortransparent($new, imagecolortransparent($this->ih));
		if ($this->loaded_format == 'png') {
			imagealphablending($new, false);
			imagesavealpha($new, true);
		}
		imagecopyresampled($new, $this->ih, 0, 0, 0, 0, $nw, $nh, $_w, $_h);
		imagedestroy($this->ih);
		$this->ih = $new;

		return $this;
	}

	public function fit_with_margins($w, $h, $c = 0xFFFFFF)
	{
		$c = $this->color2int($c);
		return $this->resize_prop($w, $h, false, $c);
	}

	protected function color2int($c)
	{
		if (is_string($c)) {
			preg_match('/^\#?([0-9a-f]{1,2})([0-9a-f]{1,2})([0-9a-f]{1,2})/i', $c, $m);
			$ac = array(hexdec(strlen($m[1]) == 1 ? $m[1] . $m[1] : $m[1]), hexdec(strlen($m[2]) == 1 ? $m[2] . $m[2] : $m[2]), hexdec(strlen($m[3]) == 1 ? $m[3] . $m[3] : $m[3]));
		} elseif (is_array($c)) {
			$ac = array((int)$c[0], (int)$c[1], (int)$c[2]);
		}

		if (is_int($c)) {
			$nc = $c;
		} elseif ($ac) {
			$nc = $ac[0] * 256 * 256 + $ac[1] * 256 + $ac[2];
		}

		return (int)$nc;
	}

	public function crop($w, $h)
	{
		if ($this->is_stretch($w, $h)) {
			return $this;
		}
		return $this->resize_prop($w, $h);
	}

	public function resize_prop($w, $h, $crop = true, $c = 0xFFFFFF)
	{
		if ($w == 0 || $h == 0) {
			return $this;
		}

		$_w = $this->width();
		$_h = $this->height();

		$nw = $_w;
		$nh = $_h;

		$sw = $w / $_w;
		$sh = $h / $_h;
		$s = $sh >= $sw ? true : false;
		if ($crop) {
			$s = !$s;
		}

		if ($s) {
			$d = $nw / $w;
			$nw = $w;
			$nh = (int)ceil($nh / $d);
		} else {
			$d = $nh / $h;
			$nh = $h;
			$nw = (int)ceil($nw / $d);
		}

		$nl = floor(($w - $nw) / 2);
		$nt = floor(($h - $nh) / 2);

		$new = imagecreatetruecolor($w, $h);
		imagecolortransparent($new, imagecolortransparent($this->ih));
		if ($this->loaded_format == 'png') {
			imagealphablending($new, false);
			imagesavealpha($new, true);
		}
		if (!$crop) {
			imagefilledrectangle($new, 0, 0, $w, $h, $c);
		}
		imagecopyresampled($new, $this->ih, $nl, $nt, 0, 0, $nw, $nh, $_w, $_h);
		imagedestroy($this->ih);
		$this->ih = $new;

		return $this;
	}

	public function watermark($image = false, $parms = false)
	{
		if (!$image) {
			$image = CMS_Images::$default_watermark_image;
		}
		if (!$parms) {
			$parms = CMS_Images::$default_watermark_parms;
		}
		if (is_string($image)) {
			$image = preg_replace('{^/?}', '', $image);

			$ext = $this->get_file_format($image);

			if (!$ext) {
				return $this;
			}

			switch ($ext) {
				case 'jpg':
					$watermark = @imagecreatefromjpeg($image);
					break;
				case 'gif':
					$watermark = @imagecreatefromgif($image);
					break;
				case 'png':
					@imagealphablending($this->ih, true);
					$watermark = @imagecreatefrompng($image);
					break;
				case 'bmp':
					$watermark = @imagecreatefromwbmp($image);
					break;
			}

			list($w, $h) = CMS_Images::size($image);

		} elseif (is_object($image) && $image instanceof CMS_Image_GD) {
			$watermark =& $image->ih;

			$w = $image->width();
			$h = $image->height();
		}

		$_w = $this->width();
		$_h = $this->height();

		list($nl, $nt, $opacity) = $this->watermark_parms($parms, $_w, $_h, $w, $h);

		if ($opacity == 1) {
			@imagecopy($this->ih, $watermark, $nl, $nt, 0, 0, $w, $h);
		} else {
			@imagecopymerge($this->ih, $watermark, $nl, $nt, 0, 0, $w, $h, (int)($opacity * 100));
		}
		
		if ($this->loaded_format == 'png') {
			@imagealphablending($this->ih, false);
		}

		return $this;
	}

	public function grayscale()
	{
		imagefilter($this->ih, IMG_FILTER_GRAYSCALE);

		return $this;
	}
}

class CMS_Image_GD extends CMS_Images_GD
{
}

class CMS_Images_Imagick extends CMS_Images_Image
{

	public function load($file)
	{

		$ext = $this->get_file_format($file);
		if (!$ext) {
			return false;
		}
		$this->loaded_format = $ext;

		$this->ih = new Imagick($file);
		return $this;
	}

	public function save($file, $format = false)
	{
		$this->ih->setImageCompressionQuality(CMS_Images::$jpeg_quality);
		if ($format == 'jpg' || $format == 'gif' || $format == 'png') {
			$this->ih->setImageFormat($format);
		}
		parent::save($file);
	}

	public function resize($w, $h, $fit = false)
	{
		$this->validate_size($w, $h);
		$this->ih->thumbnailImage($w, $h, $fit);
		return $this;
	}

	public function fit($w, $h, $fit = true)
	{
		if ($this->is_stretch($w, $h)) {
			return $this;
		}
		return $this->resize($w, $h, true);
	}

	public function width()
	{
		return $this->ih->getImageWidth();
	}

	public function height()
	{
		return $this->ih->getImageHeight();
	}

	public function crop($w, $h)
	{
		if ($this->is_stretch($w, $h)) {
			return $this;
		}
		if ($w == 0 || $h == 0) {
			return $this;
		}

		$nw = $this->width();
		$nh = $this->height();

		if (($nw / $w) < ($nh / $h)) {
			$this->ih->cropImage($nw, ceil($h * $nw / $w), 0, floor(($nh - ($h * $nw / $w)) / 2));
		} else {
			$this->ih->cropImage(ceil($w * $nh / $h), $nh, floor(($nw - ($w * $nh / $h)) / 2), 0);
		}

		$this->ih->thumbnailImage($w, $h, false);

		return $this;
	}

	public function fit_with_margins($w, $h, $c = '#FFFFFF')
	{
		if ($w == 0 || $h == 0) {
			return $this;
		}
		$c = $this->color2string($c);

		$nw = $this->width();
		$nh = $this->height();

		$new = new Imagick();
		$new->newImage($w, $h, $c);

		if (($nw / $w) > ($nh / $h)) {
			$this->ih->thumbnailImage($w, ceil($w * $nh / $nw), false);
		} else {
			$this->ih->thumbnailImage(ceil($h * $nw / $nh), $h, false);
		}

		$new->compositeImage($this->ih, imagick::COMPOSITE_OVER, floor(($w - $this->width()) / 2), floor(($h - $this->height()) / 2));
		$new->setImageFormat($this->ih->getImageFormat());
		$this->ih = $new;

		return $this;
	}

	protected function color2string($c)
	{
		if (is_string($c)) {
			$nc = $c;
		} elseif (is_array($c)) {
			$ac = array(dechex($c[0]), dechex($c[1]), dechex($c[2]));
			$nc = '#' . $ac[0] . $ac[1] . $ac[2];
		}

		return $nc;
	}

	public function watermark($image = false, $parms = false)
	{
		if (!$image) {
			$image = CMS_Images::$default_watermark_image;
		}
		if (!$parms) {
			$parms = CMS_Images::$default_watermark_parms;
		}
		if (is_string($image)) {
			$image = preg_replace('{^/?}', '', $image);

			$watermark = new Imagick($image);

			list($w, $h) = CMS_Images::size($image);

		} elseif (is_object($image) && $image instanceof CMS_Image_Imagick) {
			$watermark =& $image->ih;

			$w = $image->width();
			$h = $image->height();
		}

		$_w = $this->width();
		$_h = $this->height();

		list($nl, $nt, $opacity) = $this->watermark_parms($parms, $_w, $_h, $w, $h);

		if ($opacity != 1) {
			$watermark->setImageOpacity($opacity);
		}

		$this->ih->compositeImage($watermark, Imagick::COMPOSITE_ATOP, $nl, $nt);

		return $this;
	}

	public function grayscale()
	{
		$this->ih->modulateImage(100, 0, 0);
		return $this;
	}

	public function turn_right()
	{
		$this->ih->rotateImage(new ImagickPixel('none'), 90);
		return $this;
	}

	public function turn_left()
	{
		$this->ih->rotateImage(new ImagickPixel('none'), -90);
		return $this;
	}

	public function turn_over()
	{
		$this->ih->rotateImage(new ImagickPixel('none'), 180);
		return $this;
	}
}

class CMS_Image_Imagick extends CMS_Images_Imagick
{
}

