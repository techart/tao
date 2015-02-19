<?php
/**
 * IO.FS
 *
 * Работа с файловой системой
 *
 * <p>Модуль реализует набор классов для работы с файлами и каталогами. Для файлов и
 * каталогов реализованы соответствующие объектные представления. Также реализован
 * объектный интерфейс для запроса списка объектов файловой системы по определенным
 * критериям.</p>
 *
 * @package IO\FS
 * @version 0.2.2
 */
Core::load('Time', 'IO.Stream', 'MIME', 'Events');

/**
 * Класс модуля
 *
 * <p>Содержит набор фабричных методов для создания экземпляров классов модуля, а также
 * процедурный интерфейс для работы с файлами.</p>
 *
 * @package IO\FS
 */
class IO_FS implements Core_ConfigurableModuleInterface
{

	const VERSION = '0.2.2';

	static protected $options = array(
		'dir_mod' => 0755,
		'file_mod' => 0755,
		'dir_own' => false,
		'file_own' => false,
		'dir_grp' => false,
		'file_grp' => false
	);

	/**
	 * Инициализация
	 *
	 * @param array $options
	 */
	static public function initialize(array $options = array())
	{
		$opts = isset(Config::all()->iofs) ? (array) Config::all()->iofs : array();
		self::options($opts);
		self::options($options);
	}

	/**
	 * Устанавливает опции модуля
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
	 * Устанавливает/возвращает опцию модуля
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
	 * Создает объект класса IO.FS.File
	 *
	 * @param string $path
	 *
	 * @return IO_FS_File
	 */
	static public function File($path)
	{
		return $path instanceof IO_FS_File ? $path : new IO_FS_File($path);
	}

	/**
	 * Создает объект класса IO.FS.FileStream
	 *
	 * @param string $path
	 *
	 * @return IO_FS_FileStream
	 */
	static public function FileStream($path, $mode = IO_Stream::DEFAULT_OPEN_MODE)
	{
		return new IO_FS_FileStream($path, $mode);
	}

	/**
	 * Создает объект класса IO.FS.Stat
	 *
	 * @param  $object
	 *
	 * @return IO_FS_Stat
	 */
	static public function Stat($object)
	{
		return new IO_FS_Stat($object);
	}

	/**
	 * Создает объект класса IO.FS.Dir
	 *
	 * @param string $path
	 *
	 * @return IO_FS_Dir
	 */
	static public function Dir($path = '.')
	{
		return $path instanceof IO_FS_Dir ? $path : new IO_FS_Dir($path);
	}

	/**
	 * Создает объект  класса IO.FS.Path
	 *
	 * @param string $path
	 *
	 * @return IO_FS_Path
	 */
	static public function Path($path)
	{
		return new IO_FS_Path($path);
	}

	/**
	 * Создает объект класса IO.FS.Query
	 *
	 * @return IO_FS_Query
	 */
	static public function Query()
	{
		return new IO_FS_Query();
	}

	/**
	 * Возвращает объект классов IO.FS.Dir или IO.FS.File по заданному пути
	 *
	 * @param string $path
	 *
	 * @return IO_FS_FSObject
	 */
	static public function file_object_for($path)
	{
		return self::exists($path = (string)$path) ?
			(self::is_dir($path) ? self::Dir($path) : self::File($path)) :
			null;
	}

	/**
	 * Возвращает объект класса IO.FS.Dir, соответствующий текущему каталогу.
	 *
	 * @return IO_FS_Directory
	 */
	static public function pwd()
	{
		return self::Dir(getcwd());
	}

	/**
	 * Переходит в указанный каталог и возвращает соответствующий объект класса IO.FS.Dir
	 *
	 * @param string $path
	 *
	 * @return IO_FS_Directory
	 */
	static public function cd($path)
	{
		return chdir($path) ? self::Dir(getcwd()) : null;
	}

