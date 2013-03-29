<?php
/// <module name="CLI.Application" version="0.3.0" maintainer="timokhin@techart.ru">
///   <brief>Простейшая структура CLI-приложения</brief>
Core::load('CLI', 'CLI.GetOpt', 'IO', 'Log', 'Config.DSL');

/// <class name="CLI.Application" stereotype="module">
///   <depends supplier="CLI" stereotype="uses" />
///   <depends supplier="CLI.GetOpt" stereotype="uses" />
///   <depends supplier="IO" stereotype="uses" />
///   <depends supplier="Log" stereotype="uses" />
///   <depends supplier="Config.DSL" stereotype="uses" />
///   <depends supplier="CLI.Application.Exception" stereotype="defines" />
///   <depends supplier="CLI.Application.Base" stereotype="defines" />
///   <brief>Класс модуля</brief>
class CLI_Application implements Core_ModuleInterface {
///   <constants>
  const MODULE  = 'CLI.Application';
  const VERSION = '0.3.0';
///   </constants>
}
/// </class>


/// <class name="CLI.Application.Exception" extends="CLI.Exception" stereotype="exception">
///   <brief>Базовый класс исключений</brief>
class CLI_Application_Exception extends CLI_Exception {}
/// </class>


/// <class name="CLI.Application.Base" stereotype="abstract">
///   <implements interface="Core.PropertyAccessInterface" />
///   <brief>Базовый класс CLI-приложения</brief>
///   <details>
///     <p>Базовый класс определяет простую структуру приложения командной строки со следующими
///        возможностями:</p>
///     <ul>
///       <li>хранение информации о настройках приложения в объекте конфигурации;</li>
///       <li>разбор параметров командной строки и установка соответствующих значений
///           в объекте конфигурации;</li>
///       <li>загрузка конфигурационного файла в формате DSL.Config и установка значений
///           объекта конфигурации из этого файла;</li>
///       <li>автоматический вывод списка поддерживаемых опцией по кючам -h/--help;</li>
///       <li>ведение логов.</li>
///     </ul>
///     <p>Объекты класса содержат следующие атрибуты, доступные снаружи на чтение, а изнутри —
///        на запись:</p>
///     <dl>
///      <dt>options</dt>
///      <dd>объект класса CLI.GetOpt.Parser, отвечающий за разбор командной  строки</dd>
///      <dt>config</dt>
///      <dd>объект класса stdClass, содержащий настройки приложения. Значения настроек могут
///          быть получены из файла конфигурации или параметров командной строки;</dd>
///      <dt>log</dt>
///      <dd>объект класса Log.Context, добавляющий в запись лога поле module c именем модуля
///          приложения.</dd>
///     </dl>
///     <p>Жизненный цикл приложения выглядит следующим образом:</p>
///     <ol>
///       <li>Создание объекта приложения (__construct). Создаются объекты options, log и config
///           со значениями по умолчанию.</li>
///       <li>Первоначальная настройка приложения (setup). Метод setup предназначен для настройки
///           парсера options и установки значений по умолчанию для объекта config.</li>
///       <li>Разбор опций командной строки, запись соответствующих значений в config.</li>
///       <li>Конфигурирование приложения (configure). Метод configure предназначен для
///           окончательной настройки параметров приложения. На момент выполнения метода в
///           объекте конфигурации уже присутствуют параметры, указанные в командной строке,
///           таким образом, в качестве такого параметра можно получить путь к файлу конфигурации.
///           Для подгрузки файла можно использовать вспомогательный метод load_config().</li>
///       <li>Инициализация логов.</li>
///       <li>Выполнение метода show_usage, если установлен параметр конфигурации show_user, или
///           вызов метода run($argv), выполняющий основной код приложения. Целочисленный
///           результат выполнения метода run() используется в качестве кода завершения
///           приложения.</li>
///       <li>Завершение приложения (shutdown). Метод предназначен для определения операций,
///           которые должны быть выполнены в случае нормального или аварийного завершения
///           приложения.</li>
///       <li>Закрытие логов.</li>
///     </ol>
///   </details>
abstract class CLI_Application_Base implements Core_PropertyAccessInterface {

