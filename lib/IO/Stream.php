<?php
/**
 * IO.Stream
 *
 * Работа с потоками ввода/вывода
 *
 * <p>Модуль обеспечивает минимальную объектную абстракцию потоков ввода/вывода, при этом
 * поток представляется в виде итерируемого объекта.</p>
 *
 * @package IO\Stream
 * @version 0.2.3
 */
Core::load('IO');

/**
 * Класс модуля
 *
 * <p>Определяет набор фабричных методов для создания экземпляров классов модуля.</p>
 * <p>Модуль также определяет следующие константы:</p>
 * DEFAULT_OPEN_MODE
 * режим открытия потока по умолчанию;
 * DEFAULT_CHUNK_SIZE
 * размер буфера чтения бинарного потока по умолчанию;
 * DEFAULT_LINE_LENGTH
 * максимальная длина строки текстового потока по умолчанию.
 *
 * @package IO\Stream
 */
class IO_Stream implements Core_ModuleInterface
{

	const VERSION = '0.2.3';

	const DEFAULT_OPEN_MODE = 'rb';

	const DEFAULT_CHUNK_SIZE = 8192;
	const DEFAULT_LINE_LENGTH = 1024;

	/**
	 * Создает объект класса IO.Stream.ResourceStream
	 *
	 * @param int $id
	 *
	 * @return IO_Stream_ResourceStream
	 */
	static public function ResourceStream($id)
	{
		return new IO_Stream_ResourceStream($id);
	}

	/**
	 * Создает объект класса IO.Stream.TemporaryStream
	 *
	 * @return IO_Stream_TemporaryStream
	 */
	static public function TemporaryStream()
	{
		return new IO_Stream_TemporaryStream();
	}

	/**
	 * Создает объект класса IO.Stream.NamedResourceStream
	 *
	 * @param string $uri
	 * @param string $mode
	 *
	 * @return IO_Stream_ResourceStream
	 */
	static public function NamedResourceStream($uri, $mode = IO_Stream::DEFAULT_OPEN_MODE)
	{
		return new IO_Stream_NamedResourceStream($uri, $mode);
	}

}

/**
 * Класс исключения
 *
 * @package IO\Stream
 */
class IO_Stream_Exception extends IO_Exception
{
}

/**
 * Базовый класс потока
 *
 * <p>Определяет интерфейс класса потока, предназначен для использования в качестве базового
 * класс при реализации специфичных классов потоков.</p>
 *
 * @abstract
 * @package IO\Stream
 */
abstract class IO_Stream_AbstractStream implements IteratorAggregate
{

	protected $binary = false;

	/**
	 * Читает данные из потока
	 *
	 * @param int $length
	 *
	 * @return string
	 */
	public function read($length = null)
	{
		return $this->binary ?
			$this->read_chunk($length) :
			$this->read_line($length);
	}

	/**
	 * Читает блок данных из бинарного потока.
	 *
	 * @abstract
	 *
	 * @param int $length
	 *
	 * @return string
	 */
	abstract public function read_chunk($length = null);

	/**
	 * Читает строку из текстового потока
	 *
	 * @abstract
	 *
	 * @param int $length
	 *
	 * @return string
	 */
	abstract public function read_line($length = null);

	/**
	 * Записывает данные в поток
	 *
	 * @abstract
	 *
	 * @param string $data
	 *
	 * @return IO_Stream_AbstractStream
	 */
	abstract public function write($data);

	/**
	 * Записывает данные в поток, используя форматирование в стиле printf
	 *
	 * @return IO_Stream_AbstractStream
	 */
	public function format()
	{
		$args = func_get_args();
		return $this->write(vsprintf(array_shift($args), $args));
	}

	/**
	 * Закрывает поток
	 *
	 */
	public function close()
	{
	}

	/**
	 * Устанвливает позицию в начало
	 *
	 */
	public function rewind()
	{
		return $this;
	}

	/**
	 * Переводит поток в бинарный режим
	 *
	 * @param boolean $is_binary
	 *
	 * @return IO_Stream_Stream
	 */
	public function binary($is_binary = true)
	{
		$this->binary = $is_binary;
		return $this;
	}

