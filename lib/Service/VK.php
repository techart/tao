<?php
/**
 * Классы для работиы с API vk.com
 *
 * @author   Svistunov <svistunov@techart.ru>
 *
 * @version  0.1.0
 *
 * @package  Service\VK
 *
 */

Core::load('Net.HTTP');

/**
 * Класс модуля
 *
 * @package Service\VK
 */
class Service_VK implements Core_ModuleInterface
{

	/**
	 * Версия модуля
	 */
	const VERSION = '0.1.0';

	/**
	 * Набор опций модуля
	 *
	 * @var array
	 */
	protected static $options = array(
		'base_url' => 'https://api.vk.com/method/'
	);

	/**
	 * Инициализация модуля
	 *
	 * @param  array $options массив опций
	 */
	public static function initialize(array $options = array())
	{
		self::options($options);
	}

	/**
	 * Установка опций модуля
	 *
	 * @param  array $options массив опций
	 */
	public static function options(array $options = array())
	{
		self::$options = array_replace(self::$options, $options);
	}

	/**
	 * Чтение у установка опции модуля
	 *
	 * @param  string $name  название опции
	 * @param  mixed  $value значение опции
	 *
	 * @return mixed        значение опции при чтении, void при записи
	 */
	public static function option($name, $value = null)
	{
		if (is_null($value)) {
			return self::$options[$name];
		} else {
			self::options(array($name => $value));
		}
	}

	/**
	 * Фабричный метод для содания Service_VK_API
	 *
	 * @param  string                       $access_token токен
	 * @param  Net_HTTP_AgentInterface|null $agent        агент, который будет использоваться при запросах
	 *
	 * @return Service_VK_API
	 */
	public static function api($access_token, $agent = null)
	{
		return new Service_VK_API($access_token, $agent);
	}

}

/**
 * Класс для общения с API vk.com
 *
 * @package Service\VK
 */
class Service_VK_API
{

	/**
	 * токен доступа
	 *
	 * @var string
	 */
	protected $access_token;

	/**
	 * Базовой запрос
	 *
	 * @var Net_HTTP_Request
	 */
	protected $request;

	/**
	 * Конструктор
	 *
	 * @param string                       $access_token токен доступа
	 * @param Net_HTTP_AgentInterface|null $agent        агент, который будет использоваться при запросах
	 */
	public function __construct($access_token, $agent = null)
	{
		$this->access_token = $access_token;
		$this->agent = $agent instanceof Net_HTTP_AgentInterface ? $agent : Net_HTTP::agent();
		$this->request = Net_HTTP::Request(Service_VK::option('base_url'))->query_parameters(array('access_token' => $this->access_token));
	}

	/**
	 * Запрос к API vk.com
	 *
	 * @param  string $method_name метод API
	 * @param  string $http_method HTTP метод
	 * @param  array  $parms       Параметры API метода
	 *
	 * @return Net_HTTP_Response              Ответ сервера API vk.com
	 */
	public function call($method_name, $http_method = 'GET', $parms = array())
	{
		$request = clone $this->request;
		$request->method($http_method)->path = $request->path . $method_name;
		$request->query_parameters($parms);
		$response = $this->agent->send($request);
		return $response;
	}

	/**
	 * Публикация с изображением на стене пользователя
	 *
	 * @param  string $file  путь к изображению
	 * @param  array  $parms массив параметров
	 *
	 * @return Net_HTTP_Response        ответ от сервера API vk.com
	 */
	public function wall_post_img($file, $parms = array())
	{
		$data = json_decode($this->call('photos.getWallUploadServer')->body, true);
		$upload_url = $data['response']['upload_url'];
		$upload = Net_HTTP::Request($upload_url)->method('POST');
		$upload->body(array('photo' => '@' . $file));
		$photo = json_decode(Net_HTTP::Agent()->send($upload)->body);
		$d = json_decode($photo->photo);
		$attach = json_decode($this->call('photos.saveWallPhoto', 'GET',
				array(
					'server' => $photo->server,
					'photo' => $photo->photo,
					'hash' => $photo->hash
				)
			)->body, true
		);
		//can be uid gid
		$img_id = $attach['response'][0]['id'];
		$parms['attachments'] = $img_id;
		$res = $this->call('wall.post', 'POST', $parms);
		return $res;
	}

}
