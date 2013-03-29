<?php
/// <module name="Proc" version="0.2.0" maintainer="timokhin@techart.ru">
///   <brief>Модуль для с процессами и pipe</brief>

Core::load('IO');

/// <class name="Proc" stereotype="module">
///   <implements interface="Core.ModuleInterface" />
///   <depends supplier="Proc.Pipe" stereotype="creates" />
///   <depends supplier="Proc.Process" stereotype="creates" />
class Proc
  implements Core_ModuleInterface {

///   <constants>
  const VERSION = '0.2.0';
///   </constants>

///   <protocol name="building">

///   <method name="Process" returns="Proc.Process" scope="class">
///     <brief>Фабричный метод, возвращает объект класса Proc.Process</brief>
///     <args>
///       <arg name="command" type="string" brief="команда" />
///     </args>
///     <body>
  static public function Process($command) {
    return new Proc_Process($command);
  }
///     </body>
///   </method>

///   <method name="Pipe" returns="Proc.Pipe">
///     <brief>Фабричный метод, возвращает объект класса Proc.Process</brief>
///     <args>
///       <arg name="command" type="string" brief="команда" />
///       <arg name="mode" type="string" brief="способ открытия потока" />
///     </args>
///     <body>
  static public function Pipe($command, $mode = IO_Stream::DEFAULT_OPEN_MODE) {
    return new Proc_Pipe($command, $mode);
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="performing">

///   <method name="exec" returns="int" scope="class">
///     <brief>Выполняет команду</brief>
///     <args>
///       <arg name="command" type="string" brief="команда" />
///     </args>
///     <body>
  static public function exec($command) {
    $rc = 0;
    $lines = null;
    exec($command, $lines, $rc);
    return $rc;
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="quering">

///   <method name="process_exists" returns="boolean">
///     <brief>Проверяет существует ли процесс</brief>
///     <args>
///       <arg name="pid" type="int" brief="идентификатор процесса" />
///     </args>
///     <body>
  static public function process_exists($pid) { return posix_kill($pid, 0); }
///     </body>
///   </method>

///   </protocol>
}
/// </class>


/// <class name="Proc.Exception" extends="Core.Exception">
///   <brief>Класс искключения</brief>
class Proc_Exception extends Core_Exception {}
/// </class>


/// <class name="Proc.Pipe" extends="IO.Stream.ResourceStream">
///   <brief>Класс для работы с pipe</brief>
///   <depends supplier="Proc.Exception" stereotype="throws" />
class Proc_Pipe extends IO_Stream_ResourceStream {

  protected $exit_status = 0;

///   <protocol name="creating">

///   <method name="__construct">
///     <brief>Конструктор</brief>
///     <args>
///       <arg name="command" type="string" brief="команда" />
///       <arg name="mode" type="string" brief="способ открытия потока" />
///     </args>
///     <body>
  public function __construct($command, $mode = IO_Stream::DEFAULT_OPEN_MODE) {
    if (!$this->id = @popen($command, $mode))
      throw new Proc_Exception("Unable to open pipe: $command");
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="performing">

///   <method name="close">
///     <brief>Закрывате поток</brief>
///     <body>
  public function close() {
    $this->exit_status = @pclose($this->id);
    $this->id = null;
    return $this;
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="destroying">

///   <method name="__destruct">
///     <brief>Деструктор</brief>
///     <body>
  public function __destruct() {
    if ($this->id) $this->close();
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="accessing" interface="Core.PropertyAccessInterface">

///   <method name="__get" returns="mixed">
///     <brief>Доступ на чтение к свойствам объекта</brief>
///     <details>
///       <dl>
///         <dt>exit_status</dt><dd>Код возврата</dd>
///       </dl>
///     </details>
///     <args>
///       <arg name="property" type="string" brief="имя свойства" />
///     </args>
///     <body>
  public function __get($property) {
    switch ($property) {
      case 'exit_status':
        return $this->$property;
      default:
        return parent::__get($property);
    }
  }
///     </body>
///   </method>

///   <method name="__set" returns="mixed">
///     <brief>Доступ на запись к свойствам объекта</brief>
///     <details>
///       Выбрасывает исключение, доступ только для чтения
///     </details>
///     <args>
///       <arg name="property" type="string" brief="имя свойства" />
///       <arg name="value" brief="значение" />
///     </args>
///     <body>
  public function __set($property, $value) {
    switch ($property) {
      case 'exit_status':
        throw new Core_ReadOnlyPropertyException($property);
      default:
        return parent::__set($property, $value);
    }
  }
///     </body>
///   </method>

///   <method name="__isset" returns="boolean">
///     <brief>Проверяет установленно ил свойство</brief>
///     <body>
  public function __isset($property) {
    switch ($property) {
      case 'exit_status':
        return true;
      default:
        return parent::__isset($property);
    }
  }
///     </body>
///   </method>

///   </protocol>
}
/// </class>


/// <class name="Proc.Process">
///   <brief>Класс для работы с процессами</brief>
///   <implements interface="Core.PropertyAccessInterface" />
///   <depends supplier="IO.Stream.ResourceStream" stereotype="creates" />
class Proc_Process implements Core_PropertyAccessInterface {
  protected $id;

  private   $run_options = array();

  protected $command;
  protected $working_dir;
  protected $environment;

  protected $pid;

  protected $input;
  protected $output;
  protected $error;

///   <protocol name="creating">

///   <method name="__construct">
///     <brief>Конструктор</brief>
///     <args>
///       <arg name="command" type="string" brief="команда" />
///     </args>
///     <body>
  public function __construct($command) {
    $this->command = $command;
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="configuring">

///   <method name="working_dir" returns="Proc.Process">
///     <brief>Устанавливает рабочий каталог</brief>
///     <args>
///       <arg name="path" type="string" brief="путь к каталогу" />
///     </args>
///     <body>
  public function working_dir($path) {
    $this->working_dir = $path;
    return $this;
  }
///     </body>
///   </method>

///   <method name="environment" returns="Proc.Process">
///     <brief>Добавляет/устанавливает переменный окружения</brief>
///     <args>
///       <arg name="env" type="array" brief="массив переменных" />
///     </args>
///     <body>
  public function environment(array $env) {
    if (!Core_Types::is_array($this->environment)) $this->environment = array();
    foreach ($env as $k => $v) $this->environment[$k] = (string) $v;
    return $this;
  }
///     </body>
///   </method>

///   <method name="input" returns="Proc.Process">
///     <brief>Устанавливает входной поток</brief>
///     <details>
///       $input может быть true, тогда входной поток будет доступен для чтения/записи,
///       или строкой, содержащей путь к файлу
///     </details>
///     <args>
///       <arg name="input" type="boolean|string" brief="true или путь к файлу" />
///     </args>
///     <body>
  public function input($input = true) {
    return $this->define_redirection($input, 0, 'r');
  }
///     </body>
///   </method>

///   <method name="output" returns="Proc.Process">
///     <brief>Устанавливает выходной поток</brief>
///     <details>
///       $output может быть true, тогда выходной поток будет доступен для чтения/записи,
///       или строкой, содержащей путь к файлу
///     </details>
///     <args>
///       <arg name="output" type="boolean|string" brief="true или путь к файлу" />
///       <arg name="mode" type="string" brief="способ открытия потока" />
///     </args>
///     <body>
  public function output($output = true, $mode = 'w') {
    return $this->define_redirection($output, 1, $mode);
  }
///     </body>
///   </method>

///   <method name="error" returns="Proc.Process">
///     <brief>Устанавливает поток ошибок</brief>
///     <details>
///       $error может быть true, тогда входной поток будет доступен для чтения/записи,
///       или строкой, содержащей путь к файлу
///     </details>
///     <args>
///       <arg name="error" type="boolean|string" brief="true или путь к файлу" />
///     </args>
///     <body>
  public function error($error = true) {
    return $this->define_redirection($error, 2, 'w');
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="processing">

///   <method name="finish_input" returns="Proc.Process">
///     <brief>Закрывает входной поток</brief>
///     <body>
  public function finish_input() {
    if ($this->input) $this->input->close();
    return $this;
  }
///     </body>
///   </method>

///   <method name="run" returns="Proc.Process">
///     <brief>Запускает процесс</brief>
///     <body>
  public function run() {
    $pipes = array();

    if ($this->id = proc_open($this->command, $this->run_options, $pipes, $this->working_dir, $this->environment)) {

      if (isset($pipes[0])) $this->input  = IO_Stream::ResourceStream($pipes[0]);
      if (isset($pipes[1])) $this->output = IO_Stream::ResourceStream($pipes[1]);
      if (isset($pipes[2])) $this->error  = IO_Stream::ResourceStream($pipes[2]);

      $this->run_options = null;
    }

    return $this;
  }
///     </body>
///   </method>

///   <method name="close" returns="int">
///     <brief>Закрывает процесс и все открытые потоки</brief>
///     <body>
  public function close() {
    if (!$this->is_started()) return null;

    foreach (array('input', 'output', 'error') as $pipe)
      if (isset($this->$pipe)) $this->$pipe->close();

    proc_close($this->id);

    $this->id = null;
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="quering">

///   <method name="is_started" returns="boolean">
///     <brief>Проверяет запущен ли процесс</brief>
///     <body>
  public function is_started() { return $this->id ? true : false; }
///     </body>
///   </method>

///   <method name="get_status" returns="Data.Struct">
///     <brief>Возвращает статус процесса </brief>
///     <body>
  public function get_status() {
    return ($data = proc_get_status($this->id)) ?
      (object) $data :
      null;
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="accessing" interface="Core.PropertyAccessInterface">

///   <method name="__get" returns="mixed">
///     <brief>Доступ к свойствам объекта на чтение</brief>
///     <details>
///     <dl>
///         <dt>id</dt><dd>идетнификатор процесса</dd>
///         <dt>input</dt><dd>входной поток</dd>
///         <dt>output</dt><dd>выходной поток</dd>
///         <dt>error</dt><dd>поток ошибок</dd>
///         <dt>command</dt><dd>команда</dd>
///         <dt>environment</dt><dd>переменные окружения</dd>
///         <dt>working_dir</dt><dd>рабочая директория</dd>
///     </dl>
///     </details>
///     <args>
///       <arg name="property" type="string" brief="имя свойства" />
///     </args>
///     <body>
  public function __get($property) {
    switch ($property) {
      case 'id':
      case 'input':
      case 'output':
      case 'error':
      case 'command':
      case 'environment':
      case 'working_dir':
        return $this->$property;
      default:
        throw new Core_MissingPropertyException($property);
    }
  }
///     </body>
///   </method>

///   <method name="__set" returns="mixed">
///     <brief>Доступ к свойствам объекта на запись</brief>
///     <details>
///       Выбрасывает исключение, доступ только для чтения
///     </details>
///     <args>
///       <arg name="property" type="string" brief="имя свойства" />
///       <arg name="value" brief="значение" />
///     </args>
///     <body>
  public function __set($property, $value) {
    switch ($property) {
      case 'id':
      case 'input':
      case 'output':
      case 'error':
      case 'command':
      case 'environment':
      case 'working_dir':
        throw new Core_ReadOnlyPropertyException($property);
      default:
        throw new Core_MissingPropertyException($property);
    }
  }
///     </body>
///   </method>

///   <method name="__isset" returns="boolean">
///     <brief>Проверяет установленно ли свойство объекта</brief>
///     <args>
///       <arg name="property" type="string" brief="имя свойства" />
///     </args>
///     <body>
  public function __isset($property) {
    switch ($property) {
      case 'id':
      case 'input':
      case 'output':
      case 'error':
      case 'command':
      case 'environment':
      case 'working_dir':
        return true;
      default:
        return false;
    }
  }
///     </body>
///   </method>

///   <method name="__unset">
///     <brief>Очищает свойство объекта</brief>
///     <details>
///       Выбрасывает исключение, доступ только для чтения
///     </details>
///     <args>
///       <arg name="property" type="string" brief="имя свойства" />
///     </args>
///     <body>
  public function __unset($property) {
    throw $this->__isset($property) ?
      new Core_UndestroyablePropertyException($property) :
      new Core_MissingPropertyException($property);
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="supporting">

///   <method name="define_redirection" returns="Proc.Process" access="private">
///     <brief>Направляет потоки</brief>
///     <body>
  private function define_redirection($source, $idx, $mode) {
    if ($source === true)
      $this->run_options[$idx] = array('pipe', $mode);
    else
      $this->run_options[$idx] = array('file', (string) $source, $mode);
    return $this;
  }
///     </body>
///   </method>

///   </protocol>
}
/// </class>

/// </module>
