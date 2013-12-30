<?php
/**
 * MIME
 * 
 * Минимальная поддержка работы с MIME
 * 
 * <p>Модуль содержит небольшую базу данных наиболее распространенных MIME-типов и реализует
 * объектный интерфейс к ней.</p>
 * <p>Описание типа в базе содержит следующую информацию:</p>
 * <ul><li>имя, например, text/html;</li>
 * <li>список расширений файлов, ассоциированных с типом;</li>
 * <li>признак необходимости кодирования в BASE64 (бинарные данные).</li>
 * </ul><p>Модуль также реализует набор сервисных методов, позволяющий определять MIME-тип по имени
 * файла или его расширению, кодировать и декодировать строки с помощью base64 и
 * quoted-printable, генерировать
 * boundary.</p>
 * <p>Опции модуля:</p>
 * default_charsetкодовая страница по умолчанию, используется при
 * кодировании/декодировании строк и заголовков. По умолчанию используется кодировка
 * UTF-8.>
 * 
 * @package MIME
 * @version 0.2.4
 */

/**
 * Класс модуля
 * 
 * <p>Реализует интерфейс к базе MIME-типов и набор вспомогательных методов, отвечающих за
 * кодирование/декодирование строк и другие операции.</p>
 * 
 * @package MIME
 */

Core::load('IO.FS');

class MIME implements Core_ModuleInterface {

  const MODULE  = 'MIME';
  const VERSION = '0.2.4';

  const ENCODING_B64  = 'base64';
  const ENCODING_QP   = 'quoted-printable';
  const ENCODING_8BIT = '8bit';

  const LINE_LENGTH = 76;
  const LINE_END    = "\n";

  static protected $default_charset = 'UTF-8';

  static protected $boundary_counter = 0;

