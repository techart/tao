<?php
/**
 * Mail.Transport
 *
 * Модуль предоставляет классы для отправки сообщений
 *
 * @package Mail\Transport
 * @version 0.2..0
 */

Core::load('Mail', 'Proc');

/**
 * @package Mail\Transport
 */
class Mail_Transport implements Core_ModuleInterface
{

	const MODULE = 'Mail.Transport';
	const VERSION = '0.2.0';

	/**
	 * Фабричный метод, возвращает объект класса Mail.Transport.Sendmail.Sender
	 *
	 * @param array $options
	 *
	 * @return Mail_Transport_Sendmail_Sender
	 */
	static public function sendmail(array $options = array())
	{
		Core::load('Mail.Transport.Sendmail');
		return new Mail_Transport_Sendmail_Sender($options);
	}

	/**
	 * Фабричный метод, возвращает объект класса Mail.Transport.PHP.Sender
	 *
	 * @return Mail_Transport_PHP_Sender
	 */
	static public function php()
	{
		Core::load('Mail.Transport.PHP');
		return new Mail_Transport_PHP_Sender();
	}

}

/**
 * Класс исключения
 *
 * @package Mail\Transport
 */
class Mail_Transport_Exception extends Mail_Exception
{
}

/**
 * Абстрактный класс для отправки сообщения
 *
 * @abstract
 * @package Mail\Transport
 */
//TODO: А почему не интерфейс?
abstract class Mail_Transport_AbstractSender
{

	/**
	 * Отправляет сообщение
	 *
	 * @param Mail_Message_Message $msg
	 *
	 * @return boolean
	 */
	abstract public function send(Mail_Message_Message $msg);

}

