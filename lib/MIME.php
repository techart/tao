<?php
/// <module name="MIME" version="0.2.4" maintainer="timokhin@techart.ru">
///   <brief>Минимальная поддержка работы с MIME</brief>
///   <details>
///     <p>Модуль содержит небольшую базу данных наиболее распространенных MIME-типов и реализует
///        объектный интерфейс к ней.</p>
///     <p>Описание типа в базе содержит следующую информацию:</p>
///     <ul>
///       <li>имя, например, text/html;</li>
///       <li>список расширений файлов, ассоциированных с типом;</li>
///       <li>признак необходимости кодирования в BASE64 (бинарные данные).</li>
///     </ul>
///     <p>Модуль также реализует набор сервисных методов, позволяющий определять MIME-тип по имени
///        файла или его расширению, кодировать и декодировать строки с помощью base64 и
///        quoted-printable, генерировать
///        boundary.</p>
///     <p>Опции модуля:</p>
///     <dl>
///       <dt>default_charset</dt><dd>кодовая страница по умолчанию, используется при
///           кодировании/декодировании строк и заголовков. По умолчанию используется кодировка
///           UTF-8.</dd>>
///     </dl>
///   </details>

/// <class name="MIME" stereotype="module">
///   <brief>Класс модуля</brief>
///   <implements interface="Core.ModuleInterface" />
///   <depends supplier="MIME.UnsupportedEncodingException"  stereotype="throws" />
///   <details>
///     <p>Реализует интерфейс к базе MIME-типов и набор вспомогательных методов, отвечающих за
///        кодирование/декодирование строк и другие операции.</p>
///   </details>

Core::load('IO.FS');

class MIME implements Core_ModuleInterface {

///   <constants>
  const MODULE  = 'MIME';
  const VERSION = '0.2.4';

  const ENCODING_B64  = 'base64';
  const ENCODING_QP   = 'quoted-printable';
  const ENCODING_8BIT = '8bit';

  const LINE_LENGTH = 76;
  const LINE_END    = "\n";
///   </constants>

  static protected $default_charset = 'UTF-8';

  static protected $boundary_counter = 0;

  static protected $types_definition = array(
  'application/x-rar' => array('rar', true),
  'application/excel' => array('xls,xlt', true),
  'application/msword' => array('doc,dot,wiz,wrd', true) ,
  'application/octet-stream' => array('a,bin,dll,exe,o,obj,so', true),
  'application/oda' => array('oda', false),
  'application/pdf' => array('pdf', true),
  'application/postscript' => array('ai,eps,ps', false),
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
  'application/x-wais-source' => array('src'),
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
  'application/vnd.oasis.opendocument.presentation' => array('odp', true) );