	/**
	 * Переводит поток в текстовый режим
	 *
	 * @param boolean $is_text
	 *
	 * @return IO_Stream_Stream
	 */
	public function text($is_text = true)
	{
		$this->binary = !$is_text;
		return $this;
	}

	/**
	 * Проверяет, достигнут ли конец потока
	 *
	 * @abstract
	 * @return boolean
	 */
	abstract public function eof();

}

/**
 * Поток, связанный с ресурсом
 *
 * <p>Представляет поток, связанный с неким ресурсом ввода/вывода по его идентификатору.</p>
 * <p>Свойства:</p>
 * id
 * идентификатор ресурса (только чтение).
 *
 * @package IO\Stream
 */
// TODO: id = null по умолчанию
class IO_Stream_ResourceStream
	extends IO_Stream_AbstractStream
	implements Core_PropertyAccessInterface
{

	protected $id = false;

	/**
	 * Конструктор
	 *
	 * @param int $id
	 */
	public function __construct($id)
	{
		$this->id = $id;
	}

	/**
	 * Записывает данные в поток
	 *
	 * @param  $data
	 *
	 * @return IO_Stream_ResourceStream
	 */
	public function write($data)
	{
		fwrite($this->id, (string)$data);
		return $this;
	}

	/**
	 * Записывает в поток строку, добавляя в конец символ перевода строки
	 *
	 * @param  $data
	 *
	 * @return IO_Stream_ResourceStream
	 */
	public function write_line($data)
	{
		$this->write($data . "\n");
		return $this;
	}

	/**
	 * @param  $data
	 *
	 * @return Io_Stream_ResourceStream
	 */
	public function line($data)
	{
		return $this->write_line($date);
	}

	/**
	 * Читает данные из бинарного потока
	 *
	 * @param int $length
	 *
	 * @return string
	 */
	public function read_chunk($length = null)
	{
		return fread($this->id, $length ? (int)$length : IO_Stream::DEFAULT_CHUNK_SIZE);
	}

	/**
	 * Читает строку из текстового потока
	 *
	 * @param int $length
	 *
	 * @return string
	 */
	public function read_line($length = null)
	{
		return (!$this->id || $this->eof()) ?
			null :
			fgets($this->id, $length ? (int)$length : IO_Stream::DEFAULT_LINE_LENGTH);
	}

	/**
	 * Определяет, достигнут ли конец потока
	 *
	 * @return boolean
	 */
	public function eof()
	{
		return !$this->id || @feof($this->id);
	}

	/**
	 * Закрывает поток
	 *
	 */
	public function close()
	{
		if ($this->id) {
			if (@fclose($this->id)) {
				$this->id = null;
			} else {
				throw new IO_Stream_Exception("Unable to close named resource: $this->id");
			}
		}
	}

	/**
	 * Устанвливает позицию в начало
	 *
	 */
	public function rewind()
	{
		@rewind($this->id);
		return $this;
	}

	/**
	 * Возвращает все содержимое потока в виде строки
	 *
	 * @return string
	 */
	public function load()
	{
		$this->rewind();
		return stream_get_contents($this->id);
	}

	/**
	 * Возвращает значение свойства
	 *
	 * @param string $property
	 *
	 * @return mixed
	 */
	public function __get($property)
	{
		switch ($property) {
			case 'id':
			case 'binary':
				return $this->$property;
			default:
				throw new Core_MissingPropertyException($property);
		}
	}

	/**
	 * Устанавливает значение свойства
	 *
	 * @param string $property
	 * @param        $value
	 */
	public function __set($property, $value)
	{
		switch ($property) {
			case 'id':
			case 'binary':
				throw new Core_ReadOnlyPropertyException($property);
			default:
				throw Core_MissingPropertyException($property);
		}
	}

	/**
	 * Проверяет установку значения свойства
	 *
	 * @param string $property
	 *
	 * @return boolean
	 */
	public function __isset($property)
	{
		switch ($property) {
			case 'id':
			case 'binary':
				return true;
			default:
				return false;
		}
	}

	/**
	 * Удаляет свойство
	 *
	 * @param string $property
	 */
	public function __unset($property)
	{
		switch ($property) {
			case 'id':
			case 'binary':
				throw new Core_ReadOnlyPropertyException($property);
			default:
				throw new Core_MissingPropertyException($property);
		}
	}

	/**
	 * Создает итератор потока класса IO.Stream.Iterator
	 *
	 */
	public function getIterator()
	{
		return new IO_Stream_Iterator($this);
	}

}

