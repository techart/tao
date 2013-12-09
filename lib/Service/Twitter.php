<?php
/**
 * Service.Twitter
 * 
 * @package Service\Twitter
 * @version 0.1.0
 */
Core::load('Service.OAuth', 'CLI.Application');

/**
 * @package Service\Twitter
 */
class Service_Twitter implements Core_ModuleInterface, CLI_RunInterface {
  const VERSION = '0.1.0';


/**
 * @return int
 */
  static public function main(array $argv) {
    return Core::with(new Service_Twitter_Application())->main($argv);
  }



/**
 * @param array $values
 */
  public static function Entity(array $values = array()) {
    return new Service_Twitter_Entity($values);
  }

/**
 * @param Service_OAuth_Client $client
 */
  public static function API(Service_OAuth_Client $client) {
    return new Service_Twitter_API($client);
  }

}

/**
 * @package Service\Twitter
 */
class Service_Twitter_Exception extends Core_Exception {}

/**
 * @package Service\Twitter
 */
class Service_Twitter_RequiredParmException extends Service_Twitter_Exception {
  protected $arg_name;


/**
 * @param string $arg_name
 */
  public function __construct($arg_name) {
    $this->arg_name = (string) $arg_name;
    parent::__construct("Missing argument: $this->arg_name");
  }

}

/**
 * @package Service\Twitter
 */
class Service_Twitter_Application extends CLI_Application_Base {
  protected $client;
  protected $store;

  const VERSION = '0.1.0';


/**
 * @param array $argv
 * @return int
 */
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



/**
 */
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

/**
 */
  protected function send_message() {
    print "\nSend message ...\n";
    $r = $this->client->send(
      Net_HTTP::Request('http://api.twitter.com/1.1/statuses/update.json')->
      parameters(array('status' => $this->config->message))->
      method('POST')
    );
    printf('You message posted on twitter: %s', var_export(json_decode($r->body), true));
  }

/**
 */
  protected function build_client() {
    $this->store = Cache::connect($this->config->store_dsn, $this->config->store_to);
    $this->client = Service_OAuth::Client($this->store)->
      consumer_key($this->config->consumer_key)->
      consumer_secret($this->config->consumer_secret)->
      request_token_url($this->config->request_token_url)->
      access_token_url($this->config->access_token_url)->
      authorize_url($this->config->authorize_url);
  }



/**
 */
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

/**
 */
  protected function configure() {
    if ($this->config->config_file) {
      $this->load_config($this->config->config_file);
    }
  }

}

/**
 * @package Service\Twitter
 */
class Service_Twitter_API implements Core_PropertyAccessInterface {
  protected $client;
  protected $services = array();
  protected $registered_services = array();
  ///   <protocol name="creating">

/**
 * @param Service_OAuth_Client $client
 */
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



/**
 * @param array $values
 */
  public function register_services(array $values) {
    foreach ($values as $name => $class) {
      $this->registered_services[$name] = $class;
    }
  }



/**
 * @param string $property
 * @return mixed
 */
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

/**
 * @param string $property
 * @param  $value
 * @return mixed
 */
  public function __set($property, $value) {
    throw new Core_ReadOnlyObjectException($this);
  }

/**
 * @param string $property
 * @return boolean
 */
  public function __isset($property) {
    return isset($this->$propery) || isset($this->services[$property]);
  }

/**
 * @param string $property
 */
  public function __unset($property) {
    throw new Core_ReadOnlyObjectException($this);
  }


}

/**
 * @package Service\Twitter
 */
