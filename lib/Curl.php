<?php
/// <module name="Curl" version="0.1.0" maintainer="timokhin@techart.ru">
///   <brief>Объектный интерфейс к стандартному API Curl</brief>
///   <details>
///   <p>В модуле имеется три класса Easy, EasyHTTP и EasyFTP (последние два наследуются от первого)
///    и соответствующие им фабричные методы.</p>
///   <p>В классе Easy имеются методы option и options для установки одной и нескольких опций.
///      Установка некоторых опций вынесена в отдельные методы, такие как timeout и url. С помощью
///      методов to_string и to_stream можно установить формат возвращаемого результата (ввиде строки
///      или файла). Метод exec собственно и делает запрос по указанному адресу и с указанными параметрами,
///      после чего результат доступен в свойстве объекта result, так же через свойство info
///      доступна дополнительная информация (например http-код ответа). exec_with_options делает
///      тоже самое, но перед выполнением устанавливает переданные в метод опции,
///      а после выполнения возвращает все опции обратно.</p>
///   <p>Класс EasyHTTP предназначен для http запросов. Соответственно добавились такие методы как
///      include_header, with_headers, get, post, head и т.п. Класс EasyFTP предназначен для ftp запросов
///      и в нем добавлены методы text, binary, get, ls. Для более подробной информации обращайтесь к
///      описанию классов.</p>
///   <p>Примеры</p> 
///   <code><![CDATA[
///     $response = Curl::HTTP()->post('api-verify.recaptcha.net/verify', array(
///           'privatekey' => $privkey,
///           'remoteip' => $remote_addr,
///           'challenge' => $challenge_field,
///           'response' => $response_field
///         ))->result;
///
///     Curl::HTTP()->to_stream(IO_FS::File($path)->open('w'))->get('http://url.com')->http_code == 200;
///   ]]></code>
///   </details>

Core::load('Data');

/// <class name="Curl" stereotype="module">
///   <implements interface="Core.ModuleInterface" />
///   <depends supplier="Curl.Easy" stereotype="creates" />
///   <depends supplier="Curl.EasyHTTP" stereotype="creates" />
///   <depends supplier="Curl.EasyFTP"  stereotype="creates" />    
class Curl implements Core_ModuleInterface {
///   <constants>
  const MODULE  = 'Curl';
  const VERSION = '0.1.0';
///   </constants>

///   <protocol name="building">

///   <method name="Easy" returns="Curl.Easy" scope="class">
///     <brief>Фабричный метод, возвращает объект класса Curl.Easy</brief>
///     <args>
///       <arg name="options" type="array" default="array()" brief="массив опций" />
///     </args>
///     <body>
  static public function Easy(array $options = array()) { return new Curl_Easy($options); }
///     </body>
///   </method>
  
///   <method name="HTTP" returns="Curl.EasyHTTP" scope="class">
///     <brief>Фабричный метод, возвращает объект класса Curl.EasyHTTP</brief>
///     <args>
///       <arg name="options" type="array" default="array()" brief="массив опций" />
///     </args>
///     <body>
  static public function HTTP(array $options = array()) { 
    return new Curl_EasyHTTP($options); 
  }
///     </body>
///   </method>

///   <method name="FTP" returns="Curl.EasyFTP" scope="class">
///     <brief>Фабричный метод, возвращает объект класса Curl.EasyFTP</brief>
///     <args>
///       <arg name="options" type="array" default="array()" brief="массив опций" />
///     </args>
///     <body>
  static public function FTP(array $options = array()) {
    return new Curl_EasyFTP($options);  
  }
///     </body>
///   </method>
  
///   </protocol>
}
/// </class>