	/**
	 * Создает каталог и возвращает соответствующий объект класса IO.FS.Dir
	 *
	 * @param string  $path
	 * @param int     $mode
	 * @param boolean $recursive
	 *
	 * @return boolean
	 */
	static public function mkdir($path, $mode = null, $recursive = true)
	{
		$mode = self::get_permision_for(null, $mode, 'mod', 'dir');
		$old = umask(0);
		$rs = (self::exists((string)$path) || mkdir((string)$path, $mode, $recursive)) ?
			self::Dir($path) : null;
		umask($old);
		return $rs;
	}

	static protected function get_permision_for($path, $value, $type, $obj = null)
	{
		if (!is_null($value)) {
			return $value;
		}
		$object = !empty($path) ? (is_dir($path) ? 'dir' : 'file') : $obj;
		return self::option("{$object}_{$type}");
	}

	/**
	 * Изменяет права доступа к файлу
	 *
	 * @param string $file
	 * @param int    $mode
	 *
	 * @return boolean
	 */
	static public function chmod($file, $mode = null)
	{
		$mode = self::get_permision_for($file, $mode, 'mod');
		return $mode ? @chmod((string)$file, $mode) : false;
	}

	public static function chmod_recursive($path, $mode = null)
	{
		$object = self::file_object_for($path);
		if ($object->exists()) {
			$object->chmod($mode);
			if (self::is_dir($object->path)) {
				foreach (self::Query()->recursive(true, true)->apply_to($object) as $nested) {
					$nested->chmod($mode);
				}
			}
		}
	}

	/**
	 * Изменяет владельца файла
	 *
	 * @return boolean
	 */
	static public function chown($file, $owner = null)
	{
		$owner = self::get_permision_for($file, $owner, 'own');
		return $owner ? @chown((string)$file, $owner) : false;
	}

	static function chgrp($file, $group = null)
	{
		$group = self::get_permision_for($file, $group, 'grp');
		return $group ? @chgrp((string)$file, $group) : false;
	}

	/**
	 * Удаляет файл
	 *
	 * @param string $path
	 *
	 * @return boolean
	 */
	static public function rm($path)
	{
		$obj = self::file_object_for($path);
		return $obj ? $obj->rm() : false;
	}

	/**
	 * Удаляет файл
	 *
	 * @param string $path
	 *
	 * @return boolean
	 */
	static public function clear_dir($path)
	{
		if (!self::is_dir($path)) {
			return false;
		}
		$dir = self::Dir($path);
		$rc = true;
		foreach ($dir as $o)
			$rc = $o->rm() && $rc;
		return $rc;
	}

	/**
	 * Создает вложенные каталоги
	 *
	 * @param string $path
	 * @param int    $mode
	 *
	 * @return boolean
	 */
	static public function make_nested_dir($path, $mode = null)
	{
		return self::mkdir($path, $mode, true);
	}

	/**
	 * Проверяет существование файла или каталога
	 *
	 * @param string $path
	 *
	 * @return boolean
	 */
	static public function exists($path)
	{
		return file_exists((string)$path);
	}

	/**
	 * Проверяет, является ли файловый объект с заданным путем каталогом
	 *
	 * @param string $path
	 *
	 * @return boolean
	 */
	static public function is_dir($path)
	{
		return is_dir((string)$path);
	}

	/**
	 * Перемещает файл
	 *
	 * @param string $from
	 * @param string $to
	 *
	 * @return boolean
	 */
	static public function mv($from, $to)
	{
		$obj = self::file_object_for($from);
		return $obj ? $obj->move_to($to) : false;
	}

	/**
	 * Копирует файл
	 *
	 * @param string $from
	 * @param string $to
	 *
	 * @return boolean
	 */
	static public function cp($from, $to)
	{
		$obj = self::file_object_for($from);
		return $obj ? $obj->copy_to($to) : false;
	}

}

/**
 * Базовый класс исключений модуля
 *
 * @package IO\FS
 */
class IO_FS_Exception extends IO_Exception
{
}

/**
 * Класс исключений для ошибок получения информации о файловом объекте
 *
 * @package IO\FS
 */
class IO_FS_StatException extends IO_FS_Exception
{

	protected $object;

