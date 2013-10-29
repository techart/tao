<?php
/// <module name="Net.Agents.HTTP" version="0.2.2" maintainer="timokhin@techart.ru">

Core::load('Net.HTTP', 'WS');

/// <class name="Net.Agents.HTTP" stereotype="module">
///   <implements interface="Core.ConfigurableModuleInterface" />
class Net_Agents_HTTP implements Core_ConfigurableModuleInterface {

///   <constants>
  const VERSION = '0.2.2';
///   </constants>

  static protected $options = array(
    'curl_options' => array(
      CURLOPT_RETURNTRANSFER => 1,
      CURLOPT_TIMEOUT        => 20,
      CURLOPT_SSL_VERIFYPEER => 0,
      CURLOPT_FOLLOWLOCATION => 0
      ));

///   <protocol name="creating">

///   <method name="initialize" scope="class">
///     <args>
///       <arg name="options" type="array" default="array()" />
///     </args>
///     <body>
  static public function initialize(array $options = array()) { self::options($options); }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="configuring">

///   <method name="options" returns="array" scope="class">
///     <args>
///       <arg name="options" type="array" default="array()" />
///     </args>
///     <body>
  static public function options(array $options = array()) {
    foreach ($options as $name => $o) {
     if (isset(self::$options[$name]))
       if (is_array(self::$options[$name]))
         self::$options[$name] = self::$options[$name] + $o;
       else
         self::$options[$name] = $o;
    }
    return self::$options;
  }
///     </body>
///   </method>

///   <method name="option" returns="mixed" scope="class">
///     <args>
///       <arg name="name" type="string" />
///       <arg name="value" default="null" />
///     </args>
///     <body>
  static public function option($name, $value = null) {
    $prev = isset(self::$options[$name]) ? self::$options[$name] : null;
    if ($value !== null) self::options(array($name => $value));
    return $prev;
  }
///     </body>
///   </method>

///   <method name="default_curl_options" scope="class">
///     <args>
///       <arg name="options" type="array" />
///     </args>
///     <body>
  static public function default_curl_options(array $options) { Core_Arrays::merge(self::$options['curl_options'], $options); }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="building">

///   <method name="Agent" returns="Curl.Agent.HTTP" scope="class">
///     <body>
  static public function Agent(array $curl_options = array()) { return new Net_Agents_HTTP_Agent($curl_options); }
///     </body>
///   </method>

///   </protocol>
}
/// </class>


/// <class name="Net.Agents.HTTP.Agent">
///   <implements interface="Net.HTTP.AgentInterface" />
class Net_Agents_HTTP_Agent implements Net_HTTP_AgentInterface, Core_PropertyAccessInterface {

  protected $options;
  protected $info = array();
  protected $auto_redirect = true;
  protected $error;
  protected $errno;
  protected $last_result;
  protected $act_as_browser = false;
  protected $inspect = false;
  protected $with_body = true;
  protected $to_file = false;

///   <protocol name="creating">

///   <method name="__construct">
///     <args>
///       <arg name="curl_options" type="array" default="array()" />
///     </args>
///     <body>
  public function __construct(array $curl_options = array()) {
      $this->options = $curl_options;
      if (WS::env() && WS::env()->config && WS::env()->config->proxy)
        $this->using_proxy(WS::env()->config->proxy);
  }
///     </body>
///   </method>

///   </protocol>

