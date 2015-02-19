<?php
/**
 * Mail.Serialize
 *
 * Модуль для кодирования и декодирования письма
 *
 * @package Mail\Serialize
 * @version 0.2.3
 */
Core::load('MIME', 'MIME.Encode', 'MIME.Decode', 'Mail.Message');

/**
 * @package Mail\Serialize
 */
class Mail_Serialize implements Core_ModuleInterface
{

	const MODULE = 'Mail.Serialize';
	const VERSION = '0.2.3';

	/**
	 * Фабричный метод, возвращает объект класаа Mail.Serialize.Encoder
	 *
	 * @return Mail_Serialize_Encoder
	 */
	static public function Encoder()
	{
		return new Mail_Serialize_Encoder();
	}

	/**
	 * Фабричный метод, возвращает объект класаа Mail.Serialize.Decoder
	 *
	 * @return Mail_Serialize_Decoder
	 */
	static public function Decoder()
	{
		return new Mail_Serialize_Decoder();
	}

}

/**
 * Кодирое почтовое сообщение
 *
 * @package Mail\Serialize
 */
class Mail_Serialize_Encoder
{

	protected $output = '';

	/**
	 * Устанавливает запись результата в поток
	 *
	 * @param IO_Stream_AbstractStream $output
	 *
	 * @return Mail_Serialize_Encoder
	 */
	public function to_stream(IO_Stream_AbstractStream $output)
	{
		$this->output = $output;
		return $this;
	}

	/**
	 * Устанавливает запись результата в строку
	 *
	 * @param stream $output
	 *
	 * @return Mail_Serialize_Encoder
	 */
	public function to_string($output = '')
	{
		$this->output = (string)$output;
		return $this;
	}

	/**
	 * Кодирует почтовое сообщение Mail.Message.Message
	 *
	 * @param Mail_Message_Part $message
	 *
	 * @return mixed
	 */
	public function encode(Mail_Message_Part $message)
	{
		$this->encode_head($message);
		$this->write();
		$this->encode_body($message);
		return $this->output;
	}

	/**
	 * Кодирует заголовок письма
	 *
	 * @param Mail_Message_Part $message
	 * @param array             $fields
	 *
	 * @return mixed
	 */
	public function encode_head(Mail_Message_Part $message, array $fields = array(true))
	{
		Core_Strings::begin_binary();

		$include_by_default = isset($fields[0]) ? (boolean)$fields[0] : true;

		foreach ($message->head as $field) {
			$name = $field->name;

			$include_field = isset($fields[$name]) ?
				$fields[$name] :
				$include_by_default;

			if ($include_field) {
				$this->write($field->encode());
			}
		}

		Core_Strings::end_binary();
		return $this->output;
	}

	/**
	 * Кодирует содержимое сообщения
	 *
	 * @param Mail_Message_Part $msg
	 *
	 * @return mixed
	 */
	public function encode_body(Mail_Message_Part $msg)
	{
		Core_Strings::begin_binary();

		if ($msg instanceof Mail_Message_Message && $msg->is_multipart()) {
			$boundary = $msg->head['Content-Type']['boundary'];
		}

		if (isset($boundary)) {
			if ($msg->preamble != '') {
				$this->write($msg->preamble);
			}

			foreach ($msg->body as $part) {
				$this->write("--$boundary");
				$this->encode($part);
			}
			$this->write("--$boundary--");

			if ($msg->epilogue != '') {
				$this->write($msg->epilogue);
			}
		} else {
			$body = ($msg->body instanceof IO_FS_File || $msg->body instanceof IO_Stream_ResourceStream) ?
				$msg->body->load() : $msg->body;
//      if ($msg->body instanceof IO_FS_File) {
//        foreach (
//          $this->encoder_for($msg)->
//            from_stream($msg->body->open()) as $line)
//              $this->write($line);
//        $msg->body->close();
//      } elseif ($msg->body instanceof IO_Stream_AbstractStream) {
//        foreach (
//          $this->encoder_for($msg)->from_stream($msg->body) as $line)
//            $this->write($line);
//      } else {
			$this->write($this->encoder_for($msg)->encode($body));
//      }
		}

		Core_Strings::end_binary();
		return $this->output;
	}

