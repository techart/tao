<?php
/// <module name="IO.FS" version="0.2.2" maintainer="timokhin@techart.ru">
///     <brief>Работа с файловой системой</brief>
///     <details>
///       <p>Модуль реализует набор классов для работы с файлами и каталогами. Для файлов и
///          каталогов реализованы соответствующие объектные представления. Также реализован
///          объектный интерфейс для запроса списка объектов файловой системы по определенным
///          критериям.</p>
///     </details>
Core::load('Time', 'IO.Stream', 'MIME');

/// <class name="IO.FS" stereotype="module">
///   <brief>Класс модуля</brief>
///   <implements interface="Core.ModuleInterface" />
///   <depends supplier="IO.FS.File"       stereotype="creates" />
///   <depends supplier="IO.FS.FileStream" stereotype="creates" />
///   <depends supplier="IO.FS.Stat"       stereotype="creates" />
///   <depends supplier="IO.FS.Dir"        stereotype="creates" />
///   <depends supplier="IO.FS.Path"       stereotype="creates" />
///   <depends supplier="IO.FS.Query"      stereotype="creates" />
///   <details>
///     <p>Содержит набор фабричных методов для создания экземпляров классов модуля, а также
///        процедурный интерфейс для работы с файлами.</p>
///   </details>
class IO_FS implements  Core_ConfigurableModuleInterface {

///   <constants>
  const VERSION  = '0.2.2';
///   </constants>

