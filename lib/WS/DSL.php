<?php
/// <module name="WS.DSL" version="0.3.1" maintainer="timokhin@techart.ru">
///   <brief>Простейший builder для построения приложения из набора middleware-компонентов и обработчиков запросов</brief>
///   <details>
///     <p>Модуль реализует класс WS.DSL.Builder, позволящий строить приложение из набора стандартных
///        компонент последовательными вызовами соответствующих методов.</p>
///     <p></p>
///   </details>

/// <class name="WS.DSL" stereotype="module">
///   <brief>Модуль WS.DSL</brief>
///   <details>
///     <p>Построение приложения выполняется с помощью объектов класса WS.DSL.Builder. Класс реализует
///        Core.CallInterface, и делает это следующим образом.</p>
///     <p>Существует набор стандартных вызовов, каждый их которых приводит к созданию соответствующиего слоя
///        middleware или терминального обработчика.</p>
///     <p>Набор вызовов для создания middleware:</p>
///     <dl>
///       <dt>environment</dt><dd>WS.Middleware.Environment.Service — установка элементов окружения;</dd>
///       <dt>config</dt><dd>WS.Middleware.Config.Service — конфигурирование приложения;</dd>
///       <dt>db</dt><dd>WS.Middleware.DB.Service — подключение к БД;</dd>
///       <dt>orm</dt><dd>WS.Middleware.ORM.Service — подключение к БД с использованием DB.ORM;</dd>
///       <dt>cache</dt><dd>WS.Middleware.Cache.Service — кеширование;</dd>
///       <dt>status</dt><dd>WS.Middleware.Status.Service — обработка ошибок и шаблоны HTTP-статуса;</dd>
///       <dt>template</dt><dd>WS.Middleware.Template.Service — принудительное преобразование шаблона в строку;</dd>
///       <dt>session</dt><dd>WS.Session.Service — поддержка сессий;</dd>
///       <dt>auth_session</dt><dd>WS.Auth.Session.Service — авторизации с помощью сессий;</dd>
///       <dt>auth_basic</dt><dd>WS.Auth.Basic.Service — HTTP Basic авторизация;</dd>
///       <dt>auth_openid</dt><dd>WS.Auth.OpenID.Service — авторизация с помощью OpenID.</dd>
///     </dl>
///     <p>Набор вызовов для создания терминальных обработчиков:</p>
///     <dl>
///       <dt>application_dispatcher</dt><dd>WS.Rest.Dispatcher — диспетчер REST-приложений.</dd>
///     </dl>
///     <p>Вызов метода, соответствующего middleware-компоненту, сохраняет информацию о его параметрах и
///        возвращает ссылку на builder. Таким образом, вызывая эти методы последовательно, можно построить необходимую
///        цепочку обработчиков. Параметры метода должны соответствовать набору параметров конструктора сервиса, без
///        первого аргумента, который всегда следующий сервис в цепочке.</p>
///     <p>Вызов метода, соответствующего терминальному обработчику, выполняет построение всей цепочки middleware-компонентов,
///        определенной ранее, завершает ее соответствующим терминальным обработчиком и возвращает получившуюся цепочку. Параметры
///        метода соответствуют параметрам обработчика.</p>
///     <p>Если необходимо указать свой собственный терминальный обработчик, это можно сделать с помощью вызова handler().</p>
///     <p>Таким образом, следующий код создаст приложение, читающее конфигурацию из файла, поключащееся к базе данных и
///        используюшее пользовательский обработчик для всего остального.</p>
///     <code>
///       $application = WS_DSL::application()->
///         config('../etc/config.php')->
///         cache('dummy://')->
///         application(new App_WS_ApplicationService());
///     </code>
///     <p>Набор поддерживаемых методов можно расширять. Для этого необходимо воспользоваться механизмом
///        конфигурирования модуля. Компоненты middleware регистрируются с помощью опции middleware,
///        терминальные обработчики — с помощью опции handlers.</p>
///     <code>
///     Core::configure('WS.DSL', array(
///       'middleware' => array(
///         'app_middleware' => 'App.WS.Middleware.CustomService'),
///       'handlers'   => array(
///         'custom_app' => 'App.WS.ApplicationService')));
///     </code>
///     <p>После этого можно использовать эти вызовы при построении приложения, например:</p>
///     <code>
///       $application = WS_DSL::application()->
///         config('../etc/config.php')->
///         app_middleware($parms)->
///         custom_app();
///     </code>
///   </details>
///   <implements interface="Core.ModuleInterface" />
///   <depends supplier="WS.DSL.Builder" stereotype="creates" />
class WS_DSL implements Core_ModuleInterface {

///   <constants>
  const VERSION = '0.3.1';

