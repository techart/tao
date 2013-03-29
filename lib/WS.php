<?php
/// <module name="WS" version="0.2.0" maintainer="timokhin@techart.ru">
/// <brief>Минимальный уровень абстракции обработки HTTP-запросов</brief>
/// <details>
///   <p>Обработка запроса производится путем построения цепочки обработчиков-сервисов, реализующих стандартный интерфейс
///      WS.ServiceInterface.</p>
///   <p>Обмен информацией между сервисами производится с помощью объектов окружения класса WS.Environment. Обработчик может записать
///      какую-либо информацию в окружение, и передать его дальше по цепочке.</p>
///   <p>Кроме того, можно порождать дочерние объекты окружения, переопределяющие значения отдельных параметров родительской среды.</p>
///   <p>Использование общего окружения позволяет реализовывать так называемые middleware-сервисы. Middleware-сервис реализует различную
///      вспомогательную функциональность для основого сервиса, генерирующего объект отклика. Например, такой сервис может загружать
///      информацию о конфигурации, подключаться к базе данных, так или иначе модифицировать запрос и отклик и т.д.</p>
///   <p>Цепочка сервисов, совместно обрабатывающих запрос, формирует приложение.</p>
/// </details>
Core::load('Net.HTTP', 'WS.Adapters');

/// <class name="WS" stereotype="module">
///   <brief>Класс модуля</brief>
///   <depends supplier="WS.Environment" stereotype="creates" />
///   <depends supplier="WS.Runner" stereotype="creates" />
///   <depends supplier="WS.AdapterInterface" stereotype="uses" />
///   <depends supplier="WS.ServiceInterface" stereotype="uses" />
class WS implements Core_ModuleInterface {

///   <constants>
  const VERSION = '0.2.1';
///   </constants>

  static protected $env;

///   <protocol name="building">

///   <method name="Environment" scope="class" returns="WS.Environment">
///     <brief>Создает объект класса WS.Environment</brief>
///     <args>
///       <arg name="parent" type="WS.Environment" default="null" brief="родительское окружение" />
///     </args>
///     <body>
  static public function Environment($parent = null) { return new WS_Environment($parent); }
///     </body>
///   </method>

///   <method name="Runner" scope="class" returns="WS.Runner">
///     <brief>Создает объект класса WS.Runner</brief>
///     <args>
///       <arg name="adapter" type="WS.AdapterInterface" default="null" brief="объект-адаптер, выполняющий формирование объекта запроса" />
///     </args>
///     <body>
  static public function Runner(WS_AdapterInterface $adapter = null) { return new WS_Runner($adapter); }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="performing">

///   <method name="run" scope="class" returns="WS.Runner">
///     <brief>Обрабатывает текущий запрос с помощью цепочки обработчиков приложения с помощью объекта запуска класса WS.Runner</brief>
///     <args>
///       <arg name="app" type="WS.ServiceInterface" brief="объект приложения" />
///     </args>
///     <body>
  static public function run(WS_ServiceInterface $app) { return self::Runner()->run($app); }
///     </body>
///   </method>

  static public function env() {
    if (is_object(self::$env)) return self::$env;
    self::$env = self::Environment();
    self::$env->ts = time();
    if (Core::is_cli())
      self::$env->request = Net_HTTP::Request();
    else {
      Core::load('WS.Adapters.Apache');
      self::$env->request = WS_Adapters_Apache::adapter()->make_request();
    }
    return self::$env;
  }

///   </protocol>
}
/// </class>


/// <class name="WS.Exception" extends="Core.Exception" stereotype="exception">
///   <brief>Базовый класс исключений модуля</brief>
class WS_Exception extends Core_Exception {}
/// </class>