  static protected $options = array(
    'dir_mod' => 0755,
    'file_mod' => 0755,
    'dir_own' => false,
    'file_own' => false,
    'dir_grp' => false,
    'file_grp' => false
  );

///   <method name="initialize" scope="class">
///     <brief>Инициализация</brief>
///     <args>
///       <arg name="options" type="array" default="array()" brief="массив опций" />
///     </args>
///     <body>
  static public function initialize(array $options = array()) { self::options($options); }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="configuring">

///   <method name="options" returns="mixed" scope="class">
///     <brief>Устанавливает опции модуля</brief>
///     <args>
///       <arg name="options" type="array" default="array()" brief="массив опций" />
///     </args>
///     <body>
  static public function options(array $options = array()) {
    if (count($options)) Core_Arrays::update(self::$options, $options);
    return self::$options;
  }
///     </body>
///   </method>

///   <method name="option" returns="mixed">
///     <brief>Устанавливает/возвращает опцию модуля</brief>
///     <args>
///       <arg name="name" type="string" brief="имя опции" />
///       <arg name="value" default="null" brief="значение" />
///     </args>
///     <body>
  static public function option($name, $value = null) {
    $prev = isset(self::$options[$name]) ? self::$options[$name] : null;
    if ($value !== null) self::options(array($name => $value));
    return $prev;
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="building">

///   <method name="File" returns="IO.FS.File" scope="class" stereotype="factory">
///     <brief>Создает объект класса IO.FS.File</brief>
///     <args>
///       <arg name="path" type="string" brief="путь к файлу" />
///     </args>
///     <body>
  static public function File($path) { return $path instanceof IO_FS_File ? $path : new IO_FS_File($path); }
///     </body>
///   </method>

///   <method name="FileStream" returns="IO.FS.FileStream" scope="class" stereotype="factory">
///     <brief>Создает объект класса IO.FS.FileStream</brief>
///     <args>
///       <arg name="path" type="string" brief="путь к файлу" />
///     </args>
///     <body>
  static public function FileStream($path, $mode = IO_Stream::DEFAULT_OPEN_MODE) {
    return new IO_FS_FileStream($path, $mode);
  }
///     </body>
///   </method>

///   <method name="Stat" returns="IO.FS.Stat" scope="class" stereotype="factory">
///     <brief>Создает объект класса IO.FS.Stat</brief>
///     <args>
///       <arg name="object" brief="файловый объект" />
///     </args>
///     <body>
  static public function Stat($object) { return new IO_FS_Stat($object); }
///     </body>
///   </method>

///   <method name="Dir" returns="IO.FS.Dir" scope="class" stereotype="factory">
///     <brief>Создает объект класса IO.FS.Dir</brief>
///     <args>
///       <arg name="path" type="string" brief="путь к каталогу" />
///     </args>
///     <body>
  static public function Dir($path = '.') { return $path instanceof IO_FS_Dir ? $path : new IO_FS_Dir($path); }
///     </body>
///   </method>

///   <method name="Path" returns="IO.FS.Path" scope="class" stereotype="factory">
///     <brief>Создает объект  класса IO.FS.Path</brief>
///     <args>
///       <arg name="path" type="string" brief="путь" />
///     </args>
///     <body>
  static public function Path($path) { return new IO_FS_Path($path); }
///     </body>
///   </method>

///   <method name="Query" returns="IO.FS.Query" scope="class" stereotype="factory">
///     <brief>Создает объект класса IO.FS.Query</brief>
///     <body>
  static public function Query() { return new IO_FS_Query(); }
///     </body>
///   </method>

///   <method name="file_object_for" returns="IO.FS.FSObject" scope="class">
///     <brief>Возвращает объект классов IO.FS.Dir или IO.FS.File по заданному пути</brief>
///     <args>
///      <arg name="path" type="string" brief="путь" />
///     </args>
///     <body>
  static public function file_object_for($path) {
    return self::exists($path = (string) $path) ?
      (self::is_dir($path) ? self::Dir($path) : self::File($path)) :
       null;
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="interacting">

///   <method name="pwd" returns="IO.FS.Directory" scope="class">
///     <brief>Возвращает объект класса IO.FS.Dir, соответствующий текущему каталогу.</brief>
///     <body>
  static public function pwd() { return self::Dir(getcwd()); }
///     </body>
///   </method>

///   <method name="cd" returns="IO.FS.Directory" scope="class">
///     <brief>Переходит в указанный каталог и возвращает соответствующий объект класса IO.FS.Dir</brief>
///     <args>
///       <arg name="path" type="string" brief="путь к каталогу" />
///     </args>
///     <details>
///       <p>В случае, если каталог не существует, возвращает null.</p>
///     </details>
///     <body>
  static public function cd($path) { return chdir($path) ? self::Dir(getcwd()) : null; }
///     </body>
///   </method>

///   <method name="mkdir" returns="boolean" scope="class">
///     <brief>Создает каталог и возвращает соответствующий объект класса IO.FS.Dir</brief>
///     <args>
///       <arg name="path"      type="string"  brief="путь к каталогу" />
///       <arg name="mode"      type="int"     brief="права доступа на каталог" />
///       <arg name="recursive" type="boolean" brief="признак автоматического создания промежуточных каталогов" />
///     </args>
///     <details>
///       <p>В случае, если каталог не может быть создан, возвращает null.</p>
///     </details>
///     <body>
  static public function mkdir($path, $mode = null, $recursive = false) {
    $mode = self::get_permision_for(null, $mode, 'mod', 'dir');
    $old = umask(0);
    $rs =  (self::exists((string) $path) || mkdir((string) $path, $mode, $recursive)) ?
      self::Dir($path) : null;
    umask($old);
    return $rs;
  }
///     </body>
///   </method>

  static protected function get_permision_for($path, $value, $type, $obj = null) {
    if (!is_null($value)) return $value;
    $object = !empty($path) ? (is_dir($path) ? 'dir' : 'file') : $obj;
    return self::option("{$object}_{$type}");
  }

///   <method name="chmod" returns="boolean" scope="class">
///     <brief>Изменяет права доступа к файлу</brief>
///     <args>
///       <arg name="file" type="string" brief="путь к файлу" />
///       <arg name="mode" type="int"    brief="права доступа" />
///     </args>
///     <details>
///       <p>Вызывает встроенную функцию chmod(), возвращает булевский признак успешного выполнения
///          операции.</p>
///     </details>
///     <body>
  static public function chmod($file, $mode = null) {
    $mode = self::get_permision_for($file, $mode, 'mod');
    return $mode ? @chmod((string)$file, $mode) : false;
  }
///     </body>
///   </method>

///   <method name="chown" returns="boolean" scope="class">
///     <brief>Изменяет владельца файла</brief>
///     <args>
///     </args>
///     <details>
///       <p>Вызывает встроенную функцию chown(), возвращает булевский признак успешного выполнения
///          операции.</p>
///     </details>
///     <body>
  static public function chown($file, $owner = null) {
    $owner = self::get_permision_for($file, $owner, 'own');
    return $owner ? @chown((string)$file, $owner) : false;
  }
///     </body>
///   </method>

  static function chgrp($file, $group = null) {
    $group = self::get_permision_for($file, $group, 'grp');
    return $group ? @chgrp((string) $file, $group) : false;
  }

///   <method name="rm" returns="boolean" scope="class">
///     <brief>Удаляет файл</brief>
///     <args>
///       <arg name="path" type="string" brief="путь к файлу" />
///     </args>
///     <details>
///       <p>Удаление производится путем вызова встроенных функций rmdir() и unlink() в зависимости
///          от типа объекта файловой системы.</p>
///     </details>
///     <body>
  static public function rm($path) {
    $obj = self::file_object_for($path);
    return $obj ? $obj->rm() : false;
  }
///     </body>
///   </method>

///   <method name="clear_dir" returns="boolean" scope="class">
///     <brief>Удаляет файл</brief>
///     <args>
///       <arg name="path" type="string" brief="путь к файлу" />
///     </args>
///     <body>
  static public function clear_dir($path) {
    if (!self::is_dir($path)) return false;
    $dir = self::Dir($path);
    $rc = true;
    foreach ($dir as $o) $rc = $o->rm() && $rc;
    return $rc;
  }
///     </body>
///   </method>

///   <method name="make_nested_dir" returns="boolean" scope="class">
///     <brief>Создает вложенные каталоги</brief>
///     <args>
///       <arg name="path" type="string" brief="путь к каталогу" />
///       <arg name="mode" type="int" brief="права доступа" />
///     </args>
///     <details>
///       <p></p>
///     </details>
///     <body>
  static public function make_nested_dir($path, $mode = null) {
    return self::mkdir($path, $mode, true);
  }
///     </body>
///   </method>

///   <method name="exists" returns="boolean" scope="class">
///     <brief>Проверяет существование файла или каталога</brief>
///     <args>
///       <arg name="path" type="string" brief="путь к файлу или каталогу" />
///     </args>
///     <body>
  static public function exists($path) { return file_exists((string) $path); }
///     </body>
///   </method>

///   <method name="is_dir" returns="boolean">
///     <brief>Проверяет, является ли файловый объект с заданным путем каталогом</brief>
///     <args>
///       <arg name="path" type="string" brief="путь к файловому объекту" />
///     </args>
///     <body>
  static public function is_dir($path) { return is_dir((string) $path); }
///     </body>
///   </method>

///   <method name="mv" returns="boolean">
///     <brief>Перемещает файл</brief>
///     <args>
///       <arg name="from" type="string" brief="путь к исходному объекту" />
///       <arg name="to"   type="string" brief="новое местоположение объекта" />
///     </args>
///     <details>
///       <p>Реализация просто вызывает встроенный метод rename().</p>
///     </details>
///     <body>
  static public function mv($from, $to) {
    $obj = self::file_object_for($from);
    return $obj ? $obj->move_to($to) : false;
  }
///     </body>
///   </method>

///   <method name="cp" returns="boolean">
///     <brief>Копирует файл</brief>
///     <args>
///       <arg name="from" type="string" brief="путь к исходному файлу" />
///       <arg name="to"   type="string" brief="путь к копии" />
///     </args>
///     <details>
///       <p>Реализация просто вызывает встроенный метод copy().</p>
///     </details>
///     <body>
  static public function cp($from, $to) {
    $obj = self::file_object_for($from);
    return $obj ? $obj->copy_to($to) : false;
  }
///     </body>
///   </method>

///   </protocol>
}
/// </class>


/// <class name="IO.FS.Exception" extends="IO.Exception" stereotype="exception">
///   <brief>Базовый класс исключений модуля</brief>
class IO_FS_Exception extends IO_Exception {}
/// </class>


/// <class name="IO.FS.StatException" extends="IO.FS.Exception" stereotype="exception">
///   <brief>Класс исключений для ошибок получения информации о файловом объекте</brief>
class IO_FS_StatException extends IO_FS_Exception {

  protected $object;

///   <protocol name="creating">

///   <method name="__construct">
///     <brief>Конструктор</brief>
///     <args>
///       <arg name="object" brief="файловый объект" />
///     </args>
///     <body>
  public function __construct($object) {
    parent::__construct("Can't stat object".((string) ($this->object = $object)));
  }
///     </body>
///   </method>

///   </protocol>
}
/// </class>


/// <class name="IO.FS.Path">
///   <brief>Объектное представление пути в файловой системе</brief>
///   <implements interface="Core.PropertyAccessInterface" />
///   <details>
///     <p>Объект представляет собой объектную обертку над встроенной функцией pathinfo().
///        Соответственно, он обеспечивает доступ к следующим свойствам:</p>
///     <dl>
///       <dt>dirname</dt><dd>путь к файлу</dd>
///       <dt>basename</dt><dd>базовое имя файла</dd>
///       <dt>extension</dt><dd>расширение</dd>
///       <dt>filename</dt><dd>имя файла</dd>
///     </dl>
///   </details>
class IO_FS_Path implements Core_PropertyAccessInterface {

  protected $info = array();

///   <protocol name="creating">

///   <method name="__construct">
///     <brief>Конструктор</brief>
///     <args>
///       <arg name="path" type="string" brief="путь" />
///     </args>
///     <body>
  public function __construct($path) { $this->info = @pathinfo((string) $path); }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="accessing" interface="Core.PropertyAccessInterface">

///   <method name="__get" returns="mixed">
///     <brief>Возвращает значение свойства</brief>
///     <args>
///       <arg name="property" type="string" brief="имя свойтсва" />
///     </args>
///     <body>
  public function __get($property) {
    if (isset($this->info[$property]))
      return $this->info[$property];
    else
      throw new Core_MissingPropertyException($property);
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
  public function __isset($property) { return isset($this->info[$property]); }
///     </body>
///   </method>

///   <method name="__unset">
///     <brief>Удаляет свойство</brief>
///     <args>
///       <arg name="property" type="string" brief="имя свойства" />
///     </args>
///     <details>
///       <p>Объекты класса являются неизменяемыми, удаление свойств запрещено.</p>
///     </details>
///     <body>
  public function __unset($property) { throw new Core_ReadOnlyObjectException($this); }
///     </body>
///   </method>

///   </protocol>
}
/// </class>


/// <class name="IO.FS.Stat">
///   <brief>Объектное представление метаинформации об объекте файловой системы</brief>
///   <implements interface="Core.PropertyAccessInterface" />
///   <depends supplier="IO.FS.StatException" stereotype="throw" />
///   <details>
///     <p>Объект предоставляет ту же информацию, что и встроенные функции fstat() и stat(), однако
///        делает это более удобным образом: при создании объекта может быть использован как путь
///        к файлу, так и файловый ресурс, все свойства, содержащие дату, возвращаются в виде
///        объектов класса Time.DateTime.</p>
///   </details>
class IO_FS_Stat implements Core_PropertyAccessInterface {
  protected $stat = array();

///   <protocol name="creating">

///   <method name="__construct">
///     <brief>Конструктор</brief>
///     <args>
///       <arg name="object" brief="файловый объект" />
///     </args>
///     <details>
///       <p>В качестве файлового объекта могут быть переданы строковый путь либо ресурс.</p>
///       <p>Если объект не существует, или информация для него не может быть получена, генерируется
///          исключение класса IO.FS.StatException.</p>
///     </details>
///     <body>
  public function __construct($object) {
    if (!$stat = Core_Types::is_resource($object) ?
      @fstat($object) : @stat((string) $object))
      throw new IO_FS_StatException($object);

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
///     </body>
///   </method>

///   </protocol>

///   <protocol name="accessing" interface="Core.PropertyAccessInterface">

///   <method name="__get" returns="mixed">
///     <brief>Возвращает значние свойства</brief>
///     <args>
///       <arg name="property" type="string" brief="имя свойства" />
///     </args>
///     <body>
  public function __get($property) {
    if (isset($this->stat[$property]))
      return $this->stat[$property];
    else
      throw new Core_MissingPropertyException($property);
  }
///     </body>
///   </method>

///   <method name="__set" returns="mixed">
///     <brief>Устанавливает значение свойства</brief>
///     <args>
///       <arg name="property" type="string" brief="имя свойства" />
///       <arg name="value" brief="значение свойства" />
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
  public function __isset($property) { return isset($this->stat[$property]); }
///     </body>
///   </method>

///   <method name="__unset">
///     <brief>Удаляет свойство</brief>
///     <args>
///       <arg name="property" type="string" brief="имя свойства" />
///     </args>
///     <details>
///       <p>Объекты класса являются неизменяемыми, удаление свойств запрещено.</p>
///     </details>
///     <body>
  public function __unset($property) { throw new Core_ReadOnlyObjectException($this); }
///     </body>
///   </method>

///   </protocol>
}
/// </class>


/// <class name="IO.FS.FSObject">
///   <implements interface="Core.StringifyInterface" />
///   <implements interface="Core.EqualityInterface" />
///   <brief>Базовый класс объектов файловой системы</brief>
///   <implements interface="Core.PropertyAccessInterface" />
///   <details>
///     <p>Предназначен для использования в качестве базового класса для более специфичных классов,
///        представляющих файлы и каталоги. Каждый объект класса характеризуется своим именем и
///        метаданными, содержащимися в виде объекта IO.FS.Stat, загружаемого по требованию.</p>
///     <p>Класс также реализует набор операций, в равной степени применимых к файлам и
///        каталогам.</p>
///     <p>Свойства:</p>
///     <dl>
///       <dt>path</dt><dd>путь к файлу;</dd>
///       <dt>dir_name</dt><dd>имя каталога;</dd>
///       <dt>name</dt><dd>имя файла;</dd>
///       <dt>real_path</dt><dd>реальный путь к файлу (с раскрытыми ., .., и т.д.);</dd>
///       <dt>stat</dt><dd>метаданные в виде объекта класса IO.FS.Stat.</dd>
///     </dl>
///   </details>
class IO_FS_FSObject implements Core_StringifyInterface, Core_EqualityInterface {

  protected $path;
  protected $stat;

///   <protocol name="creating">

///   <method name="__construct">
///     <brief>Конструктор</brief>
///     <args>
///       <arg name="path" type="string" brief="путь к файловому объекту" />
///     </args>
///     <body>
  public function __construct($path) { $this->path = $path; }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="performing">

///   <method name="chmod" returns="boolean">
///     <brief>Изменяет права доступа к файловому объекту</brief>
///     <args>
///       <arg name="mode" type="int" brief="новые права доступа" />
///     </args>
///     <details>
///       <p>Возвращает признак успешного завершения операции.</p>
///     </details>
///     <body>
  public function chmod($mode = null) {
    $this->stat = null;
    return IO_FS::chmod($this->path, $mode);
  }
///     </body>
///   </method>

///   <method name="chown" returns="boolean">
///     <brief>Изменяет владельца файлового объекта</brief>
///     <args>
///       <arg name="owner" brief="новый владелец" />
///     </args>
///     <details>
///       <p>Возвращает признак успешного завершения операции.</p>
///     </details>
///     <body>
  public function chown($owner = null) {
    $this->stat = null;
    return IO_FS::chown($this->path, $owner);
  }
///     </body>
///   </method>

  public function chgrp($group = null) {
    $this->stat = null;
    return IO_FS::chgrp($this->path, $group);
  }
  
  public function set_permission($mode = null, $owner = null, $group = null) {
    $this->chmod($mode);
    $this->chown($owner);
    $this->chgrp($group);
    return $this;
  }

  public function exists() {
    return file_exists($this->path);
  }

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
      case 'path':      return $this->path;
      case 'dir_name': case 'dirname':
        return @dirname($this->path);
      case 'name':      return @basename($this->path);
      case 'real_path': return @realpath($this->path);
      case 'stat':
        if (!$this->stat) $this->stat = IO_FS::Stat($this->path);
        return $this->stat;
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
///       <p>Свойства объекта доступны только для чтения.</p>
///     </details>
///     <body>
  public function __set($property, $value) {
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
///     </body>
///   </method>

///   <method name="__isset" returns="boolean">
///     <brief>Проверяет установку свойства объекта</brief>
///     <args>
///       <arg name="property" type="string" brief="имя свойства" />
///     </args>
///     <body>
  public function __isset($property) {
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
  public function __unset($property) {
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
///     </body>
///   </method>

///   </protocol>

///   <protocol name="stringifying">

///   <method name="as_string" returns="string">
///     <body>
  public function as_string() {
    return $this->real_path;
  }
///     </body>
///   </method>

///   <method name="__toString" returns="string">
///     <body>
  public function __toString() {
    return $this->real_path;
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="quering">
///   <method name="equals" returns="boolean">
///     <args>
///       <arg name="to" />
///     </args>
///     <body>
  public function equals($to) {
    return $to instanceof self &&
      $this->real_path == $to->real_path;
  }
///     </body>
///   </method>

///</protocol>

}
/// </class>

/// <composition>
///   <source class="IO.FS.FSObject" role="object" multiplicity="1" />
///   <target class="IO.FS.Stat"     role="stat"   multiplicity="1" />
/// </composition>


/// <class name="IO.FS.File" extends="IO.FS.FSObject">
///   <brief>Объектное представление файла</brief>
///   <implements interface="Core.PropertyAccessInterface" />
///   <implements interface="IteratorAggregate" />
///   <details>
///     <p>Файл представляет собой файловый объект, для которого могут быть получены метаданные и
///        открыт файловый поток. Объект файла предоставляет также возможность получения информации
///        о MIME-типе, соответствующем файлу, с использованием модуля MIME.</p>
///     <p>Объект может работать как итератор, в этом случае соответствующий файл открывается на
///        чтение и для него создается соответствующий поток.</p>
///     <p>Свойства:</p>
///     <dl>
///       <dt>stream</dt><dd>ассоциированный поток;</dd>
///       <dt>size</dt><dd>размер файла;</dd>
///       <dt>mime_type</dt><dd>MIME-тип файла в виде объекта;</dd>
///       <dt>content_type</dt><dd>MIME-тип файла в виде строки.</dd>
///     </dl>
///   </details>
class IO_FS_File
  extends    IO_FS_FSObject
  implements Core_PropertyAccessInterface,
             IteratorAggregate {

  protected $mime_type;
  protected $stream;

///   <protocol name="destroying">

///   <method name="__destroy">
///     <brief>Деструктор</brief>
///     <details>
///       <p>Гарантирует неявное закрытие потока, если он был открыт.</p>
///     </details>
///     <body>
  public function __destroy() { $this->close(); parent::__destroy(); }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="performing">

  public function create() {
    return $this->update('', null);
  }

///   <method name="open">
///     <brief>Создает поток класса IO.FS.FileStream, соответствующий файлу</brief>
///     <args>
///       <arg name="mode" type="string" default="r" brief="режим открытия файла" />
///     </args>
///     <details>
///       <p>Если с файловым объектом уже был ассоциирован поток, возвращается ассоциированный
///          поток, при этом новый поток не открывается, а режим открытия игнорируется.</p>
///     </details>
///     <body>
  public function open($mode = IO_Stream::DEFAULT_OPEN_MODE) {
    return ($this->stream && $this->stream->id) ?
      $this->stream :
      $this->stream = new IO_FS_FileStream($this->path, $mode);
  }
///     </body>
///   </method>

///   <method name="close" returns="IO.FS.File">
///     <brief>Закрывает поток, ассоциированный с файловым объектом</brief>
///     <body>
  public function close() {
    $this->stream = null;
    return $this;
  }
///     </body>
///   </method>

///   <method name="load" returns="string">
///     <brief>Возвращает все содержимое файла в виде строки</brief>
///     <details>
///       <p>Метод представляет собой обертку над встроенной функцией file_get_contents().</p>
///     </details>
///     <body>
  public function load($use_include_path = null, $context = null, $offset = 0, $maxlen = null) {
    return file_get_contents($this->path, $use_include_path, $context, $offset, $maxlen ? $maxlen : $this->size);
  }
///     </body>
///   </method>

///   <method name="update" returns="mixed">
///     <brief>Эффективно записывает блок данных или строку в начало файла</brief>
///     <args>
///       <arg name="data" type="string" breif="данные" />
///       <arg name="flags" type="int" default="LOCK_EX" brief="флаг" />
///     </args>
///     <details>
///       <p>Метод представляет собой обертку над встроенной функцией file_put_contents().</p>
///     </details>
///     <body>
  public function update($data, $flags = 0) {
    return file_put_contents($this->path, $data, $flags);
  }
///     </body>
///   </method>

///   <method name="append" returns="mixed">
///     <brief>Добавляет данные в конец файла</brief>
///     <args>
///       <arg name="data" type="string" brief="данные" />
///       <arg name="flags" type="int" default="LOCK_EX" brief="флаг" />
///     </args>
///     <details>
///       <p>Для добавления используется встроенная функция file_put_contents().</p>
///     </details>
///     <body>
  public function append($data, $flags = 0) {
    return file_put_contents($this->path, $data, FILE_APPEND | $flags);
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="placing">

///   <method name="move_to" returns="IO.FS.File">
///     <brief>Перемещает файл</brief>
///     <args>
///       <arg name="destination" type="string" brief="новое местоположение" />
///     </args>
///     <details>
///       <p>В случае, если перемещение завершилось успешно, свойство path соответствующим образом
///          корректируется. В случае ошибки возвращается null.</p>
///     </details>
///     <body>
  public function move_to($destination) {
    if ($this->stream && $this->stream->id) return null;

    if (rename($this->path, $destination = $this->fix_destination($destination))) {
      $this->path = $destination;
      $this->stat = null;
      return $this;
    } else
      return null;
  }
///     </body>
///   </method>

///   <method name="copy_to" returns="IO.FS.File">
///     <brief>Копирует файл</brief>
///     <args>
///       <arg name="destination" type="string" brief="путь к копии" />
///     </args>
///     <details>
///       <p>В случае удачного выполнения копирования возвращается объект класса IO.FS.File,
///          соответствующий копии. В случае ошибки возвращается null.</p>
///     </details>
///     <body>
  public function copy_to($destination) {
    if ($this->stream && $this->stream->id) return null;
    $dest = copy($this->path, $destination = $this->fix_destination($destination)) ?
        IO_FS::File($destination) :
        null;
    return $dest;
  }
///     </body>
///   </method>

  public function rm() {
    return unlink($this->path);
  }

///   </protocol>

///   <protocol name="iterating">

///   <method name="getIterator" returns="IO.Stream.IOStreamIterator">
///     <brief>Создает итератор для файлового объекта</brief>
///     <details>
///       <p>Метод выполняет неявное создание файлового потока, открывает его в режиме чтения и
///          вызвращает его итератор класса IO.Stream.IOStreamIterator.</p>
///     </details>
///     <body>
  public function getIterator() { return $this->open()->getIterator(); }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="accessing" interface="Core_PropertyAccessInterface">

///   <method name="__get" returns="mixed">
///     <brief>Возвращает значение свойства</brief>
///     <details>
///     </details>
///     <args>
///       <arg name="property" type="string" brief="имя свойства" />
///     </args>
///     <body>
  public function __get($property) {
    switch ($property) {
      case 'stream':       return $this->stream;
      case 'size':         return @filesize($this->path);
      case 'mime_type':    return $this->get_mime_type();
      case 'content_type': return $this->get_mime_type()->type;
      default:
        return parent::__get($property);
    }
  }
///     </body>
///   </method>

///   <method name="__set" returns="mixed">
///     <brief>Устанавливает значение свойства</brief>
///     <details>
///       <p>Свойства объекта доступны только на чтение.</p>
///     </details>
///     <args>
///       <arg name="property" type="string" brief="имя свойства" />
///       <arg name="value" brief="значение" />
///     </args>
///     <body>
  public function __set($property, $value) {
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
///     </body>
///   </method>

///   <method name="__isset" returns="boolean">
///     <brief>Проверяет установку свойства</brief>
///     <args>
///       <arg name="property" type="string" brief="имя свойства" />
///     </args>
///     <body>
  public function __isset($property) {
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
///     </body>
///   </method>

///   <method name="__unset">
///     <brief>Удаляет свойство</brief>
///     <args>
///       <arg name="property" type="string" brief="имя свойства" />
///     </args>
///     <details>
///       <p>Свойства объекта не могут быть удалены.</p>
///     </details>
///     <body>
  public function __unset($property) {
    if ($this->__isset($property))
      throw new Core_UndestroyablePropertyException($property);
    else
      parent::__unset($property);
  }
///     </body>
///   </method>

///   </protocol>


///   <protocol name="supporting">

///   <method name="get_mime_type" returns="MIME.Type" access="private">
///     <brief>Возвращает MIME-тип, соответствующий файлу, в виде объекта класса MIME.Type</brief>
///     <body>
  private function get_mime_type() {
    return $this->mime_type ?
      $this->mime_type :
      $this->mime_type = MIME::type_for_file($this);
  }
///     </body>
///   </method>

///   <method name="fix_destination" returns="string" access="private">
///     <brief>Корректирует новый путь к файлу для операций копирования и перемещения</brief>
///     <body>
  private function fix_destination($destination) {
    return is_dir($destination) ?
      (Core_Strings::ends_with($destination, '/') ? $destination : $destination.'/').@basename($this->path) :
      $destination;
  }
///     </body>
///   </method>

///   </protocol>
}
/// </class>
/// <composition>
///   <source class="IO.FS.File"       role="file"   multiplicity="1" />
///   <target class="IO.FS.FileStream" role="stream" multiplicity="1" />
/// </composition>


/// <class name="IO.FS.FileStream" extends="IO.Stream.NamedResourceStream">
///   <brief>Файловый поток</brief>
///   <implements interface="IO.Stream.SeekInterface" />
///   <details>
///     <p>Расширяет базовый класс IO.Stream.NamedResourceStream поддержкой интерфейса
///        IO.Stream.SeekableInterface и реализацией метода truncate().</p>
///   </details>
class IO_FS_FileStream
  extends IO_Stream_NamedResourceStream
  implements IO_Stream_SeekInterface {

///   <protocol name="seeking" interface="IO.Stream.SeekableInterface">

///   <method name="seek" returns="int" >
///     <brief>Устанавливает текущую позицию в потоке</brief>
///     <args>
///       <arg name="offset" type="int" brief="смещение" />
///       <arg name="whence" type="int" brief="позиция от которой делается смещение" />
///     </args>
///     <body>
  public function seek($offset, $whence) {
    return @fseek($this->id, $offset, $whence);
  }
///     </body>
///   </method>

///   <method name="tell" returns="int">
///     <brief>Возвращает текущую позицию в потоке</brief>
///     <body>
  public function tell() { return @ftell($this->id); }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="performing">

///   <method name="truncate" returns="boolean">
///     <brief>Обрезает файл до заданной длины</brief>
///     <args>
///       <arg name="size" type="int" brief="длина файла" />
///     </args>
///     <details>
///       <p>Обертка над встроенной функцией ftruncate().</p>
///     </details>
///     <body>
  public function truncate($size = 0) { return @ftruncate($this->id, $size);  }
///     </body>
///   </method>

///   </protocol>
}
/// </class>


/// <class name="IO.FS.Dir" extends="IO.FS.FSObject">
///   <brief>Каталог файловой системы</brief>
///   <implements interface="Core.PropertyAccessInterface" />
///   <implements interface="Core.IndexedAccessInterface" />
///   <implements interface="IteratorAggregate" />
///   <depends supplier="IO.FS.Query" stereotype="uses" />
///   <depends supplier="IO.FS.DirIterator" stereotype="creates" />
///   <details>
///     <p>Расширяет базовый класс файлового объекта следующими возможностями:</p>
///     <ul>
///       <li>получение объектного представления элементов каталога с помощью операции доступа по
///           индексу;</li>
///       <li>итератор по элементам каталога;</li>
///       <li>совместная работа с объектами запроса списка файлов, позволяющих запрашивать
///           списки элементов каталога, удовлетворяющих определенным критериям.</li>
///     </ul>
///     <p>Свойства:</p>
///     <dl>
///       <dt>files</dt><dd>итератор по содержимому каталога с настройками по умолчанию;</dd>
///     </dl>
///   </details>
class IO_FS_Dir
  extends    IO_FS_FSObject
  implements Core_PropertyAccessInterface,
             Core_IndexedAccessInterface,
             IteratorAggregate {

///   <protocol name="creating">

///   <method name="__construct">
///     <brief>Конструктор</brief>
///     <args>
///       <arg name="path" type="string" brief="путь к каталогу" />
///     </args>
///     <details>
///       <p>Путь к каталогу, хранящийся в свостве объекта, не содержит завершающего слеша. Если
///          таковой присутствует в строке, передаваемой в конструктор, он удаляется.</p>
///     </details>
///     <body>
  public function __construct($path) { parent::__construct(rtrim($path, '/')); }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="quering">

///   <method name="query" returns="IO.FS.Dir">
///     <brief>Применяет к каталогу объект, содержащий условия запроса его содержимого</brief>
///     <args>
///       <arg name="query" type="IO.FS.Query" brief="параметры запроса" />
///     </args>
///     <body>
  public function query(IO_FS_Query $query) {
    return $query->apply_to($this);
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
      case 'files': return $this->make_default_iterator();
      default:
        return parent::__get($property);
    }
  }
///     </body>
///   </method>

///   <method name="__set">
///     <brief>Устанавливает значение свойства</brief>
///     <args>
///       <arg name="property" type="string" brief="имя свойства" />
///       <arg name="value" brief="значение" />
///     </args>
///     <body>
  public function __set($property, $value) {
    switch ($property) {
      case 'files':
        throw new Core_ReadOnlyObjectException($property);
      default:
        return parent::__set($property);
    }
  }
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
      case 'files':
        return true;
      default:
        return parent::__isset($property);
    }
  }
///     </body>
///   </method>

///   <method name="__unset">
///     <brief>Удаляет свойство</brief>
///     <args>
///       <arg name="property" type="string" brief="имя свойства" />
///     </args>
///     <body>
  public function __unset($property) {
    if ($this->__isset($property))
      throw Core_UndestroyablePropertyException($property);
    else
      parent::__unset($property);
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="iterating" interface="IteratorAggregate">

///   <method name="getIterator" returns="IO.FS.DirIterator">
///     <brief>Возвращает итератор по содержимому каталога. </brief>
///     <body>
  public function getIterator() { return $this->make_default_iterator(); }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="indexing" interface="Core.IndexedAccessProtocol">

///   <method name="offsetGet" returns="IO.FS.FSObject">
///   <brief>Возвращает значение индексированного свойства</brief>
///   <args>
///     <arg name="index" type="string" brief="индекс" />
///   </args>
///   <details>
///     <p>Если имя индекса соответствует файловому объекту, находящемуся внутри каталога, в случае,
///        если объект с указанным именем отсутствует, возвращается null.</p>
///   </details>
///   <body>
  public function offsetGet($index) { return IO_FS::file_object_for("{$this->path}/$index"); }
///   </body>
///   </method>

///   <method name="offsetSet">
///     <brief>Устанавливает значение индексированного свойства</brief>
///     <args>
///       <arg name="index" type="string" brief="индекс" />
///       <arg name="value" brief="значение" />
///     </args>
///     <details>
///       <p>Индексированные свойства доступны только на чтение.</p>
///     </details>
///     <body>
  public function offsetSet($index, $value) {
   throw ($this->offsetExists($index)) ?
      new Core_ReadOnlyIndexedPropertyException($index) :
      new Core_MissingIndexedPropertyException($index);
  }
///     </body>
///   </method>

///   <method name="offsetExists" returns="boolean">
///     <brief>Проверяет существование индексированного свойства</brief>
///     <args>
///       <arg name="index" type="string" brief="индекс" />
///     </args>
///     <body>
  public function offsetExists($index) { return IO_FS::exists("{$this->path}/$index"); }
///     </body>
///   </method>

///   <method name="offsetUnset">
///     <brief>Удаляет индексированное свойство</brief>
///     <args>
///       <arg name="index" type="string" brief="индекс" />
///     </args>
///     <details>
///       <p>Удаление индексированных свойств запрещено.</p>
///     </details>
///     <body>
  public function offsetUnset($index) {
    throw isset($this[$index]) ?
      new Core_ReadOnlyIndexedPropertyException($index) :
      new Core_MissingIndexedPropertyException($index);
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="supporting">

  public function rm() {
    if (!$this->exists()) return $this;
    foreach($this as $obj) {
      $obj->rm();
    }
    rmdir($this->path);
    return $this;
  }
  
  public function create() {
    return IO_FS::mkdir($this->path, null, true);
  }
  
  public function copy_to($dest) {
    $dest = IO_FS::Dir($dest);
    if (!$this->exists()) return $dest;
    $dest->rm()->create();
    foreach ($this as $obj)
      $obj->copy_to($dest->path . DIRECTORY_SEPARATOR . $obj->name);
    return $dest;
  }
  
  public function move_to($dest) {
    $dest = $this->copy_to($dest);
    $this->rm();
    return $dest;
  }

///   <method name="make_default_iterator" returns="IO.FS.DirIterator">
///     <brief>Возвращает итератор по умолчанию</brief>
///     <body>
  public function make_default_iterator() {
    return IO_FS::Query()->apply_to($this);
  }
///      </body>
///   </method>

///   </protocol>
}
/// </class>


/// <class name="IO.FS.Query">
///   <brief>Условия выборки элементов каталога</brief>
///   <implements interface="Core.PropertyAccessInterface" />
///   <details>
///     <p>Временная реализация с минимальной функциональностью -- поиск по регулярному выражению
///        или шаблону с возможностью рекурсии. Планируется изменить реализацию для поддержки поиска
///        по датам, типам файлов и т.д.</p>
///     <p>Свойства:</p>
///     <dl>
///       <dt>regexp</dt><dd>регулярное выражение</dd>
///       <dt>recursive</dt><dd>признак выполнения рекурсивного поиска</dd>
///     </dl>
///   </details>
class IO_FS_Query
  implements Core_PropertyAccessInterface {

  const DEFAULT_REGEXP = '{.+}';

  protected $regexp    = self::DEFAULT_REGEXP;
  protected $recursive = false;

///   <protocol name="configuring">

///   <method name="regexp" returns="IO.FS.Query">
///     <brief>Задает регулярное выражение для поиска</brief>
///     <args>
///       <arg name="regexp" type="string" brif="регулярное выражение" />
///     </args>
///     <body>
  public function regexp($regexp) {
    $this->regexp = (string) $regexp;
    return $this;
  }
///     </body>
///   </method>

///   <method name="glob" returns="IO.FS.Query">
///     <brief>Задает шаблон поиска с использованием * и ?</brief>
///     <args>
///       <arg name="wildcard" type="string" brief="выражение" />
///     </args>
///     <body>
  public function glob($wildcard) {
    $this->regexp ='{'.Core_Strings::replace(
      Core_Strings::replace( Core_Strings::replace(
      $wildcard, '.', '\.'), '?', '.'), '*', '.*').'}';
    return $this;
  }
///     </body>
///   </method>

///   <method name="recursive" returns="IO.FS.Query">
///     <brief>Устанавливает флаг рекурсивного поиска в каталоге</brief>
///     <args>
///       <arg name="use_recursion" type="boolean" brief="использовать или нет" />
///     </args>
///     <body>
  public function recursive($use_recursion = true) {
    $this->recursive = (boolean) $use_recursion;
    return $this;
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="performing">

///   <method name="apply_to" returns="IO.FS.DirIterator">
///     <brief>Возвращает итератор, соответствующий условиям поиска</brief>
///     <args>
///       <arg name="dir" type="IO.FS.Dir" brief="объект каталога" />
///     </args>
///     <body>
  public function apply_to(IO_FS_Dir $dir) {
    if ($this->recursive)
      return new RecursiveIteratorIterator(new IO_FS_DirIterator($dir, $this));
    else
      return new IO_FS_DirIterator($dir, $this);
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="quering">

///   <method name="is_recursive" returns="boolean">
///     <brief>Проверяет, является ли поиск рекурсивным</brief>
///     <body>
  public function is_recursive() { return $this->recursive; }
///     </body>
///   </method>

///   <method name="allows" returns="boolean">
///     <brief>Проверяет, соответствует ли заданный путь условиям поиска</brief>
///     <args>
///       <arg name="path" type="string" brief="путь" />
///     </args>
///     <body>
  public function allows($path = '.') {
    if (IO_FS::is_dir($path) && $this->recursive) return true;

    return Core_Regexps::match($this->regexp, (string) $path);
  }
///     </body>
///   </method>

///   <method name="forbids" returns="boolean">
///     <brief>Проверяет отсутствие сооответствия заданного пути условиям поиска</brief>
///     <args>
///       <arg name="path" type="string" brief="путь" />
///     </args>
///     <body>
  public function forbids($path = '.') { return !$this->allows($path); }
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
      case 'regexp':
      case 'recursive':
        return $this->$property;
      default:
        throw new Core_MissingPropertyException($property);
    }
  }
///     </body>
///   </method>

///   <method name="__set" returns="IO.FS.Query">
///     <brief>Устанавливает значение свойства</brief>
///     <args>
///       <arg name="property" type="string" breif="имя свойства" />
///       <arg name="value" brief="значение" />
///     </args>
///     <body>
  public function __set($property, $value) {
    switch ($property) {
      case 'regexp':
        $this->regexp = (string) $value;
        break;
      case 'recursive':
        $this->recursive = (boolean) $value;
        break;
      default:
        throw new Core_MissingPropertyException($property);
    }
    return $this;
  }
///     </body>
///   </method>

///   <method name="__isset" returns="boolean">
///     <brief>Проверяет установку значения свойства</brief>
///     <args>
///       <arg name="property" type="string" />
///     </args>
///     <body>
  public function __isset($property) {
    switch ($property) {
      case 'regexp':
      case 'recursive':
        return isset($this->$property);
      default:
        return false;
    }
  }
///     </body>
///   </method>

///   <method name="__unset">
///     <brief>Удаляет свойство</brief>
///     <args>
///       <arg name="property" type="string" brief="имя свойства" />
///     </args>
///     <details>
///       <p>Удаление свойств запрещено.</p>
///     </details>
///     <body>
  public function __unset($property) {
    switch ($property) {
      case 'regexp':
      case 'recursive':
        throw new Core_UndestroyablePropertyException($property);
      default:
        throw new Core_MissingPropertyException($property);
    }
  }
///     </body>
///   </method>

///   </protocol>
}
/// </class>


/// <class name="IO.FS.DirIterator">
///   <brief>Итератор по содержимому каталога</brief>
///   <implements interface="RecursiveIterator" />
///   <details>
///     <p>Предназначен для использования вместе с объектами класса IO.FS.Dir.</p>
///   </details>
class IO_FS_DirIterator
  implements RecursiveIterator {

  protected $current = false;
  protected $query;
  protected $dir;
  protected $id;

///   <protocol name="creating">
///   <method name="__construct">
///     <brief>Конструктор</brief>
///     <args>
///       <arg name="dir"   type="IO.FS.Dir"   brief="объект каталога" />
///       <arg name="query" type="IO.FS.Query" brief="критерии выборки элементов каталога" default="null" />
///     </args>
///     <body>
  public function __construct(IO_FS_Dir $dir, IO_FS_Query $query = null) {
    $this->dir   = $dir;
    $this->query = $query ? $query : IO_FS::Query();
  }
///     </body>
///   </method>
///   </protocol>

///   <protocol name="iterating" interface="Core.RecursiveIteratorInterface">

///   <method name="hasChildren" returns="boolean">
///     <brief>Проверяет, является ли текущий элемент подкаталогом</brief>
///     <body>
  public function hasChildren() {
    return ($this->query->is_recursive() && ($this->current instanceof IO_FS_Dir));
  }
///     </body>
///   </method>

///   <method name="getChildren" returns="IO.FS.DirIterator">
///     <brief>Возвращает итератор по подкаталогу</brief>
///     <body>
  public function getChildren() {
    return new IO_FS_DirIterator($this->current, $this->query);
  }
///     </body>
///   </method>

///   <method name="current" returns="IO.FS.FSObject">
///     <brief>Возвращает текущий элемент</brief>
///     <body>
  public function current() { return $this->current; }
///     </body>
///   </method>

///   <method name="key" returns="string">
///     <brief>Возвращает ключ текущего элемента</brief>
///     <details>
///       <p>Ключ представляет собой значение свойства path текущего элемента.</p>
///     </details>
///     <body>
  public function key() { return $this->current->path; }
///     </body>
///   </method>

///   <method name="rewind">
///     <brief>Сбрасывает итератор</brief>
///     <body>
  public function rewind() {
    if ($this->id) @closedir($this->id);
    rewinddir($this->id = @opendir($this->dir->path));
    $this->skip_to_next();
  }
///     </body>
///   </method>

///   <method name="next">
///     <brief>Переходит к следующему элементу итератора</brief>
///     <body>
  public function next() { return $this->skip_to_next(); }
///     </body>
///   </method>

///   <method name="valid" returns="boolean">
///     <brief>Проверяет существование текущего элемента итератора</brief>
///     <body>
  public function valid() { return $this->current ? true : false; }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="supporting">

///   <method name="skip_to_next" access="protected">
///     <brief>Переходит к очередному элементу, удовлетворяющему условиям поиска</brief>
///     <body>
  protected function skip_to_next() {
    do {
      $name = readdir($this->id);
      $path = $this->dir->path.'/'.$name;
    } while ($name &&
              ($name == '.'  ||
               $name == '..' ||
               $this->query->forbids($path = $this->dir->path.'/'.$name)
               ));
    if ($name) {
      $this->current = IO_FS::file_object_for($path);
    } else {
      @closedir($this->id);
      $this->id = null;
      $this->current = null;
    }
    return $this->current;
  }
///     </body>
///   </method>

///   </protocol>
}
/// </class>

/// </module>
