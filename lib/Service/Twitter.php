<?php
/// <module name="Service.Twitter" maintainer="svistunov@techart.ru" version="0.1.0">
Core::load('Service.OAuth', 'CLI.Application');

/// <class name="Service.Twitter" stereotype="module">
///   <implements interface="Core.ModuleInterface" />
class Service_Twitter implements Core_ModuleInterface, CLI_RunInterface {
///   <constants>
  const VERSION = '0.1.0';
///   </constants>

///   <protocol name="performing">

///   <method name="main" scope="class" returns="int">
///     <body>
  static public function main(array $argv) {
    return Core::with(new Service_Twitter_Application())->main($argv);
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="building">

///   <method name="Entity">
///     <args>
///       <arg name="values" type="array" defaults="array()" />
///     </args>
///     <body>
  public static function Entity(array $values = array()) {
    return new Service_Twitter_Entity($values);
  }
///     </body>
///   </method>

///   <method name="API">
///     <args>
///       <arg name="client" type="Service.OAuth.Client" />
///     </args>
///     <body>
  public static function API(Service_OAuth_Client $client) {
    return new Service_Twitter_API($client);
  }
///     </body>
///   </method>

///   </protocol>
}
/// </class>

/// <class name="Service.Twitter.Exception" extends="Core.Exception" >
class Service_Twitter_Exception extends Core_Exception {}
/// </class>

/// <class name="Service.Twitter.RequiredParmException" extends="Service.Twitter.Exception">
class Service_Twitter_RequiredParmException extends Service_Twitter_Exception {
  protected $arg_name;

///   <protocol name="creating">

///   <method name="__construct">
///     <args>
///       <arg name="arg_name" type="string" />
///     </args>
///     <body>
  public function __construct($arg_name) {
    $this->arg_name = (string) $arg_name;
    parent::__construct("Missing argument: $this->arg_name");
  }
///     </body>
///   </method>

///   </protocol>
}
/// </class>

/// <class name="Service.Twitter.Application" extends="CLI.Application.Base" >
class Service_Twitter_Application extends CLI_Application_Base {
  protected $client;
  protected $store;

