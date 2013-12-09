<?php
/**
 * @package Service\Yandex\Market
 */


Core::load('Net.HTTP', 'Net.Agents.HTTP');

class Service_Yandex_Market implements Core_ModuleInterface {
	const VERSION = '0.1.0';

	static protected $options = array(
		'format' => 'json',
		'base_url' => 'https://api.partner.market.yandex.ru',
		'version' => '1'
	);
	
	static private $api;
	
	static public function initialize(array $options = array()) {
		self::$api = array();
		return self::options($options);
	}
	
	static public function options(array $options = array()) {
		return self::$options = array_merge(self::$options, $options);
	}
	
	static public function option($name, $value = null) {
		if (is_null($value))
			return self::$options[$name];
		else
			return self::$options[$name] = $value;
	}
	
	static public function api(array $options = array(), $name = 'default') {
		if (isset(self::$api[$name])) return self::$api[$name];
		return self::$api[$name] =  new Service_Yandex_Market_API($options);
	}
	
	static public function connect(array $options = array()) {
		return self::api($options);
	}
	
	static public function compose_url($base, $version, $resurs, $format) {
		return "{$base}/v{$version}/$resurs.$format";
	}
}

class Service_Yandex_Market_API implements Core_CallInterface {
	
	protected $options = array();
	protected $base_request;
	protected $agent;
	protected $last_request;
	protected $last_response;
	
	public function __construct(array $options = array()) {
		$this->options = $options;
		$this->connect();
	}
	
	public function use_agent(Net_HTTP_AgentInterface $agent) {
		$this->agent = $agent;
		return $this;
	}
	
	protected function connect() {
		$o = $this->options;
		$auth_headers = array('Authorization' =>
			"OAuth oauth_token=\"{$o['token']}\", oauth_client_id=\"{$o['application_id']}\", oauth_login=\"{$o['login']}\"");
		$this->base_request = Net_HTTP::Request()->headers($auth_headers);
	}
	
	public function call($resurs, array $parms = array()) {
		$format = isset($parms['format']) ? $parms['format'] : Service_Yandex_Market::option('format');
		$version = isset($parms['version']) ? $parms['version'] : Service_Yandex_Market::option('version');
		$method = isset($parms['method']) ? $parms['method'] : 'get';
		$url = Service_Yandex_Market::compose_url(Service_Yandex_Market::option('base_url'), $version, $resurs, $format);
		$request = clone $this->base_request;
		$request->uri($url)->method($method);
		if (isset($parms['parms']))
			$rparms = $parms['parms'];
		else {
			foreach (array('format', 'version', 'method') as $name)
				unset($parms[$name]);
			$rparms = $parms;
		}
		if (!empty($rparms)) $request->parameters($rparms);
		$response = $this->pure_call($request);
		return $this->parse_response($response, $format);
	}
	
	public function pure_call(Net_HTTP_Request $request) {
		if (empty($this->agent)) $this->agent = Net_Agents_HTTP::Agent();
		$this->last_request = $request;
		$res = $this->agent->send($request);
		$this->last_response = $res;
		return $res;
	}
	
	protected function parse_response($response, $format) {
		if (!$response->status->is_success) return $response->body;
		switch($format) {
			case 'json':
				return json_decode($response->body);
			case 'xml':
				return new SimpleXMLElement($response->body);
			default:
				return $response->body;
		}
	}
	
	public function __call($method, $args) {
		return $this->call($method, $args);
	}
	
	public function __get($property) {
		if (property_exists($this, $property))
			return $this->$property;
	}
	
	public function __isset($property) {
		return property_exists($this, $property);
	}
	
	
}