/**
 * Поток для временных файлов
 *
 * @package IO\Stream
 */
class IO_Stream_TemporaryStream extends IO_Stream_ResourceStream
{

	/**
	 * Конструктор
	 *
	 */
	public function __construct()
	{
		parent::__construct(tmpfile());
	}

	/**
	 * Декструктор
	 *
	 */
	public function __destruct()
	{
		$this->close();
	}

}

/**
 * Поток для именованных ресурсов
 *
 * @package IO\Stream
 */
class IO_Stream_NamedResourceStream extends IO_Stream_ResourceStream
{

	/**
	 * Конструктор
	 *
	 * @param string $uri
	 * @param string $mode
	 */
	public function __construct($uri, $mode = IO_Stream::DEFAULT_OPEN_MODE)
	{
		if (!$this->id = @fopen($uri, $mode)) {
			throw new IO_Stream_Exception("Unable to open named resource: $uri");
		}
	}

	/**
	 * Деструктор
	 *
	 */
	public function __destruct()
	{
		$this->close();
	}

}

/**
 * Интерфейс позиционирования в потоке
 *
 * <p>Интерфейс должен быть реализован классами потоков, допускающих позиционирование,
 * например, файловыми потоками.</p>
 * <p>Интерфейс определяет набор констант, задающих тип смещения:</p>
 * SEEK_SET
 * абсолютное позицинировние;
 * SEEK_CUR
 * позиционирование относительно текущего положения;
 * SEEK_END
 * позиционирование относительно конца файла.
 *
 * @package IO\Stream
 */
interface IO_Stream_SeekInterface
{

	const SEEK_SET = 0;
	const SEEK_CUR = 1;
	const SEEK_END = 2;

	/**
	 * Устанавливает текущую позицию в потоке
	 *
	 * @param int $offset
	 * @param int $whence
	 *
	 * @return number
	 */
	public function seek($offset, $whence);

	/**
	 * Возвращает текущую позицию в потоке
	 *
	 * @return number
	 */
	public function tell();

}

/**
 * Итератор потока
 *
 * <p>Позволяет использовать объект потока в качестве итератора, например, внутри цикла
 * foreach. В большинстве случаев объекты этого класса используются неявно через интерфейс
 * IteratorAggregate потока.</p>
 * <p>Чтение данных зависит от типа потока: для текстовых оно производится построчно, для
 * бинарных -- порциями размера, соответствующего размеру буфера чтения. Ключи итератора
 * соответствуют порядковому номеру операции чтения, начиная с 1.</p>
 *
 * @package IO\Stream
 */
class IO_Stream_Iterator implements Iterator
{

	private $stream;

	private $data = null;
	private $data_count = 0;

	/**
	 * Конструктор
	 *
	 * @param IO_Stream_IOStream $stream
	 */
	public function __construct(IO_Stream_AbstractStream $stream)
	{
		$this->stream = $stream;
	}

	/**
	 * Возвращает очередной элемент
	 *
	 * @return string
	 */
	public function current()
	{
		return $this->data === null ? $this->read() : $this->data;
	}

	/**
	 * Возвращает ключ для очередного элемента
	 *
	 * @return int
	 */
	public function key()
	{
		return $this->data_count;
	}

	/**
	 * Возвращает следующий элемент.
	 *
	 */
	public function next()
	{
		$this->read();
	}

	/**
	 * Сбрасывает итератор
	 *
	 */
	public function rewind()
	{
		$this->data_count = 0;
		$this->stream->rewind();
	}

	/**
	 * Проверяет доступность элементов итератора
	 *
	 * @return boolean
	 */
	public function valid()
	{
		return $this->data_count == 0 ? !$this->stream->eof() : !$this->data === false;
	}

	/**
	 * Выполняет чтение очередной порции данных из потока
	 *
	 * @return string
	 */
	private function read()
	{
		if (($this->data = $this->stream->read()) !== false) {
			$this->data_count++;
		}
		return $this->data;
	}

}