  const PREFIX  = 'WS.Middleware';
  const SUFFIX  = 'Service';
///   </constants>

  static public $middleware = array(
    'dummy_middleware'=> '.Dummy.',
    'environment'     => '.Environment.',
    'firephp'         => '.FirePHP.',
    'oauth'           => 'Service.OAuth.Middleware.',
    'openid'          => '.OpenId.',
    'config'          => '.Config.',
    'db'              => '.DB.',
    'orm'             => '.ORM.',
    'cache'           => '.Cache.',
    'status'          => '.Status.',
    'template'        => '.Template.',
    'session'         => '.Session.',
    'pdf'         => '.PDF.',
    'auth_session'    => 'WS.Auth.Session.',
    'auth_basic'      => 'WS.Auth.Basic.',
    'auth_opensocial' => 'WS.Auth.OpenSocial.',
    );

  static public $handlers = array(
    'dummy_service' => 'WS.Services.Dummy.Service',
    'application_dispatcher' => 'WS.Services.REST.Dispatcher');

///   <protocol name="creating">

///   <method name="initialize" scope="class">
///     <brief>Выполняет инициализацию модуля</brief>
///     <args>
///       <arg name="options" type="array" default="array()" />
///     </args>
///     <body>
  static public function initialize(array $options = array()) {
    if (isset($options['middleware']) && is_array($options['middleware']))
      self::$middleware = array_merge(self::$middleware, $options['middleware']);

    if (isset($options['handlers']) && is_array($options['handlers']))
      self::$handlers= array_merge(self::$handlers, $options['handlers']);
  }
///     </body>
///   </method>

///   <method name="add_middleware" scope="class">
///     <args>
///       <arg name="name" type="string" />
///       <arg name="class" type="string" />
///     </args>
///     <body>
  static function add_middleware($name, $class) { self::$middleware[$name] = $class; }
///     </body>
///   </method>
 
///   <method name="add_handler" scope="class">
///     <args>
///       <arg name="name" type="string" />
///       <arg name="class" type="string" />
///     </args>
///     <body>
  static function add_handler($name, $class) { self::$handlers[$name] = $class; }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="building">

///   <method name="application" returns="WS.DSL.Builder" scope="class">
///     <brief>Создает объект класса WS.DSL.Builder</brief>
///     <body>
  static public function Builder() { return new WS_DSL_Builder(); }
///     </body>
///   </method>

///   <method name="application" returns="WS.DSL.Builder" scope="class">
///     <brief>Псевдоним для WS.DSL::Builder()</brief>
///     <body>
  static public function application() { return new WS_DSL_Builder(); }
///     </body>
///   </method>

///   </protocol>
}
/// </class>


/// <class name="WS.DSL.Builder">
///   <implements interface="Core.CallInterface" />
///   <brief>Диспетчер динамических вызовов</brief>
class WS_DSL_Builder implements Core_CallInterface {