class Service_Twitter_Entity implements Core_PropertyAccessInterface, Core_CallInterface {
  protected $values = array();


/**
 * @param array $values
 */
  public function __construct(array $values = array()) {
    $this->values = $values;
  }



/**
 * @param  $property
 * @return boolean
 */
  protected function is_date($property) {
    return Core_Strings::ends_with($property, '_at');
  }

/**
 * @return array
 */
  public function as_array() {
    return $this->values;
  }



/**
 * @param string $property
 * @return mixed
 */
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

/**
 * @param string $property
 * @param  $value
 * @return mixed
 */
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

/**
 * @param string $property
 * @return boolean
 */
  public function __isset($property) {
    return isset($this->values[$property]);
  }

/**
 * @param string $property
 */
  public function __unset($property) {
    return $this->__set($property, null);
  }



/**
 * @param string $method
 * @param array $args
 */
  public function __call($method, $args) {
    $parms = count($args) > 1 ? $args : $args[0];
    $this->__set($method, $parms);
    return $this;
  }



/**
 * @param  $index
 * @return mixed
 */
  public function offsetGet($index) {
    return $this->values[$index];
  }

/**
 * @param  $index
 * @param  $value
 * @return mixed
 */
  public function offsetSet($index, $value) {
    $this->values[$index] = $value;
    return $this;
  }

/**
 * @param  $index
 * @return boolean
 */
  public function offsetExists($index) {
    return isset($this->values[$index]);
  }

/**
 * @param  $index
 */
  public function offsetUnset($index) {
    unset($this->values[$index]);
    return $this;
  }



/**
 * @return Iterator
 */
  public function getIterator() {
    return new ArrayIterator($this->values);
  }

}

/**
 * @abstract
 * @package Service\Twitter
 */
abstract class Service_Twitter_Service {
  protected $client;
  const PREFIX = 'http://api.twitter.com/1.1/';

  ///   <protocol name="creating">

/**
 */
  public function __construct(Service_OAuth_Client $client) {
    $this->client = $client;
  }



/**
 * @param string $url
 * @param string $method
 * @param array $parameters
 * @return array
 */
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

/**
 * @param  $parms
 */
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

}

/**
 * @package Service\Twitter
 */
class Service_Twitter_StatusService extends Service_Twitter_Service {

/**
 * @param  $status
 */
  public function update($status) {
    if (is_string($status)) $parms = array('status' => $status);
    return $this->call('statuses/update', 'POST', $parms);
  }

/**
 * @param type $parms
 */
  public function show($parms) {
    $id = $this->extract_value('id', $parms);
    return $this->call("statuses/show/$id", 'GET', $parms);
  }

/**
 * @param  $parms
 */
  public function destroy($parms) {
    $id = $this->extract_value('id', $parms);
    return $this->call("statuses/destroy/$id", 'POST', $parms);
  }

/**
 * @param  $parms
 */
  public function retweet($parms) {
    $id = $this->extract_value('id', $parms);
    return $this->call("statuses/retweet/$id", 'POST', $parms);
  }

/**
 * @param  $parms
 */
  public function retweets($parms) {
    $id = $this->extract_value('id', $parms);
    return $this->call("statuses/retweets/$id", 'POST', $parms);
  }

/**
 * @param  $parms
 */
  public function retweeted_by($parms) {
    $id = $this->extract_value('id', $parms);
    return $this->call("statuses/$id/retweeted_by", 'GET', $parms);
  }

/**
 * @param  $parms
 */
  public function retweeted_by_ids($parms) {
    $id = $this->extract_value('id', $parms);
    return $this->call("statuses/$id/retweeted_by/ids", 'GET', $parms);
  }

/**
 * @param array() $parms
 */
  public function public_timeline($parms = array()) {
    return $this->call('statuses/public_timeline', 'GET', $parms);
  }

/**
 * @param array() $parms
 */
  public function home_timeline($parms = array()) {
    return $this->call('statuses/home_timeline', 'GET', $parms);
  }

/**
 * @param array() $parms
 */
  public function friends_timeline($parms = array()) {
    return $this->call('statuses/friends_timeline', 'GET', $parms);
  }

/**
 * @param array() $parms
 */
  public function user_timeline($parms = array()) {
    return $this->call('statuses/user_timeline', 'GET', $parms);
  }

/**
 * @param array() $parms
 */
  public function mentions($parms = array()) {
    return $this->call('statuses/mentions', 'GET', $parms);
  }

/**
 * @param array() $parms
 */
  public function retweeted_by_me($parms = array()) {
    return $this->call('statuses/retweeted_by_me', 'GET', $parms);
  }

/**
 * @param array() $parms
 */
  public function retweeted_to_me($parms = array()) {
    return $this->call('statuses/retweeted_to_me', 'GET', $parms);
  }

/**
 * @param array() $parms
 */
  public function retweeted_of_me($parms = array()) {
    return $this->call('statuses/retweeted_of_me', 'GET', $parms);
  }

}