  static protected $types_definition = array(
  'application/x-rar' => array('rar', true),
  'application/excel' => array('xls,xlt', true),
  'application/msword' => array('doc,dot,wiz,wrd', true),
  'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => array('docx', true),
  'application/octet-stream' => array('a,bin,dll,exe,o,obj,so', true),
  'application/oda' => array('oda', false),
  'application/pdf' => array('pdf', true),
  'application/postscript' => array('ai,eps,ps,aps', false),
  'application/vnd.ms-excel' => array('xlb,xls', true),
  'application/vnd.ms-powerpoint' => array('pot,ppa,pps,ppt,pwz', true),
  'application/x-bcpio' => array('bcpio', false),
  'application/xbzip2' => array('bz2', false),
  'application/x-cdf' => array('cdf', false),
  'application/x-cpio' => array('cpio', false),
  'application/x-csh' => array('csh', false),
  'application/x-dvi' => array('dvi,dvii', true),
  'application/x-gtar' => array('gtar,tgz,tbz2', true),
  'application/x-hdf' => array('hdf', false),
  'application/x-javascript' => array('js', false),
  'application/x-latex' => array('latex', false),
  'application/x-mif' => array('mif', false),
  'application/xml' => array('rdf,wsdl,xpdl,xsl', false),
  'application/x-netcdf' => array('nc,cdf', false),
  'application/x-pn-realaudio' => array('ram,rm', true),
  'application/x-shockwave-flash' => array('swf', false),
  'application/x-sh' => array('sh', false),
  'application/x-sv4cpio' => array('sv4cpio', true),
  'application/x-sv4crc' => array('sv4crc', true),
  'application/x-tar' => array('tar', true),
  'application/x-tcl' => array('tcl', false),
  'application/x-texinfo' => array('texi,texinfo', false),
  'application/x-tex' => array('tex', false),
  'application/x-troff-man' => array('man', false),
  'application/x-troff-me' => array('me', false),
  'application/x-troff-ms' => array('ms', false),
  'application/x-troff' => array('t,tr,roff', false),
  'application/x-ustar' => array('ustar', true),
  'application/x-wais-source' => array('src', false),
  'application/x-apple-diskimage' => array('dmg', true),
  'application/zip;zip' => array('zip', true),
  'audio/basic' => array('au,snd', true),
  'audio/mpeg' => array('mpga,mp2,mp3', true),
  'audio/x-aiff' => array('aif,aifc,aiff', true),
  'audio/x-pn-realaudio' => array('ra', true),
  'audio/x-wav' => array('wav', true),
  'image/gif' => array('gif', true),
  'image/ief' => array('ief', true),
  'image/jpeg' => array('jpe,jpeg,jpg', true),
  'image/png' => array('png', true),
  'image/tiff' => array('tif,tiff', true),
  'image/x-cmu-raster' => array('ras', false),
  'image/x-ms-bmp' => array('bmp', false),
  'image/x-portable-anymap' => array('pnm', true),
  'image/x-portable-bitmap' => array('pbm', true),
  'image/x-portable-graymap' => array('pgm', true),
  'image/x-portable-pixmap' => array('ppm', true),
  'image/x-rgb' => array('rgb', true),
  'image/x-xbitmap' => array('xbm', false),
  'image/x-xpixmap' => array('xpm', false),
  'image/x-xwindowdump' => array('xwd', true),
  'message/rfc822' => array('eml,mht,mhtml,nws', false),
  'text/css' => array('css', false),
  'text/csv' => array('csv', false),
  'text/html' => array('htm,html,shtml,htx,htmlx', false),
  'text/plain' => array('bat,c,h,ksh,pl,py,rb,php,txt,dat', false),
  'text/rtf' => array('rtf', false),
  'text/tab-separated-values' => array('tsv', false),
  'text/xml' => array('xml', false),
  'text/x-setext' => array('etx', false),
  'text/x-sgml' => array('sgm,sgml', false),
  'text/x-vcard' => array('vcf', false),
  'video/mpeg' => array('mlv,mpa,mpe,mpeg,mpg', true),
  'video/quicktime' => array('mov,qt', true),
  'video/x-fli' => array('fli', true),
  'video/x-flv' => array('flv', true),
  'video/x-msvideo' => array('avi', true),
  'video/x-sgi-movie' => array('movie', true),
  'application/vnd.oasis.opendocument.text' => array('odt', true),
  'application/vnd.oasis.opendocument.spreadsheet' => array('ods', true),
  'application/vnd.oasis.opendocument.presentation' => array('odp', true),
  'application/postscript' => array('aps', true),
  'application/stuffit' => array('hqx', true),
  'application/x-java-archive' => array('jar', true),
  'audio/x-mpegurl' => array('m3u', true),
  'audio/mp4' => array('m4a', true),
  'application/x-msaccess' => array('mdb', true),
  'audio/midi' => array('mid,midi', true),
  'video/mp4' => array('mp4', true),
  'vnd.oasis.opendocument.graphics' => array('odg', true),
  'audio/ogg' => array('ogg', true),
  'application/vnd.openxmlformats-officedocument.presentationml.presentation' => array('pptx', true),
  'application/x-stuffit' => array('sit', true),
  'image/svg+xml' => array('svg', true),
  'application/x-font-truetype' => array('ttf', true),
  'audio/x-ms-wma' => array('wma', true),
  'audio/x-ms-wmv' => array('wmv', true),
  'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => array('xlsx', true)
);