  protected $middleware = array();

///   <protocol name="calling" type="Core.CallInterface">

///   <method name="__call" returns="mixed">
///     <brief>Создает терминальный обратчик или сохраняет информацию о middleware в очереди</brief>
///     <args>
///       <arg name="method" type="string" brief="имя метода" />
///       <arg name="parms"  type="array"  brief="набор параметров" />
///     </args>
///     <body>
  public function __call($method, $parms) {
    return $this->add_middleware($method, $parms) ?  $this : $this->make_handler($method, $parms);
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="building">

///   <method name="handler" returns="WS.ServiceInterface">
///     <args>
///       <arg name="app" type="WS.ServiceInterface" />
///     </args>
///     <body>
  public function handler(WS_ServiceInterface $app) { return $this->build_middleware($app); }
///     </body>
///   </method>

///   </protocol>


///   <protocol name="supporting">

  public function get_middleware_parms($name) {
    return $this->middleware[$name]['parms'];
  }
  
  public function set_middleware_parms($name, array $parms) {
    $this->middleware[$name]['parms'] = $parms;
    return $this;
  }
  
  public function update_middleware_parms($name, array $parms) {
    $this->middleware[$name]['parms']  = array_merge($this->middleware[$name]['parms'], $parms);
    return $this;
  }

///   <method name="add_middleware" returns="boolean" access="protected">
///     <brief>Сохраняет информацию о параметрах вызова middleware в очереди</brief>
///     <args>
///       <arg name="method" type="string" brief="имя метода"   />
///       <arg name="parms"  type="array"  brief="параметры вызова" />
///     </args>
///     <body>
//TODO: weight
  protected function add_middleware($method, $parms) {
    if (isset(WS_DSL::$middleware[$method])) {
      $this->middleware[$method] = new ArrayObject(array('class' => WS_DSL::$middleware[$method], 'parms' => $parms));
      return true;
    } else
      return false;
  }
///     </body>
///   </method>

///   <method name="make_handler" access="protected">
///     <brief>Создает терминальный обработчик</brief>
///     <details>
///       <p>Если в очереди вызово присутствуют middleware-компоненты — создает экземпляры в порядке,
///          обратном порядку определения.</p>
///     </details>
///     <args>
///       <arg name="method" type="string" brief="имя метода" />
///       <arg name="parms"  type="array"  brief="параметры вызова" />
///     </args>
///     <body>
  protected function make_handler($method, $parms) {
    if (isset(WS_DSL::$handlers[$method])) {
      $this->load_module_for($c = $this->complete_name(WS_DSL::$handlers[$method]));
      return $this->build_middleware(Core::amake($c, $parms));
    }
  }
///     </body>
///   </method>

///   <method name="build_middleware" returns="WS.ServiceInterface" access="protected">
///     <brief>Строит цепочку middleware-компонент</brief>
///     <args>
///       <arg name="app" type="WS.ServiceInterface" brief="терминальный обработчик" />
///     </args>
///     <body>
  protected function build_middleware(WS_ServiceInterface $app) {
    foreach (array_reverse($this->middleware) as $name => $conf) {
      if ($conf['class'] instanceof WS_ServiceInterface) {
        $conf['class']->set_application($app);
        $app = $conf['class'];
      } else {
        $this->load_module_for($c = $this->complete_name((string) $conf['class']));
        $app = Core::amake($c, array_merge(array($app), $conf['parms']));
      }
    }
    $this->middleware = array();
    return $app;
  }
///     </body>
///   </method>

///   <method name="load_module_for" access="protected">
///     <brief>Подгружает модуль для указанного имени класса</brief>
///     <args>
///       <arg name="class" type="string" />
///     </args>
///     <body>
  protected function load_module_for($class) {
    Core::load(substr($class, 0, strrpos(str_replace('..', '.', $class), '.')));
  }
///     </body>
///   </method>

///   <method name="complete_name" returns="string" access="protected">
///     <brief>Выполняет развертывание имени компонента</brief>
///     <args>
///       <arg name="name" type="string" brief="сокращенное имя класса" />
///     </args>
///     <details>
///       <p>Если имя класса компонента начинается на «.», к нему добавляется префикс «WS.Middleware».</p>
///       <p>Если имя класса заканчивается на «.», к нему добавляется суффикс «Service».</p>
///     </details>
///     <body>
  protected function complete_name($name) {
    if (Core_Strings::ends_with($name, '.'))   $name = $name.WS_DSL::SUFFIX;
    if (Core_Strings::starts_with($name, '.')) $name = WS_DSL::PREFIX.$name;
    return $name;
  }
///     </body>
///   </method>

///   </protocol>
}
/// </class>

/// </module>