	/**
	 * Конструктор
	 *
	 * @param  $object
	 */
	public function __construct($object)
	{
		parent::__construct("Can't stat object" . ((string)($this->object = $object)));
	}

}

/**
 * Объектное представление пути в файловой системе
 *
 * <p>Объект представляет собой объектную обертку над встроенной функцией pathinfo().
 * Соответственно, он обеспечивает доступ к следующим свойствам:</p>
 * dirnameпуть к файлу
 * basenameбазовое имя файла
 * extensionрасширение
 * filenameимя файла
 *
 * @package IO\FS
 */
class IO_FS_Path implements Core_PropertyAccessInterface
{

	protected $info = array();

	/**
	 * Конструктор
	 *
	 * @param string $path
	 */
	public function __construct($path)
	{
		$this->info = @pathinfo((string)$path);
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
		if (isset($this->info[$property])) {
			return $this->info[$property];
		} elseif ($property == 'extension') {
			return '';
		} else {
			throw new Core_MissingPropertyException($property);
		}
	}

	/**
	 * Устанавливает значение свойства
	 *
	 * @param string $property
	 * @param        $value
	 *
	 * @return mixed
	 */
	public function __set($property, $value)
	{
		throw new Core_ReadOnlyObjectException($this);
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
		return isset($this->info[$property]);
	}

	/**
	 * Удаляет свойство
	 *
	 * @param string $property
	 */
	public function __unset($property)
	{
		throw new Core_ReadOnlyObjectException($this);
	}

}

/**
 * Объектное представление метаинформации об объекте файловой системы
 *
 * <p>Объект предоставляет ту же информацию, что и встроенные функции fstat() и stat(), однако
 * делает это более удобным образом: при создании объекта может быть использован как путь
 * к файлу, так и файловый ресурс, все свойства, содержащие дату, возвращаются в виде
 * объектов класса Time.DateTime.</p>
 *
 * @package IO\FS
 */
class IO_FS_Stat implements Core_PropertyAccessInterface
{
	protected $stat = array();

	/**
	 * Конструктор
	 *
	 * @param  $object
	 */
	public function __construct($object)
	{
		if (!$stat = Core_Types::is_resource($object) ?
			@fstat($object) : @stat((string)$object)
		) {
			throw new IO_FS_StatException($object);
		}

		foreach ($stat as $k => $v) {
			switch ($k) {
				case 'atime':
				case 'mtime':
				case 'ctime':
					$this->stat[$k] = Time::DateTime($v);
					break;
				default:
					$this->stat[$k] = $v;
			}
		}
	}

	/**
	 * Возвращает значние свойства
	 *
	 * @param string $property
	 *
	 * @return mixed
	 */
	public function __get($property)
	{
		if (isset($this->stat[$property])) {
			return $this->stat[$property];
		} else {
			throw new Core_MissingPropertyException($property);
		}
	}

	/**
	 * Устанавливает значение свойства
	 *
	 * @param string $property
	 * @param        $value
	 *
	 * @return mixed
	 */
	public function __set($property, $value)
	{
		throw new Core_ReadOnlyObjectException($this);
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
		return isset($this->stat[$property]);
	}

	/**
	 * Удаляет свойство
	 *
	 * @param string $property
	 */
	public function __unset($property)
	{
		throw new Core_ReadOnlyObjectException($this);
	}

}

/**
 * Базовый класс объектов файловой системы
 *
 * <p>Предназначен для использования в качестве базового класса для более специфичных классов,
 * представляющих файлы и каталоги. Каждый объект класса характеризуется своим именем и
 * метаданными, содержащимися в виде объекта IO.FS.Stat, загружаемого по требованию.</p>
 * <p>Класс также реализует набор операций, в равной степени применимых к файлам и
 * каталогам.</p>
 * <p>Свойства:</p>
 * pathпуть к файлу;
 * dir_nameимя каталога;
 * nameимя файла;
 * real_pathреальный путь к файлу (с раскрытыми ., .., и т.д.);
 * statметаданные в виде объекта класса IO.FS.Stat.
 *
 * @package IO\FS
 */