  static protected $type_objects = array();

///   <protocol name="creating">

///   <method name="initialize" scope="class">
///     <brief>Инициализация модуля</brief>
///     <args>
///       <arg name="options" type="array" brief="массив опций" />
///     </args>
///     <body>
  static public function initialize(array $options = array()) {
    if (isset($options['default_charset'])) self::$default_charset = $options['default_charset'];
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="quering">

///   <method name="match" returns="boolean">
///     <brief>Проверяет, соответствует ли имя name типу type</brief>
///     <details>
///       <p>Имя типа может содержать *. Метод может быть удобен для выполнения проверки
///          HTTP-заголовка Accept.</p>
///     </details>
///     <args>
///       <arg name="name" type="string" brief="имя" />
///       <arg name="type" type="string" brief="тип" />
///     </args>
///     <body>
  static public function match($name, $type) {
    return preg_match('{'.preg_replace('{[*]}', '([a-zA-Z0-9-.]+)', $type).'}', $name) ? true : false;
  }
///     </body>
///   </method>

///   <method name="type" returns="MIME.Type">
///     <brief>Выполняет поиск типа в базе по его имени</brief>
///     <args>
///       <arg name="name" type="string" brief="имя типа" />
///     </args>
///     <details>
///       <p>В случае, если запрошенный тип отсутствует в базе, метод возвращает null.</p>
///     </details>
///     <body>
  static public function type($type) {
    return isset(self::$type_objects[$type]) ? self::$type_objects[$type] :
      self::$type_objects[$type] = new MIME_Type(
        $type,
        explode(
          ',',
          self::$types_definition[$type][0]),
          self::$types_definition[$type][1] ? MIME::ENCODING_B64 : null);
  }
///     </body>
///   </method>

///   <method name="type_for_suffix" returns="MIME.Type">
///     <brief>Определяет тип по суффиксу (расширению имени файла)</brief>
///     <args>
///       <arg name="name" type="string" brief="имя" />
///     </args>
///     <details>
///       <p>В случае, если суффикс не зарегистрирован в базе и соответствующий тип найти не
///          удается, метод возвращает тип, соответствующий application/octet-stream.</p>
///     </details>
///     <body>
  static public function type_for_suffix($ext) {
    foreach (self::$types_definition as $type => $data) {
      if (preg_match('{(?:^|,)'.$ext.'(?:,|$)}', $data[0])) return self::type($type);
    }
    return self::type('application/octet-stream');
  }
///     </body>
///   </method>

///   <method name="type_for_file" returns="MIME.Type">
///     <brief>Определяет MIME-тип файла</brief>
///     <args>
///       <arg name="file" brif="файл" type="IO.FS.File|string" />
///     </args>
///     <details>
///       <p>В качестве аргумента может быть передана строка, содержащая путь к файлу, или объект
///          класса IO.FS.File.</p>
///       <p>В настоящее время для определения типа используется суффикс имени, планируется
///          добавление поддержки magic numbers через вызов внешней программы file.</p>
///     </details>
///     <body>
  static public function type_for_file($file) {
    return self::type_for_suffix(
      IO_FS::Path($file instanceof IO_FS_File ?
        $file->path :
        (string)$file)->extension);
  }
///     </body>
///   </method>

///   <method name="boundary" returns="string" scope="class">
///     <brief>Формирует строку boundary</brief>
///     <details>
///       <p>Гарантируется уникальность строки при каждом вызове.</p>
///     </details>
///     <body>
  static public function boundary() { return '=_'.md5(microtime(1).self::$boundary_counter++); }
///     </body>
///   </method>

///   <method name="default_charset" returns="string" scope="class">
///     <brief>Возвращает кодовую страницу по умолчанию</brief>
///     <details>
///       <p>Кодовая страница по умолчанию также доступна на чтение и запись как опция модуля.</p>
///     </details>
///     <body>
  static public function default_charset() { return self::$default_charset; }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="encoding">

///   <method name="is_printable" returns="boolean" scope="class">
///     <brief>Проверяет, отсутствие в строке символов, нуждающихся в кодировании</brief>
///     <args>
///       <arg name="string" type="string" brief="строка" />
///     </args>
///     <body>
  static public function is_printable($string) { return (boolean) $string == '' || preg_match('{^[\x20-\x7e]+$}', $string); }
///     </body>
///   </method>

///   <method name="is_printable_qp" returns="boolean" scope="class">
///     <brief>Проверяет отсутствие в строке символов, нуждающихся quoted-printable кодировании</brief>
///     <args>
///       <arg name="string" type="string" brief="строка" />
///     </args>
///     <body>
  static public function is_printable_qp($string) {
    return (boolean) preg_match('{^[\x21-\x3C\x3E-\x7E]+$}', $string);
  }
///     </body>
///   </method>

///   <method name="encode" returns="string" scope="class">
///     <brief>Кодирует строку заданным методом</brief>
///     <args>
///       <arg name="text"     type="string" brief="кодируемый текст" />
///       <arg name="encoding" type="string" default="MIME::ENCODING_B64" brief="тип кодирования" />
///     </args>q
///     <body>
  static public function encode($text, $encoding = MIME::ENCODING_B64) {
    switch ($encoding) {
      case MIME::ENCODING_B64:  return self::encode_b64($text);
      case MIME::ENCODING_QP:   return self::encode_qp($text);
      case MIME::ENCODING_8BIT: return $text;
      default:                  throw new MIME_UnsupportedEncodingException($encoding);
    }
  }
///     </body>
///   </method>

///   <method name="encode_b64" returns="string" scope="class">
///     <brief>Кодирует строку методом BASE64</brief>
///     <args>
///       <arg name="text" type="string" brief="строка" />
///     </args>
///     <details>
///       <p>При кодировании используются длина строки, определенная константой MIME::LINE_LENGTH и
///          символ конца строки, определенный константой MIME::LINE_END.</p>
///     </details>
///     <body>
  static public function encode_b64($text) {
    return rtrim(chunk_split(base64_encode($text), self::LINE_LENGTH, self::LINE_END));
  }
///     </body>
///   </method>

///   <method name="encode_qp" returns="string">
///     <brief>Кодирует строку методом Quoted Printable</brief>
///     <args>
///       <arg name="text" type="string" brief="текст" />
///       <arg name="length" type="int" default="MIME::LINE_LENGTH" brief="длина строки" />
///     </args>
///     <details>
///       <p>Если в качестве значения длины строки указаны null или 0, разбиение на отдельные
///          строки, длина которых не превышает максимальную, не производится.</p>
///     </details>
///     <body>
  static public function encode_qp($text, $length = MIME::LINE_LENGTH) {
    if (self::is_printable_qp($text)) return $text;
    $text = preg_replace_callback(
      '/[^\x21-\x3C\x3E-\x7E]/',
      create_function('$x', 'return strtoupper(sprintf("=%02x", ord($x[0])));'), $text);
    return (is_null($length) || $length === 0) ? $text :  preg_replace('/(.{'.($length-4).'}[^=]{0,3})/', '$1'."=\n", $text);
  }
///     </body>
///   </method>

///   <method name="split" returns="string" scope="static">
///     <brief>Обертка над wordwrap</brief>
///     <args>
///       <arg name="value" type="string" />
///       <arg name="length" type="int" default="MIME::LINE_LENGTH" />
///     </args>
///     <body>
  static public function split($value , $length = MIME::LINE_LENGTH) {
    return wordwrap($value, $length, MIME::LINE_END.' ', true);
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="decoding">

///   <method name="decode" returns="string" scope="class">
///     <brief>Производит декодирование строки</brief>
///     <args>
///       <arg name="string" type="string" brief="строка" />
///       <arg name="encoding" type="string" default="MIME::ENCODING_B64" brief="способ кодирования" />
///     </args>
///     <body>
  static public function decode($text, $encoding = MIME::ENCODING_B64) {
    switch ($encoding) {
      case MIME::ENCODING_B64:  return self::decode_b64($text);
      case MIME::ENCODING_QP:   return self::decode_qp($text);
      case MIME::ENCODING_8BIT: return $text;
      default:                  throw new MIME_UnsupportedEncodingException($encoding);
    }
  }
///     </body>
///   </method>

///   <method name="decode_b64" returns="string" scope="class">
///     <brief>Декодирует строку, закодированную методов BASE64</brief>
///     <args>
///       <arg name="text" type="string" brief="закодированная строка" />
///     </args>
///     <details>
///       <p>Обертка над встроенной функцией base64_decode().</p>
///     </details>
///     <body>
  static public function decode_b64($text) { return base64_decode($text); }
///     </body>
///   </method>

///   <method name="decode_qp" returns="string" scope="class">
///     <brief>Декодирует строку, закодированную методом Quoted Printable</brief>
///     <args>
///       <arg name="text" type="string" brief="закодированный строка" />
///     </args>
///     <body>
  static public function decode_qp($text) { return quoted_printable_decode($text); }
///     </body>
///   </method>

///   <method name="decode_headers" returns="array" scope="class">
///     <brief>Декодирует заголовки сообщения MIME</brief>
///     <args>
///       <arg name="string" type="string" brief="строка заголовков" />
///     </args>
///     <p>Обертка над встроенной функцией iconv_mime_decode_headers().</p>
///     <body>
  static public function decode_headers($string) {
    return iconv_mime_decode_headers(
      $string, ICONV_MIME_DECODE_CONTINUE_ON_ERROR, self::$default_charset);
  }
///     </body>
///   </method>

///   </protocol>
}
/// </class>
/// <composition>
///   <source class="MIME" role="module" multiplicity="1" />
///   <target class="MIME.Type" role="type" multiplicity="N" />
/// </composition>

/// <class name="MIME.Exception" extends="Core.Exception" stereotype="exception">
///   <brief>Базовый класс исключений</brief>
class MIME_Exception extends Core_Exception {}
/// </class>


/// <class name="MIME.UnsupportedEncodingException" extends="MIME.Exception" stereotype="exception">
///   <brief>Исключение: неподдерживаемый тип кодирования</brief>
///   <details>
///     <p>Свойства:</p>
///     <dl>
///       <dt>encoding</dt><dd>метод кодирования</dd>
///     </dl>
///   </details>
class MIME_UnsupportedEncodingException extends MIME_Exception {

  protected $encoding;

///   <protocol name="creating">

///   <method name="__construct">
///     <brief>Конструктор</brief>
///     <args>
///       <arg name="encoding" type="string" brief="метод кодирования" />
///     </args>
///     <body>
  public function __construct($encoding) {
    $this->encoding = $encoding;
    parent::__construct("Unsupported encoding: $encoding");
  }
}
///     </body>
///   </method>

///   </protocol>

/// </class>


/// <class name="MIME.Type">
///   <implements interface="Core.PropertyAccessInterface" />
///   <implements interface="Core.StringifyInterface" />
///   <brief>Объектное представление типа MIME</brief>
///   <details>
///     <p>Свойства:</p>
///     <dl>
///       <dt>type</dt><dd>имя типа;</dd>
///       <dt>name</dt><dd>псевдоним для type;</dd>
///       <dt>extensions</dt><dd>список ассоциированных суффиксов-расширений;</dd>
///       <dt>encoding</dt><dd>используемый метод кодирования (base64/quoted-printable);</dd>
///       <dt>simplified</dt><dd>упрощенное имя типа;</dd>
///       <dt>media_type</dt><dd>основной тип (первая часть имени типа)</dd>
///       <dt>main_type</dt><dd>псевдоним для media_type;</dd>
///       <dt>subtype</dt><dd>подтип (вторая часть имени).</dd>
///     </dl>
///   </details>
class MIME_Type
  implements Core_PropertyAccessInterface, Core_StringifyInterface {

  protected $type;
  protected $extensions = array();
  protected $encoding;

  protected $simplified;

///   <protocol name="creating">

///   <method name="__construct">
///     <brief>Конструктор</brief>
///     <args>
///       <arg name="type" type="string" brief="тип" />
///       <arg name="extensions" type="string" default="null" brief="суффикс" />
///       <arg name="encoding"   type="string" default="null" brief="тип кодировки" />
///     </args>
///     <body>
  public function __construct($type, $extensions = null, $encoding = null) {
    $this->type = (string) $type;

    $this->simplified = self::simplify($this->type);

    $this->extensions = is_null($extensions) ? array() : (array) $extensions;

    $this->encoding = $encoding ?
      $encoding :
      ($this->main_type == 'text' ? MIME::ENCODING_QP : MIME::ENCODING_B64);
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="accessing" interface="Core.PropertyAccessInterface">

///   <method name="__get" returns="mixed">
///     <brief>Возвращает значение свойства</brief>
///     <args>
///       <arg name="property" type="string" brief="имя свойства" />
///     </args>
///     <body>
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
///     </body>
///   </method>

///   <method name="__set" returns="mixed">
///     <brief>Устанавливает значение свойства</brief>
///     <args>
///       <arg name="property" type="string" brief="имя свойства" />
///       <arg name="value" brief="значение" />
///     </args>
///     <details>
///       <p>Все свойства объекта доступны только на чтение.</p>
///     </details>
///     <body>
  public function __set($property, $value) { throw new Core_ReadOnlyObjectException($this); }
///     </body>
///   </method>

///   <method name="__isset" returns="boolean">
///     <brief>Проверяет установку значения свойства</brief>
///     <args>
///       <arg name="property" type="string" brief="имя свойства" />
///     </args>
///     <body>
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
///     </body>
///   </method>

///   <method name="__unset">
///     <brief>Удаляет свойство объекта</brief>
///     <args>
///       <arg name="property" type="string" brief="имя свойства" />
///     </args>
///     <details>
///       <p>Свойства объекта не могут быть удалены.</p>
///     </details>
///     <body>
  public function __unset($property) { throw new Core_ReadOnlyObjectException($this); }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="supporting">

///   <method name="simplify" returns="string" access="protected" scope="class">
///     <brief>
///       <p>Возвращает упрощенное имя типа.</p>
///     </brief>
///     <args>
///       <arg name="type" type="string" />
///     </args>
///     <body>
  public static function simplify($type) {
    return preg_match('{^\s*(?:x\-)?([\w.+-]+)/(?:x\-)?([\w.+-]+)\s*$}', $type, $m) ?
      strtolower($m[1].'/'.$m[2]) :
      (preg_match('{text}', $type) ? 'text/plain' : null);
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="stringifying" interface="Core.StringifyInterface">

///   <method name="as_string" returns="string">
///     <brief>Возвращает строковое представление объекта</brief>
///     <details>
///       <p>Строковое представление объекта представляет собой полное имя типа.</p>
///     </details>
///     <body>
  public function as_string() { return $this->type; }
///     </body>
///   </method>

///   <method name="__toString" returns="string">
///     <brief>Возващает строковое представление объекта</brief>
///     <details>
///       <p>Псевдоним для as_string().</p>
///     </details>
///     <body>
  public function __toString() { return $this->as_string(); }
///     </body>
///   </method>

///   </protocol>
}
/// </class>

/// </module>