  static protected $type_objects = array();


/**
 * Инициализация модуля
 * 
 * @param array $options
 */
  static public function initialize(array $options = array()) {
    if (isset($options['default_charset'])) self::$default_charset = $options['default_charset'];
  }



/**
 * Проверяет, соответствует ли имя name типу type
 * 
 * @param string $name
 * @param string $type
 * @return boolean
 */
  static public function match($name, $type) {
    return preg_match('{'.preg_replace('{[*]}', '([a-zA-Z0-9-.]+)', $type).'}', $name) ? true : false;
  }

/**
 * Выполняет поиск типа в базе по его имени
 * 
 * @param string $name
 * @return MIME_Type
 */
  static public function type($type) {
    return isset(self::$type_objects[$type]) ? self::$type_objects[$type] :
      self::$type_objects[$type] = new MIME_Type(
        $type,
        explode(
          ',',
          self::$types_definition[$type][0]),
          self::$types_definition[$type][1] ? MIME::ENCODING_B64 : null);
  }

/**
 * Определяет тип по суффиксу (расширению имени файла)
 * 
 * @param string $name
 * @return MIME_Type
 */
  static public function type_for_suffix($ext) {
    foreach (self::$types_definition as $type => $data) {
      if (preg_match('{(?:^|,)'.$ext.'(?:,|$)}', $data[0])) return self::type($type);
    }
    return self::type('application/octet-stream');
  }

/**
 * Определяет MIME-тип файла
 * 
 * @param IO_FS_File|string $file
 * @return MIME_Type
 */
  static public function type_for_file($file) {
	$file = IO_FS::Path($file);
    return self::type_for_suffix(isset($file->extension) ? $file->extension : '');
  }

/**
 * Формирует строку boundary
 * 
 * @return string
 */
  static public function boundary() { return '=_'.md5(microtime(1).self::$boundary_counter++); }

/**
 * Возвращает кодовую страницу по умолчанию
 * 
 * @return string
 */
  static public function default_charset() { return self::$default_charset; }



/**
 * Проверяет, отсутствие в строке символов, нуждающихся в кодировании
 * 
 * @param string $string
 * @return boolean
 */
  static public function is_printable($string) { return (boolean) $string == '' || preg_match('{^[\x20-\x7e]+$}', $string); }

/**
 * Проверяет отсутствие в строке символов, нуждающихся quoted-printable кодировании
 * 
 * @param string $string
 * @return boolean
 */
  static public function is_printable_qp($string) {
    return (boolean) preg_match('{^[\x21-\x3C\x3E-\x7E]+$}', $string);
  }

/**
 * Кодирует строку заданным методом
 * 
 * @param string $text
 * @param string $encoding
 * @return string
 */
  static public function encode($text, $encoding = MIME::ENCODING_B64) {
    switch ($encoding) {
      case MIME::ENCODING_B64:  return self::encode_b64($text);
      case MIME::ENCODING_QP:   return self::encode_qp($text);
      case MIME::ENCODING_8BIT: return $text;
      default:                  throw new MIME_UnsupportedEncodingException($encoding);
    }
  }

/**
 * Кодирует строку методом BASE64
 * 
 * @param string $text
 * @return string
 */
  static public function encode_b64($text) {
    return rtrim(chunk_split(base64_encode($text), self::LINE_LENGTH, self::LINE_END));
  }

/**
 * Кодирует строку методом Quoted Printable
 * 
 * @param string $text
 * @param int $length
 * @return string
 */
  static public function encode_qp($text, $length = MIME::LINE_LENGTH) {
    if (self::is_printable_qp($text)) return $text;
    $text = preg_replace_callback(
      '/[^\x21-\x3C\x3E-\x7E]/',
      create_function('$x', 'return strtoupper(sprintf("=%02x", ord($x[0])));'), $text);
    return (is_null($length) || $length === 0) ? $text :  preg_replace('/(.{'.($length-4).'}[^=]{0,3})/', '$1'."=\n", $text);
  }

/**
 * Обертка над wordwrap
 * 
 * @param string $value
 * @param int $length
 * @return string
 */
  static public function split($value , $length = MIME::LINE_LENGTH) {
    return wordwrap($value, $length, MIME::LINE_END.' ', true);
  }



/**
 * Производит декодирование строки
 * 
 * @param string $string
 * @param string $encoding
 * @return string
 */
  static public function decode($text, $encoding = MIME::ENCODING_B64) {
    switch ($encoding) {
      case MIME::ENCODING_B64:  return self::decode_b64($text);
      case MIME::ENCODING_QP:   return self::decode_qp($text);
      case MIME::ENCODING_8BIT: return $text;
      default:                  throw new MIME_UnsupportedEncodingException($encoding);
    }
  }

/**
 * Декодирует строку, закодированную методов BASE64
 * 
 * @param string $text
 * @return string
 */
  static public function decode_b64($text) { return base64_decode($text); }

/**
 * Декодирует строку, закодированную методом Quoted Printable
 * 
 * @param string $text
 * @return string
 */
  static public function decode_qp($text) { return quoted_printable_decode($text); }

/**
 * Декодирует заголовки сообщения MIME
 * 
 * @param string $string
 * @return array
 */
  static public function decode_headers($string) {
    return iconv_mime_decode_headers(
      $string, ICONV_MIME_DECODE_CONTINUE_ON_ERROR, self::$default_charset);
  }

}