class IO_FS_FSObject implements Core_StringifyInterface, Core_EqualityInterface
{

	protected $path;
	protected $stat;

	/**
	 * Конструктор
	 *
	 * @param string $path
	 */
	public function __construct($path)
	{
		$this->path = $path;
	}

	/**
	 * Изменяет права доступа к файловому объекту
	 *
	 * @param int $mode
	 *
	 * @return boolean
	 */
	public function chmod($mode = null)
	{
		$this->stat = null;
		return IO_FS::chmod($this->path, $mode);
	}

	/**
	 * Изменяет владельца файлового объекта
	 *
	 * @param  $owner
	 *
	 * @return boolean
	 */
	public function chown($owner = null)
	{
		$this->stat = null;
		return IO_FS::chown($this->path, $owner);
	}

	public function chgrp($group = null)
	{
		$this->stat = null;
		return IO_FS::chgrp($this->path, $group);
	}

	public function set_permission($mode = null, $owner = null, $group = null)
	{
		$this->chmod($mode);
		$this->chown($owner);
		$this->chgrp($group);
		return $this;
	}

	public function exists()
	{
		return file_exists($this->path);
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
			case 'path':
				return $this->path;
			case 'dir_name':
			case 'dirname':
				return @dirname($this->path);
			case 'name':
				return @basename($this->path);
			case 'real_path':
				return @realpath($this->path);
			case 'stat':
				if (!$this->stat) {
					$this->stat = IO_FS::Stat($this->path);
				}
				return $this->stat;
			default:
				throw new Core_MissingPropertyException($property);
		}
	}

	/**
	 * Устанавливает значение свойства
	 *
	 * @param string $property
	 * @param        $value
	 *
	 * @return mixed
	 */
	public function __set($property, $value)
	{
		switch ($property) {
			case 'path':
			case 'dir_name':
			case 'name':
			case 'real_path':
			case 'stat':
				throw new Core_ReadOnlyPropertyException($property);
			default:
				throw new Core_MissingPropertyException($property);
		}
	}

	/**
	 * Проверяет установку свойства объекта
	 *
	 * @param string $property
	 *
	 * @return boolean
	 */
	public function __isset($property)
	{
		switch ($property) {
			case 'path':
			case 'dir_name':
			case 'name':
			case 'real_path':
			case 'stat':
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
	public function __unset($property)
	{
		switch ($property) {
			case 'path':
			case 'dir_name':
			case 'name':
			case 'real_path':
			case 'stat':
				throw new Core_UndestroyablePropertyException($property);
			default:
				throw new Core_MissingPropertyException($property);
		}
	}

	/**
	 * @return string
	 */
	public function as_string()
	{
		return $this->real_path;
	}

	/**
	 * @return string
	 */
	public function __toString()
	{
		return $this->real_path;
	}

	/**
	 * @param  $to
	 *
	 * @return boolean
	 */
	public function equals($to)
	{
		return $to instanceof self &&
		$this->real_path == $to->real_path;
	}

}

/**
 * Объектное представление файла
 *
 * <p>Файл представляет собой файловый объект, для которого могут быть получены метаданные и
 * открыт файловый поток. Объект файла предоставляет также возможность получения информации
 * о MIME-типе, соответствующем файлу, с использованием модуля MIME.</p>
 * <p>Объект может работать как итератор, в этом случае соответствующий файл открывается на
 * чтение и для него создается соответствующий поток.</p>
 * <p>Свойства:</p>
 * streamассоциированный поток;
 * sizeразмер файла;
 * mime_typeMIME-тип файла в виде объекта;
 * content_typeMIME-тип файла в виде строки.
 *
 * @package IO\FS
 */
class IO_FS_File
	extends IO_FS_FSObject
	implements Core_PropertyAccessInterface,
	IteratorAggregate
{

	protected $mime_type;
	protected $stream;

	/**
	 * Деструктор
	 *
	 */
	public function __destroy()
	{
		$this->close();
		parent::__destroy();
	}

	public function create()
	{
		return $this->update('', null);
	}

	/**
	 * Создает поток класса IO.FS.FileStream, соответствующий файлу
	 *
	 * @param string $mode
	 */
	public function open($mode = IO_Stream::DEFAULT_OPEN_MODE)
	{
		return ($this->stream && $this->stream->id) ?
			$this->stream :
			$this->stream = new IO_FS_FileStream($this->path, $mode);
	}

	/**
	 * Закрывает поток, ассоциированный с файловым объектом
	 *
	 * @return IO_FS_File
	 */
	public function close()
	{
		$this->stream = null;
		return $this;
	}

	/**
	 * Возвращает все содержимое файла в виде строки
	 *
	 * @return string
	 */
	public function load($use_include_path = null, $context = null, $offset = 0, $maxlen = null)
	{
		return file_get_contents($this->path, $use_include_path, $context, $offset, $maxlen ? $maxlen : $this->size);
	}

	/**
	 * Эффективно записывает блок данных или строку в начало файла
	 *
	 * @param string $data
	 * @param int    $flags
	 *
	 * @return mixed
	 */
	public function update($data, $flags = 0)
	{
		$res = file_put_contents($this->path, $data, (int)$flags);
		$this->set_permission();
		return $res;
	}

	/**
	 * Добавляет данные в конец файла
	 *
	 * @param string $data
	 * @param int    $flags
	 *
	 * @return mixed
	 */
	public function append($data, $flags = 0)
	{
		$res = file_put_contents($this->path, $data, FILE_APPEND | $flags);
		$this->set_permission();
		return $res;
	}

	/**
	 * Перемещает файл
	 *
	 * @param string $destination
	 *
	 * @return IO_FS_File
	 */
	public function move_to($destination)
	{
		if ($this->stream && $this->stream->id) {
			return null;
		}

		if (rename($this->path, $destination = $this->fix_destination($destination))) {
			$this->path = $destination;
			$this->stat = null;
			return $this;
		} else {
			return null;
		}
	}

	/**
	 * Копирует файл
	 *
	 * @param string $destination
	 *
	 * @return IO_FS_File
	 */
	public function copy_to($destination)
	{
		if ($this->stream && $this->stream->id) {
			return null;
		}
		$dest = copy($this->path, $destination = $this->fix_destination($destination)) ?
			IO_FS::File($destination) :
			null;
		return $dest;
	}

	public function rm()
	{
		return unlink($this->path);
	}

	/**
	 * Создает итератор для файлового объекта
	 *
	 * @return IO_Stream_IOStreamIterator
	 */
	public function getIterator()
	{
		return $this->open()->getIterator();
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
			case 'stream':
				return $this->stream;
			case 'size':
				return @filesize($this->path);
			case 'mime_type':
				return $this->get_mime_type();
			case 'content_type':
				return $this->get_mime_type()->type;
			default:
				return parent::__get($property);
		}
	}

	/**
	 * Устанавливает значение свойства
	 *
	 * @param string $property
	 * @param        $value
	 *
	 * @return mixed
	 */
	public function __set($property, $value)
	{
		switch ($property) {
			case 'stream':
			case 'size':
			case 'mime_type':
			case 'content_type':
				throw new Core_ReadOnlyPropertyException($property);
			default:
				return parent::__set($property, $value);
		}
	}

	/**
	 * Проверяет установку свойства
	 *
	 * @param string $property
	 *
	 * @return boolean
	 */
	public function __isset($property)
	{
		switch ($property) {
			case 'stream':
				return isset($this->stream);
			case 'size':
			case 'mime_type':
			case 'content_type':
				return true;
			default:
				return parent::__isset($property);
		}
	}

	/**
	 * Удаляет свойство
	 *
	 * @param string $property
	 */
	public function __unset($property)
	{
		if ($this->__isset($property)) {
			throw new Core_UndestroyablePropertyException($property);
		} else {
			parent::__unset($property);
		}
	}

	/**
	 * Возвращает MIME-тип, соответствующий файлу, в виде объекта класса MIME.Type
	 *
	 * @return MIME_Type
	 */
	private function get_mime_type()
	{
		return $this->mime_type ?
			$this->mime_type :
			$this->mime_type = MIME::type_for_file($this);
	}

	/**
	 * Корректирует новый путь к файлу для операций копирования и перемещения
	 *
	 * @return string
	 */
	private function fix_destination($destination)
	{
		return is_dir($destination) ?
			(Core_Strings::ends_with($destination, '/') ? $destination : $destination . '/') . @basename($this->path) :
			$destination;
	}

}

