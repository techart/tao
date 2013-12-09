<?php
/**
 * CLI.Application
 * 
 * Простейшая структура CLI-приложения
 * 
 * @package CLI\Application
 * @version 0.3.0
 */
Core::load('CLI', 'CLI.GetOpt', 'IO', 'Log', 'Config.DSL');

/**
 * Класс модуля
 * 
 * @package CLI\Application
 */
class CLI_Application implements Core_ModuleInterface {
  const MODULE  = 'CLI.Application';
  const VERSION = '0.3.0';
}


/**
 * Базовый класс исключений
 * 
 * @package CLI\Application
 */
class CLI_Application_Exception extends CLI_Exception {}


/**
 * Базовый класс CLI-приложения
 * 
 * <p>Базовый класс определяет простую структуру приложения командной строки со следующими
 * возможностями:</p>
 * <ul><li>хранение информации о настройках приложения в объекте конфигурации;</li>
 * <li>разбор параметров командной строки и установка соответствующих значений
 * в объекте конфигурации;</li>
 * <li>загрузка конфигурационного файла в формате DSL.Config и установка значений
 * объекта конфигурации из этого файла;</li>
 * <li>автоматический вывод списка поддерживаемых опцией по кючам -h/--help;</li>
 * <li>ведение логов.</li>
 * </ul><p>Объекты класса содержат следующие атрибуты, доступные снаружи на чтение, а изнутри —
 * на запись:</p>
 * options
 * объект класса CLI.GetOpt.Parser, отвечающий за разбор командной  строки
 * config
 * объект класса stdClass, содержащий настройки приложения. Значения настроек могут
 * быть получены из файла конфигурации или параметров командной строки;
 * log
 * объект класса Log.Context, добавляющий в запись лога поле module c именем модуля
 * приложения.
 * <p>Жизненный цикл приложения выглядит следующим образом:</p>
 * <ol><li>Создание объекта приложения (__construct). Создаются объекты options, log и config
 * со значениями по умолчанию.</li>
 * <li>Первоначальная настройка приложения (setup). Метод setup предназначен для настройки
 * парсера options и установки значений по умолчанию для объекта config.</li>
 * <li>Разбор опций командной строки, запись соответствующих значений в config.</li>
 * <li>Конфигурирование приложения (configure). Метод configure предназначен для
 * окончательной настройки параметров приложения. На момент выполнения метода в
 * объекте конфигурации уже присутствуют параметры, указанные в командной строке,
 * таким образом, в качестве такого параметра можно получить путь к файлу конфигурации.
 * Для подгрузки файла можно использовать вспомогательный метод load_config().</li>
 * <li>Инициализация логов.</li>
 * <li>Выполнение метода show_usage, если установлен параметр конфигурации show_user, или
 * вызов метода run($argv), выполняющий основной код приложения. Целочисленный
 * результат выполнения метода run() используется в качестве кода завершения
 * приложения.</li>
 * <li>Завершение приложения (shutdown). Метод предназначен для определения операций,
 * которые должны быть выполнены в случае нормального или аварийного завершения
 * приложения.</li>
 * <li>Закрытие логов.</li>
 * </ol>
 * 
 * @abstract
 * @package CLI\Application
 */
abstract class CLI_Application_Base implements Core_PropertyAccessInterface {

  protected $options;
  protected $config;
  protected $log;


/**
 * Конструктор
 * 
 */
  public function __construct() {
    $this->options = CLI_GetOpt::Parser()->
      boolean_option('show_usage', '-h', '--help', 'Shows help message');

    $this->log = Log::logger()->context(array(
      'module' => Core_Types::module_name_for($this)));

    $this->config = Core::object(array(
      'log'        => Log::logger(),
      'show_usage' => false));

  }



/**
 * Первоначальная настройка приложения
 * 
 */
  protected function setup() {}

/**
 * Завершение работы приложения
 * 
 */
  protected function shutdown() {}

/**
 * Выполняет конфигурирование приложения
 * 
 */
  protected function configure() {}



/**
 * Выполняет пользовательскую логику приложения
 * 
 * @abstract
 * @param array $argv
 * @return int
 */
  abstract public function run(array $argv);


/**
 * Точка входа приложения
 * 
 * @param array $argv
 */
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



/**
 * Возвращает значение свойства
 * 
 * @param string $property
 * @return mixed
 */
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

/**
 * Устанавливает значение свойства
 * 
 * @param string $property
 * @param  $value
 * @return Service_Yandex_Direct_Manager_Application
 */
  public function __set($property, $value) {
    throw new Core_ReadOnlyObjectException($this);
  }

/**
 * Проверяет установку значения свойства
 * 
 * @param string $property
 * @return boolean
 */
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

/**
 * Сбрасывает значение свойства
 * 
 * @param string $property
 */
  public function __unset($property) {
    throw new Core_ReadOnlyObjectException($this);
  }



/**
 * Завершает выполнение
 * 
 * @param int $status
 */
  protected function finalize($status) {
    $this->shutdown();
    Log::logger()->close();
    return $this->exit_wrapper($status);
  }

/**
 * Обертка над оператором exit
 * 
 * @param int $status
 * @return int
 */
  protected function exit_wrapper($status) {
    exit((int) $status);
  }

/**
 * Выводит в stdout описание программы
 * 
 * @return int
 */
  protected function show_usage() {
    IO::stdout()->write($this->options->usage_text());
    return 0;
  }

/**
 * Выполняет обработку ошибок
 * 
 * @param Exception $e
 */
  protected function handle_error(Exception $e) {
    try {
      $this->log->critical($e->getMessage());
    } catch (Exception $e) {}
    return -1;
  }

/**
 * Подгружает файл конфигурации в формате Config.DSL
 * 
 * @param string $path
 * @return CLI_Application_Base
 */
  protected function load_config($path) {
    if (IO_FS::exists($path)) {
      $this->log->debug('Using config: %s', $path);
      Config_DSL::Builder($this->config)->load($path);
    } else
      throw new CLI_ApplicationException("Missing config file: $path");
    return $this;
  }

}