/**
 * @package Service\Twitter
 */
class Service_Twitter_UsersService extends Service_Twitter_Service {

/**
 * @param array() $parms
 */
  public function show($parms = array()) {
    if (is_int($parms)) $parms = array('user_id' => $parms);
    if (is_string($parms)) $parms = array('screen_name' => $parms);
    return $this->call('users/show', 'GET', $parms);
  }

/**
 * @param array() $parms
 */
  public function lookup($parms = array()) {
    if (is_int($parms)) $parms = array('user_id' => $parms);
    if (is_string($parms)) $parms = array('screen_name' => $parms);
    return $this->call('users/lookup', 'GET', $parms);
  }

/**
 * @param array() $parms
 */
  public function search($parms = array()) {
    if (is_string($parms)) $parms = array('q' => $parms);
    return $this->call('users/search', 'GET', $parms);
  }

/**
 * @param array() $parms
 */
  public function suggestions($parms = array()) {
    return $this->call('users/suggestions', 'GET', $parms);
  }

/**
 * @param array() $parms
 */
  public function suggestions_slug($parms = array()) {
    $slug = $this->extract_value('slug', $parms);
    return $this->call('users/suggestions/'.$slug, 'GET', $parms);
  }

/**
 * @param array() $parms
 */
  public function profile_image($parms = array()) {
    $name = $this->extract_value('screen_name', $parms);
    return $this->call('users/profile_image/'.$name, 'GET', $parms, false);
  }

/**
 * @param array() $parms
 */
  public function friends($parms = array()) {
    return $this->call('statuses/friends', 'GET', $parms);
  }

/**
 * @param array() $parms
 */
  public function followers($parms = array()) {
    return $this->call('statuses/followers', 'GET', $parms);
  }

}

/**
 * @package Service\Twitter
 */