/**
 * Файловый поток
 *
 * <p>Расширяет базовый класс IO.Stream.NamedResourceStream поддержкой интерфейса
 * IO.Stream.SeekableInterface и реализацией метода truncate().</p>
 *
 * @package IO\FS
 */
class IO_FS_FileStream
	extends IO_Stream_NamedResourceStream
	implements IO_Stream_SeekInterface
{

	/**
	 * Устанавливает текущую позицию в потоке
	 *
	 * @param int $offset
	 * @param int $whence
	 *
	 * @return int
	 */
	public function seek($offset, $whence = SEEK_SET)
	{
		return @fseek($this->id, $offset, $whence);
	}

	/**
	 * Возвращает текущую позицию в потоке
	 *
	 * @return int
	 */
	public function tell()
	{
		return @ftell($this->id);
	}

	/**
	 * Обрезает файл до заданной длины
	 *
	 * @param int $size
	 *
	 * @return boolean
	 */
	public function truncate($size = 0)
	{
		return @ftruncate($this->id, $size);
	}

}

/**
 * Каталог файловой системы
 *
 * <p>Расширяет базовый класс файлового объекта следующими возможностями:</p>
 * <ul><li>получение объектного представления элементов каталога с помощью операции доступа по
 * индексу;</li>
 * <li>итератор по элементам каталога;</li>
 * <li>совместная работа с объектами запроса списка файлов, позволяющих запрашивать
 * списки элементов каталога, удовлетворяющих определенным критериям.</li>
 * </ul><p>Свойства:</p>
 * filesитератор по содержимому каталога с настройками по умолчанию;
 *
 * @package IO\FS
 */
