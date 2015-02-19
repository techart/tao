<?php
/**
 * Mail.Message
 *
 * Объектное представление почтового сообщения
 *
 * <p>Модуль определяет классы, соответствующие таким элементам почтового сообщения, как
 * поле заголовка, заголовок, часть сообщения и само сообщение.</p>
 *
 * @package Mail\Message
 * @version 0.2.5
 */
Core::load('Object', 'IO.FS', 'Mail');

/**
 * @package Mail\Message
 */
class Mail_Message implements Core_ModuleInterface
{

	const VERSION = '0.2.5';

	protected static $options = array(
		'transport' => 'php',
		'transport_classes' => array(
			'sendmail' => 'Mail.Transport.Sendmail.Sender',
			'php' => 'Mail.Transport.PHP.Sender',
		)
	);

	public static function initialize($options = array())
	{
		self::options($options);
	}

	public static function options($options = array())
	{
		self::$options = array_replace_recursive(self::$options, $options);
		return self::$options;
	}

	public static function option($name)
	{
		return self::$options[$name];
	}

	public static function transport($name = null)
	{
		if (is_null($name)) {
			$name = self::option('transport');
		}
		$classes = self::option('transport_classes');
		if (!isset($classes[$name])) {
			return null;
		}
		$class = $classes[$name];
		Core::autoload($class);
		return Core::make($class);
	}

	public static function send($msg, $transport = null)
	{
		$transport = self::transport($transport);
		if ($transport) {
			$transport->send($msg);
		}
	}

	/**
	 * фабричный метод, возвращает объект класса Mail.Message.Field
	 *
	 * @param string $name
	 * @param string $body
	 *
	 * @return Mail_Message_Field
	 */
	static public function Field($name, $body)
	{
		return new Mail_Message_Field($name, $body);
	}

	/**
	 * фабричный метод, возвращает объект класса Mail.Message.Head
	 *
	 * @return Mail_Message_Head
	 */
	static public function Head()
	{
		return new Mail_Message_Head();
	}

	/**
	 * фабричный метод, возвращает объект класса Mail.Message.Part
	 *
	 * @return Mail_Message_Part
	 */
	static public function Part()
	{
		return new Mail_Message_Part();
	}

	/**
	 * фабричный метод, возвращает объект класса Mail.Message.Message
	 *
	 * @param boolean $nested
	 *
	 * @return Mail_Message_Message
	 */
	static public function Message($nested = false)
	{
		return new Mail_Message_Message($nested);
	}

}

/**
 * Класс исключения
 *
 * @package Mail\Message
 */
class Mail_Message_Exception extends Mail_Exception
{
}

/**
 * Поле заголовка почтового сообщения
 *
 * <p>Поле сообщения включает в себя имя поля, значение поля и набор дополнительных
 * атрибутов.</p>
 *
 * @package Mail\Message
 */
