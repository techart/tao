<?php
/**
 * Service.Recaptcha
 *
 * @package Service\Recaptcha
 * @version 0.3.0
 */
Core::load('Net.HTTP');

/**
 * @package Service\Recaptcha
 */
class Service_Recaptcha implements Core_ModuleInterface
{

	const VERSION = '0.3.0';

	const URL = 'http://www.google.com/recaptcha/api';

	/**
	 * @param string                  $pubkey
	 * @param string                  $privkey
	 * @param Net_HTTP_AgentInterface $agent
	 *
	 * @return Service_Recaptch_Client
	 */
	static public function Client($pubkey, $privkey, $agent = null)
	{
		return new Service_Recaptcha_Client($pubkey, $privkey, $agent);
	}

}

/**
 * @package Service\Recaptcha
 */
class Service_Recaptcha_Client
	implements Core_PropertyAccessInterface,
	Core_StringifyInterface
{

	protected $pubkey;
	protected $privkey;

	protected $messages = array();
	protected $error = null;

	protected $agent;

	/**
	 * @param string                  $pubkey
	 * @param string                  $privkey
	 * @param Net_HTTP_AgentInterface $agent
	 */
	public function __construct($pubkey, $privkey, $agent = null)
	{
		$this->pubkey = $pubkey;
		$this->privkey = $privkey;
		$this->agent($agent ? $agent : Net_HTTP::Agent());
	}

	/**
	 * @param array $values
	 *
	 * @return Service_Recaptcha_Client
	 */
	public function messages(array $values)
	{
		$this->messages = $values;
		return $this;
	}

	/**
	 * @param Net_HTTP_Request $r
	 *
	 * @return boolean
	 */
	public function is_valid(Net_HTTP_Request $r)
	{
		$this->error = false;

		$response =
			$this->agent->
				send(
					Net_HTTP::Request(Service_Recaptcha::URL . '/verify')->
						method(Net_HTTP::POST)->
						parameters(array(
								'privatekey' => $this->privkey,
								'remoteip' => $r->meta['REMOTE_ADDR'],
								'challenge' => $r['recaptcha_challenge_field'],
								'response' => $r['recaptcha_response_field'])
						)
				);

		if ($response->status->is_success) {
			$lines = explode("\n", $response->body);
			if (trim($lines[0]) == 'true') {
				return true;
			} else {
				$this->error = $lines[1];
				return false;
			}
		} else {
			$this->error = 'Unknown error';
			return false;
		}
	}

	/**
	 * @return string
	 */
	public function html()
	{
		$key = urlencode($this->pubkey);
		$rror = $this->error ? '&amp;error=' . $this->error : '';
		return
			'<script type="text/javascript" src="' . Service_Recaptcha::URL . '/challenge?k=' . $key . $error . '"></script>' .
			'<noscript>' .
			'<iframe src="' . Service_Recaptcha::URL . '/noscript?k=' . $key . $error . '" height="300" width="500" frameborder="0"></iframe><br>' .
			'<textarea name="recaptcha_challenge_field" rows="3" cols="40">' .
			'</textarea>' .
			'<input type="hidden" name="recaptcha_response_field" value="manual_challenge">' .
			'</noscript>';
	}

	/**
	 * @return string
	 */
	public function as_string()
	{
		return $this->html();
	}

	/**
	 * @return string
	 */
	public function __toString()
	{
		return $this->html();
	}

	/**
	 * @param string $property
	 *
	 * @return mixed
	 */
	public function __get($property)
	{
		switch ($property) {
			case 'error':
				return $this->$property;
			case 'message':
				return $this->get_message();
			default:
				throw new Core_MissingPropertyException($property);
		}
	}

	/**
	 * @param string $property
	 * @param        $value
	 */
	public function __set($property, $value)
	{
		throw new Core_ReadOnlyObjectException($this);
	}

	/**
	 * @param string $property
	 *
	 * @return boolean
	 */
	public function __isset($property)
	{
		switch ($property) {
			case 'error':
			case 'message':
				return isset($this->error);
			default:
				return false;
		}
	}

	/**
	 * @param string $property
	 */
	public function __unset($property)
	{
		throw new Core_ReadOnlyObjectException($this);
	}

	/**
	 * @return string
	 */
	protected function get_message()
	{
		return ($this->error && isset($this->messages[$this->error])) ?
			$this->messages[$this->error] :
			(is_null($this->error) ? '' : $this->error);
	}

	/**
	 * @return Service_Recaptcha_Client
	 */
	protected function agent(Net_HTTP_AgentInterface $agent)
	{
		$this->agent = $agent;
		return $this;
	}

}
