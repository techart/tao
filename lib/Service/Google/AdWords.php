<?php
/// <module name="Service.Google.Adwords" version="0.1.1" maintainer="svistnov@techart.ru">
Core::load('Service.Google.Auth', 'SOAP');
/// <class name="Service.Google.AdWords" stereotype="module">
///   <implements interface="Core.ModuleInterface" />
class Service_Google_AdWords implements Core_ModuleInterface {
///   <constants>
  const VERSION = '0.1.2';
  const NS = "https://adwords.google.com/api/adwords/cm/v201306";
  const FMT_DATE = '%Y%m%d';
  static protected $SERVICE_SUFFIXES = array('job' => 'job', 'idea' => 'o', 'info' => 'info', 'account' => 'mcm');
  const DEFAULT_SERVICE_SUFFIX = 'cm';
///   </constants>

///   <protocol name="quering">

///   <method name="wsdl_for" returns="string">
///     <brief>Возвращает wsdl адрес для сервиса</brief>
///     <args>
///       <arg name="service" type="string" />
///       <arg name="sandbox" type="boolean" default="false" />
///       <arg name="suffix" type="string" defaults="cm" />
///     </args>
///     <body>
  static public function wsdl_for($name, $sandbox = false) {
    $service = Core_Strings::to_camel_case($name);
    $s = end(explode('_', $name));
    $suffix = isset(self::$SERVICE_SUFFIXES[$s]) ?
      self::$SERVICE_SUFFIXES[$s] : self::DEFAULT_SERVICE_SUFFIX;

    return 'https://'.($sandbox ? 'adwords-sandbox' : 'adwords').
      '.google.com/api/adwords/'.$suffix.'/v201306/'.
      $service.'Service?wsdl';
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="building">

///   <method name="Service">
///     <args>
///       <arg name="client" type="Service.Google.AdWords.Client" />
///       <arg name="wsdl" type="string" />
///       <arg name="classmap" type="array" default="array()" />
///       <arg name="options" type="array" default="array()" />
///     </args>
///     <body>
  static public function Service(Service_Google_AdWords_Client $client, $wsdl, $classmap = array(), $options = array()) {
    return new Service_Google_AdWords_Service($client, $wsdl, $classmap);
  }
///     </body>
///   </method>

///   <method name="Client" returns="Service.Google.Adwords.Client" scope="class">
///     <brief>Фабричный метод, возвращает объект класса Service.Google.Adwords.Client</brief>
///     <args>
///       <arg name="credentials" type="array" brief="массив с настройками аутентификации" />
///     </args>
///     <body>
  static public function Client(Service_Google_Auth_ClientLogin $auth, array $headers = array()) {
    return new Service_Google_AdWords_Client($auth, $headers);
  }
///     </body>
///   </method>

///   <method name="Entity" returns="Service.Google.Adwords.Entity" scope="class">
///     <brief>Фабричный метод, возвращает объект класса Service.Google.Adwords.Entity</brief>
///     <args>
///       <arg name="attrs" type="array" default="array()" brief="массив значений" />
///     </args>
///     <body>
  static public function Entity($type = '', array $attrs = array()) {
    return $type ? new Service_Google_AdWords_Entity($attrs, $type) : new Service_Google_AdWords_Object($attrs);
  }
///     </body>
///   </method>

///   <method name="Selector" returns="Service.Google.Adwords.Campaign" scope="class">
///     <brief>Фабричный метод, возвращает объект класса Service.Google.Adwords.Campaign</brief>
///     <args>
///       <arg name="attrs" type="array" default="array()" brief="массив значений" />
///     </args>
///     <body>
  static public function Selector(array $attrs = array(), $type = null) { return new Service_Google_AdWords_Selector($attrs, $type); }
///     </body>
///   </method>

///   <method name="Operations" returns="Service.Google.Adwords.Operations" scope="class">
///     <brief>Фабричный метод, возвращает объект класса Service.Google.Adwords.Operations</brief>
///     <body>
  static public function Operations() { return new Service_Google_AdWords_Operations(); }
///     </body>
///   </method>

///   <method name="Campaign" returns="Service.Google.Adwords.Campaign" scope="class">
///     <brief>Фабричный метод, возвращает объект класса Service.Google.Adwords.Campaign</brief>
///     <args>
///       <arg name="attrs" type="array" default="array()" brief="массив значений" />
///     </args>
///     <body>
  static public function Campaign(array $attrs = array()) { return new Service_Google_AdWords_Campaign($attrs); }
///     </body>
///   </method>

///   <method name="Campaign" returns="Service.Google.Adwords.Budget" scope="class">
///     <brief>Фабричный метод, возвращает объект класса Service.Google.Adwords.Budget</brief>
///     <args>
///       <arg name="attrs" type="array" default="array()" brief="массив значений" />
///     </args>
///     <body>
  static public function Budget(array $attrs = array()) { return new Service_Google_AdWords_Budget($attrs); }
///     </body>
///   </method>

///   <method name="LanguageTarget" returns="Service.Google.Adwords.LanguageTarget" scope="class">
///     <brief>Фабричный метод, возвращает объект класса Service.Google.Adwords.LanguageTarget</brief>
///     <args>
///       <arg name="attrs" type="array" default="array()" brief="массив значений" />
///     </args>
///     <body>
  static public function LanguageTarget(array $attrs = array()) { return new Service_Google_AdWords_LanguageTarget($attrs); }
///     </body>
///   </method>

///   <method name="NetworkTarget" returns="Service.Google.Adwords.NetworkTarget" scope="class">
///     <brief>Фабричный метод, возвращает объект класса Service.Google.Adwords.NetworkTarget</brief>
///     <args>
///       <arg name="attrs" type="array" default="array()" brief="массив значений" />
///     </args>
///     <body>
  static public function NetworkTarget(array $attrs = array()) { return new Service_Google_AdWords_NetworkTarget($attrs); }
///     </body>
///   </method>

///   <method name="AdScheduleTarget" returns="Service.Google.Adwords.AdScheduleTarget" scope="class">
///     <brief>Фабричный метод, возвращает объект класса Service.Google.Adwords.AdScheduleTarget</brief>
///     <args>
///       <arg name="attrs" type="array" default="array()" brief="массив значений" />
///     </args>
///     <body>
  static public function AdScheduleTarget(array $attrs = array()) { return new Service_Google_AdWords_AdScheduleTarget($attrs); }
///     </body>
///   </method>

///   <method name="CityTarget" returns="Service.Google.Adwords.CityTarget" scope="class">
///     <brief>Фабричный метод, возвращает объект класса Service.Google.Adwords.CityTarget</brief>
///     <args>
///       <arg name="attrs" type="array" default="array()" brief="массив значений" />
///     </args>
///     <body>
  static public function CityTarget(array $attrs = array()) { return new Service_Google_AdWords_CityTarget($attrs); }
///     </body>
///   </method>

///   <method name="GeoTarget" returns="Service.Google.Adwords.GeoTarget" scope="class">
///     <brief>Фабричный метод, возвращает объект класса Service.Google.Adwords.GeoTarget</brief>
///     <args>
///       <arg name="attrs" type="array" default="array()" brief="массив значений" />
///     </args>
///     <body>
  static public function GeoTarget(array $attrs = array()) { return new Service_Google_AdWords_GeoTarget($attrs); }
///     </body>
///   </method>

///   <method name="CountryTarget" returns="Service.Google.Adwords.CountryTarget" scope="class">
///     <brief>Фабричный метод, возвращает объект класса Service.Google.Adwords.CountryTarget</brief>
///     <args>
///       <arg name="attrs" type="array" default="array()" brief="массив значений" />
///     </args>
///     <body>
  static public function CountryTarget(array $attrs = array()) { return new Service_Google_AdWords_CountryTarget($attrs); }
///     </body>
///   </method>

///   <method name="MetroTarget" returns="Service.Google.Adwords.MetroTarget" scope="class">
///     <brief>Фабричный метод, возвращает объект класса Service.Google.Adwords.MetroTarget</brief>
///     <args>
///       <arg name="attrs" type="array" default="array()" brief="массив значений" />
///     </args>
///     <body>
  static public function MetroTarget(array $attrs = array()) { return new Service_Google_AdWords_MetroTarget($attrs); }
///     </body>
///   </method>

///   <method name="ProximityTarget" returns="Service.Google.Adwords.ProximityTarget" scope="class">
///     <brief>Фабричный метод, возвращает объект класса Service.Google.Adwords.ProximityTarget</brief>
///     <args>
///       <arg name="attrs" type="array" default="array()" brief="массив значений" />
///     </args>
///     <body>
  static public function ProximityTarget(array $attrs = array()) { return new Service_Google_AdWords_ProximityTarget($attrs); }
///     </body>
///   </method>

///   <method name="AdGroup" returns="Service.Google.Adwords.AdGroup" scope="class">
///     <brief>Фабричный метод, возвращает объект класса Service.Google.Adwords.AdGroup</brief>
///     <args>
///       <arg name="attrs" type="array" default="array()" brief="массив значений" />
///     </args>
///     <body>
  static public function AdGroup(array $attrs = array()) { return new Service_Google_AdWords_AdGroup($attrs); }
///     </body>
///   </method>

///   <method name="Ad" returns="Service.Google.Adwords.Ad" scope="class">
///     <brief>Фабричный метод, возвращает объект класса Service.Google.Adwords.Ad</brief>
///     <args>
///       <arg name="attrs" type="array" default="array()" brief="массив значений" />
///     </args>
///     <body>
  static public function Ad(array $attrs = array()) { return new Service_Google_AdWords_Ad($attrs); }
///     </body>
///   </method>

///   <method name="TextAd" returns="Service.Google.Adwords.Ad" scope="class">
///     <brief>Фабричный метод, возвращает объект класса Service.Google.Adwords.Ad</brief>
///     <args>
///       <arg name="attrs" type="array" default="array()" brief="массив значений" />
///     </args>
///     <body>
  static public function TextAd(array $attrs = array()) { return new Service_Google_AdWords_TextAd($attrs); }
///     </body>
///   </method>

///   <method name="AdGroupAd" returns="Service.Google.Adwords.AdGroupAd" scope="class">
///     <brief>Фабричный метод, возвращает объект класса Service.Google.Adwords.Ad</brief>
///     <args>
///       <arg name="attrs" type="array" default="array()" brief="массив значений" />
///     </args>
///     <body>
  static public function AdGroupAd(array $attrs = array()) { return new Service_Google_AdWords_AdGroupAd($attrs); }
///     </body>
///   </method>

///   <method name="AdGroupCriterion" returns="Service.Google.Adwords.AdGroupAd" scope="class">
///     <brief>Фабричный метод, возвращает объект класса Service.Google.Adwords.Ad</brief>
///     <args>
///       <arg name="attrs" type="array" default="array()" brief="массив значений" />
///     </args>
///     <body>
  static public function AdGroupCriterion(array $attrs = array()) { return new Service_Google_AdWords_AdGroupCriterion($attrs); }
///     </body>
///   </method>

///   <method name="Criterion" returns="Service.Google.Adwords.AdGroupAd" scope="class">
///     <brief>Фабричный метод, возвращает объект класса Service.Google.Adwords.Ad</brief>
///     <args>
///       <arg name="attrs" type="array" default="array()" brief="массив значений" />
///     </args>
///     <body>
  static public function Criterion(array $attrs = array()) { return new Service_Google_AdWords_Criterion($attrs); }
///     </body>
///   </method>

///   <method name="Image" returns="Service.Google.Adwords.Image" scope="class">
///     <brief>Фабричный метод, возвращает объект класса Service.Google.Adwords.Image</brief>
///     <args>
///       <arg name="attrs" type="array" default="array()" brief="массив значений" />
///     </args>
///     <body>
  static public function Image(array $attrs = array()) { return new Service_Google_AdWords_Image($attrs); }
///     </body>
///   </method>

///   <method name="Video" returns="Service.Google.Adwords.Image" scope="class">
///     <brief>Фабричный метод, возвращает объект класса Service.Google.Adwords.Video</brief>
///     <args>
///       <arg name="attrs" type="array" default="array()" brief="массив значений" />
///     </args>
///     <body>
  static public function Video(array $attrs = array()) { return new Service_Google_AdWords_Video($attrs); }
///     </body>
///   </method>

///   <method name="ApiError" returns="Service.Google.Adwords.ApiError" scope="class">
///     <brief>Фабричный метод, возвращает объект класса Service.Google.Adwords.ApiError</brief>
///     <args>
///       <arg name="attrs" type="array" default="array()" brief="массив значений" />
///     </args>
///     <body>
  static public function ApiError(array $attrs = array()) { return new Service_Google_AdWords_ApiError($attrs); }
///     </body>
///   </method>

///   </protocol>

}
/// </class>

/// <class name="Service.Google.AdWords.Exception" extends="Core.Exception">
class Service_Google_AdWords_Exception extends Core_Exception {
  protected $reason;
  protected $type;
  protected $attrs;
///   <protocol name="creating">

///   <method name="__construct">
///     <body>
  public function __construct($message = '', $code = null) {
    $res = explode('@', trim($message, '[] '));
    if (count($res) > 1) {
      $type_reason = explode('.', $res[0]);
      $this->type = trim($type_reason[0]);
      $this->reason = trim($type_reason[1]);
      $attrs = explode(';', $res[1]);
      if (isset($attrs[0])) $this->attrs['operand'] = $attrs[0];
      unset($attrs[0]);
      foreach ($attrs as $a) {
        $name_value = explode(':', $a);
        switch(count($name_value)) {
          case 1:
            $this->attrs[] = trim($a);
            break;
          case 2:
            $this->attrs[trim($name_value[0])] = trim($name_value[1]);
          default:
            $name = trim($name_value[0]);
            unset($name_value[0]);
            $this->attrs[$name] = implode(':', $name_value);
        }
      }
    }
    parent::__construct($message, $code);
  }
///     </body>
///   </method>

///   </protocol>
}
/// </class>

/// <class name="Service.Google.AdWords.Client">
///   <brief>Клиент для подключения к сервису</brief>
class Service_Google_AdWords_Client implements Core_PropertyAccessInterface {