class Mail_Message_Field
	extends Object_Struct
	implements Core_IndexedAccessInterface,
	Core_StringifyInterface,
	Core_EqualityInterface
{

	static protected $acronyms = array(
		'mime' => 'MIME', 'ldap' => 'LDAP', 'soap' => 'SOAP', 'swe' => 'SWE',
		'bcc' => 'BCC', 'cc' => 'CC', 'id' => 'ID');

	const EMAIL_REGEXP = '#(?:[a-zA-Z0-9_\.\-\+])+\@(?:(?:[a-zA-Z0-9\-])+\.)+(?:[a-zA-Z0-9]{2,4})#';
	const EMAIL_NAME_REGEXP = "{(?:(.+)\s?)?(<?(?:[a-zA-Z0-9_\.\-\+])+\@(?:(?:[a-zA-Z0-9\-])+\.)+(?:[a-zA-Z0-9]{2,4})>?)$}Ui";
	const ATTR_REGEXP = '{;\s*\b([a-zA-Z0-9_\.\-]+)\s*\=\s*(?:(?:"([^"]*)")|(?:\'([^\']*)\')|([^;\s]*))}i';

	protected $name;
	protected $value;
	protected $attrs = array();

	/**
	 * Конструктор
	 *
	 * @param string $name
	 * @param string $body
	 * @param array  $attrs
	 */
	public function __construct($name, $body, $attrs = array())
	{
		$this->name = $this->canonicalize($name);
		$this->set_body($body);
	}

	/**
	 * Проверяет соответствие имени поля указанному имени
	 *
	 * @param string $name
	 *
	 * @return boolean
	 */
	public function matches($name)
	{
		return Core_Strings::downcase($this->name) ==
		Core_Strings::downcase(Core_Strings::trim($name));
	}

	/**
	 * Возвращает значение атрибута поля
	 *
	 * @param string $index
	 *
	 * @return string
	 */
	public function offsetGet($index)
	{
		return isset($this->attrs[$index]) ? $this->attrs[$index] : null;
	}

	/**
	 * Устанавливает значение атрибута поля
	 *
	 * @param string $index
	 * @param string $value
	 *
	 * @return string
	 */
	public function offsetSet($index, $value)
	{
		$this->attrs[(string)$index] = $value;
		return $this;
	}

	/**
	 * Проверяет, установлен ли атрибут поля
	 *
	 * @param string $index
	 *
	 * @return boolean
	 */
	public function offsetExists($index)
	{
		return isset($this->attrs[$index]);
	}

	/**
	 * Удаляет атрибут
	 *
	 * @param string $index
	 */
	public function offsetUnset($index)
	{
		unset($this->attrs[$index]);
	}

	/**
	 * Возвращает поле ввиде закодированной строки
	 *
	 * @return string
	 */
	public function as_string()
	{
		return $this->encode();
	}

	/**
	 * Возвращает поле ввиде строки
	 *
	 * @return string
	 */
	public function __toString()
	{
		return $this->as_string();
	}



	/**
	 * Кодирует поле
	 *
	 * @return string
	 */
//TODO: iconv_mime_encode не верно кодирует длинные строки
	public function encode()
	{
		$body = $this->name . ': ' . $this->encode_value($this->value, false) . ';';
		foreach ($this->attrs as $index => $value) {
			$attr = $this->encode_attr($index, $value);
			$delim = (($this->line_length($body) + strlen($attr) + 1) >= MIME::LINE_LENGTH) ?
				"\n " : ' ';
			$body .= $delim . $attr;
		}
		return substr($body, 0, strlen($body) - 1);
	}

	/**
	 * Кодирует аттрибут поля
	 *
	 * @param string $index
	 *
	 * @return string
	 */
	protected function encode_attr($index, $value)
	{
		$value = $this->encode_value($value);
		switch (true) {
			case $index == 'boundary':
			case strpos($value, ' '):
				return "$index=\"$value\";";
			default:
				return "$index=$value;";
		}
	}

	/**
	 * Кодирует значение поля или значение аттрибута
	 *
	 * @param string  $value
	 * @param boolean $quote
	 *
	 * @return string
	 */
	protected function encode_value($value, $quote = true)
	{
		if ($this->is_address_line($value)) {
			return $this->encode_email($value);
		} else {
			return $this->encode_mime($value, $quote);
		}
	}

	/**
	 * Кодирует строку email адресов
	 *
	 * @param string $value
	 *
	 * @return string
	 */
	protected function encode_email($value)
	{
		$result = array();
		foreach (explode(',', $value) as $k => $v)
			if (preg_match(self::EMAIL_NAME_REGEXP, $v, $m)) {
				$result[] = ($m[1] ? $this->encode_mime(trim($m[1]), false) : '') . ' ' . $m[2];
			} else {
				return $this->encode_mime($value, false);
			}
		return implode(',' . MIME::LINE_END . ' ', $result);
	}

	/**
	 * Обертка над iconv_mime_encode
	 *
	 * @param string  $value
	 * @param boolean $quote
	 */
	protected function encode_mime($value, $quote = true)
	{
		$q = $quote ? '"' : '';
		return !MIME::is_printable($value) ? $q . preg_replace('{^: }', '', iconv_mime_encode(
					'',
					$value,
					array(
						'scheme' => 'B',
						'input-charset' => 'UTF-8',
						'output-charset' => 'UTF-8',
						"line-break-chars" => MIME::LINE_END
					)
				)
			) . $q : $value /*MIME::split($value)*/
			;
	}

	/**
	 * Возвращает последней строки в тексте
	 *
	 * @param string $txt
	 *
	 * @return int
	 */
	private function line_length($txt)
	{
		return strlen(end(explode("\n", $txt)));
	}

	/**
	 * Проверяет, является ли строка tmail адресом
	 *
	 * @param string $line
	 *
	 * @return boolean
	 */
	protected function is_address_line($line)
	{
		return preg_match(self::EMAIL_REGEXP, $line);
	}

	/**
	 * Установка свойства name извне запрещена
	 *
	 * @param string $name
	 */
	protected function set_name($name)
	{
		throw new Core_ReadOnlyPropertyException('name');
	}

	/**
	 * Устанавливает содержимое поля
	 *
	 * @param string|array|mixed $body
	 *
	 * @return string
	 */
	protected function set_body($body)
	{
		$this->attrs = array();
		if (is_array($body)) {
			foreach ($body as $k => $v)
				switch (true) {
					case is_string($k):
						$this[$k] = $v;
						break;
					case is_int($k):
						$this->value = (string)$v;
				}
		} else {
			$this->from_string((string)$body);
		}
		return $this;
	}

	/**
	 * Производит разбор строки, извлекая аттрибуты
	 *
	 * @param string $body
	 *
	 * @return Mail_Message_Field
	 */
	public function from_string($body)
	{
		if (preg_match_all(self::ATTR_REGEXP, $body, $m, PREG_SET_ORDER)) {
			foreach ($m as $res) {
				$v = $res[2] ? $res[2] : ($res[3] ? $res[3] : ($res[4] ? $res[4] : null));
				if (isset($res[1]) && $v) {
					$this[$res[1]] = $v;
				}
			}
			$this->value = trim(substr($body, 0, strpos($body, ';')));
		} else {
			$this->value = $body;
		}
		return $this;
	}

	/**
	 * @return string
	 */
	protected function get_body()
	{
		return $this->encode();
	}

	/**
	 * Устанавливает значение поля
	 *
	 * @param string $value
	 *
	 * @return string
	 */
	protected function set_value($value)
	{
		;
		$this->value = (string)$value;
		return $this;
	}

	/**
	 * Приводит имя к виду соответствующему почтовому стандарту
	 *
	 * @param string $name
	 *
	 * @return string
	 */
	protected function canonicalize($name)
	{
		$parts = Core_Arrays::map(
			'return strtolower($x);',
			Core_Strings::split_by('-', trim($name))
		);

		foreach ($parts as &$part)
			$part = isset(self::$acronyms[$part]) ?
				self::$acronyms[$part] :
				(preg_match('{[aeiouyAEIOUY]}', $part) ? ucfirst($part) : strtoupper($part));

		return Core_Arrays::join_with('-', $parts);
	}

	/**
	 * @param  $to
	 *
	 * @return boolean
	 */
	public function equals($to)
	{
		return $to instanceof self &&
		$this->value == $to->value &&
		$this->name == $to->name &&
		Core::equals($this->attrs, $to->attrs);
	}
}