class IO_FS_Dir
	extends IO_FS_FSObject
	implements Core_PropertyAccessInterface,
	Core_IndexedAccessInterface,
	IteratorAggregate
{

	/**
	 * Конструктор
	 *
	 * @param string $path
	 */
	public function __construct($path)
	{
		parent::__construct(rtrim($path, '/'));
	}

	/**
	 * Применяет к каталогу объект, содержащий условия запроса его содержимого
	 *
	 * @param IO_FS_Query $query
	 *
	 * @return IO_FS_Dir
	 */
	public function query(IO_FS_Query $query)
	{
		return $query->apply_to($this);
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
			case 'files':
				return $this->make_default_iterator();
			default:
				return parent::__get($property);
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
			case 'files':
				throw new Core_ReadOnlyObjectException($property);
			default:
				return parent::__set($property, $value);
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
			case 'files':
				return true;
			default:
				return parent::__isset($property);
		}
	}

	/**
	 * Удаляет свойство
	 *
	 * @param string $property
	 */
	public function __unset($property)
	{
		if ($this->__isset($property)) {
			throw Core_UndestroyablePropertyException($property);
		} else {
			parent::__unset($property);
		}
	}

	/**
	 * Возвращает итератор по содержимому каталога.
	 *
	 * @return IO_FS_DirIterator
	 */
	public function getIterator()
	{
		return $this->make_default_iterator();
	}

	/**
	 * Возвращает значение индексированного свойства
	 *
	 * @param string $index
	 *
	 * @return IO_FS_FSObject
	 */
	public function offsetGet($index)
	{
		return IO_FS::file_object_for("{$this->path}/$index");
	}

	/**
	 * Устанавливает значение индексированного свойства
	 *
	 * @param string $index
	 * @param        $value
	 */
	public function offsetSet($index, $value)
	{
		throw ($this->offsetExists($index)) ?
			new Core_ReadOnlyIndexedPropertyException($index) :
			new Core_MissingIndexedPropertyException($index);
	}

	/**
	 * Проверяет существование индексированного свойства
	 *
	 * @param string $index
	 *
	 * @return boolean
	 */
	public function offsetExists($index)
	{
		return IO_FS::exists("{$this->path}/$index");
	}

	/**
	 * Удаляет индексированное свойство
	 *
	 * @param string $index
	 */
	public function offsetUnset($index)
	{
		throw isset($this[$index]) ?
			new Core_ReadOnlyIndexedPropertyException($index) :
			new Core_MissingIndexedPropertyException($index);
	}

	public function rm()
	{
		if (!$this->exists()) {
			return $this;
		}
		foreach ($this as $obj) {
			$obj->rm();
		}
		rmdir($this->path);
		return $this;
	}

	public function create()
	{
		return IO_FS::mkdir($this->path, null, true);
	}

	public function copy_to($dest)
	{
		$dest = IO_FS::Dir($dest);
		if (!$this->exists()) {
			return $dest;
		}
		$dest->rm()->create();
		foreach ($this as $obj)
			$obj->copy_to($dest->path . DIRECTORY_SEPARATOR . $obj->name);
		return $dest;
	}

	public function move_to($dest)
	{
		$dest = $this->copy_to($dest);
		$this->rm();
		return $dest;
	}

	/**
	 * Возвращает итератор по умолчанию
	 *
	 * @return IO_FS_DirIterator
	 */
	public function make_default_iterator()
	{
		return IO_FS::Query()->apply_to($this);
	}

}