/**
 * Базовый класс исключений
 * 
 * @package MIME
 */
class MIME_Exception extends Core_Exception {}


/**
 * Исключение: неподдерживаемый тип кодирования
 * 
 * <p>Свойства:</p>
 * encodingметод кодирования
 * 
 * @package MIME
 */
class MIME_UnsupportedEncodingException extends MIME_Exception {

  protected $encoding;


/**
 * Конструктор
 * 
 * @param string $encoding
 */
  public function __construct($encoding) {
    $this->encoding = $encoding;
    parent::__construct("Unsupported encoding: $encoding");
  }
}




/**
 * Объектное представление типа MIME
 * 
 * <p>Свойства:</p>
 * typeимя типа;
 * nameпсевдоним для type;
 * extensionsсписок ассоциированных суффиксов-расширений;
 * encodingиспользуемый метод кодирования (base64/quoted-printable);
 * simplifiedупрощенное имя типа;
 * media_typeосновной тип (первая часть имени типа)
 * main_typeпсевдоним для media_type;
 * subtypeподтип (вторая часть имени).
 * 
 * @package MIME
 */
class MIME_Type
  implements Core_PropertyAccessInterface, Core_StringifyInterface {

  protected $type;
  protected $extensions = array();
  protected $encoding;

  protected $simplified;


/**
 * Конструктор
 * 
 * @param string $type
 * @param string $extensions
 * @param string $encoding
 */
  public function __construct($type, $extensions = null, $encoding = null) {
    $this->type = (string) $type;

    $this->simplified = self::simplify($this->type);

    $this->extensions = is_null($extensions) ? array() : (array) $extensions;

    $this->encoding = $encoding ?
      $encoding :
      ($this->main_type == 'text' ? MIME::ENCODING_QP : MIME::ENCODING_B64);
  }



/**
 * Возвращает значение свойства
 * 
 * @param string $property
 * @return mixed
 */
  public function __get($property) {
    switch ($property) {
      case 'type':
      case 'extensions':
      case 'encoding':
      case 'simplified':
        return $this->$property;
      case 'name':
        return $this->type;
      case 'media_type':
      case 'main_type':
        return preg_match('{^([\w-]+)/}', $this->simplified, $m)   ? $m[1] : null;
      case 'subtype':
        return preg_match('{/([\w-]+)$}', $this->simplified, $m) ? $m[1] : null;
      default:
        throw new Core_MissingPropertyException($property);
    }
  }

/**
 * Устанавливает значение свойства
 * 
 * @param string $property
 * @param  $value
 * @return mixed
 */
  public function __set($property, $value) { throw new Core_ReadOnlyObjectException($this); }

/**
 * Проверяет установку значения свойства
 * 
 * @param string $property
 * @return boolean
 */
  public function __isset($property) {
    switch ($property) {
      case 'name':
      case 'type':
      case 'extensions':
      case 'encoding':
      case 'simplified':
      case 'media_type':
      case 'main_type':
      case 'subtype':
        return true;
      default:
        return false;
    }
  }

/**
 * Удаляет свойство объекта
 * 
 * @param string $property
 */
  public function __unset($property) { throw new Core_ReadOnlyObjectException($this); }



/**
 * <p>Возвращает упрощенное имя типа.</p>
 * 
 * @param string $type
 * @return string
 */
  public static function simplify($type) {
    return preg_match('{^\s*(?:x\-)?([\w.+-]+)/(?:x\-)?([\w.+-]+)\s*$}', $type, $m) ?
      strtolower($m[1].'/'.$m[2]) :
      (preg_match('{text}', $type) ? 'text/plain' : null);
  }



/**
 * Возвращает строковое представление объекта
 * 
 * @return string
 */
  public function as_string() { return $this->type; }

/**
 * Возващает строковое представление объекта
 * 
 * @return string
 */
  public function __toString() { return $this->as_string(); }

}