	/**
	 * Возвращает MIME-кодировщик для сообщения $msg
	 *
	 * @param Mail_Message_Part $msg
	 */
	protected function encoder_for(Mail_Message_Part $msg)
	{
		return MIME_Encode::encoder(isset($msg->head['Content-Transfer-Encoding']) ?
				$msg->head['Content-Transfer-Encoding']->value : null
		);
	}

	/**
	 * Пишет результат кодирования
	 *
	 * @return Mail_Serialize_Encoder
	 */
	protected function write($string = '')
	{
		if (substr($string, -1) != MIME::LINE_END) {
			$string .= MIME::LINE_END;
		}
		($this->output instanceof IO_Stream_AbstractStream) ?
			$this->output->write($string) :
			$this->output .= $string;
		return $this;
	}

}

/**
 * Разкодирует закодированное постовой сообщение
 *
 * @package Mail\Serialize
 */
class Mail_Serialize_Decoder
{

	protected $input;

	/**
	 * Устанвливает поток из которого береться сообщение
	 *
	 * @param IO_Stream_AbstractStream $stream
	 *
	 * @return Mail_Serializer_Decoder
	 */
	public function from(IO_Stream_AbstractStream $stream)
	{
		$this->input = $stream;
		return $this;
	}

	/**
	 * Декодирует сообщение
	 *
	 * @return Mail_Message_Message
	 */
	public function decode()
	{
		Core_Strings::begin_binary();
		$result = ($head = $this->decode_head()) ?
			$this->decode_part(Mail::Message()->head($head)) :
			null;
		Core_Strings::end_binary();
		return $result;
	}

	/**
	 * Декодирует часть сообщения
	 *
	 * @param Mail_Message_Part $parent
	 *
	 * @return Mail_Message_Part
	 */
	protected function decode_part(Mail_Message_Part $parent)
	{
		if ($parent->is_multipart()) {

			$parent->preamble($this->skip_to_boundary($parent));

			while (true) {
				if (!$head = $this->decode_head()) {
					break;
				}

				if (Core_Regexps::match('{^[Mm]ultipart}', $head['Content-Type']->value)) {

					$parent->part($this->decode_part(Mail::Message()->head($head)));

					$parent->epilogue($this->skip_to_boundary($parent));

				} else {
					$decoder = MIME_Decode::decoder($head['Content-Transfer-Encoding']->value)->
						from_stream($this->input)->
						with_boundary($parent->head['Content-Type']['boundary']);

					$parent->part(Mail::Part()->
							head($head)->
							body($this->is_text_content_type($head['Content-Type']->value) ?
									$decoder->to_string() :
									$decoder->to_temporary_stream()
							)
					);

					if ($decoder->is_last_part()) {
						break;
					}
				}
			}
		} else {
			$decoder = MIME_Decode::decoder($parent->head['Content-Transfer-Encoding']->value)->
				from_stream($this->input);

			$parent->body($this->is_text_content_type($head['Content-Type']->value) ?
					$decoder->to_string() :
					$decoder->to_temporary_stream()
			);
		}
		return $parent;
	}

	/**
	 * Определяет является ли тип текстовым
	 *
	 * @param string $type
	 *
	 * @return boolean
	 */
	protected function is_text_content_type($type)
	{
		return Core_Regexps::match('{^(text|message)/}', (string)$type);
	}

	/**
	 * Декодирует заголовок сообщения
	 *
	 * @return Mail_Message_Head
	 */
	protected function decode_head()
	{
		$data = '';

		while (($line = $this->input->read_line()) &&
			!Core_Regexps::match("{^\n\r?$}", $line))
			$data .= $line;

		return $this->input->eof() ? null : Mail_Message_Head::from_string($data);
	}

	/**
	 * Пролистывает сообщение до следующей границы
	 *
	 * @param string $boundary
	 *
	 * @return string
	 */
	protected function skip_to_boundary(Mail_Message_Part $part)
	{
		$text = '';
		while (($line = $this->input->read_line()) &&
			!Core_Regexps::match("{^--" . $part->head['Content-Type']['boundary'] . "(?:--)?\n\r?$}", $line))
			$text .= $line;
		return $text;
	}

}

