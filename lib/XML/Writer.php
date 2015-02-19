<?php
/**
 * <code>
 *    $xml_writer = new XML_Writer;
 *    $xml_writer->open();
 *    // or $xml_writer->open('./uri_to_open');
 *    
 *    $xml_writer->document('1.0', 'UTF-8');
 *    $xml_writer->start_element('offers');
 * 
 *    for ($i = 0; $i < 10; $i++) {
 *        $xml_writer
 * 	          ->offer(array('campaign' => '1234123', 'type' => 'direct'))
 *                ->title('Участки, Абабурово')
 *                ->description('12 км от МКАД, от 13,5 млн. р. Только актуальные предложения. Звоните!')
 *                ->url('http://www.my-homes.ru/город/Абабурово/11402/?type[0]=110&utm_source=yandexdirect&utm_medium=cpc&utm_campaign=objects-lot')
 *                ->geo(1)
 *                ->keyword('price', '0.5')
 *                    ->text('земля Абабурово')
 *                ->end
 *                ->keyword('price', '0.5')
 *                    ->text('участок Абабурово')
 *                ->end
 *                ->cdata
 *                    ->text('asdfasdf')
 *                ->end
 *            ->end;
 *    }
 * 
 *    $xml_string = $xml_writer->output();
 * </code>
 */
class XML_Writer extends XMLWriter implements Core_ModuleInterface
{
	protected $start_document = false;
	protected $start_comment  = false;
	protected $start_cdata    = false;
	protected $start_raw      = false;
	protected $start_element  = 0;
	
	protected $memory_open    = false;
	protected $uri_open       = '';
	protected $is_open        = false;

	protected $indent         = false;
	protected $indent_string  = "\t";

	public function open($uri = null)
	{
		if (is_null($uri)) {
			return $this->open_memory();
		}

		return $this->open_uri($uri);
	}

	/**
	 * @param string $uri
	 * 
	 * @see XMLWriter::openURI
	 */
	public function open_uri($uri)
	{
		$this->is_open = $this->openURI($this->uri_open = $uri);
		if (!$this->is_open) {
			throw new Core_InvalidArgumentValueException('uri', $uri);
		}

		return $this->indent($this->indent, $this->indent_string);
	}

	public function open_memory()
	{
		$this->is_open = $this->memory_open = $this->openMemory();
		if (!$this->is_open) {
			throw new Core_Exception('Unable memory open');
		}

		return $this->indent($this->indent, $this->indent_string);
	}

	public function indent($indent = true, $indent_string = "\t")
	{
		if ($this->is_open) {
			$this->setIndent($indent);
			$this->setIndentString($indent_string);
		} else {
			$this->indent        = $indent;
			$this->indent_string = $indent_string;
		}

		return $this;
	}

	/**
	 * @see XMLWriter::outputMemory
	 */
	public function output($flush = true)
	{
		$this->tags_auto_close();

		if (!$this->is_open) {
			return '';
		}

		if ($this->memory_open) {
			return $this->outputMemory($flush);
		}

		return file_get_contents($this->uri_open);
	}

	/**
	 * @param string $version
	 * @param string $encoding
	 * @param string $standalone
	 * 
	 * @see XMLWriter::startDocument
	 */
	public function start_document($version = '1.0', $encoding = 'UTF-8', $standalone = null)
	{
		$this->start_document = true;
		$this->startDocument($version, $encoding, $standalone);
		return $this;
	}

	/**
	 * @see XMLWriter::endDocument
	 */
	public function end_document()
	{
		$this->start_document = false;
		$this->endDocument();
		return $this;
	}

	/**
	 * @see XMLWriter::writeDTD
	 */
	public function dtd($name, $public_id = null, $system_id = null, $subset = null)
	{
		$this->writeDTD($name, $public_id, $system_id, $subset);
		return $this;
	}

