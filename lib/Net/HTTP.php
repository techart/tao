<?php
/// <module name="Net.HTTP" version="0.2.4" maintainer="timokhin@techart.ru">
///   <brief>Объектное представления запроса и отклика HTTP-протокола</brief>
Core::load('IO.FS', 'MIME', 'Time');

/// <interface name="Net.HTTP.SessionInterface">
interface Net_HTTP_SessionInterface {}
/// </interface>

/// <class name="Net.HTTP" stereotype="module">
///   <brief>Класс модуля</brief>
///   <implements interface="Core.ModuleInterface" />
///   <depends supplier="Net.HTTP.Request" stereotype="creates" />
///   <depends supplier="Net.HTTP.Response" stereotype="creates" />
///   <depends supplier="Net.HTTP.Upload" stereotype="creates" />
class Net_HTTP implements Core_ModuleInterface {

///   <constants>
  const VERSION = '0.2.4';
  
  const DEFAULT_CACHE_LIFETIME = 86400000; // 3600*24*1000

  const GET    = 1;
  const PUT    = 2;
  const POST   = 4;
  const DELETE = 8;
  const HEAD   = 16;
  const ANY    = 31;

  const OK                              = 200;
  const CREATED                         = 201;
  const ACCEPTED                        = 202;
  const NON_AUTHORITATIVE               = 203;
  const NO_CONTENT                      = 204;
  const RESET_CONTENT                   = 205;
  const PARTIAL_CONTENT                 = 206;
  const MULTI_STATUS                    = 207;
  const MULTIPLE_CHOICES                = 300;
  const MOVED_PERMANENTLY               = 301;
  const FOUND                           = 302;
  const SEE_OTHER                       = 303;
  const NOT_MODIFIED                    = 304;
  const USE_PROXY                       = 305;
  const SWITCH_PROXY                    = 306;
  const TEMPORARY_REDIRECT              = 307;
  const BAD_REQUEST                     = 400;
  const UNAUTHORIZED                    = 401;
  const PAYMENT_REQUIRED                = 402;
  const FORBIDDEN                       = 403;
  const NOT_FOUND                       = 404;
  const METHOD_NOT_ALLOWED              = 405;
  const NOT_ACCEPTABLE                  = 406;
  const PROXY_AUTHENTICATION_REQUIRED   = 407;
  const REQUEST_TIMEOUT                 = 408;
  const CONFLICT                        = 409;
  const GONE                            = 410;
  const LENGTH_REQUIRED                 = 411;
  const PRECONDITION_FAILED             = 412;
  const REQUEST_ENTITY_TOO_LARGE        = 413;
  const REQUEST_URI_TOO_LONG            = 414;
  const UNSUPPORTED_MEDIA_TYPE          = 415;
  const REQUESTED_RANGE_NOT_SATISFIABLE = 416;
  const EXPECTATION_FAILED              = 417;
  const INTERNAL_SERVER_ERROR           = 500;
  const NOT_IMPLEMENTED                 = 501;
  const BAD_GATEWAY                     = 502;
  const SERVICE_UNAVAILABLE             = 503;
  const GATEWAY_TIMEOUT                 = 504;
  const HTTP_VERSION_NOT_SUPPORTED      = 505;
///   </constants>

  static protected $method_names = array(
    Net_HTTP::GET => 'get', Net_HTTP::PUT => 'put', Net_HTTP::POST => 'post', Net_HTTP::DELETE => 'delete', Net_HTTP::HEAD => 'head' );

///   <protocol name="building">

///   <method name="Request" returns="Net.HTTP.Request" scope="class">
///     <brief>Создает объект HTTP-запроса</brief>
///     <args>
///       <arg name="uri" type="string" brief="URI запроса" />
///       <arg name="meta" type="array" default="array()" brief="дополнительные параметры запроса" />
///     </args>
///     <body>
  static public function Request($uri = '', array $meta = array()) { return new Net_HTTP_Request($uri, $meta); }
///     </body>
///   </method>

///   <method name="Response" returns="Net.HTTP.Response" scope="class">
///     <brief>Создает объект HTTP-отклика</brief>
///     <details>
///       <p>Набор параметров, описывающих отклик, может быть различным в зависимости от ситуации. Несколько примеров:</p>
///       <dl>
///         <dt>Net_HTTP::Response(Net_HTTP::BAD_REQUEST)</dt><dd>отклик со статусом Net_HTTP::BAD_REQUEST</dd>
///         <dt>Net_HTTP::Response(null)</dt><dd>отклик со статусом Net_HTTP::NO_CONTENT</dd>
///         <dt>Net_HTTP::Response(Net_HTTP::OK, 'Body')</dt><dd>отклик со статусом Net_HTTP::OK и с телом, содержащим строку 'Body' </dd>
///       </dl>
///     </details>
///     <body>
  static public function Response() {
    $args = func_get_args();
    $response = new Net_HTTP_Response();
    switch (count($args)) {
      case 0:
        return $response;
      case 1:
        if ($args[0] instanceof Net_HTTP_Response)
          return $args[0];
        if (is_int($args[0]) || $args[0] instanceof Net_HTTP_Status)
          return $response->status($args[0]);
        elseif (is_string($args[0]) ||
          $args[0] instanceof Iterator ||
          $args[0] instanceof IteratorAggregate)
          return $response->body($args[0]);
        elseif (is_null($args[0]))
          return $response->status(Net_HTTP::NO_CONTENT);
        else
          return $response->body($args[0]);
      case 2:
        return $response->
          status($args[0])->
          body($args[1]);
      default:
        return $response->
          status($args[0])->
          body($args[1])->
          headers($args[2]);
    }
  }
///     </body>
///   </method>