/// <class name="Curl.Easy">
///   <brief>Базовый класс для работы с curl</brief>
///   <implements interface="Core_PropertyAccessInterface" />
class Curl_Easy 
  implements Core_PropertyAccessInterface {
  
  protected $id;
  protected $result;
  
  protected $options = array();
  protected $info;
    
///   <protocol name="creating">

///   <method name="__construct">
///     <brief>Конструктор</brief>
///     <args>
///       <arg name="options" type="array" default="array()" brief="массив опций" />
///     </args>
///     <body>
  public function __construct(array $options = array()) {
    $this->id = curl_init();
    $this->
      options(array(
        CURLOPT_RETURNTRANSFER => 1))->
      options($options);
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="configuring">

///   <method name="option" returns="Curl.Easy">
///     <brief>Устанавливает опцию</brief>
///     <args>
///       <arg name="option" type="int" brief="опция" />
///       <arg name="value" brief="значение" />
///     </args>
///     <body>
  public function option($option, $value) {
    if (curl_setopt($this->id, $option, $value)) 
      $this->options[$option] = $value;
    return $this;
  }
///     </body>
///   </method>
  
///   <method name="options" returns="Curl.Easy">
///     <brief>Устанавливет массив опций</brief>
///     <args>
///       <arg name="options" type="array" default="array()" brief="массив опций" />
///     </args>
///     <body>
  public function options(array $options = array()) {
    foreach ($options as $k => $v) $result &= $this->option($k, $v);
    return $this;    
  }
///     </body>
///   </method>

///   <method name="timeout" returns="Curl.Easy">
///     <brief>Устанавливает опцию CURLOPT_TIMEOUT </brief>
///     <args>
///       <arg name="seconds" type="int" brief="секунды" />
///     </args>
///     <body>
  public function timeout($seconds) {
    return $this->option(CURLOPT_TIMEOUT, (int)$seconds);
  }
///     </body>
///   </method>
  
///   <method name="url" returns="Curl.Easy">
///     <brief>Устанавливает url запроса</brief>
///     <args>
///       <arg name="url" type="string" brief="url-адрес" />
///     </args>
///     <body>
  public function url($url) { return $this->option(CURLOPT_URL, (string) $url); }
///     </body>
///   </method>
  
///   <method name="to_stream">
///     <brief>Устанавливает запись результата в поток</brief>
///     <args>
///       <arg name="stream" type="IO.Stream.ResourceStream" brief="поток" />
///     </args>
///     <body>
  public function to_stream(IO_Stream_ResourceStream $stream) {
    $this->result = $stream;
    return $this->option(CURLOPT_FILE, $stream->id);
  }
///     </body>
///   </method>

///   <method name="to_string" returns="Curl.Easy">
///     <brief>Результатом выполнения запроса будет строка</brief>
///     <body>
  public function to_string() {
    $this->result = '';
    return $this->option(CURLOPT_RETURNTRANSFER, 1);
  }
///     </body>
///   </method>  

///   <method name="with_credentials" returns="Curl.EasyHTTP">
///     <brief>Устанавливает имя пользователя и пароль для использования при соединении</brief>
///     <args>
///       <arg name="user"     type="string" brief="имя пользователя" />
///       <arg name="password" type="string" default="''" brief="пароль" />
///     </args>
///     <body>
  public function with_credentials($user, $password = '') {
    return $this->option(CURLOPT_USERPWD, $user.($password ? ":$password" : ''));
  }
///     </body>
///   </method>

///   <method name="using_proxy" returns="Curl.EasyHTTP">
///     <brief>Устанавливает настройки прокси</brief>
///     <args>
///       <arg name="proxy" type="string" brief="адресс прокси-сервера" />
///       <arg name="user" type="string" brief="имя пользователя" />
///       <arg name="password" type="string" brief="пароль" />
///     </args>
///     <body>
  public function using_proxy($proxy, $user = '', $password = '') {
    $this->option(CURLOPT_PROXY, $proxy);
    if ($user)
      $this->option(CURLOPT_PROXYUSERPWD, $user.($password ? ":$password" : ''));
    return $this;
  }
///     </body>
///   </method>
  
  
///   </protocol>
  
///   <protocol name="performing">

///   <method name="exec" returns="boolean">
///     <brief>Выполняет запрос</brief>
///     <body>
  public function exec() {
    if (($result = curl_exec($this->id)) &&
        ($this->options[CURLOPT_RETURNTRANSFER])) $this->result = $result;
    $this->info = curl_getinfo($this->id);
    return $this;
  }
///     </body>
///   </method>

///   <method name="exec_with_options" returns="boolean">
///     <brief>Выполняет запрос, устанавливая переданные опции</brief>
///     <args>
///       <arg name="options" type="array" brief="массив опций" />
///     </args>
///     <body>
  public function exec_with_options(array $options) {
    if (curl_setopt_array($this->id, $options)) {
      $this->exec();
      foreach ($options as $k => $v)
        if (isset($this->options[$k])) 
          curl_setopt($this->id, $k, $this->options[$k]);
    }
    return $this;
  }
///     </body>
///   </method>
  
///   </protocol>
    
///   <protocol name="destroying">

///   <method name="__destruct">
///     <brief>Деструктор</brief>
///     <body>
  public function __destruct() { curl_close($this->id); }
///     </body>
///   </method>
  
///   </protocol>

///   <protocol name="accessing" interface="Core.PropertyAccessInterface">

///   <method name="__get" returns="mixed">
///     <brief>Доступ на чтение к свойствам объекта</brief>
///     <details>
///       <dl>
///         <dt>id</dt><dd>идентификатор curl</dd>
///         <dt>result</dt><dd>результат выполнеиня операции</dd>
///         <dt>options</dt><dd>массив опций</dd>
///         <dt>info</dt><dd>массив информации о последней операции, полученный с помощью curl_getinfo </dd>
///         <dt>errno</dt><dd>номер ошибки</dd>
///         <dt>error</dt><dd>информация об ошибки</dd>
///         <dt>по умолчанию</dt><dd>перенаправляет на массив информации о последней операции, полученный с помощью curl_getinfo </dd>
///       </dl>
///     </details>
///     <args>
///       <arg name="property" type="string" brief="имя свойства" />
///     </args>
///     <body>
  public function __get($property) {
    switch ($property) {
      case 'id':
      case 'result':
      case 'options':
        return $this->$property;
      case 'info':
        return (object) $this->info;
      case 'errno':
        return curl_errno($this->id);
      case 'error':
        return curl_error($this->id);
      default:
        if (isset($this->info[$property]))
          return $this->info[$property];
        else
          throw new Core_MissingPropertyException($property);
    }
  }
///     </body>
///   </method>

///   <method name="__set" returns="mixed">
///     <brief>Доступ на запись к свойствам объекта</brief>
///     <details>
///       Выбрасывает исключение, доступ тольлко для чтения
///     </details>
///     <args>
///       <arg name="property" type="string" brief="имя свойства" />
///       <arg name="value" brief="значение" />
///     </args>
///     <body>  
  public function __set($property, $value) {
    throw $this->__isset($property) ?
      new Core_ReadOnlyPropertyException($property) :
      new Core_MissingPropertyException($property);
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
      case 'result':
      case 'errno':
      case 'error':
      case 'info':
      case 'options':
        return true;
      default:
        return isset($this->info[$property]);
    }
  }
///     </body>
///   </method>
  
///   <method name="__unset" returns="boolean">
///     <brief>Очищает совйство объекта</brief>
///     <args>
///       <arg name="property" type="string" brief="имя свойтсва" />
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
}
/// </class>