/**
 * Условия выборки элементов каталога
 *
 * <p>Временная реализация с минимальной функциональностью -- поиск по регулярному выражению
 * или шаблону с возможностью рекурсии. Планируется изменить реализацию для поддержки поиска
 * по датам, типам файлов и т.д.</p>
 * <p>Свойства:</p>
 * regexpрегулярное выражение
 * recursiveпризнак выполнения рекурсивного поиска
 *
 * @package IO\FS
 */
class IO_FS_Query
	implements Core_PropertyAccessInterface
{

	const DEFAULT_REGEXP = '{.+}';

	protected $regexp = self::DEFAULT_REGEXP;
	protected $recursive = false;
	protected $self = false;

	/**
	 * Задает регулярное выражение для поиска
	 *
	 * @param string $regexp
	 *
	 * @return IO_FS_Query
	 */
	public function regexp($regexp)
	{
		$this->regexp = (string)$regexp;
		return $this;
	}

	/**
	 * Задает шаблон поиска с использованием * и ?
	 *
	 * @param string $wildcard
	 *
	 * @return IO_FS_Query
	 */
	public function glob($wildcard)
	{
		$this->regexp = '{' . Core_Strings::replace(
				Core_Strings::replace(Core_Strings::replace(
						$wildcard, '.', '\.'
					), '?', '.'
				), '*', '.*'
			) . '}';
		return $this;
	}

	/**
	 * Устанавливает флаг рекурсивного поиска в каталоге
	 *
	 * @param boolean $use_recursion
	 *
	 * @return IO_FS_Query
	 */
	public function recursive($use_recursion = true, $self = false)
	{
		$this->recursive = (boolean)$use_recursion;
		$this->self = $self;
		return $this;
	}

	/**
	 * Возвращает итератор, соответствующий условиям поиска
	 *
	 * @param IO_FS_Dir $dir
	 *
	 * @return IO_FS_DirIterator
	 */
	public function apply_to(IO_FS_Dir $dir)
	{
		if ($this->recursive) {
			if (!$this->self) {
				return new RecursiveIteratorIterator(new IO_FS_DirIterator($dir, $this));
			} else {
				return new RecursiveIteratorIterator(new IO_FS_DirIterator($dir, $this), RecursiveIteratorIterator::SELF_FIRST);
			}
		} else {
			return new IO_FS_DirIterator($dir, $this);
		}
	}

	/**
	 * Проверяет, является ли поиск рекурсивным
	 *
	 * @return boolean
	 */
	public function is_recursive()
	{
		return $this->recursive;
	}

	/**
	 * Проверяет, соответствует ли заданный путь условиям поиска
	 *
	 * @param string $path
	 *
	 * @return boolean
	 */
	public function allows($path = '.')
	{
		if (IO_FS::is_dir($path) && $this->recursive) {
			return true;
		}

		return Core_Regexps::match($this->regexp, (string)$path);
	}

	/**
	 * Проверяет отсутствие сооответствия заданного пути условиям поиска
	 *
	 * @param string $path
	 *
	 * @return boolean
	 */
	public function forbids($path = '.')
	{
		return !$this->allows($path);
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
			case 'regexp':
			case 'recursive':
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
	 *
	 * @return IO_FS_Query
	 */
	public function __set($property, $value)
	{
		switch ($property) {
			case 'regexp':
				$this->regexp = (string)$value;
				break;
			case 'recursive':
				$this->recursive = (boolean)$value;
				break;
			default:
				throw new Core_MissingPropertyException($property);
		}
		return $this;
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
			case 'regexp':
			case 'recursive':
				return isset($this->$property);
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
			case 'regexp':
			case 'recursive':
				throw new Core_UndestroyablePropertyException($property);
			default:
				throw new Core_MissingPropertyException($property);
		}
	}

}