/**
 * Заголовок сообщения
 *
 * @package Mail\Message
 */
class Mail_Message_Head
	implements Core_IndexedAccessInterface,
	IteratorAggregate,
	Core_EqualityInterface
{

	protected $fields;

	/**
	 * Парсит и декодирует поля заголовка из строки
	 *
	 * @param string $data
	 *
	 * @return Mail_Message_Head
	 */
	static public function from_string($data)
	{
		$head = new Mail_Message_Head();
		foreach (MIME::decode_headers($data) as $k => $f)
			foreach ((array)$f as $v)
				$head->field($k, $v);
		return $head;
	}

	/**
	 * Конструктор
	 *
	 */
	public function __construct()
	{
		$this->fields = new ArrayObject();
	}

	/**
	 * Возвращает итератор по полям заголовка
	 *
	 * @return Iterator
	 */
	public function getIterator()
	{
		return $this->fields->getIterator();
	}

	/**
	 * Добавляет к заголовку новое поле
	 *
	 * @param string $name
	 * @param string $value
	 *
	 * @return Mail_Message_Head
	 */
	public function field($name, $value)
	{
		$this->fields[] = new Mail_Message_Field($name, $value);
		return $this;
	}

	/**
	 * Добавляет к заголовку поля из массива $values
	 *
	 * @param  $values
	 *
	 * @return Mail_Message_Head
	 */
	public function fields($values)
	{
		foreach ($values as $name => $value)
			$this->field($name, $value);
		return $this;
	}

	/**
	 * Возвращает поле заголовка
	 *
	 * @param string $index
	 *
	 * @return string
	 */
	public function offsetGet($index)
	{
		return is_int($index) ? $this->fields[$index] : $this->get($index);
	}

	/**
	 * Устанавливает или добаляет поле к заголовку
	 *
	 * @param string $index
	 * @param string $value
	 *
	 * @return string
	 */
	public function offsetSet($index, $value)
	{
		if (is_int($index)) {
			throw isset($this[$index]) ?
				new Core_ReadOnlyIndexedPropertyException($index) :
				new Core_MissingIndexedPropertyException($index);
		} else {
			if ($this->offsetExists($index)) {
				$this[$index]->body = $value;
			} else {
				$this->field($index, $value);
			}
		}
		return $this;
	}

	/**
	 * Проверяет установелнно ли поле с именем $index
	 *
	 * @param string $index
	 *
	 * @return boolean
	 */
	public function offsetExists($index)
	{
		return is_int($index) ?
			isset($this->fields[$index]) :
			($this->get($index) ? true : false);
	}

	/**
	 * Выбрасывает исключение Core.NotImplementedException
	 *
	 * @param string $index
	 */
	public function offsetUnset($index)
	{
		throw new Core_NotImplementedException();
	}

	/**
	 * Проверяет установелнно ли поле с именем $name
	 *
	 * @param string $name
	 *
	 * @return Mail_Message_Field
	 */
	public function get($name)
	{
		foreach ($this->fields as $field)
			if ($field->matches((string)$name)) {
				return $field;
			}
		return null;
	}

	/**
	 * Возвращает ArrayObject всех полей заголовка с именем $name
	 *
	 * @param string $name
	 *
	 * @return ArrayObject
	 */
	public function get_all($name)
	{
		$result = new ArrayObject();
		foreach ($this->fields as $field)
			if ($field->matches((string)$name)) {
				$result[] = $field;
			}
		return $result;
	}

	/**
	 * Возвращает количество полей с именем $name
	 *
	 * @param string $name
	 *
	 * @return int
	 */
	public function count_for($name)
	{
		$count = 0;
		foreach ($this->fields as $field)
			$count += $field->matches((string)$name) ? 1 : 0;
		return $count;
	}

	/**
	 * Кодирует заголовок
	 *
	 * @return string
	 */
	public function encode()
	{
		$encoded = '';
		foreach ($this->fields as $field)
			$encoded .= $field->encode() . MIME::LINE_END;
		return $encoded;
	}

	/**
	 * @param  $to
	 *
	 * @return boolean
	 */
	public function equals($to)
	{
		$r = $to instanceof self;
		$ar1 = $this->getIterator()->getArrayCopy();
		$ar2 = $to->getIterator()->getArrayCopy();
		$r = $r && (count($ar1) == count($ar2));
		foreach ($ar1 as $v) {
			$r = $r && (Core::equals($v, $to->get($v->name)));
		}
		return $r;
	}
}