	/**
	 * Открытие (или запись) тега <!CDATA[
	 * 
	 * @param string $text
	 * 
	 * @see XMLWriter::openCData, XMLWriter::writeCData
	 */
	public function start_cdata($text = null)
	{
		if (is_null($text)) {
			$this->startCData();
			$this->start_cdata = true;

			return $this;
		}

		$this->writeCData($text);

		return $this;
	}

	/**
	 * @see XMLWriter::endCData
	 */
	public function end_cdata()
	{
		if ($this->start_cdata) {
			$this->endCData();
			$this->start_cdata = false;
		}

		return $this;
	}

	/**
	 * Открытие (или запись) комментария
	 * 
	 * @param string $text
	 * 
	 * @see XMLWriter::startComment, XMLWriter::endComment
	 */
	public function start_comment($text = null)
	{
		if (is_null($text)) {
			$this->startComment();
			$this->start_comment = true;

			return $this;
		}

		$this->writeComment($text);

		return $this;
	}

	/**
	 * @see XMLWriter::endComment
	 */
	public function end_comment()
	{
		if ($this->start_comment) {
			$this->endComment();
			$this->start_comment = false;
		}

		return $this;
	}

	public function start_raw($text = null)
	{
		if (is_null($text)) {
			$this->start_raw = true;
			return $this;
		}

		$this->writeRaw($text);

		return $this;
	}

	public function end_raw()
	{
		$this->start_raw = false;
		return $this;
	}

	public function attribute($name, $value)
	{
		$this->startAttribute($name);
		$this->text($value);
		$this->endAttribute();

		return $this;
	}

	public function attributes(array $attributes)
	{
		foreach($attributes as $name => $value) {
			$this->attribute($name, $value);
		}

		return $this;
	}

	public function start_element($tag_name, $attributes = array(), $text = null)
	{
		$this->start_element++;
		$this->startElement($tag_name);

		if (!empty($attributes)) {
			$this->attributes($attributes);
		}

		if (!is_null($text)) {
			$this->text($text);
			$this->end_element();
		}

		return $this;
	}

	public function end_element()
	{
		$this->start_element--;
		$this->endElement();
		return $this;
	}

	public function text($string)
	{
		if ($this->start_raw) {
			$this->writeRaw($string);
			return $this;
		}

		parent::text($string);
		return $this;
	}

	/**
	 * @return mixed
	 */
	public function end($informed_if_all_closed = false)
	{
		switch (true) {
			case $this->start_raw:
				return $this->end_raw();

			case $this->start_cdata:
				return $this->end_cdata();

			case $this->start_comment:
				return $this->end_comment();

			case $this->start_element > 0:
				return $this->end_element();

			case $this->start_document:
				return $this->end_document();
		}

		return $informed_if_all_closed ? true : $this;
	}

	public function tags_auto_close()
	{
		while($this->end(true) !== true) {}
	}

	public function __get($property)
	{
		switch ($property) {
			case 'open':
				return $this->open();

			case 'indent':
				return $this->indent();

			case 'document':
			case 'cdata':
			case 'comment':
				return $this->$property();
			
			case 'end':
				return $this->end();

			default:
				return $this->start_element($property);
		}
	}

	public function __call($method, $args)
	{
		switch ($method) {
			case 'document':
			case 'cdata':
			case 'comment':
				$method = 'start_'. $method;
				return call_user_func_array(array($this, $method), $args);
		}

		$tag_name   = $method;
		$attributes = array();
		$text       = null;

		if (count($args) == 1) {
			$arg = reset($args);
			if (is_array($arg)) {
				$attributes = $arg;
			} else {
				$text = $arg;
			}
		} else {
			for ($i = 0, $count = count($args); $i < $count; $i += 2) {
				$k = $args[$i];
				$v = $args[$i + 1];
				
				$attributes[$k] = $v;
			}
		}

		return $this->start_element($tag_name, $attributes, $text);
	}
}