/**
 * Итератор по содержимому каталога
 *
 * <p>Предназначен для использования вместе с объектами класса IO.FS.Dir.</p>
 *
 * @package IO\FS
 */
class IO_FS_DirIterator
	implements RecursiveIterator
{

	protected $current = false;
	protected $query;
	protected $dir;
	protected $id;

	/**
	 * Конструктор
	 *
	 * @param IO_FS_Dir   $dir
	 * @param IO_FS_Query $query
	 */
	public function __construct(IO_FS_Dir $dir, IO_FS_Query $query = null)
	{
		$this->dir = $dir;
		$this->query = $query ? $query : IO_FS::Query();
	}

	/**
	 * Проверяет, является ли текущий элемент подкаталогом
	 *
	 * @return boolean
	 */
	public function hasChildren()
	{
		return ($this->query->is_recursive() && ($this->current instanceof IO_FS_Dir));
	}

	/**
	 * Возвращает итератор по подкаталогу
	 *
	 * @return IO_FS_DirIterator
	 */
	public function getChildren()
	{
		return new IO_FS_DirIterator($this->current, $this->query);
	}

	/**
	 * Возвращает текущий элемент
	 *
	 * @return IO_FS_FSObject
	 */
	public function current()
	{
		return $this->current;
	}

	/**
	 * Возвращает ключ текущего элемента
	 *
	 * @return string
	 */
	public function key()
	{
		return $this->current->path;
	}

	/**
	 * Сбрасывает итератор
	 *
	 */
	public function rewind()
	{
		if ($this->id) {
			@closedir($this->id);
		}
		rewinddir($this->id = @opendir($this->dir->path));
		$this->skip_to_next();
	}

	/**
	 * Переходит к следующему элементу итератора
	 *
	 */
	public function next()
	{
		return $this->skip_to_next();
	}

	/**
	 * Проверяет существование текущего элемента итератора
	 *
	 * @return boolean
	 */
	public function valid()
	{
		return $this->current ? true : false;
	}

	/**
	 * Переходит к очередному элементу, удовлетворяющему условиям поиска
	 *
	 */
	protected function skip_to_next()
	{
		do {
			$name = readdir($this->id);
			$path = $this->dir->path . '/' . $name;
		} while ($name !== false &&
			($name == '.' ||
				$name == '..' ||
				$this->query->forbids($path = $this->dir->path . '/' . $name)
			));
		if ($name !== false) {
			$this->current = IO_FS::file_object_for($path);
		} else {
			@closedir($this->id);
			$this->id = null;
			$this->current = null;
		}
		return $this->current;
	}

}

