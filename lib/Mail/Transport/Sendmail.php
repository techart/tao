<?php
/**
 * Mail.Transport.Sendmail
 *
 * Модуль для отправки сообщения с помощью стандартной утилиты sendmail
 *
 * @package Mail\Transport\Sendmail
 * @version 0.2.1
 */

Core::load('Mail.Transport');

/**
 * @package Mail\Transport\Sendmail
 */
class Mail_Transport_Sendmail implements Core_ConfigurableModuleInterface
{

	const MODULE = 'Mail.Transport.Sendmail';
	const VERSION = '0.2.1';

	static protected $options = array(
		'binary' => 'sendmail',
		'flags' => '-t -i');

	/**
	 * Инициализация модуля
	 *
	 * @param array $options
	 */
	static public function initialize(array $options = array())
	{
		self::options($options);
	}

	/**
	 * Устанавливает опции из массива $options
	 *
	 * @param array $options
	 *
	 * @return mixed
	 */
	static public function options(array $options = array())
	{
		if (count($options)) {
			Core_Arrays::update(self::$options, $options);
		}
		return self::$options;
	}

	/**
	 * Устанавливает опцию модуля
	 *
	 * @param string $name
	 * @param        $value
	 *
	 * @return mixed
	 */
	static public function option($name, $value = null)
	{
		$prev = isset(self::$options[$name]) ? self::$options[$name] : null;
		if ($value !== null) {
			self::options(array($name => $value));
		}
		return $prev;
	}

	/**
	 * Фабричный метод, возвращает объект класса Mail.Transport.Sendmail.Sender
	 *
	 * @param array $options
	 *
	 * @return Mail_Transport_Sendmail_Sender
	 */
	static public function Sender(array $options = array())
	{
		return new Mail_Transport_Sendmail_Sender($options);
	}

}

/**
 * @package Mail\Transport\Sendmail
 */
class Mail_Transport_Sendmail_Sender extends Mail_Transport_AbstractSender
{

	protected $options;

	/**
	 * Конструктор
	 *
	 * @param array $options
	 */
	public function __construct(array $options = array())
	{
		$this->options = Mail_Transport_Sendmail::options();
		Core_Arrays::update($this->options, $options);
	}

	/**
	 * Отправляет сообщение
	 *
	 * @param Mail_Message_Message $message
	 *
	 * @return boolean
	 */
	public function send(Mail_Message_Message $message)
	{
		$pipe = Proc::Pipe($this->sendmail_command(), 'wb');

		Mail_Serialize::Encoder()->
			to_stream($pipe)->
			encode($message);

		return $pipe->close()->exit_status ? false : true;
	}

	/**
	 * Возвращает команду для вызова sendmail
	 *
	 * @return string
	 */
	protected function sendmail_command()
	{
		return $this->options['binary'] . ' ' . $this->options['flags'];
	}

}