  protected $options;
  protected $config;
  protected $log;

///   <protocol name="creating">

///   <method name="__construct">
///     <brief>Конструктор</brief>
///     <details>
///       <p>Создает объекты options, log и config.</p>
///       <p>Объект options инициализируется опцией -h/--help, которой соответствует
///          элемент show_usage в объекте конфигурации config. Обработка опции
///          выполняется автоматически.</p>
///       <p>Объект log представляет собой контекст логирования, производный от
///          диспетчера Log::logger(), добавляющий атрибут module, содержащий
///          имя модуля приложения.</p>
///       <p>Объект config по умолчанию содержит два атрибута:</p>
///       <dl>
///         <dt>show_usage</dt>
///         <dd>признак запроса вывода информации об использовании;</dd>
///         <dt>log</dt>
///          <dd>Ссылка на диспетчер логов Log::logger() для удобства его
///              настройки в конфигурационном файле.</dd>
///       </dl>
///     </details>
///     <body>
  public function __construct() {
    $this->options = CLI_GetOpt::Parser()->
      boolean_option('show_usage', '-h', '--help', 'Shows help message');

    $this->log = Log::logger()->context(array(
      'module' => Core_Types::module_name_for($this)));

    $this->config = Core::object(array(
      'log'        => Log::logger(),
      'show_usage' => false));

  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="performing">

///   <method name="setup" access="protected">
///     <brief>Первоначальная настройка приложения</brief>
///     <details>
///       <p>Метод предназначен для первоначальной настройки приложения,
///          в частности:</p>
///       <ul>
///         <li>определения списка поддерживаемых опций для парсера
///             аргументов командной строки options;</li>
///         <li>установки значений параметров по умолчанию в объекте
///             конфигурации config.</li>
///       </ul>
///     </details>
///     <body>
  protected function setup() {}
///     </body>
///   </method>

///   <method name="shutdown" access="protected">
///     <brief>Завершение работы приложения</brief>
///     <details>
///       <p>Метод предназначен для выполнения операций, необходимых при
///          завершении работы приложения, например, закрытия файлов и
///          соединений с базой данных.</p>
///       <p>Закрытие логов производится классом приложения автоматически.</p>
///     </details>
///     <body>
  protected function shutdown() {}
///     </body>
///   </method>

///   <method name="configure" access="protected">
///     <brief>Выполняет конфигурирование приложения</brief>
///     <details>
///       <p>В отличие от метода setup(), этот метод вызывается после разбора
///          командной строки. Метод рекомендуется использовать, в частности,
///          для подгрузки конфигурационного файла с помощью load_config().</p>
///     </details>
///     <body>
  protected function configure() {}
///     </body>
///   </method>

///   </protocol>

///   <protocol name="performing">

///   <method name="run" returns="int" stereotype="abstract">
///   <brief>Выполняет пользовательскую логику приложения</brief>
///     <args>
///       <arg name="argv" type="array" brief="массив параметров командной строки" />
///     </args>
///     <details>
///       <p>Передаваемый массив параметров командной строки содержит параметры,
///          оставшиеся после разбора парсером.</p>
///     </details>
///     <body>
  abstract public function run(array $argv);
///     </body>
///   </method>


///   <method name="main">
///   <brief>Точка входа приложения</brief>
///     <args>
///       <arg name="argv" type="array" brief="массив параметров командной строки" />
///     </args>
///     <details>
///     </details>
///     <body>
  public function main(array $argv) {
    try {
      $this->setup();

      $this->options->parse($argv, $this->config);

      $this->configure();

      Log::logger()->init();

      $rc =  $this->config->show_usage ? $this->show_usage() : $this->run($argv);

    } catch (Exception $e) {
      return $this->finalize($this->handle_error($e));
    }
    return $this->finalize($rc);
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
      case 'options':
      case 'log':
      case 'config':
        return $this->$property;
      default:
        throw new Core_MissingPropertyException($property);
    }
  }
///     </body>
///   </method>

///   <method name="__set" returns="Service.Yandex.Direct.Manager.Application">
///     <brief>Устанавливает значение свойства</brief>
///     <args>
///       <arg name="property" type="string" brief="имя свойства" />
///       <arg name="value" brief="значение свойства" />
///     </args>
///     <details>
///       <p>Свойства объекта доступны только на чтение.</p>
///     </details>
///     <body>
  public function __set($property, $value) {
    throw new Core_ReadOnlyObjectException($this);
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
      case 'options':
      case 'log':
      case 'config':
        return isset($this->$property);
      default:
        return false;
    }
  }