  protected $auth;
  protected $headers = array();
  protected $is_sandbox  = false;
  protected $services = array();

///   <protocol name="creating">

///   <method name="__construct">
///     <brief>Конструктор</brief>
///     <args>
///       <arg name="auth" type="Service.Google.Auth.ClientLogin" />
///       <arg name="headers" type="array" defaults="array()" brief="массив заголовков" />
///     </args>
///     <body>
  public function __construct(Service_Google_Auth_ClientLogin $auth, array $headers = array()) {
    $this->auth = $auth;
    if ($auth->token)
      $headers['authToken'] = $auth->token;
    else
      throw new Service_Google_AdWords_Exception('You must login before use AdWords Service');
    $this->headers($headers);
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="configuring">

///   <method name="use_sandbox" returns="Service.Google.AdWords.Client">
///     <args>
///       <arg name="value" type="boolean" defaults="true" />
///     </args>
///     <body>
  public function use_sandbox($value = true) {
      $this->is_sandbox = $value;
      return $this;
    }
///     </body>
///   </method>

///   <method name="headers" returns="Service.Google.AdWords.Client">
///     <brief>Устанавливет заголовки</brief>
///     <args>
///       <args name="name" type="string" brief="имя заголовка" />
///       <args name="value" brief="занчение заголовка" />
///     </args>
///     <body>
  public function headers(array $headers) {
    foreach ($headers as $k => $v)
      $this->header($k, $v);
    return $this;
  }
///     </body>
///   </method>

///   <method name="header" returns="Service.Google.AdWords.Client">
///     <args>
///       <arg name="key" type="string" />
///       <arg name="value" type="string" />
///     </args>
///     <body>
  public function header($key, $value) {
    $this->headers[Core_Strings::to_camel_case($key, true)] = (string) $value;
    return $this;
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="supporting">

///   <method name="build_service">
///     <args>
///       <arg name="property" type="" />
///     </args>
///     <body>
  protected function build_service($property) {
    return Service_Google_AdWords::Service(
            $this,
            Service_Google_AdWords::wsdl_for($property, $this->is_sandbox)
    );
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="accessing">

///   <method name="__get">
///     <brief>Доступ на чтение к свойствам объекта</brief>
///     <details>
///       <dl>
///         <dt>is_sandbox</dt><dd>булевый флаг</dd>
///         <dt>headers</dt><dd>массив заголовков</dd>
///         <dt>units</dt><dd>возвращает количество затраценных units всеми использованными сервисами</dd>
///         <dt>иначе</dt><dd>возврат соответствующего сервиса</dd>
///       </dl>
///     </details>
///     <args>
///       <arg name="property" type="string" brief="имя свойтсва" />
///     </args>
///     <body>
  public function __get($property) {
    switch (true) {
      case $property == 'is_sandbox':
      case $property == 'headers':
         return $this->$property;
      case $property == 'units':
        $units = 0;
        foreach ($this->services as $s)
          $units += $s->units;
        return $units;
      case $property == 'services':
        return $this->services;
      case Core_Strings::ends_with($property, '_service') :
        $property = str_replace('_service', '', $property);
        if ($this->services[$property] == null)
          $this->services[$property] = $this->build_service($property);
        return $this->services[$property];
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
    throw new Core_ReadOnlyObjectException($this);
  }
///     </body>
///   </method>

///   <method name="__isset" returns="boolean">
///     <args>
///       <arg name="property" type="string" />
///     </args>
///     <body>
  public function __isset($property) {
    return isset($this->$property) || $property == 'units' ||
      isset($this->services[str_replace('_service', '', $property)]);
  }
///     </body>
///   </method>

///   <method name="__unset">
///     <args>
///       <arg name="property" type="string" />
///     </args>
///     <body>
  public function __unset($property) {
    throw new Core_ReadOnlyObjectException($this);
  }
///     </body>
///   </method>

///   </protocol>
}
/// </class>

/// <class name="Service.Google.AdWords.Service">
///   <implements interface="Core.PropertyAccessInterface" />
class Service_Google_AdWords_Service implements Core_PropertyAccessInterface {

  protected $wsdl;
  protected $classmap = array();
  protected $units = 0;

  protected $soap;

///   <protocol name="creating">

///   <method name="__construct">
///     <args>
///       <arg name="client" type="Service.Google.AdWords.Client" />
///       <arg name="wsdl" type="string" />
///       <arg name="classmap" type="array" default="array()" />
///       <arg name="options" type="array" default="array()" />
///     </args>
///     <body>
  public function __construct(Service_Google_AdWords_Client $client, $wsdl, $classmap = array(), $options = array()) {
    $this->classmap(array_merge(
      array(
        'Campaign', 'Budget', 'LanguageTarget',
        'NetworkTarget', 'AdScheduleTarget', 'CityTarget',
        'GeoTarget', 'CountryTarget', 'MetroTarget', 'ProximityTarget',
        'AdGroup', 'Ad', 'Image', 'Video', 'ApiError'
      ),
      $classmap
    ));
    $this->wsdl($wsdl);
    $this->setup($client);
    $this->soap = $this->build_soap_client(
      $this->wsdl,
      array_merge(array(
        'classmap' => $this->classmap,
        'trace' => true,
        'features' => SOAP_SINGLE_ELEMENT_ARRAYS), $options),
      $client->headers
    );
  }
///     </body>
///   </method>

///   <method name="setup" returns="Service.Google.AdWords.Service" access="protected">
///     <args>
///       <arg name="client" type="Service.Google.AdWords.Client" />
///     </args>
///     <body>
  protected function setup(Service_Google_AdWords_Client $client) {}
///     </body>
///   </method>

///   </protocol>

///   <protocol name="supporting">

///   <method name="build_soap_client">
///     <args>
///       <arg name="wsdl" type="" />
///       <arg name="options" type="array" defaults="array()" />
///       <arg name="headers" type="array" defaults="array()" />
///     </args>
///     <body>
  protected function build_soap_client($wsdl, $options= array(), $headers = array()) {
    $soap = Soap::Client($wsdl, $options);
    $soap->__setSoapHeaders(new SoapHeader($this->__get('ns'), 'RequestHeader', $headers));
    return $soap;
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="configuring">

///   <method name="wsdl" returns="Service.Google.AdWords.Service" access="protected">
///     <args>
///       <arg name="wsdl" type="string" />
///     </args>
///     <body>
  protected function wsdl($wsdl) {
    $this->wsdl = (string) $wsdl;
    return $this;
  }
///     </body>
///   </method>

///   <method name="classmap" returns="Service.Google.AdWords.Service" access="protected">
///     <args>
///       <arg name="classmap" type="array" />
///     </args>
///     <body>
  protected function classmap(array $mappings, $prefix = 'Service.Google.AdWords.', $default_class = 'Service.Google.AdWords.Entity') {
    foreach ($mappings as $k => $v) {
      if (is_numeric($k))
        $this->classmap[$v] = Core_Types::real_class_name_for(
          Core_Types::class_exists($class = $prefix.$v) ?
          $class :
          $default_class);
      else
        $this->classmap[$k] = $v;
    }
    return $this;
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="performing">

///   <method name="get" returns="mixed">
///     <args>
///       <arg name="selector" type="Service.Google.AdWords.Selector" default="null" />
///     </args>
///     <body>
  public function get(Service_Google_AdWords_Selector $selector = null, $selector_name = 'serviceSelector') {
    $selector = $selector ? $selector : Service_Google_AdWords::Selector()->fields(array('id', 'name'));
    try {
      $result = $this->soap->__soapCall(
        'get',
        array(array($selector_name => $selector->for_soap())),
        array(),
        array(),
        $out_headers
      );
    } catch (SoapFault $e) {
      throw new Service_Google_AdWords_Exception($e->getMessage());
    }
    $this->units += $out_headers['ResponseHeader']->units;
    return $result->rval;
  }
///     </body>
///   </method>

///   <method name="mutate" returns="mixed">
///     <args>
///       <arg name="operations" />
///     </args>
///     <body>
  public function mutate(Service_Google_AdWords_Operations $operations) {
    try {
      $result = $this->soap->__soapCall(
        'mutate',
        array(array('operations' => $operations->for_soap())),
        array(),
        array(),
        $out_headers
      );
    } catch (SoapFault $e) {
      throw new Service_Google_AdWords_Exception($e->getMessage());
    }
    $this->units += $out_headers['ResponseHeader']->units;
    return $result->rval;
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
      case 'wsdl': case 'units': case 'soap':
        return $this->$property;
      case 'ns':
        return str_replace('adwords-sandbox', 'adwords', preg_replace('{/\w+\?wsdl}', '', $this->wsdl));
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
      case 'wsdl':
        $this->$property($value);
        return $this;
      case 'units': case 'soap':
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
      case 'wsdl': case 'units': case 'soap':
        return isset($this->$property);
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
    return $this->__set($property, null);
  }
///     </body>
///   </method>

///   </protocol>
}
/// </class>

/// <class name="Service.Google.AdWords.Operations">
///   <implements interface="Core.EqualityInterface" />
///   <implements interface="Core.IndexedAccessInterface" />
///   <implements interface="IteratorAggregate" />
class Service_Google_AdWords_Operations implements Core_EqualityInterface, Core_IndexedAccessInterface, IteratorAggregate {
  protected $attrs = array();

///   <protocol name="performing">

///   <method name="add" returns="Service.Google.AdWords.Operation">
///     <args>
///       <arg name="operand" type="Service.Google.AdWords.Object" />
///     </args>
///     <body>
  public function add($operand) {
    $this->attrs[] = array('operator' => 'ADD', 'operand' => $operand);
    return $this;
  }
///     </body>
///   </method>

///   <method name="remove" returns="Service.Google.AdWords.Operation">
///     <args>
///       <arg name="operand" type="Service.Google.AdWords.Object" />
///     </args>
///     <body>
  public function remove($operand) {
    $this->attrs[] = array('operator' => 'REMOVE', 'operand' => $operand);
    return $this;
  }
///     </body>
///   </method>

///   <method name="set" returns="Service.Google.AdWords.Operation">
///     <args>
///       <arg name="operand" type="Service.Google.AdWords.Object" />
///     </args>
///     <body>
  public function set($operand) {
    $this->attrs[] = array('operator' => 'SET', 'operand' => $operand);
    return $this;
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="iterating" interface="IteratorAggregate">

///   <method name="getIterator" returns="Iterator">
///     <body>
  public function getIterator() {
    return new ArrayIterator($this->attrs);
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="supporting">

///   <method name="as_array" returns="array">
///     <body>
  public function for_soap() {
    $res = array();
    foreach ($this->attrs as $k => $v) {
      $res[$k] = array('operator' => $v['operator'], 'operand' =>
      method_exists($v['operand'], 'for_soap') ? $v['operand']->for_soap() : $v['operand']);
    }
    return $res;
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="indexing" interface="Core.IndexedPropertyAccessInterface">

///   <method name="offsetGet" returns="mixed">
///     <args>
///       <arg name="index" />
///     </args>
///     <body>
  public function offsetGet($index) {
    return $this->attrs[$index];
  }
///     </body>
///   </method>

///   <method name="offsetSet" returns="mixed">
///     <args>
///       <arg name="index" />
///       <arg name="value" />
///     </args>
///     <body>
  public function offsetSet($index, $value) {
    throw new Core_ReadOnlyIndexedPropertyException($index);
  }
///     </body>
///   </method>

///   <method name="offsetExists" returns="boolean">
///     <args>
///       <arg name="index" />
///     </args>
///     <body>
  public function offsetExists($index) {
    return isset($this->attrs[$index]);
  }
///     </body>
///   </method>

///   <method name="offsetUnset">
///     <args>
///       <arg name="index" />
///     </args>
///     <body>
  public function offsetUnset($index) {
    return $this->offsetSet($index, null);
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
    return ($to instanceof self) &&
      Core::equals($this->as_array(), $to->as_array());
  }
///     </body>
///   </method>

///</protocol>
}
/// </class>

/// <class name="Service.Google.AdWords.Object">
///   <brief>Базовый класс для soap объектов</brief>
///   <implements interface="Core.PropertyAccessInterface" />
///   <implements interface="Core.IndexedAccessInterface" />
///   <implements interface="Core.EqualityInterface" />
///   <implements interface="IteratorAggregate" />
class Service_Google_AdWords_Object implements
  Core_PropertyAccessInterface, Core_IndexedAccessInterface, Core_CallInterface, IteratorAggregate, Core_EqualityInterface {

  protected $attrs = array();

///   <protocol name="creating">

///   <method name="__construct">
///     <brief>Конструктор</brief>
///     <args>
///       <arg name="attrs" type="array" default="array()" brief="массив значений по умолчанию" />
///     </args>
///     <body>
  public function __construct(array $attrs = array()) { foreach ($attrs as $k => $v) $this->__set($k, $v); }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="iterating" interface="IteratorAggregate">

///   <method name="getIterator" returns="Iterator">
///     <brief>Возвращает итератор</brief>
///     <body>
  public function getIterator() {
    return new ArrayIterator($this->attrs);
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="accessing">

///   <method name="__get">
///     <brief>Доступ на чтение к свойствам объекта</brief>
///     <args>
///       <arg name="property" type="string" brief="имя свойтсва" />
///     </args>
///     <body>
  public function __get($property) {
    switch (true) {
      case method_exists($this, $m = "get_$property"):
        return $this->$m();
      case $this->is_date_property($property):
        return Time::DateTime($this->attrs[$this->attr_name($property)]);
      default:
        return $this->attrs[$this->attr_name($property)];
    }
  }
///     </body>
///   </method>

///   <method name="__set">
///     <brief>Доступ на запись к свойствам объекта</brief>
///     <args>
///       <arg name="property" type="string" brief="имя свойтсва" />
///       <args name="value" brief="значение" />
///     </args>
///     <body>
  public function __set($property, $value) {
    switch (true) {
      case method_exists($this, $m = "set_$property"):
        $this->$m($value);
        break;
      case ($value instanceof Time_DateTime):
        $this->attrs[$this->attr_name($property)] = $value->format(Service_Google_AdWords::FMT_DATE);
        break;
      default:
        $this->attrs[$this->attr_name($property)] = $value;
        break;
    }
    return $this;
  }
///     </body>
///   </method>

///   <method name="__isset">
///     <brief>Проверяет установленно ли свойство объекта</brief>
///     <args>
///       <arg name="property" type="string" brief="имя свойтсва" />
///     </args>
///     <body>
  public function __isset($property) {
    return isset($this->attrs[$this->attr_name($property)]);
  }
///     </body>
///   </method>

///   <method name="__unset">
///     <brief>Очищает свойство объекта</brief>
///     <args>
///       <arg name="property" type="string" brief="имя свойтсва" />
///     </args>
///     <body>
  public function __unset($property) {
    unset($this->attrs[$property]);
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="calling" >

///   <method name="__call">
///     <brief>Установка свойства объекта через вызов метода</brief>
///     <args>
///       <arg name="method" type="string" brief="имя метода" />
///       <arg name="parms" type="array" brief="массив параметров" />
///     </args>
///     <body>
  public function __call($method, $parms) {
    $this->__set($method, count($parms) > 1 ? $parms : $parms[0]);
    return $this;
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="indexing" >

///   <method name="offsetGet">
///     <brief>Индексный доступ на чтение к свойствам объекта, без CamelCase преобразование и ковертации</brief>
///     <args>
///       <arg name="name" type="string" brief="имя свойства" />
///     </args>
///     <body>
  public function offsetGet($name) { return $this->attrs[$name]; }
///     </body>
///   </method>

///   <method name="offsetSet">
///     <brief>Индексный доступ на запись к свойствам обхекта</brief>
///     <args>
///       <arg name="name" type="string" brief="имя свойства" />
///       <arg name="value"  brief="занчение" />
///     </args>
///     <body>
  public function offsetSet($name, $value) {$this->attrs[$name] = $value; return $this; }
///     </body>
///   </method>

///   <method name="offsetExists">
///     <brief>Проверяет существование свойства</brief>
///     <args>
///       <arg name="name" type="string" brief="имя свойства" />
///     </args>
///     <body>
  public function offsetExists($name) {
    return array_key_exists($name, $this->attrs);
  }
///     </body>
///   </method>

///   <method name="offsetUnset">
///     <brief>Очищает свойство объекта</brief>
///     <args>
///       <arg name="name" type="string" brief="имя свойства" />
///     </args>
///     <body>
  public function offsetUnset($name) {unset($this->attrs[$property]);}
///     </body>
///   </method>

///   </protocol>

///   <protocol name="supporting">

///   <method name="attr_name">
///     <brief>Конвертирует имя свойства</brief>
///     <args>
///       <arg name="property" type="string" brief="имя свойтсва" />
///     </args>
///     <body>
  protected function attr_name($property) {
    return Core_Strings::to_camel_case($property, true);
  }
///     </body>
///   </method>

///   <method name="update_with" returns="Service.Google.AdWords.Object">
///     <args>
///       <arg name="values" />
///     </args>
///     <body>
  public function update_with($values) {
    foreach ($values as $k => $v) $this->__set($k, $v);
    return $this;
  }
///     </body>
///   </method>

///   <method name="is_date_property" returns="boolean" accessing="protected">
///     <args>
///       <arg name="property" type="string" />
///     </args>
///     <body>
  protected function is_date_property($property) {
    return Core_Strings::ends_with($property, 'date');
  }
///     </body>
///   </method>

///   <method name="to_array">
///     <brief>Преобразует Service.Google.AdWords.Object в массив для soap</brief>
///     <body>
  public function for_soap() {
    return $this->as_array();
  }
///     </body>
///   </method>

///   <method name="to_array">
///     <brief>Преобразует Service.Google.AdWords.Object в массив с учетом вложенных массивов</brief>
///     <body>
  public function as_array() {
    $result = array();
    foreach ($this as $k => $v)
      $result[$k] = $this->convert_element($v);
    return $result;
  }
///     </body>
///   </method>

///   <method name="convert_element">
///     <args>
///       <arg name="element" />
///     </args>
///     <body>
  protected function convert_element($element) {
    switch (true) {
      case method_exists($element, 'as_array'):
        return $element->as_array();
      case is_array($element):
        return Core::with(new Service_Google_AdWords_Object($element))->as_array();
      default:
        return $element;
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
    return( $to instanceof self) &&
      Core::equals($this->as_array(), $to->as_array());
  }
///     </body>
///   </method>
///</protocol>
}
/// </class>

/// <class name="Service.Google.AdWords.Selector" extends="Service.Google.AdWords.Object">
class Service_Google_AdWords_Selector extends Service_Google_AdWords_Object {

	public function fields(array $data = array()) {
		foreach ($data as &$v) $v = Core_Strings::capitalize($v);
		return $this->__call('fields', array($data));
	}

}
/// </class>

/// <class name="Service.Google.AdWords.Entity" extends="Service.Google.AdWords.Object">
class Service_Google_AdWords_Entity extends Service_Google_AdWords_Object {

  protected $type;

///   <protocol name="creating">

///   <method name="__construct">
///     <args>
///       <arg name="attrs" type="array" defaults="array()" />
///     </args>
///     <body>
  public function __construct(array $attrs = array(), $type = '') {
    if ($type) $this->type = $type;
    parent::__construct($attrs);
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="accessing">

///   <method name="get___value" returns="SoapVar">
///     <body>
  protected function get___value() {
    return new SoapVar($this->as_array(), 0, $this->get___type(), Service_Google_AdWords::NS);
  }
///     </body>
///   </method>

///   <method name="get___type" returns="string">
///     <body>
  public function get___type() {
    return $this->type ? $this->type : $this->wsdl_type();
  }
///     </body>
///   </method>

///   <method name="set___type">
///     <args>
///       <arg name="value" />
///     </args>
///     <body>
  public function set___type($value) {
    $this->type = (string) $value;
    return $this;
  }
///     </body>
///   </method>

///   <method name="wsdl_type">
///     <body>
  protected function wsdl_type() {
    preg_match('{_([^_]+)$}', get_class($this), $m);
    return $m[1];
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="supporting">

///   <method name="for_soap">
///     <body>
  public function for_soap() {
    return $this->__value;
  }
///     </body>
///   </method>

///   <method name="convert_element">
///     <body>
  protected function convert_element($element) {
    switch (true) {
      case $element instanceof self:
        return $element->__value;
      case method_exists($element, 'as_array'):
        return $element->as_array();
      case is_array($element):
        return Core::with(new Service_Google_AdWords_Entity($element))->as_array();
      default:
        return $element;
    }
  }
///     </body>
///   </method>

///   </protocol>
}
/// </class>

/// <class name="Service.Google.AdWords.DateRange" extends="Service.Google.AdWords.Entity" >
class Service_Google_AdWords_DateRange extends Service_Google_AdWords_Entity {
  protected $attrs = array(
    'min' => null,
    'max' => null
  );

///   <protocol name="creating">

///   <method name="__construct">
///     <args>
///       <arg name="min" type="Time_DateTime" />
///       <arg name="max" type="TimeDateTime" />
///     </args>
///     <body>
  public function __construct(Time_DateTime $min, Time_DateTime $max) {
    $this->set_min($min)->set_max($max);
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="accessing">

///   <method name="get_max" returns="Time.DateTime">
///     <body>
  protected function get_max() {
    return Time::DateTime($this->attrs['max']);
  }
///     </body>
///   </method>

///   <method name="get_min" returns="Time.DateTime">
///     <body>
  protected function get_min() {
    return Time::DateTime($this->attrs['min']);
  }
///     </body>
///   </method>

///   <method name="set_max" returns="Time.DateTime">
///     <args>
///       <arg name="value" type="Time.DateTime" />
///     </args>
///     <body>
  protected function set_max(Time_DateTime $value) {
    $this->attrs['max'] = $value->format(Service_Google_Adwords::FMT_DATE);
    return $this;
  }
///     </body>
///   </method>

///   <method name="set_min" returns="Time.DateTime">
///     <args>
///       <arg name="value" type="Time.DateTime" />
///     </args>
///     <body>
  protected function set_min(Time_DateTime $value) {
    $this->attrs['min'] = $value->format(Service_Google_Adwords::FMT_DATE);
    return $this;
  }
///     </body>
///   </method>

///   </protocol>
}
/// </class>

/// <class name="Service.Google.AdWords.Campaign" extends="Service.Google.AdWords.Entity" >
class Service_Google_AdWords_Campaign extends Service_Google_AdWords_Entity {
}
/// </class>

/// <class name="Service.Google.AdWords.Budget" extends="Service.Google.AdWords.Entity" >
class Service_Google_AdWords_Budget extends Service_Google_AdWords_Entity {
}
/// </class>

/// <class name="Service.Google.AdWords.LanguageTarget" extends="Service.Google.AdWords.Entity" >
class Service_Google_AdWords_LanguageTarget extends Service_Google_AdWords_Entity {
}
/// </class>

/// <class name="Service.Google.AdWords.NetworkTarget" extends="Service.Google.AdWords.Entity" >
class Service_Google_AdWords_NetworkTarget extends Service_Google_AdWords_Entity {
}
/// </class>

/// <class name="Service.Google.AdWords.AdScheduleTarget" extends="Service.Google.AdWords.Entity" >
class Service_Google_AdWords_AdScheduleTarget extends Service_Google_AdWords_Entity {
}
/// </class>

/// <class name="Service.Google.AdWords.CityTarget" extends="Service.Google.AdWords.Entity" >
class Service_Google_AdWords_CityTarget extends Service_Google_AdWords_Entity {
}
/// </class>

/// <class name="Service.Google.AdWords.GeoTarget" extends="Service.Google.AdWords.Entity" >
class Service_Google_AdWords_GeoTarget extends Service_Google_AdWords_Entity {
}
/// </class>

/// <class name="Service.Google.AdWords.CountryTarget" extends="Service.Google.AdWords.Entity" >
class Service_Google_AdWords_CountryTarget extends Service_Google_AdWords_Entity {
}
/// </class>

/// <class name="Service.Google.AdWords.MetroTarget" extends="Service.Google.AdWords.Entity" >
class Service_Google_AdWords_MetroTarget extends Service_Google_AdWords_Entity {
}
/// </class>

/// <class name="Service.Google.AdWords.ProximityTarget" extends="Service.Google.AdWords.Entity" >
class Service_Google_AdWords_ProximityTarget extends Service_Google_AdWords_Entity {
}
/// </class>

/// <class name="Service.Google.AdWords.AdGroup" extends="Service.Google.AdWords.Entity" >
class Service_Google_AdWords_AdGroup extends Service_Google_AdWords_Entity {
}
/// </class>

/// <class name="Service.Google.AdWords.Ad" extends="Service.Google.AdWords.Entity" >
class Service_Google_AdWords_Ad extends Service_Google_AdWords_Entity {
}
/// </class>

/// <class name="Service.Google.AdWords.TextAd" extends="Service.Google.AdWords.Entity" >
class Service_Google_AdWords_TextAd extends Service_Google_AdWords_Entity {
}
/// </class>

/// <class name="Service.Google.AdWords.AdGroupAd" extends="Service.Google.AdWords.Entity" >
class Service_Google_AdWords_AdGroupAd extends Service_Google_AdWords_Entity {
}
/// </class>

/// <class name="Service.Google.AdWords.AdGroupCriterion" extends="Service.Google.AdWords.Entity" >
class Service_Google_AdWords_AdGroupCriterion extends Service_Google_AdWords_Entity {
}
/// </class>

/// <class name="Service.Google.AdWords.Criterion" extends="Service.Google.AdWords.Entity" >
class Service_Google_AdWords_Criterion extends Service_Google_AdWords_Entity {
}
/// </class>

/// <class name="Service.Google.AdWords.Image" extends="Service.Google.AdWords.Entity">
///   <brief>Класс для маппинга</brief>
class Service_Google_AdWords_Image extends Service_Google_AdWords_Entity {
}
/// </class>

/// <class name="Service.Google.AdWords.Video" extends="Service.Google.AdWords.Entity">
///   <brief>Класс для маппинга</brief>
class Service_Google_AdWords_Video extends Service_Google_AdWords_Entity {
}
/// </class>

/// <class name="Service.Google.AdWords.ApiError" extends="Service.Google.AdWords.Entity">
///   <brief>Класс для маппинга</brief>
class Service_Google_AdWords_ApiError extends Service_Google_AdWords_Entity {
}
/// </class>
/// </module>