/// <class name="WS.Environment">
///   <brief>Окружение обработчиков запросов</brief>
///   <details>
///     <p>Представляет собой простейшее хранилище пар ключ-значение.</p>
///     <p>Для окружения может быть определено родительское окружение. В этом случае, отсутствующие в данном окружении элементы
///        будут запрашиваться из родительского окружения. Таким образом, каждый сервис может передать в следующий элемент цепочки
///        модифицированное окружение, не влияя на функционирование предыдущих элементов.</p>
///     <p>Значения элементов окружения доступны через обычные и индексные свойства. Значения элементов можно также задавать
////       путем вызова одноименных динамических методов. Так, приведенные ниже вызовы эквивалентны:</p>
///     <pre>
///       $env->test = 'hello';
///       $env['test'] = 'hello';
///       $env->test('hello');
///     </pre>
///   </details>
///   <implements interface="Core.PropertyAccessInterface" />
///   <implements interface="Core.IndexedAccessInterface" />
///   <implements interface="Core.CallInterface" />
///   <implements interface="Core.EqualityInterface" />
class WS_Environment
  implements Core_PropertyAccessInterface,
             Core_IndexedAccessInterface,
             Core_CallInterface,
             Core_EqualityInterface {

  protected $parent;
  protected $attrs = array();

///   <protocol name="creating">

///   <method name="__construct">
///     <brief>Конструктор</brief>
///     <args>
///       <arg name="parent" type="WS.Environment" default="null" brief="родительское окружение" />
///     </args>
///     <body>
  public function __construct($parent = null) { $this->parent = $parent; }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="processing">

///   <method name="assign" returns="WS.Environment">
///     <brief>Устанавливает значения элементов окружения</brief>
///     <args>
///       <arg name="values" brief="значения элементов окружения" />
///     </args>
///     <body>
  public function assign($values) {
    foreach ($values as $k => $v) $this->offsetSet($k, $v);
    return $this;
  }
///     </body>
///   </method>

  public function assign_if($values) {
    foreach ($values as $k => $v) if (!$this->offsetExists($k)) $this->offsetSet($k, $v);
    return $this;
  }

///   </protocol>

///   <protocol name="accessing" interface="Core.PropertyAccessInterface">

///   <method name="__get" returns="mixed">
///     <brief>Возвращает значение элемента окружения</brief>
///     <args>
///       <arg name="property" type="string" brief="имя элемента" />
///     </args>
///     <body>
  public function __get($property) { return $this->offsetGet($property); }
///     </body>
///   </method>

///   <method name="__set" returns="mixed">
///     <brief>Устанавливает значение элемента окружения</brief>
///     <args>
///       <arg name="property" type="string" brief="имя элемента" />
///       <arg name="value" brief="значение элемента" />
///     </args>
///     <body>
  public function __set($property, $value) { return $this->offsetSet($property, $value); }
///     </body>
///   </method>

///   <method name="__isset" returns="boolean">
///     <brief>Проверяет существование элемента окружения</brief>
///     <args>
///       <arg name="property" type="string" brief="имя элемента" />
///     </args>
///     <body>
  public function __isset($property) { return $this->offsetExists($property); }
///     </body>
///   </method>

///   <method name="__unset">
///     <brief>Удаляет элемент окружения</brief>
///     <args>
///       <arg name="property" type="string" brief="имя элемента" />
///     </args>
///     <body>
  public function __unset($property) { return $this->offsetUnset($property); }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="calling" interface="Core.CallInterface">

///   <method name="__call" returns="WS.Environment">
///     <brief>Диспетчер вызовов</brief>
///     <details>
///       <p>Позволяет задавать значения элементов окружения с помощью вызова метода.</p>
///     </details>
///     <args>
///       <arg name="method" type="string" brief="имя метода" />
///       <arg name="args" type="array" brief="значения аргументов вызова метода" />
///     </args>
///     <body>
  public function __call($method, $args) {
    $this->offsetSet($method, $args[0]);
    return $this;
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="indexing" interface="Core.IndexedAccessInterface">

///   <method name="offsetGet" returns="mixed">
///     <brief>Возвращает значение элемента окружения</brief>
///     <args>
///       <arg name="index" type="string" brief="имя элемента" />
///     </args>
///     <body>
  public function offsetGet($index) {
    $index = (string) $index;
    if (isset($this->attrs[$index]))
      return $this->attrs[$index];
    else
      if ($this->parent) return $this->parent->offsetGet($index);
      else return null;
  }
///     </body>
///   </method>

///   <method name="offsetSet" returns="mixed">
///     <brief>Устанавливает значение элемента окружения</brief>
///     <args>
///       <arg name="index" type="string" brief="имя элемента" />
///       <arg name="value" />
///     </args>
///     <body>
  public function offsetSet($index, $value) {  $this->attrs[(string) $index] = $value; return $this; }
///     </body>
///   </method>

///   <method name="offsetExists" returns="boolean">
///     <brief>Проверяет существование элемента окружения</brief>
///     <args>
///       <arg name="index" type="string" brief="имя элемента" />
///     </args>
///     <body>
  public function offsetExists($index) {
    return isset($this->attrs[(string) $index]) ? true :
      ($this->parent ? $this->parent->offsetExists($index) : false);
  }
///     </body>
///   </method>

///   <method name="offsetUnset">
///     <brief>Удаляет элемент окружения</brief>
///     <args>
///       <arg name="index" type="string" brief="имя элемента" />
///     </args>
///     <body>
  public function offsetUnset($index) {
    $index = (string) $index;
    if (isset($this->attrs[$index]))
      unset($this->attrs[$index]);
    else
      if ($this->parent) $this->parent->offsetUnset($index);
      else throw new Core_MissingPropertyException($index);
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="supporting">

///   <method name="spawn">
///     <body>
  public function spawn() {
    return new self($this);
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
    if (!($to instanceof self)) return false;
    if ($this->parent && !(Core::equals($this->parent, $to->parent)))
      return false;
    foreach ($this->attrs as $k => $v) {
      if (!Core::equals($v, $to[$k])) return false;
    }
    return true;
  }
///     </body>
///   </method>
///</protocol>
}
/// </class>


/// <interface name="WS.ServiceInterface">
///   <brief>Стандартный интерфейс сервиса-обработчика запросов</brief>
///   <depends supplier="WS.Environment" stereotype="uses" />
interface WS_ServiceInterface {

///   <protocol name="processing">

///   <method name="run" returns="mixed">
///     <brief>Выполняет обработку запроса</brief>
///     <args>
///       <arg name="env" type="WS.Environment" brief="объект окружения" />
///     </args>
///     <body>
  public function run(WS_Environment $env);
///     </body>
///   </method>

///   </protocol>
}
/// </interface>


/// <class name="WS.MiddlewareService" stereotype="abstract">
///   <brief>Базовый класс middleware-сервиса</brief>
///   <details>
///     <p>Содержит ссылку на сервис приложения, которому должно быть передано управление после выполения
///        операций сервиса.</p>
///     <p>Метод run реализации сервиса при это выглядит следующим образом:</p>
///     <pre>
///       public function run(WS_Environment $env) {
///         /* действия до передачи управления дальше по цепочке, модификация $env */
///         $response = $this->application->run($env);
///         /* действия после передачи управления дальше по цепочке, модификация $response */
///         return $response;
///       }
///     </pre>
///   </details>
///   <implements interface="WS.ServiceInterface" />
abstract class WS_MiddlewareService implements WS_ServiceInterface {

  protected $application;

///   <protocol name="creating">

///   <method name="__construct">
///     <brief>Конструктор</brief>
///     <args>
///       <arg name="application" type="WS.ServiceInterface" brief="следующий сервис цепочки, которому должно быть передано управление" />
///     </args>
///     <body>
  public function __construct($application = null) {
    $this->application = $application;
  }
///     </body>
///   </method>

  public function set_application(WS_ServiceInterface $application) {
    $this->application = $application;
    return $this;
  }
  
  public function get_application() {
    return $this->application;
  }

///   </protocol>
}
/// </class>


/// <interface name="WS.AdapterInterface">
///   <brief>Адаптер, выполняющий построение объекта запроса</brief>
///   <details>
///     <p>Адаптер формирует объект запроса класса Net.HTTP.Request на основе данных, хранящихся в стандартных
///        переменных PHP ($_SERVER, $_GET, $_POST и т.д.).</p>
///   </details>
///   <depends supplier="Net.HTTP.Request" stereotype="creates" />
///   <depends supplier="Net.HTTP.Response" stereotype="uses" />
interface WS_AdapterInterface {

///   <protocol name="performing">

///   <method name="make_request" returns="Net.HTTP.Request">
///     <body>
  public function make_request();
///     </body>
///   </method>

///   <method name="process_response">
///     <args>
///       <arg name="response" type="Net.HTTP.Response" />
///     </args>
///     <body>
  public function process_response(Net_HTTP_Response $response);
///     </body>
///   </method>

///   </protocol>
}
/// </interface>


/// <class name="WS.Runner">
///   <brief>Выполняет обработку текущего запроса с помощью заданного приложения</brief>
///   <details>
///     <p>Обработка выполняется следующим образом:</p>
///     <ol>
///       <li>если объект-адаптер не был передан, создается экземпляр класса WS.Adapters.Apache;</li>
///       <li>адаптер формирует объект запроса класса Net.HTTP.Request;</li>
///       <li>создается объект окружения класса WS.Environment, сформированный объект запроса записывается
///           в него под именем $request;</li>
///       <li>вызов метода run() инициирует обработку запроса переданной в качестве параметра цепочкой обработчиков;</li>
///       <li>цепочка обработчиков формирует объект отклика класса Net.HTTP.Response;</li>
///       <li>объект отклика обрабатывается адаптером, выполняя все необходимые действия по выдаче отклика (заголовки, вывод
///           файла тела отклика и т.д.).</li>
///     </ol>
///   </details>
///   <depends supplier="WS.ServiceInterface" stereotype="uses" />
///   <depends supplier="WS.Environment" stereotype="creates" />
class WS_Runner {

  protected $adapter;

///   <protocol name="creating">

///   <method name="__construct">
///     <brief>Конструктор</brief>
///     <args>
///       <arg name="adapter" type="WS.AdapterInterface" default="null" brief="адаптер" />
///     </args>
///     <body>
  public function __construct(WS_AdapterInterface $adapter = null) {
    $this->adapter = Core::if_null($adapter, WS_Adapters::Apache());
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="performing">

///   <method name="run" returns="WS.Runner">
///     <brief>Запускает приложение</brief>
///     <args>
///       <arg name="app" type="WS.ServiceInterface" brief="объект приложения" />
///     </args>
///     <body>
  public function run(WS_ServiceInterface $app) {
    $environment = WS::env();
    $environment->request = $this->adapter->make_request();

    $rc = Events::call('ws.run');
    if (!is_null($rc)) return $rc;
    

    $environment->response = Net_HTTP::Response();
    $body = $app->run($environment);
    
    $response = Net_HTTP::merge_response($body, $environment->response);
    
    return $this->adapter->process_response($response);
  }
///     </body>
///   </method>

///   </protocol>
}
/// </class>


/// <aggregation>
///   <source class="WS.Runner" role="runner" multiplicity="1" />
///   <target class="WS.AdapterInterface"   role="adapter" multiplicity="1" />
/// </aggregation>

/// </module>