  static public function merge_response($res1, $res2) {
    $res1 = Net_HTTP::Response($res1);
    $res2 = Net_HTTP::Response($res2);
    $res = new Net_HTTP_Response();
    $res->status($res2->status->code != Net_HTTP::OK ? $res2->status : $res1->status);
    $res->headers(array_merge($res1->headers->as_array(), $res2->headers->as_array()));
    if (empty($res2->body)) $res->body($res1->body);
    if (empty($res1->body)) $res->body($res2->body);
    /*if ((is_string($res1->body) || $res1->body instanceof Core_StringifyInterface) &&
       (is_string($res1->body) || $res1->body instanceof Core_StringifyInterface))
       $res->body = $res1->body . $res2->body;*/
    return $res;
  }

///   <method name="Upload" returns="Net.HTTP.Upload">
///     <brief>Создает объектное представление http upload</brief>
///     <args>
///       <arg name="tmp_path"      type="string" brief="путь к временному файлу, соответствующему закачке" />
///       <arg name="original_name" type="string" brief="оригинальное имя файла" />
///     </args>
///     <body>
  static public function Upload($tmp_path, $original_name, $file_array = array()) { return new Net_HTTP_Upload($tmp_path, $original_name, $file_array); }
///     </body>
///   </method>

///   <method name="redirect_to" returns="Net.HTTP.Response" scope="class">
///     <brief>Строит отлик, представляющий из себя перенаправление на указанный адрес</brief>
///     <details>
///       <p>HTTP-статус редиректа может быть изменен с помощью параметра status, по умолчанию испольщуется HTTP 302: FOUND.</p>
///     </details>
///     <args>
///       <arg name="url" type="string" brief="адрес перенаправления" />
///       <arg name="status" brief="статус перенаправления" default="Net_HTTP::FOUND" />
///     </args>
///     <body>
  static public function redirect_to($url, $status = self::FOUND) {
    return Net_HTTP::Response()->
      status($status)->
      location($url);
  }
///     </body>
///   </method>


///   <method name="moved_permanently_to" returns="Net.HTTP.Response" scope="class">
///     <args>
///       <arg name="url" type="string" brief="адрес перенаправления" />
///       <arg name="status" brief="статус перенаправления" default="Net_HTTP::FOUND" />
///     </args>
///     <body>
  static public function moved_permanently_to($url, $status = self::MOVED_PERMANENTLY) {
    return self::redirect_to($url, $status);
  }
///     </body>
///   </method>

///   <method name="not_found" returns="Net.HTTP.Response" scope="class">
///     <brief>Строит отклик со статусом HTTP 404: NOT FOUND</brief>
///     <body>
  static public function not_found() { return Net_HTTP::Response(Net_HTTP::NOT_FOUND); }
///     </body>
///   </method>

///   <method name="forbidden" returns="Net.HTTP.Response" scope="class">
///     <brief>Строит отклик со статусом HTTP 404: FORBIDDEN</brief>
///     <body>
  static public function forbidden() { return Net_HTTP::Response(Net_HTTP::FORBIDDEN); }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="quering">

///   <method name="method_name_for" returns="string">
///     <brief>Возвращает имя HTTP-метода в виде строки по значению числовой константы</brief>
///     <args>
///       <arg name="method" type="int" brief="константа модуля Net.HTTP, определяющая метод" />
///     </args>
///     <body>
  static public function method_name_for($method) { return Core::if_not_set(self::$method_names, $method, ''); }
///     </body>
///   </method>

///   <method name="Agent" returns="Net.Agents.HTTP.Agent" scope="class">
///     <args>
///       <arg name="options" type="array" default="array()" />
///     </args>
///     <body>
  static public function Agent(array $options = array()) {
    Core::load('Net.Agents.HTTP');
    return new Net_Agents_HTTP_Agent($options);
  }
///     </body>
///   </method>

///   <method name="Status" returns="Net.HTTP.Status" scope="class">
///     <args>
///       <arg name="code" type="int" />
///       <arg name="message" type="string" default="null" />
///     </args>
///     <body>
  static public function Status($code, $message = null) {
    return new Net_HTTP_Status($code, $message);
  }
///     </body>
///   </method>

  static public function Download($file, $cache = false, $cache_lifetime = Net_HTTP::DEFAULT_CACHE_LIFETIME) {
    return new Net_HTTP_Download($file, $cache, $cache_lifetime);
  }

///   </protocol>
}
/// </class>