  const VERSION = '0.1.0';

///   <protocol name="performing" >

///   <method name="run" returns="int">
///     <args>
///       <arg name="argv" type="array" />
///     </args>
///     <body>
  public function run(array $argv) {
    $this->build_client();

    if ($this->client->is_logged_in()) {
      if ($this->config->message) {
        $this->send_message();
      }
      else
        print("\nYou alredy have access token. If you need new, please, clear store.\n");
      return 0;
    }
    $this->get_token();
    return 0;
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="supporting">

///   <method name="get_token" access="protected">
///     <body>
  protected function get_token() {
    $this->client->get_request_token();
    $this->client->save_token($k = 'request_token');
    $this->log->debug('Get and save request token: %s',
      var_export($this->store->get($k), true));

    printf("\nGo to:\n%s\nGet PIN and enter:\n", $this->client->get_auth_url(true));
    $verifier = trim(IO::stdin()->read());
    $this->log->debug('You enter PIN: %d', $verifier);

    $this->client->get_access_token($verifier);
    $this->client->save_token($k = 'access_token');
    $this->log->debug('Get and save access token: %s',
      var_export($this->store->get($k), true));
  }
///     </body>
///   </method>

///   <method name="send_message" access="protected">
///     <body>
  protected function send_message() {
    print "\nSend message ...\n";
    $r = $this->client->send(
      Net_HTTP::Request('http://api.twitter.com/1.1/statuses/update.json')->
      parameters(array('status' => $this->config->message))->
      method('POST')
    );
    printf('You message posted on twitter: %s', var_export(json_decode($r->body), true));
  }
///     </body>
///   </method>

///   <method name="build_client" access="protected">
///     <body>
  protected function build_client() {
    $this->store = Cache::connect($this->config->store_dsn, $this->config->store_to);
    $this->client = Service_OAuth::Client($this->store)->
      consumer_key($this->config->consumer_key)->
      consumer_secret($this->config->consumer_secret)->
      request_token_url($this->config->request_token_url)->
      access_token_url($this->config->access_token_url)->
      authorize_url($this->config->authorize_url);
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="configuring" >

///   <method name="setup" access="protected">
///     <body>
  protected function setup() {
    $this->options->
      brief('Service.Twitter '.Service_Twitter_Application::VERSION.': Twitter')->
       string_option('config_file', '-c', '--config',  'Use configuration file')->
       string_option('store_dns', '-d', '--dns', 'Cache dns string')->
       int_option('store_to', '-t', '--timeout', 'Cache timeout')->
       string_option('consumer_key', '-k', '--key', 'Consumer key')->
       string_option('consumer_secret', '-s', '--secret', 'Consumer secret')->
       string_option('request_token_url', '-r', '--rtoken', 'Request token url')->
       string_option('access_token_url', '-a', '--atoken', 'Access token url')->
       string_option('authorize_url', '-o', '--authurl', 'Authorize url')->
       string_option('message', '-m', '--message', 'Sendign message');

    $this->config->message = '';
    $this->config->store_dns = '';
    $this->config->config_file = '';
    $this->config->store_to = 0;// 60*60*24*365*10;
    $this->config->consumer_key = '';
    $this->config->consumer_secret = '';
    $this->config->request_token_url = 'https://twitter.com/oauth/request_token';
    $this->config->access_token_url = 'https://twitter.com/oauth/access_token';
    $this->config->authorize_url = 'https://twitter.com/oauth/authorize';

    $this->log->dispatcher->to_stream(IO::stderr());
  }
///     </body>
///   </method>

///   <method name="configure" access="protected">
///     <body>
  protected function configure() {
    if ($this->config->config_file) {
      $this->load_config($this->config->config_file);
    }
  }
///     </body>
///   </method>

///   </protocol>
}
/// </class>

/// <class name="Service.Twitter.API">
///   <implements interface="Core.PropertyAccessInterface" />
class Service_Twitter_API implements Core_PropertyAccessInterface {
  protected $client;
  protected $services = array();
  protected $registered_services = array();
  ///   <protocol name="creating">

///   <method name="__construct">
///     <args>
///       <arg name="client" type="Service.OAuth.Client" />
///     </args>
///     <body>
  public function __construct(Service_OAuth_Client $client) {
    if (!$client->is_logged_in())
      throw new Service_Twitter_Exception('You must login');
    $this->client = $client;
    $this->register_services(array(
      'statuses' => 'Service_Twitter_StatusService',
      'users' => 'Service_Twitter_UsersService',
      'lists' => 'Service_Twitter_ListsService',
      'friendships' => 'Service_Twitter_FriendshipsService'
    ));
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="supporting">

///   <method name="register_services">
///     <args>
///       <arg name="values" type="array" />
///     </args>
///     <body>
  public function register_services(array $values) {
    foreach ($values as $name => $class) {
      $this->registered_services[$name] = $class;
    }
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
    switch (true) {
      case isset($this->services[$property]):
        return $this->services[$property];
      case isset($this->registered_services[$property]):
        return $this->services[$property] =
          Core::make($this->registered_services[$property], $this->client);
      case in_array($property, array('client', 'services')):
        return $this->$property;
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
    return isset($this->$propery) || isset($this->services[$property]);
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

/// <class name="Service.Twitter.Entity" >
class Service_Twitter_Entity implements Core_PropertyAccessInterface, Core_CallInterface {
  protected $values = array();

///   <protocol name="creating">

///   <method name="__construct">
///     <args>
///       <arg name="values" type="array" defaults="array()" />
///     </args>
///     <body>
  public function __construct(array $values = array()) {
    $this->values = $values;
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="supporting">

///   <method name="is_date" access="protected" returns="boolean">
///     <args>
///       <arg name="property" type="" />
///     </args>
///     <body>
  protected function is_date($property) {
    return Core_Strings::ends_with($property, '_at');
  }
///     </body>
///   </method>

///   <method name="as_array" returns="array">
///     <body>
  public function as_array() {
    return $this->values;
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
    if (!isset($this->values[$property]))
      throw new Core_MissingPropertyException($property);
    switch (true) {
      case is_array($this->values[$property]):
        return new self($this->values[$property]);
      case $this->is_date($property):
        return Time::DateTime($this->values[$property]);
      default:
        return $this->values[$property];
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
    switch (true) {
      case $value instanceof self:
        $this->values[$property] = $value->as_array();
        return $this;
      case $value instanceof Time_DateTime:
        $this->values[$property] = $value->timestamp;
        return $this;
      default:
        $this->values[$property] = $value;
        return $this;
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
    return isset($this->values[$property]);
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

///   <protocol name="calling">

///   <method name="__call">
///     <args>
///       <arg name="method" type="string" />
///       <arg name="args" type="array" />
///     </args>
///     <body>
  public function __call($method, $args) {
    $parms = count($args) > 1 ? $args : $args[0];
    $this->__set($method, $parms);
    return $this;
  }
///     </body>
////  </method>

///   </protocol>

///   <protocol name="indexing" interface="Core.IndexedPropertyAccessInterface">

///   <method name="offsetGet" returns="mixed">
///     <args>
///       <arg name="index" />
///     </args>
///     <body>
  public function offsetGet($index) {
    return $this->values[$index];
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
    $this->values[$index] = $value;
    return $this;
  }
///     </body>
///   </method>

///   <method name="offsetExists" returns="boolean">
///     <args>
///       <arg name="index" />
///     </args>
///     <body>
  public function offsetExists($index) {
    return isset($this->values[$index]);
  }
///     </body>
///   </method>

///   <method name="offsetUnset">
///     <args>
///       <arg name="index" />
///     </args>
///     <body>
  public function offsetUnset($index) {
    unset($this->values[$index]);
    return $this;
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="iterating" interface="IteratorAggregate">

///   <method name="getIterator" returns="Iterator">
///     <body>
  public function getIterator() {
    return new ArrayIterator($this->values);
  }
///     </body>
///   </method>

///   </protocol>
}
/// </class>

/// <class name="Service.Twitter.Service" stereotype="abstract">
abstract class Service_Twitter_Service {
  protected $client;
  const PREFIX = 'http://api.twitter.com/1.1/';

  ///   <protocol name="creating">

///   <method name="__construct">
///     <body>
  public function __construct(Service_OAuth_Client $client) {
    $this->client = $client;
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="supporting">

///   <method name="call" returns="array">
///     <args>
///       <arg name="url" type="string" />
///       <arg name="method" type="string" defautls="'GET'" />
///       <arg name="parameters" type="array" defaults="array()" />
///     </args>
///     <body>
  public function call($url, $method = 'GET', $parameters = array(), $decode = true) {
    switch (true) {
      case $parameters instanceof Service_Twitter_Entity:
        $parameters = $parameters->as_array();
        break;
      default:
        $parameters = (array) $parameters;
        break;
    }
    try {
      $r =  $this->client->send(
        Net_HTTP::Request(self::PREFIX.$url.'.json')->
          parameters($parameters)->
          method($method)
          );
      if ($decode)
        return new Service_Twitter_Entity((array) json_decode($r->body, true));
      else
        return $r;
    } catch (SoapFault $e) {
      throw new Service_Twitter_Exception($e->getMessage());
    }
  }
///     </body>
///   </method>

///   <method name="extract_id">
///     <args>
///       <arg name="parms"/>
///     </args>
///     <body>
  protected function extract_value($name, &$parms) {
    if (is_scalar($parms)) {
      $value = $parms;
      $parms = array();
    }
    else {
      $value = $parms[$name];
      unset($parms[$name]);
    }
    if (!$value) throw new Service_Twitter_RequiredParmException($name);
    return $value;
  }
///     </body>
///   </method>

///   </protocol>
}
/// </class>

/// <class name="Service.Twitter.StatusService" extends="Service.Twitter.Service">
class Service_Twitter_StatusService extends Service_Twitter_Service {
///   <protocol name="performing">

///   <method name="update">
///     <args>
///       <arg name="status" type="" />
///     </args>
///     <body>
  public function update($status) {
    if (is_string($status)) $parms = array('status' => $status);
    return $this->call('statuses/update', 'POST', $parms);
  }
///     </body>
///   </method>

///   <method name="show">
///     <args>
///       <arg name="parms" type="type" />
///     </args>
///     <body>
  public function show($parms) {
    $id = $this->extract_value('id', $parms);
    return $this->call("statuses/show/$id", 'GET', $parms);
  }
///     </body>
///   </method>

///   <method name="destroy">
///     <args>
///       <arg name="parms" />
///     </args>
///     <body>
  public function destroy($parms) {
    $id = $this->extract_value('id', $parms);
    return $this->call("statuses/destroy/$id", 'POST', $parms);
  }
///     </body>
///   </method>

///   <method name="retweet">
///     <args>
///       <arg name="parms" />
///     </args>
///     <body>
  public function retweet($parms) {
    $id = $this->extract_value('id', $parms);
    return $this->call("statuses/retweet/$id", 'POST', $parms);
  }
///     </body>
///   </method>

///   <method name="retweets">
///     <args>
///       <arg name="parms" />
///     </args>
///     <body>
  public function retweets($parms) {
    $id = $this->extract_value('id', $parms);
    return $this->call("statuses/retweets/$id", 'POST', $parms);
  }
///     </body>
///   </method>

///   <method name="retweeted_by">
///     <args>
///       <arg name="parms" />
///     </args>
///     <body>
  public function retweeted_by($parms) {
    $id = $this->extract_value('id', $parms);
    return $this->call("statuses/$id/retweeted_by", 'GET', $parms);
  }
///     </body>
///   </method>

///   <method name="retweeted_by_ids">
///     <args>
///       <arg name="parms" />
///     </args>
///     <body>
  public function retweeted_by_ids($parms) {
    $id = $this->extract_value('id', $parms);
    return $this->call("statuses/$id/retweeted_by/ids", 'GET', $parms);
  }
///     </body>
///   </method>

///   <method name="public_timeline">
///     <args>
///       <arg name="parms" type="array()" />
///     </args>
///     <body>
  public function public_timeline($parms = array()) {
    return $this->call('statuses/public_timeline', 'GET', $parms);
  }
///     </body>
///   </method>

///   <method name="home_timeline">
///     <args>
///       <arg name="parms" type="array()" />
///     </args>
///     <body>
  public function home_timeline($parms = array()) {
    return $this->call('statuses/home_timeline', 'GET', $parms);
  }
///     </body>
///   </method>

///   <method name="friends_timeline">
///     <args>
///       <arg name="parms" type="array()" />
///     </args>
///     <body>
  public function friends_timeline($parms = array()) {
    return $this->call('statuses/friends_timeline', 'GET', $parms);
  }
///     </body>
///   </method>

///   <method name="user_timeline">
///     <args>
///       <arg name="parms" type="array()" />
///     </args>
///     <body>
  public function user_timeline($parms = array()) {
    return $this->call('statuses/user_timeline', 'GET', $parms);
  }
///     </body>
///   </method>

///   <method name="mentions">
///     <args>
///       <arg name="parms" type="array()" />
///     </args>
///     <body>
  public function mentions($parms = array()) {
    return $this->call('statuses/mentions', 'GET', $parms);
  }
///     </body>
///   </method>

///   <method name="retweeted_by_me">
///     <args>
///       <arg name="parms" type="array()" />
///     </args>
///     <body>
  public function retweeted_by_me($parms = array()) {
    return $this->call('statuses/retweeted_by_me', 'GET', $parms);
  }
///     </body>
///   </method>

///   <method name="retweeted_to_me">
///     <args>
///       <arg name="parms" type="array()" />
///     </args>
///     <body>
  public function retweeted_to_me($parms = array()) {
    return $this->call('statuses/retweeted_to_me', 'GET', $parms);
  }
///     </body>
///   </method>

///   <method name="retweeted_of_me">
///     <args>
///       <arg name="parms" type="array()" />
///     </args>
///     <body>
  public function retweeted_of_me($parms = array()) {
    return $this->call('statuses/retweeted_of_me', 'GET', $parms);
  }
///     </body>
///   </method>

///   </protocol>
}
/// </class>

/// <class name="Service.Twitter.UsersService" extends="Service.Twitter.Service">
class Service_Twitter_UsersService extends Service_Twitter_Service {
///   <protocol name="performing">

///   <method name="show">
///     <args>
///       <arg name="parms" type="array()" />
///     </args>
///     <body>
  public function show($parms = array()) {
    if (is_int($parms)) $parms = array('user_id' => $parms);
    if (is_string($parms)) $parms = array('screen_name' => $parms);
    return $this->call('users/show', 'GET', $parms);
  }
///     </body>
///   </method>

///   <method name="lookup">
///     <args>
///       <arg name="parms" type="array()" />
///     </args>
///     <body>
  public function lookup($parms = array()) {
    if (is_int($parms)) $parms = array('user_id' => $parms);
    if (is_string($parms)) $parms = array('screen_name' => $parms);
    return $this->call('users/lookup', 'GET', $parms);
  }
///     </body>
///   </method>

///   <method name="search">
///     <args>
///       <arg name="parms" type="array()" />
///     </args>
///     <body>
  public function search($parms = array()) {
    if (is_string($parms)) $parms = array('q' => $parms);
    return $this->call('users/search', 'GET', $parms);
  }
///     </body>
///   </method>

///   <method name="suggestions">
///     <args>
///       <arg name="parms" type="array()" />
///     </args>
///     <body>
  public function suggestions($parms = array()) {
    return $this->call('users/suggestions', 'GET', $parms);
  }
///     </body>
///   </method>

///   <method name="suggestions_slug">
///     <args>
///       <arg name="parms" type="array()" />
///     </args>
///     <body>
  public function suggestions_slug($parms = array()) {
    $slug = $this->extract_value('slug', $parms);
    return $this->call('users/suggestions/'.$slug, 'GET', $parms);
  }
///     </body>
///   </method>

///   <method name="profile_image">
///     <args>
///       <arg name="parms" type="array()" />
///     </args>
///     <body>
  public function profile_image($parms = array()) {
    $name = $this->extract_value('screen_name', $parms);
    return $this->call('users/profile_image/'.$name, 'GET', $parms, false);
  }
///     </body>
///   </method>

///   <method name="friends">
///     <args>
///       <arg name="parms" type="array()" />
///     </args>
///     <body>
  public function friends($parms = array()) {
    return $this->call('statuses/friends', 'GET', $parms);
  }
///     </body>
///   </method>

///   <method name="followers">
///     <args>
///       <arg name="parms" type="array()" />
///     </args>
///     <body>
  public function followers($parms = array()) {
    return $this->call('statuses/followers', 'GET', $parms);
  }
///     </body>
///   </method>

///   </protocol>
}
///   </class>

/// <class name="Service.Twitter.ListsService" extends="Service.Twitter.Service">
class Service_Twitter_ListsService extends Service_Twitter_Service {
///   <protocol name="support">

///   <method name="extract_user">
///     <args>
///       <arg name="parms" />
///     </args>
///     <body>
  protected function extract_user(&$parms, $name = 'user') {
    if (isset($parms[$name])) {
      $user = $parms[$name];
      unset($parms[$name]);
    } else
      $user = $this->client->token['screen_name'];
    return $user;
  }
///     </body>
///   </method>

///   </protocol>

///   <protocol name="performing">

///   <method name="create">
///     <args>
///       <arg name="parms" />
///     </args>
///     <body>
  public function create($parms) {
    if (is_string($parms)) $parms = array('name' => $parms);
    $user = $this->extract_user($parms);
    return $this->call($user.'/lists', 'POST', $parms);
  }
///     </body>
///   </method>

///   <method name="all">
///     <args>
///       <arg name="parms" />
///     </args>
///     <body>
  public function all($parms = array()) {
    $user = $this->extract_user($parms);
    return $this->call($user.'/lists', 'GET', $parms);
  }
///     </body>
///   </method>

///   <method name="get">
///     <args>
///       <arg name="parms"/>
///     </args>
///     <body>
  public function get($parms) {
    $id =  $this->extract_value('id', $parms);
    $user = $this->extract_user($parms);
    return $this->call($user.'/lists/'.$id, 'GET', $parms);
  }
///     </body>
///   </method>

///   <method name="update">
///     <args>
///       <arg name="parms"/>
///     </args>
///     <body>
  public function update($parms) {
    $id =  $this->extract_value('id', $parms);
    $user = $this->extract_user($parms);
    return $this->call($user.'/lists/'.$id, 'POST', $parms);
  }
///     </body>
///   </method>

///   <method name="delete">
///     <args>
///       <arg name="parms"/>
///     </args>
///     <body>
  public function delete($parms) {
    $id =  $this->extract_value('id', $parms);
    $user = $this->extract_user($parms);
    $parms['_method'] = 'DELETE';
    return $this->call($user.'/lists/'.$id, 'POST', $parms);
  }
///     </body>
///   </method>

///   <method name="statuses">
///     <args>
///       <arg name="parms"/>
///     </args>
///     <body>
  public function statuses($parms) {
    $id =  $this->extract_value('id', $parms);
    $user = $this->extract_user($parms);
    return $this->call($user.'/lists/'.$id.'/statuses', 'GET', $parms);
  }
///     </body>
///   </method>

///   <method name="memberships">
///     <args>
///       <arg name="parms"/>
///     </args>
///     <body>
  public function memberships($parms = array()) {
    $user = $this->extract_user($parms);
    return $this->call($user.'/lists/memberships', 'GET', $parms);
  }
///     </body>
///   </method>

///   <method name="subscriptions">
///     <args>
///       <arg name="parms"/>
///     </args>
///     <body>
  public function subscriptions($parms = array()) {
    $user = $this->extract_user($parms);
    return $this->call($user.'/lists/subscriptions', 'GET', $parms);
  }
///     </body>
///   </method>

///   <method name="members">
///     <args>
///       <arg name="parms"/>
///     </args>
///     <body>
  public function members($parms) {
    $id =  $this->extract_value('list_id', $parms);
    $user = $this->extract_user($parms);
    return $this->call($user.'/'.$id.'/members', 'GET', $parms);
  }
///     </body>
///   </method>

///   <method name="associate_member">
///     <args>
///       <arg name="parms"/>
///     </args>
///     <body>
  public function associate_member($parms) {
    $id =  $this->extract_value('list_id', $parms);
    $user = $this->extract_user($parms);
    return $this->call($user.'/'.$id.'/members', 'POST', $parms);
  }
///     </body>
///   </method>

///   <method name="dissociate_member">
///     <args>
///       <arg name="parms"/>
///     </args>
///     <body>
  public function dissociate_member($parms) {
    $id =  $this->extract_value('list_id', $parms);
    $user = $this->extract_user($parms);
    $parms['_method'] = 'DELETE';
    return $this->call($user.'/'.$id.'/members', 'POST', $parms);
  }
///     </body>
///   </method>

///   <method name="check_member">
///     <args>
///       <arg name="parms"/>
///     </args>
///     <body>
  public function check_member($parms) {
    $list_id =  $this->extract_value('list_id', $parms);
    $id =  $this->extract_value('id', $parms);
    $user = $this->extract_user($parms);
    return $this->call($user.'/'.$list_id.'/members/'.$id, 'GET', $parms);
  }
///     </body>
///   </method>

///   <method name="associate_members">
///     <args>
///       <arg name="parms"/>
///     </args>
///     <body>
  public function associate_members($parms) {
    $id =  $this->extract_value('list_id', $parms);
    $user = $this->extract_user($parms);
    return $this->call($user.'/'.$id.'/create_all', 'POST', $parms);
  }
///     </body>
///   </method>

///   <method name="subscribers">
///     <args>
///       <arg name="parms"/>
///     </args>
///     <body>
  public function subscribers($parms) {
    $id =  $this->extract_value('list_id', $parms);
    $user = $this->extract_user($parms);
    return $this->call($user.'/'.$id.'/subscribers', 'GET', $parms);
  }
///     </body>
///   </method>

///   <method name="subscribe">
///     <args>
///       <arg name="parms"/>
///     </args>
///     <body>
  public function subscribe($parms) {
    $id =  $this->extract_value('list_id', $parms);
    $user = $this->extract_value('user', $parms);
    return $this->call($user.'/'.$id.'/subscribers', 'POST', $parms);
  }
///     </body>
///   </method>

///   <method name="unsubscribe">
///     <args>
///       <arg name="parms"/>
///     </args>
///     <body>
  public function unsubscribe($parms) {
    $id =  $this->extract_value('list_id', $parms);
    $user = $this->extract_value('user', $parms);
    $parms['_method'] = 'DELETE';
    return $this->call($user.'/'.$id.'/subscribers', 'POST', $parms);
  }
///     </body>
///   </method>

///   <method name="check_subscriber">
///     <args>
///       <arg name="parms"/>
///     </args>
///     <body>
  public function check_subscriber($parms) {
    $list_id =  $this->extract_value('list_id', $parms);
    $user =  $this->extract_value('user', $parms);
    $id = $this->extract_user($parms, 'id');
    return $this->call($user.'/'.$list_id.'/subscribers/'.$id, 'GET', $parms);
  }
///     </body>
///   </method>

///   </protocol>
}
/// </class>

/// <class name="Service.Twitter.FriendshipsService" extends="Service.Twitter.Service">
class Service_Twitter_FriendshipsService extends Service_Twitter_Service {
///   <protocol name="performing">

///   <method name="create">
///     <args>
///       <arg name="parms" />
///     </args>
///     <body>
  public function create($parms = array()) {
    if (is_string($parms)) $parms = array('screen_name' => $parms);
    if (is_int($parms)) $parms = array('user_id' => $parms);
    return $this->call('friendships/create', 'POST', $parms);
  }
///     </body>
///   </method>

///   <method name="destroy">
///     <args>
///       <arg name="parms" />
///     </args>
///     <body>
  public function destroy($parms = array()) {
    if (is_string($parms)) $parms = array('screen_name' => $parms);
    if (is_int($parms)) $parms = array('user_id' => $parms);
    return $this->call('friendships/destroy', 'POST', $parms);
  }
///     </body>
///   </method>

///   <method name="exists">
///     <args>
///       <arg name="parms" />
///     </args>
///     <body>
  public function exists($who, $whom) {
    return $this->call('friendships/exists', 'GET',
      array('user_a' => $who, 'user_b' => $whom));
  }
///     </body>
///   </method>

///   <method name="show">
///     <args>
///       <arg name="parms" />
///     </args>
///     <body>
  public function show($who, $whom) {
    $parms = array();
    if (is_string($who)) $parms['source_screen_name'] = $who;
    if (is_string($whom)) $parms['target_screen_name'] = $whom;
    if (is_int($who)) $parms['source_id'] = $who;
    if (is_int($whom)) $parms['target_id'] = $whom;
    return $this->call('friendships/show', 'GET', $parms);
  }
///     </body>
///   </method>

///   <method name="incoming">
///     <args>
///       <arg name="parms" />
///     </args>
///     <body>
  public function incoming($parms = array()) {
    return $this->call('friendships/incoming', 'GET', $parms);
  }
///     </body>
///   </method>

///   <method name="outgoing">
///     <args>
///       <arg name="parms" />
///     </args>
///     <body>
  public function outgoing($parms = array()) {
    return $this->call('friendships/outgoing', 'GET', $parms);
  }
///     </body>
///   </method>

///   </protocol>
}
/// </class>

/// </module>
