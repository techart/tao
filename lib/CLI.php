<?php
/// <module name="CLI" version="0.2.1" maintainer="timokhin@techart.ru">
///   <brief>Поддержка CLI-приложений</brief>
///   <details>
///     <p>Иерархия модулей CLI реализует простейший фреймворк для написания приложений с
///        интерфейсом командной строки. Основная идея фреймворка заключается в том, чтобы не писать
///        отдельный сценарий командной строки для запуска PHP-кода, а вместо этого реализовать
///        единый универсальный сценарий запуска, принимающий в качестве параметра имя модуля,
///        реализующего специальный интерфейс.</p>
///     <p>В качестве сценария запуска используется стандартный сценарий tao-run, находящийся в
///        каталоге bin. В качестве первого параметра запуска ему передается имя модуля в
///        стандартной нотации (через точку), все остальные параметры предназначены для обработки
///        вызываемым модулем. При этом сценарий tao-run обеспечивает загрузку ядра, и необходимых
///        модулей CLI, что упрощает разработку утилит командной строки и обеспечивает
///        единообразность их вызова, в частности, с помощью стандартных переменных окружения.</p>
///     <p>Вызываемый модуль должен реализовывать интерфейс CLI.RunInterface, содержащий
///        единственный статический метод main().</p>
///   </details>

///   <class name="CLI" stereotype="module">
///   <implements interface="Core.ModuleInterface" />
///   <depends supplier="CLI.NotRunnableModuleException" stereotype="throws" />
class CLI implements Core_ModuleInterface {

///   <constants>
  const MODULE  = 'CLI';
  const VERSION = '0.2.1';
///   </constants>

///   <protocol name="performing">

///   <method name="run_module" scope="class">
///     <brief>Запускает CLI-приложение</brief>
///     <details>
///       <p>Первый элемент передаваемого массива параметров должен содержать имя выполняемого
///          модуля, последующие элементы -- параметры вызова, обрабатываемые вызываемым
///          модулем.</p>
///       <p>Метод производит загрузку указанного модуля, выполняет проверку реализации классом
///         модуля интерфейса CLI.RunInterface и вызывает метод main класса модуля.</p>
///       <p>Метод возвращает результат выполнения метода main(), который используется в качестве
///          кода возврата приложения или генерирует исключение CLI.NotRunnableModuleException, если
///          модуль не поддерживает соответствующий интерфейс.</p>
///     </details>
///     <args>
///       <arg name="argv" type="array" brief="входные параметры CLI приложения" />
///     </args>
///     <body>
  static public function run_module(array $argv) {
    Core::load($argv[0]);
    if (Core_Types::reflection_for($module = Core_Types::real_class_name_for($argv[0]))->implementsInterface('CLI_RunInterface'))
      return call_user_func(array($module, 'main'), $argv);
    else {
      throw new CLI_NotRunnableModuleException($argv[0]);
    }
  }
///     </body>
///   </method>

///   </protocol>
}
/// </class>


/// <class name="CLI.Exception" extends="Core.Exception" stereotype="exception">
///   <brief>Базовый класс исключений модуля</brief>
class CLI_Exception extends Core_Exception {}
/// </class>


/// <class name="CLI.NotRunnableModuleException" extends="CLI.Exception" stereotype="exception">
///   <brief>Исключение: модуль не является запускаемым</brief>
class CLI_NotRunnableModuleException extends CLI_Exception {

  protected $module;

///   <protocol name="creating">

///   <method name="__construct">
///     <brief>Конструктор</brief>
///     <args>
///       <arg name="module" type="string" brief="имя модуля" />
///     </args>
///     <details>
///       <p>Свойства:</p>
///       <dl>
///         <dt>module</dt><dd>имя модуля, который не может быть запущен.</dd>
///       </dl>
///     </details>
///     <body>
  public function __construct($module) {
    $this->module = $module;
    parent::__construct("Not runnable module: $this->module");
  }
///     </body>
///   </method>

///   </protocol>
}
/// </class>


/// <interface name="CLI.RunInterface">
///   <brief>Интерфейс запускаемых модулей</brief>
///   <details>
///     <p>Для того, чтобы модуль стал запускаемым, он должен реализовать статический метод
///        main().</p>
///     <p>В качестве аргумента метод получает массив параметров командной строки, включающей
///        в себя в качестве первого элемента собственно имя модуля.</p>
///   </details>
interface CLI_RunInterface {

///   <protocol name="performing">

///   <method name="main" scope="class">
///     <brief>Точка входа</brief>
///     <args>
///       <arg name="argv" type="array" brief="массив аргументов командной строки" />
///     </args>
///     <body>
  static public function main(array $argv);
}
///     </body>
///   </method>

///   </protocol>

/// </interface>

/// </module>