/// <class name="Curl.EasyHTTP" extends="Curl.Easy">
///   <brief>Класс для http запросов с помощью curl</brief>
class Curl_EasyHTTP extends Curl_Easy {
  protected $parameters = array();

///   <protocol name="creating">
  
///   <method name="__construct">
///     <brief>Конструктор  </brief>
///     <details>
///       Устанавливает опции CURLOPT_HEADER,CURLOPT_NOBODY в 0
///     </details>
///     <args>
///       <arg name="options" type="array" default="array" brief="массив опций" />
///     </args>
///     <body>
  public function __construct($options = array()) {
    parent::__construct();
    $this->
      options(array(
        CURLOPT_HEADER  => 0,
        CURLOPT_NOBODY  => 0 ))->
      options($options);
  }
///     </body>
///   </method>
  
///   </protocol>
  
///   <protocol name="configuring">
  
///   <method name="referer" returns="Curl.EasyHTTP">
///     <brief>Устанавливает содержимое 'Referer:' использует в заголовке запроса</brief>
///     <args>
///       <arg name="referer" type="string" />
///     </args>
///     <body>
  public function referer($referer) {
    return $this->option(CURLOPT_REFERER, (string) $referer);
  }
///     </body>
///   </method>
  
///   <method name="user_agent" returns="Curl.EasyHTTP">
///     <brief>Устанавливает содержимое 'User-Agent:' использует в заголовке запроса</brief>
///     <args>
///       <arg name="agent" />
///     </args>
///     <body>
  public function user_agent($agent) {
    return $this->option(CURLOPT_USERAGENT, (string) $agent);
  }
///     </body>
///   </method>
  
///   <method name="follow_location">
///     <brief>Устанавливает опцию CURLOPT_FOLLOWLOCATION в 1, CURLOPT_MAXREDIRS в переданное значение</brief>
///     <args>
///       <arg name="max_redirs" type="int" default="0" brief="максимальное число http redirections" />
///     </args>
///     <body>
  public function follow_location($max_redirs = 0) {
    $this->option(CURLOPT_FOLLOWLOCATION, 1);
    if ((int) $max_redirs > 0) $this->option(CURLOPT_MAXREDIRS, (int) $max_redirs);
    return $this;
  }
///     </body>
///   </method>
  
///   <method name="include_header" returns="Curl.EasyHTTP">
///     <brief>Устанавливает CURLOPT_HEADER</brief>
///     <args>
///       <arg name="flag" type="boolean" default="true" />
///     </args>
///     <body>
  public function include_header($flag = true) {
    return $this->option(CURLOPT_HEADER, $flag);
  }
///     </body>
///   </method>
  
///   </protocol>
  
///   <protocol name="performing">

///   <method name="with_headers" returns="Curl.EasyHTTP">
///     <brief>Устанавливает заголовки запроса</brief>
///     <args>
///       <arg name="headers" type="array" brief="массив заголовков" />
///     </args>
///     <body>
  public function with_headers(array $headers) {
    return $this->option(CURLOPT_HTTPHEADER, $headers);
  }
///     </body>
///   </method>
  
///   <method name="with_parameters" returns="Curl.EasyHTTP">
///     <args>
///       <arg name="parameters" />
///     </args>
//TODO: как ни странно, но $this->parameters больше нигде не используется
///     <body>
  public function with_parameters($parameters) {
    if ($parameters === null) return $this;
    if (Core_Types::is_array($parameters) || $parameters instanceof Traversable) {
      foreach ($parameters as $k => $v) $this->parameters[$k] = (string) $v;
      return $this;
    } else
      throw new Core_InvalidArgumentTypeException('parameters', $parameters);
  }
///     </body>
///   </method>

///   <method name="url" returns="Curl.EasyHTTP">
///     <brief>Устанавливает url-адрес запроса</brief>
///     <args>
///       <arg name="url" type="string" brief="url-адрес" />
///     </args>
///     <body>
  public function url($url) {
    return $url ?
      parent::url(
        Core_Regexps::match('{https?://}', $url) ? $url : "http://$url") :
      $this;
  }
///     </body>
///   </method>
  
///   <method name="get" returns="mixed">
///     <brief>Выполняет get запрос</brief>
///     <args>
///       <arg name="url" type="string" default="''" brief="url-адрес" />
///       <arg name="parms" type="array|null" brief="массив параметров" />
///     </args>
///     <body>
  public function get($url = '', $parms = null) {
    $encoded = $this->encode_parameters($parms);
    return $this->
      url($url.($encoded ? (Core_Strings::contains($url, '?') ? '&' : '?').$encoded : ''))->
      exec_with_options(array(
        CURLOPT_HTTPGET => 1 ));
  }
///     </body>
///   </method>
  
///   <method name="post" returns="mixed">
///     <brief>Выполняет post запрос</brief>
///     <args>
///       <arg name="url" type="string" default="''" brief="url-адрес" />
///       <arg name="parms" default="null" brief="массив параметров" />
///       <arg name="encode_parms" type="boolean" default="true" brief="кодировать параметры или оставить как есть" />
///     </args>
///     <body>
  public function post($url = '', $parms = null, $encode_parms = true) {
    curl_setopt($this->id, CURLOPT_POSTFIELDS, $encode_parms ? $this->encode_parameters($parms) : $parms);
    
    $this->
      url($url)->
      option(CURLOPT_POST, 1)->
      exec();
    
    curl_setopt($this->id, CURLOPT_POSTFIELDS, '');
    return $this;
  }
///     </body>
///   </method>

///   <method name="head" returns="mixed">
///     <brief>Отправляет get запрос c заголовком</brief>
///     <args>
///       <arg name="url" type="string" default="''" brief="url-адрес" />
///     </args>
///     <body>
  public function head($url = '') {
    return $this->
      url($url)->
      exec_with_options(array(
        CURLOPT_HTTPGET => 1,
        CURLOPT_HEADER  => 1,
        CURLOPT_NOBODY  => 1 ));
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="supporting">

///   <method name="encode_parameters" returns="string" access="protected">
///     <brief>Кодирует параметры  - urlencode</brief>
///     <args>
///       <arg name="parms" brief="массив параметров" />
///     </args>
///     <body>
  protected function encode_parameters(&$parms) {
    $result = '';
    if ($parms && (Core_Types::is_array($parms) || $parms instanceof Traversable))
      foreach ($parms as $k => $v) 
        $result .= ($result ? '&' : '').urlencode($k).'='.urlencode($v);
    return $result;
  }
///     </body>
///   </method>

///   </protocol>
}
/// </class>


/// <class name="Curl.EasyFTP" extends="Curl.Easy">
///   <brief>Класс дял ftp запросов с помощью curl</brief>
class Curl_EasyFTP extends Curl_Easy {

///   <protocol name="creating">
///   <method name="__construct">
///     <brief>Конструктор</brief>
///     <args>
///       <arg name="options" type="array" default="array()" brief="массив опций" />
///     </args>
///     <body>
  public function __construct(array $options = array()) {
    parent::__construct();
    $this->
      options()->
      options($options);
  }
///     </body>
///   </method>
  
///   </protocol>
  
///   <protocol name="configuring">
  
///   <method name="text" returns="Curl.EasyFTP">
///     <brief>Устанавливает ASCII mode для FTP передачи</brief>
///     <body>
  public function text() {
    return $this->option(CURLOPT_TRANSFERTEXT, 1);
  }
///     </body>
///   </method>
  
///   <method name="binary" returns="Curl.EasyFTP">
///     <brief>Устанавливает Binary mode для FTP передачи</brief>
///     <body>
  public function binary() {
    return $this->option(CURLOPT_TRANSFERTEXT, 0);
  }
///     </body>
///   </method>  
  
///   </protocol>
  
///   <protocol name="performing">

///   <method name="url" returns="Curl.EasyHTTP">
///     <brief>Устанавливает url-адрес запроса</brief>
///     <args>
///       <arg name="url" type="string" brief="url-адрес" />
///     </args>
///     <body>
  public function url($url) {
    return $url ?
      parent::url(
        Core_Regexps::match('{ftps?://}', $url) ? $url : "ftp://$url") :
      $this;
  }
///     </body>
///   </method>
  
///   <method name="get" returns="Curl.EasyFTP">
///     <brief>Выполняет запрос</brief>
///     <args>
///       <arg name="url" type="string" brief="url-адрес" />
///     </args>
///     <body>
  public function get($url = '') {
    return $this->
      url($url)->
      exec();
  }
///     </body>
///   </method>
  
///   <method name="ls" returns="Curl.EasyFTP">
///     <brief>Выполняет запрос на получение списка файлов</brief>
///     <args>
///       <arg name="url" type="string" brief="url-адрес" />
///     </args>
///     <body>
  public function ls($url = '') {
    return $this->
      url($url)->
      exec_with_options(array(
        CURLOPT_FTPLISTONLY => 1));
  }
///     </body>
///   </method>

///   </protocol>
}
/// </class>

/// </module>