  public function to_file($file)
  {
    if (!is_object($file)) {
      $this->to_file = IO_FS::File((string) $file)->open('w+');
    } else {
      $this->to_file = $file;
    }
    $this->option(CURLOPT_FILE, $this->to_file->id);
    return $this;
  }

///   <protocol name="configuring">

///   <method name="option" returns="Net.Agents.HTTP.Agent">
///     <args>
///       <arg name="option" type="int" />
///       <arg name="value" />
///     </args>
///     <body>
  public function option($option, $value) {
    $this->options[$option] = $value;
    return $this;
  }
///     </body>
///   </method>

///   <method name="options" returns="Net.Agents.HTTP.Agent">
///     <args>
///       <arg name="options" type="array" default="array()" />
///     </args>
///     <body>
  public function options(array $options = array()) {
    foreach ($options as $k => $v) $this->option($k, $v);
    return $this;
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="accessing" interface="Core.PropertyAccessInterface">

///   <method name="__get" returns="mixed">
///     <args>
///       <arg name="property" type="string" />
///     </args>
///     <body>
  public function __get($property) {
    switch ($property) {
      case property_exists($this, $property):
        return $this->$property;
      default:
        if (isset($this->info[$property]))
          return $this->info[$property];
        else
          throw new Core_MissingPropertyException($property);
      }
  }
///     </body>
///   </method>

///   <method name="__set" returns="Net.Agents.HTTP.Agent">
///     <args>
///       <arg name="property" type="string" />
///       <arg name="value" />
///     </args>
///     <body>
  public function __set($property, $value) {
    switch ($property) {
      case 'auto_redirect':
        return $THIS->$property = $value;
      case 'options':
      case 'info':
        throw new Core_ReadOnlyPropertyException($property);
      default:
        throw $this->__isset($property) ?
          new Core_ReadOnlyPropertyException($property) :
          new Core_MissingPropertyException($property);
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
      case property_exists($this, $property):
        return true;
      default:
        return isset($this->info[$property]);
    }
  }
///     </body>
///   </method>

///   <method name="__unset" returns="Net.Agents.HTTP.Agent">
///     <args>
///       <arg name="property" type="string" />
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

///   <protocol name="calling" interface="Core.CallInterface">

///   <method name="__call" returns="mixed">
///     <args>
///       <arg name="method" type="string" />
///       <arg name="args" type="array" />
///     </args>
///     <body>
  public function __call($method, $args) {
    switch ($method) {
      case 'auto_redirect': case 'act_as_browser': case 'inspect': case 'with_body':
        $this->$method = isset($args[0]) ? $args[0] : true;
        return $this;
      case 'with_credentials':
        return $this->option(CURLOPT_USERPWD, $args[0].(isset($args[1]) ? ':'.$args[1] : ''));
      case 'using_proxy':
        $this->option(CURLOPT_PROXY, $args[0]);
        if (isset($args[1]))
          $this->option(CURLOPT_PROXYUSERPWD, $args[1].(isset($args[2]) ? ':'.$args[2] : ''));
        return $this;
      default:
        $supported = array(
          'timeout'         => CURLOPT_TIMEOUT,
          'referer'         => CURLOPT_REFERER,
          'user_agent'      => CURLOPT_USERAGENT,
          'follow_location' => array(CURLOPT_FOLLOWLOCATION, 1));

        if (isset($supported[$method]))
          return  is_array($supported[$method]) ?
            $this->option($supported[$method][0], isset($args[0]) ? $args[0] : $supported[$method][1]) :
            $this->option($supported[$method], $args[0]);
        else
          throw new Core_MissingMethodException($method);
    }
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="performing">


  protected function additional_headers($request)
  {
    $request->headers(
      array(
        'User-Agent' => 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Ubuntu Chromium/28.0.1500.71 Chrome/28.0.1500.71 Safari/537.36',
        'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
        'Cache-Control' =>'no-cache',
        'Pragma' => 'no-cache',
      )
    );
    return $this;
  }

///   <method name="send" returns="Net.HTTP.Response">
///     <args>
///       <arg name="request" type="Net.HTTP.Request" />
///     </args>
///     <body>
  public function send($request) {
    if (is_string($request)) {
      $url = $request;
      $request = Net_HTTP::Request($request);
    } else {
      $url = $request->url;
    }

    if (preg_match('{[а-яА-Я]+}u', $request->host) && function_exists('idn_to_ascii')) {
      $request->host = idn_to_ascii($request->host);
      $url = $request->url;
    }

    if ($this->act_as_browser) {
      $this->additional_headers($request);
    }

    $id = $this->make_curl($url);

    $headers = $request->headers->as_array(true);
    $headers[] = 'Expect:'; // by default curl expect 100-continue 

    $options = $this->to_file ?
      array(
        CURLOPT_CUSTOMREQUEST  => strtoupper($request->method_name)
      )
      :
      array(
        CURLOPT_RETURNTRANSFER => 1,
        CURLOPT_CUSTOMREQUEST  => strtoupper($request->method_name),
        CURLOPT_HEADER         => 1,
        CURLOPT_NOBODY         => !$this->with_body
      );

    switch ($request->method_code) {
      case Net_HTTP::GET:
      case Net_HTTP::HEAD:
        break;
      case Net_HTTP::POST:
      case Net_HTTP::PUT:
      case Net_HTTP::DELETE:
        $body = isset($request->body) ?
          (is_array($request->body) ? $request->body : (string) $request->body) :
          $request->post_data;

        if (is_string($body))  $headers[] = 'Content-Length: '.strlen($body);

        $options[CURLOPT_POSTFIELDS] = $body;
        break;
    }

    if ($headers) $options[CURLOPT_HTTPHEADER] = $headers;
    
    if ($this->inspect) {
      $this->inspect($id, $options);
    }
    $result = $this->execute($id, $options);

    $header_size = curl_getinfo($id, CURLINFO_HEADER_SIZE);
    $this->info = curl_getinfo($id);
    $this->error = curl_error($id);
    $this->errno = curl_errno($id);
    $effective_url = trim(curl_getinfo($id, CURLINFO_EFFECTIVE_URL));
    // memory duplicate
    // $this->last_result = &$result;
    curl_close($id);

    if ($this->to_file) {
      $this->to_file->close();
      return Net_HTTP::Response()->status($this->info['http_code']);
    }

    if ($result !== false) {
      $header = substr($result, 0, $header_size);
      $body = substr($result, $header_size);
      unset($result);
      //FIXME:
      $header = preg_replace('{[^\r\n]{0,5}Connection established[^\r\n]{0,5}\r\n\r\n}i', '', $header, 1);
      $response =  Net_HTTP_Response::from_string($body, $header);
      unset($body);
      unset($header);
      $response->url = $url;
      if ($this->auto_redirect && $response->status->is_redirect) {
        return $this->redirect($response, $request, $effective_url);
      }
    } else if (!empty($this->error)) {
      $response = Net_HTTP::Response();
      $response->status(0, $this->error);
    } else {
      return null;
    }
    return $response;
  }

  protected function inspect($id, $options)
  {
    $options[CURLOPT_CUSTOMREQUEST] = 'HEAD';
    $options[CURLOPT_HEADER] = 1;
    $options[CURLOPT_NOBODY] = 1;
    curl_setopt_array($id, $options);
    $header = curl_exec($id);
    $body = '';
    $res = Net_HTTP_Response::from_string($body, $header);
    $len = (int) $res->headers['Content-Length'];
    if ($len > 0) {
      $limit = $this->get_memory_limit();
      if ($limit <= 0) {
        return;
      }
      $allowed_memory = $limit - memory_get_usage();
      if (2.1*$len >= $allowed_memory) {
        throw new RuntimeException('HTTPAgent: not enough memory to download');
      }
    }
  }

  private function get_memory_limit()
  {
    $string = ini_get('memory_limit');

    if ($string == '-1') {
      return -1;
    }
    preg_match('{(\d+)(.*)?}i', $string, $m);
    $amount = (int) $m[1];
    if (isset($m[2]) && !empty($m[2])) {
      $type = strtoupper($m[2]);
      $type_to_amount = array('M' => 1024*1024, 'K' => 1024);
      if (isset($type_to_amount[$type])) {
        $amount *= $type_to_amount[$type];
      }
    }
    return $amount;
  }




///     </body>
///   </method>

///   <method name="redirect">
///     <args>
///       <arg name="response" type="Net.HTTP.Response" />
///       <arg name="request"  type="Net.HTTP.Request" />
///       <arg name="id" type="int" />
///     </args>
///     <body>
  protected function redirect($response, $request, $effective_url) {
    $last_url = parse_url($effective_url);
    $next_url = parse_url(trim($response->headers['Location']));
    if (!$last_url || !$next_url) {
      return $response;
    }
    $last_url = array('scheme' => $last_url['scheme'], 'host' => $last_url['host']);
    $go_url = array_merge($last_url, $next_url);
    $request->
      scheme($go_url['scheme'])->
      host($go_url['host'])->
      path(isset($go_url['path']) ? $go_url['path'] : null)->
      query(isset($go_url['query']) ? $go_url['query'] : null);
    return $this->send($request);
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="supporting">

///   <method name="execute">
///     <args>
///       <arg name="id" type="int" brief="curl identifer" />
///       <arg name="options" type="array" brief="массив опций" />
///     </args>
///     <body>
  protected function execute($id, $options) {
    curl_setopt_array($id, $options);
    return curl_exec($id);
  }
///     </body>
///   </method>

///   <method name="make_curl" returns="int" access="protected">
///     <args>
///       <arg name="uri" type="string" />
///     </args>
///     <body>
  protected function make_curl($uri) {
    $id = curl_init($uri);
    curl_setopt_array($id, Net_Agents_HTTP::option('curl_options'));
    curl_setopt_array($id, $this->options);
    return $id;
  }
///     </body>
///   </method>

///   </protocol>
}
/// </class>

/// </module>