/**
 * Часть почтового сообщения
 *
 * @package Mail\Message
 */
class Mail_Message_Part
	implements Core_PropertyAccessInterface,
	Core_StringifyInterface,
	Core_CallInterface,
	Core_EqualityInterface
{

	protected $head;
	protected $body;

	/**
	 * Конструктор
	 *
	 */
	public function __construct()
	{
		$this->head = Mail_Message::Head();
	}

	/**
	 * Устанавливает заголовок сообщения
	 *
	 * @param Mail_Message_Head $head
	 *
	 * @return Mail_Message_Part
	 */
	public function head(Mail_Message_Head $head)
	{
		$this->head = $head;
		return $this;
	}

	/**
	 * Добавляет новое поля к заголовку письма
	 *
	 * @param string $name
	 * @param string $value
	 *
	 * @return Mail_Message_Part
	 */
	public function field($name, $value)
	{
		$this->head[$name] = $value;
		return $this;
	}

	/**
	 * Добавляет несколько полей заголовка из массива $headers
	 *
	 * @param array $headers
	 *
	 * @return Mail_Message_Part
	 */
	public function headers(array $headers)
	{
		foreach ($headers as $k => $v)
			$this->head[$k] = $v;
		return $this;
	}

	/**
	 * Заполняет письмо из файла, т.е. получаетя attach к письму
	 *
	 * @param        $file
	 * @param string $name
	 *
	 * @return Mail_Message_Part
	 */
	public function file($file, $name = '')
	{
		if (!($file instanceof IO_FS_File)) {
			$file = IO_FS::File((string)$file);
		}

		$this->head['Content-Type'] = array($file->content_type, 'name' => ($name ? $name : $file->name));
		$this->head['Content-Transfer-Encoding'] = $file->mime_type->encoding;
		$this->head['Content-Disposition'] = 'attachment';

		$this->body = $file;

		return $this;
	}

	/**
	 * Устанавливает содержимае письма
	 *
	 * @param  $body
	 *
	 * @return Mail_Message_Part
	 */
	public function body($body)
	{
		$this->body = $body;
		return $this;
	}

	/**
	 * Заполняет письмо из потока
	 *
	 * @param IO_Stream_AbstractStream $stream
	 * @param                          $content_type
	 *
	 * @return Mail_Message_Part
	 */
	public function stream(IO_Stream_AbstractStream $stream, $content_type = null)
	{
		if ($content_type) {
			$this->head['Content-Type'] = $content_type;
			$this->head['Content-Transfer-Encoding'] = MIME::type($this->head['Content-Type']->value)->encoding;
		}
		$this->body = $stream;
		return $this;
	}

	/**
	 * Заполняет письмо ввиде простого текста
	 *
	 * @param string $text
	 * @param        $content_type
	 *
	 * @return Mail_Message_Part
	 */
	public function text($text, $content_type = null)
	{
		$this->head['Content-Type'] = $content_type ?
			$content_type :
			array('text/plain', 'charset' => MIME::default_charset());

		$this->head['Content-Transfer-Encoding'] =
			MIME::type($this->head['Content-Type']->value)->encoding;

		$this->body = (string)$text;

		return $this;
	}

	/**
	 * Заполняет письмо ввиде html
	 *
	 * @param string $text
	 *
	 * @return Mail_Message_Part
	 */
	public function html($text)
	{
		return $this->text(
			$text,
			array('text/html', 'charset' => MIME::default_charset())
		);
	}

	/**
	 * Доступ на чтение к совйствам объекта
	 *
	 * @param string $property
	 *
	 * @return mixed
	 */
	public function __get($property)
	{
		switch ($property) {
			case 'head':
			case 'body':
				return $this->$property;
			default:
				throw new Core_MissingPropertyException($property);
		}
	}

	/**
	 * Доступ на запись к свойствам объекта
	 *
	 * @param string $property
	 * @param        $value
	 *
	 * @return mixed
	 */
	public function __set($property, $value)
	{
		switch ($property) {
			case 'body':
				$this->body($value);
				return $this;
			case 'head':
				throw new Core_ReadOnlyPropertyException($property);
			default:
				throw new Core_MissingPropertyException($property);
		}
	}

	/**
	 * Проверяет установлено ли свойство
	 *
	 * @param string $property
	 *
	 * @return boolean
	 */
	public function __isset($property)
	{
		switch ($property) {
			case 'body':
			case 'head':
				return true;
			default:
				return false;
		}
	}

	/**
	 * Выкидывает исключение Core.NotImplementedException
	 *
	 * @param string $property
	 */
	public function __unset($property)
	{
		throw new Core_NotImplementedException();
	}

	/**
	 * Возвращает закодированное письмо ввиде строки
	 *
	 * @return string
	 */
	public function as_string()
	{
		return Mail_Serialize::Encoder()->encode($this);
	}

	/**
	 * Возвращает закодированное письмо ввиде строки
	 *
	 * @return string
	 */
	public function __toString()
	{
		return $this->as_string();
	}

	/**
	 * С помощью вызова метода можно установить/добавить поле к заголовку письма
	 *
	 * @param string $method
	 * @param        $args
	 *
	 * @return Mail_Message_Part
	 */
	public function __call($method, $args)
	{
		$this->head[$this->field_name_for_method($method)] = $args[0];
		return $this;
	}

	/**
	 * @param string $method
	 *
	 * @return string
	 */
	protected function field_name_for_method($method)
	{
		return Core_Strings::replace($method, '_', '-');
	}

	/**
	 * @param  $to
	 *
	 * @return boolean
	 */
	public function equals($to)
	{
		$r = $to instanceof self &&
			Core::equals($this->head, $to->head);

		$this_body = ($this->body instanceof IO_Stream_AbstractStream ||
			$this->body instanceof IO_FS_File) ?
			$this->body->load() :
			$this->body;

		$to_body = ($to->body instanceof IO_Stream_AbstractStream ||
			$to->body instanceof IO_FS_File) ?
			$to->body->load() :
			$to->body;

		return $r && Core::equals($this_body, $to_body);
	}
}

