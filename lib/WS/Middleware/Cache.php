<?php
/**
 * WS.Middleware.Cache
 * 
 * Сервис кеширования
 * 
 * <p>Сервис выполняет двойную функцию. Во-первых, он создает экземпляр объекта кеширования и записывает его в объект окружения,
 * таким образом, все последующие сервисы в цепочке могут использовать этот объект. Во-вторых, сервис может кешировать полный отклик
 * для различных адресов, определяемых набором регулярных выражений, используя этот объект кеширования.</p>
 * <p>Если параметры сервиса не указаны явно при его создании, он пытается получить их из элемента окружения, соответствующего объекту, описывающему
 * конфигурацию приложения, </p><code>$env->config->cache</code>. Поэтому рекомендуется подключать этот сервис после сервиса конфигурирования, WS.Middleware.Config.
 * <p>Закешированные объекты отклика сохраняются в базе данных кеша под именами </p><code>ws.middleware.cache.pages:page_url</code>.
 * 
 * @package WS\Middleware\Cache
 * @version 0.2.2
 */
Core::load('Cache', 'WS');

/**
 * Класс модуля
 * 
 * @package WS\Middleware\Cache
 */
class WS_Middleware_Cache implements Core_ModuleInterface {

  const VERSION = '0.2.2';


/**
 * Создает объект класса WS.Middleware.Cache.Service
 * 
 * @param WS_ServiceInterface $application
 * @param string $dsn
 * @param array() $urls
 * @return WS_Middleware_Cache_Service
 */
  static public function Service(WS_ServiceInterface  $application) {
    $args = func_get_args();
    return Core::amake('WS.Middleware.Cache.Service', $args);
  }

}


/**
 * Кеширующий сервис
 * 
 * @package WS\Middleware\Cache
 */
class WS_Middleware_Cache_Service extends WS_MiddlewareService {

  protected $dsn;
  protected $urls = array();
  protected $timeout;
  protected $tagged = null;


/**
 * Конструктор
 * 
 * @param WS_ServiceInterface $application
 * @param string $dsn
 * @param array() $urls
 */
  public function __construct(WS_ServiceInterface $application) {
    parent::__construct($application);
    $args = func_get_args();
    foreach (array_slice($args, 1) as $arg)
      switch (true) {
        case is_bool($arg):
          $this->tagged = $arg;
          break;
        case is_array($arg):
          $this->urls = $arg;
          break;
        case is_int($arg):
          $this->timeout = $arg;
          break;
        case is_string($arg):
          $this->dsn = $arg;
          break;
      }
  }



/**
 * Выполняет обработку запроса
 * 
 * @param WS_Environment $env
 * @return mixed
 */
//  TODO: поддержка нескольких доменов
//  TODO: вынести 'ws.middlweware.cache.pages:' в опции модуля

//TODO: default timeout !!!!!
  public function run(WS_Environment $env) {
    $dsn = $this->dsn ? $this->dsn : $env->config->cache->dsn;
    if (empty($dsn)) $dsn = 'dummy://';
    $timeout = $this->timeout ? $this->timeout : (isset($env->config->cache->timeout)? $env->config->cache->timeout : null);
    $env->cache = Cache::connect($dsn, $timeout);
    $tagged = !is_null($this->tagged) ? $this->tagged : (isset($env->config->cache->tagged) ? $env->config->cache->tagged : !$env->cache->is_support_nesting());
    if ($tagged) {
      Core::load('Cache.Tagged');
      $env->cache = Cache_Tagged::Client($env->cache);
    }
    $response = null;

    foreach ($this->urls as $regexp => $timeout) {
      if (preg_match($regexp, $env->request->path)) {
        if (($response = $env->cache->get('ws.middlweware.cache.pages:'.$env->request->url)) === null) {
          $response = $this->application->run($env);
          $env->cache->set('ws.middleware.cache.pages:'.$env->request->url, $response, $timeout);
        }
        break;
      }
    }
    return $response ? $response : $this->application->run($env);
  }

}