///     </body>
///   </method>

///   <method name="property" type="string">
///     <brief>Сбрасывает значение свойства</brief>
///     <args>
///       <arg name="property" type="string" brief="имя свойства" />
///     </args>
///     <details>
///       <p>Свойства объекта доступны только на чтение.</p>
///     </details>
///     <body>
  public function __unset($property) {
    throw new Core_ReadOnlyObjectException($this);
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="supporting">

///   <method name="finalize" access="protected">
///     <brief>Завершает выполнение</brief>
///     <args>
///       <arg name="status" type="int" brief="код завершения" />
///     </args>
///     <details>
///       <p>Вызывает пользовательский метод shutdown(), закрывает логи и выходит с указанным кодом
///          завершения.</p>
///     </details>
///     <body>
  protected function finalize($status) {
    $this->shutdown();
    Log::logger()->close();
    return $this->exit_wrapper($status);
  }
///     </body>
///   </method>

///   <method name="exit_wrapper" access="protected" returns="int">
///     <brief>Обертка над оператором exit</brief>
///     <args>
///       <arg name="status" type="int" brief="статус завершения" />
///     </args>
///     <body>
  protected function exit_wrapper($status) {
    exit((int) $status);
  }
///     </body>
///   </method>

///   <method name="show_usage" access="protected" returns="int">
///     <brief>Выводит в stdout описание программы</brief>
///     <details>
///       <p>Выводимый текст содержит собственно текст описания и список поддерживаемых опций
///          командной строки с описанием каждой опции.</p>
///     </details>
///     <body>
  protected function show_usage() {
    IO::stdout()->write($this->options->usage_text());
    return 0;
  }
///     </body>
///   </method>

///   <method name="handle_error" access="protected">
///     <brief>Выполняет обработку ошибок</brief>
///     <args>
///       <arg name="e" type="Exception" brief="исключение" />
///     </args>
///     <details>
///       <p>Метод вызывается в случае генерации исключения. Реализация по умолчанию выводит текст
///         исключения в лог с уровнем critical  и завершает программу со статусом завершения -1.</p>
///     </details>
///     <body>
  protected function handle_error(Exception $e) {
    try {
      $this->log->critical($e->getMessage());
    } catch (Exception $e) {}
    return -1;
  }
///     </body>
///   </method>

///   <method name="load_config" returns="CLI.Application.Base" access="protected">
///     <brief>Подгружает файл конфигурации в формате Config.DSL</brief>
///     <args>
///       <arg name="path" type="string" brief="путь к файлу конфигурации" />
///     </args>
///     <details>
///       <p>Если приложение допускает указание пути к файлу конфигурации в
///          в командной строке, этот метод лучше всего вызывать из метода
///          configure().</p>
///       <p>Если приложение работает с фиксированными конфигурационными файлам,
///          метод можно вызвать из метода setup().</p>
///     </details>
///     <body>
  protected function load_config($path) {
    if (IO_FS::exists($path)) {
      $this->log->debug('Using config: %s', $path);
      Config_DSL::Builder($this->config)->load($path);
    } else
      throw new CLI_ApplicationException("Missing config file: $path");
    return $this;
  }
///     </body>
///   </method>

///   </protocol>
}
/// </class>
/// <composition>
///   <source class="CLI.Application.Base" role="application" multiplicity="1" />
///   <target class="CLI.GetOpt.Parser" role="options" multiplicity="1" />
/// </composition>
/// <composition>
///   <source class="CLI.Application.Base" stereotype="application" multiplicity="1" />
///   <target class="Log.Context" stereotype="logger" multiplicity="1" />
/// </composition>

/// </module>