/**
 * Почтовое письмо
 *
 * @package Mail\Message
 */
class Mail_Message_Message
	extends Mail_Message_Part
	implements IteratorAggregate
{

	protected $preamble = '';
	protected $epilogue = '';

	/**
	 * конструктор
	 *
	 * @param boolean $nested
	 */
	public function __construct($nested = false)
	{
		parent::__construct();
		if (!$nested) {
			$this->head['MIME-Version'] = '1.0';
			$this->date(Time::now());
		}
	}

	public function send($transport = null)
	{
		return Mail_Message::send($this, $transport);
	}

	/**
	 * Устанавливает заголовок письма в multipart с указанным типом и границей
	 *
	 * @param string $type
	 * @param string $boundary
	 *
	 * @return Mail_Message_Message
	 */
	public function multipart($type = 'mixed', $boundary = null)
	{
		$this->body = new ArrayObject();

		$this->head['Content-Type'] = array(
			'Multipart/' . ucfirst(strtolower($type)),
			'boundary' => ($boundary ? $boundary : MIME::boundary()));

		return $this;
	}

	/**
	 * Устанавливает заголовок письма в multipart/mixed
	 *
	 * @param string $boundary
	 *
	 * @return Mail_Message_Message
	 */
	public function multipart_mixed($boundary = null)
	{
		return $this->multipart('mixed', $boundary);
	}

	/**
	 * Устанавливает заголовок письма в multipart/alternative
	 *
	 * @param string $boundary
	 *
	 * @return Mail_Message_Message
	 */
	public function multipart_alternative($boundary = null)
	{
		return $this->multipart('alternative', $boundary);
	}

	/**
	 * Устанавливает заголовок письма в multipart/related
	 *
	 * @param string $boundary
	 *
	 * @return Mail_Message_Message
	 */
	public function multipart_related($boundary = null)
	{
		return $this->multipart('related', $boundary);
	}

	/**
	 * Устанавливает дату в заголовке
	 *
	 * @param  $date
	 *
	 * @return Mail_Message_Message
	 */
	public function date($date)
	{
		$this->head['Date'] = ($date instanceof Time_DateTime) ? $date->as_rfc1123() : (string)$date;
		return $this;
	}

	/**
	 * Добавляет к письму часть
	 *
	 * @param Mail_Message_Part $part
	 *
	 * @return Mail_Message_Message
	 */
	public function part(Mail_Message_Part $part)
	{
		if (!$this->body instanceof ArrayObject) {
			$this->body = new ArrayObject();
		}

		if ($this->is_multipart()) {
			$this->body->append($part);
		} else {
			throw new Mail_Message_Exception('Not multipart message');
		}
		return $this;
	}

	/**
	 * Добавляет к письму текстовую часть
	 *
	 * @param string $text
	 * @param        $content_type
	 *
	 * @return Mail_Message_Message
	 */
	public function text_part($text, $content_type = null)
	{
		return $this->part(Mail_Message::Part()->text($text, $content_type));
	}

	/**
	 * Добавляте к письму html-часть
	 *
	 * @param string $text
	 *
	 * @return Mail_Message_Message
	 */
	public function html_part($text)
	{
		return $this->part(Mail_Message::Part()->html($text));
	}

	/**
	 * Добавляет к письму attach фаил
	 *
	 * @param        $file
	 * @param string $name
	 *
	 * @return Mail_Message_Message
	 */
	public function file_part($file, $name = '')
	{
		return $this->part(Mail_Message::Part()->file($file, $name));
	}

	/**
	 * Добавляет к письму преабулу
	 *
	 * @param string $text
	 *
	 * @return Mail_Message_Message
	 */
	public function preamble($text)
	{
		$this->preamble = (string)$text;
		return $this;
	}

	/**
	 * Добавляет к письму эпилог
	 *
	 * @param string $text
	 *
	 * @return Mail_Message_Message
	 */
	public function epilogue($text)
	{
		$this->epilogue = (string)$text;
		return $this;
	}

	/**
	 * Возвращает итератор по вложенным частям письма
	 *
	 * @return Iterator
	 */
	public function getIterator()
	{
		return $this->is_multipart() ?
			$this->body->getIterator() :
			new ArrayIterator($this->body);
	}

	/**
	 * Проверяет имеет ли письмо вложения
	 *
	 * @return boolean
	 */
	public function is_multipart()
	{
		return Core_Strings::starts_with(
			Core_Strings::downcase($this->head['Content-Type']->value), 'multipart'
		);
	}

	/**
	 * Доступ на чтение к свойствам объекта
	 *
	 * @param string $property
	 *
	 * @return mixed
	 */
	public function __get($property)
	{
		switch ($property) {
			case 'preamble':
			case 'epilogue':
				return $this->$property;
			default:
				return parent::__get($property);
		}
	}

	/**
	 * Доступ на запись к свойствам объекта
	 *
	 * @param string $property
	 * @param        $value
	 *
	 * @return mixed
	 */
	public function __set($property, $value)
	{
		switch ($property) {
			case 'preamble':
			case 'epilogue':
				$this->$property = (string)$value;
				break;
			default:
				return parent::__set($property, $value);
		}
		return $this;
	}

	/**
	 * Проверяет установленно ли свойство объекта
	 *
	 * @param string $property
	 *
	 * @return boolean
	 */
	public function __isset($property)
	{
		switch ($property) {
			case 'preamble':
			case 'epilogue':
				return true;
			default:
				return parent::__isset($property);
		}
	}

	/**
	 * Выбрасывает исключение Core.UndestroyablePropertyException
	 *
	 * @param string $property
	 */
	public function __unset($property)
	{
		switch ($property) {
			case 'preamble':
			case 'epilogue':
				throw new Core_UndestroyablePropertyException($property);
			default:
				parent::__unset($property);
		}
	}

}