/// <class name="Net.HTTP.Status">
///   <implements interface="Core.PropertyAccessInterface" />
///   <implements interface="Core.StringifyInterface" />
///   <implements interface="Core.EqualityInterface" />
class Net_HTTP_Status implements
  Core_PropertyAccessInterface,
  Core_EqualityInterface,
  Core_StringifyInterface {

static protected $messages = array(
    200 => 'OK',
    201 => 'CREATED',
    202 => 'ACCEPTED',
    203 => 'NON AUTHORITATIVE',
    204 => 'NO CONTENT',
    205 => 'RESET CONTENT',
    206 => 'PARTIAL CONTENT',
    207 => 'MULTI STATUS',
    300 => 'MULTIPLE CHOICES',
    301 => 'MOVED PERMANENTLY',
    302 => 'FOUND',
    303 => 'SEE OTHER',
    304 => 'NOT MODIFIED',
    305 => 'USE PROXY',
    306 => 'SWITCH PROXY',
    307 => 'TEMPORARY REDIRECT',
    400 => 'BAD REQUEST',
    401 => 'UNAUTHORIZED',
    402 => 'PAYMENT REQUIRED',
    403 => 'FORBIDDEN',
    404 => 'NOT FOUND',
    405 => 'METHOD NOT ALLOWED',
    406 => 'NOT ACCEPTABLE',
    407 => 'PROXY AUTHENTICATION_REQUIRED',
    408 => 'REQUEST TIMEOUT',
    409 => 'CONFLICT',
    410 => 'GONE',
    411 => 'LENGTH REQUIRED',
    412 => 'PRECONDITION FAILED',
    413 => 'REQUEST ENTITY TOO LARGE',
    414 => 'REQUEST URI TOO LONG',
    415 => 'UNSUPPORTED MEDIA TYPE',
    416 => 'REQUESTED RANGE NOT SATISFABLE',
    417 => 'EXPECTATION FAILED',
    500 => 'INTERNAL SERVER ERROR',
    501 => 'NOT IMPLEMENTED',
    502 => 'BAD GATEWAY',
    503 => 'SERVICE UNAVAILABLE',
    504 => 'GATEWAY TIMEOUT',
    505 => 'HTTP VERSION NOT SUPPORTED' );

  protected $code;
  protected $message;

///   <protocol name="creating">

///   <method name="__construct">
///     <args>
///       <arg name="code" type="int" />
///       <arg name="message" type="string" default="null" />
///     </args>
///     <body>
  public function __construct($code, $message = null) {
    $this->code = (int) $code;
    $this->message = $message ? (string) $message : null;
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="supporting">

///   <method name="get_message" returns="string" access="protected">
///     <brief>Возвращает строковое сообщение, соответствующее числовому значению HTTP-статуса</brief>
///     <body>
  protected function get_message($full = false) {
    if (!$this->message) {
      $code = isset(self::$messages[$this->code]) ? $this->code : 500;
      $this->message = self::$messages[$code];
    }
    return $full ? $this->as_string() : $this->message;
  }
///     </body>
///   </method>

///   <method name="as_response">
///     <body>
  public function as_response() {
    return Net_HTTP::Response($this);
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="stringifying">

///   <method name="as_string" returns="string">
///     <body>
  public function as_string() {
    return sprintf('%d %s', $this->code, self::$messages[$this->code]);
  }
///     </body>
///   </method>

///   <method name="__toString" returns="string">
///     <body>
  public function __toString() {
    return $this->as_string();
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="accessing">

///   <method name="__get" returns="mixed">
///     <args>
///       <arg name="property" type="string" />
///     </args>
///     <body>
  public function __get($property) {
    switch ($property) {
      case 'code' :           return $this->code;
      case 'full_message':    return $this->get_message(true);
      case 'message' :        return $this->get_message();
      case 'is_info':         return $this->code >= 100 && $this->code < 200;
      case 'is_success':      return $this->code >= 200 && $this->code < 300;
      case 'is_redirect':     return $this->code >= 300 && $this->code < 400;
      case 'is_error':        return $this->code >= 400 && $this->code < 600;
      case 'is_client_error': return $this->code >= 400 && $this->code < 500;
      case 'is_server_error': return $this->code >= 500 && $this->code < 600;
      case 'response' :       return $this->as_response();
      default:
        throw new Core_MissingPropertyException($property);
    }
  }
///     </body>
///   </method>

///   <method name="__set" returns="mixed">
///     <args>
///       <arg name="property" type="string" />
///       <arg name="value" />
///     </args>
///     <body>
  public function __set($property, $value) {
    switch ($property) {
      case 'message':
      case 'code':
      case 'full_message':
      case 'is_info':
      case 'is_success':
      case 'is_redirect':
      case 'is_error':
      case 'is_client_error':
      case 'is_server_error':
      case 'response' :
        throw new Core_ReadOnlyPropertyException($property);
      default:
        throw new Core_MissingPropertyException($property);
    }
  }
///     </body>
///   </method>

///   <method name="__isset" returns="boolean">
///     <args>
///       <arg name="property" type="string" />
///     </args>
///     <body>
  public function __isset($property) {
    switch ($property) {
      case 'message':
      case 'code':
      case 'full_message':
      case 'is_info':
      case 'is_success':
      case 'is_redirect':
      case 'is_error':
      case 'is_client_error':
      case 'is_server_error':
      case 'response':
        return true;
      default:
        return false;
    }
  }
///     </body>
///   </method>

///   <method name="__unset">
///     <args>
///       <arg name="property" type="string" />
///     </args>
///     <body>
  public function __unset($property) {
    switch ($property) {
      case 'message':
        return $this->message = null;
      case 'code':
      case 'full_message':
      case 'is_info':
      case 'is_success':
      case 'is_redirect':
      case 'is_error':
      case 'is_client_error':
      case 'is_server_error':
      case 'response':
        throw new Core_ReadOnlyPropertyException($property);
      default:
        throw new Core_MissingPropertyException($property);
    }
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
    return
      get_class($this) == get_class($to) &&
      $this->code == $to->code;
  }
///     </body>
///   </method>
///</protocol>

}
/// </class>



/// <interface name="Net.HTTP.AgentInterface">
///   <brief>Интерфейс агента для работы с HTTP</brief>
///   <details>
///     <p>Интерфейс агента -- минимальный интерфейс, предназначенный для отправки HTTP-запроса и приема HTTP-отклика.</p>
///     <p>Реализация интерфейса может быть выполнена различным образом, например, с использованием модуля Curl: Curl.HTTP</p>
///   </details>
interface Net_HTTP_AgentInterface {

///   <protocol name="performing">

///   <method name="send" returns="Net.HTTP.Response">
///     <brief>Отправляет запрос и возвращает отклик в виде объекта класса Net.HTTP.Response</brief>
///     <args>
///       <arg name="request" type="Net.HTTP.Request" brief="запрос" />
///     </args>
///     <body>
  public function send($request);
///     </body>
///   </method>

///   </protocol>
}
/// </interface>


/// <class name="Net.HTTP.Head">
///   <brief>Набор полей заголовка запроса и отклика</brief>
///   <details>
///     <p>Доступ к полям запроса осуществляется с помощью двух интерфейсов: доступа к свойствам и индексированного доступа.
///        При этом индексированный доступ соответствует строковому, а доступ к свойствам -- объектному представлению значений полей.</p>
///   </details>
///   <implements interface="Core.IndexedAccessInterface" />
///   <implements interface="Core.PropertyAccessInterface" />
///   <implements interface="Core.CallInterface" />
///   <implements interface="IteratorAggregate" />
///   <implements interface="Core.EqualityInterface" />
class Net_HTTP_Head
  implements Core_IndexedAccessInterface,
             Core_PropertyAccessInterface,
             Core_CallInterface,
             Core_CountInterface,
             Core_EqualityInterface,
             IteratorAggregate {

  protected $fields = array();

///   <protocol name="creating">

///   <method name="__construct">
///     <brief>Конструктор</brief>
///     <details>
///       <p>Первоначальный набор заголовков может быть представлен в виде произвольного итерируемого объекта.
///          Более подробная информация приведена в описании метода fields().</p>
///     </details>
///     <args>
///       <arg name="fields" default="array()" brief="набор полей заголовка" />
///     </args>
///     <body>
  public function __construct($fields = array()) { $this->fields($fields); }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="changing">

///   <method name="field" returns="Net.HTTP.Head">
///     <brief>Добавляет новое поле</brief>
///     <details>
///       <p>При добавлении поля его имя приводится к каноническому формату. В том случае, если передаваемое значение представляет
///          собой объект типа Time.DateTime, в качестве значение используется строковое представление даты в формате RFC1123.</p>
///     </details>
///     <args>
///       <arg name="name"  type="string" brief="имя поля" />
///       <arg name="value" type="string" brief="значение поля" />
///     </args>
///     <body>
  public function field($name, $value) {
    $name = $this->canonicalize($name);

    $vals = array();
    foreach ((array) $value as $v)
      $vals[] = $v instanceof Time_DateTime ? $v->as_rfc1123() : (string) $v;

    $this->fields[$name] = array_merge(isset($this->fields[$name]) ? (array) $this->fields[$name] : array(), $vals);

    if (count($this->fields[$name]) == 1) $this->fields[$name] =  current($this->fields[$name]);

    return $this;
  }
///     </body>
///   </method>

///   <method name="fields" returns="Net.HTTP.Head">
///     <brief>Добавляет набор полей</brief>
///     <details>
///       <p>Набор полей, передаваемый в качестве аргумента, может представлять собой любой итерируемый объект, при этом ключи итератора
///          используются в качестве имен, а значения -- в качестве значений полей заголовка.</p>
///     </details>
///     <args>
///       <arg name="fields" brief="итерируемый набор имен и значений полей" />
///     </args>
///     <body>
  public function fields($fields) {
    foreach ($fields as $k => $v) $this->field($k, $v);
    return $this;
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="indexing" interface="Code.IndexedAccessInterface">

///   <method name="offsetGet" returns="mixed">
///     <brief>Возвращает строковое значение поля по его имени</brief>
///     <args>
///       <arg name="index" type="string" brief="имя поля" />
///     </args>
///     <body>
  public function offsetGet($index) {
    return isset($this->fields[$name = $this->canonicalize($index)]) ? $this->fields[$name] : null;
  }
///     </body>
///   </method>

///   <method name="offsetSet" returns="Net.HTTP.Head">
///     <brief>Устанавливает значение поля по его имени</brief>
///     <args>
///       <arg name="index" type="string" brief="имя поля" />
///       <arg name="value" brief="значение" />
///     </args>
///     <body>
  public function offsetSet($index, $value) {
    $this->field($index, $value);
    return $this;
  }
///     </body>
///   </method>

///   <method name="offsetExists" returns="boolean">
///     <brief>Проверяет наличие поля с заданным именем</brief>
///     <args>
///       <arg name="index" type="string" brief="имя поля" />
///     </args>
///     <body>
  public function offsetExists($index) { return isset($this->fields[$this->canonicalize($index)]); }
///     </body>
///   </method>

///   <method name="offsetUnset" returns="Net.HTTP.Head">
///     <brief>Удаляет поле с именем $index из заголовка</brief>
///     <args>
///       <arg name="index" type="string" brief="имя поля" />
///     </args>
///     <body>
  public function offsetUnset($index) {
    if (isset($this->fields[$name = $this->canonicalize($index)])) unset($this->fields[$name]);
    return $this;
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="counting" interface="Core.CountInterface">

///   <method name="count" returns="int">
///     <brief>Возвращает общее количество полей</brief>
///     <body>
  public function count() { return count($this->fields); }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="iterating" interface="IteratorAggregate">

///   <method name="getIterator" returns="ArrayIterator">
///     <brief>Возвращает итератор по полям заголовка</brief>
///     <body>
  public function getIterator() { return new ArrayIterator($this->fields); }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="calling" interface="Core.CallInterface">

///   <method name="__call" returns="Net.HTTP.Head">
///     <brief>Установка значения поля</brief>
///     <details>
///       <p>Диспетчеризация вызовов позволяет устанавливать значение полей с помощью вызовов методов с именами,
///          соответствующими имени поля. При этом имя поля приводится к каноническому виду.</p>
///     </details>
///     <args>
///       <arg name="method" type="string" brief="имя метода" />
///       <arg name="args"   type="array"  brief="аргументы метода" />
///     </args>
///     <body>
  public function __call($method, $args) {
    $this->__set($method, $args[0]);
    return $this;
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="supporting">

///   <method name="canonicalize" returns="string">
///     <brief>Приводит имя поля к каноническому виду</brief>
///     <details>
///       <p>Имя поля записывается в нижнем регистре, подчеркивания заменяются на тире, первые буквы слова -- в верхнем регистре.</p>
///     </details>
///     <args>
///       <arg name="name" type="string" brief="имя" />
///     </args>
///     <body>
  private function canonicalize($name) {
    $parts = array_map('strtolower', explode('-', trim( str_replace('_', '-', $name))));

    foreach ($parts as &$part)
      $part = preg_match('{[aeiouyAEIOUY]}', $part) ?
        ucfirst($part) :
        strtoupper($part);

    return implode('-', $parts);
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="accessing" interface="Core.PropertyAccessInterface">

///   <method name="__get" returns="mixed">
///     <brief>Возвращает значение поля</brief>
///     <details>
///       <p>Для полей, имя которых заканчивается на _date, возвращается объект типа Time.DateTime. Для получения строкового значения таких полей
///          используйте индексированный доступ.</p>
///     </details>
///     <args>
///       <arg name="property" type="string" brief="имя поля" />
///     </args>
///     <body>
  public function __get($property) {
    if ($wants_date =
       ($property == 'date' ||
        Core_Strings::ends_with($property, '_date')))
      $property = preg_replace('/_date$/', '', $property);

   return (isset($this->fields[$idx = $this->canonicalize($property)])) ?
      ($wants_date ?
        Time::parse($this->fields[$idx], Time::FMT_RFC1123) :
        $this->fields[$idx]) :
      null;
  }
///     </body>
///   </method>

///   <method name="__set" returns="mixed">
///     <brief>Устанавливает значение поля</brief>
///     <details>
///       <p><pre>$head->$field = $value</pre> идентично <pre>$head->field($field, $value)</pre></p>
///     </details>
///     <args>
///       <arg name="property" type="string" brief="имя поля" />
///       <arg name="value" brief="значение" />
///     </args>
///     <body>
  public function __set($property, $value) { return $this->field($property, $value); }
///     </body>
///   </method>

///   <method name="__isset" returns="boolean">
///     <brief>Проверяет существование поля</brief>
///     <args>
///       <arg name="property" type="string" brief="имя поля" />
///     </args>
///     <body>
  public function __isset($property) {
return isset($this->fields[$this->canonicalize(preg_replace('/_date$/', '', $property))]);
  }
///     </body>
///   </method>

///   <method name="__unset">
///     <brief>Удаляет поле</brief>
///     <args>
///       <arg name="property" type="string" brief="имя поля" />
///     </args>
///     <body>
  public function __unset($property) {
    if (isset($this->fields[$idx = $this->canonicalize(preg_replace('/_date$/', '', $property))]))
      unset($this->fields[$idx]);
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="converting">

///   <method name="as_array" returns="array">
///     <args>
///       <arg name="as_lines" type="boolean" default="false" />
///     </args>
///     <body>
  public function as_array($as_lines = false) {
    if ($as_lines) {
      $r = array();
      foreach ($this->fields as $k => $vals)
        foreach ((array) $vals as $v) $r[] = "$k: $v";
      return $r;
    } else
      return $this->fields;
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
    return
      get_class($this) == get_class($to) &&
      Core::equals($this->as_array(), $to->as_array());
  }
///     </body>
///   </method>

///</protocol>
}
/// </class>


/// <class name="Net.HTTP.Message" stereotype="abstract">
///   <brief>Базовый абстрактный класс для запроса и отлика HTTP</brief>
///   <implements interface="Core.PropertyAccessInterface" />
///   <implements interface="Core.CallInterface" />
///   <implements interface="Core.EqualityInterface" />
abstract class Net_HTTP_Message
  implements Core_PropertyAccessInterface,
             Core_EqualityInterface,
             Core_CallInterface {

   protected $protocol = 'HTTP/1.1';
   protected $headers;
   protected $body;

///   <protocol name="creating">

///   <method name="__construct">
///     <brief>Конструктор</brief>
///     <body>
  public function __construct() { $this->headers = new Net_HTTP_Head(); }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="changing">

///   <method name="headers" returns="Net.HTTP.Request">
///     <brief>Добавляет к заголовку сообщения поля из заданнго итератора</brief>
///     <args>
///       <arg name="headers" brief="набор полей" />
///     </args>
///     <body>
  public function headers($headers) {
    $this->headers->fields($headers);
    return $this;
  }
///     </body>
///   </method>

///   <method name="header" returns="Net.HTTP.Request">
///     <brief>Устанавливает поле заголовка</brief>
///     <args>
///       <arg name="name" brief="имя поля" type="string" />
///       <arg name="value" brief="значение" />
///     </args>
///     <body>
  public function header($name, $value) {
    $this->headers->field($name, $value);
    return $this;
  }
///     </body>
///   </method>

///   <method name="protocol" returns="Net.HTTP.Message">
///     <brief>Устанавливает имя протокола</brief>
///     <p>По умолчанию используется 'HTTP/1.1'.</p>
///     <args>
///       <arg name="protocol" type="string" brief="имя протокола" />
///     </args>
///     <body>
  public function protocol($protocol) {
    $this->protocol = (string) $protocol;
    return $this;
  }
///     </body>
///   </method>

///   <method name="body" returns="Net.HTTP.Message">
///     <brief>Устанавливает тело сообщения</brief>
///     <brief>
///       <p>Тело может представлять собой строку или объект, например, файл как объект класса IO.FS.File.</p>
///     </brief>
///     <args>
///       <arg name="body" brief="тело отклика" />
///     </args>
///     <body>
  public function body($body) {
    $this->body = $body;
    return $this;
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="calling">

///   <method name="__call" returns="Net.HTTP.Message">
///     <detail>
///       <p>Динамические методы позволяют устанавливать значения полей заголовка сообщения</p>
///     </detail>
///     <args>
///       <arg name="method" type="string" brief="имя метода" />
///       <arg name="args" type="array" brief="аргументы метода" />
///     </args>
///     <body>
  public function __call($method, $args) {
    $this->headers->field($method, $args[0]);
    return $this;
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="accessing" interface="Core.PropertyAccessInterface">

///   <method name="__get" returns="mixed">
///     <brief>Возвращает значение свойства</brief>
///     <details>
///     <p>Поддерживаются следующие свойства:</p>
///     <dl>
///       <dt>headers</dt><dd>объект класса Net.HTTP.Header, набор заголовков сообщения</dd>
///       <dt>protocol</dt><dd>имя протокола</dd>
///     </dl>
///     </details>
///     <args>
///       <arg name="property" brief="имя свойства" />
///     </args>
///     <body>
  public function __get($property) {
    switch ($property) {
      case 'headers':
      case 'protocol':
      case 'body':
        return $this->$property;
      default:
        throw new Core_MissingPropertyException($property);
    }
  }
///     </body>
///   </method>

///   <method name="__set" returns="mixed">
///     <brief>Устанавливает значение свойства</brief>
///     <details>
///       <p>Все свойства объекта доступны только на чтение.</p>
///     </details>
///     <args>
///       <arg name="property" type="string" brief="имя свойства" />
///       <arg name="value"    type="string" brief="значение" />
///     </args>
///     <body>
  public function __set($property, $value) {
    switch ($property) {
      case 'headers':
        throw new Core_ReadOnlyPropertyException($property);
      case 'protocol':
      case 'body':
        {$this->$property($value); return $this;};
      default:
        throw new Core_MissingPropertyException($property);
    }
  }
///     </body>
///   </method>

///   <method name="__isset" returns="boolean">
///     <brief>Проверяет установку значения свойства</brief>
///     <args>
///       <arg name="property" brief="имя свойства" />
///     </args>
///     <body>
  public function __isset($property) {
    switch ($property) {
      case 'headers':
      case 'protocol':
      case 'body':
        return isset($this->$property);
      default:
        return false;
    }
  }
///     </body>
///   </method>

///   <method name="__unset">
///     <brief>Удаляет свойство</brief>
///     <details>
///       <p>Удаление свойств объекта запрещено.</p>
///     </details>
///     <args>
///       <arg name="property" type="string" brief="имя свойства" />
///     </args>
///     <body>
  public function __unset($property) {
    switch ($property) {
      case 'headers':
      case 'protocol':
        throw new Core_UndestroyablePropertyException($property);
      case 'body':
        $this->body = null;
        break;
      default:
        throw new Core_MissingPropertyException($property);
    }
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
    return
      get_class($to) == get_class($this) &&
      $this->body == $to->body &&
      $this->protocol == $to->protocol &&
      Core::equals($this->headers, $to->headers);
  }
///     </body>
///   </method>

///</protocol>
}
/// </class>

/// <composition>
///   <source class="Net.HTTP.Message" role="message" multiplicity="1" />
///   <target class="Net.HTTP.Head" role="head" multiplicity="1" />
/// </composition>


/// <class name="Net.HTTP.Request" extends="Net.HTTP.Message">
///   <implements interface="Core.PropertyAccessInterface" />
///   <implements interface="Core.IndexedAccessInterface" />
///   <brief>HTTP-запрос</brief>
class Net_HTTP_Request
  extends Net_HTTP_Message
  implements Core_PropertyAccessInterface, Core_IndexedAccessInterface {

  protected $uri;
  protected $id;

  protected $method = Net_HTTP::GET;

  protected $meta = array();

  protected $parameters = array();
  protected $query      = array();

  protected $session;

  protected $content = null;

///   <protocol name="creating">

///   <method name="__construct">
///     <args>
///       <arg name="uri" type="string" default="''" brief="строка URI запроса" />
///       <arg name="meta" type="array" default="array()" brief="дополнительная информация" />
///     </args>
///     <brief>Конструктор</brief>
///     <body>
  public function __construct($uri = '', array $meta = array()) {
    parent::__construct();  $uri = trim($uri);
    if ($uri) $this->uri($uri);
    $this->meta = $meta;
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="changing">

///   <method name="parameters" returns="Net.HTTP.Request">
///     <brief>Устанавливает параметры запроса</brief>
///     <args>
///       <arg name="parameters"  type="array" brief="массив параметров" />
///     </args>
///     <body>
  public function parameters(array $parameters) {
    Core_Arrays::deep_merge_update_inplace($this->parameters, $parameters);
    return $this;
  }
///     </body>
///   </method>

///   <method name="query_parameters" returns="Net.HTTP.Request">
///     <args>
///       <arg name="parameters" type="array" />
///     </args>
///     <body>
  public function query_parameters(array $parameters) {
    foreach ($parameters as $k => $v) $this->query[$k] = $v;
    return $this;
  }
///     </body>
///   </method>

///   <method name="session" returns="Net.HTTP.Request">
///     <brief>Устанавливает объект сессии</brief>
///     <args>
///       <arg name="session" type="Net.HTTP.SessionInterface" brief="объект сессии" />
///     </args>
///     <body>
  public function session($session = null) {
    if ($session instanceof Net_HTTP_SessionInterface) {
      $this->session = $session;
      return $this;
    } else {
      if (empty($this->session)) {
        Core::load('Net.HTTP.Session');
        $this->session = Net_HTTP_Session::Store();
      }
      return $this->session;
    }
  }
///     </body>
///   </method>

///   <method name="uri" returns="Net.HTTP.Request">
///     <brief>Устанавливает URI запроса</brief>
///     <args>
///       <arg name="uri" type="string" brief="строка URI"/>
///     </args>
///     <body>
  public function uri($uri) {
      $this->uri = ($parsed = @parse_url((string) $uri)) ? $parsed : parse_url('/');

    //if ($this->uri['host']) $this->headers->host($this->uri['host']);
//TODO: проверить обнуление query параметров
      if ($parsed && isset($parsed['query'])) $this->parse_query($parsed['query']);
    return $this;
  }
///     </body>
///   </method>

///   <method name="method" returns="Net.HTTP.Request">
///     <brief>Устанавливает метод HTTP-запроса</brief>
///     <args>
///       <arg name="method" type="int|string" />
///     </args>
///     <details>
///       <p>В качестве параметра можно использовать целочисленную константу или строковое название метода.</p>
///     </details>
///     <body>
  public function method($method) {
    switch (is_string($method) ? strtolower($method) : $method) {
      case 'get':
      case Net_HTTP::GET:
        $this->method = Net_HTTP::GET;
        break;
      case 'put':
      case Net_HTTP::PUT:
        $this->method = Net_HTTP::PUT;
        break;
      case 'post':
      case Net_HTTP::POST:
        $this->method = Net_HTTP::POST;
        break;
      case 'delete':
      case Net_HTTP::DELETE:
        $this->method = Net_HTTP::DELETE;
        break;
      case 'head':
      case Net_HTTP::HEAD:
        $this->method = Net_HTTP::HEAD;
        break;
    }
    return $this;
  }
///     </body>
///   </method>

  public function is_post() {
    return $this->method == NET_HTTP::POST;
  }
  
  public function is_get() {
    return $this->method == NET_HTTP::GET;
  }

  public function is_delete() {
    return $this->method == NET_HTTP::DELETE;
  }

  public function is_head() {
    return $this->method == NET_HTTP::HEAD;
  }
  


///   </protocol>

///   <protocol name="accessing">

///   <method name="__get" returns="mixed">
///     <brief>Возвращает значение свойства</brief>
///     <details>
///       <p>Поддерживаются следующие свойства:</p>
///       <dl>
///         <dt>scheme</dt><dd>схема</dd>
///         <dt>host</dt><dd>хост</dd>
///         <dt>path</dt><dd>путь</dd>
///         <dt>query</dt><dd>GET-параметры</dd>
///         <dt>method</dt><dd>HTTP-метод</dd>
///         <dt>parameters</dt><dd>набор параметров</dd>
///         <dt>headers</dt><dd>набор заголовков запроса</dd>
///         <dt>session</dt><dd>объект сессии</dd>
///         <dt>meta</dt><dd>метаинформация</dd>
///         <dt>uri</dt><dd>строка URI</dd>
///       </dl>
///       <p>Также доступны свойства родительского класса, Net.HTTP.Message.</p>
///     </details>
///     <args>
///       <arg name="property" type="string" />
///     </args>
///     <body>
  public function __get($property) {
    switch ($property) {
      case 'scheme':
      case 'host':
      case 'path':
      case 'port':
      case 'user':
      case 'pass':
        return $this->uri[$property];
      case 'query':
        return $this->urlencode($this->query);
      case 'headers':
      case 'session':
      case 'meta':
      case 'body':
        return $this->$property;
      case 'content':
        return $this->$property ? $this->$property : ($this->$property = @file_get_contents('php://input'));
      case 'parameters':
        return Core_Arrays::merge($this->query, $this->parameters);
      case 'post_data':
        return $this->urlencode($this->parameters);
      case 'url':
        return $this->compose_url();
      case 'uri':
        return $this->compose_uri();
      case 'urn':
        return $this->compose_urn();
      case 'method_name':
      case 'method':
        return Net_HTTP::method_name_for($this->method);
      case 'method_code':
        return $this->method;
      case 'id':
        if ($this->id) return $this->id;
        Core::load('Text.Process');
        $id = Text_Process::process($this->path, 'translit');
        $id = trim(preg_replace('{[^a-zA-Z0-9]+}ui', '_', $id), '_');
        return $this->id = $id;
      default:
        return parent::__get($property);
    }
  }
///     </body>
///   </method>

///   <method name="__set" returns="mixed">
///     <brief>Устанавливает значение свойства</brief>
///     <details>
///       <p>На запись доступны только свойства method, uri и session, остальные -- только на чтение</p>
///     </details>
///     <args>
///       <arg name="property" type="string" brief="имя свойства" />
///       <arg name="value" brief="значение свойства" />
///     </args>
///     <body>
  public function __set($property, $value) {
    switch ($property) {
      case 'path':
      case 'host':
      case 'scheme':
      case 'method':
      case 'uri':
      case 'session':
      case 'body':
        $this->$property($value);
        return $this;
      case 'query':
        $this->parse_query($value);
        return $this;
      case 'post_data':
      case 'parameters':
      case 'meta':
      case 'method_name':
      case 'method_code':
      case 'content':
      case 'urn':
      case 'url':
      case 'id':
        throw new Core_ReadOnlyPropertyException($property);
      default:
        return parent::__set($property, $value);
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
      case 'scheme':
      case 'host':
      case 'path':
        return isset($this->uri[$property]);
      case 'body':
        return isset($this->body);
      case 'uri':
      case 'urn':
      case 'url':
      case 'id':
        return true;
      case 'content':
        return !is_null($this->$property);
      case 'query':
      case 'session':
      case 'meta':
      case 'parameters':
      case 'method':
      case 'headers':
        return isset($this->$property);
      case 'post_data':
        return true;
      case 'method_name': case 'method_code':
        return isset($this->method);
      default:
        return parent::__isset($property);
    }
  }
///     </body>
///   </method>

///   <method name="__unset">
///     <brief>Удаляет свойство</brief>
///     <details>
///       <p>Удаление свойств запрещено.</p>
///     </details>
///     <args>
///       <arg name="property" type="string" />
///     </args>
///     <body>
  public function __unset($property) {
    switch ($property) {
      case 'scheme':
      case 'host':
      case 'path':
      case 'query':
      case 'uri':
      case 'session':
      case 'meta':
      case 'parameters':
      case 'post_data':
      case 'method':
      case 'method_name':
      case 'method_code':
      case 'headers':
      case 'content':
      case 'urn':
      case 'url':
      case 'id':
        throw new Core_UndestroyablePropertyException($property);
      case 'body':
        $this->body = null;
        break;
      default:
        throw new Core_MissingPropertyExeception($property);
    }
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="calling">

///   <method name="__call" returns="mixed">
///     <brief>Диспетчер динамических вызовов</brief>
///     <details>
///       <p>Помимо вызовов базового класса Net.HTTP.Message, поддерживаются вызовы
///           path, host, query и scheme для установки различных частей URI.</p>
///     </details>
///     <args>
///       <arg name="method" type="string" brief="имя метода" />
///       <arg name="args" type="array" brief="параметры вызова метода" />
///     </args>
///     <body>
  public function __call($method, $args) {
    switch ($method) {
      case 'path':
      case 'host':
      case 'scheme':
        $this->uri[$method] = (string) $args[0];
        return $this;
      case 'query':
        $this->parse_query($args[0]);
      default:
        return parent::__call($method, $args);
    }
  }
///     </body>
///   </method>

///   </protocol>


///   <protocol name="indexing">

///   <method name="offsetGet" returns="mixed">
///     <brief>Доступ к параметрам запроса GET POST и т.д.</brief>
///     <args>
///       <arg name="index" type="string" brief="имя параметра" />
///     </args>
///     <body>
//TODO: separate method for cookie, get, post + read all values on creation
  public function offsetGet($index) {
    switch (true) {
      case isset($this->parameters[$index]) : return $this->parameters[$index];
      case isset($this->query[$index]) :      return $this->query[$index];
      case isset($_COOKIE[$index]) :          return $_COOKIE[$index];
      case isset($_POST[$index]) :            return $_POST[$index];
      case isset($_GET[$index]) :             return $_GET[$index];
      default:                                return  null;
    }
  }
///     </body>
///   </method>

///   <method name="offsetSet" returns="mixed">
///     <brief>Устанавливает параметр запроса</brief>
///     <args>
///       <arg name="index" type="string" brief="имя параметра" />
///     </args>
///     <body>
  public function offsetSet($index, $value) {
    if (isset($this->query[$index]))
      $this->query[$index] = $value;
    else
      $this->parameters[$index] = $value;
    return $this;
  }
///     </body>
///   </method>

///   <method name="offsetExists" returns="mixed">
///     <brief>Проверяет установку значения параметра запроса</brief>
///     <args>
///       <arg name="index" type="string" brief="имя параметра" />
///     </args>
///     <body>
  public function offsetExists($index) {
    return isset($this->parameters[$index]) ||
           isset($this->query[$index]) ||
           isset($_COOKIE[$index])  ||
           isset($_POST[$index]) ||
           isset($_GET[$index]);
  }
///     </body>
///   </method>

///   <method name="offsetUnset" returns="mixed">
///     <brief>Удаляет установленный параметр</brief>
///     <args>
///       <arg name="index" type="string" brief="имя параметра" />
///     </args>
///     <body>
  public function offsetUnset($index) {
    unset($this->parameters[$index]);
    return $this;
  }
///     </body>
///   </method>

///   </protocol>


///   <protocol name="quering">

///   <method name="is_xhr" returns="boolean">
///     <brief>Проверяет является ли запрос xhr запросом</brief>
///     <body>
  public function is_xhr() {
    return isset($this->headers['X_REQUESTED_WITH']) && $this->headers['X_REQUESTED_WITH'] == 'XMLHttpRequest';
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="supporting">

///   <method name="compose_url" returns="string" access="protected">
///     <brief>Формирует строку URI на основе результатов парсинга URI, указанного при создании объекта</brief>
///     <body>
  protected function compose_url() {
    $uri = (object) $this->uri;
    return
      ($uri->scheme ? "$uri->scheme://" : '').
      ($uri->user ? $uri->user.($uri->pass ? ":$uri->pass" : '').'@' : '').
      ($uri->host ? $uri->host.($uri->port ? ":$uri->port" : '') : '').
      $this->compose_urn();
  }
///     </body>
///   </method>

///   <method name="compose_url" returns="string" access="protected">
///     <brief>Формирует строку URI на основе результатов парсинга URI, указанного при создании объекта</brief>
///     <body>
  protected function compose_uri() {
    return $this->compose_urn();
  }
///     </body>
///   </method>

///   <method name="compose_url" returns="string" access="protected">
///     <brief>Формирует строку URN на основе результатов парсинга URI, указанного при создании объекта</brief>
///     <body>
  protected function compose_urn() {
    $query = $this->urlencode(
      $this->method == Net_HTTP::GET ?
        array_merge($this->query, $this->parameters) :
        $this->query);
    return $this->uri['path'].($query ? '?'.$query : '');
  }
///     </body>
///   </method>

///   <method name="urlencode" returns="string" access="protected">
///     <args>
///       <arg name="values" type="array" />
///     </args>
///     <body>
  protected function urlencode(array $values) {
    $r = array();
    foreach ($values as $k => $v) $r[] = urlencode($k).'='.urlencode($v);
    return implode('&', $r);
  }
///     </body>
///   </method>

///   <method name="parse_query" returns="Net.HTTP.Request" access="protected">
///     <args>
///       <arg name="query" type="string" />
///     </args>
///     <body>
  protected function parse_query($query) {
    $this->query = array();
    if ($query)
      foreach (explode('&', $query) as $p) {
        list($k, $v) = explode('=', $p);
        if ($k) $this->query[urldecode($k)] = urldecode($v);
      }
    return $this;
  }
///     </body>
///   </method>

///   </protocol>
}
/// </class>


/// <composition>
///   <source class="Net.HTTP.Request" role="request" multiplicity="1" />
///   <target class="Net.HTTP.SessionInterface" role="session" multiplicity="1" />
/// </composition>


/// <class name="Net.HTTP.Response" extends="Net.HTTP.Message">
///   <brief>HTTP отклик</brief>
///   <implements interface="Core.PropertyAccessInterface" />
///   <implements interface="Core.CallInterface" />
///   <implements interface="Core.EqualityInterface" />
class Net_HTTP_Response
  extends Net_HTTP_Message
  implements Core_PropertyAccessInterface,
             Core_IndexedAccessInterface,
             Core_EqualityInterface,
             Core_CallInterface {

    protected $status;
    protected $url = '';

///   <protocol name="creating">

///   <method name="__construct">
///     <body>
  public function __construct() {
    $this->status = Net_HTTP::Status(Net_HTTP::OK);
    parent::__construct();//TODO:
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="building">

///   <method name="from_string" returns="Net.HTTP.Response" scope="class">
///     <args>
///       <arg name="string" type="string" brief="строковое представление отклика" />
///     </args>
///     <body>
    static public function from_string(&$body, &$header) {
      $response = new Net_HTTP_Response();
      $header = preg_replace('!HTTP/\d.\d\s+100\s+Continue\s+!', '', $header);
      $m = array();
      $status = '';

      foreach (explode("\n", $header) as $line) {
        if (!$status && preg_match('{^HTTP/\d\.\d\s+(\d+(?:\s+.+))}', $line, $m)) $status = $m[1];
        if (preg_match("{([^:]+):\s*(.*)}", $line, $m)) $response->header($m[1], $m[2]);
      }
      list($code, $message) = explode(" ", preg_replace('{\s+}', ' ', (string) $status), 2);
      return $response->
        status($code, trim($message))->
        body($body);
    }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="changing">

///   <method name="status" returns="Net.HTTP.Response">
///     <brief>Устанавливает статус отклика</brief>
///     <args>
///       <arg name="status" type="Net.HTTP.Status" brief="статус отклика" />
///     </args>
///     <body>
  public function status($code, $message = null) {
    $this->status = $code instanceof Net_HTTP_Status ? $code : Net_HTTP::Status($code, $message);
    return $this;
  }
///     </body>
///   </method>

  public function url($url)
  {
    $this->url = $url;
    return $url;
  }

///   </protocol>

///   <protocol name="calling" interface="Core.CallInterface">

///   <method name="__call" returns="Net.HTTP.Response">
///     <brief>Диспетчер вызовов</brief>
///     <details>
///       <p>Динамические вызовы перенаправляются объекту headers -- набору заголовков отклика.</p>
///     </details>
///     <args>
///       <arg name="method" type="string" brief="имя метода" />
///       <arg name="args"   type="array" brief="параметры метода" />
///     </args>
///     <body>
  public function __call($method, $args) {
    $this->headers->__call($method, $args);
    return $this;
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="accessing" interface="Core.PropertyAccessInterface">

///   <method name="__get" returns="mixed">
///     <brief>Возвращает значение свойства</brief>
///     <details>
///     <p>Поддерживаются свойства родительского класса Net.HTTP.Message, а также следующие
///        собственные свойства:</p>
///     <dl>
///       <dt>body</dt><dd>тело отклика</dd>
///       <dt>status</dt><dd>HTTP-статус отклика</dd>
///     </dl>
///     </details>
///     <args>
///       <arg name="property" type="string" brief="имя свойства" />
///     </args>
///     <body>
  public function __get($property) {
    switch ($property) {
      case 'status': case 'url':
        return $this->$property;
      default:
        return parent::__get($property);
    }
  }
///     </body>
///   </method>

///   <method name="__set" returns="mixed">
///     <brief>Устанавливает значение свойства</brief>
///     <args>
///       <arg name="property" type="string" brief="имя свойства" />
///       <arg name="value" brief="значение свойства" />
///     </args>
///     <body>
  public function __set($property, $value) {
    switch ($property) {
      case 'status':  case 'url':
        $this->$property($value);
        return $this;
      default:
        return parent::__set($property, $value);
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
      case 'status': case 'url':
        return true;
      default:
        return parent::__isset($property);
    }
  }
///     </body>
///   </method>

///   <method name="__unset">
///     <brief>Удаляет свойство</brief>
///     <details>
///       <p>Удаление свойств запрещено.</p>
///     </details>
///     <args>
///       <arg name="property" type="string" brief="имя свойства" />
///     </args>
///     <body>
  public function __unset($property) {
    switch ($property) {
      case 'status': case 'url':
        throw new Core_UndestroyablePropertyException($property);
      default:
        parent::__unset($property);
    }
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
    return
      get_class($this) == get_class($to) &&
      Core::equals($this->status, $to->status) && parent::equals($to);
  }
///     </body>
///   </method>

///</protocol>

///   <protocol name="indexing">

///   <method name="offsetGet" returns="mixed">
///     <args>
///       <arg name="index" type="string" brief="имя параметра" />
///     </args>
///     <body>
  public function offsetGet($index) {
    return $this->headers[$index];
  }
///     </body>
///   </method>

///   <method name="offsetSet" returns="mixed">
///     <args>
///       <arg name="index" type="string" brief="имя параметра" />
///     </args>
///     <body>
  public function offsetSet($index, $value) {
    $this->headers[$index] = $value;
    return $this;
  }
///     </body>
///   </method>

///   <method name="offsetExists" returns="mixed">
///     <args>
///       <arg name="index" type="string" brief="имя параметра" />
///     </args>
///     <body>
  public function offsetExists($index) {
    return isset($this->headers[$index]);
  }
///     </body>
///   </method>

///   <method name="offsetUnset" returns="mixed">
///     <args>
///       <arg name="index" type="string" brief="имя параметра" />
///     </args>
///     <body>
  public function offsetUnset($index) {
    unset($this->headers[$index]);
  }
///     </body>
///   </method>

///   </protocol>

}
/// </class>


/// <class name="Net.HTTP.Upload" extends="IO.FS.File">
///   <brief>Объектное представление HTTP upload</brief>
///   <details>
///     <p>Класс реализует стандартные файловые объекты, соответствующие временным файлам HTTP upload,
///         с дополнительным хранением информации об оригинальном имени файла.</p>
///   </details>
///   <implements interface="Core.PropertyAccessInterface" />
///   <depends supplier="IO.FS.Path" stereotype="uses" />
///   <depends supplier="MIME" stereotype="queries" />
class Net_HTTP_Upload
  extends    IO_FS_File
  implements Core_PropertyAccessInterface {

  protected $original_name;
  protected $file_array;

///   <protocol name="creating">

///   <method name="__construct">
///     <brief>Конструктор</brief>
///     <args>
///       <arg name="tmp_name"      type="string" brief="путь к временному файлу" />
///       <arg name="original_name" type="string" brief="оригинальное имя файла" />
///     </args>
///     <body>
  public function __construct($tmp_path, $original_name, $file_array = array()) {
    $this->original_name = IO_FS::Path($original_name)->basename;
    $this->file_array = $file_array;
    parent::__construct($tmp_path);
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="accessing" interface="Core.PropertyAccessInterface">

///   <method name="__get" returns="mixed">
///     <brief>Возвращает значение свойства</brief>
///     <details>
///       <p>Помимо обычных свойств объекта IO.FS.File, доступно также свойство original_name.</p>
///     </details>
///     <args>
///       <arg name="property" type="string" brief="имя свойства" />
///     </args>
///     <body>
  public function __get($property) {
    switch ($property) {
      case 'original_name':
        return $this->original_name;
      case 'file_array':
        return $this->file_array;
      default:
        return parent::__get($property);
    }
  }
///     </body>
///   </method>

///   <method name="__set" returns="mixed">
///     <brief>Устанавливает значение свойства</brief>
///     <details>
///       <p>Свойство original_name доступно только на чтение.</p>
///     </details>
///     <args>
///       <arg name="property" type="string" brief="имя свойства" />
///       <arg name="value" brief="значение свойства" />
///     </args>
///     <body>
  public function __set($property, $value) {
    switch ($property) {
      case 'original_name': case 'file_array':
        throw new Core_ReadOnlyPropertyException($property);
      default:
        return parent::__set($property, $value);
    }
  }
///     </body>
///   </method>

///   <method name="__isset" returns="boolean">
///     <brief>Проверяет наличие значения свойства</brief>
///     <args>
///       <arg name="property" type="string" brief="имя свойства" />
///     </args>
///     <body>
  public function __isset($property) {
    switch ($property) {
      case 'original_name': case 'file_array':
        return true;
      default:
        return parent::__isset($property);
    }
  }
///     </body>
///   </method>

///   <method name="__unset">
///     <brief>Удаляет свойство</brief>
///     <details>
///       <p>Удаление свойств запрещено.</p>
///     </details>
///     <args>
///       <arg name="property" type="string" brief="имя свойства" />
///     </args>
///     <body>
  public function __unset($property) {
    switch ($property) {
      case 'original_name': case 'file_array':
        throw new Core_UndestroyablePropertyException($property);
      default:
        parent::__unset($property);
    }
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="supporting">

///   <method name="get_mime_type" returns="string" access="protected">
///     <brief>Возвращает MIME-тип файла</brief>
///     <body>
  protected function get_mime_type() {
    return $this->mime_type ?
      $this->mime_type :
      $this->mime_type = MIME::type_for_file($this->original_name);
  }
///     </body>
///   </method>

///   </protocol>
}
/// </class>

/// <class name="Net.HTTP.Download" extends="Net.HTTP.Response">
class Net_HTTP_Download extends Net_HTTP_Response {
  
  protected $file;
  protected $cache = false;
  protected $cache_lifetime;
  protected $range = false;
  
  public function __construct($file, $cache = false, $cache_lifetime = Net_HTTP::DEFAULT_CACHE_LIFETIME) {
    parent::__construct();
    $this->file = IO_FS::File($file);
    $this->cache = $cache;
    $this->cache_lifetime = $cache_lifetime;
    $this->setup();
  }
  
  protected function read_file() {
    global $_SERVER;
    $stream = $this->file->open('rb');
    if (!empty($_SERVER["HTTP_RANGE"])) { 
      $range = $_SERVER["HTTP_RANGE"]; 
      $range = str_replace("bytes=", "", $range); 
      $range = str_replace("-", "", $range); 
      if ($range) $stream->seek($range);
      $this->range = $range;
    }
    $this->body = $stream->read_chunk($this->file->size); 
    $stream->close(); 
  }
  
  protected function set_headers() {
    $ftime = $this->file->stat->mtime->timestamp;
    if ($this->cache) $ftime = 1000000;
    $ftime = gmdate("D, d M Y H:i:s T", $ftime);
    $etime = gmdate("D, d M Y H:i:s T", time() + $this->cache_lifetime);
    $fsize = $this->file->size;
    if ($this->range) {
      $this->status(Net_HTTP::PARTIAL_CONTENT);
      $this['Accept-Ranges'] = 'bytes';
      $this['Content-Range'] = "bytes {$this->range}-" . ($fsize -1) . "/" . $fsize;
    }
    $this->
      content_type($this->file->content_type)-> 
      expires($etime)->
      last_modified($ftime)->
      content_length($fsize - (int) $this->range)->
      content_disposition("inline; filename={$this->file->name}")->
      content_transfer_encoding('binary')->
      cache_control("public,private,max-age=1000000")->
      pragma("public");
  }
  
  protected function setup() { 
    $this->read_file();
    $this->set_headers();
  }
  
}
/// </class>

/// </module>