class Service_Twitter_ListsService extends Service_Twitter_Service {

/**
 * @param  $parms
 */
  protected function extract_user(&$parms, $name = 'user') {
    if (isset($parms[$name])) {
      $user = $parms[$name];
      unset($parms[$name]);
    } else
      $user = $this->client->token['screen_name'];
    return $user;
  }



/**
 * @param  $parms
 */
  public function create($parms) {
    if (is_string($parms)) $parms = array('name' => $parms);
    $user = $this->extract_user($parms);
    return $this->call($user.'/lists', 'POST', $parms);
  }

/**
 * @param  $parms
 */
  public function all($parms = array()) {
    $user = $this->extract_user($parms);
    return $this->call($user.'/lists', 'GET', $parms);
  }

/**
 * @param  $parms
 */
  public function get($parms) {
    $id =  $this->extract_value('id', $parms);
    $user = $this->extract_user($parms);
    return $this->call($user.'/lists/'.$id, 'GET', $parms);
  }

/**
 * @param  $parms
 */
  public function update($parms) {
    $id =  $this->extract_value('id', $parms);
    $user = $this->extract_user($parms);
    return $this->call($user.'/lists/'.$id, 'POST', $parms);
  }

/**
 * @param  $parms
 */
  public function delete($parms) {
    $id =  $this->extract_value('id', $parms);
    $user = $this->extract_user($parms);
    $parms['_method'] = 'DELETE';
    return $this->call($user.'/lists/'.$id, 'POST', $parms);
  }

/**
 * @param  $parms
 */
  public function statuses($parms) {
    $id =  $this->extract_value('id', $parms);
    $user = $this->extract_user($parms);
    return $this->call($user.'/lists/'.$id.'/statuses', 'GET', $parms);
  }

/**
 * @param  $parms
 */
  public function memberships($parms = array()) {
    $user = $this->extract_user($parms);
    return $this->call($user.'/lists/memberships', 'GET', $parms);
  }

/**
 * @param  $parms
 */
  public function subscriptions($parms = array()) {
    $user = $this->extract_user($parms);
    return $this->call($user.'/lists/subscriptions', 'GET', $parms);
  }

/**
 * @param  $parms
 */
  public function members($parms) {
    $id =  $this->extract_value('list_id', $parms);
    $user = $this->extract_user($parms);
    return $this->call($user.'/'.$id.'/members', 'GET', $parms);
  }

/**
 * @param  $parms
 */
  public function associate_member($parms) {
    $id =  $this->extract_value('list_id', $parms);
    $user = $this->extract_user($parms);
    return $this->call($user.'/'.$id.'/members', 'POST', $parms);
  }

/**
 * @param  $parms
 */
  public function dissociate_member($parms) {
    $id =  $this->extract_value('list_id', $parms);
    $user = $this->extract_user($parms);
    $parms['_method'] = 'DELETE';
    return $this->call($user.'/'.$id.'/members', 'POST', $parms);
  }

/**
 * @param  $parms
 */
  public function check_member($parms) {
    $list_id =  $this->extract_value('list_id', $parms);
    $id =  $this->extract_value('id', $parms);
    $user = $this->extract_user($parms);
    return $this->call($user.'/'.$list_id.'/members/'.$id, 'GET', $parms);
  }

/**
 * @param  $parms
 */
  public function associate_members($parms) {
    $id =  $this->extract_value('list_id', $parms);
    $user = $this->extract_user($parms);
    return $this->call($user.'/'.$id.'/create_all', 'POST', $parms);
  }

/**
 * @param  $parms
 */
  public function subscribers($parms) {
    $id =  $this->extract_value('list_id', $parms);
    $user = $this->extract_user($parms);
    return $this->call($user.'/'.$id.'/subscribers', 'GET', $parms);
  }

/**
 * @param  $parms
 */
  public function subscribe($parms) {
    $id =  $this->extract_value('list_id', $parms);
    $user = $this->extract_value('user', $parms);
    return $this->call($user.'/'.$id.'/subscribers', 'POST', $parms);
  }

/**
 * @param  $parms
 */
  public function unsubscribe($parms) {
    $id =  $this->extract_value('list_id', $parms);
    $user = $this->extract_value('user', $parms);
    $parms['_method'] = 'DELETE';
    return $this->call($user.'/'.$id.'/subscribers', 'POST', $parms);
  }

/**
 * @param  $parms
 */
  public function check_subscriber($parms) {
    $list_id =  $this->extract_value('list_id', $parms);
    $user =  $this->extract_value('user', $parms);
    $id = $this->extract_user($parms, 'id');
    return $this->call($user.'/'.$list_id.'/subscribers/'.$id, 'GET', $parms);
  }

}

/**
 * @package Service\Twitter
 */
class Service_Twitter_FriendshipsService extends Service_Twitter_Service {

/**
 * @param  $parms
 */
  public function create($parms = array()) {
    if (is_string($parms)) $parms = array('screen_name' => $parms);
    if (is_int($parms)) $parms = array('user_id' => $parms);
    return $this->call('friendships/create', 'POST', $parms);
  }

/**
 * @param  $parms
 */
  public function destroy($parms = array()) {
    if (is_string($parms)) $parms = array('screen_name' => $parms);
    if (is_int($parms)) $parms = array('user_id' => $parms);
    return $this->call('friendships/destroy', 'POST', $parms);
  }

/**
 * @param  $parms
 */
  public function exists($who, $whom) {
    return $this->call('friendships/exists', 'GET',
      array('user_a' => $who, 'user_b' => $whom));
  }

/**
 * @param  $parms
 */
  public function show($who, $whom) {
    $parms = array();
    if (is_string($who)) $parms['source_screen_name'] = $who;
    if (is_string($whom)) $parms['target_screen_name'] = $whom;
    if (is_int($who)) $parms['source_id'] = $who;
    if (is_int($whom)) $parms['target_id'] = $whom;
    return $this->call('friendships/show', 'GET', $parms);
  }

/**
 * @param  $parms
 */
  public function incoming($parms = array()) {
    return $this->call('friendships/incoming', 'GET', $parms);
  }

/**
 * @param  $parms
 */
  public function outgoing($parms = array()) {
    return $this->call('friendships/outgoing', 'GET', $parms);
  }

}

